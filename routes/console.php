<?php

use App\Support\ChannelManagerHealthReport;
use App\Support\ChannelInventoryFanout;
use App\Support\ChannelOutboundSyncDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('channel:dispatch-outbound {--limit=50} {--max-retries=5} {--dry-run}', function () {
    $limit = max(1, (int) $this->option('limit'));
    $maxRetries = max(1, (int) $this->option('max-retries'));
    $dryRun = (bool) $this->option('dry-run');

    $summary = ChannelOutboundSyncDispatcher::dispatchQueued($limit, $maxRetries, $dryRun);

    $this->info('Outbound channel dispatch completed.');
    $this->line('Processed: ' . (int) ($summary['processed'] ?? 0));
    $this->line('Failed: ' . (int) ($summary['failed'] ?? 0));
    $this->line('Retrying: ' . (int) ($summary['retrying'] ?? 0));
    $this->line('Dead letter: ' . (int) ($summary['dead_letter'] ?? 0));
    $this->line('Skipped: ' . (int) ($summary['skipped'] ?? 0));
})->purpose('Dispatch queued outbound channel inventory sync events');

Artisan::command('channel:fanout-inventory {--minutes=10} {--limit=200} {--dry-run}', function () {
    $minutes = max(1, (int) $this->option('minutes'));
    $limit = max(1, (int) $this->option('limit'));
    $dryRun = (bool) $this->option('dry-run');

    $summary = ChannelInventoryFanout::queueFromRecentInternalInventory($minutes, $limit, $dryRun);

    $this->info('Internal inventory fanout completed.');
    $this->line('Scanned slices: ' . (int) ($summary['scanned'] ?? 0));
    $this->line('Target deliveries: ' . (int) ($summary['targets'] ?? 0));
    $this->line('Queued events: ' . (int) ($summary['queued'] ?? 0));
    $this->line('Errors: ' . (int) ($summary['errors'] ?? 0));
})->purpose('Queue outbound channel sync events from recent internal inventory updates');

Artisan::command('channel:health {--stale-minutes=30}', function () {
    $staleMinutes = max(1, (int) $this->option('stale-minutes'));
    $report = ChannelManagerHealthReport::build($staleMinutes);

    $this->info('Channel manager health report');
    $this->line('Status: ' . strtoupper((string) ($report['status'] ?? 'unknown')));

    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
    foreach ($summary as $key => $value) {
        $label = ucwords(str_replace('_', ' ', (string) $key));
        $this->line($label . ': ' . $value);
    }

    $issues = $report['issues'] ?? [];
    if (is_array($issues) && $issues !== []) {
        $this->newLine();
        $this->warn('Issues');
        foreach ($issues as $issue) {
            $this->line('- ' . $issue);
        }
    }

    $topFailedAccounts = $report['top_failed_accounts'] ?? collect();
    if ($topFailedAccounts instanceof \Illuminate\Support\Collection && $topFailedAccounts->isNotEmpty()) {
        $this->newLine();
        $this->warn('Top Failed Accounts');
        foreach ($topFailedAccounts as $row) {
            $this->line(sprintf(
                '%s | %s | failures=%d | status=%s',
                strtoupper((string) ($row['channel_code'] ?? 'unknown')),
                (string) ($row['account_reference'] ?? 'Not set'),
                (int) ($row['failure_count'] ?? 0),
                strtoupper((string) ($row['status'] ?? 'unknown')),
            ));
        }
    }
})->purpose('Show channel manager operational health summary');

Artisan::command('channel:account:create
    {vendor-user-id : Vendor user ID from users table}
    {channel : Channel code (booking, agoda, airbnb)}
    {--property-id= : Optional vendor property id scope}
    {--account-reference= : Optional account label/reference}
    {--status=connected : Account status (connected, active, disconnected, action_required)}
    {--webhook-secret= : Webhook secret stored in connection_meta}
    {--inventory-sync-url= : Outbound inventory sync endpoint URL}
    {--api-base= : API base URL used to derive /inventory/sync endpoint}
    {--access-token= : Plain access token (will be encrypted before save)}
    {--connected-at= : Optional ISO datetime for connected_at}
    {--upsert : Update existing row when one matches vendor/property/channel}
    {--prompt-secrets : Prompt for webhook secret and access token interactively}', function () {
    if (!Schema::hasTable('vendor_channel_accounts')) {
        $this->error('vendor_channel_accounts table is missing. Run migrations first.');
        return self::FAILURE;
    }

    if (!Schema::hasTable('users')) {
        $this->error('users table is missing.');
        return self::FAILURE;
    }

    $vendorUserId = max(0, (int) $this->argument('vendor-user-id'));
    if ($vendorUserId <= 0 || !DB::table('users')->where('id', $vendorUserId)->exists()) {
        $this->error('Invalid vendor user id.');
        return self::FAILURE;
    }

    $channelInput = strtolower(trim((string) $this->argument('channel')));
    $channelAliases = [
        'booking.com' => 'booking',
        'bookingcom' => 'booking',
        'booking' => 'booking',
        'agoda' => 'agoda',
        'airbnb' => 'airbnb',
    ];
    $channelCode = $channelAliases[$channelInput] ?? $channelInput;
    if ($channelCode === '') {
        $this->error('Channel code is required.');
        return self::FAILURE;
    }

    $vendorPropertyId = $this->option('property-id');
    $vendorPropertyId = ($vendorPropertyId === null || trim((string) $vendorPropertyId) === '')
        ? null
        : max(0, (int) $vendorPropertyId);
    if ($vendorPropertyId !== null && $vendorPropertyId <= 0) {
        $vendorPropertyId = null;
    }

    $status = strtolower(trim((string) $this->option('status')));
    if (!in_array($status, ['connected', 'active', 'disconnected', 'action_required'], true)) {
        $this->error('Invalid status. Use connected, active, disconnected, or action_required.');
        return self::FAILURE;
    }

    $accountReference = trim((string) ($this->option('account-reference') ?? ''));
    $webhookSecret = trim((string) ($this->option('webhook-secret') ?? ''));
    $inventorySyncUrl = trim((string) ($this->option('inventory-sync-url') ?? ''));
    $apiBase = trim((string) ($this->option('api-base') ?? ''));
    $accessToken = trim((string) ($this->option('access-token') ?? ''));

    if ((bool) $this->option('prompt-secrets')) {
        if ($webhookSecret === '') {
            $webhookSecret = trim((string) $this->secret('Webhook secret (leave blank to skip):'));
        }
        if ($accessToken === '') {
            $accessToken = trim((string) $this->secret('Access token (leave blank to skip):'));
        }
    }

    if ($inventorySyncUrl !== '' && !filter_var($inventorySyncUrl, FILTER_VALIDATE_URL)) {
        $this->error('inventory-sync-url must be a valid URL.');
        return self::FAILURE;
    }
    if ($apiBase !== '' && !filter_var($apiBase, FILTER_VALIDATE_URL)) {
        $this->error('api-base must be a valid URL.');
        return self::FAILURE;
    }

    $connectedAt = null;
    $connectedAtRaw = trim((string) ($this->option('connected-at') ?? ''));
    if ($connectedAtRaw !== '') {
        try {
            $connectedAt = Carbon::parse($connectedAtRaw)->toDateTimeString();
        } catch (\Throwable $exception) {
            $this->error('connected-at must be a valid datetime value.');
            return self::FAILURE;
        }
    } elseif (in_array($status, ['connected', 'active'], true)) {
        $connectedAt = now()->toDateTimeString();
    }

    $existingQuery = DB::table('vendor_channel_accounts')
        ->where('vendor_user_id', $vendorUserId)
        ->where('channel_code', $channelCode);

    if ($vendorPropertyId === null) {
        $existingQuery->whereNull('vendor_property_id');
    } else {
        $existingQuery->where('vendor_property_id', $vendorPropertyId);
    }

    $existing = $existingQuery->first();
    if ($existing && !(bool) $this->option('upsert')) {
        $this->error('Matching account already exists. Use --upsert to update it.');
        return self::FAILURE;
    }

    $connectionMeta = [];
    if ($existing && is_string($existing->connection_meta ?? null) && trim((string) $existing->connection_meta) !== '') {
        $decoded = json_decode((string) $existing->connection_meta, true);
        if (is_array($decoded)) {
            $connectionMeta = $decoded;
        }
    }

    if ($webhookSecret !== '') {
        $connectionMeta['webhook_secret'] = $webhookSecret;
    }
    if ($inventorySyncUrl !== '') {
        $connectionMeta['inventory_sync_url'] = $inventorySyncUrl;
    }
    if ($apiBase !== '') {
        $connectionMeta['api_base'] = rtrim($apiBase, '/');
    }

    $accessTokenEncrypted = null;
    if ($accessToken !== '') {
        $accessTokenEncrypted = Crypt::encryptString($accessToken);
    } elseif ($existing && is_string($existing->access_token_encrypted ?? null) && trim((string) $existing->access_token_encrypted) !== '') {
        $accessTokenEncrypted = (string) $existing->access_token_encrypted;
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'channel_code' => $channelCode,
        'account_reference' => $accountReference !== '' ? $accountReference : null,
        'status' => $status,
        'access_token_encrypted' => $accessTokenEncrypted,
        'connection_meta' => $connectionMeta !== [] ? json_encode($connectionMeta, JSON_UNESCAPED_SLASHES) : null,
        'connected_at' => $connectedAt,
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('vendor_channel_accounts')
            ->where('id', (int) $existing->id)
            ->update($payload);

        $this->info('Channel account updated.');
        $this->line('Account ID: ' . (int) $existing->id);
        $this->line('Channel: ' . strtoupper($channelCode));
        $this->line('Vendor User ID: ' . $vendorUserId);
        $this->line('Property Scope: ' . ($vendorPropertyId !== null ? (string) $vendorPropertyId : 'none'));
        return self::SUCCESS;
    }

    $payload['created_at'] = now();
    $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId($payload);

    $this->info('Channel account created.');
    $this->line('Account ID: ' . $accountId);
    $this->line('Channel: ' . strtoupper($channelCode));
    $this->line('Vendor User ID: ' . $vendorUserId);
    $this->line('Property Scope: ' . ($vendorPropertyId !== null ? (string) $vendorPropertyId : 'none'));

    return self::SUCCESS;
})->purpose('Create or update a vendor OTA channel account with secure connection metadata');

Artisan::command('channel:health-alert
    {--stale-minutes=30}
    {--max-action-required=0}
    {--max-inbound-failed=0}
    {--max-outbound-retrying=10}
    {--max-dead-letter=0}
    {--max-stale-accounts=0}
    {--max-outbound-queued=250}
    {--fail-on-alert}', function () {
    $staleMinutes = max(1, (int) $this->option('stale-minutes'));
    $thresholds = [
        'action_required_accounts' => max(0, (int) $this->option('max-action-required')),
        'inbound_failed' => max(0, (int) $this->option('max-inbound-failed')),
        'outbound_retrying' => max(0, (int) $this->option('max-outbound-retrying')),
        'dead_letter_events' => max(0, (int) $this->option('max-dead-letter')),
        'stale_accounts' => max(0, (int) $this->option('max-stale-accounts')),
        'outbound_queued' => max(0, (int) $this->option('max-outbound-queued')),
    ];

    $report = ChannelManagerHealthReport::build($staleMinutes);
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];

    $alerts = [];
    foreach ($thresholds as $metric => $limit) {
        $value = max(0, (int) ($summary[$metric] ?? 0));
        if ($value > $limit) {
            $alerts[] = strtoupper($metric) . ' is ' . $value . ' (threshold ' . $limit . ')';
        }
    }

    $status = strtolower(trim((string) ($report['status'] ?? 'unavailable')));
    $isUnavailable = $status === 'unavailable';
    if ($isUnavailable) {
        $alerts[] = 'Channel manager is unavailable (tables or dependencies missing).';
    }

    if ($alerts === []) {
        $this->info('Channel health alert check passed.');
        $this->line('Status: ' . strtoupper((string) ($report['status'] ?? 'unknown')));
        return self::SUCCESS;
    }

    $alertMessage = 'Channel health alert triggered';
    Log::warning($alertMessage, [
        'status' => $report['status'] ?? 'unknown',
        'summary' => $summary,
        'issues' => $report['issues'] ?? [],
        'thresholds' => $thresholds,
        'alerts' => $alerts,
        'stale_minutes' => $staleMinutes,
    ]);

    $this->warn($alertMessage);
    foreach ($alerts as $line) {
        $this->line('- ' . $line);
    }

    if ((bool) $this->option('fail-on-alert')) {
        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Evaluate channel health against thresholds and emit operational alerts');

Schedule::command('channel:dispatch-outbound --limit=100 --max-retries=5')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-dispatch.log'));

Schedule::command('channel:fanout-inventory --minutes=10 --limit=200')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-fanout.log'));

Schedule::command('channel:health --stale-minutes=30')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-health.log'));

Schedule::command('channel:health-alert --stale-minutes=30 --max-action-required=0 --max-inbound-failed=0 --max-outbound-retrying=10 --max-dead-letter=0 --max-stale-accounts=0 --max-outbound-queued=250')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-alert.log'));
