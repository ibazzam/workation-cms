<form id="propertyCreateForm" class="ops-form" method="POST" action="/portal/vendor/properties/create" @if (!$showCreatePropertyForm) hidden @endif>
                        @csrf
                        <input type="hidden" name="property_form_intent" value="1">
                        <p class="guided-wizard-title" id="propertyCreateFormTitle">Create Listing</p>
                        <p class="guided-wizard-subtitle" id="propertyCreateFormSubtitle">Complete each section in order, then save.</p>
                        <div class="ops-form-grid">
                            @php
                                $isCategoryScopedListingPage = is_string($forcedListingCategory ?? null)
                                    && trim((string) $forcedListingCategory) !== ''
                                    && in_array((string) $forcedListingCategory, $listingCategoryViewOrder, true);
                                $defaultCreateCategory = old('listing_category');
                                if (!is_string($defaultCreateCategory) || trim($defaultCreateCategory) === '') {
                                    $defaultCreateCategory = in_array($forcedListingCategory, ['sea_transport', 'marine_transport', 'land_transport'], true)
                                        ? 'transport'
                                        : ($forcedListingCategory !== '' ? $forcedListingCategory : null);
                                }
                                if (!is_string($defaultCreateCategory) || trim($defaultCreateCategory) === '') {
                                    $defaultCreateCategory = in_array('accommodation', $selectedVendorCategories, true)
                                        ? 'accommodation'
                                        : ((string) ($selectedVendorCategories[0] ?? 'accommodation'));
                                }
                            @endphp
                            <div class="ops-field" style="display:none;">
                                <select id="property_listing_category" name="listing_category" class="ops-select" data-default-category="{{ $defaultCreateCategory }}">
                                    @php
                                        $formCategoryOptions = $isCategoryScopedListingPage
                                            ? [vendorPortalCanonicalCategory((string) $forcedListingCategory)]
                                            : $selectedVendorCategories;
                                    @endphp
                                    @foreach ($formCategoryOptions as $categoryKey)
                                        @php
                                            $categoryLabel = $listingCategoryLabelMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', (string) $categoryKey));
                                        @endphp
                                        <option value="{{ $categoryKey }}" @selected($defaultCreateCategory === $categoryKey)>{{ $categoryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_name">Listing Name</label>
                                <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_country">Country</label>
                                <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', 'Maldives') }}" required>
                                    <option value="Maldives" @selected(old('location_country', 'Maldives') === 'Maldives')>Maldives</option>
                                    <option value="Sri Lanka" @selected(old('location_country') === 'Sri Lanka')>Sri Lanka</option>
                                    <option value="India" @selected(old('location_country') === 'India')>India</option>
                                    <option value="Other" @selected(old('location_country') === 'Other')>Other</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_state">Atoll / State / Province</label>
                                <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                                    <option value="">Select atoll/state/state</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_city">Island / City</label>
                                <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                                    <option value="">Select island/city/city</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_ward">Ward</label>
                                <input id="location_ward" name="location_ward" class="ops-input" type="text" maxlength="120" value="{{ old('location_ward') }}" placeholder="Ward / Neighborhood">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_building_house_lot">Building / House / Lot No.</label>
                                <input id="property_building_house_lot" name="building_house_lot" class="ops-input" type="text" maxlength="160" value="{{ old('building_house_lot') }}" placeholder="e.g. Lily House, Lot 1142">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_street_name">Street</label>
                                <input id="property_street_name" name="street" class="ops-input" type="text" maxlength="160" value="{{ old('street') }}" placeholder="Street / Road">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_post_code">Post Code</label>
                                <input id="property_post_code" name="post_code" class="ops-input" type="text" maxlength="20" value="{{ old('post_code') }}" placeholder="Post code">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_name">Contact Name</label>
                                <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name') }}" placeholder="Contact Name">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_number">Contact Number</label>
                                <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number') }}" placeholder="Phone / WhatsApp">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_email">Property Contact Email</label>
                                <input id="property_contact_email" name="property_contact_email" class="ops-input" type="email" maxlength="190" value="{{ old('property_contact_email') }}" placeholder="property@example.com">
                            </div>
                            <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
                            <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
                            <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
                            <input id="property_base_price" name="base_price" type="hidden" value="0">
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_max_guests">Max Guests</label>
                                <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests') }}">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_description">Description</label>
                                <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
                            </div>

                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_area_value">Area Value (sqft)</label>
                                <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" max="100000" step="0.01" value="{{ old('area_value') }}" placeholder="e.g. 120">
                            </div>
                            <input name="area_unit" type="hidden" value="sqft">
                            <input name="measurement_system" type="hidden" value="imperial">
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_bedroom_count">Bedrooms</label>
                                <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_capacity_value">Capacity</label>
                                <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}" placeholder="seats, guests, units">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <p class="category-scope-note" style="margin:0;">Remote workspace booking fee is charged per guest in-app. Max Guests is the booking limit per reservation, and Capacity is total workspace seats/desks available. Extra items/services are shown for customer information and purchased separately on-site.</p>
                            </div>
                            <div class="ops-field" data-category-scope="service">
                                <label for="property_service_radius_km">Service Radius (km)</label>
                                <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" max="5000" step="0.1" value="{{ old('service_radius_km') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_mode">Transport Mode</label>
                                @php
                                    $transportModeOld = strtolower(trim((string) old('transport_mode', '')));
                                    $knownTransportModes = $transportModeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_transport_mode" name="transport_mode" class="ops-select" required>
                                    <option value="" @selected($transportModeOld === '')>Select transport mode</option>
                                    @if ($transportModeOld !== '' && !in_array($transportModeOld, $knownTransportModes, true))
                                        <option value="{{ $transportModeOld }}" selected>{{ ucfirst($transportModeOld) }} (existing)</option>
                                    @endif
                                    @foreach ($transportModeOptionGroups as $groupKey => $groupItems)
                                        @php
                                            $groupLabel = $groupKey === 'marine'
                                                ? 'Vessel / Marine'
                                                : ($groupKey === 'land' ? 'Vehicle / Land' : ucfirst(str_replace('_', ' ', (string) $groupKey)));
                                        @endphp
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($groupItems as $groupItem)
                                                @php
                                                    $groupValue = strtolower(trim((string) ($groupItem['value'] ?? '')));
                                                    $groupText = trim((string) ($groupItem['label'] ?? $groupValue));
                                                @endphp
                                                @if ($groupValue !== '' && $groupText !== '')
                                                    <option value="{{ $groupValue }}" @selected($transportModeOld === $groupValue)>{{ $groupText }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_vehicle_name">Vehicle / Vessel Name</label>
                                <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name') }}" placeholder="Sea Rider 01, Airport Van 3">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_registration_plate">Registration Plate No.</label>
                                <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="80" value="{{ old('registration_plate') }}" placeholder="AB-1234 / Vessel Reg ID">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_contact_name">Contact Name</label>
                                <input id="property_transport_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name') }}" placeholder="Dispatcher / Driver / Captain" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_contact_number">Contact Number</label>
                                <input id="property_transport_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number') }}" placeholder="+960 7xxxxxx" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_trip_type">Trip Type</label>
                                <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                                    <option value="" @selected(old('transport_trip_type') === null)>Select</option>
                                    <option value="one_way" @selected(old('transport_trip_type') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                    <option value="round_trip" @selected(old('transport_trip_type') === 'round_trip')>Round Trip</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_pickup_location">Pickup Location</label>
                                <input id="property_pickup_location" name="pickup_location" class="ops-input" type="text" maxlength="190" value="{{ old('pickup_location') }}" placeholder="Airport, Jetty, Hotel" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_dropoff_location">Dropoff Location</label>
                                <input id="property_dropoff_location" name="dropoff_location" class="ops-input" type="text" maxlength="190" value="{{ old('dropoff_location') }}" placeholder="Resort, Island, City center" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_state">Departure State / Atoll</label>
                                <input id="property_transport_departure_state" name="transport_departure_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_state') }}" placeholder="e.g. Kaafu Atoll" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_city">Departure City / Island</label>
                                <input id="property_transport_departure_city" name="transport_departure_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_city') }}" placeholder="e.g. Male" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_state">Arrival State / Atoll</label>
                                <input id="property_transport_arrival_state" name="transport_arrival_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_state') }}" placeholder="e.g. Alif Dhaal Atoll" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_city">Arrival City / Island</label>
                                <input id="property_transport_arrival_city" name="transport_arrival_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_city') }}" placeholder="e.g. Dhigurah" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_area_port_jetty">Departure Area / Port / Jetty</label>
                                <input id="property_departure_area_port_jetty" name="departure_area_port_jetty" class="ops-input" type="text" maxlength="190" value="{{ old('departure_area_port_jetty') }}" placeholder="Jetty / Harbor / Terminal" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_date">Departure Date</label>
                                <input id="property_departure_date" name="departure_date" class="ops-input" type="date" value="{{ old('departure_date') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_time">Departure Time</label>
                                <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_reporting_lead_minutes">Report Before Departure (minutes)</label>
                                <input id="property_reporting_lead_minutes" name="reporting_lead_minutes" class="ops-input" type="number" min="0" max="720" step="1" value="{{ old('reporting_lead_minutes') }}" placeholder="e.g. 15 or 20" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_trip_duration_minutes">Trip Duration Estimate (minutes)</label>
                                <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="5" max="1440" value="{{ old('trip_duration_minutes') }}" placeholder="e.g. 90" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_schedule_start_time">Operating Schedule Starts</label>
                                <input id="property_schedule_start_time" name="schedule_start_time" class="ops-input" type="time" value="{{ old('schedule_start_time') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_schedule_end_time">Operating Schedule Ends</label>
                                <input id="property_schedule_end_time" name="schedule_end_time" class="ops-input" type="time" value="{{ old('schedule_end_time') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_booking_cutoff_minutes">Booking Cutoff (minutes before departure)</label>
                                <input id="property_booking_cutoff_minutes" name="booking_cutoff_minutes" class="ops-input" type="number" min="0" max="10080" value="{{ old('booking_cutoff_minutes', 120) }}" placeholder="e.g. 120" required>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <label for="property_boarding_instructions">Boarding Instructions</label>
                                <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="2" maxlength="1000" placeholder="Where to wait, ID requirements, baggage check, boarding gate/jetty info..." required>{{ old('boarding_instructions') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_transport_pricing_model">Land Pricing Model</label>
                                <select id="property_transport_pricing_model" name="transport_pricing_model" class="ops-select">
                                    <option value="per_trip" @selected(old('transport_pricing_model', 'per_trip') === 'per_trip')>Per Trip</option>
                                    <option value="hourly" @selected(old('transport_pricing_model') === 'hourly')>Hourly Hire</option>
                                    <option value="daily" @selected(old('transport_pricing_model') === 'daily')>Daily Hire</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_hourly_rate">Hourly Rate (MVR)</label>
                                <input id="property_hourly_rate" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('hourly_rate') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_daily_rate">Daily Rate (MVR)</label>
                                <input id="property_daily_rate" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('daily_rate') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p id="transportPricingHint" class="category-scope-note" style="margin:0;">Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.</p>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p class="category-scope-note" style="margin:0;">Use entry only to enlist transport basics. Manage fixed daily schedules and seat availability in <a href="#vendorAvailabilitySection">Availability Calendar</a>, and manage price fluctuations in <a href="#vendorPricingSection">Pricing Rules</a>.</p>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_seat_class">Seat Class</label>
                                <select id="property_seat_class" name="seat_class" class="ops-select">
                                    <option value="" @selected(old('seat_class') === '')>Standard / Not Applicable</option>
                                    <option value="economy" @selected(old('seat_class') === 'economy')>Economy</option>
                                    <option value="business" @selected(old('seat_class') === 'business')>Business</option>
                                    <option value="first" @selected(old('seat_class') === 'first')>First Class</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_luggage_allowance_kg">Luggage Allowance (kg)</label>
                                <input id="property_luggage_allowance_kg" name="luggage_allowance_kg" class="ops-input" type="number" min="0" max="500" value="{{ old('luggage_allowance_kg') }}" placeholder="0 = no specific limit">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_duration_minutes">Duration (minutes)</label>
                                <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="30" max="1440" value="{{ old('excursion_duration_minutes') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_difficulty">Difficulty</label>
                                <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                                    <option value="" @selected(old('excursion_difficulty') === null)>Select</option>
                                    <option value="easy" @selected(old('excursion_difficulty') === 'easy')>Easy</option>
                                    <option value="moderate" @selected(old('excursion_difficulty') === 'moderate')>Moderate</option>
                                    <option value="hard" @selected(old('excursion_difficulty') === 'hard')>Hard</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_type">Excursion Type</label>
                                @php
                                    $excursionTypeOld = strtolower(trim((string) old('excursion_type', '')));
                                    $knownExcursionTypes = $excursionTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_excursion_type" name="excursion_type" class="ops-select" required>
                                    <option value="" @selected($excursionTypeOld === '')>Select</option>
                                    @if ($excursionTypeOld !== '' && !in_array($excursionTypeOld, $knownExcursionTypes, true))
                                        <option value="{{ $excursionTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $excursionTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($excursionTypeOptionsCollection as $excursionTypeOption)
                                        @php
                                            $excursionTypeValue = strtolower(trim((string) ($excursionTypeOption['value'] ?? '')));
                                            $excursionTypeLabel = trim((string) ($excursionTypeOption['label'] ?? $excursionTypeValue));
                                        @endphp
                                        @if ($excursionTypeValue !== '' && $excursionTypeLabel !== '')
                                            <option value="{{ $excursionTypeValue }}" @selected($excursionTypeOld === $excursionTypeValue)>{{ $excursionTypeLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_min_pax">Min. Participants</label>
                                <input id="property_excursion_min_pax" name="excursion_min_pax" class="ops-input" type="number" min="1" max="1000" value="{{ old('excursion_min_pax', 1) }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_max_pax">Max. Participants</label>
                                <input id="property_excursion_max_pax" name="excursion_max_pax" class="ops-input" type="number" min="1" max="1000" value="{{ old('excursion_max_pax') }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_min_age">Minimum Age</label>
                                <input id="property_excursion_min_age" name="excursion_min_age" class="ops-input" type="number" min="0" max="99" value="{{ old('excursion_min_age') }}" placeholder="e.g. 12 (leave blank if none)">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_meeting_point">Meeting Point</label>
                                <input id="property_meeting_point" name="meeting_point" class="ops-input" type="text" maxlength="255" value="{{ old('meeting_point') }}" placeholder="Hotel lobby, Jetty No. 3, Beach entrance…">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_inclusions">What's Included</label>
                                <textarea id="property_inclusions" name="inclusions" class="ops-textarea" rows="3" maxlength="2000" placeholder="Snorkelling gear, lunch, certified guide, hotel transfer…">{{ old('inclusions') }}</textarea>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_exclusions">Not Included</label>
                                <textarea id="property_exclusions" name="exclusions" class="ops-textarea" rows="2" maxlength="1000" placeholder="Flights, visa fees, personal expenses, tips…">{{ old('exclusions') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_safety_waiver_required">Safety Waiver Required</label>
                                <select id="property_safety_waiver_required" name="safety_waiver_required" class="ops-select" required>
                                    <option value="" @selected(old('safety_waiver_required') === '')>Select</option>
                                    <option value="yes" @selected(old('safety_waiver_required') === 'yes')>Yes</option>
                                    <option value="no" @selected(old('safety_waiver_required') === 'no')>No</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_equipment_rental_available">Equipment Rental Available</label>
                                <select id="property_equipment_rental_available" name="equipment_rental_available" class="ops-select">
                                    <option value="" @selected(old('equipment_rental_available') === '')>Select</option>
                                    <option value="yes" @selected(old('equipment_rental_available') === 'yes')>Yes</option>
                                    <option value="no" @selected(old('equipment_rental_available') === 'no')>No</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label>Equipment Included</label>
                                <div class="feature-checklist">
                                    @foreach (['snorkel_gear' => 'Snorkel Gear', 'life_jacket' => 'Life Jacket', 'fins' => 'Fins', 'wetsuit' => 'Wetsuit', 'helmet' => 'Helmet', 'gopro_mount' => 'GoPro Mount'] as $equipmentKey => $equipmentLabel)
                                        <label class="feature-item"><input type="checkbox" name="equipment_included[]" value="{{ $equipmentKey }}" @checked(in_array($equipmentKey, old('equipment_included', []), true))> {{ $equipmentLabel }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_weather_cancellation_policy">Weather Cancellation Policy</label>
                                <textarea id="property_weather_cancellation_policy" name="weather_cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="Trips may be rescheduled or refunded in case of unsafe sea/weather conditions..." required>{{ old('weather_cancellation_policy') }}</textarea>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="excursion">
                                <label for="property_special_instructions">Special Instructions</label>
                                <textarea id="property_special_instructions" name="special_instructions" class="ops-textarea" rows="3" maxlength="2000" placeholder="What guests should bring, what to wear, check-in instructions, or anything important before arrival.">{{ old('special_instructions') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_workspace_type">Workspace Type</label>
                                <select id="property_workspace_type" name="workspace_type" class="ops-select" required>
                                    <option value="" @selected(old('workspace_type') === null)>Select</option>
                                    <option value="shared" @selected(old('workspace_type') === 'shared')>Shared</option>
                                    <option value="private" @selected(old('workspace_type') === 'private')>Private</option>
                                    <option value="cabin" @selected(old('workspace_type') === 'cabin')>Cabin</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_internet_speed_mbps">Internet Speed (Mbps)</label>
                                <input id="property_internet_speed_mbps" name="internet_speed_mbps" class="ops-input" type="number" min="1" max="10000" step="1" value="{{ old('internet_speed_mbps') }}" required>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <label>Workspace Amenities (information only)</label>
                                <p class="small" style="margin-bottom:8px;">Free Amenities (tick all)</p>
                                <div class="feature-checklist" style="margin-bottom:10px;">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'free')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small" style="margin-bottom:8px;">Paid Amenities (tick all)</p>
                                <div class="feature-checklist">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'paid')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small">Set each item as Free, Purchase Separately On-Site, or Not Available. The app only collects the booking fee; extras are purchased separately.</p>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_operating_hours_open">Opens At</label>
                                <input id="property_operating_hours_open" name="operating_hours_open" class="ops-input" type="time" value="{{ old('operating_hours_open', '08:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_operating_hours_close">Closes At</label>
                                <input id="property_operating_hours_close" name="operating_hours_close" class="ops-input" type="time" value="{{ old('operating_hours_close', '22:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_min_booking_hours">Min. Booking (hours)</label>
                                <input id="property_min_booking_hours" name="min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('min_booking_hours', 1) }}" placeholder="e.g. 2">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="stay">
                                <label>Transfer Options and Charges (Per Pax)</label>
                                <p class="small" style="margin-bottom:8px;">Configure local and foreign transfer rates separately. If only one rate is set, it is treated as foreign adult default.</p>
                                <div class="ops-form-grid">
                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                        @php
                                            $transferRateValue = '';
                                            if (is_array($oldTransferRatesInput) && array_key_exists($transferOptionKey, $oldTransferRatesInput)) {
                                                $transferRateValue = (string) $oldTransferRatesInput[$transferOptionKey];
                                            }
                                            $transferRateLocalAdult = old('transfer_rates_local_adult.' . $transferOptionKey, '');
                                            $transferRateLocalChild = old('transfer_rates_local_child.' . $transferOptionKey, '');
                                            $transferRateForeignAdult = old('transfer_rates_foreign_adult.' . $transferOptionKey, $transferRateValue);
                                            $transferRateForeignChild = old('transfer_rates_foreign_child.' . $transferOptionKey, '');
                                        @endphp
                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $oldTransferOptions, true))>
                                            <span>{{ $transferOptionLabel }}</span>
                                        </label>
                                        <input name="transfer_rates[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateValue }}" placeholder="Legacy per pax rate (MVR)">
                                        <input name="transfer_rates_local_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateLocalAdult }}" placeholder="Local adult rate (MVR)">
                                        <input name="transfer_rates_local_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateLocalChild }}" placeholder="Local child rate (MVR)">
                                        <input name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateForeignAdult }}" placeholder="Foreign adult rate (MVR)">
                                        <input name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateForeignChild }}" placeholder="Foreign child rate (MVR)">
                                    @endforeach
                                    <input name="transfer_base_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_base_local', '0') }}" placeholder="Transfer base local (MVR)">
                                    <input name="transfer_base_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('transfer_base_foreign', '0') }}" placeholder="Transfer base foreign (MVR)">
                                </div>
                                @if ($vendorTaxComponents->isNotEmpty())
                                    <p class="small" style="margin:8px 0;">Vendor tax rate overrides (admin-moderated tax types)</p>
                                    <div class="ops-form-grid">
                                        @foreach ($vendorTaxComponents as $taxComponent)
                                            @php
                                                $taxCode = strtolower(trim((string) ($taxComponent['code'] ?? '')));
                                                $taxLabel = trim((string) ($taxComponent['label'] ?? $taxCode));
                                                $taxDefaultRate = (float) ($taxComponent['default_rate'] ?? 0);
                                            @endphp
                                            @if ($taxCode !== '')
                                                <input name="vendor_tax_rates[{{ $taxCode }}]" class="ops-input" type="number" min="0" step="0.0001" value="{{ old('vendor_tax_rates.' . $taxCode, (string) $taxDefaultRate) }}" placeholder="{{ $taxLabel }}">
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_start_time">Day Visit Start Time</label>
                                <input id="property_day_visit_start_time" name="day_visit_start_time" class="ops-input" type="time" value="{{ old('day_visit_start_time') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_end_time">Day Visit End Time</label>
                                <input id="property_day_visit_end_time" name="day_visit_end_time" class="ops-input" type="time" value="{{ old('day_visit_end_time') }}" required>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="day_visit">
                                <label for="property_included_access">Included Access</label>
                                <textarea id="property_included_access" name="included_access" class="ops-textarea" maxlength="2000" placeholder="Pool access, lunch, transfer, spa credits, etc.">{{ old('included_access') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_price_per_adult">Price Per Adult (MVR)</label>
                                <input id="property_price_per_adult" name="price_per_adult" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_adult') }}" placeholder="Adult day pass rate">
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_price_per_child">Price Per Child (MVR)</label>
                                <input id="property_price_per_child" name="price_per_child" class="ops-input" type="number" min="0" step="0.01" value="{{ old('price_per_child') }}" placeholder="Child day pass rate (leave blank if same as adult)">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_cuisine_type">Cuisine Type</label>
                                <input id="property_cuisine_type" name="cuisine_type" class="ops-input" type="text" maxlength="120" value="{{ old('cuisine_type') }}" placeholder="Maldivian, Asian Fusion, Seafood" required>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_meal_service">Meal Service</label>
                                @php
                                    $mealServiceOld = strtolower(trim((string) old('meal_service', '')));
                                    $knownMealServices = $restaurantMealServiceOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_meal_service" name="meal_service" class="ops-select" required>
                                    <option value="" @selected($mealServiceOld === '')>Select</option>
                                    @if ($mealServiceOld !== '' && !in_array($mealServiceOld, $knownMealServices, true))
                                        <option value="{{ $mealServiceOld }}" selected>{{ ucfirst(str_replace('_', ' ', $mealServiceOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($restaurantMealServiceOptionsCollection as $mealServiceOption)
                                        @php
                                            $mealServiceValue = strtolower(trim((string) ($mealServiceOption['value'] ?? '')));
                                            $mealServiceLabel = trim((string) ($mealServiceOption['label'] ?? $mealServiceValue));
                                        @endphp
                                        @if ($mealServiceValue !== '' && $mealServiceLabel !== '')
                                            <option value="{{ $mealServiceValue }}" @selected($mealServiceOld === $mealServiceValue)>{{ $mealServiceLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_seating_capacity">Seating Capacity</label>
                                <input id="property_seating_capacity" name="seating_capacity" class="ops-input" type="number" min="1" max="10000" value="{{ old('seating_capacity') }}" placeholder="Total covers / seats">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_restaurant_open_time">Opens At</label>
                                <input id="property_restaurant_open_time" name="restaurant_open_time" class="ops-input" type="time" value="{{ old('restaurant_open_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_restaurant_close_time">Closes At</label>
                                <input id="property_restaurant_close_time" name="restaurant_close_time" class="ops-input" type="time" value="{{ old('restaurant_close_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_booking_required">Advance Booking</label>
                                <select id="property_booking_required" name="booking_required" class="ops-select">
                                    <option value="" @selected(old('booking_required') === '')>Select</option>
                                    <option value="required" @selected(old('booking_required') === 'required')>Reservation Required</option>
                                    <option value="recommended" @selected(old('booking_required') === 'recommended')>Recommended</option>
                                    <option value="walk_in" @selected(old('booking_required') === 'walk_in')>Walk-ins Welcome</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_dress_code">Dress Code</label>
                                <select id="property_dress_code" name="dress_code" class="ops-select">
                                    <option value="" @selected(old('dress_code') === '')>None / Casual</option>
                                    <option value="casual" @selected(old('dress_code') === 'casual')>Casual</option>
                                    <option value="smart_casual" @selected(old('dress_code') === 'smart_casual')>Smart Casual</option>
                                    <option value="formal" @selected(old('dress_code') === 'formal')>Formal / Black Tie</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_price_range">Price Range</label>
                                <select id="property_price_range" name="price_range" class="ops-select">
                                    <option value="" @selected(old('price_range') === '')>Select</option>
                                    <option value="budget" @selected(old('price_range') === 'budget')>Budget (under MVR 150/pp)</option>
                                    <option value="mid_range" @selected(old('price_range') === 'mid_range')>Mid-Range (MVR 150–400/pp)</option>
                                    <option value="upscale" @selected(old('price_range') === 'upscale')>Upscale (MVR 400–800/pp)</option>
                                    <option value="fine_dining" @selected(old('price_range') === 'fine_dining')>Fine Dining (MVR 800+/pp)</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_minimum_age">Minimum Age</label>
                                <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="0" max="120" value="{{ old('minimum_age') }}">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_vehicle_type">Vehicle Type</label>
                                @php
                                    $vehicleTypeOld = strtolower(trim((string) old('vehicle_type', '')));
                                    $knownVehicleTypes = $vehicleRentalTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_vehicle_type" name="vehicle_type" class="ops-select" required>
                                    <option value="" @selected($vehicleTypeOld === '')>Select Vehicle Type</option>
                                    @if ($vehicleTypeOld !== '' && !in_array($vehicleTypeOld, $knownVehicleTypes, true))
                                        <option value="{{ $vehicleTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $vehicleTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($vehicleRentalTypeOptionGroups as $vehicleGroupKey => $vehicleGroupItems)
                                        @php
                                            $vehicleGroupLabel = $vehicleGroupKey === 'land'
                                                ? 'Land Vehicles'
                                                : ($vehicleGroupKey === 'marine' ? 'Marine Vessels' : ucfirst(str_replace('_', ' ', (string) $vehicleGroupKey)));
                                        @endphp
                                        <optgroup label="{{ $vehicleGroupLabel }}">
                                            @foreach ($vehicleGroupItems as $vehicleTypeOption)
                                                @php
                                                    $vehicleTypeValue = strtolower(trim((string) ($vehicleTypeOption['value'] ?? '')));
                                                    $vehicleTypeLabel = trim((string) ($vehicleTypeOption['label'] ?? $vehicleTypeValue));
                                                @endphp
                                                @if ($vehicleTypeValue !== '' && $vehicleTypeLabel !== '')
                                                    <option value="{{ $vehicleTypeValue }}" @selected($vehicleTypeOld === $vehicleTypeValue)>{{ $vehicleTypeLabel }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_transmission_type">Transmission</label>
                                <select id="property_transmission_type" name="transmission_type" class="ops-select" required>
                                    <option value="" @selected(old('transmission_type') === null)>Select</option>
                                    <option value="automatic" @selected(old('transmission_type') === 'automatic')>Automatic</option>
                                    <option value="manual" @selected(old('transmission_type') === 'manual')>Manual</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_fuel_type">Fuel Type</label>
                                <select id="property_fuel_type" name="fuel_type" class="ops-select" required>
                                    <option value="" @selected(old('fuel_type') === null)>Select</option>
                                    <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                                    <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                                    <option value="electric" @selected(old('fuel_type') === 'electric')>Electric</option>
                                    <option value="hybrid" @selected(old('fuel_type') === 'hybrid')>Hybrid</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_deposit_amount">Security Deposit (MVR)</label>
                                <input id="property_deposit_amount" name="deposit_amount" class="ops-input" type="number" min="0" step="0.01" value="{{ old('deposit_amount') }}" placeholder="Refundable deposit amount">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_license_class_required">License Required</label>
                                <input id="property_license_class_required" name="license_class_required" class="ops-input" type="text" maxlength="80" value="{{ old('license_class_required') }}" placeholder="e.g. B1, A1, International DL">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_daily_km_limit">Daily KM Limit</label>
                                <input id="property_daily_km_limit" name="daily_km_limit" class="ops-input" type="number" min="0" max="10000" value="{{ old('daily_km_limit') }}" placeholder="0 = unlimited">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_year_manufactured">Year</label>
                                <input id="property_year_manufactured" name="year_manufactured" class="ops-input" type="number" min="1980" max="{{ date('Y') + 1 }}" value="{{ old('year_manufactured') }}" placeholder="e.g. 2022">
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_rental_seating_count">Seats</label>
                                <input id="property_rental_seating_count" name="rental_seating_count" class="ops-input" type="number" min="1" max="200" value="{{ old('rental_seating_count') }}" placeholder="Number of seats">
                            </div>
                            {{-- Conference Room specific --}}
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_conference_room_type">Room Type</label>
                                <select id="property_conference_room_type" name="conference_room_type" class="ops-select">
                                    <option value="" @selected(old('conference_room_type') === '')>Select</option>
                                    <option value="boardroom" @selected(old('conference_room_type') === 'boardroom')>Boardroom</option>
                                    <option value="training" @selected(old('conference_room_type') === 'training')>Training Room</option>
                                    <option value="event_hall" @selected(old('conference_room_type') === 'event_hall')>Event Hall</option>
                                    <option value="banquet" @selected(old('conference_room_type') === 'banquet')>Banquet Hall</option>
                                    <option value="theater" @selected(old('conference_room_type') === 'theater')>Theater / Auditorium</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_conference_min_booking_hours">Min. Booking (hours)</label>
                                <input id="property_conference_min_booking_hours" name="conference_min_booking_hours" class="ops-input" type="number" min="1" max="24" value="{{ old('conference_min_booking_hours', 2) }}" placeholder="e.g. 2">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="conference">
                                <label>AV Equipment Included</label>
                                <div class="feature-checklist">
                                    @foreach (['projector' => 'Projector', 'screen' => 'Projection Screen', 'whiteboard' => 'Whiteboard', 'microphone' => 'Microphone / PA System', 'video_conferencing' => 'Video Conferencing', 'flip_chart' => 'Flip Chart'] as $avKey => $avLabel)
                                        <label class="feature-item"><input type="checkbox" name="av_equipment[]" value="{{ $avKey }}" @checked(in_array($avKey, old('av_equipment', []), true))> {{ $avLabel }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field" data-category-scope="conference">
                                <label for="property_catering_available">Catering</label>
                                <select id="property_catering_available" name="catering_available" class="ops-select">
                                    <option value="" @selected(old('catering_available') === '')>Select</option>
                                    <option value="yes" @selected(old('catering_available') === 'yes')>In-House Catering Available</option>
                                    <option value="external" @selected(old('catering_available') === 'external')>External Catering Allowed</option>
                                    <option value="no" @selected(old('catering_available') === 'no')>No Catering</option>
                                </select>
                            </div>
                            {{-- Accommodation type, star rating, check-in/out, policies --}}
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_property_type">Property Type</label>
                                <select id="property_property_type" name="property_type" class="ops-select">
                                    <option value="" @selected(old('property_type') === '')>Select property type</option>
                                    <option value="hotel" @selected(old('property_type') === 'hotel')>Hotel</option>
                                    <option value="resort" @selected(old('property_type') === 'resort')>Resort</option>
                                    <option value="guest_house" @selected(old('property_type') === 'guest_house')>Guest House</option>
                                    <option value="villa" @selected(old('property_type') === 'villa')>Villa / Private House</option>
                                    <option value="apartment" @selected(old('property_type') === 'apartment')>Apartment</option>
                                    <option value="bungalow" @selected(old('property_type') === 'bungalow')>Bungalow</option>
                                    <option value="hostel" @selected(old('property_type') === 'hostel')>Hostel / Dormitory</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_star_rating">Star Rating</label>
                                <select id="property_star_rating" name="star_rating" class="ops-select">
                                    <option value="" @selected(old('star_rating') === '')>Unrated / Not Applicable</option>
                                    <option value="1" @selected(old('star_rating') == '1')>1 Star</option>
                                    <option value="2" @selected(old('star_rating') == '2')>2 Stars</option>
                                    <option value="3" @selected(old('star_rating') == '3')>3 Stars</option>
                                    <option value="4" @selected(old('star_rating') == '4')>4 Stars</option>
                                    <option value="5" @selected(old('star_rating') == '5')>5 Stars</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <p class="category-scope-note" style="margin:0;">Star guidance baseline: 1 star = essential stay only, 3 star = standard comfort/service, 5 star = premium full-service hospitality. Choose the closest real operating standard for this listing.</p>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_in_time">Check-in Time</label>
                                <input id="property_check_in_time" name="check_in_time" class="ops-input" type="time" value="{{ old('check_in_time', '14:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_out_time">Check-out Time</label>
                                <input id="property_check_out_time" name="check_out_time" class="ops-input" type="time" value="{{ old('check_out_time', '12:00') }}">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_minimum_nights">Minimum Nights</label>
                                <input id="property_minimum_nights" name="minimum_nights" class="ops-input" type="number" min="1" max="365" value="{{ old('minimum_nights', 1) }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label for="property_house_rules">House Rules</label>
                                <textarea id="property_house_rules" name="house_rules" class="ops-textarea" rows="3" maxlength="2000" placeholder="No parties, no smoking indoors, quiet after 10pm…">{{ old('house_rules') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_check_in_grace_minutes">Check-in Grace (minutes)</label>
                                <input id="property_check_in_grace_minutes" name="check_in_grace_minutes" class="ops-input" type="number" min="0" max="720" value="{{ old('check_in_grace_minutes', 60) }}" placeholder="Late arrival tolerance window">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_early_check_in_allowed">Early Check-in</label>
                                <select id="property_early_check_in_allowed" name="early_check_in_allowed" class="ops-select">
                                    <option value="" @selected(old('early_check_in_allowed') === '')>Select</option>
                                    <option value="yes" @selected(old('early_check_in_allowed') === 'yes')>Allowed</option>
                                    <option value="subject_to_availability" @selected(old('early_check_in_allowed') === 'subject_to_availability')>Subject to Availability</option>
                                    <option value="no" @selected(old('early_check_in_allowed') === 'no')>Not Allowed</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_late_check_out_allowed">Late Check-out</label>
                                <select id="property_late_check_out_allowed" name="late_check_out_allowed" class="ops-select">
                                    <option value="" @selected(old('late_check_out_allowed') === '')>Select</option>
                                    <option value="yes" @selected(old('late_check_out_allowed') === 'yes')>Allowed</option>
                                    <option value="subject_to_availability" @selected(old('late_check_out_allowed') === 'subject_to_availability')>Subject to Availability</option>
                                    <option value="no" @selected(old('late_check_out_allowed') === 'no')>Not Allowed</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label for="property_child_policy">Child Policy</label>
                                <textarea id="property_child_policy" name="child_policy" class="ops-textarea" rows="3" maxlength="3000" placeholder="Children under 6 stay free with existing bedding; extra bed available at surcharge…">{{ old('child_policy') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_early_check_in_fee">Early Check-in Fee (MVR)</label>
                                <input id="property_early_check_in_fee" name="early_check_in_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('early_check_in_fee') }}" placeholder="0 = complimentary">
                            </div>
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_late_check_out_fee">Late Check-out Fee (MVR)</label>
                                <input id="property_late_check_out_fee" name="late_check_out_fee" class="ops-input" type="number" min="0" step="0.01" value="{{ old('late_check_out_fee') }}" placeholder="0 = complimentary">
                            </div>
                            {{-- Shared cancellation policy (shown for all bookable categories) --}}
                            <div class="ops-field ops-field-wide" data-category-scope="policies">
                                <label for="property_cancellation_policy">Cancellation Policy</label>
                                <textarea id="property_cancellation_policy" name="cancellation_policy" class="ops-textarea" rows="3" maxlength="2000" placeholder="e.g. Free cancellation up to 48 hours before. 50% refund within 24 hours. No refund for no-shows.">{{ old('cancellation_policy') }}</textarea>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Amenities (tick all available)</label>
                                <p class="small" style="margin:0 0 8px;">Amenities are managed by admin. Vendors can only select from this list.</p>
                                <div class="feature-checklist">
                                    @forelse ($propertyAmenityOptionsCollection as $facilityOption)
                                        @php
                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                        @endphp
                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $oldPropertyAmenities, true))> {{ $facilityLabel }}</label>
                                        @endif
                                    @empty
                                        <p class="small" style="margin:0;">No amenities configured by admin yet.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Features (tick all available)</label>
                                <p class="small" style="margin:0 0 8px;">Features are managed by admin. Vendors can only select from this list.</p>
                                <div class="feature-checklist">
                                    @forelse ($propertyFeatureOptionsCollection as $featureOption)
                                        @php
                                            $featureValue = trim((string) ($featureOption['value'] ?? ''));
                                            $featureLabel = trim((string) ($featureOption['label'] ?? $featureValue));
                                        @endphp
                                        @if ($featureValue !== '' && $featureLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_features[]" value="{{ $featureValue }}" @checked(in_array($featureValue, $oldPropertyFeatures, true))> {{ $featureLabel }}</label>
                                        @endif
                                    @empty
                                        <p class="small" style="margin:0;">No property features configured by admin yet.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="geo">
                                <div class="map-picker">
                                    <div id="propertyMap" aria-label="Map picker"></div>
                                </div>
                                <p class="map-help">Click the map to drop a pin. Coordinates are captured automatically.</p>
                            </div>
                        </div>
                        <p class="standards-note">International listing standard: fields adapt to selected category. Create one property at a time, then add rooms under that property.</p>
                        <div id="propertyCreateFormError" class="form-error-banner" hidden></div>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="propertyCreateSubmitButton" type="submit">Save Listing</button>
                            <button class="btn btn-secondary" id="closePropertyCreateForm" type="button">Cancel</button>
                            <button class="btn btn-secondary" id="backToListingsFromCreate" type="button">Back To Listings</button>
                        </div>
                    </form>
