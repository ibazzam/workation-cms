{{-- Dedicated create form entry for Corporate Retreat packages --}}
<div class="ops-note" style="margin-bottom:12px; border:1px solid #cfe0eb; border-radius:10px; padding:10px 12px; background:#f7fbff;">
    <p style="margin:0 0 4px; font-weight:700; color:#1d4b66;">Corporate Retreat Package Builder</p>
    <p style="margin:0; color:#416479; font-size:0.84rem; line-height:1.45;">
        This form is dedicated to corporate retreats. Package mode is enabled automatically and published under the corporate retreat category in the customer-facing portal.
    </p>
</div>

@include('vendor-portal.partials.forms.create.excursion')

<script>
(() => {
    const retreatToggle = document.getElementById('property_is_corporate_retreat');
    const retreatPresetSelect = document.getElementById('property_retreat_package_size');
    const retreatSizeBlock = document.querySelector('[data-retreat-package-size-block]');
    const minPaxInput = document.getElementById('property_excursion_min_pax');
    const maxPaxInput = document.getElementById('property_excursion_max_pax');
    const maxGuestsInput = document.getElementById('property_max_guests');
    const form = retreatToggle ? retreatToggle.closest('form') : document.querySelector('form.ops-form');

    if (!retreatToggle || !form) {
        return;
    }

    retreatToggle.checked = true;
    retreatToggle.disabled = true;
    const retreatToggleField = retreatToggle.closest('.ops-field');
    if (retreatToggleField) {
        retreatToggleField.style.display = 'none';
    }

    let hiddenRetreatInput = form.querySelector('input[name="is_corporate_retreat"][type="hidden"]');
    if (!hiddenRetreatInput) {
        hiddenRetreatInput = document.createElement('input');
        hiddenRetreatInput.type = 'hidden';
        hiddenRetreatInput.name = 'is_corporate_retreat';
        form.appendChild(hiddenRetreatInput);
    }
    hiddenRetreatInput.value = '1';

    const listingCategoryInput = form.querySelector('input[name="listing_category"]');
    if (listingCategoryInput) {
        listingCategoryInput.value = 'corporate_retreat';
    }

    const relabelField = (id, labelText, placeholderText = null) => {
        const input = document.getElementById(id);
        if (!input) {
            return;
        }
        const field = input.closest('.ops-field');
        if (!field) {
            return;
        }
        const label = field.querySelector('label');
        if (label) {
            label.textContent = labelText;
        }
        if (placeholderText !== null && (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) {
            input.setAttribute('placeholder', placeholderText);
        }
    };

    const hideField = (id) => {
        const input = document.getElementById(id);
        if (!input) {
            return;
        }
        const field = input.closest('.ops-field');
        if (field) {
            field.style.display = 'none';
        }
    };

    const renameSectionTitle = (currentTitle, newTitle) => {
        const allSectionTitles = Array.from(form.querySelectorAll('.ops-field p'));
        const sectionTitle = allSectionTitles.find((node) => String(node.textContent || '').trim().toLowerCase() === currentTitle.toLowerCase());
        if (sectionTitle) {
            sectionTitle.textContent = newTitle;
        }
    };

    relabelField('property_name', 'Retreat Package Name *', 'e.g. Team Alignment Retreat - 2 Days / 1 Night');
    relabelField('property_short_description', 'Short Summary', 'One-line business outcome summary shown on the listing card');
    relabelField('property_description', 'Programme Overview *', 'Describe business outcomes, facilitation style, learning objectives, and expected deliverables.');
    relabelField('property_excursion_duration_minutes', 'Session Duration (minutes)', 'e.g. 180');
    relabelField('property_activity_start_time', 'Programme Start Time');
    relabelField('property_activity_end_time', 'Programme End Time');
    relabelField('property_departure_point', 'Venue / Meeting Location', 'e.g. Resort conference hall, offsite boardroom, island campus');
    relabelField('property_departure_time', 'Standard Session Start Time');
    relabelField('property_service_radius_km', 'Service Radius (km)');
    relabelField('property_excursion_min_pax', 'Minimum Participants');
    relabelField('property_excursion_max_pax', 'Maximum Participants');
    relabelField('property_max_guests', 'Overall Capacity');
    relabelField('property_inclusions', 'What\'s Included (Programme Components)');
    relabelField('property_exclusions', 'What\'s Not Included');
    relabelField('property_activity_schedule', 'Retreat Agenda (optional)', '09:00 Welcome and goals\n10:00 Leadership workshop\n13:00 Team challenge\n16:00 Action planning');

    renameSectionTitle('Activity Details', 'Programme Details');
    renameSectionTitle('Departure & Assembly Point', 'Venue & Access');
    renameSectionTitle('Transfer & Slot Configuration', 'Scheduling Configuration');
    renameSectionTitle('Safety & Equipment', 'Programme Policies');
    renameSectionTitle('Schedule / Itinerary', 'Agenda / Run Sheet');

    const excursionTypeSelect = document.getElementById('property_excursion_type');
    if (excursionTypeSelect) {
        const previousType = String(excursionTypeSelect.value || '').trim().toLowerCase();
        const corporateTypeOptions = [
            { value: 'leadership_offsite', label: 'Leadership Offsite' },
            { value: 'team_building', label: 'Team Building Programme' },
            { value: 'strategy_retreat', label: 'Strategy Retreat' },
            { value: 'wellness_retreat', label: 'Wellness & Burnout Recovery Retreat' },
            { value: 'innovation_sprint', label: 'Innovation Sprint' },
            { value: 'annual_summit', label: 'Annual Summit / All Hands' },
            { value: 'executive_board', label: 'Executive / Board Retreat' },
            { value: 'custom', label: 'Custom Corporate Programme' },
        ];

        excursionTypeSelect.innerHTML = '<option value="">Select programme type</option>' + corporateTypeOptions
            .map((option) => '<option value="' + option.value + '">' + option.label + '</option>')
            .join('');

        const matched = corporateTypeOptions.some((option) => option.value === previousType);
        excursionTypeSelect.value = matched ? previousType : 'leadership_offsite';

        const typeField = excursionTypeSelect.closest('.ops-field');
        if (typeField) {
            const typeLabel = typeField.querySelector('label');
            if (typeLabel) {
                typeLabel.textContent = 'Programme Type';
            }
        }
    }

    const difficultySelect = document.getElementById('property_excursion_difficulty');
    if (difficultySelect) {
        const previousDifficulty = String(difficultySelect.value || '').trim().toLowerCase();
        const difficultyOptions = [
            { value: 'executive', label: 'Executive - presentation focused' },
            { value: 'interactive', label: 'Interactive - workshop based' },
            { value: 'immersive', label: 'Immersive - activity intensive' },
            { value: 'hybrid', label: 'Hybrid - indoor + outdoor mix' },
        ];

        difficultySelect.innerHTML = '<option value="">Select delivery style</option>' + difficultyOptions
            .map((option) => '<option value="' + option.value + '">' + option.label + '</option>')
            .join('');

        const matched = difficultyOptions.some((option) => option.value === previousDifficulty);
        difficultySelect.value = matched ? previousDifficulty : 'interactive';

        const difficultyField = difficultySelect.closest('.ops-field');
        if (difficultyField) {
            const difficultyLabel = difficultyField.querySelector('label');
            if (difficultyLabel) {
                difficultyLabel.textContent = 'Delivery Style';
            }
        }
    }

    hideField('property_excursion_min_age');
    hideField('property_safety_waiver_required');
    hideField('property_equipment_rental_available');
    hideField('property_weather_cancellation_policy');

    const specialInstructions = document.getElementById('property_special_instructions');
    if (specialInstructions) {
        specialInstructions.setAttribute('placeholder', 'Add operational notes such as facilitator setup, AV needs, breakout room arrangement, dress code, and arrival instructions.');
    }

    const cancellationPolicy = document.getElementById('property_cancellation_policy');
    if (cancellationPolicy) {
        cancellationPolicy.setAttribute('placeholder', 'e.g. Free reschedule up to 7 days prior. 50% charge for cancellations within 72 hours.');
    }

    const cancelLink = form.querySelector('a.btn.btn-secondary[href*="/vendor/listings/excursion"]');
    if (cancelLink) {
        cancelLink.setAttribute('href', '/vendor/listings/corporate-retreat');
    }

    const pricingSection = form.querySelector('.listing-form-section');
    if (pricingSection) {
        const pricingField = pricingSection.closest('.ops-field');
        if (pricingField) {
            const existingPackagePrice = String(
                (document.getElementById('property_adult_price_foreign') || {}).value
                || (document.getElementById('property_adult_price_local') || {}).value
                || ''
            ).trim();

            const packagePanel = document.createElement('section');
            packagePanel.className = 'listing-form-section';
            packagePanel.setAttribute('aria-label', 'Corporate retreat package configuration');
            packagePanel.style.marginBottom = '10px';
            packagePanel.innerHTML = [
                '<div class="listing-form-section-head">',
                '  <h4>Corporate Package Setup</h4>',
                '  <p>Define total package pricing, included services, date-booking mode, and extra participant policy.</p>',
                '</div>',
                '<div class="ops-form-grid" style="margin-top:10px;">',
                '  <div class="ops-field">',
                '    <label for="property_total_package_price">Total Package Price (MVR) <span style="color:#c0392b;">*</span></label>',
                '    <input id="property_total_package_price" name="total_package_price" class="ops-input" type="number" min="0" step="0.01" required placeholder="e.g. 25000" value="' + existingPackagePrice.replace(/"/g, '&quot;') + '">',
                '    <p class="map-help">This is the full package price shown to customers for the selected package size.</p>',
                '  </div>',
                '  <div class="ops-field">',
                '    <label for="property_allow_specific_date_booking">Customer Books Specific Date</label>',
                '    <select id="property_allow_specific_date_booking" name="allow_specific_date_booking" class="ops-select">',
                '      <option value="1" selected>Yes - customer selects preferred date</option>',
                '      <option value="0">No - inquiry and confirmation only</option>',
                '    </select>',
                '  </div>',
                '  <div class="ops-field">',
                '    <label for="property_package_included_pax">Package Includes (people)</label>',
                '    <input id="property_package_included_pax" name="package_included_pax" class="ops-input" type="number" min="1" step="1" value="10">',
                '    <p class="map-help">Guests up to this number are included in total package price.</p>',
                '  </div>',
                '  <div class="ops-field">',
                '    <label for="property_extra_person_price">Extra Person Price (MVR)</label>',
                '    <input id="property_extra_person_price" name="extra_person_price" class="ops-input" type="number" min="0" step="0.01" placeholder="e.g. 1200">',
                '  </div>',
                '  <div class="ops-field ops-field-wide">',
                '    <label for="property_extra_person_policy">Extra Person Policy</label>',
                '    <textarea id="property_extra_person_policy" name="extra_person_policy" class="ops-textarea" rows="2" maxlength="1000" placeholder="e.g. Additional participants are charged per person and subject to venue capacity."></textarea>',
                '  </div>',
                '  <div class="ops-field ops-field-wide">',
                '    <label>Included Services (tick all that apply)</label>',
                '    <div style="display:flex;flex-wrap:wrap;gap:8px;">',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="accommodation" style="margin-right:6px;">Accommodation</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="transfer" style="margin-right:6px;">Transfer</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="meals" style="margin-right:6px;">Meals</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="hall_or_retreat_space" style="margin-right:6px;">Hall / Retreat Space</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="facilitation" style="margin-right:6px;">Facilitation</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="activities" style="margin-right:6px;">Activities</label>',
                '      <label class="ops-chip" style="cursor:pointer;"><input type="checkbox" name="included_services[]" value="av_setup" style="margin-right:6px;">AV Setup</label>',
                '    </div>',
                '    <p class="map-help" style="margin-top:6px;">Use inclusions/exclusions below for details. Programme can be customized based on customer request.</p>',
                '  </div>',
                '</div>'
            ].join('');

            pricingField.parentNode.insertBefore(packagePanel, pricingField);
            pricingField.style.display = 'none';
        }
    }

    const packagePriceInput = document.getElementById('property_total_package_price');
    const includedPaxInput = document.getElementById('property_package_included_pax');
    const oldAdultLocal = document.getElementById('property_adult_price_local');
    const oldChildLocal = document.getElementById('property_child_price_local');
    const oldAdultForeign = document.getElementById('property_adult_price_foreign');
    const oldChildForeign = document.getElementById('property_child_price_foreign');
    const departureTimeMode = document.getElementById('property_departure_time_mode');
    const returnTimeMode = document.getElementById('property_return_time_mode');
    const allowSpecificDateSelect = document.getElementById('property_allow_specific_date_booking');

    const syncLegacyPricingInputs = () => {
        if (!packagePriceInput) {
            return;
        }
        const packagePrice = String(packagePriceInput.value || '').trim();
        if (oldAdultLocal) oldAdultLocal.value = packagePrice;
        if (oldChildLocal) oldChildLocal.value = packagePrice;
        if (oldAdultForeign) oldAdultForeign.value = packagePrice;
        if (oldChildForeign) oldChildForeign.value = packagePrice;
        const basePriceInput = form.querySelector('input[name="base_price"]');
        if (basePriceInput) {
            basePriceInput.value = packagePrice !== '' ? packagePrice : '0';
        }
    };

    const syncDateBookingMode = () => {
        const allowsDateSelection = allowSpecificDateSelect && String(allowSpecificDateSelect.value || '1') === '1';
        if (departureTimeMode) {
            departureTimeMode.value = allowsDateSelection ? 'slots' : 'fixed';
        }
        if (returnTimeMode) {
            returnTimeMode.value = allowsDateSelection ? 'slots' : 'fixed';
        }
    };

    const syncIncludedPaxDefaults = () => {
        if (!includedPaxInput || !maxPaxInput) {
            return;
        }
        if (String(includedPaxInput.value || '').trim() === '') {
            includedPaxInput.value = String(maxPaxInput.value || '10');
        }
    };

    if (allowSpecificDateSelect) {
        allowSpecificDateSelect.addEventListener('change', syncDateBookingMode);
    }
    if (packagePriceInput) {
        packagePriceInput.addEventListener('input', syncLegacyPricingInputs);
        packagePriceInput.addEventListener('change', syncLegacyPricingInputs);
    }

    form.addEventListener('submit', () => {
        syncLegacyPricingInputs();
        syncDateBookingMode();
        syncIncludedPaxDefaults();

        const programModeInputName = 'program_customization_mode';
        let programModeInput = form.querySelector('input[name="' + programModeInputName + '"]');
        if (!programModeInput) {
            programModeInput = document.createElement('input');
            programModeInput.type = 'hidden';
            programModeInput.name = programModeInputName;
            form.appendChild(programModeInput);
        }
        programModeInput.value = 'customer_defined_vendor_arranged';
    });

    syncLegacyPricingInputs();
    syncDateBookingMode();
    syncIncludedPaxDefaults();

    const presetMap = {
        getaway: { min: 1, max: 10 },
        retreat: { min: 1, max: 50 },
        summit: { min: 1, max: 150 },
    };

    if (retreatSizeBlock) {
        retreatSizeBlock.hidden = false;
    }

    if (retreatPresetSelect) {
        const preferred = String(retreatPresetSelect.value || '').trim().toLowerCase();
        retreatPresetSelect.innerHTML = [
            '<option value="getaway">Getaway (1-10 pax)</option>',
            '<option value="retreat">Retreat (1-50 pax)</option>',
            '<option value="summit">Summit (1-150 pax)</option>'
        ].join('');

        retreatPresetSelect.value = Object.prototype.hasOwnProperty.call(presetMap, preferred) ? preferred : 'getaway';

        const applyPreset = () => {
            const key = String(retreatPresetSelect.value || '').trim().toLowerCase();
            const cfg = presetMap[key] || presetMap.getaway;
            if (minPaxInput) minPaxInput.value = String(cfg.min);
            if (maxPaxInput) maxPaxInput.value = String(cfg.max);
            if (maxGuestsInput) maxGuestsInput.value = String(cfg.max);
        };

        retreatPresetSelect.addEventListener('change', applyPreset);
        applyPreset();
    }
})();
</script>