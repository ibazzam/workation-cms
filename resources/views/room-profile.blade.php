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
        .booking-layout { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(280px,0.8fr); gap:12px; align-items:start; }
        .booking-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
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
        @media (max-width: 900px) { .gallery, .booking-grid { grid-template-columns:1fr; } .booking-layout { grid-template-columns:1fr; } }
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
            <div class="booking-layout">
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

                    <div class="booking-grid">
                        <div class="field"><label for="primaryFirstName">Primary Guest First Name</label><input id="primaryFirstName" name="primary_first_name" type="text" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}" placeholder="First name" class="{{ $errors->has('primary_first_name') ? 'input-error' : '' }}" required>@error('primary_first_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="primaryLastName">Primary Guest Last Name</label><input id="primaryLastName" name="primary_last_name" type="text" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}" placeholder="Last name" class="{{ $errors->has('primary_last_name') ? 'input-error' : '' }}" required>@error('primary_last_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="primaryNationality">Primary Guest Nationality</label><input id="primaryNationality" name="primary_nationality" type="text" value="{{ old('primary_nationality', (string) ($prefill['primary_nationality'] ?? '')) }}" placeholder="Nationality" class="{{ $errors->has('primary_nationality') ? 'input-error' : '' }}" required>@error('primary_nationality')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="primaryEmail">Primary Guest Email</label><input id="primaryEmail" name="primary_email" type="email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}" placeholder="guest@example.com" class="{{ $errors->has('primary_email') ? 'input-error' : '' }}" required>@error('primary_email')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="primaryMobile">Primary Guest Mobile</label><input id="primaryMobile" name="primary_mobile" type="text" value="{{ old('primary_mobile', (string) ($prefill['primary_mobile'] ?? '')) }}" placeholder="+960 ..." class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required>@error('primary_mobile')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field full"><label for="additionalGuestDetails">Additional Guest Details (Optional)</label><textarea id="additionalGuestDetails" name="additional_guest_details" placeholder="Add optional details for additional guests: names, age group, notes, special requests.">{{ old('additional_guest_details', '') }}</textarea></div>
                        <div class="field"><label for="checkin">Check-in</label><input id="checkin" name="checkin" type="date" required value="{{ old('checkin', (string) ($prefill['checkin'] ?? '')) }}" class="{{ $errors->has('checkin') ? 'input-error' : '' }}">@error('checkin')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="checkout">Check-out</label><input id="checkout" name="checkout" type="date" required value="{{ old('checkout', (string) ($prefill['checkout'] ?? '')) }}" class="{{ $errors->has('checkout') ? 'input-error' : '' }}">@error('checkout')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ old('adults', (int) ($prefill['adults'] ?? 2)) }}" class="{{ $errors->has('adults') ? 'input-error' : '' }}" required>@error('adults')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ old('children', (int) ($prefill['children'] ?? 0)) }}" class="{{ $errors->has('children') ? 'input-error' : '' }}">@error('children')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="transferOption">Prepared Transfer Option</label>
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

                    <p class="summary">Proceeding will prepare your reservation and take you to checkout confirmation.</p>
                    <button class="submit" type="submit">Proceed to Booking & Reservation</button>
                </form>

                <aside class="invoice" aria-label="Invoice summary">
                    <h3>Invoice / Billing Summary</h3>
                    <div class="invoice-row"><span>Nightly Rate</span><strong id="invoiceNightly">{{ $currency }} {{ number_format($basePriceRaw, 2) }}</strong></div>
                    <div class="invoice-row"><span>Stay (nights)</span><strong id="invoiceNights">1</strong></div>
                    <div class="invoice-row"><span>Room Subtotal</span><strong id="invoiceRoomSubtotal">{{ $currency }} 0.00</strong></div>
                    <div class="invoice-row"><span>Promotion / Discount</span><strong id="invoiceDiscount">- {{ $currency }} 0.00</strong></div>
                    <div class="invoice-row"><span>Tax ({{ number_format($taxRate, 2) }}%)</span><strong id="invoiceTax">{{ $currency }} 0.00</strong></div>
                    <div class="invoice-row"><span>Transfer Charges</span><strong id="invoiceTransfer">{{ $currency }} 0.00</strong></div>
                    <div class="invoice-row"><span>Guests</span><strong id="invoiceGuests">{{ (int) ($prefill['adults'] ?? 2) }} Adults, {{ (int) ($prefill['children'] ?? 0) }} Children</strong></div>
                    <div class="invoice-total"><span>Total</span><span id="invoiceTotal">{{ $currency }} 0.00</span></div>

                    <div class="policy-box" aria-label="Inclusions">
                        <h4>Inclusives</h4>
                        @if ($inclusives->isNotEmpty())
                            <ul>
                                @foreach ($inclusives->take(8) as $inclusive)
                                    <li>{{ $inclusive }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Room stay inclusives will be confirmed at reservation time.</p>
                        @endif
                    </div>

                    <div class="policy-box" aria-label="Cancellation policy">
                        <h4>Cancellation Policy</h4>
                        <p>{{ $cancellationPolicy }}</p>
                    </div>
                </aside>
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
