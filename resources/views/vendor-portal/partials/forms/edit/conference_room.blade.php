{{-- Standalone edit form: Conference Room --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="conference_room">

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
            <label for="property_conference_room_type">Room Configuration</label>
            <select id="property_conference_room_type" name="conference_room_type" class="ops-select">
                <option value="">Select</option>
                @foreach (['boardroom' => 'Boardroom', 'classroom' => 'Classroom Setup', 'theatre' => 'Theatre Style', 'u_shape' => 'U-Shape', 'banquet' => 'Banquet Style', 'cocktail' => 'Cocktail / Standing', 'flexible' => 'Flexible / Custom'] as $crVal => $crLabel)
                    <option value="{{ $crVal }}" @selected(old('conference_room_type', $propertyDetails['conference_room_type'] ?? '') === $crVal)>{{ $crLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_conference_min_booking_hours">Minimum Booking (hours)</label>
            <input id="property_conference_min_booking_hours" name="conference_min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('conference_min_booking_hours', $propertyDetails['conference_min_booking_hours'] ?? 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Seating</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_capacity_value">No. of Rooms Available</label>
            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" value="{{ old('capacity_value', $propertyDetails['capacity_value'] ?? 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_catering_available">Catering Available?</label>
            <select id="property_catering_available" name="catering_available" class="ops-select">
                <option value="0" @selected(!old('catering_available', $propertyDetails['catering_available'] ?? false))>No</option>
                <option value="1" @selected((bool) old('catering_available', $propertyDetails['catering_available'] ?? false))>Yes</option>
            </select>
        </div>
        <div class="ops-field ops-field-wide">
            <label>AV Equipment</label>
            @php
                $savedAvEquipment = is_array($propertyDetails['av_equipment'] ?? null) ? $propertyDetails['av_equipment'] : [];
                $avEquipmentOptions = [
                    'projector' => 'Projector',
                    'screen' => 'Projection Screen',
                    'led_tv' => 'LED TV / Monitor',
                    'video_conferencing' => 'Video Conferencing System',
                    'microphone' => 'Microphone / PA System',
                    'whiteboard' => 'Whiteboard',
                    'flipchart' => 'Flipchart / Easel',
                    'laser_pointer' => 'Laser Pointer / Presentation Remote',
                    'hdmi_vga' => 'HDMI / VGA Adapters',
                ];
            @endphp
            <div class="feature-checklist">
                @foreach ($avEquipmentOptions as $avKey => $avLabel)
                    <label class="feature-item"><input type="checkbox" name="av_equipment[]" value="{{ $avKey }}" @checked(in_array($avKey, old('av_equipment', $savedAvEquipment), true))> {{ $avLabel }}</label>
                @endforeach
            </div>
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
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit room location pin"></div></div>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Cancellation Policy</label>
            <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000">{{ old('cancellation_policy', $propertyDetails['cancellation_policy'] ?? '') }}</textarea>
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
                    <small>Per hour, half-day, or booking slot, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/conference_room">Cancel</a>
    </div>
</form>
