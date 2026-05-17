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
            <label for="property_price_per_adult">Price Per Adult (MVR) <span style="font-weight:400; color:#5b7488;">(flat rate fallback)</span></label>
            <input id="property_price_per_adult" name="price_per_adult" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_adult', $propertyDetails['price_per_adult'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_per_child">Price Per Child (MVR) <span style="font-weight:400; color:#5b7488;">(flat rate fallback)</span></label>
            <input id="property_price_per_child" name="price_per_child" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_child', $propertyDetails['price_per_child'] ?? '') }}">
        </div>

        {{-- ── Pricing by Residency ──────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Pricing by Residency <span style="font-weight:400; font-size:0.73rem;">(overrides flat rate above if set)</span></p>
        </div>
        <div class="ops-field">
            <label for="property_adult_price_local">Adult Price — Local (MVR)</label>
            <input id="property_adult_price_local" name="adult_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_local', $propertyDetails['adult_price_local'] ?? '') }}" placeholder="e.g. 500.00">
            <p class="map-help">Price for Maldivian nationals.</p>
        </div>
        <div class="ops-field">
            <label for="property_adult_price_foreign">Adult Price — Foreign (MVR)</label>
            <input id="property_adult_price_foreign" name="adult_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_foreign', $propertyDetails['adult_price_foreign'] ?? '') }}" placeholder="e.g. 900.00">
            <p class="map-help">Price for international guests.</p>
        </div>
        <div class="ops-field">
            <label for="property_child_price_local">Child Price — Local (MVR)</label>
            <input id="property_child_price_local" name="child_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_local', $propertyDetails['child_price_local'] ?? '') }}" placeholder="e.g. 250.00">
        </div>
        <div class="ops-field">
            <label for="property_child_price_foreign">Child Price — Foreign (MVR)</label>
            <input id="property_child_price_foreign" name="child_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_foreign', $propertyDetails['child_price_foreign'] ?? '') }}" placeholder="e.g. 450.00">
        </div>

        {{-- ── Transfer & Slot Configuration ────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Transfer & Slot Configuration</p>
        </div>
        <div class="ops-field">
            <label for="property_transfer_included">Transfer Included in Price?</label>
            <select id="property_transfer_included" name="transfer_included" class="ops-select">
                <option value="0" @selected(!old('transfer_included', $propertyDetails['transfer_included'] ?? false))>No — guests arrange own transfer</option>
                <option value="1" @selected((bool) old('transfer_included', $propertyDetails['transfer_included'] ?? false))>Yes — transfer included in day visit price</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_departure_time_mode">Departure Time Type</label>
            <select id="property_departure_time_mode" name="departure_time_mode" class="ops-select">
                <option value="fixed" @selected(old('departure_time_mode', $propertyDetails['departure_time_mode'] ?? 'fixed') === 'fixed')>Fixed — single departure time</option>
                <option value="slots" @selected(old('departure_time_mode', $propertyDetails['departure_time_mode'] ?? '') === 'slots')>Slots — customers pick departure time</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Departure Time (fixed)</label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time', $propertyDetails['departure_time'] ?? '') }}">
            <p class="map-help">Used when type is Fixed.</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_departure_slots">Departure Time Slots <span style="font-weight:400;">(one per line, 24h format e.g. 08:00)</span></label>
            <textarea id="property_departure_slots" name="departure_slots" class="ops-textarea" rows="4" placeholder="08:00&#10;09:00&#10;10:00&#10;11:00">{{ old('departure_slots', implode("\n", (array) ($propertyDetails['departure_slots'] ?? []))) }}</textarea>
            <p class="map-help">Used when type is Slots. Enter one time per line.</p>
        </div>
        <div class="ops-field">
            <label for="property_return_time_mode">Return Time Type</label>
            <select id="property_return_time_mode" name="return_time_mode" class="ops-select">
                <option value="fixed" @selected(old('return_time_mode', $propertyDetails['return_time_mode'] ?? 'fixed') === 'fixed')>Fixed — single return time</option>
                <option value="slots" @selected(old('return_time_mode', $propertyDetails['return_time_mode'] ?? '') === 'slots')>Slots — customers pick return time</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_return_time">Return Time (fixed)</label>
            <input id="property_return_time" name="return_time" class="ops-input" type="time" value="{{ old('return_time', $propertyDetails['return_time'] ?? '') }}">
            <p class="map-help">Used when type is Fixed.</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_return_slots">Return Time Slots <span style="font-weight:400;">(one per line, 24h format e.g. 17:30)</span></label>
            <textarea id="property_return_slots" name="return_slots" class="ops-textarea" rows="4" placeholder="17:30&#10;18:30&#10;20:30&#10;22:30">{{ old('return_slots', implode("\n", (array) ($propertyDetails['return_slots'] ?? []))) }}</textarea>
            <p class="map-help">Used when type is Slots. Enter one time per line.</p>
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
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit resort pin"></div></div>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
        </div>

        {{-- ── Schedule / Itinerary ──────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Schedule / Itinerary</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_activity_schedule">Day Programme <span style="font-weight:400;">(optional)</span></label>
            <p class="ops-field-hint">Enter each programme item on a new line. Guests will see this as their day overview.</p>
            <textarea id="property_activity_schedule" name="activity_schedule" class="ops-textarea" rows="6" maxlength="5000" placeholder="09:00 Arrival & check-in&#10;10:00 Beach access&#10;12:30 Lunch&#10;15:00 Water activities&#10;17:00 Departure">{{ old('activity_schedule', $propertyDetails['activity_schedule'] ?? '') }}</textarea>
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
                    <small>Per guest day-pass, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/resort_day_visit">Cancel</a>
    </div>
</form>
