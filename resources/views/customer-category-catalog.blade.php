<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoryMeta['label'] }} Catalogue | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
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
            width: min(1200px, calc(100% - 24px));
            margin: 14px auto 30px;
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
            .grid,
            .catalog-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
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
                        $fallbackImage = $primaryMedia ? ('/storage/' . ltrim((string) ($primaryMedia->file_path ?? ''), '/')) : '';
                        $price = (float) ($property->base_price ?? 0);
                        $detailUrl = $categoryKey === 'accommodation'
                            ? ('/property/' . $propertyId)
                            : ('/category-booking/' . $categoryKey . '/' . $propertyId);
                        $actionLabel = $categoryKey === 'accommodation' ? 'Open Listing Profile' : 'Proceed to Booking';
                    @endphp
                    <article class="card">
                        <a class="card-link" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                            @if ($bannerUrl)
                                <img src="{{ $bannerUrl }}" onerror="if(!this.dataset.fallbackTried){this.dataset.fallbackTried='1';this.src='{{ $fallbackImage }}';}" alt="{{ (string) ($property->name ?? 'Listing image') }}" loading="lazy">
                            @else
                                <img src="" alt="No image" loading="lazy">
                            @endif
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