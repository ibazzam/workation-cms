<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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

        .portal-nav a.is-active {
            border-color: #0f6b74;
            background: #e8f7f8;
            color: #0d4f56;
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

        .progress-snapshot {
            margin-top: 12px;
        }

        .progress-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .progress-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            padding: 12px;
        }

        .progress-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .progress-value {
            margin: 6px 0 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f3346;
        }

        .progress-meta {
            margin: 6px 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        [data-panel-group][hidden] {
            display: none !important;
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

        .panel-links {
            margin: 0 0 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .panel-links a {
            text-decoration: none;
            border: 1px solid #c8d4df;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #21475c;
            background: #f8fbff;
        }

        .feature-checklist {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 6px;
        }

        .feature-item {
            border: 1px solid #d7e0e6;
            border-radius: 8px;
            padding: 8px 9px;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.82rem;
            color: #223b51;
        }

        .location-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .map-picker {
            margin-top: 8px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            overflow: hidden;
        }

        #propertyMap {
            width: 100%;
            height: 260px;
            background: #eef4f9;
        }

        .map-help {
            margin-top: 6px;
            font-size: 0.78rem;
            color: #4b6075;
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

        .listing-category-shortcuts {
            margin: 8px 0 10px;
            display: grid;
            gap: 8px;
        }

        .listing-category-shortcuts-head {
            font-size: 0.8rem;
            color: #38526a;
            font-weight: 700;
        }

        .listing-category-shortcuts-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .listing-category-shortcuts .btn {
            margin-top: 0;
        }

        .listing-category-shortcuts .btn.is-active {
            border-color: #0f6b74;
            background: #e8f7f8;
            color: #0d4f56;
        }

        .category-scope-note {
            margin: 6px 0 0;
            font-size: 0.78rem;
            color: #446079;
        }

        .form-toggle-row {
            margin: 10px 0;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .wizard-progress {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .wizard-progress-step {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            font-size: 0.78rem;
            color: #4b6075;
        }

        .wizard-progress-step strong {
            display: block;
            color: #1f3346;
            margin-bottom: 2px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.74rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .wizard-progress-step.is-active {
            border-color: #0f6d5f;
            background: #e8f5ef;
        }

        .wizard-progress-step.is-complete {
            border-color: #77bfa2;
            background: #f2faf6;
        }

        .guided-wizard {
            margin-top: 12px;
            border: 1px solid #d7e0e6;
            border-radius: 14px;
            background: linear-gradient(180deg, #f9fcff 0%, #ffffff 100%);
            padding: 14px;
        }

        .guided-wizard-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .guided-wizard-title {
            margin: 0;
            font-size: 1rem;
            color: #1f3346;
            font-weight: 700;
        }

        .guided-wizard-subtitle {
            margin: 4px 0 0;
            font-size: 0.82rem;
            color: #5b6778;
        }

        .guided-track-toggle {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .guided-track-toggle .btn {
            white-space: nowrap;
        }

        .guided-track-toggle .btn.is-active {
            border-color: #0f6b74;
            background: #e8f7f8;
            color: #0d4f56;
        }

        .guided-progress-wrap {
            margin-top: 12px;
        }

        .guided-progress-rail {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: #e6edf3;
            overflow: hidden;
        }

        .guided-progress-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #0f6b74 0%, #34a272 100%);
            transition: width 220ms ease;
        }

        .guided-step-text {
            margin-top: 8px;
            font-size: 0.84rem;
            color: #38526a;
            font-weight: 600;
        }

        .guided-steps {
            margin: 10px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .guided-step {
            border: 1px solid #d4dce5;
            border-radius: 10px;
            background: #ffffff;
            color: #35536e;
            font-size: 0.76rem;
            font-weight: 600;
            line-height: 1.35;
            padding: 9px;
            text-align: left;
            cursor: pointer;
        }

        .guided-step.is-active {
            border-color: #0f6b74;
            box-shadow: 0 0 0 1px #0f6b74 inset;
            background: #eff9fa;
            color: #0d4f56;
        }

        .guided-step.is-complete {
            border-color: #6fb78e;
            background: #eef8f1;
            color: #215336;
        }

        .guided-actions {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .properties-grid {
            grid-template-columns: 1fr;
        }

        .inline-table-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 6px;
            align-items: end;
        }

        .ops-table td {
            vertical-align: top;
        }

        .listing-cell-actions {
            display: grid;
            gap: 8px;
        }

        .update-row-form,
        .media-upload-row {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fafcff;
            padding: 8px;
        }

        .update-row-form .btn,
        .media-upload-row .btn,
        .inline-table-form .btn {
            margin-top: 0;
            width: 100%;
            margin-left: 0;
        }

        .inline-table-form .ops-input,
        .inline-table-form .ops-select {
            padding: 7px 8px;
            font-size: 0.78rem;
        }

        .inline-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .edit-toggle-actions {
            align-items: center;
        }

        .update-row-form[hidden] {
            display: none;
        }

        .btn-danger {
            background: #a33535;
            color: #fff;
        }

        .gallery-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .gallery-card {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .gallery-card img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            display: block;
            background: #edf2f7;
        }

        .gallery-meta {
            padding: 8px;
            font-size: 0.76rem;
            color: #30485e;
        }

        .gallery-meta strong {
            display: block;
            color: #1f3346;
            margin-bottom: 2px;
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

            .progress-grid {
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

            .feature-checklist,
            .location-grid {
                grid-template-columns: 1fr;
            }

            .wizard-progress,
            .gallery-grid,
            .inline-table-form {
                grid-template-columns: 1fr;
            }

            .guided-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .listing-category-shortcuts-row {
                flex-direction: column;
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

            .progress-grid {
                grid-template-columns: 1fr;
            }

            .billing-ledger-grid {
                grid-template-columns: 1fr;
            }

            .guided-steps {
                grid-template-columns: 1fr;
            }

            .listing-category-shortcuts .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        // Defensive fallback: prevents runtime failures if any legacy summary bindings remain.
        $summary = $summary ?? [
            'upcoming_bookings' => 0,
            'completed_bookings' => 0,
            'receipts_available' => 0,
            'notification_state' => 'ACTIVE',
        ];
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
        $vendorRooms = $vendorRooms ?? $vendorRoomCategories;
        $vendorMediaAssets = $vendorMediaAssets ?? collect();
        $categorySet = collect($selectedVendorCategories)->flip();
        $supportsAccommodation = $categorySet->has('accommodation');
        $hasSelectedCategories = count($selectedVendorCategories) > 0;
        $listingWizardStep = (int) session('listing_wizard_step', 1);
        $listingWizardStep = max(1, min(4, $listingWizardStep));
        $forcedPanelKey = (string) session('portal_active_panel', '');
        $propertyMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'property';
        });
        $roomMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'room';
        });
        $propertyLookupById = $vendorProperties->keyBy('id');
        $roomLookupById = $vendorRoomCategories->keyBy('id');
        $showCreatePropertyForm = old('property_form_intent') === '1';
        $showCreateRoomForm = old('room_form_intent') === '1';
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
        $expectedPayoutTotal = (float) $billingLedgerRows->where('is_settled', false)->sum('payout');
        $settledPayoutTotal = (float) $billingLedgerRows->where('is_settled', true)->sum('payout');
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
            <a href="#overview" data-panel-key="overview">Dashboard Summary</a>
            <a href="#profile" data-panel-key="profile">Profile / Update</a>
            <a href="#listings" data-panel-key="listings">Add Listings</a>
            <a href="#reservations" data-panel-key="reservations">Reservations / Bookings</a>
            <a href="#billing" data-panel-key="billing">Billing / Daily Collection</a>
            <a href="#api" data-panel-key="api">API Tools</a>
        </nav>

        <div class="portal-content">

        <section id="vendorSummary" class="summary-grid" aria-label="Vendor dashboard summary" data-panel-group="overview">
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

        <div id="vendorSummaryActions" class="summary-actions" data-panel-group="overview">
            <button id="refreshSummary" type="button" class="summary-refresh">Refresh Summary</button>
        </div>

        <section id="vendorProgressSnapshot" class="card progress-snapshot" aria-label="Vendor activity progress snapshot" data-panel-group="overview">
            <div class="ops-header">
                <p class="ops-title">Vendor Progress Snapshot</p>
                <span class="ops-chip">Live from your account data</span>
            </div>
            <div class="progress-grid">
                <article class="progress-card">
                    <p class="progress-label">Total Bookings</p>
                    <p class="progress-value">{{ $vendorReservations->count() }}</p>
                    <p class="progress-meta">Reservation entries in this vendor account</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Revenue Collected</p>
                    <p class="progress-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    <p class="progress-meta">Gross collections before commission deductions</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Expected Payout</p>
                    <p class="progress-value">MVR {{ number_format($expectedPayoutTotal, 2) }}</p>
                    <p class="progress-meta">Pending payout expected from Workation</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Settled Amount</p>
                    <p class="progress-value">MVR {{ number_format($settledPayoutTotal, 2) }}</p>
                    <p class="progress-meta">Net payouts already marked as settled</p>
                </article>
            </div>
        </section>

        <section id="payoutCenter" class="card payout-center" aria-label="Vendor payout center" data-panel-group="overview">
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

        @if ($errors->any() && !$errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        <section id="vendorProfileCard" class="card profile-card" aria-label="Vendor profile settings" data-panel-group="profile">
            <p class="label">Account Settings</p>
            <div class="panel-links" aria-label="Profile actions">
                <a href="#vendorProfileCard">Profile Settings</a>
                <a href="#vendorCategoryWizard">Category Setup</a>
                <a href="#billing">Billing Settings</a>
            </div>
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

            <div id="vendorCategoryWizard" class="ops-section" aria-label="Vendor category setup wizard">
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
            </div>
        </section>

        <section id="vendorProfileBillingSettings" class="card ops-section" aria-label="Vendor billing settings" data-panel-group="billing">
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
                        <label for="billing_beneficiary_name">Beneficiary / Account Name</label>
                        <input id="billing_beneficiary_name" name="beneficiary_name" class="ops-input" type="text" maxlength="190" value="{{ old('beneficiary_name', optional($vendorBilling)->beneficiary_name ?? '') }}" required>
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
                        <label for="billing_swift_code">SWIFT Code</label>
                        <input id="billing_swift_code" name="swift_code" class="ops-input" type="text" maxlength="20" value="{{ old('swift_code', optional($vendorBilling)->swift_code ?? '') }}" placeholder="e.g. MALAADMV">
                    </div>
                    <div class="ops-field">
                        <label for="billing_account_number">Account Number (Full)</label>
                        <input id="billing_account_number" name="bank_account_number" class="ops-input" type="text" maxlength="60" value="{{ old('bank_account_number', optional($vendorBilling)->bank_account_number ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_currency">Currency</label>
                        <select id="billing_currency" name="currency" class="ops-select" required>
                            <option value="MVR" @selected(strtoupper((string) old('currency', optional($vendorBilling)->currency ?? 'MVR')) === 'MVR')>MVR</option>
                            <option value="USD" @selected(strtoupper((string) old('currency', optional($vendorBilling)->currency ?? 'MVR')) === 'USD')>USD</option>
                        </select>
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="billing_street_name">Address: Street Name</label>
                        <input id="billing_street_name" name="billing_street_name" class="ops-input" type="text" maxlength="255" value="{{ old('billing_street_name', optional($vendorBilling)->billing_street_name ?? '') }}" required>
                    </div>
                    <div class="ops-field">
                        <label for="billing_country">Country</label>
                        <select id="billing_country" name="billing_country" class="ops-select" required>
                            <option value="Maldives" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? 'Maldives') === 'Maldives')>Maldives</option>
                            <option value="Sri Lanka" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'Sri Lanka')>Sri Lanka</option>
                            <option value="India" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'India')>India</option>
                            <option value="Other" @selected(old('billing_country', optional($vendorBilling)->billing_country ?? '') === 'Other')>Other</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_state">State / Province / Atoll</label>
                        <select id="billing_state" name="billing_state" class="ops-select" required>
                            <option value="">Select state/province</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_city">City / Island</label>
                        <select id="billing_city" name="billing_city" class="ops-select" required>
                            <option value="">Select city/island</option>
                        </select>
                    </div>
                    <div class="ops-field">
                        <label for="billing_invoice_prefix">Invoice Prefix</label>
                        <input id="billing_invoice_prefix" name="invoice_prefix" class="ops-input" type="text" maxlength="30" value="{{ old('invoice_prefix', optional($vendorBilling)->invoice_prefix ?? 'INV') }}">
                    </div>
                    <div class="ops-field ops-field-wide">
                        <label for="billing_address">Additional Address Details (optional)</label>
                        <textarea id="billing_address" name="billing_address" class="ops-textarea" maxlength="2000">{{ old('billing_address', optional($vendorBilling)->billing_address ?? '') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save Billing Details</button>
            </form>
        </section>

        <section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview" data-panel-group="listings">
            <div class="ops-header">
                <p class="ops-title">Operations Console</p>
                <span class="ops-chip">Database-backed</span>
            </div>
            @php
                $listingShortcutOrder = ['accommodation', 'transport', 'excursion', 'remote_workspace', 'resort_day_visit', 'restaurant', 'vehicle_rental'];
            @endphp
            <div class="listing-category-shortcuts" aria-label="Listing category actions">
                <div class="listing-category-shortcuts-head">Quick Add By Category</div>
                <div class="listing-category-shortcuts-row">
                    @foreach ($listingShortcutOrder as $categoryKey)
                        @if (isset($vendorCategoryMap[$categoryKey]))
                            <button type="button" class="btn btn-secondary" data-listing-category-shortcut="{{ $categoryKey }}">Add {{ $vendorCategoryMap[$categoryKey] }}</button>
                        @endif
                    @endforeach
                </div>
                <div class="listing-category-shortcuts-head">Quick List Filter</div>
                <div class="listing-category-shortcuts-row">
                    <button type="button" class="btn btn-secondary is-active" data-listing-category-filter="all">Show All Listings</button>
                    @foreach ($listingShortcutOrder as $categoryKey)
                        @if (isset($vendorCategoryMap[$categoryKey]))
                            <button type="button" class="btn btn-secondary" data-listing-category-filter="{{ $categoryKey }}">{{ $vendorCategoryMap[$categoryKey] }}</button>
                        @endif
                    @endforeach
                </div>
            </div>
            <article class="guided-wizard" aria-label="Guided listing wizard">
                <div class="guided-wizard-head">
                    <div>
                        <p class="guided-wizard-title">Guided Enlisting Wizard</p>
                        <p class="guided-wizard-subtitle">Follow simple steps to onboard property listings with less friction.</p>
                    </div>
                    <div class="guided-track-toggle" role="group" aria-label="Wizard track switcher">
                        <button type="button" class="btn btn-secondary" id="guidedTrackProperty">Property Track</button>
                    </div>
                </div>
                <div class="guided-progress-wrap" aria-live="polite">
                    <div class="guided-progress-rail">
                        <div class="guided-progress-fill" id="guidedWizardProgressFill"></div>
                    </div>
                    <div class="guided-step-text" id="guidedWizardStepText">Step 1 of 5</div>
                </div>
                <ol class="guided-steps" id="guidedWizardSteps"></ol>
                <div class="guided-actions">
                    <button type="button" class="btn btn-secondary" id="guidedWizardPrev">Back</button>
                    <button type="button" class="btn btn-secondary" id="guidedWizardResume">Resume Last Step</button>
                    <button type="button" class="btn btn-primary" id="guidedWizardNext">Next Step</button>
                </div>
            </article>
            <div class="wizard-progress" aria-label="Listings wizard progress">
                <article class="wizard-progress-step @if($listingWizardStep > 1) is-complete @elseif($listingWizardStep === 1) is-active @endif">
                    <strong>Step 1</strong>
                    Add property details
                </article>
                <article class="wizard-progress-step @if($listingWizardStep > 2) is-complete @elseif($listingWizardStep === 2) is-active @endif">
                    <strong>Step 2</strong>
                    Review property list and update/remove
                </article>
                <article class="wizard-progress-step @if($listingWizardStep > 3) is-complete @elseif($listingWizardStep === 3) is-active @endif">
                    <strong>Step 3</strong>
                    Add room inventory and toilet features
                </article>
                <article class="wizard-progress-step @if($listingWizardStep === 4) is-active @endif">
                    <strong>Step 4</strong>
                    Add property and room pictures
                </article>
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

        <section id="vendorPropertiesSection" class="card ops-section" aria-label="Vendor properties" data-panel-group="listings" data-listing-step="1">
            <div class="ops-header">
                <p class="ops-title">Properties and Listings</p>
                <span class="ops-chip">{{ $vendorProperties->count() }} total</span>
            </div>
            <div class="panel-links" aria-label="Listings actions">
                <a href="#vendorPropertiesSection">Listings</a>
                <a href="#vendorRoomsSection">Room Inventory</a>
                <a href="#vendorMediaSection">Photos</a>
            </div>
            <div class="ops-grid properties-grid">
                @php
                    $oldPropertyAmenities = collect(old('property_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldPropertyFeatures = collect(old('property_features', []))->map(fn ($item) => (string) $item)->all();
                @endphp
                <article class="ops-form ops-field-wide">
                    <div class="form-toggle-row">
                        <button class="btn btn-primary" type="button" id="openPropertyCreateForm">Create New Property</button>
                        <button class="btn btn-secondary" type="button" id="closePropertyCreateForm" @if (!$showCreatePropertyForm) hidden @endif>Cancel</button>
                    </div>
                    <form id="propertyCreateForm" class="ops-form" method="POST" action="/portal/vendor/properties/create" @if (!$showCreatePropertyForm) hidden @endif>
                        @csrf
                        <input type="hidden" name="property_form_intent" value="1">
                        <div class="ops-form-grid">
                            <div class="ops-field">
                                <label for="property_listing_category">Listing Category</label>
                                <select id="property_listing_category" name="listing_category" class="ops-select" required>
                                    @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                                        <option value="{{ $categoryKey }}" @selected(old('listing_category') === $categoryKey) @disabled(!in_array($categoryKey, $selectedVendorCategories, true))>{{ $categoryLabel }}</option>
                                    @endforeach
                                </select>
                                <p id="propertyCategoryScopeNote" class="category-scope-note">Category-specific fields will change based on your selection.</p>
                            </div>
                            <div class="ops-field">
                                <label for="property_name">Name</label>
                                <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                            </div>
                            <div class="ops-field">
                                <label for="property_type">Type</label>
                                <select id="property_type" name="property_type" class="ops-select" required>
                                    <option value="property" @selected(old('property_type', 'property') === 'property')>Property</option>
                                    <option value="service" @selected(old('property_type') === 'service')>Service Space</option>
                                </select>
                            </div>
                            <div class="ops-field">
                                <label for="location_country">Country</label>
                                <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', 'Maldives') }}" required>
                                    <option value="Maldives" @selected(old('location_country', 'Maldives') === 'Maldives')>Maldives</option>
                                    <option value="Sri Lanka" @selected(old('location_country') === 'Sri Lanka')>Sri Lanka</option>
                                    <option value="India" @selected(old('location_country') === 'India')>India</option>
                                    <option value="Other" @selected(old('location_country') === 'Other')>Other</option>
                                </select>
                            </div>
                            <div class="ops-field">
                                <label for="location_state">State / Province / Atoll</label>
                                <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                                    <option value="">Select state/province</option>
                                </select>
                            </div>
                            <div class="ops-field">
                                <label for="location_city">City / Island</label>
                                <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                                    <option value="">Select city/island</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="address_line">Exact Address</label>
                                <input id="address_line" name="address_line" class="ops-input" type="text" maxlength="255" value="{{ old('address_line') }}" placeholder="Street, house/building name, nearby landmark" required>
                            </div>
                            <div class="ops-field">
                                <label for="map_latitude">Map Latitude</label>
                                <input id="map_latitude" name="map_latitude" class="ops-input" type="number" min="-90" max="90" step="0.000001" value="{{ old('map_latitude') }}" placeholder="4.1755">
                            </div>
                            <div class="ops-field">
                                <label for="map_longitude">Map Longitude</label>
                                <input id="map_longitude" name="map_longitude" class="ops-input" type="number" min="-180" max="180" step="0.000001" value="{{ old('map_longitude') }}" placeholder="73.5093">
                            </div>
                            <div class="ops-field">
                                <label for="map_place_id">Map Place ID (optional)</label>
                                <input id="map_place_id" name="map_place_id" class="ops-input" type="text" maxlength="190" value="{{ old('map_place_id') }}" placeholder="Generated from pin-drop">
                            </div>
                            <div class="ops-field">
                                <label for="property_base_price">Base Price (MVR)</label>
                                <input id="property_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('base_price') }}">
                            </div>
                            <div class="ops-field">
                                <label for="property_max_guests">Max Guests</label>
                                <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests') }}">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_description">Description</label>
                                <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000">{{ old('description') }}</textarea>
                            </div>

                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_measurement_system">Measurement System</label>
                                <select id="property_measurement_system" name="measurement_system" class="ops-select">
                                    <option value="metric" @selected(old('measurement_system', 'metric') === 'metric')>Metric</option>
                                    <option value="imperial" @selected(old('measurement_system') === 'imperial')>Imperial</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_area_value">Area Value</label>
                                <input id="property_area_value" name="area_value" class="ops-input" type="number" min="1" max="100000" step="0.01" value="{{ old('area_value') }}" placeholder="e.g. 120">
                            </div>
                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_area_unit">Area Unit</label>
                                <select id="property_area_unit" name="area_unit" class="ops-select">
                                    <option value="sqm" @selected(old('area_unit', 'sqm') === 'sqm')>sqm</option>
                                    <option value="sqft" @selected(old('area_unit') === 'sqft')>sqft</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_bedroom_count">Bedrooms</label>
                                <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count') }}">
                            </div>
                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_bathroom_count">Bathrooms</label>
                                <input id="property_bathroom_count" name="bathroom_count" class="ops-input" type="number" min="0" max="1000" step="0.5" value="{{ old('bathroom_count') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_capacity_value">Capacity</label>
                                <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}" placeholder="seats, guests, units">
                            </div>
                            <div class="ops-field" data-category-scope="service">
                                <label for="property_service_radius_km">Service Radius (km)</label>
                                <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" max="5000" step="0.1" value="{{ old('service_radius_km') }}">
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_minimum_age">Minimum Age</label>
                                <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="0" max="120" value="{{ old('minimum_age') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="stay,experience">
                                <label>Property Amenities (tick all available)</label>
                                <div class="feature-checklist">
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="wifi" @checked(in_array('wifi', $oldPropertyAmenities, true))> Wi-Fi</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="parking" @checked(in_array('parking', $oldPropertyAmenities, true))> Parking</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="pool" @checked(in_array('pool', $oldPropertyAmenities, true))> Pool</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="gym" @checked(in_array('gym', $oldPropertyAmenities, true))> Gym</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="air_conditioning" @checked(in_array('air_conditioning', $oldPropertyAmenities, true))> Air Conditioning</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="breakfast" @checked(in_array('breakfast', $oldPropertyAmenities, true))> Breakfast</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="kitchen" @checked(in_array('kitchen', $oldPropertyAmenities, true))> Kitchen</label>
                                    <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="workspace_desk" @checked(in_array('workspace_desk', $oldPropertyAmenities, true))> Workspace Desk</label>
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="stay,experience">
                                <label>Property Features (tick all available)</label>
                                <div class="feature-checklist">
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="wheelchair_access" @checked(in_array('wheelchair_access', $oldPropertyFeatures, true))> Wheelchair Access</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="elevator" @checked(in_array('elevator', $oldPropertyFeatures, true))> Elevator</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="family_friendly" @checked(in_array('family_friendly', $oldPropertyFeatures, true))> Family Friendly</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="pet_friendly" @checked(in_array('pet_friendly', $oldPropertyFeatures, true))> Pet Friendly</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="beachfront" @checked(in_array('beachfront', $oldPropertyFeatures, true))> Beachfront</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="sea_view" @checked(in_array('sea_view', $oldPropertyFeatures, true))> Sea View</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="safety_certified" @checked(in_array('safety_certified', $oldPropertyFeatures, true))> Safety Certified</label>
                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="kids_play_area" @checked(in_array('kids_play_area', $oldPropertyFeatures, true))> Kids Play Area</label>
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <div class="map-picker">
                                    <div id="propertyMap" aria-label="Map picker"></div>
                                </div>
                                <p class="map-help">Click on the map to drop a pin for exact location. Latitude and longitude update automatically.</p>
                            </div>
                        </div>
                        <p class="standards-note">International listing standard: fields adapt to selected category. Create one property at a time, then add rooms under that property.</p>
                        <button class="btn btn-primary" type="submit">Save Property</button>
                    </form>
                </article>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor properties table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>List Details</th>
                                <th>Edit / Update / Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorProperties->take(12) as $property)
                                <tr data-property-row data-listing-category="{{ strtolower((string) ($property->listing_category ?? '')) }}">
                                    <td>
                                        <strong>{{ $property->name }}</strong><br>
                                        ID: {{ (int) $property->id }}<br>
                                        {{ strtoupper((string) ($property->listing_category ?? 'N/A')) }} / {{ strtoupper((string) $property->property_type) }}
                                    </td>
                                    <td>
                                        {{ $property->location ?: 'N/A' }}<br>
                                        {{ $property->currency }} {{ number_format((float) $property->base_price, 2) }}<br>
                                        Guests: {{ (int) ($property->max_guests ?? 0) }} | Status: {{ strtoupper((string) $property->status) }}
                                    </td>
                                    <td>
                                        <div class="listing-cell-actions">
                                            <div class="inline-actions edit-toggle-actions">
                                                <button class="btn btn-secondary" type="button" data-open-property-edit data-property-edit-id="{{ (int) $property->id }}">Edit Listing</button>
                                                <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ (int) $property->id }}">Add Room</button>
                                                <form method="POST" action="/portal/vendor/properties/{{ $property->id }}/delete" onsubmit="return confirm('Remove this property listing?');">
                                                    @csrf
                                                    <button class="btn btn-danger" type="submit">Remove</button>
                                                </form>
                                            </div>
                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/properties/{{ $property->id }}/update" data-property-edit-form="{{ (int) $property->id }}" hidden>
                                                @csrf
                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $property->name }}" required>
                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) $property->base_price }}">
                                                <input class="ops-input" name="max_guests" type="number" min="0" max="10000" value="{{ (int) ($property->max_guests ?? 1) }}">
                                                <select class="ops-select" name="status" required>
                                                    <option value="active" @selected((string) $property->status === 'active')>Active</option>
                                                    <option value="inactive" @selected((string) $property->status === 'inactive')>Inactive</option>
                                                </select>
                                                <div class="inline-actions">
                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Property</button>
                                                    <button class="btn btn-secondary" type="button" data-close-property-edit data-property-edit-id="{{ (int) $property->id }}">Cancel Edit</button>
                                                </div>
                                            </form>
                                            <form class="inline-table-form media-upload-row" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="entity_type" value="property">
                                                <input type="hidden" name="entity_id" value="{{ (int) $property->id }}">
                                                <input class="ops-input" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                                                <input class="ops-input" name="alt_text" type="text" maxlength="190" placeholder="Property photo alt text" required>
                                                <label class="feature-item"><input type="checkbox" name="is_primary" value="1"> Set as primary</label>
                                                <button class="btn btn-secondary" type="submit">Upload Property Photo</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="ops-empty">No properties yet. Use Create New Property to add your first listing.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorRoomsSection" class="card ops-section" aria-label="Vendor room inventory" data-panel-group="listings" data-listing-step="2">
            <div class="ops-header">
                <p class="ops-title">Room Inventory</p>
                <span class="ops-chip">{{ $vendorRooms->count() }} total</span>
            </div>
            <div class="ops-grid">
                @php
                    $oldRoomAmenities = collect(old('room_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldRoomFeatures = collect(old('room_features', []))->map(fn ($item) => (string) $item)->all();
                @endphp
                <article class="ops-form ops-field-wide">
                    <div class="form-toggle-row">
                        <button class="btn btn-primary" type="button" id="openRoomCreateForm" @if($vendorProperties->isEmpty()) disabled @endif>Add Room Under Property</button>
                        <button class="btn btn-secondary" type="button" id="closeRoomCreateForm" @if (!$showCreateRoomForm) hidden @endif>Cancel</button>
                    </div>
                    @if($vendorProperties->isEmpty())
                        <p class="wizard-note">Create at least one property first, then add rooms under that property.</p>
                    @endif
                    <form id="roomCreateForm" class="ops-form" method="POST" action="/portal/vendor/rooms/create" @if (!$showCreateRoomForm) hidden @endif>
                        @csrf
                        <input type="hidden" name="room_form_intent" value="1">
                        <div class="ops-form-grid">
                            <div class="ops-field">
                                <label for="room_vendor_property_id">Property</label>
                                <select id="room_vendor_property_id" name="vendor_property_id" class="ops-select" required>
                                    <option value="">Select property</option>
                                    @foreach ($vendorProperties as $property)
                                        <option value="{{ (int) $property->id }}" @selected((string) old('vendor_property_id') === (string) $property->id)>{{ $property->name }} (#{{ (int) $property->id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field">
                                <label for="room_name">Room Name</label>
                                <input id="room_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                            </div>
                            <div class="ops-field">
                                <label for="room_quantity">Quantity</label>
                                <input id="room_quantity" name="quantity" class="ops-input" type="number" min="1" max="10000" value="{{ old('quantity', 1) }}">
                            </div>
                            <div class="ops-field">
                                <label for="room_max_occupancy">Max Occupancy</label>
                                <input id="room_max_occupancy" name="max_occupancy" class="ops-input" type="number" min="1" max="50" value="{{ old('max_occupancy', 1) }}">
                            </div>
                            <div class="ops-field">
                                <label for="room_bed_type">Bed Type</label>
                                <input id="room_bed_type" name="bed_type" class="ops-input" type="text" maxlength="80" value="{{ old('bed_type') }}" placeholder="King, Twin, Bunk, etc.">
                            </div>
                            <div class="ops-field">
                                <label for="room_base_price">Base Price (MVR)</label>
                                <input id="room_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('base_price') }}">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label>Room Amenities</label>
                                <div class="feature-checklist">
                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="wifi" @checked(in_array('wifi', $oldRoomAmenities, true))> Wi-Fi</label>
                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="ac" @checked(in_array('ac', $oldRoomAmenities, true))> Air Conditioning</label>
                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="mini_bar" @checked(in_array('mini_bar', $oldRoomAmenities, true))> Mini Bar</label>
                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="safe" @checked(in_array('safe', $oldRoomAmenities, true))> In-room Safe</label>
                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="balcony" @checked(in_array('balcony', $oldRoomAmenities, true))> Balcony</label>
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label>Toilet and Bathroom Features</label>
                                <div class="feature-checklist">
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="private_bathroom" @checked(in_array('private_bathroom', $oldRoomFeatures, true))> Private Bathroom</label>
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="hot_water" @checked(in_array('hot_water', $oldRoomFeatures, true))> Hot Water</label>
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="shower" @checked(in_array('shower', $oldRoomFeatures, true))> Shower</label>
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="bathtub" @checked(in_array('bathtub', $oldRoomFeatures, true))> Bathtub</label>
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="bidet" @checked(in_array('bidet', $oldRoomFeatures, true))> Bidet</label>
                                    <label class="feature-item"><input type="checkbox" name="room_features[]" value="accessible_toilet" @checked(in_array('accessible_toilet', $oldRoomFeatures, true))> Accessible Toilet</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Save Room</button>
                    </form>
                </article>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor room inventory table">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Property</th>
                                <th>Details</th>
                                <th>Edit / Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendorRooms->take(20) as $room)
                                <tr>
                                    <td>
                                        <strong>{{ $room->name }}</strong><br>
                                        ID: {{ (int) $room->id }}
                                    </td>
                                    <td>
                                        @php
                                            $linkedProperty = $room->vendor_property_id ? $propertyLookupById->get((int) $room->vendor_property_id) : null;
                                        @endphp
                                        {{ $linkedProperty?->name ?? 'N/A' }}
                                        @if ($room->vendor_property_id)
                                            <br>#{{ (int) $room->vendor_property_id }}
                                        @endif
                                    </td>
                                    <td>
                                        Qty: {{ (int) ($room->quantity ?? 0) }}<br>
                                        Max: {{ (int) ($room->max_occupancy ?? 0) }}<br>
                                        {{ $room->currency ?? 'MVR' }} {{ number_format((float) ($room->base_price ?? 0), 2) }}
                                    </td>
                                    <td>
                                        <div class="listing-cell-actions">
                                            <div class="inline-actions edit-toggle-actions">
                                                <button class="btn btn-secondary" type="button" data-open-room-edit data-room-edit-id="{{ (int) $room->id }}">Edit Room</button>
                                                <form method="POST" action="/portal/vendor/rooms/{{ $room->id }}/delete" onsubmit="return confirm('Remove this room category?');">
                                                    @csrf
                                                    <button class="btn btn-danger" type="submit">Remove</button>
                                                </form>
                                            </div>
                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/rooms/{{ $room->id }}/update" data-room-edit-form="{{ (int) $room->id }}" hidden>
                                                @csrf
                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $room->name }}" required>
                                                <input class="ops-input" name="quantity" type="number" min="1" max="10000" value="{{ (int) ($room->quantity ?? 1) }}">
                                                <input class="ops-input" name="max_occupancy" type="number" min="1" max="50" value="{{ (int) ($room->max_occupancy ?? 1) }}">
                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) ($room->base_price ?? 0) }}">
                                                <div class="inline-actions">
                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Room</button>
                                                    <button class="btn btn-secondary" type="button" data-close-room-edit data-room-edit-id="{{ (int) $room->id }}">Cancel Edit</button>
                                                </div>
                                            </form>
                                            <form class="inline-table-form media-upload-row" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="entity_type" value="room">
                                                <input type="hidden" name="entity_id" value="{{ (int) $room->id }}">
                                                <input class="ops-input" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                                                <input class="ops-input" name="alt_text" type="text" maxlength="190" placeholder="Room photo alt text" required>
                                                <label class="feature-item"><input type="checkbox" name="is_primary" value="1"> Set as primary</label>
                                                <button class="btn btn-secondary" type="submit">Upload Room Photo</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="ops-empty">No rooms added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="vendorMediaSection" class="card ops-section" aria-label="Vendor media" data-panel-group="listings" data-listing-step="3">
            <div class="ops-header">
                <p class="ops-title">Media Library</p>
                <span class="ops-chip">{{ $vendorMediaAssets->count() }} files</span>
            </div>
            <div class="ops-grid">
                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor media table">
                        <thead>
                            <tr>
                                <th>Entity</th>
                                <th>Path</th>
                                <th>Size</th>
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

                <article class="ops-form ops-field-wide">
                    <p class="label">Property Pictures</p>
                    <p class="wizard-note">Open each property by ID and verify image quality before publishing.</p>
                    <div class="gallery-grid">
                        @forelse ($propertyMediaAssets->take(9) as $media)
                            <div class="gallery-card">
                                <img src="{{ str_starts_with((string) $media->file_path, 'http') ? (string) $media->file_path : ('/storage/' . ltrim((string) $media->file_path, '/')) }}" alt="{{ $media->alt_text ?: 'Property image' }}" loading="lazy">
                                <div class="gallery-meta">
                                    <strong>Property #{{ $media->entity_id ?: 'N/A' }}</strong>
                                    {{ $media->alt_text ?: 'No alt text' }}
                                </div>
                            </div>
                        @empty
                            <p class="ops-empty">No property pictures uploaded yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="ops-form ops-field-wide">
                    <p class="label">Room Pictures and Toilet Features</p>
                    <p class="wizard-note">Room media is shown separately so vendors can verify toilet and bathroom feature visuals.</p>
                    <div class="gallery-grid">
                        @forelse ($roomMediaAssets->take(9) as $media)
                            <div class="gallery-card">
                                <img src="{{ str_starts_with((string) $media->file_path, 'http') ? (string) $media->file_path : ('/storage/' . ltrim((string) $media->file_path, '/')) }}" alt="{{ $media->alt_text ?: 'Room image' }}" loading="lazy">
                                <div class="gallery-meta">
                                    <strong>Room #{{ $media->entity_id ?: 'N/A' }}</strong>
                                    {{ $media->alt_text ?: 'No alt text' }}
                                    @php
                                        $linkedRoom = $media->entity_id ? $roomLookupById->get((int) $media->entity_id) : null;
                                        $linkedFeatures = trim((string) ($linkedRoom->amenities ?? ''));
                                    @endphp
                                    @if ($linkedFeatures !== '')
                                        <br>Features: {{ $linkedFeatures }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="ops-empty">No room pictures uploaded yet.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>

        <section id="vendorAvailabilitySection" class="card ops-section" aria-label="Vendor availability calendar" data-panel-group="reservations">
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

        <section id="vendorReservationsSection" class="card ops-section" aria-label="Vendor reservations" data-panel-group="reservations">
            <div class="ops-header">
                <p class="ops-title">Reservations</p>
                <span class="ops-chip">{{ $vendorReservations->count() }} total</span>
            </div>
            <div class="panel-links" aria-label="Reservation actions">
                <a href="#vendorReservationsSection">Booking Inquiries</a>
                <a href="#vendorAvailabilitySection">Availability Updates</a>
                <a href="#vendorPricingSection">Pricing Rules</a>
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

        <section id="vendorPricingSection" class="card ops-section" aria-label="Vendor pricing rules" data-panel-group="reservations">
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

        <section id="vendorDailyCollectionSection" class="card ops-section" aria-label="Vendor daily collection and settlements" data-panel-group="billing">
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
        </section>

        <section class="layout" id="vendorAuthApi" data-panel-group="api">
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
            const navLinks = Array.from(document.querySelectorAll('.portal-nav a[data-panel-key]'));
            const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
            const listingStepPanels = Array.from(document.querySelectorAll('[data-listing-step]'));
            const validPanelKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));
            const locationCountry = document.getElementById("location_country");
            const locationState = document.getElementById("location_state");
            const locationCity = document.getElementById("location_city");
            const mapLatitude = document.getElementById("map_latitude");
            const mapLongitude = document.getElementById("map_longitude");
            const mapPlaceId = document.getElementById("map_place_id");
            const billingCountry = document.getElementById("billing_country");
            const billingState = document.getElementById("billing_state");
            const billingCity = document.getElementById("billing_city");
            const openPropertyCreateForm = document.getElementById("openPropertyCreateForm");
            const closePropertyCreateForm = document.getElementById("closePropertyCreateForm");
            const propertyCreateForm = document.getElementById("propertyCreateForm");
            const propertyCategorySelect = document.getElementById("property_listing_category");
            const categoryScopedFields = Array.from(document.querySelectorAll("[data-category-scope]"));
            const openRoomCreateForm = document.getElementById("openRoomCreateForm");
            const closeRoomCreateForm = document.getElementById("closeRoomCreateForm");
            const roomCreateForm = document.getElementById("roomCreateForm");
            const roomPropertySelect = document.getElementById("room_vendor_property_id");
            const roomQuickOpenButtons = Array.from(document.querySelectorAll("[data-open-room-form]"));
            const propertyEditButtons = Array.from(document.querySelectorAll('[data-open-property-edit]'));
            const propertyEditCancelButtons = Array.from(document.querySelectorAll('[data-close-property-edit]'));
            const roomEditButtons = Array.from(document.querySelectorAll('[data-open-room-edit]'));
            const roomEditCancelButtons = Array.from(document.querySelectorAll('[data-close-room-edit]'));
            const listingCategoryShortcutButtons = Array.from(document.querySelectorAll('[data-listing-category-shortcut]'));
            const listingCategoryFilterButtons = Array.from(document.querySelectorAll('[data-listing-category-filter]'));
            const propertyListingRows = Array.from(document.querySelectorAll('[data-property-row]'));
            const guidedTrackProperty = document.getElementById("guidedTrackProperty");
            const guidedWizardSteps = document.getElementById("guidedWizardSteps");
            const guidedWizardStepText = document.getElementById("guidedWizardStepText");
            const guidedWizardProgressFill = document.getElementById("guidedWizardProgressFill");
            const guidedWizardPrev = document.getElementById("guidedWizardPrev");
            const guidedWizardResume = document.getElementById("guidedWizardResume");
            const guidedWizardNext = document.getElementById("guidedWizardNext");
            const serverPanelKey = "{{ in_array($forcedPanelKey, ['overview', 'profile', 'listings', 'billing', 'reservations', 'api'], true) ? $forcedPanelKey : '' }}";
            const listingWizardStep = Number("{{ $listingWizardStep }}") || 1;
            let listingWizardStarted = serverPanelKey === "listings";
            let listingWizardPanelStep = 1;
            let guidedWizardTrack = "property";
            let guidedWizardIndex = 0;
            const vendorPropertiesCount = Number("{{ $vendorProperties->count() }}") || 0;
            const vendorRoomsCount = Number("{{ $vendorRooms->count() }}") || 0;
            const vendorBillingReady = "{{ $vendorBilling ? '1' : '0' }}" === "1";
            const GUIDED_WIZARD_STORAGE_KEY = "workation_vendor_guided_wizard";

            const guidedWizardFlows = {
                property: [
                    {
                        title: "Property setup",
                        hint: "Choose category and set listing basics.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                        openPropertyForm: true,
                    },
                    {
                        title: "Review and refine",
                        hint: "Confirm created property and update details.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 2,
                    },
                    {
                        title: "Room inventory",
                        hint: "Add room types and occupancy for each property.",
                        panel: "listings",
                        targetId: "vendorRoomsSection",
                        wizardStep: 3,
                        openRoomForm: true,
                    },
                    {
                        title: "Photos and media",
                        hint: "Upload property and room photos.",
                        panel: "listings",
                        targetId: "vendorMediaSection",
                        wizardStep: 4,
                    },
                    {
                        title: "Publish readiness",
                        hint: "Check pricing, availability, and billing before go-live.",
                        panel: "reservations",
                        targetId: "vendorPricingSection",
                    },
                ],
            };

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

            function setActiveNavLink(panelKey) {
                navLinks.forEach((link) => {
                    const isActive = (link.dataset.panelKey || "") === panelKey;
                    link.classList.toggle("is-active", isActive);
                });
            }

            function showPanelGroup(panelKey) {
                panelGroups.forEach((panel) => {
                    panel.hidden = (panel.getAttribute("data-panel-group") || "") !== panelKey;
                });
                setActiveNavLink(panelKey);

                if (panelKey === "listings") {
                    if (!listingWizardStarted) {
                        listingWizardStarted = true;
                        listingWizardPanelStep = 1;
                    }
                    applyListingWizardVisibility();
                } else {
                    setListingPanelsHidden(true);
                }
            }

            function resolvePanelFromHash(hashValue) {
                const panelKey = String(hashValue || "").replace(/^#/, "").trim().toLowerCase();
                return validPanelKeys.has(panelKey) ? panelKey : "overview";
            }

            function focusListingsWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, Number(step) || 1));
                const stepTargets = {
                    1: "vendorPropertiesSection",
                    2: "vendorPropertiesSection",
                    3: "vendorRoomsSection",
                    4: "vendorMediaSection"
                };
                const targetId = stepTargets[safeStep] || "vendorPropertiesSection";
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;
                targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            function listingPanelStepFromWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, Number(step) || 1));
                if (safeStep >= 4) return 3;
                if (safeStep >= 3) return 2;
                return 1;
            }

            function setListingPanelsHidden(hidden) {
                listingStepPanels.forEach((panel) => {
                    panel.hidden = hidden;
                });
            }

            function applyListingWizardVisibility() {
                const activeStepPanel = Math.max(1, Math.min(3, Number(listingWizardPanelStep) || 1));
                listingStepPanels.forEach((panel) => {
                    const panelStep = Number(panel.getAttribute("data-listing-step") || "0");
                    panel.hidden = panelStep !== activeStepPanel;
                });
            }

            function activateListingWizardStep(wizardStep, shouldScroll) {
                listingWizardStarted = true;
                listingWizardPanelStep = listingPanelStepFromWizardStep(wizardStep);
                applyListingWizardVisibility();
                if (shouldScroll) {
                    focusListingsWizardStep(wizardStep);
                }
            }

            function guidedWizardCurrentFlow() {
                const flow = guidedWizardFlows[guidedWizardTrack];
                return Array.isArray(flow) ? flow : guidedWizardFlows.property;
            }

            function guidedWizardCanMoveToIndex(targetIndex) {
                const safeTargetIndex = Math.max(0, Number(targetIndex) || 0);

                if (guidedWizardTrack === "property") {
                    if (safeTargetIndex >= 1 && vendorPropertiesCount <= 0) {
                        return {
                            ok: false,
                            message: "Create at least one property to continue to review, room setup, and media steps.",
                        };
                    }

                    if (safeTargetIndex >= 3 && vendorRoomsCount <= 0) {
                        return {
                            ok: false,
                            message: "Add at least one room before progressing to media-focused property flow.",
                        };
                    }

                    if (safeTargetIndex >= 4 && !vendorBillingReady) {
                        return {
                            ok: false,
                            message: "Complete billing profile before final publish readiness.",
                        };
                    }
                }

                return { ok: true, message: "" };
            }

            function persistGuidedWizardState() {
                const payload = {
                    track: guidedWizardTrack,
                    index: guidedWizardIndex,
                    savedAt: Date.now(),
                };
                try {
                    sessionStorage.setItem(GUIDED_WIZARD_STORAGE_KEY, JSON.stringify(payload));
                } catch (error) {
                    // Ignore storage errors in private/incognito contexts.
                }
            }

            function restoreGuidedWizardState() {
                try {
                    const raw = sessionStorage.getItem(GUIDED_WIZARD_STORAGE_KEY);
                    if (!raw) {
                        return false;
                    }
                    const parsed = JSON.parse(raw);
                    const track = String(parsed.track || "").toLowerCase();
                    const index = Number(parsed.index);
                    if (!(track in guidedWizardFlows)) {
                        return false;
                    }
                    const flow = guidedWizardFlows[track];
                    if (!Array.isArray(flow) || flow.length === 0) {
                        return false;
                    }
                    guidedWizardTrack = track;
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, Number.isFinite(index) ? index : 0));
                    return true;
                } catch (error) {
                    return false;
                }
            }

            function applyGuidedWizardStep(shouldScroll) {
                const flow = guidedWizardCurrentFlow();
                const safeIndex = Math.max(0, Math.min(flow.length - 1, Number(guidedWizardIndex) || 0));
                guidedWizardIndex = safeIndex;
                const currentStep = flow[safeIndex];
                if (!currentStep) {
                    return;
                }

                showPanelGroup(String(currentStep.panel || "listings"));

                if (typeof currentStep.wizardStep === "number") {
                    activateListingWizardStep(currentStep.wizardStep, Boolean(shouldScroll));
                } else if (shouldScroll && currentStep.targetId) {
                    const target = document.getElementById(currentStep.targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                }

                if (currentStep.openPropertyForm && propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                    if (closePropertyCreateForm) {
                        closePropertyCreateForm.hidden = false;
                    }
                }

                if (currentStep.openRoomForm && roomCreateForm) {
                    roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) {
                        closeRoomCreateForm.hidden = false;
                    }
                }
            }

            function renderGuidedWizard() {
                const flow = guidedWizardCurrentFlow();
                if (!guidedWizardSteps || !guidedWizardStepText || !guidedWizardProgressFill) {
                    return;
                }

                if (guidedTrackProperty) {
                    guidedTrackProperty.classList.toggle("is-active", guidedWizardTrack === "property");
                }

                guidedWizardSteps.innerHTML = "";
                flow.forEach((step, index) => {
                    const item = document.createElement("li");
                    item.className = "guided-step";
                    if (index < guidedWizardIndex) {
                        item.classList.add("is-complete");
                    }
                    if (index === guidedWizardIndex) {
                        item.classList.add("is-active");
                    }
                    item.textContent = "Step " + (index + 1) + ": " + step.title;
                    item.addEventListener("click", function () {
                        guidedWizardIndex = index;
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    });
                    guidedWizardSteps.appendChild(item);
                });

                const progressPercent = flow.length > 1
                    ? Math.round((guidedWizardIndex / (flow.length - 1)) * 100)
                    : 100;
                guidedWizardProgressFill.style.width = String(progressPercent) + "%";

                const activeStep = flow[guidedWizardIndex];
                guidedWizardStepText.textContent = "Step " + (guidedWizardIndex + 1) + " of " + flow.length + " - " + activeStep.hint;

                if (guidedWizardPrev) {
                    guidedWizardPrev.disabled = guidedWizardIndex <= 0;
                }
                if (guidedWizardNext) {
                    const isLastStep = guidedWizardIndex >= flow.length - 1;
                    guidedWizardNext.textContent = isLastStep ? "Go To Final Step" : "Next Step";

                    const targetIndex = Math.min(flow.length - 1, guidedWizardIndex + 1);
                    const gateCheck = guidedWizardCanMoveToIndex(targetIndex);
                    guidedWizardNext.disabled = !gateCheck.ok;
                    if (!gateCheck.ok) {
                        guidedWizardNext.title = gateCheck.message;
                        guidedWizardStepText.textContent = guidedWizardStepText.textContent + " | " + gateCheck.message;
                    } else {
                        guidedWizardNext.title = "";
                    }
                }

                persistGuidedWizardState();
            }

            const LOCATION_TREE = {
                "Maldives": {
                    "Kaafu Atoll": ["Male", "Hulhumale", "Maafushi"],
                    "Alif Alif Atoll": ["Rasdhoo", "Ukulhas", "Thoddoo"],
                    "Alif Dhaal Atoll": ["Dhigurah", "Dhangethi", "Mahibadhoo"],
                    "Baa Atoll": ["Eydhafushi", "Dharavandhoo", "Maalhos"]
                },
                "Sri Lanka": {
                    "Western Province": ["Colombo", "Negombo", "Kalutara"],
                    "Southern Province": ["Galle", "Matara", "Hambantota"],
                    "Central Province": ["Kandy", "Nuwara Eliya", "Matale"]
                },
                "India": {
                    "Kerala": ["Kochi", "Thiruvananthapuram", "Kozhikode"],
                    "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru"],
                    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai"]
                },
                "Other": {
                    "Other": ["Other"]
                }
            };

            function rebuildSelect(selectEl, values, placeholder) {
                if (!selectEl) return;
                selectEl.innerHTML = "";
                const defaultOption = document.createElement("option");
                defaultOption.value = "";
                defaultOption.textContent = placeholder;
                selectEl.appendChild(defaultOption);

                values.forEach((value) => {
                    const option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    selectEl.appendChild(option);
                });
            }

            function ensureSelectHasOption(selectEl, value) {
                if (!selectEl || !value) return;
                const exists = Array.from(selectEl.options).some((option) => option.value === value);
                if (exists) return;
                const option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                selectEl.appendChild(option);
            }

            function refreshLocationSelectors() {
                if (!locationCountry || !locationState || !locationCity) return;
                const selectedCountry = locationCountry.dataset.selectedValue || locationCountry.value || "Maldives";
                ensureSelectHasOption(locationCountry, selectedCountry);
                locationCountry.value = selectedCountry;
                const country = locationCountry.value || "Maldives";
                const states = Object.keys(LOCATION_TREE[country] || {});
                rebuildSelect(locationState, states, "Select state/province");
                const selectedState = locationState.dataset.selectedValue || "";
                ensureSelectHasOption(locationState, selectedState);
                if (selectedState && Array.from(locationState.options).some((option) => option.value === selectedState)) {
                    locationState.value = selectedState;
                } else {
                    locationState.value = states[0] || "";
                }
                const cities = (LOCATION_TREE[country] || {})[locationState.value] || [];
                rebuildSelect(locationCity, cities, "Select city/island");
                const selectedCity = locationCity.dataset.selectedValue || "";
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }

                locationCountry.dataset.selectedValue = "";
                locationState.dataset.selectedValue = "";
                locationCity.dataset.selectedValue = "";
            }

            function refreshCitySelector() {
                if (!locationCountry || !locationState || !locationCity) return;
                const country = locationCountry.value || "Maldives";
                const cities = (LOCATION_TREE[country] || {})[locationState.value] || [];
                const selectedCity = locationCity.dataset.selectedValue || "";
                rebuildSelect(locationCity, cities, "Select city/island");
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }
                locationCity.dataset.selectedValue = "";
            }

            function categoryScopesFor(category) {
                const raw = String(category || "").trim().toLowerCase();
                const normalized = raw.replace(/[^a-z0-9]/g, "");

                if (normalized === "accommodation") {
                    return ["stay", "capacity", "experience"];
                }

                if (normalized === "excursions" || normalized === "resortdayvisits") {
                    return ["capacity", "experience", "service"];
                }

                if (normalized === "remoteworkspaces" || normalized === "restaurants") {
                    return ["capacity", "service"];
                }

                if (normalized === "transports" || normalized === "vehiclerentals") {
                    return ["vehicle", "capacity", "service"];
                }

                return ["stay", "capacity", "service", "vehicle", "experience"];
            }

            function refreshPropertyCategoryFields() {
                if (!propertyCategorySelect || categoryScopedFields.length === 0) return;
                const activeScopes = categoryScopesFor(propertyCategorySelect.value);
                categoryScopedFields.forEach((field) => {
                    const scopes = String(field.getAttribute("data-category-scope") || "")
                        .split(",")
                        .map((value) => value.trim().toLowerCase())
                        .filter(Boolean);
                    if (scopes.length === 0) {
                        field.hidden = false;
                        return;
                    }
                    field.hidden = !scopes.some((scope) => activeScopes.includes(scope));
                });
            }

            function applyPropertyCategoryFilter(categoryKey) {
                const normalizedCategory = String(categoryKey || 'all').trim().toLowerCase();
                propertyListingRows.forEach((row) => {
                    const rowCategory = String(row.getAttribute('data-listing-category') || '').trim().toLowerCase();
                    const shouldShow = normalizedCategory === 'all' || rowCategory === normalizedCategory;
                    row.hidden = !shouldShow;
                });

                listingCategoryFilterButtons.forEach((button) => {
                    const buttonCategory = String(button.getAttribute('data-listing-category-filter') || '');
                    button.classList.toggle('is-active', buttonCategory === normalizedCategory);
                });
            }

            function openPropertyFlowWithCategory(categoryKey) {
                const normalizedCategory = String(categoryKey || '').trim().toLowerCase();
                window.location.hash = 'listings';
                showPanelGroup('listings');
                activateListingWizardStep(1, true);

                if (propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                }
                if (closePropertyCreateForm) {
                    closePropertyCreateForm.hidden = false;
                }
                if (propertyCategorySelect && normalizedCategory !== '') {
                    ensureSelectHasOption(propertyCategorySelect, normalizedCategory);
                    propertyCategorySelect.value = normalizedCategory;
                    propertyCategorySelect.dispatchEvent(new Event('change'));
                }
                if (document.getElementById('property_name')) {
                    document.getElementById('property_name').focus();
                }

                applyPropertyCategoryFilter(normalizedCategory || 'all');
            }

            function refreshBillingLocationSelectors() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const states = Object.keys(LOCATION_TREE[country] || {});
                const previousState = billingState.dataset.selectedValue || billingState.value;
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;

                rebuildSelect(billingState, states, "Select state/province");
                ensureSelectHasOption(billingState, previousState);

                if (previousState && Array.from(billingState.options).some((option) => option.value === previousState)) {
                    billingState.value = previousState;
                } else if (states.length > 0) {
                    billingState.value = states[0];
                }

                const cities = (LOCATION_TREE[country] || {})[billingState.value] || [];
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);

                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }

                billingState.dataset.selectedValue = "";
                billingCity.dataset.selectedValue = "";
            }

            function refreshBillingCitySelector() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const cities = (LOCATION_TREE[country] || {})[billingState.value] || [];
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);
                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }
                billingCity.dataset.selectedValue = "";
            }

            function initLocationMap() {
                if (!window.L) return;
                const mapEl = document.getElementById("propertyMap");
                if (!mapEl) return;

                const defaultLat = Number(mapLatitude && mapLatitude.value) || 4.1755;
                const defaultLng = Number(mapLongitude && mapLongitude.value) || 73.5093;
                const map = window.L.map(mapEl).setView([defaultLat, defaultLng], 9);

                window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    attribution: "&copy; OpenStreetMap contributors"
                }).addTo(map);

                let marker = window.L.marker([defaultLat, defaultLng]).addTo(map);

                function updateLocationFromMap(latlng) {
                    const lat = Number(latlng.lat.toFixed(6));
                    const lng = Number(latlng.lng.toFixed(6));

                    if (mapLatitude) mapLatitude.value = String(lat);
                    if (mapLongitude) mapLongitude.value = String(lng);
                    if (mapPlaceId && mapPlaceId.value.trim() === "") {
                        mapPlaceId.value = "PIN-" + lat + "," + lng;
                    }

                    marker.setLatLng([lat, lng]);
                }

                map.on("click", function (event) {
                    updateLocationFromMap(event.latlng);
                });
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

            navLinks.forEach((link) => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                    if (!panelKey) return;
                    window.location.hash = panelKey;
                    showPanelGroup(panelKey);
                });
            });

            window.addEventListener("hashchange", function () {
                showPanelGroup(resolvePanelFromHash(window.location.hash));
            });

            if (guidedTrackProperty) {
                guidedTrackProperty.addEventListener("click", function () {
                    guidedWizardTrack = "property";
                    guidedWizardIndex = 0;
                    window.location.hash = "listings";
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardPrev) {
                guidedWizardPrev.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex - 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardResume) {
                guidedWizardResume.addEventListener("click", function () {
                    if (restoreGuidedWizardState()) {
                        window.location.hash = "listings";
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    }
                });
            }

            if (guidedWizardNext) {
                guidedWizardNext.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex + 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (openPropertyCreateForm && propertyCreateForm) {
                openPropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = false;
                    if (closePropertyCreateForm) closePropertyCreateForm.hidden = false;
                    if (propertyCategorySelect) propertyCategorySelect.focus();
                });
            }

            if (closePropertyCreateForm && propertyCreateForm) {
                closePropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    closePropertyCreateForm.hidden = true;
                });
            }

            if (openRoomCreateForm && roomCreateForm) {
                openRoomCreateForm.addEventListener("click", function () {
                    roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) closeRoomCreateForm.hidden = false;
                    if (roomPropertySelect) roomPropertySelect.focus();
                });
            }

            if (closeRoomCreateForm && roomCreateForm) {
                closeRoomCreateForm.addEventListener("click", function () {
                    roomCreateForm.hidden = true;
                    closeRoomCreateForm.hidden = true;
                });
            }

            roomQuickOpenButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-property-id") || "").trim();
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(3, true);

                    if (roomCreateForm) roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) closeRoomCreateForm.hidden = false;

                    if (roomPropertySelect && propertyId) {
                        ensureSelectHasOption(roomPropertySelect, propertyId);
                        roomPropertySelect.value = propertyId;
                        roomPropertySelect.dispatchEvent(new Event("change"));
                        roomPropertySelect.focus();
                    }
                });
            });

            function openEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                form.hidden = false;
                const firstInput = form.querySelector('input, select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            }

            function closeEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                form.hidden = true;
            }

            propertyEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    openEditForm('[data-property-edit-form="' + editId + '"]');
                });
            });

            propertyEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-property-edit-form="' + editId + '"]');
                });
            });

            roomEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    openEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            roomEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            listingCategoryShortcutButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const categoryKey = String(button.getAttribute('data-listing-category-shortcut') || '');
                    openPropertyFlowWithCategory(categoryKey);
                });
            });

            listingCategoryFilterButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const categoryKey = String(button.getAttribute('data-listing-category-filter') || 'all');
                    applyPropertyCategoryFilter(categoryKey);
                });
            });

            document.querySelectorAll('.js-row-update').forEach((button) => {
                button.addEventListener('click', function (event) {
                    const form = button.closest('form');
                    if (!form) {
                        return;
                    }
                    event.preventDefault();
                    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                        return;
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });

            if (locationCountry && locationState && locationCity) {
                locationCountry.dataset.selectedValue = "{{ old('location_country', 'Maldives') }}";
                locationState.dataset.selectedValue = "{{ old('location_state', '') }}";
                locationCity.dataset.selectedValue = "{{ old('location_city', '') }}";
                refreshLocationSelectors();
                locationCountry.addEventListener("change", refreshLocationSelectors);
                locationState.addEventListener("change", refreshCitySelector);
            }

            if (propertyCategorySelect) {
                refreshPropertyCategoryFields();
                propertyCategorySelect.addEventListener("change", refreshPropertyCategoryFields);
            }

            if (billingCountry && billingState && billingCity) {
                billingState.dataset.selectedValue = "{{ old('billing_state', optional($vendorBilling)->billing_state ?? '') }}";
                billingCity.dataset.selectedValue = "{{ old('billing_city', optional($vendorBilling)->billing_city ?? '') }}";
                refreshBillingLocationSelectors();
                billingCountry.addEventListener("change", refreshBillingLocationSelectors);
                billingState.addEventListener("change", refreshBillingCitySelector);
            }
            initLocationMap();

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

            const hashPanelKey = resolvePanelFromHash(window.location.hash || "#overview");
            const initialPanelKey = serverPanelKey && validPanelKeys.has(serverPanelKey) ? serverPanelKey : hashPanelKey;
            listingWizardPanelStep = listingPanelStepFromWizardStep(listingWizardStep);
            showPanelGroup(initialPanelKey);
            restoreGuidedWizardState();
            renderGuidedWizard();
            applyPropertyCategoryFilter('all');
            if (initialPanelKey === "listings") {
                if (serverPanelKey === "listings") {
                    activateListingWizardStep(listingWizardStep, true);
                } else {
                    applyListingWizardVisibility();
                }
            }
        })();
    </script>
</body>
</html>

