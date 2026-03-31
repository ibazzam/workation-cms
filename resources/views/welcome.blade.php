<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation Maldives</title>
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
            width: min(1180px, calc(100% - 28px));
            margin: 14px auto 30px;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: color-mix(in srgb, #ffffff 88%, #eef8fc 12%);
            margin-bottom: 10px;
        }

        .header-brand {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 700;
            color: #20455f;
            letter-spacing: 0.02em;
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
            position: sticky;
            top: 72px;
            z-index: 25;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            padding: 10px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, #ffffff 84%, #eef8fc 16%);
            backdrop-filter: blur(6px);
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
            text-align: center;
            display: grid;
            gap: 2px;
            min-height: 62px;
            align-content: center;
        }

        .top-link span {
            color: #5e7388;
            font-size: 0.73rem;
            font-weight: 500;
        }

        .search-section {
            margin-top: 12px;
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(132deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: clamp(16px, 3vw, 24px);
            box-shadow: 0 20px 38px rgba(14, 65, 85, 0.22);
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
            grid-template-columns: 1fr 1.3fr auto;
            gap: 8px;
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

        .search-form button {
            border: 1px solid #f6d19a;
            background: linear-gradient(135deg, #ffc76f 0%, var(--accent) 100%);
            color: #57350b;
            border-radius: 10px;
            padding: 0 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            min-width: 122px;
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
            background: #fbfdff;
            padding: 11px;
            text-decoration: none;
            color: #1b3f58;
            display: grid;
            gap: 5px;
        }

        .item-card strong {
            font-size: 0.92rem;
        }

        .item-card span {
            color: #5a6f82;
            font-size: 0.8rem;
            line-height: 1.43;
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


        @media (max-width: 1040px) {
            .top-links {
                grid-template-columns: repeat(3, minmax(0, 1fr));
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

            .search-form button {
                grid-column: 1 / -1;
                min-height: 42px;
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .header-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
            }

            .top-links {
                grid-template-columns: 1fr 1fr;
                position: static;
            }

            .top-link {
                min-height: 56px;
                font-size: 0.76rem;
            }

            .search-form {
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
    @endphp

    <main class="page" data-api-base="{{ $apiBase }}">
        <header class="header-bar" aria-label="Customer account actions">
            <p class="header-brand">Workation Maldives</p>
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

        <section class="top-links" aria-label="Top categories">
            <a class="top-link" href="/customer?category=Accommodation">🏨 Stay Options<span>Hotels, villas, guesthouses</span></a>
            <a class="top-link" href="/customer?category=Transport">🚤 Transport<span>Speedboat, ferry, airport pickup</span></a>
            <a class="top-link" href="/customer?category=Experiences">🌊 Experiences<span>Diving, snorkel, island tours</span></a>
            <a class="top-link" href="/customer?category=Workspace">💻 Work-Friendly<span>Wi-Fi, desks, quiet corners</span></a>
            <a class="top-link" href="/customer?category=Family">👨‍👩‍👧 Family Picks<span>Kid-friendly places and services</span></a>
            <a class="top-link" href="/customer?category=Deals">🔥 Deals Zone<span>Promotions and last-minute value</span></a>
        </section>

        <section class="search-section" aria-label="Smart category search">
            <p class="search-eyebrow">Find Anything Faster</p>
            <h1 class="search-title">Search all categories with one flexible search bar.</h1>
            <form class="search-form" action="/customer" method="get">
                <select name="search_scope" aria-label="Search scope">
                    <option value="all">All Categories</option>
                    <option value="property">Property / Stays</option>
                    <option value="service">Services / Experiences</option>
                    <option value="destination">Destination / Island / City / Atoll</option>
                </select>
                <input type="search" name="q" placeholder="Try: Maafushi villa, airport transfer, diving in Baa Atoll" aria-label="Search query">
                <button type="submit">Search Now</button>
            </form>
            <div class="search-options" aria-label="Quick search options">
                <a href="/customer?search_scope=all&q=beachfront">Beachfront</a>
                <a href="/customer?search_scope=destination&q=Male">Male City</a>
                <a href="/customer?search_scope=destination&q=Baa+Atoll">Baa Atoll</a>
                <a href="/customer?search_scope=service&q=snorkeling">Snorkeling</a>
                <a href="/customer?search_scope=property&q=family+suite">Family Suite</a>
            </div>
        </section>

        <section class="promo-banner" aria-label="Offers and promotions">
            <strong>🎉 Offers & Promotions: Save up to 25% on selected stays and transfer bundles this week.</strong>
            <a href="/customer?category=Deals">View Promotions</a>
        </section>

        <section class="section" aria-label="Browse by category, property, or service">
            <div class="section-head">
                <h2 class="section-title">Browse by Category / Property / Service</h2>
                <p class="section-sub">Quick entry points for what guests usually need first.</p>
            </div>
            <div class="browse-grid">
                <a class="item-card" href="/customer?category=Accommodation">
                    <strong>Beach Resorts</strong>
                    <span>Premium rooms, ocean views, full amenities.</span>
                </a>
                <a class="item-card" href="/customer?category=Accommodation">
                    <strong>Guesthouses</strong>
                    <span>Local island stays with practical pricing.</span>
                </a>
                <a class="item-card" href="/customer?category=Transport">
                    <strong>Airport Transfer</strong>
                    <span>Coordinated speedboat and ferry options.</span>
                </a>
                <a class="item-card" href="/customer?category=Experiences">
                    <strong>Water Activities</strong>
                    <span>Dolphin cruise, snorkeling, and dives.</span>
                </a>
            </div>
        </section>

        <section class="section" aria-label="Trending destinations islands cities atolls">
            <div class="section-head">
                <h2 class="section-title">Trending Destinations: Islands, Cities, and Atolls</h2>
                <p class="section-sub">High-interest places guests are checking now.</p>
            </div>
            <div class="chip-row" aria-label="Trending filters">
                <span class="chip">Top Islands</span>
                <span class="chip">Top Cities</span>
                <span class="chip">Top Atolls</span>
                <span class="chip">Newly Rising</span>
            </div>
            <div class="trending-grid">
                <a class="item-card" href="/customer?search_scope=destination&q=Maafushi">
                    <strong>Maafushi Island</strong>
                    <span>Most searched for affordable island escapes.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Male">
                    <strong>Male City</strong>
                    <span>Convenient urban stays and transfer access.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Baa+Atoll">
                    <strong>Baa Atoll</strong>
                    <span>Nature-rich stays and iconic snorkeling spots.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Ari+Atoll">
                    <strong>Ari Atoll</strong>
                    <span>Popular for diving and premium island resorts.</span>
                </a>
            </div>
        </section>

        <section class="section" aria-label="Deals for the weekend">
            <div class="section-head">
                <h2 class="section-title">Deals for the Weekend</h2>
                <p class="section-sub">Easy picks for short breaks and quick getaways.</p>
            </div>
            <div class="deal-grid">
                <a class="item-card" href="/customer?category=Deals&q=weekend+beach">
                    <strong>2-Night Beach Stay</strong>
                    <span>Weekend promo with breakfast included.</span>
                </a>
                <a class="item-card" href="/customer?category=Deals&q=transfer+bundle">
                    <strong>Stay + Transfer Bundle</strong>
                    <span>Save when you combine stay and transport.</span>
                </a>
                <a class="item-card" href="/customer?category=Deals&q=family+weekend">
                    <strong>Family Weekend Pack</strong>
                    <span>Room upgrade and activity credits included.</span>
                </a>
                <a class="item-card" href="/customer?category=Deals&q=romantic+escape">
                    <strong>Couple Escape Offer</strong>
                    <span>Curated stay options for a quick retreat.</span>
                </a>
            </div>
        </section>

        <section class="section" aria-label="Places guests loved most">
            <div class="section-head">
                <h2 class="section-title">Places Guests Loved Most</h2>
                <p class="section-sub">Based on repeat views and top user interest.</p>
            </div>
            <div class="loved-grid">
                <a class="item-card" href="/customer?search_scope=destination&q=Hulhumale">
                    <strong>Hulhumale Seafront</strong>
                    <span>Consistently high ratings for convenience.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Thulusdhoo">
                    <strong>Thulusdhoo Island</strong>
                    <span>Guest favorite for surf culture and charm.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Ukulhas">
                    <strong>Ukulhas Island</strong>
                    <span>Loved for clean beaches and relaxed stays.</span>
                </a>
                <a class="item-card" href="/customer?search_scope=destination&q=Dhigurah">
                    <strong>Dhigurah Island</strong>
                    <span>Strong demand for reef and marine experiences.</span>
                </a>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>