<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? ($categoryLabel ?? 'Category')) }} | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root { --bg:#f3f8f5; --ink:#152738; --muted:#5f7488; --line:#d5e2ec; --surface:#ffffff; --brand:#0f6179; --accent:#f3a337; --property-header-offset:74px; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        body.is-header-hidden { --property-header-offset:0px; }
        .page { width:min(1180px,calc(100% - 24px)); margin:0 auto 28px; }

        .top-search-shell {
            position: sticky;
            top: var(--property-header-offset);
            z-index: 60;
            border: 1px solid #d4e5ef;
            border-radius: 0;
            background: #ffffff;
            padding: 10px;
            box-shadow: none;
            margin-bottom: 0;
            width: 100%;
        }

        .top-search-inner {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto;
        }

        .top-search-form {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(140px, 1fr)) auto;
            gap: 8px;
            align-items: center;
        }

        .top-search-field {
            border: 1px solid #c6d7e4;
            border-radius: 8px;
            padding: 8px 10px;
            background: #fbfdff;
            color: #17344a;
            display: grid;
            gap: 2px;
        }

        .top-search-field label {
            font-size: 0.68rem;
            color: #5f7488;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .top-search-field input {
            border: 0;
            background: transparent;
            font: inherit;
            font-size: 0.88rem;
            color: #17344a;
            padding: 0;
            outline: none;
        }

        .top-search-btn {
            border: 1px solid #0f6179;
            background: #0f6179;
            color: #ffffff;
            border-radius: 8px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 6px;
            margin-bottom: 10px;
            font-size: 0.78rem;
            color: #5f7488;
        }

        .breadcrumb a {
            color: #0f6179;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb a:hover { text-decoration: underline; }

        .breadcrumb span:last-child { color: #264d66; font-weight: 700; }

        .service-identity {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .service-title-group h1 {
            margin: 0;
            font-size: clamp(1.18rem, 2.4vw, 1.9rem);
            color: #174561;
        }

        .service-title-group p {
            margin: 6px 0 0;
            color: #49697f;
        }

        .hero-meta { margin-top:10px; display:flex; flex-wrap:wrap; gap:8px; }
        .hero-chip {
            border:1px solid rgba(216, 244, 248, 0.5);
            background:#eef8fc;
            color:#17526f;
            border-radius:999px;
            padding:7px 10px;
            font-size:0.78rem;
            font-weight:700;
        }

        .service-review {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cfe1ec;
            border-radius: 999px;
            background: #f8fcff;
            padding: 6px 10px;
            color: #2d5f7f;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .share-card {
            margin-top: 8px;
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .share-label {
            font-size: 0.78rem;
            color: #3f6278;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .share-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .share-links a,
        .share-links button {
            border: 1px solid #b8d9e2;
            background: #f8fdff;
            color: #0f6179;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font: inherit;
            font-size: 0.92rem;
            text-decoration: none;
            cursor: pointer;
            padding: 0;
        }

        .share-links a:hover,
        .share-links button:hover { background: #eef8fc; }

        .layout {
            margin-top: 12px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 12px;
            align-items: start;
        }

        .reservation-form {
            position: sticky;
            top: 12px;
            grid-column: 2;
            grid-row: 1;
        }

        .service-content {
            grid-column: 1;
            grid-row: 1;
        }

        @media (max-width: 1080px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .reservation-form {
                grid-column: 1;
            }

            .service-content {
                grid-column: 1;
            }
        }

        .block {
            border:1px solid var(--line);
            border-radius:14px;
            background:var(--surface);
            padding:12px;
        }

        .block-title {
            margin:0 0 8px;
            font-size:0.82rem;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:#3f6278;
            font-family:"Space Grotesk","Trebuchet MS",sans-serif;
        }

        .gallery-shell {
            margin-top: 10px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 260px;
            gap: 10px;
            align-items: start;
        }

        .gallery-banner-wrap {
            border-radius: 13px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
            min-height: 360px;
        }

        .gallery-main {
            width: 100%;
            height: 100%;
            min-height: 360px;
            object-fit: cover;
            display: block;
        }

        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            max-height: 360px;
            overflow: auto;
            padding-right: 2px;
        }

        .gallery-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
            padding: 0;
            margin: 0;
            cursor: pointer;
            min-height: 78px;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            min-height: 78px;
            object-fit: cover;
            display: block;
        }

        .gallery-thumb.is-active {
            border-color: #1d848c;
            box-shadow: 0 0 0 2px rgba(29, 132, 140, 0.25);
        }

        .gallery-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .gallery-grid img {
            width: 100%;
            height: 86px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #d9e7f0;
            background: #edf4fb;
        }

        .service-intel { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .intel-card {
            border:1px solid #dbe7f0;
            border-radius:12px;
            background:#fbfdff;
            padding:10px;
            display:grid;
            gap:4px;
        }
        .intel-card strong { font-size:0.83rem; color:#193f58; }
        .intel-card span { font-size:0.78rem; color:#4c687f; line-height:1.35; }

        .list { margin:0; padding:0; list-style:none; display:grid; gap:8px; }
        .list li {
            border:1px solid #d9e6ef;
            border-radius:10px;
            background:#f8fcff;
            padding:8px 10px;
            color:#284b64;
            font-size:0.83rem;
            display:flex;
            align-items:center;
            gap:8px;
            line-height:1.35;
        }

        .list li i { color:#0f6179; width:16px; text-align:center; }

        .policy-group { margin-top:10px; }
        .policy-group + .policy-group { margin-top:12px; }
        .policy-group-title {
            margin:0 0 6px;
            font-size:0.77rem;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.07em;
            color:#2d5877;
            display:flex;
            align-items:center;
            gap:6px;
            font-family:"Space Grotesk","Trebuchet MS",sans-serif;
        }

        .description {
            font-size:0.85rem;
            color:#34566d;
            line-height:1.5;
            margin:0;
        }

        .booking-card {
            position:sticky;
            top:12px;
            border:1px solid var(--line);
            border-radius:14px;
            background:var(--surface);
            padding:12px;
        }

        .booking-price {
            border:1px solid #d8e7f1;
            border-radius:11px;
            background:#f8fcff;
            padding:9px 10px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:10px;
        }

        .booking-price strong { font-size:0.98rem; color:#1a4360; }
        .booking-price span { font-size:0.76rem; color:#587188; }

        .booking-subtitle {
            margin: -2px 0 10px;
            font-size: 0.88rem;
            color: #365a71;
            font-weight: 600;
        }

        .booking-lines {
            margin-top: 6px;
            border: 1px solid #d6e6ef;
            border-radius: 12px;
            background: #fbfdff;
            overflow: hidden;
        }

        .booking-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 110px 88px;
            align-items: center;
            gap: 8px;
            padding: 9px 10px;
            border-bottom: 1px solid #e2edf4;
        }

        .booking-line:last-child { border-bottom: 0; }

        .booking-line-label {
            font-size: 0.84rem;
            color: #1f4760;
            font-weight: 700;
        }

        .booking-line-price {
            text-align: right;
            font-size: 0.8rem;
            color: #3f6278;
            font-weight: 700;
        }

        .booking-line select {
            width: 100%;
            border: 1px solid #b8d9e2;
            border-radius: 8px;
            padding: 7px 9px;
            font: inherit;
            background: #f8fdff;
        }

        .booking-total {
            margin-top: 10px;
            border: 1px solid #c6dde8;
            border-radius: 12px;
            background: #edf7f4;
            padding: 10px 11px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .booking-total span {
            font-size: 0.76rem;
            color: #3b5f75;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-weight: 700;
        }

        .booking-total strong {
            font-size: 1rem;
            color: #1a4360;
        }

        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .field { display:grid; gap:5px; }
        .field label { font-size:0.74rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        .field input, .field textarea, .field select { width:100%; border:1px solid #b8d9e2; border-radius:10px; font:inherit; background:#f8fdff; }
        .field input,
        .field select { min-height:42px; height:42px; padding:0 11px; line-height:1.2; }
        .field textarea { min-height:88px; resize:vertical; padding:10px 11px; }
        .field.full { grid-column:1/-1; }
        .field .input-error { border-color:#c54f4f; background:#fff8f8; }
        .field-error-state input,
        .field-error-state select,
        .field-error-state textarea { border-color:#c54f4f !important; background:#fff4f4 !important; }
        .field .error-text { margin:0; font-size:0.74rem; color:#a32929; }
        .required-note { margin:0; color:#8f2323; font-size:0.74rem; font-weight:600; }

        .transfer-list { display:grid; gap:8px; }
        .transfer-option {
            display:grid;
            grid-template-columns:auto 1fr;
            gap:9px;
            align-items:start;
            border:1px solid #c5daea;
            border-radius:10px;
            background:#f8fcff;
            padding:10px;
        }
        .transfer-option input { margin-top:2px; }
        .transfer-option-title { font-size:0.84rem; font-weight:700; color:#1b3f58; }
        .transfer-option-rates { font-size:0.76rem; color:#486b80; margin-top:2px; }
        .transfer-option-note { font-size:0.72rem; color:#5a778c; margin-top:2px; }

        .payment-choice-list { display:grid; gap:8px; }
        .payment-choice {
            border:1px solid #c8dceb;
            border-radius:10px;
            background:#f8fcff;
            padding:9px 10px;
            display:grid;
            grid-template-columns:auto 1fr;
            gap:8px;
            align-items:center;
        }
        .payment-choice.hidden { display:none; }
        .payment-choice-main { font-size:0.82rem; color:#1f475f; font-weight:600; }
        .payment-choice-note { font-size:0.74rem; color:#527288; }
        .payment-hint { margin:0; font-size:0.76rem; color:#486a80; }

        .form-errors { margin:0 0 10px; border:1px solid #e6b2b2; background:#fff5f5; color:#8f2323; border-radius:10px; padding:10px 12px; }
        .form-errors ul { margin:0; padding-left:18px; }

        .summary {
            margin-top:10px;
            border:1px solid #dbe7f0;
            border-radius:12px;
            background:#fbfdff;
            padding:10px;
            color:#3c5f74;
            font-size:0.82rem;
            line-height:1.45;
        }

        .actions { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }

        @media (max-width: 980px) {
            .top-search-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .top-search-btn { grid-column: span 2; }
            .layout { grid-template-columns:1fr; }
            .reservation-form,
            .service-content { grid-column: auto; grid-row: auto; }
            .booking-card { position:static; }
            .service-intel { grid-template-columns:1fr; }
            .gallery-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
        }

        @media (max-width: 760px) {
            .top-search-form { grid-template-columns: 1fr; }
            .top-search-btn { grid-column: auto; }
            .grid, .service-intel { grid-template-columns:1fr; }
            .gallery-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $headerCategoryKey = str_replace('_', '-', strtolower(trim((string) ($categoryKey ?? 'accommodation'))));
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
        'headerShowSearch' => true,
        'headerSearchAction' => '/catalog/' . $headerCategoryKey,
        'headerSearchValue' => '',
        'headerCategoryLinks' => $headerCategoryLinks,
        'headerActiveCategoryKey' => $headerCategoryKey,
        'headerContinueUrl' => (string) request()->fullUrl(),
    ])

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
        $adultUnitPrice = (float) ($pricingConfig['adult_price'] ?? $basePrice);
        $childUnitPrice = (float) ($pricingConfig['child_price'] ?? max(0, round($adultUnitPrice * 0.5, 2)));
        $infantUnitPrice = (float) ($pricingConfig['infant_price'] ?? 0);
        $qtyOptions = range(0, 20);
        $adultSelected = max(1, (int) old('adults', (int) ($prefill['adults'] ?? 2)));
        $childSelected = max(0, (int) old('children', (int) ($prefill['children'] ?? 0)));
        $infantSelected = max(0, (int) old('infants', (int) ($prefill['infants'] ?? 0)));
        $initialExcursionTotal = ($adultUnitPrice * $adultSelected) + ($childUnitPrice * $childSelected) + ($infantUnitPrice * $infantSelected);

        $propertyMedia = collect($propertyMedia ?? collect());
        $highlights = collect($highlights ?? []);
        $servicesAndAmenities = collect($servicesAndAmenities ?? []);
        $descriptionText = trim((string) ($descriptionText ?? ''));
        $vendorPolicy = $vendorPolicy ?? [];
        $transferOptions = collect($transferOptions ?? [])->filter(static fn ($option) => is_array($option))->values();
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

        $mediaUrl = static function ($media, string $variant = 'banner'): ?string {
            $mediaId = (int) ($media->id ?? 0);
            if ($mediaId <= 0) {
                return null;
            }

            $normalizedVariant = strtolower(trim($variant));
            if (!in_array($normalizedVariant, ['banner', 'thumb'], true)) {
                $normalizedVariant = 'banner';
            }

            return '/media/vendor/' . $mediaId . '/' . $normalizedVariant;
        };

        $heroImage = null;
        if ($propertyMedia->isNotEmpty()) {
            $heroImage = $mediaUrl($propertyMedia->first(), 'banner');
        }

        $fallbackImage = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2232%22%3EService%20image%3C%2Ftext%3E%3C%2Fsvg%3E';

        $locationParts = array_values(array_filter([
            trim((string) ($property->city ?? '')),
            trim((string) ($property->atoll ?? '')),
            trim((string) ($property->location_country ?? 'Maldives')),
        ], static fn ($item): bool => $item !== ''));
        $locationLine = $locationParts !== [] ? implode(', ', $locationParts) : 'Maldives';

        $descriptionSummary = \Illuminate\Support\Str::limit(trim((string) (($listingDetails['short_description'] ?? null) ?: ($property->short_description ?? $property->description ?? ''))), 180);
        $activityTypeLabel = trim((string) (($listingDetails['excursion_type'] ?? null) ?: ($property->excursion_type ?? '')));

        $formatTimeLabel = static function ($rawValue): string {
            $value = trim((string) $rawValue);
            if ($value === '') {
                return '';
            }

            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('H:i', $timestamp);
            }

            return $value;
        };

        $startTimeLabel = $formatTimeLabel(($listingDetails['activity_start_time'] ?? null) ?: ($property->activity_start_time ?? $property->start_time ?? $property->departure_time ?? ''));
        $endTimeLabel = $formatTimeLabel(($listingDetails['activity_end_time'] ?? null) ?: ($property->activity_end_time ?? $property->end_time ?? ''));
        $reviewScoreRaw = (float) ($property->rating ?? $property->average_rating ?? 0);
        $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
        $reviewCount = (int) ($property->reviews_count ?? 0);

        $serviceTypeHint = match ($categoryKey) {
            'restaurant' => 'Dining Service',
            'marine-transport' => 'Marine Transfer Service',
            'land-transport' => 'Land Transfer Service',
            'excursion' => 'Experience Service',
            'vehicle_rental' => 'Mobility Service',
            'remote_workspace' => 'Work Service',
            'conference_room' => 'Meeting Space Service',
            'resort_day_visit' => 'Day Access Service',
            default => 'Service',
        };

        $shareUrl = url()->current();
        $shareText = trim((string) ($property->name ?? ($categoryLabel . ' Listing'))) . ' on Workation';
        $shareEncodedText = urlencode($shareText . ' ' . $shareUrl);
        $shareEncodedUrl = urlencode($shareUrl);

        $policyItems = match ($categoryKey) {
            'restaurant' => ['Arrival grace period applies.', 'Inform dietary requirements in notes.', 'Group reservations may need pre-confirmation.'],
            'marine-transport' => ['Provide accurate pickup island and time.', 'Weather delays may occur; reschedule available.', 'Passenger count must match manifest.'],
            'land-transport' => ['Provide accurate pickup location.', 'Vehicle capacity and luggage limits apply.', 'Schedule changes depend on local conditions.'],
            'excursion' => ['Operator timing may vary by conditions.', 'Safety briefing required before departure.', 'Bring valid ID for check-in where requested.'],
            'vehicle_rental' => ['Driving license required at pickup.', 'Fuel and return policy confirmed at handover.', 'Late return can incur additional charge.'],
            'conference_room' => ['Room capacity must match your group size.', 'Event date and time locked at confirmation.', 'Setup and teardown times are included.'],
            default => ['Service terms are shared in checkout.', 'Requests are confirmed based on availability.', 'Support team can assist for custom notes.'],
        };
    @endphp

    <section class="top-search-shell" aria-label="Search service options">
        <div class="top-search-inner">
            <form method="GET" action="" class="top-search-form" id="categoryTopSearch">
                <div class="top-search-field">
                    <label for="topCategoryProperty">Location</label>
                    <input id="topCategoryProperty" type="text" name="property_name" value="{{ (string) ($property->name ?? '') }}" readonly>
                </div>
                <div class="top-search-field">
                    <label for="topCategoryCheckin">Start</label>
                    <input id="topCategoryCheckin" type="date" name="checkin" min="{{ now()->toDateString() }}" value="{{ (string) ($prefill['checkin'] ?? '') }}">
                </div>
                <div class="top-search-field">
                    <label for="topCategoryCheckout">End</label>
                    <input id="topCategoryCheckout" type="date" name="checkout" min="{{ now()->toDateString() }}" value="{{ (string) ($prefill['checkout'] ?? '') }}">
                </div>
                <div class="top-search-field">
                    <label for="topCategoryGuests">Guests</label>
                    <input id="topCategoryGuests" type="text" value="{{ (int) ($prefill['adults'] ?? 2) }} adults, {{ (int) ($prefill['children'] ?? 0) }} children" readonly>
                    <input type="hidden" name="adults" value="{{ (int) ($prefill['adults'] ?? 2) }}">
                    <input type="hidden" name="children" value="{{ (int) ($prefill['children'] ?? 0) }}">
                    <input type="hidden" name="infants" value="{{ (int) ($prefill['infants'] ?? 0) }}">
                </div>
                <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
            </form>
        </div>
    </section>

    <main class="page">

        @php
            $breadcrumbCategoryUrl = '/catalog/' . str_replace('_', '-', $categoryKey);
        @endphp
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span aria-hidden="true">›</span>
            <a href="{{ $breadcrumbCategoryUrl }}">{{ $categoryLabel }}</a>
            <span aria-hidden="true">›</span>
            <span>{{ (string) ($property->name ?? $categoryLabel . ' Listing') }}</span>
        </nav>
        <div class="layout">
            <section class="service-content">
                <section class="block" aria-label="Service gallery">
                    <h2 class="block-title">Service Gallery</h2>
                    <div class="gallery-shell">
                        <div class="gallery-banner-wrap">
                            <img class="gallery-main" src="{{ $heroImage ?: $fallbackImage }}" alt="Service image" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $fallbackImage }}';}">
                        </div>
                        <div class="gallery-thumbs">
                            @forelse ($propertyMedia->take(8) as $media)
                                @php
                                    $thumb = $mediaUrl($media, 'thumb') ?? $mediaUrl($media, 'banner') ?? $fallbackImage;
                                @endphp
                                <button class="gallery-thumb" aria-label="View service media">
                                    <img src="{{ $thumb }}" alt="Service media" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $fallbackImage }}';}">
                                </button>
                            @empty
                                <button class="gallery-thumb" aria-label="Service media placeholder">
                                    <img src="{{ $fallbackImage }}" alt="Service media placeholder">
                                </button>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="block" aria-label="Service overview" style="margin-top:12px;">
                    <div class="service-identity">
                        <div class="service-title-group">
                            <h1>{{ (string) ($property->name ?? ($categoryLabel . ' Listing')) }}</h1>
                            <p>{{ $locationLine }}</p>
                            @if ($descriptionSummary !== '')
                                <p>{{ $descriptionSummary }}</p>
                            @endif
                        </div>
                        <span class="hero-chip">{{ $serviceTypeHint }}</span>
                    </div>
                    <div class="hero-meta">
                        <span class="hero-chip"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ $categoryLabel }}</span>
                        @if ($activityTypeLabel !== '')
                            <span class="hero-chip"><i class="fa-solid fa-compass" aria-hidden="true"></i> {{ str_replace('_', ' ', $activityTypeLabel) }}</span>
                        @endif
                        <span class="hero-chip"><i class="fa-solid fa-coins" aria-hidden="true"></i> From {{ $currency }} {{ number_format($basePrice, 2) }}</span>
                        <span class="service-review"><i class="fa-solid fa-star" aria-hidden="true"></i> {{ $reviewScore }} ({{ number_format($reviewCount) }})</span>
                    </div>
                    <section class="share-card" aria-label="Share this listing">
                        <span class="share-label">Share this {{ strtolower($categoryLabel) }}</span>
                        <div class="share-links">
                            <a href="https://wa.me/?text={{ $shareEncodedText }}" target="_blank" rel="noopener" title="Share on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareEncodedUrl }}" target="_blank" rel="noopener" title="Share on Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareEncodedUrl }}" target="_blank" rel="noopener" title="Share on LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
                            <button type="button" data-copy-share-link="{{ $shareUrl }}" title="Copy link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
                        </div>
                    </section>
                </section>

                <section class="block" aria-label="Service details" style="margin-top:12px;">
                    <h2 class="block-title">{{ $categoryKey === 'excursion' ? 'Descriptions & Details' : 'Service Snapshot' }}</h2>
                    <div class="service-intel">
                        <article class="intel-card">
                            <strong>{{ $categoryKey === 'excursion' ? 'Activity' : 'Best For' }}</strong>
                            <span>
                                @if ($categoryKey === 'restaurant')
                                    Family dining, couples, and group reservations.
                                @elseif ($categoryKey === 'marine-transport')
                                    Island-to-island water transfers and dhoni journeys.
                                @elseif ($categoryKey === 'land-transport')
                                    Local ground moves, airport runs, and sightseeing.
                                @elseif ($categoryKey === 'excursion')
                                    {{ (string) ($property->name ?? 'Guided local activity and experience') }}
                                @elseif ($categoryKey === 'conference_room')
                                    Meetings, training sessions, seminars, and corporate events.
                                @else
                                    Travelers looking for reliable {{ strtolower($categoryLabel) }} services.
                                @endif
                            </span>
                        </article>
                        <article class="intel-card">
                            <strong>{{ $categoryKey === 'excursion' ? 'Booking Inputs' : 'Booking Readiness' }}</strong>
                            <span>
                                @if ($categoryKey === 'excursion')
                                    Select activity date, adult/children/infant counts, and add any special request. Full activity details are carried to checkout summary.
                                @else
                                    Core service requirements are captured before checkout so vendor confirmation is accurate.
                                @endif
                            </span>
                        </article>
                        @if ($startTimeLabel !== '' || $endTimeLabel !== '')
                        <article class="intel-card">
                            <strong>Time Window</strong>
                            <span>
                                @if ($startTimeLabel !== '' && $endTimeLabel !== '')
                                    {{ $startTimeLabel }} - {{ $endTimeLabel }}
                                @elseif ($startTimeLabel !== '')
                                    Starts {{ $startTimeLabel }}
                                @else
                                    Ends {{ $endTimeLabel }}
                                @endif
                            </span>
                        </article>
                        @endif
                    </div>
                </section>

                <section class="block" aria-label="Highlights and amenities" style="margin-top:12px;">
                    <h2 class="block-title">Highlights</h2>
                    <ul class="list">
                        @foreach ($highlights->take(8) as $item)
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ (string) $item }}</span></li>
                        @endforeach
                    </ul>

                    <h2 class="block-title" style="margin-top:12px;">Services and Amenities</h2>
                    <ul class="list">
                        @forelse ($servicesAndAmenities->take(12) as $item)
                            <li><i class="fa-solid fa-star" aria-hidden="true"></i><span>{{ (string) $item }}</span></li>
                        @empty
                            <li><i class="fa-solid fa-info-circle" aria-hidden="true"></i><span>Service amenities will be refined as listing details are updated.</span></li>
                        @endforelse
                    </ul>
                </section>

                <section class="block" aria-label="Service description and policies" style="margin-top:12px;">
                    <h2 class="block-title">Service Description</h2>
                    <p class="description">{!! nl2br(e($descriptionText !== '' ? $descriptionText : 'Listing description will be updated soon.')) !!}</p>

                    <h2 class="block-title" style="margin-top:12px;">Policy Snapshot</h2>
                    @php
                        $hasVendorPolicy = ($vendorPolicy['opening_hours'] ?? '') !== ''
                            || ($vendorPolicy['closing_hours'] ?? '') !== ''
                            || ($vendorPolicy['cancellation_policy'] ?? '') !== ''
                            || !empty($vendorPolicy['other_rules'] ?? []);
                    @endphp

                    @if ($hasVendorPolicy)
                        @if (($vendorPolicy['opening_hours'] ?? '') !== '' || ($vendorPolicy['closing_hours'] ?? '') !== '')
                            <div class="policy-group">
                                <p class="policy-group-title"><i class="fa-regular fa-clock" aria-hidden="true"></i> Operating Hours</p>
                                <ul class="list">
                                    @if (($vendorPolicy['opening_hours'] ?? '') !== '')
                                        <li><i class="fa-solid fa-door-open" aria-hidden="true"></i><span>Opens: {{ $vendorPolicy['opening_hours'] }}</span></li>
                                    @endif
                                    @if (($vendorPolicy['closing_hours'] ?? '') !== '')
                                        <li><i class="fa-solid fa-door-closed" aria-hidden="true"></i><span>Closes: {{ $vendorPolicy['closing_hours'] }}</span></li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        @if (($vendorPolicy['cancellation_policy'] ?? '') !== '')
                            <div class="policy-group">
                                <p class="policy-group-title"><i class="fa-solid fa-ban" aria-hidden="true"></i> Cancellation Policy</p>
                                <ul class="list">
                                    <li><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ $vendorPolicy['cancellation_policy'] }}</span></li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($vendorPolicy['other_rules'] ?? []))
                            <div class="policy-group">
                                <p class="policy-group-title"><i class="fa-solid fa-scroll" aria-hidden="true"></i> House Rules</p>
                                <ul class="list">
                                    @foreach ($vendorPolicy['other_rules'] as $rule)
                                        <li><i class="fa-solid fa-shield" aria-hidden="true"></i><span>{{ (string) $rule }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <ul class="list">
                            @foreach ($policyItems as $policy)
                                <li><i class="fa-solid fa-shield" aria-hidden="true"></i><span>{{ $policy }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="block" aria-label="Similar services" style="margin-top:12px;">
                    <h2 class="block-title">Similar {{ strtolower($categoryLabel) }} Nearby</h2>
                    <p class="description" style="margin-top:0;">Explore similar services from the same area and compare availability, inclusions, and pricing before checkout.</p>
                    <a class="btn" href="/catalog/{{ str_replace('_', '-', $categoryKey) }}">Browse {{ $categoryLabel }} listings</a>
                </section>
            </section>

            <aside class="booking-card reservation-form" aria-label="Category booking form">
                <h2 class="block-title">{{ $categoryKey === 'excursion' ? 'Book Now' : 'Booking Request' }}</h2>
                @if ($categoryKey === 'excursion')
                    <p class="booking-subtitle">{{ (string) ($property->name ?? 'Excursion Activity') }}</p>
                @endif
                <div class="booking-price">
                    <span>Starting price</span>
                    <strong>{{ $currency }} {{ number_format($basePrice, 2) }}</strong>
                </div>

                <form method="POST" action="/booking/reserve-category" id="categoryServiceBookingForm">
                    @csrf
                    <input type="hidden" name="category_key" value="{{ $categoryKey }}">
                    <input type="hidden" name="property_id" value="{{ (int) ($property->id ?? 0) }}">
                    <input type="hidden" name="guest_residency" id="guestResidencyInput" value="{{ old('guest_residency', (string) ($prefill['guest_residency'] ?? '')) }}">
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

                    <div class="grid">
                        @if ($categoryKey === 'excursion')
                            <div class="field full"><label for="serviceStartDate">Activity Date</label><input id="serviceStartDate" name="service_start_date" type="date" min="{{ (string) ($todayDate ?? now()->toDateString()) }}" value="{{ old('service_start_date', (string) ($prefill['service_start_date'] ?? '')) }}" class="{{ $errors->has('service_start_date') ? 'input-error' : '' }}" required>@error('service_start_date')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field full">
                                <label>Lead Guest</label>
                                <p class="booking-subtitle" style="margin:0;">Fill guest details below exactly as government-issued documents.</p>
                            </div>
                            <div class="field full">
                                <label>Guests and Unit Price</label>
                                <div class="booking-lines">
                                    <div class="booking-line">
                                        <span class="booking-line-label">Adult</span>
                                        <span class="booking-line-price">{{ $currency }} {{ number_format($adultUnitPrice, 2) }}</span>
                                        <select id="adults" name="adults" class="{{ $errors->has('adults') ? 'input-error' : '' }}" data-excursion-qty data-unit-price="{{ $adultUnitPrice }}" required>
                                            @foreach (range(1, 20) as $qty)
                                                <option value="{{ $qty }}" {{ $adultSelected === $qty ? 'selected' : '' }}>{{ $qty }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="booking-line">
                                        <span class="booking-line-label">Child</span>
                                        <span class="booking-line-price">{{ $currency }} {{ number_format($childUnitPrice, 2) }}</span>
                                        <select id="children" name="children" class="{{ $errors->has('children') ? 'input-error' : '' }}" data-excursion-qty data-unit-price="{{ $childUnitPrice }}">
                                            @foreach ($qtyOptions as $qty)
                                                <option value="{{ $qty }}" {{ $childSelected === $qty ? 'selected' : '' }}>{{ $qty }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="booking-line">
                                        <span class="booking-line-label">Infant</span>
                                        <span class="booking-line-price">{{ $currency }} {{ number_format($infantUnitPrice, 2) }}</span>
                                        <select id="infants" name="infants" class="{{ $errors->has('infants') ? 'input-error' : '' }}" data-excursion-qty data-unit-price="{{ $infantUnitPrice }}">
                                            @foreach ($qtyOptions as $qty)
                                                <option value="{{ $qty }}" {{ $infantSelected === $qty ? 'selected' : '' }}>{{ $qty }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @error('adults')<p class="error-text">{{ $message }}</p>@enderror
                                @error('children')<p class="error-text">{{ $message }}</p>@enderror
                                @error('infants')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                            <div class="booking-total field full" aria-live="polite">
                                <span>Price Total</span>
                                <strong id="excursionTotalDisplay">{{ $currency }} {{ number_format($initialExcursionTotal, 2) }}</strong>
                            </div>
                            <div class="field full">
                                <label for="serviceNotes">Additional Request (Optional)</label>
                                <textarea id="serviceNotes" name="service_notes" placeholder="Any dietary, timing, or service request?">{{ old('service_notes', (string) ($prefill['service_notes'] ?? '')) }}</textarea>
                            </div>
                        @else
                            <div class="field"><label for="serviceStartDate">{{ (string) ($dateLabels['start'] ?? 'Service Start Date') }}</label><input id="serviceStartDate" name="service_start_date" type="{{ in_array($categoryKey, ['restaurant', 'conference_room']) ? 'datetime-local' : 'date' }}" min="{{ in_array($categoryKey, ['restaurant', 'conference_room']) ? ((string) ($todayDate ?? now()->toDateString()) . 'T00:00') : (string) ($todayDate ?? now()->toDateString()) }}" value="{{ old('service_start_date', (string) ($prefill['service_start_date'] ?? '')) }}" class="{{ $errors->has('service_start_date') ? 'input-error' : '' }}" required>@error('service_start_date')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field"><label for="serviceEndDate">{{ (string) ($dateLabels['end'] ?? 'Service End Date') }}</label><input id="serviceEndDate" name="service_end_date" type="{{ in_array($categoryKey, ['restaurant', 'conference_room']) ? 'datetime-local' : 'date' }}" min="{{ in_array($categoryKey, ['restaurant', 'conference_room']) ? ((string) ($todayDate ?? now()->toDateString()) . 'T00:00') : (string) ($todayDate ?? now()->toDateString()) }}" value="{{ old('service_end_date', (string) ($prefill['service_end_date'] ?? '')) }}" class="{{ $errors->has('service_end_date') ? 'input-error' : '' }}">@error('service_end_date')<p class="error-text">{{ $message }}</p>@enderror</div>
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
                                @if ($fieldType === 'checkbox')
                                    <div class="field full" style="margin-top:8px;">
                                        <label style="margin-bottom:8px; display:block; font-size:0.74rem; text-transform:uppercase; letter-spacing:0.07em; color:#3c5f76; font-family:'Space Grotesk','Trebuchet MS',sans-serif;">{{ $fieldLabel }}</label>
                                        <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px;">
                                            @foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel)
                                                @php
                                                    $selectedFacilities = old($fieldKey, $prefill[$fieldKey] ?? []);
                                                    $isSelected = in_array((string) $optValue, (array) $selectedFacilities);
                                                @endphp
                                                <label style="display:flex; align-items:center; gap:7px; cursor:pointer; font-size:0.83rem; color:#34566d;">
                                                    <input type="checkbox" name="{{ $fieldKey }}[]" value="{{ (string) $optValue }}" {{ $isSelected ? 'checked' : '' }} style="cursor:pointer; width:16px; height:16px; accent-color:#0f6179;">
                                                    <span>{{ (string) $optLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($fieldType === 'select')
                                    <div class="field">
                                        <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
                                        <select id="{{ $fieldId }}" name="{{ $fieldKey }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                                            <option value="">Select {{ $fieldLabel }}</option>
                                            @foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel)
                                                <option value="{{ (string) $optValue }}" {{ (string) $fieldValue === (string) $optValue ? 'selected' : '' }}>{{ (string) $optLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error($fieldKey)<p class="error-text">{{ $message }}</p>@enderror
                                @elseif ($fieldType === 'number')
                                    <div class="field">
                                        <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
                                        <input id="{{ $fieldId }}" name="{{ $fieldKey }}" type="number" min="{{ (int) ($field['min'] ?? 0) }}" value="{{ $fieldValue }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                                        @error($fieldKey)<p class="error-text">{{ $message }}</p>@enderror
                                    </div>
                                @else
                                    <div class="field">
                                        <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
                                        <input id="{{ $fieldId }}" name="{{ $fieldKey }}" type="text" value="{{ $fieldValue }}" class="{{ $errors->has($fieldKey) ? 'input-error' : '' }}" {{ $fieldRequired ? 'required' : '' }}>
                                        @error($fieldKey)<p class="error-text">{{ $message }}</p>@enderror
                                    </div>
                                @endif
                            @endforeach

                            <div class="field full"><label for="additionalGuestDetails">Additional Guest Details (Optional)</label><textarea id="additionalGuestDetails" name="additional_guest_details">{{ old('additional_guest_details', '') }}</textarea></div>
                            <div class="field full">
                                <label for="serviceNotes">{{ $categoryKey === 'restaurant' ? 'Special Note for Food Order (Optional)' : 'Service Notes (Optional)' }}</label>
                                <textarea id="serviceNotes" name="service_notes" placeholder="{{ $categoryKey === 'restaurant' ? 'Tell us what you want to order or any dietary preferences.' : 'Add any service details or requests.' }}">{{ old('service_notes', (string) ($prefill['service_notes'] ?? '')) }}</textarea>
                            </div>
                        @endif

                        @if ($categoryKey !== 'excursion')
                            <div class="field full">
                                <label>Guest Details*</label>
                                <p class="required-note">Given names and surname must match government-issued documents. For foreigners, use passport details. For locals, use ID card details.</p>
                            </div>
                            <div class="field"><label for="primaryFirstName">Given names*</label><input id="primaryFirstName" name="primary_first_name" type="text" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}" class="{{ $errors->has('primary_first_name') ? 'input-error' : '' }}" required>@error('primary_first_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field"><label for="primaryLastName">Surname*</label><input id="primaryLastName" name="primary_last_name" type="text" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}" class="{{ $errors->has('primary_last_name') ? 'input-error' : '' }}" required>@error('primary_last_name')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field"><label for="primaryNationality">Country / Nationality*</label><select id="primaryNationality" name="primary_nationality" class="{{ $errors->has('primary_nationality') ? 'input-error' : '' }}" required><option value="">Select country</option>@foreach ($countryOptions as $country)<option value="{{ $country['name'] }}" data-iso="{{ $country['iso'] }}" data-dial="{{ $country['dial'] }}" {{ strcasecmp($oldNationality, $country['name']) === 0 ? 'selected' : '' }}>{{ $country['name'] }}</option>@endforeach</select>@error('primary_nationality')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field"><label for="primaryEmail">Email*</label><input id="primaryEmail" name="primary_email" type="email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}" class="{{ $errors->has('primary_email') ? 'input-error' : '' }}" required>@error('primary_email')<p class="error-text">{{ $message }}</p>@enderror</div>
                            <div class="field"><label for="primaryMobileCountryCode">Phone country code*</label><select id="primaryMobileCountryCode" name="primary_mobile_country_code" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required>@foreach ($countryOptions as $country)<option value="{{ $country['dial'] }}" data-iso="{{ $country['iso'] }}" {{ $oldPhoneCode === $country['dial'] ? 'selected' : '' }}>{{ $country['dial'] }} ({{ $country['name'] }})</option>@endforeach</select></div>
                            <div class="field"><label for="primaryMobileLocal">Contact number*</label><input id="primaryMobileLocal" name="primary_mobile_local" type="tel" value="{{ $oldPhoneLocal }}" class="{{ $errors->has('primary_mobile') ? 'input-error' : '' }}" required inputmode="tel">@error('primary_mobile')<p class="error-text">{{ $message }}</p>@enderror</div>
                        @else
                            <input type="hidden" name="primary_first_name" value="{{ old('primary_first_name', (string) ($prefill['primary_first_name'] ?? '')) }}">
                            <input type="hidden" name="primary_last_name" value="{{ old('primary_last_name', (string) ($prefill['primary_last_name'] ?? '')) }}">
                            <input type="hidden" name="primary_nationality" value="{{ old('primary_nationality', (string) ($prefill['primary_nationality'] ?? '')) }}">
                            <input type="hidden" name="primary_email" value="{{ old('primary_email', (string) ($prefill['primary_email'] ?? '')) }}">
                            <input type="hidden" name="primary_mobile_country_code" value="{{ $oldPhoneCode }}">
                            <input type="hidden" name="primary_mobile_local" value="{{ $oldPhoneLocal }}">
                            <input type="hidden" name="additional_guest_details" value="{{ old('additional_guest_details', '') }}">
                            <input type="hidden" name="payment_timing" value="{{ old('payment_timing', 'pay_now') }}">
                            <input type="hidden" name="payment_method" value="{{ old('payment_method', 'card') }}">
                        @endif

                        <div class="field full">
                            <label>Transfer option</label>
                            @if ($transferOptions->isNotEmpty())
                                <div class="transfer-list" id="transferOptionsList">
                                    @foreach ($transferOptions as $index => $option)
                                        @php
                                            $transferCode = strtolower(trim((string) ($option['code'] ?? '')));
                                            $optionSelected = old('transfer_option', (string) ($prefill['transfer_option'] ?? ''));
                                            $localAdultRate = (float) ($option['local_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                            $localChildRate = (float) ($option['local_child_charge'] ?? $option['child_charge'] ?? 0);
                                            $foreignAdultRate = (float) ($option['foreign_adult_charge'] ?? $option['adult_charge'] ?? 0);
                                            $foreignChildRate = (float) ($option['foreign_child_charge'] ?? $option['child_charge'] ?? 0);
                                        @endphp
                                        <label class="transfer-option">
                                            <input
                                                type="radio"
                                                name="transfer_option"
                                                value="{{ $transferCode }}"
                                                data-local-adult="{{ $localAdultRate }}"
                                                data-local-child="{{ $localChildRate }}"
                                                data-foreign-adult="{{ $foreignAdultRate }}"
                                                data-foreign-child="{{ $foreignChildRate }}"
                                                data-base-local="{{ (float) ($option['base_charge_local'] ?? 0) }}"
                                                data-base-foreign="{{ (float) ($option['base_charge_foreign'] ?? 0) }}"
                                                {{ ($optionSelected === '' && $index === 0) || strtolower((string) $optionSelected) === $transferCode ? 'checked' : '' }}
                                            >
                                            <span>
                                                <span class="transfer-option-title">{{ (string) ($option['label'] ?? Str::headline(str_replace('_', ' ', $transferCode))) }}</span>
                                                <span class="transfer-option-rates">Local: Adult {{ $currency }} {{ number_format($localAdultRate, 2) }}, Child {{ $currency }} {{ number_format($localChildRate, 2) }} • Foreigner: Adult {{ $currency }} {{ number_format($foreignAdultRate, 2) }}, Child {{ $currency }} {{ number_format($foreignChildRate, 2) }}</span>
                                                <span class="transfer-option-note">Tick to include this transfer mode in billing.</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <p class="booking-subtitle" style="margin:0;">No transfer options configured for this listing.</p>
                                <input type="hidden" name="transfer_option" value="">
                            @endif
                        </div>

                        <div class="field full">
                            <label for="transferCharge">Transfer charge</label>
                            <input id="transferCharge" name="transfer_charge" type="number" step="0.01" min="0" value="{{ old('transfer_charge', '0') }}" readonly>
                        </div>

                        @if ($categoryKey !== 'excursion')
                            <div class="field full">
                                <label>Payment preferences</label>
                                <div class="payment-choice-list">
                                    <label class="payment-choice"><input type="radio" name="payment_timing" value="pay_now" {{ old('payment_timing', 'pay_now') === 'pay_now' ? 'checked' : '' }}><span><span class="payment-choice-main">Pay now</span><span class="payment-choice-note">Secure payment before confirmation.</span></span></label>
                                    <label class="payment-choice"><input type="radio" name="payment_timing" value="pay_at_property" {{ old('payment_timing') === 'pay_at_property' ? 'checked' : '' }}><span><span class="payment-choice-main">Pay at property</span><span class="payment-choice-note">Shown for eligible local bookings.</span></span></label>
                                </div>
                                <div class="payment-choice-list" id="paymentMethodList" style="margin-top:8px;">
                                    <label class="payment-choice payment-method-option" data-scope="all"><input type="radio" name="payment_method" value="card" {{ old('payment_method', 'card') === 'card' ? 'checked' : '' }}><span><span class="payment-choice-main">Card</span><span class="payment-choice-note">Credit / debit cards.</span></span></label>
                                    <label class="payment-choice payment-method-option" data-scope="international"><input type="radio" name="payment_method" value="apple_pay" {{ old('payment_method') === 'apple_pay' ? 'checked' : '' }}><span><span class="payment-choice-main">Apple Pay</span><span class="payment-choice-note">International guests where available.</span></span></label>
                                    <label class="payment-choice payment-method-option" data-scope="international"><input type="radio" name="payment_method" value="google_pay" {{ old('payment_method') === 'google_pay' ? 'checked' : '' }}><span><span class="payment-choice-main">Google Pay</span><span class="payment-choice-note">International guests where available.</span></span></label>
                                    <label class="payment-choice payment-method-option" data-scope="local"><input type="radio" name="payment_method" value="bank_transfer_mvr" {{ old('payment_method') === 'bank_transfer_mvr' ? 'checked' : '' }}><span><span class="payment-choice-main">Local Bank Transfer (MVR)</span><span class="payment-choice-note">Recommended for local nationals.</span></span></label>
                                </div>
                                <p class="payment-hint" id="paymentHint">Payment methods are auto-filtered by guest nationality.</p>
                            </div>
                        @endif
                    </div>

                    <p class="error-text" data-service-date-error style="display:none; margin:10px 0 0;"></p>

                    <div class="actions">
                        <button class="btn primary" type="submit">{{ $categoryKey === 'excursion' ? 'Book Now' : 'Proceed to Checkout' }}</button>
                        <a class="btn" href="/catalog/{{ $categoryKey }}">Back to {{ $categoryLabel }} Catalog</a>
                    </div>
                </form>
            </aside>
        </div>

        @include('partials.global-site-footer')
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const galleryShell = document.querySelector('.gallery-shell');
            if (galleryShell) {
                const mainImage = galleryShell.querySelector('.gallery-main');
                const thumbButtons = galleryShell.querySelectorAll('.gallery-thumb');

                thumbButtons.forEach((btn, index) => {
                    const img = btn.querySelector('img');
                    if (img && index === 0) {
                        btn.classList.add('is-active');
                    }

                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (!img || !mainImage) return;

                        mainImage.src = img.src;

                        thumbButtons.forEach(b => b.classList.remove('is-active'));
                        btn.classList.add('is-active');
                    });
                });
            }

            const qtySelects = document.querySelectorAll('[data-excursion-qty]');
            const totalDisplay = document.getElementById('excursionTotalDisplay');
            if (qtySelects.length > 0 && totalDisplay) {
                const currency = @json($currency);
                const updateExcursionTotal = function () {
                    let total = 0;
                    qtySelects.forEach((select) => {
                        const qty = parseInt(select.value || '0', 10) || 0;
                        const unit = parseFloat(select.getAttribute('data-unit-price') || '0') || 0;
                        total += qty * unit;
                    });
                    totalDisplay.textContent = currency + ' ' + total.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                qtySelects.forEach((select) => {
                    select.addEventListener('change', updateExcursionTotal);
                });
                updateExcursionTotal();
            }

            const copyShareBtn = document.querySelector('[data-copy-share-link]');
            if (copyShareBtn) {
                copyShareBtn.addEventListener('click', async function () {
                    const shareUrl = this.getAttribute('data-copy-share-link') || window.location.href;
                    try {
                        await navigator.clipboard.writeText(shareUrl);
                        this.style.background = '#d7f0e4';
                        setTimeout(() => {
                            this.style.background = '#f8fdff';
                        }, 1500);
                    } catch (e) {
                        window.prompt('Copy this link', shareUrl);
                    }
                });
            }

            const serviceStartInput = document.getElementById('serviceStartDate');
            const serviceEndInput = document.getElementById('serviceEndDate');
            const serviceDateError = document.querySelector('[data-service-date-error]');
            const bookingForm = document.getElementById('categoryServiceBookingForm');
            const primaryFirstName = document.getElementById('primaryFirstName');
            const primaryLastName = document.getElementById('primaryLastName');
            const primaryNationality = document.getElementById('primaryNationality');
            const primaryEmail = document.getElementById('primaryEmail');
            const primaryMobileCountryCode = document.getElementById('primaryMobileCountryCode');
            const primaryMobileLocal = document.getElementById('primaryMobileLocal');
            const primaryMobileHidden = document.getElementById('primaryMobileHidden');
            const guestResidencyInput = document.getElementById('guestResidencyInput');
            const transferOptionInputs = Array.from(document.querySelectorAll('input[name="transfer_option"]'));
            const transferChargeInput = document.getElementById('transferCharge');
            const paymentMethodList = document.getElementById('paymentMethodList');
            const paymentHint = document.getElementById('paymentHint');
            const adultsInput = document.getElementById('adults');
            const childrenInput = document.getElementById('children');
            const todayDate = @json((string) ($todayDate ?? now()->toDateString()));
            const unavailableDates = @json($unavailableDates ?? ['blocked' => [], 'reserved' => []]);
            const blockedDateSet = new Set(Array.isArray(unavailableDates?.blocked) ? unavailableDates.blocked : []);
            const reservedDateSet = new Set(Array.isArray(unavailableDates?.reserved) ? unavailableDates.reserved : []);

            const normalizeToDate = (value) => {
                const normalized = String(value || '').trim();
                if (normalized === '') {
                    return '';
                }

                return normalized.length >= 10 ? normalized.slice(0, 10) : normalized;
            };

            const setServiceDateError = (message) => {
                const text = String(message || '').trim();
                [serviceStartInput, serviceEndInput].forEach((input) => {
                    if (!input) {
                        return;
                    }
                    input.setCustomValidity(text);
                });

                if (!serviceDateError) {
                    return;
                }

                serviceDateError.textContent = text;
                serviceDateError.style.display = text === '' ? 'none' : 'block';
            };

            const validateServiceDates = () => {
                if (!serviceStartInput) {
                    return true;
                }

                const startDate = normalizeToDate(serviceStartInput.value);
                const endDateRaw = serviceEndInput ? serviceEndInput.value : '';
                const endDate = normalizeToDate(endDateRaw);
                const effectiveEndDate = endDate !== '' ? endDate : startDate;

                if (startDate === '') {
                    setServiceDateError('');
                    return true;
                }

                if (startDate < todayDate) {
                    setServiceDateError('Selected start date cannot be in the past.');
                    return false;
                }

                if (effectiveEndDate !== '' && effectiveEndDate < startDate) {
                    setServiceDateError('End date must be after or equal to start date.');
                    return false;
                }

                let cursor = new Date(startDate + 'T00:00:00');
                const endBoundary = new Date(effectiveEndDate + 'T00:00:00');
                while (cursor <= endBoundary) {
                    const dateKey = cursor.toISOString().slice(0, 10);
                    if (blockedDateSet.has(dateKey) || reservedDateSet.has(dateKey)) {
                        setServiceDateError(`Selected date range includes unavailable day (${dateKey}).`);
                        return false;
                    }
                    cursor.setDate(cursor.getDate() + 1);
                }

                setServiceDateError('');
                return true;
            };

            [serviceStartInput, serviceEndInput].forEach((input) => {
                if (!input) {
                    return;
                }

                input.addEventListener('input', validateServiceDates);
                input.addEventListener('change', validateServiceDates);
            });

            const fieldWrap = function (element) {
                return element ? element.closest('.field') : null;
            };

            const markFieldError = function (element, hasError) {
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
            };

            const syncPrimaryMobile = function () {
                if (!primaryMobileHidden || !primaryMobileCountryCode || !primaryMobileLocal) {
                    return;
                }

                const dial = String(primaryMobileCountryCode.value || '').trim();
                const local = String(primaryMobileLocal.value || '').trim();
                primaryMobileHidden.value = (dial + ' ' + local).trim();
            };

            const currentNationalityIso = function () {
                if (!primaryNationality) {
                    return '';
                }

                const selected = primaryNationality.options[primaryNationality.selectedIndex];
                return String(selected?.dataset?.iso || '').toUpperCase();
            };

            const updateGuestResidency = function () {
                if (!guestResidencyInput) {
                    return;
                }

                guestResidencyInput.value = currentNationalityIso() === 'MV'
                    ? 'local_resident'
                    : 'foreign_national';
            };

            const selectedTransferInput = function () {
                return transferOptionInputs.find(function (input) { return input.checked; }) || null;
            };

            const transferChargeTotal = function () {
                const transferSelected = selectedTransferInput();
                if (!transferSelected) {
                    return 0;
                }

                const adults = Math.max(1, Number(adultsInput?.value || 1));
                const children = Math.max(0, Number(childrenInput?.value || 0));
                const isLocal = currentNationalityIso() === 'MV';
                const adultRate = Number(isLocal ? transferSelected.dataset.localAdult : transferSelected.dataset.foreignAdult) || 0;
                const childRate = Number(isLocal ? transferSelected.dataset.localChild : transferSelected.dataset.foreignChild) || 0;
                const baseRate = Number(isLocal ? transferSelected.dataset.baseLocal : transferSelected.dataset.baseForeign) || 0;

                return baseRate + (adultRate * adults) + (childRate * children);
            };

            const syncTransferCharge = function () {
                if (!transferChargeInput) {
                    return;
                }

                transferChargeInput.value = transferChargeTotal().toFixed(2);
            };

            const updatePaymentOptionsByNationality = function () {
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
            };

            const validateMandatoryGuestFields = function () {
                if (!bookingForm) {
                    return true;
                }

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
                        check: function (value) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value); },
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

                const existingErrorBox = bookingForm.querySelector('.form-errors.client-errors');
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
                    bookingForm.insertBefore(errorBox, bookingForm.firstElementChild.nextElementSibling);
                }

                return errors.length === 0;
            };

            [primaryFirstName, primaryLastName, primaryNationality, primaryEmail, primaryMobileCountryCode, primaryMobileLocal].forEach(function (input) {
                if (!input) {
                    return;
                }

                ['change', 'input'].forEach(function (eventName) {
                    input.addEventListener(eventName, function () {
                        markFieldError(input, false);
                        if (input === primaryNationality) {
                            const selected = primaryNationality.options[primaryNationality.selectedIndex];
                            const suggestedDial = String(selected?.dataset?.dial || '').trim();
                            if (suggestedDial !== '' && primaryMobileCountryCode) {
                                primaryMobileCountryCode.value = suggestedDial;
                            }
                            updateGuestResidency();
                            updatePaymentOptionsByNationality();
                            syncTransferCharge();
                        }

                        syncPrimaryMobile();
                    });
                });
            });

            transferOptionInputs.forEach(function (input) {
                ['change', 'input'].forEach(function (eventName) {
                    input.addEventListener(eventName, syncTransferCharge);
                });
            });

            [adultsInput, childrenInput].forEach(function (input) {
                if (!input) {
                    return;
                }

                ['change', 'input'].forEach(function (eventName) {
                    input.addEventListener(eventName, syncTransferCharge);
                });
            });

            if (paymentMethodList) {
                paymentMethodList.addEventListener('change', updatePaymentOptionsByNationality);
            }

            if (bookingForm) {
                bookingForm.addEventListener('submit', function (event) {
                    syncPrimaryMobile();
                    updateGuestResidency();
                    updatePaymentOptionsByNationality();
                    syncTransferCharge();

                    const datesValid = validateServiceDates();
                    const guestValid = validateMandatoryGuestFields();
                    if (!datesValid || !guestValid) {
                        event.preventDefault();
                        const errorBlock = bookingForm.querySelector('.form-errors.client-errors') || serviceDateError;
                        if (errorBlock && typeof errorBlock.scrollIntoView === 'function') {
                            errorBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            }

            validateServiceDates();
            syncPrimaryMobile();
            updateGuestResidency();
            updatePaymentOptionsByNationality();
            syncTransferCharge();
        });
    </script>

</body>
</html>