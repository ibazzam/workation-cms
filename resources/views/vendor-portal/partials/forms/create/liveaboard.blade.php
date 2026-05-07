{{-- Standalone create form: Liveaboard / Safari --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="liveaboard">
    <input name="property_type" type="hidden" value="liveaboard">
    <input name="area_unit" type="hidden" value="sqft">
    <input name="measurement_system" type="hidden" value="imperial">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Journey / Liveaboard Name <span style="color:#c0392b;">*</span></label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" placeholder="e.g. Male' to Seenu Gan Safari" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description <span style="color:#c0392b;">*</span></label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" rows="5" required>{{ old('description') }}</textarea>
        </div>

        {{-- ── Journey Route ─────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Journey Route</p>
        </div>
        <div class="ops-field">
            <label for="property_start_point">Start Point (Embark) <span style="color:#c0392b;">*</span></label>
            <input id="property_start_point" name="start_point" class="ops-input" type="text" maxlength="120" value="{{ old('start_point') }}" placeholder="e.g. Malé" required>
        </div>
        <div class="ops-field">
            <label for="property_end_point">End Point (Disembark) <span style="color:#c0392b;">*</span></label>
            <input id="property_end_point" name="end_point" class="ops-input" type="text" maxlength="120" value="{{ old('end_point') }}" placeholder="e.g. Seenu Gan" required>
        </div>
        <div class="ops-field">
            <label for="property_journey_duration_days">Journey Duration (days) <span style="color:#c0392b;">*</span></label>
            <input id="property_journey_duration_days" name="journey_duration_days" class="ops-input" type="number" min="1" max="90" value="{{ old('journey_duration_days') }}" required>
        </div>

        {{-- ── Stopovers Configuration ────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Stopovers (Hop-on Points)</p>
        </div>
        <p style="grid-column:1/-1; font-size:0.9rem; color:#666; margin-bottom:1rem;">Add stopovers where guests can board or disembark along the journey. Enter each on a new line in format: <strong>StopoverName|AllowEmbark|AllowDisembark</strong> (e.g., "Laamu Kahdhoo|yes|yes" or "Addu City|no|yes"). You can use just the name if all stopovers allow both.</p>
        <div class="ops-field ops-field-wide">
            <label for="property_stopovers">Stopovers List</label>
            <textarea id="property_stopovers" name="stopovers" class="ops-textarea" rows="4" maxlength="1000" placeholder="Laamu Kahdhoo|yes|yes&#10;Gan Island|yes|yes&#10;Addu City|no|yes">{{ old('stopovers') }}</textarea>
            <p style="font-size:0.85rem; color:#999;">Format per line: Name or Name|EmbarkYesNo|DisembarkYesNo</p>
        </div>

        {{-- ── Pricing Matrix ────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Pricing Matrix</p>
        </div>
        <p style="grid-column:1/-1; font-size:0.9rem; color:#666; margin-bottom:1rem;">Define package prices for different boarding-disembarking combinations. Format: <strong>FromPoint→ToPoint=Price(MVR)</strong>. Set one per line (e.g., "Malé→Seenu Gan=5000" or "Laamu→Malé=3000").</p>
        <div class="ops-field ops-field-wide">
            <label for="property_pricing_matrix">Pricing Matrix</label>
            <textarea id="property_pricing_matrix" name="pricing_matrix" class="ops-textarea" rows="5" maxlength="2000" placeholder="Malé→Seenu Gan=5000&#10;Malé→Gan=4500&#10;Laamu→Seenu Gan=3500&#10;Laamu→Malé=3000">{{ old('pricing_matrix') }}</textarea>
            <p style="font-size:0.85rem; color:#999;">Format: boarding→disembarking=price. Prices in MVR.</p>
        </div>

        {{-- ── Vessel Information ────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Vessel Information</p>
        </div>
        <div class="ops-field">
            <label for="property_vessel_name">Vessel / Liveaboard Name</label>
            <input id="property_vessel_name" name="vessel_name" class="ops-input" type="text" maxlength="120" value="{{ old('vessel_name') }}" placeholder="e.g. Island Safari">
        </div>
        <div class="ops-field">
            <label for="property_registration_no">Registration / Hull No.</label>
            <input id="property_registration_no" name="registration_no" class="ops-input" type="text" maxlength="60" value="{{ old('registration_no') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Guests Onboard</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" max="1000" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_cabin_count">Cabin / Room Count</label>
            <input id="property_cabin_count" name="cabin_count" class="ops-input" type="number" min="1" value="{{ old('cabin_count') }}">
        </div>

        {{-- ── Service Information ───────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Service Information</p>
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
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="1500" placeholder="Arrive 2 hours before departure. Bring your booking confirmation and ID.">{{ old('boarding_instructions') }}</textarea>
        </div>

        {{-- ── Location ──────────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-top:1px solid #e0e0e0; border-bottom:1px solid #cfe0eb; padding:0.5rem 0; margin-top:1rem; margin-bottom:0.5rem;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Location</p>
        </div>
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

        <div class="ops-form-actions" style="grid-column:1/-1; margin-top:2rem;">
            <button type="submit" class="ops-button ops-button-primary">Save Liveaboard</button>
            <button type="button" class="ops-button ops-button-secondary" onclick="window.history.back();">Cancel</button>
        </div>
    </div>
</form>
