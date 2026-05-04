{{-- Standalone create form: Resort Day Visit --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="resort_day_visit">
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
            <label for="property_day_visit_start_time">Day Visit Start</label>
            <input id="property_day_visit_start_time" name="day_visit_start_time" class="ops-input" type="time" value="{{ old('day_visit_start_time', '09:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_day_visit_end_time">Day Visit End</label>
            <input id="property_day_visit_end_time" name="day_visit_end_time" class="ops-input" type="time" value="{{ old('day_visit_end_time', '18:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_per_adult">Price Per Adult (MVR) <span style="font-weight:400; color:#5b7488;">(flat rate fallback)</span></label>
            <input id="property_price_per_adult" name="price_per_adult" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_adult') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_per_child">Price Per Child (MVR) <span style="font-weight:400; color:#5b7488;">(flat rate fallback)</span></label>
            <input id="property_price_per_child" name="price_per_child" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_child') }}">
        </div>

        {{-- ── Pricing by Residency ──────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Pricing by Residency <span style="font-weight:400; font-size:0.73rem;">(overrides flat rate above if set)</span></p>
        </div>
        <div class="ops-field">
            <label for="property_adult_price_local">Adult Price — Local (MVR)</label>
            <input id="property_adult_price_local" name="adult_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_local') }}" placeholder="e.g. 500.00">
            <p class="map-help">Price for Maldivian nationals.</p>
        </div>
        <div class="ops-field">
            <label for="property_adult_price_foreign">Adult Price — Foreign (MVR)</label>
            <input id="property_adult_price_foreign" name="adult_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_foreign') }}" placeholder="e.g. 900.00">
            <p class="map-help">Price for international guests.</p>
        </div>
        <div class="ops-field">
            <label for="property_child_price_local">Child Price — Local (MVR)</label>
            <input id="property_child_price_local" name="child_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_local') }}" placeholder="e.g. 250.00">
        </div>
        <div class="ops-field">
            <label for="property_child_price_foreign">Child Price — Foreign (MVR)</label>
            <input id="property_child_price_foreign" name="child_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_foreign') }}" placeholder="e.g. 450.00">
        </div>

        {{-- ── Transfer & Slot Configuration ────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Transfer & Slot Configuration</p>
        </div>
        <div class="ops-field">
            <label for="property_transfer_included">Transfer Included in Price?</label>
            <select id="property_transfer_included" name="transfer_included" class="ops-select">
                <option value="0" @selected(!old('transfer_included'))>No — guests arrange own transfer</option>
                <option value="1" @selected((bool) old('transfer_included'))>Yes — transfer included in day visit price</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_departure_time_mode">Departure Time Type</label>
            <select id="property_departure_time_mode" name="departure_time_mode" class="ops-select">
                <option value="fixed" @selected(old('departure_time_mode', 'fixed') === 'fixed')>Fixed — single departure time</option>
                <option value="slots" @selected(old('departure_time_mode') === 'slots')>Slots — customers pick departure time</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Departure Time (fixed)</label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
            <p class="map-help">Used when type is Fixed.</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_departure_slots">Departure Time Slots <span style="font-weight:400;">(one per line, 24h format e.g. 08:00)</span></label>
            <textarea id="property_departure_slots" name="departure_slots" class="ops-textarea" rows="4" placeholder="08:00&#10;09:00&#10;10:00&#10;11:00">{{ old('departure_slots') }}</textarea>
            <p class="map-help">Used when type is Slots. Enter one time per line.</p>
        </div>
        <div class="ops-field">
            <label for="property_return_time_mode">Return Time Type</label>
            <select id="property_return_time_mode" name="return_time_mode" class="ops-select">
                <option value="fixed" @selected(old('return_time_mode', 'fixed') === 'fixed')>Fixed — single return time</option>
                <option value="slots" @selected(old('return_time_mode') === 'slots')>Slots — customers pick return time</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_return_time">Return Time (fixed)</label>
            <input id="property_return_time" name="return_time" class="ops-input" type="time" value="{{ old('return_time') }}">
            <p class="map-help">Used when type is Fixed.</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_return_slots">Return Time Slots <span style="font-weight:400;">(one per line, 24h format e.g. 17:30)</span></label>
            <textarea id="property_return_slots" name="return_slots" class="ops-textarea" rows="4" placeholder="17:30&#10;18:30&#10;20:30&#10;22:30">{{ old('return_slots') }}</textarea>
            <p class="map-help">Used when type is Slots. Enter one time per line.</p>
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Day Visitors</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Daily Slot Capacity</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_included_access">What’s Included in Day Pass</label>
            <textarea id="property_included_access" name="included_access" class="ops-textarea" rows="3" maxlength="2000" placeholder="Beach access, pool, snorkelling equipment, welcome drink, lunch buffet…">{{ old('included_access') }}</textarea>
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
            <div class="map-picker"><div id="propertyMap" aria-label="Pin resort location"></div></div>
            <p class="map-help">Click to pin your resort location.</p>
        </div>

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Save Listing</button>
        <a class="btn btn-secondary" href="/vendor/listings/resort_day_visit">Cancel</a>
    </div>
</form>
