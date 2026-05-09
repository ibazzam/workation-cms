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
            --bg: #ffffff;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --brand: #0f6179;
            --brand-strong: #0b4f66;
            --accent: #f3a337;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Outfit","Trebuchet MS",sans-serif; color: var(--ink); background: var(--bg); }
        a { color: inherit; text-decoration: none; }

        .st-hero { width: 100%; height: 320px; object-fit: cover; display: block; background: #c2d9e6; }
        .st-hero-placeholder { width: 100%; height: 320px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f6179, #1d7bb5); color: #fff; font-size: 3.5rem; }
        
        .st-gallery-strip {
            max-width: 1000px; margin: 14px auto 0; padding: 0 16px;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 8px;
        }
        .st-gallery-thumb { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; height: 72px; background: #edf4fb; cursor: pointer; }
        .st-gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .st-gallery-thumb.active { border: 2px solid var(--brand); }

        .st-main-container { max-width: 1000px; margin: 0 auto; padding: 0 16px 60px; display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
        .st-main-content { width: 100%; }
        .st-sidebar { width: 100%; }

        .st-breadcrumb { font-size: 0.78rem; color: var(--muted); margin: 20px 0 14px; }
        .st-breadcrumb a { color: var(--brand); text-decoration: none; }
        .st-breadcrumb a:hover { text-decoration: underline; }

        .st-header { margin-bottom: 18px; }
        .st-title { font-size: 1.65rem; font-weight: 800; color: var(--ink); margin: 0 0 6px; line-height: 1.2; }
        .st-location { font-size: 0.88rem; color: var(--muted); margin: 0 0 8px; }
        .st-description { font-size: 0.88rem; color: #4a6478; line-height: 1.6; margin: 0 0 14px; }

        .st-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .st-tag { display: inline-block; font-size: 0.75rem; background: #e6f0f7; color: #1d4b66; border-radius: 18px; padding: 5px 11px; font-weight: 600; }

        .st-share-label { font-size: 0.7rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); margin: 0 0 6px; }
        .st-share-buttons { display: flex; gap: 8px; }
        .st-share-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--line); background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--brand); transition: all 0.2s ease; }
        .st-share-btn:hover { background: #f0f6fb; border-color: var(--brand); }

        .st-section { margin: 28px 0 0; }
        .st-section-title { font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #4a6478; margin: 0 0 12px; padding-bottom: 10px; border-bottom: 2px solid var(--line); }

        .st-snapshot { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin: 16px 0; }
        .st-snapshot-card { border: 1px solid var(--line); border-radius: 10px; background: #fbfdff; padding: 12px 14px; }
        .st-snapshot-card h4 { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #1d4b66; margin: 0 0 8px; letter-spacing: 0.05em; }
        .st-snapshot-card ul { margin: 0; padding: 0; list-style: none; }
        .st-snapshot-card li { font-size: 0.82rem; color: #35576d; margin: 0 0 5px; padding-left: 18px; position: relative; }
        .st-snapshot-card li:before { content: "✓"; position: absolute; left: 0; color: var(--brand); font-weight: bold; }
        .st-snapshot-card li:last-child { margin-bottom: 0; }

        .st-snapshot-card.info h4 { color: #0f6179; }
        .st-snapshot-card.info li:before { content: "◉"; }

        .st-service-desc { font-size: 0.88rem; color: #4a6478; line-height: 1.7; margin: 16px 0 0; }

        .st-terms-list { margin: 14px 0 0; padding: 0; list-style: none; }
        .st-terms-list li { font-size: 0.82rem; color: #35576d; margin: 0 0 8px; padding-left: 20px; position: relative; }
        .st-terms-list li:before { content: "▸"; position: absolute; left: 0; color: var(--brand); }

        .st-equipment-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin: 16px 0 0; }
        .st-ticket-card {
            border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: 16px;
            display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 14px; align-items: start;
        }
        .st-ticket-route { font-size: 0.95rem; font-weight: 700; color: var(--ink); margin: 0 0 4px; }
        .st-ticket-meta { font-size: 0.78rem; color: var(--muted); margin: 0; }
        .st-ticket-meta-item { margin: 4px 0 0; }
        .st-ticket-safety { background: #fffbf0; border-left: 3px solid #f3a337; padding: 8px 12px; border-radius: 4px; margin: 10px 0 0; }
        .st-ticket-safety-title { font-size: 0.72rem; font-weight: 700; color: #c97706; text-transform: uppercase; letter-spacing: 0.05em; }
        .st-ticket-safety-content { font-size: 0.78rem; color: #92400e; margin: 3px 0 0; }
        .st-price-col { text-align: right; }
        .st-price { font-size: 1.2rem; font-weight: 800; color: var(--brand-strong); margin: 0; }
        .st-price-unit { font-size: 0.75rem; color: var(--muted); font-weight: 500; display: block; margin: 2px 0 0; }
        .st-qty-col { display: flex; flex-direction: column; gap: 4px; }
        .st-qty-label { font-size: 0.72rem; font-weight: 600; color: #4a6478; }
        .st-qty-controls { display: flex; align-items: center; gap: 4px; }
        .st-qty-btn { width: 28px; height: 28px; border: 1px solid var(--line); background: #fff; border-radius: 5px; cursor: pointer; font-size: 0.9rem; color: var(--brand); transition: all 0.15s ease; }
        .st-qty-btn:hover { background: #f0f6fb; border-color: var(--brand); }
        .st-qty-input { width: 42px; text-align: center; border: 1px solid var(--line); border-radius: 5px; padding: 4px; font-size: 0.85rem; }
        .st-add-btn { background: var(--brand); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 0.85rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.2s ease; }
        .st-add-btn:hover { background: var(--brand-strong); }

        .st-sidebar-card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .st-sidebar-title { font-size: 0.88rem; font-weight: 700; color: var(--ink); margin: 0 0 12px; }
        .st-sidebar-content { font-size: 0.82rem; color: #4a6478; line-height: 1.6; }
        .st-sidebar-list { margin: 0; padding: 0; list-style: none; }
        .st-sidebar-list li { padding: 8px 0; font-size: 0.82rem; color: #35576d; border-bottom: 1px solid #f0f6fb; }
        .st-sidebar-list li:last-child { border-bottom: none; }

        .st-booking-cart { background: #f8fbff; border: 1px solid var(--line); border-radius: 12px; padding: 14px; margin-bottom: 12px; }
        .st-cart-empty { text-align: center; color: var(--muted); font-size: 0.82rem; padding: 20px 0; }
        .st-cart-icon { font-size: 2.2rem; opacity: 0.3; margin: 0 0 8px; }

        .st-similar-section { margin: 40px 0 0; padding: 24px 0 0; border-top: 2px solid var(--line); }

        @media (max-width: 768px) {
            .st-main-container { grid-template-columns: 1fr; }
            .st-snapshot { grid-template-columns: 1fr; }
            .st-ticket-card { grid-template-columns: 1fr; }
            .st-price-col { text-align: left; }
        }
    </style>
</head>
<body>

@php
    $stCategoryLinks = [
        ['key' => 'accommodation', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'title' => 'Live Aboard', 'url' => '/catalog/liveaboard'],
        ['key' => 'excursion', 'title' => 'Excursion', 'url' => '/catalog/excursion'],
        ['key' => 'water-sports', 'title' => 'Water Sports', 'url' => '/catalog/water_sports'],
        ['key' => 'restaurant', 'title' => 'Restaurants', 'url' => '/catalog/restaurant'],
        ['key' => 'sea-transport', 'title' => 'Sea Transport', 'url' => '/catalog/sea-transport'],
        ['key' => 'land-transport', 'title' => 'Land Transport', 'url' => '/catalog/land-transport'],
        ['key' => 'vehicle-rental', 'title' => 'Vehicle Rentals', 'url' => '/catalog/vehicle_rental'],
        ['key' => 'remote-workspace', 'title' => 'Remote Workspace', 'url' => '/catalog/remote_workspace'],
        ['key' => 'conference-room', 'title' => 'Conference Rooms', 'url' => '/catalog/conference_room'],
    ];
@endphp
@include('partials.customer-uniform-header', [
    'injectUniformHeaderStyles'  => true,
    'injectUniformHeaderScripts' => true,
    'headerNeedsSpacer'          => false,
    'headerHideOnScroll'         => true,
    'headerShowSearch'           => false,
    'headerCategoryLinks'        => $stCategoryLinks,
    'headerActiveCategoryKey'    => 'sea-transport',
    'headerContinueUrl'          => request()->fullUrl(),
])

@if($heroUrl !== '')
    <img src="{{ $heroUrl }}" alt="{{ $property->name ?? 'Vessel' }}" class="st-hero">
@else
    <div class="st-hero-placeholder"><i class="fa-solid fa-ferry"></i></div>
@endif

@if(!empty($galleryMedia) && is_array($galleryMedia) && count($galleryMedia) > 1)
    <div class="st-gallery-strip">
        @foreach($galleryMedia as $galleryIndex => $galleryUrl)
            <div class="st-gallery-thumb {{ $galleryIndex === 0 ? 'active' : '' }}" onclick="updateHeroImage(this.querySelector('img').src)">
                <img src="{{ $galleryUrl }}" alt="{{ ($property->name ?? 'Vessel') . ' image ' . ($galleryIndex + 1) }}" loading="lazy">
            </div>
        @endforeach
    </div>
@endif

<div class="st-main-container">
    <div class="st-main-content">
        <div class="st-breadcrumb">
            <a href="/catalog/sea_transport">← Sea Transport &amp; Ferries</a>
        </div>

        <div class="st-header">
            <h1 class="st-title">{{ $property->name ?? 'Vessel' }}</h1>
            @if($vendor)
                <div class="st-location">
                    <strong>{{ $vendor->name ?? ($vendor->business_name ?? 'Operator') }}</strong>
                    @if(!empty($listingDetails['location']))
                        · {{ $listingDetails['location'] }}
                    @endif
                </div>
            @endif
            @if(!empty($listingDetails['description']))
                <p class="st-description">{{ $listingDetails['description'] }}</p>
            @endif
        </div>

        <div class="st-tags">
            @if(!empty($listingDetails['vessel_type']))
                <span class="st-tag"><i class="fa-solid fa-ferry"></i> {{ ucwords(str_replace('_',' ', $listingDetails['vessel_type'])) }}</span>
            @endif
            @if(!empty($listingDetails['total_seats']))
                <span class="st-tag"><i class="fa-solid fa-users"></i> {{ $listingDetails['total_seats'] }} seats</span>
            @endif
            @if($fromPriceLocal > 0)
                <span class="st-tag"><i class="fa-solid fa-tag"></i> From MVR {{ number_format($fromPriceLocal, 2) }}</span>
            @endif
        </div>

        <div style="margin-bottom: 20px;">
            <p class="st-share-label">Share This Sea Transport</p>
            <div class="st-share-buttons">
                <button class="st-share-btn" title="Share on WhatsApp" onclick="shareOnWhatsApp()"><i class="fa-brands fa-whatsapp"></i></button>
                <button class="st-share-btn" title="Share on Facebook" onclick="shareOnFacebook()"><i class="fa-brands fa-facebook-f"></i></button>
                <button class="st-share-btn" title="Share on LinkedIn" onclick="shareOnLinkedIn()"><i class="fa-brands fa-linkedin-in"></i></button>
                <button class="st-share-btn" title="Copy link" onclick="copyLinkToClipboard()"><i class="fa-solid fa-link"></i></button>
            </div>
        </div>

        {{-- SERVICE SNAPSHOT ──────────────────────────────────────────────────── --}}
        <div class="st-section">
            <h2 class="st-section-title">Service Snapshot</h2>
            <div class="st-snapshot">
                <div class="st-snapshot-card">
                    <h4>What is Included</h4>
                    <ul>
                        <li>Vessel seating and transport</li>
                        <li>Safety briefing</li>
                        <li>Life jacket</li>
                        <li>Professional crew</li>
                    </ul>
                </div>
                <div class="st-snapshot-card">
                    <h4>What We Provide</h4>
                    <ul>
                        <li>Comfortable seating</li>
                        <li>Air-conditioned cabin</li>
                        <li>Refreshments (on demand)</li>
                        <li>Luggage assistance</li>
                    </ul>
                </div>
                <div class="st-snapshot-card info">
                    <h4>Departure / Reporting Point</h4>
                    <ul>
                        <li><strong>Duration:</strong> Varies by route</li>
                        <li><strong>Assembly:</strong> Main jetty dock</li>
                        <li><strong>Check-in:</strong> 15 mins before</li>
                        <li><strong>Meeting:</strong> Ticket counter</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- SERVICE DESCRIPTION ──────────────────────────────────────────────── --}}
        <div class="st-section">
            <h2 class="st-section-title">Service Description</h2>
            <p class="st-service-desc">
                {{ $listingDetails['description'] ?? 'Professional sea transport service offering comfortable and safe journeys across the Maldivian atolls. Our modern fleet operates on scheduled routes with experienced crew and modern safety equipment. Perfect for inter-island transfers, resort arrivals, and island hopping adventures.' }}
            </p>
        </div>

        {{-- TERMS / POLICIES ──────────────────────────────────────────────────── --}}
        <div class="st-section">
            <h2 class="st-section-title">Terms / Policies</h2>
            <ul class="st-terms-list">
                <li>Trips may be rescheduled in case of unsafe weather</li>
                <li>Life jackets must be worn at all times on deck</li>
                <li>Children under 12 must be accompanied by an adult</li>
                <li>Passengers must arrive 15 minutes before departure</li>
                <li>Refunds available for cancellations made 24 hours in advance</li>
                <li>Luggage allowance: 1 piece per passenger</li>
            </ul>
        </div>

        {{-- AVAILABLE TICKETS / ROUTES ─────────────────────────────────────────── --}}
        <div class="st-section">
            <h2 class="st-section-title">Available Tickets &amp; Routes</h2>

            @if($errors->any())
                <div style="background:#fdf0f0; border:1px solid #f5c6c6; border-radius:8px; padding:12px 14px; margin-bottom:16px; color:#c0392b; font-size:0.87rem;">
                    @foreach($errors->all() as $error)
                        <p style="margin:0 0 4px;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="st-equipment-grid">
                @forelse($routeSchedules as $legIdx => $leg)
                    @php
                        $legCode   = $leg['route_code'] ?? '';
                        $legOrigin = $leg['origin'] ?? '';
                        $legDest   = $leg['destination'] ?? '';
                        $legDep    = $leg['dep_time'] ?? '';
                        $legArr    = $leg['arr_time'] ?? '';
                        $legDays   = is_array($leg['days'] ?? null) ? $leg['days'] : [];
                        $legDur    = (int) ($leg['duration_minutes'] ?? 0);
                        $legLocalAdult   = isset($leg['local_adult'])   && $leg['local_adult']   !== null ? (float) $leg['local_adult']   : (float) ($listingDetails['local_price'] ?? 0);
                        $legLocalChild   = isset($leg['local_child'])   && $leg['local_child']   !== null ? (float) $leg['local_child']   : (float) ($listingDetails['local_child_price'] ?? 0);
                        $legForAdult     = isset($leg['foreign_adult']) && $leg['foreign_adult'] !== null ? (float) $leg['foreign_adult'] : (float) ($listingDetails['foreign_price'] ?? 0);
                        $legForChild     = isset($leg['foreign_child']) && $leg['foreign_child'] !== null ? (float) $leg['foreign_child'] : 0;
                        $isLocal         = $visitorResidency === 'local_resident';
                        $displayAdult    = $isLocal ? $legLocalAdult : $legForAdult;
                        $displayCurrency = $isLocal ? 'MVR' : 'USD';
                        $daysStr         = implode(', ', $legDays);
                    @endphp
                    <div class="st-ticket-card">
                        <div>
                            <p class="st-ticket-route">
                                <i class="fa-solid fa-route"></i>
                                {{ $legOrigin !== '' ? $legOrigin : '(boarding)' }} → {{ $legDest !== '' ? $legDest : '(destination)' }}
                            </p>
                            <div class="st-ticket-meta">
                                @if($legDep) <div class="st-ticket-meta-item">🕐 Departs {{ $legDep }}</div> @endif
                                @if($legArr) <div class="st-ticket-meta-item">🎯 Arrives {{ $legArr }}</div> @endif
                                @if($legDur > 0) <div class="st-ticket-meta-item">⏱ {{ floor($legDur/60) }}h {{ $legDur%60 }}m</div> @endif
                                @if($daysStr) <div class="st-ticket-meta-item">📅 {{ $daysStr }}</div> @endif
                            </div>
                        </div>

                        <div>
                            <div class="st-ticket-safety">
                                <div class="st-ticket-safety-title">⚠️ Requirements</div>
                                <div class="st-ticket-safety-content">
                                    Must be able to board safely. Children must have adult supervision.
                                </div>
                            </div>
                        </div>

                        <div class="st-price-col">
                            <p class="st-price">{{ $displayCurrency }} {{ number_format($displayAdult, 2) }}</p>
                            <span class="st-price-unit">per person</span>
                            <small style="font-size: 0.7rem; color: var(--muted); margin-top: 8px; display: block;">
                                @if($isLocal && $legLocalAdult > 0)
                                    Local rate
                                @elseif(!$isLocal && $legForAdult > 0)
                                    Foreign rate
                                @else
                                    Contact operator
                                @endif
                            </small>
                        </div>

                        <div class="st-qty-col">
                            <label class="st-qty-label">Quantity</label>
                            <div class="st-qty-controls">
                                <button class="st-qty-btn" type="button" onclick="decreaseQty({{ $legIdx }})">−</button>
                                <input type="number" class="st-qty-input" id="qty_{{ $legIdx }}" value="1" min="1" max="50">
                                <button class="st-qty-btn" type="button" onclick="increaseQty({{ $legIdx }})">+</button>
                            </div>
                            <button class="st-add-btn" type="button" onclick="addToCart({{ $legIdx }}, '{{ $legCode }}', '{{ $legOrigin }}', '{{ $legDest }}', {{ $displayAdult }}, '{{ $displayCurrency }}')">
                                <i class="fa-solid fa-cart-plus"></i> Add
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="background:#fff; border:1px dashed #c8d8e8; border-radius:10px; padding:24px; text-align:center; color:#5f7488;">
                        <i class="fa-solid fa-ferry" style="font-size:1.8rem; opacity:0.3;"></i>
                        <p style="margin:10px 0 0; font-size:0.88rem;">No route legs configured yet. Please check back soon.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- SIMILAR SERVICES ─────────────────────────────────────────────────── --}}
        <div class="st-similar-section">
            <h2 class="st-section-title">Similar Sea Transport Nearby</h2>
            <p style="font-size: 0.88rem; color: #5f7488; margin: 12px 0; line-height: 1.6;">
                Explore similar sea transport services from the same area and compare availability and pricing before checkout.
            </p>
            <button style="margin-top: 12px; background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 10px 16px; font-size: 0.85rem; font-weight: 600; color: var(--brand); cursor: pointer;">
                Browse Similar Operators
            </button>
        </div>
    </div>

    {{-- SIDEBAR / BOOKING CART ───────────────────────────────────────────── --}}
    <div class="st-sidebar">
        <div class="st-sidebar-card">
            <h3 class="st-sidebar-title">Book Now</h3>
            <h4 style="font-size: 0.88rem; color: #4a6478; margin: 0 0 12px;">{{ $property->name ?? 'Sea Transport' }}</h4>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #4a6478; margin-bottom: 5px;">TRAVEL DATE</label>
                <input type="date" id="booking_date" style="width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 6px; font-size: 0.85rem;">
            </div>

            <div class="st-booking-cart">
                <div class="st-cart-empty">
                    <div class="st-cart-icon"><i class="fa-solid fa-shopping-cart"></i></div>
                    Add equipment from the list on the left
                </div>
                <div id="cart_items" style="display: none;"></div>
            </div>

            <div style="background: #eaf9ef; border: 1px solid #b7e2c3; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px;">
                <p style="margin: 0; font-size: 0.78rem; color: #0b5c2a;">
                    <strong>Order Total:</strong> <span id="order_total" style="font-size: 1.1rem; font-weight: 800; color: #1d7bb5;">MVR 0.00</span>
                </p>
                <p style="margin: 5px 0 0; font-size: 0.7rem; color: #4a6b36;">Final pricing adjusted for your guest nationality at checkout.</p>
            </div>

            <button style="width: 100%; background: #17a578; color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: 0.9rem; font-weight: 700; cursor: pointer;" onclick="proceedToCheckout()">
                Book Now
            </button>
        </div>

        <div class="st-sidebar-card">
            <h3 class="st-sidebar-title">Additional Request (Optional)</h3>
            <textarea placeholder="Any dietary, timing, or service request?" style="width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 10px; font-size: 0.82rem; font-family: inherit; resize: vertical; min-height: 100px;"></textarea>
        </div>
    </div>
</div>

<script>
    let cartItems = [];

    function updateHeroImage(src) {
        const hero = document.querySelector('.st-hero');
        if (hero) hero.src = src;
        document.querySelectorAll('.st-gallery-thumb').forEach(t => t.classList.remove('active'));
        event.target.closest('.st-gallery-thumb')?.classList.add('active');
    }

    function increaseQty(idx) {
        const input = document.getElementById('qty_' + idx);
        input.value = Math.min(50, parseInt(input.value || 0) + 1);
    }

    function decreaseQty(idx) {
        const input = document.getElementById('qty_' + idx);
        input.value = Math.max(1, parseInt(input.value || 0) - 1);
    }

    function addToCart(idx, code, origin, dest, price, currency) {
        const qty = parseInt(document.getElementById('qty_' + idx).value || 1);
        const item = { idx, code, origin, dest, price, currency, qty };
        cartItems.push(item);
        updateCart();
        document.getElementById('qty_' + idx).value = 1;
    }

    function updateCart() {
        const cartContainer = document.getElementById('cart_items');
        const emptyCart = document.querySelector('.st-cart-empty');
        const total = cartItems.reduce((sum, item) => sum + (item.price * item.qty), 0);

        if (cartItems.length === 0) {
            cartContainer.style.display = 'none';
            emptyCart.style.display = 'block';
            document.getElementById('order_total').textContent = 'MVR 0.00';
        } else {
            cartContainer.style.display = 'block';
            emptyCart.style.display = 'none';
            cartContainer.innerHTML = cartItems.map((item, i) => `
                <div style="padding: 8px 0; border-bottom: 1px solid #f0f6fb; font-size: 0.8rem;">
                    <div style="font-weight: 600; color: #152738; margin-bottom: 3px;">
                        ${item.origin} → ${item.dest}
                    </div>
                    <div style="color: #5f7488;">
                        ${item.qty}x ${item.currency} ${item.price.toFixed(2)} = <strong>${item.currency} ${(item.price * item.qty).toFixed(2)}</strong>
                        <button onclick="removeFromCart(${i})" style="margin-left: 8px; background: none; border: none; color: #c0392b; cursor: pointer; font-size: 0.75rem;">Remove</button>
                    </div>
                </div>
            `).join('');
            document.getElementById('order_total').textContent = 'MVR ' + total.toFixed(2);
        }
    }

    function removeFromCart(idx) {
        cartItems.splice(idx, 1);
        updateCart();
    }

    function proceedToCheckout() {
        if (cartItems.length === 0) {
            alert('Please add at least one ticket to your cart.');
            return;
        }
        console.log('Proceeding to checkout with items:', cartItems);
        alert('Checkout feature coming soon. Cart items: ' + cartItems.length);
    }

    function shareOnWhatsApp() {
        const url = window.location.href;
        window.open(`https://wa.me/?text=${encodeURIComponent('Check this out: ' + url)}`);
    }

    function shareOnFacebook() {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`);
    }

    function shareOnLinkedIn() {
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`);
    }

    function copyLinkToClipboard() {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
    }
</script>

</body>
</html>
