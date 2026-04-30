@php
    $sidebarVendorCategoryMap = $vendorCategoryMap ?? [];
    $sidebarSelectedVendorCategories = collect($selectedVendorCategories ?? [])
        ->map(static function ($categoryKey) {
            return vendorPortalCanonicalCategory((string) $categoryKey);
        })
        ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
        ->unique()
        ->values();
    $sidebarCategoryOrder = collect($listingCategoryViewOrder ?? []);
    $sidebarCategoryLinks = $sidebarCategoryOrder
        ->filter(static fn ($categoryKey) => $sidebarSelectedVendorCategories->contains($categoryKey))
        ->values();
    $sidebarHasServiceAccess = $sidebarCategoryLinks->isNotEmpty();
    $sidebarCategoryQuery = trim((string) ($forcedListingCategory ?? '')) !== '' ? ('?category=' . urlencode((string) $forcedListingCategory)) : '';
    $sidebarOverviewOpen = in_array($activePortalPage ?? 'overview', ['overview', 'reports'], true);
    $sidebarListingsOpen = ($activePortalPage ?? '') === 'listings';
    $sidebarOperationsOpen = in_array($activePortalPage ?? '', ['reservations', 'operations', 'availability'], true);
    $sidebarGrowthOpen = in_array($activePortalPage ?? '', ['promotions', 'engagement', 'billing'], true);
    $sidebarAccountOpen = in_array($activePortalPage ?? '', ['profile', 'api'], true);
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

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="overview" aria-expanded="{{ $sidebarOverviewOpen ? 'true' : 'false' }}">
            <span>Overview</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarOverviewOpen ? 'is-open' : '' }}" data-vendor-nav-group="overview">
            <a class="nav-item-link {{ ($activePortalPage ?? 'overview') === 'overview' ? 'prominent' : '' }}" href="/vendor?page=overview" data-panel-key="overview">Dashboard</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'reports' ? 'prominent' : '' }}" href="/vendor/reports" data-panel-key="overview">Reports &amp; Performance</a>
        </div>
    </div>

    @if ($sidebarHasServiceAccess)
        <div class="nav-group">
            <button class="nav-group-header" type="button" data-vendor-nav-toggle="listings" aria-expanded="{{ $sidebarListingsOpen ? 'true' : 'false' }}">
                <span>Listings</span>
                <span class="nav-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="nav-group-body {{ $sidebarListingsOpen ? 'is-open' : '' }}" data-vendor-nav-group="listings">
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'listings' && ($forcedListingCategory ?? '') === '' ? 'prominent' : '' }}" href="/vendor/listings" data-panel-key="listings">All Listings</a>
            </div>
        </div>

        <div class="nav-group">
            <button class="nav-group-header" type="button" data-vendor-nav-toggle="operations" aria-expanded="{{ $sidebarOperationsOpen ? 'true' : 'false' }}">
                <span>Operations</span>
                <span class="nav-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="nav-group-body {{ $sidebarOperationsOpen ? 'is-open' : '' }}" data-vendor-nav-group="operations">
                <a class="nav-item-link {{ in_array($activePortalPage ?? '', ['reservations', 'operations'], true) ? 'prominent' : '' }}" href="/vendor/reservations" data-panel-key="reservations">Reservations Queue</a>
            </div>
        </div>
    @else
        <div class="nav-group">
            <p class="nav-locked-note">Listings and operations unlock after category verification by admin.</p>
        </div>
    @endif

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="growth" aria-expanded="{{ $sidebarGrowthOpen ? 'true' : 'false' }}">
            <span>Growth &amp; Billing</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarGrowthOpen ? 'is-open' : '' }}" data-vendor-nav-group="growth">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'billing' ? 'prominent' : '' }}" href="{{ '/vendor/billing' . $sidebarCategoryQuery }}" data-panel-key="billing">Collections &amp; Payouts</a>
            <a class="nav-item-link {{ in_array($activePortalPage ?? '', ['engagement', 'promotions'], true) ? 'prominent' : '' }}" href="/vendor/promotions" data-panel-key="engagement">Customers, Reviews &amp; Loyalty</a>
        </div>
    </div>

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="account" aria-expanded="{{ $sidebarAccountOpen ? 'true' : 'false' }}">
            <span>Account</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarAccountOpen ? 'is-open' : '' }}" data-vendor-nav-group="account">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'profile' ? 'prominent' : '' }}" href="/vendor/profile" data-panel-key="profile">Partner Profile</a>
            <a class="nav-item-link" href="#api" data-panel-key="api">API Tools</a>
        </div>
    </div>
</nav>