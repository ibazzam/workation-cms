<section id="vendorProfileCard" class="card profile-card" aria-label="Partner profile settings" data-panel-group="profile">
            <p class="label">Account Settings</p>
            <div class="panel-links" aria-label="Profile actions">
                <a href="#vendorProfileCard">Profile Settings</a>
                <a href="#vendorCategoryWizard">Category Setup</a>
                <a href="#billing">Billing Settings</a>
            </div>
            <form method="POST" action="/portal/vendor/profile/update">
                @csrf
                @php
                    $verificationStatus = strtolower(trim((string) ($vendorProfile['verification_status'] ?? 'pending')));
                    $verificationLabelMap = [
                        'pending' => 'Pending verification',
                        'under_review' => 'Under admin review',
                        'approved' => 'Approved for go-live',
                        'rejected' => 'Needs correction',
                        'suspended' => 'Suspended',
                    ];
                    $verificationLabel = $verificationLabelMap[$verificationStatus] ?? 'Pending verification';
                @endphp
                <p class="profile-help" style="margin-top:0;">
                    Verification status: <strong>{{ $verificationLabel }}</strong>
                    @if (trim((string) ($vendorProfile['verification_notes'] ?? '')) !== '')
                        <br>
                        Admin notes: {{ (string) ($vendorProfile['verification_notes'] ?? '') }}
                    @endif
                    @if (!empty($vendorProfile['approved_categories'] ?? []))
                        <br>
                        Approved categories:
                        {{ collect($vendorProfile['approved_categories'])->map(fn ($key) => (string) ($vendorCategoryMap[$key] ?? $key))->join(', ') }}
                    @endif
                </p>
                <div class="profile-grid">
                    <div class="profile-field">
                        <label for="display_name">Display Name</label>
                        <input
                            id="display_name"
                            name="display_name"
                            class="profile-input"
                            type="text"
                            value="{{ old('display_name', $vendorProfile['name'] ?? '') }}"
                            maxlength="120"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_phone">Contact Phone</label>
                        <input
                            id="contact_phone"
                            name="contact_phone"
                            class="profile-input"
                            type="text"
                            value="{{ old('contact_phone', $vendorProfile['phone'] ?? '') }}"
                            maxlength="40"
                            placeholder="+960..."
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="company_name">Registered Company / Service Name</label>
                        <input
                            id="company_name"
                            name="company_name"
                            class="profile-input"
                            type="text"
                            value="{{ old('company_name', $vendorProfile['company_name'] ?? '') }}"
                            maxlength="190"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="business_registration_number">Business Registration Number</label>
                        <input
                            id="business_registration_number"
                            name="business_registration_number"
                            class="profile-input"
                            type="text"
                            value="{{ old('business_registration_number', $vendorProfile['business_registration_number'] ?? '') }}"
                            maxlength="120"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="business_license_number">Service / Trade License Number</label>
                        <input
                            id="business_license_number"
                            name="business_license_number"
                            class="profile-input"
                            type="text"
                            value="{{ old('business_license_number', $vendorProfile['business_license_number'] ?? '') }}"
                            maxlength="120"
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_person_name">Authorized Contact Person Name</label>
                        <input
                            id="contact_person_name"
                            name="contact_person_name"
                            class="profile-input"
                            type="text"
                            value="{{ old('contact_person_name', $vendorProfile['contact_person_name'] ?? '') }}"
                            maxlength="190"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_person_phone">Authorized Contact Person Phone</label>
                        <input
                            id="contact_person_phone"
                            name="contact_person_phone"
                            class="profile-input"
                            type="text"
                            value="{{ old('contact_person_phone', $vendorProfile['contact_person_phone'] ?? '') }}"
                            maxlength="60"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_person_email">Authorized Contact Person Email</label>
                        <input
                            id="contact_person_email"
                            name="contact_person_email"
                            class="profile-input"
                            type="email"
                            value="{{ old('contact_person_email', $vendorProfile['contact_person_email'] ?? '') }}"
                            maxlength="190"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_person_id_number">Authorized Contact Person ID / Passport</label>
                        <input
                            id="contact_person_id_number"
                            name="contact_person_id_number"
                            class="profile-input"
                            type="text"
                            value="{{ old('contact_person_id_number', $vendorProfile['contact_person_id_number'] ?? '') }}"
                            maxlength="120"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="account_email">Account Email</label>
                        <input
                            id="account_email"
                            class="profile-input"
                            type="text"
                            value="{{ $vendorProfile['email'] ?? '' }}"
                            readonly
                        >
                    </div>
                    <div class="profile-field">
                        <label for="vendor_id">Vendor ID</label>
                        <input
                            id="vendor_id"
                            class="profile-input"
                            type="text"
                            value="{{ $vendorProfile['vendor_id'] ?? '' }}"
                            readonly
                        >
                    </div>
                </div>
                <p class="profile-help">These fields are mandatory for compliance review. Listings and operations are enabled only after admin verification and category approval.</p>
                <button class="btn btn-primary" type="submit">Save & Submit for Verification</button>
            </form>

            <div id="vendorCategoryWizard" class="ops-section" aria-label="Vendor category setup wizard">
            <div class="ops-header">
                <p class="ops-title">Category-Based Listing Wizard</p>
                <span class="ops-chip">Step {{ $vendorOnboardingStep }} of 4</span>
            </div>
            <div class="wizard-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/categories/update">
                    @csrf
                    <div class="ops-field">
                        <label>Select your service categories</label>
                        <div class="category-grid">
                            @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                                <label class="category-item" for="category_{{ $categoryKey }}">
                                    <input id="category_{{ $categoryKey }}" type="checkbox" name="categories[]" value="{{ $categoryKey }}" @checked(in_array($categoryKey, $selectedVendorCategories, true))>
                                    <span>{{ $categoryLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="ops-field" style="margin-top:10px;">
                        <label for="onboarding_step">Current onboarding step</label>
                        <select id="onboarding_step" name="onboarding_step" class="ops-select" required>
                            <option value="1" @selected((int) $vendorOnboardingStep === 1)>Step 1: Choose Categories</option>
                            <option value="2" @selected((int) $vendorOnboardingStep === 2)>Step 2: Add Profile + Billing</option>
                            <option value="3" @selected((int) $vendorOnboardingStep === 3)>Step 3: Create Listings + Availability</option>
                            <option value="4" @selected((int) $vendorOnboardingStep === 4)>Step 4: Add Photos + Publish</option>
                        </select>
                    </div>
                    <p class="wizard-note">Only selected categories can be used when creating properties/services.</p>
                    <button class="btn btn-primary" type="submit">Save Category Setup</button>
                </form>

                <article class="ops-form">
                    <p class="label">Step-by-step checklist</p>
                    <ol class="step-list">
                        <li>Select categories from schema domains: Accommodation, marine transport, land transport, excursions, remote workspaces, conference rooms, resort day visits, restaurants, and vehicle rentals.</li>
                        <li>Complete account profile and billing details.</li>
                        <li>Create listings, room categories (accommodation), availability, and pricing.</li>
                        <li>Upload photos and finalize publish-ready inventory.</li>
                    </ol>
                    <p class="wizard-note">You can update categories later. Existing records remain editable.</p>
                </article>
            </div>
            </div>
        </section>
