<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px,calc(100% - 24px)); margin:14px auto 28px; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:16px; }
        .title { margin:0; font-size:1.25rem; }
        .sub { margin:6px 0 0; color:#45667d; }
        .layout { margin-top:12px; display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,0.8fr); gap:12px; align-items:start; }
        .checkout-details { grid-column: 1; grid-row: 1; }
        .checkout-summary { grid-column: 2; grid-row: 1; }
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
        .payment-box { margin-top:12px; border:1px solid #d6e5ee; border-radius:12px; background:#f7fbff; padding:12px; display:grid; gap:10px; }
        .payment-box h2 { margin:0; font-size:0.94rem; color:#18455c; }
        .payment-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .payment-stat { border:1px solid #dbe7f0; border-radius:10px; background:#ffffff; padding:10px; display:grid; gap:4px; }
        .payment-stat .k { font-size:0.7rem; text-transform:uppercase; letter-spacing:0.07em; color:#5c7689; font-weight:700; }
        .payment-stat .v { font-size:0.9rem; font-weight:700; color:#173d54; }
        .payment-note { margin:0; color:#4a687e; font-size:0.82rem; line-height:1.45; }
        .actions { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; }
        @media (max-width: 980px) {
            .layout { grid-template-columns:1fr; }
            .checkout-details,
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
    @endphp

    @include('partials.customer-uniform-header', [
        'headerHideOnScroll' => false,
        'headerShowSearch' => false,
        'headerMode' => 'checkout',
        'headerCategoryLinks' => [],
        'headerCheckoutContext' => [
            'property' => (string) ($property->name ?? 'Checkout'),
            'dates' => $checkoutDatesLabel,
            'guests' => $checkoutGuestsLabel,
        ],
        'headerContinueUrl' => (string) request()->fullUrl(),
    ])

    @php
        $summary = $summary ?? [];
        $property = $property ?? null;
        $roomName = trim((string) ($roomName ?? ''));
        $currency = strtoupper(trim((string) ($reservation->currency ?? $room->currency ?? $property->currency ?? 'MVR')));
        $total = number_format((float) ($summary['total'] ?? 0), 2);
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
        $baseTotalBeforeTransfer = max(0, ((float) ($summary['total'] ?? 0)) - $transferAmount);
        $inclusives = collect($inclusives ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values();
        $cancellationPolicy = trim((string) ($cancellationPolicy ?? 'Standard cancellation terms apply.'));
        $dateLabels = $dateLabels ?? ['start' => 'Check-in', 'end' => 'Check-out'];
        $categoryDetails = collect($categoryDetails ?? [])->filter(static fn ($item) => is_array($item))->values();
        $paymentPolicy = $paymentPolicy ?? [];
        $paymentOptions = collect($paymentPolicy['available_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
        $paymentProviders = $paymentOptions
            ->map(static fn ($option) => [
                'provider' => strtolower(trim((string) ($option['provider'] ?? $option['gateway'] ?? ''))),
                'provider_label' => trim((string) ($option['provider_label'] ?? $option['gateway_label'] ?? 'Gateway')),
            ])
            ->filter(static fn ($option) => ($option['provider'] ?? '') !== '')
            ->unique('provider')
            ->values();
        $lockedPaymentCurrency = strtoupper(trim((string) ($paymentPolicy['currency'] ?? $currency)));
        $lockedPaymentGateway = trim((string) ($paymentPolicy['gateway'] ?? ''));
        $lockedPaymentProvider = strtolower(trim((string) ($paymentPolicy['provider'] ?? '')));
        $paymentGatewayLabel = trim((string) ($paymentPolicy['gateway_label'] ?? 'Card Gateway'));
        $paymentProviderLabel = trim((string) ($paymentPolicy['provider_label'] ?? $paymentGatewayLabel));
        $paymentNotice = trim((string) ($paymentPolicy['customer_notice'] ?? 'Payment routing is enforced based on customer segment.'));
        $checkoutPrimaryNationality = old('primary_nationality', (string) ($summary['primary_nationality'] ?? ''));
        $checkoutGuestResidency = old('guest_residency', (string) ($summary['guest_residency'] ?? ''));
        $selectedProvider = strtolower(trim((string) old('payment_provider', $lockedPaymentProvider !== '' ? $lockedPaymentProvider : (string) ($paymentProviders->first()['provider'] ?? ''))));
        $customerPaymentStatus = strtolower(trim((string) ($reservation->payment_status ?? 'unpaid')));
        $customerPaymentCollectedAt = trim((string) ($reservation->payment_collected_at ?? $reservation->payment_verified_at ?? ''));
    @endphp

    <main class="page">
        <section class="panel" aria-label="Checkout summary">
            <h1 class="title">Checkout & Reservation</h1>
            <p class="sub">Review your prepared reservation and proceed with payment confirmation.</p>

            <div class="layout">
                <div class="grid checkout-details">
                    <div class="cell"><span class="label">Property</span><div class="value">{{ (string) ($property->name ?? 'Property') }}</div></div>
                    <div class="cell"><span class="label">Room / Service</span><div class="value">{{ $roomName !== '' ? $roomName : 'Service' }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['start'] ?? 'Check-in') }}</span><div class="value">{{ (string) ($summary['checkin'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['end'] ?? 'Check-out') }}</span><div class="value">{{ (string) ($summary['checkout'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">Guests</span><div class="value">{{ (int) ($summary['adults'] ?? 1) }} Adults, {{ (int) ($summary['children'] ?? 0) }} Children{{ (int) ($summary['infants'] ?? 0) > 0 ? (', ' . (int) ($summary['infants'] ?? 0) . ' Infant' . ((int) ($summary['infants'] ?? 0) === 1 ? '' : 's')) : '' }}</div></div>
                    <div class="cell"><span class="label">Transfer Option</span><div class="value" id="transferOptionLabelDisplay">{{ (string) ($summary['transfer_option_label'] ?? $summary['transfer_option'] ?? 'Not selected') }}</div></div>
                    <div class="cell"><span class="label">Primary Guest</span><div class="value">{{ trim(((string) ($summary['primary_first_name'] ?? '')) . ' ' . ((string) ($summary['primary_last_name'] ?? ''))) ?: 'Guest Customer' }}</div></div>
                    <div class="cell"><span class="label">Nationality</span><div class="value">{{ (string) ($summary['primary_nationality'] ?? '-') }}</div></div>
                </div>

                <div class="payment-box" aria-label="Payment routing details">
                    <h2>Payment Method</h2>
                    <div class="payment-grid">
                        <div class="payment-stat"><span class="k">Customer Segment</span><span class="v">{{ $isForeigner ? 'Foreign National' : 'Local Maldivian' }}</span></div>
                        <div class="payment-stat"><span class="k">Payment Currency</span><span class="v" id="paymentCurrencyDisplay">{{ $lockedPaymentCurrency }}</span></div>
                        <div class="payment-stat"><span class="k">Gateway</span><span class="v" id="paymentGatewayDisplay">{{ $paymentProviderLabel }}</span></div>
                    </div>
                    @if ($paymentProviders->isNotEmpty())
                        <div class="payment-stat">
                            <span class="k">Available Providers</span>
                            <span class="v">{{ $paymentProviders->map(static fn ($option) => (string) ($option['provider_label'] ?? 'Gateway'))->implode(' | ') }}</span>
                        </div>
                    @endif
                    <p class="payment-note">{{ $paymentNotice }}</p>
                    <div class="payment-stat">
                        <span class="k">Payment Collection Status</span>
                        <span class="v">{{ strtoupper($customerPaymentStatus) }}</span>
                        <span class="payment-note" style="margin-top:4px;">Collected at: {{ $customerPaymentCollectedAt !== '' ? \Illuminate\Support\Carbon::parse($customerPaymentCollectedAt)->format('Y-m-d H:i') : 'Pending' }}</span>
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
                        <div class="invoice-row"><span>Price before discount</span><span class="price-muted">{{ $currency }} {{ number_format($priceBeforeDiscount, 2) }}</span></div>
                        <div class="invoice-row"><span>Limited Time Offer</span><strong>- {{ $currency }} {{ number_format($limitedTimeOffer, 2) }}</strong></div>
                        <div class="invoice-row"><span>First Booking Deal</span><strong>- {{ $currency }} {{ number_format($firstBookingDeal, 2) }}</strong></div>
                        <div class="invoice-row"><span>New user promo code</span><strong>- {{ $currency }} {{ number_format($promoCodeDiscount, 2) }}</strong></div>
                        <div class="invoice-row"><span>Special Discount</span><strong>- {{ $currency }} {{ number_format($specialDiscount, 2) }}</strong></div>
                        <div class="invoice-row"><span>Taxes & fees (included)</span><strong>{{ $currency }} {{ number_format($taxAmount, 2) }}</strong></div>
                        <div class="invoice-row"><span>Tourism tax</span><strong>{{ $currency }} {{ number_format($tourismTax, 2) }}</strong></div>
                        <div class="invoice-row"><span>Sales service tax</span><strong>{{ $currency }} {{ number_format($salesServiceTax, 2) }}</strong></div>
                        <div class="invoice-row"><span>Transfer charges</span><strong id="transferChargeDisplay">{{ $currency }} {{ number_format($transferAmount, 2) }}</strong></div>
                        <div class="total"><span>Total</span><span id="invoiceTotalDisplay">{{ $currency }} {{ $total }}</span></div>
                        <div class="price-save">You've saved {{ $currency }} {{ $savedAmount }} on this booking!</div>
                        <div class="fine-print" style="margin-top:6px;">Entered vendor prices are treated as all-inclusive. Tax is shown as a backward calculation and is not added again.</div>
                        <div class="fine-print">We Price Match</div>
                    </section>

                    <section class="mini-section" aria-label="Cancellation policy">
                        <h2 class="mini-title">4. Cancellation policy</h2>
                        <div class="fine-print">Free cancellation before 23:59, one day before check-in.</div>
                        <div class="fine-print">Cancellation fee may apply after cutoff. If you apply a discount, cancellation fee is based on total paid.</div>
                        <div class="fine-print">All times are in the hotel's local time.</div>
                        <div class="fine-print">{{ $cancellationPolicy }}</div>
                    </section>

                    <section class="mini-section" aria-label="Rewards">
                        <h2 class="mini-title">5. Rewards</h2>
                        <div class="fine-print">Earn {{ max(1, (int) round(($roomSubtotal + $taxAmount) * 0.125)) }} Trip Coins (≈{{ $currency }} {{ number_format((($roomSubtotal + $taxAmount) * 0.005), 2) }}) after your stay.</div>
                    </section>

                    <section class="mini-section" aria-label="Fine print">
                        <h2 class="mini-title">6. Fine print</h2>
                        <div class="fine-print">This property may require a deposit at check-in. Deposit hold release times vary by payment method.</div>
                        @if ($inclusives->isNotEmpty())
                            <div class="fine-print">Inclusions:</div>
                            <ul>
                                @foreach ($inclusives->take(6) as $inclusive)
                                    <li class="fine-print">{{ $inclusive }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </aside>
            </div>

            <div class="actions">
                @if (!empty($reservation->id))
                    <form method="post" action="/booking/checkout/{{ (int) $reservation->id }}/payment-intent">
                        @csrf
                        <input type="hidden" name="payment_currency" value="{{ $lockedPaymentCurrency }}">
                        <input type="hidden" name="payment_gateway" value="{{ $lockedPaymentGateway }}">
                        <input type="hidden" name="payment_provider" value="{{ $selectedProvider }}">
                        <input type="hidden" name="transfer_option" id="transfer_option_input" value="{{ (string) ($summary['transfer_option'] ?? '') }}">
                        <input type="hidden" name="transfer_option_label" id="transfer_option_label_input" value="{{ (string) ($summary['transfer_option_label'] ?? '') }}">
                        <input type="hidden" name="transfer_charge" id="transfer_charge_input" value="{{ number_format($transferAmount, 2, '.', '') }}">
                        <input type="hidden" name="invoice_total_amount" id="invoice_total_amount_input" value="{{ number_format((float) ($summary['total'] ?? 0), 2, '.', '') }}">
                        @if ($transferOptions->isNotEmpty())
                            <div class="policy" style="margin-bottom:8px; width:100%;">
                                <h3>Transfer Selection</h3>
                                @foreach ($transferOptions as $option)
                                    @php
                                        $optionCode = strtolower(trim((string) ($option['code'] ?? '')));
                                        $optionLabel = trim((string) ($option['label'] ?? Str::headline(str_replace('_', ' ', $optionCode))));
                                        $localAdultRate = (float) ($option['local_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                        $localChildRate = (float) ($option['local_child_charge'] ?? $option['child_charge'] ?? 0);
                                        $foreignAdultRate = (float) ($option['foreign_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                        $foreignChildRate = (float) ($option['foreign_child_charge'] ?? $option['child_charge'] ?? 0);
                                        $baseLocalRate = (float) ($option['base_charge_local'] ?? $option['base_charge'] ?? 0);
                                        $baseForeignRate = (float) ($option['base_charge_foreign'] ?? $option['base_charge'] ?? 0);
                                    @endphp
                                    <label style="display:flex; gap:10px; align-items:flex-start; margin:8px 0; color:#335a71;">
                                        <input
                                            type="radio"
                                            name="transfer_option_checkout"
                                            value="{{ $optionCode }}"
                                            data-label="{{ $optionLabel }}"
                                            data-local-adult-rate="{{ number_format($localAdultRate, 2, '.', '') }}"
                                            data-local-child-rate="{{ number_format($localChildRate, 2, '.', '') }}"
                                            data-foreign-adult-rate="{{ number_format($foreignAdultRate, 2, '.', '') }}"
                                            data-foreign-child-rate="{{ number_format($foreignChildRate, 2, '.', '') }}"
                                            data-base-local-rate="{{ number_format($baseLocalRate, 2, '.', '') }}"
                                            data-base-foreign-rate="{{ number_format($baseForeignRate, 2, '.', '') }}"
                                            {{ $optionCode !== '' && ($optionCode === $selectedTransferCode || ($selectedTransferCode === '' && $loop->first)) ? 'checked' : '' }}
                                        >
                                        <span>
                                            <strong>{{ $optionLabel }}</strong>
                                            <span style="display:block; font-size:0.8rem; color:#516e82;">
                                                Local: Adult {{ $currency }} {{ number_format($localAdultRate, 2) }}
                                                @if ($children > 0)
                                                    • Child {{ $currency }} {{ number_format($localChildRate, 2) }}
                                                @endif
                                            </span>
                                            <span style="display:block; font-size:0.8rem; color:#516e82;">
                                                Foreign: Adult {{ $currency }} {{ number_format($foreignAdultRate, 2) }}
                                                @if ($children > 0)
                                                    • Child {{ $currency }} {{ number_format($foreignChildRate, 2) }}
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <label class="label" for="primary_nationality">Guest nationality *</label>
                        <input
                            id="primary_nationality"
                            name="primary_nationality"
                            type="text"
                            required
                            maxlength="120"
                            value="{{ $checkoutPrimaryNationality }}"
                            style="min-width:220px; padding:8px 10px; border:1px solid #c9dbe8; border-radius:10px; margin-right:8px;"
                            placeholder="e.g. Maldivian"
                        >
                        <label class="label" for="guest_residency">Residency *</label>
                        <select
                            id="guest_residency"
                            name="guest_residency"
                            required
                            style="min-width:220px; padding:8px 10px; border:1px solid #c9dbe8; border-radius:10px; margin-right:8px;"
                        >
                            <option value="">Select residency</option>
                            <option value="local_resident" {{ $checkoutGuestResidency === 'local_resident' ? 'selected' : '' }}>Local resident</option>
                            <option value="foreign_national" {{ $checkoutGuestResidency === 'foreign_national' ? 'selected' : '' }}>Foreign national</option>
                        </select>
                        @if ($paymentProviders->isNotEmpty())
                            <label class="label" for="payment_provider_select">Choose payment method</label>
                            <select id="payment_provider_select" name="payment_provider_select" style="min-width:260px; padding:8px 10px; border:1px solid #c9dbe8; border-radius:10px; margin-right:8px;">
                                @foreach ($paymentProviders as $providerOption)
                                    @php
                                        $providerKey = strtolower(trim((string) ($providerOption['provider'] ?? '')));
                                        $providerLabel = trim((string) ($providerOption['provider_label'] ?? 'Gateway'));
                                    @endphp
                                    <option
                                        value="{{ $providerKey }}"
                                        data-provider="{{ $providerKey }}"
                                        data-provider-label="{{ $providerLabel }}"
                                        {{ $providerKey === $selectedProvider ? 'selected' : '' }}
                                    >{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('primary_nationality')
                            <div class="fine-print" style="color:#a73434; width:100%;">{{ $message }}</div>
                        @enderror
                        @error('guest_residency')
                            <div class="fine-print" style="color:#a73434; width:100%;">{{ $message }}</div>
                        @enderror
                        <button class="btn" type="submit">Confirm & Pay</button>
                    </form>
                @else
                    <button class="btn" type="button" disabled>Confirm & Pay</button>
                @endif
                <a class="btn alt" href="{{ (string) ($backUrl ?? ($room ? ('/room/' . (int) ($room->id ?? 0)) : '/customer')) }}">Back</a>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>

    <script>
        (function () {
            var selection = document.getElementById('payment_provider_select');
            var currencyDisplay = document.getElementById('paymentCurrencyDisplay');
            var gatewayDisplay = document.getElementById('paymentGatewayDisplay');
            var form = selection ? selection.closest('form') : document.querySelector('form[action*="/payment-intent"]');
            var hiddenCurrency = form ? form.querySelector('input[name="payment_currency"]') : null;
            var hiddenGateway = form ? form.querySelector('input[name="payment_gateway"]') : null;
            var hiddenProvider = form ? form.querySelector('input[name="payment_provider"]') : null;
            var transferOptionInput = document.getElementById('transfer_option_input');
            var transferOptionLabelInput = document.getElementById('transfer_option_label_input');
            var transferChargeInput = document.getElementById('transfer_charge_input');
            var invoiceTotalInput = document.getElementById('invoice_total_amount_input');
            var transferChargeDisplay = document.getElementById('transferChargeDisplay');
            var invoiceTotalDisplay = document.getElementById('invoiceTotalDisplay');
            var transferLabelDisplay = document.getElementById('transferOptionLabelDisplay');
            var guestResidencyInput = document.getElementById('guest_residency');
            var transferOptionsUi = Array.prototype.slice.call(document.querySelectorAll('input[name="transfer_option_checkout"]'));
            var adults = {{ $adults }};
            var children = {{ $children }};
            var baseTotal = {{ number_format($baseTotalBeforeTransfer, 2, '.', '') }};
            var currencyCode = @json($currency);
            var paymentOptions = @json($paymentOptions->values()->all());

            var toCurrency = function (value) {
                return currencyCode + ' ' + Number(value || 0).toFixed(2);
            };

            var selectedTransferOption = function () {
                for (var i = 0; i < transferOptionsUi.length; i++) {
                    if (transferOptionsUi[i].checked) {
                        return transferOptionsUi[i];
                    }
                }
                return null;
            };

            var syncTransferTotal = function () {
                if (!transferOptionsUi.length) {
                    return;
                }

                var selected = selectedTransferOption();
                var residency = guestResidencyInput ? String(guestResidencyInput.value || '').trim().toLowerCase() : '';
                var isForeign = residency === 'foreign_national';

                var transferTotal = 0;
                if (selected) {
                    var adultRate = Number(selected.getAttribute(isForeign ? 'data-foreign-adult-rate' : 'data-local-adult-rate') || 0);
                    var childRate = Number(selected.getAttribute(isForeign ? 'data-foreign-child-rate' : 'data-local-child-rate') || 0);
                    var baseRate = Number(selected.getAttribute(isForeign ? 'data-base-foreign-rate' : 'data-base-local-rate') || 0);
                    transferTotal = baseRate + (adultRate * Math.max(1, adults)) + (childRate * Math.max(0, children));
                }

                var grandTotal = baseTotal + transferTotal;

                if (transferOptionInput) {
                    transferOptionInput.value = selected ? String(selected.value || '').trim() : '';
                }
                if (transferOptionLabelInput) {
                    transferOptionLabelInput.value = selected ? String(selected.getAttribute('data-label') || '').trim() : '';
                }
                if (transferChargeInput) {
                    transferChargeInput.value = transferTotal.toFixed(2);
                }
                if (invoiceTotalInput) {
                    invoiceTotalInput.value = grandTotal.toFixed(2);
                }
                if (transferChargeDisplay) {
                    transferChargeDisplay.textContent = toCurrency(transferTotal);
                }
                if (invoiceTotalDisplay) {
                    invoiceTotalDisplay.textContent = toCurrency(grandTotal);
                }
                if (transferLabelDisplay) {
                    transferLabelDisplay.textContent = selected
                        ? String(selected.getAttribute('data-label') || selected.value || 'Selected')
                        : 'Not selected';
                }
            };

            var syncSelection = function () {
                if (!selection) {
                    return;
                }

                var selectedOption = selection.options[selection.selectedIndex];
                if (!selectedOption) {
                    return;
                }

                var provider = (selectedOption.getAttribute('data-provider') || '').trim().toLowerCase();
                var providerLabel = (selectedOption.getAttribute('data-provider-label') || '').trim();
                var matched = null;
                for (var i = 0; i < paymentOptions.length; i++) {
                    var option = paymentOptions[i] || {};
                    var optionProvider = String(option.provider || option.gateway || '').trim().toLowerCase();
                    if (optionProvider === provider) {
                        matched = option;
                        break;
                    }
                }

                var currency = matched ? String(matched.currency || '').trim() : '';
                var gateway = matched ? String(matched.gateway || '').trim() : '';

                if (currencyDisplay && currency !== '') {
                    currencyDisplay.textContent = currency;
                }

                if (gatewayDisplay && providerLabel !== '') {
                    gatewayDisplay.textContent = providerLabel;
                }

                if (hiddenCurrency && currency !== '') {
                    hiddenCurrency.value = currency;
                }

                if (hiddenGateway && gateway !== '') {
                    hiddenGateway.value = gateway;
                }

                if (hiddenProvider) {
                    hiddenProvider.value = provider;
                }
            };

            if (selection) {
                selection.addEventListener('change', syncSelection);
            }
            if (guestResidencyInput) {
                guestResidencyInput.addEventListener('change', syncTransferTotal);
            }
            transferOptionsUi.forEach(function (input) {
                input.addEventListener('change', syncTransferTotal);
            });
            syncSelection();
            syncTransferTotal();
        })();
    </script>
</body>
</html>