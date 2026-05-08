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
        <div class="ops-field">
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
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Service Pricing</label>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">Enter the per-seat rate for local and foreign guests. Local rates use MVR. Foreign rates use USD.</p>
        </div>
        
        <div class="ops-field">
            <label for="property_local_price">Price Per Seat - Local (MVR)</label>
            <input id="property_local_price" name="local_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('local_price') }}" placeholder="MVR 0.00" required>
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

        {{-- Route schedule roster --}}
        <div class="ops-field ops-field-wide" style="grid-column:1/-1; border:1px solid #cfe0eb; border-radius:12px; background:#f7fbff; padding:14px 14px 12px; margin-top:1rem; order:-1;">
            <p style="margin:0 0 0.3rem; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#1d4b66;">Step 1. Multi-leg Route Schedule Roster</p>
            <p style="font-size:0.85rem; color:#51697b; margin:0 0 0.35rem;">Add one row per timetable leg. Use separate rows for each direction, stop pattern, or departure slot.</p>
            <p style="font-size:0.8rem; color:#6d8191; margin:0;">Example: `MLE-MFUSHI` for Malé → Maafushi and `MFUSHI-MLE` for the return leg. This is now the primary timetable input.</p>
        </div>

        <div class="ops-field ops-field-wide" style="grid-column:1/-1;">
            <input type="hidden" id="sea_route_schedules_json" name="route_schedules">

            {{-- Roster table header --}}
            <div class="sea-roster-header" style="display:grid; grid-template-columns:130px 1fr 90px 1fr 90px 1fr 36px; gap:6px; margin-bottom:4px; font-size:0.75rem; font-weight:600; color:#555; padding:0 2px;">
                <span>Route Code</span>
                <span>Origin / Departure Stop</span>
                <span>Dep. Time</span>
                <span>Destination / Arrival Stop</span>
                <span>Arr. Time</span>
                <span>Operating Days</span>
                <span></span>
            </div>

            <div id="sea_roster_rows" style="display:flex; flex-direction:column; gap:6px;"></div>

            <button type="button" id="sea_roster_add_btn" class="ops-button ops-button-secondary" style="margin-top:10px; font-size:0.85rem;">
                + Add Route Leg
            </button>
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

    const ROW_STYLE = 'display:grid;grid-template-columns:130px 1fr 90px 1fr 90px 1fr 36px;gap:6px;align-items:start;background:#f7fbff;border:1px solid #cfe0eb;border-radius:6px;padding:8px;';
    const INPUT_STYLE = 'width:100%;box-sizing:border-box;padding:5px 8px;font-size:0.83rem;border:1px solid #c8d8e8;border-radius:4px;';
    const DAY_WRAP_STYLE = 'display:flex;flex-wrap:wrap;gap:4px;';
    const DAY_BTN_STYLE = 'cursor:pointer;font-size:0.72rem;font-weight:600;padding:2px 6px;border-radius:4px;border:1px solid #b0c8dc;background:#e8f4fb;color:#1d4b66;user-select:none;';
    const DAY_BTN_ON_STYLE = 'cursor:pointer;font-size:0.72rem;font-weight:600;padding:2px 6px;border-radius:4px;border:1px solid #1d7bb5;background:#1d7bb5;color:#fff;user-select:none;';
    const DEL_BTN_STYLE = 'cursor:pointer;width:30px;height:30px;border:none;border-radius:4px;background:#fde8e8;color:#c0392b;font-size:1rem;line-height:1;';

    function buildRow(data) {
        data = data || {};
        const row = document.createElement('div');
        row.style.cssText = ROW_STYLE;
        row.className = 'sea-roster-row';

        function inp(placeholder, val, type) {
            const el = document.createElement('input');
            el.type = type || 'text';
            el.style.cssText = INPUT_STYLE;
            el.placeholder = placeholder;
            el.value = val || '';
            return el;
        }

        const routeCodeInp  = inp('e.g. MLE-MFUSHI',       data.route_code  || '');
        const originInp     = inp('e.g. Male Jetty No.1',   data.origin      || '');
        const depTimeInp    = inp('', data.dep_time || '', 'time');
        const destInp       = inp('e.g. Maafushi Jetty',    data.destination || '');
        const arrTimeInp    = inp('', data.arr_time || '', 'time');

        /* Days toggle buttons */
        const daysWrap = document.createElement('div');
        daysWrap.style.cssText = DAY_WRAP_STYLE;
        const activeDays = Array.isArray(data.days) ? data.days : [];
        const dayButtons = {};
        DAYS.forEach(function(d) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = d;
            const on = activeDays.indexOf(d) !== -1;
            btn.style.cssText = on ? DAY_BTN_ON_STYLE : DAY_BTN_STYLE;
            btn.dataset.active = on ? '1' : '0';
            btn.addEventListener('click', function() {
                const nowOn = btn.dataset.active !== '1';
                btn.dataset.active = nowOn ? '1' : '0';
                btn.style.cssText = nowOn ? DAY_BTN_ON_STYLE : DAY_BTN_STYLE;
            });
            dayButtons[d] = btn;
            daysWrap.appendChild(btn);
        });

        /* Delete button */
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.style.cssText = DEL_BTN_STYLE;
        delBtn.title = 'Remove this leg';
        delBtn.innerHTML = '&times;';
        delBtn.addEventListener('click', function() { row.remove(); serializeRoster(); });

        row.appendChild(routeCodeInp);
        row.appendChild(originInp);
        row.appendChild(depTimeInp);
        row.appendChild(destInp);
        row.appendChild(arrTimeInp);
        row.appendChild(daysWrap);
        row.appendChild(delBtn);

        row._getData = function() {
            const selectedDays = DAYS.filter(function(d) { return dayButtons[d].dataset.active === '1'; });
            return {
                route_code:  routeCodeInp.value.trim(),
                origin:      originInp.value.trim(),
                dep_time:    depTimeInp.value.trim(),
                destination: destInp.value.trim(),
                arr_time:    arrTimeInp.value.trim(),
                days:        selectedDays,
            };
        };

        return row;
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

    /* Serialize on form submit */
    const form = addBtn.closest('form');
    if (form) {
        form.addEventListener('submit', function() { serializeRoster(); });
    }

    /* Start with one empty row for a blank create form */
    addRow();
})();
</script>
