<?php

use App\Models\User;
use App\Support\ReservationPricingPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

Route::post('/portal/vendor/rooms/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
        'room_size_sqm' => ['nullable', 'integer', 'min:5', 'max:2000'],
        'floor_info' => ['nullable', 'string', 'max:80'],
        'has_window' => ['nullable', 'in:0,1'],
        'non_smoking' => ['nullable', 'in:0,1'],
        'extra_person_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'child_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bed_type' => ['nullable', 'string', 'max:80'],
        'room_amenities' => ['nullable', 'array'],
        'room_amenities.*' => ['required', 'string', 'max:80'],
        'bathroom_type' => ['nullable', Rule::in(['ensuite', 'private_external', 'shared'])],
        'bathroom_count' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bathroom_amenities' => ['nullable', 'array'],
        'bathroom_amenities.*' => ['required', 'string', 'max:80'],
        'room_features' => ['nullable', 'array'],
        'room_features.*' => ['required', 'string', 'max:80'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price_local' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price_usd' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price_local' => ['nullable', 'numeric', 'min:0'],
        'child_price_usd' => ['nullable', 'numeric', 'min:0'],
        'child_price_local' => ['nullable', 'numeric', 'min:0'],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'extra_bed_policy' => ['nullable', 'string', 'max:3000'],
    ]);

    // MVR/USD rate — used to auto-compute MVR equivalents from vendor USD inputs.
    // Booking POST and payment processing always operate in MVR.
    $mvrUsdRate = (float) env('MVR_USD_RATE', 15.42);
    $mvrFromUsd = static fn (float $usd): float => $usd > 0 ? round($usd * $mvrUsdRate, 2) : 0.0;

    $roomAmenities = vendorPortalNormalizedStringList($validated['room_amenities'] ?? []);
    $roomFeatures = vendorPortalNormalizedStringList($validated['room_features'] ?? []);
    $bathroomAmenities = vendorPortalNormalizedStringList($validated['bathroom_amenities'] ?? []);
    $roomAmenityTokens = array_values(array_unique(array_merge($roomAmenities, $roomFeatures)));
    $bathroomAmenityTokens = array_values(array_unique($bathroomAmenities));
    $submittedBedType = trim((string) ($validated['bed_type'] ?? ''));
    $submittedBathroomType = trim((string) ($validated['bathroom_type'] ?? ''));
    $invalidRoomAmenities = vendorPortalDisallowedOptionValues('room_amenity', $roomAmenityTokens);
    if ($invalidRoomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more room amenities are not in the allowed catalog.',
        ])->withInput();
    }
    $invalidBathroomAmenities = vendorPortalDisallowedOptionValues('bathroom_amenity', $bathroomAmenityTokens);
    if ($invalidBathroomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more bathroom amenities are not in the allowed catalog.',
        ])->withInput();
    }
    if ($submittedBedType !== '') {
        $invalidBedTypes = vendorPortalDisallowedOptionValues('room_bed_type', [$submittedBedType]);
        if ($invalidBedTypes !== []) {
            return back()->withErrors([
                'profile' => 'Selected room bed type is not in the allowed catalog.',
            ])->withInput();
        }
    }
    if ($submittedBathroomType === 'shared' && (int) ($validated['bathroom_count'] ?? 0) === 0) {
        return back()->withErrors([
            'profile' => 'Provide bathroom count when bathroom type is shared.',
        ])->withInput();
    }

    $vendorPropertyId = (int) ($validated['vendor_property_id'] ?? 0);

    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($vendorPropertyId, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Select a valid property owned by your vendor account.'])->withInput();
    }

    $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    if ($propertyCategory !== 'accommodation') {
        return back()->withErrors(['profile' => 'Room categories can only be added under accommodation listings.'])->withInput();
    }

    $roUsd = (float) ($validated['meal_plan_room_only_price_usd'] ?? 0);
    $roMvr = $mvrFromUsd($roUsd);
    $legacyBasePrice = (float) ($validated['base_price'] ?? 0);
    $resolvedBasePrice = $roMvr > 0 ? $roMvr : $legacyBasePrice;

    $insertPayload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenityTokens),
        'base_price' => $resolvedBasePrice,
        'currency' => 'MVR',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_property_room_categories', 'room_size_sqm')) {
        $insertPayload['room_size_sqm'] = isset($validated['room_size_sqm']) ? (int) $validated['room_size_sqm'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'floor_info')) {
        $floorInfo = trim((string) ($validated['floor_info'] ?? ''));
        $insertPayload['floor_info'] = $floorInfo !== '' ? $floorInfo : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'has_window')) {
        $insertPayload['has_window'] = (int) ($validated['has_window'] ?? 1) === 1;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'non_smoking')) {
        $insertPayload['non_smoking'] = (int) ($validated['non_smoking'] ?? 1) === 1;
    }

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
        $insertPayload['extra_person_capacity'] = (int) ($validated['extra_person_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
        $insertPayload['child_capacity'] = (int) ($validated['child_capacity'] ?? 0);
    }
    // Foreign segment: vendor entered USD; compute and store MVR equivalent for booking math.
    $bbUsd   = (float) ($validated['meal_plan_bb_price_usd'] ?? 0);
    $hbUsd   = (float) ($validated['meal_plan_hb_price_usd'] ?? 0);
    $fbUsd   = (float) ($validated['meal_plan_fb_price_usd'] ?? 0);
    $aiUsd   = (float) ($validated['meal_plan_ai_price_usd'] ?? 0);
    $epUsd   = (float) ($validated['extra_person_price_usd'] ?? 0);
    $chUsd   = (float) ($validated['child_price_usd'] ?? 0);

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
        $insertPayload['extra_person_price'] = $mvrFromUsd($epUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_usd')) {
        $insertPayload['extra_person_price_usd'] = $epUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
        $insertPayload['child_price'] = $mvrFromUsd($chUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price_usd')) {
        $insertPayload['child_price_usd'] = $chUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price')) {
        $insertPayload['meal_plan_room_only_price'] = $roMvr > 0 ? $roMvr : $resolvedBasePrice;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_usd')) {
        $insertPayload['meal_plan_room_only_price_usd'] = $roUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price')) {
        $insertPayload['meal_plan_bb_price'] = $mvrFromUsd($bbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_usd')) {
        $insertPayload['meal_plan_bb_price_usd'] = $bbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price')) {
        $insertPayload['meal_plan_hb_price'] = $mvrFromUsd($hbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_usd')) {
        $insertPayload['meal_plan_hb_price_usd'] = $hbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price')) {
        $insertPayload['meal_plan_fb_price'] = $mvrFromUsd($fbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_usd')) {
        $insertPayload['meal_plan_fb_price_usd'] = $fbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price')) {
        $insertPayload['meal_plan_ai_price'] = $mvrFromUsd($aiUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_usd')) {
        $insertPayload['meal_plan_ai_price_usd'] = $aiUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_local')) {
        $insertPayload['meal_plan_room_only_price_local'] = (float) ($validated['meal_plan_room_only_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_local')) {
        $insertPayload['meal_plan_bb_price_local'] = (float) ($validated['meal_plan_bb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_local')) {
        $insertPayload['meal_plan_hb_price_local'] = (float) ($validated['meal_plan_hb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_local')) {
        $insertPayload['meal_plan_fb_price_local'] = (float) ($validated['meal_plan_fb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_local')) {
        $insertPayload['meal_plan_ai_price_local'] = (float) ($validated['meal_plan_ai_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_local')) {
        $insertPayload['extra_person_price_local'] = (float) ($validated['extra_person_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price_local')) {
        $insertPayload['child_price_local'] = (float) ($validated['child_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $insertPayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $insertPayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $insertPayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_policy')) {
        $childPolicy = trim((string) ($validated['child_policy'] ?? ''));
        $insertPayload['child_policy'] = $childPolicy !== '' ? $childPolicy : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_bed_policy')) {
        $extraBedPolicy = trim((string) ($validated['extra_bed_policy'] ?? ''));
        $insertPayload['extra_bed_policy'] = $extraBedPolicy !== '' ? $extraBedPolicy : null;
    }

    DB::table('vendor_property_room_categories')->insert($insertPayload);

    return vendorPortalListingsBackResponse('Room category added.', 3);
});

Route::post('/portal/vendor/rooms/{room}/update', function (Request $request, int $room) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $roomRecord = DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$roomRecord) {
        return back()->withErrors(['profile' => 'Room category not found for this vendor account.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
        'room_size_sqm' => ['nullable', 'integer', 'min:5', 'max:2000'],
        'floor_info' => ['nullable', 'string', 'max:80'],
        'has_window' => ['nullable', 'in:0,1'],
        'non_smoking' => ['nullable', 'in:0,1'],
        'extra_person_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'child_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bed_type' => ['nullable', 'string', 'max:80'],
        'room_amenities' => ['nullable', 'array'],
        'room_amenities.*' => ['required', 'string', 'max:80'],
        'bathroom_type' => ['nullable', Rule::in(['ensuite', 'private_external', 'shared'])],
        'bathroom_count' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bathroom_amenities' => ['nullable', 'array'],
        'bathroom_amenities.*' => ['required', 'string', 'max:80'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price_local' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price_usd' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price_local' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price_usd' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price_local' => ['nullable', 'numeric', 'min:0'],
        'child_price_usd' => ['nullable', 'numeric', 'min:0'],
        'child_price_local' => ['nullable', 'numeric', 'min:0'],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'extra_bed_policy' => ['nullable', 'string', 'max:3000'],
    ]);

    // MVR/USD rate — used to auto-compute MVR equivalents from vendor USD inputs.
    $mvrUsdRate = (float) env('MVR_USD_RATE', 15.42);
    $mvrFromUsd = static fn (float $usd): float => $usd > 0 ? round($usd * $mvrUsdRate, 2) : 0.0;

    $roomAmenities = vendorPortalNormalizedStringList($validated['room_amenities'] ?? []);
    $bathroomAmenities = vendorPortalNormalizedStringList($validated['bathroom_amenities'] ?? []);
    $bathroomAmenityTokens = array_values(array_unique($bathroomAmenities));
    $submittedBedType = trim((string) ($validated['bed_type'] ?? ''));
    $submittedBathroomType = trim((string) ($validated['bathroom_type'] ?? ''));
    $invalidRoomAmenities = vendorPortalDisallowedOptionValues('room_amenity', $roomAmenities);
    if ($invalidRoomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more room amenities are not in the allowed catalog.',
        ])->withInput();
    }
    $invalidBathroomAmenities = vendorPortalDisallowedOptionValues('bathroom_amenity', $bathroomAmenityTokens);
    if ($invalidBathroomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more bathroom amenities are not in the allowed catalog.',
        ])->withInput();
    }
    if ($submittedBedType !== '') {
        $invalidBedTypes = vendorPortalDisallowedOptionValues('room_bed_type', [$submittedBedType]);
        if ($invalidBedTypes !== []) {
            return back()->withErrors([
                'profile' => 'Selected room bed type is not in the allowed catalog.',
            ])->withInput();
        }
    }
    if ($submittedBathroomType === 'shared' && (int) ($validated['bathroom_count'] ?? 0) === 0) {
        return back()->withErrors([
            'profile' => 'Provide bathroom count when bathroom type is shared.',
        ])->withInput();
    }

    if (isset($roomRecord->vendor_property_id)) {
        $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $roomRecord->vendor_property_id, $vendorUserId);
        $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
        if ($propertyCategory !== 'accommodation') {
            return back()->withErrors(['profile' => 'Only rooms under accommodation listings can be updated here.'])->withInput();
        }
    }

    $roUsd = (float) ($validated['meal_plan_room_only_price_usd'] ?? 0);
    $roMvr = $mvrFromUsd($roUsd);
    $legacyBasePrice = (float) ($validated['base_price'] ?? 0);
    $resolvedBasePrice = $roMvr > 0 ? $roMvr : $legacyBasePrice;

    $updatePayload = [
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenities),
        'base_price' => $resolvedBasePrice,
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_property_room_categories', 'room_size_sqm')) {
        $updatePayload['room_size_sqm'] = isset($validated['room_size_sqm']) ? (int) $validated['room_size_sqm'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'floor_info')) {
        $floorInfo = trim((string) ($validated['floor_info'] ?? ''));
        $updatePayload['floor_info'] = $floorInfo !== '' ? $floorInfo : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'has_window')) {
        $updatePayload['has_window'] = (int) ($validated['has_window'] ?? 1) === 1;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'non_smoking')) {
        $updatePayload['non_smoking'] = (int) ($validated['non_smoking'] ?? 1) === 1;
    }

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
        $updatePayload['extra_person_capacity'] = (int) ($validated['extra_person_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
        $updatePayload['child_capacity'] = (int) ($validated['child_capacity'] ?? 0);
    }
    // Foreign segment: vendor entered USD; compute and store MVR equivalent for booking math.
    $bbUsd   = (float) ($validated['meal_plan_bb_price_usd'] ?? 0);
    $hbUsd   = (float) ($validated['meal_plan_hb_price_usd'] ?? 0);
    $fbUsd   = (float) ($validated['meal_plan_fb_price_usd'] ?? 0);
    $aiUsd   = (float) ($validated['meal_plan_ai_price_usd'] ?? 0);
    $epUsd   = (float) ($validated['extra_person_price_usd'] ?? 0);
    $chUsd   = (float) ($validated['child_price_usd'] ?? 0);

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
        $updatePayload['extra_person_price'] = $mvrFromUsd($epUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_usd')) {
        $updatePayload['extra_person_price_usd'] = $epUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
        $updatePayload['child_price'] = $mvrFromUsd($chUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price_usd')) {
        $updatePayload['child_price_usd'] = $chUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price')) {
        $updatePayload['meal_plan_room_only_price'] = $roMvr > 0 ? $roMvr : $resolvedBasePrice;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_usd')) {
        $updatePayload['meal_plan_room_only_price_usd'] = $roUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price')) {
        $updatePayload['meal_plan_bb_price'] = $mvrFromUsd($bbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_usd')) {
        $updatePayload['meal_plan_bb_price_usd'] = $bbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price')) {
        $updatePayload['meal_plan_hb_price'] = $mvrFromUsd($hbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_usd')) {
        $updatePayload['meal_plan_hb_price_usd'] = $hbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price')) {
        $updatePayload['meal_plan_fb_price'] = $mvrFromUsd($fbUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_usd')) {
        $updatePayload['meal_plan_fb_price_usd'] = $fbUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price')) {
        $updatePayload['meal_plan_ai_price'] = $mvrFromUsd($aiUsd);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_usd')) {
        $updatePayload['meal_plan_ai_price_usd'] = $aiUsd;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_local')) {
        $updatePayload['meal_plan_room_only_price_local'] = (float) ($validated['meal_plan_room_only_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_local')) {
        $updatePayload['meal_plan_bb_price_local'] = (float) ($validated['meal_plan_bb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_local')) {
        $updatePayload['meal_plan_hb_price_local'] = (float) ($validated['meal_plan_hb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_local')) {
        $updatePayload['meal_plan_fb_price_local'] = (float) ($validated['meal_plan_fb_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_local')) {
        $updatePayload['meal_plan_ai_price_local'] = (float) ($validated['meal_plan_ai_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_local')) {
        $updatePayload['extra_person_price_local'] = (float) ($validated['extra_person_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price_local')) {
        $updatePayload['child_price_local'] = (float) ($validated['child_price_local'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $updatePayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $updatePayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $updatePayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_policy')) {
        $childPolicy = trim((string) ($validated['child_policy'] ?? ''));
        $updatePayload['child_policy'] = $childPolicy !== '' ? $childPolicy : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_bed_policy')) {
        $extraBedPolicy = trim((string) ($validated['extra_bed_policy'] ?? ''));
        $updatePayload['extra_bed_policy'] = $extraBedPolicy !== '' ? $extraBedPolicy : null;
    }

    DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->update($updatePayload);

    return vendorPortalListingsBackResponse('Room category updated.', 3);
});

Route::post('/portal/vendor/rooms/{room}/delete', function (int $room) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->delete();

    return vendorPortalListingsBackResponse('Room category removed.', 3);
});

Route::post('/portal/vendor/water-sports-equipment/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_water_sports_rental_items')) {
        return back()->withErrors(['profile' => 'Water sports rental items table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'string', 'max:160'],
        'equipment_type' => ['nullable', 'string', 'max:80'],
        'equipment_category' => ['nullable', 'string', 'max:40'],
        'description' => ['nullable', 'string', 'max:3000'],
        'pricing_type' => ['nullable', Rule::in(['hourly', 'per_seat'])],
        'price_per_hour_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_child_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_child_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_adult_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_adult_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_child_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_child_usd' => ['nullable', 'numeric', 'min:0'],
        'min_age_years' => ['nullable', 'integer', 'min:0', 'max:120'],
        'requires_swimming' => ['nullable', 'boolean'],
        'safety_notes' => ['nullable', 'string', 'max:1000'],
        'min_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'max_duration_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
        'quantity_available' => ['nullable', 'integer', 'min:1', 'max:10000'],
    ]);

    $vendorPropertyId = (int) ($validated['vendor_property_id'] ?? 0);
    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($vendorPropertyId, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Select a valid property owned by your vendor account.'])->withInput();
    }

    $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    if ($propertyCategory !== 'water_sports') {
        return back()->withErrors(['profile' => 'Rental equipment items can only be added under water sports listings.'])->withInput();
    }

    $allowedEquipmentTypes = ['jetski', 'snorkeling_gear', 'canoe', 'surfboard', 'paddleboard', 'banana_boat', 'parasailing', 'windsurf', 'other'];
    $allowedCategories = ['motorized', 'non_motorized', 'adrenaline', 'guided', 'snorkeling_diving', 'other'];
    
    $equipmentType = trim((string) ($validated['equipment_type'] ?? 'other'));
    if (!in_array($equipmentType, $allowedEquipmentTypes, true)) {
        $equipmentType = 'other';
    }
    
    $equipmentCategory = trim((string) ($validated['equipment_category'] ?? 'non_motorized'));
    if (!in_array($equipmentCategory, $allowedCategories, true)) {
        $equipmentCategory = 'non_motorized';
    }

    $pricingType = in_array((string) ($validated['pricing_type'] ?? 'hourly'), ['hourly', 'per_seat'], true)
        ? $validated['pricing_type'] : 'hourly';

    DB::table('vendor_water_sports_rental_items')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'name' => trim((string) $validated['name']),
        'equipment_type' => $equipmentType,
        'equipment_category' => $equipmentCategory,
        'description' => trim((string) ($validated['description'] ?? '')),
        'pricing_type' => $pricingType,
        'price_per_hour_local' => max(0, (float) ($validated['price_per_hour_local'] ?? 0)),
        'price_per_hour_usd' => max(0, (float) ($validated['price_per_hour_usd'] ?? 0)),
        'price_per_hour_child_local' => max(0, (float) ($validated['price_per_hour_child_local'] ?? 0)),
        'price_per_hour_child_usd' => max(0, (float) ($validated['price_per_hour_child_usd'] ?? 0)),
        'price_per_seat_adult_local' => max(0, (float) ($validated['price_per_seat_adult_local'] ?? 0)),
        'price_per_seat_adult_usd' => max(0, (float) ($validated['price_per_seat_adult_usd'] ?? 0)),
        'price_per_seat_child_local' => max(0, (float) ($validated['price_per_seat_child_local'] ?? 0)),
        'price_per_seat_child_usd' => max(0, (float) ($validated['price_per_seat_child_usd'] ?? 0)),
        'min_age_years' => max(0, (int) ($validated['min_age_years'] ?? 0)),
        'requires_swimming' => (bool) ($validated['requires_swimming'] ?? false),
        'safety_notes' => trim((string) ($validated['safety_notes'] ?? '')),
        'min_duration_minutes' => max(5, (int) ($validated['min_duration_minutes'] ?? 30)),
        'max_duration_hours' => max(1, (int) ($validated['max_duration_hours'] ?? 8)),
        'quantity_available' => max(1, (int) ($validated['quantity_available'] ?? 1)),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return vendorPortalListingsBackResponse('Rental equipment item added.', 3);
});

Route::post('/portal/vendor/water-sports-equipment/{item}/update', function (Request $request, int $item) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_water_sports_rental_items')) {
        return back()->withErrors(['profile' => 'Water sports rental items table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $itemRecord = DB::table('vendor_water_sports_rental_items')
        ->where('id', $item)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$itemRecord) {
        return back()->withErrors(['profile' => 'Rental item not found for this vendor account.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'equipment_type' => ['nullable', 'string', 'max:80'],
        'equipment_category' => ['nullable', 'string', 'max:40'],
        'description' => ['nullable', 'string', 'max:3000'],
        'pricing_type' => ['nullable', Rule::in(['hourly', 'per_seat'])],
        'price_per_hour_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_child_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_hour_child_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_adult_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_adult_usd' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_child_local' => ['nullable', 'numeric', 'min:0'],
        'price_per_seat_child_usd' => ['nullable', 'numeric', 'min:0'],
        'min_age_years' => ['nullable', 'integer', 'min:0', 'max:120'],
        'requires_swimming' => ['nullable', 'boolean'],
        'safety_notes' => ['nullable', 'string', 'max:1000'],
        'min_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'max_duration_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
        'quantity_available' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
    ]);

    $allowedEquipmentTypes = ['jetski', 'snorkeling_gear', 'canoe', 'surfboard', 'paddleboard', 'banana_boat', 'parasailing', 'windsurf', 'other'];
    $allowedCategories = ['motorized', 'non_motorized', 'adrenaline', 'guided', 'snorkeling_diving', 'other'];
    
    $equipmentType = trim((string) ($validated['equipment_type'] ?? 'other'));
    if (!in_array($equipmentType, $allowedEquipmentTypes, true)) {
        $equipmentType = 'other';
    }
    
    $equipmentCategory = trim((string) ($validated['equipment_category'] ?? 'non_motorized'));
    if (!in_array($equipmentCategory, $allowedCategories, true)) {
        $equipmentCategory = 'non_motorized';
    }

    $updatePricingType = in_array((string) ($validated['pricing_type'] ?? 'hourly'), ['hourly', 'per_seat'], true)
        ? $validated['pricing_type'] : 'hourly';

    DB::table('vendor_water_sports_rental_items')
        ->where('id', $item)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'name' => trim((string) $validated['name']),
            'equipment_type' => $equipmentType,
            'equipment_category' => $equipmentCategory,
            'description' => trim((string) ($validated['description'] ?? '')),
            'pricing_type' => $updatePricingType,
            'price_per_hour_local' => max(0, (float) ($validated['price_per_hour_local'] ?? 0)),
            'price_per_hour_usd' => max(0, (float) ($validated['price_per_hour_usd'] ?? 0)),
            'price_per_hour_child_local' => max(0, (float) ($validated['price_per_hour_child_local'] ?? 0)),
            'price_per_hour_child_usd' => max(0, (float) ($validated['price_per_hour_child_usd'] ?? 0)),
            'price_per_seat_adult_local' => max(0, (float) ($validated['price_per_seat_adult_local'] ?? 0)),
            'price_per_seat_adult_usd' => max(0, (float) ($validated['price_per_seat_adult_usd'] ?? 0)),
            'price_per_seat_child_local' => max(0, (float) ($validated['price_per_seat_child_local'] ?? 0)),
            'price_per_seat_child_usd' => max(0, (float) ($validated['price_per_seat_child_usd'] ?? 0)),
            'min_age_years' => max(0, (int) ($validated['min_age_years'] ?? 0)),
            'requires_swimming' => (bool) ($validated['requires_swimming'] ?? false),
            'safety_notes' => trim((string) ($validated['safety_notes'] ?? '')),
            'min_duration_minutes' => max(5, (int) ($validated['min_duration_minutes'] ?? 30)),
            'max_duration_hours' => max(1, (int) ($validated['max_duration_hours'] ?? 8)),
            'quantity_available' => max(1, (int) ($validated['quantity_available'] ?? 1)),
            'status' => in_array((string) ($validated['status'] ?? 'active'), ['active', 'inactive'], true) ? $validated['status'] : 'active',
            'updated_at' => now(),
        ]);

    return vendorPortalListingsBackResponse('Rental equipment item updated.', 3);
});

Route::post('/portal/vendor/water-sports-equipment/{item}/delete', function (int $item) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_water_sports_rental_items')) {
        return back()->withErrors(['profile' => 'Water sports rental items table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    DB::table('vendor_water_sports_rental_items')
        ->where('id', $item)
        ->where('vendor_user_id', $vendorUserId)
        ->delete();

    return vendorPortalListingsBackResponse('Rental equipment item removed.', 3);
});

Route::post('/portal/vendor/properties/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || !vendorPortalCanManageListings($vendorUser)) {
        return back()->withErrors([
            'profile' => 'Listings are locked until admin verification is approved. Complete My Account compliance details and wait for approval.',
        ])->withInput();
    }
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', 'string', 'max:80'],
        'property_type' => ['nullable', Rule::in(['property', 'service'])],
        'location' => ['nullable', 'string', 'max:190'],
        'location_country' => ['nullable', 'string', 'max:90'],
        'location_state' => ['nullable', 'string', 'max:120'],
        'location_city' => ['nullable', 'string', 'max:120'],
        'location_ward' => ['nullable', 'string', 'max:120'],
        'address_line' => ['nullable', 'string', 'max:255'],
        'building_house_lot' => ['nullable', 'string', 'max:160'],
        'street' => ['nullable', 'string', 'max:160'],
        'post_code' => ['nullable', 'string', 'max:20'],
        'property_contact_name' => ['nullable', 'string', 'max:120'],
        'property_contact_number' => ['nullable', 'string', 'max:60'],
        'property_contact_email' => ['nullable', 'email:rfc', 'max:190'],
        'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        'map_place_id' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'short_description' => ['nullable', 'string', 'max:160'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'price_local' => ['nullable', 'numeric', 'min:0'],
        'price_usd' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'area_value' => ['nullable', 'numeric', 'min:5', 'max:100000'],
        'area_unit' => ['nullable', Rule::in(['sqm', 'sqft'])],
        'bedroom_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'capacity_value' => ['nullable', 'integer', 'min:1', 'max:20000'],
        'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
        'transport_mode' => ['nullable', 'string', 'max:80'],
        'transport_trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
        'transport_pricing_model' => ['nullable', Rule::in(['per_trip', 'hourly', 'daily'])],
        'vehicle_name' => ['nullable', 'string', 'max:120'],
        'registration_plate' => ['nullable', 'string', 'max:80'],
        'contact_name' => ['nullable', 'string', 'max:120'],
        'contact_number' => ['nullable', 'string', 'max:60'],
        'pickup_location' => ['nullable', 'string', 'max:190'],
        'dropoff_location' => ['nullable', 'string', 'max:190'],
        'transport_departure_state' => ['nullable', 'string', 'max:120'],
        'transport_departure_city' => ['nullable', 'string', 'max:120'],
        'transport_arrival_state' => ['nullable', 'string', 'max:120'],
        'transport_arrival_city' => ['nullable', 'string', 'max:120'],
        'departure_area_port_jetty' => ['nullable', 'string', 'max:190'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'departure_date' => ['nullable', 'date'],
        'departure_time' => ['nullable', 'date_format:H:i'],
        'reporting_time' => ['nullable', 'date_format:H:i'],
        'reporting_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'trip_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'schedule_start_time' => ['nullable', 'date_format:H:i'],
        'schedule_end_time' => ['nullable', 'date_format:H:i'],
        'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        'boarding_instructions' => ['nullable', 'string', 'max:1000'],
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'activity_start_time' => ['nullable', 'date_format:H:i'],
        'activity_end_time' => ['nullable', 'date_format:H:i'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
        'excursion_min_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_max_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_min_age' => ['nullable', 'integer', 'min:0', 'max:99'],
        'meeting_point' => ['nullable', 'string', 'max:255'],
        'departure_point' => ['nullable', 'string', 'max:255'],
        'arrival_point' => ['nullable', 'string', 'max:255'],
        'total_seats' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'local_price' => ['nullable', 'numeric', 'min:0'],
        'foreign_price' => ['nullable', 'numeric', 'min:0'],
        'infant_price_local' => ['nullable', 'numeric', 'min:0'],
        'infant_price_foreign' => ['nullable', 'numeric', 'min:0'],
        'price_per_infant' => ['nullable', 'numeric', 'min:0'],
        'inclusions' => ['nullable', 'string', 'max:2000'],
        'exclusions' => ['nullable', 'string', 'max:1000'],
        'safety_waiver_required' => ['nullable', Rule::in(['yes', 'no', '0', '1'])],
        'equipment_rental_available' => ['nullable', Rule::in(['yes', 'no', '0', '1'])],
        'equipment_included' => ['nullable', 'array'],
        'equipment_included.*' => ['required', 'string', 'max:80'],
        'weather_cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'special_instructions' => ['nullable', 'string', 'max:2000'],
        'cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'activity_schedule' => ['nullable', 'string', 'max:5000'],
        'route_schedules' => ['nullable', 'string', 'max:200000'],
        'stop_sequence' => ['nullable', 'string', 'max:10000'],
        'workspace_type' => ['nullable', Rule::in(['shared', 'private', 'cabin'])],
        'internet_speed_mbps' => ['nullable', 'numeric', 'min:1', 'max:10000'],
        'workspace_amenities_free' => ['nullable', 'array'],
        'workspace_amenities_free.*' => ['required', 'string', 'max:80'],
        'workspace_amenities_paid' => ['nullable', 'array'],
        'workspace_amenities_paid.*' => ['required', 'string', 'max:80'],
        'transfer_options' => ['nullable', 'array'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_adult' => ['nullable', 'array'],
        'transfer_rates_local_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_child' => ['nullable', 'array'],
        'transfer_rates_local_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_adult' => ['nullable', 'array'],
        'transfer_rates_foreign_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_child' => ['nullable', 'array'],
        'transfer_rates_foreign_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_local' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_foreign' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'vendor_tax_rates' => ['nullable', 'array'],
        'vendor_tax_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'day_visit_start_time' => ['nullable', 'date_format:H:i'],
        'day_visit_end_time' => ['nullable', 'date_format:H:i'],
        'included_access' => ['nullable', 'string', 'max:2000'],
        'price_per_adult' => ['nullable', 'numeric', 'min:0'],
        'price_per_child' => ['nullable', 'numeric', 'min:0'],
        'transfer_included' => ['nullable', Rule::in(['0', '1', 'yes', 'no', 'true', 'false'])],
        'departure_time_mode' => ['nullable', Rule::in(['fixed', 'slots'])],
        'departure_slots' => ['nullable', 'string', 'max:2000'],
        'return_time_mode' => ['nullable', Rule::in(['fixed', 'slots'])],
        'return_time' => ['nullable', 'date_format:H:i'],
        'return_slots' => ['nullable', 'string', 'max:2000'],
        'adult_price_local' => ['nullable', 'numeric', 'min:0'],
        'adult_price_foreign' => ['nullable', 'numeric', 'min:0'],
        'child_price_local' => ['nullable', 'numeric', 'min:0'],
        'child_price_foreign' => ['nullable', 'numeric', 'min:0'],
        'cuisine_type' => ['nullable', 'string', 'max:120'],
        'meal_service' => ['nullable', 'string', 'max:80'],
        'vehicle_type' => ['nullable', 'string', 'max:80'],
        'transmission_type' => ['nullable', Rule::in(['automatic', 'manual'])],
        'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
        'safety_certifications' => ['nullable', 'string', 'max:2000'],
        'accessibility_features' => ['nullable', 'string', 'max:2000'],
        'property_amenities' => ['nullable', 'array'],
        'property_amenities.*' => ['required', 'string', 'max:80'],
        'property_features' => ['nullable', 'array'],
        'property_features.*' => ['required', 'string', 'max:80'],
        'check_in_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'early_check_in_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'late_check_out_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'early_check_in_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'late_check_out_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) $validated['listing_category']);
    if ($canonicalListingCategory === null) {
        return back()->withErrors(['profile' => 'Invalid listing category selected.'])->withInput();
    }

    $approvedCategories = vendorPortalApprovedCategories($vendorUser);
    if (!in_array($canonicalListingCategory, $approvedCategories, true)) {
        return back()->withErrors([
            'profile' => 'This category is not yet approved for your vendor account. Request admin category approval before creating listings.',
        ])->withInput();
    }

    $submittedTransportMode = trim((string) ($validated['transport_mode'] ?? ''));
    if ($canonicalListingCategory === 'transport' && $submittedTransportMode !== '') {
        $invalidTransportModes = vendorPortalDisallowedOptionValues('transport_mode', [$submittedTransportMode]);
        if ($invalidTransportModes !== []) {
            return back()->withErrors(['profile' => 'Selected transport mode is not in the allowed catalog.'])->withInput();
        }
    }

    $propertyAmenities = vendorPortalNormalizedStringList($validated['property_amenities'] ?? []);
    $propertyFeatures = vendorPortalNormalizedStringList($validated['property_features'] ?? []);
    $invalidPropertyAmenities = vendorPortalDisallowedOptionValuesFromTypes(['property_amenity', 'accommodation_facility'], $propertyAmenities);
    if ($invalidPropertyAmenities !== []) {
        return back()->withErrors(['profile' => 'One or more accommodation facilities are not in the allowed catalog.'])->withInput();
    }
    $invalidPropertyFeatures = vendorPortalDisallowedOptionValues('property_feature', $propertyFeatures);
    if ($invalidPropertyFeatures !== []) {
        return back()->withErrors(['profile' => 'One or more property features are not in the allowed catalog.'])->withInput();
    }

    $submittedMealService = trim((string) ($validated['meal_service'] ?? ''));
    if ($canonicalListingCategory === 'restaurant' && $submittedMealService !== '') {
        $invalidMealServices = vendorPortalDisallowedOptionValues('restaurant_meal_service', [$submittedMealService]);
        if ($invalidMealServices !== []) {
            return back()->withErrors(['profile' => 'Selected restaurant meal service is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedExcursionType = trim((string) ($validated['excursion_type'] ?? ''));
    if (in_array($canonicalListingCategory, ['excursion', 'water_sports'], true) && $submittedExcursionType !== '') {
        $invalidExcursionTypes = vendorPortalDisallowedOptionValues('excursion_type', [$submittedExcursionType]);
        if ($invalidExcursionTypes !== []) {
            return back()->withErrors(['profile' => 'Selected excursion type is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedVehicleType = trim((string) ($validated['vehicle_type'] ?? ''));
    if ($canonicalListingCategory === 'vehicle_rental' && $submittedVehicleType !== '') {
        $invalidVehicleTypes = vendorPortalDisallowedOptionValues('vehicle_rental_type', [$submittedVehicleType]);
        if ($invalidVehicleTypes !== []) {
            return back()->withErrors(['profile' => 'Selected vehicle rental type is not in the allowed land/marine catalog.'])->withInput();
        }
    }

    $resolvedPropertyType = vendorPortalPropertyTypeForCategory($canonicalListingCategory);

    $propertyDetails = vendorPortalBuildPropertyDetails($validated, $canonicalListingCategory);
    $propertyDetailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $propertyDetails);
    if (!empty($propertyDetailErrors)) {
        return back()->withErrors(['profile' => implode(' ', $propertyDetailErrors)])->withInput();
    }

    $locationCountry = trim((string) ($validated['location_country'] ?? ''));
    $locationState = trim((string) ($validated['location_state'] ?? ''));
    $locationCity = trim((string) ($validated['location_city'] ?? ''));
    $locationWard = trim((string) ($validated['location_ward'] ?? ''));
    $addressLine = trim((string) ($validated['address_line'] ?? ''));
    $buildingHouseLot = trim((string) ($validated['building_house_lot'] ?? ''));
    $street = trim((string) ($validated['street'] ?? ''));
    $locationParts = array_values(array_filter([$street, $locationWard, $locationCity, $locationState, $locationCountry], static fn (string $item): bool => $item !== ''));
    $locationFromStructuredFields = implode(', ', $locationParts);
    $resolvedLocation = $locationFromStructuredFields !== '' ? $locationFromStructuredFields : trim((string) ($validated['location'] ?? ''));

    if ($buildingHouseLot !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($buildingHouseLot . ', ' . $resolvedLocation) : $buildingHouseLot;
    }

    if ($addressLine !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($addressLine . ' - ' . $resolvedLocation) : $addressLine;
    }

    if ($canonicalListingCategory === 'transport') {
        $pickup = trim((string) ($propertyDetails['pickup_location'] ?? ''));
        $dropoff = trim((string) ($propertyDetails['dropoff_location'] ?? ''));
        $resolvedLocation = ($pickup !== '' && $dropoff !== '')
            ? ($pickup . ' -> ' . $dropoff)
            : ($pickup !== '' ? $pickup : ($dropoff !== '' ? $dropoff : 'Route details pending'));
    }

    $selectedAmenityTokens = array_values(array_unique(array_merge($propertyAmenities, $propertyFeatures)));
    $categoryCapacity = isset($propertyDetails['capacity_value']) && is_numeric($propertyDetails['capacity_value'])
        ? (int) $propertyDetails['capacity_value']
        : null;
    $normalizedMaxGuests = $canonicalListingCategory === 'accommodation'
        ? 0
        : max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? 0)));

    $resolvedBasePrice = $canonicalListingCategory === 'accommodation'
        ? 0
        : (float) ($validated['price_local'] ?? ($validated['base_price'] ?? 0));

    if ($canonicalListingCategory === 'transport') {
        $normalizedMaxGuests = max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? 0)));
    }

    DB::transaction(function () use ($canonicalListingCategory, $vendorUserId, $validated, $resolvedLocation, $normalizedMaxGuests, $propertyDetails, $resolvedBasePrice): void {
        vendorPortalCreateCategoryListingRecord(
            $canonicalListingCategory,
            $vendorUserId,
            trim((string) $validated['name']),
            $resolvedLocation,
            trim((string) ($validated['description'] ?? '')),
            $normalizedMaxGuests,
            $propertyDetails,
            'draft',
            $resolvedBasePrice,
            'MVR'
        );
    });

    Cache::forget('vendor:portal:listings:v4:' . $vendorUserId . ':all');
    Cache::forget('vendor:portal:listings:v4:' . $vendorUserId . ':' . $canonicalListingCategory);

    return vendorPortalListingsBackResponse('Listing created successfully.', 1, [
        'portal_listing_mode' => 'manage',
        'portal_listing_category' => $canonicalListingCategory,
    ]);
});

Route::post('/portal/vendor/properties/{property}/update', function (Request $request, int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $requestedListingCategory = vendorPortalCanonicalCategory((string) $request->input('listing_category', ''));
    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById(
        $property,
        $vendorUserId,
        $requestedListingCategory
    );

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Property not found for this vendor account.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'location' => ['nullable', 'string', 'max:190'],
        'location_country' => ['nullable', 'string', 'max:90'],
        'location_state' => ['nullable', 'string', 'max:120'],
        'location_city' => ['nullable', 'string', 'max:120'],
        'location_ward' => ['nullable', 'string', 'max:120'],
        'address_line' => ['nullable', 'string', 'max:255'],
        'building_house_lot' => ['nullable', 'string', 'max:160'],
        'street' => ['nullable', 'string', 'max:160'],
        'post_code' => ['nullable', 'string', 'max:20'],
        'property_contact_name' => ['nullable', 'string', 'max:120'],
        'property_contact_number' => ['nullable', 'string', 'max:60'],
        'property_contact_email' => ['nullable', 'email:rfc', 'max:190'],
        'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        'map_place_id' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'short_description' => ['nullable', 'string', 'max:160'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'price_local' => ['nullable', 'numeric', 'min:0'],
        'price_usd' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'area_value' => ['nullable', 'numeric', 'min:5', 'max:100000'],
        'area_unit' => ['nullable', Rule::in(['sqm', 'sqft'])],
        'bedroom_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'capacity_value' => ['nullable', 'integer', 'min:1', 'max:20000'],
        'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
        'transport_mode' => ['nullable', 'string', 'max:80'],
        'transport_trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
        'transport_pricing_model' => ['nullable', Rule::in(['per_trip', 'hourly', 'daily'])],
        'vehicle_name' => ['nullable', 'string', 'max:120'],
        'registration_plate' => ['nullable', 'string', 'max:80'],
        'contact_name' => ['nullable', 'string', 'max:120'],
        'contact_number' => ['nullable', 'string', 'max:60'],
        'pickup_location' => ['nullable', 'string', 'max:190'],
        'dropoff_location' => ['nullable', 'string', 'max:190'],
        'transport_departure_state' => ['nullable', 'string', 'max:120'],
        'transport_departure_city' => ['nullable', 'string', 'max:120'],
        'transport_arrival_state' => ['nullable', 'string', 'max:120'],
        'transport_arrival_city' => ['nullable', 'string', 'max:120'],
        'departure_area_port_jetty' => ['nullable', 'string', 'max:190'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'departure_date' => ['nullable', 'date'],
        'departure_time' => ['nullable', 'date_format:H:i'],
        'reporting_time' => ['nullable', 'date_format:H:i'],
        'reporting_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'trip_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'schedule_start_time' => ['nullable', 'date_format:H:i'],
        'schedule_end_time' => ['nullable', 'date_format:H:i'],
        'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        'boarding_instructions' => ['nullable', 'string', 'max:1000'],
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'activity_start_time' => ['nullable', 'date_format:H:i'],
        'activity_end_time' => ['nullable', 'date_format:H:i'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
        'excursion_min_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_max_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_min_age' => ['nullable', 'integer', 'min:0', 'max:99'],
        'meeting_point' => ['nullable', 'string', 'max:255'],
        'departure_point' => ['nullable', 'string', 'max:255'],
        'arrival_point' => ['nullable', 'string', 'max:255'],
        'total_seats' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'local_price' => ['nullable', 'numeric', 'min:0'],
        'foreign_price' => ['nullable', 'numeric', 'min:0'],
        'infant_price_local' => ['nullable', 'numeric', 'min:0'],
        'infant_price_foreign' => ['nullable', 'numeric', 'min:0'],
        'price_per_infant' => ['nullable', 'numeric', 'min:0'],
        'inclusions' => ['nullable', 'string', 'max:2000'],
        'exclusions' => ['nullable', 'string', 'max:1000'],
        'safety_waiver_required' => ['nullable', Rule::in(['yes', 'no', '0', '1'])],
        'equipment_rental_available' => ['nullable', Rule::in(['yes', 'no', '0', '1'])],
        'equipment_included' => ['nullable', 'array'],
        'equipment_included.*' => ['required', 'string', 'max:80'],
        'weather_cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'special_instructions' => ['nullable', 'string', 'max:2000'],
        'activity_schedule' => ['nullable', 'string', 'max:5000'],
        'route_schedules' => ['nullable', 'string', 'max:200000'],
        'stop_sequence' => ['nullable', 'string', 'max:10000'],
        'late_check_out_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'early_check_in_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'late_check_out_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'property_type' => ['nullable', Rule::in(['hotel', 'resort', 'guest_house', 'villa', 'apartment', 'bungalow', 'hostel'])],
        'star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
    ]);
    $resolvedStatus = (string) ($validated['status'] ?? $propertyRecord->status ?? 'active');
    if (!in_array($resolvedStatus, ['active', 'inactive'], true)) {
        $resolvedStatus = 'active';
    }


    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    $existingDetails = [];
    if (isset($propertyRecord->listing_details) && is_string($propertyRecord->listing_details) && trim($propertyRecord->listing_details) !== '') {
        $decodedDetails = json_decode((string) $propertyRecord->listing_details, true);
        if (is_array($decodedDetails)) {
            $existingDetails = $decodedDetails;
        }
    }

    if ($canonicalListingCategory === null && $requestedListingCategory !== null) {
        $canonicalListingCategory = $requestedListingCategory;
    }

    if ($canonicalListingCategory === null && isset($existingDetails['listing_category'])) {
        $canonicalListingCategory = vendorPortalCanonicalCategory((string) $existingDetails['listing_category']);
    }

    $submittedTransportMode = trim((string) ($validated['transport_mode'] ?? ''));
    if ($canonicalListingCategory === 'transport' && $submittedTransportMode !== '') {
        $invalidTransportModes = vendorPortalDisallowedOptionValues('transport_mode', [$submittedTransportMode]);
        if ($invalidTransportModes !== []) {
            return back()->withErrors(['profile' => 'Selected transport mode is not in the allowed catalog.'])->withInput();
        }
    }

    $propertyAmenities = vendorPortalNormalizedStringList($validated['property_amenities'] ?? []);
    $propertyFeatures = vendorPortalNormalizedStringList($validated['property_features'] ?? []);
    $invalidPropertyAmenities = vendorPortalDisallowedOptionValuesFromTypes(['property_amenity', 'accommodation_facility'], $propertyAmenities);
    if ($invalidPropertyAmenities !== []) {
        return back()->withErrors(['profile' => 'One or more accommodation facilities are not in the allowed catalog.'])->withInput();
    }
    $invalidPropertyFeatures = vendorPortalDisallowedOptionValues('property_feature', $propertyFeatures);
    if ($invalidPropertyFeatures !== []) {
        return back()->withErrors(['profile' => 'One or more property features are not in the allowed catalog.'])->withInput();
    }

    $submittedMealService = trim((string) ($validated['meal_service'] ?? ''));
    if ($canonicalListingCategory === 'restaurant' && $submittedMealService !== '') {
        $invalidMealServices = vendorPortalDisallowedOptionValues('restaurant_meal_service', [$submittedMealService]);
        if ($invalidMealServices !== []) {
            return back()->withErrors(['profile' => 'Selected restaurant meal service is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedExcursionType = trim((string) ($validated['excursion_type'] ?? ''));
    if (in_array($canonicalListingCategory, ['excursion', 'water_sports'], true) && $submittedExcursionType !== '') {
        $invalidExcursionTypes = vendorPortalDisallowedOptionValues('excursion_type', [$submittedExcursionType]);
        if ($invalidExcursionTypes !== []) {
            return back()->withErrors(['profile' => 'Selected excursion type is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedVehicleType = trim((string) ($validated['vehicle_type'] ?? ''));
    if ($canonicalListingCategory === 'vehicle_rental' && $submittedVehicleType !== '') {
        $invalidVehicleTypes = vendorPortalDisallowedOptionValues('vehicle_rental_type', [$submittedVehicleType]);
        if ($invalidVehicleTypes !== []) {
            return back()->withErrors(['profile' => 'Selected vehicle rental type is not in the allowed land/marine catalog.'])->withInput();
        }
    }

    if ($canonicalListingCategory !== null) {
        if (in_array($canonicalListingCategory, ['accommodation', 'remote_workspace'], true)) {
            // Preserve intentional clears from checkbox-based transfer UI.
            $validated['transfer_options'] = $request->input('transfer_options', []);
            $validated['transfer_rates'] = $request->input('transfer_rates', []);
            $validated['transfer_rates_local_adult'] = $request->input('transfer_rates_local_adult', []);
            $validated['transfer_rates_local_child'] = $request->input('transfer_rates_local_child', []);
            $validated['transfer_rates_foreign_adult'] = $request->input('transfer_rates_foreign_adult', []);
            $validated['transfer_rates_foreign_child'] = $request->input('transfer_rates_foreign_child', []);
        }

        $mergedDetailsInput = array_merge($existingDetails, $validated);
        $mergedDetails = vendorPortalBuildPropertyDetails($mergedDetailsInput, $canonicalListingCategory);
        $detailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $mergedDetails);
        if (!empty($detailErrors)) {
            return back()->withErrors(['profile' => implode(' ', $detailErrors)])->withInput();
        }
        $existingDetails = $mergedDetails;
    }

    $categoryCapacity = isset($existingDetails['capacity_value']) && is_numeric($existingDetails['capacity_value'])
        ? (int) $existingDetails['capacity_value']
        : null;

    $normalizedMaxGuests = $canonicalListingCategory === 'accommodation'
        ? 0
        : max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? ($propertyRecord->max_guests ?? 0))));

    $resolvedBasePrice = $canonicalListingCategory === 'accommodation'
        ? 0
        : (float) ($validated['price_local'] ?? ($validated['base_price'] ?? ($propertyRecord->base_price ?? 0)));

    $locationCountry = trim((string) ($validated['location_country'] ?? ''));
    $locationState = trim((string) ($validated['location_state'] ?? ''));
    $locationCity = trim((string) ($validated['location_city'] ?? ''));
    $locationWard = trim((string) ($validated['location_ward'] ?? ''));
    $addressLine = trim((string) ($validated['address_line'] ?? ''));
    $buildingHouseLot = trim((string) ($validated['building_house_lot'] ?? ''));
    $street = trim((string) ($validated['street'] ?? ''));
    $locationParts = array_values(array_filter([$street, $locationWard, $locationCity, $locationState, $locationCountry], static fn (string $item): bool => $item !== ''));
    $locationFromStructuredFields = implode(', ', $locationParts);
    $resolvedLocation = $locationFromStructuredFields !== '' ? $locationFromStructuredFields : trim((string) ($validated['location'] ?? ''));

    if ($buildingHouseLot !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($buildingHouseLot . ', ' . $resolvedLocation) : $buildingHouseLot;
    }

    if ($addressLine !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($addressLine . ' - ' . $resolvedLocation) : $addressLine;
    }

    if ($canonicalListingCategory === 'transport') {
        $pickup = trim((string) ($existingDetails['pickup_location'] ?? ''));
        $dropoff = trim((string) ($existingDetails['dropoff_location'] ?? ''));
        $resolvedLocation = ($pickup !== '' && $dropoff !== '')
            ? ($pickup . ' -> ' . $dropoff)
            : ($pickup !== '' ? $pickup : ($dropoff !== '' ? $dropoff : 'Route details pending'));
    }

    $selectedAmenityTokens = array_values(array_unique(array_merge($propertyAmenities, $propertyFeatures)));

    if ($canonicalListingCategory === 'transport') {
        $normalizedMaxGuests = max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? ($propertyRecord->max_guests ?? 0))));
    }

    $updatePayload = [
        'name' => trim((string) $validated['name']),
        'location' => $resolvedLocation,
        'description' => trim((string) ($validated['description'] ?? '')),
        'base_price' => $resolvedBasePrice,
        'max_guests' => $normalizedMaxGuests,
        'status' => $resolvedStatus,
        'updated_at' => now(),
    ];

    DB::transaction(function () use ($property, $vendorUserId, $updatePayload, $canonicalListingCategory, $resolvedLocation, $resolvedBasePrice, $normalizedMaxGuests, $existingDetails, $validated, $resolvedStatus): void {
        if (Schema::hasTable('vendor_properties')) {
            $legacyPropertyUpdate = $updatePayload;
            if (Schema::hasColumn('vendor_properties', 'listing_details')) {
                $legacyPropertyUpdate['listing_details'] = empty($existingDetails) ? null : json_encode($existingDetails);
            }
            if (Schema::hasColumn('vendor_properties', 'currency')) {
                $legacyPropertyUpdate['currency'] = 'MVR';
            }

            DB::table('vendor_properties')
                ->where('id', $property)
                ->where('vendor_user_id', $vendorUserId)
                ->update($legacyPropertyUpdate);
        }

        if ($canonicalListingCategory !== null) {
            vendorPortalSyncCategoryListingRecord(
                $canonicalListingCategory,
                $property,
                $vendorUserId,
                trim((string) ($validated['name'] ?? '')),
                $resolvedStatus,
                $resolvedLocation,
                trim((string) ($validated['description'] ?? '')),
                $resolvedBasePrice,
                'MVR',
                $normalizedMaxGuests,
                $existingDetails
            );

            if ($canonicalListingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
                vendorPortalSyncAccommodationStructuredData($property, $vendorUserId, $existingDetails);
            }
        }
    });

    // Bust vendor portal listing cache so the updated data is visible immediately.
    Cache::forget('vendor:portal:listings:v4:' . $vendorUserId . ':all');
    Cache::forget('vendor:portal:listings:v4:' . $vendorUserId . ':' . ($canonicalListingCategory ?? ''));
    // Bust the property-by-id cache used by the edit form loader.
    Cache::forget('vendor_property_compatibility_reader:property_by_id:' . md5(($canonicalListingCategory ?? '*') . ':' . $property));
    Cache::forget('vendor_property_compatibility_reader:property_by_id:' . md5('*:' . $property));

    return vendorPortalListingsBackResponse('Listing updated successfully.', 2, [
        'portal_listing_mode' => 'manage',
        'portal_listing_category' => (string) ($canonicalListingCategory ?? ''),
    ]);
});

Route::post('/portal/vendor/properties/{property}/delete', function (int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($property, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Property not found for this vendor account.']);
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));

    DB::transaction(function () use ($property, $canonicalListingCategory): void {
        if ($canonicalListingCategory !== null) {
            vendorPortalDeleteCategoryListingRecord($canonicalListingCategory, $property);
        }
    });

    return vendorPortalListingsBackResponse('Property listing removed.', 1);
});

Route::post('/portal/vendor/properties/{property}/submit-for-review', function (int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    // Load listing from dedicated table first; fall back to vendor_properties.
    $listing = \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($property);
    if (!$listing || (int) ($listing->vendor_user_id ?? 0) !== $vendorUserId) {
        return back()->withErrors(['profile' => 'Listing not found.']);
    }

    $currentStatus = strtolower(trim((string) ($listing->listing_moderation_status ?? 'draft')));

    if (!in_array($currentStatus, ['draft', 'rejected'], true)) {
        return back()->with('portal_notice', 'This listing cannot be submitted for review in its current state. Status: ' . strtoupper($currentStatus));
    }

    $listingDetails = [];
    $rawDetails = $listing->listing_details ?? ($listing->details ?? null);
    if (is_string($rawDetails) && trim((string) $rawDetails) !== '') {
        $decodedDetails = json_decode((string) $rawDetails, true);
        if (is_array($decodedDetails)) {
            $listingDetails = $decodedDetails;
        }
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($listing->listing_category ?? ''));
    if ($canonicalListingCategory !== null) {
        $detailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $listingDetails);
        if (!empty($detailErrors)) {
            return back()->withErrors([
                'profile' => implode(' ', $detailErrors),
            ]);
        }
    }

    \App\Support\VendorPropertyCompatibilityReader::submitForReview($property, $canonicalListingCategory);

    return vendorPortalListingsBackResponse('Listing submitted for admin review. You will be notified once it has been approved.', 1);
});


Route::post('/portal/vendor/services/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_services')) {
        return back()->withErrors(['profile' => 'Vendor services table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', 'string', 'max:80'],
        'category' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:3000'],
        'price' => ['nullable', 'numeric', 'min:0'],
        'price_local' => ['nullable', 'numeric', 'min:0'],
        'price_usd' => ['nullable', 'numeric', 'min:0'],
        'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
        'property_id' => ['nullable', 'integer'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'lead_time_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
        'min_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'quantity_unit' => ['nullable', Rule::in(['seat', 'room', 'desk', 'vehicle', 'ticket', 'table', 'pass'])],
        'compliance_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $mvrUsdRate = (float) env('MVR_USD_RATE', 15.42);
    $mvrFromUsd = static fn (float $usd): float => $usd > 0 ? round($usd * $mvrUsdRate, 2) : 0.0;
    $localPriceMvr = max(0, (float) ($validated['price_local'] ?? 0));
    $foreignPriceUsd = max(0, (float) ($validated['price_usd'] ?? 0));
    $foreignPriceMvr = $mvrFromUsd($foreignPriceUsd);
    $legacyPriceMvr = max(0, (float) ($validated['price'] ?? 0));
    $resolvedBasePriceMvr = $foreignPriceMvr > 0
        ? $foreignPriceMvr
        : ($localPriceMvr > 0 ? $localPriceMvr : $legacyPriceMvr);

    if ($resolvedBasePriceMvr <= 0) {
        return back()->withErrors(['price' => 'Enter at least one service price (Foreign USD, Local MVR, or base MVR).'])->withInput();
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) $validated['listing_category']);
    if ($canonicalListingCategory === null) {
        return back()->withErrors(['profile' => 'Invalid service listing category selected.'])->withInput();
    }

    $allowedForUser = vendorPortalSelectedCategories($vendorUser);
    if (!in_array($canonicalListingCategory, $allowedForUser, true)) {
        return back()->withErrors(['profile' => 'Select category in onboarding before creating this service.']);
    }

    $serviceDetails = vendorPortalBuildServiceDetails($validated, $canonicalListingCategory);
    $serviceDetailErrors = vendorPortalValidateServiceDetails($serviceDetails);
    if (!empty($serviceDetailErrors)) {
        return back()->withErrors(['profile' => implode(' ', $serviceDetailErrors)])->withInput();
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['property_id'] ?? null) ? (int) $validated['property_id'] : null,
        'name' => trim((string) $validated['name']),
        'category' => trim((string) $validated['category']),
        'description' => trim((string) ($validated['description'] ?? '')),
        'duration_minutes' => (int) ($validated['duration_minutes'] ?? 0),
        // Primary booking math remains in MVR.
        'price' => $resolvedBasePriceMvr,
        'currency' => 'MVR',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_services', 'price_local')) {
        $payload['price_local'] = $localPriceMvr;
    }
    if (Schema::hasColumn('vendor_services', 'price_usd')) {
        $payload['price_usd'] = $foreignPriceUsd;
    }

    if (Schema::hasColumn('vendor_services', 'listing_category')) {
        $payload['listing_category'] = $canonicalListingCategory;
    }
    if (Schema::hasColumn('vendor_services', 'service_details')) {
        $payload['service_details'] = empty($serviceDetails) ? null : json_encode($serviceDetails);
    }

    DB::table('vendor_services')->insert($payload);

    return back()->with('portal_notice', 'Service added successfully.');
});

