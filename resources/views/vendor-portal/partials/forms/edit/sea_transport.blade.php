{{-- Standalone edit form: Sea Transport / Ferries --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="sea_transport">

    <div class="ops-form-grid">

        {{-- ── Basic Information ─────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Basic Information</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_name">Route Name <span style="color:#c0392b;">*</span></label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name', $property->name ?? '') }}" placeholder="e.g. Male' to Seenu Gan" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description <span style="color:#c0392b;">*</span></label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" rows="5" required>{{ old('description', $property->description ?? '') }}</textarea>
        </div>

        {{-- ── Vessel Details ────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Vessel Details</p>
        </div>
        <div class="ops-field">
            <label for="property_vessel_name">Vessel Name</label>
            <input id="property_vessel_name" name="vessel_name" class="ops-input" type="text" maxlength="120" value="{{ old('vessel_name', $propertyDetails['vessel_name'] ?? '') }}" placeholder="e.g. Island Express">
        </div>
        <div class="ops-field">
            <label for="property_registration_no">Registration / Hull No.</label>
            <input id="property_registration_no" name="registration_no" class="ops-input" type="text" maxlength="60" value="{{ old('registration_no', $propertyDetails['registration_no'] ?? '') }}">
        </div>

        {{-- ── Route Schedule ────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Route Schedule</p>
        </div>
        <div class="ops-field">
            <label for="property_departure_point">Departure Point <span style="color:#c0392b;">*</span></label>
            <input id="property_departure_point" name="departure_point" class="ops-input" type="text" maxlength="120" value="{{ old('departure_point', $propertyDetails['departure_point'] ?? '') }}" placeholder="e.g. Malé Harbour" required>
        </div>
        <div class="ops-field">
            <label for="property_arrival_point">Arrival Point <span style="color:#c0392b;">*</span></label>
            <input id="property_arrival_point" name="arrival_point" class="ops-input" type="text" maxlength="120" value="{{ old('arrival_point', $propertyDetails['arrival_point'] ?? '') }}" placeholder="e.g. Seenu Gan" required>
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Departure Time <span style="color:#c0392b;">*</span></label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time', $propertyDetails['departure_time'] ?? '') }}" required>
        </div>
        <div class="ops-field">
            <label for="property_return_time">Return / Arrival Time <span style="color:#c0392b;">*</span></label>
            <input id="property_return_time" name="return_time" class="ops-input" type="time" value="{{ old('return_time', $propertyDetails['return_time'] ?? '') }}" required>
        </div>
        <div class="ops-field">
            <label for="property_trip_duration_minutes">Trip Duration (minutes) <span style="color:#c0392b;">*</span></label>
            <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="1" value="{{ old('trip_duration_minutes', $propertyDetails['trip_duration_minutes'] ?? '') }}" required>
        </div>

        {{-- ── Seat Configuration ────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Seat Configuration</p>
        </div>
        <div class="ops-field">
            <label for="property_total_seats">Total Seats <span style="color:#c0392b;">*</span></label>
            <input id="property_total_seats" name="total_seats" class="ops-input" type="number" min="1" max="1000" value="{{ old('total_seats', $propertyDetails['total_seats'] ?? '') }}" required>
        </div>

        {{-- ── Service Pricing ───────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Service Pricing</p>
        </div>
        <p style="grid-column:1/-1; font-size:0.9rem; color:#666; margin-bottom:1rem;">Enter the per-seat rate for local and foreign guests. Local rates use MVR. Foreign rates use USD. Leave foreign blank to use local pricing logic.</p>
        <div class="ops-field">
            <label for="property_local_price">Price Per Seat - Local (MVR) <span style="color:#c0392b;">*</span></label>
            <input id="property_local_price" name="local_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('local_price', $propertyDetails['local_price'] ?? '') }}" placeholder="MVR 0.00" required>
        </div>
        <div class="ops-field">
            <label for="property_foreign_price">Price Per Seat - Foreign (USD)</label>
            <input id="property_foreign_price" name="foreign_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('foreign_price', $propertyDetails['foreign_price'] ?? '') }}" placeholder="USD 0.00">
        </div>

        {{-- ── Additional Information ────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Additional Information</p>
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
            <label for="property_boarding_instructions">Boarding Instructions</label>
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="1500" placeholder="Arrive 30 min early. Bring your booking confirmation.">{{ old('boarding_instructions', $propertyDetails['boarding_instructions'] ?? '') }}</textarea>
        </div>

        {{-- ── Location / Geography ──────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Location / Geography</p>
        </div>
        <div class="ops-field">
            <label for="location_country">Operating Country</label>
            <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', $propertyDetails['location_country'] ?? 'Maldives') }}" required>
                <option value="Maldives" @selected(old('location_country', $propertyDetails['location_country'] ?? 'Maldives') === 'Maldives')>Maldives</option>
                <option value="Sri Lanka" @selected(old('location_country', $propertyDetails['location_country'] ?? '') === 'Sri Lanka')>Sri Lanka</option>
                <option value="India" @selected(old('location_country', $propertyDetails['location_country'] ?? '') === 'India')>India</option>
                <option value="Other" @selected(old('location_country', $propertyDetails['location_country'] ?? '') === 'Other')>Other</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_state">Atoll / Province</label>
            <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state', $propertyDetails['location_state'] ?? '') }}" required>
                <option value="">Select atoll</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island / City</label>
            <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city', $propertyDetails['location_city'] ?? '') }}" required>
                <option value="">Select island</option>
            </select>
        </div>

        {{-- ── Availability ──────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Availability</p>
        </div>
        <p style="grid-column:1/-1; font-size:0.9rem; color:#666; margin-bottom:1rem;">Enter available days and dates when this route operates (one per line). Format: Mon,Wed,Fri or specific dates YYYY-MM-DD</p>
        <div class="ops-field ops-field-wide">
            <label for="property_availability_schedule">Operating Schedule</label>
            <textarea id="property_availability_schedule" name="availability_schedule" class="ops-textarea" rows="3" maxlength="1000" placeholder="Mon&#10;Wed&#10;Fri&#10;2026-05-15">{{ old('availability_schedule', implode("\n", (array) ($propertyDetails['availability_schedule'] ?? []))) }}</textarea>
        </div>

        <div class="ops-form-actions">
            <button type="submit" class="ops-button ops-button-primary">Save Route</button>
            <button type="button" class="ops-button ops-button-secondary" onclick="window.history.back();">Cancel</button>
        </div>
    </div>
</form>
