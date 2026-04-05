@php
    $sidebarVendorCategoryMap = $vendorCategoryMap ?? [];
    $sidebarSelectedVendorCategories = collect($selectedVendorCategories ?? []);
    $sidebarCategoryLinks = $sidebarSelectedVendorCategories
        ->map(static function ($categoryKey) {
            return vendorPortalCanonicalCategory((string) $categoryKey);
        })
        ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
        ->unique()
        ->values();
@endphp

<nav class="portal-nav" aria-label="Vendor navigation">
    <div class="vendor-nav-head">
        <div class="vendor-nav-avatar" aria-hidden="true">{{ strtoupper(substr((string) ($vendorProfile['name'] ?? 'V'), 0, 1)) }}</div>
        <div class="vendor-nav-user-meta">
            <p class="vendor-nav-user-name">{{ (string) ($vendorProfile['name'] ?? $portalUser ?? 'Vendor') }}</p>
            @if (trim((string) ($vendorProfile['email'] ?? '')) !== '')
                <p class="vendor-nav-user-email">{{ (string) ($vendorProfile['email'] ?? '') }}</p>
            @endif
        </div>
    </div>

    <a class="nav-item-link is-active" href="#vendorSummary" data-panel-key="overview">Dashboard Home</a>
    <a class="nav-item-link" href="#vendorReportsSection" data-panel-key="overview">Reports &amp; Performance</a>

    <button class="nav-group-header" type="button" data-vendor-nav-toggle="vendor-listings-group" aria-expanded="true">
        <span>My Listings</span>
        <span class="nav-chevron" aria-hidden="true">▾</span>
    </button>
    <div class="nav-group-body is-open" data-vendor-nav-group="vendor-listings-group">
        <a class="nav-item-link" href="#vendorPropertiesSection" data-panel-key="listings">All Listings</a>
        <a class="nav-item-link" href="#vendorPropertiesSection" data-panel-key="listings" data-vendor-listing-action="create">Create Listing</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="#vendorPropertiesSection"
                data-panel-key="listings"
                data-vendor-category-target="{{ $categoryKey }}"
            >{{ $categoryLabel }}</a>
        @endforeach
    </div>

    <button class="nav-group-header" type="button" data-vendor-nav-toggle="vendor-reservations-group" aria-expanded="true">
        <span>Reservations, Availability &amp; Pricing</span>
        <span class="nav-chevron" aria-hidden="true">▾</span>
    </button>
    <div class="nav-group-body is-open" data-vendor-nav-group="vendor-reservations-group">
        <a class="nav-item-link" href="#vendorAvailabilitySection" data-panel-key="reservations">Manage Reservations</a>
        <a class="nav-item-link" href="#vendorAvailabilitySection" data-panel-key="reservations">Update Availability</a>
        <a class="nav-item-link" href="#vendorPricingSection" data-panel-key="reservations">Change Pricing</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="#vendorAvailabilitySection"
                data-panel-key="reservations"
                data-vendor-category-target="{{ $categoryKey }}"
            >{{ $categoryLabel }} Reservations</a>
        @endforeach
    </div>

    <a class="nav-item-link" href="#vendorEngagement" data-panel-key="engagement">Customer Care</a>
    <a class="nav-item-link" href="#vendorDailyCollectionSection" data-panel-key="billing">Billing &amp; Refunds</a>

    <div class="nav-divider"></div>

    <a class="nav-item-link" href="#profile" data-panel-key="profile">Vendor Profile</a>
    <a class="nav-item-link" href="#vendorProfileBillingSettings" data-panel-key="billing">Billing Setup</a>
    <a class="nav-item-link" href="#api" data-panel-key="api">API Tools</a>
</nav>