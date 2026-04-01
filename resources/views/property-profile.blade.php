<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Property') }} | Workation Maldives</title>
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
            background:
                radial-gradient(circle at 8% 0%, #d8ece6 0, #d8ece600 30%),
                radial-gradient(circle at 92% 6%, #ffe7c6 0, #ffe7c600 30%),
                var(--bg);
        }

        .page { width: min(1180px, calc(100% - 24px)); margin: 14px auto 28px; }

        .hero {
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(130deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: 18px;
            box-shadow: 0 20px 36px rgba(15, 88, 113, 0.22);
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .hero h1 { margin: 0; font-size: clamp(1.45rem, 2.8vw, 2.25rem); }
        .hero .sub { margin: 7px 0 0; font-size: 0.92rem; color: #d8f4f8; }

        .hero-rating {
            border: 1px solid rgba(225, 248, 252, 0.4);
            border-radius: 12px;
            padding: 8px 11px;
            background: rgba(6, 70, 87, 0.26);
            font-size: 0.82rem;
            color: #ebfbff;
            white-space: nowrap;
        }

        .hero-stats {
            margin-top: 11px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .hero-stat {
            border: 1px solid rgba(225, 248, 252, 0.38);
            border-radius: 11px;
            background: rgba(7, 74, 93, 0.23);
            padding: 9px 10px;
        }

        .hero-stat .k { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #cfeff4; }
        .hero-stat .v { margin-top: 2px; font-size: 0.9rem; font-weight: 700; color: #f1fcff; }

        .layout {
            margin-top: 12px;
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(270px, 0.9fr);
            gap: 12px;
            align-items: start;
        }

        .section {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 14px;
        }

        .section h2 { margin: 0; font-size: 1.04rem; }

        .gallery {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
        }

        .gallery-item {
            border-radius: 11px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
        }

        .gallery-item:first-child {
            grid-column: span 3;
            grid-row: span 2;
        }

        .gallery-item img { width: 100%; height: 100%; min-height: 100px; object-fit: cover; display: block; }

        .about {
            display: grid;
            gap: 12px;
            position: sticky;
            top: 10px;
        }

        .summary-card {
            border: 1px solid #d4e5ef;
            border-radius: 14px;
            background: #f8fcff;
            padding: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px dashed #d6e6ef;
            font-size: 0.85rem;
            color: #33536a;
        }

        .summary-row:last-child { border-bottom: 0; }

        .chips {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .chip {
            border: 1px solid #cfe0eb;
            background: #edf6f3;
            color: #24516b;
            border-radius: 999px;
            font-size: 0.76rem;
            padding: 6px 10px;
        }

        .rooms-section { margin-top: 12px; }
        .rooms-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .rooms-sub { margin: 0; color: #5d7487; font-size: 0.83rem; }

        .rooms-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .room-card {
            border: 1px solid #dbe7f0;
            border-radius: 13px;
            overflow: hidden;
            background: #fbfdff;
            display: grid;
            grid-template-rows: 150px auto;
        }

        .room-media {
            position: relative;
            background: linear-gradient(135deg, #d9ebf4 0%, #f0f7fc 100%);
        }

        .room-media img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .room-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 999px;
            background: rgba(14, 70, 92, 0.72);
            color: #f2fcff;
            font-size: 0.71rem;
            padding: 4px 8px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .room-body { padding: 10px; display: grid; gap: 6px; }
        .room-body h3 { margin: 0; font-size: 0.95rem; color: #153f59; }
        .muted { color: var(--muted); font-size: 0.8rem; line-height: 1.35; }

        .amenity-mini {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .amenity-mini span {
            border: 1px solid #d8e6ef;
            border-radius: 999px;
            background: #f4f9fc;
            color: #3c5b72;
            font-size: 0.72rem;
            padding: 4px 8px;
        }

        .room-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 4px;
        }

        .room-price {
            font-size: 0.88rem;
            color: #143c55;
            font-weight: 700;
        }

        .cta {
            display: inline-block;
            text-decoration: none;
            border: 1px solid #d9b06f;
            background: linear-gradient(135deg, #ffc76f 0%, var(--accent) 100%);
            color: #603b0c;
            border-radius: 10px;
            padding: 8px 11px;
            font-weight: 700;
            font-size: 0.81rem;
        }

        @media (max-width: 1080px) {
            .layout { grid-template-columns: 1fr; }
            .about { position: static; }
            .gallery { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .gallery-item:first-child { grid-column: span 2; }
            .rooms-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 680px) {
            .hero-stats { grid-template-columns: 1fr; }
            .gallery { grid-template-columns: 1fr 1fr; }
            .gallery-item:first-child { grid-column: span 2; }
            .rooms-grid { grid-template-columns: 1fr; }
        }
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
        $description = trim((string) ($property->description ?? ''));
        $listingCategory = strtoupper(str_replace('_', ' ', (string) ($property->listing_category ?? 'ACCOMMODATION')));
    @endphp

    <main class="page">
        <section class="hero" aria-label="Property summary">
            <div class="hero-top">
                <div>
                    <h1>{{ (string) ($property->name ?? 'Property') }}</h1>
                    <p class="sub">{{ $locationLine !== '' ? $locationLine : 'Address details will be updated shortly.' }}</p>
                </div>
                <div class="hero-rating">
                    {{ $ratingValue > 0 ? ('★ ' . number_format($ratingValue, 1) . ' / 5') : 'No rating yet' }}{{ $ratingUsers > 0 ? (' • ' . $ratingUsers . ' reviews') : '' }}
                </div>
            </div>

            <div class="hero-stats">
                <div class="hero-stat"><div class="k">Category</div><div class="v">{{ $listingCategory }}</div></div>
                <div class="hero-stat"><div class="k">Starting Price</div><div class="v">{{ $currency }} {{ $basePrice }}</div></div>
                <div class="hero-stat"><div class="k">Available Rooms</div><div class="v">{{ $rooms->count() }}</div></div>
            </div>
        </section>

        <div class="layout">
            <div>
                <section class="section" aria-label="Property gallery">
                    <h2>Property Gallery</h2>
                    <div class="gallery">
                        @forelse ($propertyMedia->take(9) as $media)
                            @php $imageUrl = $mediaUrl($media, 'banner'); @endphp
                            <div class="gallery-item">
                                <img src="{{ $imageUrl }}" alt="Property image" loading="lazy">
                            </div>
                        @empty
                            <div class="gallery-item"><img src="" alt="No image" loading="lazy"></div>
                            <div class="gallery-item"><img src="" alt="No image" loading="lazy"></div>
                        @endforelse
                    </div>
                </section>

                <section class="section" aria-label="Property facilities" style="margin-top:12px;">
                    <h2>Facilities</h2>
                    <div class="chips">
                        @forelse ($propertyFacilities->take(24) as $facility)
                            <span class="chip">{{ $facility }}</span>
                        @empty
                            <span class="chip">Facility details will be updated soon.</span>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="about" aria-label="Property details">
                <section class="summary-card">
                    <h2 style="margin:0 0 8px; font-size:0.98rem;">Property Snapshot</h2>
                    <div class="summary-row"><span>Location</span><strong>{{ $locationLine !== '' ? $locationLine : 'To be updated' }}</strong></div>
                    <div class="summary-row"><span>Price From</span><strong>{{ $currency }} {{ $basePrice }}</strong></div>
                    <div class="summary-row"><span>Rooms</span><strong>{{ $rooms->count() }}</strong></div>
                    <div class="summary-row"><span>Rating</span><strong>{{ $ratingValue > 0 ? number_format($ratingValue, 1) . ' / 5' : 'N/A' }}</strong></div>
                </section>

                <section class="summary-card">
                    <h2 style="margin:0 0 6px; font-size:0.98rem;">About This Property</h2>
                    <p class="muted" style="margin:0;">
                        {{ $description !== '' ? Str::limit($description, 380) : 'Detailed overview will be published by the host soon. You can still explore room options and proceed with reservation.' }}
                    </p>
                </section>
            </aside>
        </div>

        <section class="section rooms-section" aria-label="Available rooms">
            <div class="rooms-head">
                <h2>Available Rooms</h2>
                <p class="rooms-sub">Choose a room type to view full profile and proceed with booking options.</p>
            </div>
            <div class="rooms-grid">
                @forelse ($rooms as $room)
                    @php
                        $roomId = (int) ($room->id ?? 0);
                        $roomMedia = collect($roomMediaByRoom->get($roomId, collect()));
                        $roomThumb = $roomMedia->isNotEmpty() ? $mediaUrl($roomMedia->first(), 'thumb') : null;
                        $amenitiesText = strtolower((string) ($room->amenities ?? ''));
                        $hasBreakfast = str_contains($amenitiesText, 'breakfast');
                        $roomPrice = number_format((float) ($room->base_price ?? 0), 2);
                        $amenities = collect([
                                (string) ($room->bed_type ?? ''),
                                ...(preg_split('/[,\n]+/', (string) ($room->amenities ?? '')) ?: []),
                                ...(preg_split('/[,\n]+/', (string) ($room->bathroom_amenities ?? '')) ?: []),
                            ])
                            ->map(static fn ($item) => trim((string) $item))
                            ->filter(static fn ($item) => $item !== '')
                            ->unique()
                            ->take(3)
                            ->values();
                    @endphp
                    <article class="room-card">
                        <div class="room-media">
                            <img src="{{ $roomThumb ?? '' }}" alt="{{ (string) ($room->name ?? 'Room') }}" loading="lazy">
                            <span class="room-tag">{{ $hasBreakfast ? 'Breakfast Included' : 'Breakfast Optional' }}</span>
                        </div>
                        <div class="room-body">
                            <h3>{{ (string) ($room->name ?? 'Room') }}</h3>
                            <span class="muted">Occupancy: {{ (int) ($room->max_occupancy ?? 1) }} guests</span>
                            @if ($amenities->isNotEmpty())
                                <div class="amenity-mini">
                                    @foreach ($amenities as $amenity)
                                        <span>{{ $amenity }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="room-footer">
                                <span class="room-price">{{ strtoupper((string) ($room->currency ?? $currency)) }} {{ $roomPrice }}</span>
                                <a class="cta" href="/room/{{ $roomId }}?checkin={{ urlencode((string) ($prefill['checkin'] ?? '')) }}&checkout={{ urlencode((string) ($prefill['checkout'] ?? '')) }}&adults={{ (int) ($prefill['adults'] ?? 2) }}&children={{ (int) ($prefill['children'] ?? 0) }}">Book Now</a>
                            </div>
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
