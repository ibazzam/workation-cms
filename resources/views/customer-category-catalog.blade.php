<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoryMeta['label'] }} Catalogue | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --brand: #0f6179;
            --accent: #f3a337;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .page {
            width: calc(100% - 294px);
            margin: 14px 14px 30px 270px;
        }

        .floating-sidebar {
            position: fixed;
            left: 12px;
            top: 12px;
            width: 250px;
            height: calc(100vh - 24px);
            overflow-y: auto;
            z-index: 900;
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

        .top-links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
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
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-height: 56px;
            justify-content: center;
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .top-link:hover {
            border-color: #8db5cf;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(34, 86, 120, 0.16);
        }

        .top-link.is-active {
            border-color: #6ca6c3;
            background: #ebf6ff;
            box-shadow: 0 4px 10px rgba(22, 70, 102, 0.12);
        }

        .top-link span {
            color: #5e7388;
            font-size: 0.73rem;
            font-weight: 500;
        }

            .top-link-head {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .top-link-head i {
                font-size: 0.9rem;
                color: #0f6179;
                width: 16px;
                text-align: center;
                flex: 0 0 16px;
            }

            .mobile-category-nav {
                display: none;
                margin-bottom: 10px;
                border: 1px solid #c9ddeb;
                border-radius: 14px;
                background: linear-gradient(160deg, #f8fcff 0%, #eef6fb 100%);
                padding: 9px;
            }

            .mobile-category-toggle {
                list-style: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.09em;
                color: #4e6d83;
                font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            }

            .mobile-category-toggle::-webkit-details-marker {
                display: none;
            }

            .mobile-category-toggle::after {
                content: '+';
                font-size: 1rem;
                line-height: 1;
                color: #0f6179;
            }

            .mobile-category-nav[open] .mobile-category-toggle::after {
                content: '-';
            }

            .mobile-category-row {
                display: none;
                flex-wrap: nowrap;
                gap: 7px;
                overflow-x: auto;
                overflow-y: hidden;
                padding-top: 8px;
                padding-bottom: 2px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }

            .mobile-category-nav[open] .mobile-category-row {
                display: flex;
            }

            .mobile-category-link {
                text-decoration: none;
                border: 1px solid #d4e3ee;
                border-radius: 999px;
                background: #f8fcff;
                color: #19405b;
                padding: 7px 10px;
                font-size: 0.75rem;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                flex: 0 0 auto;
            }

        .hero {
            border: 1px solid #cbe0ea;
            border-radius: 16px;
            background: linear-gradient(132deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: 16px;
        }

        .hero p {
            margin: 0;
        }

        .hero h1 {
            margin: 6px 0;
            font-size: clamp(1.2rem, 2.2vw, 2rem);
        }

        .hero-links {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hero-links a {
            text-decoration: none;
            border: 1px solid rgba(214, 244, 248, 0.5);
            background: rgba(4, 64, 83, 0.25);
            color: #eafcff;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .search-box {
            margin-top: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .field label {
            display: block;
            margin-bottom: 4px;
            font-size: 0.76rem;
            color: #4b6378;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .field input,
        .field select {
            width: 100%;
            border: 1px solid #c8d8e5;
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            font-size: 0.9rem;
            height: 42px;
            line-height: 1.4;
            display: flex;
            align-items: center;
        }
        
        .field select {
            padding: 8px 12px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .section-title {
            margin: 14px 0 0;
            font-size: 1rem;
        }

        .catalog-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            overflow: hidden;
        }

        .card-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #edf4fb;
            display: block;
        }

        .card-body {
            padding: 10px;
            display: grid;
            gap: 6px;
        }

        .card h3 {
            margin: 0;
            font-size: 0.93rem;
        }

        .meta {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .empty {
            margin-top: 10px;
            border: 1px dashed #cddbe8;
            background: #f7fbff;
            color: #3f5a72;
            border-radius: 12px;
            padding: 14px;
            font-size: 0.88rem;
        }

        .island-context-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            border: 1px solid #b6daea;
            border-radius: 10px;
            background: #eaf6fb;
            color: #1a4b62;
            padding: 9px 12px;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .island-context-note i {
            color: #0f6179;
            margin-top: 2px;
            flex: 0 0 14px;
            text-align: center;
        }

        @media (max-width: 1040px) {
            .page {
                width: calc(100% - 28px);
                margin: 14px auto 30px;
            }

            .floating-sidebar {
                position: static;
                width: calc(100% - 28px);
                height: auto;
                margin: 14px auto 12px;
            }

            .top-links {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .grid,
            .catalog-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .floating-sidebar {
                display: none;
            }

            .mobile-category-nav {
                display: block;
            }

            .mobile-category-row {
                flex-direction: column;
                gap: 8px;
                overflow-x: visible;
                overflow-y: visible;
            }

            .mobile-category-link {
                width: 100%;
                justify-content: flex-start;
            }

            .top-links {
                grid-template-columns: 1fr;
            }

            .grid,
            .catalog-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $filters = $filters ?? [];
        $categoryKey = $categoryKey ?? 'accommodation';
        $categoryMeta = $categoryMeta ?? ['label' => 'Catalogue', 'subtitle' => ''];
        $catalogCategoryLinks = collect([
            ['key' => 'accommodation',    'icon' => 'fa-solid fa-hotel',          'title' => 'Accommodation',   'subtitle' => 'Hotels, resorts, villas'],
            ['key' => 'marine-transport',  'icon' => 'fa-solid fa-water',          'title' => 'Marine Transport','subtitle' => 'Speedboats & water transfers'],
            ['key' => 'land-transport',    'icon' => 'fa-solid fa-van-shuttle',    'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers'],
            ['key' => 'transport',        'icon' => 'fa-solid fa-ship',           'title' => 'Transport',       'subtitle' => 'Marine and land transfers'],
            ['key' => 'excursion',        'icon' => 'fa-solid fa-compass',       'title' => 'Excursion',       'subtitle' => 'Tours and activities'],
            ['key' => 'remote_workspace', 'icon' => 'fa-solid fa-laptop',         'title' => 'Remote Workspace','subtitle' => 'Work-friendly spaces'],
            ['key' => 'conference_room',  'icon' => 'fa-solid fa-object-group',   'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces'],
            ['key' => 'resort_day_visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit','subtitle' => 'Day-use resort offers'],
            ['key' => 'restaurant',       'icon' => 'fa-solid fa-utensils',       'title' => 'Restaurant',      'subtitle' => 'Dining experiences'],
            ['key' => 'vehicle_rental',   'icon' => 'fa-solid fa-car',            'title' => 'Vehicle Rental',  'subtitle' => 'Cars and local rentals'],
        ]);
        $catalogProperties = $catalogProperties ?? collect();
        $catalogPropertyMediaByProperty = $catalogPropertyMediaByProperty ?? collect();
        $atollOptions = $atollOptions ?? collect();
        $islandOptions = $islandOptions ?? collect();
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

    <aside class="floating-sidebar" aria-label="Category sidebar">
        <p class="sidebar-title">Browse Categories</p>
        <section class="top-links" aria-label="Top categories">
            @foreach ($catalogCategoryLinks as $item)
                <a class="top-link{{ $categoryKey === ($item['key'] ?? '') ? ' is-active' : '' }}" href="{{ '/catalog/' . ($item['key'] ?? 'accommodation') }}">
                    <span class="top-link-head"><i class="{{ $item['icon'] ?? 'fa-solid fa-location-dot' }}"></i>{{ $item['title'] ?? 'Category' }}</span>
                    <span>{{ $item['subtitle'] ?? '' }}</span>
                </a>
            @endforeach
        </section>
    </aside>

    <main class="page" data-api-base="{{ $apiBase }}">
        <details class="mobile-category-nav" aria-label="Mobile category quick links">
            <summary class="mobile-category-toggle">Browse Categories</summary>
            <div class="mobile-category-row">
                @foreach ($catalogCategoryLinks as $item)
                    <a class="mobile-category-link" href="{{ '/catalog/' . ($item['key'] ?? 'accommodation') }}"><i class="{{ $item['icon'] ?? 'fa-solid fa-location-dot' }}" aria-hidden="true"></i><span>{{ $item['title'] ?? 'Category' }}</span></a>
                @endforeach
            </div>
        </details>

        <section class="hero">
            <p>Category Portfolio</p>
            <h1>{{ $categoryMeta['label'] }} Catalogue</h1>
            <p>{{ $categoryMeta['subtitle'] }}</p>
            <div class="hero-links">
                <a href="/">Back Home</a>
                <a href="/customer">Customer Portal</a>
                <a href="/catalog/accommodation">Accommodation</a>
                <a href="/catalog/marine-transport">Marine Transfer</a>
                <a href="/catalog/land-transport">Land Transfer</a>
                <a href="/catalog/excursion">Excursion</a>
                <a href="/catalog/remote_workspace">Remote Workspace</a>
                <a href="/catalog/conference_room">Conference Rooms</a>
                <a href="/catalog/resort_day_visit">Resort Day Visit</a>
                <a href="/catalog/restaurant">Restaurant</a>
                <a href="/catalog/vehicle_rental">Vehicle Rental</a>
            </div>
        </section>

        <form class="search-box" method="GET" action="/catalog/{{ $categoryKey }}">
            <div class="grid">
                <div class="field">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Atoll, island, place, or property name">
                </div>
                <div class="field">
                    <label for="atoll">Atoll</label>
                    <select id="atoll" name="atoll">
                        <option value="">All Atolls</option>
                        @foreach ($atollOptions as $atoll)
                            <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="island">Island / City</label>
                    <select id="island" name="island">
                        <option value="">All Islands/Cities</option>
                        @foreach ($islandOptions as $island)
                            <option value="{{ $island }}" {{ ($filters['island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="sort">Sort</label>
                    <select id="sort" name="sort">
                        <option value="recommended" {{ ($filters['sort'] ?? '') === 'recommended' ? 'selected' : '' }}>Recommended</option>
                        <option value="most_wanted" {{ ($filters['sort'] ?? '') === 'most_wanted' ? 'selected' : '' }}>Most Wanted</option>
                        <option value="most_booked" {{ ($filters['sort'] ?? '') === 'most_booked' ? 'selected' : '' }}>Most Booked</option>
                        <option value="highest_reviews" {{ ($filters['sort'] ?? '') === 'highest_reviews' ? 'selected' : '' }}>Highest Reviews</option>
                        <option value="price_low_high" {{ ($filters['sort'] ?? '') === 'price_low_high' ? 'selected' : '' }}>Price Low to High</option>
                        <option value="price_high_low" {{ ($filters['sort'] ?? '') === 'price_high_low' ? 'selected' : '' }}>Price High to Low</option>
                    </select>
                </div>
                <div class="field">
                    <label for="min_price">Min Price</label>
                    <input id="min_price" name="min_price" type="number" min="0" value="{{ $filters['min_price'] ?? 0 }}">
                </div>
                <div class="field">
                    <label for="max_price">Max Price</label>
                    <input id="max_price" name="max_price" type="number" min="0" value="{{ $filters['max_price'] ?? 0 }}">
                </div>
            </div>

            @if ($categoryKey === 'accommodation')
                <div class="grid">
                    <div class="field"><label for="checkin">Check-in Date</label><input id="checkin" name="checkin" type="date" value="{{ $filters['checkin'] ?? '' }}"></div>
                    <div class="field"><label for="checkout">Check-out Date</label><input id="checkout" name="checkout" type="date" value="{{ $filters['checkout'] ?? '' }}"></div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                    <div class="field"><label for="rooms">Rooms</label><input id="rooms" name="rooms" type="number" min="1" value="{{ $filters['rooms'] ?? 1 }}"></div>
                </div>
            @elseif ($categoryKey === 'marine-transport' || $categoryKey === 'land-transport')
                <div class="grid">
                    <div class="field"><label for="origin_point">From (Island/Location)</label><input id="origin_point" name="origin_point" type="text" value="{{ $filters['origin_point'] ?? '' }}"></div>
                    <div class="field"><label for="destination_point">To (Island/Location)</label><input id="destination_point" name="destination_point" type="text" value="{{ $filters['destination_point'] ?? '' }}"></div>
                    <div class="field"><label for="travel_date">Travel Date</label><input id="travel_date" name="travel_date" type="date" value="{{ $filters['travel_date'] ?? '' }}"></div>
                    <div class="field"><label for="return_date">Return Date</label><input id="return_date" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}"></div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                </div>
            @elseif ($categoryKey === 'restaurant')
                <div class="island-context-note" style="margin-bottom:8px;">
                    <i class="fa-solid fa-water" aria-hidden="true"></i>
                    <span>Restaurants in the Maldives are <strong>island-specific</strong>. Select the island where you are currently staying or planning to visit to see what’s available at that location.</span>
                </div>
                <div class="grid">
                    <div class="field">
                        <label for="current_island">Your Current Island / Stay Location</label>
                        <select id="current_island" name="current_island">
                            <option value="">All Islands</option>
                            @foreach ($islandOptions as $island)
                                <option value="{{ $island }}" {{ ($filters['current_island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="atoll_restaurant">Atoll (Optional)</label>
                        <select id="atoll_restaurant" name="atoll">
                            <option value="">All Atolls</option>
                            @foreach ($atollOptions as $atoll)
                                <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="reservation_datetime">Reservation Date &amp; Time</label>
                        <input id="reservation_datetime" name="reservation_datetime" type="datetime-local" value="{{ $filters['reservation_datetime'] ?? '' }}">
                    </div>
                    <div class="field">
                        <label for="party_size">Party Size</label>
                        <input id="party_size" name="party_size" type="number" min="1" value="{{ $filters['party_size'] ?? 2 }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'excursion')
                <div class="grid">
                    <div class="field">
                        <label for="activity_type">Activity Type</label>
                        <select id="activity_type" name="activity_type">
                            <option value="">All Activities</option>
                            <option value="water_sports" {{ ($filters['activity_type'] ?? '') === 'water_sports' ? 'selected' : '' }}>Water Sports</option>
                            <option value="cultural" {{ ($filters['activity_type'] ?? '') === 'cultural' ? 'selected' : '' }}>Cultural</option>
                            <option value="adventure" {{ ($filters['activity_type'] ?? '') === 'adventure' ? 'selected' : '' }}>Adventure</option>
                            <option value="relaxation" {{ ($filters['activity_type'] ?? '') === 'relaxation' ? 'selected' : '' }}>Relaxation</option>
                            <option value="wildlife" {{ ($filters['activity_type'] ?? '') === 'wildlife' ? 'selected' : '' }}>Wildlife</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="difficulty">Difficulty Level</label>
                        <select id="difficulty" name="difficulty">
                            <option value="">All Levels</option>
                            <option value="easy" {{ ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="moderate" {{ ($filters['difficulty'] ?? '') === 'moderate' ? 'selected' : '' }}>Moderate</option>
                            <option value="challenging" {{ ($filters['difficulty'] ?? '') === 'challenging' ? 'selected' : '' }}>Challenging</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="excursion_date">Excursion Date</label>
                        <input id="excursion_date" name="excursion_date" type="date" value="{{ $filters['excursion_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'remote_workspace')
                <div class="grid">
                    <div class="field">
                        <label for="workspace_type_filter">Workspace Type</label>
                        <select id="workspace_type_filter" name="workspace_type_filter">
                            <option value="">All Types</option>
                            <option value="coworking" {{ ($filters['workspace_type_filter'] ?? '') === 'coworking' ? 'selected' : '' }}>Co-working Space</option>
                            <option value="cafe" {{ ($filters['workspace_type_filter'] ?? '') === 'cafe' ? 'selected' : '' }}>Cafe / Coffee Shop</option>
                            <option value="library" {{ ($filters['workspace_type_filter'] ?? '') === 'library' ? 'selected' : '' }}>Library</option>
                            <option value="private" {{ ($filters['workspace_type_filter'] ?? '') === 'private' ? 'selected' : '' }}>Private Office</option>
                            <option value="resort" {{ ($filters['workspace_type_filter'] ?? '') === 'resort' ? 'selected' : '' }}>Resort Workspace</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="internet_speed">Internet Speed</label>
                        <select id="internet_speed" name="internet_speed">
                            <option value="">Any Speed</option>
                            <option value="high" {{ ($filters['internet_speed'] ?? '') === 'high' ? 'selected' : '' }}>High Speed (100+ Mbps)</option>
                            <option value="standard" {{ ($filters['internet_speed'] ?? '') === 'standard' ? 'selected' : '' }}>Standard (50+ Mbps)</option>
                            <option value="basic" {{ ($filters['internet_speed'] ?? '') === 'basic' ? 'selected' : '' }}>Basic (20+ Mbps)</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="workspace_start">Start Date</label>
                        <input id="workspace_start" name="workspace_start" type="date" value="{{ $filters['workspace_start'] ?? '' }}">
                    </div>
                    <div class="field">
                        <label for="workspace_end">End Date</label>
                        <input id="workspace_end" name="workspace_end" type="date" value="{{ $filters['workspace_end'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'conference_room')
                <div class="grid">
                    <div class="field">
                        <label for="conference_event_type">Event Type</label>
                        <select id="conference_event_type" name="conference_event_type">
                            <option value="">All Event Types</option>
                            <option value="meeting" {{ ($filters['conference_event_type'] ?? '') === 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="training" {{ ($filters['conference_event_type'] ?? '') === 'training' ? 'selected' : '' }}>Training</option>
                            <option value="seminar" {{ ($filters['conference_event_type'] ?? '') === 'seminar' ? 'selected' : '' }}>Seminar</option>
                            <option value="conference" {{ ($filters['conference_event_type'] ?? '') === 'conference' ? 'selected' : '' }}>Conference</option>
                            <option value="workshop" {{ ($filters['conference_event_type'] ?? '') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="conference_capacity">Minimum Capacity (Attendees)</label>
                        <input id="conference_capacity" name="conference_capacity" type="number" min="1" value="{{ $filters['conference_capacity'] ?? 0 }}">
                    </div>
                    <div class="field">
                        <label for="conference_date">Event Date</label>
                        <input id="conference_date" name="conference_date" type="date" value="{{ $filters['conference_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'resort_day_visit')
                <div class="grid">
                    <div class="field">
                        <label for="time_slot">Time Slot</label>
                        <select id="time_slot" name="time_slot">
                            <option value="">Any Time</option>
                            <option value="morning" {{ ($filters['time_slot'] ?? '') === 'morning' ? 'selected' : '' }}>Morning Half-day (6am-12pm)</option>
                            <option value="afternoon" {{ ($filters['time_slot'] ?? '') === 'afternoon' ? 'selected' : '' }}>Afternoon Half-day (12pm-6pm)</option>
                            <option value="evening" {{ ($filters['time_slot'] ?? '') === 'evening' ? 'selected' : '' }}>Evening (3pm-9pm)</option>
                            <option value="fullday" {{ ($filters['time_slot'] ?? '') === 'fullday' ? 'selected' : '' }}>Full Day</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="facility_type">Facility Type</label>
                        <select id="facility_type" name="facility_type">
                            <option value="">All Facilities</option>
                            <option value="beach" {{ ($filters['facility_type'] ?? '') === 'beach' ? 'selected' : '' }}>Beach Access</option>
                            <option value="pool" {{ ($filters['facility_type'] ?? '') === 'pool' ? 'selected' : '' }}>Swimming Pool</option>
                            <option value="spa" {{ ($filters['facility_type'] ?? '') === 'spa' ? 'selected' : '' }}>Spa & Wellness</option>
                            <option value="water_sports" {{ ($filters['facility_type'] ?? '') === 'water_sports' ? 'selected' : '' }}>Water Sports</option>
                            <option value="dining" {{ ($filters['facility_type'] ?? '') === 'dining' ? 'selected' : '' }}>Dining Experience</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="visit_date">Visit Date</label>
                        <input id="visit_date" name="visit_date" type="date" value="{{ $filters['visit_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'vehicle_rental')
                <div class="island-context-note" style="margin-bottom:8px;">
                    <i class="fa-solid fa-water" aria-hidden="true"></i>
                    <span>Vehicle and vessel hire in the Maldives is <strong>island-specific</strong>. Select your pickup island to find available cars, motorcycles, speedboats, and private vessel hire at that location.</span>
                </div>
                <div class="grid">
                    <div class="field">
                        <label for="pickup_island">Pickup Island</label>
                        <select id="pickup_island" name="pickup_island">
                            <option value="">All Islands</option>
                            @foreach ($islandOptions as $island)
                                <option value="{{ $island }}" {{ ($filters['pickup_island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="atoll_rental">Atoll (Optional)</label>
                        <select id="atoll_rental" name="atoll">
                            <option value="">All Atolls</option>
                            @foreach ($atollOptions as $atoll)
                                <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="vehicle_kind">Vehicle / Vessel Type</label>
                        <select id="vehicle_kind" name="vehicle_kind">
                            <option value="">All Types</option>
                            <option value="car" {{ ($filters['vehicle_kind'] ?? '') === 'car' ? 'selected' : '' }}>Car / 4x4</option>
                            <option value="motorcycle" {{ ($filters['vehicle_kind'] ?? '') === 'motorcycle' ? 'selected' : '' }}>Motorcycle / Scooter</option>
                            <option value="bicycle" {{ ($filters['vehicle_kind'] ?? '') === 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                            <option value="speedboat" {{ ($filters['vehicle_kind'] ?? '') === 'speedboat' ? 'selected' : '' }}>Speedboat</option>
                            <option value="vessel" {{ ($filters['vehicle_kind'] ?? '') === 'vessel' ? 'selected' : '' }}>Private Vessel / Dhoni</option>
                            <option value="yacht" {{ ($filters['vehicle_kind'] ?? '') === 'yacht' ? 'selected' : '' }}>Yacht / Charter</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="pickup_date">Pickup Date</label>
                        <input id="pickup_date" name="pickup_date" type="date" value="{{ $filters['pickup_date'] ?? '' }}">
                    </div>
                    <div class="field">
                        <label for="return_date_rental">Return Date</label>
                        <input id="return_date_rental" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}">
                    </div>
                    <div class="field">
                        <label for="adults_rental">Passengers / Pax</label>
                        <input id="adults_rental" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}">
                    </div>
                </div>
            @endif

            <div class="actions">
                <button class="primary" type="submit">Apply Filters</button>
                <a href="/catalog/{{ $categoryKey }}">Reset</a>
            </div>
        </form>

        <h2 class="section-title">Available Portfolio Items</h2>
        @if ($catalogProperties->isEmpty())
            <div class="empty">No listings found for this category and selected filters yet.</div>
        @else
            <section class="catalog-grid" aria-label="Category listing catalogue">
                @foreach ($catalogProperties as $property)
                    @php
                        $propertyId = (int) ($property->id ?? 0);
                        $mediaItems = collect($catalogPropertyMediaByProperty->get($propertyId, collect()));
                        $primaryMedia = $mediaItems->first();
                        $bannerUrl = $primaryMedia ? $mediaVariantUrl($primaryMedia, 'banner') : null;
                        $fallbackPath = trim((string) ($primaryMedia->file_path ?? ''));
                        $fallbackImage = '';
                        if ($fallbackPath !== '') {
                            if (str_starts_with($fallbackPath, 'http://') || str_starts_with($fallbackPath, 'https://')) {
                                $fallbackImage = $fallbackPath;
                            } else {
                                $fallbackImage = '/storage/' . ltrim(str_replace('public/', '', str_replace('storage/', '', str_replace('\\', '/', $fallbackPath))), '/');
                            }
                        }
                        $svgFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3ENo%20image%3C%2Ftext%3E%3C%2Fsvg%3E";
                        $price = (float) ($property->base_price ?? 0);
                        $detailUrl = $categoryKey === 'accommodation'
                            ? ('/property/' . $propertyId)
                            : ('/category-booking/' . $categoryKey . '/' . $propertyId);
                        $actionLabel = match ($categoryKey) {
                            'accommodation'     => 'Open Listing Profile',
                            'restaurant'        => 'Reserve a Table',
                            'vehicle_rental'    => 'Hire Vehicle / Vessel',
                            'marine-transport'  => 'Book Marine Transfer',
                            'land-transport'    => 'Book Land Transfer',
                            'excursion'         => 'Book Excursion',
                            'conference_room'   => 'Book Conference Room',
                            'resort_day_visit'  => 'Book Day Visit',
                            'remote_workspace'  => 'Book Workspace',
                            default             => 'Proceed to Booking',
                        };
                    @endphp
                    <article class="card">
                        <a class="card-link" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                            @php
                                $resolvedImage = $fallbackImage !== '' ? $fallbackImage : ($bannerUrl ?: $svgFallback);
                            @endphp
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $svgFallback }}';};" alt="{{ (string) ($property->name ?? 'Listing image') }}" loading="lazy">
                            <div class="card-body">
                                <h3>{{ (string) ($property->name ?? 'Listing') }}</h3>
                                <div class="meta">{{ trim((string) (($property->atoll ?? '') . ' ' . ($property->island ?? ''))) !== '' ? trim((string) (($property->atoll ?? '') . ' · ' . ($property->island ?? ''))) : 'Location will be updated soon.' }}</div>
                                <div class="meta">{{ strtoupper((string) ($property->currency ?? 'MVR')) }} {{ number_format($price, 2) }}</div>
                                <div class="meta">{{ strtoupper(str_replace('_', ' ', (string) ($property->listing_category ?? $categoryKey))) }}</div>
                                <div class="actions"><span>{{ $actionLabel }}</span></div>
                            </div>
                        </a>
                    </article>
                @endforeach
            </section>
        @endif

        @include('partials.global-site-footer')
    </main>
</body>
</html>