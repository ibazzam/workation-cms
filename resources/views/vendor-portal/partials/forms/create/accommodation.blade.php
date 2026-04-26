{{-- Standalone create form: Accommodation --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="accommodation">
    <input type="hidden" name="property_form_intent" value="1">
    <input name="area_unit" type="hidden" value="sqft">
    <input name="measurement_system" type="hidden" value="imperial">
    <input id="property_base_price" name="base_price" type="hidden" value="0">

    <div class="listing-form-stack">
        <section class="listing-form-section" aria-label="Property information">
            <div class="listing-form-section-head">
                <h4>Property Information</h4>
                <p>Start with the core listing details guests and your operations team need to identify the property quickly.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field ops-field-wide">
                    <label for="property_name">Property Name</label>
                    <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                </div>
                <div class="ops-field">
                    <label for="property_property_type">Property Type</label>
                    <select id="property_property_type" name="property_type" class="ops-select">
                        <option value="">Select property type</option>
                        <option value="hotel" @selected(old('property_type') === 'hotel')>Hotel</option>
                        <option value="resort" @selected(old('property_type') === 'resort')>Resort</option>
                        <option value="guest_house" @selected(old('property_type') === 'guest_house')>Guest House</option>
                        <option value="villa" @selected(old('property_type') === 'villa')>Villa / Private House</option>
                        <option value="apartment" @selected(old('property_type') === 'apartment')>Apartment</option>
                        <option value="bungalow" @selected(old('property_type') === 'bungalow')>Bungalow</option>
                        <option value="hostel" @selected(old('property_type') === 'hostel')>Hostel / Dormitory</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_star_rating">Star Rating</label>
                    <select id="property_star_rating" name="star_rating" class="ops-select">
                        <option value="">Unrated / Not Applicable</option>
                        <option value="1" @selected(old('star_rating') == '1')>1 Star</option>
                        <option value="2" @selected(old('star_rating') == '2')>2 Stars</option>
                        <option value="3" @selected(old('star_rating') == '3')>3 Stars</option>
                        <option value="4" @selected(old('star_rating') == '4')>4 Stars</option>
                        <option value="5" @selected(old('star_rating') == '5')>5 Stars</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_bedroom_count">Bedrooms</label>
                    <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count') }}">
                </div>
                <div class="ops-field">
                    <label for="property_area_value">Area Value (sqft)</label>
                    <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" max="100000" step="0.01" value="{{ old('area_value') }}" placeholder="e.g. 120">
                </div>
                <div class="ops-field">
                    <label for="property_max_guests">Max Guests</label>
                    <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests') }}">
                </div>
                <div class="ops-field">
                    <label for="property_capacity_value">Capacity (total units/rooms)</label>
                    <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}" placeholder="Total bookable units">
                </div>
                <div class="ops-field">
                    <label for="location_country">Country</label>
                    <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', 'Maldives') }}" required>
                        <option value="Maldives" @selected(old('location_country', 'Maldives') === 'Maldives')>Maldives</option>
                        <option value="Sri Lanka" @selected(old('location_country') === 'Sri Lanka')>Sri Lanka</option>
                        <option value="India" @selected(old('location_country') === 'India')>India</option>
                        <option value="Other" @selected(old('location_country') === 'Other')>Other</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="location_state">Atoll / Province</label>
                    <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                        <option value="">Select atoll</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="location_city">Island / City</label>
                    <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                        <option value="">Select island</option>
                    </select>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_address_line">Address Line</label>
                    <input id="property_address_line" name="address_line" class="ops-input" type="text" maxlength="255" value="{{ old('address_line') }}" placeholder="Property address or front desk address">
                </div>
                <div class="ops-field">
                    <label for="property_building_house_lot">Building / House / Lot No.</label>
                    <input id="property_building_house_lot" name="building_house_lot" class="ops-input" type="text" maxlength="160" value="{{ old('building_house_lot') }}">
                </div>
                <div class="ops-field">
                    <label for="property_street">Street</label>
                    <input id="property_street" name="street" class="ops-input" type="text" maxlength="160" value="{{ old('street') }}">
                </div>
                <div class="ops-field">
                    <label for="property_post_code">Post Code</label>
                    <input id="property_post_code" name="post_code" class="ops-input" type="text" maxlength="20" value="{{ old('post_code') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_name">Contact Name</label>
                    <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_number">Contact Number</label>
                    <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number') }}">
                </div>
                <div class="ops-field">
                    <label for="property_contact_email">Contact Email</label>
                    <input id="property_contact_email" name="property_contact_email" class="ops-input" type="email" maxlength="190" value="{{ old('property_contact_email') }}">
                </div>
                <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
                <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
                <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
                <div class="ops-field ops-field-wide">
                    <label>Location Map Pin</label>
                    <div class="map-picker">
                        <div id="propertyMap" aria-label="Pin your property on the map"></div>
                    </div>
                    <p class="map-help">Click the map to drop a pin. Coordinates save automatically.</p>
                </div>
            </div>
        </section>

        <section class="listing-form-section" aria-label="Description, facilities and amenities">
            <div class="listing-form-section-head">
                <h4>Description, Facilities and Amenities</h4>
                <p>Explain the stay clearly, then tick the facilities and amenities guests will expect to find on arrival.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field ops-field-wide">
                    <label for="property_description">Description</label>
                    <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label>Property Amenities</label>
                    <div class="feature-checklist">
                        @forelse ($propertyAmenityOptionsCollection as $amenityOption)
                            @php $amenityValue = trim((string) ($amenityOption['value'] ?? '')); @endphp
                            @if ($amenityValue !== '')
                                <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $amenityValue }}" @checked(in_array($amenityValue, old('property_amenities', []), true))> {{ $amenityOption['label'] ?? $amenityValue }}</label>
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
                                <label class="feature-item"><input type="checkbox" name="property_features[]" value="{{ $featureValue }}" @checked(in_array($featureValue, old('property_features', []), true))> {{ $featureOption['label'] ?? $featureValue }}</label>
                            @endif
                        @empty
                            <p class="small">No features configured yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="listing-form-section" aria-label="Transfer options and charges">
            <div class="listing-form-section-head">
                <h4>Transfer Options</h4>
                <p>Select the transfer modes this property supports and enter the adult and child fares for locals and foreigners. Final selection is still made by the guest during booking.</p>
            </div>
            <p class="listing-form-note">Leave any fare blank if that transfer is not priced yet. Only checked transfer modes will be shown to guests.</p>
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
                                <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, old('transfer_options', []), true))>
                                <span>{{ $transferOptionLabel }}</span>
                            </label>
                            <small>Optional transfer mode for this accommodation listing.</small>
                        </div>
                        <label class="listing-transfer-rate">
                            <span>Local Adult</span>
                            <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_adult.' . $transferOptionKey) }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Local Child</span>
                            <input name="transfer_rates_local_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_child.' . $transferOptionKey) }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Adult</span>
                            <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_adult.' . $transferOptionKey) }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Child</span>
                            <input name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_child.' . $transferOptionKey) }}" placeholder="MVR 0.00">
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="listing-form-section" aria-label="Policies and stay rules">
            <div class="listing-form-section-head">
                <h4>Policies</h4>
                <p>Finish with operational rules and policy text so vendors can review guest-facing conditions in one place.</p>
            </div>
            <div class="ops-form-grid">
                <div class="ops-field">
                    <label for="property_check_in_time">Check-in Time</label>
                    <input id="property_check_in_time" name="check_in_time" class="ops-input" type="time" value="{{ old('check_in_time', '14:00') }}">
                </div>
                <div class="ops-field">
                    <label for="property_check_out_time">Check-out Time</label>
                    <input id="property_check_out_time" name="check_out_time" class="ops-input" type="time" value="{{ old('check_out_time', '12:00') }}">
                </div>
                <div class="ops-field">
                    <label for="property_minimum_nights">Minimum Nights</label>
                    <input id="property_minimum_nights" name="minimum_nights" class="ops-input" type="number" min="1" max="365" value="{{ old('minimum_nights', 1) }}">
                </div>
                <div class="ops-field">
                    <label for="property_check_in_grace_minutes">Check-in Grace (minutes)</label>
                    <input id="property_check_in_grace_minutes" name="check_in_grace_minutes" class="ops-input" type="number" min="0" max="720" value="{{ old('check_in_grace_minutes', 60) }}">
                </div>
                <div class="ops-field">
                    <label for="property_early_check_in_allowed">Early Check-in</label>
                    <select id="property_early_check_in_allowed" name="early_check_in_allowed" class="ops-select">
                        <option value="">Select</option>
                        <option value="yes" @selected(old('early_check_in_allowed') === 'yes')>Allowed</option>
                        <option value="subject_to_availability" @selected(old('early_check_in_allowed') === 'subject_to_availability')>Subject to Availability</option>
                        <option value="no" @selected(old('early_check_in_allowed') === 'no')>Not Allowed</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_late_check_out_allowed">Late Check-out</label>
                    <select id="property_late_check_out_allowed" name="late_check_out_allowed" class="ops-select">
                        <option value="">Select</option>
                        <option value="yes" @selected(old('late_check_out_allowed') === 'yes')>Allowed</option>
                        <option value="subject_to_availability" @selected(old('late_check_out_allowed') === 'subject_to_availability')>Subject to Availability</option>
                        <option value="no" @selected(old('late_check_out_allowed') === 'no')>Not Allowed</option>
                    </select>
                </div>
                <div class="ops-field">
                    <label for="property_early_check_in_fee">Early Check-in Fee (MVR)</label>
                    <input id="property_early_check_in_fee" name="early_check_in_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('early_check_in_fee') }}" placeholder="0 = complimentary">
                </div>
                <div class="ops-field">
                    <label for="property_late_check_out_fee">Late Check-out Fee (MVR)</label>
                    <input id="property_late_check_out_fee" name="late_check_out_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('late_check_out_fee') }}" placeholder="0 = complimentary">
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_house_rules">House Rules</label>
                    <textarea id="property_house_rules" name="house_rules" class="ops-textarea" rows="3" maxlength="2000" placeholder="No parties, no smoking indoors, quiet after 10pm…">{{ old('house_rules') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_child_policy">Child Policy</label>
                    <textarea id="property_child_policy" name="child_policy" class="ops-textarea" rows="3" maxlength="3000" placeholder="Children under 6 stay free…">{{ old('child_policy') }}</textarea>
                </div>
                <div class="ops-field ops-field-wide">
                    <label for="property_cancellation_policy">Cancellation Policy</label>
                    <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="e.g. Free cancellation up to 48 hours before. 50% refund within 24 hours.">{{ old('cancellation_policy') }}</textarea>
                </div>
            </div>
        </section>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Listing</button>
        <a class="btn btn-secondary" href="/vendor/listings/accommodation">Cancel</a>
    </div>
</form>
