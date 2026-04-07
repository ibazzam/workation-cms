<section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview" data-panel-group="listings">
            <div class="ops-header">
                <p class="ops-title">My Listings Console</p>
                <span class="ops-chip">Database-backed</span>
            </div>
            @if (!$vendorCanManageListings)
                <p class="wizard-note" style="margin-bottom:10px;">Listings, operations, and pricing are currently locked. Complete My Account compliance details and wait for admin verification approval.</p>
            @endif
            <div class="inline-actions" style="margin-bottom:10px;">
                <a class="btn btn-secondary" href="#vendorAvailabilitySection">Manage Reservations</a>
                <a class="btn btn-secondary" href="#vendorPricingSection">Change Pricing</a>
                <a class="btn btn-secondary" href="#vendorDailyCollectionSection">Billing &amp; Refunds</a>
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
                @endphp
                <article class="ops-form ops-field-wide">
                    <form id="propertyCreateForm" class="ops-form" method="POST" action="/portal/vendor/properties/create" @if (!$showCreatePropertyForm) hidden @endif>
                        @csrf
                        <input type="hidden" name="property_form_intent" value="1">
                        <p class="guided-wizard-title" id="propertyCreateFormTitle">Accommodation Enlisting</p>
                        <p class="guided-wizard-subtitle" id="propertyCreateFormSubtitle">Fill required fields and save.</p>
                        <div class="ops-form-grid">
                            @php
                                $defaultCreateCategory = old('listing_category');
                                if (!is_string($defaultCreateCategory) || trim($defaultCreateCategory) === '') {
                                    $defaultCreateCategory = in_array($forcedListingCategory, ['marine_transport', 'land_transport'], true)
                                        ? 'transport'
                                        : ($forcedListingCategory !== '' ? $forcedListingCategory : null);
                                }
                                if (!is_string($defaultCreateCategory) || trim($defaultCreateCategory) === '') {
                                    $defaultCreateCategory = in_array('accommodation', $selectedVendorCategories, true)
                                        ? 'accommodation'
                                        : ((string) ($selectedVendorCategories[0] ?? 'accommodation'));
                                }
                            @endphp
                            <div class="ops-field" style="display:none;">
                                <select id="property_listing_category" name="listing_category" class="ops-select" data-default-category="{{ $defaultCreateCategory }}">
                                    @foreach ($selectedVendorCategories as $categoryKey)
                                        @php
                                            $categoryLabel = $listingCategoryLabelMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', (string) $categoryKey));
                                        @endphp
                                        <option value="{{ $categoryKey }}" @selected($defaultCreateCategory === $categoryKey)>{{ $categoryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_name">Name</label>
                                <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_country">Country</label>
                                <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', 'Maldives') }}" required>
                                    <option value="Maldives" @selected(old('location_country', 'Maldives') === 'Maldives')>Maldives</option>
                                    <option value="Sri Lanka" @selected(old('location_country') === 'Sri Lanka')>Sri Lanka</option>
                                    <option value="India" @selected(old('location_country') === 'India')>India</option>
                                    <option value="Other" @selected(old('location_country') === 'Other')>Other</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_state">State / Province / Atoll</label>
                                <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                                    <option value="">Select state/province</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_city">City / Island</label>
                                <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                                    <option value="">Select city/island</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_ward">Ward</label>
                                <input id="location_ward" name="location_ward" class="ops-input" type="text" maxlength="120" value="{{ old('location_ward') }}" placeholder="Ward / Neighborhood">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_building_house_lot">Building / House / Lot No.</label>
                                <input id="property_building_house_lot" name="building_house_lot" class="ops-input" type="text" maxlength="160" value="{{ old('building_house_lot') }}" placeholder="e.g. Lily House, Lot 1142">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_street_name">Street</label>
                                <input id="property_street_name" name="street" class="ops-input" type="text" maxlength="160" value="{{ old('street') }}" placeholder="Street / Road">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_post_code">Post Code</label>
                                <input id="property_post_code" name="post_code" class="ops-input" type="text" maxlength="20" value="{{ old('post_code') }}" placeholder="Post code">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_name">Contact Name</label>
                                <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name') }}" placeholder="Contact Name">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_number">Contact Number</label>
                                <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number') }}" placeholder="Phone / WhatsApp">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_email">Property Contact Email</label>
                                <input id="property_contact_email" name="property_contact_email" class="ops-input" type="email" maxlength="190" value="{{ old('property_contact_email') }}" placeholder="property@example.com">
                            </div>
                            <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
                            <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
                            <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_base_price">Base Price (MVR)</label>
                                <input id="property_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('base_price') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_max_guests">Max Guests</label>
                                <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests') }}">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_description">Description</label>
                                <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000">{{ old('description') }}</textarea>
                            </div>

                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_area_value">Area Value (sqft)</label>
                                <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" max="100000" step="0.01" value="{{ old('area_value') }}" placeholder="e.g. 120">
                            </div>
                            <input name="area_unit" type="hidden" value="sqft">
                            <input name="measurement_system" type="hidden" value="imperial">
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_bedroom_count">Bedrooms</label>
                                <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_capacity_value">Capacity</label>
                                <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}" placeholder="seats, guests, units">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <p class="category-scope-note" style="margin:0;">Remote workspace booking fee is charged per guest in-app. Max Guests is the booking limit per reservation, and Capacity is total workspace seats/desks available. Extra items/services are shown for customer information and purchased separately on-site.</p>
                            </div>
                            <div class="ops-field" data-category-scope="service">
                                <label for="property_service_radius_km">Service Radius (km)</label>
                                <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" max="5000" step="0.1" value="{{ old('service_radius_km') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_mode">Transport Mode</label>
                                @php
                                    $transportModeOld = strtolower(trim((string) old('transport_mode', '')));
                                    $knownTransportModes = $transportModeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_transport_mode" name="transport_mode" class="ops-select">
                                    <option value="" @selected($transportModeOld === '')>Select transport mode</option>
                                    @if ($transportModeOld !== '' && !in_array($transportModeOld, $knownTransportModes, true))
                                        <option value="{{ $transportModeOld }}" selected>{{ ucfirst($transportModeOld) }} (existing)</option>
                                    @endif
                                    @foreach ($transportModeOptionGroups as $groupKey => $groupItems)
                                        @php
                                            $groupLabel = $groupKey === 'marine'
                                                ? 'Vessel / Marine'
                                                : ($groupKey === 'land' ? 'Vehicle / Land' : ucfirst(str_replace('_', ' ', (string) $groupKey)));
                                        @endphp
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($groupItems as $groupItem)
                                                @php
                                                    $groupValue = strtolower(trim((string) ($groupItem['value'] ?? '')));
                                                    $groupText = trim((string) ($groupItem['label'] ?? $groupValue));
                                                @endphp
                                                @if ($groupValue !== '' && $groupText !== '')
                                                    <option value="{{ $groupValue }}" @selected($transportModeOld === $groupValue)>{{ $groupText }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_vehicle_name">Vehicle / Vessel Name</label>
                                <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name') }}" placeholder="Sea Rider 01, Airport Van 3">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_registration_plate">Registration Plate No.</label>
                                <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="80" value="{{ old('registration_plate') }}" placeholder="AB-1234 / Vessel Reg ID">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_contact_name">Contact Name</label>
                                <input id="property_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name') }}" placeholder="Dispatcher / Driver / Captain">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_contact_number">Contact Number</label>
                                <input id="property_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number') }}" placeholder="+960 7xxxxxx">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_trip_type">Trip Type</label>
                                <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                                    <option value="" @selected(old('transport_trip_type') === null)>Select</option>
                                    <option value="one_way" @selected(old('transport_trip_type') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                    <option value="round_trip" @selected(old('transport_trip_type') === 'round_trip')>Round Trip</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_pickup_location">Pickup Location</label>
                                <input id="property_pickup_location" name="pickup_location" class="ops-input" type="text" maxlength="190" value="{{ old('pickup_location') }}" placeholder="Airport, Jetty, Hotel">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_dropoff_location">Dropoff Location</label>
                                <input id="property_dropoff_location" name="dropoff_location" class="ops-input" type="text" maxlength="190" value="{{ old('dropoff_location') }}" placeholder="Resort, Island, City center">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_state">Departure State / Atoll</label>
                                <input id="property_transport_departure_state" name="transport_departure_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_state') }}" placeholder="e.g. Kaafu Atoll">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_city">Departure City / Island</label>
                                <input id="property_transport_departure_city" name="transport_departure_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_city') }}" placeholder="e.g. Male">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_state">Arrival State / Atoll</label>
                                <input id="property_transport_arrival_state" name="transport_arrival_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_state') }}" placeholder="e.g. Alif Dhaal Atoll">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_city">Arrival City / Island</label>
                                <input id="property_transport_arrival_city" name="transport_arrival_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_city') }}" placeholder="e.g. Dhigurah">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_area_port_jetty">Departure Area / Port / Jetty</label>
                                <input id="property_departure_area_port_jetty" name="departure_area_port_jetty" class="ops-input" type="text" maxlength="190" value="{{ old('departure_area_port_jetty') }}" placeholder="Jetty / Harbor / Terminal">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_date">Departure Date</label>
                                <input id="property_departure_date" name="departure_date" class="ops-input" type="date" value="{{ old('departure_date') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_time">Departure Time</label>
                                <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_reporting_lead_minutes">Report Before Departure (minutes)</label>
                                <input id="property_reporting_lead_minutes" name="reporting_lead_minutes" class="ops-input" type="number" min="0" max="720" step="1" value="{{ old('reporting_lead_minutes') }}" placeholder="e.g. 15 or 20">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_trip_duration_minutes">Trip Duration Estimate (minutes)</label>
                                <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="5" max="1440" value="{{ old('trip_duration_minutes') }}" placeholder="e.g. 90">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_schedule_start_time">Operating Schedule Starts</label>
                                <input id="property_schedule_start_time" name="schedule_start_time" class="ops-input" type="time" value="{{ old('schedule_start_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_schedule_end_time">Operating Schedule Ends</label>
                                <input id="property_schedule_end_time" name="schedule_end_time" class="ops-input" type="time" value="{{ old('schedule_end_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_booking_cutoff_minutes">Booking Cutoff (minutes before departure)</label>
                                <input id="property_booking_cutoff_minutes" name="booking_cutoff_minutes" class="ops-input" type="number" min="0" max="10080" value="{{ old('booking_cutoff_minutes', 120) }}" placeholder="e.g. 120">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <label for="property_boarding_instructions">Boarding Instructions</label>
                                <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="2" maxlength="1000" placeholder="Where to wait, ID requirements, baggage check, boarding gate/jetty info...">{{ old('boarding_instructions') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_transport_pricing_model">Land Pricing Model</label>
                                <select id="property_transport_pricing_model" name="transport_pricing_model" class="ops-select">
                                    <option value="per_trip" @selected(old('transport_pricing_model', 'per_trip') === 'per_trip')>Per Trip</option>
                                    <option value="hourly" @selected(old('transport_pricing_model') === 'hourly')>Hourly Hire</option>
                                    <option value="daily" @selected(old('transport_pricing_model') === 'daily')>Daily Hire</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_hourly_rate">Hourly Rate (MVR)</label>
                                <input id="property_hourly_rate" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('hourly_rate') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_daily_rate">Daily Rate (MVR)</label>
                                <input id="property_daily_rate" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('daily_rate') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p id="transportPricingHint" class="category-scope-note" style="margin:0;">Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.</p>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p class="category-scope-note" style="margin:0;">Use entry only to enlist transport basics. Manage fixed daily schedules and seat availability in <a href="#vendorAvailabilitySection">Availability Calendar</a>, and manage price fluctuations in <a href="#vendorPricingSection">Pricing Rules</a>.</p>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_seat_class">Seat Class</label>
                                <select id="property_seat_class" name="seat_class" class="ops-select">
                                    <option value="" @selected(old('seat_class') === '')>Standard / Not Applicable</option>
                                    <option value="economy" @selected(old('seat_class') === 'economy')>Economy</option>
                                    <option value="business" @selected(old('seat_class') === 'business')>Business</option>
                                    <option value="first" @selected(old('seat_class') === 'first')>First Class</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_luggage_allowance_kg">Luggage Allowance (kg)</label>
                                <input id="property_luggage_allowance_kg" name="luggage_allowance_kg" class="ops-input" type="number" min="0" max="500" value="{{ old('luggage_allowance_kg') }}" placeholder="0 = no specific limit">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_duration_minutes">Duration (minutes)</label>
                                <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="30" max="1440" value="{{ old('excursion_duration_minutes') }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_difficulty">Difficulty</label>
                                <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                                    <option value="" @selected(old('excursion_difficulty') === null)>Select</option>
                                    <option value="easy" @selected(old('excursion_difficulty') === 'easy')>Easy</option>
                                    <option value="moderate" @selected(old('excursion_difficulty') === 'moderate')>Moderate</option>
                                    <option value="hard" @selected(old('excursion_difficulty') === 'hard')>Hard</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_type">Excursion Type</label>
                                @php
                                    $excursionTypeOld = strtolower(trim((string) old('excursion_type', '')));
                                    $knownExcursionTypes = $excursionTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_excursion_type" name="excursion_type" class="ops-select">
                                    <option value="" @selected($excursionTypeOld === '')>Select</option>
                                    @if ($excursionTypeOld !== '' && !in_array($excursionTypeOld, $knownExcursionTypes, true))
                                        <option value="{{ $excursionTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $excursionTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($excursionTypeOptionsCollection as $excursionTypeOption)
                                        @php
                                            $excursionTypeValue = strtolower(trim((string) ($excursionTypeOption['value'] ?? '')));
                                            $excursionTypeLabel = trim((string) ($excursionTypeOption['label'] ?? $excursionTypeValue));
                                        @endphp
                                        @if ($excursionTypeValue !== '' && $excursionTypeLabel !== '')
                                            <option value="{{ $excursionTypeValue }}" @selected($excursionTypeOld === $excursionTypeValue)>{{ $excursionTypeLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_min_pax">Min. Participants</label>
                                <input id="property_excursion_min_pax" name="excursion_min_pax" class="ops-input" type="number" min="1" max="1000" value="{{ old('excursion_min_pax', 1) }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_max_pax">Max. Participants</label>
                                <input id="property_excursion_max_pax" name="excursion_max_pax" class="ops-input" type="number" min="1" max="1000" value="{{ old('excursion_max_pax') }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_min_age">Minimum Age</label>
                                <input id="property_excursion_min_age" name="excursion_min_age" class="ops-input" type="number" min="0" max="99" value="{{ old('excursion_min_age') }}" placeholder="e.g. 12 (leave blank if none)">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_meeting_point">Meeting Point</label>
                                <input id="property_meeting_point" name="meeting_point" class="ops-input" type="text" maxlength="255" value="{{ old('meeting_point') }}" placeholder="Hotel lobby, Jetty No. 3, Beach entrance…">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_inclusions">What's Included</label>
                                <textarea id="property_inclusions" name="inclusions" class="ops-textarea" rows="3" maxlength="2000" placeholder="Snorkelling gear, lunch, certified guide, hotel transfer…">{{ old('inclusions') }}</textarea>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_exclusions">Not Included</label>
                                <textarea id="property_exclusions" name="exclusions" class="ops-textarea" rows="2" maxlength="1000" placeholder="Flights, visa fees, personal expenses, tips…">{{ old('exclusions') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_safety_waiver_required">Safety Waiver Required</label>
                                <select id="property_safety_waiver_required" name="safety_waiver_required" class="ops-select">
                                    <option value="" @selected(old('safety_waiver_required') === '')>Select</option>
                                    <option value="yes" @selected(old('safety_waiver_required') === 'yes')>Yes</option>
                                    <option value="no" @selected(old('safety_waiver_required') === 'no')>No</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_equipment_rental_available">Equipment Rental Available</label>
                                <select id="property_equipment_rental_available" name="equipment_rental_available" class="ops-select">
                                    <option value="" @selected(old('equipment_rental_available') === '')>Select</option>
                                    <option value="yes" @selected(old('equipment_rental_available') === 'yes')>Yes</option>
                                    <option value="no" @selected(old('equipment_rental_available') === 'no')>No</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label>Equipment Included</label>
                                <div class="feature-checklist">
                                    @foreach (['snorkel_gear' => 'Snorkel Gear', 'life_jacket' => 'Life Jacket', 'fins' => 'Fins', 'wetsuit' => 'Wetsuit', 'helmet' => 'Helmet', 'gopro_mount' => 'GoPro Mount'] as $equipmentKey => $equipmentLabel)
                                        <label class="feature-item"><input type="checkbox" name="equipment_included[]" value="{{ $equipmentKey }}" @checked(in_array($equipmentKey, old('equipment_included', []), true))> {{ $equipmentLabel }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_weather_cancellation_policy">Weather Cancellation Policy</label>
                                <textarea id="property_weather_cancellation_policy" name="weather_cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="Trips may be rescheduled or refunded in case of unsafe sea/weather conditions...">{{ old('weather_cancellation_policy') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_workspace_type">Workspace Type</label>
                                <select id="property_workspace_type" name="workspace_type" class="ops-select">
                                    <option value="" @selected(old('workspace_type') === null)>Select</option>
                                    <option value="shared" @selected(old('workspace_type') === 'shared')>Shared</option>
                                    <option value="private" @selected(old('workspace_type') === 'private')>Private</option>
                                    <option value="cabin" @selected(old('workspace_type') === 'cabin')>Cabin</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_internet_speed_mbps">Internet Speed (Mbps)</label>
                                <input id="property_internet_speed_mbps" name="internet_speed_mbps" class="ops-input" type="number" min="1" max="10000" step="1" value="{{ old('internet_speed_mbps') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <label>Workspace Amenities (information only)</label>
                                <p class="small" style="margin-bottom:8px;">Free Amenities (tick all)</p>
                                <div class="feature-checklist" style="margin-bottom:10px;">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'free')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small" style="margin-bottom:8px;">Paid Amenities (tick all)</p>
                                <div class="feature-checklist">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'paid')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small">Set each item as Free, Purchase Separately On-Site, or Not Available. The app only collects the booking fee; extras are purchased separately.</p>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_operating_hours_open">Opens At</label>
                                <input id="property_operating_hours_open" name="operating_hours_open" class="ops-input" type="time" value="{{ old('operating_hours_open', '08:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_operating_hours_close">Closes At</label>
                                <input id="property_operating_hours_close" name="operating_hours_close" class="ops-input" type="time" value="{{ old('operating_hours_close', '22:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_min_booking_hours">Min. Booking (hours)</label>
                                <input id="property_min_booking_hours" name="min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('min_booking_hours', 1) }}" placeholder="e.g. 2">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="stay">
                                <label>Transfer Options and Charges (Per Pax)</label>
                                <p class="small" style="margin-bottom:8px;">Configure local and foreign transfer rates separately. If only one rate is set, it is treated as foreign adult default.</p>
                                <div class="ops-form-grid">
                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                        @php
                                            $transferRateValue = '';
                                            if (is_array($oldTransferRatesInput) && array_key_exists($transferOptionKey, $oldTransferRatesInput)) {
                                                $transferRateValue = (string) $oldTransferRatesInput[$transferOptionKey];
                                            }
                                            $transferRateLocalAdult = old('transfer_rates_local_adult.' . $transferOptionKey, '');
                                            $transferRateLocalChild = old('transfer_rates_local_child.' . $transferOptionKey, '');
                                            $transferRateForeignAdult = old('transfer_rates_foreign_adult.' . $transferOptionKey, $transferRateValue);
                                            $transferRateForeignChild = old('transfer_rates_foreign_child.' . $transferOptionKey, '');
                                        @endphp
                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $oldTransferOptions, true))>
                                            <span>{{ $transferOptionLabel }}</span>
                                        </label>
                                        <input name="transfer_rates[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateValue }}" placeholder="Legacy per pax rate (MVR)">
                                        <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateLocalAdult }}" placeholder="Local adult rate (MVR)">
                                        <input name="transfer_rates_local_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateLocalChild }}" placeholder="Local child rate (MVR)">
                                        <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateForeignAdult }}" placeholder="Foreign adult rate (MVR)">
                                        <input name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateForeignChild }}" placeholder="Foreign child rate (MVR)">
                                    @endforeach
                                    <input name="transfer_base_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_base_local', '0') }}" placeholder="Transfer base local (MVR)">
                                    <input name="transfer_base_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_base_foreign', '0') }}" placeholder="Transfer base foreign (MVR)">
                                </div>
                                @if ($vendorTaxComponents->isNotEmpty())
                                    <p class="small" style="margin:8px 0;">Vendor tax rate overrides (admin-moderated tax types)</p>
                                    <div class="ops-form-grid">
                                        @foreach ($vendorTaxComponents as $taxComponent)
                                            @php
                                                $taxCode = strtolower(trim((string) ($taxComponent['code'] ?? '')));
                                                $taxLabel = trim((string) ($taxComponent['label'] ?? $taxCode));
                                                $taxDefaultRate = (float) ($taxComponent['default_rate'] ?? 0);
                                            @endphp
                                            @if ($taxCode !== '')
                                                <input name="vendor_tax_rates[{{ $taxCode }}]" class="ops-input" type="number" min="0" step="0.0001" value="{{ old('vendor_tax_rates.' . $taxCode, (string) $taxDefaultRate) }}" placeholder="{{ $taxLabel }}">
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_start_time">Day Visit Start Time</label>
                                <input id="property_day_visit_start_time" name="day_visit_start_time" class="ops-input" type="time" value="{{ old('day_visit_start_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_end_time">Day Visit End Time</label>
                                <input id="property_day_visit_end_time" name="day_visit_end_time" class="ops-input" type="time" value="{{ old('day_visit_end_time') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="day_visit">
                                <label for="property_included_access">Included Access</label>
                                <textarea id="property_included_access" name="included_access" class="ops-textarea" maxlength="2000" placeholder="Pool access, lunch, transfer, spa credits, etc.">{{ old('included_access') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_price_per_adult">Price Per Adult (MVR)</label>
                                <input id="property_price_per_adult" name="price_per_adult" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_adult') }}" placeholder="Adult day pass rate">
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_price_per_child">Price Per Child (MVR)</label>
                                <input id="property_price_per_child" name="price_per_child" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_child') }}" placeholder="Child day pass rate (leave blank if same as adult)">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_cuisine_type">Cuisine Type</label>
                                <input id="property_cuisine_type" name="cuisine_type" class="ops-input" type="text" maxlength="120" value="{{ old('cuisine_type') }}" placeholder="Maldivian, Asian Fusion, Seafood">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_meal_service">Meal Service</label>
                                @php
                                    $mealServiceOld = strtolower(trim((string) old('meal_service', '')));
                                    $knownMealServices = $restaurantMealServiceOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_meal_service" name="meal_service" class="ops-select">
                                    <option value="" @selected($mealServiceOld === '')>Select</option>
                                    @if ($mealServiceOld !== '' && !in_array($mealServiceOld, $knownMealServices, true))
                                        <option value="{{ $mealServiceOld }}" selected>{{ ucfirst(str_replace('_', ' ', $mealServiceOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($restaurantMealServiceOptionsCollection as $mealServiceOption)
                                        @php
                                            $mealServiceValue = strtolower(trim((string) ($mealServiceOption['value'] ?? '')));
                                            $mealServiceLabel = trim((string) ($mealServiceOption['label'] ?? $mealServiceValue));
                                        @endphp
                                        @if ($mealServiceValue !== '' && $mealServiceLabel !== '')
                                            <option value="{{ $mealServiceValue }}" @selected($mealServiceOld === $mealServiceValue)>{{ $mealServiceLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_seating_capacity">Seating Capacity</label>
                                <input id="property_seating_capacity" name="seating_capacity" class="ops-input" type="number" min="1" max="10000" value="{{ old('seating_capacity') }}" placeholder="Total covers / seats">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_restaurant_open_time">Opens At</label>
                                <input id="property_restaurant_open_time" name="restaurant_open_time" class="ops-input" type="time" value="{{ old('restaurant_open_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_restaurant_close_time">Closes At</label>
                                <input id="property_restaurant_close_time" name="restaurant_close_time" class="ops-input" type="time" value="{{ old('restaurant_close_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_booking_required">Advance Booking</label>
                                <select id="property_booking_required" name="booking_required" class="ops-select">
                                    <option value="" @selected(old('booking_required') === '')>Select</option>
                                    <option value="required" @selected(old('booking_required') === 'required')>Reservation Required</option>
                                    <option value="recommended" @selected(old('booking_required') === 'recommended')>Recommended</option>
                                    <option value="walk_in" @selected(old('booking_required') === 'walk_in')>Walk-ins Welcome</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_dress_code">Dress Code</label>
                                <select id="property_dress_code" name="dress_code" class="ops-select">
                                    <option value="" @selected(old('dress_code') === '')>None / Casual</option>
                                    <option value="casual" @selected(old('dress_code') === 'casual')>Casual</option>
                                    <option value="smart_casual" @selected(old('dress_code') === 'smart_casual')>Smart Casual</option>
                                    <option value="formal" @selected(old('dress_code') === 'formal')>Formal / Black Tie</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_price_range">Price Range</label>
                                <select id="property_price_range" name="price_range" class="ops-select">
                                    <option value="" @selected(old('price_range') === '')>Select</option>
                                    <option value="budget" @selected(old('price_range') === 'budget')>Budget (under MVR 150/pp)</option>
                                    <option value="mid_range" @selected(old('price_range') === 'mid_range')>Mid-Range (MVR 150–400/pp)</option>
                                    <option value="upscale" @selected(old('price_range') === 'upscale')>Upscale (MVR 400–800/pp)</option>
                                    <option value="fine_dining" @selected(old('price_range') === 'fine_dining')>Fine Dining (MVR 800+/pp)</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_minimum_age">Minimum Age</label>
                                <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="0" max="120" value="{{ old('minimum_age') }}">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_vehicle_type">Vehicle Type</label>
                                @php
                                    $vehicleTypeOld = strtolower(trim((string) old('vehicle_type', '')));
                                    $knownVehicleTypes = $vehicleRentalTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_vehicle_type" name="vehicle_type" class="ops-select">
                                    <option value="" @selected($vehicleTypeOld === '')>Select Vehicle Type</option>
                                    @if ($vehicleTypeOld !== '' && !in_array($vehicleTypeOld, $knownVehicleTypes, true))
                                        <option value="{{ $vehicleTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $vehicleTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($vehicleRentalTypeOptionGroups as $vehicleGroupKey => $vehicleGroupItems)
                                        @php
                                            $vehicleGroupLabel = $vehicleGroupKey === 'land'
                                                ? 'Land Vehicles'
                                                : ($vehicleGroupKey === 'marine' ? 'Marine Vessels' : ucfirst(str_replace('_', ' ', (string) $vehicleGroupKey)));
                                        @endphp
                                        <optgroup label="{{ $vehicleGroupLabel }}">
                                            @foreach ($vehicleGroupItems as $vehicleTypeOption)
                                                @php
                                                    $vehicleTypeValue = strtolower(trim((string) ($vehicleTypeOption['value'] ?? '')));
                                                    $vehicleTypeLabel = trim((string) ($vehicleTypeOption['label'] ?? $vehicleTypeValue));
                                                @endphp
                                                @if ($vehicleTypeValue !== '' && $vehicleTypeLabel !== '')
                                                    <option value="{{ $vehicleTypeValue }}" @selected($vehicleTypeOld === $vehicleTypeValue)>{{ $vehicleTypeLabel }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_transmission_type">Transmission</label>
                                <select id="property_transmission_type" name="transmission_type" class="ops-select">
                                    <option value="" @selected(old('transmission_type') === null)>Select</option>
                                    <option value="automatic" @selected(old('transmission_type') === 'automatic')>Automatic</option>
                                    <option value="manual" @selected(old('transmission_type') === 'manual')>Manual</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_fuel_type">Fuel Type</label>
                                <select id="property_fuel_type" name="fuel_type" class="ops-select">
                                    <option value="" @selected(old('fuel_type') === null)>Select</option>
                                    <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                                    <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                                    <option value="electric" @selected(old('fuel_type') === 'electric')>Electric</option>
                                    <option value="hybrid" @selected(old('fuel_type') === 'hybrid')>Hybrid</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_deposit_amount">Security Deposit (MVR)</label>
                                <input id="property_deposit_amount" name="deposit_amount" class="ops-input" type="number" min="0" step="0.01" value="{{ old('deposit_amount') }}" placeholder="Refundable deposit amount">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_license_class_required">License Required</label>
                                <input id="property_license_class_required" name="license_class_required" class="ops-input" type="text" maxlength="80" value="{{ old('license_class_required') }}" placeholder="e.g. B1, A1, International DL">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_daily_km_limit">Daily KM Limit</label>
                                <input id="property_daily_km_limit" name="daily_km_limit" class="ops-input" type="number" min="0" max="10000" value="{{ old('daily_km_limit') }}" placeholder="0 = unlimited">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_year_manufactured">Year</label>
                                <input id="property_year_manufactured" name="year_manufactured" class="ops-input" type="number" min="1980" max="{{ date('Y') + 1 }}" value="{{ old('year_manufactured') }}" placeholder="e.g. 2022">
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_rental_seating_count">Seats</label>
                                <input id="property_rental_seating_count" name="rental_seating_count" class="ops-input" type="number" min="1" max="200" value="{{ old('rental_seating_count') }}" placeholder="Number of seats">
                            </div>
                            {{-- Conference Room specific --}}
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_conference_room_type">Room Type</label>
                                <select id="property_conference_room_type" name="conference_room_type" class="ops-select">
                                    <option value="" @selected(old('conference_room_type') === '')>Select</option>
                                    <option value="boardroom" @selected(old('conference_room_type') === 'boardroom')>Boardroom</option>
                                    <option value="training" @selected(old('conference_room_type') === 'training')>Training Room</option>
                                    <option value="event_hall" @selected(old('conference_room_type') === 'event_hall')>Event Hall</option>
                                    <option value="banquet" @selected(old('conference_room_type') === 'banquet')>Banquet Hall</option>
                                    <option value="theater" @selected(old('conference_room_type') === 'theater')>Theater / Auditorium</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_conference_min_booking_hours">Min. Booking (hours)</label>
                                <input id="property_conference_min_booking_hours" name="conference_min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('conference_min_booking_hours', 2) }}" placeholder="e.g. 2">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="conference">
                                <label>AV Equipment Included</label>
                                <div class="feature-checklist">
                                    @foreach (['projector' => 'Projector', 'screen' => 'Projection Screen', 'whiteboard' => 'Whiteboard', 'microphone' => 'Microphone / PA System', 'video_conferencing' => 'Video Conferencing', 'flip_chart' => 'Flip Chart'] as $avKey => $avLabel)
                                        <label class="feature-item"><input type="checkbox" name="av_equipment[]" value="{{ $avKey }}" @checked(in_array($avKey, old('av_equipment', []), true))> {{ $avLabel }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_catering_available">Catering</label>
                                <select id="property_catering_available" name="catering_available" class="ops-select">
                                    <option value="" @selected(old('catering_available') === '')>Select</option>
                                    <option value="yes" @selected(old('catering_available') === 'yes')>In-House Catering Available</option>
                                    <option value="external" @selected(old('catering_available') === 'external')>External Catering Allowed</option>
                                    <option value="no" @selected(old('catering_available') === 'no')>No Catering</option>
                                </select>
                            </div>
                            {{-- Accommodation type, star rating, check-in/out, policies --}}
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_property_type">Property Type</label>
                                <select id="property_property_type" name="property_type" class="ops-select">
                                    <option value="" @selected(old('property_type') === '')>Select property type</option>
                                    <option value="hotel" @selected(old('property_type') === 'hotel')>Hotel</option>
                                    <option value="resort" @selected(old('property_type') === 'resort')>Resort</option>
                                    <option value="guest_house" @selected(old('property_type') === 'guest_house')>Guest House</option>
                                    <option value="villa" @selected(old('property_type') === 'villa')>Villa / Private House</option>
                                    <option value="apartment" @selected(old('property_type') === 'apartment')>Apartment</option>
                                    <option value="bungalow" @selected(old('property_type') === 'bungalow')>Bungalow</option>
                                    <option value="hostel" @selected(old('property_type') === 'hostel')>Hostel / Dormitory</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_star_rating">Star Rating</label>
                                <select id="property_star_rating" name="star_rating" class="ops-select">
                                    <option value="" @selected(old('star_rating') === '')>Unrated / Not Applicable</option>
                                    <option value="1" @selected(old('star_rating') == '1')>1 Star</option>
                                    <option value="2" @selected(old('star_rating') == '2')>2 Stars</option>
                                    <option value="3" @selected(old('star_rating') == '3')>3 Stars</option>
                                    <option value="4" @selected(old('star_rating') == '4')>4 Stars</option>
                                    <option value="5" @selected(old('star_rating') == '5')>5 Stars</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <p class="category-scope-note" style="margin:0;">Star guidance baseline: 1 star = essential stay only, 3 star = standard comfort/service, 5 star = premium full-service hospitality. Choose the closest real operating standard for this listing.</p>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_in_time">Check-in Time</label>
                                <input id="property_check_in_time" name="check_in_time" class="ops-input" type="time" value="{{ old('check_in_time', '14:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_out_time">Check-out Time</label>
                                <input id="property_check_out_time" name="check_out_time" class="ops-input" type="time" value="{{ old('check_out_time', '12:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_minimum_nights">Minimum Nights</label>
                                <input id="property_minimum_nights" name="minimum_nights" class="ops-input" type="number" min="1" max="365" value="{{ old('minimum_nights', 1) }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label for="property_house_rules">House Rules</label>
                                <textarea id="property_house_rules" name="house_rules" class="ops-textarea" rows="3" maxlength="2000" placeholder="No parties, no smoking indoors, quiet after 10pm…">{{ old('house_rules') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_meal_plan">Meal Plan</label>
                                <select id="property_meal_plan" name="meal_plan" class="ops-select">
                                    <option value="" @selected(old('meal_plan') === '')>Select meal plan</option>
                                    <option value="room_only" @selected(old('meal_plan') === 'room_only')>Room Only</option>
                                    <option value="bed_breakfast" @selected(old('meal_plan') === 'bed_breakfast')>Bed &amp; Breakfast</option>
                                    <option value="half_board" @selected(old('meal_plan') === 'half_board')>Half Board</option>
                                    <option value="full_board" @selected(old('meal_plan') === 'full_board')>Full Board</option>
                                    <option value="all_inclusive" @selected(old('meal_plan') === 'all_inclusive')>All Inclusive</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_in_grace_minutes">Check-in Grace (minutes)</label>
                                <input id="property_check_in_grace_minutes" name="check_in_grace_minutes" class="ops-input" type="number" min="0" max="720" value="{{ old('check_in_grace_minutes', 60) }}" placeholder="Late arrival tolerance window">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_early_check_in_allowed">Early Check-in</label>
                                <select id="property_early_check_in_allowed" name="early_check_in_allowed" class="ops-select">
                                    <option value="" @selected(old('early_check_in_allowed') === '')>Select</option>
                                    <option value="yes" @selected(old('early_check_in_allowed') === 'yes')>Allowed</option>
                                    <option value="subject_to_availability" @selected(old('early_check_in_allowed') === 'subject_to_availability')>Subject to Availability</option>
                                    <option value="no" @selected(old('early_check_in_allowed') === 'no')>Not Allowed</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_late_check_out_allowed">Late Check-out</label>
                                <select id="property_late_check_out_allowed" name="late_check_out_allowed" class="ops-select">
                                    <option value="" @selected(old('late_check_out_allowed') === '')>Select</option>
                                    <option value="yes" @selected(old('late_check_out_allowed') === 'yes')>Allowed</option>
                                    <option value="subject_to_availability" @selected(old('late_check_out_allowed') === 'subject_to_availability')>Subject to Availability</option>
                                    <option value="no" @selected(old('late_check_out_allowed') === 'no')>Not Allowed</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label for="property_child_policy">Child Policy</label>
                                <textarea id="property_child_policy" name="child_policy" class="ops-textarea" rows="3" maxlength="3000" placeholder="Children under 6 stay free with existing bedding; extra bed available at surcharge…">{{ old('child_policy') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_extra_guest_fee">Extra Guest Fee (MVR)</label>
                                <input id="property_extra_guest_fee" name="extra_guest_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('extra_guest_fee') }}" placeholder="Per extra guest per night">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_child_fee">Child Fee (MVR)</label>
                                <input id="property_child_fee" name="child_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_fee') }}" placeholder="Per child per night">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_early_check_in_fee">Early Check-in Fee (MVR)</label>
                                <input id="property_early_check_in_fee" name="early_check_in_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('early_check_in_fee') }}" placeholder="0 = complimentary">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_late_check_out_fee">Late Check-out Fee (MVR)</label>
                                <input id="property_late_check_out_fee" name="late_check_out_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('late_check_out_fee') }}" placeholder="0 = complimentary">
                            </div>
                            {{-- Shared cancellation policy (shown for all bookable categories) --}}
                            <div class="ops-field ops-field-wide" data-category-scope="policies">
                                <label for="property_cancellation_policy">Cancellation Policy</label>
                                <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="e.g. Free cancellation up to 48 hours before. 50% refund within 24 hours. No refund for no-shows.">{{ old('cancellation_policy') }}</textarea>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Amenities (tick all available)</label>
                                <p class="small" style="margin:0 0 8px;">Amenities are managed by admin. Vendors can only select from this list.</p>
                                <div class="feature-checklist">
                                    @forelse ($propertyAmenityOptionsCollection as $facilityOption)
                                        @php
                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                        @endphp
                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $oldPropertyAmenities, true))> {{ $facilityLabel }}</label>
                                        @endif
                                    @empty
                                        <p class="small" style="margin:0;">No amenities configured by admin yet.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Features (tick all available)</label>
                                <p class="small" style="margin:0 0 8px;">Features are managed by admin. Vendors can only select from this list.</p>
                                <div class="feature-checklist">
                                    @forelse ($propertyFeatureOptionsCollection as $featureOption)
                                        @php
                                            $featureValue = trim((string) ($featureOption['value'] ?? ''));
                                            $featureLabel = trim((string) ($featureOption['label'] ?? $featureValue));
                                        @endphp
                                        @if ($featureValue !== '' && $featureLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_features[]" value="{{ $featureValue }}" @checked(in_array($featureValue, $oldPropertyFeatures, true))> {{ $featureLabel }}</label>
                                        @endif
                                    @empty
                                        <p class="small" style="margin:0;">No property features configured by admin yet.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="geo">
                                <div class="map-picker">
                                    <div id="propertyMap" aria-label="Map picker"></div>
                                </div>
                                <p class="map-help">Click the map to drop a pin. Coordinates are captured automatically.</p>
                            </div>
                        </div>
                        <p class="standards-note">International listing standard: fields adapt to selected category. Create one property at a time, then add rooms under that property.</p>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="propertyCreateSubmitButton" type="submit">Save Listing</button>
                            <button class="btn btn-secondary" id="closePropertyCreateForm" type="button">Cancel</button>
                            <button class="btn btn-secondary" id="backToListingsFromCreate" type="button">Back To Listings</button>
                        </div>
                    </form>

                </article>
                <div class="category-listings-stack" aria-label="Category listing views">
                    @foreach ($listingCategoryViewOrder as $categoryKey)
                        @php
                            $categoryProperties = $propertiesByCategory->get($categoryKey, collect());
                            $categoryLabel = $listingCategoryLabelMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', $categoryKey));
                        @endphp
                        <article class="category-listing-section" id="category-view-{{ $categoryKey }}" data-category-view="{{ $categoryKey }}">
                            <div class="category-listing-header">
                                <h4>{{ $categoryLabel }} Listings</h4>
                                <div class="inline-actions">
                                    <span class="ops-chip">{{ $categoryProperties->count() }} listed</span>
                                    <button type="button" class="btn btn-secondary" data-listing-category-shortcut="{{ $categoryKey }}">Add {{ $categoryLabel }}</button>
                                </div>
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
                                                            <div class="inline-actions listing-actions-inline">
                                                                <button class="btn btn-secondary" type="button" data-open-property-edit data-property-edit-id="{{ $propertyId }}" data-property-edit-category="{{ $editCategory }}">Edit Listing</button>
                                                                @if ($categoryKey === 'accommodation')
                                                                    <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ $propertyId }}">Add Room</button>
                                                                @endif
                                                                <button class="btn btn-secondary" type="button" data-toggle-property-media="{{ $propertyId }}">Manage Media</button>
                                                                    @if ($listingModerationStatus === 'pending_review')
                                                                    <span class="ops-chip is-pending" style="padding:7px 10px;">Under Review</span>
                                                                @endif
                                                                <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/delete" onsubmit="return confirm('Remove this listing?');">
                                                                    @csrf
                                                                    <button class="btn btn-danger" type="submit">Remove Listing</button>
                                                                </form>
                                                            </div>
                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update" data-property-edit-form="{{ $propertyId }}" data-property-edit-category="{{ $editCategory }}" hidden>
                                                                @csrf
                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $property->name }}" required>
                                                                <input class="ops-input" name="location_country" type="text" maxlength="90" value="{{ (string) ($propertyDetails['location_country'] ?? '') }}" placeholder="Country" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_state'] ?? '') }}" placeholder="State / Province / Atoll" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_city'] ?? '') }}" placeholder="City / Island" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_ward" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_ward'] ?? '') }}" placeholder="Ward / Neighborhood" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="address_line" type="text" maxlength="255" value="{{ (string) ($propertyDetails['address_line'] ?? '') }}" placeholder="Address line" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="building_house_lot" type="text" maxlength="160" value="{{ (string) ($propertyDetails['building_house_lot'] ?? '') }}" placeholder="Building / House / Lot No." data-property-edit-scope="geo">
                                                                <input class="ops-input" name="street" type="text" maxlength="160" value="{{ (string) ($propertyDetails['street'] ?? '') }}" placeholder="Street" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="post_code" type="text" maxlength="20" value="{{ (string) ($propertyDetails['post_code'] ?? '') }}" placeholder="Post code" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['property_contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['property_contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_email" type="email" maxlength="190" value="{{ (string) ($propertyDetails['property_contact_email'] ?? '') }}" placeholder="Property Contact Email" data-property-edit-scope="geo">
                                                                <input name="map_latitude" type="hidden" value="{{ (string) ($propertyDetails['map_latitude'] ?? '') }}">
                                                                <input name="map_longitude" type="hidden" value="{{ (string) ($propertyDetails['map_longitude'] ?? '') }}">
                                                                <input name="map_place_id" type="hidden" value="{{ (string) ($propertyDetails['map_place_id'] ?? '') }}">
                                                                <textarea class="ops-textarea" name="description" maxlength="3000" placeholder="Description">{{ (string) ($property->description ?? '') }}</textarea>
                                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) ($property->base_price ?? 0) }}" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="max_guests" type="number" min="0" max="10000" value="{{ (int) ($property->max_guests ?? 0) }}" data-property-edit-scope="capacity">

                                                                <input class="ops-input" name="area_value" type="number" min="5" max="100000" step="0.01" value="{{ (string) ($propertyDetails['area_value'] ?? '') }}" placeholder="Area Value (sqft)" data-property-edit-scope="stay">
                                                                <input name="area_unit" type="hidden" value="sqft" data-property-edit-scope="stay">
                                                                <input name="measurement_system" type="hidden" value="imperial" data-property-edit-scope="stay">
                                                                <input class="ops-input" name="bedroom_count" type="number" min="0" max="1000" value="{{ (string) ($propertyDetails['bedroom_count'] ?? '') }}" placeholder="Bedrooms" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="capacity_value" type="number" min="1" max="20000" value="{{ (string) ($propertyDetails['capacity_value'] ?? '') }}" placeholder="Capacity" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="service_radius_km" type="number" min="0" max="5000" step="0.1" value="{{ (string) ($propertyDetails['service_radius_km'] ?? '') }}" placeholder="Service Radius (km)" data-property-edit-scope="service">
                                                                @php
                                                                    $transportModeEdit = strtolower(trim((string) ($propertyDetails['transport_mode'] ?? '')));
                                                                    $knownTransportModes = $transportModeOptionsCollection
                                                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                        ->filter(fn ($item) => $item !== '')
                                                                        ->values()
                                                                        ->all();
                                                                @endphp
                                                                <select class="ops-select" name="transport_mode" data-property-edit-scope="transport">
                                                                    <option value="" @selected($transportModeEdit === '')>Transport Mode</option>
                                                                    @if ($transportModeEdit !== '' && !in_array($transportModeEdit, $knownTransportModes, true))
                                                                        <option value="{{ $transportModeEdit }}" selected>{{ ucfirst($transportModeEdit) }} (existing)</option>
                                                                    @endif
                                                                    @foreach ($transportModeOptionGroups as $groupKey => $groupItems)
                                                                        @php
                                                                            $groupLabel = $groupKey === 'marine'
                                                                                ? 'Vessel / Marine'
                                                                                : ($groupKey === 'land' ? 'Vehicle / Land' : ucfirst(str_replace('_', ' ', (string) $groupKey)));
                                                                        @endphp
                                                                        <optgroup label="{{ $groupLabel }}">
                                                                            @foreach ($groupItems as $groupItem)
                                                                                @php
                                                                                    $groupValue = strtolower(trim((string) ($groupItem['value'] ?? '')));
                                                                                    $groupText = trim((string) ($groupItem['label'] ?? $groupValue));
                                                                                @endphp
                                                                                @if ($groupValue !== '' && $groupText !== '')
                                                                                    <option value="{{ $groupValue }}" @selected($transportModeEdit === $groupValue)>{{ $groupText }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                                <input class="ops-input" name="vehicle_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_name'] ?? '') }}" placeholder="Vehicle / Vessel Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="registration_plate" type="text" maxlength="80" value="{{ (string) ($propertyDetails['registration_plate'] ?? '') }}" placeholder="Registration Plate" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="transport">
                                                                <select class="ops-select" name="transport_trip_type" data-property-edit-scope="transport">
                                                                    <option value="" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === '')>Trip Type</option>
                                                                    <option value="one_way" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                                                    <option value="round_trip" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'round_trip')>Round Trip</option>
                                                                </select>
                                                                <select class="ops-select" name="transport_pricing_model" data-property-edit-scope="transport">
                                                                    <option value="per_trip" @selected((string) ($propertyDetails['transport_pricing_model'] ?? 'per_trip') === 'per_trip')>Per Trip</option>
                                                                    <option value="hourly" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'hourly')>Hourly Hire</option>
                                                                    <option value="daily" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'daily')>Daily Hire</option>
                                                                </select>
                                                                <input class="ops-input" name="hourly_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['hourly_rate'] ?? '') }}" placeholder="Hourly Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="daily_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['daily_rate'] ?? '') }}" placeholder="Daily Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="pickup_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['pickup_location'] ?? '') }}" placeholder="Pickup Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="dropoff_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['dropoff_location'] ?? '') }}" placeholder="Dropoff Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_state'] ?? '') }}" placeholder="Departure State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_city'] ?? '') }}" placeholder="Departure City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_state'] ?? '') }}" placeholder="Arrival State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_city'] ?? '') }}" placeholder="Arrival City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_area_port_jetty" type="text" maxlength="190" value="{{ (string) ($propertyDetails['departure_area_port_jetty'] ?? '') }}" placeholder="Departure Area / Port / Jetty" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_date" type="date" value="{{ (string) ($propertyDetails['departure_date'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_time" type="time" value="{{ (string) ($propertyDetails['departure_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="reporting_lead_minutes" type="number" min="0" max="720" step="1" value="{{ (string) ($propertyDetails['reporting_lead_minutes'] ?? '') }}" placeholder="Report Before Departure (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="trip_duration_minutes" type="number" min="5" max="1440" value="{{ (string) ($propertyDetails['trip_duration_minutes'] ?? '') }}" placeholder="Trip Duration (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="excursion_duration_minutes" type="number" min="30" max="1440" value="{{ (string) ($propertyDetails['excursion_duration_minutes'] ?? '') }}" placeholder="Excursion Duration (min)" data-property-edit-scope="excursion">
                                                                <select class="ops-select" name="excursion_difficulty" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === '')>Difficulty</option>
                                                                    <option value="easy" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'easy')>Easy</option>
                                                                    <option value="moderate" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'moderate')>Moderate</option>
                                                                    <option value="hard" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'hard')>Hard</option>
                                                                </select>
                                                                <select class="ops-select" name="workspace_type" data-property-edit-scope="workspace">
                                                                    <option value="" @selected((string) ($propertyDetails['workspace_type'] ?? '') === '')>Workspace Type</option>
                                                                    <option value="shared" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'shared')>Shared</option>
                                                                    <option value="private" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'private')>Private</option>
                                                                    <option value="cabin" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'cabin')>Cabin</option>
                                                                </select>
                                                                <input class="ops-input" name="internet_speed_mbps" type="number" min="1" max="10000" step="1" value="{{ (string) ($propertyDetails['internet_speed_mbps'] ?? '') }}" placeholder="Internet Speed (Mbps)" data-property-edit-scope="workspace">
                                                                <div class="ops-form-grid" data-property-edit-scope="workspace">
                                                                    <p class="small" style="margin:0;">Free Amenities (tick all)</p>
                                                                    <div class="feature-checklist" style="margin-bottom:8px;">
                                                                        @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                            @php
                                                                                $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                            @endphp
                                                                            <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityFreeValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                        @endforeach
                                                                    </div>
                                                                    <p class="small" style="margin:0;">Paid Amenities (tick all)</p>
                                                                    <div class="feature-checklist">
                                                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                        @php
                                                                            $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                        @endphp
                                                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityPaidValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                    @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="ops-form-grid" data-property-edit-scope="stay">
                                                                    <p class="small" style="margin:0;">Transfer Options and Charges (Per Pax)</p>
                                                                    @php
                                                                        $transferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null) ? $propertyDetails['transfer_rate_matrix'] : [];
                                                                    @endphp
                                                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                                                        @php
                                                                            $transferEditRate = '';
                                                                            if (array_key_exists($transferOptionKey, $transferRates)) {
                                                                                $transferEditRate = (string) $transferRates[$transferOptionKey];
                                                                            }
                                                                            $transferMatrixRow = is_array($transferRateMatrix[$transferOptionKey] ?? null) ? $transferRateMatrix[$transferOptionKey] : [];
                                                                        @endphp
                                                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $transferOptionValues, true))>
                                                                            <span>{{ $transferOptionLabel }}</span>
                                                                        </label>
                                                                        <input class="ops-input" name="transfer_rates[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ $transferEditRate }}" placeholder="Legacy per pax rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_local_adult[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['local_adult_charge'] ?? '') }}" placeholder="Local adult rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_local_child[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['local_child_charge'] ?? '') }}" placeholder="Local child rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['foreign_adult_charge'] ?? $transferEditRate) }}" placeholder="Foreign adult rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['foreign_child_charge'] ?? '') }}" placeholder="Foreign child rate (MVR)">
                                                                    @endforeach
                                                                    <input class="ops-input" name="transfer_base_local" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['transfer_base_local'] ?? 0) }}" placeholder="Transfer base local (MVR)">
                                                                    <input class="ops-input" name="transfer_base_foreign" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['transfer_base_foreign'] ?? 0) }}" placeholder="Transfer base foreign (MVR)">
                                                                    @php
                                                                        $vendorTaxRateOverrides = is_array($propertyDetails['vendor_tax_overrides'] ?? null) ? $propertyDetails['vendor_tax_overrides'] : [];
                                                                    @endphp
                                                                    @foreach ($vendorTaxComponents as $taxComponent)
                                                                        @php
                                                                            $taxCode = strtolower(trim((string) ($taxComponent['code'] ?? '')));
                                                                            $taxLabel = trim((string) ($taxComponent['label'] ?? $taxCode));
                                                                            $taxDefaultRate = (float) ($taxComponent['default_rate'] ?? 0);
                                                                            $taxCurrentRate = array_key_exists($taxCode, $vendorTaxRateOverrides)
                                                                                ? (float) $vendorTaxRateOverrides[$taxCode]
                                                                                : $taxDefaultRate;
                                                                        @endphp
                                                                        @if ($taxCode !== '')
                                                                            <input class="ops-input" name="vendor_tax_rates[{{ $taxCode }}]" type="number" min="0" step="0.0001" value="{{ (string) $taxCurrentRate }}" placeholder="{{ $taxLabel }}">
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <input class="ops-input" name="day_visit_start_time" type="time" value="{{ (string) ($propertyDetails['day_visit_start_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="day_visit_end_time" type="time" value="{{ (string) ($propertyDetails['day_visit_end_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="included_access" type="text" maxlength="2000" value="{{ (string) ($propertyDetails['included_access'] ?? '') }}" placeholder="Included Access" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="cuisine_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['cuisine_type'] ?? '') }}" placeholder="Cuisine Type" data-property-edit-scope="restaurant">
                                                                <select class="ops-select" name="meal_service" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['meal_service'] ?? '') === '')>Meal Service</option>
                                                                    <option value="breakfast" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'breakfast')>Breakfast</option>
                                                                    <option value="lunch" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'lunch')>Lunch</option>
                                                                    <option value="dinner" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'dinner')>Dinner</option>
                                                                    <option value="all_day" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'all_day')>All Day</option>
                                                                </select>
                                                                <input class="ops-input" name="minimum_age" type="number" min="0" max="120" value="{{ (string) ($propertyDetails['minimum_age'] ?? '') }}" placeholder="Minimum Age" data-property-edit-scope="vehicle">
                                                                <input class="ops-input" name="vehicle_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_type'] ?? '') }}" placeholder="Vehicle Type" data-property-edit-scope="rental">
                                                                <select class="ops-select" name="transmission_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['transmission_type'] ?? '') === '')>Transmission</option>
                                                                    <option value="automatic" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'automatic')>Automatic</option>
                                                                    <option value="manual" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'manual')>Manual</option>
                                                                </select>
                                                                <select class="ops-select" name="fuel_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['fuel_type'] ?? '') === '')>Fuel Type</option>
                                                                    <option value="petrol" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'petrol')>Petrol</option>
                                                                    <option value="diesel" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'diesel')>Diesel</option>
                                                                    <option value="electric" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'electric')>Electric</option>
                                                                    <option value="hybrid" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'hybrid')>Hybrid</option>
                                                                </select>

                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    @foreach ($accommodationFacilityOptionsCollection as $facilityOption)
                                                                        @php
                                                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                                                        @endphp
                                                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $propertyAmenityValues, true))> {{ $facilityLabel }}</label>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="wheelchair_access" @checked(in_array('wheelchair_access', $propertyFeatureValues, true))> Wheelchair Access</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="elevator" @checked(in_array('elevator', $propertyFeatureValues, true))> Elevator</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="family_friendly" @checked(in_array('family_friendly', $propertyFeatureValues, true))> Family Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="pet_friendly" @checked(in_array('pet_friendly', $propertyFeatureValues, true))> Pet Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="beachfront" @checked(in_array('beachfront', $propertyFeatureValues, true))> Beachfront</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="sea_view" @checked(in_array('sea_view', $propertyFeatureValues, true))> Sea View</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="safety_certified" @checked(in_array('safety_certified', $propertyFeatureValues, true))> Safety Certified</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="kids_play_area" @checked(in_array('kids_play_area', $propertyFeatureValues, true))> Kids Play Area</label>
                                                                </div>

                                                                {{-- Transport extras --}}
                                                                <select class="ops-select" name="seat_class" data-property-edit-scope="transport">
                                                                    <option value="" @selected((string) ($propertyDetails['seat_class'] ?? '') === '')>Seat Class (Standard)</option>
                                                                    <option value="economy" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'economy')>Economy</option>
                                                                    <option value="business" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'business')>Business</option>
                                                                    <option value="first" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'first')>First Class</option>
                                                                </select>
                                                                <input class="ops-input" name="luggage_allowance_kg" type="number" min="0" max="500" value="{{ (string) ($propertyDetails['luggage_allowance_kg'] ?? '') }}" placeholder="Luggage Allowance (kg)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="schedule_start_time" type="time" value="{{ (string) ($propertyDetails['schedule_start_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="schedule_end_time" type="time" value="{{ (string) ($propertyDetails['schedule_end_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="booking_cutoff_minutes" type="number" min="0" max="10080" value="{{ (string) ($propertyDetails['booking_cutoff_minutes'] ?? '') }}" placeholder="Booking Cutoff (minutes)" data-property-edit-scope="transport">
                                                                <textarea class="ops-textarea" name="boarding_instructions" rows="2" maxlength="1000" placeholder="Boarding Instructions" data-property-edit-scope="transport">{{ (string) ($propertyDetails['boarding_instructions'] ?? '') }}</textarea>
                                                                {{-- Excursion extras --}}
                                                                <input class="ops-input" name="excursion_min_pax" type="number" min="1" max="1000" value="{{ (string) ($propertyDetails['excursion_min_pax'] ?? '') }}" placeholder="Min. Participants" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="excursion_max_pax" type="number" min="1" max="1000" value="{{ (string) ($propertyDetails['excursion_max_pax'] ?? '') }}" placeholder="Max. Participants" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="excursion_min_age" type="number" min="0" max="99" value="{{ (string) ($propertyDetails['excursion_min_age'] ?? '') }}" placeholder="Minimum Age" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="meeting_point" type="text" maxlength="255" value="{{ (string) ($propertyDetails['meeting_point'] ?? '') }}" placeholder="Meeting Point" data-property-edit-scope="excursion">
                                                                <textarea class="ops-textarea" name="inclusions" rows="3" maxlength="2000" placeholder="What's Included" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['inclusions'] ?? '') }}</textarea>
                                                                <textarea class="ops-textarea" name="exclusions" rows="2" maxlength="1000" placeholder="Not Included" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['exclusions'] ?? '') }}</textarea>
                                                                <select class="ops-select" name="safety_waiver_required" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === '')>Safety Waiver Required</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === 'yes')>Yes</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === 'no')>No</option>
                                                                </select>
                                                                <select class="ops-select" name="equipment_rental_available" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === '')>Equipment Rental Available</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === 'yes')>Yes</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === 'no')>No</option>
                                                                </select>
                                                                @php
                                                                    $equipmentIncludedValues = is_array($propertyDetails['equipment_included'] ?? null)
                                                                        ? array_map(static fn ($item): string => strtolower(trim((string) $item)), $propertyDetails['equipment_included'])
                                                                        : [];
                                                                @endphp
                                                                <div class="feature-checklist" data-property-edit-scope="excursion">
                                                                    @foreach (['snorkel_gear' => 'Snorkel Gear', 'life_jacket' => 'Life Jacket', 'fins' => 'Fins', 'wetsuit' => 'Wetsuit', 'helmet' => 'Helmet', 'gopro_mount' => 'GoPro Mount'] as $equipmentKey => $equipmentLabel)
                                                                        <label class="feature-item"><input type="checkbox" name="equipment_included[]" value="{{ $equipmentKey }}" @checked(in_array($equipmentKey, $equipmentIncludedValues, true))> {{ $equipmentLabel }}</label>
                                                                    @endforeach
                                                                </div>
                                                                <textarea class="ops-textarea" name="weather_cancellation_policy" rows="3" maxlength="2000" placeholder="Weather Cancellation Policy" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['weather_cancellation_policy'] ?? '') }}</textarea>
                                                                {{-- Workspace operating hours --}}
                                                                <input class="ops-input" name="operating_hours_open" type="time" value="{{ (string) ($propertyDetails['operating_hours_open'] ?? '08:00') }}" data-property-edit-scope="workspace">
                                                                <input class="ops-input" name="operating_hours_close" type="time" value="{{ (string) ($propertyDetails['operating_hours_close'] ?? '22:00') }}" data-property-edit-scope="workspace">
                                                                <input class="ops-input" name="min_booking_hours" type="number" min="1" max="24" value="{{ (string) ($propertyDetails['min_booking_hours'] ?? '') }}" placeholder="Min. Booking (hours)" data-property-edit-scope="workspace">
                                                                {{-- Day visit pricing --}}
                                                                <input class="ops-input" name="price_per_adult" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['price_per_adult'] ?? '') }}" placeholder="Price Per Adult (MVR)" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="price_per_child" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['price_per_child'] ?? '') }}" placeholder="Price Per Child (MVR)" data-property-edit-scope="day_visit">
                                                                {{-- Restaurant extras --}}
                                                                <input class="ops-input" name="seating_capacity" type="number" min="1" max="10000" value="{{ (string) ($propertyDetails['seating_capacity'] ?? '') }}" placeholder="Seating Capacity" data-property-edit-scope="restaurant">
                                                                <input class="ops-input" name="restaurant_open_time" type="time" value="{{ (string) ($propertyDetails['restaurant_open_time'] ?? '') }}" data-property-edit-scope="restaurant">
                                                                <input class="ops-input" name="restaurant_close_time" type="time" value="{{ (string) ($propertyDetails['restaurant_close_time'] ?? '') }}" data-property-edit-scope="restaurant">
                                                                <select class="ops-select" name="booking_required" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['booking_required'] ?? '') === '')>Advance Booking</option>
                                                                    <option value="required" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'required')>Reservation Required</option>
                                                                    <option value="recommended" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'recommended')>Recommended</option>
                                                                    <option value="walk_in" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'walk_in')>Walk-ins Welcome</option>
                                                                </select>
                                                                <select class="ops-select" name="dress_code" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['dress_code'] ?? '') === '')>Dress Code</option>
                                                                    <option value="casual" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'casual')>Casual</option>
                                                                    <option value="smart_casual" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'smart_casual')>Smart Casual</option>
                                                                    <option value="formal" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'formal')>Formal / Black Tie</option>
                                                                </select>
                                                                <select class="ops-select" name="price_range" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['price_range'] ?? '') === '')>Price Range</option>
                                                                    <option value="budget" @selected((string) ($propertyDetails['price_range'] ?? '') === 'budget')>Budget</option>
                                                                    <option value="mid_range" @selected((string) ($propertyDetails['price_range'] ?? '') === 'mid_range')>Mid-Range</option>
                                                                    <option value="upscale" @selected((string) ($propertyDetails['price_range'] ?? '') === 'upscale')>Upscale</option>
                                                                    <option value="fine_dining" @selected((string) ($propertyDetails['price_range'] ?? '') === 'fine_dining')>Fine Dining</option>
                                                                </select>
                                                                {{-- Vehicle rental extras --}}
                                                                <input class="ops-input" name="deposit_amount" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['deposit_amount'] ?? '') }}" placeholder="Security Deposit (MVR)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="license_class_required" type="text" maxlength="80" value="{{ (string) ($propertyDetails['license_class_required'] ?? '') }}" placeholder="License Required (e.g. B1)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="daily_km_limit" type="number" min="0" max="10000" value="{{ (string) ($propertyDetails['daily_km_limit'] ?? '') }}" placeholder="Daily KM Limit (0=unlimited)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="year_manufactured" type="number" min="1980" max="{{ date('Y') + 1 }}" value="{{ (string) ($propertyDetails['year_manufactured'] ?? '') }}" placeholder="Year" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="rental_seating_count" type="number" min="1" max="200" value="{{ (string) ($propertyDetails['rental_seating_count'] ?? '') }}" placeholder="Seats" data-property-edit-scope="vehicle">
                                                                {{-- Conference room extras --}}
                                                                <select class="ops-select" name="conference_room_type" data-property-edit-scope="conference">
                                                                    <option value="" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === '')>Room Type</option>
                                                                    <option value="boardroom" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'boardroom')>Boardroom</option>
                                                                    <option value="training" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'training')>Training Room</option>
                                                                    <option value="event_hall" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'event_hall')>Event Hall</option>
                                                                    <option value="banquet" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'banquet')>Banquet Hall</option>
                                                                    <option value="theater" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'theater')>Theater / Auditorium</option>
                                                                </select>
                                                                <input class="ops-input" name="conference_min_booking_hours" type="number" min="1" max="24" value="{{ (string) ($propertyDetails['conference_min_booking_hours'] ?? '') }}" placeholder="Min. Booking (hours)" data-property-edit-scope="conference">
                                                                <select class="ops-select" name="catering_available" data-property-edit-scope="conference">
                                                                    <option value="" @selected((string) ($propertyDetails['catering_available'] ?? '') === '')>Catering</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'yes')>In-House Catering</option>
                                                                    <option value="external" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'external')>External Catering Allowed</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'no')>No Catering</option>
                                                                </select>
                                                                {{-- Accommodation: property type, star rating, check-in/out, minimum nights, house rules --}}
                                                                <select class="ops-select" name="property_type" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['property_type'] ?? '') === '')>Property Type</option>
                                                                    <option value="hotel" @selected((string) ($propertyDetails['property_type'] ?? '') === 'hotel')>Hotel</option>
                                                                    <option value="resort" @selected((string) ($propertyDetails['property_type'] ?? '') === 'resort')>Resort</option>
                                                                    <option value="guest_house" @selected((string) ($propertyDetails['property_type'] ?? '') === 'guest_house')>Guest House</option>
                                                                    <option value="villa" @selected((string) ($propertyDetails['property_type'] ?? '') === 'villa')>Villa / Private House</option>
                                                                    <option value="apartment" @selected((string) ($propertyDetails['property_type'] ?? '') === 'apartment')>Apartment</option>
                                                                    <option value="bungalow" @selected((string) ($propertyDetails['property_type'] ?? '') === 'bungalow')>Bungalow</option>
                                                                    <option value="hostel" @selected((string) ($propertyDetails['property_type'] ?? '') === 'hostel')>Hostel / Dormitory</option>
                                                                </select>
                                                                <select class="ops-select" name="star_rating" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['star_rating'] ?? '') === '')>Star Rating (Unrated)</option>
                                                                    <option value="1" @selected((string) ($propertyDetails['star_rating'] ?? '') === '1')>1 Star</option>
                                                                    <option value="2" @selected((string) ($propertyDetails['star_rating'] ?? '') === '2')>2 Stars</option>
                                                                    <option value="3" @selected((string) ($propertyDetails['star_rating'] ?? '') === '3')>3 Stars</option>
                                                                    <option value="4" @selected((string) ($propertyDetails['star_rating'] ?? '') === '4')>4 Stars</option>
                                                                    <option value="5" @selected((string) ($propertyDetails['star_rating'] ?? '') === '5')>5 Stars</option>
                                                                </select>
                                                                <input class="ops-input" name="check_in_time" type="time" value="{{ (string) ($propertyDetails['check_in_time'] ?? '14:00') }}" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="check_out_time" type="time" value="{{ (string) ($propertyDetails['check_out_time'] ?? '12:00') }}" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="minimum_nights" type="number" min="1" max="365" value="{{ (string) ($propertyDetails['minimum_nights'] ?? 1) }}" placeholder="Minimum Nights" data-property-edit-scope="accommodation">
                                                                <textarea class="ops-textarea" name="house_rules" rows="3" maxlength="2000" placeholder="House Rules" data-property-edit-scope="accommodation">{{ (string) ($propertyDetails['house_rules'] ?? '') }}</textarea>
                                                                <select class="ops-select" name="meal_plan" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['meal_plan'] ?? '') === '')>Meal Plan</option>
                                                                    <option value="room_only" @selected((string) ($propertyDetails['meal_plan'] ?? '') === 'room_only')>Room Only</option>
                                                                    <option value="bed_breakfast" @selected((string) ($propertyDetails['meal_plan'] ?? '') === 'bed_breakfast')>Bed &amp; Breakfast</option>
                                                                    <option value="half_board" @selected((string) ($propertyDetails['meal_plan'] ?? '') === 'half_board')>Half Board</option>
                                                                    <option value="full_board" @selected((string) ($propertyDetails['meal_plan'] ?? '') === 'full_board')>Full Board</option>
                                                                    <option value="all_inclusive" @selected((string) ($propertyDetails['meal_plan'] ?? '') === 'all_inclusive')>All Inclusive</option>
                                                                </select>
                                                                <input class="ops-input" name="check_in_grace_minutes" type="number" min="0" max="720" value="{{ (string) ($propertyDetails['check_in_grace_minutes'] ?? 60) }}" placeholder="Check-in Grace (minutes)" data-property-edit-scope="accommodation">
                                                                <select class="ops-select" name="early_check_in_allowed" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === '')>Early Check-in</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'yes')>Allowed</option>
                                                                    <option value="subject_to_availability" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'subject_to_availability')>Subject to Availability</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'no')>Not Allowed</option>
                                                                </select>
                                                                <select class="ops-select" name="late_check_out_allowed" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === '')>Late Check-out</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'yes')>Allowed</option>
                                                                    <option value="subject_to_availability" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'subject_to_availability')>Subject to Availability</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'no')>Not Allowed</option>
                                                                </select>
                                                                <textarea class="ops-textarea" name="child_policy" rows="3" maxlength="3000" placeholder="Child Policy" data-property-edit-scope="accommodation">{{ (string) ($propertyDetails['child_policy'] ?? '') }}</textarea>
                                                                <input class="ops-input" name="extra_guest_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['extra_guest_fee'] ?? '') }}" placeholder="Extra Guest Fee (MVR)" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="child_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['child_fee'] ?? '') }}" placeholder="Child Fee (MVR)" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="early_check_in_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['early_check_in_fee'] ?? '') }}" placeholder="Early Check-in Fee (MVR)" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="late_check_out_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['late_check_out_fee'] ?? '') }}" placeholder="Late Check-out Fee (MVR)" data-property-edit-scope="accommodation">
                                                                {{-- Shared: cancellation policy --}}
                                                                <textarea class="ops-textarea" name="cancellation_policy" rows="3" maxlength="2000" placeholder="Cancellation Policy" data-property-edit-scope="policies">{{ (string) ($propertyDetails['cancellation_policy'] ?? '') }}</textarea>
                                                                <select class="ops-select" name="status" required>
                                                                    <option value="active" @selected((string) ($property->status ?? '') === 'active')>Active</option>
                                                                    <option value="inactive" @selected((string) ($property->status ?? '') === 'inactive')>Inactive</option>
                                                                </select>
                                                                <div class="inline-actions">
                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Listing</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-property-edit data-property-edit-id="{{ $propertyId }}">Cancel Edit</button>
                                                                </div>
                                                            </form>
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
                                                                                            <div class="inline-actions listing-actions-inline">
                                                                                                <button class="btn btn-secondary" type="button" data-open-room-edit data-room-edit-id="{{ $roomId }}">Edit Room</button>
                                                                                                <button class="btn btn-secondary" type="button" data-toggle-room-media="{{ $roomId }}">Manage Media</button>
                                                                                                <form method="POST" action="/portal/vendor/rooms/{{ $roomId }}/delete" onsubmit="return confirm('Remove this room category?');">
                                                                                                    @csrf
                                                                                                    <button class="btn btn-danger" type="submit">Remove Room</button>
                                                                                                </form>
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

        
