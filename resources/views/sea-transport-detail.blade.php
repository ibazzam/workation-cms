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

        /* ── Layout ─────────────────────────────────── */
        .st-hero {
            width: 100%; height: 280px; object-fit: cover; display: block;
            background: #c2d9e6;
        }
        .st-hero-placeholder {
            width: 100%; height: 280px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f6179, #1d7bb5);
            color: #fff; font-size: 3.5rem;
        }
        .st-container { max-width: 900px; margin: 0 auto; padding: 24px 16px 60px; }

        /* ── Vessel header ───────────────────────────── */
        .st-header { margin-bottom: 20px; }
        .st-title { font-size: 1.6rem; font-weight: 800; color: var(--ink); margin: 0 0 4px; }
        .st-operator { font-size: 0.88rem; color: var(--muted); }

        /* ── Stop chain ─────────────────────────────── */
        .st-stops {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0;
            background: #fff; border: 1px solid var(--line); border-radius: 10px;
            padding: 12px 14px; margin-bottom: 22px; font-size: 0.83rem; font-weight: 600;
        }
        .st-stop { color: var(--brand); white-space: nowrap; }
        .st-stop-sep { color: var(--muted); margin: 0 5px; font-weight: 400; }

        /* ── Section title ───────────────────────────── */
        .st-section-title {
            font-size: 1rem; font-weight: 700; color: var(--ink);
            margin: 0 0 12px; padding-bottom: 8px; border-bottom: 2px solid var(--line);
        }

        /* ── Fare card ───────────────────────────────── */
        .st-fare-card {
            background: #fff; border: 1px solid var(--line); border-radius: 10px;
            margin-bottom: 12px; overflow: hidden;
        }
        .st-fare-head {
            display: grid;
            grid-template-columns: 1fr auto auto 140px;
            gap: 10px; align-items: center;
            padding: 14px 16px; cursor: pointer;
        }
        .st-fare-head:hover { background: #f7fbff; }
        .st-fare-route { font-size: 0.95rem; font-weight: 700; color: var(--ink); }
        .st-fare-meta { font-size: 0.78rem; color: var(--muted); margin-top: 3px; }
        .st-fare-days { font-size: 0.72rem; color: var(--brand); font-weight: 600; }
        .st-fare-price {
            text-align: right; font-size: 1.05rem; font-weight: 800;
            color: var(--brand-strong);
        }
        .st-fare-price small { font-size: 0.7rem; font-weight: 500; color: var(--muted); display: block; }
        .st-book-btn {
            background: var(--brand); color: #fff; border: none; border-radius: 6px;
            padding: 9px 14px; font-size: 0.83rem; font-weight: 700; cursor: pointer;
            white-space: nowrap;
        }
        .st-book-btn:hover { background: var(--brand-strong); }

        /* ── Booking inline form ─────────────────────── */
        .st-booking-form {
            display: none; padding: 16px; border-top: 1px dashed var(--line);
            background: #f7fbff;
        }
        .st-booking-form.open { display: block; }
        .st-booking-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px; margin-bottom: 12px;
        }
        .st-field label { display: block; font-size: 0.72rem; font-weight: 700; color: #4a6478; margin-bottom: 3px; }
        .st-field input, .st-field select {
            width: 100%; padding: 6px 9px; font-size: 0.85rem;
            border: 1px solid #c8d8e8; border-radius: 5px; background: #fff;
        }
        .st-submit-btn {
            background: var(--brand); color: #fff; border: none; border-radius: 6px;
            padding: 10px 22px; font-size: 0.9rem; font-weight: 700; cursor: pointer;
        }
        .st-submit-btn:hover { background: var(--brand-strong); }
        .st-error { color: #c0392b; font-size: 0.82rem; margin-top: 6px; }

        /* ── Breadcrumb ──────────────────────────────── */
        .st-breadcrumb { font-size: 0.78rem; color: var(--muted); margin-bottom: 14px; }
        .st-breadcrumb a { color: var(--brand); }
        .st-breadcrumb a:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .st-fare-head { grid-template-columns: 1fr 100px; }
            .st-fare-head .st-fare-days, .st-fare-head .st-fare-price { display: none; }
            .st-fare-head .st-fare-price { display: block; }
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

<div class="st-container">
    <div class="st-breadcrumb">
        <a href="/catalog/sea_transport">← Sea Transport &amp; Ferries</a>
    </div>

    {{-- ── Vessel header ───────────────────────────────────────────── --}}
    <div class="st-header">
        <h1 class="st-title">{{ $property->name ?? 'Vessel' }}</h1>
        @if($vendor)
            <div class="st-operator">Operated by <strong>{{ $vendor->name ?? ($vendor->business_name ?? 'Unknown Operator') }}</strong></div>
        @endif
        @if(!empty($listingDetails['description']))
            <p style="font-size:0.88rem; color:#5f7488; margin: 8px 0 0; line-height:1.55;">{{ $listingDetails['description'] }}</p>
        @endif
    </div>

    {{-- ── Stop chain diagram ──────────────────────────────────────── --}}
    @if(count($stopSequence) > 0)
        <div class="st-stops">
            @foreach($stopSequence as $i => $stop)
                <span class="st-stop">{{ $stop }}</span>
                @if(!$loop->last)
                    <span class="st-stop-sep">──◉──</span>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Vessel info chips ──────────────────────────────────────────── --}}
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:22px;">
        @if(!empty($listingDetails['total_seats']))
            <span style="font-size:0.78rem; background:#e6f0f7; color:#1d4b66; border-radius:20px; padding:4px 10px; font-weight:600;">
                <i class="fa-solid fa-chair" aria-hidden="true"></i> {{ $listingDetails['total_seats'] }} seats
            </span>
        @endif
        @if(!empty($listingDetails['vessel_type']))
            <span style="font-size:0.78rem; background:#e6f0f7; color:#1d4b66; border-radius:20px; padding:4px 10px; font-weight:600;">
                <i class="fa-solid fa-ferry" aria-hidden="true"></i> {{ ucwords(str_replace('_',' ', $listingDetails['vessel_type'])) }}
            </span>
        @endif
        @if($fromPriceLocal > 0)
            <span style="font-size:0.78rem; background:#e3f6ec; color:#1b6b41; border-radius:20px; padding:4px 10px; font-weight:700;">
                From MVR {{ number_format($fromPriceLocal, 2) }}
            </span>
        @endif
    </div>

    {{-- ── Session errors ──────────────────────────────────────────── --}}
    @if($errors->any())
        <div style="background:#fdf0f0; border:1px solid #f5c6c6; border-radius:8px; padding:12px 14px; margin-bottom:16px; color:#c0392b; font-size:0.87rem;">
            @foreach($errors->all() as $error)
                <p style="margin:0 0 4px;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ── Route legs ──────────────────────────────────────────────── --}}
    <p class="st-section-title">Available Routes &amp; Fares</p>

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
            $legLocalInfant  = isset($leg['local_infant'])  && $leg['local_infant']  !== null ? (float) $leg['local_infant']  : 0;
            $legForAdult     = isset($leg['foreign_adult']) && $leg['foreign_adult'] !== null ? (float) $leg['foreign_adult'] : (float) ($listingDetails['foreign_price'] ?? 0);
            $legForChild     = isset($leg['foreign_child']) && $leg['foreign_child'] !== null ? (float) $leg['foreign_child'] : 0;
            $legForInfant    = isset($leg['foreign_infant'])&& $leg['foreign_infant']!== null ? (float) $leg['foreign_infant']: 0;
            $isLocal         = $visitorResidency === 'local_resident';
            $displayAdult    = $isLocal ? $legLocalAdult : $legForAdult;
            $displayCurrency = $isLocal ? 'MVR' : 'USD';
            $daysStr         = implode(' · ', $legDays);
        @endphp
        <div class="st-fare-card" id="fare-card-{{ $legIdx }}">
            <div class="st-fare-head" onclick="toggleBookingForm({{ $legIdx }})">
                <div>
                    <div class="st-fare-route">
                        <i class="fa-solid fa-route" aria-hidden="true"></i>
                        {{ $legOrigin !== '' ? $legOrigin : '(boarding stop)' }}
                        <span style="color:#aaa; margin:0 4px;">→</span>
                        {{ $legDest !== '' ? $legDest : '(destination stop)' }}
                    </div>
                    <div class="st-fare-meta">
                        @if($legDep !== '') Departs {{ $legDep }}@endif
                        @if($legArr !== '') · Arrives {{ $legArr }}@endif
                        @if($legDur > 0) · {{ $legDur >= 60 ? floor($legDur/60).'h '.($legDur%60 ? ($legDur%60).'m' : '') : $legDur.'m' }}@endif
                    </div>
                </div>
                <div class="st-fare-days">{{ $daysStr !== '' ? $daysStr : 'Daily' }}</div>
                <div class="st-fare-price">
                    @if($displayAdult > 0)
                        {{ $displayCurrency }} {{ number_format($displayAdult, 2) }}
                        <small>per adult</small>
                    @else
                        <span style="color:#aaa; font-size:0.8rem;">Contact</span>
                    @endif
                </div>
                <button type="button" class="st-book-btn" onclick="event.stopPropagation(); openBookingForm({{ $legIdx }})">
                    Book <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>

            <div class="st-booking-form" id="booking-form-{{ $legIdx }}">
                <form method="POST" action="/category-booking/sea_transport/{{ $property->vendor_property_id ?? $property->id }}">
                    @csrf
                    <input type="hidden" name="route_code"       value="{{ $legCode }}">
                    <input type="hidden" name="boarding_point"   value="{{ $legOrigin }}">
                    <input type="hidden" name="disembark_point"  value="{{ $legDest }}">
                    <input type="hidden" name="listing_category" value="sea_transport">

                    <div class="st-booking-grid">
                        <div class="st-field">
                            <label for="travel_date_{{ $legIdx }}">Travel Date</label>
                            <input type="date" id="travel_date_{{ $legIdx }}" name="travel_date"
                                   min="{{ date('Y-m-d') }}" required
                                   value="{{ old('travel_date', $legDep !== '' ? '' : '') }}">
                        </div>
                        <div class="st-field">
                            <label for="adults_{{ $legIdx }}">Adults</label>
                            <input type="number" id="adults_{{ $legIdx }}" name="adults" min="1" max="50" value="{{ old('adults', 1) }}" required>
                        </div>
                        <div class="st-field">
                            <label for="children_{{ $legIdx }}">Children</label>
                            <input type="number" id="children_{{ $legIdx }}" name="children" min="0" max="50" value="{{ old('children', 0) }}">
                        </div>
                        <div class="st-field">
                            <label for="infants_{{ $legIdx }}">Infants</label>
                            <input type="number" id="infants_{{ $legIdx }}" name="infants" min="0" max="20" value="{{ old('infants', 0) }}">
                        </div>
                        <div class="st-field">
                            <label for="residency_{{ $legIdx }}">Residency</label>
                            <select id="residency_{{ $legIdx }}" name="guest_residency">
                                <option value="foreign_national" {{ $visitorResidency !== 'local_resident' ? 'selected' : '' }}>Foreign national</option>
                                <option value="local_resident"   {{ $visitorResidency === 'local_resident' ? 'selected' : '' }}>Maldivian resident</option>
                            </select>
                        </div>
                    </div>

                    @if($legLocalAdult > 0 || $legForAdult > 0)
                        <p style="font-size:0.8rem; color:#4a6478; margin:0 0 10px;">
                            <strong>Local fare:</strong>
                            @if($legLocalAdult > 0) Adult MVR {{ number_format($legLocalAdult,2) }}@endif
                            @if($legLocalChild > 0)  · Child MVR {{ number_format($legLocalChild,2) }}@endif
                            @if($legLocalInfant > 0) · Infant MVR {{ number_format($legLocalInfant,2) }}@endif
                            &nbsp;|&nbsp;
                            <strong>Foreign fare:</strong>
                            @if($legForAdult > 0) Adult USD {{ number_format($legForAdult,2) }}@endif
                            @if($legForChild > 0)  · Child USD {{ number_format($legForChild,2) }}@endif
                            @if($legForInfant > 0) · Infant USD {{ number_format($legForInfant,2) }}@endif
                        </p>
                    @endif

                    <button type="submit" class="st-submit-btn">
                        <i class="fa-solid fa-check" aria-hidden="true"></i> Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div style="background:#fff; border:1px dashed #c8d8e8; border-radius:10px; padding:24px; text-align:center; color:#5f7488;">
            <i class="fa-solid fa-ferry" style="font-size:1.8rem; opacity:0.3;"></i>
            <p style="margin:10px 0 0; font-size:0.88rem;">No route legs configured yet. Please check back soon.</p>
        </div>
    @endforelse

</div>{{-- /.st-container --}}

<script>
    function toggleBookingForm(idx) {
        const form = document.getElementById('booking-form-' + idx);
        if (form) { form.classList.toggle('open'); }
    }
    function openBookingForm(idx) {
        const form = document.getElementById('booking-form-' + idx);
        if (form) {
            form.classList.add('open');
            setTimeout(function() { form.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 50);
        }
    }
    // Auto-open the form that had a validation error on the previous POST.
    @if(old('route_code'))
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.st-fare-card');
            cards.forEach(function(card, i) {
                const rc = card.querySelector('input[name="route_code"]');
                if (rc && rc.value === '{{ old("route_code") }}') { openBookingForm(i); }
            });
        });
    @endif
</script>

</body>
</html>