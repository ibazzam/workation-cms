<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $property->name ?? 'Liveaboard' }} | Workation</title>
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
            --accent: #f3a337;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Outfit","Trebuchet MS",sans-serif; color: var(--ink); background: var(--bg); }
        a { color: inherit; text-decoration: none; }

        .property-profile-container { display: grid; grid-template-columns: 1fr; gap: 24px; margin: 28px auto; max-width: 1200px; padding: 0 16px; }
        .breadcrumb { font-size: 0.85rem; color: #6a7f90; margin-bottom: 16px; }
        .breadcrumb a { color: #0f6179; }
        .breadcrumb a:hover { text-decoration: underline; }

        .property-hero-section { background: var(--surface); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(15, 97, 121, 0.12); }
        .property-hero-image { width: 100%; height: 400px; object-fit: cover; display: block; background: #d6e8f3; }
        
        .property-gallery-strip { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; padding: 12px; }
        .gallery-thumb { height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid var(--line); cursor: pointer; }
        .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-thumb.active { border-color: var(--brand); }

        .property-info-section { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
        .property-details { background: var(--surface); border-radius: 12px; padding: 24px; }
        .property-header { margin-bottom: 24px; }
        .property-name { font-size: 1.8rem; font-weight: 700; margin: 0 0 12px; }
        .property-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; }
        .property-meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: var(--muted); }
        .property-description { color: #4a6478; font-size: 0.95rem; line-height: 1.6; margin: 16px 0 0; }

        .property-sidebar { display: grid; gap: 16px; }
        .booking-card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 20px; }
        .booking-price { font-size: 1.8rem; font-weight: 700; color: var(--brand); margin-bottom: 8px; }
        .booking-price-label { font-size: 0.8rem; color: var(--muted); margin-bottom: 16px; }
        
        .booking-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .detail-input-group { display: grid; gap: 4px; }
        .detail-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--muted); }
        .detail-value { font-size: 0.9rem; font-weight: 600; color: var(--ink); }

        .booking-btn { background: var(--brand); color: #fff; border: 0; padding: 12px 20px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.22s ease; }
        .booking-btn:hover { background: var(--brand-strong); }

        .details-section { background: var(--surface); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .section-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 16px; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .detail-card { background: #f7fbfd; padding: 16px; border-radius: 8px; border: 1px solid var(--line); }
        .detail-card-label { font-size: 0.8rem; text-transform: uppercase; color: var(--muted); font-weight: 700; margin-bottom: 6px; }
        .detail-card-value { font-size: 1rem; font-weight: 600; color: var(--ink); }

        .stopovers-list { display: grid; gap: 12px; }
        .stopover-item { padding: 12px; background: #f7fbfd; border-radius: 8px; border-left: 4px solid var(--brand); }
        .stopover-name { font-weight: 600; color: var(--ink); margin-bottom: 4px; }
        .stopover-info { font-size: 0.85rem; color: var(--muted); }

        @media (max-width: 768px) {
            .property-info-section { grid-template-columns: 1fr; }
            .property-hero-image { height: 300px; }
            .booking-details-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

@php
    $laRouteStart = trim((string) ($listingDetails['start_point'] ?? ''));
    $laRouteEnd   = trim((string) ($listingDetails['end_point'] ?? ''));
    $laDays       = (int) ($listingDetails['journey_duration_days'] ?? 0);
    $laCabins     = (int) ($listingDetails['cabin_count'] ?? 0);
    $laVessel     = trim((string) ($listingDetails['vessel_name'] ?? ''));
    $laCompany    = trim((string) ($listingDetails['operator_name'] ?? ($vendor->name ?? 'Operator')));
    $laDescription = trim((string) ($property->description ?? ''));
    $laContactEmail = trim((string) ($listingDetails['contact_email'] ?? ($vendor->email ?? '')));
    $laContactPhone = trim((string) ($listingDetails['contact_phone'] ?? ''));
    $laStopovers = (array) ($stopovers ?? []);
    
    $laImageFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22600%22 viewBox=%220 0 1200 600%22%3E%3Cdefs%3E%3ClinearGradient id=%22laGrad%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 stop-color=%220f6179%22/%3E%3Cstop offset=%22100%25%22 stop-color=%231d7bb5%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%221200%22 height=%22600%22 fill=%22url(%23laGrad)%22/%3E%3C/svg%3E";
    
    $laHero = !empty($heroUrl) && $heroUrl !== '' ? $heroUrl : $laImageFallback;
    $laGallery = !empty($galleryMedia) ? $galleryMedia : [$laHero];
    
    $laVisitorIsLocal = $visitorResidency === 'local_resident';
    $laDisplayCurrency = $laVisitorIsLocal ? 'MVR' : 'USD';
    $laMinPrice = (float) $minPrice;
@endphp

<nav class="breadcrumb" style="max-width: 1200px; margin: 14px auto 0; padding: 0 16px;">
    <a href="/">Home</a> › <a href="/catalog/liveaboard">Liveaboard</a> › <span>{{ $property->name ?? 'Journey' }}</span>
</nav>

<div class="property-profile-container">

    <!-- Hero Section -->
    <div class="property-hero-section">
        <img id="heroImage" src="{{ $laHero }}" alt="{{ $property->name ?? 'Liveaboard' }}" class="property-hero-image" onerror="if(!this.dataset.fb)this.src='{{ $laImageFallback }}'" data-fb="1">
        @if (count($laGallery) > 1)
        <div class="property-gallery-strip">
            @foreach ($laGallery as $idx => $image)
                <div class="gallery-thumb {{ $idx === 0 ? 'active' : '' }}" onclick="updateHeroImage('{{ $image }}', this)">
                    <img src="{{ $image }}" alt="Gallery {{ $idx + 1 }}" onerror="if(!this.dataset.fb)this.src='{{ $laImageFallback }}'" data-fb="1">
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Info & Booking Section -->
    <div class="property-info-section">

        <div class="property-details">
            <div class="property-header">
                <h1 class="property-name">{{ $property->name ?? 'Safari Vessel' }}</h1>
                
                <div class="property-meta">
                    @if ($laRouteStart !== '' || $laRouteEnd !== '')
                        <div class="property-meta-item">
                            <i class="fa-solid fa-route"></i>
                            {{ $laRouteStart }} → {{ $laRouteEnd }}
                        </div>
                    @endif
                    @if ($laDays > 0)
                        <div class="property-meta-item">
                            <i class="fa-solid fa-calendar-days"></i>
                            {{ $laDays }}-day journey
                        </div>
                    @endif
                    @if ($laCabins > 0)
                        <div class="property-meta-item">
                            <i class="fa-solid fa-bed"></i>
                            {{ $laCabins }} {{ $laCabins === 1 ? 'room' : 'rooms' }}
                        </div>
                    @endif
                </div>

                @if (!empty($laDescription))
                    <p class="property-description">{{ $laDescription }}</p>
                @endif
            </div>

            @if (count($laStopovers) > 0)
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--line);">
                <h3 style="font-size: 1.1rem; margin: 0 0 12px; font-weight: 700;">Stopover Points</h3>
                <div class="stopovers-list">
                    @foreach ($laStopovers as $stopover)
                        <div class="stopover-item">
                            <div class="stopover-name"><i class="fa-solid fa-map-pin"></i> {{ $stopover['name'] ?? 'Stop' }}</div>
                            <div class="stopover-info">
                                @if (!empty($stopover['boarding_allowed']))
                                    <i class="fa-solid fa-arrow-up"></i> Boarding
                                @endif
                                @if (!empty($stopover['disembark_allowed']))
                                    <i class="fa-solid fa-arrow-down"></i> Disembarking
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Booking Sidebar -->
        <div class="property-sidebar">
            <div class="booking-card">
                <div class="booking-price">
                    @if ($laMinPrice > 0)
                        {{ $laDisplayCurrency }} {{ number_format($laMinPrice, 2) }}
                    @else
                        Price on request
                    @endif
                </div>
                <div class="booking-price-label">
                    {{ $laVisitorIsLocal ? 'Local pricing (MVR)' : 'Foreign pricing (USD)' }}
                </div>

                <div class="booking-details-grid">
                    @if ($laDays > 0)
                    <div class="detail-input-group">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">{{ $laDays }} Days</span>
                    </div>
                    @endif
                    @if ($laCabins > 0)
                    <div class="detail-input-group">
                        <span class="detail-label">Rooms</span>
                        <span class="detail-value">{{ $laCabins }}</span>
                    </div>
                    @endif
                </div>

                <button type="button" class="booking-btn" onclick="location.href='/category-booking/liveaboard/{{ $property->vendor_property_id ?? $property->id }}'">
                    <i class="fa-solid fa-calendar-check"></i> Book Journey
                </button>
            </div>

            @if (!empty($laCompany) || !empty($laContactEmail) || !empty($laContactPhone))
            <div class="booking-card" style="border: none; box-shadow: 0 2px 8px rgba(15, 97, 121, 0.08);">
                <div style="font-size: 0.9rem; font-weight: 700; margin-bottom: 12px; color: var(--muted); text-transform: uppercase;">Operator</div>
                @if (!empty($laCompany))
                    <div style="font-weight: 600; color: var(--ink); margin-bottom: 8px;">{{ $laCompany }}</div>
                @endif
                @if (!empty($laContactEmail))
                    <a href="mailto:{{ $laContactEmail }}" style="color: var(--brand); font-size: 0.9rem; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-envelope"></i> {{ $laContactEmail }}
                    </a>
                @endif
                @if (!empty($laContactPhone))
                    <a href="tel:{{ $laContactPhone }}" style="color: var(--brand); font-size: 0.9rem; display: block;">
                        <i class="fa-solid fa-phone"></i> {{ $laContactPhone }}
                    </a>
                @endif
            </div>
            @endif
        </div>

    </div>

    <!-- Additional Details Section -->
    @if ($laVessel !== '')
    <div class="details-section">
        <h2 class="section-title">Vessel Information</h2>
        <div class="details-grid">
            <div class="detail-card">
                <div class="detail-card-label">Vessel Name</div>
                <div class="detail-card-value">{{ $laVessel }}</div>
            </div>
            @if ($laCabins > 0)
            <div class="detail-card">
                <div class="detail-card-label">Total Rooms</div>
                <div class="detail-card-value">{{ $laCabins }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Journey Details Section -->
    <div class="details-section">
        <h2 class="section-title">Journey Details</h2>
        <div class="details-grid">
            @if ($laRouteStart !== '')
            <div class="detail-card">
                <div class="detail-card-label">Starting Point</div>
                <div class="detail-card-value">{{ $laRouteStart }}</div>
            </div>
            @endif
            @if ($laRouteEnd !== '')
            <div class="detail-card">
                <div class="detail-card-label">Ending Point</div>
                <div class="detail-card-value">{{ $laRouteEnd }}</div>
            </div>
            @endif
            @if ($laDays > 0)
            <div class="detail-card">
                <div class="detail-card-label">Duration</div>
                <div class="detail-card-value">{{ $laDays }} Days</div>
            </div>
            @endif
        </div>
    </div>

    <!-- CTA Section -->
    <div class="details-section" style="text-align: center; background: linear-gradient(135deg, #f7fbfd 0%, #eef6fb 100%);">
        <div style="margin-bottom: 16px;">
            <h2 class="section-title" style="text-align: center;">Ready to Embark?</h2>
            <p style="color: var(--muted); margin: 0; font-size: 0.95rem;">Select your boarding point and confirm your reservation</p>
        </div>
        <button type="button" class="booking-btn" onclick="location.href='/category-booking/liveaboard/{{ $property->vendor_property_id ?? $property->id }}'" style="width: 200px; margin: 0 auto; display: block;">
            <i class="fa-solid fa-compass"></i> Plan Your Journey
        </button>
    </div>

</div>

<script>
function updateHeroImage(src, thumbEl) {
    document.getElementById('heroImage').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(el => el.classList.remove('active'));
    if (thumbEl) thumbEl.classList.add('active');
}
</script>

</body>
</html>
