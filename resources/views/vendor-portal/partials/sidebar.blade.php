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

    <button class="nav-group-header" type="button" data-vendor-nav-toggle="vendor-listings-group" aria-expanded="true">
        <span>Listings Management</span>
        <span class="nav-chevron" aria-hidden="true">▾</span>
    </button>
    <div class="nav-group-body is-open" data-vendor-nav-group="vendor-listings-group">
        <a class="nav-item-link is-active" href="#listings" data-panel-key="listings">All Listings</a>
        <a class="nav-item-link" href="#listings" data-panel-key="listings" data-vendor-listing-action="create">Upload New Listing</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="#listings"
                data-panel-key="listings"
                data-vendor-category-target="{{ $categoryKey }}"
            >{{ $categoryLabel }}</a>
        @endforeach
    </div>

    <button class="nav-group-header" type="button" data-vendor-nav-toggle="vendor-reservations-group" aria-expanded="true">
        <span>Reservations &amp; Calendar</span>
        <span class="nav-chevron" aria-hidden="true">▾</span>
    </button>
    <div class="nav-group-body is-open" data-vendor-nav-group="vendor-reservations-group">
        <a class="nav-item-link" href="#reservations" data-panel-key="reservations">Category Operations Overview</a>
        @foreach ($sidebarCategoryLinks as $categoryKey)
            @php
                $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            @endphp
            <a
                class="nav-sub-link"
                href="#reservations"
                data-panel-key="reservations"
                data-vendor-category-target="{{ $categoryKey }}"
            >{{ $categoryLabel }} Calendar &amp; Reservations</a>
        @endforeach
    </div>

    <a class="nav-item-link" href="#engagement" data-panel-key="engagement">Customer Engagement</a>

    <div class="nav-divider"></div>

    <a class="nav-item-link" href="#profile" data-panel-key="profile">Vendor Profile</a>
    <a class="nav-item-link" href="#billing" data-panel-key="billing">Billing / Daily Collection</a>
    <a class="nav-item-link" href="#api" data-panel-key="api">API Tools</a>
</nav>
