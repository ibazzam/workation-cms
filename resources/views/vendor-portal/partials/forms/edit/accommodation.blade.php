{{-- Standalone edit form: Accommodation --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="accommodation">
    <input type="hidden" name="property_form_intent" value="1">
    <input name="area_unit" type="hidden" value="sqft">
    <input name="measurement_system" type="hidden" value="imperial">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Listing Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name', $property->name ?? '') }}" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description', $property->description ?? '') }}</textarea>
        </div>

        <div class="ops-field">
            <label for="property_property_type">Property Type</label>
            <select id="property_property_type" name="property_type" class="ops-select">
                <option value="">Select property type</option>
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
            <label for="property_capacity_value">Capacity (total units/rooms)</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value', $propertyDetails['capacity_value'] ?? '') }}">
        </div>
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

        {{-- Transfer options --}}
        <div class="ops-field ops-field-wide">
            <label>Transfer Options and Charges (Per Pax)</label>
            @php
                $savedTransferOptions = is_array($propertyDetails['transfer_options'] ?? null) ? $propertyDetails['transfer_options'] : [];
                $savedTransferRatesLocalAdult = is_array($propertyDetails['transfer_rates_local_adult'] ?? null) ? $propertyDetails['transfer_rates_local_adult'] : [];
                $savedTransferRatesLocalChild = is_array($propertyDetails['transfer_rates_local_child'] ?? null) ? $propertyDetails['transfer_rates_local_child'] : [];
                $savedTransferRatesForeignAdult = is_array($propertyDetails['transfer_rates_foreign_adult'] ?? null) ? $propertyDetails['transfer_rates_foreign_adult'] : [];
                $savedTransferRatesForeignChild = is_array($propertyDetails['transfer_rates_foreign_child'] ?? null) ? $propertyDetails['transfer_rates_foreign_child'] : [];
            @endphp
            <div class="ops-form-grid">
                @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                    <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}"
                            @checked(in_array($transferOptionKey, old('transfer_options', $savedTransferOptions), true))>
                        <span>{{ $transferOptionLabel }}</span>
                    </label>
                    <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_adult.' . $transferOptionKey, $savedTransferRatesLocalAdult[$transferOptionKey] ?? '') }}" placeholder="Local adult (MVR)">
                    <input name="transfer_rates_local_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_child.' . $transferOptionKey, $savedTransferRatesLocalChild[$transferOptionKey] ?? '') }}" placeholder="Local child (MVR)">
                    <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_adult.' . $transferOptionKey, $savedTransferRatesForeignAdult[$transferOptionKey] ?? '') }}" placeholder="Foreign adult (MVR)">
                    <input name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_child.' . $transferOptionKey, $savedTransferRatesForeignChild[$transferOptionKey] ?? '') }}" placeholder="Foreign child (MVR)">
                @endforeach
            </div>
        </div>

        {{-- Geo --}}
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
        <div class="ops-field">
            <label for="property_contact_name">Contact Name</label>
            <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name', $propertyDetails['property_contact_name'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_number">Contact Number</label>
            <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number', $propertyDetails['property_contact_number'] ?? '') }}">
        </div>
        <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
        <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
        <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit pin location"></div></div>
            <p class="map-help">Click to move the pin.</p>
        </div>

        {{-- Amenities / Features --}}
        @php
            $savedPropertyAmenities = is_array($propertyDetails['property_amenities'] ?? null) ? $propertyDetails['property_amenities'] : [];
            $savedPropertyFeatures = is_array($propertyDetails['property_features'] ?? null) ? $propertyDetails['property_features'] : [];
        @endphp
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

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="/vendor/listings/accommodation">Cancel</a>
    </div>
</form>
