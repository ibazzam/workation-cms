<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f6f8fc;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #ffffff;
            --line: #d7e0e6;
            --hero-1: #0f4f7f;
            --hero-2: #0b7880;
            --hero-3: #47a89f;
            --accent: #f6a53e;
            --ok: #0b5c2a;
            --ok-bg: #d8f7e2;
            --warn: #7a4606;
            --warn-bg: #ffeccd;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 6%, #d6f2ef 0, #d6f2ef00 30%),
                radial-gradient(circle at 90% 8%, #ffe5b7 0, #ffe5b700 28%),
                var(--bg);
        }

        .page {
            width: min(1240px, calc(100% - 24px));
            margin: 12px auto 28px;
        }

        .customer-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: color-mix(in srgb, #ffffff 90%, #eef7fd 10%);
            margin-bottom: 10px;
        }

        .customer-topbar h2 {
            margin: 0;
            font-size: 0.92rem;
            color: #1d435d;
            letter-spacing: 0.02em;
        }

        .customer-topbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .customer-pill {
            display: inline-flex;
            align-items: center;
            border: 1px solid #cadbeb;
            border-radius: 999px;
            padding: 6px 10px;
            background: #f4fbff;
            color: #1e4b69;
            font-size: 0.79rem;
            font-weight: 700;
        }

        .topbar-link,
        .topbar-btn {
            text-decoration: none;
            border: 1px solid #cadbeb;
            border-radius: 10px;
            padding: 7px 11px;
            background: #ffffff;
            color: #1e4b69;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        .topbar-link.primary {
            color: #ffffff;
            background: linear-gradient(135deg, #0f5f79 0%, #1f7f8f 100%);
            border-color: #0f5f79;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 52%, var(--hero-3) 100%);
            border-radius: 18px;
            color: #fff;
            padding: 18px;
            box-shadow: 0 16px 36px rgba(18, 38, 58, 0.18);
        }

        .eyebrow {
            display: inline-block;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #d7f2f5;
            margin-bottom: 10px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.45rem, 2.5vw, 2.1rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 900px;
            font-size: 0.95rem;
        }

        .hero-actions {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hero-btn {
            border: 1px solid rgba(219, 247, 248, 0.5);
            border-radius: 10px;
            padding: 8px 12px;
            text-decoration: none;
            color: #ebfbff;
            background: rgba(10, 57, 70, 0.25);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .portal-shell {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            min-height: calc(100vh - 96px);
        }

        .side-panel {
            display: grid;
            gap: 10px;
            position: sticky;
            top: 8px;
        }

        .hero-links,
        .quick-filters {
            display: grid;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
        }

        .hero-link {
            color: #1f4d53;
            text-decoration: none;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.82rem;
            background: #f4faf8;
            border: 1px solid #d4e6e2;
            font-weight: 700;
        }

        .portal-nav {
            display: grid;
            gap: 8px;
        }

        .portal-nav a {
            text-decoration: none;
            border: 1px solid #d6e2ec;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #204864;
            background: #f7fbff;
        }

        .quick-filter-btn {
            border: 1px solid #d7e4ee;
            border-radius: 10px;
            padding: 8px;
            background: #f8fbff;
            color: #24405a;
            text-align: left;
            font-size: 0.82rem;
            cursor: pointer;
            font-weight: 700;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .quick-filter-btn:hover {
            border-color: #a6bfd4;
            background: #f0f7ff;
        }

        .quick-filter-btn.is-active {
            border-color: #5d97bd;
            background: #eaf4ff;
            color: #113e62;
        }

        .portal-content {
            min-width: 0;
        }

        .summary-grid {
            margin-top: 2px;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .summary-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }

        .summary-card.accent {
            background: linear-gradient(120deg, #fff6e3 0%, #fff1d6 100%);
            border-color: #f5d29b;
        }

        .summary-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .summary-value {
            margin: 6px 0 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f3346;
        }

        .summary-meta {
            margin: 6px 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .easy-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #d7e2ea;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            color: #294760;
        }

        .easy-toggle input {
            width: 18px;
            height: 18px;
            accent-color: #187e73;
        }

        .discovery-tools {
            margin-top: 12px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            display: grid;
            grid-template-columns: 1.25fr repeat(3, minmax(0, 1fr));
            gap: 8px;
            align-items: end;
        }

        .field {
            display: grid;
            gap: 4px;
        }

        .field label {
            font-size: 0.73rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .field input,
        .field select {
            border: 1px solid #c9d7e3;
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1a3348;
            background: #fff;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .label {
            margin: 0 0 8px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .results-row {
            margin: 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 0.83rem;
            color: #4c6278;
        }

        .listing-feed {
            margin-top: 14px;
        }

        .listing-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 12px;
        }

        .listing-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(23, 44, 66, 0.08);
            display: grid;
            grid-template-rows: auto 1fr;
        }

        .listing-property-media {
            width: 100%;
            aspect-ratio: 16 / 10;
            height: auto;
            display: block;
            object-fit: cover;
            background: #e8eef4;
        }

        .listing-content {
            padding: 12px;
        }

        .listing-title {
            margin: 0;
            font-size: 1.06rem;
            color: #1f3346;
        }

        .listing-badges {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .badge {
            border-radius: 999px;
            border: 1px solid #d8e4ee;
            background: #f7fbff;
            color: #35556f;
            padding: 4px 8px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .listing-meta {
            margin: 6px 0 0;
            font-size: 0.84rem;
            color: #48627b;
        }

        .room-list {
            margin-top: 10px;
            display: grid;
            gap: 8px;
        }

        .room-item {
            border: 1px solid #dbe5ee;
            border-radius: 10px;
            background: #f9fcff;
            padding: 8px;
            display: grid;
            grid-template-columns: 78px 1fr;
            gap: 8px;
            align-items: center;
        }

        .room-item img {
            width: 78px;
            height: 58px;
            object-fit: cover;
            border-radius: 8px;
            background: #e8eef4;
        }

        .room-item strong {
            display: block;
            font-size: 0.84rem;
            color: #1f3346;
        }

        .room-item span {
            font-size: 0.78rem;
            color: #48627b;
        }

        .listing-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .listing-actions a {
            text-decoration: none;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .btn-go {
            background: #0f7e79;
            color: #f2fffd;
        }

        .btn-lite {
            border: 1px solid #d5e3ef;
            color: #2a4c68;
            background: #f7fbff;
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .empty {
            border: 1px dashed #c8d3df;
            border-radius: 10px;
            padding: 12px;
            color: var(--muted);
            font-size: 0.85rem;
            background: #f9fcff;
        }

        .list {
            margin: 0;
            padding-left: 18px;
            line-height: 1.45;
            color: #29435b;
            font-size: 0.84rem;
        }

        .status-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-pill.ok { color: var(--ok); background: var(--ok-bg); }
        .status-pill.warn { color: var(--warn); background: var(--warn-bg); }

        .no-results {
            margin-top: 12px;
            border: 1px dashed #cddbe8;
            background: #f7fbff;
            color: #3f5a72;
            border-radius: 12px;
            padding: 14px;
            font-size: 0.88rem;
        }

        .easy-mode .listing-title { font-size: 1.24rem; }
        .easy-mode .listing-meta,
        .easy-mode .room-item span,
        .easy-mode .hero p { font-size: 0.98rem; }
        .easy-mode .listing-actions a,
        .easy-mode .portal-nav a,
        .easy-mode .hero-link,
        .easy-mode .quick-filter-btn { font-size: 0.92rem; padding: 10px 12px; }

        @media (max-width: 980px) {
            .portal-shell { grid-template-columns: 1fr; min-height: auto; }
            .side-panel { position: static; }
            .portal-nav { grid-template-columns: 1fr 1fr; }
            .discovery-tools { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .page { width: calc(100% - 16px); }
            .customer-topbar { flex-direction: column; align-items: flex-start; }
            .customer-topbar-actions { width: 100%; justify-content: flex-start; }
            .hero { padding: 14px; }
            .portal-nav { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $customerProperties = $customerProperties ?? collect();
        $customerRoomsByProperty = $customerRoomsByProperty ?? collect();
        $propertyMediaByProperty = $propertyMediaByProperty ?? collect();
        $roomMediaByRoom = $roomMediaByRoom ?? collect();
        $customerLoggedIn = (bool) session('portal_customer_authenticated', false);
        $customerName = trim((string) session('portal_customer_user', 'Customer'));
        $mediaVariantUrl = static function ($media, string $variant = 'banner'): ?string {
            $mediaId = (int) ($media->id ?? 0);
            if ($mediaId <= 0) {
                return null;
            }

            $normalizedVariant = strtolower(trim($variant));
            if (!in_array($normalizedVariant, ['banner', 'thumb'], true)) {
                $normalizedVariant = 'banner';
            }

            return '/media/vendor/' . $mediaId . '/' . $normalizedVariant;
        };
    @endphp

    <main class="page">
        <header class="customer-topbar" aria-label="Customer account status">
            <h2>Customer Portal</h2>
            <div class="customer-topbar-actions">
                @if ($customerLoggedIn)
                    <span class="customer-pill">Hi, {{ $customerName }}</span>
                    <form method="POST" action="/portal/customer/logout" style="margin:0;">
                        @csrf
                        <button class="topbar-btn" type="submit">Logout</button>
                    </form>
                @else
                    <a class="topbar-link" href="/portal/customer/login">Customer Login</a>
                    <a class="topbar-link primary" href="/portal/customer/register">Customer Registration</a>
                @endif
            </div>
        </header>

        <section class="hero">
            <span class="eyebrow">Customer Experience</span>
            <h1>Pick your island plan in a few taps.</h1>
            <p>Search, compare, and choose your ideal stay or experience with clear photos, simple language, and no confusion.</p>
            <div class="hero-actions">
                <a class="hero-btn" href="/">Back to Home</a>
                <a class="hero-btn" href="#discoverListings">Start browsing</a>
                <a class="hero-btn" href="mailto:support@workation.mv">Need help?</a>
            </div>
        </section>

        <div class="portal-shell">
            <aside class="side-panel" aria-label="Customer shortcuts">
                <div class="hero-links">
                    <a class="hero-link" href="/">Home</a>
                    <a class="hero-link" href="/vendor">Vendor Portal</a>
                    <a class="hero-link" href="/admin">Admin Portal</a>
                </div>
                <nav class="portal-nav" aria-label="Customer navigation">
                    <a href="#customerSummary">Summary</a>
                    <a href="#discoverListings">Listings</a>
                    <a href="#bookingsCard">Bookings</a>
                    <a href="#paymentsCard">Payments</a>
                    <a href="#notificationsCard">Notifications</a>
                </nav>
                <div class="quick-filters">
                    <strong style="font-size:0.82rem; color:#2a4862;">Quick Picks</strong>
                    <button class="quick-filter-btn" type="button" data-quick-category="all">All listings</button>
                    <button class="quick-filter-btn" type="button" data-quick-category="accommodation">Stays</button>
                    <button class="quick-filter-btn" type="button" data-quick-category="remote_workspace">Workspaces</button>
                    <button class="quick-filter-btn" type="button" data-quick-category="transport">Transport</button>
                    <button class="quick-filter-btn" type="button" data-quick-category="excursion">Excursions</button>
                </div>
            </aside>

            <div class="portal-content">
                <section id="customerSummary" class="summary-grid" aria-label="Customer dashboard summary">
                    <article class="summary-card">
                        <p class="summary-label">Upcoming Trips</p>
                        <p class="summary-value">{{ $summary['upcoming_bookings'] }}</p>
                        <p class="summary-meta">Confirmed future bookings</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-label">Completed Trips</p>
                        <p class="summary-value">{{ $summary['completed_bookings'] }}</p>
                        <p class="summary-meta">Booking history completed</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-label">Payment Receipts</p>
                        <p class="summary-value">{{ $summary['receipts_available'] }}</p>
                        <p class="summary-meta">Downloadable receipts available</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-label">Notification Status</p>
                        <p class="summary-value"><span class="status-pill {{ $summary['notification_state'] === 'ACTIVE' ? 'ok' : 'warn' }}">{{ $summary['notification_state'] }}</span></p>
                        <p class="summary-meta">Messages and booking updates</p>
                    </article>
                    <article class="summary-card accent">
                        <p class="summary-label">Simple Mode</p>
                        <p class="summary-value" style="font-size:1rem; margin-top:8px;">
                            <label class="easy-toggle" for="easyModeToggle">
                                <input id="easyModeToggle" type="checkbox">
                                Bigger text and simpler browsing
                            </label>
                        </p>
                        <p class="summary-meta">Designed for kids and first-time users</p>
                    </article>
                </section>

                <section class="discovery-tools" aria-label="Discovery filters">
                    <div class="field">
                        <label for="listingSearch">Search</label>
                        <input id="listingSearch" type="text" placeholder="Try: beach, male, ferry, workspace...">
                    </div>
                    <div class="field">
                        <label for="listingCategoryFilter">Category</label>
                        <select id="listingCategoryFilter">
                            <option value="all">All categories</option>
                            <option value="accommodation">Accommodation</option>
                            <option value="remote_workspace">Remote Workspace</option>
                            <option value="transport">Transport</option>
                            <option value="excursion">Excursion</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="vehicle_rental">Vehicle Rental</option>
                            <option value="resort_day_visit">Resort Day Visit</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="listingPriceFilter">Price band</label>
                        <select id="listingPriceFilter">
                            <option value="all">Any price</option>
                            <option value="0-500">Up to 500</option>
                            <option value="501-1000">501 to 1000</option>
                            <option value="1001-2500">1001 to 2500</option>
                            <option value="2501-9999999">2500+</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="listingSort">Sort by</label>
                        <select id="listingSort">
                            <option value="recommended">Recommended</option>
                            <option value="priceAsc">Price low to high</option>
                            <option value="priceDesc">Price high to low</option>
                            <option value="nameAsc">Name A to Z</option>
                        </select>
                    </div>
                </section>

                <section id="discoverListings" class="card listing-feed" aria-label="Customer listing feed">
                    <p class="label">Discover Properties and Rooms</p>
                    <div class="results-row">
                        <strong id="listingCountLabel">Showing 0 listings</strong>
                        <span>Tip: use quick picks for one-tap browsing</span>
                    </div>
                    @if ($customerProperties->isEmpty())
                        <div class="empty">No active listings are available right now. Please check again shortly.</div>
                    @else
                        <div class="listing-grid" id="listingGrid">
                            @foreach ($customerProperties as $property)
                                @php
                                    $propertyId = (int) ($property->id ?? 0);
                                    $propertyMedia = collect($propertyMediaByProperty->get($propertyId, collect()));
                                    $primaryPropertyMedia = $propertyMedia->first();
                                    $propertyImageThumbUrl = $primaryPropertyMedia ? $mediaVariantUrl($primaryPropertyMedia, 'thumb') : null;
                                    $propertyImageBannerUrl = $primaryPropertyMedia ? $mediaVariantUrl($primaryPropertyMedia, 'banner') : null;
                                    $propertyImageFallback = $primaryPropertyMedia ? ('/storage/' . ltrim((string) ($primaryPropertyMedia->file_path ?? ''), '/')) : '';
                                    $allRoomsForProperty = collect($customerRoomsByProperty->get($propertyId, collect()));
                                    $roomsForProperty = $allRoomsForProperty->take(4);
                                    $lowestPricedRoom = $allRoomsForProperty
                                        ->filter(static fn ($room) => is_numeric($room->base_price ?? null))
                                        ->sortBy(static fn ($room) => (float) ($room->base_price ?? 0))
                                        ->first();
                                    $categoryKey = strtolower((string) ($property->listing_category ?? ''));
                                    $isAccommodationListing = $categoryKey === 'accommodation';
                                    $lowestRoomRate = $lowestPricedRoom ? (float) ($lowestPricedRoom->base_price ?? 0) : null;
                                    $lowestRoomCurrency = strtoupper((string) (($lowestPricedRoom->currency ?? null) ?: ($property->currency ?? 'MVR')));
                                    $displayPrice = $isAccommodationListing ? (float) ($lowestRoomRate ?? 0) : (float) ($property->base_price ?? 0);
                                    $locationText = trim((string) ($property->location ?? ''));
                                    $searchText = strtolower(trim((string) ($property->name ?? '')) . ' ' . $locationText . ' ' . $categoryKey);
                                @endphp
                                <article
                                    class="listing-card"
                                    data-listing-card
                                    data-search="{{ $searchText }}"
                                    data-category="{{ $categoryKey !== '' ? $categoryKey : 'other' }}"
                                    data-price="{{ number_format($displayPrice, 2, '.', '') }}"
                                    data-name="{{ strtolower(trim((string) ($property->name ?? ''))) }}"
                                >
                                    @if ($propertyImageThumbUrl)
                                        <img class="listing-property-media" src="{{ $propertyImageThumbUrl }}" srcset="{{ $propertyImageThumbUrl }} 480w, {{ $propertyImageBannerUrl ?? $propertyImageThumbUrl }} 1600w" sizes="(max-width: 900px) 100vw, 50vw" onerror="if(!this.dataset.fallbackTried){this.dataset.fallbackTried='1';this.src='{{ $propertyImageFallback }}';}" alt="{{ $primaryPropertyMedia->alt_text ?? ($property->name . ' photo') }}" loading="lazy">
                                    @endif
                                    <div class="listing-content">
                                        <h2 class="listing-title">{{ $property->name }}</h2>
                                        <p class="listing-meta">{{ $property->location ?: 'Location details coming soon' }}</p>
                                        <div class="listing-badges">
                                            <span class="badge">{{ strtoupper($categoryKey !== '' ? str_replace('_', ' ', $categoryKey) : 'GENERAL') }}</span>
                                            <span class="badge">{{ strtoupper((string) ($property->currency ?? 'MVR')) }}</span>
                                        </div>
                                        @if ($isAccommodationListing)
                                            <p class="listing-meta">
                                                @if ($lowestRoomRate !== null)
                                                    Starting from {{ $lowestRoomCurrency }} {{ number_format($lowestRoomRate, 2) }} per night | {{ $allRoomsForProperty->count() }} room type{{ $allRoomsForProperty->count() === 1 ? '' : 's' }}
                                                @else
                                                    Room rates coming soon
                                                @endif
                                            </p>
                                        @else
                                            <p class="listing-meta">{{ strtoupper((string) ($property->currency ?? 'MVR')) }} {{ number_format((float) ($property->base_price ?? 0), 2) }} | Guests: {{ (int) ($property->max_guests ?? 0) }}</p>
                                        @endif
                                        <div class="room-list" aria-label="Room-level entries for {{ $property->name }}">
                                            @forelse ($roomsForProperty as $room)
                                                @php
                                                    $roomId = (int) ($room->id ?? 0);
                                                    $roomMediaItems = collect($roomMediaByRoom->get($roomId, collect()));
                                                    $primaryRoomMedia = $roomMediaItems->first();
                                                    $roomImageThumbUrl = $primaryRoomMedia ? $mediaVariantUrl($primaryRoomMedia, 'thumb') : null;
                                                    $roomImageBannerUrl = $primaryRoomMedia ? $mediaVariantUrl($primaryRoomMedia, 'banner') : null;
                                                    $roomImageFallback = $primaryRoomMedia ? ('/storage/' . ltrim((string) ($primaryRoomMedia->file_path ?? ''), '/')) : '';
                                                @endphp
                                                <article class="room-item">
                                                    @if ($roomImageThumbUrl)
                                                        <img src="{{ $roomImageThumbUrl }}" srcset="{{ $roomImageThumbUrl }} 480w, {{ $roomImageBannerUrl ?? $roomImageThumbUrl }} 1600w" sizes="70px" onerror="if(!this.dataset.fallbackTried){this.dataset.fallbackTried='1';this.src='{{ $roomImageFallback }}';}" alt="{{ $primaryRoomMedia->alt_text ?? ($room->name . ' photo') }}" loading="lazy">
                                                    @else
                                                        <div class="empty" style="margin:0; padding:6px; font-size:0.74rem;">No photo</div>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $room->name }}</strong>
                                                        <span>Qty {{ (int) ($room->quantity ?? 0) }} | Max {{ (int) ($room->max_occupancy ?? 0) }} | {{ strtoupper((string) ($room->currency ?? 'MVR')) }} {{ number_format((float) ($room->base_price ?? 0), 2) }}</span>
                                                    </div>
                                                </article>
                                            @empty
                                                <div class="empty">Room inventory will appear here once published by the vendor.</div>
                                            @endforelse
                                        </div>
                                        <div class="listing-actions">
                                            <a class="btn-go" href="#">Book now</a>
                                            <a class="btn-lite" href="#">View details</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div id="noResultsState" class="no-results" hidden>No listings match your filters yet. Try another category or clear search terms.</div>
                    @endif
                </section>

                <section class="layout">
                    <article id="bookingsCard" class="card">
                        <p class="label">Bookings</p>
                        <div class="empty">Bookings layout is ready. Connect this panel to customer booking API payloads for live itinerary cards and detail links.</div>
                    </article>
                    <article id="paymentsCard" class="card">
                        <p class="label">Payments and Receipts</p>
                        <ul class="list">
                            <li>Recent payment timeline with status badges</li>
                            <li>Receipt download links for settled invoices</li>
                            <li>Support escalation entry for failed charges</li>
                        </ul>
                    </article>
                    <article id="notificationsCard" class="card">
                        <p class="label">Notifications Center</p>
                        <div class="empty">No new notifications. This section is ready for booking updates, reminders, and support responses.</div>
                    </article>
                    <article class="card">
                        <p class="label">Account and Support</p>
                        <ul class="list">
                            <li><a href="/terms-of-service">Terms of Service</a></li>
                            <li><a href="/privacy-policy">Privacy Policy</a></li>
                            <li><a href="mailto:support@workation.mv">Contact Support</a></li>
                        </ul>
                    </article>
                </section>

                @include('partials.global-site-footer')
            </div>
        </div>
    </main>

    <script>
        (function () {
            const searchInput = document.getElementById('listingSearch');
            const categoryFilter = document.getElementById('listingCategoryFilter');
            const priceFilter = document.getElementById('listingPriceFilter');
            const sortFilter = document.getElementById('listingSort');
            const listingGrid = document.getElementById('listingGrid');
            const listingCountLabel = document.getElementById('listingCountLabel');
            const noResultsState = document.getElementById('noResultsState');
            const easyModeToggle = document.getElementById('easyModeToggle');
            const quickButtons = Array.from(document.querySelectorAll('[data-quick-category]'));

            if (!listingGrid) {
                return;
            }

            const cards = Array.from(listingGrid.querySelectorAll('[data-listing-card]'));

            function parsePriceBand(value) {
                if (!value || value === 'all') {
                    return null;
                }

                const tokens = String(value).split('-');
                if (tokens.length !== 2) {
                    return null;
                }

                const minValue = Number(tokens[0]);
                const maxValue = Number(tokens[1]);
                if (!Number.isFinite(minValue) || !Number.isFinite(maxValue)) {
                    return null;
                }

                return { minValue, maxValue };
            }

            function render() {
                const searchTerm = (searchInput ? searchInput.value : '').trim().toLowerCase();
                const categoryValue = categoryFilter ? categoryFilter.value : 'all';
                const priceBand = parsePriceBand(priceFilter ? priceFilter.value : 'all');

                const visibleCards = cards.filter((card) => {
                    const searchable = String(card.getAttribute('data-search') || '').toLowerCase();
                    const category = String(card.getAttribute('data-category') || 'other').toLowerCase();
                    const price = Number(card.getAttribute('data-price') || 0);

                    const searchMatch = searchTerm === '' || searchable.includes(searchTerm);
                    const categoryMatch = categoryValue === 'all' || category === categoryValue;
                    const priceMatch = priceBand === null || (price >= priceBand.minValue && price <= priceBand.maxValue);

                    const visible = searchMatch && categoryMatch && priceMatch;
                    card.hidden = !visible;
                    return visible;
                });

                const sortValue = sortFilter ? sortFilter.value : 'recommended';
                const sortedCards = visibleCards.slice().sort((left, right) => {
                    const leftPrice = Number(left.getAttribute('data-price') || 0);
                    const rightPrice = Number(right.getAttribute('data-price') || 0);
                    const leftName = String(left.getAttribute('data-name') || '');
                    const rightName = String(right.getAttribute('data-name') || '');

                    if (sortValue === 'priceAsc') {
                        return leftPrice - rightPrice;
                    }
                    if (sortValue === 'priceDesc') {
                        return rightPrice - leftPrice;
                    }
                    if (sortValue === 'nameAsc') {
                        return leftName.localeCompare(rightName);
                    }
                    return 0;
                });

                sortedCards.forEach((card) => listingGrid.appendChild(card));

                if (listingCountLabel) {
                    listingCountLabel.textContent = 'Showing ' + visibleCards.length + ' listing' + (visibleCards.length === 1 ? '' : 's');
                }

                if (noResultsState) {
                    noResultsState.hidden = visibleCards.length > 0;
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', render);
            }
            if (categoryFilter) {
                categoryFilter.addEventListener('change', render);
            }
            if (priceFilter) {
                priceFilter.addEventListener('change', render);
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', render);
            }

            quickButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const quickCategory = String(button.getAttribute('data-quick-category') || 'all');
                    if (categoryFilter) {
                        categoryFilter.value = quickCategory;
                    }

                    quickButtons.forEach((item) => item.classList.remove('is-active'));
                    button.classList.add('is-active');
                    render();
                });
            });

            if (easyModeToggle) {
                easyModeToggle.addEventListener('change', function () {
                    document.body.classList.toggle('easy-mode', !!easyModeToggle.checked);
                });
            }

            render();
        })();
    </script>
</body>
</html>