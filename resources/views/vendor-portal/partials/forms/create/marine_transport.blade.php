{{-- Standalone create form: Marine Transport --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="marine_transport">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Listing Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
        </div>

        {{-- Transport mode (marine only) --}}
        <div class="ops-field">
            <label for="property_transport_mode">Transport Mode</label>
            <select id="property_transport_mode" name="transport_mode" class="ops-select">
                <option value="">Select mode</option>
                @foreach ($transportModeOptionsCollection as $modeOption)
                    @php $modeValue = trim((string) ($modeOption['value'] ?? '')); @endphp
                    @if ($modeValue !== '' && !in_array($modeValue, ['car','van','bus','truck','bicycle','motorbike','scooter'], true))
                        <option value="{{ $modeValue }}" @selected(old('transport_mode') === $modeValue)>{{ $modeOption['label'] ?? $modeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_vehicle_name">Vessel / Vehicle Name</label>
            <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name') }}" placeholder="e.g. Island Express">
        </div>
        <div class="ops-field">
            <label for="property_registration_plate">Registration / Hull No.</label>
            <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="60" value="{{ old('registration_plate') }}">
        </div>
        <div class="ops-field">
            <label for="property_transport_trip_type">Trip Type</label>
            <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                <option value="">Select</option>
                <option value="one_way" @selected(old('transport_trip_type') === 'one_way')>One Way</option>
                <option value="return" @selected(old('transport_trip_type') === 'return')>Return / Round Trip</option>
                <option value="charter" @selected(old('transport_trip_type') === 'charter')>Charter</option>
                <option value="scheduled" @selected(old('transport_trip_type') === 'scheduled')>Scheduled Service</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_seat_class">Seat Class</label>
            <input id="property_seat_class" name="seat_class" class="ops-input" type="text" maxlength="80" value="{{ old('seat_class') }}" placeholder="Economy, Business, VIP…">
        </div>
        <div class="ops-field">
            <label for="property_luggage_allowance_kg">Luggage Allowance (kg)</label>
            <input id="property_luggage_allowance_kg" name="luggage_allowance_kg" class="ops-input" type="number" min="0" step="0.5" value="{{ old('luggage_allowance_kg') }}">
        </div>

        {{-- Marine-specific --}}
        <div class="ops-field">
            <label for="property_transport_departure_state">Departure Atoll</label>
            <input id="property_transport_departure_state" name="transport_departure_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_state') }}" placeholder="e.g. Kaafu Atoll">
        </div>
        <div class="ops-field">
            <label for="property_transport_departure_city">Departure Island / Port</label>
            <input id="property_transport_departure_city" name="transport_departure_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_city') }}" placeholder="e.g. Malé Harbour">
        </div>
        <div class="ops-field">
            <label for="property_transport_arrival_state">Arrival Atoll</label>
            <input id="property_transport_arrival_state" name="transport_arrival_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_state') }}">
        </div>
        <div class="ops-field">
            <label for="property_transport_arrival_city">Arrival Island / Port</label>
            <input id="property_transport_arrival_city" name="transport_arrival_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_city') }}">
        </div>
        <div class="ops-field">
            <label for="property_departure_area_port_jetty">Jetty / Port Name</label>
            <input id="property_departure_area_port_jetty" name="departure_area_port_jetty" class="ops-input" type="text" maxlength="120" value="{{ old('departure_area_port_jetty') }}">
        </div>
        <div class="ops-field">
            <label for="property_departure_date">Departure Date</label>
            <input id="property_departure_date" name="departure_date" class="ops-input" type="date" value="{{ old('departure_date') }}">
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Departure Time</label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_trip_duration_minutes">Trip Duration (minutes)</label>
            <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="1" max="10000" value="{{ old('trip_duration_minutes') }}">
        </div>
        <div class="ops-field">
            <label for="property_reporting_lead_minutes">Reporting Lead Time (minutes)</label>
            <input id="property_reporting_lead_minutes" name="reporting_lead_minutes" class="ops-input" type="number" min="0" max="1440" value="{{ old('reporting_lead_minutes', 30) }}">
        </div>
        <div class="ops-field">
            <label for="property_schedule_start_time">Schedule Start</label>
            <input id="property_schedule_start_time" name="schedule_start_time" class="ops-input" type="time" value="{{ old('schedule_start_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_schedule_end_time">Schedule End</label>
            <input id="property_schedule_end_time" name="schedule_end_time" class="ops-input" type="time" value="{{ old('schedule_end_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_booking_cutoff_minutes">Booking Cutoff (minutes before)</label>
            <input id="property_booking_cutoff_minutes" name="booking_cutoff_minutes" class="ops-input" type="number" min="0" value="{{ old('booking_cutoff_minutes', 60) }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Passengers</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" max="10000" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Total Seats</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}">
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
            <label for="property_boarding_instructions">Boarding Instructions</label>
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="2000" placeholder="Arrive 30 min early. Bring your booking confirmation.">{{ old('boarding_instructions') }}</textarea>
        </div>

        {{-- Geo --}}
        <div class="ops-field">
            <label for="location_country">Operating Country</label>
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
            <p class="map-help">Click the map to drop a pin for the departure location.</p>
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
                    <small>Per seat, transfer, or charter, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/marine_transport">Cancel</a>
    </div>
</form>
