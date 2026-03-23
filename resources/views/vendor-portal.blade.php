<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f1f5ef;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #194356;
            --hero-2: #0e6b74;
            --hero-3: #34a272;
            --ok: #0b5c2a;
            --ok-bg: #d8f7e2;
            --warn: #7a4606;
            --warn-bg: #ffeccd;
            --err: #6d1111;
            --err-bg: #ffe0de;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 10%, #d7f4e7 0, #d7f4e700 32%),
                radial-gradient(circle at 90% 10%, #d9f1ff 0, #d9f1ff00 35%),
                var(--bg);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 18px 34px;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 48%, var(--hero-3) 100%);
            border-radius: 18px;
            color: #fff;
            padding: 24px;
            box-shadow: 0 22px 44px rgba(18, 38, 58, 0.2);
        }

        .eyebrow {
            display: inline-block;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #d7f2f5;
            margin-bottom: 10px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.45rem, 2.8vw, 2.3rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 780px;
        }

        .hero-links {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .auth-bar {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .auth-user {
            font-size: 0.82rem;
            border: 1px solid #b8dfe4;
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(11, 49, 75, 0.32);
            color: #dff4fb;
        }

        .logout {
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #f0fbff;
            background: rgba(11, 49, 75, 0.45);
            cursor: pointer;
        }

        .hero-link {
            color: #ecfbff;
            text-decoration: none;
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 0.82rem;
            background: rgba(11, 49, 75, 0.32);
        }

        .portal-shell {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 252px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .portal-nav {
            position: sticky;
            top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #f7fbff;
        }

        .portal-content {
            min-width: 0;
        }

        .portal-nav a {
            text-decoration: none;
            border: 1px solid #c8d4df;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1f4a53;
            background: #ffffff;
        }

        .menu-title {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5b6778;
            margin-top: 4px;
        }

        .menu-title:first-child {
            margin-top: 0;
        }

        .portal-nav a.menu-sub {
            margin-left: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            color: #33566f;
            background: #f8fbff;
        }

        .portal-content section {
            scroll-margin-top: 16px;
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 12px;
        }

        .summary-grid {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .summary-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }

        .summary-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .summary-value {
            margin: 6px 0 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f3346;
        }

        .summary-meta {
            margin: 6px 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .summary-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .summary-refresh {
            border: 1px solid #c8d3df;
            border-radius: 9px;
            background: #ffffff;
            color: #20415d;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 7px 10px;
            cursor: pointer;
        }

        .summary-refresh[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .support-links {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .support-footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #d7e0e6;
        }

        .support-links a {
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: #20415d;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .notice {
            margin-top: 12px;
            border: 1px solid #b8debf;
            border-radius: 10px;
            background: #ecf9f0;
            color: #184b23;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 10px 12px;
        }

        .error {
            margin-top: 12px;
            border: 1px solid #f0c3c0;
            border-radius: 10px;
            background: #fff1f0;
            color: #7f1b1b;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 10px 12px;
        }

        .profile-card {
            margin-top: 12px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .profile-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .profile-field label {
            font-size: 0.78rem;
            color: var(--muted);

        .payout-center {
            margin-top: 12px;
        }

        .payout-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .payout-metric {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .payout-metric .metric-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .payout-metric .metric-value {
            margin: 5px 0 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f3346;
        }

        .payout-table-wrap {
            margin-top: 10px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .payout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payout-table th,
        .payout-table td {
            text-align: left;
            border-bottom: 1px solid #edf2f8;
            padding: 9px 10px;
            font-size: 0.82rem;
            color: #233247;
        }

        .payout-table th {
            background: #f8fbff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #456077;
            font-size: 0.72rem;
        }

        .payout-table tr:last-child td {
            border-bottom: 0;
        }

        .payout-empty {
            padding: 12px;
            font-size: 0.82rem;
            color: var(--muted);
        }
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .payout-center {
            margin-top: 12px;
        }

        .payout-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .payout-metric {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .payout-metric .metric-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .payout-metric .metric-value {
            margin: 5px 0 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f3346;
        }

        .payout-table-wrap {
            margin-top: 10px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .payout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payout-table th,
        .payout-table td {
            text-align: left;
            border-bottom: 1px solid #edf2f8;
            padding: 9px 10px;
            font-size: 0.82rem;
            color: #233247;
        }

        .payout-table th {
            background: #f8fbff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #456077;
            font-size: 0.72rem;
        }

        .payout-table tr:last-child td {
            border-bottom: 0;
        }

        .payout-empty {
            padding: 12px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .ops-section {
            margin-top: 12px;
        }

        .ops-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .ops-title {
            margin: 0;
            font-size: 1rem;
            color: #1f3346;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .ops-chip {
            display: inline-block;
            border-radius: 999px;
            border: 1px solid #d7e0e6;
            background: #f7fbff;
            color: #3a5b78;
            padding: 4px 9px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .ops-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .ops-form {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .ops-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .ops-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ops-field-wide {
            grid-column: 1 / -1;
        }

        .ops-field label {
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .ops-input,
        .ops-select,
        .ops-textarea {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 10px;
            padding: 9px 11px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1d3045;
            background: #fff;
        }

        .ops-textarea {
            min-height: 90px;
            resize: vertical;
        }

        .ops-table-wrap {
            margin-top: 10px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .ops-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ops-table th,
        .ops-table td {
            text-align: left;
            border-bottom: 1px solid #edf2f8;
            padding: 8px 9px;
            font-size: 0.8rem;
            color: #233247;
            vertical-align: top;
        }

        .ops-table th {
            background: #f8fbff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #456077;
            font-size: 0.7rem;
        }

        .ops-table tr:last-child td {
            border-bottom: 0;
        }

        .ops-empty {
            padding: 10px;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .ops-metrics {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
        }

        .billing-ledger-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .billing-ledger-card {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 9px;
        }

        .billing-ledger-card .metric-label {
            margin: 0;
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .billing-ledger-card .metric-value {
            margin: 6px 0 0;
            font-size: 1.03rem;
            font-weight: 700;
            color: #1f3346;
        }

        .ops-metric {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 9px;
        }

        .ops-metric p {
            margin: 0;
        }

        .ops-metric .metric-label {
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .ops-metric .metric-value {
            margin-top: 5px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f3346;
        }

        .wizard-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }

        .category-item {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.84rem;
            color: #21384c;
        }

        .step-list {
            margin: 0;
            padding-left: 18px;
            color: #30485e;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .media-thumb {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            font-size: 0.8rem;
            color: #30485e;
        }

        .wizard-note {
            margin-top: 8px;
            font-size: 0.8rem;
            color: #4f6479;
        }

        .standards-note {
            margin-top: 6px;
            font-size: 0.78rem;
            color: #4b6075;
        }

        .inline-status-form {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .inline-status-form .btn {
            margin-top: 0;
        }

            .payout-grid {
                grid-template-columns: 1fr;
            }
            
        .profile-input {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.92rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1d3045;
            background: #fff;
        }

        .profile-input[readonly] {
            background: #f4f7fa;
            color: #4b5c70;
        }

        .profile-help {
            margin-top: 8px;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .status-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-pill.ok { color: var(--ok); background: var(--ok-bg); }
        .status-pill.warn { color: var(--warn); background: var(--warn-bg); }
        .status-pill.err { color: var(--err); background: var(--err-bg); }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .label {
            margin: 0 0 8px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .token-input {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .btn {
            margin-top: 10px;
            border: 0;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f6d5f;
            color: #fff;
        }

        .btn-secondary {
            background: #edf2f8;
            color: #183452;
            margin-left: 8px;
        }

        .endpoint {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
            border: 1px solid #d7dee6;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            background: #fff;
        }

        .endpoint code {
            font-size: 0.83rem;
            color: #233247;
            word-break: break-all;
        }

        .endpoint button {
            border: 0;
            border-radius: 8px;
            background: #0e6b5f;
            color: #fff;
            font-weight: 700;
            padding: 7px 10px;
            cursor: pointer;
        }

        .endpoint button[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .endpoint button.is-loading::after {
            content: " ...";
        }

        .state {
            margin-top: 12px;
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-weight: 700;
            font-size: 0.78rem;
        }

        .state.ok { color: var(--ok); background: var(--ok-bg); }
        .state.warn { color: var(--warn); background: var(--warn-bg); }
        .state.err { color: var(--err); background: var(--err-bg); }

        .token-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.8rem;
            line-height: 1.35;
        }

        pre {
            margin: 10px 0 0;
            border-radius: 10px;
            border: 1px solid #d8e1ea;
            background: #f8fbff;
            padding: 12px;
            max-height: 360px;
            overflow: auto;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        @media (max-width: 900px) {
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .ops-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .ops-grid,
            .ops-form-grid {
                grid-template-columns: 1fr;
            }

            .wizard-grid,
            .category-grid {
                grid-template-columns: 1fr;
            }

            .payout-grid {
                grid-template-columns: 1fr;
            }

            .support-links {
                grid-template-columns: 1fr;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .portal-shell {
                grid-template-columns: 1fr;
            }

            .portal-nav {
                position: static;
                overflow-x: auto;
                white-space: nowrap;
                flex-direction: row;
                flex-wrap: wrap;
            }

            .portal-nav .menu-title {
                width: 100%;
            }

            .portal-nav a.menu-sub {
                margin-left: 0;
            }

            .billing-ledger-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .ops-metrics {
                grid-template-columns: 1fr 1fr;
            }

            .billing-ledger-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $vendorProperties = $vendorProperties ?? collect();
        $vendorServices = $vendorServices ?? collect();
        $vendorAvailability = $vendorAvailability ?? collect();
        $vendorReservations = $vendorReservations ?? collect();
        $vendorPricingRules = $vendorPricingRules ?? collect();
        $vendorBilling = $vendorBilling ?? null;
        $vendorCategoryMap = $vendorCategoryMap ?? [];
        $selectedVendorCategories = $selectedVendorCategories ?? [];
        $vendorOnboardingStep = $vendorOnboardingStep ?? 1;
        $vendorRoomCategories = $vendorRoomCategories ?? collect();
        $vendorMediaAssets = $vendorMediaAssets ?? collect();
        $categorySet = collect($selectedVendorCategories)->flip();
        $supportsAccommodation = $categorySet->has('accommodation');
        $hasSelectedCategories = count($selectedVendorCategories) > 0;
        $commissionRate = 0.12;
        $billingLedgerRows = $vendorReservations->take(50)->map(function ($reservation) use ($commissionRate) {
            $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
            $subtotal = (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0);
            $taxTotal = (float) ($reservation->total_tax_amount ?? 0);
            $serviceChargeTotal = (float) ($reservation->service_charge_total ?? 0);
            $paymentStatus = (string) ($reservation->payment_status ?? 'unpaid');
            $bookingStatus = (string) ($reservation->status ?? 'pending');
            $isSettled = $paymentStatus === 'paid' && in_array($bookingStatus, ['confirmed', 'completed'], true);
            $commission = $isSettled ? round($gross * $commissionRate, 2) : 0.0;
            $payout = max(0, round($gross - $commission, 2));
            $invoiceRef = 'INV-' . str_pad((string) ($reservation->id ?? '0'), 6, '0', STR_PAD_LEFT);
            $collectionDate = (string) ($reservation->start_at ?? $reservation->created_at ?? '');
            $collectionDay = strlen($collectionDate) >= 10 ? substr($collectionDate, 0, 10) : 'N/A';

            return [
                'invoice_ref' => $invoiceRef,
                'customer_name' => (string) ($reservation->customer_name ?? 'N/A'),
                'customer_email' => (string) ($reservation->customer_email ?? ''),
                'collection_day' => $collectionDay,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'service_charge_total' => $serviceChargeTotal,
                'green_tax_total' => (float) ($reservation->green_tax_total ?? 0),
                'tgst_total' => (float) ($reservation->tgst_total ?? 0),
                'cgst_total' => (float) ($reservation->cgst_total ?? 0),
                'guest_is_foreigner' => (bool) ($reservation->guest_is_foreigner ?? true),
                'gross' => $gross,
                'commission' => $commission,
                'payout' => $payout,
                'currency' => (string) ($reservation->currency ?? 'MVR'),
                'payment_status' => $paymentStatus,
                'booking_status' => $bookingStatus,
                'is_settled' => $isSettled,
            ];
        });
        $dailyCollection = $billingLedgerRows->groupBy('collection_day')->map(function ($rows) {
            return [
                'gross' => (float) $rows->sum('gross'),
                'commission' => (float) $rows->sum('commission'),
                'payout' => (float) $rows->sum('payout'),
                'count' => (int) $rows->count(),
            ];
        })->sortKeysDesc();
        $settledInvoicesCount = (int) $billingLedgerRows->where('is_settled', true)->count();
        $grossCollectionsTotal = (float) $billingLedgerRows->sum('gross');
        $commissionTotal = (float) $billingLedgerRows->sum('commission');
        $payoutTotal = (float) $billingLedgerRows->sum('payout');
    @endphp
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <span class="eyebrow">Partner Access</span>
            <h1>Vendor Portal</h1>
            <p>Use a valid vendor bearer token to check vendor-facing APIs and account-level data.</p>
            <div class="hero-links">
                <a class="hero-link" href="/">Back to Home</a>
                <a class="hero-link" href="/admin">Go to Admin Portal</a>
                <a class="hero-link" href="{{ $apiBase }}/api/v1/ops/metrics" target="_blank" rel="noopener">Open Public Metrics</a>
            </div>
            <div class="auth-bar">
                <span class="auth-user">Signed in as {{ $portalUser }}</span>
                <form method="POST" action="/portal/vendor/logout">
                    @csrf
                    <button class="logout" type="submit">Log Out</button>
                </form>
            </div>
        </section>

        <div class="portal-shell">
        <nav class="portal-nav" aria-label="Vendor navigation">
            <span class="menu-title">Overview</span>
            <a href="#vendorSummary">Dashboard Summary</a>
            <a href="#payoutCenter" class="menu-sub">Payout Snapshot</a>

            <span class="menu-title">Profile / Update</span>
            <a href="#vendorProfileCard">Profile Settings</a>
            <a href="#vendorCategoryWizard" class="menu-sub">Category Setup</a>

            <span class="menu-title">Add Listings</span>
            <a href="#vendorPropertiesSection">Add/Edit Listings</a>
            <a href="#vendorServicesSection" class="menu-sub">Add/Edit Services</a>
            <a href="#vendorRoomsSection" class="menu-sub">Room Inventory</a>
            <a href="#vendorMediaSection" class="menu-sub">Listing Photos</a>

            <span class="menu-title">Reservations / Bookings</span>
            <a href="#vendorReservationsSection">Booking Inquiries</a>
            <a href="#vendorAvailabilitySection" class="menu-sub">Availability Updates</a>
            <a href="#vendorPricingSection" class="menu-sub">Pricing Rules</a>

            <span class="menu-title">Billing / Daily Collection</span>
            <a href="#vendorBillingSection">Billing Settings</a>
            <a href="#vendorDailyCollectionSection" class="menu-sub">Collections & Payouts</a>

            <span class="menu-title">API Tools</span>
            <a href="#vendorAuthApi">Auth and API</a>
            <a href="#vendorAuthCard" class="menu-sub">Token</a>
            <a href="#vendorApiCard" class="menu-sub">API Actions</a>
        </nav>

        <div class="portal-content">

        <section id="vendorSummary" class="summary-grid" aria-label="Vendor dashboard summary">
            <article class="summary-card">
                <p class="summary-label">Bookings</p>
                <p id="summaryBookings" class="summary-value">-</p>
                <p class="summary-meta">Total bookings visible with current token</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Settlements</p>
                <p id="summarySettlements" class="summary-value">-</p>
                <p class="summary-meta">Settlement entries returned by payments API</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Token Status</p>
                <p id="summaryToken" class="summary-value">N/A</p>
                <p id="summaryTokenMeta" class="summary-meta">Save token to evaluate readiness</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Backend Connectivity</p>
                <p class="summary-value"><span id="summaryConnectivity" class="status-pill warn">UNKNOWN</span></p>
                <p id="summaryLastSync" class="summary-meta">Last sync: not run yet</p>
            </article>
        </section>

        <div class="summary-actions">
            <button id="refreshSummary" type="button" class="summary-refresh">Refresh Summary</button>
        </div>

        <section id="payoutCenter" class="card payout-center" aria-label="Vendor payout center">
            <p class="label">Payout Center</p>
            <div class="payout-grid">
                <article class="payout-metric">
                    <p class="metric-label">Settled Total</p>
                    <p id="payoutSettledTotal" class="metric-value">MVR 0.00</p>
                </article>
                <article class="payout-metric">
                    <p class="metric-label">Pending Total</p>
                    <p id="payoutPendingTotal" class="metric-value">MVR 0.00</p>
                </article>
                <article class="payout-metric">
                    <p class="metric-label">Next Payout Estimate</p>
                    <p id="payoutNextEstimate" class="metric-value">N/A</p>
                </article>
            </div>
            <div class="payout-table-wrap">
                <table class="payout-table" aria-label="Recent payouts">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="payoutRows">
                        <tr>
                            <td colspan="4" class="payout-empty">Refresh summary to load payout data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        @if (session('portal_notice'))
            <div class="notice" role="status" aria-live="polite">{{ session('portal_notice') }}</div>
        @endif

        @if ($errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first('profile') }}</div>
        @endif

        <section id="vendorProfileCard" class="card profile-card" aria-label="Vendor profile settings">
            <p class="label">Account Settings</p>
            <form method="POST" action="/portal/vendor/profile/update">
                @csrf
                <div class="profile-grid">
                    <div class="profile-field">
                        <label for="display_name">Display Name</label>
                        <input
                            id="display_name"
                            name="display_name"
                            class="profile-input"
                            type="text"
                            value="{{ old('display_name', $vendorProfile['name'] ?? '') }}"
                            maxlength="120"
                            required
                        >
                    </div>
                    <div class="profile-field">
                        <label for="contact_phone">Contact Phone</label>
                        <input
                            id="contact_phone"
                            name="contact_phone"
                            class="profile-input"
                            type="text"
                            value="{{ old('contact_phone', $vendorProfile['phone'] ?? '') }}"
                            maxlength="40"
                            placeholder="+960..."
                        >
                    </div>
                    <div class="profile-field">
                        <label for="account_email">Account Email</label>
                        <input
                            id="account_email"
                            class="profile-input"
                            type="text"
                            value="{{ $vendorProfile['email'] ?? '' }}"
                            readonly
                        >
                    </div>
                    <div class="profile-field">
                        <label for="vendor_id">Vendor ID</label>
                        <input
                            id="vendor_id"
                            class="profile-input"
                            type="text"
                            value="{{ $vendorProfile['vendor_id'] ?? '' }}"
                            readonly
                        >
                    </div>
                </div>
                <p class="profile-help">Update your display name and primary phone number used by the vendor team.</p>
                <button class="btn btn-primary" type="submit">Save Profile Settings</button>
            </form>
        </section>

        <section id="vendorCategoryWizard" class="card ops-section" aria-label="Vendor category setup wizard">
            <div class="ops-header">
                <p class="ops-title">Category-Based Listing Wizard</p>
                <span class="ops-chip">Step {{ $vendorOnboardingStep }} of 4</span>
            </div>
            <div class="wizard-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/categories/update">
                    @csrf
                    <div class="ops-field">
                        <label>Select your service categories</label>
                        <div class="category-grid">
                            @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                                <label class="category-item" for="category_{{ $categoryKey }}">
                                    <input id="category_{{ $categoryKey }}" type="checkbox" name="categories[]" value="{{ $categoryKey }}" @checked(in_array($categoryKey, $selectedVendorCategories, true))>
                                    <span>{{ $categoryLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="ops-field" style="margin-top:10px;">
                        <label for="onboarding_step">Current onboarding step</label>
                        <select id="onboarding_step" name="onboarding_step" class="ops-select" required>
                            <option value="1" @selected((int) $vendorOnboardingStep === 1)>Step 1: Choose Categories</option>
                            <option value="2" @selected((int) $vendorOnboardingStep === 2)>Step 2: Add Profile + Billing</option>
                            <option value="3" @selected((int) $vendorOnboardingStep === 3)>Step 3: Create Listings + Availability</option>
                            <option value="4" @selected((int) $vendorOnboardingStep === 4)>Step 4: Add Photos + Publish</option>
                        </select>
                    </div>
                    <p class="wizard-note">Only selected categories can be used when creating properties/services.</p>
                    <button class="btn btn-primary" type="submit">Save Category Setup</button>
                </form>

                <article class="ops-form">
                    <p class="label">Step-by-step checklist</p>
                    <ol class="step-list">
                        <li>Select categories from schema domains: Accommodation, excursions, remoteWorkSpaces, resortDayVisits, restaurants, transports, vehicleRentals.</li>
                        <li>Complete account profile and billing details.</li>
                        <li>Create listings, room categories (accommodation), availability, and pricing.</li>
                        <li>Upload photos and finalize publish-ready inventory.</li>
                    </ol>
                    <p class="wizard-note">You can update categories later. Existing records remain editable.</p>
                </article>
            </div>
        </section>

        <section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview">
            <div class="ops-header">
                <p class="ops-title">Operations Console</p>
                <span class="ops-chip">Database-backed</span>
            </div>
            @if (!$hasSelectedCategories)
                <p class="wizard-note">Select at least one category in Category Wizard before creating listings.</p>
            @endif
            <div class="ops-metrics">
                <article class="ops-metric">
                    <p class="metric-label">Properties</p>
                    <p class="metric-value">{{ $vendorProperties->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Services</p>
                    <p class="metric-value">{{ $vendorServices->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Availability Days</p>
                    <p class="metric-value">{{ $vendorAvailability->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Reservations</p>
                    <p class="metric-value">{{ $vendorReservations->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Pricing Rules</p>
                    <p class="metric-value">{{ $vendorPricingRules->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Billing Profile</p>
                    <p class="metric-value">{{ $vendorBilling ? 'Ready' : 'Missing' }}</p>
                </article>
            </div>
        </section>

        <section id="vendorPropertiesSection" class="card ops-section" aria-label="Vendor properties">
            <div class="ops-header">
                <p class="ops-title">Properties and Listings</p>
                <span class="ops-chip">{{ $vendorProperties->count() }} total</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/properties/create">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="property_listing_category">Listing Category</label>
                            <select id="property_listing_category" name="listing_category" class="ops-select" required>
                                @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                                    <option value="{{ $categoryKey }}" @disabled(!in_array($categoryKey, $selectedVendorCategories, true))>{{ $categoryLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="property_name">Name</label>
                            <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" required>
                        </div>
                        <div class="ops-field">
                            <label for="property_type">Type</label>
                            <select id="property_type" name="property_type" class="ops-select" required>
                                <option value="property">Property</option>
                                <option value="service">Service Space</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="property_location">Location</label>
                            <input id="property_location" name="location" class="ops-input" type="text" maxlength="190">
                        </div>
                        <div class="ops-field">
                            <label for="property_base_price">Base Price (MVR)</label>
                            <input id="property_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01">
                        </div>
                        <div class="ops-field">
                            <label for="property_max_guests">Max Guests</label>
                            <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="1" max="10000">
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="property_description">Description</label>
                            <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000"></textarea>
                        </div>
                        <div class="ops-field">
                            <label for="property_measurement_system">Measurement System</label>
                            <select id="property_measurement_system" name="measurement_system" class="ops-select">
                                <option value="metric">Metric</option>
                                <option value="imperial">Imperial</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="property_area_value">Area Value</label>
                            <input id="property_area_value" name="area_value" class="ops-input" type="number" min="1" max="100000" step="0.01" placeholder="e.g. 120">
                        </div>
                        <div class="ops-field">
                            <label for="property_area_unit">Area Unit</label>
                            <select id="property_area_unit" name="area_unit" class="ops-select">
                                <option value="sqm">sqm</option>
                                <option value="sqft">sqft</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="property_bedroom_count">Bedrooms</label>
                            <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000">
                        </div>
                        <div class="ops-field">
                            <label for="property_bathroom_count">Bathrooms</label>
                            <input id="property_bathroom_count" name="bathroom_count" class="ops-input" type="number" min="0" max="1000" step="0.5">
                        </div>
                        <div class="ops-field">
                            <label for="property_capacity_value">Capacity</label>
                            <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" placeholder="seats, guests, units">
                        </div>
                        <div class="ops-field">
                            <label for="property_service_radius_km">Service Radius (km)</label>
                            <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" max="5000" step="0.1">
                        </div>
                        <div class="ops-field">
                            <label for="property_minimum_age">Minimum Age</label>
                            <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="0" max="120">
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="property_safety_certifications">Safety Certifications</label>
                            <textarea id="property_safety_certifications" name="safety_certifications" class="ops-textarea" maxlength="2000" placeholder="ISO, local licenses, fire safety, marine certifications"></textarea>
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="property_accessibility_features">Accessibility Features</label>
                            <textarea id="property_accessibility_features" name="accessibility_features" class="ops-textarea" maxlength="2000" placeholder="Wheelchair access, ramps, accessible toilets, visual aids"></textarea>
                        </div>
                    </div>
                    <p class="standards-note">International listing standard: include measurable area/capacity and safety/accessibility details for trust and compliance.</p>
                    <button class="btn btn-primary" type="submit">Add Listing</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor properties table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorProperties->take(12) as $property)
                                <tr>
                                    <td>{{ strtoupper((string) ($property->listing_category ?? 'N/A')) }}</td>
                                    <td>{{ $property->name }}</td>
                                    <td>{{ strtoupper((string) $property->property_type) }}</td>
                                    <td>{{ $property->location ?: 'N/A' }}</td>
                                    <td>{{ $property->currency }} {{ number_format((float) $property->base_price, 2) }}</td>
                                    <td>{{ strtoupper((string) $property->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No properties yet. Add your first listing.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if ($supportsAccommodation)
            <section id="vendorRoomsSection" class="card ops-section" aria-label="Vendor room categories">
                <div class="ops-header">
                    <p class="ops-title">Room Categories (Accommodation)</p>
                    <span class="ops-chip">{{ $vendorRoomCategories->count() }} total</span>
                </div>
                <div class="ops-grid">
                    <form class="ops-form" method="POST" action="/portal/vendor/rooms/create">
                        @csrf
                        <div class="ops-form-grid">
                            <div class="ops-field">
                                <label for="room_property_id">Property ID (optional)</label>
                                <input id="room_property_id" name="vendor_property_id" class="ops-input" type="number" min="1">
                            </div>
                            <div class="ops-field">
                                <label for="room_name">Room Category Name</label>
                                <input id="room_name" name="name" class="ops-input" type="text" maxlength="160" required>
                            </div>
                            <div class="ops-field">
                                <label for="room_quantity">Quantity</label>
                                <input id="room_quantity" name="quantity" class="ops-input" type="number" min="1" max="10000" value="1">
                            </div>
                            <div class="ops-field">
                                <label for="room_occupancy">Max Occupancy</label>
                                <input id="room_occupancy" name="max_occupancy" class="ops-input" type="number" min="1" max="50" value="2">
                            </div>
                            <div class="ops-field">
                                <label for="room_bed_type">Bed Type</label>
                                <input id="room_bed_type" name="bed_type" class="ops-input" type="text" maxlength="80" placeholder="King, Twin, etc.">
                            </div>
                            <div class="ops-field">
                                <label for="room_base_price">Base Price (MVR)</label>
                                <input id="room_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01" value="0">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="room_amenities">Room Amenities</label>
                                <textarea id="room_amenities" name="amenities" class="ops-textarea" maxlength="3000" placeholder="WiFi, balcony, sea-view, breakfast included"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Add Room Category</button>
                    </form>

                    <div class="ops-table-wrap">
                        <table class="ops-table" aria-label="Vendor room categories table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Qty</th>
                                    <th>Occupancy</th>
                                    <th>Bed</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vendorRoomCategories->take(12) as $room)
                                    <tr>
                                        <td>{{ $room->name }}</td>
                                        <td>{{ (int) $room->quantity }}</td>
                                        <td>{{ (int) $room->max_occupancy }}</td>
                                        <td>{{ $room->bed_type ?: 'N/A' }}</td>
                                        <td>{{ $room->currency }} {{ number_format((float) $room->base_price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="ops-empty">No room categories yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <section id="vendorMediaSection" class="card ops-section" aria-label="Vendor listing photos">
            <div class="ops-header">
                <p class="ops-title">Photos and Media</p>
                <span class="ops-chip">{{ $vendorMediaAssets->count() }} uploaded</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="media_entity_type">Entity Type</label>
                            <select id="media_entity_type" name="entity_type" class="ops-select" required>
                                <option value="property">Property</option>
                                <option value="service">Service</option>
                                <option value="room">Room</option>
                                <option value="menu">Menu</option>
                                <option value="vehicle">Vehicle</option>
                                <option value="profile">Profile</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="media_entity_id">Entity ID (optional)</label>
                            <input id="media_entity_id" name="entity_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="media_photo">Photo</label>
                            <input id="media_photo" name="photo" class="ops-input" type="file" accept="image/*" required>
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="media_alt_text">Alt Text</label>
                            <input id="media_alt_text" name="alt_text" class="ops-input" type="text" maxlength="190" placeholder="Describe this photo" required>
                        </div>
                        <div class="ops-field">
                            <label for="media_is_primary">Primary Photo</label>
                            <select id="media_is_primary" name="is_primary" class="ops-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                    <p class="standards-note">Image standard: JPG/PNG/WebP, minimum 1200x800px, descriptive alt text required.</p>
                    <button class="btn btn-primary" type="submit">Upload Photo</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor media table">
                        <thead>
                            <tr>
                                <th>Entity</th>
                                <th>Path</th>
                                <th>Dimensions</th>
                                <th>Quality</th>
                                <th>Alt Text</th>
                                <th>Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorMediaAssets->take(15) as $media)
                                <tr>
                                    <td>{{ strtoupper((string) $media->entity_type) }} #{{ $media->entity_id ?: 'N/A' }}</td>
                                    <td class="media-thumb">{{ $media->file_path }}</td>
                                    <td>{{ ($media->width_px ?? '-') }} x {{ ($media->height_px ?? '-') }}</td>
                                    <td>{{ $media->quality_grade ?? 'N/A' }}</td>
                                    <td>{{ $media->alt_text ?: 'N/A' }}</td>
                                    <td>{{ $media->created_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No media uploads yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorServicesSection" class="card ops-section" aria-label="Vendor services">
            <div class="ops-header">
                <p class="ops-title">Services Catalog</p>
                <span class="ops-chip">{{ $vendorServices->count() }} total</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/services/create">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="service_listing_category">Listing Category</label>
                            <select id="service_listing_category" name="listing_category" class="ops-select" required>
                                @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                                    <option value="{{ $categoryKey }}" @disabled(!in_array($categoryKey, $selectedVendorCategories, true))>{{ $categoryLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="service_name">Service Name</label>
                            <input id="service_name" name="name" class="ops-input" type="text" maxlength="160" required>
                        </div>
                        <div class="ops-field">
                            <label for="service_category">Category</label>
                            <input id="service_category" name="category" class="ops-input" type="text" maxlength="120" required>
                        </div>
                        <div class="ops-field">
                            <label for="service_price">Price (MVR)</label>
                            <input id="service_price" name="price" class="ops-input" type="number" min="0" step="0.01" required>
                        </div>
                        <div class="ops-field">
                            <label for="service_duration">Duration (minutes)</label>
                            <input id="service_duration" name="duration_minutes" class="ops-input" type="number" min="0" max="100000">
                        </div>
                        <div class="ops-field">
                            <label for="service_property_id">Property ID (optional)</label>
                            <input id="service_property_id" name="property_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="service_description">Description</label>
                            <textarea id="service_description" name="description" class="ops-textarea" maxlength="3000"></textarea>
                        </div>
                        <div class="ops-field">
                            <label for="service_measurement_system">Measurement System</label>
                            <select id="service_measurement_system" name="measurement_system" class="ops-select">
                                <option value="metric">Metric</option>
                                <option value="imperial">Imperial</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="service_lead_time">Lead Time (minutes)</label>
                            <input id="service_lead_time" name="lead_time_minutes" class="ops-input" type="number" min="0" max="43200" placeholder="e.g. 120">
                        </div>
                        <div class="ops-field">
                            <label for="service_min_booking">Min Booking Size</label>
                            <input id="service_min_booking" name="min_booking_size" class="ops-input" type="number" min="1" max="10000">
                        </div>
                        <div class="ops-field">
                            <label for="service_max_booking">Max Booking Size</label>
                            <input id="service_max_booking" name="max_booking_size" class="ops-input" type="number" min="1" max="10000">
                        </div>
                        <div class="ops-field">
                            <label for="service_quantity_unit">Quantity Unit</label>
                            <select id="service_quantity_unit" name="quantity_unit" class="ops-select">
                                <option value="seat">Seat</option>
                                <option value="room">Room</option>
                                <option value="desk">Desk</option>
                                <option value="vehicle">Vehicle</option>
                                <option value="ticket">Ticket</option>
                                <option value="table">Table</option>
                                <option value="pass">Pass</option>
                            </select>
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="service_compliance_notes">Compliance Notes</label>
                            <textarea id="service_compliance_notes" name="compliance_notes" class="ops-textarea" maxlength="2000" placeholder="Operational and safety constraints, policy references, legal requirements"></textarea>
                        </div>
                    </div>
                    <p class="standards-note">International listing standard: include lead times and capacity boundaries to reduce booking failures.</p>
                    <button class="btn btn-primary" type="submit">Add Service</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor services table">
                        <thead>
                            <tr>
                                <th>Listing</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorServices->take(12) as $service)
                                <tr>
                                    <td>{{ strtoupper((string) ($service->listing_category ?? 'N/A')) }}</td>
                                    <td>{{ $service->name }}</td>
                                    <td>{{ $service->category }}</td>
                                    <td>{{ (int) $service->duration_minutes }} min</td>
                                    <td>{{ $service->currency }} {{ number_format((float) $service->price, 2) }}</td>
                                    <td>{{ $service->is_active ? 'YES' : 'NO' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No services yet. Add one to start taking reservations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorAvailabilitySection" class="card ops-section" aria-label="Vendor availability calendar">
            <div class="ops-header">
                <p class="ops-title">Availability Calendar</p>
                <span class="ops-chip">{{ $vendorAvailability->count() }} days tracked</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/availability/save">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="availability_date">Date</label>
                            <input id="availability_date" name="slot_date" class="ops-input" type="date" required>
                        </div>
                        <div class="ops-field">
                            <label for="availability_inventory">Inventory</label>
                            <input id="availability_inventory" name="inventory" class="ops-input" type="number" min="0" max="100000" required>
                        </div>
                        <div class="ops-field">
                            <label for="availability_property">Property ID (optional)</label>
                            <input id="availability_property" name="vendor_property_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field">
                            <label for="availability_closed">Closed Day</label>
                            <select id="availability_closed" name="is_closed" class="ops-select">
                                <option value="0">Open</option>
                                <option value="1">Closed</option>
                            </select>
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="availability_notes">Notes</label>
                            <textarea id="availability_notes" name="notes" class="ops-textarea" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Save Availability</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor availability table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Inventory</th>
                                <th>Reserved</th>
                                <th>Closed</th>
                                <th>Property</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorAvailability->take(20) as $slot)
                                <tr>
                                    <td>{{ $slot->slot_date }}</td>
                                    <td>{{ (int) $slot->inventory }}</td>
                                    <td>{{ (int) $slot->reserved_count }}</td>
                                    <td>{{ $slot->is_closed ? 'YES' : 'NO' }}</td>
                                    <td>{{ $slot->vendor_property_id ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No availability slots yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorReservationsSection" class="card ops-section" aria-label="Vendor reservations">
            <div class="ops-header">
                <p class="ops-title">Reservations</p>
                <span class="ops-chip">{{ $vendorReservations->count() }} total</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/reservations/create">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="reservation_customer_name">Customer Name</label>
                            <input id="reservation_customer_name" name="customer_name" class="ops-input" type="text" maxlength="160" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_customer_email">Customer Email</label>
                            <input id="reservation_customer_email" name="customer_email" class="ops-input" type="email" maxlength="190" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_start_at">Start</label>
                            <input id="reservation_start_at" name="start_at" class="ops-input" type="datetime-local" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_end_at">End</label>
                            <input id="reservation_end_at" name="end_at" class="ops-input" type="datetime-local" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_guests">Guests</label>
                            <input id="reservation_guests" name="guests" class="ops-input" type="number" min="1" max="10000" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_guest_origin">Guest Type</label>
                            <select id="reservation_guest_origin" name="guest_is_foreigner" class="ops-select" required>
                                <option value="1">Foreigner</option>
                                <option value="0">Local</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_total_amount">Total Amount (MVR)</label>
                            <input id="reservation_total_amount" name="total_amount" class="ops-input" type="number" min="0" step="0.01" required>
                        </div>
                        <div class="ops-field">
                            <label for="reservation_property_id">Property ID (optional)</label>
                            <input id="reservation_property_id" name="vendor_property_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field">
                            <label for="reservation_service_id">Service ID (optional)</label>
                            <input id="reservation_service_id" name="vendor_service_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field ops-field-wide">
                            <label for="reservation_notes">Notes</label>
                            <textarea id="reservation_notes" name="notes" class="ops-textarea" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <p class="standards-note">Accommodation invoice charges: Service Charge 10% + Foreigner taxes (Green Tax $12/person for 50+ rooms, else $6/person and TGST 17%) or Local tax (CGST 8%).</p>
                    <button class="btn btn-primary" type="submit">Create Reservation</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor reservations table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Dates</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorReservations->take(12) as $reservation)
                                <tr>
                                    <td>
                                        {{ $reservation->customer_name }}<br>
                                        {{ $reservation->customer_email }}
                                    </td>
                                    <td>{{ $reservation->start_at }}<br>{{ $reservation->end_at }}</td>
                                    <td>
                                        Base: {{ $reservation->currency }} {{ number_format((float) ($reservation->subtotal_amount ?? $reservation->total_amount), 2) }}<br>
                                        Service Charge: {{ $reservation->currency }} {{ number_format((float) ($reservation->service_charge_total ?? 0), 2) }}<br>
                                        Taxes: {{ $reservation->currency }} {{ number_format((float) ($reservation->total_tax_amount ?? 0), 2) }}<br>
                                        Total: {{ $reservation->currency }} {{ number_format((float) ($reservation->invoice_total_amount ?? $reservation->total_amount), 2) }}<br>
                                        <span class="small">
                                            Service {{ number_format((float) ($reservation->service_charge_total ?? 0), 2) }} |
                                            Green {{ number_format((float) ($reservation->green_tax_total ?? 0), 2) }} |
                                            TGST {{ number_format((float) ($reservation->tgst_total ?? 0), 2) }} |
                                            CGST {{ number_format((float) ($reservation->cgst_total ?? 0), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <form class="inline-status-form" method="POST" action="/portal/vendor/reservations/{{ $reservation->id }}/status">
                                            @csrf
                                            <select class="ops-select" name="status" required>
                                                <option value="pending" @selected($reservation->status === 'pending')>Pending</option>
                                                <option value="confirmed" @selected($reservation->status === 'confirmed')>Confirmed</option>
                                                <option value="cancelled" @selected($reservation->status === 'cancelled')>Cancelled</option>
                                                <option value="completed" @selected($reservation->status === 'completed')>Completed</option>
                                            </select>
                                            <select class="ops-select" name="payment_status" required>
                                                <option value="unpaid" @selected($reservation->payment_status === 'unpaid')>Unpaid</option>
                                                <option value="partially_paid" @selected($reservation->payment_status === 'partially_paid')>Partially Paid</option>
                                                <option value="paid" @selected($reservation->payment_status === 'paid')>Paid</option>
                                                <option value="refunded" @selected($reservation->payment_status === 'refunded')>Refunded</option>
                                            </select>
                                            <button class="btn btn-secondary" type="submit">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="ops-empty">No reservations yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorPricingSection" class="card ops-section" aria-label="Vendor pricing rules">
            <div class="ops-header">
                <p class="ops-title">Pricing Rules</p>
                <span class="ops-chip">{{ $vendorPricingRules->count() }} active + historical</span>
            </div>
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/pricing/create">
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="pricing_name">Rule Name</label>
                            <input id="pricing_name" name="name" class="ops-input" type="text" maxlength="160" required>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_type">Rule Type</label>
                            <select id="pricing_type" name="rule_type" class="ops-select" required>
                                <option value="flat">Flat</option>
                                <option value="percent">Percent</option>
                                <option value="nightly">Nightly</option>
                                <option value="weekend_markup">Weekend Markup</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_value">Value</label>
                            <input id="pricing_value" name="value" class="ops-input" type="number" min="0" step="0.01" required>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_starts">Starts On</label>
                            <input id="pricing_starts" name="starts_on" class="ops-input" type="date">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_ends">Ends On</label>
                            <input id="pricing_ends" name="ends_on" class="ops-input" type="date">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_property_id">Property ID (optional)</label>
                            <input id="pricing_property_id" name="vendor_property_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_service_id">Service ID (optional)</label>
                            <input id="pricing_service_id" name="vendor_service_id" class="ops-input" type="number" min="1">
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Save Pricing Rule</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor pricing rules table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Window</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorPricingRules->take(12) as $rule)
                                <tr>
                                    <td>{{ $rule->name }}</td>
                                    <td>{{ strtoupper((string) $rule->rule_type) }}</td>
                                    <td>{{ number_format((float) $rule->value, 2) }}</td>
                                    <td>{{ $rule->starts_on ?: '-' }} to {{ $rule->ends_on ?: '-' }}</td>
                                    <td>{{ $rule->is_active ? 'YES' : 'NO' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No pricing rules yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorBillingSection" class="card ops-section" aria-label="Vendor billing details">
            <div class="ops-header">
                <p class="ops-title">Billing Details</p>
                <span class="ops-chip">{{ $vendorBilling ? 'Configured' : 'Pending' }}</span>
            </div>
            <form class="ops-form" method="POST" action="/portal/vendor/billing/update">
                @csrf
                <div class="ops-form-grid">
                    <div class="ops-field">
                        <label for="billing_business_name">Business Name</label>
                        <input id="billing_business_name" name="business_name" class="ops-input" type="text" maxlength="190" value="{{ old('business_name', optional($vendorBilling)->business_name ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_tax_id">Tax ID</label>
                        <input id="billing_tax_id" name="tax_id" class="ops-input" type="text" maxlength="120" value="{{ old('tax_id', optional($vendorBilling)->tax_id ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_email">Billing Email</label>
                        <input id="billing_email" name="billing_email" class="ops-input" type="email" maxlength="190" value="{{ old('billing_email', optional($vendorBilling)->billing_email ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_payout_method">Payout Method</label>
                        <select id="billing_payout_method" name="payout_method" class="ops-select" required>
                            <option value="bank_transfer" @selected((optional($vendorBilling)->payout_method ?? '') === 'bank_transfer')>Bank Transfer</option>
                            <option value="mobile_wallet" @selected((optional($vendorBilling)->payout_method ?? '') === 'mobile_wallet')>Mobile Wallet</option>
                            <option value="manual" @selected((optional($vendorBilling)->payout_method ?? '') === 'manual')>Manual</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_payout_reference">Payout Reference</label>
                        <input id="billing_payout_reference" name="payout_reference" class="ops-input" type="text" maxlength="190" value="{{ old('payout_reference', optional($vendorBilling)->payout_reference ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_bank_name">Bank Name</label>
                        <input id="billing_bank_name" name="bank_name" class="ops-input" type="text" maxlength="190" value="{{ old('bank_name', optional($vendorBilling)->bank_name ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_bank_last4">Bank Account Last 4</label>
                        <input id="billing_bank_last4" name="bank_account_last4" class="ops-input" type="text" maxlength="8" value="{{ old('bank_account_last4', optional($vendorBilling)->bank_account_last4 ?? '') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_currency">Currency</label>
                        <input id="billing_currency" name="currency" class="ops-input" type="text" maxlength="8" value="{{ old('currency', optional($vendorBilling)->currency ?? 'MVR') }}">
                    </div>
                    <div class="ops-field">
                        <label for="billing_invoice_prefix">Invoice Prefix</label>
                        <input id="billing_invoice_prefix" name="invoice_prefix" class="ops-input" type="text" maxlength="30" value="{{ old('invoice_prefix', optional($vendorBilling)->invoice_prefix ?? 'INV') }}">
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="billing_address">Billing Address</label>
                        <textarea id="billing_address" name="billing_address" class="ops-textarea" maxlength="2000">{{ old('billing_address', optional($vendorBilling)->billing_address ?? '') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save Billing Details</button>
            </form>

            <div id="vendorDailyCollectionSection" class="ops-section" aria-label="Vendor daily collection and settlements">
                <div class="ops-header">
                    <p class="ops-title">Daily Collection and Payout Ledger</p>
                    <span class="ops-chip">Commission {{ (int) ($commissionRate * 100) }}%</span>
                </div>

                <div class="billing-ledger-grid">
                    <article class="billing-ledger-card">
                        <p class="metric-label">Gross Collection</p>
                        <p class="metric-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Workation Commission</p>
                        <p class="metric-value">MVR {{ number_format($commissionTotal, 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Net Payout</p>
                        <p class="metric-value">MVR {{ number_format($payoutTotal, 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Settled Invoices</p>
                        <p class="metric-value">{{ $settledInvoicesCount }}</p>
                    </article>
                </div>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor daily collection table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Gross</th>
                                <th>Commission</th>
                                <th>Payout</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyCollection as $day => $daily)
                                <tr>
                                    <td>{{ $day }}</td>
                                    <td>{{ $daily['count'] }}</td>
                                    <td>MVR {{ number_format((float) $daily['gross'], 2) }}</td>
                                    <td>MVR {{ number_format((float) $daily['commission'], 2) }}</td>
                                    <td>MVR {{ number_format((float) $daily['payout'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No collection data yet. Add reservations to populate this section.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor invoice settlement ledger">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Collected From</th>
                                <th>Status</th>
                                <th>Gross</th>
                                <th>Commission</th>
                                <th>Payout</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($billingLedgerRows->take(20) as $entry)
                                <tr>
                                    <td>{{ $entry['invoice_ref'] }}<br>{{ $entry['collection_day'] }}</td>
                                    <td>{{ $entry['customer_name'] }}<br>{{ $entry['customer_email'] ?: 'N/A' }}</td>
                                    <td>{{ strtoupper($entry['booking_status']) }} / {{ strtoupper($entry['payment_status']) }}<br>{{ $entry['is_settled'] ? 'SETTLED' : 'PENDING' }}</td>
                                    <td>{{ $entry['currency'] }} {{ number_format((float) $entry['gross'], 2) }}</td>
                                    <td>{{ $entry['currency'] }} {{ number_format((float) $entry['commission'], 2) }}</td>
                                    <td>{{ $entry['currency'] }} {{ number_format((float) $entry['payout'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No invoice ledger data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="layout" id="vendorAuthApi">
            <article class="card" id="vendorAuthCard">
                <p class="label">Auth</p>
                <input id="tokenInput" class="token-input" type="password" placeholder="Paste vendor JWT bearer token">
                <div>
                    <button id="saveToken" class="btn btn-primary" type="button">Save Token</button>
                    <button id="clearToken" class="btn btn-secondary" type="button">Clear</button>
                </div>
                <div id="tokenState" class="state warn">TOKEN NOT SET</div>
                <div id="tokenMeta" class="token-meta">Token is stored only in this browser tab session.</div>
            </article>

            <article class="card" id="vendorApiCard">
                <p class="label">Vendor API Actions</p>
                <div class="endpoint">
                    <code>GET /api/v1/auth/me</code>
                    <button type="button" data-path="/api/v1/auth/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/bookings</code>
                    <button type="button" data-path="/api/v1/bookings">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/loyalty/me</code>
                    <button type="button" data-path="/api/v1/loyalty/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/vendor/me/settlements/report</code>
                    <button type="button" data-path="/api/v1/payments/vendor/me/settlements/report">Run</button>
                </div>
                <pre id="output">Ready. Save token, then run an endpoint.</pre>
            </article>
        </section>

        <footer class="support-links support-footer" aria-label="Global support links">
            <a href="/terms-of-service">Terms of Service</a>
            <a href="/privacy-policy">Privacy Policy</a>
            <a href="mailto:support@workation.mv">Email Support</a>
            <a href="{{ $apiBase }}/api/v1/ops/runbooks" target="_blank" rel="noopener">Operations Runbooks</a>
        </footer>
        </div>
        </div>
    </main>

    <script>
        (function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const tokenMeta = document.getElementById("tokenMeta");
            const output = document.getElementById("output");
            const summaryBookings = document.getElementById("summaryBookings");
            const summarySettlements = document.getElementById("summarySettlements");
            const summaryToken = document.getElementById("summaryToken");
            const summaryTokenMeta = document.getElementById("summaryTokenMeta");
            const summaryConnectivity = document.getElementById("summaryConnectivity");
            const summaryLastSync = document.getElementById("summaryLastSync");
            const refreshSummaryBtn = document.getElementById("refreshSummary");
            const payoutSettledTotal = document.getElementById("payoutSettledTotal");
            const payoutPendingTotal = document.getElementById("payoutPendingTotal");
            const payoutNextEstimate = document.getElementById("payoutNextEstimate");
            const payoutRows = document.getElementById("payoutRows");

            const SESSION_KEY = "workation_vendor_token";

            function setState(type, text) {
                tokenState.className = "state " + type;
                tokenState.textContent = text;
            }

            function setMeta(text) {
                if (tokenMeta) {
                    tokenMeta.textContent = text;
                }
            }

            function getToken() {
                return sessionStorage.getItem(SESSION_KEY) || "";
            }

            function decodeBase64Url(value) {
                try {
                    const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
                    const padded = normalized + "=".repeat((4 - (normalized.length % 4)) % 4);
                    return atob(padded);
                } catch (error) {
                    return "";
                }
            }

            function parseJwtPayload(token) {
                const parts = String(token || "").split(".");
                if (parts.length !== 3) {
                    return null;
                }
                const payloadRaw = decodeBase64Url(parts[1]);
                if (!payloadRaw) {
                    return null;
                }
                try {
                    return JSON.parse(payloadRaw);
                } catch (error) {
                    return null;
                }
            }

            function formatDuration(seconds) {
                const total = Math.max(0, Math.floor(seconds));
                const hours = Math.floor(total / 3600);
                const minutes = Math.floor((total % 3600) / 60);
                if (hours > 0) {
                    return hours + "h " + minutes + "m";
                }
                return minutes + "m";
            }

            function formatDateTime(epochSeconds) {
                return new Date(epochSeconds * 1000).toLocaleString();
            }

            function evaluateToken(token) {
                const payload = parseJwtPayload(token);
                if (!payload) {
                    return {
                        isValidFormat: false,
                        isUsable: false,
                        stateType: "err",
                        stateText: "INVALID TOKEN FORMAT",
                        metaText: "Expected a JWT with 3 parts: header.payload.signature"
                    };
                }

                const exp = Number(payload.exp);
                if (!Number.isFinite(exp)) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN SAVED (NO EXP)",
                        metaText: "No expiration claim found. Token expiry cannot be predicted."
                    };
                }

                const now = Math.floor(Date.now() / 1000);
                const secondsLeft = exp - now;
                const expiresAt = formatDateTime(exp);
                if (secondsLeft <= 0) {
                    return {
                        isValidFormat: true,
                        isUsable: false,
                        stateType: "err",
                        stateText: "TOKEN EXPIRED",
                        metaText: "Expired at " + expiresAt + ". Save a fresh token."
                    };
                }

                if (secondsLeft <= 5 * 60) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN EXPIRING SOON",
                        metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                    };
                }

                return {
                    isValidFormat: true,
                    isUsable: true,
                    stateType: "ok",
                    stateText: "TOKEN READY",
                    metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                };
            }

            function applyTokenFeedback(token, fallbackType, fallbackStateText, fallbackMetaText) {
                if (!token) {
                    setState(fallbackType || "warn", fallbackStateText || "TOKEN NOT SET");
                    setMeta(fallbackMetaText || "Token is stored only in this browser tab session.");
                    return;
                }

                const verdict = evaluateToken(token);
                setState(verdict.stateType, verdict.stateText);
                setMeta(verdict.metaText);
            }

            function saveToken() {
                const value = (tokenInput.value || "").trim();
                if (!value) {
                    setState("warn", "TOKEN NOT SET");
                    setMeta("Paste a JWT token to continue.");
                    return;
                }

                const verdict = evaluateToken(value);
                if (!verdict.isValidFormat || !verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    return;
                }

                sessionStorage.setItem(SESSION_KEY, value);
                tokenInput.value = "";
                applyTokenFeedback(value, "ok", "TOKEN SAVED");
                refreshSummary();
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
                setMeta("Token removed from this tab session.");
                setSummaryDefaults();
            }

            function setSummaryDefaults() {
                if (summaryBookings) summaryBookings.textContent = "-";
                if (summarySettlements) summarySettlements.textContent = "-";
                if (summaryToken) summaryToken.textContent = "N/A";
                if (summaryTokenMeta) summaryTokenMeta.textContent = "Save token to evaluate readiness";
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill warn";
                    summaryConnectivity.textContent = "UNKNOWN";
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: not run yet";
                }

                if (payoutSettledTotal) payoutSettledTotal.textContent = "MVR 0.00";
                if (payoutPendingTotal) payoutPendingTotal.textContent = "MVR 0.00";
                if (payoutNextEstimate) payoutNextEstimate.textContent = "N/A";
                if (payoutRows) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">Refresh summary to load payout data.</td></tr>';
                }
            }

            function formatCurrency(value) {
                const amount = Number(value);
                if (!Number.isFinite(amount)) {
                    return "MVR 0.00";
                }
                return "MVR " + amount.toFixed(2);
            }

            function normalizeSettlementRows(payload) {
                if (Array.isArray(payload)) return payload;
                if (payload && Array.isArray(payload.data)) return payload.data;
                if (payload && Array.isArray(payload.items)) return payload.items;
                return [];
            }

            function extractAmount(row) {
                const candidates = [row && row.amount, row && row.net_amount, row && row.total, row && row.value];
                for (const value of candidates) {
                    const parsed = Number(value);
                    if (Number.isFinite(parsed)) {
                        return parsed;
                    }
                }
                return 0;
            }

            function toRowStatus(row) {
                const raw = String((row && (row.status || row.state)) || "").trim();
                return raw === "" ? "UNKNOWN" : raw.toUpperCase();
            }

            function toRowReference(row, index) {
                return String((row && (row.reference || row.settlement_id || row.id || row.code)) || "SETTLEMENT-" + (index + 1));
            }

            function toRowDate(row) {
                const raw = String((row && (row.paid_at || row.created_at || row.date)) || "").trim();
                if (!raw) return "N/A";
                const date = new Date(raw);
                if (Number.isNaN(date.getTime())) return raw;
                return date.toLocaleDateString();
            }

            function renderPayoutCenter(payload) {
                const rows = normalizeSettlementRows(payload);
                let settledTotal = 0;
                let pendingTotal = 0;

                rows.forEach((row) => {
                    const amount = extractAmount(row);
                    const status = toRowStatus(row);
                    if (status.includes("SETTLED") || status.includes("PAID") || status.includes("COMPLETED")) {
                        settledTotal += amount;
                    } else {
                        pendingTotal += amount;
                    }
                });

                if (payoutSettledTotal) payoutSettledTotal.textContent = formatCurrency(settledTotal);
                if (payoutPendingTotal) payoutPendingTotal.textContent = formatCurrency(pendingTotal);

                const nextEstimateDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000);
                if (payoutNextEstimate) {
                    payoutNextEstimate.textContent = rows.length === 0
                        ? "N/A"
                        : nextEstimateDate.toLocaleDateString();
                }

                if (!payoutRows) return;
                if (rows.length === 0) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">No settlements returned for this token yet.</td></tr>';
                    return;
                }

                payoutRows.innerHTML = rows.slice(0, 8).map((row, index) => {
                    const reference = toRowReference(row, index);
                    const status = toRowStatus(row);
                    const amount = formatCurrency(extractAmount(row));
                    const date = toRowDate(row);
                    return '<tr><td>' + reference + '</td><td>' + status + '</td><td>' + amount + '</td><td>' + date + '</td></tr>';
                }).join('');
            }

            async function fetchJsonWithAuth(path, token) {
                const res = await fetch(apiBase + path, {
                    method: "GET",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                const bodyText = await res.text();
                let json = null;
                try {
                    json = JSON.parse(bodyText);
                } catch (error) {
                    json = null;
                }

                return { ok: res.ok, status: res.status, json: json, text: bodyText };
            }

            function deriveCount(payload) {
                if (Array.isArray(payload)) {
                    return payload.length;
                }
                if (payload && Array.isArray(payload.data)) {
                    return payload.data.length;
                }
                if (payload && Array.isArray(payload.items)) {
                    return payload.items.length;
                }
                if (payload && Number.isFinite(Number(payload.total))) {
                    return Number(payload.total);
                }
                return null;
            }

            function setConnectivity(type, label, lastSyncText) {
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill " + type;
                    summaryConnectivity.textContent = label;
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: " + lastSyncText;
                }
            }

            async function refreshSummary() {
                const token = getToken();
                if (!token) {
                    setSummaryDefaults();
                    return;
                }

                if (refreshSummaryBtn) refreshSummaryBtn.disabled = true;

                const verdict = evaluateToken(token);
                if (summaryToken) {
                    summaryToken.textContent = verdict.stateText.replace("TOKEN ", "");
                }
                if (summaryTokenMeta) {
                    summaryTokenMeta.textContent = verdict.metaText;
                }

                try {
                    const [bookingsResult, settlementsResult] = await Promise.all([
                        fetchJsonWithAuth("/api/v1/bookings", token),
                        fetchJsonWithAuth("/api/v1/payments/vendor/me/settlements/report", token),
                    ]);

                    const bookingsCount = deriveCount(bookingsResult.json);
                    const settlementsCount = deriveCount(settlementsResult.json);
                    if (summaryBookings) {
                        summaryBookings.textContent = bookingsCount === null ? "N/A" : String(bookingsCount);
                    }
                    if (summarySettlements) {
                        summarySettlements.textContent = settlementsCount === null ? "N/A" : String(settlementsCount);
                    }

                    renderPayoutCenter(settlementsResult.json);

                    const nowText = new Date().toLocaleString();
                    if (bookingsResult.ok || settlementsResult.ok) {
                        setConnectivity("ok", "ONLINE", nowText);
                    } else if (bookingsResult.status === 401 || bookingsResult.status === 403 || settlementsResult.status === 401 || settlementsResult.status === 403) {
                        setConnectivity("warn", "AUTH ISSUE", nowText);
                    } else {
                        setConnectivity("err", "OFFLINE", nowText);
                    }
                } catch (error) {
                    setConnectivity("err", "OFFLINE", new Date().toLocaleString());
                    if (summaryBookings) summaryBookings.textContent = "N/A";
                    if (summarySettlements) summarySettlements.textContent = "N/A";
                } finally {
                    if (refreshSummaryBtn) refreshSummaryBtn.disabled = false;
                }
            }

            async function run(path, triggerButton) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    setMeta("Save a vendor token before running requests.");
                    output.textContent = "Save a vendor token first.";
                    return;
                }

                const verdict = evaluateToken(token);
                if (!verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    output.textContent = "Token is expired or invalid. Save a fresh vendor token first.";
                    return;
                }

                const button = triggerButton || null;
                if (button) {
                    button.disabled = true;
                    button.classList.add("is-loading");
                    if (!button.dataset.label) {
                        button.dataset.label = button.textContent || "Run";
                    }
                    button.textContent = "Running";
                }

                output.textContent = "Loading " + path + " ...";
                try {
                    const res = await fetch(apiBase + path, {
                        method: "GET",
                        headers: {
                            "Authorization": "Bearer " + token,
                            "Accept": "application/json"
                        },
                        cache: "no-store"
                    });
                    const text = await res.text();
                    let parsed = text;
                    try {
                        parsed = JSON.stringify(JSON.parse(text), null, 2);
                    } catch (error) {
                        // Keep plain text if response is not JSON.
                    }
                    output.textContent = "Status: " + res.status + "\n\n" + parsed;
                    if (res.ok) {
                        applyTokenFeedback(token, "ok", "TOKEN VALID");
                    } else if (res.status === 401 || res.status === 403) {
                        setState("err", "TOKEN INVALID FOR VENDOR");
                        setMeta("The API rejected this token for vendor access.");
                    } else {
                        applyTokenFeedback(token, "warn", "REQUEST COMPLETED WITH WARNINGS");
                    }
                } catch (error) {
                    setState("err", "REQUEST FAILED");
                    setMeta("Request failed before token validation could complete.");
                    output.textContent = "Network/CORS error. Ensure API allows origin https://www.workation.mv\n\n" + String(error);
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove("is-loading");
                        button.textContent = button.dataset.label || "Run";
                    }
                }
            }

            document.getElementById("saveToken").addEventListener("click", saveToken);
            document.getElementById("clearToken").addEventListener("click", clearToken);
            if (refreshSummaryBtn) {
                refreshSummaryBtn.addEventListener("click", refreshSummary);
            }
            tokenInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    saveToken();
                }
            });
            document.querySelectorAll("button[data-path]").forEach((button) => {
                button.addEventListener("click", function () {
                    run(button.getAttribute("data-path"), button);
                });
            });

            setInterval(function () {
                const token = getToken();
                if (token) {
                    applyTokenFeedback(token);
                }
            }, 60000);

            if (getToken()) {
                applyTokenFeedback(getToken());
                refreshSummary();
            } else {
                setMeta("Token is stored only in this browser tab session.");
                setSummaryDefaults();
            }
        })();
    </script>
</body>
</html>

