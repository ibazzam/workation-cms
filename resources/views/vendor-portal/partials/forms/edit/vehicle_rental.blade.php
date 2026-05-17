{{-- Standalone edit form: Vehicle Rental --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="vehicle_rental">

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
            <label for="property_vehicle_type">Vehicle Type</label>
            <select id="property_vehicle_type" name="vehicle_type" class="ops-select">
                <option value="">Select type</option>
                @foreach ($vehicleRentalTypeOptionsCollection as $vTypeOption)
                    @php $vTypeValue = trim((string) ($vTypeOption['value'] ?? '')); @endphp
                    @if ($vTypeValue !== '')
                        <option value="{{ $vTypeValue }}" @selected(old('vehicle_type', $propertyDetails['vehicle_type'] ?? '') === $vTypeValue)>{{ $vTypeOption['label'] ?? $vTypeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_transmission_type">Transmission</label>
            <select id="property_transmission_type" name="transmission_type" class="ops-select">
                <option value="">Select</option>
                @foreach (['automatic' => 'Automatic', 'manual' => 'Manual', 'semi_auto' => 'Semi-Automatic'] as $tmVal => $tmLabel)
                    <option value="{{ $tmVal }}" @selected(old('transmission_type', $propertyDetails['transmission_type'] ?? '') === $tmVal)>{{ $tmLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_fuel_type">Fuel Type</label>
            <select id="property_fuel_type" name="fuel_type" class="ops-select">
                <option value="">Select</option>
                @foreach (['petrol' => 'Petrol', 'diesel' => 'Diesel', 'electric' => 'Electric', 'hybrid' => 'Hybrid', 'lpg' => 'LPG'] as $ftVal => $ftLabel)
                    <option value="{{ $ftVal }}" @selected(old('fuel_type', $propertyDetails['fuel_type'] ?? '') === $ftVal)>{{ $ftLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_year_manufactured">Year</label>
            <input id="property_year_manufactured" name="year_manufactured" class="ops-input" type="number" min="1990" max="{{ date('Y') + 1 }}" value="{{ old('year_manufactured', $propertyDetails['year_manufactured'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_rental_seating_count">Number of Seats</label>
            <input id="property_rental_seating_count" name="rental_seating_count" class="ops-input" type="number" min="1" max="100" value="{{ old('rental_seating_count', $propertyDetails['rental_seating_count'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Occupants</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Fleet Size</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value', $propertyDetails['capacity_value'] ?? 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_minimum_age">Minimum Driver Age</label>
            <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="16" max="99" value="{{ old('minimum_age', $propertyDetails['minimum_age'] ?? 18) }}">
        </div>
        <div class="ops-field">
            <label for="property_license_class_required">License Class Required</label>
            <input id="property_license_class_required" name="license_class_required" class="ops-input" type="text" maxlength="60" value="{{ old('license_class_required', $propertyDetails['license_class_required'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_deposit_amount">Security Deposit (MVR)</label>
            <input id="property_deposit_amount" name="deposit_amount" class="ops-input" type="number" min="0" step="0.01" value="{{ old('deposit_amount', $propertyDetails['deposit_amount'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_daily_km_limit">Daily KM Limit (0 = unlimited)</label>
            <input id="property_daily_km_limit" name="daily_km_limit" class="ops-input" type="number" min="0" value="{{ old('daily_km_limit', $propertyDetails['daily_km_limit'] ?? 0) }}">
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
            <label for="location_state">Atoll / State / Province</label>
            <select id="location_state" name="location_state" class="ops-select" data-edit-state data-selected-value="{{ old('location_state', $propertyDetails['location_state'] ?? '') }}" required>
                <option value="">Select atoll/state</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island / City</label>
            <select id="location_city" name="location_city" class="ops-select" data-edit-city data-selected-value="{{ old('location_city', $propertyDetails['location_city'] ?? '') }}" required>
                <option value="">Select island/city</option>
            </select>
        </div>
        <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
        <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
        <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit depot pin"></div></div>
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
                    <small>Per day, trip, or rental period, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/vehicle_rental">Cancel</a>
    </div>
</form>
