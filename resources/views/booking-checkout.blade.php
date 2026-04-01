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
        .layout { margin-top:12px; display:grid; grid-template-columns:minmax(0,1.2fr) minmax(300px,0.8fr); gap:12px; align-items:start; }
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
        .actions { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; }
        .btn { text-decoration:none; border:1px solid #d9b06f; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#603b0c; border-radius:10px; padding:10px 13px; font-weight:700; font-size:0.86rem; }
        .btn.alt { border-color:#c5d8e6; background:#f7fbff; color:#244a65; }
        @media (max-width: 980px) { .layout { grid-template-columns:1fr; } }
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
        $inclusives = collect($inclusives ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values();
        $cancellationPolicy = trim((string) ($cancellationPolicy ?? 'Standard cancellation terms apply.'));
        $dateLabels = $dateLabels ?? ['start' => 'Check-in', 'end' => 'Check-out'];
        $categoryDetails = collect($categoryDetails ?? [])->filter(static fn ($item) => is_array($item))->values();
    @endphp

    <main class="page">
        <section class="panel" aria-label="Checkout summary">
            <h1 class="title">Checkout & Reservation</h1>
            <p class="sub">Review your prepared reservation and proceed with payment confirmation.</p>

            <div class="layout">
                <div class="grid">
                    <div class="cell"><span class="label">Property</span><div class="value">{{ (string) ($property->name ?? 'Property') }}</div></div>
                    <div class="cell"><span class="label">Room / Service</span><div class="value">{{ $roomName !== '' ? $roomName : 'Service' }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['start'] ?? 'Check-in') }}</span><div class="value">{{ (string) ($summary['checkin'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">{{ (string) ($dateLabels['end'] ?? 'Check-out') }}</span><div class="value">{{ (string) ($summary['checkout'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">Guests</span><div class="value">{{ (int) ($summary['adults'] ?? 1) }} Adults, {{ (int) ($summary['children'] ?? 0) }} Children</div></div>
                    <div class="cell"><span class="label">Transfer Option</span><div class="value">{{ (string) ($summary['transfer_option'] ?? 'Not selected') }}</div></div>
                    <div class="cell"><span class="label">Primary Guest</span><div class="value">{{ trim(((string) ($summary['primary_first_name'] ?? '')) . ' ' . ((string) ($summary['primary_last_name'] ?? ''))) ?: 'Guest Customer' }}</div></div>
                    <div class="cell"><span class="label">Nationality</span><div class="value">{{ (string) ($summary['primary_nationality'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">Primary Guest Email</span><div class="value">{{ (string) ($summary['primary_email'] ?? 'guest@workation.local') }}</div></div>
                    <div class="cell"><span class="label">Primary Guest Mobile</span><div class="value">{{ (string) ($summary['primary_mobile'] ?? '-') }}</div></div>
                    <div class="cell"><span class="label">Additional Guest Details</span><div class="value">{{ (string) ($summary['additional_guest_details'] ?? 'Not provided') }}</div></div>
                    @if ($categoryDetails->isNotEmpty())
                        <div class="cell"><span class="label">Category Details</span><div class="value">{{ $categoryDetails->map(static fn ($item) => ((string) ($item['label'] ?? 'Detail')) . ': ' . ((string) ($item['value'] ?? '-')))->implode(' | ') }}</div></div>
                    @endif
                </div>

                <aside class="invoice" aria-label="Invoice summary">
                    <h2>Invoice Summary</h2>
                    <div class="invoice-row"><span>Subtotal</span><strong>{{ $currency }} {{ number_format((float) ($summary['room_subtotal'] ?? 0), 2) }}</strong></div>
                    <div class="invoice-row"><span>Promotion / Discount ({{ number_format((float) ($summary['discount_percent'] ?? 0), 2) }}%)</span><strong>- {{ $currency }} {{ number_format((float) ($summary['discount_amount'] ?? 0), 2) }}</strong></div>
                    <div class="invoice-row"><span>Tax ({{ number_format((float) ($summary['tax_rate'] ?? 0), 2) }}%)</span><strong>{{ $currency }} {{ number_format((float) ($summary['tax_amount'] ?? 0), 2) }}</strong></div>
                    <div class="invoice-row"><span>Transfer Charges</span><strong>{{ $currency }} {{ number_format((float) ($summary['transfer_charge'] ?? 0), 2) }}</strong></div>
                    <div class="total"><span>Total</span><span>{{ $currency }} {{ $total }}</span></div>

                    <div class="policy" aria-label="Inclusions">
                        <h3>Inclusives</h3>
                        @if ($inclusives->isNotEmpty())
                            <ul>
                                @foreach ($inclusives->take(8) as $inclusive)
                                    <li>{{ $inclusive }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Inclusion details are available on request from the property host.</p>
                        @endif
                    </div>

                    <div class="policy" aria-label="Cancellation policy">
                        <h3>Cancellation Policy</h3>
                        <p>{{ $cancellationPolicy }}</p>
                    </div>
                </aside>
            </div>

            <div class="actions">
                <a class="btn" href="#" onclick="alert('Payment gateway integration can be connected next.'); return false;">Confirm & Pay</a>
                <a class="btn alt" href="{{ (string) ($backUrl ?? ($room ? ('/room/' . (int) ($room->id ?? 0)) : '/customer')) }}">Back</a>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
