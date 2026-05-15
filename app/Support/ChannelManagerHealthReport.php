<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChannelManagerHealthReport
{
    /**
     * @return array<string,mixed>
     */
    public static function build(int $staleMinutes = 30): array
    {
        $empty = [
            'available' => false,
            'summary' => [
                'accounts_total' => 0,
                'active_accounts' => 0,
                'action_required_accounts' => 0,
                'inbound_failed' => 0,
                'outbound_queued' => 0,
                'outbound_retrying' => 0,
                'dead_letter_events' => 0,
                'stale_accounts' => 0,
            ],
            'status' => 'unavailable',
            'issues' => ['Channel manager tables are not available.'],
            'stale_threshold_minutes' => $staleMinutes,
            'top_failed_accounts' => collect(),
        ];

        if (!Schema::hasTable('vendor_channel_accounts') || !Schema::hasTable('vendor_channel_events')) {
            return $empty;
        }

        $accounts = DB::table('vendor_channel_accounts')
            ->orderByDesc('updated_at')
            ->get();

        $events = DB::table('vendor_channel_events')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $staleThreshold = Carbon::now()->subMinutes(max(1, $staleMinutes));
        $staleAccounts = $accounts->filter(static function ($account) use ($staleThreshold): bool {
            $lastSync = trim((string) ($account->last_sync_at ?? ''));
            if ($lastSync === '') {
                return true;
            }

            try {
                return Carbon::parse($lastSync)->lt($staleThreshold);
            } catch (\Throwable $exception) {
                return true;
            }
        });

        $topFailedAccounts = self::buildTopFailedAccounts($accounts, $events);

        $summary = [
            'accounts_total' => (int) $accounts->count(),
            'active_accounts' => (int) $accounts->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['active', 'connected'], true))->count(),
            'action_required_accounts' => (int) $accounts->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['action_required', 'error', 'token_expired', 'disconnected'], true))->count(),
            'inbound_failed' => (int) $events->filter(static fn ($row): bool => strtolower(trim((string) ($row->direction ?? ''))) === 'inbound' && in_array(strtolower(trim((string) ($row->status ?? ''))), ['failed', 'error', 'dead_letter'], true))->count(),
            'outbound_queued' => (int) $events->filter(static fn ($row): bool => strtolower(trim((string) ($row->direction ?? ''))) === 'outbound' && strtolower(trim((string) ($row->status ?? ''))) === 'queued')->count(),
            'outbound_retrying' => (int) $events->filter(static fn ($row): bool => strtolower(trim((string) ($row->direction ?? ''))) === 'outbound' && strtolower(trim((string) ($row->status ?? ''))) === 'retrying')->count(),
            'dead_letter_events' => (int) $events->filter(static fn ($row): bool => strtolower(trim((string) ($row->status ?? ''))) === 'dead_letter')->count(),
            'stale_accounts' => (int) $staleAccounts->count(),
        ];

        $issues = [];
        if ($summary['accounts_total'] === 0) {
            $issues[] = 'No channel accounts connected.';
        }
        if ($summary['action_required_accounts'] > 0) {
            $issues[] = $summary['action_required_accounts'] . ' channel accounts require action.';
        }
        if ($summary['inbound_failed'] > 0) {
            $issues[] = $summary['inbound_failed'] . ' inbound channel events have failed.';
        }
        if ($summary['outbound_retrying'] > 0) {
            $issues[] = $summary['outbound_retrying'] . ' outbound sync events are retrying.';
        }
        if ($summary['dead_letter_events'] > 0) {
            $issues[] = $summary['dead_letter_events'] . ' channel events are in dead-letter state.';
        }
        if ($summary['stale_accounts'] > 0) {
            $issues[] = $summary['stale_accounts'] . ' channel accounts have stale or missing sync timestamps.';
        }

        $status = 'healthy';
        if ($summary['dead_letter_events'] > 0 || $summary['action_required_accounts'] > 0 || $summary['inbound_failed'] > 0) {
            $status = 'action_required';
        } elseif ($summary['outbound_queued'] > 0 || $summary['outbound_retrying'] > 0 || $summary['stale_accounts'] > 0) {
            $status = 'degraded';
        }

        return [
            'available' => true,
            'status' => $status,
            'summary' => $summary,
            'issues' => $issues,
            'stale_threshold_minutes' => $staleMinutes,
            'top_failed_accounts' => $topFailedAccounts,
        ];
    }

    /**
     * @param Collection<int,object> $accounts
     * @param Collection<int,object> $events
     * @return Collection<int,array<string,mixed>>
     */
    private static function buildTopFailedAccounts(Collection $accounts, Collection $events): Collection
    {
        return $accounts
            ->map(static function ($account) use ($events): array {
                $accountId = (int) ($account->id ?? 0);
                $failed = $events->filter(static fn ($event): bool => (int) ($event->vendor_channel_account_id ?? 0) === $accountId && in_array(strtolower(trim((string) ($event->status ?? ''))), ['failed', 'error', 'dead_letter', 'retrying'], true));

                return [
                    'account_id' => $accountId,
                    'channel_code' => strtolower(trim((string) ($account->channel_code ?? 'unknown'))),
                    'account_reference' => trim((string) ($account->account_reference ?? 'Not set')),
                    'status' => strtolower(trim((string) ($account->status ?? 'disconnected'))),
                    'failure_count' => (int) $failed->count(),
                    'last_error' => trim((string) ($account->last_error ?? '')),
                    'last_sync_at' => trim((string) ($account->last_sync_at ?? '')),
                ];
            })
            ->filter(static fn (array $row): bool => $row['failure_count'] > 0 || $row['status'] === 'action_required')
            ->sortByDesc('failure_count')
            ->take(10)
            ->values();
    }
}
