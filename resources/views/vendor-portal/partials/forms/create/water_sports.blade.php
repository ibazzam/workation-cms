{{-- Standalone create form: Water Sports --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="water_sports">

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
            <label for="property_excursion_type">Activity Type</label>
            <select id="property_excursion_type" name="excursion_type" class="ops-select">
                <option value="">Select type</option>
                @foreach ($excursionTypeOptionsCollection as $typeOption)
                    @php $typeValue = trim((string) ($typeOption['value'] ?? '')); @endphp
                    @if ($typeValue !== '')
                        <option value="{{ $typeValue }}" @selected(old('excursion_type') === $typeValue)>{{ $typeOption['label'] ?? $typeValue }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_excursion_difficulty">Difficulty</label>
            <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                <option value="">Select</option>
                <option value="easy" @selected(old('excursion_difficulty') === 'easy')>Easy</option>
                <option value="moderate" @selected(old('excursion_difficulty') === 'moderate')>Moderate</option>
                <option value="hard" @selected(old('excursion_difficulty') === 'hard')>Challenging</option>
                <option value="extreme" @selected(old('excursion_difficulty') === 'extreme')>Extreme</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_excursion_duration_minutes">Duration (minutes)</label>
            <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="1" value="{{ old('excursion_duration_minutes') }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_min_pax">Min Participants</label>
            <input id="property_excursion_min_pax" name="excursion_min_pax" class="ops-input" type="number" min="1" value="{{ old('excursion_min_pax', 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_max_pax">Max Participants</label>
            <input id="property_excursion_max_pax" name="excursion_max_pax" class="ops-input" type="number" min="1" value="{{ old('excursion_max_pax') }}">
        </div>
        <div class="ops-field">
            <label for="property_excursion_min_age">Minimum Age</label>
            <input id="property_excursion_min_age" name="excursion_min_age" class="ops-input" type="number" min="0" max="120" value="{{ old('excursion_min_age') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Guests (overall)</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_service_radius_km">Service Radius (km)</label>
            <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" step="0.1" value="{{ old('service_radius_km') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_meeting_point">Meeting Point</label>
            <input id="property_meeting_point" name="meeting_point" class="ops-input" type="text" maxlength="200" value="{{ old('meeting_point') }}" placeholder="e.g. Hotel jetty, Beach entry at north end">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_inclusions">Inclusions</label>
            <textarea id="property_inclusions" name="inclusions" class="ops-textarea" rows="3" maxlength="2000" placeholder="Equipment, instructor, snacks…">{{ old('inclusions') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_exclusions">Exclusions</label>
            <textarea id="property_exclusions" name="exclusions" class="ops-textarea" rows="3" maxlength="2000" placeholder="Flights, transfers, meals not listed…">{{ old('exclusions') }}</textarea>
        </div>
        <div class="ops-field">
            <label for="property_safety_waiver_required">Safety Waiver Required?</label>
            <select id="property_safety_waiver_required" name="safety_waiver_required" class="ops-select">
                <option value="0" @selected(!old('safety_waiver_required'))>No</option>
                <option value="1" @selected((bool) old('safety_waiver_required'))>Yes</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_equipment_rental_available">Equipment Rental Available?</label>
            <select id="property_equipment_rental_available" name="equipment_rental_available" class="ops-select">
                <option value="0" @selected(!old('equipment_rental_available'))>No</option>
                <option value="1" @selected((bool) old('equipment_rental_available'))>Yes</option>
            </select>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_weather_cancellation_policy">Weather Cancellation Policy</label>
            <textarea id="property_weather_cancellation_policy" name="weather_cancellation_policy" class="ops-textarea" rows="2" maxlength="1000" placeholder="We cancel if wind exceeds 30 knots. Full refund given.">{{ old('weather_cancellation_policy') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_special_instructions">Special Instructions</label>
            <textarea id="property_special_instructions" name="special_instructions" class="ops-textarea" rows="3" maxlength="2000" placeholder="What guests should bring, what to wear, when to arrive, or any safety prep notes.">{{ old('special_instructions') }}</textarea>
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
            <div class="map-picker"><div id="propertyMap" aria-label="Pin activity location"></div></div>
            <p class="map-help">Click to pin the activity location / entry point.</p>
        </div>

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy') }}</textarea>
        </div>

        {{-- ── Schedule / Itinerary ──────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Schedule / Itinerary</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_activity_schedule">Activity Schedule <span style="font-weight:400;">(optional)</span></label>
            <p class="ops-field-hint">Enter each schedule item on a new line, e.g. &ldquo;09:00 Safety briefing&rdquo;, &ldquo;09:30 Session begins&rdquo;. Guests will see this as a programme overview.</p>
            <textarea id="property_activity_schedule" name="activity_schedule" class="ops-textarea" rows="6" maxlength="5000" placeholder="09:00 Safety briefing&#10;09:30 Session begins&#10;11:30 Wrap-up">{{ old('activity_schedule') }}</textarea>
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
                    <small>Per guest or per activity session, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/water_sports">Cancel</a>
    </div>
</form>
