<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Property') }} | Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px,calc(100% - 24px)); margin:14px auto 28px; }
        .hero { border:1px solid #cbe0ea; border-radius:18px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; padding:18px; }
        .hero h1 { margin:0; font-size:clamp(1.3rem,2.5vw,2.1rem); }
        .hero .sub { margin:6px 0 0; font-size:0.9rem; color:#d8f4f8; }
        .hero .meta { margin-top:8px; font-size:0.86rem; color:#e8fbff; }
        .gallery { margin-top:12px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        .gallery img { width:100%; height:170px; object-fit:cover; border-radius:12px; border:1px solid #cfe1ec; background:#eff7fb; }
        .section { margin-top:12px; border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:14px; }
        .section h2 { margin:0; font-size:1.05rem; }
        .chips { margin-top:10px; display:flex; flex-wrap:wrap; gap:7px; }
        .chip { border:1px solid #cfe0eb; background:#edf6f3; color:#24516b; border-radius:999px; font-size:0.77rem; padding:6px 10px; }
        .rooms-grid { margin-top:10px; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .room-card { border:1px solid #dbe7f0; border-radius:12px; overflow:hidden; background:#fbfdff; }
        .room-card img { width:100%; height:140px; object-fit:cover; background:#edf4fb; display:block; }
        .room-body { padding:10px; display:grid; gap:5px; }
        .room-body h3 { margin:0; font-size:0.95rem; }
        .muted { color:var(--muted); font-size:0.82rem; }
        .cta { display:inline-block; margin-top:4px; text-decoration:none; border:1px solid #d9b06f; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#603b0c; border-radius:10px; padding:8px 11px; font-weight:700; font-size:0.82rem; }
        @media (max-width: 980px) { .gallery { grid-template-columns:repeat(2,minmax(0,1fr)); } .rooms-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width: 680px) { .gallery, .rooms-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    @php
        $propertyMedia = $propertyMedia ?? collect();
        $rooms = $rooms ?? collect();
        $roomMediaByRoom = $roomMediaByRoom ?? collect();
        $propertyFacilities = $propertyFacilities ?? collect();
        $locationLine = trim((string) ($locationLine ?? ''));
        $ratingValue = (float) ($ratingValue ?? 0);
        $ratingUsers = (int) ($ratingUsers ?? 0);
        $prefill = $prefill ?? ['checkin' => '', 'checkout' => '', 'adults' => 2, 'children' => 0];
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
        $basePrice = number_format((float) ($property->base_price ?? 0), 2);
    @endphp

    <main class="page">
        <section class="hero" aria-label="Property summary">
            <h1>{{ (string) ($property->name ?? 'Property') }}</h1>
            <p class="sub">{{ $locationLine !== '' ? $locationLine : 'Address details will be updated shortly.' }}</p>
            <p class="meta">{{ $ratingValue > 0 ? ('Rating ' . number_format($ratingValue, 1) . ' / 5') : 'No rating yet' }}{{ $ratingUsers > 0 ? (' • ' . $ratingUsers . ' reviews') : '' }} • From {{ $currency }} {{ $basePrice }}</p>
        </section>

        <section class="section" aria-label="Property gallery">
            <h2>Property Gallery</h2>
            <div class="gallery">
                @forelse ($propertyMedia->take(8) as $media)
                    @php $imageUrl = $mediaUrl($media, 'banner'); @endphp
                    <img src="{{ $imageUrl }}" alt="Property image" loading="lazy">
                @empty
                    <img src="" alt="No image" loading="lazy">
                @endforelse
            </div>
        </section>

        <section class="section" aria-label="Property facilities">
            <h2>Facilities</h2>
            <div class="chips">
                @forelse ($propertyFacilities->take(20) as $facility)
                    <span class="chip">{{ $facility }}</span>
                @empty
                    <span class="chip">Facility details will be updated soon.</span>
                @endforelse
            </div>
        </section>

        <section class="section" aria-label="Available rooms">
            <h2>Rooms</h2>
            <div class="rooms-grid">
                @forelse ($rooms as $room)
                    @php
                        $roomId = (int) ($room->id ?? 0);
                        $roomMedia = collect($roomMediaByRoom->get($roomId, collect()));
                        $roomThumb = $roomMedia->isNotEmpty() ? $mediaUrl($roomMedia->first(), 'thumb') : null;
                        $amenitiesText = strtolower((string) ($room->amenities ?? ''));
                        $hasBreakfast = str_contains($amenitiesText, 'breakfast');
                        $roomPrice = number_format((float) ($room->base_price ?? 0), 2);
                    @endphp
                    <article class="room-card">
                        <img src="{{ $roomThumb ?? '' }}" alt="{{ (string) ($room->name ?? 'Room') }}" loading="lazy">
                        <div class="room-body">
                            <h3>{{ (string) ($room->name ?? 'Room') }}</h3>
                            <span class="muted">Occupancy: {{ (int) ($room->max_occupancy ?? 1) }} guests</span>
                            <span class="muted">{{ $hasBreakfast ? 'Breakfast included' : 'Breakfast optional' }} • {{ strtoupper((string) ($room->currency ?? $currency)) }} {{ $roomPrice }}</span>
                            <a class="cta" href="/room/{{ $roomId }}?checkin={{ urlencode((string) ($prefill['checkin'] ?? '')) }}&checkout={{ urlencode((string) ($prefill['checkout'] ?? '')) }}&adults={{ (int) ($prefill['adults'] ?? 2) }}&children={{ (int) ($prefill['children'] ?? 0) }}">Book Now</a>
                        </div>
                    </article>
                @empty
                    <article class="room-card"><div class="room-body"><h3>No rooms yet</h3><span class="muted">Room inventory for this property will be published soon.</span></div></article>
                @endforelse
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
