{{-- Standalone create form: Restaurant --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="restaurant">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Restaurant Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
        </div>

        <div class="ops-field">
            <label for="property_cuisine_type">Cuisine Type</label>
            <input id="property_cuisine_type" name="cuisine_type" class="ops-input" type="text" maxlength="120" value="{{ old('cuisine_type') }}" placeholder="e.g. Maldivian, Italian, International">
        </div>
        <div class="ops-field">
            <label for="property_price_range">Price Range</label>
            <select id="property_price_range" name="price_range" class="ops-select">
                <option value="">Select</option>
                <option value="budget" @selected(old('price_range') === 'budget')>Budget (MVR 0–200/pax)</option>
                <option value="mid" @selected(old('price_range') === 'mid')>Mid-range (MVR 200–600/pax)</option>
                <option value="upscale" @selected(old('price_range') === 'upscale')>Upscale (MVR 600+/pax)</option>
                <option value="fine_dining" @selected(old('price_range') === 'fine_dining')>Fine Dining</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_seating_capacity">Seating Capacity</label>
            <input id="property_seating_capacity" name="seating_capacity" class="ops-input" type="number" min="1" value="{{ old('seating_capacity') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Guests per Sitting</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_restaurant_open_time">Opens At</label>
            <input id="property_restaurant_open_time" name="restaurant_open_time" class="ops-input" type="time" value="{{ old('restaurant_open_time', '11:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_restaurant_close_time">Closes At</label>
            <input id="property_restaurant_close_time" name="restaurant_close_time" class="ops-input" type="time" value="{{ old('restaurant_close_time', '22:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_booking_required">Booking Required?</label>
            <select id="property_booking_required" name="booking_required" class="ops-select">
                <option value="0" @selected(!old('booking_required'))>Walk-ins welcome</option>
                <option value="1" @selected((bool) old('booking_required'))>Advance booking required</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_dress_code">Dress Code</label>
            <input id="property_dress_code" name="dress_code" class="ops-input" type="text" maxlength="120" value="{{ old('dress_code') }}" placeholder="Smart casual, formal… or leave blank">
        </div>
        <div class="ops-field ops-field-wide">
            <label>Meal Services Offered</label>
            <div class="feature-checklist">
                @forelse ($restaurantMealServiceOptionsCollection as $mealOption)
                    @php $mealValue = trim((string) ($mealOption['value'] ?? '')); @endphp
                    @if ($mealValue !== '')
                        <label class="feature-item">
                            <input type="checkbox" name="meal_service[]" value="{{ $mealValue }}" @checked(in_array($mealValue, old('meal_service', []), true))>
                            {{ $mealOption['label'] ?? $mealValue }}
                        </label>
                    @endif
                @empty
                    <p class="small">No meal service options configured.</p>
                @endforelse
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
            <div class="map-picker"><div id="propertyMap" aria-label="Pin restaurant location"></div></div>
            <p class="map-help">Click to pin your restaurant location.</p>
        </div>

        <div class="ops-field ops-field-wide">
            <label for="property_cancellation_policy">Booking Cancellation Policy</label>
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
                    <small>Per guest, meal package, or table booking, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/restaurant">Cancel</a>
    </div>
</form>
