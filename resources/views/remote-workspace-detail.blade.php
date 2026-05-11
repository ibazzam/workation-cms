<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Remote Workspace') }} | Workation</title>
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
            --brand-soft: #edf6fc;
            --brand-soft-2: #f7fbff;
            --brand-line: #d4e5ef;
            --brand-ink: #1f4f6b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        :root {
            --property-header-offset: 74px;
        }

        body.is-header-hidden {
            --property-header-offset: 0px;
        }

        .page { width: min(1180px, calc(100% - 24px)); margin: 14px auto 28px; }

        .top-search-shell {
            position: sticky;
            top: var(--property-header-offset);
            z-index: 60;
            border: 1px solid #d4e5ef;
            border-radius: 0;
            background: #ffffff;
            padding: 10px;
            box-shadow: none;
            margin-bottom: 0;
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

        .section {
            border: none;
            border-top: 1px solid #f0f4f8;
            border-radius: 0;
            background: transparent;
            padding: 0;
            margin-top: 20px;
        }

        .section:first-of-type {
            border-top: none;
            margin-top: 0;
        }

        .section h2 {
            margin: 0;
            font-size: 1.04rem;
            padding-top: 20px;
            padding-bottom: 14px;
        }

        .section:first-of-type h2 {
            padding-top: 0;
        }

        .gallery-shell {
            margin-top: 14px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 260px;
            gap: 10px;
            align-items: start;
        }

        .gallery-banner-wrap {
            border-radius: 13px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
            min-height: 360px;
        }

        .gallery-banner {
            width: 100%;
            height: 100%;
            min-height: 360px;
            object-fit: cover;
            display: block;
        }

        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            max-height: 360px;
            overflow: auto;
            padding-right: 2px;
        }

        .property-summary-shell {
            margin-top: 12px;
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(220px, 0.7fr);
            gap: 14px;
            align-items: start;
        }

        .property-summary-main {
            display: grid;
            gap: 8px;
        }

        .property-summary-title {
            margin: 0;
            font-size: clamp(1.25rem, 2vw, 1.65rem);
            color: #1a3347;
        }

        .property-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #c6dded;
            background: #eef7ff;
            color: #1f4f6b;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .property-summary-address {
            color: #3a5568;
            font-size: 0.9rem;
        }

        .property-summary-address a {
            color: #0f6179;
            text-decoration: none;
            font-weight: 600;
        }

        .property-summary-address a:hover { text-decoration: underline; }

        .property-summary-price {
            border: 1px solid #d4e5ef;
            border-radius: 12px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 8px;
            justify-items: end;
            text-align: right;
        }

        .property-summary-price .k {
            color: #5f7488;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
        }

        .property-summary-price .v {
            color: #17344a;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .property-summary-price .sub {
            color: #4c6477;
            font-size: 0.76rem;
        }

        .property-summary-price .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #0f6179;
            background: #0f6179;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 700;
            padding: 9px 14px;
            min-width: 120px;
        }

        .property-summary-price .cta:hover { filter: brightness(1.04); }

        .gallery-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
            padding: 0;
            margin: 0;
            cursor: pointer;
            min-height: 78px;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            min-height: 78px;
            object-fit: cover;
            display: block;
        }

        .gallery-thumb.is-active {
            border-color: #1d848c;
            box-shadow: 0 0 0 2px rgba(29, 132, 140, 0.25);
        }

        .amenities-board {
            margin-top: 10px;
            border: 1px solid #d7e6f0;
            border-radius: 14px;
            background: linear-gradient(160deg, #f7fbff 0%, #f1f8fe 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .amenities-head {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #173f57;
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.01em;
        }

        .amenities-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            align-items: start;
        }

        .amenity-group {
            border: 1px solid #d2e3ef;
            border-radius: 12px;
            background: #ffffff;
            padding: 11px;
            display: grid;
            gap: 9px;
            align-content: start;
            align-self: start;
            box-shadow: 0 5px 14px rgba(15, 68, 97, 0.06);
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        .amenity-group:hover {
            border-color: #b9d5e6;
            box-shadow: 0 8px 20px rgba(15, 68, 97, 0.1);
        }

        .amenity-group-title {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1f4f6b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding-bottom: 7px;
            border-bottom: 1px dashed #d6e6f2;
        }

        .amenity-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .amenity-list li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2f556e;
            font-size: 0.78rem;
            line-height: 1.25;
            border: 1px solid #d6e7f2;
            border-radius: 999px;
            background: #f7fbff;
            padding: 5px 9px;
            max-width: 100%;
        }

        .facility-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 16px;
            width: 16px;
            height: 16px;
            font-size: 0.72rem;
            color: #1d5a7a;
            line-height: 1;
        }

        .features-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .feature-card {
            border: 1px solid #d6e5ef;
            border-radius: 14px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .feature-title {
            margin: 0;
            font-size: 0.95rem;
            color: #1f4f6b;
            font-weight: 700;
        }

        .feature-details {
            display: grid;
            gap: 6px;
            font-size: 0.8rem;
            color: #3a5568;
        }

        .detail-line {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0 0 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f4f8;
        }

        .section-tab {
            text-decoration: none;
            color: #1f4f6b;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }

        .section-tab:hover {
            color: #0f6179;
            border-bottom-color: #0f6179;
        }

        .empty-state {
            border: 1px dashed #c6dde5;
            border-radius: 12px;
            background: #f5f9fb;
            padding: 16px;
            color: #4a677a;
            font-size: 0.9rem;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .gallery-shell { grid-template-columns: 1fr; }
            .property-summary-shell { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .top-search-form { grid-template-columns: 1fr; }
            .gallery-thumbs { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .gallery-banner { min-height: 250px; }
            .gallery-banner-wrap { min-height: 250px; }
            .features-grid { grid-template-columns: 1fr; }
            .amenities-columns { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
@php
    $headerCategoryKey = 'remote-workspace';
    $headerCategoryLinks = [
        ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'icon' => 'fa-solid fa-ship', 'title' => 'Live Aboard', 'url' => '/catalog/liveaboard'],
        ['key' => 'excursion', 'icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'url' => '/catalog/excursion'],
        ['key' => 'sea-transport', 'icon' => 'fa-solid fa-ferry', 'title' => 'Sea Transport', 'url' => '/catalog/sea-transport'],
        ['key' => 'land-transport', 'icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'url' => '/catalog/land-transport'],
        ['key' => 'vehicle-rental', 'icon' => 'fa-solid fa-car-side', 'title' => 'Vehicle Rentals', 'url' => '/catalog/vehicle_rental'],
        ['key' => 'remote-workspace', 'icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'url' => '/catalog/remote_workspace'],
        ['key' => 'conference-room', 'icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'url' => '/catalog/conference_room'],
    ];

    // Extract workspace data
    $location = trim((string) ($listingDetails['location'] ?? ''));
    $internetSpeed = trim((string) ($listingDetails['internet_speed'] ?? ''));
    $seatingCapacity = (int) ($listingDetails['seating_capacity'] ?? 0);
    $availabilityHours = trim((string) ($listingDetails['availability_hours'] ?? ''));
    $description = trim((string) ($property->description ?? ''));
    $rating = (float) ($property->star_rating ?? $property->stars ?? 0);
    $ratingCount = (int) ($property->reviews_count ?? 0);

    // Extract amenities
    $amenitiesRaw = [];
    foreach (['amenities', 'services', 'facilities', 'highlights'] as $amenityKey) {
        $value = $listingDetails[$amenityKey] ?? null;
        if (is_array($value)) {
            $amenitiesRaw = array_merge($amenitiesRaw, $value);
            continue;
        }
        if (is_string($value) && trim($value) !== '') {
            $amenitiesRaw = array_merge($amenitiesRaw, preg_split('/[\r\n,]+/', $value) ?: []);
        }
    }
    $amenities = collect($amenitiesRaw)->map(static fn ($item) => trim((string) $item))->filter()->unique()->values();

    // Pricing
    $minPrice = (float) ($minPrice ?? 0);
    $displayPrice = $minPrice > 0 ? number_format($minPrice, 0) : 'POA';

    // Gallery
    $galleryFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22420%22 viewBox=%220 0 900 420%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22420%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2228%22%3ERemote%20Workspace%20Image%3C%2Ftext%3E%3C%2Fsvg%3E";
    $gallery = !empty($galleryMedia) ? collect($galleryMedia)->filter()->values() : collect([$galleryFallback]);
    $initialBanner = $gallery->first() ?: $galleryFallback;

    $ratingStr = $rating > 0 ? number_format($rating, 1) : 'N/A';
    $ratingLabel = $ratingCount === 1 ? 'review' : 'reviews';
@endphp

@include('partials.customer-uniform-header', [
    'injectUniformHeaderStyles' => true,
    'injectUniformHeaderScripts' => true,
    'headerNeedsSpacer' => false,
    'headerHideOnScroll' => true,
    'headerShowSearch' => false,
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => $headerCategoryKey,
])

<section class="top-search-shell" aria-label="Search remote workspace options">
    <div class="top-search-inner">
        <form method="GET" action="" class="top-search-form">
            <div class="top-search-field">
                <label for="topWorkspace">Workspace</label>
                <input id="topWorkspace" type="text" value="{{ (string) ($property->name ?? '') }}" readonly>
            </div>
            <div class="top-search-field">
                <label for="topCheckIn">Check In</label>
                <input id="topCheckIn" type="date" name="check_in" min="{{ (string) now()->toDateString() }}">
            </div>
            <div class="top-search-field">
                <label for="topCheckOut">Check Out</label>
                <input id="topCheckOut" type="date" name="check_out" min="{{ (string) now()->toDateString() }}">
            </div>
            <div class="top-search-field">
                <label for="topDuration">Duration</label>
                <input id="topDuration" type="text" value="Flexible" readonly>
            </div>
            <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </div>
</section>

<main class="page">
    <section id="property-gallery-section" class="section" aria-label="Workspace gallery">
        <h2>Gallery</h2>
        <div class="gallery-shell" data-property-gallery>
            <div class="gallery-banner-wrap">
                <img id="propertyGalleryBanner" class="gallery-banner" src="{{ $initialBanner }}" alt="Remote workspace image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $galleryFallback }}';}">
            </div>
            <div class="gallery-thumbs" role="list" aria-label="Workspace thumbnails">
                @foreach ($gallery as $index => $media)
                    <button type="button" class="gallery-thumb{{ $loop->first ? ' is-active' : '' }}" data-banner-src="{{ $media }}" aria-label="Show image {{ $index + 1 }}">
                        <img src="{{ $media }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="if(!this.dataset.fallbackApplied){this.dataset.fallbackApplied='1';this.src='{{ $media }}';}else{this.onerror=null;this.src='{{ $galleryFallback }}';}">
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="property-summary-shell" aria-label="Workspace overview">
        <div class="property-summary-main">
            <span class="property-summary-stars" style="color: #f3a337; letter-spacing: 0.08em; font-size: 0.9rem;">
                <i class="fa-solid fa-star"></i> {{ $ratingStr }}
            </span>
            <h1 class="property-summary-title">{{ (string) ($property->name ?? 'Remote Workspace') }}</h1>
            <span class="property-type-badge"><i class="fa-solid fa-laptop"></i> Remote Workspace</span>
            <div class="property-summary-address">
                @if ($location)
                    <span><i class="fa-solid fa-location-dot"></i> {{ $location }}</span>
                    @if ($internetSpeed)
                        <span> · </span>
                        <span>{{ $internetSpeed }} Internet</span>
                    @endif
                @else
                    <span>Location and details coming soon.</span>
                @endif
            </div>
            <div class="property-summary-reviews">
                <span class="summary-rating-chip"><i class="fa-solid fa-star"></i> {{ $ratingStr }} · {{ $ratingCount }} {{ $ratingLabel }}</span>
            </div>
        </div>

        <aside class="property-summary-price" aria-label="Workspace pricing">
            <span class="k">Starting from</span>
            <span class="v">MVR {{ $displayPrice }}</span>
            <span class="sub">per day</span>
            <a class="cta" href="/category-booking/remote_workspace/{{ $property->vendor_property_id ?? $property->id }}"><i class="fa-solid fa-calendar-check"></i> Book Workspace</a>
        </aside>
    </section>

    <nav class="section-tabs" aria-label="Workspace content navigation">
        <a class="section-tab" href="#property-gallery-section">Photos</a>
        <a class="section-tab" href="#amenities-section">Amenities</a>
        <a class="section-tab" href="#features-section">Features</a>
        <a class="section-tab" href="#policies-section">Policies</a>
    </nav>

    <section id="amenities-section" class="section" aria-label="Workspace amenities">
        <h2>Amenities & Services</h2>
        @if ($amenities->isNotEmpty())
            <div class="amenities-board">
                <h3 class="amenities-head"><i class="fa-solid fa-sparkles"></i> What's Included</h3>
                <div class="amenities-columns">
                    @php
                        $grouped = $amenities->chunk(ceil($amenities->count() / 3));
                    @endphp
                    @foreach ($grouped as $group)
                        <div class="amenity-group">
                            <ul class="amenity-list">
                                @foreach ($group as $amenity)
                                    <li>
                                        <span class="facility-icon"><i class="fa-solid fa-check"></i></span>
                                        <span>{{ $amenity }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="empty-state">Amenities and services will be listed shortly.</div>
        @endif
    </section>

    <section id="features-section" class="section" aria-label="Workspace features">
        <h2>Features & Capacity</h2>
        <div class="features-grid">
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-wifi"></i> Internet Connectivity</h3>
                <div class="feature-details">
                    <div class="detail-line">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ $internetSpeed ?: 'High-speed internet available' }}</span>
                    </div>
                </div>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-chair"></i> Seating Capacity</h3>
                <div class="feature-details">
                    <div class="detail-line">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ $seatingCapacity > 0 ? $seatingCapacity . ' workstations' : 'Multiple workstations available' }}</span>
                    </div>
                </div>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-clock"></i> Availability</h3>
                <div class="feature-details">
                    <div class="detail-line">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ $availabilityHours ?: '24/7 access available' }}</span>
                    </div>
                </div>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-location-dot"></i> Location</h3>
                <div class="feature-details">
                    <div class="detail-line">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ $location ?: 'Prime workspace location' }}</span>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section id="policies-section" class="section" aria-label="Workspace policies">
        <h2>Policies & Information</h2>
        <div class="features-grid">
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-ban"></i> Cancellation</h3>
                <p class="detail-line">{{ trim((string) ($listingDetails['cancellation_policy'] ?? 'Flexible cancellation up to 24 hours before booking start time.')) }}</p>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-door-open"></i> Check-in</h3>
                <p class="detail-line">{{ trim((string) ($listingDetails['checkin_policy'] ?? 'Self-service check-in with digital access key.')) }}</p>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-shield"></i> House Rules</h3>
                <p class="detail-line">{{ trim((string) ($listingDetails['house_rules'] ?? 'Professional environment - please maintain workspace cleanliness and respect other members.')) }}</p>
            </article>
            <article class="feature-card">
                <h3 class="feature-title"><i class="fa-solid fa-headset"></i> Support</h3>
                <p class="detail-line">{{ trim((string) ($listingDetails['support_info'] ?? 'Dedicated support team available during business hours.')) }}</p>
            </article>
        </div>
    </section>
</main>

@include('partials.global-site-footer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const gallery = document.querySelector('[data-property-gallery]');
        if (!gallery) return;

        const banner = gallery.querySelector('#propertyGalleryBanner');
        const thumbs = gallery.querySelectorAll('.gallery-thumb');

        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                const newSrc = thumb.dataset.bannerSrc;
                thumbs.forEach(t => t.classList.remove('is-active'));
                thumb.classList.add('is-active');
                banner.src = newSrc;
            });
        });

        // Smooth scroll for section tabs
        document.querySelectorAll('.section-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const href = tab.getAttribute('href');
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    });
</script>
</body>
</html>
