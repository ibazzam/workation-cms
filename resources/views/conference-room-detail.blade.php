<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Conference Room') }} | Workation</title>
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
    $headerCategoryKey = 'conference-room';
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

    $description = trim((string) ($property->description ?? ''));
    $capacity = (int) ($listingDetails['capacity'] ?? 0);
    $imageUrl = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22600%22 viewBox=%220 0 1200 600%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 stop-color=%230f6179%22/%3E%3Cstop offset=%22100%25%22 stop-color=%231d7bb5%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%221200%22 height=%22600%22 fill=%22url(%23g)%22/%3E%3C/svg%3E";
    $gallery = !empty($galleryMedia) ? collect($galleryMedia)->filter()->values() : collect([$imageUrl]);

    $amenitiesRaw = [];
    foreach (['amenities', 'equipment', 'services', 'features', 'highlights'] as $key) {
        $val = $listingDetails[$key] ?? null;
        if (is_array($val)) {
            $amenitiesRaw = array_merge($amenitiesRaw, $val);
        } elseif (is_string($val) && trim($val) !== '') {
            $amenitiesRaw = array_merge($amenitiesRaw, preg_split('/[\r\n,]+/', $val) ?: []);
        }
    }
    $amenities = collect($amenitiesRaw)->map(static fn ($item) => trim((string) $item))->filter()->unique()->values();
    $minPrice = (float) ($minPrice ?? 0);
    $displayPrice = $minPrice > 0 ? ('MVR ' . number_format($minPrice, 0)) : 'Price on request';
@endphp

@include('partials.customer-uniform-header', [
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => $headerCategoryKey,
])

<main class="page">
    <nav class="breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">›</span>
        <a href="/catalog/conference_room">Conference Rooms</a>
        <span aria-hidden="true">›</span>
        <span>{{ (string) ($property->name ?? 'Conference Room') }}</span>
    </nav>

    <section class="section" aria-label="Gallery">
        <h2>Photo Gallery</h2>
        <div class="gallery-shell" data-gallery>
            <img id="galleryHero" class="gallery-hero" src="{{ $gallery->first() ?: $imageUrl }}" alt="Room image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $imageUrl }}';}">
            <div class="gallery-thumbs" role="list">
                @foreach ($gallery as $index => $image)
                    <button type="button" class="gallery-thumb{{ $loop->first ? ' is-active' : '' }}" data-src="{{ $image }}" aria-label="Image {{ $index + 1 }}">
                        <img src="{{ $image }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="if(!this.dataset.fb){this.dataset.fb='1';this.src='{{ $imageUrl }}';}">
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="property-summary-shell" aria-label="Summary">
        <div class="property-summary-main">
            <h1>{{ (string) ($property->name ?? 'Conference Room') }}</h1>
            <div class="property-summary-meta">
                <span><i class="fa-solid fa-object-group" aria-hidden="true"></i> Event & meeting space</span>
                @if ($capacity > 0)
                    <span><i class="fa-solid fa-users" aria-hidden="true"></i> Capacity: {{ $capacity }} guests</span>
                @endif
            </div>
            @if ($description !== '')
                <p style="margin:10px 0 0; color:#4b6578; line-height:1.5;">{{ \Illuminate\Support\Str::words($description, 70) }}</p>
            @endif
        </div>

        <aside class="property-summary-price">
            <span class="k">Starting from</span>
            <span class="v">{{ $displayPrice }}</span>
            <span class="sub">Per day booking</span>
            <a class="booking-btn" href="/category-booking/conference-room/{{ $property->vendor_property_id ?? $property->id }}"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Book Room</a>
        </aside>
    </section>

    <nav class="section-tabs">
        <a class="section-tab" href="#amenities-section">Equipment & Features</a>
        <a class="section-tab" href="#policies-section">Policies</a>
    </nav>

    <section id="amenities-section" class="section">
        <h2>Equipment & Features</h2>
        @if ($amenities->isNotEmpty())
            <div class="amenities-grid">
                @foreach ($amenities as $amenity)
                    <div class="amenity-item"><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $amenity }}</div>
                @endforeach
            </div>
        @else
            <div class="empty-note">Equipment and features will be published shortly.</div>
        @endif
    </section>

    <section id="policies-section" class="section">
        <h2>Booking Policies</h2>
        <div class="policies-grid">
            <article class="policy-card">
                <div class="policy-label">Advance Booking</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['booking_policy'] ?? 'Minimum 48 hours advance booking required.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Cancellation</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['cancellation_policy'] ?? 'Free cancellation up to 72 hours before event.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Setup & Cleanup</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['setup_policy'] ?? '1 hour setup and 30 minutes cleanup included.')) }}</div>
            </article>
            <article class="policy-card">
                <div class="policy-label">Catering & Outside Vendors</div>
                <div class="policy-value">{{ trim((string) ($listingDetails['catering_policy'] ?? 'In-house catering available or approved external vendors permitted.')) }}</div>
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
