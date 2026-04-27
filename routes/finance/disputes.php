<?php

/**
 * routes/finance/disputes.php
 *
 * Admin payment dispute / chargeback management.
 *
 * Stripe disputes are the primary concern (foreign bank, formal chargeback
 * mechanics with respond-by deadlines and financial debits on loss).
 * MIB and BML disputes are handled as manual cases.
 *
 * SECURITY / PRIVACY:
 *   source_medium is INTERNAL ONLY.  Vendors see only: case_ref, disputed_amount,
 *   currency, and outcome (won/lost/accepted) – never which gateway filed it.
 */

use App\Finance\DisputeHandler;
use App\Finance\LedgerWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::middleware('web')->group(function (): void {

    // ── GET /portal/admin/finance/disputes ────────────────────────────────────
    Route::get('/portal/admin/finance/disputes', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        $cases = collect();
        if (Schema::hasTable('finance_dispute_cases')) {
            $status = $request->query('status', '');
            $medium = strtolower((string) $request->query('medium', ''));

            $q = DB::table('finance_dispute_cases as fdc')
                ->leftJoin('users as vendors', 'vendors.id', '=', 'fdc.vendor_user_id')
                ->leftJoin('users as assigned', 'assigned.id', '=', 'fdc.assigned_to_user_id')
                ->orderByRaw("CASE WHEN fdc.respond_by IS NOT NULL AND fdc.status NOT IN ('won','lost','accepted') THEN 0 ELSE 1 END")
                ->orderBy('fdc.respond_by')
                ->orderByDesc('fdc.created_at')
                ->limit(200);

            if ($status !== '') {
                $q->where('fdc.status', $status);
            }
            if ($medium !== '') {
                $q->where('fdc.source_medium', $medium); // INTERNAL filter
            }

            $cases = $q->get([
                'fdc.id',
                'fdc.case_ref',
                'fdc.reservation_id',
                'fdc.vendor_user_id',
                'fdc.gateway_dispute_id',   // INTERNAL
                'fdc.source_medium',         // INTERNAL
                'fdc.currency_band',         // INTERNAL
                'fdc.disputed_amount',
                'fdc.disputed_currency',
                'fdc.dispute_reason',
                'fdc.respond_by',
                'fdc.status',
                'fdc.outcome',
                'fdc.outcome_amount',
                'fdc.evidence_notes',
                'fdc.evidence_submitted_at',
                'fdc.resolved_at',
                'fdc.resolution_notes',
                'fdc.created_at',
                'vendors.name as vendor_name',
                'vendors.email as vendor_email',
                'assigned.name as assigned_to_name',
            ]);
        }

        // Urgent: disputes with respond_by within 3 days
        $urgentCount = $cases->filter(function ($c): bool {
            if (!$c->respond_by || in_array($c->status, ['won', 'lost', 'accepted'], true)) {
                return false;
            }
            $diff = now()->diffInDays($c->respond_by, false);
            return $diff >= 0 && $diff <= 3;
        })->count();

        return view('admin.finance.disputes', [
            'cases'       => $cases,
            'urgentCount' => $urgentCount,
            'filters'     => ['status' => $request->query('status', ''), 'medium' => $request->query('medium', '')],
        ]);
    });

    // ── POST /portal/admin/finance/disputes ───────────────────────────────────
    // Open a new dispute case (or receive it from a gateway webhook handler).
    Route::post('/portal/admin/finance/disputes', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'reservation_id'     => ['required', 'integer', 'min:1'],
            'disputed_amount'    => ['required', 'numeric', 'min:0.01'],
            'dispute_reason'     => ['nullable', 'string', 'max:60'],
            'gateway_dispute_id' => ['nullable', 'string', 'max:160'],
            'respond_by'         => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation = DB::table('vendor_reservations')->find($validated['reservation_id']);
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found.'], 404);
        }

        $ledger  = new LedgerWriter();
        $handler = new DisputeHandler($ledger);

        $caseRef = $handler->openCase([
            'reservation_id'     => (int) $validated['reservation_id'],
            'vendor_user_id'     => (int) $reservation->vendor_user_id,
            'customer_user_id'   => $reservation->customer_user_id ? (int) $reservation->customer_user_id : null,
            'gateway_dispute_id' => $validated['gateway_dispute_id'] ?? null,
            'disputed_amount'    => (float) $validated['disputed_amount'],
            'dispute_reason'     => $validated['dispute_reason'] ?? null,
            'respond_by'         => $validated['respond_by'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'assigned_to_user_id' => (int) ($user['id'] ?? 0),
        ]);

        return redirect('/portal/admin/finance/disputes')->with('success', "Dispute case {$caseRef} opened.");
    });

    // ── POST /portal/admin/finance/disputes/{caseRef}/evidence ───────────────
    Route::post('/portal/admin/finance/disputes/{caseRef}/evidence', function (Request $request, string $caseRef): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'evidence_notes' => ['required', 'string', 'max:5000'],
        ]);

        $ledger  = new LedgerWriter();
        $handler = new DisputeHandler($ledger);
        $handler->submitEvidence($caseRef, $validated['evidence_notes'], (int) ($user['id'] ?? 0));

        return redirect('/portal/admin/finance/disputes')->with('success', "Evidence submitted for {$caseRef}.");
    });

    // ── POST /portal/admin/finance/disputes/{caseRef}/resolve ────────────────
    Route::post('/portal/admin/finance/disputes/{caseRef}/resolve', function (Request $request, string $caseRef): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'outcome'          => ['required', 'in:won,lost,accepted'],
            'outcome_amount'   => ['required', 'numeric', 'min:0'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ledger  = new LedgerWriter();
        $handler = new DisputeHandler($ledger);
        $handler->resolveCase(
            $caseRef,
            $validated['outcome'],
            (float) $validated['outcome_amount'],
            (int) ($user['id'] ?? 0),
            $validated['resolution_notes'] ?? null,
        );

        return redirect('/portal/admin/finance/disputes')->with('success', "Dispute {$caseRef} resolved as {$validated['outcome']}.");
    });
});
