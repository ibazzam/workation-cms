<?php

declare(strict_types=1);

namespace App\Finance;

use App\Support\ReservationSettlementCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * PayoutBatchBuilder – assembles and progresses payout batches.
 *
 * Default mode keeps batches separated by source_medium + currency_band.
 * Optional combine mode allows same-vendor + same-currency reservations to be
 * merged into one payout item even when sourced from different gateways.
 *
 * Operational rules:
 *   MIB   → local MVR bank, settlement to Workation in 5–7 business days
 *   BML   → local MVR bank, settlement to Workation in 5–7 business days
 *   Stripe → foreign bank account, USD, settlement to Workation in 10–12 business days
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
    *   - provider settlement window has elapsed based on payment_collected_at
     *
    * Default batches are created per source_medium + currency_band combination.
    * When $combineByVendorCurrency is enabled, batches are created per currency
    * with internal source_medium='mixed' and currency_band based on currency.
     * Returns a summary array of batch refs created.
     *
     * @return array<string, array{batch_ref: string, item_count: int, net_total: float, currency: string}>
     */
    public function buildBatchesForDate(Carbon $upToDate, int $actorUserId, bool $combineByVendorCurrency = false): array
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
                'vr.payment_collected_at',
                'vr.payment_verified_at',
                'vr.payout_expected_at',
                'vr.payment_amount',
                'vr.commission_amount',
                'vr.commission_rate_percent',
                'vr.gateway_fee_amount',
                'vr.gateway_fee_rate_percent',
                'vr.vendor_payout_amount',
                'vr.created_at',
                'vendor_users.name as vendor_name',
                'vendor_users.email as vendor_email',
            ]);

        $cutoff = $upToDate->copy()->endOfDay();
        $eligible = $eligible
            ->filter(static function ($row) use ($cutoff): bool {
                $expectedPayoutAt = !empty($row->payout_expected_at)
                    ? Carbon::parse((string) $row->payout_expected_at)
                    : ReservationSettlementCalculator::expectedPayoutAt(
                        $row->payment_collected_at ?? $row->payment_verified_at ?? $row->created_at ?? null,
                        (string) ($row->payment_gateway ?? ''),
                        null
                    );

                return $expectedPayoutAt !== null && $expectedPayoutAt->lte($cutoff);
            })
            ->values();

        if ($eligible->isEmpty()) {
            return [];
        }

        // ── Group eligible reservations ────────────────────────────────────────
        // default: source_medium + currency_band + vendor
        // combine mode: currency + vendor across gateways (internal medium='mixed')
        $groups = [];
        foreach ($eligible as $row) {
            $ctx = LedgerWriter::resolveSourceContext(
                (string) ($row->payment_gateway ?? 'stripe'),
                (string) ($row->payment_currency ?? 'USD'),
            );
            $currency = strtoupper((string) ($row->payment_currency ?? 'USD'));

            if ($combineByVendorCurrency) {
                $combinedBand = $currency === 'MVR' ? 'local_mvr' : 'foreign_usd';
                $key = 'mixed|' . $combinedBand . '|' . $currency;
                $sourceMedium = 'mixed';
                $currencyBand = $combinedBand;
            } else {
                $key = $ctx['source_medium'] . '|' . $ctx['currency_band'] . '|' . $currency;
                $sourceMedium = $ctx['source_medium'];
                $currencyBand = $ctx['currency_band'];
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'source_medium' => $sourceMedium,
                    'currency_band' => $currencyBand,
                    'currency'      => $currency,
                    'vendors'       => [],
                ];
            }
            $vendorId = (int) $row->vendor_user_id;
            if (!isset($groups[$key]['vendors'][$vendorId])) {
                $groups[$key]['vendors'][$vendorId] = [
                    'vendor_user_id'   => $vendorId,
                    'vendor_name'      => (string) ($row->vendor_name ?? ''),
                    'vendor_email'     => (string) ($row->vendor_email ?? ''),
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
                [$medium, $band] = explode('|', $groupKey, 3);
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
                    $linkedAccount = $this->resolveVerifiedVendorPayoutAccount(
                        (int) $vendorData['vendor_user_id'],
                        (string) $group['currency']
                    );

                    $itemStatus = 'queued';
                    $itemNotes = null;
                    if ($linkedAccount === null) {
                        $itemStatus = 'on_hold';
                        $itemNotes = 'Missing verified payout account in ' . strtoupper((string) $group['currency'])
                            . '. Add and verify a ' . strtoupper((string) $group['currency'])
                            . ' vendor payout account to continue settlement.';
                    }

                    $itemId = DB::table('finance_payout_batch_items')->insertGetId([
                        'batch_id'          => $batchId,
                        'vendor_user_id'    => $vendorData['vendor_user_id'],
                        'payout_account_id' => $linkedAccount !== null ? (int) ($linkedAccount->id ?? 0) : null,
                        'reservation_ids'   => json_encode($vendorData['reservation_ids']),
                        'gross_amount'      => round($vendorData['gross_amount'], 4),
                        'commission_amount' => round($vendorData['commission_amount'], 4),
                        'gateway_fee_amount' => round($vendorData['gateway_fee_amount'], 4),
                        'net_payout_amount' => round($vendorData['net_payout_amount'], 4),
                        'currency'          => $group['currency'],
                        'payout_account_currency' => $linkedAccount !== null ? strtoupper(trim((string) ($linkedAccount->currency ?? ''))) : null,
                        'payout_account_verification_status' => $linkedAccount !== null ? strtolower(trim((string) ($linkedAccount->verification_status ?? ''))) : null,
                        'bank_account_name' => $linkedAccount !== null ? trim((string) ($linkedAccount->beneficiary_name ?? '')) : null,
                        'bank_account_number' => $linkedAccount !== null ? ('****' . trim((string) ($linkedAccount->bank_account_last4 ?? ''))) : null,
                        'bank_name' => $linkedAccount !== null ? trim((string) ($linkedAccount->bank_name ?? '')) : null,
                        'status'            => $itemStatus,
                        'notes' => $itemNotes,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);

                    // ── Mark each reservation as payout_status = 'queued' ─────────
                    // NOTE: payout_source_medium written here but NEVER exposed to vendor.
                    $reservationPayoutStatus = $itemStatus === 'on_hold' ? 'on_hold' : 'queued';
                    DB::table('vendor_reservations')
                        ->whereIn('id', $vendorData['reservation_ids'])
                        ->update([
                            'payout_status'        => $reservationPayoutStatus,
                            'payout_currency'      => $group['currency'],
                            'payout_batch_item_id' => $itemId,
                            'payout_source_medium' => $medium, // INTERNAL ONLY
                            'updated_at'           => $now,
                        ]);

                    // ── Write ledger event per reservation ────────────────────────
                    foreach ($vendorData['reservation_ids'] as $resId) {
                        $this->ledger->append([
                            'event_type'     => $itemStatus === 'on_hold'
                                ? LedgerWriter::EVT_VENDOR_PAYOUT_ON_HOLD
                                : LedgerWriter::EVT_VENDOR_PAYOUT_QUEUED,
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
                            'notes'          => $itemNotes,
                        ]);
                    }

                    if ($itemStatus === 'on_hold' && Schema::hasTable('finance_payout_item_status_logs')) {
                        DB::table('finance_payout_item_status_logs')->insert([
                            'batch_id' => $batchId,
                            'item_id' => $itemId,
                            'vendor_user_id' => (int) ($vendorData['vendor_user_id'] ?? 0),
                            'from_status' => 'queued',
                            'to_status' => 'on_hold',
                            'notes' => $itemNotes,
                            'actor_user_id' => $actorUserId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    // ── Notify vendor of payout queued ────────────────────────
                    $vendorPayoutEmail = (string) ($vendorData['vendor_email'] ?? '');
                    if ($vendorPayoutEmail !== '' && str_contains($vendorPayoutEmail, '@')) {
                        $queuedNetFmt = number_format($vendorData['net_payout_amount'], 2);
                        $queuedBody = implode("\n", [
                            'Dear Vendor,',
                            '',
                            'Your payout has been queued and will be processed shortly.',
                            '',
                            'Batch Reference: ' . $batchRef,
                            'Currency: ' . $group['currency'],
                            'Reservations Included: ' . count($vendorData['reservation_ids']),
                            'Net Payout Amount: ' . $group['currency'] . ' ' . $queuedNetFmt,
                            '',
                            'You will receive a further notification once the payout has been submitted to your bank.',
                            '',
                            'Thank you,',
                            'Workation Team',
                        ]);
                        try {
                            Mail::raw($queuedBody, static function ($msg) use ($vendorPayoutEmail, $batchRef): void {
                                $msg->to($vendorPayoutEmail)->subject('Payout Queued – Batch ' . $batchRef);
                            });
                        } catch (\Throwable $e) {
                            Log::warning('PayoutBatchBuilder: failed to send payout queued email', [
                                'vendor_user_id' => $vendorData['vendor_user_id'],
                                'batch_ref' => $batchRef,
                                'error' => $e->getMessage(),
                            ]);
                        }
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
    public function markBatchSent(int $batchId, string $bankReference, int $actorUserId, ?Carbon $expectedPayoutAt = null): void
    {
        $this->assertBatchReadyForSubmission($batchId);

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
            ->update([
                'payout_status' => 'processing',
                'payout_processing_at' => $now,
                'payout_expected_at' => $expectedPayoutAt,
                'updated_at' => $now,
            ]);

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

            // ── Notify each vendor that their payout is now in progress ───────
            $batchRef = (string) ($batch->batch_ref ?? '');
            $batchCurrency = strtoupper((string) ($batch->currency ?? ''));
            $expectedLabel = $expectedPayoutAt !== null ? $expectedPayoutAt->toDateString() : 'as soon as possible';
            $traceId = trim($bankReference) !== '' ? trim($bankReference) : $batchRef;
            $vendorItems = DB::table('finance_payout_batch_items as fpi')
                ->leftJoin('users', 'users.id', '=', 'fpi.vendor_user_id')
                ->where('fpi.batch_id', $batchId)
                ->get(['fpi.vendor_user_id', 'fpi.net_payout_amount', 'fpi.bank_account_name', 'fpi.bank_account_number', 'fpi.bank_name', 'users.email as vendor_email']);
            foreach ($vendorItems as $vItem) {
                $vEmail = (string) ($vItem->vendor_email ?? '');
                if ($vEmail === '' || !str_contains($vEmail, '@')) {
                    continue;
                }
                $sentNetFmt = number_format((float) ($vItem->net_payout_amount ?? 0), 2);
                $bankAccountNumber = (string) ($vItem->bank_account_number ?? '');
                $maskedAccountNumber = $bankAccountNumber !== '' ? '****' . substr($bankAccountNumber, -4) : 'Saved account';
                $bankName = trim((string) ($vItem->bank_name ?? ''));
                $bankAccountName = trim((string) ($vItem->bank_account_name ?? ''));
                $trackUrl = url('/portal/vendor');
                
                $sentBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { font-size: 24px; font-weight: 600; margin-bottom: 30px; color: #1a1a1a; }
        .status { font-size: 18px; font-weight: 500; color: #28a745; margin-bottom: 25px; }
        .details-box { background: #f9f9f9; border-left: 4px solid #4f46e5; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 500; color: #666; }
        .detail-value { font-weight: 600; color: #1a1a1a; text-align: right; }
        .guidance { background: #fffbf0; border-left: 4px solid #f59e0b; padding: 20px; margin: 25px 0; border-radius: 4px; font-size: 14px; }
        .guidance-title { font-weight: 600; color: #92400e; margin-bottom: 10px; }
        .cta-wrap { margin: 24px 0; }
        .cta { display: inline-block; background: #635bff; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; }
        .extra-info { background: #f4f6f8; border: 1px solid #d9dee3; border-radius: 12px; padding: 18px; margin-top: 26px; font-size: 14px; }
        .extra-info p { margin: 6px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 13px; color: #999; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Your payout is on the way</div>
        <div class="status">✓ $batchCurrency $sentNetFmt is being processed</div>
        
        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Amount</span>
                <span class="detail-value">$batchCurrency $sentNetFmt</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Estimated arrival</span>
                <span class="detail-value">$expectedLabel, by end of day</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">To</span>
                <span class="detail-value">$maskedAccountNumber</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payout ID</span>
                <span class="detail-value">$batchRef</span>
            </div>
        </div>

        <div class="cta-wrap">
            <a class="cta" href="$trackUrl">Track payout</a>
        </div>
        
        <div class="guidance">
            <div class="guidance-title">Processing your payout</div>
            <p>Your payout has been submitted to your bank and is being processed. Please allow 1–3 business days for the funds to appear in your account, depending on your bank's processing times.</p>
        </div>
        
        <div class="guidance">
            <div class="guidance-title">Don't see your payout on the expected arrival date?</div>
            <p>Banks can take up to 5 business days after the expected date to process a payout. Wait until 5 business days have passed, then contact your bank with the payout information, including the payout trace ID. If the payout is still not found, request a written confirmation from your bank and share it with our support team.</p>
        </div>

        <div class="extra-info">
            <p class="muted">You might also need this additional information:</p>
            <p><strong>Bank:</strong> {$bankName}</p>
            <p><strong>Account holder:</strong> {$bankAccountName}</p>
            <p><strong>Payout trace ID:</strong> {$traceId}</p>
            <p><strong>Email ID:</strong> {$vEmail}</p>
        </div>
        
        <div class="footer">
            <p class="muted">You got this email because payout notifications are enabled for your vendor account.</p>
            <p>Questions? Log in to your vendor portal or contact our support team.</p>
            <p style="margin-top: 15px;">Best regards,<br>The Workation Team</p>
        </div>
    </div>
</body>
</html>
HTML;

                try {
                    Mail::html($sentBody, static function ($msg) use ($vEmail, $batchRef): void {
                        $msg->to($vEmail)
                            ->subject($batchRef . ' is on the way');
                    });
                } catch (\Throwable $e) {
                    Log::warning('PayoutBatchBuilder: failed to send payout sent email', [
                        'vendor_user_id' => (int) ($vItem->vendor_user_id ?? 0),
                        'batch_id' => $batchId,
                        'error' => $e->getMessage(),
                    ]);
                }
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
            ->update([
                'payout_status' => 'paid',
                'payout_paid_at' => $now,
                'updated_at' => $now,
            ]);

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

        // ── Notify each vendor that their payout has been confirmed/settled ──
        $confirmedBatchRef = (string) ($batch->batch_ref ?? '');
        $confirmedCurrency = strtoupper((string) ($batch->currency ?? ''));
        $confirmedVendorItems = DB::table('finance_payout_batch_items as fpi')
            ->leftJoin('users', 'users.id', '=', 'fpi.vendor_user_id')
            ->where('fpi.batch_id', $batchId)
            ->get(['fpi.vendor_user_id', 'fpi.net_payout_amount', 'fpi.bank_account_number', 'users.email as vendor_email']);
        foreach ($confirmedVendorItems as $cvItem) {
            $cvEmail = (string) ($cvItem->vendor_email ?? '');
            if ($cvEmail === '' || !str_contains($cvEmail, '@')) {
                continue;
            }
            $confirmedNetFmt = number_format((float) ($cvItem->net_payout_amount ?? 0), 2);
            $confirmedAccountNumber = (string) ($cvItem->bank_account_number ?? '');
            $confirmedMaskedAccount = $confirmedAccountNumber !== '' ? '****' . substr($confirmedAccountNumber, -4) : 'Saved account';
            
            $confirmedBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { font-size: 24px; font-weight: 600; margin-bottom: 30px; color: #1a1a1a; }
        .status { font-size: 18px; font-weight: 500; color: #28a745; margin-bottom: 25px; }
        .details-box { background: #f9f9f9; border-left: 4px solid #4f46e5; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 500; color: #666; }
        .detail-value { font-weight: 600; color: #1a1a1a; text-align: right; }
        .next-steps { background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .next-steps-title { font-weight: 600; color: #0c4a6e; margin-bottom: 10px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Payout confirmed and settled</div>
        <div class="status">✓ Your funds have been successfully transferred</div>
        
        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Amount</span>
                <span class="detail-value">$confirmedCurrency $confirmedNetFmt</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value" style="color: #28a745;">Settled</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Transferred to</span>
                <span class="detail-value">$confirmedMaskedAccount</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payout ID</span>
                <span class="detail-value">$confirmedBatchRef</span>
            </div>
        </div>
        
        <div class="next-steps">
            <div class="next-steps-title">Next steps</div>
            <p>The funds should now be available in your registered bank account. Log in to your vendor portal to view the updated billing ledger and detailed transaction history for this settlement.</p>
        </div>
        
        <div class="footer">
            <p>Questions? Log in to your vendor portal or contact our support team.</p>
            <p style="margin-top: 15px;">Best regards,<br>The Workation Team</p>
        </div>
    </div>
</body>
</html>
HTML;

            try {
                Mail::html($confirmedBody, static function ($msg) use ($cvEmail, $confirmedBatchRef): void {
                    $msg->to($cvEmail)
                    ->subject('Payout confirmed - ' . $confirmedBatchRef);
                });
            } catch (\Throwable $e) {
                Log::warning('PayoutBatchBuilder: failed to send payout confirmed email', [
                    'vendor_user_id' => (int) ($cvItem->vendor_user_id ?? 0),
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
            }
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

    private function resolveVerifiedVendorPayoutAccount(int $vendorUserId, string $currency): ?object
    {
        if (!Schema::hasTable('vendor_payout_accounts')) {
            return null;
        }

        $targetCurrency = strtoupper(trim($currency));

        return DB::table('vendor_payout_accounts')
            ->where('vendor_user_id', $vendorUserId)
            ->where('is_active', true)
            ->where('currency', $targetCurrency)
            ->whereIn('verification_status', ['approved', 'verified'])
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Prevent finance from submitting payout batches before funds are expected
     * to have settled from the payment gateway into Workation's bank.
     */
    private function assertBatchReadyForSubmission(int $batchId): void
    {
        $itemIds = DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            throw new \RuntimeException('Cannot submit payout batch: batch has no payout items.');
        }

        $rows = DB::table('vendor_reservations')
            ->whereIn('payout_batch_item_id', $itemIds)
            ->get([
                'id',
                'payment_status',
                'payout_status',
                'payment_gateway',
                'payment_collected_at',
                'payment_verified_at',
                'payout_expected_at',
                'created_at',
            ]);

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Cannot submit payout batch: no linked reservations found.');
        }

        $now = Carbon::now();
        $blocked = [];
        foreach ($rows as $row) {
            $paymentStatus = strtolower(trim((string) ($row->payment_status ?? '')));
            if ($paymentStatus !== 'paid') {
                $blocked[] = 'reservation #' . (int) ($row->id ?? 0) . ' is not paid';
                continue;
            }

            $expected = !empty($row->payout_expected_at)
                ? Carbon::parse((string) $row->payout_expected_at)
                : ReservationSettlementCalculator::expectedPayoutAt(
                    $row->payment_collected_at ?? $row->payment_verified_at ?? $row->created_at ?? null,
                    (string) ($row->payment_gateway ?? ''),
                    null
                );

            if ($expected === null) {
                $blocked[] = 'reservation #' . (int) ($row->id ?? 0) . ' has no settlement date';
                continue;
            }

            if ($expected->gt($now)) {
                $blocked[] = 'reservation #' . (int) ($row->id ?? 0) . ' settles on ' . $expected->toDateString();
            }
        }

        if ($blocked !== []) {
            throw new \RuntimeException('Cannot submit payout batch yet. Settlement window not reached for: ' . implode('; ', array_slice($blocked, 0, 5)) . (count($blocked) > 5 ? '; ...' : ''));
        }
    }
}