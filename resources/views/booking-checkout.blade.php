<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px,calc(100% - 24px)); margin:14px auto 28px; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:16px; }
        .title { margin:0; font-size:1.25rem; color:#173d55; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .sub { margin:6px 0 0; color:#4f6d82; font-size:0.9rem; }
        .layout { margin-top:12px; display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,0.8fr); gap:12px; align-items:start; }
        .left-stack { display:grid; gap:10px; align-content:start; }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .cell { border:1px solid #dbe7f0; border-radius:12px; padding:10px; background:#fbfdff; }
        .label { display:block; font-size:0.74rem; text-transform:uppercase; letter-spacing:0.06em; color:#58708a; }
        .value { margin-top:4px; font-weight:600; }
        .invoice { border:1px solid #dbe7f0; border-radius:12px; background:#fbfdff; padding:12px; }
        .invoice h2 { margin:0 0 8px; font-size:1rem; }
        .invoice-row { display:flex; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px dashed #d6e4ee; color:#3b5c73; font-size:0.84rem; }
        .invoice-row:last-child { border-bottom:0; }
        .total { margin-top:10px; border:1px solid #cfe0eb; border-radius:12px; background:#edf6f3; padding:12px; font-size:1.02rem; font-weight:700; color:#21475f; display:flex; justify-content:space-between; }
        .policy { margin-top:10px; border:1px solid #d6e5ee; border-radius:10px; background:#f7fbff; padding:10px; }
        .policy h3 { margin:0 0 6px; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.07em; color:#4a677d; }
        .policy ul { margin:0; padding-left:18px; color:#4a687e; font-size:0.82rem; }
        .policy p { margin:0; color:#4a687e; font-size:0.82rem; line-height:1.4; }
        .mini-panel { display:grid; gap:10px; }
        .mini-section { border:1px solid #dbe7f0; border-radius:10px; background:#fbfdff; padding:10px; display:grid; gap:5px; }
        .mini-title { margin:0; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:#49657c; }
        .hotel-thumb { width:100%; height:156px; object-fit:cover; border-radius:9px; border:1px solid #d9e7f0; background:#eef6fb; }
        .hotel-name { font-size:0.92rem; font-weight:700; color:#1a4159; }
        .score-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .score-chip { background:#0f6179; color:#ecfcff; border-radius:8px; padding:3px 8px; font-size:0.8rem; font-weight:700; }
        .room-meta { display:grid; gap:1px; color:#3c6077; font-size:0.78rem; line-height:1.22; }
        .price-muted { color:#6a8294; font-size:0.78rem; text-decoration:line-through; }
        .price-save { color:#1a8f58; font-size:0.78rem; font-weight:700; }
        .fine-print { color:#4c6a7f; font-size:0.78rem; line-height:1.45; }
        .compact-line { display:flex; justify-content:space-between; gap:10px; font-size:0.8rem; color:#3b5c73; }
        .compact-line strong { color:#1f465f; }
        .payment-box { border:1px solid #d6e5ee; border-radius:12px; background:#f7fbff; padding:12px; display:grid; gap:10px; }
        .payment-box h2 { margin:0; font-size:0.94rem; color:#18455c; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .terms-box { border:1px solid #d6e5ee; border-radius:12px; background:#f7fbff; padding:12px; display:grid; gap:10px; }
        .terms-box h2 { margin:0; font-size:0.94rem; color:#18455c; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .terms-grid { display:grid; gap:8px; }
        .terms-item { border:1px solid #dbe7f0; border-radius:10px; background:#ffffff; padding:10px; display:grid; gap:4px; }
        .terms-item h3 { margin:0; font-size:0.76rem; text-transform:uppercase; letter-spacing:0.07em; color:#4a677d; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .agree-row { border:1px solid #cfe0eb; border-radius:10px; background:#edf6f3; padding:10px; display:flex; align-items:flex-start; gap:8px; }
        .agree-row input[type="checkbox"] { margin-top:2px; accent-color:#0f6179; }
        .agree-row label { font-size:0.84rem; color:#264e66; font-weight:600; line-height:1.45; }
        .payment-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .payment-stat { border:1px solid #dbe7f0; border-radius:10px; background:#ffffff; padding:10px; display:grid; gap:4px; }
        .payment-stat .k { font-size:0.7rem; text-transform:uppercase; letter-spacing:0.07em; color:#5c7689; font-weight:700; }
        .payment-stat .v { font-size:0.9rem; font-weight:700; color:#173d54; }
        .payment-note { margin:0; color:#4a687e; font-size:0.82rem; line-height:1.45; }
        .payment-warning { margin:0; color:#8a3a12; background:#fff0e8; border:1px solid #f2cab5; border-radius:10px; padding:10px; font-size:0.82rem; line-height:1.45; }
        .payment-option-list { display:grid; gap:8px; margin-top:6px; }
        .payment-option-list.is-disabled { opacity:0.56; pointer-events:none; }
        .payment-option { border:1px solid #dbe7f0; border-radius:10px; background:#fff; padding:10px; display:grid; grid-template-columns:auto 1fr; gap:8px; align-items:start; }
        .payment-option-title { font-weight:700; color:#173d54; font-size:0.86rem; }
        .payment-option-meta { color:#4a687e; font-size:0.78rem; }
        .actions { margin-top:6px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .actions form { margin:0; }
        @media (max-width: 980px) {
            .layout { grid-template-columns:1fr; }
            .checkout-summary { grid-column: auto; grid-row: auto; }
        }
        @media (max-width: 760px) {
            .grid,
            .payment-grid { grid-template-columns:1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $summary = $summary ?? [];
        $checkoutDatesLabel = trim((string) (($summary['checkin'] ?? '-') . ' - ' . ($summary['checkout'] ?? '-')));
        $checkoutGuestsLabel = (int) ($summary['adults'] ?? 1) . ' Adults, ' . (int) ($summary['children'] ?? 0) . ' Children';
        if ((int) ($summary['infants'] ?? 0) > 0) {
            $checkoutGuestsLabel .= ', ' . (int) ($summary['infants'] ?? 0) . ' Infants';
        }

           $headerCategorySource = trim((string) ($summary['category_key'] ?? ($property->listing_category ?? 'accommodation')));
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

    @php
        $summary = $summary ?? [];
        $property = $property ?? null;
        $roomName = trim((string) ($roomName ?? ''));
        $currency = strtoupper(trim((string) ($reservation->currency ?? $room->currency ?? $property->currency ?? 'MVR')));
        $totalAmountRaw = (float) ($summary['total'] ?? 0);
        $total = number_format($totalAmountRaw, 2);
        $checkoutMediaUrl = trim((string) ($checkoutMediaUrl ?? ''));
        $adults = max(1, (int) ($summary['adults'] ?? 1));
        $children = max(0, (int) ($summary['children'] ?? 0));
        $infants = max(0, (int) ($summary['infants'] ?? 0));
        $guests = $adults + $children + $infants;
        $categoryKey = strtolower(trim((string) ($summary['category_key'] ?? '')));
        $isExcursionBooking = $categoryKey === 'excursion';
        $roomSubtotal = (float) ($summary['room_subtotal'] ?? 0);
        $discountAmount = max(0, (float) ($summary['discount_amount'] ?? 0));
        $taxAmount = max(0, (float) ($summary['tax_amount'] ?? 0));
        $transferAmount = max(0, (float) ($summary['transfer_charge'] ?? 0));
        $priceBeforeDiscount = $roomSubtotal + $discountAmount;
        $savedAmount = number_format($discountAmount, 2);
        $guestResidency = strtolower(trim((string) ($summary['guest_residency'] ?? '')));
        $isForeigner = $guestResidency === 'foreign_national';
        $taxLines = collect($summary['tax_lines'] ?? [])->filter(static fn ($line) => is_array($line))->values();
        $serviceChargeTotal = max(0, (float) ($summary['service_charge_total'] ?? 0));
        $totalTaxAmount = max(0, (float) ($summary['total_tax_amount'] ?? $taxAmount));
        $limitedTimeOffer = 0.0;
        $firstBookingDeal = 0.0;
        $promoCodeDiscount = 0.0;
        $specialDiscount = max(0, $discountAmount);
        $tourismTax = max(0, (float) ($summary['green_tax_total'] ?? 0));
        $salesServiceTax = max(0, $totalTaxAmount - $tourismTax);
        $transferAppliedAdultRate = max(0, (float) ($summary['transfer_applied_adult_rate'] ?? 0));
        $transferAppliedChildRate = max(0, (float) ($summary['transfer_applied_child_rate'] ?? 0));
        $transferOptions = collect($summary['property_transfer_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
        $selectedTransferCode = strtolower(trim((string) ($summary['transfer_option'] ?? '')));
        if (in_array($selectedTransferCode, ['none', 'no_transfer', 'decline', 'declined'], true)) {
            $selectedTransferCode = '';
        }
        $transferOptionDisplayLabel = trim((string) ($summary['transfer_option_label'] ?? ''));
        if ($transferOptionDisplayLabel === '') {
            $transferOptionDisplayLabel = $selectedTransferCode !== ''
                ? Str::headline(str_replace('_', ' ', $selectedTransferCode))
                : 'No transfer';
        }
        $baseTotalBeforeTransfer = max(0, $totalAmountRaw - $transferAmount);
        $effectiveTransferAmount = $selectedTransferCode === '' ? 0.0 : $transferAmount;
        $transferGstLineAmount = 0.0;
        $invoiceTaxLines = $taxLines->map(static function (array $line): array {
            $label = trim((string) ($line['label'] ?? $line['name'] ?? $line['type'] ?? 'Tax'));
            $amount = (float) ($line['amount'] ?? $line['value'] ?? 0);
            $code = strtolower(trim((string) ($line['code'] ?? '')));
            return [
                'label' => $label !== '' ? $label : 'Tax',
                'amount' => max(0, $amount),
                'code' => $code,
            ];
        })->filter(static fn (array $line): bool => $line['amount'] > 0)->values();
        if ($selectedTransferCode === '') {
            $transferGstLineAmount = (float) $invoiceTaxLines
                ->filter(static fn (array $line): bool => ($line['code'] ?? '') === 'transfer_gst' || str_starts_with(strtolower((string) ($line['label'] ?? '')), 'transfer gst'))
                ->sum(static fn (array $line): float => (float) ($line['amount'] ?? 0));
            $invoiceTaxLines = $invoiceTaxLines
                ->reject(static fn (array $line): bool => ($line['code'] ?? '') === 'transfer_gst' || str_starts_with(strtolower((string) ($line['label'] ?? '')), 'transfer gst'))
                ->values();
        }
        $effectiveInvoiceTotal = max(0, $baseTotalBeforeTransfer + $effectiveTransferAmount - $transferGstLineAmount);
        $total = number_format($effectiveInvoiceTotal, 2);
        $inclusives = collect($inclusives ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values();
        $cancellationPolicy = trim((string) ($cancellationPolicy ?? 'Standard cancellation terms apply.'));
        $dateLabels = $dateLabels ?? ['start' => 'Check-in', 'end' => 'Check-out'];
        $categoryDetails = collect($categoryDetails ?? [])->filter(static fn ($item) => is_array($item))->values();
        $paymentPolicy = $paymentPolicy ?? [];
        $paymentOptions = collect($paymentPolicy['available_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
        $lockedPaymentCurrency = strtoupper(trim((string) ($summary['quote_payment_currency'] ?? ($paymentPolicy['currency'] ?? $currency))));
        $lockedPaymentGateway = trim((string) ($summary['quote_gateway'] ?? ($paymentPolicy['gateway'] ?? '')));
        $lockedPaymentProvider = strtolower(trim((string) ($summary['quote_provider'] ?? ($paymentPolicy['provider'] ?? ''))));
        $paymentGatewayLabel = trim((string) ($summary['quote_gateway_label'] ?? ($paymentPolicy['gateway_label'] ?? 'Card Gateway')));
        $paymentProviderLabel = trim((string) ($summary['quote_provider_label'] ?? ($paymentPolicy['provider_label'] ?? $paymentGatewayLabel)));
        $paymentNotice = trim((string) ($paymentPolicy['customer_notice'] ?? 'Payment routing is enforced based on customer segment.'));
        $lockedPaymentAmount = (float) ($summary['quote_payment_amount'] ?? ($summary['total'] ?? 0));
        $lockedSourceCurrency = strtoupper(trim((string) ($summary['quote_source_currency'] ?? $currency)));
        $lockedSourceAmount = (float) ($summary['quote_source_amount'] ?? ($summary['total'] ?? 0));
        $selectedProvider = $lockedPaymentProvider;
        $hasAvailablePaymentOptions = $paymentOptions->isNotEmpty();
        $customerPaymentStatus = strtolower(trim((string) ($reservation->payment_status ?? 'unpaid')));
        $customerPaymentCollectedAt = trim((string) ($reservation->payment_collected_at ?? $reservation->payment_verified_at ?? ''));
        $selectedPaymentOption = '';
        foreach ($paymentOptions as $paymentOption) {
            $optionGateway = strtolower(trim((string) ($paymentOption['gateway'] ?? '')));
            $optionCurrency = strtoupper(trim((string) ($paymentOption['currency'] ?? '')));
            if ($optionGateway === strtolower($lockedPaymentGateway) && $optionCurrency === $lockedPaymentCurrency) {
                $selectedPaymentOption = $optionGateway . '|' . $optionCurrency;
                break;
            }
        }
        if ($selectedPaymentOption === '' && $paymentOptions->isNotEmpty()) {
            $firstGateway = strtolower(trim((string) ($paymentOptions[0]['gateway'] ?? '')));
            $firstCurrency = strtoupper(trim((string) ($paymentOptions[0]['currency'] ?? '')));
            $selectedPaymentOption = $firstGateway . '|' . $firstCurrency;
        }
        $bookingProcessBackUrl = '/booking/checkout/' . (int) ($reservation->id ?? 0) . '/transfer';
    @endphp

    <main class="page">
        @include('partials.booking-process-highlights', [
            'bookingProcessCurrentStep' => 3,
            'bookingProcessBackUrl' => $bookingProcessBackUrl,
            'bookingProcessNextText' => 'Next step after this page: complete payment and receive reservation confirmation.',
        ])

        <section class="panel" aria-label="Checkout summary">
            <h1 class="title">Checkout & Reservation</h1>
            <p class="sub">Review your prepared reservation and proceed with payment confirmation.</p>

            @if ($errors->any())
                <div id="checkout-error-box" style="border:1px solid #f2b8b5; background:#fff5f5; color:#8e1d1d; border-radius:12px; padding:10px 12px; margin:8px 0 12px;">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="layout">
                <div class="left-stack">
                <div class="grid checkout-details">
                    <div class="cell"><span class="label">Property</span><div class="value">{{ (string) ($property->name ?? 'Property') }}</div></div>
                    <div class="cell"><span class="label">Room / Service</span><div class="value">{{ $roomName !== '' ? $roomName : 'Service' }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['start'] ?? 'Check-in') }}</span><div class="value">{{ (string) ($summary['checkin'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['end'] ?? 'Check-out') }}</span><div class="value">{{ (string) ($summary['checkout'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">Guests</span><div class="value">{{ (int) ($summary['adults'] ?? 1) }} Adults, {{ (int) ($summary['children'] ?? 0) }} Children{{ (int) ($summary['infants'] ?? 0) > 0 ? (', ' . (int) ($summary['infants'] ?? 0) . ' Infant' . ((int) ($summary['infants'] ?? 0) === 1 ? '' : 's')) : '' }}</div></div>
                    <div class="cell"><span class="label">Transfer Option</span><div class="value" id="transferOptionLabelDisplay">{{ $transferOptionDisplayLabel }}</div></div>
                    <div class="cell"><span class="label">Primary Guest</span><div class="value">{{ trim(((string) ($summary['primary_first_name'] ?? '')) . ' ' . ((string) ($summary['primary_last_name'] ?? ''))) ?: 'Guest Customer' }}</div></div>
                    <div class="cell"><span class="label">Nationality</span><div class="value">{{ (string) ($summary['primary_nationality'] ?? '-') }}</div></div>
                </div>

                <section class="terms-box" aria-label="Booking terms and policies">
                    <h2>Booking Terms Before Payment</h2>
                    <div class="terms-grid">
                        <article class="terms-item" aria-label="Cancellation policy">
                            <h3>Cancellation Policy</h3>
                            <div class="fine-print">{{ $cancellationPolicy !== '' ? $cancellationPolicy : 'Cancellation terms are set by the property/service provider and shown in your booking details.' }}</div>
                        </article>

                        <article class="terms-item" aria-label="Customer protection and disclaimer">
                            <h3>Customer Protection and Disclaimer</h3>
                            <div class="fine-print">If a paid booking is cancelled from your customer portal, a refund request is created and its status is tracked in your account.</div>
                            <div class="fine-print">Approved refunds are sent back to the original payment method used at checkout.</div>
                            <div class="fine-print">Refund processing is usually completed within 7-10 business days and may vary by bank or card network.</div>
                            <div class="fine-print">Workation coordinates booking and payment workflows; final service delivery remains the responsibility of the property or operator.</div>
                        </article>

                        <article class="terms-item" aria-label="Rewards and fine print">
                            <h3>Important Notes</h3>
                            <div class="fine-print">This checkout currently does not apply loyalty points or cashback rewards at payment time.</div>
                            <div class="fine-print">Maldives guest houses and hotels do not require a check-in deposit through this checkout flow.</div>
                            @if ($inclusives->isNotEmpty())
                                <div class="fine-print" style="margin-top:4px;">Inclusions:</div>
                                <ul>
                                    @foreach ($inclusives->take(6) as $inclusive)
                                        <li class="fine-print">{{ $inclusive }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    </div>
                    <div class="agree-row">
                        <input type="checkbox" id="checkoutTermsAgree" name="checkout_terms_agree" value="1">
                        <label for="checkoutTermsAgree">I have read and agree to the cancellation policy, customer protection terms, and booking fine print before selecting payment methods.</label>
                    </div>
                </section>

                <div class="payment-box" aria-label="Payment routing details">
                    <h2>Payment Method</h2>
                    <div class="payment-grid">
                        <div class="payment-stat"><span class="k">Customer Segment</span><span class="v">{{ $isForeigner ? 'Foreign National' : 'Local Maldivian' }}</span></div>
                        <div class="payment-stat"><span class="k">Payment Currency</span><span class="v" id="paymentCurrencyDisplay">{{ $lockedPaymentCurrency }}</span></div>
                        <div class="payment-stat"><span class="k">Gateway</span><span class="v" id="paymentGatewayDisplay">{{ $paymentProviderLabel }}</span></div>
                    </div>
                    <div class="payment-stat">
                        <span class="k">Payable Now</span>
                        <span class="v" id="paymentAmountDisplay">{{ $lockedPaymentCurrency }} {{ number_format($lockedPaymentAmount, 2) }}</span>
                    </div>
                    @if ($paymentOptions->isNotEmpty())
                        <div class="payment-option-list is-disabled" id="paymentOptionList">
                            @foreach ($paymentOptions as $paymentOption)
                                @php
                                    $optionGateway = strtolower(trim((string) ($paymentOption['gateway'] ?? '')));
                                    $optionCurrency = strtoupper(trim((string) ($paymentOption['currency'] ?? '')));
                                    $optionProvider = strtolower(trim((string) ($paymentOption['provider'] ?? '')));
                                    $optionProviderLabel = trim((string) ($paymentOption['provider_label'] ?? 'Gateway'));
                                    $optionGatewayLabel = trim((string) ($paymentOption['gateway_label'] ?? $optionProviderLabel));
                                    $optionAmount = (float) ($paymentOption['amount'] ?? 0);
                                    $optionSelection = $optionGateway . '|' . $optionCurrency;
                                    $isChecked = $selectedPaymentOption === $optionSelection;
                                @endphp
                                <label class="payment-option">
                                    <input
                                        type="radio"
                                        name="payment_selection_ui"
                                        value="{{ $optionSelection }}"
                                        data-gateway="{{ $optionGateway }}"
                                        data-provider="{{ $optionProvider }}"
                                        data-currency="{{ $optionCurrency }}"
                                        data-provider-label="{{ $optionProviderLabel }}"
                                        data-amount="{{ number_format($optionAmount, 2, '.', '') }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        disabled
                                    >
                                    <span>
                                        <span class="payment-option-title">{{ $optionProviderLabel }} ({{ $optionCurrency }})</span>
                                        <span class="payment-option-meta">Route: {{ $optionGatewayLabel }} | Pay {{ $optionCurrency }} {{ number_format($optionAmount, 2) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="payment-warning">No live payment gateway route is currently available for this booking segment/currency. Please contact support to complete gateway setup.</p>
                    @endif
                    <p class="payment-note">{{ $paymentNotice }}</p>
                    <p class="payment-note">Booking total: {{ $lockedSourceCurrency }} {{ number_format($lockedSourceAmount, 2) }}. Converted payable amount updates based on your selected payment route.</p>
                </div>
                </div>

                <aside class="mini-panel checkout-summary" aria-label="Reservation compact summary">
                    <section class="mini-section" aria-label="Hotel and room summary">
                        <h2 class="mini-title">1. Hotel detail</h2>
                        @if ($checkoutMediaUrl !== '')
                            <img class="hotel-thumb" src="{{ $checkoutMediaUrl }}" alt="Hotel image" loading="lazy">
                        @endif
                        <div class="hotel-name">{{ (string) ($property->name ?? 'Property') }}</div>
                        <div class="room-meta">
                            <strong>{{ $roomName !== '' ? $roomName : 'Room' }}</strong>
                            <span>x{{ $guests }} guests</span>
                            <span>{{ trim((string) ($room->bed_type ?? '1 bed')) }}</span>
                            <span>{{ (int) ($room->non_smoking ?? 1) === 1 ? 'Non-smoking' : 'Smoking allowed' }}</span>
                        </div>
                    </section>

                    <section class="mini-section" aria-label="Stay dates">
                        <h2 class="mini-title">2. Stay dates</h2>
                        <div class="compact-line"><span>{{ (string) ($summary['checkin'] ?? '-') }} - {{ (string) ($summary['checkout'] ?? '-') }}</span><strong>{{ max(1, (int) ((strtotime((string) ($summary['checkout'] ?? '')) > strtotime((string) ($summary['checkin'] ?? ''))) ? ceil((strtotime((string) ($summary['checkout'] ?? '')) - strtotime((string) ($summary['checkin'] ?? ''))) / 86400) : 1)) }} night</strong></div>
                        <div class="fine-print">Check-in: 15:00-06:00</div>
                        <div class="fine-print">Check-out: Before 12:00</div>
                    </section>

                    <section class="mini-section" aria-label="Price details">
                        <h2 class="mini-title">3. Price details</h2>
                        <div class="invoice-row"><span>1 room x 1 night</span><strong>{{ $currency }} {{ number_format($roomSubtotal, 2) }}</strong></div>
                        @if ($discountAmount > 0)
                            <div class="invoice-row"><span>Discount</span><strong>- {{ $currency }} {{ number_format($discountAmount, 2) }}</strong></div>
                        @endif
                        @foreach ($invoiceTaxLines as $taxLine)
                            <div class="invoice-row"><span>{{ (string) ($taxLine['label'] ?? 'Tax') }}</span><strong>{{ $currency }} {{ number_format((float) ($taxLine['amount'] ?? 0), 2) }}</strong></div>
                        @endforeach
                        @php
                            $serviceChargeAlreadyRendered = collect($invoiceTaxLines)->contains(fn ($line) => stripos((string) ($line['label'] ?? ''), 'service') !== false);
                        @endphp
                        @if ($serviceChargeTotal > 0 && !$serviceChargeAlreadyRendered)
                            <div class="invoice-row"><span>Service charge (included)</span><strong>{{ $currency }} {{ number_format($serviceChargeTotal, 2) }}</strong></div>
                        @endif
                        <div class="invoice-row"><span>Transfer charges</span><strong id="transferChargeDisplay">{{ $currency }} {{ number_format($effectiveTransferAmount, 2) }}</strong></div>
                        @if ($effectiveTransferAmount > 0)
                            <div class="fine-print">Transfer charges include additional GST where applicable.</div>
                        @endif
                        <div class="total"><span>Total</span><span id="invoiceTotalDisplay">{{ $currency }} {{ $total }}</span></div>
                        @if ($discountAmount > 0)
                            <div class="price-save">You've saved {{ $currency }} {{ $savedAmount }} on this booking.</div>
                        @endif
                        <div class="fine-print" style="margin-top:6px;">Vendor prices are treated as all-inclusive. Tax is shown for transparency and is not added again.</div>
                    </section>

                </aside>
            </div>

            <div class="actions">
                @if (!empty($reservation->id))
                    <form method="post" action="/booking/checkout/{{ (int) $reservation->id }}/payment-intent" id="checkoutConfirmForm">
                        @csrf
                        <input type="hidden" name="payment_selection" id="payment_selection_input" value="{{ $selectedPaymentOption }}">
                        <input type="hidden" name="payment_currency" id="payment_currency_input" value="{{ $lockedPaymentCurrency }}">
                        <input type="hidden" name="payment_gateway" id="payment_gateway_input" value="{{ $lockedPaymentGateway }}">
                        <input type="hidden" name="payment_provider" id="payment_provider_input" value="{{ $selectedProvider }}">
                        <input type="hidden" name="checkout_terms_accepted" id="checkout_terms_accepted_input" value="0">
                        <input type="hidden" name="transfer_option" id="transfer_option_input" value="{{ $selectedTransferCode !== '' ? $selectedTransferCode : 'none' }}">
                        <input type="hidden" name="transfer_option_label" id="transfer_option_label_input" value="{{ $transferOptionDisplayLabel }}">
                        <input type="hidden" name="transfer_charge" id="transfer_charge_input" value="{{ number_format($effectiveTransferAmount, 2, '.', '') }}">
                        <input type="hidden" name="invoice_total_amount" id="invoice_total_amount_input" value="{{ number_format($effectiveInvoiceTotal, 2, '.', '') }}">
                        <p class="fine-print" style="width:100%; margin:0 0 6px;">
                            Guest nationality and residency are locked from your booking details and cannot be changed at checkout.
                        </p>
                        <button class="btn primary" id="confirmPayButton" type="submit" disabled>Confirm & Pay</button>
                    </form>
                @else
                    <button class="btn primary" type="button" disabled>Confirm & Pay</button>
                @endif
                <a class="btn alt" href="/booking/checkout/{{ (int) ($reservation->id ?? 0) }}/transfer">Back</a>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>

    <script>
        (function () {
            const optionInputs = Array.from(document.querySelectorAll('input[name="payment_selection_ui"]'));
            const paymentSelectionInput = document.getElementById('payment_selection_input');
            const paymentCurrencyInput = document.getElementById('payment_currency_input');
            const paymentGatewayInput = document.getElementById('payment_gateway_input');
            const paymentProviderInput = document.getElementById('payment_provider_input');
            const paymentCurrencyDisplay = document.getElementById('paymentCurrencyDisplay');
            const paymentGatewayDisplay = document.getElementById('paymentGatewayDisplay');
            const paymentAmountDisplay = document.getElementById('paymentAmountDisplay');
            const checkoutTermsAgree = document.getElementById('checkoutTermsAgree');
            const paymentOptionList = document.getElementById('paymentOptionList');
            const confirmPayButton = document.getElementById('confirmPayButton');
            const checkoutConfirmForm = document.getElementById('checkoutConfirmForm');
            const checkoutTermsAcceptedInput = document.getElementById('checkout_terms_accepted_input');

            if (optionInputs.length === 0) {
                return;
            }

            const syncPaymentSelection = function () {
                const selected = optionInputs.find(function (input) { return input.checked; }) || optionInputs[0];
                if (!selected) {
                    return;
                }

                const gateway = String(selected.dataset.gateway || '').trim();
                const provider = String(selected.dataset.provider || '').trim();
                const currency = String(selected.dataset.currency || '').trim().toUpperCase();
                const providerLabel = String(selected.dataset.providerLabel || '').trim();
                const amount = Number(selected.dataset.amount || 0);

                if (paymentSelectionInput) {
                    paymentSelectionInput.value = selected.value;
                }
                if (paymentCurrencyInput) {
                    paymentCurrencyInput.value = currency;
                }
                if (paymentGatewayInput) {
                    paymentGatewayInput.value = gateway;
                }
                if (paymentProviderInput) {
                    paymentProviderInput.value = provider;
                }
                if (paymentCurrencyDisplay && currency !== '') {
                    paymentCurrencyDisplay.textContent = currency;
                }
                if (paymentGatewayDisplay && providerLabel !== '') {
                    paymentGatewayDisplay.textContent = providerLabel;
                }
                if (paymentAmountDisplay && currency !== '') {
                    paymentAmountDisplay.textContent = currency + ' ' + amount.toFixed(2);
                }
            };

            const syncTermsState = function () {
                const agreed = !!(checkoutTermsAgree && checkoutTermsAgree.checked);
                optionInputs.forEach(function (input) {
                    input.disabled = !agreed;
                });
                if (paymentOptionList) {
                    paymentOptionList.classList.toggle('is-disabled', !agreed);
                }
                if (checkoutTermsAcceptedInput) {
                    checkoutTermsAcceptedInput.value = agreed ? '1' : '0';
                }
                if (confirmPayButton) {
                    confirmPayButton.disabled = !agreed || optionInputs.length === 0;
                }
            };

            optionInputs.forEach(function (input) {
                input.addEventListener('change', syncPaymentSelection);
            });

            if (checkoutTermsAgree) {
                checkoutTermsAgree.addEventListener('change', syncTermsState);
            }

            if (checkoutConfirmForm) {
                checkoutConfirmForm.addEventListener('submit', function (event) {
                    const agreed = !!(checkoutTermsAgree && checkoutTermsAgree.checked);
                    if (!agreed) {
                        event.preventDefault();
                        if (checkoutTermsAgree) {
                            checkoutTermsAgree.focus();
                        }
                    }
                });
            }

            syncPaymentSelection();
            syncTermsState();
        })();
    </script>
    @if ($errors->any())
    <script>
        (function () {
            const errBox = document.getElementById('checkout-error-box');
            if (errBox) {
                errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })();
    </script>
    @endif
</body>
</html>