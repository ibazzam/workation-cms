<?php

declare(strict_types=1);

namespace App\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DisputeHandler – chargeback and payment dispute state machine.
 *
 * Stripe disputes are the primary concern: formal chargeback mechanics,
 * respond-by deadlines, and financial debits on loss.
 * MIB/BML disputes are handled as manual escalation cases.
 *
 * States: opened → evidence_submitted → under_review → won | lost | accepted
 *
 * SECURITY / PRIVACY:
 *   source_medium (mib | bml | stripe) is stored internally and must NEVER
 *   appear in vendor-facing views.  Vendors are told a dispute exists and
 *   whether it was won/lost, but not which payment processor filed it.
 */
final class DisputeHandler
{
    public function __construct(
        private readonly LedgerWriter $ledger,
    ) {}

    /**
     * Open a new dispute case for a reservation.
     *
     * @param  array{
     *   reservation_id: int,
     *   vendor_user_id: int,
     *   customer_user_id?: int|null,
     *   gateway_dispute_id?: string|null,
     *   disputed_amount: float,
     *   dispute_reason?: string|null,
     *   respond_by?: string|null,
     *   assigned_to_user_id?: int|null,
     *   notes?: string|null,
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

        DB::table('finance_dispute_cases')->insert([
            'case_ref'                   => $caseRef,
            'reservation_id'             => $params['reservation_id'],
            'vendor_user_id'             => $params['vendor_user_id'],
            'customer_user_id'           => $params['customer_user_id'] ?? null,
            'gateway_dispute_id'         => $params['gateway_dispute_id'] ?? null,
            'original_payment_reference' => $reservation->payment_reference,
            'disputed_amount'            => (float) $params['disputed_amount'],
            'disputed_currency'          => strtoupper((string) ($reservation->payment_currency ?? 'USD')),
            // ── INTERNAL routing fields (never shown to vendor) ──────────────────
            'source_medium'              => $ctx['source_medium'],
            'currency_band'              => $ctx['currency_band'],
            // ────────────────────────────────────────────────────────────────────
            'dispute_reason'             => $params['dispute_reason'] ?? null,
            'respond_by'                 => $params['respond_by'] ?? null,
            'status'                     => 'opened',
            'assigned_to_user_id'        => $params['assigned_to_user_id'] ?? null,
            'evidence_notes'             => $params['notes'] ?? null,
            'created_at'                 => $now,
            'updated_at'                 => $now,
        ]);

        // Flag the reservation (vendor sees the flag, not the medium)
        DB::table('vendor_reservations')
            ->where('id', $params['reservation_id'])
            ->update(['has_open_dispute' => true, 'updated_at' => $now]);

        $this->ledger->append([
            'event_type'       => LedgerWriter::EVT_DISPUTE_OPENED,
            'reservation_id'   => $params['reservation_id'],
            'vendor_user_id'   => $params['vendor_user_id'],
            'customer_user_id' => $params['customer_user_id'] ?? null,
            'amount'           => -(float) $params['disputed_amount'],
            'currency'         => strtoupper((string) ($reservation->payment_currency ?? 'USD')),
            'source_medium'    => $ctx['source_medium'],
            'currency_band'    => $ctx['currency_band'],
            'notes'            => "Dispute case {$caseRef} opened. Reason: " . ($params['dispute_reason'] ?? 'N/A'),
            'actor_role'       => 'system',
        ]);

        return $caseRef;
    }

    /**
     * Submit evidence for a dispute (moves to evidence_submitted).
     */
    public function submitEvidence(string $caseRef, string $evidenceNotes, int $actorUserId): void
    {
        $now = Carbon::now();
        DB::table('finance_dispute_cases')
            ->where('case_ref', $caseRef)
            ->whereIn('status', ['opened', 'under_review'])
            ->update([
                'status'                    => 'evidence_submitted',
                'evidence_notes'            => $evidenceNotes,
                'evidence_submitted_at'     => $now,
                'assigned_to_user_id'       => $actorUserId,
                'updated_at'                => $now,
            ]);
    }

    /**
     * Resolve a dispute (won, lost, or accepted by us).
     *
     * If outcome = 'lost', records a dispute_lost ledger event with the debited amount.
     * If outcome = 'won', records a dispute_resolved event.
     */
    public function resolveCase(
        string $caseRef,
        string $outcome,
        float $outcomeAmount,
        int $resolvedByUserId,
        ?string $resolutionNotes = null,
    ): void {
        if (!in_array($outcome, ['won', 'lost', 'accepted'], true)) {
            throw new \InvalidArgumentException("Invalid dispute outcome: {$outcome}");
        }

        $now  = Carbon::now();
        $case = DB::table('finance_dispute_cases')->where('case_ref', $caseRef)->first();
        if (!$case) {
            return;
        }

        DB::table('finance_dispute_cases')
            ->where('case_ref', $caseRef)
            ->update([
                'status'               => $outcome,
                'outcome'              => $outcome,
                'outcome_amount'       => $outcomeAmount,
                'resolved_at'          => $now,
                'resolved_by_user_id'  => $resolvedByUserId,
                'resolution_notes'     => $resolutionNotes,
                'updated_at'           => $now,
            ]);

        // Clear the open dispute flag on the reservation
        $hasOtherOpen = DB::table('finance_dispute_cases')
            ->where('reservation_id', $case->reservation_id)
            ->whereNotIn('status', ['won', 'lost', 'accepted'])
            ->where('case_ref', '!=', $caseRef)
            ->exists();

        DB::table('vendor_reservations')
            ->where('id', $case->reservation_id)
            ->update([
                'has_open_dispute' => $hasOtherOpen,
                'updated_at'       => $now,
            ]);

        $eventType = ($outcome === 'lost') ? LedgerWriter::EVT_DISPUTE_LOST : LedgerWriter::EVT_DISPUTE_RESOLVED;

        $this->ledger->append([
            'event_type'      => $eventType,
            'reservation_id'  => (int) $case->reservation_id,
            'vendor_user_id'  => (int) $case->vendor_user_id,
            'amount'          => $outcome === 'lost' ? -abs($outcomeAmount) : abs($outcomeAmount),
            'currency'        => (string) $case->disputed_currency,
            'source_medium'   => (string) $case->source_medium,
            'currency_band'   => (string) $case->currency_band,
            'notes'           => "Dispute {$caseRef} resolved: {$outcome}. " . ($resolutionNotes ?? ''),
            'actor_role'      => 'ADMIN_FINANCE',
            'actor_user_id'   => $resolvedByUserId,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateCaseRef(Carbon $now): string
    {
        return 'DISP-' . $now->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
