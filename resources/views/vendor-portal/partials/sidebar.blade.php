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
    $portalMode = in_array(($portalMode ?? 'simple'), ['simple', 'advanced'], true) ? $portalMode : 'simple';
    $sidebarOverviewOpen = in_array($activePortalPage ?? 'overview', ['overview', 'setup'], true);
    $sidebarListingsOpen = in_array($activePortalPage ?? '', ['listings', 'availability'], true);
    $sidebarOperationsOpen = in_array($activePortalPage ?? '', ['reservations', 'operations'], true);
    $sidebarBillingOpen = ($activePortalPage ?? '') === 'billing';
    $sidebarDistributionOpen = in_array($activePortalPage ?? '', ['distribution', 'compliance', 'profile', 'reports'], true);
    $sidebarGuestOpen = in_array($activePortalPage ?? '', ['messages'], true);
@endphp

<button class="portal-nav-mobile-toggle" id="portalNavMobileToggle" type="button" aria-expanded="false" aria-controls="portalNavMenu">
    <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-bars" aria-hidden="true"></i> Menu</span>
    <i class="fa-solid fa-chevron-down portal-nav-toggle-icon" aria-hidden="true"></i>
</button>

<nav class="portal-nav" id="portalNavMenu" aria-label="Vendor navigation">
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
            <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarOverviewOpen ? 'is-open' : '' }}" data-vendor-nav-group="overview">
            <a class="nav-item-link {{ ($activePortalPage ?? 'overview') === 'overview' ? 'prominent' : '' }}" href="/vendor?page=overview&mode=simple" data-panel-key="overview"><i class="fa-solid fa-gauge" aria-hidden="true"></i> Home</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'setup' ? 'prominent' : '' }}" href="/vendor?page=setup&mode=simple" data-panel-key="distribution"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Setup</a>
        </div>
    </div>

    @if ($sidebarHasServiceAccess)
        <div class="nav-group">
            <button class="nav-group-header" type="button" data-vendor-nav-toggle="listings" aria-expanded="{{ $sidebarListingsOpen ? 'true' : 'false' }}">
                <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-building" aria-hidden="true"></i> Listings</span>
                <span class="nav-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="nav-group-body {{ $sidebarListingsOpen ? 'is-open' : '' }}" data-vendor-nav-group="listings">
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'listings' && ($forcedListingCategory ?? '') === '' ? 'prominent' : '' }}" href="/vendor/listings" data-panel-key="listings"><i class="fa-solid fa-list-check" aria-hidden="true"></i> My Listings</a>
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'availability' ? 'prominent' : '' }}" href="/vendor/availability" data-panel-key="availability"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Calendar</a>
            </div>
        </div>

        <div class="nav-group">
            <button class="nav-group-header" type="button" data-vendor-nav-toggle="operations" aria-expanded="{{ $sidebarOperationsOpen ? 'true' : 'false' }}">
                <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Operations</span>
                <span class="nav-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="nav-group-body {{ $sidebarOperationsOpen ? 'is-open' : '' }}" data-vendor-nav-group="operations">
                <a class="nav-item-link {{ in_array($activePortalPage ?? '', ['reservations', 'operations'], true) ? 'prominent' : '' }}" href="/vendor/reservations" data-panel-key="reservations"><i class="fa-solid fa-receipt" aria-hidden="true"></i> Bookings</a>
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'messages' ? 'prominent' : '' }}" href="{{ '/vendor/messages' . $sidebarCategoryQuery }}" data-panel-key="messages"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Messages</a>
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'reports' ? 'prominent' : '' }}" href="/vendor/reports" data-panel-key="overview"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Performance</a>
            </div>
        </div>
    @else
        <div class="nav-group">
            <p class="nav-locked-note">Listings and operations unlock after category verification by admin.</p>
        </div>
    @endif

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="billing" aria-expanded="{{ $sidebarBillingOpen ? 'true' : 'false' }}">
            <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-wallet" aria-hidden="true"></i> Payments</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarBillingOpen ? 'is-open' : '' }}" data-vendor-nav-group="billing">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'billing' ? 'prominent' : '' }}" href="{{ '/vendor/billing' . $sidebarCategoryQuery }}" data-panel-key="billing"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i> Billing &amp; Payouts</a>
        </div>
    </div>

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="distribution" aria-expanded="{{ $sidebarDistributionOpen ? 'true' : 'false' }}">
            <span style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Advanced</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarDistributionOpen ? 'is-open' : '' }}" data-vendor-nav-group="distribution">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'distribution' ? 'prominent' : '' }}" href="/vendor?page=distribution&mode=advanced" data-panel-key="distribution"><i class="fa-solid fa-right-left" aria-hidden="true"></i> Channel logs</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'compliance' ? 'prominent' : '' }}" href="/vendor/compliance" data-panel-key="compliance"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Compliance</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'profile' ? 'prominent' : '' }}" href="/vendor/profile" data-panel-key="profile"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Technical settings</a>
        </div>
    </div>
</nav>