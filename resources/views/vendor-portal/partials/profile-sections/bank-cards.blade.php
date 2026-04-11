<section id="vendorBankCardSection" class="ops-section" aria-label="Bank and cards details" style="margin-top:12px;">
    <div class="ops-header">
        <p class="ops-title">Bank &amp; Cards Details</p>
        <span class="ops-chip">Payout setup</span>
    </div>
    <div class="profile-grid">
        <div class="profile-field"><label>Business Name</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->business_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Billing Email</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->billing_email ?? '') }}" readonly></div>
        <div class="profile-field"><label>Payout Method</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->payout_method ?? '') }}" readonly></div>
        <div class="profile-field"><label>Beneficiary Name</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->beneficiary_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Bank Name</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->bank_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Bank Account Last 4</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->bank_account_last4 ?? '') }}" readonly></div>
    </div>

    <details class="ops-form" style="margin-top:12px;">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Edit Bank &amp; Cards Details</summary>
        <form method="POST" action="/portal/vendor/billing/update" style="margin-top:12px;">
            @csrf
            <div class="profile-grid">
                <div class="profile-field"><label for="business_name">Business Name</label><input id="business_name" name="business_name" class="profile-input" type="text" value="{{ old('business_name', optional($billingRow)->business_name ?? ($vendorProfile['company_name'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="tax_id">Tax ID</label><input id="tax_id" name="tax_id" class="profile-input" type="text" value="{{ old('tax_id', optional($billingRow)->tax_id ?? '') }}"></div>
                <div class="profile-field"><label for="billing_email">Billing Email</label><input id="billing_email" name="billing_email" class="profile-input" type="email" value="{{ old('billing_email', optional($billingRow)->billing_email ?? ($vendorProfile['email'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="payout_method">Payout Method</label><select id="payout_method" name="payout_method" class="ops-select" required><option value="bank_transfer" @selected(old('payout_method', optional($billingRow)->payout_method ?? 'bank_transfer') === 'bank_transfer')>Bank Transfer</option><option value="mobile_wallet" @selected(old('payout_method', optional($billingRow)->payout_method) === 'mobile_wallet')>Mobile Wallet</option><option value="manual" @selected(old('payout_method', optional($billingRow)->payout_method) === 'manual')>Manual</option></select></div>
                <div class="profile-field"><label for="beneficiary_name">Beneficiary Name</label><input id="beneficiary_name" name="beneficiary_name" class="profile-input" type="text" value="{{ old('beneficiary_name', optional($billingRow)->beneficiary_name ?? ($vendorProfile['name'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="payout_reference">Payout Reference</label><input id="payout_reference" name="payout_reference" class="profile-input" type="text" value="{{ old('payout_reference', optional($billingRow)->payout_reference ?? '') }}"></div>
                <div class="profile-field"><label for="bank_name">Bank Name</label><input id="bank_name" name="bank_name" class="profile-input" type="text" value="{{ old('bank_name', optional($billingRow)->bank_name ?? '') }}"></div>
                <div class="profile-field"><label for="swift_code">SWIFT Code</label><input id="swift_code" name="swift_code" class="profile-input" type="text" value="{{ old('swift_code', optional($billingRow)->swift_code ?? '') }}"></div>
                <div class="profile-field"><label for="bank_account_number">Bank Account Number</label><input id="bank_account_number" name="bank_account_number" class="profile-input" type="text" value="{{ old('bank_account_number', optional($billingRow)->bank_account_number ?? '') }}" required></div>
                <div class="profile-field"><label for="bank_account_last4">Bank Account Last 4</label><input id="bank_account_last4" name="bank_account_last4" class="profile-input" type="text" value="{{ old('bank_account_last4', optional($billingRow)->bank_account_last4 ?? '') }}"></div>
                <div class="profile-field"><label for="currency">Currency</label><select id="currency" name="currency" class="ops-select" required><option value="MVR" @selected(old('currency', optional($billingRow)->currency ?? 'MVR') === 'MVR')>MVR</option><option value="USD" @selected(old('currency', optional($billingRow)->currency) === 'USD')>USD</option></select></div>
                <div class="profile-field"><label for="invoice_prefix">Invoice Prefix</label><input id="invoice_prefix" name="invoice_prefix" class="profile-input" type="text" value="{{ old('invoice_prefix', optional($billingRow)->invoice_prefix ?? 'INV') }}"></div>
                <input type="hidden" name="billing_street_name" value="{{ old('billing_street_name', optional($billingRow)->billing_street_name ?? '') }}">
                <input type="hidden" name="billing_country" value="{{ old('billing_country', optional($billingRow)->billing_country ?? 'Maldives') }}">
                <input type="hidden" name="billing_state" value="{{ old('billing_state', optional($billingRow)->billing_state ?? '') }}">
                <input type="hidden" name="billing_city" value="{{ old('billing_city', optional($billingRow)->billing_city ?? '') }}">
                <input type="hidden" name="billing_address" value="{{ old('billing_address', optional($billingRow)->billing_address ?? '') }}">
            </div>
            <button class="btn btn-primary" type="submit">Save Bank &amp; Cards</button>
        </form>
    </details>
</section>
