<?php

declare(strict_types=1);

namespace App\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * LedgerWriter – append-only finance event log.
 *
 * This is the ONLY class authorised to write rows to finance_ledger.
 * All other finance operations (payout, refund, dispute) use this class
 * to record their events.
 *
 * SECURITY / PRIVACY:
 *   source_medium (mib | bml | stripe) and currency_band are written here
 *   for admin reconciliation.  These fields must NEVER be included in any
 *   vendor-facing query, Blade view, CSV export, or API response.
 *   See: resources/views/vendor-portal/partials/payout-status.blade.php for
 *   the correct vendor-facing view which deliberately omits these fields.
 */
final class LedgerWriter
{
    // ── Event type constants ──────────────────────────────────────────────────

    /** Customer payment successfully collected. */
    public const EVT_PAYMENT_COLLECTED = 'payment_collected';

    /** Workation platform commission deducted from gross. */
    public const EVT_COMMISSION_DEDUCTED = 'commission_deducted';

    /** Payment gateway fee deducted from gross. */
    public const EVT_GATEWAY_FEE_DEDUCTED = 'gateway_fee_deducted';

    /** Vendor payout queued in a payout batch. */
    public const EVT_VENDOR_PAYOUT_QUEUED = 'vendor_payout_queued';

    /** Payout batch submitted to the bank/gateway. */
    public const EVT_VENDOR_PAYOUT_SENT = 'vendor_payout_sent';

    /** Payout confirmed settled by the bank/gateway. */
    public const EVT_VENDOR_PAYOUT_CONFIRMED = 'vendor_payout_confirmed';

    /** Customer refund initiated. */
    public const EVT_REFUND_INITIATED = 'refund_initiated';

    /** Refund completed and returned to customer. */
    public const EVT_REFUND_COMPLETED = 'refund_completed';

    /** Chargeback / payment dispute opened. */
    public const EVT_DISPUTE_OPENED = 'dispute_opened';

    /** Dispute resolved (won or lost). */
    public const EVT_DISPUTE_RESOLVED = 'dispute_resolved';

    /** Dispute lost – funds debited. */
    public const EVT_DISPUTE_LOST = 'dispute_lost';

    // ── Medium constants (INTERNAL ONLY) ─────────────────────────────────────

    public const MEDIUM_MIB    = 'mib';
    public const MEDIUM_BML    = 'bml';
    public const MEDIUM_STRIPE = 'stripe';

    // ── Currency band constants (INTERNAL ONLY) ───────────────────────────────

    public const BAND_LOCAL_MVR   = 'local_mvr';
    public const BAND_FOREIGN_USD = 'foreign_usd';

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Append a new event to the ledger.
     *
     * @param  array{
     *   event_type: string,
     *   amount: float|int,
     *   currency: string,
     *   reservation_id?: int|null,
     *   vendor_user_id?: int|null,
     *   customer_user_id?: int|null,
     *   source_medium?: string|null,
     *   currency_band?: string|null,
     *   gateway_reference?: string|null,
     *   batch_id?: string|null,
     *   reference_ledger_id?: int|null,
     *   actor_role?: string|null,
     *   actor_user_id?: int|null,
     *   commission_rate_pct?: float|null,
     *   gateway_fee_rate_pct?: float|null,
     *   notes?: string|null,
     *   occurred_at?: \DateTimeInterface|string|null,
     * } $payload
     */
    public function append(array $payload): int
    {
        $now = Carbon::now();

        $id = DB::table('finance_ledger')->insertGetId([
            'event_type'           => $payload['event_type'],
            'reservation_id'       => $payload['reservation_id'] ?? null,
            'vendor_user_id'       => $payload['vendor_user_id'] ?? null,
            'customer_user_id'     => $payload['customer_user_id'] ?? null,
            'amount'               => (float) ($payload['amount'] ?? 0),
            'currency'             => strtoupper((string) ($payload['currency'] ?? 'MVR')),
            // ── INTERNAL fields ─────────────────────────────────────────────
            'source_medium'        => $payload['source_medium'] ?? null,
            'currency_band'        => $payload['currency_band'] ?? null,
            // ────────────────────────────────────────────────────────────────
            'gateway_reference'    => $payload['gateway_reference'] ?? null,
            'batch_id'             => $payload['batch_id'] ?? null,
            'reference_ledger_id'  => $payload['reference_ledger_id'] ?? null,
            'actor_role'           => $payload['actor_role'] ?? 'system',
            'actor_user_id'        => $payload['actor_user_id'] ?? null,
            'commission_rate_pct'  => isset($payload['commission_rate_pct']) ? (float) $payload['commission_rate_pct'] : null,
            'gateway_fee_rate_pct' => isset($payload['gateway_fee_rate_pct']) ? (float) $payload['gateway_fee_rate_pct'] : null,
            'notes'                => $payload['notes'] ?? null,
            'occurred_at'          => isset($payload['occurred_at'])
                ? Carbon::parse($payload['occurred_at'])->toDateTimeString()
                : $now->toDateTimeString(),
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        return $id;
    }

    /**
     * Record all three settlement events for a paid reservation in a single call:
     *   1. payment_collected  (positive, full gross)
     *   2. commission_deducted (negative)
     *   3. gateway_fee_deducted (negative)
     *
     * Returns the ledger ID of the payment_collected row.
     */
    public function recordSettlement(
        int $reservationId,
        int $vendorUserId,
        ?int $customerUserId,
        float $grossAmount,
        float $commissionAmount,
        float $commissionRatePct,
        float $gatewayFeeAmount,
        float $gatewayFeeRatePct,
        string $currency,
        string $sourceMedium,
        string $currencyBand,
        ?string $gatewayReference = null,
        ?string $actorRole = 'system',
        ?int $actorUserId = null,
    ): int {
        $collectedId = $this->append([
            'event_type'           => self::EVT_PAYMENT_COLLECTED,
            'reservation_id'       => $reservationId,
            'vendor_user_id'       => $vendorUserId,
            'customer_user_id'     => $customerUserId,
            'amount'               => $grossAmount,
            'currency'             => $currency,
            'source_medium'        => $sourceMedium,
            'currency_band'        => $currencyBand,
            'gateway_reference'    => $gatewayReference,
            'commission_rate_pct'  => $commissionRatePct,
            'gateway_fee_rate_pct' => $gatewayFeeRatePct,
            'actor_role'           => $actorRole,
            'actor_user_id'        => $actorUserId,
        ]);

        $this->append([
            'event_type'          => self::EVT_COMMISSION_DEDUCTED,
            'reservation_id'      => $reservationId,
            'vendor_user_id'      => $vendorUserId,
            'amount'              => -abs($commissionAmount),
            'currency'            => $currency,
            'source_medium'       => $sourceMedium,
            'currency_band'       => $currencyBand,
            'reference_ledger_id' => $collectedId,
            'commission_rate_pct' => $commissionRatePct,
            'actor_role'          => $actorRole,
            'actor_user_id'       => $actorUserId,
        ]);

        $this->append([
            'event_type'           => self::EVT_GATEWAY_FEE_DEDUCTED,
            'reservation_id'       => $reservationId,
            'vendor_user_id'       => $vendorUserId,
            'amount'               => -abs($gatewayFeeAmount),
            'currency'             => $currency,
            'source_medium'        => $sourceMedium,
            'currency_band'        => $currencyBand,
            'reference_ledger_id'  => $collectedId,
            'gateway_fee_rate_pct' => $gatewayFeeRatePct,
            'actor_role'           => $actorRole,
            'actor_user_id'        => $actorUserId,
        ]);

        return $collectedId;
    }

    /**
     * Derive the internal source medium and currency band from a reservation row.
     * Used when recording events for reservations that already have payment metadata.
     *
     * INTERNAL ONLY – do not expose this mapping to vendor-facing code.
     *
     * @return array{source_medium: string, currency_band: string}
     */
    public static function resolveSourceContext(string $paymentGateway, string $currency): array
    {
        $gateway = strtolower($paymentGateway);
        $currency = strtoupper($currency);

        $medium = match (true) {
            str_starts_with($gateway, 'mib') => self::MEDIUM_MIB,
            str_starts_with($gateway, 'bml') => self::MEDIUM_BML,
            str_starts_with($gateway, 'stripe') => self::MEDIUM_STRIPE,
            default => self::MEDIUM_STRIPE,
        };

        $band = ($currency === 'MVR') ? self::BAND_LOCAL_MVR : self::BAND_FOREIGN_USD;

        return [
            'source_medium' => $medium,
            'currency_band' => $band,
        ];
    }
}
