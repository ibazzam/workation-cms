<section id="vendorProfileInformation" class="ops-section" aria-label="Profile and business information">
    <div class="ops-header">
        <p class="ops-title">Profile &amp; Business Information</p>
        <span class="ops-chip">{{ $verificationLabel }}</span>
    </div>
    <p class="profile-help" style="margin-top:0;">
        @if (trim((string) ($vendorProfile['verification_notes'] ?? '')) !== '')
            Admin notes: {{ (string) ($vendorProfile['verification_notes'] ?? '') }}<br>
        @endif
        Approved categories: {{ $approvedCategoryLabels->isNotEmpty() ? $approvedCategoryLabels->join(', ') : 'Not approved yet' }}
    </p>

    <div class="profile-grid">
        <div class="profile-field"><label>Display Name</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['name'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Contact Phone</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['phone'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Registered Company / Service Name</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['company_name'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Business Registration Number</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['business_registration_number'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Service / Trade License Number</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['business_license_number'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Authorized Contact Person</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['contact_person_name'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Authorized Contact Phone</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['contact_person_phone'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Authorized Contact Email</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['contact_person_email'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Authorized Contact ID / Passport</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['contact_person_id_number'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Account Email</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['email'] ?? '') }}" readonly></div>
        <div class="profile-field"><label>Vendor ID</label><input class="profile-input" type="text" value="{{ (string) ($vendorProfile['vendor_id'] ?? '') }}" readonly></div>
    </div>

    <details class="ops-form" style="margin-top:12px;">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Edit Personal &amp; Business Information</summary>
        <form method="POST" action="/portal/vendor/profile/update" style="margin-top:12px;">
            @csrf
            <div class="profile-grid">
                <div class="profile-field"><label for="display_name">Display Name</label><input id="display_name" name="display_name" class="profile-input" type="text" value="{{ old('display_name', $vendorProfile['name'] ?? '') }}" maxlength="120" required></div>
                <div class="profile-field"><label for="contact_phone">Contact Phone</label><input id="contact_phone" name="contact_phone" class="profile-input" type="text" value="{{ old('contact_phone', $vendorProfile['phone'] ?? '') }}" maxlength="40" required></div>
                <div class="profile-field"><label for="company_name">Registered Company / Service Name</label><input id="company_name" name="company_name" class="profile-input" type="text" value="{{ old('company_name', $vendorProfile['company_name'] ?? '') }}" maxlength="190" required></div>
                <div class="profile-field"><label for="business_registration_number">Business Registration Number</label><input id="business_registration_number" name="business_registration_number" class="profile-input" type="text" value="{{ old('business_registration_number', $vendorProfile['business_registration_number'] ?? '') }}" maxlength="120" required></div>
                <div class="profile-field"><label for="business_license_number">Service / Trade License Number</label><input id="business_license_number" name="business_license_number" class="profile-input" type="text" value="{{ old('business_license_number', $vendorProfile['business_license_number'] ?? '') }}" maxlength="120"></div>
                <div class="profile-field"><label for="contact_person_name">Authorized Contact Person Name</label><input id="contact_person_name" name="contact_person_name" class="profile-input" type="text" value="{{ old('contact_person_name', $vendorProfile['contact_person_name'] ?? '') }}" maxlength="190" required></div>
                <div class="profile-field"><label for="contact_person_phone">Authorized Contact Person Phone</label><input id="contact_person_phone" name="contact_person_phone" class="profile-input" type="text" value="{{ old('contact_person_phone', $vendorProfile['contact_person_phone'] ?? '') }}" maxlength="60" required></div>
                <div class="profile-field"><label for="contact_person_email">Authorized Contact Person Email</label><input id="contact_person_email" name="contact_person_email" class="profile-input" type="email" value="{{ old('contact_person_email', $vendorProfile['contact_person_email'] ?? '') }}" maxlength="190" required></div>
                <div class="profile-field"><label for="contact_person_id_number">Authorized Contact Person ID / Passport</label><input id="contact_person_id_number" name="contact_person_id_number" class="profile-input" type="text" value="{{ old('contact_person_id_number', $vendorProfile['contact_person_id_number'] ?? '') }}" maxlength="120" required></div>
            </div>
            <button class="btn btn-primary" type="submit">Save Profile Updates</button>
        </form>
    </details>
</section>
