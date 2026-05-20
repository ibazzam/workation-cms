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