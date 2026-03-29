<section id="vendorProfileBillingSettings" class="card ops-section" aria-label="Vendor billing settings" data-panel-group="billing">
            <div class="ops-header">
                <p class="ops-title">Billing Details</p>
                <span class="ops-chip">{{ $vendorBilling ? 'Configured' : 'Pending' }}</span>
            </div>
            <form class="ops-form" method="POST" action="/portal/vendor/billing/update">
                @csrf
                <div class="ops-form-grid">
                    <div class="ops-field">
                        <label for="billing_business_name">Business Name</label>
                        <input id="billing_business_name" name="business_name" class="ops-input" type="text" maxlength="190" value="{{ old('business_name', optional($vendorBilling)->business_name ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_tax_id">Tax ID</label>
                        <input id="billing_tax_id" name="tax_id" class="ops-input" type="text" maxlength="120" value="{{ old('tax_id', optional($vendorBilling)->tax_id ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_email">Billing Email</label>
                        <input id="billing_email" name="billing_email" class="ops-input" type="email" maxlength="190" value="{{ old('billing_email', optional($vendorBilling)->billing_email ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_payout_method">Payout Method</label>
                        <select id="billing_payout_method" name="payout_method" class="ops-select" required>
                            <option value="bank_transfer" @selected((optional($vendorBilling)->payout_method ?? '') === 'bank_transfer')>Bank Transfer</option>
                            <option value="mobile_wallet" @selected((optional($vendorBilling)->payout_method ?? '') === 'mobile_wallet')>Mobile Wallet</option>
                            <option value="manual" @selected((optional($vendorBilling)->payout_method ?? '') === 'manual')>Manual</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_beneficiary_name">Beneficiary / Account Name</label>
                        <input id="billing_beneficiary_name" name="beneficiary_name" class="ops-input" type="text" maxlength="190" value="{{ old('beneficiary_name', optional($vendorBilling)->beneficiary_name ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_payout_reference">Payout Reference</label>
                        <input id="billing_payout_reference" name="payout_reference" class="ops-input" type="text" maxlength="190" value="{{ old('payout_reference', optional($vendorBilling)->payout_reference ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_bank_name">Bank Name</label>
                        <input id="billing_bank_name" name="bank_name" class="ops-input" type="text" maxlength="190" value="{{ old('bank_name', optional($vendorBilling)->bank_name ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_swift_code">SWIFT Code</label>
                        <input id="billing_swift_code" name="swift_code" class="ops-input" type="text" maxlength="20" value="{{ old('swift_code', optional($vendorBilling)->swift_code ?? '') }}" placeholder="e.g. MALAADMV">
                    </div>
                    <div class="ops-field">
                        <label for="billing_account_number">Account Number (Full)</label>
                        <input id="billing_account_number" name="bank_account_number" class="ops-input" type="text" maxlength="60" value="{{ old('bank_account_number', optional($vendorBilling)->bank_account_number ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_currency">Currency</label>
                        <select id="billing_currency" name="currency" class="ops-select" required>
                            <option value="MVR" @selected(strtoupper((string) old('currency', optional($vendorBilling)->currency ?? 'MVR')) === 'MVR')>MVR</option>
                            <option value="USD" @selected(strtoupper((string) old('currency', optional($vendorBilling)->currency ?? 'MVR')) === 'USD')>USD</option>
                        </select>
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="billing_street_name">Address: Street Name</label>
                        <input id="billing_street_name" name="billing_street_name" class="ops-input" type="text" maxlength="255" value="{{ old('billing_street_name', optional($vendorBilling)->billing_street_name ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_country">Country</label>
                        <select id="billing_country" name="billing_country" class="ops-select" required>
                            <option value="Maldives" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? 'Maldives') === 'Maldives')>Maldives</option>
                            <option value="Sri Lanka" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'Sri Lanka')>Sri Lanka</option>
                            <option value="India" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'India')>India</option>
                            <option value="Other" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'Other')>Other</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_state">State / Province / Atoll</label>
                        <select id="billing_state" name="billing_state" class="ops-select" required>
                            <option value="">Select state/province</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_city">City / Island</label>
                        <select id="billing_city" name="billing_city" class="ops-select" required>
                            <option value="">Select city/island</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_invoice_prefix">Invoice Prefix</label>
                        <input id="billing_invoice_prefix" name="invoice_prefix" class="ops-input" type="text" maxlength="30" value="{{ old('invoice_prefix', optional($vendorBilling)->invoice_prefix ?? 'INV') }}">
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="billing_address">Additional Address Details (optional)</label>
                        <textarea id="billing_address" name="billing_address" class="ops-textarea" maxlength="2000">{{ old('billing_address', optional($vendorBilling)->billing_address ?? '') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save Billing Details</button>
            </form>
        </section>
