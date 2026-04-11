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

    <p class="nav-group-title">Overview</p>
    <a class="nav-item-link {{ in_array($activePortalPage ?? 'overview', ['overview', 'reports'], true) ? 'prominent' : '' }}" href="/vendor?page=overview" data-panel-key="overview">Dashboard</a>
    <a class="nav-item-link {{ ($activePortalPage ?? '') === 'reports' ? 'prominent' : '' }}" href="/vendor/reports" data-panel-key="overview">Reports &amp; Performance</a>

    <p class="nav-group-title">Listings</p>
    <a class="nav-item-link {{ ($activePortalPage ?? '') === 'listings' ? 'prominent' : '' }}" href="/vendor/listings" data-panel-key="listings">All Listings</a>
    @foreach ($sidebarCategoryLinks as $categoryKey)
        @php
            $categoryLabel = (string) ($sidebarVendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
            $isActiveCategory = ($activePortalPage ?? '') === 'listings' && (($forcedListingCategory ?? '') === $categoryKey);
        @endphp
        <a
            class="nav-sub-link {{ $isActiveCategory ? 'prominent' : '' }}"
            href="{{ '/vendor/listings/' . $categoryKey }}"
            data-panel-key="listings"
            data-vendor-category-target="{{ $categoryKey }}"
        >{{ $categoryLabel }}</a>
    @endforeach

    <p class="nav-group-title">Engagement &amp; Billing</p>
    <a class="nav-item-link {{ in_array($activePortalPage ?? '', ['engagement', 'promotions'], true) ? 'prominent' : '' }}" href="/vendor/promotions" data-panel-key="engagement">Promotions &amp; Loyalty</a>
    <a class="nav-item-link {{ ($activePortalPage ?? '') === 'billing' ? 'prominent' : '' }}" href="/vendor/billing" data-panel-key="billing">Billing &amp; Refunds</a>

    <p class="nav-group-title">Account</p>
    <a class="nav-item-link {{ ($activePortalPage ?? '') === 'profile' ? 'prominent' : '' }}" href="/vendor/profile" data-panel-key="profile">Partner Profile</a>
    <a class="nav-item-link" href="#api" data-panel-key="api">API Tools</a>
</nav>