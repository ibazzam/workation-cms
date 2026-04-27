<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Payment | Workation</title>
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
        $gatewayLabel = trim((string) ($paymentPayload['gateway_label'] ?? 'Card Gateway'));
        $paymentCurrency = strtoupper(trim((string) ($reservation->payment_currency ?? $reservation->currency ?? 'MVR')));
        $paymentAmount = number_format((float) ($reservation->payment_amount ?? $reservation->total_amount ?? 0), 2);
        $sourceCurrency = strtoupper(trim((string) ($paymentPayload['source_currency'] ?? $reservation->currency ?? 'MVR')));
        $sourceAmount = number_format((float) ($paymentPayload['source_amount'] ?? $reservation->total_amount ?? 0), 2);
    @endphp

    <main class="page">
        <section class="panel">
            <p class="eyebrow">Hosted Payment</p>
            <h1>Secure Payment Confirmation</h1>
            <p class="sub">This internal hosted page is the handoff target for the new payment intent flow. It keeps the checkout path working now and can later be swapped to an external provider without changing the reservation routing rules.</p>

            <div class="grid">
                <div class="cell"><span class="k">Property</span><span class="v">{{ (string) ($property->name ?? 'Property') }}</span></div>
                <div class="cell"><span class="k">Reservation</span><span class="v">#{{ (int) ($reservation->id ?? 0) }}</span></div>
                <div class="cell"><span class="k">Gateway</span><span class="v">{{ $gatewayLabel }}</span></div>
                <div class="cell"><span class="k">Amount</span><span class="v">{{ $paymentCurrency }} {{ $paymentAmount }}</span></div>
                <div class="cell"><span class="k">Booking Amount</span><span class="v">{{ $sourceCurrency }} {{ $sourceAmount }}</span></div>
            </div>

            <div class="actions">
                <form method="post" action="/booking/payment/hosted/{{ (int) ($reservation->id ?? 0) }}/complete">
                    @csrf
                    <input type="hidden" name="intent_id" value="{{ $intentId }}">
                    <input type="hidden" name="payment_reference" value="SIM-{{ (int) ($reservation->id ?? 0) }}">
                    <button class="btn" type="submit">Complete Payment</button>
                </form>
                <a class="btn alt" href="/booking/checkout/{{ (int) ($reservation->id ?? 0) }}">Back to Checkout</a>
            </div>
        </section>
    </main>
</body>
</html>