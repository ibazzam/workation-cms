<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($room->name ?? 'Room') }} | Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1060px,calc(100% - 24px)); margin:14px auto 28px; }
        .hero { border:1px solid #cbe0ea; border-radius:18px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; padding:18px; }
        .hero h1 { margin:0; font-size:clamp(1.2rem,2.3vw,1.9rem); }
        .hero p { margin:6px 0 0; color:#daf5f9; font-size:0.9rem; }
        .section { margin-top:12px; border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:14px; }
        .section h2 { margin:0 0 8px; font-size:1.03rem; }
        .gallery { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .gallery img { width:100%; height:190px; object-fit:cover; border-radius:12px; border:1px solid #cfe1ec; background:#eff7fb; }
        .chips { display:flex; flex-wrap:wrap; gap:7px; }
        .chip { border:1px solid #cfe0eb; background:#edf6f3; color:#24516b; border-radius:999px; font-size:0.77rem; padding:6px 10px; }
        .booking-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .field { display:grid; gap:5px; }
        .field label { font-size:0.78rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; }
        .field input, .field select { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; font:inherit; background:#f8fdff; }
        .summary { margin-top:8px; color:#3f5a72; font-size:0.86rem; }
        .submit { margin-top:10px; border:1px solid #d9b06f; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#603b0c; border-radius:10px; padding:10px 14px; font:inherit; font-weight:700; cursor:pointer; }
        @media (max-width: 900px) { .gallery, .booking-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    @php
        $roomMedia = $roomMedia ?? collect();
        $roomFeatures = $roomFeatures ?? collect();
        $transferOptions = $transferOptions ?? collect();
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $prefill = $prefill ?? ['checkin' => '', 'checkout' => '', 'adults' => 2, 'children' => 0];
        $currency = strtoupper(trim((string) ($room->currency ?? $property->currency ?? 'MVR')));
        $basePrice = number_format((float) ($room->base_price ?? 0), 2);
    @endphp

    <main class="page">
        <section class="hero" aria-label="Room summary">
            <h1>{{ (string) ($room->name ?? 'Room') }}</h1>
            <p>{{ (string) ($property->name ?? 'Property') }} • Base rate {{ $currency }} {{ $basePrice }}</p>
        </section>

        <section class="section" aria-label="Room gallery">
            <h2>Room Gallery</h2>
            <div class="gallery">
                @forelse ($roomMedia->take(9) as $media)
                    @php $img = $mediaUrl($media, 'banner'); @endphp
                    <img src="{{ $img }}" alt="Room image" loading="lazy">
                @empty
                    <img src="" alt="No image" loading="lazy">
                @endforelse
            </div>
        </section>

        <section class="section" aria-label="Room features">
            <h2>Room Features & Amenities</h2>
            <div class="chips">
                @forelse ($roomFeatures->take(24) as $feature)
                    <span class="chip">{{ $feature }}</span>
                @empty
                    <span class="chip">Amenities will be updated soon.</span>
                @endforelse
            </div>
        </section>

        <section class="section" aria-label="Booking options">
            <h2>Book This Room</h2>
            <form method="POST" action="/booking/reserve">
                @csrf
                <input type="hidden" name="property_id" value="{{ (int) ($property->id ?? 0) }}">
                <input type="hidden" name="room_id" value="{{ (int) ($room->id ?? 0) }}">

                <div class="booking-grid">
                    <div class="field"><label for="checkin">Check-in</label><input id="checkin" name="checkin" type="date" required value="{{ (string) ($prefill['checkin'] ?? '') }}"></div>
                    <div class="field"><label for="checkout">Check-out</label><input id="checkout" name="checkout" type="date" required value="{{ (string) ($prefill['checkout'] ?? '') }}"></div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ (int) ($prefill['adults'] ?? 2) }}" required></div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ (int) ($prefill['children'] ?? 0) }}"></div>
                    <div class="field">
                        <label for="transferOption">Prepared Transfer Option</label>
                        <select id="transferOption" name="transfer_option">
                            @foreach ($transferOptions as $option)
                                <option value="{{ (string) ($option['code'] ?? '') }}" data-charge="{{ (float) ($option['charge'] ?? 0) }}">
                                    {{ (string) ($option['label'] ?? 'Transfer') }} ({{ $currency }} {{ number_format((float) ($option['charge'] ?? 0), 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="transferCharge">Transfer Charge</label><input id="transferCharge" name="transfer_charge" type="number" min="0" step="0.01" value="{{ number_format((float) ($transferOptions->first()['charge'] ?? 0), 2, '.', '') }}"></div>
                </div>

                <p class="summary">Proceeding will prepare your reservation and take you to checkout confirmation.</p>
                <button class="submit" type="submit">Proceed to Booking & Reservation</button>
            </form>
        </section>

        @include('partials.global-site-footer')
    </main>

    <script>
        (function () {
            const transferOption = document.getElementById('transferOption');
            const transferCharge = document.getElementById('transferCharge');
            if (!transferOption || !transferCharge) {
                return;
            }

            transferOption.addEventListener('change', function () {
                const selected = transferOption.options[transferOption.selectedIndex];
                const charge = Number(selected?.dataset?.charge || 0);
                transferCharge.value = charge.toFixed(2);
            });
        })();
    </script>
</body>
</html>
