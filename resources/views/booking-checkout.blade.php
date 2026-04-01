<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(900px,calc(100% - 24px)); margin:14px auto 28px; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:16px; }
        .title { margin:0; font-size:1.25rem; }
        .sub { margin:6px 0 0; color:#45667d; }
        .grid { margin-top:12px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .cell { border:1px solid #dbe7f0; border-radius:12px; padding:10px; background:#fbfdff; }
        .label { display:block; font-size:0.74rem; text-transform:uppercase; letter-spacing:0.06em; color:#58708a; }
        .value { margin-top:4px; font-weight:600; }
        .total { margin-top:12px; border:1px solid #cfe0eb; border-radius:12px; background:#edf6f3; padding:12px; font-size:1.02rem; font-weight:700; color:#21475f; }
        .actions { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; }
        .btn { text-decoration:none; border:1px solid #d9b06f; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#603b0c; border-radius:10px; padding:10px 13px; font-weight:700; font-size:0.86rem; }
        .btn.alt { border-color:#c5d8e6; background:#f7fbff; color:#244a65; }
        @media (max-width: 760px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    @php
        $summary = $summary ?? [];
        $property = $property ?? null;
        $roomName = trim((string) ($roomName ?? ''));
        $currency = strtoupper(trim((string) ($reservation->currency ?? $room->currency ?? $property->currency ?? 'MVR')));
        $total = number_format((float) ($summary['total'] ?? 0), 2);
    @endphp

    <main class="page">
        <section class="panel" aria-label="Checkout summary">
            <h1 class="title">Checkout & Reservation</h1>
            <p class="sub">Review your prepared reservation and proceed with payment confirmation.</p>

            <div class="grid">
                <div class="cell"><span class="label">Property</span><div class="value">{{ (string) ($property->name ?? 'Property') }}</div></div>
                <div class="cell"><span class="label">Room</span><div class="value">{{ $roomName !== '' ? $roomName : 'Room' }}</div></div>
                <div class="cell"><span class="label">Check-in</span><div class="value">{{ (string) ($summary['checkin'] ?? '-') }}</div></div>
                <div class="cell"><span class="label">Check-out</span><div class="value">{{ (string) ($summary['checkout'] ?? '-') }}</div></div>
                <div class="cell"><span class="label">Guests</span><div class="value">{{ (int) ($summary['adults'] ?? 1) }} Adults, {{ (int) ($summary['children'] ?? 0) }} Children</div></div>
                <div class="cell"><span class="label">Transfer</span><div class="value">{{ (string) ($summary['transfer_option'] ?? 'Not selected') }} ({{ $currency }} {{ number_format((float) ($summary['transfer_charge'] ?? 0), 2) }})</div></div>
            </div>

            <div class="total">Total: {{ $currency }} {{ $total }}</div>

            <div class="actions">
                <a class="btn" href="#" onclick="alert('Payment gateway integration can be connected next.'); return false;">Confirm & Pay</a>
                <a class="btn alt" href="{{ $room ? ('/room/' . (int) ($room->id ?? 0)) : '/customer' }}">Back to Room Profile</a>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
