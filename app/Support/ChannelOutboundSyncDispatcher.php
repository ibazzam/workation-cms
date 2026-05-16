<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ChannelOutboundSyncDispatcher
{
    /**
     * Process queued outbound inventory sync events.
     *
     * @return array{processed:int,failed:int,retrying:int,dead_letter:int,skipped:int}
     */
    public static function dispatchQueued(int $limit = 50, int $maxRetries = 5, bool $dryRun = false, ?int $eventId = null): array
    {
        $summary = [
            'processed' => 0,
            'failed' => 0,
            'retrying' => 0,
            'dead_letter' => 0,
            'skipped' => 0,
        ];

        if (!Schema::hasTable('vendor_channel_events') || !Schema::hasTable('vendor_channel_accounts')) {
            return $summary;
        }

        $eventsQuery = DB::table('vendor_channel_events as e')
            ->join('vendor_channel_accounts as a', 'a.id', '=', 'e.vendor_channel_account_id')
            ->where('e.direction', 'outbound')
            ->whereIn('e.status', ['queued', 'retrying'])
            ->orderBy('e.created_at');

        if (($eventId ?? 0) > 0) {
            $eventsQuery->where('e.id', (int) $eventId);
        }

        $events = $eventsQuery
            ->limit(max(1, $limit))
            ->get([
                'e.id',
                'e.vendor_channel_account_id',
                'e.event_type',
                'e.idempotency_key',
                'e.retry_count',
                'e.payload',
                'a.channel_code',
                'a.status as account_status',
                'a.access_token_encrypted',
                'a.connection_meta',
            ]);

        foreach ($events as $event) {
            $eventId = (int) ($event->id ?? 0);
            if ($eventId <= 0) {
                $summary['skipped']++;
                continue;
            }

            $payload = [];
            if (is_string($event->payload ?? null) && trim((string) $event->payload) !== '') {
                $decoded = json_decode((string) $event->payload, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            if ($payload === []) {
                DB::table('vendor_channel_events')->where('id', $eventId)->update([
                    'status' => 'dead_letter',
                    'error_message' => 'Outbound payload is empty.',
                    'updated_at' => now(),
                ]);
                $summary['dead_letter']++;
                continue;
            }

            if ($dryRun) {
                $summary['skipped']++;
                continue;
            }

            DB::table('vendor_channel_events')->where('id', $eventId)->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

            $result = self::pushToChannel($event, $payload);

            if (($result['ok'] ?? false) === true) {
                DB::table('vendor_channel_events')->where('id', $eventId)->update([
                    'status' => 'processed',
                    'error_message' => null,
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('vendor_channel_accounts')
                    ->where('id', (int) ($event->vendor_channel_account_id ?? 0))
                    ->update([
                        'status' => 'active',
                        'last_sync_at' => now(),
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);

                $summary['processed']++;
                continue;
            }

            $retryCount = max(0, (int) ($event->retry_count ?? 0)) + 1;
            $nextStatus = $retryCount >= max(1, $maxRetries) ? 'dead_letter' : 'retrying';

            DB::table('vendor_channel_events')->where('id', $eventId)->update([
                'status' => $nextStatus,
                'retry_count' => $retryCount,
                'error_message' => (string) ($result['message'] ?? 'Outbound sync failed.'),
                'updated_at' => now(),
            ]);

            DB::table('vendor_channel_accounts')
                ->where('id', (int) ($event->vendor_channel_account_id ?? 0))
                ->update([
                    'status' => 'action_required',
                    'last_error' => (string) ($result['message'] ?? 'Outbound sync failed.'),
                    'updated_at' => now(),
                ]);

            if ($nextStatus === 'dead_letter') {
                $summary['dead_letter']++;
            } else {
                $summary['retrying']++;
            }
            $summary['failed']++;
        }

        return $summary;
    }

    /**
     * @param object $event
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string}
     */
    private static function pushToChannel(object $event, array $payload): array
    {
        $connectionMeta = [];
        if (is_string($event->connection_meta ?? null) && trim((string) $event->connection_meta) !== '') {
            $decoded = json_decode((string) $event->connection_meta, true);
            if (is_array($decoded)) {
                $connectionMeta = $decoded;
            }
        }

        $endpoint = trim((string) ($connectionMeta['inventory_sync_url'] ?? ''));
        if ($endpoint === '') {
            $apiBase = trim((string) ($connectionMeta['api_base'] ?? ''));
            if ($apiBase !== '') {
                $endpoint = rtrim($apiBase, '/') . '/inventory/sync';
            }
        }

        if ($endpoint === '') {
            return [
                'ok' => false,
                'message' => 'Missing inventory sync endpoint in channel account connection_meta.',
            ];
        }

        $token = '';
        if (is_string($event->access_token_encrypted ?? null) && trim((string) $event->access_token_encrypted) !== '') {
            try {
                $token = (string) Crypt::decryptString((string) $event->access_token_encrypted);
            } catch (\Throwable $exception) {
                Log::warning('Channel token decryption failed for outbound sync.', [
                    'event_id' => (int) ($event->id ?? 0),
                    'channel_code' => (string) ($event->channel_code ?? ''),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $request = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Idempotency-Key' => (string) ($event->idempotency_key ?? ''),
                    'X-Channel-Event' => (string) ($event->event_type ?? 'inventory.sync'),
                ]);

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($endpoint, $payload);
            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Outbound sync sent successfully.'];
            }

            return [
                'ok' => false,
                'message' => 'Outbound endpoint returned HTTP ' . $response->status() . '.',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Outbound request exception: ' . $exception->getMessage(),
            ];
        }
    }
}