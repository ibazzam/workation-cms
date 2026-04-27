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
        .page { width:min(1180px,calc(100% - 24px)); margin:14px auto 28px; }
        .hero { border:1px solid #cbe0ea; border-radius:18px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; padding:18px; }
        .hero h1 { margin:0; font-size:clamp(1.2rem,2.3vw,1.9rem); }
        .hero p { margin:6px 0 0; color:#daf5f9; font-size:0.9rem; }
        .section { margin-top:12px; border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:14px; }
        .section h2 { margin:0 0 8px; font-size:1.03rem; }
        .gallery { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .gallery img { width:100%; height:190px; object-fit:cover; border-radius:12px; border:1px solid #cfe1ec; background:#eff7fb; }
        .chips { display:flex; flex-wrap:wrap; gap:7px; }
        .chip { border:1px solid #cfe0eb; background:#edf6f3; color:#24516b; border-radius:999px; font-size:0.77rem; padding:6px 10px; }
        .booking-layout { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:14px; align-items:start; }
        .booking-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .booking-page-header { padding:14px 16px; border:1px solid #cbe0ea; border-radius:16px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; margin-bottom:12px; display:grid; gap:8px; }
        .bph-back { font-size:0.76rem; color:#cfeff4; text-decoration:none; }
        .bph-back:hover { text-decoration:underline; }
        .bph-process-title { margin:0; font-size:0.86rem; color:#d9f6fa; text-transform:uppercase; letter-spacing:0.08em; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .bph-steps { display:flex; flex-wrap:wrap; gap:8px; }
        .bph-step { border:1px solid rgba(232,252,255,0.4); border-radius:999px; padding:7px 11px; font-size:0.78rem; color:#dff7ff; background:rgba(8,52,69,0.18); }
        .bph-step.current { background:#ecfcff; color:#0f6179; font-weight:700; border-color:#ecfcff; }
        .bph-next { margin:0; font-size:0.83rem; color:#e9fbff; }
        .sidebar-summary { border:1px solid #c6dce9; border-radius:16px; background:linear-gradient(180deg,#fbfeff 0%,#f4faff 100%); overflow:hidden; display:grid; gap:0; align-content:start; position:sticky; top:12px; max-height:calc(100vh - 24px); overflow-y:auto; grid-column:2; grid-row:1; box-shadow:0 16px 34px rgba(13,68,96,0.09); }
        .sum-section { border-bottom:1px solid #dde9f2; padding:10px 12px; display:grid; gap:4px; }
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
        .sum-revise-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:4px; }
        .sum-revise-field { display:grid; gap:4px; }
        .sum-revise-field label { font-size:0.64rem; text-transform:uppercase; letter-spacing:0.07em; color:#5b768a; }
        .sum-revise-field input { width:100%; border:1px solid #c2dceb; border-radius:8px; padding:7px 8px; font:inherit; font-size:0.76rem; background:#ffffff; }
        .sum-policy-ul { margin:0; padding-left:16px; color:#4a687e; font-size:0.78rem; line-height:1.35; }
        .sum-policy-text { margin:0 0 2px; color:#4a687e; font-size:0.78rem; line-height:1.3; }
        .sum-policy-text:last-child { margin-bottom:0; }
        .booking-form-wrap { display:grid; gap:12px; grid-column:1; grid-row:1; }
        .booking-form-title { margin:0; border:1px solid #dbe7f0; border-radius:14px; background:linear-gradient(135deg,#f3f8fc 0%,#edf5fb 100%); padding:12px 16px; font-size:1.04rem; font-weight:700; color:#153f59; display:flex; align-items:center; gap:10px; }
        .guest-form-stack { display:grid; gap:12px; }
        .booking-subsection { border:1px solid #dbe7f0; border-radius:12px; background:#fcfeff; padding:12px; display:grid; gap:10px; }
        .booking-subtitle { margin:0; font-size:0.98rem; color:#163f59; font-weight:700; }
        .booking-subnote { margin:0; color:#4f6a7f; font-size:0.8rem; line-height:1.45; }
        .required-note { margin:0; color:#8f2323; font-size:0.76rem; font-weight:600; }
        .helper { margin:0; color:#5c7488; font-size:0.76rem; }
        .add-guest-btn { border:1px dashed #9eb9ca; background:#f5fbff; color:#295571; border-radius:9px; padding:8px 10px; font-size:0.8rem; font-weight:600; width:max-content; }
        .inline-choices { display:flex; flex-wrap:wrap; gap:8px; }
        .choice-pill { border:1px solid #c9dceb; background:#fff; border-radius:999px; padding:6px 10px; font-size:0.78rem; color:#35586f; }
        .choice-pill input { margin-right:6px; }
        .transfer-list { display:grid; gap:8px; }
        .transfer-option { display:grid; grid-template-columns:auto 1fr; gap:9px; align-items:start; border:1px solid #c5daea; border-radius:10px; background:#f8fcff; padding:10px; }
        .transfer-option input { margin-top:2px; }
        .transfer-option-title { font-size:0.84rem; font-weight:700; color:#1b3f58; }
        .transfer-option-rates { font-size:0.76rem; color:#486b80; margin-top:2px; }
        .transfer-option-note { font-size:0.72rem; color:#5a778c; margin-top:2px; }
        .promo-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:8px; }
        .promo-apply { border:1px solid #0f6179; background:#0f6179; color:#ecfcff; border-radius:9px; padding:0 12px; font-weight:700; }
        .promo-chip { display:inline-block; border:1px solid #cfe0eb; background:#edf6f3; color:#24516b; border-radius:999px; font-size:0.74rem; padding:4px 8px; }
        .pay-icons { display:flex; gap:8px; flex-wrap:wrap; }
        .pay-icon { border:1px solid #d3e2ec; border-radius:8px; background:#fff; padding:6px 10px; font-size:0.78rem; color:#254e67; }
        .payment-choice-list { display:grid; gap:8px; }
        .payment-choice { border:1px solid #c8dceb; border-radius:10px; background:#f8fcff; padding:9px 10px; display:grid; grid-template-columns:auto 1fr; gap:8px; align-items:center; }
        .payment-choice.hidden { display:none; }
        .payment-choice-main { font-size:0.82rem; color:#1f475f; font-weight:600; }
        .payment-choice-note { font-size:0.74rem; color:#527288; }
        .payment-hint { margin:0; font-size:0.76rem; color:#486a80; }
        .hidden { display:none !important; }
        .card-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .card-grid .field.full { grid-column:1/-1; }
        .legal-note { margin:0; color:#5a7184; font-size:0.75rem; line-height:1.45; }
        .field { display:grid; gap:5px; }
        .field label { font-size:0.78rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; }
        .field input, .field select {
            width:100%;
            border:1px solid #b8d9e2;
            border-radius:10px;
            padding:10px 11px;
            min-height:42px;
            line-height:1.2;
            font:inherit;
            background:#f8fdff;
        }
        .field textarea { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; font:inherit; background:#f8fdff; min-height:90px; resize:vertical; }
        .field .input-error, .field-error-state input, .field-error-state select { border-color:#c54f4f !important; background:#fff4f4 !important; }
        .field .error-text { margin:0; font-size:0.75rem; color:#a32929; }
        .field.full { grid-column:1/-1; }
        .form-errors { margin:0 0 10px; border:1px solid #e6b2b2; background:#fff5f5; color:#8f2323; border-radius:10px; padding:10px 12px; }
        .form-errors ul { margin:0; padding-left:18px; }
        .summary { margin-top:8px; color:#3f5a72; font-size:0.86rem; }
        .submit { margin-top:10px; }
        .invoice { border:1px solid #dbe7f0; border-radius:14px; background:#fbfdff; padding:12px; }
        .invoice h3 { margin:0 0 8px; font-size:0.96rem; }
        .invoice-row { display:flex; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px dashed #d6e4ee; font-size:0.83rem; color:#36586f; }
        .invoice-row:last-child { border-bottom:0; }
        .invoice-total { margin-top:8px; border:1px solid #cfe0eb; border-radius:10px; background:#edf6f3; padding:9px 10px; display:flex; justify-content:space-between; font-weight:700; color:#21475f; }
        .policy-box { margin-top:10px; border:1px solid #d5e4ee; border-radius:10px; background:#f7fbff; padding:9px; }
        .policy-box h4 { margin:0 0 6px; font-size:0.79rem; text-transform:uppercase; letter-spacing:0.07em; color:#47647a; }
        .policy-box ul { margin:0; padding-left:18px; color:#48677f; font-size:0.8rem; }
        .policy-box p { margin:0; color:#48677f; font-size:0.8rem; line-height:1.4; }
        @media (max-width: 1120px) { .booking-layout { grid-template-columns:minmax(0,1fr) 330px; } }
        @media (max-width: 960px) { .booking-layout { grid-template-columns:minmax(0,1fr) 300px; } }
        @media (max-width: 900px) { .gallery, .booking-grid { grid-template-columns:1fr; } .booking-layout { grid-template-columns:1fr; } .sidebar-summary { position:static; max-height:none; grid-column:auto; grid-row:auto; } .booking-form-wrap { grid-column:auto; grid-row:auto; } }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @include('partials.customer-uniform-header', [
        'headerHideOnScroll' => true,
        'headerShowSearch' => true,
        'headerSearchAction' => '/catalog/' . str_replace('_', '-', strtolower(trim((string) ($property->listing_category ?? 'accommodation')))),
        'headerSearchValue' => '',
        'headerCategoryLinks' => [
            ['key' => 'accommodation', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
            ['key' => 'marine-transport', 'title' => 'Marine Transport', 'url' => '/catalog/marine-transport'],
            ['key' => 'land-transport', 'title' => 'Land Transport', 'url' => '/catalog/land-transport'],
            ['key' => 'excursion', 'title' => 'Excursion', 'url' => '/catalog/excursion'],
            ['key' => 'remote_workspace', 'title' => 'Remote Workspace', 'url' => '/catalog/remote_workspace'],
            ['key' => 'conference_room', 'title' => 'Conference Rooms', 'url' => '/catalog/conference_room'],
            ['key' => 'resort_day_visit', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
            ['key' => 'restaurant', 'title' => 'Restaurant', 'url' => '/catalog/restaurant'],
            ['key' => 'vehicle_rental', 'title' => 'Vehicle Rental', 'url' => '/catalog/vehicle_rental'],
        ],
        'headerActiveCategoryKey' => str_replace('_', '-', strtolower(trim((string) ($property->listing_category ?? 'accommodation')))),
    ])

    @php
        $roomMedia = $roomMedia ?? collect();
        $roomFeatures = $roomFeatures ?? collect();
        $transferOptions = $transferOptions ?? collect();
        $pricingConfig = $pricingConfig ?? ['tax_rate' => 16, 'discount_percent' => 0];
        $bookingPolicies = $bookingPolicies ?? [
            'inclusives' => [],
            'cancellation_policy' => 'Standard cancellation terms apply.',
            'check_in_time' => '',
            'check_out_time' => '',
            'child_policy' => '',
            'house_rules' => '',
            'minimum_nights' => null,
        ];
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $prefill = $prefill ?? ['checkin' => '', 'checkout' => '', 'adults' => 2, 'children' => 0];
        $currency = strtoupper(trim((string) ($room->currency ?? $property->currency ?? 'MVR')));
        $basePrice = number_format((float) ($room->base_price ?? 0), 2);
        $basePriceRaw = (float) ($room->base_price ?? 0);
        $selectedNightlyRateRaw = (float) ($prefill['selected_nightly_rate'] ?? 0);
        if (!is_finite($selectedNightlyRateRaw) || $selectedNightlyRateRaw <= 0) {
            $selectedNightlyRateRaw = $basePriceRaw;
        }
        $selectedMealPlan = trim((string) ($prefill['selected_meal_plan'] ?? ''));
        $selectedNightlyRate = number_format($selectedNightlyRateRaw, 2);
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
        $roomCheckinStart = trim((string) ($bookingPolicies['check_in_time'] ?? ''));
        if ($roomCheckinStart === '') {
            $roomCheckinStart = '15:00';
        }
        $roomCheckinEnd = '06:00';
        $roomCheckoutBefore = trim((string) ($bookingPolicies['check_out_time'] ?? ''));
        if ($roomCheckoutBefore === '') {
            $roomCheckoutBefore = '12:00';
        }
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
        $roomChildPolicy = trim((string) ($bookingPolicies['child_policy'] ?? ($room->child_policy ?? 'Children of all ages can stay in this room. Additional fees may be charged for children using existing beds.')));
        $roomExtraBedPolicy = trim((string) ($room->extra_bed_policy ?? 'Extra beds and cots are not available for this room type.'));
        $roomHouseRules = trim((string) ($bookingPolicies['house_rules'] ?? ''));
        $countryOptions = [
            ['name' => 'Maldives', 'iso' => 'MV', 'dial' => '+960'],
            ['name' => 'India', 'iso' => 'IN', 'dial' => '+91'],
            ['name' => 'Sri Lanka', 'iso' => 'LK', 'dial' => '+94'],
            ['name' => 'Bangladesh', 'iso' => 'BD', 'dial' => '+880'],
            ['name' => 'Pakistan', 'iso' => 'PK', 'dial' => '+92'],
            ['name' => 'Nepal', 'iso' => 'NP', 'dial' => '+977'],
            ['name' => 'United Arab Emirates', 'iso' => 'AE', 'dial' => '+971'],
            ['name' => 'Saudi Arabia', 'iso' => 'SA', 'dial' => '+966'],
            ['name' => 'Qatar', 'iso' => 'QA', 'dial' => '+974'],
            ['name' => 'Kuwait', 'iso' => 'KW', 'dial' => '+965'],
            ['name' => 'Bahrain', 'iso' => 'BH', 'dial' => '+973'],
            ['name' => 'Oman', 'iso' => 'OM', 'dial' => '+968'],
            ['name' => 'Singapore', 'iso' => 'SG', 'dial' => '+65'],
            ['name' => 'Malaysia', 'iso' => 'MY', 'dial' => '+60'],
            ['name' => 'Thailand', 'iso' => 'TH', 'dial' => '+66'],
            ['name' => 'Indonesia', 'iso' => 'ID', 'dial' => '+62'],
            ['name' => 'China', 'iso' => 'CN', 'dial' => '+86'],
            ['name' => 'Japan', 'iso' => 'JP', 'dial' => '+81'],
            ['name' => 'South Korea', 'iso' => 'KR', 'dial' => '+82'],
            ['name' => 'Australia', 'iso' => 'AU', 'dial' => '+61'],
            ['name' => 'New Zealand', 'iso' => 'NZ', 'dial' => '+64'],
            ['name' => 'United Kingdom', 'iso' => 'GB', 'dial' => '+44'],
            ['name' => 'Germany', 'iso' => 'DE', 'dial' => '+49'],
            ['name' => 'France', 'iso' => 'FR', 'dial' => '+33'],
            ['name' => 'Italy', 'iso' => 'IT', 'dial' => '+39'],
            ['name' => 'Spain', 'iso' => 'ES', 'dial' => '+34'],
            ['name' => 'Netherlands', 'iso' => 'NL', 'dial' => '+31'],
            ['name' => 'Switzerland', 'iso' => 'CH', 'dial' => '+41'],
            ['name' => 'United States', 'iso' => 'US', 'dial' => '+1'],
            ['name' => 'Canada', 'iso' => 'CA', 'dial' => '+1'],
        ];
        $oldNationality = trim((string) old('primary_nationality', (string) ($prefill['primary_nationality'] ?? '')));
        $oldPhoneCode = trim((string) old('primary_mobile_country_code', '+960'));
        $oldPhoneLocal = trim((string) old('primary_mobile_local', (string) ($prefill['primary_mobile'] ?? '')));
        if ($oldPhoneLocal === '' && trim((string) old('primary_mobile', '')) !== '') {
            $oldPhoneLocal = trim((string) old('primary_mobile', ''));
        }
    @endphp

    <main class="page">
        <header class="booking-page-header" aria-label="Property and room">
            <a class="bph-back" href="{{ url()->previous('/') }}">&larr; Back to property</a>
            <p class="bph-process-title">Booking Process Highlights</p>
            <div class="bph-steps" aria-label="Booking progress">
                <span class="bph-step current">1. Guest Details</span>
                <span class="bph-step">2. Transfer Selection</span>
                <span class="bph-step">3. Payment Method</span>
                <span class="bph-step">4. Final Confirmation</span>
            </div>
            <p class="bph-next">Next step after this page: select transfer option on checkout, then continue to payment confirmation.</p>
        </header>

        <section class="section" aria-label="Booking">
            <div class="booking-layout">

                <aside class="sidebar-summary" aria-label="Booking summary">

                    <section class="sum-section" aria-label="Property and room">
                        <h2 class="sum-title"><span class="sum-title-number">1</span> Property &amp; Room</h2>
                        <p class="sum-prop-name">{{ (string) ($property->name ?? 'Property') }}</p>
                        <p class="sum-room-name">{{ (string) ($room->name ?? 'Room') }}</p>
                        <p class="sum-room-meta">{{ $roomBedLabel }}{{ $roomSize > 0 ? ' · ' . $roomSize . '㎡' : '' }}{{ $roomNonSmoking ? ' · Non-smoking' : '' }}</p>
                        <p class="sum-room-meta">Rate: <strong style="color:#1b3f58">{{ $currency }} {{ $selectedNightlyRate }}</strong> / night</p>
                        @if ($selectedMealPlan !== '')
                            <p class="sum-room-meta">Meal plan: <strong style="color:#1b3f58">{{ $selectedMealPlan }}</strong></p>
                        @endif
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
                        <div class="sum-revise-grid">
                            <div class="sum-revise-field">
                                <label for="sumCheckinInput">Revise check-in</label>
                                <input id="sumCheckinInput" type="date" value="{{ old('checkin', (string) ($prefill['checkin'] ?? '')) }}" min="{{ now()->toDateString() }}">
                            </div>
                            <div class="sum-revise-field">
                                <label for="sumCheckoutInput">Revise check-out</label>
                                <input id="sumCheckoutInput" type="date" value="{{ old('checkout', (string) ($prefill['checkout'] ?? '')) }}" min="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    </section>

                    <section class="sum-section" aria-label="Price summary">
                        <h2 class="sum-title"><span class="sum-title-number">3</span> Price Summary</h2>
                        <div class="sum-compact-line"><span>Nightly rate</span><strong id="invoiceNightly">{{ $currency }} {{ $selectedNightlyRate }}</strong></div>
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
                        @if (isset($bookingPolicies['minimum_nights']) && is_numeric($bookingPolicies['minimum_nights']) && (int) $bookingPolicies['minimum_nights'] > 1)
                            <p class="sum-policy-text">Minimum stay: {{ (int) $bookingPolicies['minimum_nights'] }} nights.</p>
                        @endif
                        <p class="sum-policy-text">{{ $cancellationPolicy }}</p>
                    </section>

                    <section class="sum-section" aria-label="Guest policy">
                        <h2 class="sum-title"><span class="sum-title-number">6</span> Guest Policy</h2>
                        <p class="sum-policy-text">{{ $roomChildPolicy }}</p>
                        <p class="sum-policy-text">{{ $roomExtraBedPolicy }}</p>
                        @if ($roomHouseRules !== '')
                            <p class="sum-policy-text">House rules: {{ $roomHouseRules }}</p>
                        @endif
                    </section>

                </aside>

                <div class="booking-form-wrap">
                <h2 class="booking-form-title">Reserve This Room</h2>
                <form method="POST" action="/booking/reserve" id="roomBookingForm">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ (int) ($property->id ?? 0) }}">
                    <input type="hidden" name="room_id" value="{{ (int) ($room->id ?? 0) }}">
                    <input type="hidden" name="checkin" id="checkin" value="{{ old('checkin', (string) ($prefill['checkin'] ?? '')) }}">
                    <input type="hidden" name="checkout" id="checkout" value="{{ old('checkout', (string) ($prefill['checkout'] ?? '')) }}">
                    <input type="hidden" name="transfer_option" value="">
                    <input type="hidden" name="transfer_charge" id="transferCharge" value="0">
                    <input type="hidden" name="room_subtotal" id="roomSubtotalInput" value="0">
                    <input type="hidden" name="discount_amount" id="discountAmountInput" value="0">
                    <input type="hidden" name="tax_amount" id="taxAmountInput" value="0">
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">
                    <input type="hidden" name="primary_mobile" id="primaryMobileHidden" value="{{ old('primary_mobile', '') }}">

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
                            <p class="booking-subnote">Given names and surname must match government-issued documents. For foreigners, use passport details. For locals, use your national ID details.</p>
                            <p class="required-note">All fields marked with * are mandatory. Missing fields are highlighted in red and must be completed to continue.</p>
                            <button type="button" class="add-guest-btn">+ Add New Guest (Optional)</button>
                            <div class="booking-grid">
                                <div class="field"><label for="primaryFirstName">Given names*</label><input id="primaryFirstName" name="primary_first_name" type="text" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}" placeholder="Given names" class="{{ $errors->has('primary_first_name') ? 'input-error' : '' }}" required>@error('primary_first_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryLastName">Surname*</label><input id="primaryLastName" name="primary_last_name" type="text" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}" placeholder="Surname" class="{{ $errors->has('primary_last_name') ? 'input-error' : '' }}" required>@error('primary_last_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryNationality">Country / Nationality*</label><select id="primaryNationality" name="primary_nationality" class="{{ $errors->has('primary_nationality') ? 'input-error' : '' }}" required><option value="">Select country</option>@foreach ($countryOptions as $country)<option value="{{ $country['name'] }}" data-iso="{{ $country['iso'] }}" data-dial="{{ $country['dial'] }}" {{ strcasecmp($oldNationality, $country['name']) === 0 ? 'selected' : '' }}>{{ $country['name'] }}</option>@endforeach</select>@error('primary_nationality')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="primaryEmail">Email*</label><input id="primaryEmail" name="primary_email" type="email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}" placeholder="guest@example.com" class="{{ $errors->has('primary_email') ? 'input-error' : '' }}" required>@error('primary_email')<p class="error-text">{{ $message }}</p>@enderror<p class="helper">Booking confirmation will be sent to this email</p></div>
                                <div class="field"><label for="primaryMobileCountryCode">Phone country code*</label><select id="primaryMobileCountryCode" name="primary_mobile_country_code" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required>@foreach ($countryOptions as $country)<option value="{{ $country['dial'] }}" data-iso="{{ $country['iso'] }}" {{ $oldPhoneCode === $country['dial'] ? 'selected' : '' }}>{{ $country['dial'] }} ({{ $country['name'] }})</option>@endforeach</select></div>
                                <div class="field"><label for="primaryMobileLocal">Contact number*</label><input id="primaryMobileLocal" name="primary_mobile_local" type="tel" value="{{ $oldPhoneLocal }}" placeholder="7712345" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required inputmode="tel">@error('primary_mobile')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="adults">Adults</label><input id="adults" name="adults" type="number" min="1" value="{{ old('adults', (int) ($prefill['adults'] ?? 2)) }}" class="{{ $errors->has('adults') ? 'input-error' : '' }}" required>@error('adults')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ old('children', (int) ($prefill['children'] ?? 0)) }}" class="{{ $errors->has('children') ? 'input-error' : '' }}">@error('children')<p class="error-text">{{ $message }}</p>@enderror</div>
                                <div class="field full">
                                    <p class="booking-subnote">In accordance with local regulations, guests who are not nationals or permanent residents may be required to pay tourism tax per room per night (included in total).</p>
                                </div>
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
                            <h3 class="booking-subtitle">Promo Code</h3>
                            @if ($discountPercent > 0)
                                <p class="promo-chip active-promo">
                                    <span>&#10003; {{ number_format($discountPercent, 0) }}% listing discount — already applied to your total</span>
                                </p>
                            @endif
                            <input type="hidden" id="appliedPromoCode" name="promo_code" value="">
                            <input type="hidden" id="appliedPromoDiscount" name="promo_discount_percent" value="0">
                            <div class="promo-row">
                                <input id="promoCode" type="text" placeholder="Enter promo code" autocomplete="off" maxlength="64">
                                <button type="button" class="promo-apply">Apply</button>
                            </div>
                            <p id="promoMessage" class="helper" style="display:none;"></p>
                            <p class="legal-note">Terms and Conditions apply.</p>
                        </section>

                        <section class="booking-subsection" aria-label="Payment options">
                            <h3 class="booking-subtitle">Payment</h3>
                            <p class="booking-subnote">Payment method selection and card/bank details are captured on the next checkout step only.</p>
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
            const form = document.getElementById('roomBookingForm');
            const transferOptionInputs = Array.from(document.querySelectorAll('input[name="transfer_option"]'));
            const transferCharge = document.getElementById('transferCharge');
            const adults = document.getElementById('adults');
            const children = document.getElementById('children');
            const checkin = document.getElementById('checkin');
            const checkout = document.getElementById('checkout');
            const sumCheckinInput = document.getElementById('sumCheckinInput');
            const sumCheckoutInput = document.getElementById('sumCheckoutInput');
            const invoiceNights = document.getElementById('invoiceNights');
            const invoiceGuests = document.getElementById('invoiceGuests');
            const invoiceRoomSubtotal = document.getElementById('invoiceRoomSubtotal');
            const invoiceDiscount = document.getElementById('invoiceDiscount');
            const invoiceTax = document.getElementById('invoiceTax');
            const invoiceTransfer = document.getElementById('invoiceTransfer');
            const invoiceTotal = document.getElementById('invoiceTotal');
            const invoiceNightly = document.getElementById('invoiceNightly');
            const roomSubtotalInput = document.getElementById('roomSubtotalInput');
            const discountAmountInput = document.getElementById('discountAmountInput');
            const taxAmountInput = document.getElementById('taxAmountInput');
            const totalAmountInput = document.getElementById('totalAmountInput');
            const primaryFirstName = document.getElementById('primaryFirstName');
            const primaryLastName = document.getElementById('primaryLastName');
            const primaryNationality = document.getElementById('primaryNationality');
            const primaryEmail = document.getElementById('primaryEmail');
            const primaryMobileCountryCode = document.getElementById('primaryMobileCountryCode');
            const primaryMobileLocal = document.getElementById('primaryMobileLocal');
            const primaryMobileHidden = document.getElementById('primaryMobileHidden');
            const paymentMethodList = document.getElementById('paymentMethodList');
            const paymentHint = document.getElementById('paymentHint');
            const cardDetailsBlock = document.getElementById('cardDetailsBlock');
            const currency = @json($currency);
            const nightlyRate = Number(@json($selectedNightlyRateRaw));
            const taxRate = Number(@json($taxRate));
            const discountPercent = Number(@json($discountPercent));
            const todayDate = @json(now()->toDateString());

            if (!form || !transferCharge || !adults || !children || !checkin || !checkout) {
                return;
            }

            checkin.min = todayDate;
            checkout.min = todayDate;

            function syncCheckoutMin() {
                const checkinValue = String(checkin.value || '').trim();
                checkout.min = checkinValue !== '' ? checkinValue : todayDate;

                if (checkinValue !== '' && checkinValue < todayDate) {
                    checkin.setCustomValidity('Check-in date cannot be in the past.');
                } else {
                    checkin.setCustomValidity('');
                }

                const checkoutValue = String(checkout.value || '').trim();
                if (checkoutValue !== '' && checkinValue !== '' && checkoutValue <= checkinValue) {
                    checkout.setCustomValidity('Check-out date must be after check-in date.');
                } else {
                    checkout.setCustomValidity('');
                }
            }

            function toCurrency(value) {
                return currency + ' ' + Number(value || 0).toFixed(2);
            }

            function syncPrimaryMobile() {
                if (!primaryMobileHidden || !primaryMobileCountryCode || !primaryMobileLocal) {
                    return;
                }

                const dial = String(primaryMobileCountryCode.value || '').trim();
                const local = String(primaryMobileLocal.value || '').trim();
                primaryMobileHidden.value = (dial + ' ' + local).trim();
            }

            function fieldWrap(element) {
                return element ? element.closest('.field') : null;
            }

            function markFieldError(element, hasError) {
                const wrapper = fieldWrap(element);
                if (!wrapper || !element) {
                    return;
                }

                wrapper.classList.toggle('field-error-state', hasError);
                if (hasError) {
                    element.setAttribute('aria-invalid', 'true');
                } else {
                    element.removeAttribute('aria-invalid');
                }
            }

            function currentNationalityIso() {
                if (!primaryNationality) {
                    return '';
                }

                const selected = primaryNationality.options[primaryNationality.selectedIndex];
                return String(selected?.dataset?.iso || '').toUpperCase();
            }

            function updatePaymentOptionsByNationality() {
                if (!paymentMethodList) {
                    return;
                }

                const isLocalGuest = currentNationalityIso() === 'MV';
                const methodOptions = Array.from(paymentMethodList.querySelectorAll('.payment-method-option'));

                methodOptions.forEach(function (option) {
                    const scope = String(option.dataset.scope || 'all');
                    const shouldShow = scope === 'all' || (isLocalGuest ? scope === 'local' : scope === 'international');
                    option.classList.toggle('hidden', !shouldShow);

                    const input = option.querySelector('input[name="payment_method"]');
                    if (input) {
                        input.disabled = !shouldShow;
                    }
                });

                const visibleEnabledInputs = methodOptions
                    .map(function (option) { return option.querySelector('input[name="payment_method"]'); })
                    .filter(function (input) { return !!input && !input.disabled; });

                const anyCheckedVisible = visibleEnabledInputs.some(function (input) { return input.checked; });
                if (!anyCheckedVisible && visibleEnabledInputs[0]) {
                    visibleEnabledInputs[0].checked = true;
                }

                if (paymentHint) {
                    paymentHint.textContent = isLocalGuest
                        ? 'Local payment options enabled for Maldivian nationals.'
                        : 'International payment options enabled for foreign guests.';
                }

                const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
                const usesCardFields = ['card', 'apple_pay', 'google_pay'].includes(String(selectedPaymentMethod?.value || ''));
                if (cardDetailsBlock) {
                    cardDetailsBlock.classList.toggle('hidden', !usesCardFields);
                }
            }

            function validateMandatoryGuestFields() {
                const errors = [];
                const requiredChecks = [
                    {
                        element: primaryFirstName,
                        check: function (value) { return value.length > 0; },
                        message: 'Given names are required.'
                    },
                    {
                        element: primaryLastName,
                        check: function (value) { return value.length > 0; },
                        message: 'Surname is required.'
                    },
                    {
                        element: primaryNationality,
                        check: function (value) { return value.length > 0; },
                        message: 'Country / nationality is required.'
                    },
                    {
                        element: primaryEmail,
                        check: function (value) {
                            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                        },
                        message: 'Enter a valid email address.'
                    },
                    {
                        element: primaryMobileCountryCode,
                        check: function (value) { return value.length > 0; },
                        message: 'Phone country code is required.'
                    },
                    {
                        element: primaryMobileLocal,
                        check: function (value) { return value.replace(/\D+/g, '').length >= 6; },
                        message: 'Enter a valid contact number.'
                    },
                ];

                requiredChecks.forEach(function (rule) {
                    if (!rule.element) {
                        return;
                    }

                    const value = String(rule.element.value || '').trim();
                    const isValid = rule.check(value);
                    markFieldError(rule.element, !isValid);
                    if (!isValid) {
                        errors.push(rule.message);
                    }
                });

                const existingErrorBox = form.querySelector('.form-errors.client-errors');
                if (existingErrorBox) {
                    existingErrorBox.remove();
                }

                if (errors.length > 0) {
                    const errorBox = document.createElement('div');
                    errorBox.className = 'form-errors client-errors';
                    errorBox.setAttribute('role', 'alert');
                    errorBox.setAttribute('aria-live', 'polite');
                    errorBox.innerHTML = '<ul>' + errors.map(function (error) {
                        return '<li>' + error + '</li>';
                    }).join('') + '</ul>';
                    form.insertBefore(errorBox, form.firstElementChild.nextElementSibling);
                }

                return errors.length === 0;
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
                const adultCount = Math.max(1, Number(adults.value || 1));
                const childCount = Math.max(0, Number(children.value || 0));
                const nights = calculateNights();

                const roomSubtotal = nightlyRate * nights;
                const discountAmount = roomSubtotal * (discountPercent / 100);
                const discountedSubtotal = Math.max(0, roomSubtotal - discountAmount);
                const taxAmount = taxRate > 0
                    ? discountedSubtotal - (discountedSubtotal / (1 + (taxRate / 100)))
                    : 0;
                const transferTotal = 0;
                const total = discountedSubtotal + transferTotal;

                transferCharge.value = transferTotal.toFixed(2);

                if (invoiceNightly) invoiceNightly.textContent = toCurrency(nightlyRate);
                if (invoiceNights) invoiceNights.textContent = String(nights);
                if (invoiceGuests) invoiceGuests.textContent = adultCount + ' Adults, ' + childCount + ' Children';
                if (invoiceRoomSubtotal) invoiceRoomSubtotal.textContent = toCurrency(roomSubtotal);
                if (invoiceDiscount) invoiceDiscount.textContent = '- ' + toCurrency(discountAmount);
                if (invoiceTax) invoiceTax.textContent = toCurrency(taxAmount) + ' (included)';
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
                if (sumCheckinInput && sumCheckinInput.value !== checkin.value) {
                    sumCheckinInput.value = checkin.value;
                }
                if (sumCheckoutInput && sumCheckoutInput.value !== checkout.value) {
                    sumCheckoutInput.value = checkout.value;
                }

                if (roomSubtotalInput) roomSubtotalInput.value = roomSubtotal.toFixed(2);
                if (discountAmountInput) discountAmountInput.value = discountAmount.toFixed(2);
                if (taxAmountInput) taxAmountInput.value = taxAmount.toFixed(2);
                if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
            }

            function syncDatesFromSummary() {
                if (sumCheckinInput && checkin.value !== sumCheckinInput.value) {
                    checkin.value = sumCheckinInput.value;
                }
                if (sumCheckoutInput && checkout.value !== sumCheckoutInput.value) {
                    checkout.value = sumCheckoutInput.value;
                }
                syncCheckoutMin();
                syncSummary();
            }

            ['change', 'input'].forEach(function (eventName) {
                adults.addEventListener(eventName, syncSummary);
                children.addEventListener(eventName, syncSummary);
                checkin.addEventListener(eventName, function () {
                    syncCheckoutMin();
                    syncSummary();
                });
                checkout.addEventListener(eventName, function () {
                    syncCheckoutMin();
                    syncSummary();
                });
                if (sumCheckinInput) {
                    sumCheckinInput.addEventListener(eventName, syncDatesFromSummary);
                }
                if (sumCheckoutInput) {
                    sumCheckoutInput.addEventListener(eventName, syncDatesFromSummary);
                }
                [primaryFirstName, primaryLastName, primaryNationality, primaryEmail, primaryMobileCountryCode, primaryMobileLocal].forEach(function (input) {
                    if (!input) {
                        return;
                    }

                    input.addEventListener(eventName, function () {
                        markFieldError(input, false);
                        syncPrimaryMobile();
                        if (input === primaryNationality) {
                            const selected = primaryNationality.options[primaryNationality.selectedIndex];
                            const suggestedDial = String(selected?.dataset?.dial || '').trim();
                            if (suggestedDial !== '' && primaryMobileCountryCode) {
                                primaryMobileCountryCode.value = suggestedDial;
                            }
                            updatePaymentOptionsByNationality();
                        }
                    });
                });
            });

            if (paymentMethodList) {
                paymentMethodList.addEventListener('change', updatePaymentOptionsByNationality);
            }

            form.addEventListener('submit', function (event) {
                syncPrimaryMobile();
                updatePaymentOptionsByNationality();

                const hasValidGuests = validateMandatoryGuestFields();
                if (!hasValidGuests) {
                    event.preventDefault();
                    const errorBlock = form.querySelector('.form-errors.client-errors');
                    if (errorBlock) {
                        errorBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            syncCheckoutMin();
            syncPrimaryMobile();
            updatePaymentOptionsByNationality();
            syncSummary();
        })();
    </script>
</body>
</html>