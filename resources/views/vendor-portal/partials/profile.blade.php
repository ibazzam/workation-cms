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
        $payoutAccounts = collect($vendorPayoutAccounts ?? []);
        $profileSectionQuery = strtolower(trim((string) request()->query('section', 'profile')));
        $allowedProfileSections = ['profile', 'categories', 'banking', 'address', 'password', 'all'];
        $activeProfileSection = in_array($profileSectionQuery, $allowedProfileSections, true) ? $profileSectionQuery : 'profile';
    @endphp

    <p class="label">My Account</p>
    @php
        $profileRequiredValues = [
            (string) ($vendorProfile['name'] ?? ''),
            (string) ($vendorProfile['phone'] ?? ''),
            (string) ($vendorProfile['company_name'] ?? ''),
            (string) ($vendorProfile['business_registration_number'] ?? ''),
            (string) ($vendorProfile['contact_person_name'] ?? ''),
            (string) ($vendorProfile['contact_person_phone'] ?? ''),
            (string) ($vendorProfile['contact_person_email'] ?? ''),
            (string) ($billingRow->billing_street_name ?? ''),
            (string) ($billingRow->billing_city ?? ''),
            (string) ($billingRow->billing_state ?? ''),
            (string) ($billingRow->billing_country ?? ''),
        ];
        $profileRequiredCount = count($profileRequiredValues);
        $profileCompleteCount = collect($profileRequiredValues)->filter(static fn ($value) => trim((string) $value) !== '')->count();
        $profileCompletionPercent = $profileRequiredCount > 0
            ? (int) round(($profileCompleteCount / $profileRequiredCount) * 100)
            : 0;
        $profileCompletionLabel = $profileCompletionPercent >= 90
            ? 'Operationally ready'
            : ($profileCompletionPercent >= 70 ? 'Nearly ready' : 'Action required');
        $templateLogoConfigured = trim((string) (($billingRow->letterhead_logo_path ?? $billingRow->logo_path ?? $billingRow->company_logo_path ?? $billingRow->brand_logo_path ?? ''))) !== '';
    @endphp
    <div class="summary-grid summary-grid-compact" style="margin:8px 0 10px;">
        <article class="summary-card">
            <p class="summary-label">Verification</p>
            <p class="summary-value">{{ $verificationLabel }}</p>
            <p class="summary-meta">Profile governance status</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Profile Completion</p>
            <p class="summary-value">{{ $profileCompletionPercent }}%</p>
            <p class="summary-meta">{{ $profileCompletionLabel }}</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Approved Categories</p>
            <p class="summary-value">{{ $approvedCategoryLabels->count() }}</p>
            <p class="summary-meta">Out of {{ $selectedCategoryLabels->count() > 0 ? $selectedCategoryLabels->count() : 0 }} requested</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Template Readiness</p>
            <p class="summary-value">{{ $templateLogoConfigured ? 'Configured' : 'Pending' }}</p>
            <p class="summary-meta">Logo + billing address for print docs</p>
        </article>
    </div>
    <div class="panel-links" aria-label="My account sections">
        <a href="/vendor/profile?section=profile" class="{{ $activeProfileSection === 'profile' ? 'is-active' : '' }}">Profile &amp; Business</a>
        <a href="/vendor/profile?section=categories" class="{{ $activeProfileSection === 'categories' ? 'is-active' : '' }}">Category Requests</a>
        <a href="/vendor/profile?section=banking" class="{{ $activeProfileSection === 'banking' ? 'is-active' : '' }}">Bank &amp; Cards</a>
        <a href="/vendor/profile?section=address" class="{{ $activeProfileSection === 'address' ? 'is-active' : '' }}">Address</a>
        <a href="/vendor/profile?section=password" class="{{ $activeProfileSection === 'password' ? 'is-active' : '' }}">Password</a>
        <a href="/vendor/profile?section=all" class="{{ $activeProfileSection === 'all' ? 'is-active' : '' }}">Show All</a>
    </div>

    @if (in_array($activeProfileSection, ['profile', 'all'], true))
        @include('vendor-portal.partials.profile-sections.profile-business')
    @endif
    @if (in_array($activeProfileSection, ['categories', 'all'], true))
        @include('vendor-portal.partials.profile-sections.category-requests')
    @endif
    @if (in_array($activeProfileSection, ['banking', 'all'], true))
        @include('vendor-portal.partials.profile-sections.bank-cards')
    @endif
    @if (in_array($activeProfileSection, ['address', 'all'], true))
        @include('vendor-portal.partials.profile-sections.address')
    @endif
    @if (in_array($activeProfileSection, ['password', 'all'], true))
        @include('vendor-portal.partials.profile-sections.password')
    @endif
</section>