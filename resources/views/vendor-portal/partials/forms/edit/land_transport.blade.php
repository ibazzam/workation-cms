{{-- Standalone edit form: Land Transport --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="land_transport">

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
            <label for="property_transport_mode">Transport Mode</label>
            <select id="property_transport_mode" name="transport_mode" class="ops-select">
                <option value="">Select mode</option>
                @foreach ($transportModeOptionsCollection as $modeOption)
                    @php $modeValue = trim((string) ($modeOption['value'] ?? '')); @endphp
                    @if ($modeValue !== '' && !in_array($modeValue, ['speedboat','dhoni','yacht','boat','catamaran','ferry','cruise_ship','submarine','seaplane'], true))
                        <option value="{{ $modeValue }}" @selected(old('transport_mode', $propertyDetails['transport_mode'] ?? '') === $modeValue)>{{ $modeOption['label'] ?? $modeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_vehicle_name">Vehicle Name / Model</label>
            <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name', $propertyDetails['vehicle_name'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_registration_plate">Registration Plate</label>
            <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="60" value="{{ old('registration_plate', $propertyDetails['registration_plate'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_transport_trip_type">Trip Type</label>
            <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                <option value="">Select</option>
                @foreach (['one_way' => 'One Way', 'return' => 'Return / Round Trip', 'charter' => 'Charter', 'hourly' => 'Hourly', 'daily' => 'Daily'] as $ttVal => $ttLabel)
                    <option value="{{ $ttVal }}" @selected(old('transport_trip_type', $propertyDetails['transport_trip_type'] ?? '') === $ttVal)>{{ $ttLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_transport_pricing_model">Pricing Model</label>
            <select id="property_transport_pricing_model" name="transport_pricing_model" class="ops-select">
                <option value="">Select</option>
                @foreach (['per_trip' => 'Per Trip', 'per_hour' => 'Per Hour', 'per_day' => 'Per Day', 'per_km' => 'Per Kilometre'] as $pmVal => $pmLabel)
                    <option value="{{ $pmVal }}" @selected(old('transport_pricing_model', $propertyDetails['transport_pricing_model'] ?? '') === $pmVal)>{{ $pmLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_hourly_rate">Hourly Rate (MVR)</label>
            <input id="property_hourly_rate" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('hourly_rate', $propertyDetails['hourly_rate'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_daily_rate">Daily Rate (MVR)</label>
            <input id="property_daily_rate" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('daily_rate', $propertyDetails['daily_rate'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Passengers</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" max="200" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_booking_cutoff_minutes">Booking Cutoff (minutes)</label>
            <input id="property_booking_cutoff_minutes" name="booking_cutoff_minutes" class="ops-input" type="number" min="0" value="{{ old('booking_cutoff_minutes', $propertyDetails['booking_cutoff_minutes'] ?? 30) }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_name">Contact Name</label>
            <input id="property_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name', $propertyDetails['contact_name'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_number">Contact Number</label>
            <input id="property_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number', $propertyDetails['contact_number'] ?? '') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_boarding_instructions">Pickup Instructions</label>
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="2000">{{ old('boarding_instructions', $propertyDetails['boarding_instructions'] ?? '') }}</textarea>
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
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit pickup pin"></div></div>
            <p class="map-help">Click to move the pickup location pin.</p>
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
                    <small>Per seat, transfer, or trip, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/land_transport">Cancel</a>
    </div>
</form>
