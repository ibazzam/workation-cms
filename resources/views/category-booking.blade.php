<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($categoryLabel ?? 'Category') }} Booking | Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(980px,calc(100% - 24px)); margin:14px auto 28px; }
        .hero { border:1px solid #cbe0ea; border-radius:16px; background:linear-gradient(132deg,#0f6179 0%,#1d848c 58%,#2f9891 100%); color:#ecfcff; padding:16px; }
        .hero h1 { margin:0; font-size:clamp(1.15rem,2.2vw,1.7rem); }
        .hero p { margin:6px 0 0; color:#daf5f9; }
        .panel { margin-top:12px; border:1px solid var(--line); border-radius:14px; background:var(--surface); padding:12px; }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .field { display:grid; gap:5px; }
        .field label { font-size:0.78rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; }
        .field input, .field textarea { width:100%; border:1px solid #b8d9e2; border-radius:10px; padding:10px 11px; font:inherit; background:#f8fdff; }
        .field textarea { min-height:90px; resize:vertical; }
        .field.full { grid-column:1/-1; }
        .field .input-error { border-color:#c54f4f; background:#fff8f8; }
        .field .error-text { margin:0; font-size:0.75rem; color:#a32929; }
        .form-errors { margin:0 0 10px; border:1px solid #e6b2b2; background:#fff5f5; color:#8f2323; border-radius:10px; padding:10px 12px; }
        .form-errors ul { margin:0; padding-left:18px; }
        .summary { margin-top:10px; border:1px solid #dbe7f0; border-radius:12px; background:#fbfdff; padding:10px; color:#3c5f74; }
        .actions { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
        .btn { text-decoration:none; border:1px solid #c5d8e6; background:#f7fbff; color:#244a65; border-radius:10px; padding:9px 12px; font-weight:700; }
        .btn.primary { border-color:#f6d19a; background:linear-gradient(135deg,#ffc76f 0%,var(--accent) 100%); color:#57350b; }
        @media (max-width: 760px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    @php
        $categoryKey = $categoryKey ?? 'accommodation';
        $categoryLabel = $categoryLabel ?? 'Category';
        $property = $property ?? null;
        $prefill = $prefill ?? [];
        $categoryFields = collect($categoryFields ?? []);
        $dateLabels = $dateLabels ?? ['start' => 'Service Start Date', 'end' => 'Service End Date'];
        $pricingConfig = $pricingConfig ?? ['tax_rate' => 16, 'discount_percent' => 0];
        $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
        $basePrice = (float) ($property->base_price ?? 0);
    @endphp

    <main class="page">
        <section class="hero">
            <h1>{{ $categoryLabel }} Booking Request</h1>
            <p>{{ (string) ($property->name ?? 'Listing') }} • {{ $currency }} {{ number_format($basePrice, 2) }} base price</p>
        </section>

        <section class="panel" aria-label="Category booking form">
            <form method="POST" action="/booking/reserve-category">
                @csrf
                <input type="hidden" name="category_key" value="{{ $categoryKey }}">
                <input type="hidden" name="property_id" value="{{ (int) ($property->id ?? 0) }}">

                @if ($errors->any())
                    <div class="form-errors" role="alert" aria-live="polite">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid">
                    <div class="field"><label for="serviceStartDate">{{ (string) ($dateLabels['start'] ?? 'Service Start Date') }}</label><input id="serviceStartDate" name="service_start_date" type="date" value="{{ old('service_start_date', (string) ($prefill['service_start_date'] ?? '')) }}" class="{{ $errors->has('service_start_date') ? 'input-error' : '' }}" required>@error('service_start_date')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="serviceEndDate">{{ (string) ($dateLabels['end'] ?? 'Service End Date') }}</label><input id="serviceEndDate" name="service_end_date" type="date" value="{{ old('service_end_date', (string) ($prefill['service_end_date'] ?? '')) }}" class="{{ $errors->has('service_end_date') ? 'input-error' : '' }}">@error('service_end_date')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ old('adults', (int) ($prefill['adults'] ?? 2)) }}" class="{{ $errors->has('adults') ? 'input-error' : '' }}" required>@error('adults')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ old('children', (int) ($prefill['children'] ?? 0)) }}" class="{{ $errors->has('children') ? 'input-error' : '' }}">@error('children')<p class="error-text">{{ $message }}</p>@enderror</div>

                    @foreach ($categoryFields as $field)
                        @php
                            $fieldKey = (string) ($field['key'] ?? '');
                            $fieldType = (string) ($field['type'] ?? 'text');
                            $fieldLabel = (string) ($field['label'] ?? Str::headline(str_replace('_', ' ', $fieldKey)));
                            $fieldRequired = (bool) ($field['required'] ?? false);
                            $fieldId = 'categoryField_' . $fieldKey;
                            $fieldValue = old($fieldKey, $prefill[$fieldKey] ?? '');
                        @endphp
                        <div class="field">
                            <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
                            @if ($fieldType === 'select')
                                <select id="{{ $fieldId }}" name="{{ $fieldKey }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                                    <option value="">Select {{ $fieldLabel }}</option>
                                    @foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel)
                                        <option value="{{ (string) $optValue }}" {{ (string) $fieldValue === (string) $optValue ? 'selected' : '' }}>{{ (string) $optLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif ($fieldType === 'number')
                                <input id="{{ $fieldId }}" name="{{ $fieldKey }}" type="number" min="{{ (int) ($field['min'] ?? 0) }}" value="{{ $fieldValue }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                            @else
                                <input id="{{ $fieldId }}" name="{{ $fieldKey }}" type="text" value="{{ $fieldValue }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                            @endif
                            @error($fieldKey)<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    @endforeach

                    <div class="field"><label for="primaryFirstName">Primary Guest First Name</label><input id="primaryFirstName" name="primary_first_name" type="text" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}" class="{{ $errors->has('primary_first_name') ? 'input-error' : '' }}" required>@error('primary_first_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="primaryLastName">Primary Guest Last Name</label><input id="primaryLastName" name="primary_last_name" type="text" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}" class="{{ $errors->has('primary_last_name') ? 'input-error' : '' }}" required>@error('primary_last_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="primaryNationality">Primary Guest Nationality</label><input id="primaryNationality" name="primary_nationality" type="text" value="{{ old('primary_nationality', (string) ($prefill['primary_nationality'] ?? '')) }}" class="{{ $errors->has('primary_nationality') ? 'input-error' : '' }}" required>@error('primary_nationality')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="primaryEmail">Primary Guest Email</label><input id="primaryEmail" name="primary_email" type="email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}" class="{{ $errors->has('primary_email') ? 'input-error' : '' }}" required>@error('primary_email')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="primaryMobile">Primary Guest Mobile</label><input id="primaryMobile" name="primary_mobile" type="text" placeholder="+960 ..." value="{{ old('primary_mobile', (string) ($prefill['primary_mobile'] ?? '')) }}" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required>@error('primary_mobile')<p class="error-text">{{ $message }}</p>@enderror</div>

                    <div class="field full"><label for="additionalGuestDetails">Additional Guest Details (Optional)</label><textarea id="additionalGuestDetails" name="additional_guest_details">{{ old('additional_guest_details', '') }}</textarea></div>
                    <div class="field full"><label for="serviceNotes">Service Notes (Optional)</label><textarea id="serviceNotes" name="service_notes">{{ old('service_notes', (string) ($prefill['service_notes'] ?? '')) }}</textarea></div>
                </div>

                <div class="summary">
                    Estimated pricing follows existing checkout procedure: base subtotal, promotion/discount, tax, and final total with mandatory primary guest identity capture.
                    Tax: {{ number_format((float) ($pricingConfig['tax_rate'] ?? 16), 2) }}% • Discount: {{ number_format((float) ($pricingConfig['discount_percent'] ?? 0), 2) }}%
                </div>

                <div class="actions">
                    <button class="btn primary" type="submit">Proceed to Checkout</button>
                    <a class="btn" href="/catalog/{{ $categoryKey }}">Back to {{ $categoryLabel }} Catalog</a>
                </div>
            </form>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
