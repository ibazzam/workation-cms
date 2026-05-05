{{-- Standalone create form: Excursion --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="excursion">
    <input type="hidden" name="base_price" value="0">

    <div class="ops-form-grid">

        {{-- ── Basic Information ─────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Basic Information</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_name">Activity Name <span style="color:#c0392b;">*</span></label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required placeholder="e.g. Sunrise Snorkelling at Manta Point">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_short_description">Short Tagline <span style="font-weight:400; color:#5b7488;">(shown on listing card — max 160 chars)</span></label>
            <input id="property_short_description" name="short_description" class="ops-input" type="text" maxlength="160" value="{{ old('short_description') }}" placeholder="One-line summary displayed on the excursion card">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Full Description <span style="color:#c0392b;">*</span></label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" rows="5" required placeholder="Describe the experience: itinerary, highlights, what guests will see...">{{ old('description') }}</textarea>
        </div>

        {{-- ── Activity Details ──────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Activity Details</p>
        </div>
        <div class="ops-field">
            <label for="property_excursion_type">Excursion Type</label>
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
            <label for="property_excursion_difficulty">Difficulty Level</label>
            <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                <option value="">Select</option>
                <option value="easy" @selected(old('excursion_difficulty') === 'easy')>Easy — suitable for all ages</option>
                <option value="moderate" @selected(old('excursion_difficulty') === 'moderate')>Moderate — some fitness required</option>
                <option value="hard" @selected(old('excursion_difficulty') === 'hard')>Challenging — active participants</option>
                <option value="extreme" @selected(old('excursion_difficulty') === 'extreme')>Extreme — experienced only</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_excursion_duration_minutes">Duration (minutes)</label>
            <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="15" step="15" value="{{ old('excursion_duration_minutes') }}" placeholder="e.g. 120">
        </div>
        <div class="ops-field">
            <label for="property_activity_start_time">Activity Start Time</label>
            <input id="property_activity_start_time" name="activity_start_time" class="ops-input" type="time" value="{{ old('activity_start_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_activity_end_time">Activity End Time</label>
            <input id="property_activity_end_time" name="activity_end_time" class="ops-input" type="time" value="{{ old('activity_end_time') }}">
        </div>

        {{-- ── Departure & Assembly Point ────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Departure & Assembly Point</p>
        </div>

        {{-- ── Participants & Capacity ───────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Participants & Capacity</p>
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
            <label for="property_max_guests">Overall Capacity (max guests)</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>

        {{-- Departure fields (under Departure & Assembly section rendered above) --}}
        <div class="ops-field ops-field-wide">
            <label for="property_departure_point">Reporting / Departure Location</label>
            <input id="property_departure_point" name="departure_point" class="ops-input" type="text" maxlength="255" value="{{ old('departure_point') }}" placeholder="e.g. Male' Jetty Gate 3, Hotel lobby pickup">
            <p class="map-help">Shown on the excursion detail page under "Departure & Assembly Point".</p>
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Standard Departure Time</label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
            <p class="map-help">Default departure; guests can confirm at checkout.</p>
        </div>
        <div class="ops-field">
            <label for="property_service_radius_km">Service Radius (km)</label>
            <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" step="0.1" value="{{ old('service_radius_km') }}" placeholder="0">
        </div>

        {{-- ── Transfer & Slot Configuration ────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Transfer & Slot Configuration</p>
        </div>
        <div class="ops-field">
            <label for="property_transfer_included">Transfer Included in Price?</label>
            <select id="property_transfer_included" name="transfer_included" class="ops-select">
                <option value="0" @selected(!old('transfer_included'))>No — guests arrange own transfer</option>
                <option value="1" @selected((bool) old('transfer_included'))>Yes — transfer included in activity price</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_departure_time_mode">Departure Time Type</label>
            <select id="property_departure_time_mode" name="departure_time_mode" class="ops-select">
                <option value="fixed" @selected(old('departure_time_mode', 'fixed') === 'fixed')>Fixed — single departure time</option>
                <option value="slots" @selected(old('departure_time_mode') === 'slots')>Slots — customers pick departure time</option>
            </select>
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
        <div class="ops-field ops-field-wide">
            <label for="property_return_slots">Return Time Slots <span style="font-weight:400;">(one per line, 24h format e.g. 17:30)</span></label>
            <textarea id="property_return_slots" name="return_slots" class="ops-textarea" rows="4" placeholder="17:30&#10;18:30&#10;20:30&#10;22:30">{{ old('return_slots') }}</textarea>
            <p class="map-help">Used when type is Slots. Enter one time per line.</p>
        </div>

        <div class="ops-field ops-field-wide" style="grid-column:1/-1;">
            <section class="listing-form-section" aria-label="Pricing by residency">
                <div class="listing-form-section-head">
                    <h4>Pricing by Residency</h4>
                    <p>Set adult and child fares for local and foreign guests.</p>
                </div>
                <p class="listing-form-note">Use this matrix when pricing differs by residency segment.</p>
                <div class="listing-transfer-table">
                    <div class="listing-transfer-head" aria-hidden="true">
                        <span>Rate Type</span>
                        <span>Local Adult</span>
                        <span>Local Child</span>
                        <span>Foreigner Adult</span>
                        <span>Foreigner Child</span>
                    </div>
                    <div class="listing-transfer-row">
                        <div class="listing-transfer-option">
                            <label><span>Activity Fare</span></label>
                            <small>Per person rate for this excursion.</small>
                        </div>
                        <label class="listing-transfer-rate">
                            <span>Local Adult</span>
                            <input id="property_adult_price_local" name="adult_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_local') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Local Child</span>
                            <input id="property_child_price_local" name="child_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_local') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Adult</span>
                            <input id="property_adult_price_foreign" name="adult_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('adult_price_foreign') }}" placeholder="MVR 0.00">
                        </label>
                        <label class="listing-transfer-rate">
                            <span>Foreigner Child</span>
                            <input id="property_child_price_foreign" name="child_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_foreign') }}" placeholder="MVR 0.00">
                        </label>
                    </div>
                </div>
            </section>
        </div>

        {{-- ── Inclusions & Exclusions ───────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Inclusions & Exclusions</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_inclusions">What’s Included</label>
            <textarea id="property_inclusions" name="inclusions" class="ops-textarea" rows="3" maxlength="2000">{{ old('inclusions') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_exclusions">What’s Not Included</label>
            <textarea id="property_exclusions" name="exclusions" class="ops-textarea" rows="3" maxlength="2000" placeholder="e.g. Personal insurance, hotel transfer, gratuity">{{ old('exclusions') }}</textarea>
        </div>

        {{-- ── Safety & Equipment ────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Safety & Equipment</p>
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
            <textarea id="property_weather_cancellation_policy" name="weather_cancellation_policy" class="ops-textarea" rows="2" maxlength="1000" placeholder="e.g. Full refund if cancelled due to adverse weather conditions">{{ old('weather_cancellation_policy') }}</textarea>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_special_instructions">Special Instructions</label>
            <textarea id="property_special_instructions" name="special_instructions" class="ops-textarea" rows="3" maxlength="2000" placeholder="What guests should bring, when to arrive, dress code, or any important pre-trip notes.">{{ old('special_instructions') }}</textarea>
        </div>

        {{-- ── Location ──────────────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Location</p>
        </div>
        <div class="ops-field">
            <label for="location_country">Country <span style="color:#c0392b;">*</span></label>
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
            <div class="map-picker"><div id="propertyMap" aria-label="Pin excursion departure location"></div></div>
            <p class="map-help">Click to pin the departure / assembly point coordinates.</p>
        </div>

        {{-- ── Booking Policies ──────────────────────────────────────────── --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Booking Policies</p>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="e.g. Free cancellation up to 24 hours before departure">{{ old('cancellation_policy') }}</textarea>
        </div>
    </div>

    <div class="inline-actions" style="margin-top:16px;">
        <button class="btn btn-primary" type="submit">Save Listing</button>
        <a class="btn btn-secondary" href="/vendor/listings/excursion">Cancel</a>
    </div>
</form>

<script>
(() => {
    const startInput = document.getElementById('property_activity_start_time');
    const endInput = document.getElementById('property_activity_end_time');
    const durationInput = document.getElementById('property_excursion_duration_minutes');
    if (!startInput || !endInput || !durationInput) {
        return;
    }

    const parseMinutes = (value) => {
        const match = /^(\d{2}):(\d{2})$/.exec(String(value || '').trim());
        if (!match) {
            return null;
        }
        const hours = Number(match[1]);
        const minutes = Number(match[2]);
        if (!Number.isFinite(hours) || !Number.isFinite(minutes) || hours > 23 || minutes > 59) {
            return null;
        }
        return (hours * 60) + minutes;
    };

    const syncDuration = () => {
        const start = parseMinutes(startInput.value);
        const end = parseMinutes(endInput.value);
        if (start === null || end === null) {
            return;
        }

        let duration = end - start;
        if (duration <= 0) {
            duration += 24 * 60;
        }
        if (duration > 0) {
            durationInput.value = String(duration);
        }
    };

    startInput.addEventListener('change', syncDuration);
    endInput.addEventListener('change', syncDuration);
    startInput.addEventListener('input', syncDuration);
    endInput.addEventListener('input', syncDuration);
    syncDuration();
})();
</script>
