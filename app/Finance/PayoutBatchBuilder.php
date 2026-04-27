<?php

declare(strict_types=1);

namespace App\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PayoutBatchBuilder – assembles and progresses payout batches.
 *
 * Batches are STRICTLY separated by source_medium + currency_band.
 * This ensures that MIB (MVR), BML (MVR), and Stripe (USD) payouts are
 * always tracked, submitted, and reconciled independently.
 *
 * Operational rules:
 *   MIB   → local MVR bank, fast settlement (same/next business day)
 *   BML   → local MVR bank, fast settlement (same/next business day)
 *   Stripe → foreign bank account, USD, delayed settlement (2–7 business days)
 *
 * SECURITY / PRIVACY:
 *   The source_medium column and the batch_ref prefix reveal which bank/gateway
 *   processed the funds.  This is INTERNAL ONLY and must NEVER appear in any
 *   vendor-facing view, export, or API response.
 *   Vendors see only: net_payout_amount, payout_currency, payout_status.
 */
final class PayoutBatchBuilder
{
    public function __construct(
        private readonly LedgerWriter $ledger,
    ) {}

    /**
     * Build payout batches for all eligible reservations up to and including $upToDate.
     *
     * "Eligible" means:
     *   - payment_status = 'paid'
     *   - booking status IN ('confirmed', 'completed')
     *   - payout_status IS NULL (not yet queued)
     *   - has vendor_payout_amount > 0
     *
     * Batches are created per source_medium + currency_band combination.
     * Returns a summary array of batch refs created.
     *
     * @return array<string, array{batch_ref: string, item_count: int, net_total: float, currency: string}>
     */
    public function buildBatchesForDate(Carbon $upToDate, int $actorUserId): array
    {
        if (!DB::getSchemaBuilder()->hasTable('vendor_reservations')) {
            return [];
        }

        // ── Fetch all eligible unpaid-out reservations ────────────────────────
        $eligible = DB::table('vendor_reservations as vr')
            ->leftJoin('users as vendor_users', 'vendor_users.id', '=', 'vr.vendor_user_id')
            ->where('vr.payment_status', 'paid')
            ->whereIn('vr.status', ['confirmed', 'completed'])
            ->whereNull('vr.payout_status')
            ->where('vr.vendor_payout_amount', '>', 0)
            ->where('vr.created_at', '<=', $upToDate->endOfDay())
            ->get([
                'vr.id',
                'vr.vendor_user_id',
                'vr.payment_gateway',
                'vr.payment_currency',
                'vr.payment_amount',
                'vr.commission_amount',
                'vr.commission_rate_percent',
                'vr.gateway_fee_amount',
                'vr.gateway_fee_rate_percent',
                'vr.vendor_payout_amount',
                'vendor_users.name as vendor_name',
            ]);

        if ($eligible->isEmpty()) {
            return [];
        }

        // ── Group by medium + currency band + vendor ──────────────────────────
        $groups = [];
        foreach ($eligible as $row) {
            $ctx = LedgerWriter::resolveSourceContext(
                (string) ($row->payment_gateway ?? 'stripe'),
                (string) ($row->payment_currency ?? 'USD'),
            );
            $key = $ctx['source_medium'] . '|' . $ctx['currency_band'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'source_medium' => $ctx['source_medium'],
                    'currency_band' => $ctx['currency_band'],
                    'currency'      => strtoupper((string) ($row->payment_currency ?? 'USD')),
                    'vendors'       => [],
                ];
            }
            $vendorId = (int) $row->vendor_user_id;
            if (!isset($groups[$key]['vendors'][$vendorId])) {
                $groups[$key]['vendors'][$vendorId] = [
                    'vendor_user_id'   => $vendorId,
                    'vendor_name'      => (string) ($row->vendor_name ?? ''),
                    'reservation_ids'  => [],
                    'gross_amount'     => 0.0,
                    'commission_amount' => 0.0,
                    'gateway_fee_amount' => 0.0,
                    'net_payout_amount' => 0.0,
                ];
            }
            $groups[$key]['vendors'][$vendorId]['reservation_ids'][] = (int) $row->id;
            $groups[$key]['vendors'][$vendorId]['gross_amount']       += (float) ($row->payment_amount ?? 0);
            $groups[$key]['vendors'][$vendorId]['commission_amount']  += (float) ($row->commission_amount ?? 0);
            $groups[$key]['vendors'][$vendorId]['gateway_fee_amount'] += (float) ($row->gateway_fee_amount ?? 0);
            $groups[$key]['vendors'][$vendorId]['net_payout_amount']  += (float) ($row->vendor_payout_amount ?? 0);
        }

        $batchDateStr = $upToDate->toDateString();
        $createdBatches = [];

        DB::transaction(function () use ($groups, $batchDateStr, $actorUserId, &$createdBatches): void {
            foreach ($groups as $groupKey => $group) {
                [$medium, $band] = explode('|', $groupKey);
                $batchRef = $this->generateBatchRef($medium, $band, $batchDateStr);

                $itemCount       = count($group['vendors']);
                $grossTotal      = 0.0;
                $commissionTotal = 0.0;
                $feeTotal        = 0.0;
                $netTotal        = 0.0;

                foreach ($group['vendors'] as $v) {
                    $grossTotal      += $v['gross_amount'];
                    $commissionTotal += $v['commission_amount'];
                    $feeTotal        += $v['gateway_fee_amount'];
                    $netTotal        += $v['net_payout_amount'];
                }

                $now = Carbon::now();

                // ── Create the batch ──────────────────────────────────────────────
                $batchId = DB::table('finance_payout_batches')->insertGetId([
                    'batch_ref'          => $batchRef,
                    'batch_date'         => $batchDateStr,
                    'source_medium'      => $medium,
                    'currency_band'      => $band,
                    'currency'           => $group['currency'],
                    'item_count'         => $itemCount,
                    'gross_amount'       => round($grossTotal, 4),
                    'commission_amount'  => round($commissionTotal, 4),
                    'gateway_fee_amount' => round($feeTotal, 4),
                    'net_payout_amount'  => round($netTotal, 4),
                    'status'             => 'queued',
                    'created_by_user_id' => $actorUserId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                // ── Create items and update reservations ──────────────────────────
                foreach ($group['vendors'] as $vendorData) {
                    $itemId = DB::table('finance_payout_batch_items')->insertGetId([
                        'batch_id'          => $batchId,
                        'vendor_user_id'    => $vendorData['vendor_user_id'],
                        'reservation_ids'   => json_encode($vendorData['reservation_ids']),
                        'gross_amount'      => round($vendorData['gross_amount'], 4),
                        'commission_amount' => round($vendorData['commission_amount'], 4),
                        'gateway_fee_amount' => round($vendorData['gateway_fee_amount'], 4),
                        'net_payout_amount' => round($vendorData['net_payout_amount'], 4),
                        'currency'          => $group['currency'],
                        'status'            => 'queued',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);

                    // ── Mark each reservation as payout_status = 'queued' ─────────
                    // NOTE: payout_source_medium written here but NEVER exposed to vendor.
                    DB::table('vendor_reservations')
                        ->whereIn('id', $vendorData['reservation_ids'])
                        ->update([
                            'payout_status'        => 'queued',
                            'payout_currency'      => $group['currency'],
                            'payout_batch_item_id' => $itemId,
                            'payout_source_medium' => $medium, // INTERNAL ONLY
                            'updated_at'           => $now,
                        ]);

                    // ── Write ledger event per reservation ────────────────────────
                    foreach ($vendorData['reservation_ids'] as $resId) {
                        $this->ledger->append([
                            'event_type'     => LedgerWriter::EVT_VENDOR_PAYOUT_QUEUED,
                            'reservation_id' => $resId,
                            'vendor_user_id' => $vendorData['vendor_user_id'],
                            'amount'         => (float) DB::table('vendor_reservations')
                                ->where('id', $resId)
                                ->value('vendor_payout_amount') ?? 0,
                            'currency'       => $group['currency'],
                            'source_medium'  => $medium,
                            'currency_band'  => $band,
                            'batch_id'       => $batchRef,
                            'actor_role'     => 'ADMIN_FINANCE',
                            'actor_user_id'  => $actorUserId,
                        ]);
                    }
                }

                $createdBatches[$groupKey] = [
                    'batch_ref'  => $batchRef,
                    'item_count' => $itemCount,
                    'net_total'  => round($netTotal, 2),
                    'currency'   => $group['currency'],
                ];
            }
        });

        return $createdBatches;
    }

    /**
     * Mark a batch as submitted to the bank/gateway (processing).
     */
    public function markBatchSent(int $batchId, string $bankReference, int $actorUserId): void
    {
        $now = Carbon::now();

        DB::table('finance_payout_batches')->where('id', $batchId)->update([
            'status'               => 'processing',
            'bank_reference'       => $bankReference,
            'submitted_at'         => $now,
            'updated_at'           => $now,
        ]);

        DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->where('status', 'queued')
            ->update(['status' => 'sent', 'sent_at' => $now, 'updated_at' => $now]);

        // Update reservations in this batch
        $itemIds = DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->pluck('id');

        DB::table('vendor_reservations')
            ->whereIn('payout_batch_item_id', $itemIds)
            ->where('payout_status', 'queued')
            ->update(['payout_status' => 'processing', 'updated_at' => $now]);

        // Write sent events via ledger
        $batch = DB::table('finance_payout_batches')->find($batchId);
        if ($batch) {
            $resIds = DB::table('vendor_reservations')
                ->whereIn('payout_batch_item_id', $itemIds)
                ->pluck('id');
            foreach ($resIds as $resId) {
                $this->ledger->append([
                    'event_type'       => LedgerWriter::EVT_VENDOR_PAYOUT_SENT,
                    'reservation_id'   => $resId,
                    'amount'           => 0,
                    'currency'         => (string) $batch->currency,
                    'source_medium'    => (string) $batch->source_medium,
                    'currency_band'    => (string) $batch->currency_band,
                    'batch_id'         => (string) $batch->batch_ref,
                    'gateway_reference' => $bankReference,
                    'actor_role'       => 'ADMIN_FINANCE',
                    'actor_user_id'    => $actorUserId,
                ]);
            }
        }
    }

    /**
     * Confirm a batch as fully settled by the bank/gateway.
     */
    public function confirmBatchSettled(int $batchId, int $actorUserId): void
    {
        $now = Carbon::now();
        $batch = DB::table('finance_payout_batches')->find($batchId);
        if (!$batch) {
            return;
        }

        DB::table('finance_payout_batches')->where('id', $batchId)->update([
            'status'               => 'confirmed',
            'confirmed_at'         => $now,
            'confirmed_by_user_id' => $actorUserId,
            'updated_at'           => $now,
        ]);

        $itemIds = DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->pluck('id');

        DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->update(['status' => 'confirmed', 'confirmed_at' => $now, 'updated_at' => $now]);

        DB::table('vendor_reservations')
            ->whereIn('payout_batch_item_id', $itemIds)
            ->update(['payout_status' => 'paid', 'updated_at' => $now]);

        $resIds = DB::table('vendor_reservations')
            ->whereIn('payout_batch_item_id', $itemIds)
            ->pluck('id');

        foreach ($resIds as $resId) {
            $this->ledger->append([
                'event_type'    => LedgerWriter::EVT_VENDOR_PAYOUT_CONFIRMED,
                'reservation_id' => $resId,
                'amount'        => 0,
                'currency'      => (string) $batch->currency,
                'source_medium' => (string) $batch->source_medium,
                'currency_band' => (string) $batch->currency_band,
                'batch_id'      => (string) $batch->batch_ref,
                'actor_role'    => 'ADMIN_FINANCE',
                'actor_user_id' => $actorUserId,
            ]);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateBatchRef(string $medium, string $band, string $dateStr): string
    {
        $mediumKey = strtoupper($medium);
        $bandKey   = strtoupper(str_replace('_', '-', $band));
        $date      = str_replace('-', '', $dateStr);
        $suffix    = strtoupper(Str::random(4));
        return "BATCH-{$mediumKey}-{$bandKey}-{$date}-{$suffix}";
    }
}
