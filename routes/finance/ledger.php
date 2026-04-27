<?php

/**
 * routes/finance/ledger.php
 *
 * Admin-only routes for the immutable finance event ledger.
 * ADMIN_FINANCE and ADMIN_SUPER roles only.
 *
 * All data here is INTERNAL.  source_medium, currency_band, and raw gateway
 * references must NEVER be forwarded to vendor-facing routes or views.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::middleware('web')->group(function (): void {

    // ── GET /portal/admin/finance/ledger ──────────────────────────────────────
    Route::get('/portal/admin/finance/ledger', function (Request $request): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        if (!Schema::hasTable('finance_ledger')) {
            return view('admin.finance.ledger', [
                'events'       => collect(),
                'eventSummary' => [],
                'filters'      => [],
            ]);
        }

        $eventType = $request->query('event_type', '');
        $medium = strtolower((string) $request->query('medium', ''));
        $band = $request->query('band', '');
        $vendorId = (int) $request->query('vendor_id', 0);
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        $query = DB::table('finance_ledger as fl')
            ->leftJoin('users as vendors', 'vendors.id', '=', 'fl.vendor_user_id')
            ->orderByDesc('fl.occurred_at')
            ->limit(500);

        if ($eventType !== '') {
            $query->where('fl.event_type', $eventType);
        }
        if ($medium !== '') {
            $query->where('fl.source_medium', $medium);
        }
        if ($band !== '') {
            $query->where('fl.currency_band', $band);
        }
        if ($vendorId > 0) {
            $query->where('fl.vendor_user_id', $vendorId);
        }
        if ($dateFrom !== '') {
            $query->where('fl.occurred_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $query->where('fl.occurred_at', '<=', $dateTo . ' 23:59:59');
        }

        $events = $query->get([
            'fl.id',
            'fl.event_type',
            'fl.reservation_id',
            'fl.vendor_user_id',
            'fl.amount',
            'fl.currency',
            'fl.source_medium',      // INTERNAL
            'fl.currency_band',      // INTERNAL
            'fl.gateway_reference',  // INTERNAL
            'fl.batch_id',
            'fl.reference_ledger_id',
            'fl.actor_role',
            'fl.notes',
            'fl.occurred_at',
            'vendors.name as vendor_name',
            'vendors.email as vendor_email',
        ]);

        // Summary counts by event type
        $eventSummary = DB::table('finance_ledger')
            ->selectRaw('event_type, COUNT(*) as event_count, SUM(amount) as total_amount, currency')
            ->groupBy('event_type', 'currency')
            ->orderBy('event_type')
            ->get();

        // Summary by medium (INTERNAL – admin only)
        $mediumSummary = DB::table('finance_ledger')
            ->selectRaw('source_medium, currency_band, currency, SUM(amount) as total_amount, COUNT(*) as event_count')
            ->groupBy('source_medium', 'currency_band', 'currency')
            ->orderBy('source_medium')
            ->get();

        return view('admin.finance.ledger', [
            'events'        => $events,
            'eventSummary'  => $eventSummary,
            'mediumSummary' => $mediumSummary,
            'filters'       => [
                'event_type' => $eventType,
                'medium' => $medium,
                'band' => $band,
                'vendor_id' => $vendorId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    });
});
