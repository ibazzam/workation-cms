{{-- Standalone edit form: Remote Workspace --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="remote_workspace">

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
            <label for="property_workspace_type">Workspace Type</label>
            <select id="property_workspace_type" name="workspace_type" class="ops-select">
                <option value="">Select type</option>
                @foreach (['hot_desk' => 'Hot Desk', 'dedicated_desk' => 'Dedicated Desk', 'private_office' => 'Private Office', 'coworking_lounge' => 'Co-working Lounge', 'resort_nook' => 'Resort Nook / Cabana', 'villa_study' => 'Villa Study', 'meeting_room' => 'Meeting Room'] as $wsVal => $wsLabel)
                    <option value="{{ $wsVal }}" @selected(old('workspace_type', $propertyDetails['workspace_type'] ?? '') === $wsVal)>{{ $wsLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_internet_speed_mbps">Internet Speed (Mbps)</label>
            <input id="property_internet_speed_mbps" name="internet_speed_mbps" class="ops-input" type="number" min="0" step="1" value="{{ old('internet_speed_mbps', $propertyDetails['internet_speed_mbps'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_min_booking_hours">Minimum Booking (hours)</label>
            <input id="property_min_booking_hours" name="min_booking_hours" class="ops-input" type="number" min="1" max="720" value="{{ old('min_booking_hours', $propertyDetails['min_booking_hours'] ?? 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_operating_hours_open">Opens At</label>
            <input id="property_operating_hours_open" name="operating_hours_open" class="ops-input" type="time" value="{{ old('operating_hours_open', $propertyDetails['operating_hours_open'] ?? '08:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_operating_hours_close">Closes At</label>
            <input id="property_operating_hours_close" name="operating_hours_close" class="ops-input" type="time" value="{{ old('operating_hours_close', $propertyDetails['operating_hours_close'] ?? '20:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Concurrent Users</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_area_value">Area (sqft)</label>
            <input id="property_area_value" name="area_value" class="ops-input" type="number" min="1" step="0.01" value="{{ old('area_value', $propertyDetails['area_value'] ?? '') }}">
        </div>
        @php
            $savedWsFree = is_array($propertyDetails['workspace_amenities_free'] ?? null) ? $propertyDetails['workspace_amenities_free'] : [];
            $savedWsPaid = is_array($propertyDetails['workspace_amenities_paid'] ?? null) ? $propertyDetails['workspace_amenities_paid'] : [];
        @endphp
        <div class="ops-field ops-field-wide">
            <label>Free Amenities</label>
            <div class="feature-checklist">
                @foreach ($workspaceAmenityCatalog as $wsAmenityKey => $wsAmenityLabel)
                    <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $wsAmenityKey }}" @checked(in_array($wsAmenityKey, old('workspace_amenities_free', $savedWsFree), true))> {{ $wsAmenityLabel }}</label>
                @endforeach
            </div>
        </div>
        <div class="ops-field ops-field-wide">
            <label>Paid Add-ons</label>
            <div class="feature-checklist">
                @foreach ($workspaceAmenityCatalog as $wsAmenityKey => $wsAmenityLabel)
                    <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $wsAmenityKey }}" @checked(in_array($wsAmenityKey, old('workspace_amenities_paid', $savedWsPaid), true))> {{ $wsAmenityLabel }}</label>
                @endforeach
            </div>
        </div>
        {{-- Transfer options --}}
        <div class="ops-field ops-field-wide">
            <label>Transfer Options (Optional)</label>
            @php
                $savedTransferOptions = is_array($propertyDetails['transfer_options'] ?? null) ? $propertyDetails['transfer_options'] : [];
                $savedTransferRatesLocalAdult = is_array($propertyDetails['transfer_rates_local_adult'] ?? null) ? $propertyDetails['transfer_rates_local_adult'] : [];
                $savedTransferRatesForeignAdult = is_array($propertyDetails['transfer_rates_foreign_adult'] ?? null) ? $propertyDetails['transfer_rates_foreign_adult'] : [];
            @endphp
            <div class="ops-form-grid">
                @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                    <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}"
                            @checked(in_array($transferOptionKey, old('transfer_options', $savedTransferOptions), true))>
                        <span>{{ $transferOptionLabel }}</span>
                    </label>
                    <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_adult.' . $transferOptionKey, $savedTransferRatesLocalAdult[$transferOptionKey] ?? '') }}" placeholder="Local adult (MVR)">
                    <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_adult.' . $transferOptionKey, $savedTransferRatesForeignAdult[$transferOptionKey] ?? '') }}" placeholder="Foreign adult (MVR)">
                @endforeach
            </div>
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
        <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
        <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
        <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit workspace location pin"></div></div>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
        </div>
    </div>

    <section class="listing-form-section listing-price-band" aria-label="Service pricing">
        <div class="listing-form-section-head">
            <h4>Service Pricing</h4>
            <p>Enter the customer-facing rate bands for local and foreign guests.</p>
        </div>
        <p class="listing-form-note">Local rates use MVR. Foreign rates use USD. Leave foreign blank to keep local pricing as the default.</p>
        <div class="listing-transfer-table">
            <div class="listing-transfer-head" aria-hidden="true">
                <span>Rate</span>
                <span>Local (MVR)</span>
                <span>Foreign (USD)</span>
            </div>
            <div class="listing-transfer-row">
                <div class="listing-transfer-option">
                    <label><span>Base Rate</span></label>
                    <small>Per desk, room, or booking block, based on this listing.</small>
                </div>
                <label class="listing-transfer-rate">
                    <span>Local (MVR)</span>
                    <input name="price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_local', $propertyDetails['price_local'] ?? ($property->base_price ?? '')) }}" placeholder="MVR 0.00">
                </label>
                <label class="listing-transfer-rate">
                    <span>Foreign (USD)</span>
                    <input name="price_usd" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_usd', $propertyDetails['price_usd'] ?? ($propertyDetails['price_foreign'] ?? '')) }}" placeholder="USD 0.00">
                </label>
            </div>
        </div>
    </section>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="/vendor/listings/remote_workspace">Cancel</a>
    </div>
</form>
