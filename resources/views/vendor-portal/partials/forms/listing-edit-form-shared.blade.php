<form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update" data-property-edit-form="{{ $propertyId }}" data-property-edit-category="{{ vendorPortalCanonicalCategory((string) $editCategory) ?? $editCategory }}" hidden>
                                                                @csrf
                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $property->name }}" required>
                                                                <select class="ops-select" name="location_country" data-property-edit-scope="geo" data-edit-country data-selected-value="{{ (string) ($propertyDetails['location_country'] ?? 'Maldives') }}">
                                                                    <option value="Maldives" @selected((string) ($propertyDetails['location_country'] ?? 'Maldives') === 'Maldives')>Maldives</option>
                                                                    <option value="Sri Lanka" @selected((string) ($propertyDetails['location_country'] ?? '') === 'Sri Lanka')>Sri Lanka</option>
                                                                    <option value="India" @selected((string) ($propertyDetails['location_country'] ?? '') === 'India')>India</option>
                                                                    <option value="Other" @selected((string) ($propertyDetails['location_country'] ?? '') === 'Other')>Other</option>
                                                                </select>
                                                                <select class="ops-select" name="location_state" data-property-edit-scope="geo" data-edit-state data-selected-value="{{ (string) ($propertyDetails['location_state'] ?? '') }}">
                                                                    <option value="">Select atoll</option>
                                                                </select>
                                                                <select class="ops-select" name="location_city" data-property-edit-scope="geo" data-edit-city data-selected-value="{{ (string) ($propertyDetails['location_city'] ?? '') }}">
                                                                    <option value="">Select island</option>
                                                                </select>
                                                                <input class="ops-input" name="location_ward" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_ward'] ?? '') }}" placeholder="Ward / Neighborhood" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="address_line" type="text" maxlength="255" value="{{ (string) ($propertyDetails['address_line'] ?? '') }}" placeholder="Address line" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="building_house_lot" type="text" maxlength="160" value="{{ (string) ($propertyDetails['building_house_lot'] ?? '') }}" placeholder="Building / House / Lot No." data-property-edit-scope="geo">
                                                                <input class="ops-input" name="street" type="text" maxlength="160" value="{{ (string) ($propertyDetails['street'] ?? '') }}" placeholder="Street" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="post_code" type="text" maxlength="20" value="{{ (string) ($propertyDetails['post_code'] ?? '') }}" placeholder="Post code" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['property_contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['property_contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_email" type="email" maxlength="190" value="{{ (string) ($propertyDetails['property_contact_email'] ?? '') }}" placeholder="Property Contact Email" data-property-edit-scope="geo">
                                                                <input name="map_latitude" type="hidden" value="{{ (string) ($propertyDetails['map_latitude'] ?? '') }}">
                                                                <input name="map_longitude" type="hidden" value="{{ (string) ($propertyDetails['map_longitude'] ?? '') }}">
                                                                <input name="map_place_id" type="hidden" value="{{ (string) ($propertyDetails['map_place_id'] ?? '') }}">
                                                                    <div class="edit-map-picker" data-property-edit-scope="geo">
                                                                        <div class="edit-map-label">Pin Location on Map <span style="font-weight:400;color:#5b7488;">(click or drag the pin to set exact coordinates)</span></div>
                                                                        <div class="edit-map-wrap" id="editPropertyMap_{{ $propertyId }}"></div>
                                                                        <div class="edit-map-coords" id="editMapCoords_{{ $propertyId }}">
                                                                            @php
                                                                                $editLat = (string) ($propertyDetails['map_latitude'] ?? '');
                                                                                $editLng = (string) ($propertyDetails['map_longitude'] ?? '');
                                                                            @endphp
                                                                            @if ($editLat !== '' && $editLng !== '')
                                                                                Pinned: {{ $editLat }}, {{ $editLng }}
                                                                            @else
                                                                                No pin saved yet - click the map to set one
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <textarea class="ops-textarea" name="description" maxlength="3000" placeholder="Description">{{ (string) ($property->description ?? '') }}</textarea>
                                                                <input name="base_price" type="hidden" value="{{ (float) ($property->base_price ?? 0) }}" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="max_guests" type="number" min="0" max="10000" value="{{ (int) ($property->max_guests ?? 0) }}" data-property-edit-scope="capacity">

                                                                <input class="ops-input" name="area_value" type="number" min="5" max="100000" step="0.01" value="{{ (string) ($propertyDetails['area_value'] ?? '') }}" placeholder="Area Value (sqft)" data-property-edit-scope="stay">
                                                                <input name="area_unit" type="hidden" value="sqft" data-property-edit-scope="stay">
                                                                <input name="measurement_system" type="hidden" value="imperial" data-property-edit-scope="stay">
                                                                <input class="ops-input" name="bedroom_count" type="number" min="0" max="1000" value="{{ (string) ($propertyDetails['bedroom_count'] ?? '') }}" placeholder="Bedrooms" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="capacity_value" type="number" min="1" max="20000" value="{{ (string) ($propertyDetails['capacity_value'] ?? '') }}" placeholder="Capacity" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="service_radius_km" type="number" min="0" max="5000" step="0.1" value="{{ (string) ($propertyDetails['service_radius_km'] ?? '') }}" placeholder="Service Radius (km)" data-property-edit-scope="service">
                                                                @php
                                                                    $transportModeEdit = strtolower(trim((string) ($propertyDetails['transport_mode'] ?? '')));
                                                                    $knownTransportModes = $transportModeOptionsCollection
                                                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                        ->filter(fn ($item) => $item !== '')
                                                                        ->values()
                                                                        ->all();
                                                                @endphp
                                                                <select class="ops-select" name="transport_mode" data-property-edit-scope="transport">
                                                                    <option value="" @selected($transportModeEdit === '')>Transport Mode</option>
                                                                    @if ($transportModeEdit !== '' && !in_array($transportModeEdit, $knownTransportModes, true))
                                                                        <option value="{{ $transportModeEdit }}" selected>{{ ucfirst($transportModeEdit) }} (existing)</option>
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
                                                                                    <option value="{{ $groupValue }}" @selected($transportModeEdit === $groupValue)>{{ $groupText }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                                <input class="ops-input" name="vehicle_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_name'] ?? '') }}" placeholder="Vehicle / Vessel Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="registration_plate" type="text" maxlength="80" value="{{ (string) ($propertyDetails['registration_plate'] ?? '') }}" placeholder="Registration Plate" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="transport">
                                                                <select class="ops-select" name="transport_trip_type" data-property-edit-scope="transport">
                                                                    <option value="" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === '')>Trip Type</option>
                                                                    <option value="one_way" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                                                    <option value="round_trip" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'round_trip')>Round Trip</option>
                                                                </select>
                                                                <select class="ops-select" name="transport_pricing_model" data-property-edit-scope="transport">
                                                                    <option value="per_trip" @selected((string) ($propertyDetails['transport_pricing_model'] ?? 'per_trip') === 'per_trip')>Per Trip</option>
                                                                    <option value="hourly" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'hourly')>Hourly Hire</option>
                                                                    <option value="daily" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'daily')>Daily Hire</option>
                                                                </select>
                                                                <input class="ops-input" name="hourly_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['hourly_rate'] ?? '') }}" placeholder="Hourly Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="daily_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['daily_rate'] ?? '') }}" placeholder="Daily Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="pickup_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['pickup_location'] ?? '') }}" placeholder="Pickup Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="dropoff_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['dropoff_location'] ?? '') }}" placeholder="Dropoff Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_state'] ?? '') }}" placeholder="Departure State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_city'] ?? '') }}" placeholder="Departure City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_state'] ?? '') }}" placeholder="Arrival State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_city'] ?? '') }}" placeholder="Arrival City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_area_port_jetty" type="text" maxlength="190" value="{{ (string) ($propertyDetails['departure_area_port_jetty'] ?? '') }}" placeholder="Departure Area / Port / Jetty" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_date" type="date" value="{{ (string) ($propertyDetails['departure_date'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_time" type="time" value="{{ (string) ($propertyDetails['departure_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="reporting_lead_minutes" type="number" min="0" max="720" step="1" value="{{ (string) ($propertyDetails['reporting_lead_minutes'] ?? '') }}" placeholder="Report Before Departure (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="trip_duration_minutes" type="number" min="5" max="1440" value="{{ (string) ($propertyDetails['trip_duration_minutes'] ?? '') }}" placeholder="Trip Duration (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="excursion_duration_minutes" type="number" min="30" max="1440" value="{{ (string) ($propertyDetails['excursion_duration_minutes'] ?? '') }}" placeholder="Excursion Duration (min)" data-property-edit-scope="excursion">
                                                                <select class="ops-select" name="excursion_difficulty" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === '')>Difficulty</option>
                                                                    <option value="easy" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'easy')>Easy</option>
                                                                    <option value="moderate" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'moderate')>Moderate</option>
                                                                    <option value="hard" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'hard')>Hard</option>
                                                                </select>
                                                                <select class="ops-select" name="workspace_type" data-property-edit-scope="workspace">
                                                                    <option value="" @selected((string) ($propertyDetails['workspace_type'] ?? '') === '')>Workspace Type</option>
                                                                    <option value="shared" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'shared')>Shared</option>
                                                                    <option value="private" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'private')>Private</option>
                                                                    <option value="cabin" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'cabin')>Cabin</option>
                                                                </select>
                                                                <input class="ops-input" name="internet_speed_mbps" type="number" min="1" max="10000" step="1" value="{{ (string) ($propertyDetails['internet_speed_mbps'] ?? '') }}" placeholder="Internet Speed (Mbps)" data-property-edit-scope="workspace">
                                                                <div class="ops-form-grid" data-property-edit-scope="workspace">
                                                                    <p class="small" style="margin:0;">Free Amenities (tick all)</p>
                                                                    <div class="feature-checklist" style="margin-bottom:8px;">
                                                                        @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                            @php
                                                                                $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                            @endphp
                                                                            <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityFreeValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                        @endforeach
                                                                    </div>
                                                                    <p class="small" style="margin:0;">Paid Amenities (tick all)</p>
                                                                    <div class="feature-checklist">
                                                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                        @php
                                                                            $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                        @endphp
                                                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityPaidValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                    @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="ops-form-grid" data-property-edit-scope="stay">
                                                                    <p class="small" style="margin:0;">Transfer Options and Charges (Per Pax)</p>
                                                                    @php
                                                                        $transferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null) ? $propertyDetails['transfer_rate_matrix'] : [];
                                                                    @endphp
                                                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                                                        @php
                                                                            $transferEditRate = '';
                                                                            if (array_key_exists($transferOptionKey, $transferRates)) {
                                                                                $transferEditRate = (string) $transferRates[$transferOptionKey];
                                                                            }
                                                                            $transferMatrixRow = is_array($transferRateMatrix[$transferOptionKey] ?? null) ? $transferRateMatrix[$transferOptionKey] : [];
                                                                        @endphp
                                                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $transferOptionValues, true))>
                                                                            <span>{{ $transferOptionLabel }}</span>
                                                                        </label>
                                                                        <input class="ops-input" name="transfer_rates[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ $transferEditRate }}" placeholder="Legacy per pax rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_local_adult[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['local_adult_charge'] ?? '') }}" placeholder="Local adult rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_local_child[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['local_child_charge'] ?? '') }}" placeholder="Local child rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_foreign_adult[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['foreign_adult_charge'] ?? $transferEditRate) }}" placeholder="Foreign adult rate (MVR)">
                                                                        <input class="ops-input" name="transfer_rates_foreign_child[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ (string) ($transferMatrixRow['foreign_child_charge'] ?? '') }}" placeholder="Foreign child rate (MVR)">
                                                                    @endforeach
                                                                    <input class="ops-input" name="transfer_base_local" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['transfer_base_local'] ?? 0) }}" placeholder="Transfer base local (MVR)">
                                                                    <input class="ops-input" name="transfer_base_foreign" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['transfer_base_foreign'] ?? 0) }}" placeholder="Transfer base foreign (MVR)">
                                                                    @php
                                                                        $vendorTaxRateOverrides = is_array($propertyDetails['vendor_tax_overrides'] ?? null) ? $propertyDetails['vendor_tax_overrides'] : [];
                                                                    @endphp
                                                                    @foreach ($vendorTaxComponents as $taxComponent)
                                                                        @php
                                                                            $taxCode = strtolower(trim((string) ($taxComponent['code'] ?? '')));
                                                                            $taxLabel = trim((string) ($taxComponent['label'] ?? $taxCode));
                                                                            $taxDefaultRate = (float) ($taxComponent['default_rate'] ?? 0);
                                                                            $taxCurrentRate = array_key_exists($taxCode, $vendorTaxRateOverrides)
                                                                                ? (float) $vendorTaxRateOverrides[$taxCode]
                                                                                : $taxDefaultRate;
                                                                        @endphp
                                                                        @if ($taxCode !== '')
                                                                            <input class="ops-input" name="vendor_tax_rates[{{ $taxCode }}]" type="number" min="0" step="0.0001" value="{{ (string) $taxCurrentRate }}" placeholder="{{ $taxLabel }}">
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <input class="ops-input" name="day_visit_start_time" type="time" value="{{ (string) ($propertyDetails['day_visit_start_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="day_visit_end_time" type="time" value="{{ (string) ($propertyDetails['day_visit_end_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="included_access" type="text" maxlength="2000" value="{{ (string) ($propertyDetails['included_access'] ?? '') }}" placeholder="Included Access" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="cuisine_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['cuisine_type'] ?? '') }}" placeholder="Cuisine Type" data-property-edit-scope="restaurant">
                                                                <select class="ops-select" name="meal_service" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['meal_service'] ?? '') === '')>Meal Service</option>
                                                                    <option value="breakfast" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'breakfast')>Breakfast</option>
                                                                    <option value="lunch" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'lunch')>Lunch</option>
                                                                    <option value="dinner" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'dinner')>Dinner</option>
                                                                    <option value="all_day" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'all_day')>All Day</option>
                                                                </select>
                                                                <input class="ops-input" name="minimum_age" type="number" min="0" max="120" value="{{ (string) ($propertyDetails['minimum_age'] ?? '') }}" placeholder="Minimum Age" data-property-edit-scope="vehicle">
                                                                <input class="ops-input" name="vehicle_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_type'] ?? '') }}" placeholder="Vehicle Type" data-property-edit-scope="rental">
                                                                <select class="ops-select" name="transmission_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['transmission_type'] ?? '') === '')>Transmission</option>
                                                                    <option value="automatic" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'automatic')>Automatic</option>
                                                                    <option value="manual" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'manual')>Manual</option>
                                                                </select>
                                                                <select class="ops-select" name="fuel_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['fuel_type'] ?? '') === '')>Fuel Type</option>
                                                                    <option value="petrol" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'petrol')>Petrol</option>
                                                                    <option value="diesel" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'diesel')>Diesel</option>
                                                                    <option value="electric" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'electric')>Electric</option>
                                                                    <option value="hybrid" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'hybrid')>Hybrid</option>
                                                                </select>

                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    @foreach ($accommodationFacilityOptionsCollection as $facilityOption)
                                                                        @php
                                                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                                                        @endphp
                                                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $propertyAmenityValues, true))> {{ $facilityLabel }}</label>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="wheelchair_access" @checked(in_array('wheelchair_access', $propertyFeatureValues, true))> Wheelchair Access</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="elevator" @checked(in_array('elevator', $propertyFeatureValues, true))> Elevator</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="family_friendly" @checked(in_array('family_friendly', $propertyFeatureValues, true))> Family Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="pet_friendly" @checked(in_array('pet_friendly', $propertyFeatureValues, true))> Pet Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="beachfront" @checked(in_array('beachfront', $propertyFeatureValues, true))> Beachfront</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="sea_view" @checked(in_array('sea_view', $propertyFeatureValues, true))> Sea View</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="safety_certified" @checked(in_array('safety_certified', $propertyFeatureValues, true))> Safety Certified</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="kids_play_area" @checked(in_array('kids_play_area', $propertyFeatureValues, true))> Kids Play Area</label>
                                                                </div>

                                                                {{-- Transport extras --}}
                                                                <select class="ops-select" name="seat_class" data-property-edit-scope="transport">
                                                                    <option value="" @selected((string) ($propertyDetails['seat_class'] ?? '') === '')>Seat Class (Standard)</option>
                                                                    <option value="economy" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'economy')>Economy</option>
                                                                    <option value="business" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'business')>Business</option>
                                                                    <option value="first" @selected((string) ($propertyDetails['seat_class'] ?? '') === 'first')>First Class</option>
                                                                </select>
                                                                <input class="ops-input" name="luggage_allowance_kg" type="number" min="0" max="500" value="{{ (string) ($propertyDetails['luggage_allowance_kg'] ?? '') }}" placeholder="Luggage Allowance (kg)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="schedule_start_time" type="time" value="{{ (string) ($propertyDetails['schedule_start_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="schedule_end_time" type="time" value="{{ (string) ($propertyDetails['schedule_end_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="booking_cutoff_minutes" type="number" min="0" max="10080" value="{{ (string) ($propertyDetails['booking_cutoff_minutes'] ?? '') }}" placeholder="Booking Cutoff (minutes)" data-property-edit-scope="transport">
                                                                <textarea class="ops-textarea" name="boarding_instructions" rows="2" maxlength="1000" placeholder="Boarding Instructions" data-property-edit-scope="transport">{{ (string) ($propertyDetails['boarding_instructions'] ?? '') }}</textarea>
                                                                {{-- Excursion extras --}}
                                                                <input class="ops-input" name="excursion_min_pax" type="number" min="1" max="1000" value="{{ (string) ($propertyDetails['excursion_min_pax'] ?? '') }}" placeholder="Min. Participants" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="excursion_max_pax" type="number" min="1" max="1000" value="{{ (string) ($propertyDetails['excursion_max_pax'] ?? '') }}" placeholder="Max. Participants" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="excursion_min_age" type="number" min="0" max="99" value="{{ (string) ($propertyDetails['excursion_min_age'] ?? '') }}" placeholder="Minimum Age" data-property-edit-scope="excursion">
                                                                <input class="ops-input" name="meeting_point" type="text" maxlength="255" value="{{ (string) ($propertyDetails['meeting_point'] ?? '') }}" placeholder="Meeting Point" data-property-edit-scope="excursion">
                                                                <textarea class="ops-textarea" name="inclusions" rows="3" maxlength="2000" placeholder="What's Included" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['inclusions'] ?? '') }}</textarea>
                                                                <textarea class="ops-textarea" name="exclusions" rows="2" maxlength="1000" placeholder="Not Included" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['exclusions'] ?? '') }}</textarea>
                                                                <select class="ops-select" name="safety_waiver_required" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === '')>Safety Waiver Required</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === 'yes')>Yes</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['safety_waiver_required'] ?? '') === 'no')>No</option>
                                                                </select>
                                                                <select class="ops-select" name="equipment_rental_available" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === '')>Equipment Rental Available</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === 'yes')>Yes</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['equipment_rental_available'] ?? '') === 'no')>No</option>
                                                                </select>
                                                                @php
                                                                    $equipmentIncludedValues = is_array($propertyDetails['equipment_included'] ?? null)
                                                                        ? array_map(static fn ($item): string => strtolower(trim((string) $item)), $propertyDetails['equipment_included'])
                                                                        : [];
                                                                @endphp
                                                                <div class="feature-checklist" data-property-edit-scope="excursion">
                                                                    @foreach (['snorkel_gear' => 'Snorkel Gear', 'life_jacket' => 'Life Jacket', 'fins' => 'Fins', 'wetsuit' => 'Wetsuit', 'helmet' => 'Helmet', 'gopro_mount' => 'GoPro Mount'] as $equipmentKey => $equipmentLabel)
                                                                        <label class="feature-item"><input type="checkbox" name="equipment_included[]" value="{{ $equipmentKey }}" @checked(in_array($equipmentKey, $equipmentIncludedValues, true))> {{ $equipmentLabel }}</label>
                                                                    @endforeach
                                                                </div>
                                                                <textarea class="ops-textarea" name="weather_cancellation_policy" rows="3" maxlength="2000" placeholder="Weather Cancellation Policy" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['weather_cancellation_policy'] ?? '') }}</textarea>
                                                                <textarea class="ops-textarea" name="special_instructions" rows="3" maxlength="2000" placeholder="Special Instructions" data-property-edit-scope="excursion">{{ (string) ($propertyDetails['special_instructions'] ?? '') }}</textarea>
                                                                {{-- Workspace operating hours --}}
                                                                <input class="ops-input" name="operating_hours_open" type="time" value="{{ (string) ($propertyDetails['operating_hours_open'] ?? '08:00') }}" data-property-edit-scope="workspace">
                                                                <input class="ops-input" name="operating_hours_close" type="time" value="{{ (string) ($propertyDetails['operating_hours_close'] ?? '22:00') }}" data-property-edit-scope="workspace">
                                                                <input class="ops-input" name="min_booking_hours" type="number" min="1" max="24" value="{{ (string) ($propertyDetails['min_booking_hours'] ?? '') }}" placeholder="Min. Booking (hours)" data-property-edit-scope="workspace">
                                                                {{-- Day visit pricing --}}
                                                                <input class="ops-input" name="price_per_adult" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['price_per_adult'] ?? '') }}" placeholder="Price Per Adult (MVR)" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="price_per_child" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['price_per_child'] ?? '') }}" placeholder="Price Per Child (MVR)" data-property-edit-scope="day_visit">
                                                                {{-- Restaurant extras --}}
                                                                <input class="ops-input" name="seating_capacity" type="number" min="1" max="10000" value="{{ (string) ($propertyDetails['seating_capacity'] ?? '') }}" placeholder="Seating Capacity" data-property-edit-scope="restaurant">
                                                                <input class="ops-input" name="restaurant_open_time" type="time" value="{{ (string) ($propertyDetails['restaurant_open_time'] ?? '') }}" data-property-edit-scope="restaurant">
                                                                <input class="ops-input" name="restaurant_close_time" type="time" value="{{ (string) ($propertyDetails['restaurant_close_time'] ?? '') }}" data-property-edit-scope="restaurant">
                                                                <select class="ops-select" name="booking_required" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['booking_required'] ?? '') === '')>Advance Booking</option>
                                                                    <option value="required" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'required')>Reservation Required</option>
                                                                    <option value="recommended" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'recommended')>Recommended</option>
                                                                    <option value="walk_in" @selected((string) ($propertyDetails['booking_required'] ?? '') === 'walk_in')>Walk-ins Welcome</option>
                                                                </select>
                                                                <select class="ops-select" name="dress_code" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['dress_code'] ?? '') === '')>Dress Code</option>
                                                                    <option value="casual" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'casual')>Casual</option>
                                                                    <option value="smart_casual" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'smart_casual')>Smart Casual</option>
                                                                    <option value="formal" @selected((string) ($propertyDetails['dress_code'] ?? '') === 'formal')>Formal / Black Tie</option>
                                                                </select>
                                                                <select class="ops-select" name="price_range" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['price_range'] ?? '') === '')>Price Range</option>
                                                                    <option value="budget" @selected((string) ($propertyDetails['price_range'] ?? '') === 'budget')>Budget</option>
                                                                    <option value="mid_range" @selected((string) ($propertyDetails['price_range'] ?? '') === 'mid_range')>Mid-Range</option>
                                                                    <option value="upscale" @selected((string) ($propertyDetails['price_range'] ?? '') === 'upscale')>Upscale</option>
                                                                    <option value="fine_dining" @selected((string) ($propertyDetails['price_range'] ?? '') === 'fine_dining')>Fine Dining</option>
                                                                </select>
                                                                {{-- Vehicle rental extras --}}
                                                                <input class="ops-input" name="deposit_amount" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['deposit_amount'] ?? '') }}" placeholder="Security Deposit (MVR)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="license_class_required" type="text" maxlength="80" value="{{ (string) ($propertyDetails['license_class_required'] ?? '') }}" placeholder="License Required (e.g. B1)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="daily_km_limit" type="number" min="0" max="10000" value="{{ (string) ($propertyDetails['daily_km_limit'] ?? '') }}" placeholder="Daily KM Limit (0=unlimited)" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="year_manufactured" type="number" min="1980" max="{{ date('Y') + 1 }}" value="{{ (string) ($propertyDetails['year_manufactured'] ?? '') }}" placeholder="Year" data-property-edit-scope="rental">
                                                                <input class="ops-input" name="rental_seating_count" type="number" min="1" max="200" value="{{ (string) ($propertyDetails['rental_seating_count'] ?? '') }}" placeholder="Seats" data-property-edit-scope="vehicle">
                                                                {{-- Conference room extras --}}
                                                                <select class="ops-select" name="conference_room_type" data-property-edit-scope="conference">
                                                                    <option value="" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === '')>Room Type</option>
                                                                    <option value="boardroom" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'boardroom')>Boardroom</option>
                                                                    <option value="training" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'training')>Training Room</option>
                                                                    <option value="event_hall" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'event_hall')>Event Hall</option>
                                                                    <option value="banquet" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'banquet')>Banquet Hall</option>
                                                                    <option value="theater" @selected((string) ($propertyDetails['conference_room_type'] ?? '') === 'theater')>Theater / Auditorium</option>
                                                                </select>
                                                                <input class="ops-input" name="conference_min_booking_hours" type="number" min="1" max="24" value="{{ (string) ($propertyDetails['conference_min_booking_hours'] ?? '') }}" placeholder="Min. Booking (hours)" data-property-edit-scope="conference">
                                                                <select class="ops-select" name="catering_available" data-property-edit-scope="conference">
                                                                    <option value="" @selected((string) ($propertyDetails['catering_available'] ?? '') === '')>Catering</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'yes')>In-House Catering</option>
                                                                    <option value="external" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'external')>External Catering Allowed</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['catering_available'] ?? '') === 'no')>No Catering</option>
                                                                </select>
                                                                {{-- Accommodation: property type, star rating, check-in/out, minimum nights, house rules --}}
                                                                <select class="ops-select" name="property_type" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['property_type'] ?? '') === '')>Property Type</option>
                                                                    <option value="hotel" @selected((string) ($propertyDetails['property_type'] ?? '') === 'hotel')>Hotel</option>
                                                                    <option value="resort" @selected((string) ($propertyDetails['property_type'] ?? '') === 'resort')>Resort</option>
                                                                    <option value="guest_house" @selected((string) ($propertyDetails['property_type'] ?? '') === 'guest_house')>Guest House</option>
                                                                    <option value="villa" @selected((string) ($propertyDetails['property_type'] ?? '') === 'villa')>Villa / Private House</option>
                                                                    <option value="apartment" @selected((string) ($propertyDetails['property_type'] ?? '') === 'apartment')>Apartment</option>
                                                                    <option value="bungalow" @selected((string) ($propertyDetails['property_type'] ?? '') === 'bungalow')>Bungalow</option>
                                                                    <option value="hostel" @selected((string) ($propertyDetails['property_type'] ?? '') === 'hostel')>Hostel / Dormitory</option>
                                                                </select>
                                                                <select class="ops-select" name="star_rating" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['star_rating'] ?? '') === '')>Star Rating (Unrated)</option>
                                                                    <option value="1" @selected((string) ($propertyDetails['star_rating'] ?? '') === '1')>1 Star</option>
                                                                    <option value="2" @selected((string) ($propertyDetails['star_rating'] ?? '') === '2')>2 Stars</option>
                                                                    <option value="3" @selected((string) ($propertyDetails['star_rating'] ?? '') === '3')>3 Stars</option>
                                                                    <option value="4" @selected((string) ($propertyDetails['star_rating'] ?? '') === '4')>4 Stars</option>
                                                                    <option value="5" @selected((string) ($propertyDetails['star_rating'] ?? '') === '5')>5 Stars</option>
                                                                </select>
                                                                <input class="ops-input" name="check_in_time" type="time" value="{{ (string) ($propertyDetails['check_in_time'] ?? '14:00') }}" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="check_out_time" type="time" value="{{ (string) ($propertyDetails['check_out_time'] ?? '12:00') }}" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="minimum_nights" type="number" min="1" max="365" value="{{ (string) ($propertyDetails['minimum_nights'] ?? 1) }}" placeholder="Minimum Nights" data-property-edit-scope="accommodation">
                                                                <textarea class="ops-textarea" name="house_rules" rows="3" maxlength="2000" placeholder="House Rules" data-property-edit-scope="accommodation">{{ (string) ($propertyDetails['house_rules'] ?? '') }}</textarea>
                                                                <input class="ops-input" name="check_in_grace_minutes" type="number" min="0" max="720" value="{{ (string) ($propertyDetails['check_in_grace_minutes'] ?? 60) }}" placeholder="Check-in Grace (minutes)" data-property-edit-scope="accommodation">
                                                                <select class="ops-select" name="early_check_in_allowed" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === '')>Early Check-in</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'yes')>Allowed</option>
                                                                    <option value="subject_to_availability" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'subject_to_availability')>Subject to Availability</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['early_check_in_allowed'] ?? '') === 'no')>Not Allowed</option>
                                                                </select>
                                                                <select class="ops-select" name="late_check_out_allowed" data-property-edit-scope="accommodation">
                                                                    <option value="" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === '')>Late Check-out</option>
                                                                    <option value="yes" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'yes')>Allowed</option>
                                                                    <option value="subject_to_availability" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'subject_to_availability')>Subject to Availability</option>
                                                                    <option value="no" @selected((string) ($propertyDetails['late_check_out_allowed'] ?? '') === 'no')>Not Allowed</option>
                                                                </select>
                                                                <textarea class="ops-textarea" name="child_policy" rows="3" maxlength="3000" placeholder="Child Policy" data-property-edit-scope="accommodation">{{ (string) ($propertyDetails['child_policy'] ?? '') }}</textarea>
                                                                <input class="ops-input" name="early_check_in_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['early_check_in_fee'] ?? '') }}" placeholder="Early Check-in Fee (MVR)" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="late_check_out_fee" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['late_check_out_fee'] ?? '') }}" placeholder="Late Check-out Fee (MVR)" data-property-edit-scope="accommodation">
                                                                {{-- Shared: cancellation policy --}}
                                                                <textarea class="ops-textarea" name="cancellation_policy" rows="3" maxlength="2000" placeholder="Cancellation Policy" data-property-edit-scope="policies">{{ (string) ($propertyDetails['cancellation_policy'] ?? '') }}</textarea>
                                                                <select class="ops-select" name="status" required>
                                                                    <option value="active" @selected((string) ($property->status ?? '') === 'active')>Active</option>
                                                                    <option value="inactive" @selected((string) ($property->status ?? '') === 'inactive')>Inactive</option>
                                                                </select>
                                                                <div class="inline-actions">
                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Listing</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-property-edit data-property-edit-id="{{ $propertyId }}">Cancel Edit</button>
                                                                </div>
                                                            </form>
