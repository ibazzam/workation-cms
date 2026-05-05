{{-- Standalone edit form: Restaurant --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update">
    @csrf
    <input type="hidden" name="listing_category" value="restaurant">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Restaurant Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name', $property->name ?? '') }}" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description', $property->description ?? '') }}</textarea>
        </div>
        <div class="ops-field">
            <label for="property_cuisine_type">Cuisine Type</label>
            <input id="property_cuisine_type" name="cuisine_type" class="ops-input" type="text" maxlength="120" value="{{ old('cuisine_type', $propertyDetails['cuisine_type'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_price_range">Price Range</label>
            <select id="property_price_range" name="price_range" class="ops-select">
                <option value="">Select</option>
                @foreach (['budget' => 'Budget (MVR 0–200/pax)', 'mid' => 'Mid-range (MVR 200–600/pax)', 'upscale' => 'Upscale (MVR 600+/pax)', 'fine_dining' => 'Fine Dining'] as $prVal => $prLabel)
                    <option value="{{ $prVal }}" @selected(old('price_range', $propertyDetails['price_range'] ?? '') === $prVal)>{{ $prLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="ops-field">
            <label for="property_seating_capacity">Seating Capacity</label>
            <input id="property_seating_capacity" name="seating_capacity" class="ops-input" type="number" min="1" value="{{ old('seating_capacity', $propertyDetails['seating_capacity'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Guests per Sitting</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests', $propertyDetails['max_guests'] ?? '') }}">
        </div>
        <div class="ops-field">
            <label for="property_restaurant_open_time">Opens At</label>
            <input id="property_restaurant_open_time" name="restaurant_open_time" class="ops-input" type="time" value="{{ old('restaurant_open_time', $propertyDetails['restaurant_open_time'] ?? '11:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_restaurant_close_time">Closes At</label>
            <input id="property_restaurant_close_time" name="restaurant_close_time" class="ops-input" type="time" value="{{ old('restaurant_close_time', $propertyDetails['restaurant_close_time'] ?? '22:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_booking_required">Booking Required?</label>
            <select id="property_booking_required" name="booking_required" class="ops-select">
                <option value="0" @selected(!(bool) old('booking_required', $propertyDetails['booking_required'] ?? false))>Walk-ins welcome</option>
                <option value="1" @selected((bool) old('booking_required', $propertyDetails['booking_required'] ?? false))>Advance booking required</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_dress_code">Dress Code</label>
            <input id="property_dress_code" name="dress_code" class="ops-input" type="text" maxlength="120" value="{{ old('dress_code', $propertyDetails['dress_code'] ?? '') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label>Meal Services Offered</label>
            @php $savedMealService = is_array($propertyDetails['meal_service'] ?? null) ? $propertyDetails['meal_service'] : []; @endphp
            <div class="feature-checklist">
                @forelse ($restaurantMealServiceOptionsCollection as $mealOption)
                    @php $mealValue = trim((string) ($mealOption['value'] ?? '')); @endphp
                    @if ($mealValue !== '')
                        <label class="feature-item">
                            <input type="checkbox" name="meal_service[]" value="{{ $mealValue }}" @checked(in_array($mealValue, old('meal_service', $savedMealService), true))>
                            {{ $mealOption['label'] ?? $mealValue }}
                        </label>
                    @endif
                @empty
                    <p class="small">No meal service options configured.</p>
                @endforelse
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
            <label for="location_state">Atoll / Province</label>
            <select id="location_state" name="location_state" class="ops-select" data-edit-state data-selected-value="{{ old('location_state', $propertyDetails['location_state'] ?? '') }}" required>
                <option value="">Select atoll</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="location_city">Island / City</label>
            <select id="location_city" name="location_city" class="ops-select" data-edit-city data-selected-value="{{ old('location_city', $propertyDetails['location_city'] ?? '') }}" required>
                <option value="">Select island</option>
            </select>
        </div>
        <input name="map_latitude" type="hidden" value="{{ old('map_latitude', $propertyDetails['map_latitude'] ?? '') }}">
        <input name="map_longitude" type="hidden" value="{{ old('map_longitude', $propertyDetails['map_longitude'] ?? '') }}">
        <input name="map_place_id" type="hidden" value="{{ old('map_place_id', $propertyDetails['map_place_id'] ?? '') }}">
        <div class="ops-field ops-field-wide">
            <div class="map-picker"><div data-edit-map-wrap aria-label="Edit restaurant pin"></div></div>
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
                    <small>Per guest, meal package, or table booking, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/restaurant">Cancel</a>
    </div>
</form>
