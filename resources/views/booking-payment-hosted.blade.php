<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Payment | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @include('partials.uniform-buttons')
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; background:var(--bg); color:var(--ink); }
        .page { width:min(760px,calc(100% - 24px)); margin:24px auto; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:18px; display:grid; gap:14px; }
        .eyebrow { margin:0; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#557185; font-weight:700; }
        h1 { margin:0; font-size:1.3rem; color:#173d55; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .sub { margin:0; color:#4f6d82; line-height:1.55; font-size:0.9rem; }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .cell { border:1px solid #dbe7f0; border-radius:12px; background:#fbfdff; padding:12px; display:grid; gap:4px; }
        .k { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.07em; color:#58708a; font-weight:700; }
        .v { font-size:0.92rem; font-weight:700; color:#173d54; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn { appearance:none; border:1px solid #0f6179; background:#0f6179; color:#ffffff; border-radius:10px; padding:11px 16px; font:inherit; font-weight:700; cursor:pointer; text-decoration:none; }
        .btn.alt { border-color:#cfe0eb; background:#ffffff; color:#1c4259; }
        @media (max-width: 720px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    @php
        $paymentPayload = json_decode((string) ($reservation->payment_payload_json ?? ''), true);
        if (!is_array($paymentPayload)) {
            $paymentPayload = [];
        }
        $externalHandoff = (bool) ($externalHandoff ?? false);
        $externalHandoffUrl = trim((string) ($externalHandoffUrl ?? ''));
        $externalHandoffProvider = trim((string) ($externalHandoffProvider ?? ($paymentPayload['provider_label'] ?? 'Gateway')));
        $gatewayLabel = trim((string) ($paymentPayload['gateway_label'] ?? 'Card Gateway'));
        $paymentCurrency = strtoupper(trim((string) ($reservation->payment_currency ?? $reservation->currency ?? 'MVR')));
        $paymentAmount = number_format((float) ($reservation->payment_amount ?? $reservation->total_amount ?? 0), 2);
        $sourceCurrency = strtoupper(trim((string) ($paymentPayload['source_currency'] ?? $reservation->currency ?? 'MVR')));
        $sourceAmount = number_format((float) ($paymentPayload['source_amount'] ?? $reservation->total_amount ?? 0), 2);

        $paymentNotes = json_decode((string) ($reservation->notes ?? ''), true);
        if (!is_array($paymentNotes)) {
            $paymentNotes = [];
        }
        $headerCategorySource = trim((string) ($paymentNotes['category_key'] ?? ($property->listing_category ?? 'accommodation')));
        $headerCategoryKey = str_replace('_', '-', strtolower($headerCategorySource !== '' ? $headerCategorySource : 'accommodation'));
        $headerCategoryLinks = [
            ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas', 'url' => '/catalog/accommodation'],
            ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'subtitle' => 'Day-use resort offers', 'url' => '/catalog/resort_day_visit'],
            ['key' => 'excursion', 'icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'subtitle' => 'Tours and activities', 'url' => '/catalog/excursion'],
            ['key' => 'water-sports', 'icon' => 'fa-solid fa-person-swimming', 'title' => 'Water Sports', 'subtitle' => 'Diving, snorkelling and sea fun', 'url' => '/catalog/water_sports'],
            ['key' => 'restaurant', 'icon' => 'fa-solid fa-utensils', 'title' => 'Restaurants', 'subtitle' => 'Dining experiences', 'url' => '/catalog/restaurant'],
            ['key' => 'marine-transport', 'icon' => 'fa-solid fa-water', 'title' => 'Sea Transport', 'subtitle' => 'Speedboats & water transfers', 'url' => '/catalog/marine-transport'],
            ['key' => 'land-transport', 'icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers', 'url' => '/catalog/land-transport'],
            ['key' => 'vehicle-rental', 'icon' => 'fa-solid fa-car', 'title' => 'Vehicle Rentals', 'subtitle' => 'Cars and local rentals', 'url' => '/catalog/vehicle_rental'],
            ['key' => 'remote-workspace', 'icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces', 'url' => '/catalog/remote_workspace'],
            ['key' => 'conference-room', 'icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces', 'url' => '/catalog/conference_room'],
            ['key' => 'blog', 'icon' => 'fa-solid fa-newspaper', 'title' => 'Blog', 'subtitle' => 'Travel stories and picks', 'url' => '/blog'],
        ];
    @endphp

    @include('partials.customer-uniform-header', [
        'injectUniformHeaderStyles' => true,
        'injectUniformHeaderScripts' => true,
        'headerNeedsSpacer' => false,
        'headerHideOnScroll' => true,
        'headerShowSearch' => false,
        'headerSearchAction' => '/catalog/' . $headerCategoryKey,
        'headerSearchValue' => '',
        'headerCategoryLinks' => $headerCategoryLinks,
        'headerActiveCategoryKey' => $headerCategoryKey,
        'headerContinueUrl' => (string) request()->fullUrl(),
    ])

    <main class="page">
        @include('partials.booking-process-highlights', [
            'bookingProcessCurrentStep' => 4,
            'bookingProcessBackUrl' => '/booking/checkout/' . (int) ($reservation->id ?? 0),
            'bookingProcessNextText' => $externalHandoff
                ? 'You can continue to the external gateway or safely return to checkout.'
                : 'Final step on this page: submit the payment confirmation and finish the reservation.',
        ])

        <section class="panel">
            <p class="eyebrow">{{ $externalHandoff ? 'External Gateway Handoff' : 'Internal Hosted Payment' }}</p>
            <h1>{{ $externalHandoff ? ('Continue to ' . $externalHandoffProvider . ' Checkout') : 'Secure Payment Confirmation (Simulator)' }}</h1>
            <p class="sub">
                {{ $externalHandoff
                    ? 'Use Continue to open the bank checkout page. If you need to edit details or change payment method, use Back to Checkout.'
                    : 'This page appears only when the selected gateway is running in internal mode. Live external gateways bypass this screen and redirect directly to the provider checkout page.' }}
            </p>

            <div class="grid">
                <div class="cell"><span class="k">Property</span><span class="v">{{ (string) ($property->name ?? 'Property') }}</span></div>
                <div class="cell"><span class="k">Reservation</span><span class="v">#{{ (int) ($reservation->id ?? 0) }}</span></div>
                <div class="cell"><span class="k">Gateway</span><span class="v">{{ $gatewayLabel }}</span></div>
                <div class="cell"><span class="k">Amount</span><span class="v">{{ $paymentCurrency }} {{ $paymentAmount }}</span></div>
                <div class="cell"><span class="k">Booking Amount</span><span class="v">{{ $sourceCurrency }} {{ $sourceAmount }}</span></div>
            </div>

            <div class="actions">
                @if ($externalHandoff && $externalHandoffUrl !== '')
                    <a class="btn primary" href="{{ $externalHandoffUrl }}" rel="noopener">Continue to {{ $externalHandoffProvider }}</a>
                @else
                    <form method="post" action="/booking/payment/hosted/{{ (int) ($reservation->id ?? 0) }}/complete">
                        @csrf
                        <input type="hidden" name="intent_id" value="{{ $intentId }}">
                        <input type="hidden" name="payment_reference" value="SIM-{{ (int) ($reservation->id ?? 0) }}">
                            <button class="btn primary" type="submit">Complete Payment</button>
                    </form>
                @endif
                <a class="btn alt" href="/booking/checkout/{{ (int) ($reservation->id ?? 0) }}">Back to Checkout</a>
            </div>
        </section>
    </main>
</body>
</html>