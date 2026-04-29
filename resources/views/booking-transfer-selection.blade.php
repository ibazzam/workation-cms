<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transfer Selection | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px,calc(100% - 24px)); margin:14px auto 28px; }
        .panel { border:1px solid var(--line); border-radius:16px; background:var(--surface); padding:16px; display:grid; gap:12px; }
        .title { margin:0; font-size:1.2rem; color:#153f59; }
        .sub { margin:0; color:#4b6980; font-size:0.88rem; }
        .process-chip { width:max-content; border:1px solid #cfe0eb; border-radius:999px; background:#edf6f3; padding:5px 10px; color:#26526c; font-size:0.74rem; font-weight:700; }
        .layout { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(320px,0.8fr); gap:12px; align-items:start; }
        .matrix-wrap { border:1px solid #dbe7f0; border-radius:12px; background:#fbfdff; padding:12px; display:grid; gap:10px; }
        .matrix-head { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
        .matrix-title { margin:0; font-size:1rem; color:#163f59; }
        .matrix-note { margin:2px 0 0; color:#4f6a7f; font-size:0.82rem; }
        .badge { border:1px solid #cbdde8; border-radius:999px; background:#f4fbff; color:#24516b; font-size:0.74rem; font-weight:700; padding:4px 10px; }
        .matrix-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .matrix-card { border:1px solid #d3e2ec; border-radius:10px; background:#ffffff; padding:10px; display:grid; gap:8px; }
        .matrix-card h3 { margin:0; font-size:0.86rem; text-transform:uppercase; letter-spacing:0.08em; color:#335f78; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:6px 2px; border-bottom:1px solid #e3edf4; font-size:0.82rem; color:#35586f; }
        th { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.07em; color:#54738a; }
        tr:last-child td { border-bottom:0; }
        .form-box { border:1px solid #dbe7f0; border-radius:12px; background:#fbfdff; padding:12px; display:grid; gap:10px; }
        .field { display:grid; gap:6px; }
        .field label { font-size:0.76rem; text-transform:uppercase; letter-spacing:0.06em; color:#3f6177; }
        .field select { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; min-height:42px; font:inherit; background:#f8fdff; }
        .toggle { display:flex; align-items:center; gap:8px; font-size:0.84rem; color:#2a526b; font-weight:600; }
        .helper { margin:0; color:#55748a; font-size:0.78rem; line-height:1.45; }
        .summary { border:1px solid #d6e5ee; border-radius:12px; background:#f7fbff; padding:12px; display:grid; gap:7px; }
        .summary-line { display:flex; justify-content:space-between; gap:8px; font-size:0.84rem; color:#3b5c73; }
        .summary-total { margin-top:6px; border:1px solid #cfe0eb; border-radius:10px; background:#edf6f3; padding:10px; display:flex; justify-content:space-between; font-weight:700; color:#21475f; }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .error-box { border:1px solid #e6b2b2; background:#fff5f5; color:#8f2323; border-radius:10px; padding:10px 12px; }
        .error-box ul { margin:0; padding-left:18px; }
        @media (max-width: 980px) {
            .layout, .matrix-grid { grid-template-columns:1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $summary = $summary ?? [];
        $transferOptions = collect($transferOptions ?? [])->filter(static fn ($option) => is_array($option))->values();
        $currency = strtoupper(trim((string) ($currency ?? 'MVR')));
        $adults = max(1, (int) ($summary['adults'] ?? 1));
        $children = max(0, (int) ($summary['children'] ?? 0));
        $isForeigner = strtolower(trim((string) ($summary['guest_residency'] ?? ''))) === 'foreign_national';
        $baseTotal = max(0, (float) ($summary['discounted_subtotal'] ?? ((float) ($summary['invoice_total_amount'] ?? 0))));
        $reservationTotal = max(0, (float) ($summary['invoice_total_amount'] ?? 0));
        $backUrl = trim((string) ($backUrl ?? '/'));
        $selectedOption = strtolower(trim((string) ($selectedTransferOption ?? 'none')));
        $includeTransfer = (bool) ($includeTransfer ?? false);
    @endphp

    @include('partials.customer-uniform-header', [
        'headerHideOnScroll' => false,
        'headerShowSearch' => false,
        'headerMode' => 'checkout',
        'headerCategoryLinks' => [],
        'headerCheckoutContext' => [],
        'headerContinueUrl' => (string) request()->fullUrl(),
    ])

    <main class="page">
        @include('partials.booking-process-highlights', [
            'bookingProcessCurrentStep' => 2,
            'bookingProcessBackUrl' => $backUrl,
            'bookingProcessNextText' => 'Next step after this page: review payment options and confirm the locked checkout summary.',
        ])

        <section class="panel" aria-label="Transfer selection">
            <span class="process-chip">Checkout Process: Step 2 of 3</span>
            <h1 class="title">Select Transfer Option</h1>
            <p class="sub">Guest details and nationality are already captured. Choose transfer here, then continue to payment selection.</p>

            @if ($errors->any())
                <div class="error-box" role="alert" aria-live="polite">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="layout">
                <div class="matrix-wrap">
                    <div class="matrix-head">
                        <div>
                            <h2 class="matrix-title">Transfer Fare Matrix</h2>
                            <p class="matrix-note">Mode of transport and per-passenger fares by residency type.</p>
                        </div>
                        <span class="badge">Available at checkout</span>
                    </div>

                    <div class="matrix-grid">
                        <section class="matrix-card" aria-label="Local resident rates">
                            <h3>Local Resident</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mode</th>
                                        <th>Adult Fare</th>
                                        <th>Child Fare</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transferOptions as $option)
                                        @php
                                            $label = trim((string) ($option['label'] ?? 'Transfer'));
                                            $adult = (float) ($option['local_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                            $child = (float) ($option['local_child_charge'] ?? $option['child_charge'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>{{ $currency }} {{ number_format($adult, 2) }}</td>
                                            <td>{{ $currency }} {{ number_format($child, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3">No transfer options configured.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </section>

                        <section class="matrix-card" aria-label="Foreigner rates">
                            <h3>Foreigner</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mode</th>
                                        <th>Adult Fare</th>
                                        <th>Child Fare</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transferOptions as $option)
                                        @php
                                            $label = trim((string) ($option['label'] ?? 'Transfer'));
                                            $adult = (float) ($option['foreign_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                            $child = (float) ($option['foreign_child_charge'] ?? $option['child_charge'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>{{ $currency }} {{ number_format($adult, 2) }}</td>
                                            <td>{{ $currency }} {{ number_format($child, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3">No transfer options configured.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </section>
                    </div>

                    <p class="helper">Guest nationality: <strong>{{ (string) ($summary['primary_nationality'] ?? '-') }}</strong>. Applied rate type: <strong id="rateTypeLabel">{{ $isForeigner ? 'Foreigner' : 'Local Resident' }}</strong>.</p>
                </div>

                <form class="form-box" method="post" action="/booking/checkout/{{ (int) ($reservation->id ?? 0) }}/transfer" id="transferSelectionForm">
                    @csrf
                    <label class="toggle">
                        <input type="checkbox" name="include_transfer" id="includeTransferInput" value="1" {{ $includeTransfer ? 'checked' : '' }}>
                        Include transfer in this booking
                    </label>

                    <div class="field">
                        <label for="transferOptionInput">Select Transfer Mode</label>
                        <select name="transfer_option" id="transferOptionInput" {{ $includeTransfer ? '' : 'disabled' }}>
                            @foreach ($transferOptions as $option)
                                @php
                                    $code = strtolower(trim((string) ($option['code'] ?? '')));
                                    $label = trim((string) ($option['label'] ?? 'Transfer'));
                                    $base = (float) ($option['base_charge'] ?? 0);
                                    $localAdult = (float) ($option['local_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                    $localChild = (float) ($option['local_child_charge'] ?? $option['child_charge'] ?? 0);
                                    $foreignAdult = (float) ($option['foreign_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                    $foreignChild = (float) ($option['foreign_child_charge'] ?? $option['child_charge'] ?? 0);
                                @endphp
                                <option
                                    value="{{ $code }}"
                                    data-base-charge="{{ number_format($base, 2, '.', '') }}"
                                    data-local-adult-rate="{{ number_format($localAdult, 2, '.', '') }}"
                                    data-local-child-rate="{{ number_format($localChild, 2, '.', '') }}"
                                    data-foreign-adult-rate="{{ number_format($foreignAdult, 2, '.', '') }}"
                                    data-foreign-child-rate="{{ number_format($foreignChild, 2, '.', '') }}"
                                    {{ $selectedOption === $code ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="summary" aria-label="Transfer summary">
                        <div class="summary-line"><span>Guests</span><strong>{{ $adults }} Adults, {{ $children }} Children</strong></div>
                        <div class="summary-line"><span>Base booking amount</span><strong id="baseAmountLabel">{{ $currency }} {{ number_format($baseTotal, 2) }}</strong></div>
                        <div class="summary-line"><span>Transfer charge</span><strong id="transferChargeLabel">{{ $currency }} 0.00</strong></div>
                        <div class="summary-total"><span>Estimated total</span><span id="estimatedTotalLabel">{{ $currency }} {{ number_format($reservationTotal, 2) }}</span></div>
                    </div>

                    <p class="helper">Next step will show payment options (MIB/BML/Stripe) based on your saved nationality and residency.</p>

                    <div class="actions">
                        <a class="btn alt" href="{{ $backUrl !== '' ? $backUrl : '/' }}">Back</a>
                        <button class="btn" type="submit">Select &amp; Continue</button>
                    </div>
                </form>
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>

    <script>
        (function () {
            const includeTransferInput = document.getElementById('includeTransferInput');
            const transferOptionInput = document.getElementById('transferOptionInput');
            const transferChargeLabel = document.getElementById('transferChargeLabel');
            const estimatedTotalLabel = document.getElementById('estimatedTotalLabel');
            const rateTypeLabel = document.getElementById('rateTypeLabel');

            const currency = @json($currency);
            const adults = Number(@json($adults));
            const children = Number(@json($children));
            const baseTotal = Number(@json($baseTotal));
            const isForeigner = @json($isForeigner);

            const toCurrency = function (value) {
                return currency + ' ' + Number(value || 0).toFixed(2);
            };

            const sync = function () {
                const includeTransfer = !!(includeTransferInput && includeTransferInput.checked);
                if (transferOptionInput) {
                    transferOptionInput.disabled = !includeTransfer;
                }

                const selected = transferOptionInput && transferOptionInput.selectedOptions.length > 0
                    ? transferOptionInput.selectedOptions[0]
                    : null;

                const baseCharge = Number(selected?.dataset?.baseCharge || 0);
                const adultRate = Number(selected?.dataset?.[isForeigner ? 'foreignAdultRate' : 'localAdultRate'] || 0);
                const childRate = Number(selected?.dataset?.[isForeigner ? 'foreignChildRate' : 'localChildRate'] || 0);

                const transferCharge = includeTransfer && selected
                    ? baseCharge + (adultRate * adults) + (childRate * children)
                    : 0;
                const estimatedTotal = baseTotal + transferCharge;

                if (rateTypeLabel) {
                    rateTypeLabel.textContent = isForeigner ? 'Foreigner' : 'Local Resident';
                }
                if (transferChargeLabel) {
                    transferChargeLabel.textContent = toCurrency(transferCharge);
                }
                if (estimatedTotalLabel) {
                    estimatedTotalLabel.textContent = toCurrency(estimatedTotal);
                }
            };

            if (includeTransferInput) {
                includeTransferInput.addEventListener('change', sync);
            }
            if (transferOptionInput) {
                transferOptionInput.addEventListener('change', sync);
            }

            sync();
        })();
    </script>
</body>
</html>
