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
use Illuminate\Support\Str;

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
        $combineByVendorCurrency = $request->boolean('combine_by_vendor_currency');
        $ledger    = new LedgerWriter();
        $builder   = new PayoutBatchBuilder($ledger);
        $created   = $builder->buildBatchesForDate($upToDate, $actorId, $combineByVendorCurrency);

        $modeLabel = $combineByVendorCurrency
            ? 'combined by vendor+currency'
            : 'separated by medium+currency band';

        return redirect('/portal/admin/finance/payouts')
            ->with('success', 'Payout batches built (' . $modeLabel . '): ' . count($created));
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

    // ── POST /portal/admin/finance/payout-items/{item}/status ────────────────
    // Update a single payout item status with admin audit logging.
    Route::post('/portal/admin/finance/payout-items/{item}/status', function (Request $request, string $item): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        if (!Schema::hasTable('finance_payout_batch_items')) {
            return back()->with('error', 'Payout items table is not available.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:queued,processing,sent,on_hold,confirmed,paid,failed,cancelled'],
            'bank_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $itemRow = DB::table('finance_payout_batch_items')
            ->where('id', is_numeric($item) ? (int) $item : 0)
            ->first();

        if (!$itemRow) {
            return back()->with('error', 'Payout item not found.');
        }

        $oldStatus = strtolower(trim((string) ($itemRow->status ?? 'queued')));
        $newStatus = strtolower(trim((string) ($validated['status'] ?? 'queued')));
        $bankReference = trim((string) ($validated['bank_reference'] ?? ''));
        $notes = trim((string) ($validated['notes'] ?? ''));

        $now = now();
        $itemUpdate = [
            'status' => $newStatus,
            'updated_at' => $now,
        ];
        if ($bankReference !== '') {
            $itemUpdate['bank_reference'] = $bankReference;
        }
        if (in_array($newStatus, ['sent', 'processing'], true)) {
            $itemUpdate['sent_at'] = $itemRow->sent_at ?? $now;
        }
        if (in_array($newStatus, ['confirmed', 'paid'], true)) {
            $itemUpdate['confirmed_at'] = $itemRow->confirmed_at ?? $now;
        }
        if ($notes !== '') {
            $itemUpdate['notes'] = $notes;
        }

        DB::table('finance_payout_batch_items')
            ->where('id', (int) $itemRow->id)
            ->update($itemUpdate);

        $reservationIds = collect(is_string($itemRow->reservation_ids) ? json_decode($itemRow->reservation_ids, true) : ($itemRow->reservation_ids ?? []))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($reservationIds !== [] && Schema::hasTable('vendor_reservations')) {
            $reservationPayoutStatus = match ($newStatus) {
                'queued' => 'queued',
                'processing', 'sent' => 'processing',
                'on_hold', 'failed' => 'on_hold',
                'cancelled' => 'cancelled',
                'confirmed', 'paid' => 'paid',
                default => 'queued',
            };

            $reservationUpdate = [
                'payout_status' => $reservationPayoutStatus,
                'updated_at' => $now,
            ];

            if ($reservationPayoutStatus === 'processing') {
                $reservationUpdate['payout_processing_at'] = $now;
            }

            if ($reservationPayoutStatus === 'paid') {
                $reservationUpdate['payout_paid_at'] = $now;
            }

            DB::table('vendor_reservations')
                ->whereIn('id', $reservationIds)
                ->update($reservationUpdate);
        }

        if (Schema::hasTable('finance_payout_item_status_logs')) {
            DB::table('finance_payout_item_status_logs')->insert([
                'batch_id' => (int) ($itemRow->batch_id ?? 0),
                'item_id' => (int) $itemRow->id,
                'vendor_user_id' => (int) ($itemRow->vendor_user_id ?? 0),
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'bank_reference' => $bankReference !== '' ? $bankReference : (string) ($itemRow->bank_reference ?? ''),
                'notes' => $notes,
                'actor_user_id' => (int) ($user['id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return redirect('/portal/admin/finance/payouts/' . (int) ($itemRow->batch_id ?? 0))
            ->with('success', 'Payout item status updated to ' . strtoupper($newStatus) . '.');
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

        $reservationIds = $items
            ->flatMap(static function ($item): array {
                $decoded = is_string($item->reservation_ids)
                    ? json_decode($item->reservation_ids, true)
                    : ($item->reservation_ids ?? []);

                if (!is_array($decoded)) {
                    return [];
                }

                return collect($decoded)
                    ->map(static fn ($id): int => (int) $id)
                    ->filter(static fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();
            })
            ->unique()
            ->values();

        $reservationLedgerRows = collect();
        if ($reservationIds->isNotEmpty() && Schema::hasTable('vendor_reservations')) {
            $reservationRows = DB::table('vendor_reservations as vr')
                ->leftJoin('users as vendors', 'vendors.id', '=', 'vr.vendor_user_id')
                ->whereIn('vr.id', $reservationIds->all())
                ->get([
                    'vr.id',
                    'vr.vendor_user_id',
                    'vr.vendor_property_id',
                    'vr.vendor_service_id',
                    'vr.payment_gateway',
                    'vr.payment_currency',
                    'vr.payment_amount',
                    'vr.total_amount',
                    'vr.currency',
                    'vr.commission_amount',
                    'vr.gateway_fee_amount',
                    'vr.vendor_payout_amount',
                    'vr.payment_status',
                    'vr.payout_status',
                    'vr.payment_collected_at',
                    'vr.payment_verified_at',
                    'vr.payout_expected_at',
                    'vr.payout_paid_at',
                    'vr.start_at',
                    'vr.end_at',
                    'vr.notes',
                    'vr.payout_batch_item_id',
                    'vendors.name as vendor_name',
                ]);

            $itemMapById = $items->keyBy(static fn ($item) => (int) ($item->id ?? 0));

            $reservationLedgerRows = $reservationRows
                ->map(static function ($row) use ($itemMapById) {
                    $notes = [];
                    if (is_string($row->notes ?? null) && trim((string) $row->notes) !== '') {
                        $decoded = json_decode((string) $row->notes, true);
                        if (is_array($decoded)) {
                            $notes = $decoded;
                        }
                    }

                    $categoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));
                    $categoryLabel = $categoryKey !== ''
                        ? Str::of(str_replace('_', ' ', $categoryKey))->title()->toString()
                        : 'Accommodation';
                    $serviceOrRoomName = trim((string) ($notes['room_name'] ?? $notes['service_label'] ?? ''));
                    if ($serviceOrRoomName === '') {
                        $serviceOrRoomName = 'Service #' . (int) ($row->id ?? 0);
                    }

                    $itemId = (int) ($row->payout_batch_item_id ?? 0);
                    $item = $itemMapById->get($itemId);
                    $itemStatus = strtolower(trim((string) ($item->status ?? '')));
                    if ($itemStatus === '') {
                        $itemStatus = strtolower(trim((string) ($row->payout_status ?? 'queued')));
                    }

                    return (object) [
                        'reservation_id' => (int) ($row->id ?? 0),
                        'vendor_name' => (string) ($row->vendor_name ?? '—'),
                        'service_category' => $categoryLabel,
                        'service_or_room_name' => $serviceOrRoomName,
                        'collected_total_amount' => (float) ($row->payment_amount ?? $row->total_amount ?? 0),
                        'commission_amount' => (float) ($row->commission_amount ?? 0),
                        'gateway_fee_amount' => (float) ($row->gateway_fee_amount ?? 0),
                        'payout_amount' => (float) ($row->vendor_payout_amount ?? 0),
                        'collected_from_customer' => (float) ($row->payment_amount ?? $row->total_amount ?? 0),
                        'payment_currency' => strtoupper(trim((string) ($row->payment_currency ?? $row->currency ?? 'MVR'))),
                        'payment_gateway' => strtoupper(trim((string) ($row->payment_gateway ?? ''))),
                        'collected_date' => (string) ($row->payment_collected_at ?? $row->payment_verified_at ?? ''),
                        'payout_date' => (string) ($row->payout_paid_at ?? ''),
                        'status' => $itemStatus,
                        'item_id' => $itemId,
                    ];
                })
                ->sortByDesc(static function ($row): int {
                    $raw = trim((string) ($row->collected_date ?? ''));
                    if ($raw === '') {
                        return 0;
                    }

                    $time = strtotime($raw);
                    return $time === false ? 0 : $time;
                })
                ->values();
        }

        $itemStatusLogs = collect();
        if (Schema::hasTable('finance_payout_item_status_logs')) {
            $itemStatusLogs = DB::table('finance_payout_item_status_logs as logs')
                ->leftJoin('users as actors', 'actors.id', '=', 'logs.actor_user_id')
                ->where('logs.batch_id', (int) $batchRow->id)
                ->orderByDesc('logs.created_at')
                ->limit(300)
                ->get([
                    'logs.id',
                    'logs.item_id',
                    'logs.vendor_user_id',
                    'logs.from_status',
                    'logs.to_status',
                    'logs.bank_reference',
                    'logs.notes',
                    'logs.actor_user_id',
                    'logs.created_at',
                    'actors.name as actor_name',
                ]);
        }

        return view('admin.finance.payout-batch-detail', [
            'batch' => $batchRow,
            'items' => $items,
            'reservationLedgerRows' => $reservationLedgerRows,
            'itemStatusLogs' => $itemStatusLogs,
        ]);
    });
});
