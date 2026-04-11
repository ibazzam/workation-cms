<section id="vendorAddressSection" class="ops-section" aria-label="Address details" style="margin-top:12px;">
    <div class="ops-header">
        <p class="ops-title">Address</p>
        <span class="ops-chip">Billing / Operational</span>
    </div>
    <div class="profile-grid">
        <div class="profile-field"><label>Street</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_street_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Country</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_country ?? '') }}" readonly></div>
        <div class="profile-field"><label>State / Atoll</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_state ?? '') }}" readonly></div>
        <div class="profile-field"><label>City / Island</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_city ?? '') }}" readonly></div>
        <div class="profile-field ops-field-wide"><label>Full Address</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_address ?? '') }}" readonly></div>
    </div>

    <details class="ops-form" style="margin-top:12px;">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Edit Address</summary>
        <form method="POST" action="/portal/vendor/address/update" style="margin-top:12px;">
            @csrf
            <div class="profile-grid">
                <div class="profile-field"><label for="billing_street_name">Street / Building / Lot</label><input id="billing_street_name" name="billing_street_name" class="profile-input" type="text" value="{{ old('billing_street_name', optional($billingRow)->billing_street_name ?? '') }}" required></div>
                <div class="profile-field"><label for="billing_country">Country</label><input id="billing_country" name="billing_country" class="profile-input" type="text" value="{{ old('billing_country', optional($billingRow)->billing_country ?? 'Maldives') }}" required></div>
                <div class="profile-field"><label for="billing_state">State / Atoll</label><input id="billing_state" name="billing_state" class="profile-input" type="text" value="{{ old('billing_state', optional($billingRow)->billing_state ?? '') }}" required></div>
                <div class="profile-field"><label for="billing_city">City / Island</label><input id="billing_city" name="billing_city" class="profile-input" type="text" value="{{ old('billing_city', optional($billingRow)->billing_city ?? '') }}" required></div>
                <div class="profile-field ops-field-wide"><label for="billing_address">Full Address</label><textarea id="billing_address" name="billing_address" class="ops-textarea" maxlength="2000">{{ old('billing_address', optional($billingRow)->billing_address ?? '') }}</textarea></div>
            </div>
            <button class="btn btn-primary" type="submit">Save Address</button>
        </form>
    </details>
</section>
