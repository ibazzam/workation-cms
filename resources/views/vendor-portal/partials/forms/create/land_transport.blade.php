{{-- Standalone create form: Land Transport --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="land_transport">
    <input type="hidden" name="base_price" value="0">

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
            <label for="property_transport_mode">Transport Mode</label>
            <select id="property_transport_mode" name="transport_mode" class="ops-select">
                <option value="">Select mode</option>
                @foreach ($transportModeOptionsCollection as $modeOption)
                    @php $modeValue = trim((string) ($modeOption['value'] ?? '')); @endphp
                    @if ($modeValue !== '' && !in_array($modeValue, ['speedboat','dhoni','yacht','boat','catamaran','ferry','cruise_ship','submarine','seaplane'], true))
                        <option value="{{ $modeValue }}" @selected(old('transport_mode') === $modeValue)>{{ $modeOption['label'] ?? $modeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_vehicle_name">Vehicle Name / Model</label>
            <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name') }}" placeholder="e.g. Toyota Hiace 2022">
        </div>
        <div class="ops-field">
            <label for="property_registration_plate">Registration Plate</label>
            <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="60" value="{{ old('registration_plate') }}">
        </div>
        <div class="ops-field">
            <label for="property_transport_trip_type">Trip Type</label>
            <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                <option value="">Select</option>
                <option value="one_way" @selected(old('transport_trip_type') === 'one_way')>One Way</option>
                <option value="return" @selected(old('transport_trip_type') === 'return')>Return / Round Trip</option>
                <option value="charter" @selected(old('transport_trip_type') === 'charter')>Charter</option>
                <option value="hourly" @selected(old('transport_trip_type') === 'hourly')>Hourly</option>
                <option value="daily" @selected(old('transport_trip_type') === 'daily')>Daily</option>
            </select>
        </div>
        {{-- Land-specific pricing --}}
        <div class="ops-field">
            <label for="property_transport_pricing_model">Pricing Model</label>
            <select id="property_transport_pricing_model" name="transport_pricing_model" class="ops-select">
                <option value="">Select</option>
                <option value="per_trip" @selected(old('transport_pricing_model') === 'per_trip')>Per Trip</option>
                <option value="per_hour" @selected(old('transport_pricing_model') === 'per_hour')>Per Hour</option>
                <option value="per_day" @selected(old('transport_pricing_model') === 'per_day')>Per Day</option>
                <option value="per_km" @selected(old('transport_pricing_model') === 'per_km')>Per Kilometre</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_hourly_rate">Hourly Rate (MVR)</label>
            <input id="property_hourly_rate" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('hourly_rate') }}">
        </div>
        <div class="ops-field">
            <label for="property_daily_rate">Daily Rate (MVR)</label>
            <input id="property_daily_rate" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('daily_rate') }}">
        </div>
        <div class="ops-field">
            <label for="property_seat_class">Seat Class / Comfort Level</label>
            <input id="property_seat_class" name="seat_class" class="ops-input" type="text" maxlength="80" value="{{ old('seat_class') }}" placeholder="Standard, Business, VIP">
        </div>
        <div class="ops-field">
            <label for="property_luggage_allowance_kg">Luggage Allowance (kg)</label>
            <input id="property_luggage_allowance_kg" name="luggage_allowance_kg" class="ops-input" type="number" min="0" step="0.5" value="{{ old('luggage_allowance_kg') }}">
        </div>
        <div class="ops-field">
            <label for="property_schedule_start_time">Availability Start</label>
            <input id="property_schedule_start_time" name="schedule_start_time" class="ops-input" type="time" value="{{ old('schedule_start_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_schedule_end_time">Availability End</label>
            <input id="property_schedule_end_time" name="schedule_end_time" class="ops-input" type="time" value="{{ old('schedule_end_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_booking_cutoff_minutes">Booking Cutoff (minutes)</label>
            <input id="property_booking_cutoff_minutes" name="booking_cutoff_minutes" class="ops-input" type="number" min="0" value="{{ old('booking_cutoff_minutes', 30) }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Passengers</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" max="200" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Number of Vehicles</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value') }}">
        </div>
        <div class="ops-field">
            <label for="property_pickup_location">Pickup Location</label>
            <input id="property_pickup_location" name="pickup_location" class="ops-input" type="text" maxlength="200" value="{{ old('pickup_location') }}">
        </div>
        <div class="ops-field">
            <label for="property_dropoff_location">Dropoff Location</label>
            <input id="property_dropoff_location" name="dropoff_location" class="ops-input" type="text" maxlength="200" value="{{ old('dropoff_location') }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_name">Contact Name</label>
            <input id="property_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name') }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_number">Contact Number</label>
            <input id="property_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_boarding_instructions">Boarding / Pickup Instructions</label>
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="2000">{{ old('boarding_instructions') }}</textarea>
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
        <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
        <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
        <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div id="propertyMap" aria-label="Pin location on map"></div></div>
            <p class="map-help">Click the map to pin the primary pickup location.</p>
        </div>

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Listing</button>
        <a class="btn btn-secondary" href="/vendor/listings/land_transport">Cancel</a>
    </div>
</form>
