<section id="vendorProfileCard" class="card profile-card" aria-label="Partner profile settings" data-panel-group="profile">
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
        $selectedCategoryLabels = collect($selectedVendorCategories ?? [])->map(fn ($key) => (string) ($vendorCategoryMap[$key] ?? $key))->values();
        $approvedCategoryLabels = collect($vendorProfile['approved_categories'] ?? [])->map(fn ($key) => (string) ($vendorCategoryMap[$key] ?? $key))->values();
        $billingRow = $vendorBilling ?? null;
    @endphp

    <p class="label">My Account</p>
    <div class="panel-links" aria-label="My account sections">
        <a href="#vendorProfileInformation">Profile &amp; Business</a>
        <a href="#vendorCategoryWizard">Category Requests</a>
        <a href="#vendorBankCardSection">Bank &amp; Cards</a>
        <a href="#vendorAddressSection">Address</a>
        <a href="#vendorPasswordSection">Password</a>
    </div>

    @include('vendor-portal.partials.profile-sections.profile-business')
    @include('vendor-portal.partials.profile-sections.category-requests')
    @include('vendor-portal.partials.profile-sections.bank-cards')
    @include('vendor-portal.partials.profile-sections.address')
    @include('vendor-portal.partials.profile-sections.password')
</section>