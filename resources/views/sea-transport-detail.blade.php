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

        .top-search-shell {
            position: sticky;
            top: var(--property-header-offset);
            z-index: 60;
            border: 1px solid #d4e5ef;
            background: #ffffff;
            padding: 8px;
            margin-bottom: 0;
        }

        .top-search-inner {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto;
        }

        .top-search-form {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(140px, 1fr)) auto;
            gap: 8px;
            align-items: center;
        }

        .top-search-field {
            border: 1px solid #c6d7e4;
            border-radius: 8px;
            padding: 8px 10px;
            background: #fbfdff;
            color: #17344a;
            display: grid;
            gap: 2px;
        }

        .top-search-field label {
            font-size: 0.68rem;
            color: #5f7488;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .top-search-field input {
            border: 0;
            background: transparent;
            font: inherit;
            font-size: 0.88rem;
            color: #17344a;
            outline: none;
            padding: 0;
        }

        .top-search-btn {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 8px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(300px, 340px);
            gap: 12px;
            align-items: start;
        }

        .block {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 16px;
        }

        .block-title {
            margin: 0 0 12px;
            font-size: 1.18rem;
            color: #173d55;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

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
        .gallery-shell {
            grid-template-columns: minmax(0, 1fr) 260px;
            align-items: start;
        }
        .gallery-banner-wrap {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #d9e7f0;
            background: #eef6fb;
        }
        .gallery-hero { width: 100%; height: 360px; object-fit: cover; border-radius: 12px; border: 1px solid #d9e7f0; background: #eef6fb; }
        .gallery-thumbs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .gallery-thumb {
            border: 2px solid #d7e7f1;
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
            height: 84px;
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
            gap: 10px;
        }

        .property-summary-main h1 { margin: 0 0 8px; font-size: clamp(1.45rem, 2.8vw, 2rem); }
        .property-summary-meta { display: flex; gap: 12px; flex-wrap: wrap; color: #44637a; font-size: 0.88rem; }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cde1ef;
            border-radius: 999px;
            padding: 4px 10px;
            background: #edf6fc;
            color: #1f4f6b;
            font-weight: 700;
            font-size: 0.78rem;
        }

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

        .booking-card {
            border: 1px solid #d6e6ef;
            border-radius: 14px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 8px;
            position: sticky;
            top: calc(var(--property-header-offset) + 8px);
        }

        .booking-card .block-title {
            margin-bottom: 0;
        }

        .booking-subtitle {
            margin: 0;
            font-size: 0.88rem;
            color: #35586e;
            font-weight: 700;
        }

        .booking-field {
            display: grid;
            gap: 4px;
        }

        .booking-field label {
            font-size: 0.74rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #35586e;
            font-weight: 700;
        }

            .booking-field input,
            .booking-field select,
            .booking-field textarea {
            width: 100%;
            border: 1px solid #bfe0f1;
            border-radius: 10px;
            padding: 10px;
            font: inherit;
            color: #1f3f55;
            background: #fff;
        }

        .booking-order-box {
            border: 1px solid #dbeaf4;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            color: #527188;
            font-size: 0.86rem;
            min-height: 84px;
        }

        .booking-order-box.is-empty {
            display: grid;
            place-items: center;
            color: #9ab5c7;
            text-align: center;
        }

        .booking-total {
            border: 1px solid #d6e6ef;
            border-radius: 12px;
            background: #f2fbf8;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
        }

        .booking-total span { font-size: 0.76rem; letter-spacing: 0.06em; text-transform: uppercase; color: #35586e; font-weight: 700; }
        .booking-total strong { font-size: 1.85rem; color: #163e57; line-height: 1; font-weight: 800; }

        .booking-note {
            margin: 0;
            color: #4f7188;
            font-size: 0.82rem;
            line-height: 1.3;
            font-style: italic;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #d4e5ef;
        }

        .order-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .order-name { font-weight: 700; color: #1b455f; font-size: 0.87rem; }
        .order-sub { color: #5a768a; font-size: 0.76rem; margin-top: 2px; }
        .order-price { font-weight: 800; color: #163e57; font-size: 0.85rem; }

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

        .service-review {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: #2e566d;
            font-weight: 700;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .detail-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
        }

        .detail-card h3 {
            margin: 0 0 8px;
            font-size: 1rem;
            color: #163e57;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .detail-bullet-list,
        .list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .detail-bullet-list li,
        .list li {
            display: grid;
            grid-template-columns: 18px 1fr;
            gap: 8px;
            align-items: start;
            color: #2f5168;
            font-size: 0.9rem;
        }

        .description {
            margin: 0;
            color: #40657d;
            line-height: 1.5;
            font-size: 0.93rem;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .equipment-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .route-name {
            margin: 0;
            font-size: 1rem;
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

        .equipment-add-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 8px;
            align-items: center;
            padding-top: 8px;
            border-top: 1px dashed #d9e7f0;
        }

        .equipment-stepper-label {
            font-size: 0.78rem;
            color: #5b7488;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .equipment-stepper {
            display: grid;
            grid-template-columns: 30px 72px 30px;
            align-items: center;
            border: 1px solid #c9dce8;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .equipment-stepper button {
            border: 0;
            background: #f4fbff;
            color: #0f6179;
            font-size: 1rem;
            font-weight: 700;
            height: 34px;
            cursor: pointer;
        }

        .equipment-stepper input {
            border: 0;
            text-align: center;
            width: 100%;
            padding: 6px 4px;
            font: inherit;
            font-size: 0.84rem;
            color: #1f3f55;
        }

        .equipment-add-btn {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 8px;
            padding: 8px 11px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-self: end;
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
            .top-search-form { grid-template-columns: 1fr 1fr; }
            .layout { grid-template-columns: 1fr; }
            .booking-card { position: static; }
            .detail-grid,
            .equipment-grid,
            .policies-grid { grid-template-columns: 1fr; }
            .gallery-shell { grid-template-columns: 1fr; }
            .gallery-thumbs { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .top-search-form { grid-template-columns: 1fr; }
            .gallery-hero { height: 250px; }
            .gallery-thumbs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>

@php
    $headerCategoryLinks = [
        ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'icon' => 'fa-solid fa-ship', 'title' => 'Liveaboard', 'url' => '/catalog/liveaboard'],
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

    $locationLine = collect([
        trim((string) ($property->island ?? '')),
        trim((string) ($property->atoll ?? '')),
        trim((string) ($property->location_country ?? 'Maldives')),
    ])->filter()->unique()->implode(', ');

    $descriptionSummary = trim((string) ($listingDetails['description'] ?? $property->description ?? ''));
    $descriptionSummary = $descriptionSummary !== '' ? \Illuminate\Support\Str::words($descriptionSummary, 45) : '';

    $routeCollection = collect($routeSchedules ?? [])->filter(static function ($leg) {
        return is_array($leg)
            && (trim((string) ($leg['origin'] ?? '')) !== '' || trim((string) ($leg['destination'] ?? '')) !== '');
    })->values();

    $termsAndPolicies = collect([
        trim((string) ($listingDetails['boarding_policy'] ?? 'Passengers should arrive at least 30 minutes before departure.')),
        trim((string) ($listingDetails['cancellation_policy'] ?? 'Cancellation terms vary by route and fare type.')),
        trim((string) ($listingDetails['luggage_policy'] ?? 'Standard baggage allowance applies unless otherwise stated.')),
        trim((string) ($listingDetails['safety_policy'] ?? 'Follow all onboard safety instructions provided by the crew.')),
    ])->filter()->values();

    $defaultPrice = $visitorResidency === 'local_resident' ? (float) ($fromPriceLocal ?? 0) : (float) ($fromPriceForeign ?? 0);
    $defaultCurrency = $visitorResidency === 'local_resident' ? 'MVR' : 'USD';
@endphp

@include('partials.customer-uniform-header', [
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => 'sea-transport',
])

<section class="top-search-shell" aria-label="Search service options">
    <div class="top-search-inner">
        <form method="GET" action="" class="top-search-form">
            <div class="top-search-field">
                <label for="topLocation">Location</label>
                <input id="topLocation" type="text" value="{{ (string) ($property->name ?? 'Sea Transport') }}" readonly>
            </div>
            <div class="top-search-field">
                <label for="topStart">Start</label>
                <input id="topStart" type="date" min="{{ now()->toDateString() }}">
            </div>
            <div class="top-search-field">
                <label for="topEnd">End</label>
                <input id="topEnd" type="date" min="{{ now()->toDateString() }}">
            </div>
            <div class="top-search-field">
                <label for="topGuests">Guests</label>
                <input id="topGuests" type="text" value="2 adults, 0 children" readonly>
            </div>
            <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
        </form>
    </div>
</section>

<main class="page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">›</span>
        <a href="/catalog/sea-transport">Sea Transport</a>
        <span aria-hidden="true">›</span>
        <span>{{ (string) ($property->name ?? 'Sea Transport') }}</span>
    </nav>

    <div class="layout">
        <section class="service-content">
            <section class="block" aria-label="Service gallery">
                <h2 class="block-title">Service Gallery</h2>
                <div class="gallery-shell" data-gallery>
                    <div class="gallery-banner-wrap">
                        <img id="galleryHero" class="gallery-hero" src="{{ $gallery->first() ?: $imageUrl }}" alt="Sea transport image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $imageUrl }}';}">
                    </div>
                    <div class="gallery-thumbs" role="list">
                        @foreach ($gallery->take(8) as $index => $image)
                            <button type="button" class="gallery-thumb{{ $index === 0 ? ' is-active' : '' }}" data-src="{{ $image }}" aria-label="Image {{ $index + 1 }}">
                                <img src="{{ $image }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.closest('.gallery-thumb').classList.add('is-fallback');">
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="block property-summary-shell" aria-label="Service overview" style="margin-top:12px;">
                <div class="property-summary-main">
                    <h1>{{ (string) ($property->name ?? 'Sea Transport') }}</h1>
                    @if ($locationLine !== '')
                        <p class="description">{{ $locationLine }}</p>
                    @endif
                    @if ($descriptionSummary !== '')
                        <p class="description">{{ $descriptionSummary }}</p>
                    @endif
                </div>
                <div class="hero-meta">
                    <span class="hero-chip"><i class="fa-solid fa-ferry" aria-hidden="true"></i> Sea Transport</span>
                    @if ($summaryRoute)
                        <span class="hero-chip"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ trim((string) ($summaryRoute['origin'] ?? 'Departure')) }} → {{ trim((string) ($summaryRoute['destination'] ?? 'Destination')) }}</span>
                    @endif
                    @if (!empty($listingDetails['total_seats']))
                        <span class="hero-chip"><i class="fa-solid fa-users" aria-hidden="true"></i> {{ (int) $listingDetails['total_seats'] }} seats</span>
                    @endif
                </div>
            </section>

            <section class="block" aria-label="Service details" style="margin-top:12px;">
                <h2 class="block-title">Descriptions & Details</h2>
                <div class="detail-grid">
                    <article class="detail-card">
                        <h3>What is Included:</h3>
                        @if ($amenities->isNotEmpty())
                            <ul class="detail-bullet-list">
                                @foreach ($amenities->take(6) as $amenity)
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ $amenity }}</span></li>
                                @endforeach
                            </ul>
                        @else
                            <p class="description">Standard sea transfer inclusions apply for this route.</p>
                        @endif
                    </article>

                    <article class="detail-card">
                        <h3>What We Provide:</h3>
                        <ul class="detail-bullet-list">
                            <li><i class="fa-solid fa-life-ring" aria-hidden="true"></i><span>Safety equipment onboard</span></li>
                            <li><i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Crew-assisted boarding support</span></li>
                            <li><i class="fa-solid fa-clock" aria-hidden="true"></i><span>Scheduled departure operations</span></li>
                        </ul>
                    </article>

                    <article class="detail-card">
                        <h3>Departure / Reporting Point</h3>
                        <ul class="detail-bullet-list">
                            <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>{{ trim((string) ($summaryRoute['origin'] ?? ($property->island ?? 'Departure point'))) }}</span></li>
                            <li><i class="fa-solid fa-route" aria-hidden="true"></i><span>{{ trim((string) ($summaryRoute['origin'] ?? 'Departure')) }} → {{ trim((string) ($summaryRoute['destination'] ?? 'Destination')) }}</span></li>
                            @if (!empty($summaryRoute['dep_time']))
                                <li><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i><span>Departure: {{ (string) $summaryRoute['dep_time'] }}</span></li>
                            @endif
                        </ul>
                    </article>
                </div>
            </section>

            <section id="routes-section" class="block" aria-label="Route fare cards" style="margin-top:12px;">
                <h2 class="block-title">Available Routes</h2>
                @if ($routeCollection->isNotEmpty())
                    <div class="equipment-grid">
                        @foreach ($routeCollection as $legIdx => $leg)
                            @php
                                $legCode = (string) ($leg['route_code'] ?? '');
                                $legOrigin = trim((string) ($leg['origin'] ?? 'Departure'));
                                $legDest = trim((string) ($leg['destination'] ?? 'Destination'));
                                $legDep = trim((string) ($leg['dep_time'] ?? ''));
                                $legArr = trim((string) ($leg['arr_time'] ?? ''));
                                $legDays = is_array($leg['days'] ?? null) ? implode(', ', $leg['days']) : '';
                                $localFare = (float) ($leg['local_adult'] ?? $fromPriceLocal ?? 0);
                                $foreignFare = (float) ($leg['foreign_adult'] ?? $fromPriceForeign ?? 0);
                                $fallbackFare = $localFare > 0 ? $localFare : $foreignFare;
                            @endphp
                            <article class="equipment-card" data-route-card
                                data-route-code="{{ $legCode }}"
                                data-boarding="{{ e($legOrigin) }}"
                                data-disembark="{{ e($legDest) }}"
                                data-local-fare="{{ $localFare }}"
                                data-foreign-fare="{{ $foreignFare }}"
                                data-fallback-fare="{{ $fallbackFare }}">
                                <div style="display:flex; justify-content:space-between; align-items:start; gap:8px;">
                                    <h3 class="route-name">{{ $legOrigin }} → {{ $legDest }}</h3>
                                    <span style="background:#e8f4f8; color:#0f6179; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:600; white-space:nowrap;">route</span>
                                </div>
                                <div class="route-meta">
                                    @if ($legDep !== '' || $legArr !== '')
                                        <span><i class="fa-solid fa-clock" aria-hidden="true"></i> {{ $legDep !== '' ? ('Dep ' . $legDep) : '' }}{{ $legDep !== '' && $legArr !== '' ? ' · ' : '' }}{{ $legArr !== '' ? ('Arr ' . $legArr) : '' }}</span>
                                    @endif
                                    @if ($legDays !== '')
                                        <span><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $legDays }}</span>
                                    @endif
                                </div>
                                <div style="border-top:1px solid var(--line); padding-top:8px; margin-top:4px;">
                                    <p style="margin:0 0 4px; font-size:0.85rem; color:var(--muted);">Price per passenger</p>
                                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:baseline;">
                                        @if ($localFare > 0)
                                            <strong style="color:var(--ink);">MVR {{ number_format($localFare, 2) }}</strong>
                                        @endif
                                        @if ($foreignFare > 0)
                                            <span style="color:var(--muted); font-size:0.85rem;">≈ USD {{ number_format($foreignFare, 2) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="equipment-add-row">
                                    <span class="equipment-stepper-label">Qty</span>
                                    <div class="equipment-stepper">
                                        <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                                        <input type="number" min="1" step="1" value="1" data-qty-input aria-label="Quantity">
                                        <button type="button" data-step="+1" aria-label="Increase quantity">+</button>
                                    </div>
                                    <button type="button" class="equipment-add-btn" data-add-route><i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Add</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-note">No route schedules are available yet for this service.</div>
                @endif
            </section>

            <section id="policies-section" class="block" aria-label="Terms and policies" style="margin-top:12px;">
                <h2 class="block-title">Terms / Policies</h2>
                @if ($termsAndPolicies->isNotEmpty())
                    <ul class="list">
                        @foreach ($termsAndPolicies as $policy)
                            <li><i class="fa-solid fa-shield" aria-hidden="true"></i><span>{{ $policy }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <p class="description">Sea transport service policies will be published shortly.</p>
                @endif
            </section>
        </section>

        <aside class="booking-card" aria-label="Booking summary">
            <h2 class="block-title">Book Now</h2>
            <p class="booking-subtitle">{{ (string) ($property->name ?? 'Sea Transport') }}</p>

            <form method="POST" action="/category-booking/sea_transport/{{ $property->vendor_property_id ?? $property->id }}" id="seaTransportBookingForm">
                @csrf
                <input type="hidden" name="listing_category" value="sea_transport">
                <input type="hidden" name="route_code" id="bookingRouteCode" value="">
                <input type="hidden" name="boarding_point" id="bookingBoarding" value="">
                <input type="hidden" name="disembark_point" id="bookingDisembark" value="">
                <input type="hidden" name="guest_residency" id="bookingResidency" value="{{ $visitorResidency === 'local_resident' ? 'local_resident' : 'foreign_national' }}">
                <input type="hidden" name="return_route_code" id="bookingReturnRouteCode" value="">
                <input type="hidden" name="return_boarding_point" id="bookingReturnBoarding" value="">
                <input type="hidden" name="return_disembark_point" id="bookingReturnDisembark" value="">

                <div class="booking-field">
                    <label for="bookingTravelDate">Travel Date</label>
                    <input id="bookingTravelDate" type="date" name="travel_date" min="{{ date('Y-m-d') }}" required>
                </div>

                <div class="booking-field">
                    <label for="bookingAdults">Passengers</label>
                    <input id="bookingAdults" type="number" name="adults" min="1" step="1" value="1" required>
                </div>

                <div class="booking-field">
                    <label for="bookingResidencySelect">Guest Type</label>
                    <select id="bookingResidencySelect">
                        <option value="local_resident" {{ $visitorResidency === 'local_resident' ? 'selected' : '' }}>Local</option>
                        <option value="foreign_national" {{ $visitorResidency !== 'local_resident' ? 'selected' : '' }}>Foreign</option>
                    </select>
                </div>

                <div class="booking-field">
                    <label for="bookingTripType">Trip Type</label>
                    <select id="bookingTripType" name="trip_type">
                        <option value="one_way" selected>One-way</option>
                        <option value="round_trip">Round-trip</option>
                    </select>
                </div>

                <div class="booking-field" id="bookingReturnRouteWrap" style="display:none;">
                    <label for="bookingReturnRouteSelect">Return Route</label>
                    <select id="bookingReturnRouteSelect">
                        <option value="">Select return route</option>
                    </select>
                </div>

                <div class="booking-field">
                    <label>Your Order</label>
                    <div id="bookingOrderBox" class="booking-order-box is-empty">
                        <div>
                            <i class="fa-solid fa-basket-shopping" aria-hidden="true"></i><br>
                            Add route from the list on the left
                        </div>
                    </div>
                </div>

                <div class="booking-total">
                    <span>Order Total</span>
                    <strong id="bookingOrderTotal">{{ $visitorResidency === 'local_resident' ? 'MVR' : 'USD' }} 0.00</strong>
                </div>
                <p class="booking-note"><i class="fa-solid fa-info-circle" aria-hidden="true"></i> Final pricing follows selected guest type and may be adjusted at checkout.</p>

                <div class="booking-field">
                    <label for="serviceNotes">Additional Request (Optional)</label>
                    <textarea id="serviceNotes" name="service_notes" placeholder="Any dietary, timing, or service request?"></textarea>
                </div>

                <button class="booking-btn" id="bookingSubmitBtn" type="submit" disabled><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Book Now</button>
            </form>
        </aside>
    </div>

    <section class="block" aria-label="Similar services" style="margin-top:12px;">
        <h2 class="block-title">Similar Sea Transport Nearby</h2>
        <p class="description" style="margin-top:0;">Explore similar services from the same area and compare availability and inclusions before checkout.</p>
        <a class="booking-btn" href="/catalog/sea-transport" style="width:fit-content;">Browse Sea Transport listings</a>
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

        const bookingSubmitBtn = document.getElementById('bookingSubmitBtn');
        const bookingOrderBox = document.getElementById('bookingOrderBox');
        const bookingOrderTotal = document.getElementById('bookingOrderTotal');
        const bookingRouteCode = document.getElementById('bookingRouteCode');
        const bookingBoarding = document.getElementById('bookingBoarding');
        const bookingDisembark = document.getElementById('bookingDisembark');
        const bookingReturnRouteCode = document.getElementById('bookingReturnRouteCode');
        const bookingReturnBoarding = document.getElementById('bookingReturnBoarding');
        const bookingReturnDisembark = document.getElementById('bookingReturnDisembark');
        const bookingAdults = document.getElementById('bookingAdults');
        const bookingResidency = document.getElementById('bookingResidency');
        const bookingResidencySelect = document.getElementById('bookingResidencySelect');
        const bookingTripType = document.getElementById('bookingTripType');
        const bookingReturnRouteWrap = document.getElementById('bookingReturnRouteWrap');
        const bookingReturnRouteSelect = document.getElementById('bookingReturnRouteSelect');
        const routeCards = Array.from(document.querySelectorAll('[data-route-card]'));
        let selectedRoute = null;
        let selectedReturnRoute = null;
        const selectedResidency = function () {
            if (bookingResidencySelect && bookingResidencySelect.value) {
                return bookingResidencySelect.value;
            }
            return bookingResidency && bookingResidency.value ? bookingResidency.value : 'foreign_national';
        };

        const defaultDisplayCurrency = function () {
            return selectedResidency() === 'local_resident' ? 'MVR' : 'USD';
        };

        const formatMoney = function (amount, currency) {
            return currency + ' ' + (amount || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        const resolveUnitPrice = function (card) {
            const localFare = parseFloat(card.dataset.localFare || '0') || 0;
            const foreignFare = parseFloat(card.dataset.foreignFare || '0') || 0;
            const fallbackFare = parseFloat(card.dataset.fallbackFare || '0') || 0;
            const residency = selectedResidency() === 'local_resident' ? 'local' : 'foreign';

            if (residency === 'local' && localFare > 0) {
                return { amount: localFare, currency: 'MVR' };
            }
            if (residency === 'foreign' && foreignFare > 0) {
                return { amount: foreignFare, currency: 'USD' };
            }
            if (localFare > 0) {
                return { amount: localFare, currency: 'MVR' };
            }
            if (foreignFare > 0) {
                return { amount: foreignFare, currency: 'USD' };
            }
            return { amount: fallbackFare, currency: 'MVR' };
        };

        const routeKeyFromCard = function (card) {
            if (!card) {
                return '';
            }
            const code = String(card.dataset.routeCode || '').trim();
            const boarding = String(card.dataset.boarding || '').trim();
            const disembark = String(card.dataset.disembark || '').trim();
            return [code, boarding, disembark].join('||');
        };

        const routeLabelFromCard = function (card) {
            if (!card) {
                return 'Route';
            }
            const name = (card.querySelector('.route-name') || { textContent: 'Route' }).textContent.trim();
            const code = String(card.dataset.routeCode || '').trim();
            return code !== '' ? `${name} (${code})` : name;
        };

        const toRouteSelection = function (card, qty) {
            if (!card) {
                return null;
            }
            const price = resolveUnitPrice(card);
            return {
                key: routeKeyFromCard(card),
                code: card.dataset.routeCode || '',
                boarding: card.dataset.boarding || '',
                disembark: card.dataset.disembark || '',
                qty: qty,
                unitPrice: price.amount,
                currency: price.currency,
                name: routeLabelFromCard(card),
                card: card,
            };
        };

        const findReturnCandidate = function (outbound) {
            if (!outbound || !outbound.card) {
                return null;
            }

            const outboundBoarding = String(outbound.card.dataset.boarding || '').trim();
            const outboundDisembark = String(outbound.card.dataset.disembark || '').trim();
            const reverse = routeCards.find((card) => {
                const cardBoarding = String(card.dataset.boarding || '').trim();
                const cardDisembark = String(card.dataset.disembark || '').trim();
                return cardBoarding === outboundDisembark && cardDisembark === outboundBoarding;
            });

            if (reverse) {
                return reverse;
            }

            return routeCards.find((card) => routeKeyFromCard(card) !== outbound.key) || null;
        };

        const populateReturnRoutes = function () {
            if (!bookingReturnRouteSelect) {
                return;
            }

            bookingReturnRouteSelect.innerHTML = '<option value="">Select return route</option>';
            if (!selectedRoute) {
                return;
            }

            routeCards.forEach((card) => {
                const key = routeKeyFromCard(card);
                if (key === '' || key === selectedRoute.key) {
                    return;
                }

                const option = document.createElement('option');
                option.value = key;
                option.textContent = routeLabelFromCard(card);
                bookingReturnRouteSelect.appendChild(option);
            });
        };

        const syncReturnHiddenFields = function () {
            if (!bookingReturnRouteCode || !bookingReturnBoarding || !bookingReturnDisembark) {
                return;
            }

            if (selectedReturnRoute) {
                bookingReturnRouteCode.value = selectedReturnRoute.code;
                bookingReturnBoarding.value = selectedReturnRoute.boarding;
                bookingReturnDisembark.value = selectedReturnRoute.disembark;
                return;
            }

            bookingReturnRouteCode.value = '';
            bookingReturnBoarding.value = '';
            bookingReturnDisembark.value = '';
        };

        const isRoundTrip = function () {
            return bookingTripType && bookingTripType.value === 'round_trip';
        };

        const renderOrder = function () {
            if (!selectedRoute) {
                bookingOrderBox.classList.add('is-empty');
                bookingOrderBox.innerHTML = '<div><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i><br>Add route from the list on the left</div>';
                bookingOrderTotal.textContent = defaultDisplayCurrency() + ' 0.00';
                if (bookingSubmitBtn) bookingSubmitBtn.disabled = true;
                syncReturnHiddenFields();
                return;
            }

            const qty = parseInt(bookingAdults && bookingAdults.value ? bookingAdults.value : String(selectedRoute.qty), 10) || 1;
            const outboundTotal = selectedRoute.unitPrice * qty;
            const tripModeRound = isRoundTrip();

            if (tripModeRound && !selectedReturnRoute) {
                bookingOrderBox.classList.remove('is-empty');
                bookingOrderBox.innerHTML = '<div><i class="fa-solid fa-rotate" aria-hidden="true"></i><br>Select return route to continue</div>';
                bookingOrderTotal.textContent = formatMoney(outboundTotal, selectedRoute.currency);
                if (bookingSubmitBtn) bookingSubmitBtn.disabled = true;
                syncReturnHiddenFields();
                return;
            }

            let total = outboundTotal;
            if (tripModeRound && selectedReturnRoute) {
                total += selectedReturnRoute.unitPrice * qty;
            }

            bookingOrderBox.classList.remove('is-empty');
            let orderHtml =
                '<div class="order-row">'
                + '<div>'
                + '<div class="order-name">Outbound: ' + selectedRoute.name + '</div>'
                + '<div class="order-sub">' + qty + ' passenger' + (qty > 1 ? 's' : '') + '</div>'
                + '</div>'
                + '<div class="order-price">' + formatMoney(outboundTotal, selectedRoute.currency) + '</div>'
                + '</div>';

            if (tripModeRound && selectedReturnRoute) {
                const returnTotal = selectedReturnRoute.unitPrice * qty;
                orderHtml +=
                    '<div class="order-row">'
                    + '<div>'
                    + '<div class="order-name">Return: ' + selectedReturnRoute.name + '</div>'
                    + '<div class="order-sub">' + qty + ' passenger' + (qty > 1 ? 's' : '') + '</div>'
                    + '</div>'
                    + '<div class="order-price">' + formatMoney(returnTotal, selectedReturnRoute.currency) + '</div>'
                    + '</div>';
            }

            bookingOrderBox.innerHTML = orderHtml;

            bookingOrderTotal.textContent = formatMoney(total, selectedRoute.currency);
            if (bookingSubmitBtn) bookingSubmitBtn.disabled = false;
            syncReturnHiddenFields();
        };

        routeCards.forEach(function (card) {
            const qtyInput = card.querySelector('[data-qty-input]');
            card.querySelectorAll('[data-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const step = parseInt(btn.getAttribute('data-step'), 10) || 0;
                    const current = parseInt(qtyInput.value || '1', 10) || 1;
                    qtyInput.value = Math.max(1, current + step);
                });
            });

            const addBtn = card.querySelector('[data-add-route]');
            addBtn.addEventListener('click', function () {
                const qty = parseInt(qtyInput.value || '1', 10) || 1;
                selectedRoute = toRouteSelection(card, qty);
                selectedReturnRoute = null;

                if (bookingRouteCode) bookingRouteCode.value = selectedRoute.code;
                if (bookingBoarding) bookingBoarding.value = selectedRoute.boarding;
                if (bookingDisembark) bookingDisembark.value = selectedRoute.disembark;
                if (bookingAdults) bookingAdults.value = selectedRoute.qty;
                if (bookingResidency) bookingResidency.value = selectedResidency();

                populateReturnRoutes();
                if (isRoundTrip()) {
                    const candidate = findReturnCandidate(selectedRoute);
                    if (candidate) {
                        selectedReturnRoute = toRouteSelection(candidate, qty);
                        if (bookingReturnRouteSelect && selectedReturnRoute) {
                            bookingReturnRouteSelect.value = selectedReturnRoute.key;
                        }
                    }
                }

                renderOrder();
            });
        });

        if (bookingAdults) {
            bookingAdults.addEventListener('change', renderOrder);
        }

        if (bookingResidencySelect) {
            bookingResidencySelect.addEventListener('change', function () {
                if (bookingResidency) {
                    bookingResidency.value = bookingResidencySelect.value;
                }

                if (selectedRoute) {
                    selectedRoute = toRouteSelection(selectedRoute.card, parseInt(bookingAdults?.value || '1', 10) || 1);
                }
                if (selectedReturnRoute) {
                    selectedReturnRoute = toRouteSelection(selectedReturnRoute.card, parseInt(bookingAdults?.value || '1', 10) || 1);
                }

                renderOrder();
            });
        }

        if (bookingTripType) {
            bookingTripType.addEventListener('change', function () {
                if (bookingReturnRouteWrap) {
                    bookingReturnRouteWrap.style.display = isRoundTrip() ? '' : 'none';
                }

                if (!isRoundTrip()) {
                    selectedReturnRoute = null;
                    if (bookingReturnRouteSelect) {
                        bookingReturnRouteSelect.value = '';
                    }
                    renderOrder();
                    return;
                }

                populateReturnRoutes();
                if (selectedRoute && !selectedReturnRoute) {
                    const candidate = findReturnCandidate(selectedRoute);
                    if (candidate) {
                        selectedReturnRoute = toRouteSelection(candidate, parseInt(bookingAdults?.value || '1', 10) || 1);
                        if (bookingReturnRouteSelect && selectedReturnRoute) {
                            bookingReturnRouteSelect.value = selectedReturnRoute.key;
                        }
                    }
                }
                renderOrder();
            });
        }

        if (bookingReturnRouteSelect) {
            bookingReturnRouteSelect.addEventListener('change', function () {
                const selectedKey = String(bookingReturnRouteSelect.value || '');
                if (selectedKey === '') {
                    selectedReturnRoute = null;
                    renderOrder();
                    return;
                }

                const card = routeCards.find((item) => routeKeyFromCard(item) === selectedKey) || null;
                selectedReturnRoute = card ? toRouteSelection(card, parseInt(bookingAdults?.value || '1', 10) || 1) : null;
                renderOrder();
            });
        }

        if (bookingReturnRouteWrap) {
            bookingReturnRouteWrap.style.display = isRoundTrip() ? '' : 'none';
        }
    });
</script>
</body>
</html>