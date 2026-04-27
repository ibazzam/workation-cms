<?php

declare(strict_types=1);

namespace App\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RefundRouter – refund lifecycle manager.
 *
 * Refunds MUST flow back through the same payment medium that collected the money:
 *   MIB payment   → MIB refund
 *   BML payment   → BML refund
 *   Stripe payment → Stripe refund
 *
 * This is enforced here.  The routing decision is based on the reservation's
 * payment_gateway column which maps to a medium via LedgerWriter::resolveSourceContext().
 *
 * SECURITY / PRIVACY:
 *   source_medium is stored in finance_refund_cases for routing and admin
 *   reconciliation only.  It must NEVER appear in any vendor-facing view,
 *   export, or API response.  Vendors see only: case_ref, refund_amount,
 *   refund_currency, status (displayed as a plain status label with no medium detail).
 */
final class RefundRouter
{
    public function __construct(
        private readonly LedgerWriter $ledger,
    ) {}

    /**
     * Open a new refund case for a reservation.
     *
     * @param  array{
     *   reservation_id: int,
     *   vendor_user_id: int,
     *   customer_user_id?: int|null,
     *   refund_amount: float,
     *   refund_type?: string,
     *   reason_code?: string|null,
     *   reason_notes?: string|null,
     *   requested_by_role?: string,
     *   requested_by_user_id?: int|null,
     * } $params
     * @return string case_ref
     */
    public function openCase(array $params): string
    {
        $now = Carbon::now();

        $reservation = DB::table('vendor_reservations')->find($params['reservation_id']);
        if (!$reservation) {
            throw new \RuntimeException("Reservation {$params['reservation_id']} not found.");
        }

        $ctx = LedgerWriter::resolveSourceContext(
            (string) ($reservation->payment_gateway ?? 'stripe'),
            (string) ($reservation->payment_currency ?? 'USD'),
        );

        $caseRef = $this->generateCaseRef($now);

        DB::table('finance_refund_cases')->insert([
            'case_ref'                    => $caseRef,
            'reservation_id'              => $params['reservation_id'],
            'vendor_user_id'              => $params['vendor_user_id'],
            'customer_user_id'            => $params['customer_user_id'] ?? null,
            'original_gateway'            => $reservation->payment_gateway,
            'original_payment_reference'  => $reservation->payment_reference,
            'original_amount'             => (float) ($reservation->payment_amount ?? 0),
            'original_currency'           => strtoupper((string) ($reservation->payment_currency ?? 'USD')),
            // ── INTERNAL routing fields ──────────────────────────────────────────
            'source_medium'               => $ctx['source_medium'],
            'currency_band'               => $ctx['currency_band'],
            // ────────────────────────────────────────────────────────────────────
            'refund_amount'               => (float) $params['refund_amount'],
            'refund_currency'             => strtoupper((string) ($reservation->payment_currency ?? 'USD')),
            'refund_type'                 => $params['refund_type'] ?? 'full',
            'reason_code'                 => $params['reason_code'] ?? null,
            'reason_notes'                => $params['reason_notes'] ?? null,
            'requested_by_role'           => $params['requested_by_role'] ?? 'ADMIN',
            'requested_by_user_id'        => $params['requested_by_user_id'] ?? null,
            'status'                      => 'requested',
            'created_at'                  => $now,
            'updated_at'                  => $now,
        ]);

        // Flag on the reservation (vendor can see this flag – not the medium)
        DB::table('vendor_reservations')
            ->where('id', $params['reservation_id'])
            ->update(['has_refund_case' => true, 'updated_at' => $now]);

        // Ledger event
        $this->ledger->append([
            'event_type'       => LedgerWriter::EVT_REFUND_INITIATED,
            'reservation_id'   => $params['reservation_id'],
            'vendor_user_id'   => $params['vendor_user_id'],
            'customer_user_id' => $params['customer_user_id'] ?? null,
            'amount'           => -(float) $params['refund_amount'],
            'currency'         => strtoupper((string) ($reservation->payment_currency ?? 'USD')),
            'source_medium'    => $ctx['source_medium'],
            'currency_band'    => $ctx['currency_band'],
            'notes'            => "Refund case {$caseRef} opened",
            'actor_role'       => $params['requested_by_role'] ?? 'ADMIN',
            'actor_user_id'    => $params['requested_by_user_id'] ?? null,
        ]);

        return $caseRef;
    }

    /**
     * Approve a refund case (moves to 'approved' status).
     */
    public function approveCase(string $caseRef, int $reviewedByUserId): void
    {
        $now = Carbon::now();
        DB::table('finance_refund_cases')
            ->where('case_ref', $caseRef)
            ->where('status', 'requested')
            ->update([
                'status'               => 'approved',
                'reviewed_at'          => $now,
                'reviewed_by_user_id'  => $reviewedByUserId,
                'updated_at'           => $now,
            ]);
    }

    /**
     * Mark a refund as completed (funds returned to customer).
     * Writes the refund_completed ledger event.
     */
    public function completeCase(string $caseRef, string $gatewayRefundReference, int $processedByUserId): void
    {
        $now = Carbon::now();

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        if (!$case) {
            return;
        }

        DB::table('finance_refund_cases')
            ->where('case_ref', $caseRef)
            ->update([
                'status'                  => 'completed',
                'gateway_refund_reference' => $gatewayRefundReference,
                'processed_at'            => $now,
                'completed_at'            => $now,
                'processed_by_user_id'    => $processedByUserId,
                'updated_at'              => $now,
            ]);

        $this->ledger->append([
            'event_type'       => LedgerWriter::EVT_REFUND_COMPLETED,
            'reservation_id'   => (int) $case->reservation_id,
            'vendor_user_id'   => (int) $case->vendor_user_id,
            'amount'           => -(float) $case->refund_amount,
            'currency'         => (string) $case->refund_currency,
            'source_medium'    => (string) $case->source_medium,
            'currency_band'    => (string) $case->currency_band,
            'gateway_reference' => $gatewayRefundReference,
            'notes'            => "Refund case {$caseRef} completed",
            'actor_role'       => 'ADMIN_FINANCE',
            'actor_user_id'    => $processedByUserId,
        ]);
    }

    /**
     * Reject a refund case.
     */
    public function rejectCase(string $caseRef, string $resolutionNotes, int $reviewedByUserId): void
    {
        $now = Carbon::now();
        DB::table('finance_refund_cases')
            ->where('case_ref', $caseRef)
            ->update([
                'status'              => 'rejected',
                'reviewed_at'         => $now,
                'reviewed_by_user_id' => $reviewedByUserId,
                'resolution_notes'    => $resolutionNotes,
                'updated_at'          => $now,
            ]);

        // Clear the flag only if no other open refund cases exist
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        if ($case) {
            $otherOpen = DB::table('finance_refund_cases')
                ->where('reservation_id', $case->reservation_id)
                ->whereNotIn('status', ['completed', 'rejected'])
                ->where('case_ref', '!=', $caseRef)
                ->exists();
            if (!$otherOpen) {
                DB::table('vendor_reservations')
                    ->where('id', $case->reservation_id)
                    ->update(['has_refund_case' => false, 'updated_at' => $now]);
            }
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateCaseRef(Carbon $now): string
    {
        return 'RFND-' . $now->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
