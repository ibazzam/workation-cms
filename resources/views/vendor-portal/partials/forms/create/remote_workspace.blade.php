{{-- Standalone create form: Remote Workspace --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="remote_workspace">
    <input name="area_unit" type="hidden" value="sqft">
    <input name="measurement_system" type="hidden" value="imperial">

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
            <label for="property_workspace_type">Workspace Type</label>
            <select id="property_workspace_type" name="workspace_type" class="ops-select">
                <option value="">Select type</option>
                <option value="private_office" @selected(old('workspace_type') === 'private_office')>Private Office</option>
                <option value="coworking" @selected(old('workspace_type') === 'coworking')>Co-working Space</option>
                <option value="meeting_room" @selected(old('workspace_type') === 'meeting_room')>Meeting Room</option>
                <option value="desk_hot" @selected(old('workspace_type') === 'desk_hot')>Hot Desk</option>
                <option value="library" @selected(old('workspace_type') === 'library')>Library / Reading Room</option>
                <option value="pod" @selected(old('workspace_type') === 'pod')>Quiet Pod</option>
            </select>
        </div>
        <div class="ops-field">
            <label for="property_internet_speed_mbps">Internet Speed (Mbps)</label>
            <input id="property_internet_speed_mbps" name="internet_speed_mbps" class="ops-input" type="number" min="0" step="0.1" value="{{ old('internet_speed_mbps') }}" placeholder="e.g. 100">
        </div>
        <div class="ops-field">
            <label for="property_min_booking_hours">Minimum Booking (hours)</label>
            <input id="property_min_booking_hours" name="min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('min_booking_hours', 1) }}">
        </div>
        <div class="ops-field">
            <label for="property_operating_hours_open">Opens At</label>
            <input id="property_operating_hours_open" name="operating_hours_open" class="ops-input" type="time" value="{{ old('operating_hours_open', '08:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_operating_hours_close">Closes At</label>
            <input id="property_operating_hours_close" name="operating_hours_close" class="ops-input" type="time" value="{{ old('operating_hours_close', '20:00') }}">
        </div>
        <div class="ops-field">
            <label for="property_max_guests">Max Occupants</label>
            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" value="{{ old('max_guests') }}">
        </div>
        <div class="ops-field">
            <label for="property_area_value">Area (sqft)</label>
            <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" step="0.01" value="{{ old('area_value') }}">
        </div>

        {{-- Workspace amenities --}}
        <div class="ops-field ops-field-wide">
            <label>Included Amenities</label>
            <div class="feature-checklist">
                @foreach ($workspaceAmenityCatalog as $amenityKey => $amenityLabel)
                    <label class="feature-item">
                        <input type="checkbox" name="workspace_amenities_free[]" value="{{ $amenityKey }}" @checked(in_array($amenityKey, old('workspace_amenities_free', []), true))>
                        {{ $amenityLabel }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="ops-field ops-field-wide">
            <label>Paid Add-on Amenities</label>
            <div class="feature-checklist">
                @foreach ($workspaceAmenityCatalog as $amenityKey => $amenityLabel)
                    <label class="feature-item">
                        <input type="checkbox" name="workspace_amenities_paid[]" value="{{ $amenityKey }}" @checked(in_array($amenityKey, old('workspace_amenities_paid', []), true))>
                        {{ $amenityLabel }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Transfer options (workspace can have them) --}}
        <div class="ops-field ops-field-wide">
            <label>Transfer Options (optional)</label>
            <div class="ops-form-grid">
                @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                    <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, old('transfer_options', []), true))>
                        <span>{{ $transferOptionLabel }}</span>
                    </label>
                    <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_local_adult.' . $transferOptionKey) }}" placeholder="Local adult (MVR)">
                    <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_rates_foreign_adult.' . $transferOptionKey) }}" placeholder="Foreign adult (MVR)">
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
            <div class="map-picker"><div id="propertyMap" aria-label="Pin workspace location"></div></div>
            <p class="map-help">Click to pin your workspace location.</p>
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
                    <small>Per desk, room, or booking block, based on this listing.</small>
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
        <a class="btn btn-secondary" href="/vendor/listings/remote_workspace">Cancel</a>
    </div>
</form>
