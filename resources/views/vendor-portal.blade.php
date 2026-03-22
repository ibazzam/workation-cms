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

        .portal-nav {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .portal-nav a {
            text-decoration: none;
            border: 1px solid #c8d4df;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1f4a53;
            background: #f4faf8;
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

        .inline-status-form {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .inline-status-form .btn {
            margin-top: 0;
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

            .portal-nav {
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 640px) {
            .ops-metrics {
                grid-template-columns: 1fr 1fr;
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

        <nav class="portal-nav" aria-label="Vendor navigation">
            <a href="#vendorSummary">Summary</a>
            <a href="#payoutCenter">Payout Center</a>
            <a href="#vendorProfileCard">Profile</a>
            <a href="#vendorOperationsOverview">Operations</a>
            <a href="#vendorPropertiesSection">Properties</a>
            <a href="#vendorServicesSection">Services</a>
            <a href="#vendorAvailabilitySection">Availability</a>
            <a href="#vendorReservationsSection">Reservations</a>
            <a href="#vendorPricingSection">Pricing</a>
            <a href="#vendorBillingSection">Billing</a>
            <a href="#vendorAuthApi">Auth and API</a>
            <a href="#vendorAuthCard">Token</a>
            <a href="#vendorApiCard">API Actions</a>
        </nav>

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

        <section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview">
            <div class="ops-header">
                <p class="ops-title">Operations Console</p>
                <span class="ops-chip">Database-backed</span>
            </div>
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
                    </div>
                    <button class="btn btn-primary" type="submit">Add Listing</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor properties table">
                        <thead>
                            <tr>
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
                                    <td>{{ $property->name }}</td>
                                    <td>{{ strtoupper((string) $property->property_type) }}</td>
                                    <td>{{ $property->location ?: 'N/A' }}</td>
                                    <td>{{ $property->currency }} {{ number_format((float) $property->base_price, 2) }}</td>
                                    <td>{{ strtoupper((string) $property->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No properties yet. Add your first listing.</td>
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
                    </div>
                    <button class="btn btn-primary" type="submit">Add Service</button>
                </form>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor services table">
                        <thead>
                            <tr>
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
                                    <td>{{ $service->name }}</td>
                                    <td>{{ $service->category }}</td>
                                    <td>{{ (int) $service->duration_minutes }} min</td>
                                    <td>{{ $service->currency }} {{ number_format((float) $service->price, 2) }}</td>
                                    <td>{{ $service->is_active ? 'YES' : 'NO' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No services yet. Add one to start taking reservations.</td>
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
                                    <td>{{ $reservation->currency }} {{ number_format((float) $reservation->total_amount, 2) }}</td>
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

