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

    <a class="nav-item-link is-active" href="/vendor?page=overview" data-panel-key="overview">Dashboard Home</a>
    <a class="nav-item-link" href="/vendor/reports" data-panel-key="overview">Reports &amp; Performance</a>

    <button class="nav-group-header" type="button" data-vendor-nav-toggle="vendor-listings-group" aria-expanded="true">
        <span>My Listings</span>
        <span class="nav-chevron" aria-hidden="true">▾</span>
    </button>
    <div class="nav-group-body is-open" data-vendor-nav-group="vendor-listings-group">
        <a class="nav-item-link" href="/vendor/listings" data-panel-key="listings">All Listings</a>
        <a class="nav-item-link" href="/vendor/listings/create" data-panel-key="listings" data-vendor-listing-action="create">Create Listing</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="{{ '/vendor/listings/manage/' . $categoryKey }}"
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
        <a class="nav-item-link" href="/vendor/reservations" data-panel-key="reservations">Manage Reservations</a>
        <a class="nav-item-link" href="/vendor/availability" data-panel-key="reservations">Update Availability</a>
        <a class="nav-item-link" href="/vendor/pricing" data-panel-key="reservations">Change Pricing</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="{{ '/vendor/reservations?category=' . urlencode($categoryKey) }}"
                data-panel-key="reservations"
                data-vendor-category-target="{{ $categoryKey }}"
            >{{ $categoryLabel }} Reservations</a>
        @endforeach
    </div>

    <a class="nav-item-link" href="/vendor/promotions" data-panel-key="engagement">Promotions &amp; Loyalty</a>
    <a class="nav-item-link" href="/vendor/billing" data-panel-key="billing">Billing &amp; Refunds</a>

    <div class="nav-divider"></div>

    <a class="nav-item-link" href="/vendor/profile" data-panel-key="profile">Partner Profile</a>
    <a class="nav-item-link" href="/vendor/billing" data-panel-key="billing">Billing Setup</a>
    <a class="nav-item-link" href="#api" data-panel-key="api">API Tools</a>
</nav>