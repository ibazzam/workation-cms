<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($room->name ?? 'Room') }} | Workation</title>
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
        .booking-layout { display:grid; grid-template-columns:300px minmax(0,1fr); gap:12px; align-items:start; }
        .booking-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .booking-page-header { padding:12px 16px; border:1px solid #cbe0ea; border-radius:16px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; margin-bottom:12px; }
        .bph-back { font-size:0.76rem; color:#cfeff4; text-decoration:none; }
        .bph-back:hover { text-decoration:underline; }
        .bph-name { font-size:0.78rem; color:#cfeff4; text-transform:uppercase; letter-spacing:0.07em; margin-top:4px; }
        .bph-room { font-size:1.35rem; font-weight:800; margin-top:3px; }
        .sidebar-summary { border:1px solid #d0e4ef; border-radius:16px; background:#f7fbff; overflow:hidden; display:grid; gap:0; align-content:start; position:sticky; top:12px; max-height:calc(100vh - 24px); overflow-y:auto; }
        .sum-section { border-bottom:1px solid #dde9f2; padding:12px 14px; display:grid; gap:6px; }
        .sum-section:last-child { border-bottom:0; }
        .sum-title { margin:0; font-size:0.69rem; text-transform:uppercase; letter-spacing:0.09em; color:#3c6480; font-family:"Space Grotesk","Trebuchet MS",sans-serif; display:flex; align-items:center; gap:6px; }
        .sum-title-number { width:18px; height:18px; border-radius:999px; background:#1a6d8a; color:#fff; font-size:0.64rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex:0 0 18px; }
        .sum-prop-name { font-size:0.9rem; font-weight:700; color:#1b3f58; }
        .sum-room-name { font-size:0.82rem; color:#336077; font-weight:600; }
        .sum-room-meta { font-size:0.74rem; color:#5c7488; }
        .sum-compact-line { display:flex; justify-content:space-between; gap:8px; font-size:0.79rem; color:#3b5c73; padding:2px 0; }
        .sum-compact-line strong { color:#1f465f; font-weight:600; }
        .sum-total { margin-top:6px; border:1px solid #cfe0eb; border-radius:10px; background:#edf6f3; padding:9px 10px; display:flex; justify-content:space-between; font-weight:700; color:#21475f; font-size:0.88rem; }
        .sum-date-line { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .sum-date-box { border:1px solid #dbe9f2; border-radius:8px; background:#f0f7fc; padding:8px 10px; }
        .sum-date-label { font-size:0.66rem; text-transform:uppercase; letter-spacing:0.07em; color:#5c7488; }
        .sum-date-value { font-size:0.86rem; font-weight:700; color:#1b3f58; margin-top:2px; }
        .sum-date-time { font-size:0.72rem; color:#4d6e84; margin-top:1px; }
        .sum-nights-badge { font-size:0.77rem; color:#336077; font-weight:600; text-align:center; padding:4px 0; background:#e8f3fa; border-radius:6px; }
        .sum-policy-ul { margin:0; padding-left:16px; color:#4a687e; font-size:0.78rem; line-height:1.55; }
        .sum-policy-text { margin:0 0 4px; color:#4a687e; font-size:0.78rem; line-height:1.5; }
        .sum-policy-text:last-child { margin-bottom:0; }
        .booking-form-wrap { display:grid; gap:12px; }
        .booking-form-title { margin:0; border:1px solid #dbe7f0; border-radius:14px; background:linear-gradient(135deg,#f3f8fc 0%,#edf5fb 100%); padding:12px 16px; font-size:1.04rem; font-weight:700; color:#153f59; display:flex; align-items:center; gap:10px; }
        .guest-form-stack { display:grid; gap:12px; }
        .booking-subsection { border:1px solid #dbe7f0; border-radius:12px; background:#fcfeff; padding:12px; display:grid; gap:10px; }
        .booking-subtitle { margin:0; font-size:0.98rem; color:#163f59; font-weight:700; }
        .booking-subnote { margin:0; color:#4f6a7f; font-size:0.8rem; line-height:1.45; }
        .helper { margin:0; color:#5c7488; font-size:0.76rem; }
        .add-guest-btn { border:1px dashed #9eb9ca; background:#f5fbff; color:#295571; border-radius:9px; padding:8px 10px; font-size:0.8rem; font-weight:600; width:max-content; }
        .inline-choices { display:flex; flex-wrap:wrap; gap:8px; }
        .choice-pill { border:1px solid #c9dceb; background:#fff; border-radius:999px; padding:6px 10px; font-size:0.78rem; color:#35586f; }
        .choice-pill input { margin-right:6px; }
        .promo-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:8px; }
        .promo-apply { border:1px solid #2f6ed8; background:#2f6ed8; color:#fff; border-radius:9px; padding:0 12px; font-weight:700; }
        .promo-chip { display:inline-block; border:1px solid #cfe0eb; background:#edf6f3; color:#24516b; border-radius:999px; font-size:0.74rem; padding:4px 8px; }
        .pay-icons { display:flex; gap:8px; flex-wrap:wrap; }
        .pay-icon { border:1px solid #d3e2ec; border-radius:8px; background:#fff; padding:6px 10px; font-size:0.78rem; color:#254e67; }
        .card-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .card-grid .field.full { grid-column:1/-1; }
        .legal-note { margin:0; color:#5a7184; font-size:0.75rem; line-height:1.45; }
        .field { display:grid; gap:5px; }
        .field label { font-size:0.78rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; }
        .field input, .field select { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; font:inherit; background:#f8fdff; }
        .field textarea { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; font:inherit; background:#f8fdff; min-height:90px; resize:vertical; }
        .field .input-error { border-color:#c54f4f; background:#fff8f8; }
        .field .error-text { margin:0; font-size:0.75rem; color:#a32929; }
        .field.full { grid-column:1/-1; }
        .form-errors { margin:0 0 10px; border:1px solid #e6b2b2; background:#fff5f5; color:#8f2323; border-radius:10px; padding:10px 12px; }
        .form-errors ul { margin:0; padding-left:18px; }
        .summary { margin-top:8px; color:#3f5a72; font-size:0.86rem; }
        .submit { margin-top:10px; border:1px solid #d9b06f; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#603b0c; border-radius:10px; padding:10px 14px; font:inherit; font-weight:700; cursor:pointer; }
        .invoice { border:1px solid #dbe7f0; border-radius:14px; background:#fbfdff; padding:12px; }
        .invoice h3 { margin:0 0 8px; font-size:0.96rem; }
        .invoice-row { display:flex; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px dashed #d6e4ee; font-size:0.83rem; color:#36586f; }
        .invoice-row:last-child { border-bottom:0; }
        .invoice-total { margin-top:8px; border:1px solid #cfe0eb; border-radius:10px; background:#edf6f3; padding:9px 10px; display:flex; justify-content:space-between; font-weight:700; color:#21475f; }
        .policy-box { margin-top:10px; border:1px solid #d5e4ee; border-radius:10px; background:#f7fbff; padding:9px; }
        .policy-box h4 { margin:0 0 6px; font-size:0.79rem; text-transform:uppercase; letter-spacing:0.07em; color:#47647a; }
        .policy-box ul { margin:0; padding-left:18px; color:#48677f; font-size:0.8rem; }
        .policy-box p { margin:0; color:#48677f; font-size:0.8rem; line-height:1.4; }
        @media (max-width: 960px) { .booking-layout { grid-template-columns:260px minmax(0,1fr); } }
        @media (max-width: 900px) { .gallery, .booking-grid { grid-template-columns:1fr; } .booking-layout { grid-template-columns:1fr; } .sidebar-summary { position:static; max-height:none; } }
    </style>
</head>
<body>
    @php
        $roomMedia = $roomMedia ?? collect();
        $roomFeatures = $roomFeatures ?? collect();
        $transferOptions = $transferOptions ?? collect();
        $pricingConfig = $pricingConfig ?? ['tax_rate' => 16, 'discount_percent' => 0];
        $bookingPolicies = $bookingPolicies ?? ['inclusives' => [], 'cancellation_policy' => 'Standard cancellation terms apply.'];
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $prefill = $prefill ?? ['checkin' => '', 'checkout' => '', 'adults' => 2, 'children' => 0];
        $currency = strtoupper(trim((string) ($room->currency ?? $property->currency ?? 'MVR')));
        $basePrice = number_format((float) ($room->base_price ?? 0), 2);
        $basePriceRaw = (float) ($room->base_price ?? 0);
        $taxRate = (float) ($pricingConfig['tax_rate'] ?? 16);
        $discountPercent = (float) ($pricingConfig['discount_percent'] ?? 0);
        $inclusives = collect($bookingPolicies['inclusives'] ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values();
        $cancellationPolicy = trim((string) ($bookingPolicies['cancellation_policy'] ?? 'Standard cancellation terms apply.'));
        $roomHeroImage = $roomMedia->isNotEmpty() ? ($mediaUrl($roomMedia->first(), 'banner') ?? $mediaUrl($roomMedia->first(), 'thumb')) : null;
        $roomBedLabel = trim((string) ($room->bed_type ?? '1 bed'));
        $roomSize = (int) ($room->room_size_sqm ?? 0);
        $roomFloor = trim((string) ($room->floor_info ?? ''));
        $roomNonSmoking = (int) ($room->non_smoking ?? 1) === 1;
        $roomHasWindow = (int) ($room->has_window ?? 1) === 1;
        $roomHasWifi = $roomFeatures->contains(static fn ($feature) => str_contains(strtolower((string) $feature), 'wifi') || str_contains(strtolower((string) $feature), 'wi-fi'));
        $roomCheckinStart = '15:00';
        $roomCheckinEnd = '06:00';
        $roomCheckoutBefore = '12:00';
        $checkinDate = trim((string) ($prefill['checkin'] ?? ''));
        $checkoutDate = trim((string) ($prefill['checkout'] ?? ''));
        $parsedCheckin = $checkinDate !== '' ? \Carbon\Carbon::parse($checkinDate) : null;
        $parsedCheckout = $checkoutDate !== '' ? \Carbon\Carbon::parse($checkoutDate) : null;
        $stayNights = ($parsedCheckin && $parsedCheckout) ? max(1, $parsedCheckin->diffInDays($parsedCheckout)) : 1;
        $stayDateRange = ($parsedCheckin && $parsedCheckout)
            ? ($parsedCheckin->format('D, M j') . ' - ' . $parsedCheckout->format('D, M j'))
            : 'Select check-in and check-out dates';
        $cancelDeadlineLabel = $parsedCheckin
            ? $parsedCheckin->copy()->subDay()->format('H:i, M j, Y')
            : '23:59, one day before check-in';
        $ratingValue = collect(['review_score', 'rating_average', 'average_rating', 'rating'])
            ->map(static fn ($column) => (float) ($property->{$column} ?? 0))
            ->first(static fn ($value) => $value > 0) ?: 9.3;
        $ratingOutOfTen = min(10, $ratingValue > 5 ? $ratingValue : ($ratingValue * 2));
        $ratingLabel = $ratingOutOfTen >= 9 ? 'Great' : ($ratingOutOfTen >= 8 ? 'Very Good' : 'Good');
        $ratingCount = (int) (collect(['review_count', 'rating_count', 'total_reviews'])
            ->map(static fn ($column) => (int) ($property->{$column} ?? 0))
            ->first(static fn ($value) => $value > 0) ?: 2508);
        $roomChildPolicy = trim((string) ($room->child_policy ?? 'Children of all ages can stay in this room. Additional fees may be charged for children using existing beds.'));
        $roomExtraBedPolicy = trim((string) ($room->extra_bed_policy ?? 'Extra beds and cots are not available for this room type.'));
    @endphp

    <main class="page">
        <header class="booking-page-header" aria-label="Property and room">
            <a class="bph-back" href="{{ url()->previous('/') }}">&larr; Back to property</a>
            <p class="bph-name">{{ (string) ($property->name ?? 'Property') }}</p>
            <p class="bph-room">{{ (string) ($room->name ?? 'Room') }} &middot; {{ $roomBedLabel }}{{ $roomSize > 0 ? ' &middot; ' . $roomSize . '&#13217;' : '' }}</p>
        </header>

        <section class="section" aria-label="Booking">
            <div class="booking-layout">

                <aside class="sidebar-summary" aria-label="Booking summary">

                    <section class="sum-section" aria-label="Property and room">
                        <h2 class="sum-title"><span class="sum-title-number">1</span> Property &amp; Room</h2>
                        <p class="sum-prop-name">{{ (string) ($property->name ?? 'Property') }}</p>
                        <p class="sum-room-name">{{ (string) ($room->name ?? 'Room') }}</p>
                        <p class="sum-room-meta">{{ $roomBedLabel }}{{ $roomSize > 0 ? ' · ' . $roomSize . '㎡' : '' }}{{ $roomNonSmoking ? ' · Non-smoking' : '' }}</p>
                        <p class="sum-room-meta">Rate: <strong style="color:#1b3f58">{{ $currency }} {{ number_format($basePriceRaw, 2) }}</strong> / night</p>
                    </section>

                    <section class="sum-section" aria-label="Stay dates">
                        <h2 class="sum-title"><span class="sum-title-number">2</span> Stay Dates</h2>
                        <div class="sum-date-line">
                            <div class="sum-date-box">
                                <div class="sum-date-label">Check-in</div>
                                <div class="sum-date-value" id="sumCheckinDate">{{ $checkinDate !== '' ? date('D, M j', strtotime($checkinDate)) : '—' }}</div>
                                <div class="sum-date-time">From {{ $roomCheckinStart }}</div>
                            </div>
                            <div class="sum-date-box">
                                <div class="sum-date-label">Check-out</div>
                                <div class="sum-date-value" id="sumCheckoutDate">{{ $checkoutDate !== '' ? date('D, M j', strtotime($checkoutDate)) : '—' }}</div>
                                <div class="sum-date-time">Before {{ $roomCheckoutBefore }}</div>
                            </div>
                        </div>
                        <div class="sum-nights-badge" id="sumNightsBadge">{{ $stayNights }} night{{ $stayNights !== 1 ? 's' : '' }}</div>
                    </section>

                    <section class="sum-section" aria-label="Price summary">
                        <h2 class="sum-title"><span class="sum-title-number">3</span> Price Summary</h2>
                        <div class="sum-compact-line"><span>Nightly rate</span><strong id="invoiceNightly">{{ $currency }} {{ number_format($basePriceRaw, 2) }}</strong></div>
                        <div class="sum-compact-line"><span>Stay (nights)</span><strong id="invoiceNights">{{ $stayNights }}</strong></div>
                        <div class="sum-compact-line"><span>Room subtotal</span><strong id="invoiceRoomSubtotal">{{ $currency }} 0.00</strong></div>
                        <div class="sum-compact-line"><span>Discount</span><strong id="invoiceDiscount">- {{ $currency }} 0.00</strong></div>
                        <div class="sum-compact-line"><span>Tax ({{ number_format($taxRate, 2) }}%)</span><strong id="invoiceTax">{{ $currency }} 0.00</strong></div>
                        <div class="sum-compact-line"><span>Transfer charges</span><strong id="invoiceTransfer">{{ $currency }} 0.00</strong></div>
                        <div class="sum-compact-line"><span>Guests</span><strong id="invoiceGuests">{{ (int) ($prefill['adults'] ?? 2) }} Adults, {{ (int) ($prefill['children'] ?? 0) }} Children</strong></div>
                        <div class="sum-total"><span>Total</span><span id="invoiceTotal">{{ $currency }} 0.00</span></div>
                    </section>

                    <section class="sum-section" aria-label="Inclusives">
                        <h2 class="sum-title"><span class="sum-title-number">4</span> Inclusives</h2>
                        @if ($inclusives->isNotEmpty())
                            <ul class="sum-policy-ul">
                                @foreach ($inclusives->take(8) as $inclusive)
                                    <li>{{ $inclusive }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="sum-policy-text">Inclusives will be confirmed at reservation time.</p>
                        @endif
                    </section>

                    <section class="sum-section" aria-label="Cancellation policy">
                        <h2 class="sum-title"><span class="sum-title-number">5</span> Cancellation Policy</h2>
                        <p class="sum-policy-text">Free cancellation before {{ $cancelDeadlineLabel }}.</p>
                        <p class="sum-policy-text">{{ $cancellationPolicy }}</p>
                    </section>

                    <section class="sum-section" aria-label="Guest policy">
                        <h2 class="sum-title"><span class="sum-title-number">6</span> Guest Policy</h2>
                        <p class="sum-policy-text">{{ $roomChildPolicy }}</p>
                        <p class="sum-policy-text">{{ $roomExtraBedPolicy }}</p>
                    </section>

                </aside>

                <div class="booking-form-wrap">
                <h2 class="booking-form-title">Reserve This Room</h2>
                <form method="POST" action="/booking/reserve" id="roomBookingForm">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ (int) ($property->id ?? 0) }}">
                    <input type="hidden" name="room_id" value="{{ (int) ($room->id ?? 0) }}">
                    <input type="hidden" name="room_subtotal" id="roomSubtotalInput" value="0">
                    <input type="hidden" name="discount_amount" id="discountAmountInput" value="0">
                    <input type="hidden" name="tax_amount" id="taxAmountInput" value="0">
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                    @if ($errors->any())
                        <div class="form-errors" role="alert" aria-live="polite">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="guest-form-stack">
                        <section class="booking-subsection" aria-label="Guest details">
                            <h3 class="booking-subtitle">Who's staying?</h3>
                            <p class="booking-subnote">Guest names must match the valid ID which will be used at check-in.</p>
                            <button type="button" class="add-guest-btn">+ Add New Guest (Optional)</button>
                            <div class="booking-grid">
                                <div class="field"><label for="primaryFirstName">Given names*</label><input id="primaryFirstName" name="primary_first_name" type="text" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}" placeholder="Given names" class="{{ $errors->has('primary_first_name') ? 'input-error' : '' }}" required>@error('primary_first_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryLastName">Surname*</label><input id="primaryLastName" name="primary_last_name" type="text" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}" placeholder="Surname" class="{{ $errors->has('primary_last_name') ? 'input-error' : '' }}" required>@error('primary_last_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryNationality">Nationality*</label><input id="primaryNationality" name="primary_nationality" type="text" value="{{ old('primary_nationality', (string) ($prefill['primary_nationality'] ?? '')) }}" placeholder="Nationality" class="{{ $errors->has('primary_nationality') ? 'input-error' : '' }}" required>@error('primary_nationality')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryEmail">Email*</label><input id="primaryEmail" name="primary_email" type="email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}" placeholder="guest@example.com" class="{{ $errors->has('primary_email') ? 'input-error' : '' }}" required>@error('primary_email')<p class="error-text">{{ $message }}</p>@enderror<p class="helper">Booking confirmation will be sent to this email</p></div>
                                <div class="field"><label for="primaryMobile">Phone number*</label><input id="primaryMobile" name="primary_mobile" type="text" value="{{ old('primary_mobile', (string) ($prefill['primary_mobile'] ?? '')) }}" placeholder="(+60) 1123103013" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required>@error('primary_mobile')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field full">
                                    <p class="booking-subnote">In accordance with local regulations, guests who are not nationals or permanent residents may be required to pay tourism tax per room per night (included in total).</p>
                                </div>
                            </div>
                        </section>

                        <section class="booking-subsection" aria-label="Stay details">
                            <h3 class="booking-subtitle">Stay details</h3>
                            <div class="booking-grid">
                                <div class="field"><label for="checkin">Check-in</label><input id="checkin" name="checkin" type="date" required value="{{ old('checkin', (string) ($prefill['checkin'] ?? '')) }}" class="{{ $errors->has('checkin') ? 'input-error' : '' }}">@error('checkin')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="checkout">Check-out</label><input id="checkout" name="checkout" type="date" required value="{{ old('checkout', (string) ($prefill['checkout'] ?? '')) }}" class="{{ $errors->has('checkout') ? 'input-error' : '' }}">@error('checkout')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="adults">Adults</label><input id="adults" name="adults" type="number" min="1" value="{{ old('adults', (int) ($prefill['adults'] ?? 2)) }}" class="{{ $errors->has('adults') ? 'input-error' : '' }}" required>@error('adults')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ old('children', (int) ($prefill['children'] ?? 0)) }}" class="{{ $errors->has('children') ? 'input-error' : '' }}">@error('children')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="transferOption">Transfer Option</label>
                                    <select id="transferOption" name="transfer_option">
                                        @foreach ($transferOptions as $option)
                                            <option
                                                value="{{ (string) ($option['code'] ?? '') }}"
                                                data-base-charge="{{ (float) ($option['base_charge'] ?? 0) }}"
                                                data-adult-charge="{{ (float) ($option['adult_charge'] ?? 0) }}"
                                                data-child-charge="{{ (float) ($option['child_charge'] ?? 0) }}"
                                            >
                                                {{ (string) ($option['label'] ?? 'Transfer') }} (Adult {{ $currency }} {{ number_format((float) ($option['adult_charge'] ?? 0), 2) }}{{ (float) ($option['child_charge'] ?? 0) > 0 ? (', Child ' . $currency . ' ' . number_format((float) ($option['child_charge'] ?? 0), 2)) : '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field"><label for="transferCharge">Transfer Charge</label><input id="transferCharge" name="transfer_charge" type="number" min="0" step="0.01" readonly></div>
                            </div>
                        </section>

                        <section class="booking-subsection" aria-label="Special requests">
                            <h3 class="booking-subtitle">Special Requests (Optional)</h3>
                            <p class="booking-subnote">The property will do its best, but cannot guarantee to fulfil all requests.</p>
                            <div class="inline-choices">
                                <label class="choice-pill"><input type="radio" name="lift_proximity" value="near">Near lift</label>
                                <label class="choice-pill"><input type="radio" name="lift_proximity" value="away">Away from lift</label>
                            </div>
                            <div class="field full"><label for="additionalGuestDetails">Other requests</label><textarea id="additionalGuestDetails" name="additional_guest_details" placeholder="Special request notes...">{{ old('additional_guest_details', '') }}</textarea></div>
                        </section>

                        <section class="booking-subsection" aria-label="Promo code">
                            <h3 class="booking-subtitle">Available for This Booking</h3>
                            <label class="field" for="promoCode"><span>Enter promo code</span></label>
                            <div class="promo-row">
                                <input id="promoCode" type="text" placeholder="promoCode">
                                <button type="button" class="promo-apply">Apply</button>
                            </div>
                            <span class="promo-chip">10% off max discount {{ $currency }} 25.00</span>
                            <p class="helper">New user promo code (1st booking) • Valid until 23:59, Apr 15, 2026</p>
                            <p class="legal-note">Terms and Conditions apply.</p>
                        </section>

                        <section class="booking-subsection" aria-label="Payment options">
                            <h3 class="booking-subtitle">When would you like to pay?</h3>
                            <div class="pay-icons">
                                <span class="pay-icon">Pay now</span>
                                <span class="pay-icon">Pay at property</span>
                            </div>
                            <h3 class="booking-subtitle">How would you like to pay?</h3>
                            <div class="pay-icons">
                                <span class="pay-icon">Credit/Debit Card</span>
                                <span class="pay-icon">Apple Pay</span>
                                <span class="pay-icon">Google Pay</span>
                                <span class="pay-icon">Touch'n Go</span>
                            </div>
                            <div class="card-grid">
                                <div class="field full"><label for="cardNumber">Bank card no.</label><input id="cardNumber" type="text" placeholder="Card number"></div>
                                <div class="field full"><label for="cardHolder">Name (as registered for related account)</label><input id="cardHolder" type="text" placeholder="Cardholder name"></div>
                                <div class="field"><label for="cardExpiry">MM/YY</label><input id="cardExpiry" type="text" placeholder="Expiry date"></div>
                                <div class="field"><label for="cardCvv">CVV/CVC</label><input id="cardCvv" type="text" placeholder="CVV"></div>
                            </div>
                            <p class="helper">Other payment methods are available on next page.</p>
                        </section>

                        <section class="booking-subsection" aria-label="Booking terms">
                            <p class="booking-subnote">Free Cancellation before {{ $cancelDeadlineLabel }}</p>
                            <p class="booking-subnote">We price match • Secure payment</p>
                            <p class="legal-note">By submitting this booking, you acknowledge that you have read and agree to the Terms of Use and Privacy Statement.</p>
                        </section>
                    </div>

                    <p class="summary">Proceeding will prepare your reservation and take you to checkout confirmation.</p>
                    <button class="submit" type="submit">Proceed to Booking &amp; Reservation</button>
                </form>
                </div>{{-- /.booking-form-wrap --}}
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>

    <script>
        (function () {
            const transferOption = document.getElementById('transferOption');
            const transferCharge = document.getElementById('transferCharge');
            const adults = document.getElementById('adults');
            const children = document.getElementById('children');
            const checkin = document.getElementById('checkin');
            const checkout = document.getElementById('checkout');
            const invoiceNights = document.getElementById('invoiceNights');
            const invoiceGuests = document.getElementById('invoiceGuests');
            const invoiceRoomSubtotal = document.getElementById('invoiceRoomSubtotal');
            const invoiceDiscount = document.getElementById('invoiceDiscount');
            const invoiceTax = document.getElementById('invoiceTax');
            const invoiceTransfer = document.getElementById('invoiceTransfer');
            const invoiceTotal = document.getElementById('invoiceTotal');
            const roomSubtotalInput = document.getElementById('roomSubtotalInput');
            const discountAmountInput = document.getElementById('discountAmountInput');
            const taxAmountInput = document.getElementById('taxAmountInput');
            const totalAmountInput = document.getElementById('totalAmountInput');
            const currency = @json($currency);
            const nightlyRate = Number(@json($basePriceRaw));
            const taxRate = Number(@json($taxRate));
            const discountPercent = Number(@json($discountPercent));

            if (!transferOption || !transferCharge || !adults || !children || !checkin || !checkout) {
                return;
            }

            function toCurrency(value) {
                return currency + ' ' + Number(value || 0).toFixed(2);
            }

            function calculateNights() {
                const inDate = checkin.value ? new Date(checkin.value + 'T00:00:00') : null;
                const outDate = checkout.value ? new Date(checkout.value + 'T00:00:00') : null;
                if (!inDate || !outDate || Number.isNaN(inDate.getTime()) || Number.isNaN(outDate.getTime())) {
                    return 1;
                }

                const diffMs = outDate.getTime() - inDate.getTime();
                const nights = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
                return nights > 0 ? nights : 1;
            }

            function syncSummary() {
                const selected = transferOption.options[transferOption.selectedIndex];
                const adultFare = Number(selected?.dataset?.adultCharge || 0);
                const childFare = Number(selected?.dataset?.childCharge || 0);
                const baseFare = Number(selected?.dataset?.baseCharge || 0);
                const adultCount = Math.max(1, Number(adults.value || 1));
                const childCount = Math.max(0, Number(children.value || 0));
                const nights = calculateNights();

                const roomSubtotal = nightlyRate * nights;
                const discountAmount = roomSubtotal * (discountPercent / 100);
                const taxableAmount = Math.max(0, roomSubtotal - discountAmount);
                const taxAmount = taxableAmount * (taxRate / 100);
                const transferTotal = baseFare + (adultFare * adultCount) + (childFare * childCount);
                const total = taxableAmount + taxAmount + transferTotal;

                transferCharge.value = transferTotal.toFixed(2);

                if (invoiceNights) invoiceNights.textContent = String(nights);
                if (invoiceGuests) invoiceGuests.textContent = adultCount + ' Adults, ' + childCount + ' Children';
                if (invoiceRoomSubtotal) invoiceRoomSubtotal.textContent = toCurrency(roomSubtotal);
                if (invoiceDiscount) invoiceDiscount.textContent = '- ' + toCurrency(discountAmount);
                if (invoiceTax) invoiceTax.textContent = toCurrency(taxAmount);
                if (invoiceTransfer) invoiceTransfer.textContent = toCurrency(transferTotal);
                if (invoiceTotal) invoiceTotal.textContent = toCurrency(total);

                // Update date display in sidebar
                const sumCheckinDate = document.getElementById('sumCheckinDate');
                const sumCheckoutDate = document.getElementById('sumCheckoutDate');
                const sumNightsBadge = document.getElementById('sumNightsBadge');
                if (sumCheckinDate && checkin.value) {
                    const d = new Date(checkin.value + 'T00:00:00');
                    sumCheckinDate.textContent = d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'});
                }
                if (sumCheckoutDate && checkout.value) {
                    const d = new Date(checkout.value + 'T00:00:00');
                    sumCheckoutDate.textContent = d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'});
                }
                if (sumNightsBadge) {
                    sumNightsBadge.textContent = nights + ' night' + (nights !== 1 ? 's' : '');
                }

                if (roomSubtotalInput) roomSubtotalInput.value = roomSubtotal.toFixed(2);
                if (discountAmountInput) discountAmountInput.value = discountAmount.toFixed(2);
                if (taxAmountInput) taxAmountInput.value = taxAmount.toFixed(2);
                if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
            }

            ['change', 'input'].forEach(function (eventName) {
                transferOption.addEventListener(eventName, syncSummary);
                adults.addEventListener(eventName, syncSummary);
                children.addEventListener(eventName, syncSummary);
                checkin.addEventListener(eventName, syncSummary);
                checkout.addEventListener(eventName, syncSummary);
            });

            syncSummary();
        })();
    </script>
</body>
</html>