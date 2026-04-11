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
        $profileSectionQuery = strtolower(trim((string) request()->query('section', 'profile')));
        $allowedProfileSections = ['profile', 'categories', 'banking', 'address', 'password', 'all'];
        $activeProfileSection = in_array($profileSectionQuery, $allowedProfileSections, true) ? $profileSectionQuery : 'profile';
    @endphp

    <p class="label">My Account</p>
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