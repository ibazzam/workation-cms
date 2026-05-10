{{-- Standalone edit form: Accommodation --}}
@php
    $accommodationListingCategory = (string) ($listingCategoryOverride ?? 'accommodation');
    $accommodationCategoryLabel = (string) ($categoryLabelOverride ?? ucwords(str_replace('_', ' ', $accommodationListingCategory)));
    $accommodationCancelHref = (string) ($cancelHrefOverride ?? ('/vendor/listings/' . $accommodationListingCategory));
@endphp
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="{{ $accommodationListingCategory }}">
    <input type="hidden" name="property_form_intent" value="1">
    <input name="area_unit" type="hidden" value="sqft">
    <input name="measurement_system" type="hidden" value="imperial">

    @php
        $savedTransferOptions = is_array($propertyDetails['transfer_options'] ?? null) ? $propertyDetails['transfer_options'] : [];
        $savedTransferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null) ? $propertyDetails['transfer_rate_matrix'] : [];
        $savedTransferRates = is_array($propertyDetails['transfer_rates'] ?? null) ? $propertyDetails['transfer_rates'] : [];
        $savedTransferRatesLocalAdult = is_array($propertyDetails['transfer_rates_local_adult'] ?? null) ? $propertyDetails['transfer_rates_local_adult'] : [];
        $savedTransferRatesLocalChild = is_array($propertyDetails['transfer_rates_local_child'] ?? null) ? $propertyDetails['transfer_rates_local_child'] : [];
        $savedTransferRatesForeignAdult = is_array($propertyDetails['transfer_rates_foreign_adult'] ?? null) ? $propertyDetails['transfer_rates_foreign_adult'] : [];
        $savedTransferRatesForeignChild = is_array($propertyDetails['transfer_rates_foreign_child'] ?? null) ? $propertyDetails['transfer_rates_foreign_child'] : [];

        foreach ($savedTransferRateMatrix as $transferKey => $transferRow) {
            if (!is_array($transferRow)) {
                continue;
            }
            $transferKey = trim((string) $transferKey);
            if ($transferKey === '') {
                continue;
            }

            if (!array_key_exists($transferKey, $savedTransferRatesLocalAdult) && isset($transferRow['local_adult_charge']) && is_numeric($transferRow['local_adult_charge'])) {
                $savedTransferRatesLocalAdult[$transferKey] = (float) $transferRow['local_adult_charge'];
            }
            if (!array_key_exists($transferKey, $savedTransferRatesLocalChild) && isset($transferRow['local_child_charge']) && is_numeric($transferRow['local_child_charge'])) {
                $savedTransferRatesLocalChild[$transferKey] = (float) $transferRow['local_child_charge'];
            }
            if (!array_key_exists($transferKey, $savedTransferRatesForeignAdult) && isset($transferRow['foreign_adult_charge']) && is_numeric($transferRow['foreign_adult_charge'])) {
                $savedTransferRatesForeignAdult[$transferKey] = (float) $transferRow['foreign_adult_charge'];
            }
            if (!array_key_exists($transferKey, $savedTransferRatesForeignChild) && isset($transferRow['foreign_child_charge']) && is_numeric($transferRow['foreign_child_charge'])) {
                $savedTransferRatesForeignChild[$transferKey] = (float) $transferRow['foreign_child_charge'];
            }
        }

        foreach ($savedTransferRates as $transferKey => $legacyRate) {
            $transferKey = trim((string) $transferKey);
            if ($transferKey === '' || !is_numeric($legacyRate)) {
                continue;
            }
            if (!array_key_exists($transferKey, $savedTransferRatesForeignAdult)) {
                $savedTransferRatesForeignAdult[$transferKey] = (float) $legacyRate;
            }
        }

        if ($savedTransferOptions === []) {
            $savedTransferOptions = array_values(array_unique(array_filter(array_merge(
                array_keys($savedTransferRatesLocalAdult),
                array_keys($savedTransferRatesLocalChild),
                array_keys($savedTransferRatesForeignAdult),
                array_keys($savedTransferRatesForeignChild)
            ), static fn ($value) => is_string($value) && trim($value) !== '')));
        }

        $savedPropertyAmenities = is_array($propertyDetails['property_amenities'] ?? null) ? $propertyDetails['property_amenities'] : [];
        $savedPropertyFeatures = is_array($propertyDetails['property_features'] ?? null) ? $propertyDetails['property_features'] : [];
        $savedLiveaboardStopoversText = '';
        if (is_array($propertyDetails['stopovers'] ?? null)) {
            $savedLiveaboardStopoversText = implode("\n", array_map(static function ($stop): string {
                if (!is_array($stop)) {
                    return trim((string) $stop);
                }
                $name = trim((string) ($stop['name'] ?? ''));
                if ($name === '') {
                    return '';
                }
                $dayStage = trim((string) ($stop['day_stage'] ?? ''));
                $embark = !empty($stop['allow_embark']) ? 'yes' : 'no';
                $disembark = !empty($stop['allow_disembark']) ? 'yes' : 'no';
                $notes = trim((string) ($stop['notes'] ?? ''));
                if ($dayStage !== '' || $notes !== '') {
                    return $dayStage . '|' . $name . '|' . $embark . '|' . $disembark . '|' . $notes;
                }
                return $name . '|' . $embark . '|' . $disembark;
            }, $propertyDetails['stopovers']));
            $savedLiveaboardStopoversText = trim((string) $savedLiveaboardStopoversText);
        }
        $savedLiveaboardPricingMatrixText = '';
        if (is_array($propertyDetails['pricing_matrix'] ?? null)) {
            $savedLiveaboardPricingMatrixText = implode("\n", array_map(
                static fn ($routeKey, $price): string => trim((string) $routeKey) . '=' . (is_numeric($price) ? (string) $price : trim((string) $price)),
                array_keys($propertyDetails['pricing_matrix']),
                array_values($propertyDetails['pricing_matrix'])
            ));
            $savedLiveaboardPricingMatrixText = trim((string) $savedLiveaboardPricingMatrixText);
        }
    @endphp

    <div class="listing-form-stack">
        <section class="listing-form-section" aria-label="Property information">
            <div class="listing-form-section-head">
                <h4>Property Information</h4>
                <p>Keep the core accommodation details, address, contact information, and map pin together so the listing reads top to bottom without guesswork.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field ops-field-wide">
                    <label for="property_name">Property Name</label>
                    <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name', $property->name ?? '') }}" required>
                </div>
                <div class="ops-field">
                    <label for="property_property_type">Property Type</label>
                    <select id="property_property_type" name="property_type" class="ops-select">
                        <option value="">Select property type</option>
                        <option value="liveaboard" @selected(old('property_type', $propertyDetails['property_type'] ?? '') === 'liveaboard')>Liveaboard Vessel</option>
                        @foreach (['hotel' => 'Hotel', 'resort' => 'Resort', 'guest_house' => 'Guest House', 'villa' => 'Villa / Private House', 'apartment' => 'Apartment', 'bungalow' => 'Bungalow', 'hostel' => 'Hostel / Dormitory'] as $ptVal => $ptLabel)
                            <option value="{{ $ptVal }}" @selected(old('property_type', $propertyDetails['property_type'] ?? '') === $ptVal)>{{ $ptLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_star_rating">Star Rating</label>
                    <select id="property_star_rating" name="star_rating" class="ops-select">
                        <option value="">Unrated / Not Applicable</option>
                        @foreach ([1, 2, 3, 4, 5] as $stars)
                            <option value="{{ $stars }}" @selected((string) old('star_rating', $propertyDetails['star_rating'] ?? '') === (string) $stars)>{{ $stars }} Star{{ $stars > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_bedroom_count">Bedrooms</label>
                    <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count', $propertyDetails['bedroom_count'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_area_value">Area Value (sqft)</label>
                    <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" max="100000" step="0.01" value="{{ old('area_value', $propertyDetails['area_value'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_max_guests">Max Guests</label>
                    <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_capacity_value">{{ $accommodationListingCategory === 'liveaboard' ? 'Capacity (total cabins)' : 'Capacity (total units/rooms)' }}</label>
                    <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value', $propertyDetails['capacity_value'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="location_country">Country</label>
                    <select id="location_country" name="location_country" class="ops-select" data-edit-country data-selected-value="{{ old('location_country', $propertyDetails['location_country'] ?? 'Maldives') }}" required>
                        <option value="Maldives">Maldives</option>
                        <option value="Sri Lanka">Sri Lanka</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="location_state">Atoll / Province</label>
                    <select id="location_state" name="location_state" class="ops-select" data-edit-state data-selected-value="{{ old('location_state', $propertyDetails['location_state'] ?? '') }}" required>
                        <option value="">Select atoll</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="location_city">Island / City</label>
                    <select id="location_city" name="location_city" class="ops-select" data-edit-city data-selected-value="{{ old('location_city', $propertyDetails['location_city'] ?? '') }}" required>
                        <option value="">Select island</option>
                    </select>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_address_line">Address Line</label>
                    <input id="property_address_line" name="address_line" class="ops-input" type="text" maxlength="255" value="{{ old('address_line', $propertyDetails['address_line'] ?? '') }}" placeholder="Property address or front desk address">
                </div>
                <div class="ops-field">
                    <label for="property_building_house_lot">Building / House / Lot No.</label>
                    <input id="property_building_house_lot" name="building_house_lot" class="ops-input" type="text" maxlength="160" value="{{ old('building_house_lot', $propertyDetails['building_house_lot'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_street">Street</label>
                    <input id="property_street" name="street" class="ops-input" type="text" maxlength="160" value="{{ old('street', $propertyDetails['street'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_post_code">Post Code</label>
                    <input id="property_post_code" name="post_code" class="ops-input" type="text" maxlength="20" value="{{ old('post_code', $propertyDetails['post_code'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_name">Contact Name</label>
                    <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name', $propertyDetails['property_contact_name'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_number">Contact Number</label>
                    <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number', $propertyDetails['property_contact_number'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_email">Contact Email</label>
                    <input id="property_contact_email" name="property_contact_email" class="ops-input" type="email" maxlength="190" value="{{ old('property_contact_email', $propertyDetails['property_contact_email'] ?? '') }}">
                </div>
                <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
                <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
                <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
                <div class="ops-field ops-field-wide">
                    <label>Location Map Pin</label>
                    <div class="map-picker"><div data-edit-map-wrap aria-label="Edit pin location"></div></div>
                    <p class="map-help">Click to move the pin.</p>
                </div>
                @if ($accommodationListingCategory === 'liveaboard')
                    <div class="ops-field ops-field-wide">
                        <label style="font-weight:700; color:#1d4b66;">Journey Map Guidance</label>
                        <p class="map-help" style="margin-bottom:8px;">Keep one pin for the primary vessel/embark base. Then maintain embark point, stopovers, and disembark point below for route clarity.</p>
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_start_point">Embark Point</label>
                        <input id="liveaboard_start_point" name="start_point" class="ops-input" type="text" maxlength="120" value="{{ old('start_point', $propertyDetails['start_point'] ?? '') }}" placeholder="e.g. Male Jetty">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_end_point">End Point / Disembark</label>
                        <input id="liveaboard_end_point" name="end_point" class="ops-input" type="text" maxlength="120" value="{{ old('end_point', $propertyDetails['end_point'] ?? '') }}" placeholder="e.g. Gan Harbor">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_journey_days">Journey Duration (days)</label>
                        <input id="liveaboard_journey_days" name="journey_duration_days" class="ops-input" type="number" min="1" max="90" value="{{ old('journey_duration_days', $propertyDetails['journey_duration_days'] ?? '') }}" placeholder="e.g. 5">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_journey_start_date">Journey Start Date</label>
                        <input id="liveaboard_journey_start_date" name="journey_start_date" class="ops-input" type="date" value="{{ old('journey_start_date', $propertyDetails['journey_start_date'] ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_journey_end_date">Journey End Date</label>
                        <input id="liveaboard_journey_end_date" name="journey_end_date" class="ops-input" type="date" value="{{ old('journey_end_date', $propertyDetails['journey_end_date'] ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_auto_stop_sale_on_boarding">Auto Stop Sales At Boarding Date</label>
                        <select id="liveaboard_auto_stop_sale_on_boarding" name="auto_stop_sale_on_boarding" class="ops-select">
                            <option value="1" @selected((string) old('auto_stop_sale_on_boarding', (isset($propertyDetails['auto_stop_sale_on_boarding']) ? ($propertyDetails['auto_stop_sale_on_boarding'] ? '1' : '0') : '1')) === '1')>Yes (recommended)</option>
                            <option value="0" @selected((string) old('auto_stop_sale_on_boarding', (isset($propertyDetails['auto_stop_sale_on_boarding']) ? ($propertyDetails['auto_stop_sale_on_boarding'] ? '1' : '0') : '1')) === '0')>No (use manual stop-sale date)</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_journey_stop_sale_date">Stop Sale Date (optional)</label>
                        <input id="liveaboard_journey_stop_sale_date" name="journey_stop_sale_date" class="ops-input" type="date" value="{{ old('journey_stop_sale_date', $propertyDetails['journey_stop_sale_date'] ?? '') }}" required>
                        <p class="map-help">Mandatory. Set the final date guests are allowed to book this journey.</p>
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_vessel_name">Vessel / Boat Name</label>
                        <input id="liveaboard_vessel_name" name="vessel_name" class="ops-input" type="text" maxlength="120" value="{{ old('vessel_name', $propertyDetails['vessel_name'] ?? '') }}" placeholder="e.g. Ocean Explorer">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_registration_no">Registration / Hull No.</label>
                        <input id="liveaboard_registration_no" name="registration_no" class="ops-input" type="text" maxlength="60" value="{{ old('registration_no', $propertyDetails['registration_no'] ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="liveaboard_cabin_count">Cabin Count</label>
                        <input id="liveaboard_cabin_count" name="cabin_count" class="ops-input" type="number" min="1" max="500" value="{{ old('cabin_count', $propertyDetails['cabin_count'] ?? '') }}" placeholder="e.g. 8">
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="liveaboard_stopovers">Route Timeline (one stop per line)</label>
                        <textarea id="liveaboard_stopovers" name="stopovers" class="ops-textarea" rows="5" maxlength="5000" placeholder="Format: Day/Stage|Location|yes|yes|Notes&#10;Example: Day 3-4|Vaavu Atoll Thinadhoo|yes|no|Island hopping">{{ old('stopovers', $savedLiveaboardStopoversText) }}</textarea>
                        <p class="map-help">Use format: day/stage | location | allow embark | allow disembark | notes.</p>
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="liveaboard_journey_itinerary">Journey Summary (optional)</label>
                        <textarea id="liveaboard_journey_itinerary" name="journey_itinerary" class="ops-textarea" rows="4" maxlength="5000" placeholder="Day 1: Male embark and safety briefing...">{{ old('journey_itinerary', $propertyDetails['journey_itinerary'] ?? '') }}</textarea>
                    </div>
                @endif
            </div>
        </section>

        <section class="listing-form-section" aria-label="Description, facilities and amenities">
            <div class="listing-form-section-head">
                <h4>Description, Facilities and Amenities</h4>
                <p>Keep the guest-facing story and the facility checklist together so vendors can review what the property promises in one pass.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field ops-field-wide">
                    <label for="property_description">Description</label>
                    <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description', $property->description ?? '') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label>Property Amenities</label>
                    <div class="feature-checklist">
                        @forelse ($propertyAmenityOptionsCollection as $amenityOption)
                            @php $amenityValue = trim((string) ($amenityOption['value'] ?? '')); @endphp
                            @if ($amenityValue !== '')
                                <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $amenityValue }}" @checked(in_array($amenityValue, old('property_amenities', $savedPropertyAmenities), true))> {{ $amenityOption['label'] ?? $amenityValue }}</label>
                            @endif
                        @empty
                            <p class="small">No amenities configured yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="ops-field ops-field-wide">
                    <label>Property Features</label>
                    <div class="feature-checklist">
                        @forelse ($propertyFeatureOptionsCollection as $featureOption)
                            @php $featureValue = trim((string) ($featureOption['value'] ?? '')); @endphp
                            @if ($featureValue !== '')
                                <label class="feature-item"><input type="checkbox" name="property_features[]" value="{{ $featureValue }}" @checked(in_array($featureValue, old('property_features', $savedPropertyFeatures), true))> {{ $featureOption['label'] ?? $featureValue }}</label>
                            @endif
                        @empty
                            <p class="small">No features configured yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        @if ($accommodationListingCategory !== 'liveaboard')
        <section class="listing-form-section" aria-label="Transfer options and charges">
            <div class="listing-form-section-head">
                <h4>Transfer Options</h4>
                <p>Use a simple fare grid for each enabled transfer mode. Guests will see available options on the property page and the final transfer charge is selected during booking.</p>
            </div>
            <p class="listing-form-note">Only checked transfer modes are shown publicly. Leave a fare blank if it is still pending confirmation.</p>
            <div class="listing-transfer-table">
                <div class="listing-transfer-head" aria-hidden="true">
                    <span>Transfer Mode</span>
                    <span>Local Adult</span>
                    <span>Local Child</span>
                    <span>Foreigner Adult</span>
                    <span>Foreigner Child</span>
                </div>
                @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                    <div class="listing-transfer-row">
                        <div class="listing-transfer-option">
                            <label>
                                <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, old('transfer_options', $savedTransferOptions), true))>
                                <span>{{ $transferOptionLabel }}</span>
                            </label>
                            <small>Optional transfer mode for this {{ strtolower($accommodationCategoryLabel) }} listing.</small>
                        </div>
                        <label class="listing-transfer-rate">
                            <span>Local Adult</span>
                            <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_adult.' . $transferOptionKey, $savedTransferRatesLocalAdult[$transferOptionKey] ?? '') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Local Child</span>
                            <input name="transfer_rates_local_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_child.' . $transferOptionKey, $savedTransferRatesLocalChild[$transferOptionKey] ?? '') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Adult</span>
                            <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_adult.' . $transferOptionKey, $savedTransferRatesForeignAdult[$transferOptionKey] ?? '') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Child</span>
                            <input name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_child.' . $transferOptionKey, $savedTransferRatesForeignChild[$transferOptionKey] ?? '') }}" placeholder="MVR 0.00">
                        </label>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <section class="listing-form-section" aria-label="Policies and stay rules">
            <div class="listing-form-section-head">
                <h4>Policies</h4>
                <p>Review check-in rules, stay limits, and guest-facing policy text together before saving changes.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field">
                    <label for="property_check_in_time">Check-in Time</label>
                    <input id="property_check_in_time" name="check_in_time" class="ops-input" type="time" value="{{ old('check_in_time', $propertyDetails['check_in_time'] ?? '14:00') }}">
                </div>
                <div class="ops-field">
                    <label for="property_check_out_time">Check-out Time</label>
                    <input id="property_check_out_time" name="check_out_time" class="ops-input" type="time" value="{{ old('check_out_time', $propertyDetails['check_out_time'] ?? '12:00') }}">
                </div>
                <div class="ops-field">
                    <label for="property_minimum_nights">Minimum Nights</label>
                    <input id="property_minimum_nights" name="minimum_nights" class="ops-input" type="number" min="1" max="365" value="{{ old('minimum_nights', $propertyDetails['minimum_nights'] ?? 1) }}">
                </div>
                <div class="ops-field">
                    <label for="property_check_in_grace_minutes">Check-in Grace (minutes)</label>
                    <input id="property_check_in_grace_minutes" name="check_in_grace_minutes" class="ops-input" type="number" min="0" max="720" value="{{ old('check_in_grace_minutes', $propertyDetails['check_in_grace_minutes'] ?? 60) }}">
                </div>
                <div class="ops-field">
                    <label for="property_early_check_in_allowed">Early Check-in</label>
                    <select id="property_early_check_in_allowed" name="early_check_in_allowed" class="ops-select">
                        <option value="">Select</option>
                        @foreach (['yes' => 'Allowed', 'subject_to_availability' => 'Subject to Availability', 'no' => 'Not Allowed'] as $eciVal => $eciLabel)
                            <option value="{{ $eciVal }}" @selected(old('early_check_in_allowed', $propertyDetails['early_check_in_allowed'] ?? '') === $eciVal)>{{ $eciLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_late_check_out_allowed">Late Check-out</label>
                    <select id="property_late_check_out_allowed" name="late_check_out_allowed" class="ops-select">
                        <option value="">Select</option>
                        @foreach (['yes' => 'Allowed', 'subject_to_availability' => 'Subject to Availability', 'no' => 'Not Allowed'] as $lcoVal => $lcoLabel)
                            <option value="{{ $lcoVal }}" @selected(old('late_check_out_allowed', $propertyDetails['late_check_out_allowed'] ?? '') === $lcoVal)>{{ $lcoLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_early_check_in_fee">Early Check-in Fee (MVR)</label>
                    <input id="property_early_check_in_fee" name="early_check_in_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('early_check_in_fee', $propertyDetails['early_check_in_fee'] ?? '') }}">
                </div>
                <div class="ops-field">
                    <label for="property_late_check_out_fee">Late Check-out Fee (MVR)</label>
                    <input id="property_late_check_out_fee" name="late_check_out_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('late_check_out_fee', $propertyDetails['late_check_out_fee'] ?? '') }}">
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_house_rules">House Rules</label>
                    <textarea id="property_house_rules" name="house_rules" class="ops-textarea" rows="3" maxlength="2000">{{ old('house_rules', $propertyDetails['house_rules'] ?? '') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_child_policy">Child Policy</label>
                    <textarea id="property_child_policy" name="child_policy" class="ops-textarea" rows="3" maxlength="3000">{{ old('child_policy', $propertyDetails['child_policy'] ?? '') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_cancellation_policy">Cancellation Policy</label>
                    <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
                </div>
            </div>
        </section>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="{{ $accommodationCancelHref }}">Cancel</a>
    </div>
</form>
