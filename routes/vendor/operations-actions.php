<?php

use App\Models\User;
use App\Support\ReservationPricingPolicy;
use App\Support\ReservationSettlementCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

Route::post('/portal/vendor/availability/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_availability_slots')) {
        return back()->withErrors(['profile' => 'Vendor availability table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'slot_date' => ['nullable', 'date'],
        'apply_range_from' => ['nullable', 'date'],
        'apply_range_to' => ['nullable', 'date', 'after_or_equal:apply_range_from'],
        'inventory' => ['required', 'integer', 'min:0', 'max:100000'],
        'is_closed' => ['nullable', 'boolean'],
        'listing_category' => ['nullable', 'string', 'max:80'],
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'vendor_service_id' => ['nullable', 'integer', 'min:1'],
        'vendor_room_category_id' => ['nullable', 'integer', 'min:1'],
        'route_name' => ['nullable', 'string', 'max:120'],
        'schedule_profile' => ['nullable', Rule::in(['one_off', 'daily', 'weekly_6', 'weekly_3', 'weekly_custom'])],
        'service_days' => ['nullable', 'array'],
        'service_days.*' => ['integer', 'between:0,6'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $singleDate = filled($validated['slot_date'] ?? null) ? (string) $validated['slot_date'] : '';
    $rangeFrom = filled($validated['apply_range_from'] ?? null) ? (string) $validated['apply_range_from'] : '';
    $rangeTo = filled($validated['apply_range_to'] ?? null) ? (string) $validated['apply_range_to'] : '';

    if ($singleDate === '' && ($rangeFrom === '' || $rangeTo === '')) {
        return back()->withErrors([
            'profile' => 'Provide either a single date or a date range for recurring schedule updates.',
        ])->withInput();
    }

    $scheduleProfile = (string) ($validated['schedule_profile'] ?? 'one_off');
    $submittedServiceDays = collect($validated['service_days'] ?? [])
        ->map(static fn ($day) => (int) $day)
        ->filter(static fn (int $day): bool => $day >= 0 && $day <= 6)
        ->unique()
        ->sort()
        ->values()
        ->all();

    $effectiveServiceDays = $submittedServiceDays;
    if ($scheduleProfile === 'weekly_6') {
        // Default six-day pattern: Monday-Saturday.
        $effectiveServiceDays = [1, 2, 3, 4, 5, 6];
    } elseif ($scheduleProfile === 'weekly_3' && $effectiveServiceDays === []) {
        // Default three-day pattern for marine routes if none selected.
        $effectiveServiceDays = [1, 3, 5];
    }

    if ($scheduleProfile === 'weekly_custom' && $effectiveServiceDays === []) {
        return back()->withErrors([
            'profile' => 'Select at least one service day for weekly custom schedules.',
        ])->withInput();
    }

    $slotDates = [];
    if ($singleDate !== '') {
        $slotDates[] = $singleDate;
    } else {
        $cursor = \Carbon\Carbon::parse($rangeFrom)->startOfDay();
        $last = \Carbon\Carbon::parse($rangeTo)->startOfDay();
        while ($cursor->lessThanOrEqualTo($last)) {
            $slotDates[] = $cursor->toDateString();
            $cursor->addDay();
        }
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($validated['listing_category'] ?? ''));
    $normalizedListingCategory = $canonicalListingCategory ?? strtolower(trim((string) ($validated['listing_category'] ?? '')));
    $vendorPropertyId = filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null;
    $vendorServiceId = filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null;
    $vendorRoomCategoryId = filled($validated['vendor_room_category_id'] ?? null) ? (int) $validated['vendor_room_category_id'] : null;
    $routeName = trim((string) ($validated['route_name'] ?? ''));
    $freeNotes = trim((string) ($validated['notes'] ?? ''));

    $propertyCategoryFromTarget = null;
    if ($vendorPropertyId !== null) {
        $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($vendorPropertyId, $vendorUserId);
        if (!$propertyRecord) {
            return back()->withErrors(['profile' => 'Selected property is not valid for this vendor account.'])->withInput();
        }
        $propertyCategoryFromTarget = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    }

    $serviceCategoryFromTarget = null;
    if ($vendorServiceId !== null && Schema::hasTable('vendor_services')) {
        $serviceRecord = DB::table('vendor_services')
            ->select(['id', 'listing_category'])
            ->where('id', $vendorServiceId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();
        if (!$serviceRecord) {
            return back()->withErrors(['profile' => 'Selected service is not valid for this vendor account.'])->withInput();
        }
        $serviceCategoryFromTarget = vendorPortalCanonicalCategory((string) ($serviceRecord->listing_category ?? ''));
    }

    if ($vendorRoomCategoryId !== null) {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.'])->withInput();
        }

        $roomRecord = DB::table('vendor_property_room_categories')
            ->select(['id', 'vendor_property_id'])
            ->where('id', $vendorRoomCategoryId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();
        if (!$roomRecord) {
            return back()->withErrors(['profile' => 'Selected room category is not valid for this vendor account.'])->withInput();
        }

        if ($vendorPropertyId === null && isset($roomRecord->vendor_property_id)) {
            $vendorPropertyId = (int) $roomRecord->vendor_property_id;
        }
        if ($normalizedListingCategory === '') {
            $normalizedListingCategory = 'accommodation';
        }
    }

    if ($normalizedListingCategory === '') {
        $normalizedListingCategory = $propertyCategoryFromTarget
            ?? $serviceCategoryFromTarget
            ?? '';
    }

    $appliedCount = 0;
    foreach ($slotDates as $slotDate) {
        $slotWeekday = (int) \Carbon\Carbon::parse($slotDate)->dayOfWeek;
        $isWeeklyProfile = in_array($scheduleProfile, ['weekly_6', 'weekly_3', 'weekly_custom'], true);
        if ($isWeeklyProfile && !in_array($slotWeekday, $effectiveServiceDays, true)) {
            continue;
        }

        $meta = array_filter([
            'listing_category' => $normalizedListingCategory,
            'vendor_property_id' => $vendorPropertyId,
            'vendor_service_id' => $vendorServiceId,
            'vendor_room_category_id' => $vendorRoomCategoryId,
            'route_name' => $routeName,
            'schedule_profile' => $scheduleProfile,
            'service_days' => $effectiveServiceDays,
            'text' => $freeNotes,
        ], static fn ($value) => !($value === null || $value === '' || $value === []));

        $encodedNotes = $freeNotes;
        if ($meta !== []) {
            $encodedNotes = json_encode($meta);
        }

        $matchAttributes = [
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => $vendorPropertyId,
            'slot_date' => $slotDate,
        ];
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $matchAttributes['vendor_service_id'] = $vendorServiceId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id')) {
            $matchAttributes['vendor_room_category_id'] = $vendorRoomCategoryId;
        }

        $updatePayload = [
            'inventory' => (int) $validated['inventory'],
            'is_closed' => (bool) ($validated['is_closed'] ?? false),
            'notes' => $encodedNotes,
            'updated_at' => now(),
            'created_at' => now(),
        ];
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $updatePayload['vendor_service_id'] = $vendorServiceId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id')) {
            $updatePayload['vendor_room_category_id'] = $vendorRoomCategoryId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'listing_category')) {
            $updatePayload['listing_category'] = $normalizedListingCategory;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'route_name')) {
            $updatePayload['route_name'] = $routeName;
        }

        DB::table('vendor_availability_slots')->updateOrInsert($matchAttributes, $updatePayload);
        $appliedCount++;
    }

    if ($appliedCount === 0) {
        return back()->withErrors([
            'profile' => 'No slots matched the selected schedule pattern. Adjust range or service days and try again.',
        ])->withInput();
    }

    $message = $appliedCount === 1
        ? 'Availability updated for 1 day.'
        : ('Availability updated for ' . $appliedCount . ' days.');

    return back()->with('portal_notice', $message);
});

Route::post('/portal/vendor/reservations/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    return back()->withErrors([
        'profile' => 'Reservations are customer-generated. Vendors can manage booking status and payments from the reservations dashboard.',
    ]);
});

Route::post('/portal/vendor/transport/tariff/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'tariff_mode' => ['required', Rule::in(['per_trip', 'hourly', 'daily', 'private_hire'])],
        'per_trip_rate' => ['nullable', 'numeric', 'min:0'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'private_hire_rate' => ['nullable', 'numeric', 'min:0'],
    ]);

    $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $validated['vendor_property_id'], $vendorUserId);
    if (!$property) {
        return back()->withErrors(['profile' => 'Selected transport listing was not found for this vendor account.'])->withInput();
    }

    $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
    if (!in_array($listingCategory, ['marine_transport', 'land_transport'], true)) {
        return back()->withErrors(['profile' => 'Tariff options can only be updated for transport listings.'])->withInput();
    }

    $tariffMode = (string) $validated['tariff_mode'];
    $rateByMode = [
        'per_trip' => (float) ($validated['per_trip_rate'] ?? 0),
        'hourly' => (float) ($validated['hourly_rate'] ?? 0),
        'daily' => (float) ($validated['daily_rate'] ?? 0),
        'private_hire' => (float) ($validated['private_hire_rate'] ?? 0),
    ];

    if (($rateByMode[$tariffMode] ?? 0) <= 0) {
        return back()->withErrors(['profile' => 'Provide a tariff amount greater than zero for the selected mode.'])->withInput();
    }

    $details = [];
    if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
        $decoded = json_decode((string) $property->listing_details, true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $details['transport_tariff_mode'] = $tariffMode;
    $details['per_trip_rate'] = $rateByMode['per_trip'];
    $details['hourly_rate'] = $rateByMode['hourly'];
    $details['daily_rate'] = $rateByMode['daily'];
    $details['private_hire_rate'] = $rateByMode['private_hire'];
    if (in_array($tariffMode, ['per_trip', 'hourly', 'daily'], true)) {
        $details['transport_pricing_model'] = $tariffMode;
    } else {
        $details['transport_pricing_model'] = 'per_trip';
    }

    $selectedRate = (float) ($rateByMode[$tariffMode] ?? 0);
    $tableName = vendorPortalCategoryStorageTable($listingCategory);
    if ($tableName !== null && Schema::hasTable($tableName)) {
        $colUpdate = ['details' => json_encode($details), 'updated_at' => now()];
        if (Schema::hasColumn($tableName, 'base_price')) {
            $colUpdate['base_price'] = $selectedRate;
        }
        DB::table($tableName)
            ->where('vendor_property_id', (int) $validated['vendor_property_id'])
            ->where('vendor_user_id', $vendorUserId)
            ->update($colUpdate);
    }

    return back()->with('portal_notice', 'Transport tariff updated.');
});

Route::post('/portal/vendor/transfer/rates/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'transfer_options' => ['required', 'array', 'min:1'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $validated['vendor_property_id'], $vendorUserId);
    if (!$property) {
        return back()->withErrors(['profile' => 'Selected listing was not found for this vendor account.'])->withInput();
    }

    $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
    if (!in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
        return back()->withErrors(['profile' => 'Transfer rates can only be updated for accommodation or remote workspace listings.'])->withInput();
    }

    $details = [];
    if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
        $decoded = json_decode((string) $property->listing_details, true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $transferCatalog = vendorPortalTransferOptionCatalog();
    $configuredTransferOptions = collect(is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : [])
        ->map(static fn ($item): string => strtolower(trim((string) $item)))
        ->filter(static fn (string $item): bool => $item !== '')
        ->values()
        ->all();

    if ($configuredTransferOptions === []) {
        return back()->withErrors(['profile' => 'Set transfer options in listing setup before changing rates from operations.'])->withInput();
    }

    $submittedTransferOptions = collect($validated['transfer_options'] ?? [])
        ->map(static fn ($item): string => strtolower(trim((string) $item)))
        ->filter(static fn (string $item): bool => $item !== '')
        ->unique()
        ->values()
        ->all();

    $invalidTransferOptions = array_values(array_diff($submittedTransferOptions, $transferCatalog));
    if ($invalidTransferOptions !== []) {
        return back()->withErrors(['profile' => 'Transfer options must be selected from the allowed transfer catalog.'])->withInput();
    }

    $disallowedTransferOptions = array_values(array_diff($submittedTransferOptions, $configuredTransferOptions));
    if ($disallowedTransferOptions !== []) {
        return back()->withErrors(['profile' => 'Only transfer options configured in listing setup can be updated from operations.'])->withInput();
    }

    $submittedTransferRates = is_array($validated['transfer_rates'] ?? null)
        ? $validated['transfer_rates']
        : [];

    $currentTransferRates = is_array($details['transfer_rates'] ?? null)
        ? $details['transfer_rates']
        : [];
    $currentTransferRateMatrix = is_array($details['transfer_rate_matrix'] ?? null)
        ? $details['transfer_rate_matrix']
        : [];

    foreach ($submittedTransferOptions as $transferOption) {
        $candidateRate = $submittedTransferRates[$transferOption] ?? null;
        if (!is_numeric($candidateRate) || (float) $candidateRate <= 0) {
            return back()->withErrors(['profile' => 'Provide a transfer rate greater than zero for every selected transfer option.'])->withInput();
        }

        $currentTransferRates[$transferOption] = round((float) $candidateRate, 2);

        $existingMatrix = is_array($currentTransferRateMatrix[$transferOption] ?? null)
            ? $currentTransferRateMatrix[$transferOption]
            : [];
        $currentTransferRateMatrix[$transferOption] = [
            'local_adult_charge' => max(0, (float) ($existingMatrix['local_adult_charge'] ?? 0)),
            'local_child_charge' => max(0, (float) ($existingMatrix['local_child_charge'] ?? 0)),
            // Operations-level transfer update is a single per-pax rate.
            // Keep local matrix values untouched and sync the foreign adult value shown on property pages.
            'foreign_adult_charge' => round((float) $candidateRate, 2),
            'foreign_child_charge' => max(0, (float) ($existingMatrix['foreign_child_charge'] ?? 0)),
            'base_charge_local' => max(0, (float) ($existingMatrix['base_charge_local'] ?? ($details['transfer_base_local'] ?? 0))),
            'base_charge_foreign' => max(0, (float) ($existingMatrix['base_charge_foreign'] ?? ($details['transfer_base_foreign'] ?? 0))),
        ];
    }

    $details['listing_category'] = $listingCategory;
    $details['transfer_pricing_basis'] = 'per_pax';
    $details['transfer_options'] = array_values(array_unique($configuredTransferOptions));
    $details['transfer_rates'] = $currentTransferRates;
    $details['transfer_rate_matrix'] = $currentTransferRateMatrix;

    $tableName = vendorPortalCategoryStorageTable($listingCategory);
    if ($tableName !== null && Schema::hasTable($tableName)) {
        DB::table($tableName)
            ->where('vendor_property_id', (int) $validated['vendor_property_id'])
            ->where('vendor_user_id', $vendorUserId)
            ->update(['details' => json_encode($details), 'updated_at' => now()]);
    }

    if ($listingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
        vendorPortalSyncAccommodationStructuredData((int) $validated['vendor_property_id'], $vendorUserId, $details);
    }

    return back()->with('portal_notice', 'Transfer rates updated for availability and bookings.');
});

Route::post('/portal/vendor/reservations/{reservation}/status', function (Request $request, int $reservation) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_reservations')) {
        return back()->withErrors(['profile' => 'Vendor reservations table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'status' => ['required', Rule::in(['pending', 'confirmed', 'checked_in', 'checked_out', 'completed', 'cancelled'])],
    ]);

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->where('vendor_user_id', $vendorUserId)
        ->first();
    if (!$reservationRow) {
        return back()->withErrors(['profile' => 'Reservation not found for this vendor account.']);
    }

    $requestedStatus = strtolower(trim((string) $validated['status']));
    $priorStatus = strtolower(trim((string) ($reservationRow->status ?? 'pending')));
    $priorPaymentStatus = strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid')));
    $hasOpenDispute = (bool) ($reservationRow->has_open_dispute ?? false);
    $hasRefundCase = (bool) ($reservationRow->has_refund_case ?? false);

    $allowedTransitions = [
        'pending' => ['pending', 'confirmed', 'cancelled'],
        'confirmed' => ['confirmed', 'checked_in', 'cancelled'],
        'checked_in' => ['checked_in', 'checked_out'],
        'checked_out' => ['checked_out', 'completed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    $allowedNextStatuses = $allowedTransitions[$priorStatus] ?? [$priorStatus];
    if (!in_array($requestedStatus, $allowedNextStatuses, true)) {
        return back()->withErrors([
            'profile' => 'Invalid timeline step. Move booking status in sequence: confirmed -> checked-in -> checked-out -> completed.',
        ]);
    }

    $paymentGateway = strtolower(trim((string) ($reservationRow->payment_gateway ?? '')));
    $onlineGatewayProviders = ['stripe', 'bml', 'mib'];
    $isOnlineGatewayReservation = collect($onlineGatewayProviders)->contains(static function (string $provider) use ($paymentGateway): bool {
        return $paymentGateway !== '' && str_contains($paymentGateway, $provider);
    });

    if ($isOnlineGatewayReservation) {
        // Keep gateway-provider guardrail in place; timeline updates do not alter payment state.
    }

    $isPaidReservation = $priorPaymentStatus === 'paid';
    $shouldQueuePayout = $requestedStatus === 'completed' && $isPaidReservation && !$hasOpenDispute && !$hasRefundCase;
    $payoutStatus = strtolower(trim((string) ($reservationRow->payout_status ?? 'queued')));
    if ($requestedStatus === 'completed' && (!$isPaidReservation || $hasOpenDispute || $hasRefundCase)) {
        $payoutStatus = 'on_hold';
    } elseif ($shouldQueuePayout && in_array($payoutStatus, ['', 'queued', 'on_hold'], true)) {
        $payoutStatus = 'queued';
    }

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->where('vendor_user_id', $vendorUserId)
        ->update(array_filter([
            'status' => $requestedStatus,
            'payout_status' => $payoutStatus,
            'payout_expected_at' => $shouldQueuePayout
                ? (($reservationRow->payout_expected_at ?? null) ?: ReservationSettlementCalculator::expectedPayoutAt(
                    $reservationRow->payment_collected_at ?? $reservationRow->payment_verified_at ?? now(),
                    (string) ($reservationRow->payment_gateway ?? ''),
                    null
                ))
                : ($reservationRow->payout_expected_at ?? null),
            'updated_at' => now(),
        ], static fn ($value) => $value !== null));

    $updatedRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if ($updatedRow) {
        $bookingRef = '#' . (int) ($updatedRow->id ?? $reservation);
        $subject = 'Reservation Timeline Updated – Booking ' . $bookingRef;

        $timelineMessage = 'Timeline: ' . strtoupper($priorStatus) . ' -> ' . strtoupper($requestedStatus);
        $payoutTimeline = 'Payout Timeline: ' . strtoupper((string) ($updatedRow->payout_status ?? 'queued'));
        if ((bool) ($updatedRow->has_open_dispute ?? false)) {
            $payoutTimeline .= ' (DISPUTE HOLD)';
        }
        if ((bool) ($updatedRow->has_refund_case ?? false)) {
            $payoutTimeline .= ' (REFUND HOLD)';
        }

        $body = implode("\n", [
            'Reservation timeline update notification:',
            '',
            'Booking Reference: ' . $bookingRef,
            $timelineMessage,
            'Payment: ' . strtoupper($priorPaymentStatus) . ' (read-only in vendor portal)',
            $payoutTimeline,
            'Updated by: Vendor portal',
            'Updated at: ' . now()->format('Y-m-d H:i:s'),
            '',
            'This notice is sent to customer, vendor and admin stakeholders for portal synchronization.',
            '',
            'Workation Team',
        ]);
        workationNotifyReservationStakeholders($updatedRow, $subject, $body);
    }

    if ($shouldQueuePayout) {
        return back()->with('portal_notice', 'Reservation timeline updated. Stay marked completed and payout queued for release window.');
    }

    if ($requestedStatus === 'completed' && (!$isPaidReservation || $hasOpenDispute || $hasRefundCase)) {
        return back()->with('portal_notice', 'Reservation timeline updated. Payout remains on hold until payment and dispute/refund checks are cleared.');
    }

    return back()->with('portal_notice', 'Reservation timeline updated.');
});

Route::post('/portal/vendor/inquiries/{inquiry}/status', function (Request $request, int $inquiry) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'table' => ['required', Rule::in(['vendor_customer_inquiries', 'vendor_inquiries', 'customer_inquiries', 'vendor_messages'])],
        'status' => ['nullable', Rule::in(['open', 'pending', 'in_progress', 'replied', 'resolved', 'closed'])],
        'response' => ['nullable', 'string', 'max:3000'],
    ]);

    $table = (string) $validated['table'];
    if (!Schema::hasTable($table)) {
        return back()->withErrors(['profile' => 'Inquiry source table is not available.']);
    }

    $columns = Schema::getColumnListing($table);
    $idColumn = collect(['id', 'inquiry_id', 'message_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($idColumn === null) {
        return back()->withErrors(['profile' => 'Inquiry source table has no supported identifier column.']);
    }

    $query = DB::table($table)->where($idColumn, $inquiry);

    $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($vendorColumn !== null) {
        $query->where($vendorColumn, $vendorUserId);
    } else {
        $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
        if ($propertyColumn === null) {
            return back()->withErrors(['profile' => 'Unable to verify inquiry ownership for this account.']);
        }

        $vendorPropertyIds = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId)
            ->pluck('vendor_property_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($vendorPropertyIds->isEmpty()) {
            return back()->withErrors(['profile' => 'No vendor listings available to validate inquiry access.']);
        }

        $query->whereIn($propertyColumn, $vendorPropertyIds->all());
    }

    $updates = [];
    $statusColumn = collect(['status', 'inquiry_status', 'state'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($statusColumn !== null && filled($validated['status'] ?? null)) {
        $updates[$statusColumn] = (string) $validated['status'];
    }

    $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response', 'resolution_note'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($responseColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$responseColumn] = trim((string) $validated['response']);
    }

    $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($respondedAtColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$respondedAtColumn] = now();
    }

    if (in_array('updated_at', $columns, true)) {
        $updates['updated_at'] = now();
    }

    if ($updates === []) {
        return back()->withErrors(['profile' => 'No compatible inquiry fields found to update.']);
    }

    $updated = $query->update($updates);
    if ($updated < 1) {
        return back()->withErrors(['profile' => 'Inquiry update did not apply. Verify access and try again.']);
    }

    return back()->with('portal_notice', 'Inquiry updated successfully.');
});

Route::post('/portal/vendor/reviews/{review}/respond', function (Request $request, int $review) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'table' => ['required', Rule::in(['vendor_property_reviews', 'vendor_reviews', 'customer_reviews', 'property_reviews'])],
        'status' => ['nullable', Rule::in(['pending', 'approved', 'published', 'hidden', 'rejected', 'responded'])],
        'response' => ['nullable', 'string', 'max:3000'],
    ]);

    $table = (string) $validated['table'];
    if (!Schema::hasTable($table)) {
        return back()->withErrors(['profile' => 'Review source table is not available.']);
    }

    $columns = Schema::getColumnListing($table);
    $idColumn = collect(['id', 'review_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($idColumn === null) {
        return back()->withErrors(['profile' => 'Review source table has no supported identifier column.']);
    }

    $query = DB::table($table)->where($idColumn, $review);

    $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($vendorColumn !== null) {
        $query->where($vendorColumn, $vendorUserId);
    } else {
        $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
        if ($propertyColumn === null) {
            return back()->withErrors(['profile' => 'Unable to verify review ownership for this account.']);
        }

        $vendorPropertyIds = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId)
            ->pluck('vendor_property_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($vendorPropertyIds->isEmpty()) {
            return back()->withErrors(['profile' => 'No vendor listings available to validate review access.']);
        }

        $query->whereIn($propertyColumn, $vendorPropertyIds->all());
    }

    $updates = [];
    $statusColumn = collect(['status', 'review_status', 'moderation_status'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($statusColumn !== null && filled($validated['status'] ?? null)) {
        $updates[$statusColumn] = (string) $validated['status'];
    }

    $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($responseColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$responseColumn] = trim((string) $validated['response']);
    }

    $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($respondedAtColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$respondedAtColumn] = now();
    }

    if (in_array('updated_at', $columns, true)) {
        $updates['updated_at'] = now();
    }

    if ($updates === []) {
        return back()->withErrors(['profile' => 'No compatible review fields found to update.']);
    }

    $updated = $query->update($updates);
    if ($updated < 1) {
        return back()->withErrors(['profile' => 'Review update did not apply. Verify access and try again.']);
    }

    return back()->with('portal_notice', 'Review response saved.');
});

Route::post('/portal/vendor/pricing/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_pricing_rules')) {
        return back()->withErrors(['profile' => 'Vendor pricing rules table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'rule_type' => ['required', Rule::in(['flat', 'percent', 'nightly', 'weekend_markup', 'demand_discount', 'promo_discount'])],
        'value' => ['required', 'numeric', 'min:0'],
        'starts_on' => ['nullable', 'date'],
        'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'vendor_service_id' => ['nullable', 'integer', 'min:1'],
        'vendor_room_category_id' => ['nullable', 'integer', 'min:1'],
    ]);

    $vendorPropertyId = filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null;
    $vendorServiceId = filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null;
    $vendorRoomCategoryId = filled($validated['vendor_room_category_id'] ?? null) ? (int) $validated['vendor_room_category_id'] : null;

    if ($vendorPropertyId !== null) {
        $propertyExists = \App\Support\VendorPropertyCompatibilityReader::vendorOwnsProperty($vendorPropertyId, $vendorUserId);
        if (!$propertyExists) {
            return back()->withErrors(['profile' => 'Property ID is not valid for this vendor account.'])->withInput();
        }
    }

    if ($vendorServiceId !== null) {
        if (!Schema::hasTable('vendor_services')) {
            return back()->withErrors(['profile' => 'Services table is not ready. Run migrations first.'])->withInput();
        }

        $serviceExists = DB::table('vendor_services')
            ->where('id', $vendorServiceId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();
        if (!$serviceExists) {
            return back()->withErrors(['profile' => 'Service ID is not valid for this vendor account.'])->withInput();
        }
    }

    if ($vendorRoomCategoryId !== null) {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.'])->withInput();
        }

        $roomExists = DB::table('vendor_property_room_categories')
            ->where('id', $vendorRoomCategoryId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();
        if (!$roomExists) {
            return back()->withErrors(['profile' => 'Room category ID is not valid for this vendor account.'])->withInput();
        }
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'vendor_service_id' => $vendorServiceId,
        'name' => trim((string) $validated['name']),
        'rule_type' => (string) $validated['rule_type'],
        'value' => (float) $validated['value'],
        'starts_on' => $validated['starts_on'] ?? null,
        'ends_on' => $validated['ends_on'] ?? null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_pricing_rules', 'vendor_room_category_id')) {
        $payload['vendor_room_category_id'] = $vendorRoomCategoryId;
    }

    DB::table('vendor_pricing_rules')->insert($payload);

    return back()->with('portal_notice', 'Pricing rule saved.');
});

Route::post('/portal/vendor/billing/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_billing_details')) {
        return back()->withErrors(['profile' => 'Vendor billing details table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'business_name' => ['required', 'string', 'max:190'],
        'responsible_person_name' => ['required', 'string', 'max:190'],
        'billing_emails' => ['required', 'string', 'max:2000'],
        'contact_number' => ['required', 'string', 'max:40'],
        'tax_id' => ['nullable', 'string', 'max:120'],
        'invoice_prefix' => ['nullable', 'string', 'max:30'],
        'payout_accounts' => ['required', 'array', 'min:1'],
        'payout_accounts.*.account_label' => ['nullable', 'string', 'max:80'],
        'payout_accounts.*.payout_method' => ['nullable', Rule::in(['bank_transfer', 'mobile_wallet', 'manual'])],
        'payout_accounts.*.beneficiary_name' => ['nullable', 'string', 'max:190'],
        'payout_accounts.*.bank_account_number' => ['nullable', 'string', 'max:60'],
        'payout_accounts.*.bank_name' => ['nullable', 'string', 'max:190'],
        'payout_accounts.*.swift_code' => ['nullable', 'string', 'max:20'],
        'payout_accounts.*.currency' => ['nullable', Rule::in(['MVR', 'USD'])],
        'primary_payout_account' => ['nullable', 'integer', 'min:0'],
        'billing_street_name' => ['required', 'string', 'max:255'],
        'billing_country' => ['required', 'string', 'max:90'],
        'billing_state' => ['required', 'string', 'max:120'],
        'billing_city' => ['required', 'string', 'max:120'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
    ]);

    $existingBillingRow = DB::table('vendor_billing_details')
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    $billingEmails = collect(preg_split('/[\r\n,;]+/', (string) ($validated['billing_emails'] ?? '')))
        ->map(static fn ($email): string => strtolower(trim((string) $email)))
        ->filter(static fn (string $email): bool => $email !== '')
        ->unique()
        ->values();

    if ($billingEmails->isEmpty()) {
        return back()->withErrors(['billing_emails' => 'Provide at least one billing email address.'])->withInput();
    }

    $invalidBillingEmail = $billingEmails->first(static fn (string $email): bool => !filter_var($email, FILTER_VALIDATE_EMAIL));
    if ($invalidBillingEmail !== null) {
        return back()->withErrors(['billing_emails' => 'Billing emails must be valid email addresses.'])->withInput();
    }

    $normalizedAccounts = collect($validated['payout_accounts'] ?? [])
        ->map(static function ($row): array {
            $account = is_array($row) ? $row : [];

            return [
                'account_label' => trim((string) ($account['account_label'] ?? '')),
                'payout_method' => trim((string) ($account['payout_method'] ?? 'bank_transfer')),
                'beneficiary_name' => trim((string) ($account['beneficiary_name'] ?? '')),
                'bank_account_number' => trim((string) ($account['bank_account_number'] ?? '')),
                'bank_name' => trim((string) ($account['bank_name'] ?? '')),
                'swift_code' => strtoupper(trim((string) ($account['swift_code'] ?? ''))),
                'currency' => strtoupper(trim((string) ($account['currency'] ?? 'MVR'))),
            ];
        })
        ->filter(static function (array $account): bool {
            return collect($account)
                ->only(['account_label', 'beneficiary_name', 'bank_account_number', 'bank_name', 'swift_code'])
                ->contains(static fn ($value): bool => trim((string) $value) !== '');
        })
        ->values();

    if ($normalizedAccounts->isEmpty()) {
        return back()->withErrors(['payout_accounts' => 'Add at least one payout account.'])->withInput();
    }

    foreach ($normalizedAccounts as $index => $account) {
        $rowValidator = Validator::make($account, [
            'account_label' => ['nullable', 'string', 'max:80'],
            'payout_method' => ['required', Rule::in(['bank_transfer', 'mobile_wallet', 'manual'])],
            'beneficiary_name' => ['required', 'string', 'max:190'],
            'bank_account_number' => ['required', 'string', 'max:60'],
            'bank_name' => ['required', 'string', 'max:190'],
            'swift_code' => ['nullable', 'string', 'max:20'],
            'currency' => ['required', Rule::in(['MVR', 'USD'])],
        ]);

        if ($rowValidator->fails()) {
            $messages = collect($rowValidator->errors()->all())
                ->map(static fn (string $message): string => 'Payout account ' . ($index + 1) . ': ' . $message)
                ->all();

            return back()->withErrors(['payout_accounts' => implode(' ', $messages)])->withInput();
        }
    }

    $primaryAccountIndex = min(
        max(0, (int) ($validated['primary_payout_account'] ?? 0)),
        max(0, $normalizedAccounts->count() - 1)
    );
    $primaryAccount = $normalizedAccounts->get($primaryAccountIndex, $normalizedAccounts->first());

    $streetName = trim((string) ($validated['billing_street_name'] ?? ''));
    $billingCity = trim((string) ($validated['billing_city'] ?? ''));
    $billingState = trim((string) ($validated['billing_state'] ?? ''));
    $billingCountry = trim((string) ($validated['billing_country'] ?? ''));
    $locationSuffix = implode(', ', array_values(array_filter([$billingCity, $billingState, $billingCountry], static fn (string $value): bool => $value !== '')));
    $composedAddress = trim($streetName . ($locationSuffix !== '' ? ', ' . $locationSuffix : ''));
    $manualAddress = trim((string) ($validated['billing_address'] ?? ''));
    $resolvedBillingAddress = $manualAddress !== '' ? $manualAddress : $composedAddress;
    $bankAccountNumber = trim((string) ($primaryAccount['bank_account_number'] ?? ''));

    $payload = [
        'business_name' => trim((string) $validated['business_name']),
        'tax_id' => trim((string) ($validated['tax_id'] ?? ($existingBillingRow->tax_id ?? ''))),
        'billing_email' => (string) $billingEmails->first(),
        'payout_method' => (string) ($primaryAccount['payout_method'] ?? 'bank_transfer'),
        'beneficiary_name' => trim((string) ($primaryAccount['beneficiary_name'] ?? '')),
        'payout_reference' => trim((string) ($existingBillingRow->payout_reference ?? '')),
        'bank_name' => trim((string) ($primaryAccount['bank_name'] ?? '')),
        'bank_account_number' => $bankAccountNumber,
        'bank_account_last4' => '',
        'billing_street_name' => $streetName,
        'billing_country' => $billingCountry,
        'billing_state' => $billingState,
        'billing_city' => $billingCity,
        'billing_address' => $resolvedBillingAddress,
        'currency' => strtoupper(trim((string) ($primaryAccount['currency'] ?? 'MVR'))),
        'invoice_prefix' => strtoupper(trim((string) ($validated['invoice_prefix'] ?? ($existingBillingRow->invoice_prefix ?? 'INV')))),
        'updated_at' => now(),
        'created_at' => now(),
    ];

    if ($payload['bank_account_last4'] === '' && $bankAccountNumber !== '') {
        $payload['bank_account_last4'] = substr($bankAccountNumber, -4);
    }

    if (Schema::hasColumn('vendor_billing_details', 'billing_emails_json')) {
        $payload['billing_emails_json'] = json_encode($billingEmails->all());
    }

    if (Schema::hasColumn('vendor_billing_details', 'responsible_person_name')) {
        $payload['responsible_person_name'] = trim((string) ($validated['responsible_person_name'] ?? ''));
    }

    if (Schema::hasColumn('vendor_billing_details', 'contact_number')) {
        $payload['contact_number'] = trim((string) ($validated['contact_number'] ?? ''));
    }

    foreach (['beneficiary_name', 'bank_account_number', 'billing_street_name', 'billing_country', 'billing_state', 'billing_city'] as $column) {
        if (!Schema::hasColumn('vendor_billing_details', $column)) {
            unset($payload[$column]);
        }
    }

    if (!Schema::hasColumn('vendor_billing_details', 'bank_account_last4')) {
        unset($payload['bank_account_last4']);
    }

    if (Schema::hasColumn('vendor_billing_details', 'swift_code')) {
        $payload['swift_code'] = strtoupper(trim((string) ($primaryAccount['swift_code'] ?? '')));
    }

    DB::transaction(function () use ($vendorUserId, $payload, $normalizedAccounts, $primaryAccountIndex): void {
        DB::table('vendor_billing_details')->updateOrInsert(
            [
                'vendor_user_id' => $vendorUserId,
            ],
            $payload
        );

        if (Schema::hasTable('vendor_payout_accounts')) {
            DB::table('vendor_payout_accounts')
                ->where('vendor_user_id', $vendorUserId)
                ->delete();

            foreach ($normalizedAccounts->values() as $index => $account) {
                $accountNumber = trim((string) ($account['bank_account_number'] ?? ''));

                DB::table('vendor_payout_accounts')->insert([
                    'vendor_user_id' => $vendorUserId,
                    'account_label' => trim((string) ($account['account_label'] ?? '')),
                    'payout_method' => trim((string) ($account['payout_method'] ?? 'bank_transfer')),
                    'beneficiary_name' => trim((string) ($account['beneficiary_name'] ?? '')),
                    'bank_account_number' => $accountNumber,
                    'bank_account_last4' => $accountNumber !== '' ? substr($accountNumber, -4) : null,
                    'bank_name' => trim((string) ($account['bank_name'] ?? '')),
                    'swift_code' => strtoupper(trim((string) ($account['swift_code'] ?? ''))),
                    'currency' => strtoupper(trim((string) ($account['currency'] ?? 'MVR'))),
                    'is_primary' => $index === $primaryAccountIndex,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    });

    Cache::forget('vendor:portal:billing:v2:' . $vendorUserId);
    Cache::forget('vendor:portal:payout-accounts:v1:' . $vendorUserId);

    return back()->with('portal_notice', 'Billing details updated.');
});

Route::post('/portal/vendor/address/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_billing_details')) {
        return back()->withErrors(['profile' => 'Vendor billing details table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'billing_street_name' => ['required', 'string', 'max:255'],
        'billing_country' => ['required', 'string', 'max:90'],
        'billing_state' => ['required', 'string', 'max:120'],
        'billing_city' => ['required', 'string', 'max:120'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
    ]);

    $streetName = trim((string) $validated['billing_street_name']);
    $billingCountry = trim((string) $validated['billing_country']);
    $billingState = trim((string) $validated['billing_state']);
    $billingCity = trim((string) $validated['billing_city']);
    $manualAddress = trim((string) ($validated['billing_address'] ?? ''));
    $locationSuffix = implode(', ', array_values(array_filter([$billingCity, $billingState, $billingCountry], static fn (string $value): bool => $value !== '')));
    $resolvedAddress = $manualAddress !== '' ? $manualAddress : trim($streetName . ($locationSuffix !== '' ? ', ' . $locationSuffix : ''));

    $payload = [
        'billing_street_name' => $streetName,
        'billing_country' => $billingCountry,
        'billing_state' => $billingState,
        'billing_city' => $billingCity,
        'billing_address' => $resolvedAddress,
        'updated_at' => now(),
        'created_at' => now(),
    ];

    DB::table('vendor_billing_details')->updateOrInsert(
        ['vendor_user_id' => $vendorUserId],
        $payload
    );

    return back()->with('portal_notice', 'Address details updated.');
});

Route::post('/portal/vendor/password/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'current_password' => ['required', 'string', 'min:8'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if (!Hash::check((string) $validated['current_password'], (string) $vendorUser->password)) {
        return back()->withErrors([
            'profile' => 'Current password is incorrect.',
        ])->withInput();
    }

    $vendorUser->password = (string) $validated['password'];
    $vendorUser->save();

    return back()->with('portal_notice', 'Password updated successfully.');
});