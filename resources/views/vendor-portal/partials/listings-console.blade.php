<section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview" data-panel-group="listings">
            @php
                $consoleCategoryLabel = $forcedListingCategory !== ''
                    ? (string) ($listingCategoryLabelMap[$forcedListingCategory] ?? ucwords(str_replace('_', ' ', $forcedListingCategory)))
                    : '';
                $consoleTitleLabel = $consoleCategoryLabel !== '' ? $consoleCategoryLabel . ' Listings' : 'My Listings';
            @endphp
            <div class="ops-header">
                <p class="ops-title">{{ $consoleTitleLabel }}</p>
            </div>
            @if (!$vendorCanManageListings)
                <p class="wizard-note" style="margin-bottom:10px;">Listings, operations, and pricing are currently locked. Complete My Account compliance details and wait for admin verification approval.</p>
            @endif
            <div class="inline-actions" style="margin-bottom:10px;">
                <a class="btn btn-secondary" href="/vendor/reservations">Manage Reservations</a>
                <a class="btn btn-secondary" href="/vendor/pricing">Change Pricing</a>
                <a class="btn btn-secondary" href="/vendor/billing">Billing &amp; Refunds</a>
            </div>
            @if (!$hasSelectedCategories)
                <p class="wizard-note">Select at least one category in Category Wizard before creating listings.</p>
            @endif
            <div class="ops-metrics">
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
            </div>
        </section>

        <section id="vendorPropertiesSection" class="card ops-section" aria-label="Vendor properties" data-panel-group="listings" data-listing-step="1">
            <div class="ops-header">
                <p class="ops-title">My Listings by Category</p>
                <span class="ops-chip">{{ $vendorProperties->count() }} total</span>
            </div>
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
                        'marine_transport' => 'vendor-portal.partials.forms.create.marine_transport',
                        'land_transport' => 'vendor-portal.partials.forms.create.land_transport',
                        'water_sports' => 'vendor-portal.partials.forms.create.water_sports',
                        'excursion' => 'vendor-portal.partials.forms.create.excursion',
                        'remote_workspace' => 'vendor-portal.partials.forms.create.remote_workspace',
                        'conference_room' => 'vendor-portal.partials.forms.create.conference_room',
                        'resort_day_visit' => 'vendor-portal.partials.forms.create.resort_day_visit',
                        'restaurant' => 'vendor-portal.partials.forms.create.restaurant',
                        'vehicle_rental' => 'vendor-portal.partials.forms.create.vehicle_rental',
                    ];
                    $createCategoryFallback = vendorPortalCanonicalCategory((string) ($selectedVendorCategories[0] ?? 'accommodation'));
                    $activeCreateCategory = vendorPortalCanonicalCategory((string) ($forcedListingCategory !== '' ? $forcedListingCategory : $createCategoryFallback));
                    $activeCreateFormPartial = $createFormPartialMap[$activeCreateCategory] ?? 'vendor-portal.partials.forms.create.accommodation';
                @endphp
                <article class="ops-form ops-field-wide">
                    @include($activeCreateFormPartial)

                </article>
                <div class="category-listings-stack" aria-label="Category listing views">
                    @foreach ($listingCategoryViewOrder as $categoryKey)
                        @php
                            $categoryProperties = $propertiesByCategory->get($categoryKey, collect());
                            $categoryLabel = $listingCategoryLabelMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', $categoryKey));
                            $categoryPropertyIds = $categoryProperties
                                ->pluck('id')
                                ->map(static fn ($id) => (int) $id)
                                ->filter(static fn (int $id): bool => $id > 0)
                                ->values();
                            $categoryReservations = $vendorReservations
                                ->filter(static function ($reservation) use ($categoryPropertyIds): bool {
                                    return $categoryPropertyIds->contains((int) ($reservation->vendor_property_id ?? 0));
                                })
                                ->values();
                            $categoryConfirmedReservations = $categoryReservations
                                ->filter(static function ($reservation): bool {
                                    return strtolower((string) ($reservation->status ?? 'pending')) === 'confirmed';
                                })
                                ->count();
                            $categoryGrossRevenue = (float) $categoryReservations
                                ->sum(static fn ($reservation) => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0));
                            $categoryPendingReviewCount = $categoryProperties
                                ->filter(static fn ($property): bool => strtolower(trim((string) ($property->listing_moderation_status ?? 'draft'))) === 'pending_review')
                                ->count();
                        @endphp
                        <article class="category-listing-section" id="category-view-{{ $categoryKey }}" data-category-view="{{ $categoryKey }}">
                            <div class="category-listing-header">
                                <h4>{{ $categoryLabel }} Listings</h4>
                                <div class="inline-actions">
                                    <span class="ops-chip">{{ $categoryProperties->count() }} listed</span>
                                    <a class="btn btn-secondary" href="/vendor/listings/create/{{ $categoryKey }}">Add {{ $categoryLabel }}</a>
                                </div>
                            </div>
                            <div class="ops-metrics" style="margin:0 0 10px;">
                                <article class="ops-metric">
                                    <p class="metric-label">Listings</p>
                                    <p class="metric-value">{{ $categoryProperties->count() }}</p>
                                </article>
                                <article class="ops-metric">
                                    <p class="metric-label">Reservations</p>
                                    <p class="metric-value">{{ $categoryReservations->count() }}</p>
                                </article>
                                <article class="ops-metric">
                                    <p class="metric-label">Confirmed</p>
                                    <p class="metric-value">{{ $categoryConfirmedReservations }}</p>
                                </article>
                                <article class="ops-metric">
                                    <p class="metric-label">Gross Revenue</p>
                                    <p class="metric-value">{{ number_format($categoryGrossRevenue, 2) }} MVR</p>
                                </article>
                                <article class="ops-metric">
                                    <p class="metric-label">Pending Review</p>
                                    <p class="metric-value">{{ $categoryPendingReviewCount }}</p>
                                </article>
                            </div>
                            @if ($categoryProperties->isEmpty())
                                <p class="ops-empty">No {{ strtolower((string) $categoryLabel) }} listings yet.</p>
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
                                                    $publishChecklist = portalVendorListingPublishChecklist($property, $propertyDetails, $propertyMediaItems->count(), $propertyRooms->count());
                                                    $publishMissing = (array) ($publishChecklist['missing'] ?? []);
                                                    $publishReady = (bool) ($publishChecklist['ready'] ?? false);
                                                    $publishReadinessLabel = $publishReady
                                                        ? 'Publish Ready'
                                                        : (($publishChecklist['missing_count'] ?? count($publishMissing)) . ' items missing');
                                                    $publishReadinessChipClass = $publishReady ? 'is-active' : 'is-pending';
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
                                                        <div class="listing-summary-line">
                                                            <strong>{{ $property->name }}</strong>
                                                            <span class="ops-chip">ID {{ $propertyId }}</span>
                                                            <span class="ops-chip">{{ $listingType }}</span>
                                                            <span class="ops-chip listing-status-chip {{ $listingStatusClass }}">{{ $listingStatus }}</span>
                                                            <span class="ops-chip listing-status-chip {{ $moderationChipClass }}" title="Moderation status">{{ $moderationLabel }}</span>
                                                            <span class="ops-chip listing-status-chip {{ $publishReadinessChipClass }}" title="Publishing readiness">{{ $publishReadinessLabel }}</span>
                                                            @if ($categoryKey === 'accommodation')
                                                                <span class="ops-chip">Rooms: {{ $propertyRooms->count() }}</span>
                                                            @endif
                                                        </div>
                                                        @if (!$publishReady)
                                                            <p class="listing-publish-hint">Complete before publishing: {{ implode(' • ', array_slice($publishMissing, 0, 4)) }}</p>
                                                        @endif
                                                        @if ($listingModerationStatus === 'rejected' && !empty($property->listing_admin_notes))
                                                            <p style="margin:6px 0 0;font-size:0.78rem;color:#7a2020;background:#fff0ef;border:1px solid #f0b7b3;border-radius:8px;padding:6px 9px;">Admin note: {{ $property->listing_admin_notes }}</p>
                                                        @endif
                                                    </td>
                                                    <td class="listing-cell-actions-cell">
                                                        <div class="listing-cell-actions">
                                                            <div class="listing-actions-compact">
                                                                <div class="listing-actions-row">
                                                                    <button class="btn btn-secondary" type="button" data-open-property-edit data-property-edit-id="{{ $propertyId }}" data-property-edit-category="{{ $editCategory }}">Edit</button>
                                                                    <button class="btn btn-secondary" type="button" data-toggle-property-media="{{ $propertyId }}">Manage Media</button>
                                                                    @if ($categoryKey === 'accommodation')
                                                                        <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ $propertyId }}">Add Room</button>
                                                                    @endif
                                                                    @if ($listingModerationStatus === 'pending_review')
                                                                        <span class="ops-chip is-pending">Under Review</span>
                                                                    @else
                                                                        <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/delete" onsubmit="return confirm('Remove this listing?');">
                                                                            @csrf
                                                                            <button class="btn btn-danger" type="submit">Remove</button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @php
                                                                $editFormPartialMap = [
                                                                    'accommodation' => 'vendor-portal.partials.forms.edit.accommodation',
                                                                    'marine_transport' => 'vendor-portal.partials.forms.edit.marine_transport',
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
                                                            @include($activeEditFormPartial)
                                                            <div class="media-upload-row" data-property-media-panel="{{ $propertyId }}" hidden>
                                                                <form class="inline-table-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                    @csrf
                                                                    <input type="hidden" name="entity_type" value="property">
                                                                    <input type="hidden" name="entity_id" value="{{ $propertyId }}">
                                                                    <input type="hidden" name="panel_entity_type" value="property">
                                                                    <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $property->name }} photo" placeholder="Photo alt text" required>
                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                    <p class="small" style="grid-column:1 / -1; margin:0;">Upload standard: JPG/PNG/WebP, any dimensions accepted, max 2MB per image. Recommended quality source: around 1600x900.</p>
                                                                    <button class="btn btn-secondary" type="submit">Upload</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-property-media="{{ $propertyId }}">Close</button>
                                                                </form>
                                                                @if ($propertyMediaItems->isEmpty())
                                                                    <p class="ops-empty">No listing photos uploaded yet.</p>
                                                                @else
                                                                    <form class="inline-table-form" method="POST" action="/portal/vendor/media/bulk-delete" onsubmit="return confirm('Remove selected photos?');">
                                                                        @csrf
                                                                        <input type="hidden" name="panel_entity_type" value="property">
                                                                        <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                        <div class="inline-actions" style="justify-content:flex-start; margin-bottom:0.75rem;">
                                                                            <button class="btn btn-danger" type="submit">Delete Selected</button>
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
                                                                                    <label class="feature-item" style="margin-bottom:0.5rem;"><input type="checkbox" name="media_ids[]" value="{{ (int) ($media->id ?? 0) }}"> Select</label>
                                                                                    <p class="gallery-card-title">{{ (string) ($media->alt_text ?? 'Listing photo') }}</p>
                                                                                    <form class="gallery-edit-form" method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/update">
                                                                                        @csrf
                                                                                        <input type="hidden" name="panel_entity_type" value="property">
                                                                                        <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                                        <input name="alt_text" type="text" maxlength="190" value="{{ (string) ($media->alt_text ?? 'Listing photo') }}" aria-label="Edit listing photo text">
                                                                                        <button class="btn btn-secondary" type="submit">Save</button>
                                                                                    </form>
                                                                                    <div class="gallery-card-actions">
                                                                                        @if ((bool) ($media->is_primary ?? false))
                                                                                            <span class="ops-chip">Primary</span>
                                                                                        @else
                                                                                            <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary">
                                                                                                @csrf
                                                                                                <input type="hidden" name="panel_entity_type" value="property">
                                                                                                <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                                                <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                            </form>
                                                                                        @endif
                                                                                        <form class="gallery-delete-form" method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/delete" onsubmit="return confirm('Remove this photo?');">
                                                                                            @csrf
                                                                                            <input type="hidden" name="panel_entity_type" value="property">
                                                                                            <input type="hidden" name="panel_entity_id" value="{{ $propertyId }}">
                                                                                            <button class="btn btn-danger" type="submit">Remove</button>
                                                                                        </form>
                                                                                    </div>
                                                                                </div>
                                                                            </article>
                                                                        @endforeach
                                                                    </div>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                            <div class="publish-readiness-box">
                                                                <div class="publish-readiness-head">
                                                                    <strong>Publishing</strong>
                                                                    <span class="ops-chip {{ $publishReadinessChipClass }}">{{ $publishReadinessLabel }}</span>
                                                                </div>
                                                                @if ($publishReady)
                                                                    <p class="small" style="margin:0;">This listing is complete enough to be submitted for admin approval.</p>
                                                                @else
                                                                    <ul class="publish-readiness-list">
                                                                        @foreach ($publishMissing as $missingItem)
                                                                            <li>{{ $missingItem }}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                @endif

                                                                <div class="publish-readiness-actions">
                                                                    @if (in_array($listingModerationStatus, ['draft', 'rejected'], true) && $publishReady)
                                                                        <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/submit-for-review">
                                                                            @csrf
                                                                            <button class="btn btn-primary" type="submit">Publish Listing</button>
                                                                        </form>
                                                                    @elseif (in_array($listingModerationStatus, ['draft', 'rejected'], true))
                                                                        <button class="btn btn-secondary" type="button" disabled>Complete Checklist to Publish</button>
                                                                    @elseif ($listingModerationStatus === 'pending_review')
                                                                        <span class="ops-chip is-pending" style="padding:7px 10px;">Publishing request submitted</span>
                                                                    @elseif ($listingModerationStatus === 'approved')
                                                                        <span class="ops-chip is-active" style="padding:7px 10px;">Live for bookings</span>
                                                                    @elseif ($listingModerationStatus === 'suspended')
                                                                        <span class="ops-chip is-inactive" style="padding:7px 10px;">Publishing suspended</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @if ($categoryKey === 'accommodation')
                                                    <tr class="accommodation-room-stretch-row">
                                                        <td colspan="2">
                                                            <div class="accommodation-room-stretch">
                                                                <p class="property-subsection-head">Rooms Under This Property ({{ $propertyRooms->count() }})</p>
                                                                @if ($propertyRooms->isEmpty())
                                                                    <p class="ops-empty">No rooms for this listing yet.</p>
                                                                @else
                                                                    <div class="ops-table-wrap">
                                                                        <table class="ops-table is-compact room-management-table" aria-label="Rooms for property {{ $propertyId }}">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Room</th>
                                                                                    <th>Summary</th>
                                                                                    <th>Actions</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($propertyRooms as $room)
                                                                                    @php
                                                                                        $roomId = (int) ($room->id ?? 0);
                                                                                        $roomMediaItems = $roomMediaByRoomId->get($roomId, collect());
                                                                                        $roomAmenityValues = collect(explode(',', (string) ($room->amenities ?? '')))
                                                                                            ->map(static fn ($token) => trim((string) $token))
                                                                                            ->filter(static fn ($token) => $token !== '')
                                                                                            ->values()
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
                                                                                            <span class="room-summary-line">Qty: {{ (int) ($room->quantity ?? 0) }} | Max: {{ (int) ($room->max_occupancy ?? 0) }} | {{ (int) ($room->room_size_sqm ?? 0) > 0 ? ((int) ($room->room_size_sqm ?? 0) . 'sqm') : 'Size n/a' }} | Floor: {{ trim((string) ($room->floor_info ?? '')) !== '' ? (string) ($room->floor_info ?? '') : 'n/a' }} | Base: {{ $property->currency ?? 'MVR' }} {{ number_format((float) ($room->base_price ?? 0), 2) }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div class="inline-actions listing-actions-inline listing-actions-compact">
                                                                                                <div class="listing-actions-row">
                                                                                                    <button class="btn btn-secondary" type="button" data-open-room-edit data-room-edit-id="{{ $roomId }}">Edit Room</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-toggle-room-media="{{ $roomId }}">Manage Media</button>
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
                                                                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) ($room->base_price ?? 0) > 0 ? (float) ($room->base_price ?? 0) : '' }}" placeholder="Base room price">
                                                                                                <input class="ops-input" name="extra_person_price" type="number" min="0" step="0.01" value="{{ (float) ($room->extra_person_price ?? 0) > 0 ? (float) ($room->extra_person_price ?? 0) : '' }}" placeholder="Extra adult price">
                                                                                                <input class="ops-input" name="child_price" type="number" min="0" step="0.01" value="{{ (float) ($room->child_price ?? 0) > 0 ? (float) ($room->child_price ?? 0) : '' }}" placeholder="Child price">
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
                                                                                                <div class="inline-actions">
                                                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Room</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-room-edit data-room-edit-id="{{ $roomId }}">Cancel Edit</button>
                                                                                                </div>
                                                                                            </form>
                                                                                            <div class="media-upload-row" data-room-media-panel="{{ $roomId }}" hidden>
                                                                                                <form class="inline-table-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                                                    @csrf
                                                                                                    <input type="hidden" name="entity_type" value="room">
                                                                                                    <input type="hidden" name="entity_id" value="{{ $roomId }}">
                                                                                                    <input type="hidden" name="panel_entity_type" value="room">
                                                                                                    <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $room->name }} photo" placeholder="Photo alt text" required>
                                                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                                                    <p class="small" style="grid-column:1 / -1; margin:0;">Upload standard: JPG/PNG/WebP, any dimensions accepted, max 2MB per image. Recommended quality source: around 1600x900.</p>
                                                                                                    <button class="btn btn-secondary" type="submit">Upload</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-room-media="{{ $roomId }}">Close</button>
                                                                                                </form>
                                                                                                @if ($roomMediaItems->isEmpty())
                                                                                                    <p class="ops-empty">No room photos uploaded yet.</p>
                                                                                                @else
                                                                                                    <form class="inline-table-form" method="POST" action="/portal/vendor/media/bulk-delete" onsubmit="return confirm('Remove selected photos?');">
                                                                                                        @csrf
                                                                                                        <input type="hidden" name="panel_entity_type" value="room">
                                                                                                        <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                        <div class="inline-actions" style="justify-content:flex-start; margin-bottom:0.75rem;">
                                                                                                            <button class="btn btn-danger" type="submit">Delete Selected</button>
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
                                                                                                                    <label class="feature-item" style="margin-bottom:0.5rem;"><input type="checkbox" name="media_ids[]" value="{{ (int) ($media->id ?? 0) }}"> Select</label>
                                                                                                                    <p class="gallery-card-title">{{ (string) ($media->alt_text ?? 'Room photo') }}</p>
                                                                                                                    <form class="gallery-edit-form" method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/update">
                                                                                                                        @csrf
                                                                                                                        <input type="hidden" name="panel_entity_type" value="room">
                                                                                                                        <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                                        <input name="alt_text" type="text" maxlength="190" value="{{ (string) ($media->alt_text ?? 'Room photo') }}" aria-label="Edit room photo text">
                                                                                                                        <button class="btn btn-secondary" type="submit">Save</button>
                                                                                                                    </form>
                                                                                                                    <div class="gallery-card-actions">
                                                                                                                        @if ((bool) ($media->is_primary ?? false))
                                                                                                                            <span class="ops-chip">Primary</span>
                                                                                                                        @else
                                                                                                                            <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary">
                                                                                                                                @csrf
                                                                                                                                <input type="hidden" name="panel_entity_type" value="room">
                                                                                                                                <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                                                <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                                                            </form>
                                                                                                                        @endif
                                                                                                                        <form class="gallery-delete-form" method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/delete" onsubmit="return confirm('Remove this photo?');">
                                                                                                                            @csrf
                                                                                                                            <input type="hidden" name="panel_entity_type" value="room">
                                                                                                                            <input type="hidden" name="panel_entity_id" value="{{ $roomId }}">
                                                                                                                            <button class="btn btn-danger" type="submit">Remove</button>
                                                                                                                        </form>
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
                                                @endif
                                                @if ($categoryKey === 'accommodation')
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
                                                                        <label>Accommodation Listing</label>
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
                                                                        <label>Room Base Price (MVR)</label>
                                                                        <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('base_price', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Price (MVR)</label>
                                                                        <input class="ops-input" name="extra_person_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('extra_person_price', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Price (MVR)</label>
                                                                        <input class="ops-input" name="child_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('child_price', '') : '' }}">
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
                                                                </div>
                                                                <div class="inline-actions" style="margin-top:10px;">
                                                                    <button class="btn btn-primary" type="submit">Save Room</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-inline-room-row="{{ $propertyId }}">Cancel</button>
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

        

