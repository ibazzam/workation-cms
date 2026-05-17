<section id="vendorBankCardSection" class="ops-section" aria-label="Bank and cards details" style="margin-top:12px;">
    @php
        $decodedBillingEmails = [];
        if (!empty(optional($billingRow)->billing_emails_json)) {
            $candidateEmails = json_decode((string) optional($billingRow)->billing_emails_json, true);
            if (is_array($candidateEmails)) {
                $decodedBillingEmails = collect($candidateEmails)->map(static fn ($email) => trim((string) $email))->filter()->values()->all();
            }
        }
        if ($decodedBillingEmails === [] && !empty(optional($billingRow)->billing_email)) {
            $decodedBillingEmails = [trim((string) optional($billingRow)->billing_email)];
        }

        $accountCollection = collect($payoutAccounts ?? [])->values();
        $primaryAccount = $accountCollection->firstWhere('is_primary', true) ?? $accountCollection->first();
        $editableAccounts = old('payout_accounts');
        if (!is_array($editableAccounts)) {
            $editableAccounts = $accountCollection
                ->map(static function ($account): array {
                    return [
                        'account_label' => (string) ($account->account_label ?? ''),
                        'payout_method' => (string) ($account->payout_method ?? 'bank_transfer'),
                        'beneficiary_name' => (string) ($account->beneficiary_name ?? ''),
                        'bank_account_number' => (string) ($account->bank_account_number ?? ''),
                        'bank_name' => (string) ($account->bank_name ?? ''),
                        'swift_code' => (string) ($account->swift_code ?? ''),
                        'currency' => (string) ($account->currency ?? 'MVR'),
                        'is_primary' => (bool) ($account->is_primary ?? false),
                    ];
                })
                ->values()
                ->all();
        }
        if ($editableAccounts === []) {
            $editableAccounts = [[
                'account_label' => '',
                'payout_method' => 'bank_transfer',
                'beneficiary_name' => (string) (optional($billingRow)->beneficiary_name ?? ($vendorProfile['name'] ?? '')),
                'bank_account_number' => (string) (optional($billingRow)->bank_account_number ?? ''),
                'bank_name' => (string) (optional($billingRow)->bank_name ?? ''),
                'swift_code' => (string) (optional($billingRow)->swift_code ?? ''),
                'currency' => (string) (optional($billingRow)->currency ?? 'MVR'),
                'is_primary' => true,
            ]];
        }

        $defaultPrimaryIndex = collect($editableAccounts)->search(static fn ($account) => (bool) ($account['is_primary'] ?? false));
        if ($defaultPrimaryIndex === false) {
            $defaultPrimaryIndex = 0;
        }
        $selectedPrimaryIndex = (string) old('primary_payout_account', (string) $defaultPrimaryIndex);
        $billingEmailsTextarea = old('billing_emails', implode(PHP_EOL, $decodedBillingEmails));
        $logoPathCandidates = [
            optional($billingRow)->letterhead_logo_path ?? null,
            optional($billingRow)->logo_path ?? null,
            optional($billingRow)->company_logo_path ?? null,
            optional($billingRow)->brand_logo_path ?? null,
        ];
        $currentLogoPath = collect($logoPathCandidates)
            ->map(static fn ($value): string => trim((string) ($value ?? '')))
            ->first(static fn (string $value): bool => $value !== '') ?? '';
        $currentLogoUrl = '';
        if ($currentLogoPath !== '') {
            $currentLogoUrl = preg_match('/^https?:\/\//i', $currentLogoPath)
                ? $currentLogoPath
                : \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($currentLogoPath, '/'));
        }
    @endphp

    <div class="ops-header">
        <p class="ops-title">Bank &amp; Payout Accounts</p>
        <span class="ops-chip">Payout setup</span>
    </div>
    <div class="policy-box" style="margin:0 0 10px;border:1px solid #d3e2ec;border-radius:12px;background:#f8fcff;padding:10px 12px;">
        <p class="small" style="margin:0 0 6px;"><strong>Document Template Settings</strong></p>
        <p class="small" style="margin:0 0 8px;">Vendor Reservation + Invoice documents use your vendor letterhead. Address is sourced from Address section fields (street, city, state, country, full address).</p>
        <form method="POST" action="/portal/vendor/billing/logo/upload" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="profile-field" style="min-width:260px;">
                <label for="profile_letterhead_logo">Upload Vendor Logo</label>
                <input id="profile_letterhead_logo" name="letterhead_logo" class="profile-input" type="file" accept=".png,.jpg,.jpeg,.webp,.svg" required>
            </div>
            <button class="btn btn-secondary" type="submit">Upload Logo</button>
        </form>
        @if ($currentLogoUrl !== '')
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="small">Current logo:</span>
                <img src="{{ $currentLogoUrl }}" alt="Current vendor logo" style="max-height:52px;max-width:220px;object-fit:contain;border:1px solid #d7e0e6;border-radius:8px;padding:4px;background:#fff;">
            </div>
        @endif
    </div>
    <div class="profile-grid">
        <div class="profile-field"><label>Business Name</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->business_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Person Responsible</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->responsible_person_name ?? ($vendorProfile['contact_person_name'] ?? '')) }}" readonly></div>
        <div class="profile-field"><label>Billing Emails</label><input class="profile-input" type="text" value="{{ implode(', ', $decodedBillingEmails) }}" readonly></div>
        <div class="profile-field"><label>Contact Number</label><input class="profile-input" type="text" value="{{ (string) (optional($billingRow)->contact_number ?? ($vendorProfile['contact_person_phone'] ?? '')) }}" readonly></div>
        <div class="profile-field"><label>Primary Beneficiary</label><input class="profile-input" type="text" value="{{ (string) ($primaryAccount->beneficiary_name ?? optional($billingRow)->beneficiary_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Primary Bank</label><input class="profile-input" type="text" value="{{ (string) ($primaryAccount->bank_name ?? optional($billingRow)->bank_name ?? '') }}" readonly></div>
        <div class="profile-field"><label>Primary Account</label><input class="profile-input" type="text" value="{{ !empty($primaryAccount->bank_account_last4) ? '****' . $primaryAccount->bank_account_last4 : ((string) (optional($billingRow)->bank_account_last4 ? '****' . optional($billingRow)->bank_account_last4 : '')) }}" readonly></div>
        <div class="profile-field"><label>Primary Currency</label><input class="profile-input" type="text" value="{{ (string) ($primaryAccount->currency ?? optional($billingRow)->currency ?? '') }}" readonly></div>
        <div class="profile-field"><label>Registered Payout Accounts</label><input class="profile-input" type="text" value="{{ $accountCollection->count() }}" readonly></div>
    </div>

    @if ($accountCollection->isNotEmpty())
        <div style="display:grid;gap:10px;margin-top:12px;">
            @foreach ($accountCollection as $account)
                <div class="policy-box" style="border:1px solid #d3e2ec;border-radius:12px;background:#f8fcff;padding:10px 12px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <p style="margin:0;font-weight:700;">{{ (string) ($account->account_label ?? '') !== '' ? (string) $account->account_label : 'Payout Account #' . $loop->iteration }}</p>
                            <p class="small" style="margin:4px 0 0;">{{ (string) ($account->beneficiary_name ?? 'Beneficiary pending') }} · {{ (string) ($account->bank_name ?? 'Bank pending') }}</p>
                            <p class="small" style="margin:4px 0 0;">{{ (string) ($account->currency ?? 'MVR') }} · {{ (string) ($account->swift_code ?? '') !== '' ? 'SWIFT ' . (string) $account->swift_code : 'SWIFT not set' }}</p>
                            @php
                                $verificationStatus = strtolower(trim((string) ($account->verification_status ?? 'needs_review')));
                                $verificationLabel = match ($verificationStatus) {
                                    'verified' => 'Verified',
                                    'approved' => 'Approved by Finance',
                                    'rejected' => 'Rejected by Finance',
                                    'pending', 'pending_review' => 'Pending Review',
                                    default => 'Needs Review',
                                };
                            @endphp
                            <p class="small" style="margin:4px 0 0;">Verification: <strong>{{ $verificationLabel }}</strong></p>
                            @if (!empty($account->verification_notes))
                                <p class="small" style="margin:4px 0 0;">{{ (string) $account->verification_notes }}</p>
                            @endif
                        </div>
                        <div style="text-align:right;">
                            @if ((bool) ($account->is_primary ?? false))
                                <span class="ops-chip">Primary</span>
                            @endif
                            <p class="small" style="margin:6px 0 0;">Account: {{ !empty($account->bank_account_last4) ? '****' . (string) $account->bank_account_last4 : 'Hidden' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <details class="ops-form" style="margin-top:12px;">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Edit Bank &amp; Cards Details</summary>
        <form method="POST" action="/portal/vendor/billing/update" style="margin-top:12px;" data-payout-accounts-form>
            @csrf
            <div class="profile-grid">
                <div class="profile-field"><label for="business_name">Business Name</label><input id="business_name" name="business_name" class="profile-input" type="text" value="{{ old('business_name', optional($billingRow)->business_name ?? ($vendorProfile['company_name'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="responsible_person_name">Person Responsible</label><input id="responsible_person_name" name="responsible_person_name" class="profile-input" type="text" value="{{ old('responsible_person_name', optional($billingRow)->responsible_person_name ?? ($vendorProfile['contact_person_name'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="contact_number">Contact Number</label><input id="contact_number" name="contact_number" class="profile-input" type="text" value="{{ old('contact_number', optional($billingRow)->contact_number ?? ($vendorProfile['contact_person_phone'] ?? '')) }}" required></div>
                <div class="profile-field"><label for="tax_id">Tax ID</label><input id="tax_id" name="tax_id" class="profile-input" type="text" value="{{ old('tax_id', optional($billingRow)->tax_id ?? '') }}"></div>
                <div class="profile-field"><label for="invoice_prefix">Invoice Prefix</label><input id="invoice_prefix" name="invoice_prefix" class="profile-input" type="text" value="{{ old('invoice_prefix', optional($billingRow)->invoice_prefix ?? 'INV') }}"></div>
                <div class="profile-field" style="grid-column:1 / -1;">
                    <label for="billing_emails">Billing Emails</label>
                    <textarea id="billing_emails" name="billing_emails" class="profile-input" rows="4" required>{{ $billingEmailsTextarea }}</textarea>
                    <p class="small" style="margin:6px 0 0;">Use one email per line or separate multiple emails with commas.</p>
                </div>
                <input type="hidden" name="billing_street_name" value="{{ old('billing_street_name', optional($billingRow)->billing_street_name ?? '') }}">
                <input type="hidden" name="billing_country" value="{{ old('billing_country', optional($billingRow)->billing_country ?? 'Maldives') }}">
                <input type="hidden" name="billing_state" value="{{ old('billing_state', optional($billingRow)->billing_state ?? '') }}">
                <input type="hidden" name="billing_city" value="{{ old('billing_city', optional($billingRow)->billing_city ?? '') }}">
                <input type="hidden" name="billing_address" value="{{ old('billing_address', optional($billingRow)->billing_address ?? '') }}">
            </div>
            <div class="policy-box" style="margin-top:12px;border:1px solid #d3e2ec;border-radius:12px;background:#f8fcff;padding:12px;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <p style="margin:0;font-weight:700;">Payout Accounts</p>
                        <p class="small" style="margin:4px 0 0;">Register one or more payout accounts and mark the primary account Workation should use for vendor settlements.</p>
                    </div>
                    <button class="btn btn-secondary" type="button" data-add-payout-account>Add payout account</button>
                </div>

                <div data-payout-account-list style="display:grid;gap:12px;margin-top:12px;">
                    @foreach ($editableAccounts as $index => $account)
                        <div data-payout-account-item class="card" style="padding:12px;border:1px solid #d3e2ec;">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                                <strong>Payout Account <span data-account-number>{{ $loop->iteration }}</span></strong>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <label class="small" style="display:inline-flex;gap:6px;align-items:center;">
                                        <input type="radio" name="primary_payout_account" value="{{ $index }}" @checked($selectedPrimaryIndex === (string) $index)>
                                        Primary account
                                    </label>
                                    <button type="button" class="btn btn-secondary" data-remove-payout-account>Remove</button>
                                </div>
                            </div>
                            <div class="profile-grid">
                                <div class="profile-field"><label>Account Label</label><input name="payout_accounts[{{ $index }}][account_label]" class="profile-input" type="text" value="{{ old('payout_accounts.' . $index . '.account_label', $account['account_label'] ?? '') }}" placeholder="e.g. Main MVR Account"></div>
                                <div class="profile-field"><label>Payout Method</label><select name="payout_accounts[{{ $index }}][payout_method]" class="ops-select"><option value="bank_transfer" @selected(old('payout_accounts.' . $index . '.payout_method', $account['payout_method'] ?? 'bank_transfer') === 'bank_transfer')>Bank Transfer</option><option value="mobile_wallet" @selected(old('payout_accounts.' . $index . '.payout_method', $account['payout_method'] ?? '') === 'mobile_wallet')>Mobile Wallet</option><option value="manual" @selected(old('payout_accounts.' . $index . '.payout_method', $account['payout_method'] ?? '') === 'manual')>Manual</option></select></div>
                                <div class="profile-field"><label>Beneficiary Account Name</label><input name="payout_accounts[{{ $index }}][beneficiary_name]" class="profile-input" type="text" value="{{ old('payout_accounts.' . $index . '.beneficiary_name', $account['beneficiary_name'] ?? '') }}" required></div>
                                <div class="profile-field"><label>Account Number</label><input name="payout_accounts[{{ $index }}][bank_account_number]" class="profile-input" type="text" value="{{ old('payout_accounts.' . $index . '.bank_account_number', $account['bank_account_number'] ?? '') }}" required></div>
                                <div class="profile-field"><label>Bank Name</label><input name="payout_accounts[{{ $index }}][bank_name]" class="profile-input" type="text" value="{{ old('payout_accounts.' . $index . '.bank_name', $account['bank_name'] ?? '') }}" required></div>
                                <div class="profile-field"><label>SWIFT Code</label><input name="payout_accounts[{{ $index }}][swift_code]" class="profile-input" type="text" value="{{ old('payout_accounts.' . $index . '.swift_code', $account['swift_code'] ?? '') }}"></div>
                                <div class="profile-field"><label>Currency</label><select name="payout_accounts[{{ $index }}][currency]" class="ops-select" required><option value="MVR" @selected(old('payout_accounts.' . $index . '.currency', $account['currency'] ?? 'MVR') === 'MVR')>MVR</option><option value="USD" @selected(old('payout_accounts.' . $index . '.currency', $account['currency'] ?? '') === 'USD')>USD</option></select></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Save Bank &amp; Cards</button>
        </form>
    </details>

    <template id="payoutAccountTemplate">
        <div data-payout-account-item class="card" style="padding:12px;border:1px solid #d3e2ec;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                <strong>Payout Account <span data-account-number>__NUMBER__</span></strong>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <label class="small" style="display:inline-flex;gap:6px;align-items:center;">
                        <input type="radio" name="primary_payout_account" value="__INDEX__">
                        Primary account
                    </label>
                    <button type="button" class="btn btn-secondary" data-remove-payout-account>Remove</button>
                </div>
            </div>
            <div class="profile-grid">
                <div class="profile-field"><label>Account Label</label><input name="payout_accounts[__INDEX__][account_label]" class="profile-input" type="text" placeholder="e.g. Secondary USD Account"></div>
                <div class="profile-field"><label>Payout Method</label><select name="payout_accounts[__INDEX__][payout_method]" class="ops-select"><option value="bank_transfer" selected>Bank Transfer</option><option value="mobile_wallet">Mobile Wallet</option><option value="manual">Manual</option></select></div>
                <div class="profile-field"><label>Beneficiary Account Name</label><input name="payout_accounts[__INDEX__][beneficiary_name]" class="profile-input" type="text"></div>
                <div class="profile-field"><label>Account Number</label><input name="payout_accounts[__INDEX__][bank_account_number]" class="profile-input" type="text"></div>
                <div class="profile-field"><label>Bank Name</label><input name="payout_accounts[__INDEX__][bank_name]" class="profile-input" type="text"></div>
                <div class="profile-field"><label>SWIFT Code</label><input name="payout_accounts[__INDEX__][swift_code]" class="profile-input" type="text"></div>
                <div class="profile-field"><label>Currency</label><select name="payout_accounts[__INDEX__][currency]" class="ops-select"><option value="MVR" selected>MVR</option><option value="USD">USD</option></select></div>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const section = document.getElementById('vendorBankCardSection');
            if (!section || section.dataset.accountsReady === '1') {
                return;
            }
            section.dataset.accountsReady = '1';

            const form = section.querySelector('[data-payout-accounts-form]');
            const list = section.querySelector('[data-payout-account-list]');
            const template = document.getElementById('payoutAccountTemplate');
            const addButton = section.querySelector('[data-add-payout-account]');
            if (!form || !list || !template || !addButton) {
                return;
            }

            function resequence() {
                const items = Array.from(list.querySelectorAll('[data-payout-account-item]'));
                items.forEach((item, index) => {
                    const numberEl = item.querySelector('[data-account-number]');
                    if (numberEl) {
                        numberEl.textContent = String(index + 1);
                    }

                    item.querySelectorAll('input, select, textarea').forEach((field) => {
                        if (!field.name) {
                            return;
                        }
                        field.name = field.name.replace(/payout_accounts\[\d+\]/, 'payout_accounts[' + index + ']');
                        if (field.type === 'radio' && field.name === 'primary_payout_account') {
                            field.value = String(index);
                        }
                    });
                });

                const checkedPrimary = list.querySelector('input[type="radio"][name="primary_payout_account"]:checked');
                if (!checkedPrimary) {
                    const firstPrimary = list.querySelector('input[type="radio"][name="primary_payout_account"]');
                    if (firstPrimary) {
                        firstPrimary.checked = true;
                    }
                }

                list.querySelectorAll('[data-remove-payout-account]').forEach((button) => {
                    button.disabled = items.length <= 1;
                });
            }

            function appendAccount() {
                const index = list.querySelectorAll('[data-payout-account-item]').length;
                const html = template.innerHTML
                    .replaceAll('__INDEX__', String(index))
                    .replaceAll('__NUMBER__', String(index + 1));
                list.insertAdjacentHTML('beforeend', html);
                resequence();
            }

            addButton.addEventListener('click', appendAccount);

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-payout-account]');
                if (!button) {
                    return;
                }
                const item = button.closest('[data-payout-account-item]');
                if (!item) {
                    return;
                }
                const items = list.querySelectorAll('[data-payout-account-item]');
                if (items.length <= 1) {
                    return;
                }
                item.remove();
                resequence();
            });

            form.addEventListener('submit', () => {
                Array.from(list.querySelectorAll('[data-payout-account-item]')).forEach((item) => {
                    const beneficiary = String(item.querySelector('input[name$="[beneficiary_name]"]')?.value || '').trim();
                    const accountNumber = String(item.querySelector('input[name$="[bank_account_number]"]')?.value || '').trim();
                    const bankName = String(item.querySelector('input[name$="[bank_name]"]')?.value || '').trim();
                    if (beneficiary === '' && accountNumber === '' && bankName === '') {
                        item.remove();
                    }
                });
                resequence();
            });

            resequence();
        }());
    </script>
</section>
