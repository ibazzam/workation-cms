{{-- Standalone edit form: Resort Day Visit --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="resort_day_visit">

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
            <label for="property_day_visit_start_time">Day Visit Start</label>
            <input id="property_day_visit_start_time" name="day_visit_start_time" class="ops-input" type="time" value="{{ old('day_visit_start_time', $propertyDetails['day_visit_start_time'] ?? '09:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_day_visit_end_time">Day Visit End</label>
            <input id="property_day_visit_end_time" name="day_visit_end_time" class="ops-input" type="time" value="{{ old('day_visit_end_time', $propertyDetails['day_visit_end_time'] ?? '18:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_per_adult">Price Per Adult (MVR)</label>
            <input id="property_price_per_adult" name="price_per_adult" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_adult', $propertyDetails['price_per_adult'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_per_child">Price Per Child (MVR)</label>
            <input id="property_price_per_child" name="price_per_child" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_child', $propertyDetails['price_per_child'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Day Visitors</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Daily Slot Capacity</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value', $propertyDetails['capacity_value'] ?? '') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_included_access">What’s Included in Day Pass</label>
            <textarea id="property_included_access" name="included_access" class="ops-textarea" rows="3" maxlength="2000">{{ old('included_access', $propertyDetails['included_access'] ?? '') }}</textarea>
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
            <label for="location_state">Atoll</label>
            <select id="location_state" name="location_state" class="ops-select" data-edit-state data-selected-value="{{ old('location_state', $propertyDetails['location_state'] ?? '') }}" required>
                <option value="">Select atoll</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island</label>
            <select id="location_city" name="location_city" class="ops-select" data-edit-city data-selected-value="{{ old('location_city', $propertyDetails['location_city'] ?? '') }}" required>
                <option value="">Select island</option>
            </select>
        </div>
        <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
        <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
        <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit resort pin"></div></div>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="/vendor/listings/resort_day_visit">Cancel</a>
    </div>
</form>
