<section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview" data-panel-group="listings">
            @php
                $consoleCategoryLabel = $forcedListingCategory !== ''
                    ? (string) ($listingCategoryLabelMap[$forcedListingCategory] ?? ucwords(str_replace('_', ' ', $forcedListingCategory)))
                    : '';
                $consoleTitleLabel = $consoleCategoryLabel !== '' ? $consoleCategoryLabel . ' Listings' : 'My Listings';
                $reservationSummaryByProperty = collect($vendorReservationSummaryByProperty ?? []);
                $overviewPropertyCount = $vendorProperties->count();
                $overviewReservationCount = (int) ($vendorDashboardSnapshot['reservations_count'] ?? $vendorReservations->count());
                $overviewConfirmedCount = (int) ($vendorDashboardSnapshot['confirmed_reservations'] ?? $vendorReservations
                    ->filter(static function ($reservation): bool {
                        return strtolower((string) ($reservation->status ?? 'pending')) === 'confirmed';
                    })
                    ->count());
                $overviewGrossRevenue = (float) ($vendorDashboardSnapshot['gross_collections_total'] ?? $vendorReservations
                    ->sum(static fn ($reservation) => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0)));
                $overviewPendingReviewCount = $vendorProperties
                    ->filter(static fn ($property): bool => strtolower(trim((string) ($property->listing_moderation_status ?? 'draft'))) === 'pending_review')
                    ->count();

                if ($forcedListingCategory !== '') {
                    $forcedCategoryProperties = $propertiesByCategory->get($forcedListingCategory, collect());
                    $forcedCategoryPropertyIds = $forcedCategoryProperties
                        ->pluck('id')
                        ->map(static fn ($id) => (int) $id)
                        ->filter(static fn (int $id): bool => $id > 0)
                        ->values();
                    $forcedCategoryReservationSummaries = $forcedCategoryPropertyIds
                        ->map(static fn (int $propertyId) => $reservationSummaryByProperty->get($propertyId))
                        ->filter();

                    $overviewPropertyCount = $forcedCategoryProperties->count();
                    $overviewReservationCount = (int) $forcedCategoryReservationSummaries->sum(static fn ($summary) => (int) ($summary->reservations_count ?? 0));
                    $overviewConfirmedCount = (int) $forcedCategoryReservationSummaries->sum(static fn ($summary) => (int) ($summary->confirmed_count ?? 0));
                    $overviewGrossRevenue = (float) $forcedCategoryReservationSummaries->sum(static fn ($summary) => (float) ($summary->gross_total ?? 0));
                    $overviewPendingReviewCount = $forcedCategoryProperties
                        ->filter(static fn ($property): bool => strtolower(trim((string) ($property->listing_moderation_status ?? 'draft'))) === 'pending_review')
                        ->count();
                }

                $categoryQuery = $forcedListingCategory !== '' ? ('?category=' . urlencode($forcedListingCategory)) : '';
            @endphp
            <div class="ops-header">
                <p class="ops-title">{{ $consoleTitleLabel }}</p>
                @if ($forcedListingCategory !== '')
                    <div class="inline-actions">
                        <a class="btn btn-primary" href="/vendor/listings/{{ $forcedListingCategory }}/create">Add {{ $consoleCategoryLabel }}</a>
                        <a class="btn btn-secondary" href="/vendor?page=setup&mode=simple">Open Setup Wizard</a>
                    </div>
                @endif
            </div>
            @if ($forcedListingCategory !== '')
                <div class="inline-actions" style="margin:0 0 10px;">
                    <a class="btn btn-secondary" href="/vendor/reservations?category={{ urlencode($forcedListingCategory) }}#vendorAvailabilitySection">Open Availability Calendar</a>
                    <a class="btn btn-secondary" href="/vendor/pricing?category={{ urlencode($forcedListingCategory) }}#vendorPricingSection">Open Pricing Rules</a>
                </div>
            @endif
            @if (!$vendorCanManageListings)
                <p class="wizard-note" style="margin-bottom:10px;">Listings, operations, and pricing are currently locked. Complete My Account compliance details and wait for admin verification approval.</p>
            @endif
            @if (!$hasSelectedCategories)
                <p class="wizard-note">Select at least one category in Category Wizard before creating listings.</p>
            @endif
            <div class="ops-metrics">
                @if ($forcedListingCategory !== '')
                    <article class="ops-metric">
                        <p class="metric-label">Listings</p>
                        <p class="metric-value">{{ $overviewPropertyCount }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Reservations</p>
                        <p class="metric-value">{{ $overviewReservationCount }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Confirmed</p>
                        <p class="metric-value">{{ $overviewConfirmedCount }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Gross Revenue</p>
                        <p class="metric-value">{{ number_format($overviewGrossRevenue, 2) }} MVR</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Pending Review</p>
                        <p class="metric-value">{{ $overviewPendingReviewCount }}</p>
                    </article>
                @else
                    <article class="ops-metric">
                        <p class="metric-label">Properties</p>
                        <p class="metric-value">{{ $vendorProperties->count() }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Services</p>
                        <p class="metric-value">{{ $vendorServices->count() }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Availability Days</p>
                        <p class="metric-value">{{ $vendorAvailability->count() }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Reservations</p>
                        <p class="metric-value">{{ $vendorReservations->count() }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Pricing Rules</p>
                        <p class="metric-value">{{ $vendorPricingRules->count() }}</p>
                    </article>
                    <article class="ops-metric">
                        <p class="metric-label">Billing Profile</p>
                        <p class="metric-value">{{ $vendorBilling ? 'Ready' : 'Missing' }}</p>
                    </article>
                @endif
            </div>

            @if ($vendorCanManageListings && $hasSelectedCategories)
                <article class="listing-setup-wizard" aria-label="New listing setup wizard">
                    <div class="listing-setup-wizard-head">
                        <div>
                            <p class="listing-setup-wizard-label">New Listing Wizard</p>
                            <h3>Build your listing in 4 clear steps</h3>
                            <p>Follow the same booking-engine flow every time: create listing, add rooms or inventory, upload media, then submit for approval.</p>
                        </div>
                        <div class="listing-setup-wizard-actions">
                            @if ($forcedListingCategory !== '')
                                <a class="btn btn-primary" href="/vendor/listings/{{ $forcedListingCategory }}/create">Start New {{ $consoleCategoryLabel }}</a>
                            @else
                                <a class="btn btn-primary" href="/vendor/listings/create">Start New Listing</a>
                            @endif
                        </div>
                    </div>
                    <div class="listing-setup-steps" role="list" aria-label="Listing creation steps">
                        <div class="listing-setup-step" role="listitem">
                            <span class="listing-setup-step-index">1</span>
                            <div>
                                <p class="listing-setup-step-title">Create Listing Basics</p>
                                <p class="listing-setup-step-copy">Select category, pricing model, and core listing details.</p>
                            </div>
                        </div>
                        <div class="listing-setup-step" role="listitem">
                            <span class="listing-setup-step-index">2</span>
                            <div>
                                <p class="listing-setup-step-title">Add Rooms Or Inventory</p>
                                <p class="listing-setup-step-copy">Define sellable units, capacities, and rate context.</p>
                            </div>
                        </div>
                        <div class="listing-setup-step" role="listitem">
                            <span class="listing-setup-step-index">3</span>
                            <div>
                                <p class="listing-setup-step-title">Upload Professional Media</p>
                                <p class="listing-setup-step-copy">Attach photos or videos that match each listing and room.</p>
                            </div>
                        </div>
                        <div class="listing-setup-step" role="listitem">
                            <span class="listing-setup-step-index">4</span>
                            <div>
                                <p class="listing-setup-step-title">Submit For Approval</p>
                                <p class="listing-setup-step-copy">Send the listing for moderation and publish when approved.</p>
                            </div>
                        </div>
                    </div>
                </article>
            @endif
        </section>

<script>
(function () {
    if (window.__vendorSeaRouteInlineBound) {
        return;
    }
    window.__vendorSeaRouteInlineBound = true;

    document.addEventListener('click', function (event) {
        const toggleButton = event.target.closest('[data-toggle-sea-route-table]');
        if (toggleButton) {
            const propertyId = String(toggleButton.getAttribute('data-toggle-sea-route-table') || '').trim();
            if (propertyId !== '') {
                const panel = document.querySelector('[data-sea-route-table-row="' + propertyId + '"]');
                if (panel) {
                    panel.hidden = !panel.hidden;
                }
            }
            return;
        }

        const openEditButton = event.target.closest('[data-open-sea-leg-edit]');
        if (openEditButton) {
            const editId = String(openEditButton.getAttribute('data-open-sea-leg-edit') || '').trim();
            if (editId !== '') {
                const form = document.querySelector('[data-sea-leg-edit-form="' + editId + '"]');
                if (form) {
                    form.hidden = false;
                }
            }
            return;
        }

        const closeEditButton = event.target.closest('[data-close-sea-leg-edit]');
        if (closeEditButton) {
            const editId = String(closeEditButton.getAttribute('data-close-sea-leg-edit') || '').trim();
            if (editId !== '') {
                const form = document.querySelector('[data-sea-leg-edit-form="' + editId + '"]');
                if (form) {
                    form.hidden = true;
                }
            }
        }
    });
})();
</script>

        <section id="vendorPropertiesSection" class="card ops-section" aria-label="Vendor properties" data-panel-group="listings" data-listing-step="1">
            <div class="ops-grid properties-grid">
                @php
                    $oldPropertyAmenities = collect(old('property_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldPropertyFeatures = collect(old('property_features', []))->map(fn ($item) => (string) $item)->all();
                    $workspaceAmenityCatalog = [
                        'workdesk' => 'Workdesk',
                        'wifi' => 'WiFi',
                        'printing' => 'Printing',
                        'water_bottles' => 'Water Bottles',
                        'coffee' => 'Coffee',
                        'tea' => 'Tea',
                        'snacks' => 'Snacks',
                    ];
                    $transferOptionCatalog = [
                        'car' => 'Car',
                        'van' => 'Van',
                        'ferry' => 'Ferry',
                        'speedboat' => 'SpeedBoat',
                        'seaplane' => 'SeaPlane',
                        'domestic_flight' => 'Domestic Flight',
                    ];
                    $oldWorkspaceAmenityStatusInput = old('workspace_amenity_status', []);
                    $oldWorkspaceAmenitiesFree = collect(old('workspace_amenities_free', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldWorkspaceAmenitiesPaid = collect(old('workspace_amenities_paid', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldTransferOptions = collect(old('transfer_options', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldTransferRatesInput = old('transfer_rates', []);
                    $oldWorkspaceAmenityStatus = [];
                    foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel) {
                        $statusValue = 'not_available';
                        if (in_array($workspaceAmenityKey, $oldWorkspaceAmenitiesPaid, true)) {
                            $statusValue = 'paid';
                        } elseif (in_array($workspaceAmenityKey, $oldWorkspaceAmenitiesFree, true)) {
                            $statusValue = 'free';
                        } else {
                            $legacyStatusValue = strtolower(trim((string) ($oldWorkspaceAmenityStatusInput[$workspaceAmenityKey] ?? '')));
                            if (in_array($legacyStatusValue, ['free', 'paid', 'not_available'], true)) {
                                $statusValue = $legacyStatusValue;
                            } elseif (in_array($workspaceAmenityKey, ['workdesk', 'wifi'], true)) {
                                $statusValue = 'free';
                            }
                        }
                        $oldWorkspaceAmenityStatus[$workspaceAmenityKey] = $statusValue;
                    }
                    $oldRoomAmenities = collect(old('room_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldBathroomAmenities = collect(old('bathroom_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $transportModeOptionsCollection = collect($transportModeOptions ?? []);
                    $transportModeOptionGroups = $transportModeOptionsCollection
                        ->groupBy(fn ($item) => strtolower(trim((string) ($item['group'] ?? 'other'))));
                    $accommodationFacilityOptionsCollection = collect($accommodationFacilityOptions ?? []);
                    $roomAmenityOptionsCollection = collect($roomAmenityOptions ?? []);
                    $bathroomAmenityOptionsCollection = collect($bathroomAmenityOptions ?? []);
                    $propertyAmenityOptionsCollection = collect($propertyAmenityOptions ?? [])->values();
                    if ($propertyAmenityOptionsCollection->isEmpty()) {
                        $propertyAmenityOptionsCollection = $accommodationFacilityOptionsCollection->values();
                    }
                    $propertyFeatureOptionsCollection = collect($propertyFeatureOptions ?? [])->values();
                    $roomBedTypeOptionsCollection = collect($roomBedTypeOptions ?? [])->values();
                    $excursionTypeOptionsCollection = collect($excursionTypeOptions ?? [])->values();
                    $restaurantMealServiceOptionsCollection = collect($restaurantMealServiceOptions ?? [])->values();
                    $vehicleRentalTypeOptionsCollection = collect($vehicleRentalTypeOptions ?? [])->values();
                    $vehicleRentalTypeOptionGroups = $vehicleRentalTypeOptionsCollection
                        ->groupBy(fn ($item) => strtolower(trim((string) ($item['group'] ?? 'other'))));
                    $createFormPartialMap = [
                        'accommodation' => 'vendor-portal.partials.forms.create.accommodation',
                        'liveaboard' => 'vendor-portal.partials.forms.create.liveaboard',
                        'sea_transport' => 'vendor-portal.partials.forms.create.sea_transport',
                        'marine_transport' => 'vendor-portal.partials.forms.create.sea_transport',
                        'land_transport' => 'vendor-portal.partials.forms.create.land_transport',
                        'water_sports' => 'vendor-portal.partials.forms.create.water_sports',
                        'excursion' => 'vendor-portal.partials.forms.create.excursion',
                        'remote_workspace' => 'vendor-portal.partials.forms.create.remote_workspace',
                        'conference_room' => 'vendor-portal.partials.forms.create.conference_room',
                        'resort_day_visit' => 'vendor-portal.partials.forms.create.resort_day_visit',
                        'restaurant' => 'vendor-portal.partials.forms.create.restaurant',
                        'vehicle_rental' => 'vendor-portal.partials.forms.create.vehicle_rental',
                    ];
                    $allowedCategoryKeys = collect($listingCategoryViewOrder ?? [])
                        ->map(static fn ($categoryKey) => vendorPortalCanonicalCategory((string) $categoryKey))
                        ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
                        ->values();
                    $canManageAnyCategory = $allowedCategoryKeys->isNotEmpty();
                    $createCategoryFallback = vendorPortalCanonicalCategory((string) ($allowedCategoryKeys->first() ?? 'accommodation'));
                    $activeCreateCategory = vendorPortalCanonicalCategory((string) ($forcedListingCategory !== '' ? $forcedListingCategory : $createCategoryFallback));
                    if (!$allowedCategoryKeys->contains($activeCreateCategory)) {
                        $activeCreateCategory = $createCategoryFallback;
                    }
                    $activeCreateFormPartial = $createFormPartialMap[$activeCreateCategory] ?? 'vendor-portal.partials.forms.create.accommodation';
                @endphp
                @if (!$canManageAnyCategory)
                    <article class="ops-form ops-field-wide">
                        <p class="wizard-note" style="margin:0;">No category is unlocked for this account yet. Ask admin to verify at least one registered category before creating or editing listings.</p>
                    </article>
                @endif
                @if ($showCreatePropertyForm && $canManageAnyCategory)
                    <article class="ops-form ops-field-wide">
                        <section class="ops-note" style="margin-bottom:12px; border:1px solid #cfe0eb; border-radius:10px; padding:10px 12px; background:#f7fbff;">
                            <p style="margin:0 0 6px; font-weight:700; color:#1d4b66;">Pricing Guidance For Vendors</p>
                            <p style="margin:0; color:#416479; font-size:0.84rem; line-height:1.45;">
                                Enter customer-facing sell prices as all-inclusive amounts. Your listed price must already include: 12% Workation commission, payment gateway fee (MIB/BML 4% or Stripe 6.5%), and applicable taxes/fees.
                                These items are shown as an included breakdown to guests and are not added again at checkout.
                            </p>
                        </section>
                        @include($activeCreateFormPartial)

                    </article>
                @endif
                <div class="category-listings-stack" aria-label="Category listing views" @if (!$canManageAnyCategory) hidden @endif>
                    @foreach ($allowedCategoryKeys as $categoryKey)
                        @php
                            $categoryProperties = $propertiesByCategory->get($categoryKey, collect());
                            $categoryLabel = $listingCategoryLabelMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', $categoryKey));
                        @endphp
                        <article class="category-listing-section" id="category-view-{{ $categoryKey }}" data-category-view="{{ $categoryKey }}">
                            @if ($categoryProperties->isEmpty())
                                <p class="ops-empty">No {{ strtolower((string) $categoryLabel) }} listings yet. Use <strong>Add {{ $categoryLabel }}</strong> to create the first listing for this category.</p>
                            @else
                                <div class="ops-table-wrap">
                                    <table class="ops-table is-compact listing-management-table" aria-label="{{ $categoryLabel }} listings table">
                                        <thead>
                                            <tr>
                                                <th>Listing</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($categoryProperties as $property)
                                                @php
                                                    $propertyId = (int) ($property->id ?? 0);
                                                    $propertyRooms = $roomsByPropertyId->get($propertyId, collect());
                                                    $propertyRentalItems = ($rentalItemsByPropertyId ?? collect())->get($propertyId, collect());
                                                    $propertyDetails = [];
                                                    if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                                                        $decodedPropertyDetails = json_decode((string) $property->listing_details, true);
                                                        if (is_array($decodedPropertyDetails)) {
                                                            $propertyDetails = $decodedPropertyDetails;
                                                        }
                                                    }
                                                    $editCategoryRaw = strtolower((string) ($property->listing_category ?? $categoryKey));
                                                    $editCategory = preg_replace('/[^a-z0-9]+/', '_', $editCategoryRaw) ?? $editCategoryRaw;
                                                    $editCategory = trim((string) preg_replace('/_+/', '_', $editCategory), '_');
                                                    $propertyAmenityValues = [];
                                                    $propertyFeatureValues = [];
                                                    if (isset($propertyDetails['property_amenities']) && is_array($propertyDetails['property_amenities'])) {
                                                        $propertyAmenityValues = array_map(static fn ($item) => (string) $item, $propertyDetails['property_amenities']);
                                                    }
                                                    if (isset($propertyDetails['property_features']) && is_array($propertyDetails['property_features'])) {
                                                        $propertyFeatureValues = array_map(static fn ($item) => (string) $item, $propertyDetails['property_features']);
                                                    }
                                                    $workspaceAmenityConfig = is_array($propertyDetails['workspace_amenities'] ?? null)
                                                        ? $propertyDetails['workspace_amenities']
                                                        : [];
                                                    $workspaceAmenityFreeValues = [];
                                                    $workspaceAmenityPaidValues = [];
                                                    foreach ($workspaceAmenityConfig as $workspaceAmenityKey => $workspaceAmenityRow) {
                                                        if (!is_array($workspaceAmenityRow)) {
                                                            continue;
                                                        }
                                                        $workspaceAmenityStatus = strtolower(trim((string) ($workspaceAmenityRow['status'] ?? 'not_available')));
                                                        if ($workspaceAmenityStatus === 'free') {
                                                            $workspaceAmenityFreeValues[] = strtolower(trim((string) $workspaceAmenityKey));
                                                        } elseif ($workspaceAmenityStatus === 'paid') {
                                                            $workspaceAmenityPaidValues[] = strtolower(trim((string) $workspaceAmenityKey));
                                                        }
                                                    }
                                                    $transferOptionValues = [];
                                                    if (isset($propertyDetails['transfer_options']) && is_array($propertyDetails['transfer_options'])) {
                                                        $transferOptionValues = array_map(static fn ($item) => strtolower(trim((string) $item)), $propertyDetails['transfer_options']);
                                                    }
                                                    $transferRates = is_array($propertyDetails['transfer_rates'] ?? null)
                                                        ? $propertyDetails['transfer_rates']
                                                        : [];
                                                    $transportMode = strtolower((string) ($propertyDetails['transport_mode'] ?? ''));
                                                    $transportPricingBasis = strtolower((string) ($propertyDetails['transport_pricing_basis'] ?? ''));
                                                    if ($transportPricingBasis === '') {
                                                        $transportPricingBasis = preg_match('/\b(speed ?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)\b/', $transportMode) ? 'per_seat' : 'per_trip';
                                                    }
                                                    $transportTripType = strtolower((string) ($propertyDetails['transport_trip_type'] ?? ''));
                                                    $transportPricingModel = strtolower((string) ($propertyDetails['transport_pricing_model'] ?? ''));
                                                    $listingStatus = strtoupper((string) ($property->status ?? 'active'));
                                                    $listingType = strtoupper((string) ($property->property_type ?? 'N/A'));
                                                    $propertyMediaItems = $propertyMediaByPropertyId->get($propertyId, collect());
                                                    $listingStatusClass = 'is-neutral';
                                                    if (strtolower($listingStatus) === 'active') {
                                                        $listingStatusClass = 'is-active';
                                                    } elseif (strtolower($listingStatus) === 'inactive') {
                                                        $listingStatusClass = 'is-inactive';
                                                    }
                                                    $listingModerationStatus = strtolower(trim((string) ($property->listing_moderation_status ?? 'draft')));
                                                    if (!in_array($listingModerationStatus, ['draft', 'pending_review', 'approved', 'rejected', 'suspended'], true)) {
                                                        $listingModerationStatus = 'draft';
                                                    }
                                                    $moderationChipClass = match($listingModerationStatus) {
                                                        'approved'      => 'is-active',
                                                        'pending_review'=> 'is-pending',
                                                        'rejected'      => 'is-inactive',
                                                        'suspended'     => 'is-inactive',
                                                        default         => 'is-neutral',
                                                    };
                                                    $moderationLabel = match($listingModerationStatus) {
                                                        'draft'         => 'Draft',
                                                        'pending_review'=> 'Pending Review',
                                                        'approved'      => 'Approved',
                                                        'rejected'      => 'Rejected',
                                                        'suspended'     => 'Suspended',
                                                        default         => strtoupper($listingModerationStatus),
                                                    };
                                                @endphp
                                                <tr data-property-row="{{ $propertyId }}" data-listing-category="{{ $categoryKey }}">
                                                    <td class="listing-cell-main">
                                                        @php
                                                            $primaryPropertyMedia = $propertyMediaItems->firstWhere('is_primary', true);
                                                            if (!$primaryPropertyMedia) {
                                                                $primaryPropertyMedia = $propertyMediaItems->first();
                                                            }
                                                            $listingThumbUrl = '';
                                                            if ($primaryPropertyMedia) {
                                                                $listingMediaId = (int) ($primaryPropertyMedia->id ?? 0);
                                                                if ($listingMediaId > 0) {
                                                                    $listingThumbUrl = '/media/vendor/' . $listingMediaId . '/thumb';
                                                                } else {
                                                                    $listingThumbUrl = vendorMediaStorageUrlFromPath((string) ($primaryPropertyMedia->file_path ?? '')) ?? '';
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="listing-card-head">
                                                            <div class="listing-card-thumb" aria-hidden="true">
                                                                @if ($listingThumbUrl !== '')
                                                                    <img src="{{ $listingThumbUrl }}" alt="{{ (string) ($property->name ?? 'Listing') }} thumbnail" loading="lazy" onerror="if(!this.dataset.fb && this.src !== '/images/placeholders/listing-fallback.svg' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='/images/placeholders/listing-fallback.svg';}else{this.onerror=null;}">
                                                                @else
                                                                    <i class="fa-solid fa-building"></i>
                                                                @endif
                                                            </div>
                                                            <div class="listing-card-main">
                                                                <div class="listing-summary-line">
                                                                    <strong>{{ $property->name }}</strong>
                                                                    <span class="ops-chip">ID {{ $propertyId }}</span>
                                                                    <span class="ops-chip">{{ $listingType }}</span>
                                                                    <span class="ops-chip listing-status-chip {{ $listingStatusClass }}">{{ $listingStatus }}</span>
                                                                    <span class="ops-chip listing-status-chip {{ $moderationChipClass }}" title="Moderation status">{{ $moderationLabel }}</span>
                                                                    @if (in_array($categoryKey, ['accommodation', 'liveaboard'], true))
                                                                        <span class="ops-chip">Rooms: {{ $propertyRooms->count() }}</span>
                                                                    @elseif ($categoryKey === 'water_sports')
                                                                        <span class="ops-chip">Equipment: {{ $propertyRentalItems->count() }}</span>
                                                                    @endif
                                                                </div>
                                                                @if ($listingModerationStatus === 'rejected' && !empty($property->listing_admin_notes))
                                                                    <p style="margin:6px 0 0;font-size:0.78rem;color:#7a2020;background:#fff0ef;border:1px solid #f0b7b3;border-radius:8px;padding:6px 9px;">Admin note: {{ $property->listing_admin_notes }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="listing-cell-actions-cell">
                                                        <div class="listing-cell-actions">
                                                            <div class="listing-actions-compact">
                                                                <div class="listing-actions-row">
                                                                    <a class="btn btn-secondary" href="/vendor/listings/{{ $editCategory }}/{{ $propertyId }}/edit">Edit</a>
                                                                    @if ($categoryKey === 'sea_transport')
                                                                        <a class="btn btn-secondary" href="/vendor/listings/{{ $editCategory }}/{{ $propertyId }}/edit#vessel-details-section">Vessel Details</a>
                                                                        <a class="btn btn-secondary" href="/vendor/listings/{{ $editCategory }}/{{ $propertyId }}/edit#route-fares-section">Route &amp; Fares</a>
                                                                        <button class="btn btn-secondary" type="button" data-toggle-sea-route-table="{{ $propertyId }}">Route/Fare Table</button>
                                                                    @endif
                                                                    <button class="btn btn-secondary" type="button" data-toggle-property-media="{{ $propertyId }}">Manage Media</button>
                                                                    @if (in_array($categoryKey, ['accommodation', 'liveaboard'], true))
                                                                        <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ $propertyId }}">Add Room</button>
                                                                    @endif
                                                                    @if ($categoryKey === 'water_sports')
                                                                        <button class="btn btn-secondary" type="button" data-open-rental-item-form data-property-id="{{ $propertyId }}">Add Equipment</button>
                                                                    @endif
                                                                    <span class="listing-actions-break" aria-hidden="true"></span>
                                                                    @if ($listingModerationStatus === 'pending_review')
                                                                        <span class="ops-chip is-pending">Under Review</span>
                                                                    @else
                                                                        <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/delete" onsubmit="return confirm('Delete this listing?');">
                                                                            @csrf
                                                                            <input type="hidden" name="portal_listing_mode" value="manage">
                                                                            <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                            <button class="btn btn-danger" type="submit">Delete Listing</button>
                                                                        </form>
                                                                    @endif
                                                                    @if (in_array($listingModerationStatus, ['draft', 'rejected'], true))
                                                                        <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/submit-for-review">
                                                                            @csrf
                                                                            <input type="hidden" name="portal_listing_mode" value="manage">
                                                                            <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                            <button class="btn btn-primary" type="submit">Submit For Approval</button>
                                                                        </form>
                                                                    @elseif ($listingModerationStatus === 'approved')
                                                                        <span class="ops-chip is-active">Live for bookings</span>
                                                                    @elseif ($listingModerationStatus === 'suspended')
                                                                        <span class="ops-chip is-inactive">Publishing suspended</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @php
                                                                $editFormPartialMap = [
                                                                    'accommodation' => 'vendor-portal.partials.forms.edit.accommodation',
                                                                    'liveaboard' => 'vendor-portal.partials.forms.edit.liveaboard',
                                                                    'sea_transport' => 'vendor-portal.partials.forms.edit.sea_transport',
                                                                    'marine_transport' => 'vendor-portal.partials.forms.edit.sea_transport',
                                                                    'land_transport' => 'vendor-portal.partials.forms.edit.land_transport',
                                                                    'water_sports' => 'vendor-portal.partials.forms.edit.water_sports',
                                                                    'excursion' => 'vendor-portal.partials.forms.edit.excursion',
                                                                    'remote_workspace' => 'vendor-portal.partials.forms.edit.remote_workspace',
                                                                    'conference_room' => 'vendor-portal.partials.forms.edit.conference_room',
                                                                    'resort_day_visit' => 'vendor-portal.partials.forms.edit.resort_day_visit',
                                                                    'restaurant' => 'vendor-portal.partials.forms.edit.restaurant',
                                                                    'vehicle_rental' => 'vendor-portal.partials.forms.edit.vehicle_rental',
                                                                ];
                                                                $activeEditCategory = vendorPortalCanonicalCategory((string) $editCategory);
                                                                $activeEditFormPartial = $editFormPartialMap[$activeEditCategory] ?? 'vendor-portal.partials.forms.edit.accommodation';
                                                            @endphp
                                                            <div class="media-upload-row" data-property-media-panel="{{ $propertyId }}" hidden>
                                                                <form class="media-panel-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                    @csrf
                                                                    <input type="hidden" name="portal_listing_mode" value="manage">
                                                                    <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                    <input type="hidden" name="entity_type" value="property">
                                                                    <input type="hidden" name="entity_id" value="{{ $propertyId }}">
                                                                    <input type="hidden" name="panel_entity_type" value="property">
                                                                    <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $property->name }} photo" placeholder="Photo alt text" required>
                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                    <p class="media-panel-hint">JPG/PNG/WebP · max 2 MB · recommended 1600×900</p>
                                                                    <div class="media-panel-bar">
                                                                        <button class="btn btn-secondary" type="submit">Upload</button>
                                                                        <button class="btn btn-secondary" type="button" data-close-property-media="{{ $propertyId }}">Close</button>
                                                                    </div>
                                                                </form>
                                                                @if ($propertyMediaItems->isEmpty())
                                                                    <p class="ops-empty">No listing photos uploaded yet. Upload at least one cover photo for better conversion.</p>
                                                                @else
                                                                    <form class="gallery-media-form" method="POST" action="/portal/vendor/media/bulk-delete" onsubmit="return confirm('Remove selected photos?');" data-gallery-selection-form>
                                                                        @csrf
                                                                        <input type="hidden" name="portal_listing_mode" value="manage">
                                                                        <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                        <input type="hidden" name="panel_entity_type" value="property">
                                                                        <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                        <div class="media-panel-bar gallery-toolbar">
                                                                            <label class="feature-item" style="margin:0;"><input type="checkbox" data-gallery-select-all> Select all</label>
                                                                            <button class="btn btn-danger" type="submit" data-gallery-bulk-delete-button disabled>Delete Selected (0)</button>
                                                                        </div>
                                                                        <div class="gallery-grid">
                                                                            @foreach ($propertyMediaItems as $media)
                                                                                @php
                                                                                    $mediaUrl = '/media/vendor/' . (int) ($media->id ?? 0) . '/banner';
                                                                                    $mediaFallbackUrl = vendorMediaStorageUrlFromPath((string) ($media->file_path ?? '')) ?? '';
                                                                                @endphp
                                                                                <article class="gallery-card">
                                                                                    <img src="{{ $mediaUrl }}" onerror="if(!this.dataset.fallbackTried){this.dataset.fallbackTried='1';this.src='{{ $mediaFallbackUrl }}';}" alt="{{ (string) ($media->alt_text ?? $property->name) }}" loading="lazy">
                                                                                    <div class="gallery-card-body">
                                                                                        <label class="feature-item" style="margin:0;"><input type="checkbox" name="media_ids[]" value="{{ (int) ($media->id ?? 0) }}" data-gallery-select-item> Select</label>
                                                                                        <div class="gallery-card-actions">
                                                                                            @if ((bool) ($media->is_primary ?? false))
                                                                                                <span class="ops-chip">Primary</span>
                                                                                            @else
                                                                                                <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary">
                                                                                                    @csrf
                                                                                                    <input type="hidden" name="portal_listing_mode" value="manage">
                                                                                                    <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                                                    <input type="hidden" name="panel_entity_type" value="property">
                                                                                                    <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                                                    <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                                </form>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                </article>
                                                                            <!-- Row 1: Meta bar -->
                                                                            <div class="booking-card-meta-bar">
                                                                                <span class="booking-number-link">#{{ str_pad($propertyId, 6, '0', STR_PAD_LEFT) }}</span>
                                                                                <span>{{ $property->created_at ? $property->created_at->format('M d, Y') : '' }}</span>
                                                                                <span class="booking-status-badge bs-{{ strtolower($listingStatusClass) }}" style="margin-left:auto;">{{ $listingStatus }}</span>
                                                                            </div>
                                                                            <!-- Row 2: Body -->
                                                                            <div class="booking-card-body">
                                                                                <div class="booking-card-thumb-wrap">
                                                                                    @if ($listingThumbUrl !== '')
                                                                                        <img src="{{ $listingThumbUrl }}" alt="{{ (string) ($property->name ?? 'Listing') }} thumbnail" loading="lazy" onerror="if(!this.dataset.fb && this.src !== '/images/placeholders/listing-fallback.svg' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='/images/placeholders/listing-fallback.svg';}else{this.onerror=null;}">
                                                                                    @else
                                                                                        <i class="fa-solid fa-building"></i>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="booking-card-info">
                                                                                    <div class="booking-card-title-row">
                                                                                        <span class="booking-card-property">{{ $property->name }}</span>
                                                                                    </div>
                                                                                    <div class="booking-card-meta">
                                                                                        {{ $listingType }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                <p class="property-subsection-head">Route Legs &amp; Fares ({{ count($routeSchedules) }})</p>
                                                                    </td>
                                                                    <td class="listing-cell-actions-cell">
                                                                        <!-- Row 3: Actions -->
                                                                        <div class="booking-card-actions" style="display:flex;justify-content:flex-end;gap:8px;padding:10px 16px;border-top:1px solid var(--line);background:#fbfdff;">
                                                                            <a class="btn btn-secondary" href="/vendor/listings/{{ $editCategory }}/{{ $propertyId }}/edit">Edit</a>
                                                                            <button class="btn btn-secondary" type="button" data-toggle-property-media="{{ $propertyId }}">Manage Media</button>
                                                                            @if (in_array($categoryKey, ['accommodation', 'liveaboard'], true))
                                                                                <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ $propertyId }}">Add Room</button>
                                                                            @endif
                                                                            <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/delete" onsubmit="return confirm('Delete this listing?');" style="display:inline;">
                                                                                @csrf
                                                                                <input type="hidden" name="portal_listing_mode" value="manage">
                                                                                <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                                <button class="btn btn-danger" type="submit">Delete Listing</button>
                                                                            </form>
                                                                        </div>
                                                                                            ->all();
                                                                                        $bathroomAmenityValues = collect(explode(',', (string) ($room->bathroom_amenities ?? '')))
                                                                                            ->map(static fn ($token) => trim((string) $token))
                                                                                            ->filter(static fn ($token) => $token !== '')
                                                                                            ->values()
                                                                                            ->all();
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>
                                                                                            <div class="listing-summary-line">
                                                                                                <strong>{{ $room->name }}</strong>
                                                                                                <span class="ops-chip">Room ID {{ $roomId }}</span>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="room-summary-line">Qty: {{ (int) ($room->quantity ?? 0) }} | Max: {{ (int) ($room->max_occupancy ?? 0) }} | {{ (int) ($room->room_size_sqm ?? 0) > 0 ? ((int) ($room->room_size_sqm ?? 0) . 'sqm') : 'Size n/a' }} | Floor: {{ trim((string) ($room->floor_info ?? '')) !== '' ? (string) ($room->floor_info ?? '') : 'n/a' }} | Room Only: {{ $property->currency ?? 'MVR' }} {{ number_format((float) (($room->meal_plan_room_only_price ?? 0) > 0 ? ($room->meal_plan_room_only_price ?? 0) : ($room->base_price ?? 0)), 2) }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div class="inline-actions listing-actions-inline listing-actions-compact">
                                                                                                <div class="listing-actions-row">
                                                                                                    <button class="btn btn-secondary" type="button" data-open-room-edit data-room-edit-id="{{ $roomId }}">Edit Room</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-toggle-room-media="{{ $roomId }}">Manage Media</button>
                                                                                                    <span class="listing-actions-break" aria-hidden="true"></span>
                                                                                                    <form method="POST" action="/portal/vendor/rooms/{{ $roomId }}/delete" onsubmit="return confirm('Remove this room category?');">
                                                                                                        @csrf
                                                                                                        <button class="btn btn-danger" type="submit">Remove Room</button>
                                                                                                    </form>
                                                                                                </div>
                                                                                            </div>
                                                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/rooms/{{ $roomId }}/update" data-room-edit-form="{{ $roomId }}" hidden>
                                                                                                @csrf
                                                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ (string) ($room->name ?? '') }}" required>
                                                                                                <input class="ops-input" name="quantity" type="number" min="1" max="10000" value="{{ (int) ($room->quantity ?? 1) }}" required>
                                                                                                <input class="ops-input" name="max_occupancy" type="number" min="1" max="50" value="{{ (int) ($room->max_occupancy ?? 1) }}" required>
                                                                                                <input class="ops-input" name="room_size_sqm" type="number" min="5" max="2000" value="{{ (int) ($room->room_size_sqm ?? 0) > 0 ? (int) ($room->room_size_sqm ?? 0) : '' }}" placeholder="Room size (sqm)">
                                                                                                <input class="ops-input" name="floor_info" type="text" maxlength="80" value="{{ trim((string) ($room->floor_info ?? '')) }}" placeholder="Floor info (e.g. 1-3)">
                                                                                                <select class="ops-select" name="has_window">
                                                                                                    <option value="1" @selected((int) ($room->has_window ?? 1) === 1)>Has window(s)</option>
                                                                                                    <option value="0" @selected((int) ($room->has_window ?? 1) === 0)>No windows</option>
                                                                                                </select>
                                                                                                <select class="ops-select" name="non_smoking">
                                                                                                    <option value="1" @selected((int) ($room->non_smoking ?? 1) === 1)>Non-smoking</option>
                                                                                                    <option value="0" @selected((int) ($room->non_smoking ?? 1) === 0)>Smoking allowed</option>
                                                                                                </select>
                                                                                                <input class="ops-input" name="extra_person_capacity" type="number" min="0" max="20" value="{{ (int) ($room->extra_person_capacity ?? 0) > 0 ? (int) ($room->extra_person_capacity ?? 0) : '' }}" placeholder="Extra adult capacity">
                                                                                                <input class="ops-input" name="child_capacity" type="number" min="0" max="20" value="{{ (int) ($room->child_capacity ?? 0) > 0 ? (int) ($room->child_capacity ?? 0) : '' }}" placeholder="Child capacity">
                                                                                                <div class="ops-field ops-field-wide" style="grid-column:1/-1;">
                                                                                                    <section class="listing-form-section listing-price-band" aria-label="Room meal plan pricing">
                                                                                                        <div class="listing-form-section-head">
                                                                                                            <h4>Room Pricing Matrix</h4>
                                                                                                            <p>Set local MVR and foreign USD rates by meal plan and guest segment.</p>
                                                                                                        </div>
                                                                                                        <div class="listing-transfer-table">
                                                                                                            <div class="listing-transfer-head" aria-hidden="true">
                                                                                                                <span>Rate</span>
                                                                                                                <span>Local (MVR)</span>
                                                                                                                <span>Foreign (USD)</span>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Room Only</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="meal_plan_room_only_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_room_only_price_local ?? 0) > 0 ? (float) ($room->meal_plan_room_only_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="meal_plan_room_only_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_room_only_price_usd ?? 0) > 0 ? (float) ($room->meal_plan_room_only_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Bed & Breakfast</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="meal_plan_bb_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_bb_price_local ?? 0) > 0 ? (float) ($room->meal_plan_bb_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="meal_plan_bb_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_bb_price_usd ?? 0) > 0 ? (float) ($room->meal_plan_bb_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Half Board</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="meal_plan_hb_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_hb_price_local ?? 0) > 0 ? (float) ($room->meal_plan_hb_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="meal_plan_hb_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_hb_price_usd ?? 0) > 0 ? (float) ($room->meal_plan_hb_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Full Board</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="meal_plan_fb_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_fb_price_local ?? 0) > 0 ? (float) ($room->meal_plan_fb_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="meal_plan_fb_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_fb_price_usd ?? 0) > 0 ? (float) ($room->meal_plan_fb_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>All Inclusive</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="meal_plan_ai_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_ai_price_local ?? 0) > 0 ? (float) ($room->meal_plan_ai_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="meal_plan_ai_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->meal_plan_ai_price_usd ?? 0) > 0 ? (float) ($room->meal_plan_ai_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Extra Adult</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="extra_person_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->extra_person_price_local ?? 0) > 0 ? (float) ($room->extra_person_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="extra_person_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->extra_person_price_usd ?? 0) > 0 ? (float) ($room->extra_person_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                            <div class="listing-transfer-row">
                                                                                                                <div class="listing-transfer-option"><label><span>Child Rate</span></label></div>
                                                                                                                <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="child_price_local" type="number" min="0" step="0.01" value="{{ (float) ($room->child_price_local ?? 0) > 0 ? (float) ($room->child_price_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                                <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="child_price_usd" type="number" min="0" step="0.01" value="{{ (float) ($room->child_price_usd ?? 0) > 0 ? (float) ($room->child_price_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </section>
                                                                                                </div>
                                                                                                @php
                                                                                                    $roomBedTypeCurrent = strtolower(trim((string) ($room->bed_type ?? '')));
                                                                                                    $knownRoomBedTypes = $roomBedTypeOptionsCollection
                                                                                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                                                        ->filter(fn ($item) => $item !== '')
                                                                                                        ->values()
                                                                                                        ->all();
                                                                                                @endphp
                                                                                                <select class="ops-select" name="bed_type">
                                                                                                    <option value="" @selected($roomBedTypeCurrent === '')>Bed Type</option>
                                                                                                    @if ($roomBedTypeCurrent !== '' && !in_array($roomBedTypeCurrent, $knownRoomBedTypes, true))
                                                                                                        <option value="{{ $roomBedTypeCurrent }}" selected>{{ ucfirst(str_replace('_', ' ', $roomBedTypeCurrent)) }} (existing)</option>
                                                                                                    @endif
                                                                                                    @foreach ($roomBedTypeOptionsCollection as $roomBedTypeOption)
                                                                                                        @php
                                                                                                            $roomBedTypeValue = strtolower(trim((string) ($roomBedTypeOption['value'] ?? '')));
                                                                                                            $roomBedTypeLabel = trim((string) ($roomBedTypeOption['label'] ?? $roomBedTypeValue));
                                                                                                        @endphp
                                                                                                        @if ($roomBedTypeValue !== '' && $roomBedTypeLabel !== '')
                                                                                                            <option value="{{ $roomBedTypeValue }}" @selected($roomBedTypeCurrent === $roomBedTypeValue)>{{ $roomBedTypeLabel }}</option>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </select>
                                                                                                <select class="ops-select" name="bathroom_type">
                                                                                                    <option value="" @selected((string) ($room->bathroom_type ?? '') === '')>Bathroom Type</option>
                                                                                                    <option value="ensuite" @selected((string) ($room->bathroom_type ?? '') === 'ensuite')>Ensuite</option>
                                                                                                    <option value="private_external" @selected((string) ($room->bathroom_type ?? '') === 'private_external')>Private External</option>
                                                                                                    <option value="shared" @selected((string) ($room->bathroom_type ?? '') === 'shared')>Shared</option>
                                                                                                </select>
                                                                                                <input class="ops-input" name="bathroom_count" type="number" min="0" max="20" value="{{ (int) ($room->bathroom_count ?? 0) > 0 ? (int) ($room->bathroom_count ?? 0) : '' }}" placeholder="Bathroom Count">
                                                                                                <div class="feature-checklist">
                                                                                                    @foreach ($roomAmenityOptionsCollection as $roomAmenityOption)
                                                                                                        @php
                                                                                                            $roomAmenityValue = trim((string) ($roomAmenityOption['value'] ?? ''));
                                                                                                            $roomAmenityLabel = trim((string) ($roomAmenityOption['label'] ?? $roomAmenityValue));
                                                                                                        @endphp
                                                                                                        @if ($roomAmenityValue !== '' && $roomAmenityLabel !== '')
                                                                                                            <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="{{ $roomAmenityValue }}" @checked(in_array($roomAmenityValue, $roomAmenityValues, true))> {{ $roomAmenityLabel }}</label>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <div class="feature-checklist">
                                                                                                    @foreach ($bathroomAmenityOptionsCollection as $bathroomAmenityOption)
                                                                                                        @php
                                                                                                            $bathroomAmenityValue = trim((string) ($bathroomAmenityOption['value'] ?? ''));
                                                                                                            $bathroomAmenityLabel = trim((string) ($bathroomAmenityOption['label'] ?? $bathroomAmenityValue));
                                                                                                        @endphp
                                                                                                        @if ($bathroomAmenityValue !== '' && $bathroomAmenityLabel !== '')
                                                                                                            <label class="feature-item"><input type="checkbox" name="bathroom_amenities[]" value="{{ $bathroomAmenityValue }}" @checked(in_array($bathroomAmenityValue, $bathroomAmenityValues, true))> {{ $bathroomAmenityLabel }}</label>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <textarea class="ops-textarea" name="child_policy" rows="3" maxlength="3000" placeholder="Child policy">{{ trim((string) ($room->child_policy ?? '')) }}</textarea>
                                                                                                <textarea class="ops-textarea" name="extra_bed_policy" rows="3" maxlength="3000" placeholder="Extra bed policy">{{ trim((string) ($room->extra_bed_policy ?? '')) }}</textarea>
                                                                                                @if ($categoryKey === 'liveaboard')
                                                                                                    <div class="ops-field ops-field-wide" style="grid-column:1/-1;">
                                                                                                        <section class="listing-form-section" aria-label="Liveaboard package transfer setup">
                                                                                                            <div class="listing-form-section-head">
                                                                                                                <h4>Package Transfer Setup</h4>
                                                                                                                <p>Define embark and disembark points at package level. Enable mid-trip join only when this package supports it.</p>
                                                                                                            </div>
                                                                                                            <div class="ops-form-grid">
                                                                                                                <div class="ops-field">
                                                                                                                    <label>Transfer Included In Package</label>
                                                                                                                    <select class="ops-select" name="package_transfer_included">
                                                                                                                        <option value="1" @selected((int) ($room->package_transfer_included ?? 1) === 1)>Yes</option>
                                                                                                                        <option value="0" @selected((int) ($room->package_transfer_included ?? 1) === 0)>No</option>
                                                                                                                    </select>
                                                                                                                </div>
                                                                                                                <div class="ops-field">
                                                                                                                    <label>Package Embark Point</label>
                                                                                                                    <input class="ops-input" name="package_embark_point" type="text" maxlength="120" value="{{ trim((string) ($room->package_embark_point ?? '')) }}" placeholder="e.g. Male'">
                                                                                                                </div>
                                                                                                                <div class="ops-field">
                                                                                                                    <label>Package Disembark Point</label>
                                                                                                                    <input class="ops-input" name="package_disembark_point" type="text" maxlength="120" value="{{ trim((string) ($room->package_disembark_point ?? '')) }}" placeholder="e.g. S. Gan">
                                                                                                                </div>
                                                                                                                <div class="ops-field">
                                                                                                                    <label>Allow Mid-Trip Join</label>
                                                                                                                    <select class="ops-select" name="package_mid_trip_join_allowed">
                                                                                                                        <option value="0" @selected((int) ($room->package_mid_trip_join_allowed ?? 0) === 0)>No</option>
                                                                                                                        <option value="1" @selected((int) ($room->package_mid_trip_join_allowed ?? 0) === 1)>Yes</option>
                                                                                                                    </select>
                                                                                                                </div>
                                                                                                                <div class="ops-field">
                                                                                                                    <label>Mid-Trip Join Transfer Fee (MVR)</label>
                                                                                                                    <input class="ops-input" name="package_mid_trip_join_transfer_fee" type="number" min="0" step="0.01" value="{{ isset($room->package_mid_trip_join_transfer_fee) && (float) $room->package_mid_trip_join_transfer_fee > 0 ? (float) $room->package_mid_trip_join_transfer_fee : '' }}" placeholder="Optional surcharge">
                                                                                                                </div>
                                                                                                                <div class="ops-field ops-field-wide">
                                                                                                                    <label>Transfer Notes</label>
                                                                                                                    <textarea class="ops-textarea" name="package_transfer_notes" rows="2" maxlength="2000" placeholder="Optional notes for boarding and transfer terms">{{ trim((string) ($room->package_transfer_notes ?? '')) }}</textarea>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </section>
                                                                                                    </div>
                                                                                                @endif
                                                                                                <div class="inline-actions">
                                                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Room</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-room-edit data-room-edit-id="{{ $roomId }}">Cancel Edit</button>
                                                                                                </div>
                                                                                            </form>
                                                                                            <div class="media-upload-row" data-room-media-panel="{{ $roomId }}" hidden>
                                                                                                <form class="media-panel-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                                                    @csrf
                                                                                                    <input type="hidden" name="portal_listing_mode" value="manage">
                                                                                                    <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                                                    <input type="hidden" name="entity_type" value="room">
                                                                                                    <input type="hidden" name="entity_id" value="{{ $roomId }}">
                                                                                                    <input type="hidden" name="panel_entity_type" value="room">
                                                                                                    <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $room->name }} photo" placeholder="Photo alt text" required>
                                                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                                                    <p class="media-panel-hint">JPG/PNG/WebP · max 2 MB · recommended 1600×900</p>
                                                                                                    <div class="media-panel-bar">
                                                                                                        <button class="btn btn-secondary" type="submit">Upload</button>
                                                                                                        <button class="btn btn-secondary" type="button" data-close-room-media="{{ $roomId }}">Close</button>
                                                                                                    </div>
                                                                                                </form>
                                                                                                @if ($roomMediaItems->isEmpty())
                                                                                                    <p class="ops-empty">No room photos uploaded yet.</p>
                                                                                                @else
                                                                                                    <form class="gallery-media-form" method="POST" action="/portal/vendor/media/bulk-delete" onsubmit="return confirm('Remove selected photos?');" data-gallery-selection-form>
                                                                                                        @csrf
                                                                                                        <input type="hidden" name="portal_listing_mode" value="manage">
                                                                                                        <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                                                        <input type="hidden" name="panel_entity_type" value="room">
                                                                                                        <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                        <div class="media-panel-bar gallery-toolbar">
                                                                                                            <label class="feature-item" style="margin:0;"><input type="checkbox" data-gallery-select-all> Select all</label>
                                                                                                            <button class="btn btn-danger" type="submit" data-gallery-bulk-delete-button disabled>Delete Selected (0)</button>
                                                                                                        </div>
                                                                                                        <div class="gallery-grid">
                                                                                                            @foreach ($roomMediaItems as $media)
                                                                                                                @php
                                                                                                                    $roomMediaUrl = '/media/vendor/' . (int) ($media->id ?? 0) . '/banner';
                                                                                                                    $roomMediaFallbackUrl = vendorMediaStorageUrlFromPath((string) ($media->file_path ?? '')) ?? '';
                                                                                                                @endphp
                                                                                                                <article class="gallery-card">
                                                                                                                    <img src="{{ $roomMediaUrl }}" onerror="if(!this.dataset.fallbackTried){this.dataset.fallbackTried='1';this.src='{{ $roomMediaFallbackUrl }}';}" alt="{{ (string) ($media->alt_text ?? $room->name) }}" loading="lazy">
                                                                                                                    <div class="gallery-card-body">
                                                                                                                        <label class="feature-item" style="margin:0;"><input type="checkbox" name="media_ids[]" value="{{ (int) ($media->id ?? 0) }}" data-gallery-select-item> Select</label>
                                                                                                                        <div class="gallery-card-actions">
                                                                                                                            @if ((bool) ($media->is_primary ?? false))
                                                                                                                                <span class="ops-chip">Primary</span>
                                                                                                                            @else
                                                                                                                                <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary">
                                                                                                                                    @csrf
                                                                                                                                    <input type="hidden" name="portal_listing_mode" value="manage">
                                                                                                                                    <input type="hidden" name="portal_listing_category" value="{{ $categoryKey }}">
                                                                                                                                    <input type="hidden" name="panel_entity_type" value="room">
                                                                                                                                    <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                                                    <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                                                                </form>
                                                                                                                            @endif
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </article>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                    </form>
                                                                                                @endif
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @if ($categoryKey === 'water_sports')
                                                    <tr class="water-sports-equipment-stretch-row">
                                                        <td colspan="2">
                                                            <div class="accommodation-room-stretch">
                                                                <p class="property-subsection-head">Rental Equipment Under This Shop ({{ $propertyRentalItems->count() }})</p>
                                                                @if ($propertyRentalItems->isEmpty())
                                                                    <p class="ops-empty">No rental equipment for this listing yet. Add equipment items to start taking water sports bookings.</p>
                                                                @else
                                                                    <div class="ops-table-wrap">
                                                                        <table class="ops-table is-compact room-management-table" aria-label="Equipment for property {{ $propertyId }}">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Equipment</th>
                                                                                    <th>Summary</th>
                                                                                    <th>Actions</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($propertyRentalItems as $rentalItem)
                                                                                    @php
                                                                                        $rentalItemId = (int) ($rentalItem->id ?? 0);
                                                                                        $rentalItemType = strtolower(trim((string) ($rentalItem->equipment_type ?? 'other')));
                                                                                        $rentalItemTypeBadge = ucfirst(str_replace('_', ' ', $rentalItemType));
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>
                                                                                            <div class="listing-summary-line">
                                                                                                <strong>{{ $rentalItem->name }}</strong>
                                                                                                <span class="ops-chip">ID {{ $rentalItemId }}</span>
                                                                                                <span class="ops-chip">{{ $rentalItemTypeBadge }}</span>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            @php
                                                                                                $rentalSummaryText = (($rentalItem->pricing_type ?? 'hourly') === 'per_seat')
                                                                                                    ? 'Adult: MVR ' . number_format((float) ($rentalItem->price_per_seat_adult_local ?? 0), 2) . ' / USD ' . number_format((float) ($rentalItem->price_per_seat_adult_usd ?? 0), 2) . ' per seat'
                                                                                                    : 'Local: MVR ' . number_format((float) ($rentalItem->price_per_hour_local ?? 0), 2) . '/hr | Foreign: USD ' . number_format((float) ($rentalItem->price_per_hour_usd ?? 0), 2) . '/hr | Min: ' . (int) ($rentalItem->min_duration_minutes ?? 30) . 'min | Max: ' . (int) ($rentalItem->max_duration_hours ?? 8) . 'hrs';
                                                                                            @endphp
                                                                                            <span class="room-summary-line">Qty: {{ (int) ($rentalItem->quantity_available ?? 1) }} | {{ $rentalSummaryText }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div class="inline-actions listing-actions-inline listing-actions-compact">
                                                                                                <div class="listing-actions-row">
                                                                                                    <button class="btn btn-secondary" type="button" data-open-rental-item-edit data-rental-item-edit-id="{{ $rentalItemId }}">Edit</button>
                                                                                                    <span class="listing-actions-break" aria-hidden="true"></span>
                                                                                                    <form method="POST" action="/portal/vendor/water-sports-equipment/{{ $rentalItemId }}/delete" onsubmit="return confirm('Remove this rental item?');">
                                                                                                        @csrf
                                                                                                        <button class="btn btn-danger" type="submit">Remove</button>
                                                                                                    </form>
                                                                                                </div>
                                                                                            </div>
                                                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/water-sports-equipment/{{ $rentalItemId }}/update" data-rental-item-edit-form="{{ $rentalItemId }}" hidden>
                                                                                                @csrf
                                                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ (string) ($rentalItem->name ?? '') }}" required>
                                                                                                <select class="ops-select" name="equipment_type">
                                                                                                    @php
                                                                                                        $editEquipmentType = strtolower(trim((string) ($rentalItem->equipment_type ?? 'other')));
                                                                                                        $equipmentTypeOptions = [
                                                                                                            'jetski' => 'Jet Ski',
                                                                                                            'snorkeling_gear' => 'Snorkeling Gear',
                                                                                                            'canoe' => 'Canoe',
                                                                                                            'surfboard' => 'Surf Board',
                                                                                                            'paddleboard' => 'Paddle Board',
                                                                                                            'banana_boat' => 'Banana Boat',
                                                                                                            'parasailing' => 'Parasailing',
                                                                                                            'windsurf' => 'Wind Surf',
                                                                                                            'other' => 'Other',
                                                                                                        ];
                                                                                                    @endphp
                                                                                                    @foreach ($equipmentTypeOptions as $etValue => $etLabel)
                                                                                                        <option value="{{ $etValue }}" @selected($editEquipmentType === $etValue)>{{ $etLabel }}</option>
                                                                                                    @endforeach
                                                                                                </select>
                                                                                                <select class="ops-select" name="equipment_category">
                                                                                                    @php
                                                                                                        $editEquipmentCategory = strtolower(trim((string) ($rentalItem->equipment_category ?? 'non_motorized')));
                                                                                                        $equipmentCategoryOptions = [
                                                                                                            'motorized' => 'Motorized',
                                                                                                            'non_motorized' => 'Non-Motorized',
                                                                                                            'adrenaline' => 'Adrenaline',
                                                                                                            'guided' => 'Guided Activity',
                                                                                                            'snorkeling_diving' => 'Snorkeling/Diving',
                                                                                                            'other' => 'Other',
                                                                                                        ];
                                                                                                    @endphp
                                                                                                    @foreach ($equipmentCategoryOptions as $ecValue => $ecLabel)
                                                                                                        <option value="{{ $ecValue }}" @selected($editEquipmentCategory === $ecValue)>{{ $ecLabel }}</option>
                                                                                                    @endforeach
                                                                                                </select>
                                                                                                <textarea class="ops-textarea" name="description" rows="3" maxlength="3000" placeholder="Description">{{ trim((string) ($rentalItem->description ?? '')) }}</textarea>
                                                                                                @php $editPricingType = strtolower(trim((string) ($rentalItem->pricing_type ?? 'hourly'))); @endphp
                                                                                                <div style="display:flex;gap:16px;flex-wrap:wrap;margin:6px 0;">
                                                                                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;">
                                                                                                        <input type="radio" name="pricing_type" value="hourly" @checked($editPricingType !== 'per_seat') style="cursor:pointer;"> Hourly Rental
                                                                                                    </label>
                                                                                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;">
                                                                                                        <input type="radio" name="pricing_type" value="per_seat" @checked($editPricingType === 'per_seat') style="cursor:pointer;"> Per Seat / Per Person
                                                                                                    </label>
                                                                                                </div>
                                                                                                <div class="listing-transfer-table js-pricing-hourly" @if($editPricingType === 'per_seat') style="display:none" @endif>
                                                                                                    <div class="listing-transfer-head" aria-hidden="true">
                                                                                                        <span>Rate</span>
                                                                                                        <span>Price per Hour</span>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Adult Local (MVR/hr)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="price_per_hour_local" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_hour_local ?? 0) > 0 ? (float) ($rentalItem->price_per_hour_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Adult Foreign (USD/hr)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="price_per_hour_usd" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_hour_usd ?? 0) > 0 ? (float) ($rentalItem->price_per_hour_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Child Local (MVR/hr)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="price_per_hour_child_local" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_hour_child_local ?? 0) > 0 ? (float) ($rentalItem->price_per_hour_child_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Child Foreign (USD/hr)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="price_per_hour_child_usd" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_hour_child_usd ?? 0) > 0 ? (float) ($rentalItem->price_per_hour_child_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="listing-transfer-table js-pricing-per-seat" @if($editPricingType !== 'per_seat') style="display:none" @endif>
                                                                                                    <div class="listing-transfer-head" aria-hidden="true">
                                                                                                        <span>Seat Rate</span>
                                                                                                        <span>Price per Person</span>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Adult Local (MVR)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="price_per_seat_adult_local" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_seat_adult_local ?? 0) > 0 ? (float) ($rentalItem->price_per_seat_adult_local ?? 0) : '' }}" placeholder="MVR 0.00"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Adult Foreign (USD)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="price_per_seat_adult_usd" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_seat_adult_usd ?? 0) > 0 ? (float) ($rentalItem->price_per_seat_adult_usd ?? 0) : '' }}" placeholder="USD 0.00"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Child Local (MVR)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Local (MVR)</span><input class="ops-input" name="price_per_seat_child_local" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_seat_child_local ?? 0) > 0 ? (float) ($rentalItem->price_per_seat_child_local ?? 0) : '' }}" placeholder="MVR 0.00 (optional)"></label>
                                                                                                    </div>
                                                                                                    <div class="listing-transfer-row">
                                                                                                        <div class="listing-transfer-option"><label><span>Child Foreign (USD)</span></label></div>
                                                                                                        <label class="listing-transfer-rate"><span>Foreign (USD)</span><input class="ops-input" name="price_per_seat_child_usd" type="number" min="0" step="0.01" value="{{ (float) ($rentalItem->price_per_seat_child_usd ?? 0) > 0 ? (float) ($rentalItem->price_per_seat_child_usd ?? 0) : '' }}" placeholder="USD 0.00 (optional)"></label>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <input class="ops-input js-pricing-hourly" @if($editPricingType === 'per_seat') style="display:none" @endif name="min_duration_minutes" type="number" min="5" max="1440" value="{{ (int) ($rentalItem->min_duration_minutes ?? 30) }}" placeholder="Min duration (minutes)">
                                                                                                <input class="ops-input js-pricing-hourly" @if($editPricingType === 'per_seat') style="display:none" @endif name="max_duration_hours" type="number" min="1" max="24" value="{{ (int) ($rentalItem->max_duration_hours ?? 8) }}" placeholder="Max duration (hours)">
                                                                                                <input class="ops-input" name="min_age_years" type="number" min="0" max="120" value="{{ (int) ($rentalItem->min_age_years ?? 0) }}" placeholder="Minimum age (0 = no restriction)">
                                                                                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;margin:4px 0;">
                                                                                                    <input type="checkbox" name="requires_swimming" value="1" @checked((bool) ($rentalItem->requires_swimming ?? false)) style="cursor:pointer;width:15px;height:15px;">
                                                                                                    Requires ability to swim
                                                                                                </label>
                                                                                                <textarea class="ops-textarea" name="safety_notes" rows="2" maxlength="1000" placeholder="Safety / warning notes for guests">{{ trim((string) ($rentalItem->safety_notes ?? '')) }}</textarea>
                                                                                                <input class="ops-input" name="quantity_available" type="number" min="1" max="10000" value="{{ (int) ($rentalItem->quantity_available ?? 1) }}" placeholder="Quantity available">
                                                                                                <select class="ops-select" name="status">
                                                                                                    <option value="active" @selected(strtolower((string) ($rentalItem->status ?? 'active')) === 'active')>Active</option>
                                                                                                    <option value="inactive" @selected(strtolower((string) ($rentalItem->status ?? 'active')) === 'inactive')>Inactive</option>
                                                                                                </select>
                                                                                                <div class="inline-actions">
                                                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Equipment</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-rental-item-edit data-rental-item-edit-id="{{ $rentalItemId }}">Cancel</button>
                                                                                                </div>
                                                                                            </form>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if (in_array($categoryKey, ['accommodation', 'liveaboard'], true))
                                                    @php
                                                        $showInlineRoomRow = $showCreateRoomForm && (string) old('vendor_property_id') === (string) $propertyId;
                                                    @endphp
                                                    <tr data-inline-room-row="{{ $propertyId }}" @if (!$showInlineRoomRow) hidden @endif>
                                                        <td colspan="2">
                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/rooms/create" data-inline-room-form="{{ $propertyId }}">
                                                                @csrf
                                                                <input type="hidden" name="room_form_intent" value="1">
                                                                <input type="hidden" name="vendor_property_id" value="{{ $propertyId }}">
                                                                <div class="ops-form-grid">
                                                                    <div class="ops-field">
                                                                        <label>Listing</label>
                                                                        <input class="ops-input" type="text" value="{{ $property->name }} (ID {{ $propertyId }})" readonly>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Name</label>
                                                                        <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $showInlineRoomRow ? old('name') : '' }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Quantity</label>
                                                                        <input class="ops-input" name="quantity" type="number" min="1" max="10000" value="{{ $showInlineRoomRow ? old('quantity', 1) : 1 }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Base Occupancy (Adults)</label>
                                                                        <input class="ops-input" name="max_occupancy" type="number" min="1" max="50" value="{{ $showInlineRoomRow ? old('max_occupancy', 1) : 1 }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Size (sqm)</label>
                                                                        <input class="ops-input" name="room_size_sqm" type="number" min="5" max="2000" value="{{ $showInlineRoomRow ? old('room_size_sqm', '') : '' }}" placeholder="e.g. 28">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Floor Info</label>
                                                                        <input class="ops-input" name="floor_info" type="text" maxlength="80" value="{{ $showInlineRoomRow ? old('floor_info', '') : '' }}" placeholder="e.g. 1-3">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Has Window</label>
                                                                        <select class="ops-select" name="has_window">
                                                                            <option value="1" @selected((string) ($showInlineRoomRow ? old('has_window', '1') : '1') === '1')>Yes</option>
                                                                            <option value="0" @selected((string) ($showInlineRoomRow ? old('has_window') : '') === '0')>No</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Smoking Policy</label>
                                                                        <select class="ops-select" name="non_smoking">
                                                                            <option value="1" @selected((string) ($showInlineRoomRow ? old('non_smoking', '1') : '1') === '1')>Non-smoking</option>
                                                                            <option value="0" @selected((string) ($showInlineRoomRow ? old('non_smoking') : '') === '0')>Smoking allowed</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Capacity</label>
                                                                        <input class="ops-input" name="extra_person_capacity" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('extra_person_capacity', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Capacity</label>
                                                                        <input class="ops-input" name="child_capacity" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('child_capacity', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Only Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="meal_plan_room_only_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_room_only_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Only Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="meal_plan_room_only_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_room_only_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>BB Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="meal_plan_bb_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_bb_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>BB Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="meal_plan_bb_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_bb_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>HB Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="meal_plan_hb_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_hb_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>HB Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="meal_plan_hb_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_hb_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>FB Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="meal_plan_fb_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_fb_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>FB Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="meal_plan_fb_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_fb_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>All Inclusive Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="meal_plan_ai_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_ai_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>All Inclusive Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="meal_plan_ai_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('meal_plan_ai_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="extra_person_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('extra_person_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="extra_person_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('extra_person_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="child_price_usd" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('child_price_usd', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="child_price_local" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('child_price_local', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bed Type</label>
                                                                        @php
                                                                            $roomBedTypeOld = strtolower(trim((string) ($showInlineRoomRow ? old('bed_type') : '')));
                                                                            $knownRoomBedTypes = $roomBedTypeOptionsCollection
                                                                                ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                                ->filter(fn ($item) => $item !== '')
                                                                                ->values()
                                                                                ->all();
                                                                        @endphp
                                                                        <select class="ops-select" name="bed_type">
                                                                            <option value="" @selected($roomBedTypeOld === '')>Select</option>
                                                                            @if ($roomBedTypeOld !== '' && !in_array($roomBedTypeOld, $knownRoomBedTypes, true))
                                                                                <option value="{{ $roomBedTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $roomBedTypeOld)) }} (existing)</option>
                                                                            @endif
                                                                            @foreach ($roomBedTypeOptionsCollection as $roomBedTypeOption)
                                                                                @php
                                                                                    $roomBedTypeValue = strtolower(trim((string) ($roomBedTypeOption['value'] ?? '')));
                                                                                    $roomBedTypeLabel = trim((string) ($roomBedTypeOption['label'] ?? $roomBedTypeValue));
                                                                                @endphp
                                                                                @if ($roomBedTypeValue !== '' && $roomBedTypeLabel !== '')
                                                                                    <option value="{{ $roomBedTypeValue }}" @selected($roomBedTypeOld === $roomBedTypeValue)>{{ $roomBedTypeLabel }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bathroom Type</label>
                                                                        <select class="ops-select" name="bathroom_type">
                                                                            <option value="" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === '')>Select</option>
                                                                            <option value="ensuite" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'ensuite')>Ensuite</option>
                                                                            <option value="private_external" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'private_external')>Private External</option>
                                                                            <option value="shared" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'shared')>Shared</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bathroom Count</label>
                                                                        <input class="ops-input" name="bathroom_count" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('bathroom_count', 1) : 1 }}">
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Room Amenities</label>
                                                                        <div class="feature-checklist">
                                                                            @foreach ($roomAmenityOptionsCollection as $roomAmenityOption)
                                                                                @php
                                                                                    $roomAmenityValue = trim((string) ($roomAmenityOption['value'] ?? ''));
                                                                                    $roomAmenityLabel = trim((string) ($roomAmenityOption['label'] ?? $roomAmenityValue));
                                                                                @endphp
                                                                                @if ($roomAmenityValue !== '' && $roomAmenityLabel !== '')
                                                                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="{{ $roomAmenityValue }}" @checked(in_array($roomAmenityValue, $oldRoomAmenities, true))> {{ $roomAmenityLabel }}</label>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Bathroom Amenities</label>
                                                                        <div class="feature-checklist">
                                                                            @foreach ($bathroomAmenityOptionsCollection as $bathroomAmenityOption)
                                                                                @php
                                                                                    $bathroomAmenityValue = trim((string) ($bathroomAmenityOption['value'] ?? ''));
                                                                                    $bathroomAmenityLabel = trim((string) ($bathroomAmenityOption['label'] ?? $bathroomAmenityValue));
                                                                                @endphp
                                                                                @if ($bathroomAmenityValue !== '' && $bathroomAmenityLabel !== '')
                                                                                    <label class="feature-item"><input type="checkbox" name="bathroom_amenities[]" value="{{ $bathroomAmenityValue }}" @checked(in_array($bathroomAmenityValue, $oldBathroomAmenities, true))> {{ $bathroomAmenityLabel }}</label>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Child Policy</label>
                                                                        <textarea class="ops-textarea" name="child_policy" rows="3" maxlength="3000" placeholder="Children of all ages can stay in this room...">{{ $showInlineRoomRow ? old('child_policy', '') : '' }}</textarea>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Cots and Extra Beds Policy</label>
                                                                        <textarea class="ops-textarea" name="extra_bed_policy" rows="3" maxlength="3000" placeholder="Extra beds and cots availability...">{{ $showInlineRoomRow ? old('extra_bed_policy', '') : '' }}</textarea>
                                                                    </div>
                                                                    @if ($categoryKey === 'liveaboard')
                                                                        <div class="ops-field ops-field-wide">
                                                                            <label>Transfer Included In Package</label>
                                                                            <select class="ops-select" name="package_transfer_included">
                                                                                <option value="1" @selected((string) ($showInlineRoomRow ? old('package_transfer_included', '1') : '1') === '1')>Yes</option>
                                                                                <option value="0" @selected((string) ($showInlineRoomRow ? old('package_transfer_included', '1') : '1') === '0')>No</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="ops-field">
                                                                            <label>Package Embark Point</label>
                                                                            <input class="ops-input" name="package_embark_point" type="text" maxlength="120" value="{{ $showInlineRoomRow ? old('package_embark_point', '') : '' }}" placeholder="e.g. Male'">
                                                                        </div>
                                                                        <div class="ops-field">
                                                                            <label>Package Disembark Point</label>
                                                                            <input class="ops-input" name="package_disembark_point" type="text" maxlength="120" value="{{ $showInlineRoomRow ? old('package_disembark_point', '') : '' }}" placeholder="e.g. S. Gan">
                                                                        </div>
                                                                        <div class="ops-field">
                                                                            <label>Allow Mid-Trip Join</label>
                                                                            <select class="ops-select" name="package_mid_trip_join_allowed">
                                                                                <option value="0" @selected((string) ($showInlineRoomRow ? old('package_mid_trip_join_allowed', '0') : '0') === '0')>No</option>
                                                                                <option value="1" @selected((string) ($showInlineRoomRow ? old('package_mid_trip_join_allowed', '0') : '0') === '1')>Yes</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="ops-field">
                                                                            <label>Mid-Trip Join Transfer Fee (MVR)</label>
                                                                            <input class="ops-input" name="package_mid_trip_join_transfer_fee" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('package_mid_trip_join_transfer_fee', '') : '' }}" placeholder="Optional surcharge">
                                                                        </div>
                                                                        <div class="ops-field ops-field-wide">
                                                                            <label>Transfer Notes</label>
                                                                            <textarea class="ops-textarea" name="package_transfer_notes" rows="2" maxlength="2000" placeholder="Optional notes for boarding and transfer terms">{{ $showInlineRoomRow ? old('package_transfer_notes', '') : '' }}</textarea>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="inline-actions" style="margin-top:10px;">
                                                                    <button class="btn btn-primary" type="submit">Save Room</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-inline-room-row="{{ $propertyId }}">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if ($categoryKey === 'water_sports')
                                                    @php
                                                        $showInlineRentalItemRow = isset($showCreateRentalItemForm) && $showCreateRentalItemForm && (string) old('vendor_property_id') === (string) $propertyId;
                                                    @endphp
                                                    <tr data-inline-rental-item-row="{{ $propertyId }}" @if (!$showInlineRentalItemRow) hidden @endif>
                                                        <td colspan="2">
                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/water-sports-equipment/create" data-inline-rental-item-form="{{ $propertyId }}">
                                                                @csrf
                                                                <input type="hidden" name="vendor_property_id" value="{{ $propertyId }}">
                                                                <div class="ops-form-grid">
                                                                    <div class="ops-field">
                                                                        <label>Water Sports Shop</label>
                                                                        <input class="ops-input" type="text" value="{{ $property->name }} (ID {{ $propertyId }})" readonly>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Equipment Name</label>
                                                                        <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $showInlineRentalItemRow ? old('name') : '' }}" required placeholder="e.g. Jet Ski (2-seater)">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Equipment Type</label>
                                                                        <select class="ops-select" name="equipment_type">
                                                                            @php
                                                                                $newEquipmentTypeOptions = [
                                                                                    'jetski' => 'Jet Ski',
                                                                                    'snorkeling_gear' => 'Snorkeling Gear',
                                                                                    'canoe' => 'Canoe',
                                                                                    'surfboard' => 'Surf Board',
                                                                                    'paddleboard' => 'Paddle Board',
                                                                                    'banana_boat' => 'Banana Boat',
                                                                                    'parasailing' => 'Parasailing',
                                                                                    'windsurf' => 'Wind Surf',
                                                                                    'other' => 'Other',
                                                                                ];
                                                                                $oldEquipmentType = $showInlineRentalItemRow ? old('equipment_type', 'other') : 'other';
                                                                            @endphp
                                                                            @foreach ($newEquipmentTypeOptions as $etValue => $etLabel)
                                                                                <option value="{{ $etValue }}" @selected($oldEquipmentType === $etValue)>{{ $etLabel }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Equipment Category</label>
                                                                        <select class="ops-select" name="equipment_category">
                                                                            @php
                                                                                $newEquipmentCategoryOptions = [
                                                                                    'motorized' => 'Motorized',
                                                                                    'non_motorized' => 'Non-Motorized',
                                                                                    'adrenaline' => 'Adrenaline',
                                                                                    'guided' => 'Guided Activity',
                                                                                    'snorkeling_diving' => 'Snorkeling/Diving',
                                                                                    'other' => 'Other',
                                                                                ];
                                                                                $oldEquipmentCategory = $showInlineRentalItemRow ? old('equipment_category', 'non_motorized') : 'non_motorized';
                                                                            @endphp
                                                                            @foreach ($newEquipmentCategoryOptions as $ecValue => $ecLabel)
                                                                                <option value="{{ $ecValue }}" @selected($oldEquipmentCategory === $ecValue)>{{ $ecLabel }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Description</label>
                                                                        <textarea class="ops-textarea" name="description" rows="3" maxlength="3000" placeholder="Brief description of this equipment...">{{ $showInlineRentalItemRow ? old('description', '') : '' }}</textarea>
                                                                    </div>
                                                                    {{-- Pricing type --}}
                                                                    @php $oldPricingType = $showInlineRentalItemRow ? old('pricing_type', 'hourly') : 'hourly'; @endphp
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Pricing Model</label>
                                                                        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:4px;">
                                                                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400;">
                                                                                <input type="radio" name="pricing_type" value="hourly" @checked($oldPricingType === 'hourly') style="cursor:pointer;"> Hourly Rental (jet ski, kayak…)
                                                                            </label>
                                                                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400;">
                                                                                <input type="radio" name="pricing_type" value="per_seat" @checked($oldPricingType === 'per_seat') style="cursor:pointer;"> Per Seat / Per Person (parasailing, banana boat…)
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    {{-- Hourly pricing fields --}}
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Adult Price / Hour — Local (MVR)</label>
                                                                        <input class="ops-input" name="price_per_hour_local" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_hour_local', '') : '' }}" placeholder="MVR 0.00">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Adult Price / Hour — Foreign (USD)</label>
                                                                        <input class="ops-input" name="price_per_hour_usd" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_hour_usd', '') : '' }}" placeholder="USD 0.00">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Child Price / Hour — Local (MVR)</label>
                                                                        <input class="ops-input" name="price_per_hour_child_local" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_hour_child_local', '') : '' }}" placeholder="MVR 0.00 (optional)">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Child Price / Hour — Foreign (USD)</label>
                                                                        <input class="ops-input" name="price_per_hour_child_usd" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_hour_child_usd', '') : '' }}" placeholder="USD 0.00 (optional)">
                                                                    </div>
                                                                    {{-- Per-seat pricing fields --}}
                                                                    <div class="ops-field js-pricing-per-seat" @if($oldPricingType !== 'per_seat') style="display:none" @endif>
                                                                        <label>Adult Seat Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="price_per_seat_adult_local" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_seat_adult_local', '') : '' }}" placeholder="MVR 0.00">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-per-seat" @if($oldPricingType !== 'per_seat') style="display:none" @endif>
                                                                        <label>Adult Seat Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="price_per_seat_adult_usd" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_seat_adult_usd', '') : '' }}" placeholder="USD 0.00">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-per-seat" @if($oldPricingType !== 'per_seat') style="display:none" @endif>
                                                                        <label>Child Seat Price — Local (MVR)</label>
                                                                        <input class="ops-input" name="price_per_seat_child_local" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_seat_child_local', '') : '' }}" placeholder="MVR 0.00 (optional)">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-per-seat" @if($oldPricingType !== 'per_seat') style="display:none" @endif>
                                                                        <label>Child Seat Price — Foreign (USD)</label>
                                                                        <input class="ops-input" name="price_per_seat_child_usd" type="number" min="0" step="0.01" value="{{ $showInlineRentalItemRow ? old('price_per_seat_child_usd', '') : '' }}" placeholder="USD 0.00 (optional)">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Minimum Duration (minutes)</label>
                                                                        <input class="ops-input" name="min_duration_minutes" type="number" min="5" max="1440" value="{{ $showInlineRentalItemRow ? old('min_duration_minutes', 30) : 30 }}" placeholder="e.g. 30">
                                                                    </div>
                                                                    <div class="ops-field js-pricing-hourly" @if($oldPricingType === 'per_seat') style="display:none" @endif>
                                                                        <label>Maximum Duration (hours)</label>
                                                                        <input class="ops-input" name="max_duration_hours" type="number" min="1" max="24" value="{{ $showInlineRentalItemRow ? old('max_duration_hours', 8) : 8 }}" placeholder="e.g. 8">
                                                                    </div>
                                                                    {{-- Safety --}}
                                                                    <div class="ops-field">
                                                                        <label>Minimum Age (years)</label>
                                                                        <input class="ops-input" name="min_age_years" type="number" min="0" max="120" value="{{ $showInlineRentalItemRow ? old('min_age_years', 0) : 0 }}" placeholder="0 = no restriction">
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                                                                            <input type="checkbox" name="requires_swimming" value="1" @checked((bool) ($showInlineRentalItemRow ? old('requires_swimming') : false)) style="cursor:pointer;width:16px;height:16px;">
                                                                            Requires ability to swim
                                                                        </label>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Safety / Warning Notes</label>
                                                                        <textarea class="ops-textarea" name="safety_notes" rows="2" maxlength="1000" placeholder="e.g. Helmet and life jacket provided. Not suitable for pregnant guests or those with back problems.">{{ $showInlineRentalItemRow ? old('safety_notes', '') : '' }}</textarea>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Quantity Available</label>
                                                                        <input class="ops-input" name="quantity_available" type="number" min="1" max="10000" value="{{ $showInlineRentalItemRow ? old('quantity_available', 1) : 1 }}" placeholder="1">
                                                                    </div>
                                                                </div>
                                                                <div class="inline-actions" style="margin-top:10px;">
                                                                    <button class="btn btn-primary" type="submit">Save Equipment</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-inline-rental-item-row="{{ $propertyId }}">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>