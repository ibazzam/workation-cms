<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $property->name ?? 'Sea Transport' }} | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --brand: #0f6179;
            --brand-strong: #0b4f66;
            --soft: #f7fbfd;
            --chip: #edf6fc;
            --property-header-offset: 74px;
        }

        body.is-header-hidden {
            --property-header-offset: 0px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .page { width: min(1180px, calc(100% - 24px)); margin: 14px auto 28px; }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 6px;
            margin-bottom: 10px;
            font-size: 0.78rem;
            color: #5f7488;
        }

        .breadcrumb a { color: var(--brand); text-decoration: none; font-weight: 600; }
        .breadcrumb a:hover { text-decoration: underline; }

        .section {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 16px;
            margin-top: 12px;
        }

        .gallery-shell { display: grid; gap: 10px; }
        .gallery-hero { width: 100%; height: 360px; object-fit: cover; border-radius: 12px; border: 1px solid #d9e7f0; background: #eef6fb; }
        .gallery-thumbs { display: flex; gap: 8px; flex-wrap: wrap; }
        .gallery-thumb {
            border: 2px solid #d7e7f1;
            border-radius: 10px;
            overflow: hidden;
            width: 72px;
            height: 72px;
            padding: 0;
            background: #fff;
            cursor: pointer;
        }
        .gallery-thumb.is-fallback {
            border-color: #cfe1ed;
            background: linear-gradient(135deg, #e8f3fa 0%, #d9eaf6 100%);
            position: relative;
        }
        .gallery-thumb.is-fallback::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(255,255,255,0.55), rgba(255,255,255,0));
        }
        .gallery-thumb.is-active { border-color: var(--brand); }
        .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .property-summary-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 12px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            padding: 16px;
            margin-top: 12px;
        }

        .property-summary-main h1 { margin: 0 0 8px; font-size: clamp(1.45rem, 2.8vw, 2rem); }
        .property-summary-meta { display: flex; gap: 12px; flex-wrap: wrap; color: #44637a; font-size: 0.88rem; }

        .summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #d3e4ee;
            background: var(--chip);
            font-size: 0.78rem;
            color: #1f4f6b;
            font-weight: 700;
            margin-top: 8px;
        }

        .property-summary-price {
            border: 1px solid #d6e6ef;
            border-radius: 12px;
            background: #f7fbff;
            padding: 12px;
            display: grid;
            gap: 6px;
            align-content: start;
        }

        .property-summary-price .k { font-size: 0.72rem; text-transform: uppercase; color: #5f7488; letter-spacing: 0.06em; font-weight: 700; }
        .property-summary-price .v { font-size: 1.45rem; font-weight: 800; color: #163e57; }
        .property-summary-price .sub { color: #587086; font-size: 0.8rem; }

        .booking-btn {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .section-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .section-tab {
            text-decoration: none;
            border: 1px solid #d4e5ef;
            background: #fff;
            color: #1f4f6b;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .section h2 {
            margin: 0 0 12px;
            font-size: 1.18rem;
            color: #173d55;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .routes-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .route-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .route-name {
            margin: 0;
            font-size: 0.95rem;
            color: #173d55;
            font-weight: 700;
        }

        .route-meta {
            display: grid;
            gap: 5px;
            font-size: 0.84rem;
            color: #35576d;
        }

        .route-fares {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .fare-pill {
            border: 1px solid #cfe2ee;
            border-radius: 999px;
            background: #eef6fc;
            color: #1f4f6b;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 10px;
        }

        .route-form {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            align-items: end;
            padding-top: 8px;
            border-top: 1px dashed #d9e7f0;
        }

        .route-field label {
            display: block;
            margin-bottom: 4px;
            font-size: 0.72rem;
            color: #5b7488;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .route-field input,
        .route-field select {
            width: 100%;
            border: 1px solid #c9dce8;
            border-radius: 8px;
            padding: 8px 9px;
            font: inherit;
            font-size: 0.84rem;
            background: #fff;
            color: #1f3f55;
        }

        .route-book-btn {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .amenity-item {
            border: 1px solid #dbe7f0;
            border-radius: 10px;
            background: #fbfdff;
            padding: 10px;
            font-size: 0.88rem;
            color: #2f5168;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .policies-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .policy-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
        }

        .policy-label {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #5b7488;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .policy-value { font-size: 0.9rem; color: #284f67; line-height: 1.45; }

        .empty-note {
            border: 1px dashed #cddfea;
            border-radius: 10px;
            background: #fbfdff;
            padding: 12px;
            color: #517188;
            font-size: 0.9rem;
        }

        @media (max-width: 980px) {
            .property-summary-shell { grid-template-columns: 1fr; }
            .routes-grid { grid-template-columns: 1fr; }
            .route-form { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .gallery-hero { height: 250px; }
            .amenities-grid,
            .policies-grid { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>

@php
    $headerCategoryLinks = [
        ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'icon' => 'fa-solid fa-ship', 'title' => 'Live Aboard', 'url' => '/catalog/liveaboard'],
        ['key' => 'excursion', 'icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'url' => '/catalog/excursion'],
        ['key' => 'water-sports', 'icon' => 'fa-solid fa-person-swimming', 'title' => 'Water Sports', 'url' => '/catalog/water_sports'],
        ['key' => 'restaurant', 'icon' => 'fa-solid fa-utensils', 'title' => 'Restaurants', 'url' => '/catalog/restaurant'],
        ['key' => 'sea-transport', 'icon' => 'fa-solid fa-ferry', 'title' => 'Sea Transport', 'url' => '/catalog/sea-transport'],
        ['key' => 'land-transport', 'icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'url' => '/catalog/land-transport'],
        ['key' => 'vehicle-rental', 'icon' => 'fa-solid fa-car-side', 'title' => 'Vehicle Rentals', 'url' => '/catalog/vehicle_rental'],
        ['key' => 'remote-workspace', 'icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'url' => '/catalog/remote_workspace'],
        ['key' => 'conference-room', 'icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'url' => '/catalog/conference_room'],
    ];

    $imageUrl = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22600%22 viewBox=%220 0 1200 600%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 stop-color=%230f6179%22/%3E%3Cstop offset=%22100%25%22 stop-color=%231d7bb5%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%221200%22 height=%22600%22 fill=%22url(%23g)%22/%3E%3C/svg%3E";

    $gallery = collect();
    if (is_string($heroUrl ?? '') && trim((string) $heroUrl) !== '') {
        $gallery->push(trim((string) $heroUrl));
    }
    if (!empty($galleryMedia) && is_array($galleryMedia)) {
        foreach ($galleryMedia as $galleryUrl) {
            if (is_string($galleryUrl) && trim($galleryUrl) !== '') {
                $gallery->push(trim($galleryUrl));
            }
        }
    }

    $gallery = $gallery
        ->map(static function ($url) {
            $value = trim((string) $url);
            if (str_starts_with($value, 'http://')) {
                return 'https://' . ltrim(substr($value, 7), '/');
            }
            return $value;
        })
        ->filter(static fn ($url) => str_starts_with($url, 'https://') || str_starts_with($url, '/'))
        ->unique()
        ->values();

    if ($gallery->isEmpty()) {
        $gallery = collect([$imageUrl]);
    }

    $amenitiesRaw = [];
    foreach (['amenities', 'features', 'services', 'highlights', 'safety_equipment'] as $key) {
        $val = $listingDetails[$key] ?? null;
        if (is_array($val)) {
            $amenitiesRaw = array_merge($amenitiesRaw, $val);
        } elseif (is_string($val) && trim($val) !== '') {
            $amenitiesRaw = array_merge($amenitiesRaw, preg_split('/[\r\n,]+/', $val) ?: []);
        }
    }
    $amenities = collect($amenitiesRaw)->map(static fn ($item) => trim((string) $item))->filter()->unique()->values();

    $summaryRoute = collect($routeSchedules ?? [])->first(function ($candidateRoute) {
        return trim((string) ($candidateRoute['origin'] ?? '')) !== ''
            || trim((string) ($candidateRoute['destination'] ?? '')) !== '';
    });

    $defaultPrice = $visitorResidency === 'local_resident' ? (float) ($fromPriceLocal ?? 0) : (float) ($fromPriceForeign ?? 0);
    $defaultCurrency = $visitorResidency === 'local_resident' ? 'MVR' : 'USD';
@endphp

@include('partials.customer-uniform-header', [
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => 'sea-transport',
])

<main class="page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">›</span>
        <a href="/catalog/sea-transport">Sea Transport</a>
        <span aria-hidden="true">›</span>
        <span>{{ (string) ($property->name ?? 'Sea Transport') }}</span>
    </nav>

    <section class="section" aria-label="Gallery">
        <h2>Photo Gallery</h2>
        <div class="gallery-shell" data-gallery>
            <img id="galleryHero" class="gallery-hero" src="{{ $gallery->first() ?: $imageUrl }}" alt="Sea transport image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $imageUrl }}';}">
            <div class="gallery-thumbs" role="list">
                @foreach ($gallery as $index => $image)
                    <button type="button" class="gallery-thumb{{ $index === 0 ? ' is-active' : '' }}" data-src="{{ $image }}" aria-label="Image {{ $index + 1 }}">
                        <img src="{{ $image }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.closest('.gallery-thumb').classList.add('is-fallback');">
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="property-summary-shell" aria-label="Summary">
        <div class="property-summary-main">
            <h1>{{ (string) ($property->name ?? 'Sea Transport') }}</h1>
            <div class="property-summary-meta">
                <span><i class="fa-solid fa-ferry" aria-hidden="true"></i> Scheduled sea transport</span>
                @if ($summaryRoute)
                    <span><i class="fa-solid fa-route" aria-hidden="true"></i> {{ trim((string) ($summaryRoute['origin'] ?? 'Departure')) }} → {{ trim((string) ($summaryRoute['destination'] ?? 'Destination')) }}</span>
                @endif
                @if (!empty($listingDetails['total_seats']))
                    <span><i class="fa-solid fa-users" aria-hidden="true"></i> {{ (int) $listingDetails['total_seats'] }} seats</span>
                @endif
            </div>
            @if (!empty($listingDetails['description']))
                <p style="margin:10px 0 0; color:#4b6578; line-height:1.5;">{{ \Illuminate\Support\Str::words((string) $listingDetails['description'], 70) }}</p>
            @endif
        </div>

        <aside class="property-summary-price">
            <span class="k">Starting from</span>
            <span class="v">{{ $defaultPrice > 0 ? ($defaultCurrency . ' ' . number_format($defaultPrice, 2)) : 'Price on request' }}</span>
            <span class="sub">Per passenger</span>
            <a class="booking-btn" href="#routes-section"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Choose Route</a>
        </aside>
    </section>

    <nav class="section-tabs">
        <a class="section-tab" href="#routes-section">Routes</a>
        <a class="section-tab" href="#amenities-section">Service Features</a>
        <a class="section-tab" href="#policies-section">Policies</a>
    </nav>

    <section id="routes-section" class="section">
        <h2>Available Routes</h2>
        @if (!empty($routeSchedules) && is_array($routeSchedules))
            <div class="routes-grid">
                @foreach ($routeSchedules as $legIdx => $leg)
                    @php
                        $legCode = (string) ($leg['route_code'] ?? '');
                        $legOrigin = trim((string) ($leg['origin'] ?? 'Departure'));
                        $legDest = trim((string) ($leg['destination'] ?? 'Destination'));
                        $legDep = trim((string) ($leg['dep_time'] ?? ''));
                        $legArr = trim((string) ($leg['arr_time'] ?? ''));
                        $legDays = is_array($leg['days'] ?? null) ? implode(', ', $leg['days']) : '';
                        $localFare = (float) ($leg['local_adult'] ?? $fromPriceLocal ?? 0);
                        $foreignFare = (float) ($leg['foreign_adult'] ?? $fromPriceForeign ?? 0);
                    @endphp
                    <article class="route-card">
                        <h3 class="route-name">{{ $legOrigin }} → {{ $legDest }}</h3>
                        <div class="route-meta">
                            @if ($legDep !== '' || $legArr !== '')
                                <span><i class="fa-solid fa-clock"></i> {{ $legDep !== '' ? ('Dep ' . $legDep) : '' }}{{ $legDep !== '' && $legArr !== '' ? ' · ' : '' }}{{ $legArr !== '' ? ('Arr ' . $legArr) : '' }}</span>
                            @endif
                            @if ($legDays !== '')
                                <span><i class="fa-solid fa-calendar-days"></i> {{ $legDays }}</span>
                            @endif
                        </div>

                        <div class="route-fares">
                            @if ($localFare > 0)
                                <span class="fare-pill">Local: MVR {{ number_format($localFare, 2) }}</span>
                            @endif
                            @if ($foreignFare > 0)
                                <span class="fare-pill">Foreign: USD {{ number_format($foreignFare, 2) }}</span>
                            @endif
                        </div>

                        <form method="POST" action="/category-booking/sea_transport/{{ $property->vendor_property_id ?? $property->id }}" class="route-form">
                            @csrf
                            <input type="hidden" name="route_code" value="{{ $legCode }}">
                            <input type="hidden" name="boarding_point" value="{{ $legOrigin }}">
                            <input type="hidden" name="disembark_point" value="{{ $legDest }}">
                            <input type="hidden" name="listing_category" value="sea_transport">

                            <div class="route-field">
                                <label for="travel_date_{{ $legIdx }}">Travel Date</label>
                                <input id="travel_date_{{ $legIdx }}" type="date" name="travel_date" min="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="route-field">
                                <label for="adults_{{ $legIdx }}">Adults</label>
                                <input id="adults_{{ $legIdx }}" type="number" name="adults" min="1" value="1" required>
                            </div>

                            <div class="route-field">
                                <label for="residency_{{ $legIdx }}">Residency</label>
                                <select id="residency_{{ $legIdx }}" name="guest_residency">
                                    <option value="local_resident" {{ $visitorResidency === 'local_resident' ? 'selected' : '' }}>Local resident</option>
                                    <option value="foreign_national" {{ $visitorResidency !== 'local_resident' ? 'selected' : '' }}>Foreign visitor</option>
                                </select>
                            </div>

                            <div class="route-field" style="grid-column: 1 / -1;">
                                <button type="submit" class="route-book-btn"><i class="fa-solid fa-calendar-check"></i> Continue Booking</button>
                            </div>
                        </form>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-note">No route schedules are available yet for this service.</div>
        @endif
    </section>

    <section id="amenities-section" class="section">
        <h2>Service Features</h2>
        @if ($amenities->isNotEmpty())
            <div class="amenities-grid">
                @foreach ($amenities as $amenity)
                    <div class="amenity-item"><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $amenity }}</div>
                @endforeach
            </div>
        @else
            <div class="empty-note">Service features will be published shortly.</div>
        @endif
    </section>

    <section id="policies-section" class="section">
        <h2>Travel Policies</h2>
        <div class="policies-grid">
            <article class="policy-card">
                <div class="policy-label">Boarding Policy</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['boarding_policy'] ?? 'Passengers should arrive at least 30 minutes before departure.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Cancellation</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['cancellation_policy'] ?? 'Cancellation terms vary by route and fare type.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Luggage</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['luggage_policy'] ?? 'Standard baggage allowance applies unless otherwise stated.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Safety</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['safety_policy'] ?? 'Follow all onboard safety instructions provided by the crew.')) }}</div>
            </article>
        </div>
    </section>
</main>

@include('partials.global-site-footer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const gallery = document.querySelector('[data-gallery]');
        if (!gallery) return;
        const hero = gallery.querySelector('#galleryHero');
        const thumbs = gallery.querySelectorAll('.gallery-thumb');
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                thumbs.forEach(t => t.classList.remove('is-active'));
                thumb.classList.add('is-active');
                const src = thumb.dataset.src;
                hero.src = src;
            });
        });
    });
</script>
</body>
</html>
