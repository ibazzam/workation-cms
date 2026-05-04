{{-- Standalone edit form: Water Sports --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="water_sports">

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
            <label for="property_excursion_type">Activity Type</label>
            <select id="property_excursion_type" name="excursion_type" class="ops-select">
                <option value="">Select type</option>
                @foreach ($excursionTypeOptionsCollection as $typeOption)
                    @php $typeValue = trim((string) ($typeOption['value'] ?? '')); @endphp
                    @if ($typeValue !== '')
                        <option value="{{ $typeValue }}" @selected(old('excursion_type', $propertyDetails['excursion_type'] ?? '') === $typeValue)>{{ $typeOption['label'] ?? $typeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_excursion_difficulty">Difficulty</label>
            <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                <option value="">Select</option>
                @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'hard' => 'Challenging', 'extreme' => 'Extreme'] as $diffVal => $diffLabel)
                    <option value="{{ $diffVal }}" @selected(old('excursion_difficulty', $propertyDetails['excursion_difficulty'] ?? '') === $diffVal)>{{ $diffLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_excursion_duration_minutes">Duration (minutes)</label>
            <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="1" value="{{ old('excursion_duration_minutes', $propertyDetails['excursion_duration_minutes'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_min_pax">Min Participants</label>
            <input id="property_excursion_min_pax" name="excursion_min_pax" class="ops-input" type="number" min="1" value="{{ old('excursion_min_pax', $propertyDetails['excursion_min_pax'] ?? 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_max_pax">Max Participants</label>
            <input id="property_excursion_max_pax" name="excursion_max_pax" class="ops-input" type="number" min="1" value="{{ old('excursion_max_pax', $propertyDetails['excursion_max_pax'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_min_age">Minimum Age</label>
            <input id="property_excursion_min_age" name="excursion_min_age" class="ops-input" type="number" min="0" max="120" value="{{ old('excursion_min_age', $propertyDetails['excursion_min_age'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Guests</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_service_radius_km">Service Radius (km)</label>
            <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" step="0.1" value="{{ old('service_radius_km', $propertyDetails['service_radius_km'] ?? '') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_meeting_point">Meeting Point</label>
            <input id="property_meeting_point" name="meeting_point" class="ops-input" type="text" maxlength="200" value="{{ old('meeting_point', $propertyDetails['meeting_point'] ?? '') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_inclusions">Inclusions</label>
            <textarea id="property_inclusions" name="inclusions" class="ops-textarea" rows="3" maxlength="2000">{{ old('inclusions', $propertyDetails['inclusions'] ?? '') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_exclusions">Exclusions</label>
            <textarea id="property_exclusions" name="exclusions" class="ops-textarea" rows="3" maxlength="2000">{{ old('exclusions', $propertyDetails['exclusions'] ?? '') }}</textarea>
        </div>
        <div class="ops-field">
            <label for="property_safety_waiver_required">Safety Waiver Required?</label>
            <select id="property_safety_waiver_required" name="safety_waiver_required" class="ops-select">
                <option value="0" @selected(!old('safety_waiver_required', $propertyDetails['safety_waiver_required'] ?? false))>No</option>
                <option value="1" @selected((bool) old('safety_waiver_required', $propertyDetails['safety_waiver_required'] ?? false))>Yes</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_equipment_rental_available">Equipment Rental?</label>
            <select id="property_equipment_rental_available" name="equipment_rental_available" class="ops-select">
                <option value="0" @selected(!old('equipment_rental_available', $propertyDetails['equipment_rental_available'] ?? false))>No</option>
                <option value="1" @selected((bool) old('equipment_rental_available', $propertyDetails['equipment_rental_available'] ?? false))>Yes</option>
            </select>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_weather_cancellation_policy">Weather Cancellation Policy</label>
            <textarea id="property_weather_cancellation_policy" name="weather_cancellation_policy" class="ops-textarea" rows="2" maxlength="1000">{{ old('weather_cancellation_policy', $propertyDetails['weather_cancellation_policy'] ?? '') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_special_instructions">Special Instructions</label>
            <textarea id="property_special_instructions" name="special_instructions" class="ops-textarea" rows="3" maxlength="2000">{{ old('special_instructions', $propertyDetails['special_instructions'] ?? '') }}</textarea>
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
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit location pin"></div></div>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-secondary" href="/vendor/listings/water_sports">Cancel</a>
    </div>
</form>
