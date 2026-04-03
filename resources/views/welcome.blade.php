<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --surface-soft: #edf6f3;
            --brand: #0f6179;
            --brand-soft: #dff1f6;
            --accent: #f3a337;
            --accent-soft: #fff3df;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 6% 2%, #d9eee8 0, #d9eee800 32%),
                radial-gradient(circle at 95% 4%, #ffe6c2 0, #ffe6c200 33%),
                var(--bg);
        }

        .page {
            margin: 14px 14px 30px 270px;
            position: relative;
        }

        .floating-sidebar {
            position: fixed;
            left: 12px;
            top: 12px;
            width: 250px;
            height: calc(100vh - 24px);
            overflow-y: auto;
            z-index: 900;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid #c7d9e7;
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #f2f9ff 55%, #eef8fb 100%);
            box-shadow: 0 12px 26px rgba(22, 64, 93, 0.1);
            margin-bottom: 12px;
        }

        .header-brand {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: #163f5a;
            letter-spacing: 0.02em;
        }

        .header-subline {
            margin: 2px 0 0;
            font-size: 0.76rem;
            color: #587186;
        }

        .hero-layout {
            display: grid;
            grid-template-columns: minmax(220px, 250px) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .page-with-sidebar {
            display: contents;
        }

        .sidebar-fixed {
            display: none;
        }

        .sidebar-shell {
            border: 1px solid #c9ddeb;
            border-radius: 16px;
            background: linear-gradient(160deg, #f8fcff 0%, #eef6fb 100%);
            padding: 10px;
            box-shadow: inset 0 1px 0 #ffffff;
        }

        .sidebar-title {
            margin: 0 0 8px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4e6d83;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .customer-auth {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .auth-welcome {
            display: inline-flex;
            align-items: center;
            border: 1px solid #c9dbea;
            border-radius: 999px;
            padding: 6px 11px;
            background: #f6fbff;
            color: #1a4968;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .auth-link {
            text-decoration: none;
            border: 1px solid #c9dbea;
            border-radius: 10px;
            padding: 7px 12px;
            background: #f6fbff;
            color: #19466a;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
        }

        .auth-link.primary {
            background: linear-gradient(135deg, #0f6179 0%, #1e7d90 100%);
            border-color: #0f6179;
            color: #ffffff;
        }

        .auth-btn {
            border: 1px solid #d2dde8;
            border-radius: 10px;
            padding: 7px 12px;
            background: #ffffff;
            color: #385772;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        .top-links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .top-link {
            text-decoration: none;
            border: 1px solid #d4e3ee;
            border-radius: 10px;
            background: #f8fcff;
            color: #19405b;
            padding: 9px 10px;
            font-size: 0.8rem;
            line-height: 1.28;
            font-weight: 600;
            text-align: left;
            display: grid;
            gap: 2px;
            min-height: 56px;
            align-content: center;
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .top-link:hover {
            border-color: #8db5cf;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(34, 86, 120, 0.16);
        }

        .top-link span {
            color: #5e7388;
            font-size: 0.73rem;
            font-weight: 500;
        }

        .search-section {
            margin-top: 0;
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(132deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: clamp(16px, 3vw, 24px);
            box-shadow: 0 20px 38px rgba(14, 65, 85, 0.22);
        }

        .search-section-full-width {
            margin-top: 0;
            margin-bottom: 12px;
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(132deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: clamp(16px, 3vw, 24px);
            box-shadow: 0 20px 38px rgba(14, 65, 85, 0.22);
            width: 100%;
        }

        .search-section-hidden {
            display: none;
        }

        .search-eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.72rem;
            color: #cfeff4;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .search-title {
            margin: 7px 0 0;
            font-size: clamp(1.2rem, 2.4vw, 2rem);
            line-height: 1.16;
        }

        .search-form {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            align-items: start;
        }

        .search-dynamic-fields {
            margin-top: 8px;
            display: none;
            grid-column: 1 / -1;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .search-dynamic-fields.is-active {
            display: grid;
        }

        .search-dynamic-fields .field {
            display: grid;
            gap: 4px;
        }

        .search-dynamic-fields .field label {
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #cfeff4;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .search-form select,
        .search-form input {
            width: 100%;
            border: 1px solid #b8d9e2;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            color: #103247;
            background: #f8fdff;
        }

        .search-dynamic-fields select,
        .search-dynamic-fields input {
            width: 100%;
            border: 1px solid #b8d9e2;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            color: #103247;
            background: #f8fdff;
        }

        .search-form button {
            border: 1px solid #f6d19a;
            background: linear-gradient(135deg, #ffc76f 0%, var(--accent) 100%);
            color: #57350b;
            border-radius: 10px;
            padding: 11px 20px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            min-width: 180px;
            justify-self: end;
            box-shadow: 0 8px 18px rgba(243, 163, 55, 0.28);
        }

        .search-submit-row {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            margin-top: 2px;
        }

        .search-actions {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-actions a {
            color: #dff7fb;
            font-size: 0.8rem;
            text-decoration: none;
            border: 1px solid rgba(214, 244, 248, 0.45);
            border-radius: 10px;
            padding: 9px 12px;
            background: rgba(4, 64, 83, 0.22);
        }

        .search-options {
            margin-top: 9px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .search-options a {
            text-decoration: none;
            color: #eafcff;
            border: 1px solid rgba(214, 244, 248, 0.5);
            background: rgba(4, 64, 83, 0.25);
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.76rem;
        }

        .promo-banner {
            margin-top: 12px;
            border: 1px solid #f3d2a4;
            border-radius: 14px;
            background: linear-gradient(95deg, #fff6e4 0%, #ffefd6 48%, #ffe5bf 100%);
            color: #5b3c13;
            padding: 12px 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .promo-banner strong {
            font-size: 0.95rem;
        }

        .promo-banner a {
            text-decoration: none;
            border: 1px solid #e5be86;
            background: #fff;
            color: #68410f;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .section {
            margin-top: 13px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 14px;
        }

        .section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }

        .section-title {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0.03em;
        }

        .section-sub {
            margin: 0;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .browse-grid,
        .trending-grid,
        .deal-grid,
        .loved-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .item-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #f5fbff;
            text-decoration: none;
            color: #1b3f58;
            display: grid;
            overflow: hidden;
            min-height: 210px;
            grid-template-rows: 128px auto;
        }

        .item-card-media {
            position: relative;
            width: 100%;
            height: 128px;
            background: linear-gradient(140deg, #d6edf1 0%, #bfdfeb 45%, #ffe3be 100%);
            overflow: hidden;
        }

        .item-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-card-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(7, 35, 52, 0.35), rgba(7, 35, 52, 0.05));
        }

        .item-card-body {
            padding: 10px;
            display: grid;
            gap: 4px;
            align-content: start;
            background: #fbfdff;
        }

        .item-card strong {
            font-size: 0.95rem;
            line-height: 1.28;
            color: #133b55;
        }

        .item-card span {
            color: #5b7185;
            font-size: 0.79rem;
            line-height: 1.35;
        }

        .item-card-meta {
            color: #2b617e;
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .home-footer-skin {
            margin-top: 8px;
            border-radius: 16px;
            background: linear-gradient(165deg, #f1f8fc 0%, #f9fcff 100%);
            border: 1px solid #d2e2ee;
            padding: 12px;
        }

        .home-footer-skin .wf-site-footer {
            margin-top: 0;
            border-top: 0;
            padding-top: 0;
        }

        .home-footer-skin .wf-footer-col {
            background: #ffffff;
            border-color: #d2e2ee;
        }

        .home-footer-skin .wf-footer-note {
            margin: 10px 2px 0;
            font-size: 0.75rem;
            color: #597286;
        }

        .chip-row {
            margin-top: 8px;
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .chip {
            border: 1px solid #cfe0eb;
            background: var(--surface-soft);
            color: #24516b;
            border-radius: 999px;
            font-size: 0.76rem;
            padding: 5px 10px;
        }

        /* Uniform Icon System Styles */
        .uniform-icon {
            display: inline-block;
            font-size: 1em;
            line-height: 1;
            margin: 0;
            padding: 0;
        }

        .uniform-icon-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: inherit;
        }

        .uniform-icon-label .uniform-icon {
            font-size: 1.2em;
            flex-shrink: 0;
        }

        .uniform-label {
            display: inline;
            font-size: inherit;
        }


        @media (max-width: 1040px) {
            .page {
                width: calc(100% - 28px);
                margin: 14px auto 30px;
                margin-left: 14px;
            }

            .floating-sidebar {
                position: static;
                width: 100%;
                height: auto;
                margin-bottom: 12px;
            }

            .page-with-sidebar {
                display: contents;
            }

            .sidebar-fixed {
                position: static;
                width: 100%;
                flex-shrink: auto;
            }

            .top-links {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .top-link {
                text-align: center;
            }

            .browse-grid,
            .trending-grid,
            .deal-grid,
            .loved-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .search-form {
                grid-template-columns: 1fr 1fr;
            }

            .search-submit-row {
                justify-content: stretch;
            }

            .search-submit-row button {
                width: 100%;
            }

            .search-dynamic-fields {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .floating-sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .header-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
            }

            .page-with-sidebar {
                flex-direction: column;
            }

            .sidebar-fixed {
                position: static;
                width: 100%;
            }

            .top-links {
                grid-template-columns: 1fr;
                position: static;
            }

            .top-link {
                min-height: 56px;
                font-size: 0.76rem;
                text-align: left;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .search-submit-row {
                justify-content: stretch;
            }

            .search-submit-row button {
                width: 100%;
            }

            .search-dynamic-fields {
                grid-template-columns: 1fr;
            }

            .browse-grid,
            .trending-grid,
            .deal-grid,
            .loved-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $customerLoggedIn = (bool) session('portal_customer_authenticated', false);
        $customerName = trim((string) session('portal_customer_user', 'Customer'));
        $homeTopCategoryLinks = $homeTopCategoryLinks ?? collect();
        $homePromoBanner = $homePromoBanner ?? ['message' => 'Promotions coming soon.', 'url' => '/catalog/accommodation', 'cta' => 'View Promotions'];
        $homeTrendingChips = $homeTrendingChips ?? collect();
        $homeBrowseCards = $homeBrowseCards ?? collect();
        $homeTrendingCards = $homeTrendingCards ?? collect();
        $homeWeekendDealCards = $homeWeekendDealCards ?? collect();
        $homeLovedCards = $homeLovedCards ?? collect();
    @endphp

    <aside class="floating-sidebar sidebar-shell" aria-label="Category sidebar">
        <p class="sidebar-title">Browse Categories</p>
        <section class="top-links" aria-label="Top categories">
            @foreach ($homeTopCategoryLinks as $link)
                @php
                    $linkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                    $categoryKeyFromUrl = preg_match('#/catalog/([a-z_]+)#', $linkUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                @endphp
                <a class="top-link floating-link" data-category-key="{{ $categoryKeyFromUrl }}" href="{{ $linkUrl }}">{{ ($link['emoji'] ?? '📌') . ' ' . ($link['title'] ?? 'Category') }}<span>{{ $link['subtitle'] ?? '' }}</span></a>
            @endforeach
        </section>
    </aside>

    <main class="page" data-api-base="{{ $apiBase }}">
        <header class="header-bar" aria-label="Customer account actions">
            <div>
                <p class="header-brand">Workation</p>
                <p class="header-subline">Discover stays, services, and transfers across the Maldives.</p>
            </div>
            <div class="customer-auth">
                @if ($customerLoggedIn)
                    <span class="auth-welcome">Hi, {{ $customerName }}</span>
                    <a class="auth-link" href="/customer">Open Customer Portal</a>
                    <form method="POST" action="/portal/customer/logout" style="margin:0;">
                        @csrf
                        <button class="auth-btn" type="submit">Logout</button>
                    </form>
                @else
                    <a class="auth-link" href="/portal/customer/login">Customer Login</a>
                    <a class="auth-link primary" href="/portal/customer/register">Customer Registration</a>
                @endif
            </div>
        </header>

        <div class="search-section-full-width" aria-label="Smart category search">
            <p class="search-eyebrow">Find Anything Faster</p>
            <h1 class="search-title">Select category and search with category-specific filters.</h1>
            <form id="homeCatalogSearchForm" class="search-form" action="/catalog/accommodation" method="get">
                <select id="categorySelect" name="category" aria-label="Select category">
                    @foreach ($homeTopCategoryLinks as $link)
                        @php
                            $linkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                            $categoryKeyFromUrl = preg_match('#/catalog/([a-z_]+)#', $linkUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                        @endphp
                        <option value="{{ $categoryKeyFromUrl }}">{{ $link['title'] ?? 'Category' }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" placeholder="Atoll, island, property, or service name" aria-label="Search query">

                <div id="accommodationFields" class="search-dynamic-fields is-active" data-fields-for="accommodation" aria-hidden="false">
                    <div class="field"><label for="checkin">Check-in Date</label><input id="checkin" name="checkin" type="date"></div>
                    <div class="field"><label for="checkout">Check-out Date</label><input id="checkout" name="checkout" type="date"></div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="2"></div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="0"></div>
                    <div class="field"><label for="rooms">Rooms</label><input id="rooms" name="rooms" type="number" min="1" value="1"></div>
                </div>

                <div id="transportFields" class="search-dynamic-fields" data-fields-for="transport" hidden aria-hidden="true">
                    <div class="field"><label for="transportMode">Transport Mode</label><select id="transportMode" name="transport_mode"><option value="marine">Marine Transport</option><option value="land">Land Transport</option></select></div>
                    <div class="field" id="transportTripTypeField"><label for="transportTripType">Trip Type</label><select id="transportTripType" name="trip_type"><option value="one_way">One Way</option><option value="round_trip">Round Trip</option></select></div>
                    <div class="field"><label for="transportFrom">From (Atoll/Island)</label><input id="transportFrom" name="from" type="text" placeholder="Atoll or island"></div>
                    <div class="field"><label for="transportTo">To (Atoll/Island)</label><input id="transportTo" name="to" type="text" placeholder="Atoll or island"></div>
                    <div class="field" id="transportDepartureDateField"><label for="travelDate">Departure Date</label><input id="travelDate" name="travel_date" type="date"></div>
                    <div class="field" id="transportReturnDateField"><label for="returnDate">Return Date</label><input id="returnDate" name="return_date" type="date"></div>
                    <div class="field"><label for="transportAdults">Adults / Pax</label><input id="transportAdults" name="adults" type="number" min="1" value="2"></div>
                    <div class="field"><label for="transportChildren">Children</label><input id="transportChildren" name="children" type="number" min="0" value="0"></div>
                    <div class="field" id="transportVehicleTypeField"><label for="vehicleType">Vehicle Type</label><input id="vehicleType" name="vehicle_type" type="text" placeholder="Car, Van, Bike"></div>
                </div>

                <div id="serviceFields" class="search-dynamic-fields" data-fields-for="service" hidden aria-hidden="true">
                    <div class="field">
                        <label for="serviceAtoll">Atoll</label>
                        <select id="serviceAtoll" name="atoll">
                            <option value="">All Atolls</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="serviceIsland">Island</label>
                        <select id="serviceIsland" name="island">
                            <option value="">All Islands</option>
                        </select>
                    </div>
                    <div class="field"><label for="minPrice">Min Price</label><input id="minPrice" name="min_price" type="number" min="0" placeholder="0"></div>
                    <div class="field"><label for="maxPrice">Max Price</label><input id="maxPrice" name="max_price" type="number" min="0" placeholder="5000"></div>
                </div>

                <div class="search-submit-row">
                    <button type="submit">Search Now</button>
                </div>
            </form>
        </div>

        <section class="promo-banner" aria-label="Offers and promotions">
            <strong>{{ $homePromoBanner['message'] ?? 'Promotions coming soon.' }}</strong>
            <a href="{{ $homePromoBanner['url'] ?? '/catalog/accommodation' }}">{{ $homePromoBanner['cta'] ?? 'View Promotions' }}</a>
        </section>

        <section class="section" aria-label="Browse by category, property, or service">
            <div class="section-head">
                <h2 class="section-title">Browse by Category / Property / Service</h2>
                <p class="section-sub">Quick entry points for what guests usually need first.</p>
            </div>
            <div class="browse-grid">
                @foreach ($homeBrowseCards as $card)
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            @if (!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';" alt="{{ $card['title'] ?? 'Category' }} thumbnail" loading="lazy">
                            @endif
                        </div>
                        <div class="item-card-body">
                            <strong>{{ $card['title'] ?? 'Category' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Explore listings in this category.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Trending destinations islands cities atolls">
            <div class="section-head">
                <h2 class="section-title">Trending Destinations: Islands, Cities, and Atolls</h2>
                <p class="section-sub">High-interest places guests are checking now.</p>
            </div>
            <div class="chip-row" aria-label="Trending filters">
                @foreach ($homeTrendingChips as $chip)
                    <span class="chip">{{ $chip }}</span>
                @endforeach
            </div>
            <div class="trending-grid">
                @foreach ($homeTrendingCards as $card)
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            @if (!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';" alt="{{ $card['title'] ?? 'Trending Destination' }} thumbnail" loading="lazy">
                            @endif
                        </div>
                        <div class="item-card-body">
                            <strong>{{ $card['title'] ?? 'Trending Destination' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Trending destination currently popular with guests.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Deals for the weekend">
            <div class="section-head">
                <h2 class="section-title">Deals for the Weekend</h2>
                <p class="section-sub">Easy picks for short breaks and quick getaways.</p>
            </div>
            <div class="deal-grid">
                @foreach ($homeWeekendDealCards as $card)
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            @if (!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';" alt="{{ $card['title'] ?? 'Weekend Deal' }} thumbnail" loading="lazy">
                            @endif
                        </div>
                        <div class="item-card-body">
                            @if (!empty($card['meta']))
                                <span class="item-card-meta">{{ $card['meta'] }}</span>
                            @endif
                            <strong>{{ $card['title'] ?? 'Weekend Deal' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Recommended weekend offer for quick getaways.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Places guests loved most">
            <div class="section-head">
                <h2 class="section-title">Places Guests Loved Most</h2>
                <p class="section-sub">Based on repeat views and top user interest.</p>
            </div>
            <div class="loved-grid">
                @foreach ($homeLovedCards as $card)
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            @if (!empty($card['image_url']))
                                <img src="{{ $card['image_url'] }}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';" alt="{{ $card['title'] ?? 'Loved Place' }} thumbnail" loading="lazy">
                            @endif
                        </div>
                        <div class="item-card-body">
                            @if (!empty($card['meta']))
                                <span class="item-card-meta">{{ $card['meta'] }}</span>
                            @endif
                            <strong>{{ $card['title'] ?? 'Loved Place' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Highly rated by guests and repeat visitors.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="home-footer-skin">
            @include('partials.global-site-footer')
        </div>
    </main>

    <script>
        (function () {
            const form = document.getElementById('homeCatalogSearchForm');
            const categorySelect = document.getElementById('categorySelect');
            const accommodationFields = document.getElementById('accommodationFields');
            const transportFields = document.getElementById('transportFields');
            const serviceFields = document.getElementById('serviceFields');
            const transportMode = document.getElementById('transportMode');
            const transportTripType = document.getElementById('transportTripType');
            const transportTripTypeField = document.getElementById('transportTripTypeField');
            const transportReturnDateField = document.getElementById('transportReturnDateField');
            const transportVehicleTypeField = document.getElementById('transportVehicleTypeField');
            const transportReturnDateInput = document.getElementById('returnDate');
            const transportVehicleTypeInput = document.getElementById('vehicleType');
            const serviceAtollSelect = document.getElementById('serviceAtoll');
            const serviceIslandSelect = document.getElementById('serviceIsland');
            const apiBase = String(document.querySelector('.page')?.getAttribute('data-api-base') || '').replace(/\/$/, '');
            const topCategoryLinks = Array.from(document.querySelectorAll('.top-link[data-category-key]'));

            if (!form || !categorySelect || !accommodationFields || !transportFields || !serviceFields) {
                return;
            }

            function setFieldActive(fieldWrapper, isActive) {
                if (!fieldWrapper) {
                    return;
                }

                fieldWrapper.hidden = !isActive;
                fieldWrapper.querySelectorAll('input, select, textarea').forEach(function (control) {
                    control.disabled = !isActive;
                });
            }

            function fillSelect(select, options, emptyLabel) {
                if (!select) {
                    return;
                }

                const currentValue = String(select.value || '');
                select.innerHTML = '';

                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = emptyLabel;
                select.appendChild(emptyOption);

                options.forEach(function (entry) {
                    const option = document.createElement('option');
                    option.value = String(entry.value ?? '');
                    option.textContent = String(entry.label ?? entry.value ?? '');
                    select.appendChild(option);
                });

                if (currentValue !== '') {
                    const hasValue = Array.from(select.options).some(function (option) {
                        return String(option.value) === currentValue;
                    });

                    if (hasValue) {
                        select.value = currentValue;
                    }
                }
            }

            async function loadAtolls() {
                if (!serviceAtollSelect || !apiBase) {
                    return;
                }

                try {
                    const response = await fetch(apiBase + '/atolls', { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) {
                        return;
                    }

                    const rows = await response.json();
                    const options = Array.isArray(rows)
                        ? rows
                            .map(function (row) {
                                return {
                                    value: String(row.id ?? ''),
                                    label: String(row.name ?? '')
                                };
                            })
                            .filter(function (row) {
                                return row.value !== '' && row.label !== '';
                            })
                        : [];

                    fillSelect(serviceAtollSelect, options, 'All Atolls');
                } catch (error) {
                    // Keep default empty options when API is unavailable.
                }
            }

            async function loadIslandsByAtoll(atollId) {
                if (!serviceIslandSelect || !apiBase) {
                    return;
                }

                if (!atollId) {
                    fillSelect(serviceIslandSelect, [], 'All Islands');
                    return;
                }

                try {
                    const response = await fetch(apiBase + '/islands?atollId=' + encodeURIComponent(String(atollId)), {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) {
                        fillSelect(serviceIslandSelect, [], 'All Islands');
                        return;
                    }

                    const rows = await response.json();
                    const options = Array.isArray(rows)
                        ? rows
                            .map(function (row) {
                                return {
                                    value: String(row.name ?? ''),
                                    label: String(row.name ?? '')
                                };
                            })
                            .filter(function (row) {
                                return row.value !== '';
                            })
                        : [];

                    fillSelect(serviceIslandSelect, options, 'All Islands');
                } catch (error) {
                    fillSelect(serviceIslandSelect, [], 'All Islands');
                }
            }

            function toggleTransportModeFields() {
                if (!transportMode || !transportTripType) {
                    return;
                }

                const mode = String(transportMode.value || 'marine').toLowerCase();
                const tripType = String(transportTripType.value || 'one_way').toLowerCase();
                const isLand = mode === 'land';
                const isMarineRoundTrip = mode === 'marine' && tripType === 'round_trip';

                setFieldActive(transportTripTypeField, !isLand);
                setFieldActive(transportVehicleTypeField, isLand);
                setFieldActive(transportReturnDateField, isLand || isMarineRoundTrip);

                if (!isLand && !isMarineRoundTrip && transportReturnDateInput) {
                    transportReturnDateInput.value = '';
                }

                if (!isLand && transportVehicleTypeInput) {
                    transportVehicleTypeInput.value = '';
                }
            }

            function resolveGroup(category) {
                if (category === 'accommodation') {
                    return 'accommodation';
                }

                if (category === 'transport') {
                    return 'transport';
                }

                return 'service';
            }

            function toggleFields() {
                const category = String(categorySelect.value || 'accommodation').toLowerCase();
                const group = resolveGroup(category);
                const groups = [
                    { key: 'accommodation', el: accommodationFields },
                    { key: 'transport', el: transportFields },
                    { key: 'service', el: serviceFields }
                ];

                form.setAttribute('action', '/catalog/' + category);

                groups.forEach(function (entry) {
                    const isActive = entry.key === group;
                    entry.el.hidden = !isActive;
                    entry.el.classList.toggle('is-active', isActive);
                    entry.el.setAttribute('aria-hidden', isActive ? 'false' : 'true');

                    entry.el.querySelectorAll('input, select, textarea').forEach(function (control) {
                        control.disabled = !isActive;
                    });
                });

                if (group === 'transport') {
                    toggleTransportModeFields();
                }
            }

            if (transportMode) {
                transportMode.addEventListener('change', toggleTransportModeFields);
            }

            if (transportTripType) {
                transportTripType.addEventListener('change', toggleTransportModeFields);
            }

            if (serviceAtollSelect) {
                serviceAtollSelect.addEventListener('change', function () {
                    loadIslandsByAtoll(serviceAtollSelect.value || '');
                });
            }

            if (topCategoryLinks.length > 0) {
                topCategoryLinks.forEach(function (link) {
                    // Skip floating sidebar links - let them navigate naturally
                    if (link.classList.contains('floating-link')) {
                        return;
                    }

                    link.addEventListener('click', function (event) {
                        const categoryKey = String(link.getAttribute('data-category-key') || '').toLowerCase();
                        if (!categoryKey || !categorySelect.querySelector('option[value="' + categoryKey + '"]')) {
                            return;
                        }

                        event.preventDefault();
                        categorySelect.value = categoryKey;
                        toggleFields();
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });
            }

            categorySelect.addEventListener('change', toggleFields);
            loadAtolls();
            loadIslandsByAtoll('');
            toggleFields();
        })();
    </script>
</body>
</html>