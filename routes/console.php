<?php

use App\Support\ChannelManagerHealthReport;
use App\Support\ChannelOutboundSyncDispatcher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('channel:health --stale-minutes=30')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-health.log'));

Schedule::command('channel:health-alert --stale-minutes=30 --max-action-required=0 --max-inbound-failed=0 --max-outbound-retrying=10 --max-dead-letter=0 --max-stale-accounts=0 --max-outbound-queued=250')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/channel-alert.log'));
