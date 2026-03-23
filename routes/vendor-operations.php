<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

if (!function_exists('vendorPortalCategoryMap')) {
    function vendorPortalCategoryMap(): array
    {
        return [
            'accommodation' => 'Accommodation',
            'transport' => 'Transport',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspace',
            'resort_day_visit' => 'Resort Day Visit',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
        ];
    }
}

if (!function_exists('vendorPortalSelectedCategories')) {
    function vendorPortalSelectedCategories(?User $vendorUser): array
    {
        if (!$vendorUser instanceof User || !Schema::hasColumn('users', 'portal_service_categories')) {
            return [];
        }

        $raw = $vendorUser->portal_service_categories;
        if (is_array($raw)) {
            $candidate = $raw;
        } else {
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $candidate = is_array($decoded) ? $decoded : [];
        }

        $allowed = array_keys(vendorPortalCategoryMap());
        return array_values(array_intersect($allowed, array_map('strval', $candidate)));
    }
}

if (!function_exists('vendorPortalRequiresAccommodation')) {
    function vendorPortalRequiresAccommodation(array $selectedCategories): bool
    {
        return in_array('accommodation', $selectedCategories, true);
    }
}

if (!function_exists('vendorPortalNormalizedNumeric')) {
    function vendorPortalNormalizedNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

if (!function_exists('vendorPortalBuildPropertyDetails')) {
    function vendorPortalBuildPropertyDetails(array $validated, string $listingCategory): array
    {
        $details = [
            'measurement_system' => (string) ($validated['measurement_system'] ?? 'metric'),
            'area_value' => vendorPortalNormalizedNumeric($validated['area_value'] ?? null),
            'area_unit' => (string) ($validated['area_unit'] ?? ''),
            'bedroom_count' => isset($validated['bedroom_count']) ? (int) $validated['bedroom_count'] : null,
            'bathroom_count' => vendorPortalNormalizedNumeric($validated['bathroom_count'] ?? null),
            'capacity_value' => isset($validated['capacity_value']) ? (int) $validated['capacity_value'] : null,
            'service_radius_km' => vendorPortalNormalizedNumeric($validated['service_radius_km'] ?? null),
            'minimum_age' => isset($validated['minimum_age']) ? (int) $validated['minimum_age'] : null,
            'safety_certifications' => trim((string) ($validated['safety_certifications'] ?? '')),
            'accessibility_features' => trim((string) ($validated['accessibility_features'] ?? '')),
            'listing_category' => $listingCategory,
        ];

        return array_filter($details, static fn (mixed $value): bool => !($value === null || $value === ''));
    }
}

if (!function_exists('vendorPortalValidatePropertyDetails')) {
    function vendorPortalValidatePropertyDetails(string $listingCategory, array $details): array
    {
        $errors = [];

        if (in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
            if (!isset($details['area_value']) || $details['area_value'] < 5 || $details['area_value'] > 100000) {
                $errors[] = 'Area measurement must be between 5 and 100000.';
            }
            if (!in_array(($details['area_unit'] ?? ''), ['sqm', 'sqft'], true)) {
                $errors[] = 'Area unit must be sqm or sqft.';
            }
        }

        if (in_array($listingCategory, ['transport', 'excursion', 'resort_day_visit', 'restaurant', 'vehicle_rental'], true)) {
            if (!isset($details['capacity_value']) || $details['capacity_value'] < 1 || $details['capacity_value'] > 20000) {
                $errors[] = 'Capacity must be between 1 and 20000 for this category.';
            }
        }

        if ($listingCategory === 'vehicle_rental') {
            if (!isset($details['minimum_age']) || $details['minimum_age'] < 16 || $details['minimum_age'] > 99) {
                $errors[] = 'Vehicle rental minimum age must be between 16 and 99.';
            }
        }

        return $errors;
    }
}

if (!function_exists('vendorPortalBuildServiceDetails')) {
    function vendorPortalBuildServiceDetails(array $validated, string $listingCategory): array
    {
        $details = [
            'measurement_system' => (string) ($validated['measurement_system'] ?? 'metric'),
            'lead_time_minutes' => isset($validated['lead_time_minutes']) ? (int) $validated['lead_time_minutes'] : null,
            'min_booking_size' => isset($validated['min_booking_size']) ? (int) $validated['min_booking_size'] : null,
            'max_booking_size' => isset($validated['max_booking_size']) ? (int) $validated['max_booking_size'] : null,
            'quantity_unit' => (string) ($validated['quantity_unit'] ?? ''),
            'compliance_notes' => trim((string) ($validated['compliance_notes'] ?? '')),
            'listing_category' => $listingCategory,
        ];

        return array_filter($details, static fn (mixed $value): bool => !($value === null || $value === ''));
    }
}

if (!function_exists('vendorPortalValidateServiceDetails')) {
    function vendorPortalValidateServiceDetails(array $details): array
    {
        $errors = [];

        $minBooking = $details['min_booking_size'] ?? null;
        $maxBooking = $details['max_booking_size'] ?? null;
        if ($minBooking !== null && $maxBooking !== null && $minBooking > $maxBooking) {
            $errors[] = 'Min booking size cannot be greater than max booking size.';
        }

        if (isset($details['lead_time_minutes']) && ($details['lead_time_minutes'] < 0 || $details['lead_time_minutes'] > 43200)) {
            $errors[] = 'Lead time must be between 0 and 43200 minutes.';
        }

        return $errors;
    }
}

Route::get('/vendor', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;

    $vendorCategoryMap = vendorPortalCategoryMap();
    $selectedVendorCategories = vendorPortalSelectedCategories($vendorUser);
    $vendorOnboardingStep = ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_onboarding_step'))
        ? max(1, min(4, (int) ($vendorUser->vendor_onboarding_step ?? 1)))
        : 1;

    $vendorProperties = collect();
    $vendorServices = collect();
    $vendorAvailability = collect();
    $vendorReservations = collect();
    $vendorPricingRules = collect();
    $vendorBilling = null;
    $vendorRoomCategories = collect();
    $vendorMediaAssets = collect();

    if ($vendorUserId > 0) {
        if (Schema::hasTable('vendor_properties')) {
            $vendorProperties = DB::table('vendor_properties')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_services')) {
            $vendorServices = DB::table('vendor_services')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(250)
                ->get();
        }

        if (Schema::hasTable('vendor_availability_slots')) {
            $vendorAvailability = DB::table('vendor_availability_slots')
                ->where('vendor_user_id', $vendorUserId)
                ->orderBy('slot_date')
                ->limit(365)
                ->get();
        }

        if (Schema::hasTable('vendor_reservations')) {
            $vendorReservations = DB::table('vendor_reservations')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('start_at')
                ->limit(300)
                ->get();
        }

        if (Schema::hasTable('vendor_pricing_rules')) {
            $vendorPricingRules = DB::table('vendor_pricing_rules')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_billing_details')) {
            $vendorBilling = DB::table('vendor_billing_details')
                ->where('vendor_user_id', $vendorUserId)
                ->first();
        }

        if (Schema::hasTable('vendor_property_room_categories')) {
            $vendorRoomCategories = DB::table('vendor_property_room_categories')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_listing_media')) {
            $vendorMediaAssets = DB::table('vendor_listing_media')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();
        }
    }

    return view('vendor-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_vendor_user', 'Vendor'),
        'vendorProfile' => [
            'name' => $vendorUser instanceof User ? (string) $vendorUser->name : (string) session('portal_vendor_user', 'Vendor'),
            'email' => $vendorUser instanceof User ? (string) $vendorUser->email : '',
            'phone' => ($vendorUser instanceof User && Schema::hasColumn('users', 'phone')) ? (string) ($vendorUser->phone ?? '') : '',
            'vendor_id' => $vendorUser instanceof User ? (string) ($vendorUser->portal_vendor_id ?? '') : '',
        ],
        'vendorCategoryMap' => $vendorCategoryMap,
        'selectedVendorCategories' => $selectedVendorCategories,
        'vendorOnboardingStep' => $vendorOnboardingStep,
        'vendorProperties' => $vendorProperties,
        'vendorServices' => $vendorServices,
        'vendorAvailability' => $vendorAvailability,
        'vendorReservations' => $vendorReservations,
        'vendorPricingRules' => $vendorPricingRules,
        'vendorBilling' => $vendorBilling,
        'vendorRoomCategories' => $vendorRoomCategories,
        'vendorMediaAssets' => $vendorMediaAssets,
    ]);
});

Route::post('/portal/vendor/categories/update', function (Request $request) {
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

    $categoryKeys = array_keys(vendorPortalCategoryMap());
    $validated = $request->validate([
        'categories' => ['required', 'array', 'min:1'],
        'categories.*' => ['required', 'string', Rule::in($categoryKeys)],
        'onboarding_step' => ['nullable', 'integer', 'min:1', 'max:4'],
    ]);

    $normalizedCategories = array_values(array_unique(array_map('strval', $validated['categories'])));

    if (Schema::hasColumn('users', 'portal_service_categories')) {
        $vendorUser->portal_service_categories = json_encode($normalizedCategories);
    }

    if (Schema::hasColumn('users', 'vendor_onboarding_step')) {
        $vendorUser->vendor_onboarding_step = (int) ($validated['onboarding_step'] ?? 1);
    }

    $vendorUser->save();

    return back()->with('portal_notice', 'Vendor categories and onboarding step updated.');
});

Route::post('/portal/vendor/profile/update', function (Request $request) {
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
        'display_name' => ['required', 'string', 'max:120'],
        'contact_phone' => ['nullable', 'string', 'max:40'],
    ]);

    $vendorUser->name = trim((string) $validated['display_name']);
    if (Schema::hasColumn('users', 'phone')) {
        $vendorUser->phone = vendorNormalizePhoneNumber((string) ($validated['contact_phone'] ?? ''));
    }
    $vendorUser->save();

    session([
        'portal_vendor_user' => $vendorUser->name,
    ]);

    return back()->with('portal_notice', 'Profile settings updated successfully.');
});

Route::post('/portal/vendor/media/upload', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'entity_type' => ['required', Rule::in(['property', 'service', 'room', 'profile', 'menu', 'vehicle'])],
        'entity_id' => ['nullable', 'integer', 'min:1'],
        'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        'alt_text' => ['required', 'string', 'max:190'],
        'is_primary' => ['nullable', 'boolean'],
    ]);

    $file = $request->file('photo');
    $imageSize = @getimagesize($file->getPathname());
    if (!is_array($imageSize) || count($imageSize) < 2) {
        return back()->withErrors(['profile' => 'Uploaded file is not a valid image.'])->withInput();
    }

    $widthPx = (int) $imageSize[0];
    $heightPx = (int) $imageSize[1];
    $fileSizeKb = (int) ceil(((int) $file->getSize()) / 1024);

    if ($widthPx < 1200 || $heightPx < 800) {
        return back()->withErrors(['profile' => 'Image dimensions must be at least 1200x800 pixels.'])->withInput();
    }
    if ($widthPx > 10000 || $heightPx > 10000) {
        return back()->withErrors(['profile' => 'Image dimensions exceed allowed maximum of 10000x10000 pixels.'])->withInput();
    }

    $qualityGrade = ($widthPx >= 2400 && $heightPx >= 1600 && $fileSizeKb <= 6000) ? 'A' : 'B';
    $filePath = $file->store('vendor-listings/' . $vendorUserId, 'public');

    if ((bool) ($validated['is_primary'] ?? false)) {
        DB::table('vendor_listing_media')
            ->where('vendor_user_id', $vendorUserId)
            ->where('entity_type', (string) $validated['entity_type'])
            ->where('entity_id', filled($validated['entity_id'] ?? null) ? (int) $validated['entity_id'] : null)
            ->update(['is_primary' => false, 'updated_at' => now()]);
    }

    $mediaPayload = [
        'vendor_user_id' => $vendorUserId,
        'entity_type' => (string) $validated['entity_type'],
        'entity_id' => filled($validated['entity_id'] ?? null) ? (int) $validated['entity_id'] : null,
        'file_path' => (string) $filePath,
        'mime_type' => $file->getMimeType(),
        'alt_text' => trim((string) ($validated['alt_text'] ?? '')),
        'is_primary' => (bool) ($validated['is_primary'] ?? false),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_listing_media', 'width_px')) {
        $mediaPayload['width_px'] = $widthPx;
    }
    if (Schema::hasColumn('vendor_listing_media', 'height_px')) {
        $mediaPayload['height_px'] = $heightPx;
    }
    if (Schema::hasColumn('vendor_listing_media', 'file_size_kb')) {
        $mediaPayload['file_size_kb'] = $fileSizeKb;
    }
    if (Schema::hasColumn('vendor_listing_media', 'quality_grade')) {
        $mediaPayload['quality_grade'] = $qualityGrade;
    }

    DB::table('vendor_listing_media')->insert($mediaPayload);

    return back()->with('portal_notice', 'Photo uploaded successfully.');
});

Route::post('/portal/vendor/rooms/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    $selectedCategories = vendorPortalSelectedCategories($vendorUser);
    if (!vendorPortalRequiresAccommodation($selectedCategories)) {
        return back()->withErrors(['profile' => 'Room categories are available for accommodation vendors only.']);
    }

    $validated = $request->validate([
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
        'bed_type' => ['nullable', 'string', 'max:80'],
        'amenities' => ['nullable', 'string', 'max:3000'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
    ]);

    DB::table('vendor_property_room_categories')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => trim((string) ($validated['amenities'] ?? '')),
        'base_price' => (float) ($validated['base_price'] ?? 0),
        'currency' => 'MVR',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Room category added.');
});

Route::post('/portal/vendor/properties/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    $categoryKeys = array_keys(vendorPortalCategoryMap());

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', Rule::in($categoryKeys)],
        'property_type' => ['required', Rule::in(['property', 'service'])],
        'location' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'area_value' => ['nullable', 'numeric', 'min:1', 'max:100000'],
        'area_unit' => ['nullable', Rule::in(['sqm', 'sqft'])],
        'bedroom_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'bathroom_count' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        'capacity_value' => ['nullable', 'integer', 'min:1', 'max:20000'],
        'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
        'safety_certifications' => ['nullable', 'string', 'max:2000'],
        'accessibility_features' => ['nullable', 'string', 'max:2000'],
    ]);

    $allowedForUser = vendorPortalSelectedCategories($vendorUser);
    if (!in_array((string) $validated['listing_category'], $allowedForUser, true)) {
        return back()->withErrors(['profile' => 'Select category in onboarding before creating this listing.']);
    }

    $propertyDetails = vendorPortalBuildPropertyDetails($validated, (string) $validated['listing_category']);
    $propertyDetailErrors = vendorPortalValidatePropertyDetails((string) $validated['listing_category'], $propertyDetails);
    if (!empty($propertyDetailErrors)) {
        return back()->withErrors(['profile' => implode(' ', $propertyDetailErrors)])->withInput();
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'name' => trim((string) $validated['name']),
        'property_type' => (string) $validated['property_type'],
        'location' => trim((string) ($validated['location'] ?? '')),
        'description' => trim((string) ($validated['description'] ?? '')),
        'status' => 'active',
        'base_price' => (float) ($validated['base_price'] ?? 0),
        'currency' => 'MVR',
        'max_guests' => (int) ($validated['max_guests'] ?? 1),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_properties', 'listing_category')) {
        $payload['listing_category'] = (string) $validated['listing_category'];
    }
    if (Schema::hasColumn('vendor_properties', 'listing_details')) {
        $payload['listing_details'] = empty($propertyDetails) ? null : json_encode($propertyDetails);
    }

    DB::table('vendor_properties')->insert($payload);

    return back()->with('portal_notice', 'Property/service listing added.');
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
    $categoryKeys = array_keys(vendorPortalCategoryMap());

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', Rule::in($categoryKeys)],
        'category' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:3000'],
        'price' => ['required', 'numeric', 'min:0'],
        'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
        'property_id' => ['nullable', 'integer'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'lead_time_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
        'min_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'quantity_unit' => ['nullable', Rule::in(['seat', 'room', 'desk', 'vehicle', 'ticket', 'table', 'pass'])],
        'compliance_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $allowedForUser = vendorPortalSelectedCategories($vendorUser);
    if (!in_array((string) $validated['listing_category'], $allowedForUser, true)) {
        return back()->withErrors(['profile' => 'Select category in onboarding before creating this service.']);
    }

    $serviceDetails = vendorPortalBuildServiceDetails($validated, (string) $validated['listing_category']);
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
        'price' => (float) $validated['price'],
        'currency' => 'MVR',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_services', 'listing_category')) {
        $payload['listing_category'] = (string) $validated['listing_category'];
    }
    if (Schema::hasColumn('vendor_services', 'service_details')) {
        $payload['service_details'] = empty($serviceDetails) ? null : json_encode($serviceDetails);
    }

    DB::table('vendor_services')->insert($payload);

    return back()->with('portal_notice', 'Service added successfully.');
});

Route::post('/portal/vendor/availability/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_availability_slots')) {
        return back()->withErrors(['profile' => 'Vendor availability table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'slot_date' => ['required', 'date'],
        'inventory' => ['required', 'integer', 'min:0', 'max:100000'],
        'is_closed' => ['nullable', 'boolean'],
        'vendor_property_id' => ['nullable', 'integer'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::table('vendor_availability_slots')->updateOrInsert(
        [
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
            'slot_date' => (string) $validated['slot_date'],
        ],
        [
            'inventory' => (int) $validated['inventory'],
            'is_closed' => (bool) ($validated['is_closed'] ?? false),
            'notes' => trim((string) ($validated['notes'] ?? '')),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return back()->with('portal_notice', 'Availability updated.');
});

Route::post('/portal/vendor/reservations/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_reservations')) {
        return back()->withErrors(['profile' => 'Vendor reservations table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'customer_name' => ['required', 'string', 'max:160'],
        'customer_email' => ['required', 'email', 'max:190'],
        'start_at' => ['required', 'date'],
        'end_at' => ['required', 'date', 'after_or_equal:start_at'],
        'guests' => ['required', 'integer', 'min:1', 'max:10000'],
        'total_amount' => ['required', 'numeric', 'min:0'],
        'vendor_property_id' => ['nullable', 'integer'],
        'vendor_service_id' => ['nullable', 'integer'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::table('vendor_reservations')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
        'vendor_service_id' => filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null,
        'customer_name' => trim((string) $validated['customer_name']),
        'customer_email' => strtolower(trim((string) $validated['customer_email'])),
        'start_at' => (string) $validated['start_at'],
        'end_at' => (string) $validated['end_at'],
        'guests' => (int) $validated['guests'],
        'total_amount' => (float) $validated['total_amount'],
        'currency' => 'MVR',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'notes' => trim((string) ($validated['notes'] ?? '')),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Reservation added.');
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
        'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
        'payment_status' => ['required', Rule::in(['unpaid', 'partially_paid', 'paid', 'refunded'])],
    ]);

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'status' => (string) $validated['status'],
            'payment_status' => (string) $validated['payment_status'],
            'updated_at' => now(),
        ]);

    return back()->with('portal_notice', 'Reservation status updated.');
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
        'rule_type' => ['required', Rule::in(['flat', 'percent', 'nightly', 'weekend_markup'])],
        'value' => ['required', 'numeric', 'min:0'],
        'starts_on' => ['nullable', 'date'],
        'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        'vendor_property_id' => ['nullable', 'integer'],
        'vendor_service_id' => ['nullable', 'integer'],
    ]);

    DB::table('vendor_pricing_rules')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
        'vendor_service_id' => filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null,
        'name' => trim((string) $validated['name']),
        'rule_type' => (string) $validated['rule_type'],
        'value' => (float) $validated['value'],
        'starts_on' => $validated['starts_on'] ?? null,
        'ends_on' => $validated['ends_on'] ?? null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

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
        'tax_id' => ['nullable', 'string', 'max:120'],
        'billing_email' => ['required', 'email', 'max:190'],
        'payout_method' => ['required', Rule::in(['bank_transfer', 'mobile_wallet', 'manual'])],
        'payout_reference' => ['nullable', 'string', 'max:190'],
        'bank_name' => ['nullable', 'string', 'max:190'],
        'bank_account_last4' => ['nullable', 'string', 'max:8'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
        'currency' => ['nullable', 'string', 'max:8'],
        'invoice_prefix' => ['nullable', 'string', 'max:30'],
    ]);

    DB::table('vendor_billing_details')->updateOrInsert(
        [
            'vendor_user_id' => $vendorUserId,
        ],
        [
            'business_name' => trim((string) $validated['business_name']),
            'tax_id' => trim((string) ($validated['tax_id'] ?? '')),
            'billing_email' => strtolower(trim((string) $validated['billing_email'])),
            'payout_method' => (string) $validated['payout_method'],
            'payout_reference' => trim((string) ($validated['payout_reference'] ?? '')),
            'bank_name' => trim((string) ($validated['bank_name'] ?? '')),
            'bank_account_last4' => trim((string) ($validated['bank_account_last4'] ?? '')),
            'billing_address' => trim((string) ($validated['billing_address'] ?? '')),
            'currency' => strtoupper(trim((string) ($validated['currency'] ?? 'MVR'))),
            'invoice_prefix' => strtoupper(trim((string) ($validated['invoice_prefix'] ?? 'INV'))),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return back()->with('portal_notice', 'Billing details updated.');
});
