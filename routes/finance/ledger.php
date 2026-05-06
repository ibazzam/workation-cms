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

use App\Finance\LedgerWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::middleware('web')->group(function (): void {

    $baseEventTypes = [
        'payment_collected',
        'commission_deducted',
        'gateway_fee_deducted',
        'vendor_payout_queued',
        'vendor_payout_sent',
        'vendor_payout_confirmed',
        'vendor_payout_on_hold',
        'refund_initiated',
        'refund_completed',
        'dispute_opened',
        'dispute_resolved',
        'dispute_lost',
    ];
    $expenseEventTypes = [
        'website_maintenance_expense',
        'domain_expense',
        'subscription_expense',
        'salary_expense',
        'operations_expense',
    ];

    $applyLedgerFilters = static function ($query, array $filters) {
        if (($filters['event_type'] ?? '') !== '') {
            $query->where('fl.event_type', (string) $filters['event_type']);
        }
        if (($filters['medium'] ?? '') !== '') {
            $query->where('fl.source_medium', strtolower((string) $filters['medium']));
        }
        if (($filters['band'] ?? '') !== '') {
            $query->where('fl.currency_band', (string) $filters['band']);
        }
        if ((int) ($filters['vendor_id'] ?? 0) > 0) {
            $query->where('fl.vendor_user_id', (int) $filters['vendor_id']);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $query->where('fl.occurred_at', '>=', (string) $filters['date_from'] . ' 00:00:00');
        }
        if (($filters['date_to'] ?? '') !== '') {
            $query->where('fl.occurred_at', '<=', (string) $filters['date_to'] . ' 23:59:59');
        }

        return $query;
    };

    // ── GET /portal/admin/finance/ledger/export/csv ─────────────────────────
    // Export filtered ledger rows as CSV for monthly finance reporting.
    Route::get('/portal/admin/finance/ledger/export/csv', function (Request $request) use ($applyLedgerFilters): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }
        if (!Schema::hasTable('finance_ledger')) {
            return redirect('/portal/admin/finance/ledger')->with('error', 'Finance ledger table is not available.');
        }

        $reportMonth = trim((string) $request->query('report_month', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        if ($reportMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $reportMonth) === 1) {
            $monthStart = Carbon::parse($reportMonth . '-01')->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            if ($dateFrom === '') {
                $dateFrom = $monthStart->toDateString();
            }
            if ($dateTo === '') {
                $dateTo = $monthEnd->toDateString();
            }
        }

        $filters = [
            'event_type' => trim((string) $request->query('event_type', '')),
            'medium' => strtolower(trim((string) $request->query('medium', ''))),
            'band' => trim((string) $request->query('band', '')),
            'vendor_id' => (int) $request->query('vendor_id', 0),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $rows = $applyLedgerFilters(
            DB::table('finance_ledger as fl')
                ->leftJoin('users as vendors', 'vendors.id', '=', 'fl.vendor_user_id')
                ->orderByDesc('fl.occurred_at'),
            $filters
        )->get([
            'fl.id',
            'fl.event_type',
            'fl.amount',
            'fl.currency',
            'fl.source_medium',
            'fl.currency_band',
            'fl.reservation_id',
            'fl.vendor_user_id',
            'vendors.name as vendor_name',
            'vendors.email as vendor_email',
            'fl.batch_id',
            'fl.gateway_reference',
            'fl.actor_role',
            'fl.notes',
            'fl.occurred_at',
        ]);

        $fileSuffix = $reportMonth !== '' ? $reportMonth : Carbon::now()->format('Y-m-d');
        $fileName = 'finance-ledger-report-' . $fileSuffix . '.csv';

        $response = new StreamedResponse(static function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'ID', 'Event Type', 'Amount', 'Currency', 'Source Medium', 'Currency Band',
                'Reservation ID', 'Vendor User ID', 'Vendor Name', 'Vendor Email',
                'Batch ID', 'Reference', 'Actor Role', 'Notes', 'Occurred At',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    (int) ($row->id ?? 0),
                    (string) ($row->event_type ?? ''),
                    (float) ($row->amount ?? 0),
                    (string) ($row->currency ?? ''),
                    (string) ($row->source_medium ?? ''),
                    (string) ($row->currency_band ?? ''),
                    (int) ($row->reservation_id ?? 0),
                    (int) ($row->vendor_user_id ?? 0),
                    (string) ($row->vendor_name ?? ''),
                    (string) ($row->vendor_email ?? ''),
                    (string) ($row->batch_id ?? ''),
                    (string) ($row->gateway_reference ?? ''),
                    (string) ($row->actor_role ?? ''),
                    trim((string) ($row->notes ?? '')),
                    (string) ($row->occurred_at ?? ''),
                ]);
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    });

    // ── POST /portal/admin/finance/ledger/expenses ──────────────────────────
    // Append manual operating expense events for transparent reporting.
    Route::post('/portal/admin/finance/ledger/expenses', function (Request $request) use ($expenseEventTypes): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        if (!Schema::hasTable('finance_ledger')) {
            return redirect('/portal/admin/finance/ledger')->with('error', 'Finance ledger table is not available.');
        }

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:' . implode(',', $expenseEventTypes)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'in:MVR,USD'],
            'reference_id' => ['nullable', 'string', 'max:160'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $actorId = (int) ($user['id'] ?? 0);
        $amount = -abs((float) ($validated['amount'] ?? 0));
        $currency = strtoupper(trim((string) ($validated['currency'] ?? 'MVR')));
        $referenceId = trim((string) ($validated['reference_id'] ?? ''));
        $notes = trim((string) ($validated['notes'] ?? ''));
        $occurredAt = !empty($validated['occurred_at'])
            ? Carbon::parse((string) $validated['occurred_at'])->endOfDay()
            : Carbon::now();

        $ledger = new LedgerWriter();
        $ledger->append([
            'event_type' => (string) $validated['event_type'],
            'amount' => $amount,
            'currency' => $currency,
            'source_medium' => 'internal',
            'currency_band' => $currency === 'MVR' ? LedgerWriter::BAND_LOCAL_MVR : LedgerWriter::BAND_FOREIGN_USD,
            'gateway_reference' => $referenceId !== '' ? $referenceId : null,
            'actor_role' => 'ADMIN_FINANCE',
            'actor_user_id' => $actorId,
            'notes' => $notes,
            'occurred_at' => $occurredAt,
        ]);

        return redirect('/portal/admin/finance/ledger')->with('success', 'Expense recorded in finance ledger.');
    });

    // ── GET /portal/admin/finance/ledger ──────────────────────────────────────
    Route::get('/portal/admin/finance/ledger', function (Request $request) use ($baseEventTypes, $expenseEventTypes, $applyLedgerFilters): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return redirect('/portal/admin')->with('error', 'Access denied.');
        }

        if (!Schema::hasTable('finance_ledger')) {
            return view('admin.finance.ledger', [
                'events'       => collect(),
                'eventSummary' => [],
                'financialSnapshot' => collect(),
                'availableEventTypes' => array_merge($baseEventTypes, $expenseEventTypes),
                'filters'      => [],
            ]);
        }

        $reportMonth = trim((string) $request->query('report_month', ''));
        $eventType = trim((string) $request->query('event_type', ''));
        $medium = strtolower(trim((string) $request->query('medium', '')));
        $band = trim((string) $request->query('band', ''));
        $vendorId = (int) $request->query('vendor_id', 0);
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        if ($reportMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $reportMonth) === 1) {
            $monthStart = Carbon::parse($reportMonth . '-01')->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            if ($dateFrom === '') {
                $dateFrom = $monthStart->toDateString();
            }
            if ($dateTo === '') {
                $dateTo = $monthEnd->toDateString();
            }
        }

        $filters = [
            'event_type' => $eventType,
            'medium' => $medium,
            'band' => $band,
            'vendor_id' => $vendorId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'report_month' => $reportMonth,
        ];

        $query = $applyLedgerFilters(
            DB::table('finance_ledger as fl')
            ->leftJoin('users as vendors', 'vendors.id', '=', 'fl.vendor_user_id')
            ->orderByDesc('fl.occurred_at')
            ->limit(500),
            $filters
        );

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
        $eventSummary = $applyLedgerFilters(
            DB::table('finance_ledger as fl')
            ->selectRaw('event_type, COUNT(*) as event_count, SUM(amount) as total_amount, currency')
            ->groupBy('fl.event_type', 'fl.currency')
            ->orderBy('event_type')
            ->orderBy('currency'),
            $filters
        )->get();

        // Summary by medium (INTERNAL – admin only)
        $mediumSummary = $applyLedgerFilters(
            DB::table('finance_ledger as fl')
            ->selectRaw('source_medium, currency_band, currency, SUM(amount) as total_amount, COUNT(*) as event_count')
            ->groupBy('fl.source_medium', 'fl.currency_band', 'fl.currency')
            ->orderBy('source_medium')
            ->orderBy('currency_band'),
            $filters
        )->get();

        $financialSnapshot = $applyLedgerFilters(
            DB::table('finance_ledger as fl')
            ->selectRaw("currency,
                SUM(CASE WHEN event_type = 'payment_collected' THEN amount ELSE 0 END) as revenue_collected,
                ABS(SUM(CASE WHEN event_type = 'commission_deducted' THEN amount ELSE 0 END)) as commission_deducted,
                ABS(SUM(CASE WHEN event_type = 'gateway_fee_deducted' THEN amount ELSE 0 END)) as gateway_fee_deducted,
                SUM(CASE WHEN event_type = 'vendor_payout_queued' THEN amount ELSE 0 END) as vendor_payout_queued,
                ABS(SUM(CASE WHEN event_type IN ('refund_initiated','refund_completed') THEN amount ELSE 0 END)) as refunds,
                ABS(SUM(CASE WHEN event_type IN ('website_maintenance_expense','domain_expense','subscription_expense','salary_expense','operations_expense') THEN amount ELSE 0 END)) as operating_expenses")
            ->groupBy('fl.currency')
            ->orderBy('currency'),
            $filters
        )
            ->get()
            ->map(static function ($row) {
                $row->net_after_payout_and_expenses = round(
                    ((float) ($row->revenue_collected ?? 0))
                    - ((float) ($row->gateway_fee_deducted ?? 0))
                    - ((float) ($row->vendor_payout_queued ?? 0))
                    - ((float) ($row->refunds ?? 0))
                    - ((float) ($row->operating_expenses ?? 0)),
                    2
                );

                return $row;
            });

        return view('admin.finance.ledger', [
            'events'        => $events,
            'eventSummary'  => $eventSummary,
            'mediumSummary' => $mediumSummary,
            'financialSnapshot' => $financialSnapshot,
            'availableEventTypes' => array_merge($baseEventTypes, $expenseEventTypes),
            'expenseEventTypes' => $expenseEventTypes,
            'filters'       => $filters,
        ]);
    });
});
