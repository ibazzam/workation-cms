{{-- Standalone create form: Sea Transport / Ferries --}}
<form class="ops-form" method="POST" action="/portal/vendor/properties/create">
    @csrf
    <input type="hidden" name="listing_category" value="sea_transport">

    <div class="ops-form-grid">
        <div class="ops-field ops-field-wide">
            <label for="property_name">Route Name</label>
            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" placeholder="e.g. Male' to Seenu Gan" required>
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_description">Description</label>
            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000" required>{{ old('description') }}</textarea>
        </div>

        {{-- Route Details --}}
        <div id="vessel-details-section" class="ops-field">
            <label for="property_vessel_name">Vessel Name</label>
            <input id="property_vessel_name" name="vessel_name" class="ops-input" type="text" maxlength="120" value="{{ old('vessel_name') }}" placeholder="e.g. Island Express">
        </div>
        <div class="ops-field">
            <label for="property_registration_no">Registration / Hull No.</label>
            <input id="property_registration_no" name="registration_no" class="ops-input" type="text" maxlength="60" value="{{ old('registration_no') }}">
        </div>
        <div class="ops-field">
            <label for="property_departure_point">Departure Point <span style="color:#5f7a8e; font-weight:500;">(optional fallback)</span></label>
            <input id="property_departure_point" name="departure_point" class="ops-input" type="text" maxlength="120" value="{{ old('departure_point') }}" placeholder="e.g. Malé Harbour">
        </div>
        <div class="ops-field">
            <label for="property_arrival_point">Arrival Point <span style="color:#5f7a8e; font-weight:500;">(optional fallback)</span></label>
            <input id="property_arrival_point" name="arrival_point" class="ops-input" type="text" maxlength="120" value="{{ old('arrival_point') }}" placeholder="e.g. Seenu Gan">
        </div>

        {{-- Primary route defaults --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border-bottom:1px solid #cfe0eb; padding-bottom:4px; margin-top:8px; margin-bottom:2px;">
            <p style="margin:0; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Step 2. Primary Route Defaults <span style="font-weight:600; color:#5f7a8e; text-transform:none; letter-spacing:0;">(optional fallback)</span></p>
        </div>
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; margin-top:-2px;">
            <p style="margin:0; font-size:0.85rem; color:#5a7284; line-height:1.45;">Use these as the headline route values shown in catalog cards and booking summaries. If you leave them blank, the first non-empty row from the timetable roster will populate them automatically.</p>
        </div>
        <div class="ops-field">
            <label for="property_departure_time">Departure Time <span style="color:#5f7a8e; font-weight:500;">(optional)</span></label>
            <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_return_time">Return / Arrival Time <span style="color:#5f7a8e; font-weight:500;">(optional)</span></label>
            <input id="property_return_time" name="return_time" class="ops-input" type="time" value="{{ old('return_time') }}">
        </div>
        <div class="ops-field">
            <label for="property_trip_duration_minutes">Trip Duration (minutes) <span style="color:#5f7a8e; font-weight:500;">(optional)</span></label>
            <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="1" value="{{ old('trip_duration_minutes') }}">
        </div>

        {{-- Seat Configuration --}}
        <div class="ops-field">
            <label for="property_total_seats">Total Seats</label>
            <input id="property_total_seats" name="total_seats" class="ops-input" type="number" min="1" max="1000" value="{{ old('total_seats') }}" required>
        </div>

        {{-- Service Pricing (Local & Foreign) --}}
        <div class="ops-field ops-field-wide" style="border-top: 1px solid #e0e0e0; padding-top: 1rem; margin-top: 1rem;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Step 3. Fallback Service Pricing (optional)</label>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">Use these only as defaults. Route-leg pricing in Step 1 is primary and will override these values when provided.</p>
        </div>
        
        <div class="ops-field">
            <label for="property_local_price">Price Per Seat - Local (MVR)</label>
            <input id="property_local_price" name="local_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('local_price') }}" placeholder="MVR 0.00">
        </div>
        <div class="ops-field">
            <label for="property_foreign_price">Price Per Seat - Foreign (USD)</label>
            <input id="property_foreign_price" name="foreign_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('foreign_price') }}" placeholder="USD 0.00">
        </div>
        <div class="ops-field">
            <label for="property_child_price_local">Child Seat Rate - Local (MVR)</label>
            <input id="property_child_price_local" name="child_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_local') }}" placeholder="MVR 0.00">
        </div>
        <div class="ops-field">
            <label for="property_child_price_foreign">Child Seat Rate - Foreign (USD)</label>
            <input id="property_child_price_foreign" name="child_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('child_price_foreign') }}" placeholder="USD 0.00">
        </div>
        <div class="ops-field">
            <label for="property_infant_price_local">Infant Rate - Local (MVR)</label>
            <input id="property_infant_price_local" name="infant_price_local" class="ops-input" type="number" min="0" step="0.01" value="{{ old('infant_price_local') }}" placeholder="MVR 0.00">
        </div>
        <div class="ops-field">
            <label for="property_infant_price_foreign">Infant Rate - Foreign (USD)</label>
            <input id="property_infant_price_foreign" name="infant_price_foreign" class="ops-input" type="number" min="0" step="0.01" value="{{ old('infant_price_foreign') }}" placeholder="USD 0.00">
        </div>

        {{-- Additional Info --}}
        <div class="ops-field">
            <label for="property_contact_name">Contact Name</label>
            <input id="property_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name') }}">
        </div>
        <div class="ops-field">
            <label for="property_contact_number">Contact Number</label>
            <input id="property_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number') }}">
        </div>
        <div class="ops-field ops-field-wide">
            <label for="property_boarding_instructions">Boarding Instructions</label>
            <textarea id="property_boarding_instructions" name="boarding_instructions" class="ops-textarea" rows="3" maxlength="1500" placeholder="Arrive 30 min early. Bring your booking confirmation.">{{ old('boarding_instructions') }}</textarea>
        </div>

        {{-- Location / Geo --}}
        <div class="ops-field">
            <label for="location_country">Operating Country</label>
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

        {{-- ── Route Legs Roster ──────────────────────────────────────── --}}
        <div id="route-fares-section" class="ops-field ops-field-wide" style="grid-column:1/-1; border:2px solid #1d7bb5; border-radius:12px; background:#f0f8ff; padding:16px 16px 12px; margin-top:1rem;">
            <p style="margin:0 0 0.3rem; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Route Legs &amp; Fares</p>
            <p style="font-size:0.85rem; color:#51697b; margin:0 0 0.8rem;">Add one card per bookable leg. Each leg has its own origin, destination, schedule, and per-leg pricing. Per-leg prices override the fallback prices in Step 3 below.</p>

            <div style="background:#fff; border:1px solid #cfe0eb; border-radius:8px; padding:10px 12px; margin-bottom:10px;">
                <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#1d4b66; margin-bottom:4px;">Master Enroute</label>
                <p style="font-size:0.78rem; color:#6d8191; margin:0 0 8px;">Set one master route, then generate legs in order (chain or branch mode). You can still edit or remove any generated leg.</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:6px;">
                    <input type="text" id="sea_master_origin" class="ops-input" placeholder="Master origin, e.g. Maafushi">
                    <input type="text" id="sea_master_destination" class="ops-input" placeholder="Master destination, e.g. Male">
                </div>
                <div style="display:grid; grid-template-columns:1fr auto; gap:6px; align-items:center;">
                    <select id="sea_master_mode" class="ops-select">
                        <option value="pool_roundtrip">Pool Corridor Round-Trip (forward + return)</option>
                        <option value="branch_balanced">Branch Balanced (easy for vendors)</option>
                        <option value="chain">Closest Chain Only (adjacent stops)</option>
                        <option value="origin_branches">Origin to Every Stop (+ final)</option>
                        <option value="destination_branches">Every Stop to Destination (+ direct)</option>
                    </select>
                    <button type="button" id="sea_apply_master_btn" class="ops-button ops-button-secondary" style="font-size:0.82rem;">Generate Legs</button>
                </div>
                <p id="sea_master_summary" style="margin:8px 0 0; font-size:0.77rem; color:#567287;"></p>
            </div>

            {{-- Physical stop sequence --}}
            <div style="background:#fff; border:1px solid #cfe0eb; border-radius:8px; padding:10px 12px;">
                <label for="sea_stop_sequence" style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#1d4b66; margin-bottom:4px;">Physical Stop Order <span style="font-weight:500; color:#5f7a8e; text-transform:none;">(one stop per line, in vessel travel order)</span></label>
                <p style="font-size:0.78rem; color:#6d8191; margin:0 0 6px;">Enter all stops in the order the vessel physically passes them. Used to calculate shared seat capacity across overlapping legs. E.g. if the vessel travels Male → Island 1 → Island 2 → Airport, a seat sold on Male→Island 2 also occupies the Island 1→Island 2 segment.</p>
                <textarea name="stop_sequence" id="sea_stop_sequence" class="ops-textarea" rows="3" maxlength="3000" placeholder="Male Jetty No.1&#10;Island 1 Jetty&#10;Island 2 Jetty&#10;Airport (Hulhule) Jetty"></textarea>
                <div style="margin-top:8px; display:grid; grid-template-columns:1fr auto; gap:6px; align-items:center;">
                    <input type="text" id="sea_stop_new" class="ops-input" placeholder="Add a stop, e.g. Male Jetty">
                    <button type="button" id="sea_stop_add_btn" class="ops-button ops-button-secondary" style="font-size:0.82rem;">Add Stop</button>
                </div>
                <div id="sea_stop_editor" style="margin-top:8px; display:flex; flex-direction:column; gap:6px;"></div>
                <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" id="sea_generate_legs_btn" class="ops-button ops-button-secondary" style="font-size:0.82rem;">Generate Sequential Legs From Stops</button>
                </div>
            </div>
        </div>

        <div class="ops-field ops-field-wide" style="grid-column:1/-1;">
            <input type="hidden" id="sea_route_schedules_json" name="route_schedules">
            <div id="sea_roster_rows" style="display:flex; flex-direction:column; gap:10px;"></div>
            <button type="button" id="sea_roster_add_btn" class="ops-button ops-button-secondary" style="margin-top:10px; font-size:0.85rem;">+ Add Route Leg</button>
        </div>

        <div class="ops-form-actions">
            <button type="submit" class="ops-button ops-button-primary">Save Route</button>
            <button type="button" class="ops-button ops-button-secondary" onclick="window.history.back();">Cancel</button>
        </div>
    </div>
</form>

<script>
(function () {
    /* ── Duration auto-calc ───────────────────────────────────────── */
    const departureInput = document.getElementById('property_departure_time');
    const arrivalInput   = document.getElementById('property_return_time');
    const durationInput  = document.getElementById('property_trip_duration_minutes');

    function parseMinutes(value) {
        const parts = String(value || '').split(':');
        if (parts.length !== 2) return null;
        const hh = Number(parts[0]), mm = Number(parts[1]);
        if (!Number.isInteger(hh) || !Number.isInteger(mm)) return null;
        if (hh < 0 || hh > 23 || mm < 0 || mm > 59) return null;
        return (hh * 60) + mm;
    }

    function syncDuration() {
        if (!departureInput || !arrivalInput || !durationInput) return;
        const start = parseMinutes(departureInput.value);
        const end   = parseMinutes(arrivalInput.value);
        if (start === null || end === null) return;
        let d = end - start;
        if (d <= 0) d += 24 * 60;
        if (d > 0) durationInput.value = String(d);
    }

    if (departureInput) departureInput.addEventListener('change', syncDuration);
    if (arrivalInput)   arrivalInput.addEventListener('change', syncDuration);
    if (departureInput) departureInput.addEventListener('input', syncDuration);
    if (arrivalInput)   arrivalInput.addEventListener('input', syncDuration);
    syncDuration();

    /* ── Route Schedule Roster ────────────────────────────────────── */
    const DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    const rosterContainer = document.getElementById('sea_roster_rows');
    const addBtn          = document.getElementById('sea_roster_add_btn');
    const jsonInput       = document.getElementById('sea_route_schedules_json');
    const stopTextarea    = document.getElementById('sea_stop_sequence');
    const stopEditor      = document.getElementById('sea_stop_editor');
    const stopNewInput    = document.getElementById('sea_stop_new');
    const stopAddBtn      = document.getElementById('sea_stop_add_btn');
    const generateLegsBtn = document.getElementById('sea_generate_legs_btn');
    const masterOriginInput = document.getElementById('sea_master_origin');
    const masterDestinationInput = document.getElementById('sea_master_destination');
    const masterModeInput = document.getElementById('sea_master_mode');
    const masterApplyBtn = document.getElementById('sea_apply_master_btn');
    const masterSummaryEl = document.getElementById('sea_master_summary');
    const fallbackLocalInput = document.getElementById('property_local_price');
    const fallbackForeignInput = document.getElementById('property_foreign_price');

    const CARD_STYLE = 'background:#fff;border:1px solid #cfe0eb;border-radius:8px;padding:12px 14px;';
    const INPUT_STYLE = 'width:100%;box-sizing:border-box;padding:5px 8px;font-size:0.83rem;border:1px solid #c8d8e8;border-radius:4px;';
    const DAY_BTN_STYLE = 'cursor:pointer;font-size:0.72rem;font-weight:600;padding:2px 6px;border-radius:4px;border:1px solid #b0c8dc;background:#e8f4fb;color:#1d4b66;user-select:none;';
    const DAY_BTN_ON_STYLE = 'cursor:pointer;font-size:0.72rem;font-weight:600;padding:2px 6px;border-radius:4px;border:1px solid #1d7bb5;background:#1d7bb5;color:#fff;user-select:none;';

    function buildRow(data) {
        data = data || {};
        const card = document.createElement('div');
        card.style.cssText = CARD_STYLE;
        card.className = 'sea-roster-row';

        function inp(ph, val, type) {
            const el = document.createElement('input');
            el.type = type || 'text'; el.style.cssText = INPUT_STYLE;
            el.placeholder = ph; el.value = val || '';
            return el;
        }
        function numInp(ph, val) {
            const el = document.createElement('input');
            el.type = 'number'; el.min = '0'; el.step = '0.01'; el.style.cssText = INPUT_STYLE;
            el.placeholder = ph;
            el.value = (val !== undefined && val !== null && String(val) !== '') ? String(val) : '';
            return el;
        }
        function fieldWrap(labelText, inputEl) {
            const wrap = document.createElement('div');
            const lbl = document.createElement('label');
            lbl.textContent = labelText;
            lbl.style.cssText = 'display:block;font-size:0.7rem;font-weight:600;color:#4a6478;margin-bottom:2px;';
            wrap.appendChild(lbl); wrap.appendChild(inputEl); return wrap;
        }

        /* Row 1: schedule fields */
        const row1 = document.createElement('div');
        row1.style.cssText = 'display:grid;grid-template-columns:110px 1fr 80px 1fr 80px 28px;gap:6px;align-items:end;margin-bottom:8px;';
        const routeCodeInp = inp('e.g. MLE-ISL1', data.route_code || '');
        const originInp    = inp('Board stop',    data.origin || '');
        const depTimeInp   = inp('',              data.dep_time || '', 'time');
        const destInp      = inp('Alight stop',   data.destination || '');
        const arrTimeInp   = inp('',              data.arr_time || '', 'time');
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.style.cssText = 'cursor:pointer;width:28px;height:28px;border:none;border-radius:4px;background:#fde8e8;color:#c0392b;font-size:1rem;line-height:1;align-self:end;flex-shrink:0;';
        delBtn.title = 'Remove leg'; delBtn.innerHTML = '&times;';
        delBtn.addEventListener('click', function() { card.remove(); serializeRoster(); });
        row1.appendChild(fieldWrap('Route Code', routeCodeInp));
        row1.appendChild(fieldWrap('Origin / Board Stop', originInp));
        row1.appendChild(fieldWrap('Dep.', depTimeInp));
        row1.appendChild(fieldWrap('Destination / Alight Stop', destInp));
        row1.appendChild(fieldWrap('Arr.', arrTimeInp));
        row1.appendChild(delBtn);
        card.appendChild(row1);

        /* Row 2: operating days */
        const row2 = document.createElement('div');
        row2.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;align-items:center;margin-bottom:10px;';
        const daysLbl = document.createElement('span');
        daysLbl.textContent = 'Operating days:';
        daysLbl.style.cssText = 'font-size:0.72rem;font-weight:600;color:#4a6478;margin-right:4px;';
        row2.appendChild(daysLbl);
        const activeDays = Array.isArray(data.days) ? data.days : [];
        const dayButtons = {};
        DAYS.forEach(function(d) {
            const btn = document.createElement('button');
            btn.type = 'button'; btn.textContent = d;
            const on = activeDays.indexOf(d) !== -1;
            btn.style.cssText = on ? DAY_BTN_ON_STYLE : DAY_BTN_STYLE;
            btn.dataset.active = on ? '1' : '0';
            btn.addEventListener('click', function() {
                const nowOn = btn.dataset.active !== '1';
                btn.dataset.active = nowOn ? '1' : '0';
                btn.style.cssText = nowOn ? DAY_BTN_ON_STYLE : DAY_BTN_STYLE;
            });
            dayButtons[d] = btn; row2.appendChild(btn);
        });
        card.appendChild(row2);

        /* Row 3+: per-leg pricing */
        const pSection = document.createElement('div');
        pSection.style.cssText = 'border-top:1px dashed #c8d8e8;padding-top:8px;';
        const pHdr = document.createElement('span');
        pHdr.textContent = 'Leg Pricing — leave blank to use fallback prices';
        pHdr.style.cssText = 'font-size:0.71rem;font-weight:600;color:#4a6478;display:block;margin-bottom:6px;';
        pSection.appendChild(pHdr);
        const pGrid = document.createElement('div');
        pGrid.style.cssText = 'display:grid;grid-template-columns:90px 1fr 1fr 1fr;gap:5px;';
        function hdrCell(t) { const s = document.createElement('span'); s.textContent = t; s.style.cssText = 'font-size:0.7rem;font-weight:600;color:#777;display:flex;align-items:flex-end;padding-bottom:2px;'; return s; }
        function rowLbl(t, color) { const s = document.createElement('span'); s.textContent = t; s.style.cssText = 'font-size:0.7rem;font-weight:600;color:' + (color||'#1d4b66') + ';display:flex;align-items:flex-end;padding-bottom:2px;'; return s; }
        pGrid.appendChild(document.createElement('div'));
        pGrid.appendChild(hdrCell('Adult')); pGrid.appendChild(hdrCell('Child')); pGrid.appendChild(hdrCell('Infant'));
        const locAdult  = numInp('MVR', data.local_adult  ?? '');
        const locChild  = numInp('MVR', data.local_child  ?? '');
        const locInfant = numInp('MVR', data.local_infant ?? '');
        pGrid.appendChild(rowLbl('Local (MVR)')); pGrid.appendChild(locAdult); pGrid.appendChild(locChild); pGrid.appendChild(locInfant);
        const forAdult  = numInp('USD', data.foreign_adult  ?? '');
        const forChild  = numInp('USD', data.foreign_child  ?? '');
        const forInfant = numInp('USD', data.foreign_infant ?? '');
        pGrid.appendChild(rowLbl('Foreign (USD)')); pGrid.appendChild(forAdult); pGrid.appendChild(forChild); pGrid.appendChild(forInfant);
        pSection.appendChild(pGrid);
        card.appendChild(pSection);

        card._getData = function() {
            const selectedDays = DAYS.filter(function(d) { return dayButtons[d].dataset.active === '1'; });
            function pv(el) { return el.value !== '' ? parseFloat(el.value) : null; }
            return {
                route_code:     routeCodeInp.value.trim(),
                origin:         originInp.value.trim(),
                dep_time:       depTimeInp.value.trim(),
                destination:    destInp.value.trim(),
                arr_time:       arrTimeInp.value.trim(),
                days:           selectedDays,
                local_adult:    pv(locAdult),
                local_child:    pv(locChild),
                local_infant:   pv(locInfant),
                foreign_adult:  pv(forAdult),
                foreign_child:  pv(forChild),
                foreign_infant: pv(forInfant),
            };
        };

        return card;
    }

    function parseStopsFromTextarea() {
        if (!stopTextarea) return [];
        return String(stopTextarea.value || '')
            .split(/\r?\n/)
            .map(function (line) { return line.trim(); })
            .filter(function (line) { return line !== ''; });
    }

    function syncStopTextarea(stops) {
        if (!stopTextarea) return;
        stopTextarea.value = stops.join('\n');
    }

    function makeRouteCode(fromStop, toStop, index) {
        function tok(v) {
            return String(v || '')
                .split(/\s+/)
                .map(function (part) { return part.replace(/[^A-Za-z0-9]/g, ''); })
                .filter(function (part) { return part !== ''; })
                .map(function (part) { return part.substring(0, 3).toUpperCase(); })
                .join('')
                .substring(0, 6);
        }
        const from = tok(fromStop) || ('STOP' + (index + 1));
        const to = tok(toStop) || ('STOP' + (index + 2));
        return from + '-' + to;
    }

    function normalizedStopText(value) {
        return String(value || '').trim();
    }

    function uniquePairs(pairs) {
        const seen = {};
        const out = [];
        pairs.forEach(function (pair) {
            const origin = normalizedStopText(pair.origin);
            const destination = normalizedStopText(pair.destination);
            if (origin === '' || destination === '' || origin === destination) return;
            const key = origin.toLowerCase() + '||' + destination.toLowerCase();
            if (seen[key]) return;
            seen[key] = true;
            out.push({ origin: origin, destination: destination });
        });
        return out;
    }

    function buildMasterLegPairs(mode, origin, destination, stops) {
        const cleanOrigin = normalizedStopText(origin);
        const cleanDestination = normalizedStopText(destination);
        const cleanStops = stops
            .map(function (s) { return normalizedStopText(s); })
            .filter(function (s) {
                return s !== ''
                    && s.toLowerCase() !== cleanOrigin.toLowerCase()
                    && s.toLowerCase() !== cleanDestination.toLowerCase();
            });

        const chainPoints = [cleanOrigin].concat(cleanStops).concat([cleanDestination]);
        const pairs = [];

        if (mode === 'chain') {
            for (let i = 0; i < chainPoints.length - 1; i += 1) {
                pairs.push({ origin: chainPoints[i], destination: chainPoints[i + 1] });
            }
            return uniquePairs(pairs);
        }

        if (mode === 'origin_branches') {
            cleanStops.forEach(function (stop) {
                pairs.push({ origin: cleanOrigin, destination: stop });
            });
            pairs.push({ origin: cleanOrigin, destination: cleanDestination });
            return uniquePairs(pairs);
        }

        if (mode === 'destination_branches') {
            cleanStops.forEach(function (stop) {
                pairs.push({ origin: stop, destination: cleanDestination });
            });
            pairs.push({ origin: cleanOrigin, destination: cleanDestination });
            return uniquePairs(pairs);
        }

        if (mode === 'pool_roundtrip') {
            for (let i = 0; i < chainPoints.length - 1; i += 1) {
                pairs.push({ origin: chainPoints[i], destination: chainPoints[i + 1] });
            }
            for (let i = chainPoints.length - 1; i > 0; i -= 1) {
                pairs.push({ origin: chainPoints[i], destination: chainPoints[i - 1] });
            }
            return uniquePairs(pairs);
        }

        // branch_balanced (default): origin->each stop, each stop->destination, plus direct origin->destination.
        cleanStops.forEach(function (stop) {
            pairs.push({ origin: cleanOrigin, destination: stop });
        });
        cleanStops.forEach(function (stop) {
            pairs.push({ origin: stop, destination: cleanDestination });
        });
        pairs.push({ origin: cleanOrigin, destination: cleanDestination });
        return uniquePairs(pairs);
    }

    function generateLegRowsFromPairs(pairs) {
        rosterContainer.innerHTML = '';
        pairs.forEach(function (pair, idx) {
            addRow({
                route_code: makeRouteCode(pair.origin, pair.destination, idx),
                origin: pair.origin,
                destination: pair.destination,
                local_adult: fallbackLocalInput && fallbackLocalInput.value !== '' ? parseFloat(fallbackLocalInput.value) : null,
                foreign_adult: fallbackForeignInput && fallbackForeignInput.value !== '' ? parseFloat(fallbackForeignInput.value) : null,
            });
        });
        serializeRoster();
    }

    function renderStopEditor() {
        if (!stopEditor) return;
        const stops = parseStopsFromTextarea();
        stopEditor.innerHTML = '';

        if (stops.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'No stops added yet.';
            empty.style.cssText = 'margin:0; font-size:0.78rem; color:#6d8191;';
            stopEditor.appendChild(empty);
            return;
        }

        stops.forEach(function (stop, idx) {
            const row = document.createElement('div');
            row.style.cssText = 'display:grid; grid-template-columns:24px 1fr auto; gap:6px; align-items:center;';

            const indexChip = document.createElement('span');
            indexChip.textContent = String(idx + 1);
            indexChip.style.cssText = 'display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:12px; background:#e8f4fb; color:#1d4b66; font-size:0.72rem; font-weight:700;';

            const input = document.createElement('input');
            input.type = 'text';
            input.value = stop;
            input.className = 'ops-input';
            input.style.fontSize = '0.82rem';
            input.addEventListener('input', function () {
                const nextStops = parseStopsFromTextarea();
                nextStops[idx] = input.value.trim();
                syncStopTextarea(nextStops.filter(function (v) { return v !== ''; }));
            });

            const actions = document.createElement('div');
            actions.style.cssText = 'display:flex; gap:4px;';

            function mkBtn(label, onClick) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = label;
                btn.className = 'ops-button ops-button-secondary';
                btn.style.cssText = 'font-size:0.72rem; padding:4px 7px;';
                btn.addEventListener('click', onClick);
                return btn;
            }

            actions.appendChild(mkBtn('Up', function () {
                if (idx <= 0) return;
                const nextStops = parseStopsFromTextarea();
                const tmp = nextStops[idx - 1];
                nextStops[idx - 1] = nextStops[idx];
                nextStops[idx] = tmp;
                syncStopTextarea(nextStops);
                renderStopEditor();
            }));
            actions.appendChild(mkBtn('Down', function () {
                const nextStops = parseStopsFromTextarea();
                if (idx >= nextStops.length - 1) return;
                const tmp = nextStops[idx + 1];
                nextStops[idx + 1] = nextStops[idx];
                nextStops[idx] = tmp;
                syncStopTextarea(nextStops);
                renderStopEditor();
            }));
            actions.appendChild(mkBtn('Remove', function () {
                const nextStops = parseStopsFromTextarea().filter(function (_, i) { return i !== idx; });
                syncStopTextarea(nextStops);
                renderStopEditor();
            }));

            row.appendChild(indexChip);
            row.appendChild(input);
            row.appendChild(actions);
            stopEditor.appendChild(row);
        });
    }

    function syncPrimaryDefaultsFromRoster(data) {
        if (!Array.isArray(data) || data.length === 0) return;

        const firstLeg = data.find(function (leg) {
            return leg && (leg.origin || leg.destination || leg.dep_time || leg.arr_time);
        });

        if (!firstLeg) return;

        if (departureInput && !departureInput.value && firstLeg.dep_time) {
            departureInput.value = firstLeg.dep_time;
        }
        if (arrivalInput && !arrivalInput.value && firstLeg.arr_time) {
            arrivalInput.value = firstLeg.arr_time;
        }

        const departurePointInput = document.getElementById('property_departure_point');
        const arrivalPointInput = document.getElementById('property_arrival_point');
        if (departurePointInput && !departurePointInput.value && firstLeg.origin) {
            departurePointInput.value = firstLeg.origin;
        }
        if (arrivalPointInput && !arrivalPointInput.value && firstLeg.destination) {
            arrivalPointInput.value = firstLeg.destination;
        }

        syncDuration();
    }

    function serializeRoster() {
        const rows = rosterContainer.querySelectorAll('.sea-roster-row');
        const data = [];
        rows.forEach(function(row) { if (row._getData) data.push(row._getData()); });
        jsonInput.value = JSON.stringify(data);
        syncPrimaryDefaultsFromRoster(data);
    }

    function addRow(data) {
        const row = buildRow(data || {});
        rosterContainer.appendChild(row);
        // Re-serialize on any input change inside the row
        row.addEventListener('change', serializeRoster);
        row.addEventListener('input', serializeRoster);
        serializeRoster();
    }

    addBtn.addEventListener('click', function() { addRow(); });

    if (stopTextarea) {
        stopTextarea.addEventListener('input', renderStopEditor);
    }
    if (stopAddBtn && stopNewInput) {
        stopAddBtn.addEventListener('click', function () {
            const next = stopNewInput.value.trim();
            if (next === '') return;
            const stops = parseStopsFromTextarea();
            stops.push(next);
            syncStopTextarea(stops);
            stopNewInput.value = '';
            renderStopEditor();
        });
    }

    if (generateLegsBtn) {
        generateLegsBtn.addEventListener('click', function () {
            const stops = parseStopsFromTextarea();
            if (stops.length < 2) {
                alert('Add at least 2 stops to generate route legs.');
                return;
            }

            const sequentialPairs = [];
            for (let i = 0; i < stops.length - 1; i += 1) {
                sequentialPairs.push({ origin: stops[i], destination: stops[i + 1] });
            }
            generateLegRowsFromPairs(uniquePairs(sequentialPairs));
        });
    }

    if (masterApplyBtn) {
        masterApplyBtn.addEventListener('click', function () {
            const departurePointInput = document.getElementById('property_departure_point');
            const arrivalPointInput = document.getElementById('property_arrival_point');
            const origin = normalizedStopText((masterOriginInput && masterOriginInput.value) || (departurePointInput && departurePointInput.value) || '');
            const destination = normalizedStopText((masterDestinationInput && masterDestinationInput.value) || (arrivalPointInput && arrivalPointInput.value) || '');
            const mode = (masterModeInput && masterModeInput.value) ? masterModeInput.value : 'pool_roundtrip';

            if (origin === '' || destination === '') {
                alert('Provide master origin and destination before generating legs.');
                return;
            }

            if (departurePointInput && departurePointInput.value.trim() === '') {
                departurePointInput.value = origin;
            }
            if (arrivalPointInput && arrivalPointInput.value.trim() === '') {
                arrivalPointInput.value = destination;
            }

            const stops = parseStopsFromTextarea();
            const pairs = buildMasterLegPairs(mode, origin, destination, stops);
            if (pairs.length === 0) {
                alert('No legs could be generated. Add at least one stop or switch generation mode.');
                return;
            }

            generateLegRowsFromPairs(pairs);
            if (masterSummaryEl) {
                masterSummaryEl.textContent = origin + ' -> ' + destination + ' (Master Route) | ' + pairs.length + ' legs generated';
            }
        });
    }

    /* Serialize on form submit */
    const form = addBtn.closest('form');
    if (form) {
        form.addEventListener('submit', function() { serializeRoster(); });
    }

    /* Start with one empty row for a blank create form */
    addRow();
    const departurePointInput = document.getElementById('property_departure_point');
    const arrivalPointInput = document.getElementById('property_arrival_point');
    if (masterOriginInput && departurePointInput && departurePointInput.value.trim() !== '') {
        masterOriginInput.value = departurePointInput.value.trim();
    }
    if (masterDestinationInput && arrivalPointInput && arrivalPointInput.value.trim() !== '') {
        masterDestinationInput.value = arrivalPointInput.value.trim();
    }
    if (masterSummaryEl && masterOriginInput && masterDestinationInput) {
        const summaryOrigin = masterOriginInput.value.trim();
        const summaryDestination = masterDestinationInput.value.trim();
        if (summaryOrigin !== '' && summaryDestination !== '') {
            masterSummaryEl.textContent = summaryOrigin + ' -> ' + summaryDestination + ' (Master Route)';
        }
    }
    renderStopEditor();
})();
</script>