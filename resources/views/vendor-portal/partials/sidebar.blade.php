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
    $sidebarBillingOpen = ($activePortalPage ?? '') === 'billing';
    $sidebarDistributionOpen = ($activePortalPage ?? '') === 'distribution';
    $sidebarGuestOpen = in_array($activePortalPage ?? '', ['messages', 'engagement', 'promotions'], true);
    $sidebarAccountOpen = in_array($activePortalPage ?? '', ['profile', 'compliance'], true);
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
            <span>Executive Overview</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarOverviewOpen ? 'is-open' : '' }}" data-vendor-nav-group="overview">
            <a class="nav-item-link {{ ($activePortalPage ?? 'overview') === 'overview' ? 'prominent' : '' }}" href="/vendor?page=overview" data-panel-key="overview">Dashboard</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'reports' ? 'prominent' : '' }}" href="/vendor/reports" data-panel-key="reports">Performance Reports</a>
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
                <span>Inventory &amp; Operations</span>
                <span class="nav-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="nav-group-body {{ $sidebarOperationsOpen ? 'is-open' : '' }}" data-vendor-nav-group="operations">
                <a class="nav-item-link {{ in_array($activePortalPage ?? '', ['reservations', 'operations'], true) ? 'prominent' : '' }}" href="/vendor/reservations" data-panel-key="reservations">Reservations Queue</a>
                <a class="nav-item-link {{ ($activePortalPage ?? '') === 'availability' ? 'prominent' : '' }}" href="/vendor/availability" data-panel-key="availability">Availability &amp; Allotment</a>
            </div>
        </div>
    @else
        <div class="nav-group">
            <p class="nav-locked-note">Listings and operations unlock after category verification by admin.</p>
        </div>
    @endif

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="billing" aria-expanded="{{ $sidebarBillingOpen ? 'true' : 'false' }}">
            <span>Finance &amp; Reconciliation</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarBillingOpen ? 'is-open' : '' }}" data-vendor-nav-group="billing">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'billing' ? 'prominent' : '' }}" href="{{ '/vendor/billing' . $sidebarCategoryQuery }}" data-panel-key="billing">Collections &amp; Payouts</a>
        </div>
    </div>

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="distribution" aria-expanded="{{ $sidebarDistributionOpen ? 'true' : 'false' }}">
            <span>Distribution &amp; Connectivity</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarDistributionOpen ? 'is-open' : '' }}" data-vendor-nav-group="distribution">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'distribution' ? 'prominent' : '' }}" href="/vendor/distribution" data-panel-key="distribution">Channel Manager</a>
        </div>
    </div>

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="guest" aria-expanded="{{ $sidebarGuestOpen ? 'true' : 'false' }}">
            <span>Guest Experience &amp; Growth</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarGuestOpen ? 'is-open' : '' }}" data-vendor-nav-group="guest">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'messages' ? 'prominent' : '' }}" href="{{ '/vendor/messages' . $sidebarCategoryQuery }}" data-panel-key="messages">Guest Messaging</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'engagement' ? 'prominent' : '' }}" href="/vendor/engagement" data-panel-key="engagement">Reviews &amp; Loyalty</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'promotions' ? 'prominent' : '' }}" href="/vendor/promotions" data-panel-key="promotions">Promotions</a>
        </div>
    </div>

    <div class="nav-group">
        <button class="nav-group-header" type="button" data-vendor-nav-toggle="account" aria-expanded="{{ $sidebarAccountOpen ? 'true' : 'false' }}">
            <span>Account &amp; Compliance</span>
            <span class="nav-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="nav-group-body {{ $sidebarAccountOpen ? 'is-open' : '' }}" data-vendor-nav-group="account">
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'profile' ? 'prominent' : '' }}" href="/vendor/profile" data-panel-key="profile">Partner Profile</a>
            <a class="nav-item-link {{ ($activePortalPage ?? '') === 'compliance' ? 'prominent' : '' }}" href="/vendor/compliance" data-panel-key="compliance">Compliance &amp; Operations</a>
        </div>
    </div>
</nav>