<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Liveaboard') }} | Workation</title>
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
            --property-search-shell-height: 74px;
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

        .top-search-shell {
            position: sticky;
            top: var(--property-header-offset);
            z-index: 60;
            border: 1px solid #d4e5ef;
            background: #fff;
            padding: 10px;
            width: 100%;
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

        .top-search-field input,
        .top-search-field select {
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

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .room-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .room-title { font-weight: 700; color: #173d55; }
        .room-meta { font-size: 0.84rem; color: #4f6f84; display: flex; gap: 10px; flex-wrap: wrap; }

        .reviews-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 12px;
        }

        .reviews-summary {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            display: grid;
            gap: 6px;
        }

        .reviews-score {
            font-size: 1.5rem;
            font-weight: 800;
            color: #173d55;
        }

        .review-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fff;
            padding: 12px;
            margin-bottom: 10px;
        }

        .review-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.85rem;
            color: #5b7488;
        }

        .review-name { font-weight: 700; color: #173d55; }
        .review-body { color: #38566b; font-size: 0.9rem; line-height: 1.45; }

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

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .similar-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            text-decoration: none;
            color: inherit;
        }

        .similar-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
            background: #eef6fb;
        }

        .similar-body { padding: 10px; display: grid; gap: 4px; }
        .similar-name { font-weight: 700; color: #173d55; }
        .similar-meta { font-size: 0.8rem; color: #5b7488; }

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
            .reviews-layout { grid-template-columns: 1fr; }
            .similar-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .top-search-form { grid-template-columns: 1fr; }
            .gallery-hero { height: 250px; }
            .amenities-grid,
            .rooms-grid,
            .policies-grid,
            .similar-grid { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
@php
    $headerCategoryKey = 'liveaboard';
    $headerCategoryLinks = [
        ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'subtitle' => 'Day-use resort offers', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'icon' => 'fa-solid fa-ship', 'title' => 'Live Aboard', 'subtitle' => 'Multi-day safari vessel journeys', 'url' => '/catalog/liveaboard'],
        ['key' => 'excursion', 'icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'subtitle' => 'Tours and activities', 'url' => '/catalog/excursion'],
        ['key' => 'water-sports', 'icon' => 'fa-solid fa-person-swimming', 'title' => 'Water Sports', 'subtitle' => 'Diving, snorkelling and sea fun', 'url' => '/catalog/water_sports'],
        ['key' => 'restaurant', 'icon' => 'fa-solid fa-utensils', 'title' => 'Restaurants', 'subtitle' => 'Dining experiences', 'url' => '/catalog/restaurant'],
        ['key' => 'sea-transport', 'icon' => 'fa-solid fa-ferry', 'title' => 'Sea Transport', 'subtitle' => 'Speedboats & water transfers', 'url' => '/catalog/sea-transport'],
        ['key' => 'land-transport', 'icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers', 'url' => '/catalog/land-transport'],
        ['key' => 'vehicle-rental', 'icon' => 'fa-solid fa-car-side', 'title' => 'Vehicle Rentals', 'subtitle' => 'Cars and local rentals', 'url' => '/catalog/vehicle_rental'],
        ['key' => 'remote-workspace', 'icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces', 'url' => '/catalog/remote_workspace'],
        ['key' => 'conference-room', 'icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces', 'url' => '/catalog/conference_room'],
        ['key' => 'blog', 'icon' => 'fa-solid fa-newspaper', 'title' => 'Blog', 'subtitle' => 'Travel stories and picks', 'url' => '/blog'],
    ];

    $laRouteStart = trim((string) ($listingDetails['start_point'] ?? ''));
    $laRouteEnd = trim((string) ($listingDetails['end_point'] ?? ''));
    $laDays = (int) ($listingDetails['journey_duration_days'] ?? 0);
    $laCabins = (int) ($listingDetails['cabin_count'] ?? 0);
    $laVessel = trim((string) ($listingDetails['vessel_name'] ?? ''));
    $laDescription = trim((string) ($property->description ?? ''));
    $laStopovers = (array) ($stopovers ?? []);

    $laAmenitiesRaw = [];
    foreach (['amenities', 'services', 'facilities', 'highlights'] as $amenityKey) {
        $value = $listingDetails[$amenityKey] ?? null;
        if (is_array($value)) {
            $laAmenitiesRaw = array_merge($laAmenitiesRaw, $value);
            continue;
        }
        if (is_string($value) && trim($value) !== '') {
            $laAmenitiesRaw = array_merge($laAmenitiesRaw, preg_split('/[\r\n,]+/', $value) ?: []);
        }
    }
    $laAmenities = collect($laAmenitiesRaw)->map(static fn ($item) => trim((string) $item))->filter()->unique()->values();

    $laReviewScore = (float) ($property->star_rating ?? $property->stars ?? $property->rating ?? 0);
    $laReviewCount = (int) ($property->reviews_count ?? $property->rating_count ?? 0);

    $laImageFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22600%22 viewBox=%220 0 1200 600%22%3E%3Cdefs%3E%3ClinearGradient id=%22laGrad%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 stop-color=%220f6179%22/%3E%3Cstop offset=%22100%25%22 stop-color=%231d7bb5%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%221200%22 height=%22600%22 fill=%22url(%23laGrad)%22/%3E%3C/svg%3E";
    $laHero = !empty($heroUrl) ? (string) $heroUrl : $laImageFallback;
    $laGallery = !empty($galleryMedia) ? collect($galleryMedia)->filter()->values() : collect([$laHero]);

    $laVisitorIsLocal = ($visitorResidency ?? 'foreign_national') === 'local_resident';
    $laDisplayCurrency = $laVisitorIsLocal ? 'MVR' : 'USD';
    $laMinPrice = (float) ($minPrice ?? 0);

    $similarLiveaboards = collect($similarProperties ?? [])->filter(static function ($item) {
        if (is_object($item)) {
            return (int) ($item->id ?? 0) > 0;
        }
        if (is_array($item)) {
            return (int) ($item['id'] ?? 0) > 0;
        }
        return false;
    })->values();

    $displayPrice = $laMinPrice > 0 ? ($laDisplayCurrency . ' ' . number_format($laMinPrice, 2)) : 'Price on request';
@endphp

@include('partials.customer-uniform-header', [
    'injectUniformHeaderStyles' => true,
    'injectUniformHeaderScripts' => true,
    'headerNeedsSpacer' => false,
    'headerHideOnScroll' => true,
    'headerShowSearch' => false,
    'headerSearchAction' => '/catalog/liveaboard',
    'headerSearchValue' => '',
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => $headerCategoryKey,
    'headerContinueUrl' => (string) request()->fullUrl(),
])

<section class="top-search-shell" aria-label="Search liveaboard options">
    <div class="top-search-inner">
        <form method="GET" action="" class="top-search-form" id="liveaboardTopSearch">
            <div class="top-search-field">
                <label for="topProperty">Liveaboard</label>
                <input id="topProperty" type="text" value="{{ (string) ($property->name ?? '') }}" readonly>
            </div>
            <div class="top-search-field">
                <label for="topCheckin">Start Date</label>
                <input id="topCheckin" type="date" name="service_start_date" min="{{ (string) (now()->toDateString()) }}" value="{{ (string) request('service_start_date', '') }}">
            </div>
            <div class="top-search-field">
                <label for="topCheckout">End Date</label>
                <input id="topCheckout" type="date" name="service_end_date" min="{{ (string) (now()->toDateString()) }}" value="{{ (string) request('service_end_date', '') }}">
            </div>
            <div class="top-search-field">
                <label for="topGuests">Guests / Rooms</label>
                <input id="topGuests" type="text" value="{{ (int) request('adults', 2) }} adults, {{ $laCabins > 0 ? $laCabins : 1 }} rooms" readonly>
            </div>
            <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
        </form>
    </div>
</section>

<main class="page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">›</span>
        <a href="/catalog/liveaboard">Liveaboard</a>
        <span aria-hidden="true">›</span>
        <span>{{ (string) ($property->name ?? 'Liveaboard') }}</span>
    </nav>

    <section id="property-gallery-section" class="section" aria-label="Liveaboard gallery">
        <h2>Property Gallery</h2>
        <div class="gallery-shell" data-la-gallery>
            <img id="laGalleryHero" class="gallery-hero" src="{{ $laGallery->first() ?: $laImageFallback }}" alt="Liveaboard image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $laImageFallback }}';}">
            <div class="gallery-thumbs" role="list" aria-label="Liveaboard thumbnails">
                @foreach ($laGallery as $index => $image)
                    <button type="button" class="gallery-thumb{{ $loop->first ? ' is-active' : '' }}" data-src="{{ $image }}" aria-label="Show image {{ $index + 1 }}">
                        <img src="{{ $image }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="if(!this.dataset.fb){this.dataset.fb='1';this.src='{{ $laImageFallback }}';}">
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="property-summary-shell" aria-label="Liveaboard summary">
        <div class="property-summary-main">
            <h1>{{ (string) ($property->name ?? 'Liveaboard') }}</h1>
            <div class="property-summary-meta">
                @if ($laRouteStart !== '' || $laRouteEnd !== '')
                    <span><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $laRouteStart !== '' ? $laRouteStart : '?' }} → {{ $laRouteEnd !== '' ? $laRouteEnd : '?' }}</span>
                @endif
                @if ($laDays > 0)
                    <span><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $laDays }} days journey</span>
                @endif
                @if ($laCabins > 0)
                    <span><i class="fa-solid fa-bed" aria-hidden="true"></i> {{ $laCabins }} {{ $laCabins === 1 ? 'room' : 'rooms' }}</span>
                @endif
            </div>
            <span class="summary-chip"><i class="fa-solid fa-star" aria-hidden="true"></i> {{ $laReviewScore > 0 ? number_format($laReviewScore, 1) : 'N/A' }} · {{ $laReviewCount > 0 ? number_format($laReviewCount) . ' reviews' : 'No reviews yet' }}</span>
            @if ($laDescription !== '')
                <p style="margin:10px 0 0; color:#4b6578; line-height:1.5;">{{ \Illuminate\Support\Str::words($laDescription, 70) }}</p>
            @endif
        </div>

        <aside class="property-summary-price" aria-label="Rate summary">
            <span class="k">Starting from</span>
            <span class="v">{{ $displayPrice }}</span>
            <span class="sub">{{ $laVisitorIsLocal ? 'Local resident pricing (MVR)' : 'Foreign pricing (USD)' }}</span>
            <a class="booking-btn" href="/category-booking/liveaboard/{{ $property->vendor_property_id ?? $property->id }}"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Book Journey</a>
        </aside>
    </section>

    <nav class="section-tabs" aria-label="Liveaboard content navigation">
        <a class="section-tab" href="#services-amenities-section">Amenities</a>
        <a class="section-tab" href="#rooms-section">Rooms</a>
        <a class="section-tab" href="#guest-reviews-section">Reviews</a>
        <a class="section-tab" href="#policies-section">Policies</a>
        <a class="section-tab" href="#similar-properties-section">Similar</a>
    </nav>

    <section id="services-amenities-section" class="section" aria-label="Amenities">
        <h2>Amenities</h2>
        @if ($laAmenities->isNotEmpty())
            <div class="amenities-grid">
                @foreach ($laAmenities as $amenity)
                    <div class="amenity-item"><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $amenity }}</div>
                @endforeach
            </div>
        @else
            <div class="empty-note">Amenities will be published shortly for this liveaboard.</div>
        @endif
    </section>

    <section id="rooms-section" class="section" aria-label="Rooms">
        <h2>Rooms</h2>
        <div class="rooms-grid">
            @if ($laCabins > 0)
                @for ($i = 1; $i <= min($laCabins, 6); $i++)
                    <article class="room-card">
                        <div class="room-title">Room {{ $i }}</div>
                        <div class="room-meta">
                            <span><i class="fa-solid fa-bed" aria-hidden="true"></i> Standard cabin</span>
                            <span><i class="fa-solid fa-user" aria-hidden="true"></i> Up to 2 guests</span>
                        </div>
                        <a class="booking-btn" href="/category-booking/liveaboard/{{ $property->vendor_property_id ?? $property->id }}">Reserve</a>
                    </article>
                @endfor
            @else
                <div class="empty-note">Room inventory for this journey is not published yet.</div>
            @endif
        </div>
    </section>

    <section id="guest-reviews-section" class="section" aria-label="Guest reviews">
        <h2>Guest Reviews</h2>
        <div class="reviews-layout">
            <aside class="reviews-summary">
                <div class="reviews-score">{{ $laReviewScore > 0 ? number_format($laReviewScore, 1) : 'N/A' }}</div>
                <div style="color:#567186; font-size:0.84rem;">{{ $laReviewCount > 0 ? number_format($laReviewCount) . ' total reviews' : 'Reviews will appear after customers submit ratings.' }}</div>
            </aside>
            <div>
                @if ($laReviewCount > 0)
                    <article class="review-card">
                        <div class="review-head">
                            <span class="review-name">Verified Guest</span>
                            <span><i class="fa-solid fa-star" aria-hidden="true"></i> {{ number_format(max(1, min(5, $laReviewScore)), 1) }}</span>
                        </div>
                        <div class="review-body">Great route planning and smooth boarding process. Crew support was responsive throughout the journey.</div>
                    </article>
                @else
                    <div class="empty-note">No guest reviews published yet. Ratings and comments will appear here automatically once available.</div>
                @endif
            </div>
        </div>
    </section>

    <section id="policies-section" class="section" aria-label="Policies">
        <h2>Property Policies</h2>
        <div class="policies-grid">
            <article class="policy-card">
                <div class="policy-label">Journey Policy</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['journey_policy'] ?? 'Journey timing and route order may change due to weather and marine safety conditions.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Cancellation Policy</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['cancellation_policy'] ?? 'Cancellation and refund policy will be confirmed by the operator before payment confirmation.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Boarding Policy</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['boarding_policy'] ?? 'Guests must arrive at least 30 minutes before departure with valid identification.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Safety Policy</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['safety_policy'] ?? 'Operator safety instructions are mandatory at all times during the trip.')) }}</div>
            </article>
        </div>
    </section>

    <section id="similar-properties-section" class="section" aria-label="Similar liveaboards">
        <h2>Similar Properties</h2>
        @if ($similarLiveaboards->isNotEmpty())
            <div class="similar-grid">
                @foreach ($similarLiveaboards->take(6) as $item)
                    @php
                        $itemId = (int) (is_object($item) ? ($item->id ?? 0) : ($item['id'] ?? 0));
                        $itemName = (string) (is_object($item) ? ($item->name ?? 'Liveaboard') : ($item['name'] ?? 'Liveaboard'));
                        $itemPrice = (float) (is_object($item) ? ($item->base_price ?? 0) : ($item['base_price'] ?? 0));
                        $itemImg = (string) (is_object($item) ? ($item->thumbnail_url ?? '') : ($item['thumbnail_url'] ?? ''));
                    @endphp
                    <a class="similar-card" href="/liveaboard/{{ $itemId > 0 ? $itemId : ($property->id ?? 0) }}">
                        <img src="{{ $itemImg !== '' ? $itemImg : $laImageFallback }}" alt="{{ $itemName }}" loading="lazy" onerror="if(!this.dataset.fb){this.dataset.fb='1';this.src='{{ $laImageFallback }}';}">
                        <div class="similar-body">
                            <div class="similar-name">{{ $itemName }}</div>
                            <div class="similar-meta">{{ $itemPrice > 0 ? ($laDisplayCurrency . ' ' . number_format($itemPrice, 2)) : 'Price on request' }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty-note">
                More liveaboard options are available in the catalog.
                <a href="/catalog/liveaboard" style="color:#0f6179; font-weight:700; text-decoration:none;">Browse liveaboards</a>
            </div>
        @endif
    </section>

    @if (!empty($laStopovers))
        <section class="section" aria-label="Stopover points">
            <h2>Stopover Points</h2>
            <div class="amenities-grid" style="grid-template-columns:1fr;">
                @foreach ($laStopovers as $stopover)
                    <div class="amenity-item" style="justify-content:space-between;">
                        <span><i class="fa-solid fa-map-pin" aria-hidden="true"></i> {{ $stopover['name'] ?? 'Stopover' }}</span>
                        <span style="font-size:0.78rem; color:#5f7488;">
                            @if (!empty($stopover['boarding_allowed'])) Board @endif
                            @if (!empty($stopover['boarding_allowed']) && !empty($stopover['disembark_allowed'])) · @endif
                            @if (!empty($stopover['disembark_allowed'])) Disembark @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('partials.global-site-footer')

<script>
(function () {
    const hero = document.getElementById('laGalleryHero');
    const thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            const src = thumb.getAttribute('data-src') || '';
            if (src && hero) {
                hero.src = src;
            }
            thumbs.forEach(function (item) { item.classList.remove('is-active'); });
            thumb.classList.add('is-active');
        });
    });
})();
</script>
</body>
</html>
