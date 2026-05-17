{{-- Standalone create form: Conference Room --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="conference_room">

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
            <label for="property_conference_room_type">Room Type</label>
            <select id="property_conference_room_type" name="conference_room_type" class="ops-select">
                <option value="">Select type</option>
                <option value="boardroom" @selected(old('conference_room_type') === 'boardroom')>Boardroom</option>
                <option value="classroom" @selected(old('conference_room_type') === 'classroom')>Classroom Style</option>
                <option value="theatre" @selected(old('conference_room_type') === 'theatre')>Theatre / Auditorium</option>
                <option value="u_shape" @selected(old('conference_room_type') === 'u_shape')>U-Shape</option>
                <option value="banquet" @selected(old('conference_room_type') === 'banquet')>Banquet</option>
                <option value="cocktail" @selected(old('conference_room_type') === 'cocktail')>Cocktail / Reception</option>
                <option value="hybrid" @selected(old('conference_room_type') === 'hybrid')>Hybrid / Multi-use</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_conference_min_booking_hours">Minimum Booking (hours)</label>
            <input id="property_conference_min_booking_hours" name="conference_min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('conference_min_booking_hours', 2) }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Seating Capacity</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}" placeholder="Max number of attendees">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">Number of Rooms</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value', 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_catering_available">Catering Available?</label>
            <select id="property_catering_available" name="catering_available" class="ops-select">
                <option value="0" @selected(!old('catering_available'))>No</option>
                <option value="1" @selected((bool) old('catering_available'))>Yes</option>
            </select>
        </div>

        {{-- AV equipment --}}
        <div class="ops-field ops-field-wide">
            <label>AV / Technical Equipment (tick all available)</label>
            <div class="feature-checklist">
                @foreach (['projector' => 'Projector', 'led_screen' => 'LED Screen', 'video_conferencing' => 'Video Conferencing', 'microphone' => 'Microphone / PA System', 'whiteboard' => 'Whiteboard', 'flipchart' => 'Flip Chart', 'wifi_fiber' => 'Dedicated Fibre Wi-Fi', 'recording_equipment' => 'Recording Equipment', 'stage' => 'Stage / Podium'] as $avKey => $avLabel)
                    <label class="feature-item">
                        <input type="checkbox" name="av_equipment[]" value="{{ $avKey }}" @checked(in_array($avKey, old('av_equipment', []), true))>
                        {{ $avLabel }}
                    </label>
                @endforeach
            </div>
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
            <label for="location_state">Atoll / State / Province</label>
            <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                <option value="">Select atoll/state</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island / City</label>
            <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                <option value="">Select island/city</option>
            </select>
        </div>
        <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
        <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
        <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div id="propertyMap" aria-label="Pin venue location"></div></div>
            <p class="map-help">Click to pin your venue location.</p>
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
                    <small>Per hour, half-day, or booking slot, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/conference_room">Cancel</a>
    </div>
</form>
