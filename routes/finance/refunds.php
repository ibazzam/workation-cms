<?php

/**
 * routes/finance/refunds.php
 *
 * Admin refund case management.
 *
 * Refunds are ALWAYS routed back through the same medium that collected the payment.
 * That routing decision is handled by RefundRouter and stored in source_medium
 * on the refund case – which is INTERNAL ONLY.
 *
 * Vendors see: case_ref, refund_amount, currency, status.
 * Vendors do NOT see: source_medium, original_gateway, bank/gateway references.
 */

use App\Finance\LedgerWriter;
use App\Finance\RefundRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::middleware('web')->group(function (): void {

    // ── GET /portal/admin/finance/refunds ─────────────────────────────────────
    Route::get('/portal/admin/finance/refunds', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE', 'ADMIN_CARE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        $cases = collect();
        if (Schema::hasTable('finance_refund_cases')) {
            $status = $request->query('status', '');
            $medium = strtolower((string) $request->query('medium', ''));

            $q = DB::table('finance_refund_cases as frc')
                ->leftJoin('users as vendors', 'vendors.id', '=', 'frc.vendor_user_id')
                ->leftJoin('users as reviewers', 'reviewers.id', '=', 'frc.reviewed_by_user_id')
                ->orderByDesc('frc.created_at')
                ->limit(300);

            if ($status !== '') {
                $q->where('frc.status', $status);
            }
            // source_medium filter is INTERNAL (admin only)
            if ($medium !== '') {
                $q->where('frc.source_medium', $medium);
            }

            $cases = $q->get([
                'frc.id',
                'frc.case_ref',
                'frc.reservation_id',
                'frc.vendor_user_id',
                'frc.original_gateway',      // INTERNAL
                'frc.source_medium',          // INTERNAL
                'frc.currency_band',          // INTERNAL
                'frc.refund_amount',
                'frc.refund_currency',
                'frc.refund_type',
                'frc.reason_code',
                'frc.reason_notes',
                'frc.status',
                'frc.gateway_refund_reference', // INTERNAL
                'frc.reviewed_at',
                'frc.completed_at',
                'frc.resolution_notes',
                'frc.created_at',
                'vendors.name as vendor_name',
                'vendors.email as vendor_email',
                'reviewers.name as reviewed_by_name',
            ]);
        }

        return view('admin.finance.refunds', [
            'cases'   => $cases,
            'filters' => ['status' => $request->query('status', ''), 'medium' => $request->query('medium', '')],
        ]);
    });

    // ── POST /portal/admin/finance/refunds ────────────────────────────────────
    // Open a new refund case.
    Route::post('/portal/admin/finance/refunds', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE', 'ADMIN_CARE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'reservation_id'  => ['required', 'integer', 'min:1'],
            'refund_amount'   => ['required', 'numeric', 'min:0.01'],
            'refund_type'     => ['required', 'in:full,partial'],
            'reason_code'     => ['nullable', 'string', 'max:40'],
            'reason_notes'    => ['nullable', 'string', 'max:1000'],
        ]);

        $reservation = DB::table('vendor_reservations')->find($validated['reservation_id']);
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found.'], 404);
        }

        $ledger = new LedgerWriter();
        $router = new RefundRouter($ledger);

        $caseRef = $router->openCase([
            'reservation_id'       => (int) $validated['reservation_id'],
            'vendor_user_id'       => (int) $reservation->vendor_user_id,
            'customer_user_id'     => $reservation->customer_user_id ? (int) $reservation->customer_user_id : null,
            'refund_amount'        => (float) $validated['refund_amount'],
            'refund_type'          => $validated['refund_type'],
            'reason_code'          => $validated['reason_code'] ?? null,
            'reason_notes'         => $validated['reason_notes'] ?? null,
            'requested_by_role'    => (string) ($user['portal_role'] ?? 'ADMIN'),
            'requested_by_user_id' => (int) ($user['id'] ?? 0),
        ]);

        return redirect('/portal/admin/finance/refunds')->with('success', "Refund case {$caseRef} opened.");
    });

    // ── POST /portal/admin/finance/refunds/{caseRef}/approve ─────────────────
    Route::post('/portal/admin/finance/refunds/{caseRef}/approve', function (Request $request, string $caseRef): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $ledger = new LedgerWriter();
        $router = new RefundRouter($ledger);
        $router->approveCase($caseRef, (int) ($user['id'] ?? 0));

        return redirect('/portal/admin/finance/refunds')->with('success', "Refund case {$caseRef} approved.");
    });

    // ── POST /portal/admin/finance/refunds/{caseRef}/complete ────────────────
    Route::post('/portal/admin/finance/refunds/{caseRef}/complete', function (Request $request, string $caseRef): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'gateway_refund_reference' => ['required', 'string', 'max:160'],
        ]);

        $ledger = new LedgerWriter();
        $router = new RefundRouter($ledger);
        $router->completeCase($caseRef, $validated['gateway_refund_reference'], (int) ($user['id'] ?? 0));

        return redirect('/portal/admin/finance/refunds')->with('success', "Refund case {$caseRef} completed.");
    });

    // ── POST /portal/admin/finance/refunds/{caseRef}/reject ──────────────────
    Route::post('/portal/admin/finance/refunds/{caseRef}/reject', function (Request $request, string $caseRef): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:1000'],
        ]);

        $ledger = new LedgerWriter();
        $router = new RefundRouter($ledger);
        $router->rejectCase($caseRef, $validated['resolution_notes'], (int) ($user['id'] ?? 0));

        return redirect('/portal/admin/finance/refunds')->with('success', "Refund case {$caseRef} rejected.");
    });
});
