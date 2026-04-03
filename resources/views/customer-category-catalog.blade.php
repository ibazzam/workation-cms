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
            padding: 10px;
            font: inherit;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .actions button,
        .actions a {
            text-decoration: none;
            border: 1px solid #c8d8e5;
            border-radius: 10px;
            padding: 8px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            background: #fff;
            color: #20415d;
        }

        .actions button.primary {
            border-color: #f6d19a;
            background: linear-gradient(135deg, #ffc76f 0%, var(--accent) 100%);
            color: #57350b;
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
                width: calc(100% - 18px);
                margin: 10px auto 12px;
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
</head>
<body>
    @php
        $filters = $filters ?? [];
        $categoryKey = $categoryKey ?? 'accommodation';
        $categoryMeta = $categoryMeta ?? ['label' => 'Catalogue', 'subtitle' => ''];
        $catalogCategoryLinks = collect([
            ['key' => 'accommodation',    'icon' => 'fa-solid fa-hotel',          'title' => 'Accommodation',   'subtitle' => 'Hotels, resorts, villas'],
            ['key' => 'transport',        'icon' => 'fa-solid fa-ship',           'title' => 'Transport',       'subtitle' => 'Marine and land transfers'],
            ['key' => 'excursion',        'icon' => 'fa-solid fa-water',          'title' => 'Excursion',       'subtitle' => 'Tours and activities'],
            ['key' => 'remote_workspace', 'icon' => 'fa-solid fa-laptop',         'title' => 'Remote Workspace','subtitle' => 'Work-friendly spaces'],
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
                    <i class="{{ $item['icon'] ?? 'fa-solid fa-location-dot' }}"></i> {{ $item['title'] ?? 'Category' }}
                    <span>{{ $item['subtitle'] ?? '' }}</span>
                </a>
            @endforeach
        </section>
    </aside>

    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <p>Category Portfolio</p>
            <h1>{{ $categoryMeta['label'] }} Catalogue</h1>
            <p>{{ $categoryMeta['subtitle'] }}</p>
            <div class="hero-links">
                <a href="/">Back Home</a>
                <a href="/customer">Customer Portal</a>
                <a href="/catalog/accommodation">Accommodation</a>
                <a href="/catalog/transport">Transport</a>
                <a href="/catalog/excursion">Excursion</a>
                <a href="/catalog/remote_workspace">Remote Workspace</a>
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
            @elseif ($categoryKey === 'transport')
                <div class="grid">
                    <div class="field">
                        <label for="transport_mode">Mode</label>
                        <select id="transport_mode" name="transport_mode">
                            <option value="marine" {{ ($filters['transport_mode'] ?? 'marine') === 'marine' ? 'selected' : '' }}>Marine Transport</option>
                            <option value="land" {{ ($filters['transport_mode'] ?? '') === 'land' ? 'selected' : '' }}>Land Transport</option>
                        </select>
                    </div>
                    <div class="field"><label for="from">From</label><input id="from" name="from" type="text" value="{{ $filters['from'] ?? '' }}"></div>
                    <div class="field"><label for="to">To</label><input id="to" name="to" type="text" value="{{ $filters['to'] ?? '' }}"></div>
                    <div class="field"><label for="travel_date">Travel Date</label><input id="travel_date" name="travel_date" type="date" value="{{ $filters['travel_date'] ?? '' }}"></div>
                    <div class="field"><label for="return_date">Return Date</label><input id="return_date" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}"></div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                    <div class="field"><label for="vehicle_type">Vehicle Type</label><input id="vehicle_type" name="vehicle_type" type="text" value="{{ $filters['vehicle_type'] ?? '' }}" placeholder="Car, Van, Bike"></div>
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
                        $actionLabel = $categoryKey === 'accommodation' ? 'Open Listing Profile' : 'Proceed to Booking';
                    @endphp
                    <article class="card">
                        <a class="card-link" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                            @php
                                $resolvedImage = $bannerUrl ?: ($fallbackImage !== '' ? $fallbackImage : $svgFallback);
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