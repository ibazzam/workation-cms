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
use App\Support\ReservationSettlementCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::middleware('web')->group(function (): void {

    $batchMaturitySnapshot = static function (int $batchId): array {
        if (!Schema::hasTable('finance_payout_batch_items') || !Schema::hasTable('vendor_reservations')) {
            return [
                'ready' => false,
                'maturity_at' => null,
                'blocked_count' => 0,
                'sample_reasons' => ['Payout tables are not available.'],
            ];
        }

        $itemIds = DB::table('finance_payout_batch_items')
            ->where('batch_id', $batchId)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            return [
                'ready' => false,
                'maturity_at' => null,
                'blocked_count' => 0,
                'sample_reasons' => ['Batch has no payout items.'],
            ];
        }

        $rows = DB::table('vendor_reservations')
            ->whereIn('payout_batch_item_id', $itemIds)
            ->get([
                'id',
                'payment_status',
                'payment_gateway',
                'payment_collected_at',
                'payment_verified_at',
                'payout_expected_at',
                'created_at',
            ]);

        if ($rows->isEmpty()) {
            return [
                'ready' => false,
                'maturity_at' => null,
                'blocked_count' => 0,
                'sample_reasons' => ['No linked reservations found.'],
            ];
        }

        $now = Carbon::now();
        $blockedCount = 0;
        $sampleReasons = [];
        $latestExpected = null;

        foreach ($rows as $row) {
            $paymentStatus = strtolower(trim((string) ($row->payment_status ?? '')));
            if ($paymentStatus !== 'paid') {
                $blockedCount++;
                if (count($sampleReasons) < 3) {
                    $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' is not paid';
                }
                continue;
            }

            $expectedAt = !empty($row->payout_expected_at)
                ? Carbon::parse((string) $row->payout_expected_at)
                : ReservationSettlementCalculator::expectedPayoutAt(
                    $row->payment_collected_at ?? $row->payment_verified_at ?? $row->created_at ?? null,
                    (string) ($row->payment_gateway ?? ''),
                    null
                );

            if (!$expectedAt) {
                $blockedCount++;
                if (count($sampleReasons) < 3) {
                    $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' has no settlement date';
                }
                continue;
            }

            if (!$latestExpected || $expectedAt->gt($latestExpected)) {
                $latestExpected = $expectedAt->copy();
            }

            if ($expectedAt->gt($now)) {
                $blockedCount++;
                if (count($sampleReasons) < 3) {
                    $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' matures on ' . $expectedAt->toDateString();
                }
            }
        }

        return [
            'ready' => $blockedCount === 0,
            'maturity_at' => $latestExpected,
            'blocked_count' => $blockedCount,
            'sample_reasons' => $sampleReasons,
        ];
    };

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

            $batches = $batches->map(static function ($batch) use ($batchMaturitySnapshot) {
                $snapshot = $batchMaturitySnapshot((int) ($batch->id ?? 0));

                $batch->maturity_at = $snapshot['maturity_at'];
                $batch->maturity_ready = (bool) ($snapshot['ready'] ?? false);
                $batch->maturity_blocked_count = (int) ($snapshot['blocked_count'] ?? 0);
                $batch->maturity_sample_reasons = (array) ($snapshot['sample_reasons'] ?? []);

                $firstApprovedBy = (int) ($batch->first_approved_by_user_id ?? 0);
                $secondApprovedBy = (int) ($batch->second_approved_by_user_id ?? 0);
                $hasReference = trim((string) ($batch->settlement_reference_id ?? '')) !== '';
                $hasProof = trim((string) ($batch->settlement_reference_proof ?? '')) !== '';

                $batch->is_ready_to_send = $hasReference
                    && $hasProof
                    && $firstApprovedBy > 0
                    && $secondApprovedBy > 0
                    && $firstApprovedBy !== $secondApprovedBy
                    && $batch->maturity_ready;

                return $batch;
            });
        }

        $readyToSendCount = collect($batches ?? [])
            ->filter(static fn ($batch): bool => strtolower((string) ($batch->status ?? '')) === 'queued' && (bool) ($batch->is_ready_to_send ?? false))
            ->count();

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

        $payoutAccountQueue = collect();
        if (Schema::hasTable('vendor_payout_accounts')) {
            $queueStatuses = ['pending_review', 'needs_review', 'rejected'];
            $userHasVendorVerificationStatus = Schema::hasColumn('users', 'vendor_verification_status');
            $userHasBusinessRegistration = Schema::hasColumn('users', 'vendor_business_registration_number');
            $userHasBusinessLicense = Schema::hasColumn('users', 'vendor_business_license_number');
            $userHasVerificationDocuments = Schema::hasColumn('users', 'vendor_verification_documents');
            $billingHasContactNumber = Schema::hasColumn('vendor_billing_details', 'contact_number');

            $queueSelects = [
                'vpa.id',
                'vpa.vendor_user_id',
                'vpa.account_label',
                'vpa.beneficiary_name',
                'vpa.bank_name',
                'vpa.bank_account_last4',
                'vpa.swift_code',
                'vpa.currency',
                'vpa.verification_status',
                'vpa.verification_notes',
                'vpa.created_at',
                'vpa.updated_at',
                'vendors.name as vendor_name',
                'vendors.email as vendor_email',
                DB::raw($userHasVendorVerificationStatus ? 'vendors.vendor_verification_status' : "'' as vendor_verification_status"),
                DB::raw($userHasBusinessRegistration ? 'vendors.vendor_business_registration_number' : "'' as vendor_business_registration_number"),
                DB::raw($userHasBusinessLicense ? 'vendors.vendor_business_license_number' : "'' as vendor_business_license_number"),
                DB::raw($userHasVerificationDocuments ? 'vendors.vendor_verification_documents' : "'' as vendor_verification_documents"),
                'vbd.business_name',
                'vbd.responsible_person_name',
                DB::raw($billingHasContactNumber ? 'vbd.contact_number' : "'' as contact_number"),
            ];

            $payoutAccountQueue = DB::table('vendor_payout_accounts as vpa')
                ->join('users as vendors', 'vendors.id', '=', 'vpa.vendor_user_id')
                ->leftJoin('vendor_billing_details as vbd', 'vbd.vendor_user_id', '=', 'vpa.vendor_user_id')
                ->whereIn('vpa.verification_status', $queueStatuses)
                ->where('vpa.is_active', true)
                ->orderByRaw("CASE vpa.verification_status WHEN 'needs_review' THEN 0 WHEN 'pending_review' THEN 1 ELSE 2 END")
                ->orderByDesc('vpa.updated_at')
                ->limit(200)
                ->get($queueSelects);
        }

        return view('admin.finance.payouts', [
            'batches'        => $batches,
            'eligibleCount'  => $eligibleCount,
            'pendingSummary' => $pendingSummary,
            'payoutAccountQueue' => $payoutAccountQueue,
            'readyToSendCount' => $readyToSendCount,
            'filters'        => ['status' => $request->query('status', ''), 'medium' => $request->query('medium', ''), 'band' => $request->query('band', '')],
        ]);
    });

    // ── POST /portal/admin/finance/payout-accounts/{account}/verify ───────────
    // Manual payout account review by finance admin with business/service/ID checks.
    Route::post('/portal/admin/finance/payout-accounts/{account}/verify', function (Request $request, string $account): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        if (!Schema::hasTable('vendor_payout_accounts')) {
            return back()->with('error', 'Vendor payout accounts table is not available.');
        }

        $validated = $request->validate([
            'verification_status' => ['required', 'in:approved,rejected,pending_review'],
            'crosscheck_business_profile' => ['nullable', 'boolean'],
            'crosscheck_service_profile' => ['nullable', 'boolean'],
            'crosscheck_id_proof' => ['nullable', 'boolean'],
            'sole_proprietor_personal_name_allowed' => ['nullable', 'boolean'],
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        $accountRow = DB::table('vendor_payout_accounts')
            ->where('id', is_numeric($account) ? (int) $account : 0)
            ->first();

        if (!$accountRow) {
            return back()->with('error', 'Payout account not found.');
        }

        $status = strtolower(trim((string) ($validated['verification_status'] ?? 'pending_review')));
        $crossBusiness = (bool) $request->boolean('crosscheck_business_profile');
        $crossService = (bool) $request->boolean('crosscheck_service_profile');
        $crossIdProof = (bool) $request->boolean('crosscheck_id_proof');
        $soleProprietorOverride = (bool) $request->boolean('sole_proprietor_personal_name_allowed');
        $notes = trim((string) ($validated['review_notes'] ?? ''));

        $reviewSummary = [
            'business_profile=' . ($crossBusiness ? 'yes' : 'no'),
            'service_profile=' . ($crossService ? 'yes' : 'no'),
            'id_proof=' . ($crossIdProof ? 'yes' : 'no'),
            'sole_prop_personal_name=' . ($soleProprietorOverride ? 'allowed' : 'not_allowed'),
        ];
        $mergedNotes = implode(' | ', $reviewSummary) . ' | note=' . $notes;

        $now = now();
        DB::table('vendor_payout_accounts')
            ->where('id', (int) $accountRow->id)
            ->update([
                'verification_status' => $status,
                'verification_notes' => $mergedNotes,
                'verified_at' => $status === 'approved' ? $now : null,
                'verified_by_user_id' => $status === 'approved' ? (int) ($user['id'] ?? 0) : null,
                'updated_at' => $now,
            ]);

        if (Schema::hasTable('finance_payout_account_verification_logs')) {
            DB::table('finance_payout_account_verification_logs')->insert([
                'payout_account_id' => (int) $accountRow->id,
                'vendor_user_id' => (int) ($accountRow->vendor_user_id ?? 0),
                'from_status' => strtolower(trim((string) ($accountRow->verification_status ?? 'pending_review'))),
                'to_status' => $status,
                'crosscheck_business_profile' => $crossBusiness,
                'crosscheck_service_profile' => $crossService,
                'crosscheck_id_proof' => $crossIdProof,
                'sole_proprietor_personal_name_allowed' => $soleProprietorOverride,
                'notes' => $notes,
                'actor_user_id' => (int) ($user['id'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $statusLabel = strtoupper(str_replace('_', ' ', $status));
        return back()->with('success', 'Payout account review saved: ' . $statusLabel . '.');
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

    // ── POST /portal/admin/finance/payouts/{batch}/approve-primary ───────────
    // First finance reviewer submits settlement reference proof and first approval.
    Route::post('/portal/admin/finance/payouts/{batch}/approve-primary', function (Request $request, string $batch): mixed {
        $user = $request->session()->get('portal_user');
        if (!$user || !in_array($user['portal_role'] ?? '', ['ADMIN_SUPER', 'ADMIN_FINANCE'], true)) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'settlement_reference_id' => ['required', 'string', 'max:160'],
            'settlement_reference_proof' => ['required', 'string', 'max:2000'],
        ]);

        $batchRow = DB::table('finance_payout_batches')
            ->where('batch_ref', $batch)
            ->orWhere('id', is_numeric($batch) ? (int) $batch : 0)
            ->first();

        if (!$batchRow) {
            return redirect('/portal/admin/finance/payouts')->with('error', 'Batch not found.');
        }
        if (strtolower((string) ($batchRow->status ?? '')) !== 'queued') {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', 'Only queued batches can be approved for release.');
        }

        $actorId = (int) ($user['id'] ?? 0);
        $now = Carbon::now();

        $updatePayload = [
            'settlement_reference_id' => trim((string) $validated['settlement_reference_id']),
            'settlement_reference_proof' => trim((string) $validated['settlement_reference_proof']),
            'first_approved_by_user_id' => $actorId,
            'first_approved_at' => $now,
            'updated_at' => $now,
        ];

        if (empty($batchRow->settlement_verified_at)) {
            $updatePayload['settlement_verified_at'] = $now;
            $updatePayload['settlement_verified_by_user_id'] = $actorId;
        }

        DB::table('finance_payout_batches')
            ->where('id', (int) $batchRow->id)
            ->update($updatePayload);

        return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
            ->with('success', 'Primary approval saved with bank settlement proof. Awaiting second approver.');
    });

    // ── POST /portal/admin/finance/payouts/{batch}/approve-secondary ─────────
    // Second finance reviewer approval (must be a different user).
    Route::post('/portal/admin/finance/payouts/{batch}/approve-secondary', function (Request $request, string $batch): mixed {
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
        if (strtolower((string) ($batchRow->status ?? '')) !== 'queued') {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', 'Only queued batches can receive secondary approval.');
        }

        $firstApprovedBy = (int) ($batchRow->first_approved_by_user_id ?? 0);
        if ($firstApprovedBy <= 0) {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', 'Primary approval is required before secondary approval.');
        }

        $actorId = (int) ($user['id'] ?? 0);
        if ($actorId === $firstApprovedBy) {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', '4-eyes policy: secondary approver must be different from primary approver.');
        }

        $hasReference = trim((string) ($batchRow->settlement_reference_id ?? '')) !== '';
        $hasProof = trim((string) ($batchRow->settlement_reference_proof ?? '')) !== '';
        if (!$hasReference || !$hasProof) {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', 'Settlement reference proof is required before secondary approval.');
        }

        DB::table('finance_payout_batches')
            ->where('id', (int) $batchRow->id)
            ->update([
                'second_approved_by_user_id' => $actorId,
                'second_approved_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
            ->with('success', 'Secondary approval captured. Batch is now eligible for send when maturity is reached.');
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

        $hasReference = trim((string) ($batchRow->settlement_reference_id ?? '')) !== '';
        $hasProof = trim((string) ($batchRow->settlement_reference_proof ?? '')) !== '';
        $firstApprovedBy = (int) ($batchRow->first_approved_by_user_id ?? 0);
        $secondApprovedBy = (int) ($batchRow->second_approved_by_user_id ?? 0);
        if (!$hasReference || !$hasProof || $firstApprovedBy <= 0 || $secondApprovedBy <= 0 || $firstApprovedBy === $secondApprovedBy) {
            return redirect('/portal/admin/finance/payouts/' . (int) $batchRow->id)
                ->with('error', 'Send blocked: settlement reference proof and 4-eyes approvals are required first.');
        }

        $ledger  = new LedgerWriter();
        $builder = new PayoutBatchBuilder($ledger);
        $actorId = (int) ($user['id'] ?? 0);
        $expectedPayoutAt = null;
        if (!empty($validated['expected_payout_date'])) {
            $expectedPayoutAt = Carbon::parse((string) $validated['expected_payout_date'])->endOfDay();
        }
        try {
            $builder->markBatchSent((int) $batchRow->id, $validated['bank_reference'], $actorId, $expectedPayoutAt);
        } catch (\RuntimeException $e) {
            return redirect('/portal/admin/finance/payouts/' . $batchRow->id)
                ->with('error', $e->getMessage());
        }

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
                'pbi.payout_account_id',
                'pbi.reservation_ids',
                'pbi.gross_amount',
                'pbi.commission_amount',
                'pbi.gateway_fee_amount',
                'pbi.net_payout_amount',
                'pbi.currency',
                'pbi.payout_account_currency',
                'pbi.payout_account_verification_status',
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

        $batchMaturitySnapshot = static function (int $batchId): array {
            if (!Schema::hasTable('finance_payout_batch_items') || !Schema::hasTable('vendor_reservations')) {
                return ['ready' => false, 'maturity_at' => null, 'blocked_count' => 0, 'sample_reasons' => ['Payout tables are not available.']];
            }
            $itemIds = DB::table('finance_payout_batch_items')->where('batch_id', $batchId)->pluck('id');
            if ($itemIds->isEmpty()) {
                return ['ready' => false, 'maturity_at' => null, 'blocked_count' => 0, 'sample_reasons' => ['Batch has no payout items.']];
            }
            $rows = DB::table('vendor_reservations')->whereIn('payout_batch_item_id', $itemIds)->get(['id', 'payment_status', 'payment_gateway', 'payment_collected_at', 'payment_verified_at', 'payout_expected_at', 'created_at']);
            if ($rows->isEmpty()) {
                return ['ready' => false, 'maturity_at' => null, 'blocked_count' => 0, 'sample_reasons' => ['No linked reservations found.']];
            }
            $now = Carbon::now();
            $blockedCount = 0;
            $sampleReasons = [];
            $latestExpected = null;
            foreach ($rows as $row) {
                $paymentStatus = strtolower(trim((string) ($row->payment_status ?? '')));
                if ($paymentStatus !== 'paid') {
                    $blockedCount++;
                    if (count($sampleReasons) < 3) {
                        $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' is not paid';
                    }
                    continue;
                }
                $expectedAt = !empty($row->payout_expected_at)
                    ? Carbon::parse((string) $row->payout_expected_at)
                    : ReservationSettlementCalculator::expectedPayoutAt($row->payment_collected_at ?? $row->payment_verified_at ?? $row->created_at ?? null, (string) ($row->payment_gateway ?? ''), null);
                if (!$expectedAt) {
                    $blockedCount++;
                    if (count($sampleReasons) < 3) {
                        $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' has no settlement date';
                    }
                    continue;
                }
                if (!$latestExpected || $expectedAt->gt($latestExpected)) {
                    $latestExpected = $expectedAt->copy();
                }
                if ($expectedAt->gt($now)) {
                    $blockedCount++;
                    if (count($sampleReasons) < 3) {
                        $sampleReasons[] = 'Reservation #' . (int) ($row->id ?? 0) . ' matures on ' . $expectedAt->toDateString();
                    }
                }
            }
            return ['ready' => $blockedCount === 0, 'maturity_at' => $latestExpected, 'blocked_count' => $blockedCount, 'sample_reasons' => $sampleReasons];
        };

        $maturity = $batchMaturitySnapshot((int) $batchRow->id);
        $firstApprovedBy = (int) ($batchRow->first_approved_by_user_id ?? 0);
        $secondApprovedBy = (int) ($batchRow->second_approved_by_user_id ?? 0);
        $hasReference = trim((string) ($batchRow->settlement_reference_id ?? '')) !== '';
        $hasProof = trim((string) ($batchRow->settlement_reference_proof ?? '')) !== '';
        $isReadyToSend = $hasReference
            && $hasProof
            && $firstApprovedBy > 0
            && $secondApprovedBy > 0
            && $firstApprovedBy !== $secondApprovedBy
            && (bool) ($maturity['ready'] ?? false);

        return view('admin.finance.payout-batch-detail', [
            'batch' => $batchRow,
            'items' => $items,
            'reservationLedgerRows' => $reservationLedgerRows,
            'itemStatusLogs' => $itemStatusLogs,
            'maturitySnapshot' => $maturity,
            'isReadyToSend' => $isReadyToSend,
            'currentFinanceUserId' => (int) ($user['id'] ?? 0),
        ]);
    });
});
