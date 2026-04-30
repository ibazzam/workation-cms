<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Payment | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; background:var(--bg); color:var(--ink); }
        .page { width:min(760px,calc(100% - 24px)); margin:24px auto; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:18px; display:grid; gap:14px; }
        .eyebrow { margin:0; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#557185; font-weight:700; }
        h1 { margin:0; font-size:1.3rem; }
        .sub { margin:0; color:#4d6a7e; line-height:1.55; }
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
    @endphp

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
                    <a class="btn" href="{{ $externalHandoffUrl }}" rel="noopener">Continue to {{ $externalHandoffProvider }}</a>
                @else
                    <form method="post" action="/booking/payment/hosted/{{ (int) ($reservation->id ?? 0) }}/complete">
                        @csrf
                        <input type="hidden" name="intent_id" value="{{ $intentId }}">
                        <input type="hidden" name="payment_reference" value="SIM-{{ (int) ($reservation->id ?? 0) }}">
                        <button class="btn" type="submit">Complete Payment</button>
                    </form>
                @endif
                <a class="btn alt" href="/booking/checkout/{{ (int) ($reservation->id ?? 0) }}">Back to Checkout</a>
            </div>
        </section>
    </main>
</body>
</html>