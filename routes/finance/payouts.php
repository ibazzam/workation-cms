<?php

/**
 * routes/finance/payouts.php
 *
 * Admin payout batch operations.
 * Separated by source_medium (mib | bml | stripe) and currency_band.
 *
 * SECURITY / PRIVACY:
 *   source_medium is strictly INTERNAL.  These routes are behind ADMIN_FINANCE /
 *   ADMIN_SUPER only.  Batch ref prefixes (BATCH-MIB-*, BATCH-BML-*, BATCH-STRIPE-*)
 *   must never be forwarded to vendor routes, vendor exports, or any vendor view.
 */

use App\Finance\LedgerWriter;
use App\Finance\PayoutBatchBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::middleware('web')->group(function (): void {

    // ── GET /portal/admin/finance/payouts ─────────────────────────────────────
    Route::get('/portal/admin/finance/payouts', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        $batches       = collect();
        $eligibleCount = 0;

        if (Schema::hasTable('finance_payout_batches')) {
            $status = $request->query('status', '');
            $medium = strtolower((string) $request->query('medium', ''));
            $band   = $request->query('band', '');

            $q = DB::table('finance_payout_batches')
                ->orderByDesc('batch_date')
                ->orderByDesc('created_at')
                ->limit(200);

            if ($status !== '') {
                $q->where('status', $status);
            }
            if ($medium !== '') {
                $q->where('source_medium', $medium);
            }
            if ($band !== '') {
                $q->where('currency_band', $band);
            }

            $batches = $q->get();
        }

        if (Schema::hasTable('vendor_reservations')) {
            $eligibleCount = DB::table('vendor_reservations')
                ->where('payment_status', 'paid')
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereNull('payout_status')
                ->where('vendor_payout_amount', '>', 0)
                ->count();
        }

        // Summary by medium (INTERNAL – admin only)
        $pendingSummary = collect();
        if (Schema::hasTable('finance_payout_batches')) {
            $pendingSummary = DB::table('finance_payout_batches')
                ->selectRaw('source_medium, currency_band, currency, status, COUNT(*) as batch_count, SUM(net_payout_amount) as total_net')
                ->groupBy('source_medium', 'currency_band', 'currency', 'status')
                ->orderBy('source_medium')
                ->get();
        }

        return view('admin.finance.payouts', [
            'batches'        => $batches,
            'eligibleCount'  => $eligibleCount,
            'pendingSummary' => $pendingSummary,
            'filters'        => ['status' => $request->query('status', ''), 'medium' => $request->query('medium', ''), 'band' => $request->query('band', '')],
        ]);
    });

    // ── POST /portal/admin/finance/payouts/build ──────────────────────────────
    // Build batches for all eligible reservations up to today.
    Route::post('/portal/admin/finance/payouts/build', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $upToDate  = Carbon::today();
        $actorId   = (int) ($user['id'] ?? 0);
        $ledger    = new LedgerWriter();
        $builder   = new PayoutBatchBuilder($ledger);
        $created   = $builder->buildBatchesForDate($upToDate, $actorId);

        return redirect('/portal/admin/finance/payouts')
            ->with('success', 'Payout batches built: ' . count($created));
    });

    // ── POST /portal/admin/finance/payouts/{batch}/send ───────────────────────
    // Mark a batch as submitted to the bank/gateway.
    Route::post('/portal/admin/finance/payouts/{batch}/send', function (Request $request, string $batch): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'bank_reference' => ['required', 'string', 'max:160'],
            'expected_payout_date' => ['nullable', 'date'],
        ]);

        $batchRow = DB::table('finance_payout_batches')
            ->where('batch_ref', $batch)
            ->orWhere('id', is_numeric($batch) ? (int) $batch : 0)
            ->first();

        if (!$batchRow) {
            return redirect('/portal/admin/finance/payouts')->with('error', 'Batch not found.');
        }
        if (!in_array($batchRow->status, ['queued'], true)) {
            return redirect('/portal/admin/finance/payouts')->with('error', "Batch is already {$batchRow->status}.");
        }

        $ledger  = new LedgerWriter();
        $builder = new PayoutBatchBuilder($ledger);
        $actorId = (int) ($user['id'] ?? 0);
        $expectedPayoutAt = null;
        if (!empty($validated['expected_payout_date'])) {
            $expectedPayoutAt = Carbon::parse((string) $validated['expected_payout_date'])->endOfDay();
        }
        $builder->markBatchSent((int) $batchRow->id, $validated['bank_reference'], $actorId, $expectedPayoutAt);

        return redirect('/portal/admin/finance/payouts/' . $batchRow->id)
            ->with('success', 'Batch marked as sent for processing.');
    });

    // ── POST /portal/admin/finance/payouts/{batch}/confirm ────────────────────
    // Confirm a batch as fully settled.
    Route::post('/portal/admin/finance/payouts/{batch}/confirm', function (Request $request, string $batch): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $batchRow = DB::table('finance_payout_batches')
            ->where('batch_ref', $batch)
            ->orWhere('id', is_numeric($batch) ? (int) $batch : 0)
            ->first();

        if (!$batchRow) {
            return redirect('/portal/admin/finance/payouts')->with('error', 'Batch not found.');
        }
        if ($batchRow->status !== 'processing') {
            return redirect('/portal/admin/finance/payouts/' . $batchRow->id)
                ->with('error', "Batch must be in 'processing' status to confirm.");
        }

        $ledger  = new LedgerWriter();
        $builder = new PayoutBatchBuilder($ledger);
        $actorId = (int) ($user['id'] ?? 0);
        $builder->confirmBatchSettled((int) $batchRow->id, $actorId);

        return redirect('/portal/admin/finance/payouts/' . $batchRow->id)
            ->with('success', 'Batch confirmed as settled.');
    });

    // ── GET /portal/admin/finance/payouts/{batch} ─────────────────────────────
    // Batch detail with items (INTERNAL – admin only).
    Route::get('/portal/admin/finance/payouts/{batch}', function (Request $request, string $batch): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        $batchRow = DB::table('finance_payout_batches')
            ->where('batch_ref', $batch)
            ->orWhere('id', is_numeric($batch) ? (int) $batch : 0)
            ->first();

        if (!$batchRow) {
            return redirect('/portal/admin/finance/payouts')->with('error', 'Batch not found.');
        }

        $items = DB::table('finance_payout_batch_items as pbi')
            ->leftJoin('users as vendors', 'vendors.id', '=', 'pbi.vendor_user_id')
            ->where('pbi.batch_id', $batchRow->id)
            ->get([
                'pbi.id',
                'pbi.vendor_user_id',
                'pbi.reservation_ids',
                'pbi.gross_amount',
                'pbi.commission_amount',
                'pbi.gateway_fee_amount',
                'pbi.net_payout_amount',
                'pbi.currency',
                'pbi.bank_account_name',
                'pbi.bank_account_number',
                'pbi.bank_name',
                'pbi.status',
                'pbi.bank_reference',
                'pbi.sent_at',
                'pbi.confirmed_at',
                'vendors.name as vendor_name',
                'vendors.email as vendor_email',
            ]);

        return view('admin.finance.payout-batch-detail', [
            'batch' => $batchRow,
            'items' => $items,
        ]);
    });
});
