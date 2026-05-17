{{-- Standalone create form: Vehicle Rental --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="vehicle_rental">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Listing Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
        </div>

        <div class="ops-field">
            <label for="property_vehicle_rental_type">Vehicle Type</label>
            <select id="property_vehicle_rental_type" name="vehicle_type" class="ops-select">
                <option value="">Select type</option>
                @foreach ($vehicleRentalTypeOptionsCollection as $vTypeOption)
                    @php $vTypeValue = trim((string) ($vTypeOption['value'] ?? '')); @endphp
                    @if ($vTypeValue !== '')
                        <option value="{{ $vTypeValue }}" @selected(old('vehicle_type') === $vTypeValue)>{{ $vTypeOption['label'] ?? $vTypeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_transmission_type">Transmission</label>
            <select id="property_transmission_type" name="transmission_type" class="ops-select">
                <option value="">Select</option>
                <option value="automatic" @selected(old('transmission_type') === 'automatic')>Automatic</option>
                <option value="manual" @selected(old('transmission_type') === 'manual')>Manual</option>
                <option value="semi_auto" @selected(old('transmission_type') === 'semi_auto')>Semi-Automatic</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_fuel_type">Fuel Type</label>
            <select id="property_fuel_type" name="fuel_type" class="ops-select">
                <option value="">Select</option>
                <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                <option value="electric" @selected(old('fuel_type') === 'electric')>Electric</option>
                <option value="hybrid" @selected(old('fuel_type') === 'hybrid')>Hybrid</option>
                <option value="lpg" @selected(old('fuel_type') === 'lpg')>LPG</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_year_manufactured">Year</label>
            <input id="property_year_manufactured" name="year_manufactured" class="ops-input" type="number" min="1990" max="{{ date('Y') + 1 }}" value="{{ old('year_manufactured') }}" placeholder="e.g. 2022">
        </div>
        <div class="ops-field">
            <label for="property_rental_seating_count">Number of Seats</label>
            <input id="property_rental_seating_count" name="rental_seating_count" class="ops-input" type="number" min="1" max="100" value="{{ old('rental_seating_count') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Occupants</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Fleet Size (no. of vehicles)</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value', 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_minimum_age">Minimum Driver Age</label>
            <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="16" max="99" value="{{ old('minimum_age', 18) }}">
        </div>
        <div class="ops-field">
            <label for="property_license_class_required">License Class Required</label>
            <input id="property_license_class_required" name="license_class_required" class="ops-input" type="text" maxlength="60" value="{{ old('license_class_required') }}" placeholder="e.g. B, A, International">
        </div>
        <div class="ops-field">
            <label for="property_deposit_amount">Security Deposit (MVR)</label>
            <input id="property_deposit_amount" name="deposit_amount" class="ops-input" type="number" min="0" step="0.01" value="{{ old('deposit_amount') }}">
        </div>
        <div class="ops-field">
            <label for="property_daily_km_limit">Daily KM Limit (0 = unlimited)</label>
            <input id="property_daily_km_limit" name="daily_km_limit" class="ops-input" type="number" min="0" value="{{ old('daily_km_limit', 0) }}">
        </div>

        {{-- Geo --}}
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
            <label for="location_state">Atoll / State / Province</label>
            <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                <option value="">Select atoll/state</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island / City</label>
            <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                <option value="">Select island/city</option>
            </select>
        </div>
        <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
        <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
        <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div id="propertyMap" aria-label="Pin vehicle depot location"></div></div>
            <p class="map-help">Click to pin the vehicle pickup / depot location.</p>
        </div>

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy') }}</textarea>
        </div>
    </div>

    <section class="listing-form-section listing-price-band" aria-label="Service pricing">
        <div class="listing-form-section-head">
            <h4>Service Pricing</h4>
            <p>Enter the customer-facing rate bands for local and foreign guests.</p>
        </div>
        <p class="listing-form-note">Local rates use MVR. Foreign rates use USD. Leave foreign blank to match local pricing logic in your offers.</p>
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
                    <input name="price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_local') }}" placeholder="MVR 0.00">
                </label>
                <label class="listing-transfer-rate">
                    <span>Foreign (USD)</span>
                    <input name="price_usd" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_usd') }}" placeholder="USD 0.00">
                </label>
            </div>
        </div>
    </section>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Listing</button>
        <a class="btn btn-secondary" href="/vendor/listings/vehicle_rental">Cancel</a>
    </div>
</form>
