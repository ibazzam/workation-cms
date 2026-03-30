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
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 10px 12px 20px;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 48%, var(--hero-3) 100%);
            border-radius: 12px;
            color: #fff;
            padding: 12px 14px;
            box-shadow: 0 10px 24px rgba(18, 38, 58, 0.18);
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
            font-size: clamp(1.2rem, 2vw, 1.65rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 980px;
            font-size: 0.86rem;
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
            margin-top: 10px;
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            min-height: calc(100vh - 86px);
        }

        .portal-nav {
            position: sticky;
            top: 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #f7fbff;
            max-height: calc(100vh - 16px);
            overflow-y: auto;
        }

        .portal-content {
            min-width: 0;
            width: 100%;
        }

        .portal-content > section,
        .portal-content > div {
            width: 100%;
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

        .ops-category-card {
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #f9fcff;
            padding: 10px;
        }

        .ops-category-card + .ops-category-card {
            margin-top: 10px;
        }

        .ops-category-toggle {
            width: 100%;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px;
            cursor: pointer;
            font: inherit;
            text-align: left;
            color: inherit;
        }

        .ops-category-toggle-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .ops-category-toggle .ops-title {
            font-size: 0.96rem;
            margin: 0;
        }

        .ops-category-toggle-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            border: 1px solid #d7e0e6;
            background: #f6faff;
            color: #30495f;
            font-size: 0.74rem;
            transition: transform 0.16s ease;
        }

        .ops-category-toggle[aria-expanded="true"] .ops-category-toggle-icon {
            transform: rotate(180deg);
        }

        .ops-category-body {
            margin-top: 10px;
        }

        .ops-target-quicklist {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
            justify-content: flex-end;
            flex-direction: row-reverse;
        }

        .ops-target-quickpick {
            border: 1px solid #d7e0e6;
            background: #fff;
            color: #2a4259;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.76rem;
            cursor: pointer;
        }

        .ops-target-quickpick:hover {
            border-color: #9ab1c6;
            background: #f3f8fd;
        }

        .ops-subtitle {
            margin: 0 0 8px;
            font-size: 0.8rem;
            color: #3a556f;
            font-weight: 700;
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

        .ops-category-card .ops-form > .btn {
            display: block;
            margin-left: auto;
        }

        .ops-category-card .ops-table .inline-status-form {
            justify-content: flex-end;
            flex-direction: row-reverse;
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

        .ops-input.is-invalid,
        .ops-select.is-invalid,
        .ops-textarea.is-invalid {
            border-color: #c13d3d;
            box-shadow: 0 0 0 2px rgba(193, 61, 61, 0.14);
            background: #fff8f8;
        }

        .ops-field.has-invalid label {
            color: #8a2f2f;
        }

        .ops-textarea {
            min-height: 90px;
            resize: vertical;
        }

        .ops-table-wrap {
            margin-top: 10px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            overflow-x: auto;
            overflow-y: hidden;
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

        .ops-table.is-compact th,
        .ops-table.is-compact td {
            padding: 7px 8px;
        }

        .ops-table.is-compact th:last-child,
        .ops-table.is-compact td:last-child {
            width: auto;
            white-space: normal;
        }

        .ops-table.is-compact td:last-child {
            min-width: 0;
        }

        .listing-management-table th:nth-child(1),
        .listing-management-table td:nth-child(1) {
            width: auto;
        }

        .listing-management-table th:nth-child(2),
        .listing-management-table td:nth-child(2) {
            width: auto;
        }

        .room-management-table th:nth-child(1),
        .room-management-table td:nth-child(1) {
            width: auto;
        }

        .room-management-table th:nth-child(2),
        .room-management-table td:nth-child(2) {
            width: auto;
        }

        .room-management-table th:nth-child(3),
        .room-management-table td:nth-child(3) {
            width: auto;
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

        .category-view-panels {
            margin-top: 8px;
            display: grid;
            gap: 8px;
        }

        .category-view-panel {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #f8fcff;
            padding: 10px;
        }

        .category-view-panel strong {
            display: block;
            color: #1f3346;
            margin-bottom: 4px;
            font-size: 0.84rem;
        }

        .category-view-panel p {
            margin: 0;
            color: #446079;
            font-size: 0.78rem;
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
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
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

        .listing-summary-line {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .listing-cell-main {
            min-width: 240px;
        }

        .listing-cell-actions-cell {
            min-width: 0;
        }

        .listing-management-table tr.is-editing .listing-cell-main {
            display: none;
        }

        .listing-management-table tr.is-editing .listing-cell-actions-cell {
            width: 100%;
        }

        .listing-management-table tr.is-editing .listing-cell-actions {
            grid-template-columns: 1fr;
        }

        .room-management-table tr.is-editing td:nth-child(1),
        .room-management-table tr.is-editing td:nth-child(2) {
            display: none;
        }

        .room-management-table tr.is-editing td:nth-child(3) {
            width: 100%;
        }

        .listing-summary-line strong {
            margin-right: 4px;
        }

        .listing-actions-inline {
            align-items: center;
        }

        .listing-status-chip.is-active {
            border-color: #9fd4b3;
            background: #edf9f1;
            color: #215336;
        }

        .listing-status-chip.is-inactive {
            border-color: #e6c9c9;
            background: #fff3f3;
            color: #8a2f2f;
        }

        .listing-status-chip.is-neutral {
            border-color: #d7e0e6;
            background: #f7fbff;
            color: #3a5b78;
        }

        .room-summary-line {
            color: #3a5166;
            white-space: nowrap;
        }

        .listing-actions-inline form {
            margin: 0;
        }

        .update-row-form,
        .media-upload-row {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fafcff;
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        .update-row-form.inline-table-form {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .update-row-form .ops-textarea,
        .update-row-form .ops-form-grid,
        .update-row-form .feature-checklist,
        .update-row-form .inline-actions {
            grid-column: 1 / -1;
        }

        .update-row-form .ops-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
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

        .listing-management-table .inline-actions,
        .room-management-table .inline-actions,
        .listing-management-table .inline-status-form,
        .room-management-table .inline-status-form {
            justify-content: flex-end;
            flex-direction: row-reverse;
        }

        .listing-management-table .inline-actions form,
        .room-management-table .inline-actions form {
            margin: 0;
        }

        .edit-toggle-actions {
            align-items: center;
        }

        .update-row-form[hidden] {
            display: none;
        }

        /* Category-specific listing edit scopes: keep only relevant fields visible per listing category */
        .update-row-form[data-property-edit-form] [data-property-edit-scope] {
            display: none;
        }

        .update-row-form[data-property-edit-category="accommodation"] [data-property-edit-scope="stay"],
        .update-row-form[data-property-edit-category="accommodation"] [data-property-edit-scope="accommodation"],
        .update-row-form[data-property-edit-category="accommodation"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="transport"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="transport"] [data-property-edit-scope="transport"],
        .update-row-form[data-property-edit-category="transport"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="excursion"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="excursion"] [data-property-edit-scope="service"],
        .update-row-form[data-property-edit-category="excursion"] [data-property-edit-scope="excursion"],
        .update-row-form[data-property-edit-category="excursion"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="remote_workspace"] [data-property-edit-scope="stay"],
        .update-row-form[data-property-edit-category="remote_workspace"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="remote_workspace"] [data-property-edit-scope="workspace"],
        .update-row-form[data-property-edit-category="remote_workspace"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="resort_day_visit"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="resort_day_visit"] [data-property-edit-scope="day_visit"],
        .update-row-form[data-property-edit-category="resort_day_visit"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="restaurant"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="restaurant"] [data-property-edit-scope="restaurant"],
        .update-row-form[data-property-edit-category="restaurant"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="vehicle_rental"] [data-property-edit-scope="vehicle"],
        .update-row-form[data-property-edit-category="vehicle_rental"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="vehicle_rental"] [data-property-edit-scope="rental"],
        .update-row-form[data-property-edit-category="vehicle_rental"] [data-property-edit-scope="geo"] {
            display: revert;
        }

        .btn-danger {
            background: #a33535;
            color: #fff;
        }

        .gallery-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 8px;
        }

        .media-dropzone {
            grid-column: 1 / -1;
            border: 1px dashed #9eb2c6;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            font-size: 0.8rem;
            color: #2f4f6c;
            background: #f7fbff;
            cursor: pointer;
        }

        .media-dropzone.is-dragover {
            border-color: #0f6b74;
            background: #ecf8f9;
            color: #0c4f56;
        }

        .media-upload-preview {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
        }

        .media-upload-item {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #ffffff;
            overflow: hidden;
        }

        .media-upload-item img {
            display: block;
            width: 100%;
            height: 90px;
            object-fit: cover;
            background: #e8eef4;
        }

        .media-upload-item .media-upload-meta {
            padding: 6px;
            display: grid;
            gap: 4px;
        }

        .media-primary-select {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            color: #31506a;
        }

        .media-remove-btn {
            border: 1px solid #d8b3b3;
            background: #fff5f5;
            color: #8e2e2e;
            border-radius: 8px;
            font-size: 0.72rem;
            padding: 4px 6px;
            cursor: pointer;
            width: fit-content;
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

        .category-listings-stack {
            margin-top: 12px;
            display: grid;
            gap: 12px;
        }

        .category-listing-section {
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #f8fcff;
            padding: 10px;
        }

        .category-listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .category-listing-header h4 {
            margin: 0;
            color: #1f3346;
            font-size: 0.92rem;
        }

        .property-subsection-head {
            margin: 0;
            font-size: 0.8rem;
            color: #365670;
            font-weight: 700;
        }

        .accommodation-room-stretch-row > td {
            background: #f5f9fc;
            border-top: 0;
            padding-top: 6px;
        }

        .accommodation-room-stretch {
            display: grid;
            gap: 8px;
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

            .update-row-form.inline-table-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

            .listing-cell-actions-cell {
                min-width: 0;
            }

            .update-row-form.inline-table-form {
                grid-template-columns: 1fr;
            }

            .ops-table.is-compact th:last-child,
            .ops-table.is-compact td:last-child {
                white-space: normal;
                width: auto;
            }

            .room-summary-line {
                white-space: normal;
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
        $listingCategoryViewOrder = ['accommodation', 'transport', 'excursion', 'remote_workspace', 'resort_day_visit', 'restaurant', 'vehicle_rental'];
        $roomsByPropertyId = $vendorRooms->groupBy(static function ($room) {
            return (int) ($room->vendor_property_id ?? 0);
        });
        $propertyMediaByPropertyId = $propertyMediaAssets->groupBy(static function ($media) {
            return (int) ($media->entity_id ?? 0);
        });
        $roomMediaByRoomId = $roomMediaAssets->groupBy(static function ($media) {
            return (int) ($media->entity_id ?? 0);
        });
        $propertiesByCategory = $vendorProperties->groupBy(static function ($property) {
            return strtolower((string) ($property->listing_category ?? ''));
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
        @include('vendor-portal.partials.sidebar')

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

        @include('vendor-portal.partials.profile')

        @include('vendor-portal.partials.billing-settings')

        <section id="vendorOperationsOverview" class="card ops-section" aria-label="Vendor operations overview" data-panel-group="listings">
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

        <section id="vendorPropertiesSection" class="card ops-section" aria-label="Vendor properties" data-panel-group="listings" data-listing-step="1">
            <div class="ops-header">
                <p class="ops-title">Properties and Listings</p>
                <span class="ops-chip">{{ $vendorProperties->count() }} total</span>
            </div>
            <div class="ops-grid properties-grid">
                @php
                    $oldPropertyAmenities = collect(old('property_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldPropertyFeatures = collect(old('property_features', []))->map(fn ($item) => (string) $item)->all();
                    $workspaceAmenityCatalog = [
                        'workdesk' => 'Workdesk',
                        'wifi' => 'WiFi',
                        'printing' => 'Printing',
                        'water_bottles' => 'Water Bottles',
                        'coffee' => 'Coffee',
                        'tea' => 'Tea',
                        'snacks' => 'Snacks',
                    ];
                    $transferOptionCatalog = [
                        'car' => 'Car',
                        'van' => 'Van',
                        'ferry' => 'Ferry',
                        'speedboat' => 'SpeedBoat',
                        'seaplane' => 'SeaPlane',
                        'domestic_flight' => 'Domestic Flight',
                    ];
                    $oldWorkspaceAmenityStatusInput = old('workspace_amenity_status', []);
                    $oldWorkspaceAmenitiesFree = collect(old('workspace_amenities_free', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldWorkspaceAmenitiesPaid = collect(old('workspace_amenities_paid', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldTransferOptions = collect(old('transfer_options', []))->map(fn ($item) => strtolower(trim((string) $item)))->values()->all();
                    $oldTransferRatesInput = old('transfer_rates', []);
                    $oldWorkspaceAmenityStatus = [];
                    foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel) {
                        $statusValue = 'not_available';
                        if (in_array($workspaceAmenityKey, $oldWorkspaceAmenitiesPaid, true)) {
                            $statusValue = 'paid';
                        } elseif (in_array($workspaceAmenityKey, $oldWorkspaceAmenitiesFree, true)) {
                            $statusValue = 'free';
                        } else {
                            $legacyStatusValue = strtolower(trim((string) ($oldWorkspaceAmenityStatusInput[$workspaceAmenityKey] ?? '')));
                            if (in_array($legacyStatusValue, ['free', 'paid', 'not_available'], true)) {
                                $statusValue = $legacyStatusValue;
                            } elseif (in_array($workspaceAmenityKey, ['workdesk', 'wifi'], true)) {
                                $statusValue = 'free';
                            }
                        }
                        $oldWorkspaceAmenityStatus[$workspaceAmenityKey] = $statusValue;
                    }
                    $oldRoomAmenities = collect(old('room_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $oldBathroomAmenities = collect(old('bathroom_amenities', []))->map(fn ($item) => (string) $item)->all();
                    $transportModeOptionsCollection = collect($transportModeOptions ?? []);
                    $transportModeOptionGroups = $transportModeOptionsCollection
                        ->groupBy(fn ($item) => strtolower(trim((string) ($item['group'] ?? 'other'))));
                    $accommodationFacilityOptionsCollection = collect($accommodationFacilityOptions ?? []);
                    $roomAmenityOptionsCollection = collect($roomAmenityOptions ?? []);
                    $bathroomAmenityOptionsCollection = collect($bathroomAmenityOptions ?? []);
                    $propertyAmenityOptionsCollection = collect($propertyAmenityOptions ?? [])->values();
                    if ($propertyAmenityOptionsCollection->isEmpty()) {
                        $propertyAmenityOptionsCollection = $accommodationFacilityOptionsCollection->values();
                    }
                    $propertyFeatureOptionsCollection = collect($propertyFeatureOptions ?? [])->values();
                    $roomBedTypeOptionsCollection = collect($roomBedTypeOptions ?? [])->values();
                    $excursionTypeOptionsCollection = collect($excursionTypeOptions ?? [])->values();
                    $restaurantMealServiceOptionsCollection = collect($restaurantMealServiceOptions ?? [])->values();
                    $vehicleRentalTypeOptionsCollection = collect($vehicleRentalTypeOptions ?? [])->values();
                    $vehicleRentalTypeOptionGroups = $vehicleRentalTypeOptionsCollection
                        ->groupBy(fn ($item) => strtolower(trim((string) ($item['group'] ?? 'other'))));
                @endphp
                <article class="ops-form ops-field-wide">
                    <form id="propertyCreateForm" class="ops-form" method="POST" action="/portal/vendor/properties/create" @if (!$showCreatePropertyForm) hidden @endif>
                        @csrf
                        <input type="hidden" name="property_form_intent" value="1">
                        <p class="guided-wizard-title" id="propertyCreateFormTitle">Accommodation Enlisting</p>
                        <p class="guided-wizard-subtitle" id="propertyCreateFormSubtitle">Fill required fields and save.</p>
                        <div class="ops-form-grid">
                            @php
                                $defaultCreateCategory = old('listing_category');
                                if (!is_string($defaultCreateCategory) || trim($defaultCreateCategory) === '') {
                                    $defaultCreateCategory = in_array('accommodation', $selectedVendorCategories, true)
                                        ? 'accommodation'
                                        : ((string) ($selectedVendorCategories[0] ?? 'accommodation'));
                                }
                            @endphp
                            <div class="ops-field" style="display:none;">
                                <select id="property_listing_category" name="listing_category" class="ops-select" data-default-category="{{ $defaultCreateCategory }}">
                                    @foreach ($selectedVendorCategories as $categoryKey)
                                        @php
                                            $categoryLabel = $vendorCategoryMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', (string) $categoryKey));
                                        @endphp
                                        <option value="{{ $categoryKey }}" @selected($defaultCreateCategory === $categoryKey)>{{ $categoryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_name">Name</label>
                                <input id="property_name" name="name" class="ops-input" type="text" maxlength="160" value="{{ old('name') }}" required>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_country">Country</label>
                                <select id="location_country" name="location_country" class="ops-select" data-selected-value="{{ old('location_country', 'Maldives') }}" required>
                                    <option value="Maldives" @selected(old('location_country', 'Maldives') === 'Maldives')>Maldives</option>
                                    <option value="Sri Lanka" @selected(old('location_country') === 'Sri Lanka')>Sri Lanka</option>
                                    <option value="India" @selected(old('location_country') === 'India')>India</option>
                                    <option value="Other" @selected(old('location_country') === 'Other')>Other</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_state">State / Province / Atoll</label>
                                <select id="location_state" name="location_state" class="ops-select" data-selected-value="{{ old('location_state') }}" required>
                                    <option value="">Select state/province</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_city">City / Island</label>
                                <select id="location_city" name="location_city" class="ops-select" data-selected-value="{{ old('location_city') }}" required>
                                    <option value="">Select city/island</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="location_ward">Ward</label>
                                <input id="location_ward" name="location_ward" class="ops-input" type="text" maxlength="120" value="{{ old('location_ward') }}" placeholder="Ward / Neighborhood">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_building_house_lot">Building / House / Lot No.</label>
                                <input id="property_building_house_lot" name="building_house_lot" class="ops-input" type="text" maxlength="160" value="{{ old('building_house_lot') }}" placeholder="e.g. Lily House, Lot 1142">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_street_name">Street</label>
                                <input id="property_street_name" name="street" class="ops-input" type="text" maxlength="160" value="{{ old('street') }}" placeholder="Street / Road">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_post_code">Post Code</label>
                                <input id="property_post_code" name="post_code" class="ops-input" type="text" maxlength="20" value="{{ old('post_code') }}" placeholder="Post code">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_name">Contact Name</label>
                                <input id="property_contact_name" name="property_contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('property_contact_name') }}" placeholder="Contact Name">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_number">Contact Number</label>
                                <input id="property_contact_number" name="property_contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('property_contact_number') }}" placeholder="Phone / WhatsApp">
                            </div>
                            <div class="ops-field" data-category-scope="geo">
                                <label for="property_contact_email">Property Contact Email</label>
                                <input id="property_contact_email" name="property_contact_email" class="ops-input" type="email" maxlength="190" value="{{ old('property_contact_email') }}" placeholder="property@example.com">
                            </div>
                            <input id="map_latitude" name="map_latitude" type="hidden" value="{{ old('map_latitude') }}">
                            <input id="map_longitude" name="map_longitude" type="hidden" value="{{ old('map_longitude') }}">
                            <input id="map_place_id" name="map_place_id" type="hidden" value="{{ old('map_place_id') }}">
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_base_price">Base Price (MVR)</label>
                                <input id="property_base_price" name="base_price" class="ops-input" type="number" min="0" step="0.01" value="{{ old('base_price') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_max_guests">Max Guests</label>
                                <input id="property_max_guests" name="max_guests" class="ops-input" type="number" min="0" max="10000" value="{{ old('max_guests') }}">
                            </div>
                            <div class="ops-field ops-field-wide">
                                <label for="property_description">Description</label>
                                <textarea id="property_description" name="description" class="ops-textarea" maxlength="3000">{{ old('description') }}</textarea>
                            </div>

                            <div class="ops-field" data-category-scope="stay">
                                <label for="property_area_value">Area Value (sqft)</label>
                                <input id="property_area_value" name="area_value" class="ops-input" type="number" min="5" max="100000" step="0.01" value="{{ old('area_value') }}" placeholder="e.g. 120">
                            </div>
                            <input name="area_unit" type="hidden" value="sqft">
                            <input name="measurement_system" type="hidden" value="imperial">
                            <div class="ops-field" data-category-scope="accommodation">
                                <label for="property_bedroom_count">Bedrooms</label>
                                <input id="property_bedroom_count" name="bedroom_count" class="ops-input" type="number" min="0" max="1000" value="{{ old('bedroom_count') }}">
                            </div>
                            <div class="ops-field" data-category-scope="capacity">
                                <label for="property_capacity_value">Capacity</label>
                                <input id="property_capacity_value" name="capacity_value" class="ops-input" type="number" min="1" max="20000" value="{{ old('capacity_value') }}" placeholder="seats, guests, units">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <p class="category-scope-note" style="margin:0;">Remote workspace booking fee is charged per guest in-app. Max Guests is the booking limit per reservation, and Capacity is total workspace seats/desks available. Extra items/services are shown for customer information and purchased separately on-site.</p>
                            </div>
                            <div class="ops-field" data-category-scope="service">
                                <label for="property_service_radius_km">Service Radius (km)</label>
                                <input id="property_service_radius_km" name="service_radius_km" class="ops-input" type="number" min="0" max="5000" step="0.1" value="{{ old('service_radius_km') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_mode">Transport Mode</label>
                                @php
                                    $transportModeOld = strtolower(trim((string) old('transport_mode', '')));
                                    $knownTransportModes = $transportModeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_transport_mode" name="transport_mode" class="ops-select">
                                    <option value="" @selected($transportModeOld === '')>Select transport mode</option>
                                    @if ($transportModeOld !== '' && !in_array($transportModeOld, $knownTransportModes, true))
                                        <option value="{{ $transportModeOld }}" selected>{{ ucfirst($transportModeOld) }} (existing)</option>
                                    @endif
                                    @foreach ($transportModeOptionGroups as $groupKey => $groupItems)
                                        @php
                                            $groupLabel = $groupKey === 'marine'
                                                ? 'Vessel / Marine'
                                                : ($groupKey === 'land' ? 'Vehicle / Land' : ucfirst(str_replace('_', ' ', (string) $groupKey)));
                                        @endphp
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($groupItems as $groupItem)
                                                @php
                                                    $groupValue = strtolower(trim((string) ($groupItem['value'] ?? '')));
                                                    $groupText = trim((string) ($groupItem['label'] ?? $groupValue));
                                                @endphp
                                                @if ($groupValue !== '' && $groupText !== '')
                                                    <option value="{{ $groupValue }}" @selected($transportModeOld === $groupValue)>{{ $groupText }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_vehicle_name">Vehicle / Vessel Name</label>
                                <input id="property_vehicle_name" name="vehicle_name" class="ops-input" type="text" maxlength="120" value="{{ old('vehicle_name') }}" placeholder="Sea Rider 01, Airport Van 3">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_registration_plate">Registration Plate No.</label>
                                <input id="property_registration_plate" name="registration_plate" class="ops-input" type="text" maxlength="80" value="{{ old('registration_plate') }}" placeholder="AB-1234 / Vessel Reg ID">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_contact_name">Contact Name</label>
                                <input id="property_contact_name" name="contact_name" class="ops-input" type="text" maxlength="120" value="{{ old('contact_name') }}" placeholder="Dispatcher / Driver / Captain">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_contact_number">Contact Number</label>
                                <input id="property_contact_number" name="contact_number" class="ops-input" type="text" maxlength="60" value="{{ old('contact_number') }}" placeholder="+960 7xxxxxx">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_transport_trip_type">Trip Type</label>
                                <select id="property_transport_trip_type" name="transport_trip_type" class="ops-select">
                                    <option value="" @selected(old('transport_trip_type') === null)>Select</option>
                                    <option value="one_way" @selected(old('transport_trip_type') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                    <option value="round_trip" @selected(old('transport_trip_type') === 'round_trip')>Round Trip</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_pickup_location">Pickup Location</label>
                                <input id="property_pickup_location" name="pickup_location" class="ops-input" type="text" maxlength="190" value="{{ old('pickup_location') }}" placeholder="Airport, Jetty, Hotel">
                            </div>
                            <div class="ops-field" data-category-scope="transport">
                                <label for="property_dropoff_location">Dropoff Location</label>
                                <input id="property_dropoff_location" name="dropoff_location" class="ops-input" type="text" maxlength="190" value="{{ old('dropoff_location') }}" placeholder="Resort, Island, City center">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_state">Departure State / Atoll</label>
                                <input id="property_transport_departure_state" name="transport_departure_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_state') }}" placeholder="e.g. Kaafu Atoll">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_departure_city">Departure City / Island</label>
                                <input id="property_transport_departure_city" name="transport_departure_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_departure_city') }}" placeholder="e.g. Male">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_state">Arrival State / Atoll</label>
                                <input id="property_transport_arrival_state" name="transport_arrival_state" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_state') }}" placeholder="e.g. Alif Dhaal Atoll">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_transport_arrival_city">Arrival City / Island</label>
                                <input id="property_transport_arrival_city" name="transport_arrival_city" class="ops-input" type="text" maxlength="120" value="{{ old('transport_arrival_city') }}" placeholder="e.g. Dhigurah">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_area_port_jetty">Departure Area / Port / Jetty</label>
                                <input id="property_departure_area_port_jetty" name="departure_area_port_jetty" class="ops-input" type="text" maxlength="190" value="{{ old('departure_area_port_jetty') }}" placeholder="Jetty / Harbor / Terminal">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_date">Departure Date</label>
                                <input id="property_departure_date" name="departure_date" class="ops-input" type="date" value="{{ old('departure_date') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_departure_time">Departure Time</label>
                                <input id="property_departure_time" name="departure_time" class="ops-input" type="time" value="{{ old('departure_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_reporting_lead_minutes">Report Before Departure (minutes)</label>
                                <input id="property_reporting_lead_minutes" name="reporting_lead_minutes" class="ops-input" type="number" min="0" max="720" step="1" value="{{ old('reporting_lead_minutes') }}" placeholder="e.g. 15 or 20">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-marine-only>
                                <label for="property_trip_duration_minutes">Trip Duration Estimate (minutes)</label>
                                <input id="property_trip_duration_minutes" name="trip_duration_minutes" class="ops-input" type="number" min="5" max="1440" value="{{ old('trip_duration_minutes') }}" placeholder="e.g. 90">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_transport_pricing_model">Land Pricing Model</label>
                                <select id="property_transport_pricing_model" name="transport_pricing_model" class="ops-select">
                                    <option value="per_trip" @selected(old('transport_pricing_model', 'per_trip') === 'per_trip')>Per Trip</option>
                                    <option value="hourly" @selected(old('transport_pricing_model') === 'hourly')>Hourly Hire</option>
                                    <option value="daily" @selected(old('transport_pricing_model') === 'daily')>Daily Hire</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_hourly_rate">Hourly Rate (MVR)</label>
                                <input id="property_hourly_rate" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('hourly_rate') }}">
                            </div>
                            <div class="ops-field" data-category-scope="transport" data-transport-land-only>
                                <label for="property_daily_rate">Daily Rate (MVR)</label>
                                <input id="property_daily_rate" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="{{ old('daily_rate') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p id="transportPricingHint" class="category-scope-note" style="margin:0;">Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.</p>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="transport">
                                <p class="category-scope-note" style="margin:0;">Use entry only to enlist transport basics. Manage fixed daily schedules and seat availability in <a href="#vendorAvailabilitySection">Availability Calendar</a>, and manage price fluctuations in <a href="#vendorPricingSection">Pricing Rules</a>.</p>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_duration_minutes">Duration (minutes)</label>
                                <input id="property_excursion_duration_minutes" name="excursion_duration_minutes" class="ops-input" type="number" min="30" max="1440" value="{{ old('excursion_duration_minutes') }}">
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_difficulty">Difficulty</label>
                                <select id="property_excursion_difficulty" name="excursion_difficulty" class="ops-select">
                                    <option value="" @selected(old('excursion_difficulty') === null)>Select</option>
                                    <option value="easy" @selected(old('excursion_difficulty') === 'easy')>Easy</option>
                                    <option value="moderate" @selected(old('excursion_difficulty') === 'moderate')>Moderate</option>
                                    <option value="hard" @selected(old('excursion_difficulty') === 'hard')>Hard</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="excursion">
                                <label for="property_excursion_type">Excursion Type</label>
                                @php
                                    $excursionTypeOld = strtolower(trim((string) old('excursion_type', '')));
                                    $knownExcursionTypes = $excursionTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_excursion_type" name="excursion_type" class="ops-select">
                                    <option value="" @selected($excursionTypeOld === '')>Select</option>
                                    @if ($excursionTypeOld !== '' && !in_array($excursionTypeOld, $knownExcursionTypes, true))
                                        <option value="{{ $excursionTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $excursionTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($excursionTypeOptionsCollection as $excursionTypeOption)
                                        @php
                                            $excursionTypeValue = strtolower(trim((string) ($excursionTypeOption['value'] ?? '')));
                                            $excursionTypeLabel = trim((string) ($excursionTypeOption['label'] ?? $excursionTypeValue));
                                        @endphp
                                        @if ($excursionTypeValue !== '' && $excursionTypeLabel !== '')
                                            <option value="{{ $excursionTypeValue }}" @selected($excursionTypeOld === $excursionTypeValue)>{{ $excursionTypeLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_workspace_type">Workspace Type</label>
                                <select id="property_workspace_type" name="workspace_type" class="ops-select">
                                    <option value="" @selected(old('workspace_type') === null)>Select</option>
                                    <option value="shared" @selected(old('workspace_type') === 'shared')>Shared</option>
                                    <option value="private" @selected(old('workspace_type') === 'private')>Private</option>
                                    <option value="cabin" @selected(old('workspace_type') === 'cabin')>Cabin</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="workspace">
                                <label for="property_internet_speed_mbps">Internet Speed (Mbps)</label>
                                <input id="property_internet_speed_mbps" name="internet_speed_mbps" class="ops-input" type="number" min="1" max="10000" step="1" value="{{ old('internet_speed_mbps') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="workspace">
                                <label>Workspace Amenities (information only)</label>
                                <p class="small" style="margin-bottom:8px;">Free Amenities (tick all)</p>
                                <div class="feature-checklist" style="margin-bottom:10px;">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'free')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small" style="margin-bottom:8px;">Paid Amenities (tick all)</p>
                                <div class="feature-checklist">
                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                        @php
                                            $workspaceStatusValue = (string) ($oldWorkspaceAmenityStatus[$workspaceAmenityKey] ?? 'not_available');
                                        @endphp
                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked($workspaceStatusValue === 'paid')> {{ $workspaceAmenityLabel }}</label>
                                    @endforeach
                                </div>
                                <p class="small">Set each item as Free, Purchase Separately On-Site, or Not Available. The app only collects the booking fee; extras are purchased separately.</p>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="stay">
                                <label>Transfer Options and Charges (Per Pax)</label>
                                <p class="small" style="margin-bottom:8px;">Select transfer options you provide and set the per-pax charge for each selected option.</p>
                                <div class="ops-form-grid">
                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                        @php
                                            $transferRateValue = '';
                                            if (is_array($oldTransferRatesInput) && array_key_exists($transferOptionKey, $oldTransferRatesInput)) {
                                                $transferRateValue = (string) $oldTransferRatesInput[$transferOptionKey];
                                            }
                                        @endphp
                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $oldTransferOptions, true))>
                                            <span>{{ $transferOptionLabel }}</span>
                                        </label>
                                        <input name="transfer_rates[{{ $transferOptionKey }}]" class="ops-input" type="number" min="0" step="0.01" value="{{ $transferRateValue }}" placeholder="{{ $transferOptionLabel }} per pax (MVR)">
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_start_time">Day Visit Start Time</label>
                                <input id="property_day_visit_start_time" name="day_visit_start_time" class="ops-input" type="time" value="{{ old('day_visit_start_time') }}">
                            </div>
                            <div class="ops-field" data-category-scope="day_visit">
                                <label for="property_day_visit_end_time">Day Visit End Time</label>
                                <input id="property_day_visit_end_time" name="day_visit_end_time" class="ops-input" type="time" value="{{ old('day_visit_end_time') }}">
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="day_visit">
                                <label for="property_included_access">Included Access</label>
                                <textarea id="property_included_access" name="included_access" class="ops-textarea" maxlength="2000" placeholder="Pool access, lunch, transfer, spa credits, etc.">{{ old('included_access') }}</textarea>
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_cuisine_type">Cuisine Type</label>
                                <input id="property_cuisine_type" name="cuisine_type" class="ops-input" type="text" maxlength="120" value="{{ old('cuisine_type') }}" placeholder="Maldivian, Asian Fusion, Seafood">
                            </div>
                            <div class="ops-field" data-category-scope="restaurant">
                                <label for="property_meal_service">Meal Service</label>
                                @php
                                    $mealServiceOld = strtolower(trim((string) old('meal_service', '')));
                                    $knownMealServices = $restaurantMealServiceOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_meal_service" name="meal_service" class="ops-select">
                                    <option value="" @selected($mealServiceOld === '')>Select</option>
                                    @if ($mealServiceOld !== '' && !in_array($mealServiceOld, $knownMealServices, true))
                                        <option value="{{ $mealServiceOld }}" selected>{{ ucfirst(str_replace('_', ' ', $mealServiceOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($restaurantMealServiceOptionsCollection as $mealServiceOption)
                                        @php
                                            $mealServiceValue = strtolower(trim((string) ($mealServiceOption['value'] ?? '')));
                                            $mealServiceLabel = trim((string) ($mealServiceOption['label'] ?? $mealServiceValue));
                                        @endphp
                                        @if ($mealServiceValue !== '' && $mealServiceLabel !== '')
                                            <option value="{{ $mealServiceValue }}" @selected($mealServiceOld === $mealServiceValue)>{{ $mealServiceLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="vehicle">
                                <label for="property_minimum_age">Minimum Age</label>
                                <input id="property_minimum_age" name="minimum_age" class="ops-input" type="number" min="0" max="120" value="{{ old('minimum_age') }}">
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_vehicle_type">Vehicle Type</label>
                                @php
                                    $vehicleTypeOld = strtolower(trim((string) old('vehicle_type', '')));
                                    $knownVehicleTypes = $vehicleRentalTypeOptionsCollection
                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                        ->filter(fn ($item) => $item !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                <select id="property_vehicle_type" name="vehicle_type" class="ops-select">
                                    <option value="" @selected($vehicleTypeOld === '')>Select Vehicle Type</option>
                                    @if ($vehicleTypeOld !== '' && !in_array($vehicleTypeOld, $knownVehicleTypes, true))
                                        <option value="{{ $vehicleTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $vehicleTypeOld)) }} (existing)</option>
                                    @endif
                                    @foreach ($vehicleRentalTypeOptionGroups as $vehicleGroupKey => $vehicleGroupItems)
                                        @php
                                            $vehicleGroupLabel = $vehicleGroupKey === 'land'
                                                ? 'Land Vehicles'
                                                : ($vehicleGroupKey === 'marine' ? 'Marine Vessels' : ucfirst(str_replace('_', ' ', (string) $vehicleGroupKey)));
                                        @endphp
                                        <optgroup label="{{ $vehicleGroupLabel }}">
                                            @foreach ($vehicleGroupItems as $vehicleTypeOption)
                                                @php
                                                    $vehicleTypeValue = strtolower(trim((string) ($vehicleTypeOption['value'] ?? '')));
                                                    $vehicleTypeLabel = trim((string) ($vehicleTypeOption['label'] ?? $vehicleTypeValue));
                                                @endphp
                                                @if ($vehicleTypeValue !== '' && $vehicleTypeLabel !== '')
                                                    <option value="{{ $vehicleTypeValue }}" @selected($vehicleTypeOld === $vehicleTypeValue)>{{ $vehicleTypeLabel }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_transmission_type">Transmission</label>
                                <select id="property_transmission_type" name="transmission_type" class="ops-select">
                                    <option value="" @selected(old('transmission_type') === null)>Select</option>
                                    <option value="automatic" @selected(old('transmission_type') === 'automatic')>Automatic</option>
                                    <option value="manual" @selected(old('transmission_type') === 'manual')>Manual</option>
                                </select>
                            </div>
                            <div class="ops-field" data-category-scope="rental">
                                <label for="property_fuel_type">Fuel Type</label>
                                <select id="property_fuel_type" name="fuel_type" class="ops-select">
                                    <option value="" @selected(old('fuel_type') === null)>Select</option>
                                    <option value="petrol" @selected(old('fuel_type') === 'petrol')>Petrol</option>
                                    <option value="diesel" @selected(old('fuel_type') === 'diesel')>Diesel</option>
                                    <option value="electric" @selected(old('fuel_type') === 'electric')>Electric</option>
                                    <option value="hybrid" @selected(old('fuel_type') === 'hybrid')>Hybrid</option>
                                </select>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Amenities (tick all available)</label>
                                <div class="feature-checklist">
                                    @foreach ($propertyAmenityOptionsCollection as $facilityOption)
                                        @php
                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                        @endphp
                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $oldPropertyAmenities, true))> {{ $facilityLabel }}</label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="accommodation">
                                <label>Property Features (tick all available)</label>
                                <div class="feature-checklist">
                                    @foreach ($propertyFeatureOptionsCollection as $featureOption)
                                        @php
                                            $featureValue = trim((string) ($featureOption['value'] ?? ''));
                                            $featureLabel = trim((string) ($featureOption['label'] ?? $featureValue));
                                        @endphp
                                        @if ($featureValue !== '' && $featureLabel !== '')
                                            <label class="feature-item"><input type="checkbox" name="property_features[]" value="{{ $featureValue }}" @checked(in_array($featureValue, $oldPropertyFeatures, true))> {{ $featureLabel }}</label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="ops-field ops-field-wide" data-category-scope="geo">
                                <div class="map-picker">
                                    <div id="propertyMap" aria-label="Map picker"></div>
                                </div>
                                <p class="map-help">Click the map to drop a pin. Coordinates are captured automatically.</p>
                            </div>
                        </div>
                        <p class="standards-note">International listing standard: fields adapt to selected category. Create one property at a time, then add rooms under that property.</p>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="propertyCreateSubmitButton" type="submit">Save Listing</button>
                            <button class="btn btn-secondary" id="closePropertyCreateForm" type="button">Cancel</button>
                            <button class="btn btn-secondary" id="backToListingsFromCreate" type="button">Back To Listings</button>
                        </div>
                    </form>

                </article>
                <div class="category-listings-stack" aria-label="Category listing views">
                    @foreach ($listingCategoryViewOrder as $categoryKey)
                        @php
                            $categoryProperties = $propertiesByCategory->get($categoryKey, collect());
                            $categoryLabel = $vendorCategoryMap[$categoryKey] ?? strtoupper(str_replace('_', ' ', $categoryKey));
                        @endphp
                        <article class="category-listing-section" id="category-view-{{ $categoryKey }}">
                            <div class="category-listing-header">
                                <h4>{{ $categoryLabel }} Listings</h4>
                                <div class="inline-actions">
                                    <span class="ops-chip">{{ $categoryProperties->count() }} listed</span>
                                    <button type="button" class="btn btn-secondary" data-listing-category-shortcut="{{ $categoryKey }}">Add {{ $categoryLabel }}</button>
                                </div>
                            </div>
                            @if ($categoryProperties->isEmpty())
                                <p class="ops-empty">No {{ strtolower((string) $categoryLabel) }} listings yet.</p>
                            @else
                                <div class="ops-table-wrap">
                                    <table class="ops-table is-compact listing-management-table" aria-label="{{ $categoryLabel }} listings table">
                                        <thead>
                                            <tr>
                                                <th>Listing</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($categoryProperties as $property)
                                                @php
                                                    $propertyId = (int) ($property->id ?? 0);
                                                    $propertyRooms = $roomsByPropertyId->get($propertyId, collect());
                                                    $propertyDetails = [];
                                                    if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                                                        $decodedPropertyDetails = json_decode((string) $property->listing_details, true);
                                                        if (is_array($decodedPropertyDetails)) {
                                                            $propertyDetails = $decodedPropertyDetails;
                                                        }
                                                    }
                                                    $editCategoryRaw = strtolower((string) ($property->listing_category ?? $categoryKey));
                                                    $editCategory = preg_replace('/[^a-z0-9]+/', '_', $editCategoryRaw) ?? $editCategoryRaw;
                                                    $editCategory = trim((string) preg_replace('/_+/', '_', $editCategory), '_');
                                                    $propertyAmenityValues = [];
                                                    $propertyFeatureValues = [];
                                                    if (isset($propertyDetails['property_amenities']) && is_array($propertyDetails['property_amenities'])) {
                                                        $propertyAmenityValues = array_map(static fn ($item) => (string) $item, $propertyDetails['property_amenities']);
                                                    }
                                                    if (isset($propertyDetails['property_features']) && is_array($propertyDetails['property_features'])) {
                                                        $propertyFeatureValues = array_map(static fn ($item) => (string) $item, $propertyDetails['property_features']);
                                                    }
                                                    $workspaceAmenityConfig = is_array($propertyDetails['workspace_amenities'] ?? null)
                                                        ? $propertyDetails['workspace_amenities']
                                                        : [];
                                                    $workspaceAmenityFreeValues = [];
                                                    $workspaceAmenityPaidValues = [];
                                                    foreach ($workspaceAmenityConfig as $workspaceAmenityKey => $workspaceAmenityRow) {
                                                        if (!is_array($workspaceAmenityRow)) {
                                                            continue;
                                                        }
                                                        $workspaceAmenityStatus = strtolower(trim((string) ($workspaceAmenityRow['status'] ?? 'not_available')));
                                                        if ($workspaceAmenityStatus === 'free') {
                                                            $workspaceAmenityFreeValues[] = strtolower(trim((string) $workspaceAmenityKey));
                                                        } elseif ($workspaceAmenityStatus === 'paid') {
                                                            $workspaceAmenityPaidValues[] = strtolower(trim((string) $workspaceAmenityKey));
                                                        }
                                                    }
                                                    $transferOptionValues = [];
                                                    if (isset($propertyDetails['transfer_options']) && is_array($propertyDetails['transfer_options'])) {
                                                        $transferOptionValues = array_map(static fn ($item) => strtolower(trim((string) $item)), $propertyDetails['transfer_options']);
                                                    }
                                                    $transferRates = is_array($propertyDetails['transfer_rates'] ?? null)
                                                        ? $propertyDetails['transfer_rates']
                                                        : [];
                                                    $transportMode = strtolower((string) ($propertyDetails['transport_mode'] ?? ''));
                                                    $transportPricingBasis = strtolower((string) ($propertyDetails['transport_pricing_basis'] ?? ''));
                                                    if ($transportPricingBasis === '') {
                                                        $transportPricingBasis = preg_match('/\b(speed ?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)\b/', $transportMode) ? 'per_seat' : 'per_trip';
                                                    }
                                                    $transportTripType = strtolower((string) ($propertyDetails['transport_trip_type'] ?? ''));
                                                    $transportPricingModel = strtolower((string) ($propertyDetails['transport_pricing_model'] ?? ''));
                                                    $listingStatus = strtoupper((string) ($property->status ?? 'active'));
                                                    $listingType = strtoupper((string) ($property->property_type ?? 'N/A'));
                                                    $propertyMediaItems = $propertyMediaByPropertyId->get($propertyId, collect());
                                                    $listingStatusClass = 'is-neutral';
                                                    if (strtolower($listingStatus) === 'active') {
                                                        $listingStatusClass = 'is-active';
                                                    } elseif (strtolower($listingStatus) === 'inactive') {
                                                        $listingStatusClass = 'is-inactive';
                                                    }
                                                @endphp
                                                <tr data-property-row="{{ $propertyId }}">
                                                    <td class="listing-cell-main">
                                                        <div class="listing-summary-line">
                                                            <strong>{{ $property->name }}</strong>
                                                            <span class="ops-chip">ID {{ $propertyId }}</span>
                                                            <span class="ops-chip">{{ $listingType }}</span>
                                                            <span class="ops-chip listing-status-chip {{ $listingStatusClass }}">{{ $listingStatus }}</span>
                                                            @if ($categoryKey === 'accommodation')
                                                                <span class="ops-chip">Rooms: {{ $propertyRooms->count() }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="listing-cell-actions-cell">
                                                        <div class="listing-cell-actions">
                                                            <div class="inline-actions listing-actions-inline">
                                                                <button class="btn btn-secondary" type="button" data-open-property-edit data-property-edit-id="{{ $propertyId }}" data-property-edit-category="{{ $editCategory }}">Edit Listing</button>
                                                                @if ($categoryKey === 'accommodation')
                                                                    <button class="btn btn-secondary" type="button" data-open-room-form data-property-id="{{ $propertyId }}">Add Room</button>
                                                                @endif
                                                                <button class="btn btn-secondary" type="button" data-toggle-property-media="{{ $propertyId }}">Manage Media</button>
                                                                <form method="POST" action="/portal/vendor/properties/{{ $propertyId }}/delete" onsubmit="return confirm('Remove this listing?');">
                                                                    @csrf
                                                                    <button class="btn btn-danger" type="submit">Remove Listing</button>
                                                                </form>
                                                            </div>
                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/properties/{{ $propertyId }}/update" data-property-edit-form="{{ $propertyId }}" data-property-edit-category="{{ $editCategory }}" hidden>
                                                                @csrf
                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $property->name }}" required>
                                                                <input class="ops-input" name="location_country" type="text" maxlength="90" value="{{ (string) ($propertyDetails['location_country'] ?? '') }}" placeholder="Country" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_state'] ?? '') }}" placeholder="State / Province / Atoll" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_city'] ?? '') }}" placeholder="City / Island" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="location_ward" type="text" maxlength="120" value="{{ (string) ($propertyDetails['location_ward'] ?? '') }}" placeholder="Ward / Neighborhood" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="address_line" type="text" maxlength="255" value="{{ (string) ($propertyDetails['address_line'] ?? '') }}" placeholder="Address line" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="building_house_lot" type="text" maxlength="160" value="{{ (string) ($propertyDetails['building_house_lot'] ?? '') }}" placeholder="Building / House / Lot No." data-property-edit-scope="geo">
                                                                <input class="ops-input" name="street" type="text" maxlength="160" value="{{ (string) ($propertyDetails['street'] ?? '') }}" placeholder="Street" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="post_code" type="text" maxlength="20" value="{{ (string) ($propertyDetails['post_code'] ?? '') }}" placeholder="Post code" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['property_contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['property_contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="geo">
                                                                <input class="ops-input" name="property_contact_email" type="email" maxlength="190" value="{{ (string) ($propertyDetails['property_contact_email'] ?? '') }}" placeholder="Property Contact Email" data-property-edit-scope="geo">
                                                                <input name="map_latitude" type="hidden" value="{{ (string) ($propertyDetails['map_latitude'] ?? '') }}">
                                                                <input name="map_longitude" type="hidden" value="{{ (string) ($propertyDetails['map_longitude'] ?? '') }}">
                                                                <input name="map_place_id" type="hidden" value="{{ (string) ($propertyDetails['map_place_id'] ?? '') }}">
                                                                <textarea class="ops-textarea" name="description" maxlength="3000" placeholder="Description">{{ (string) ($property->description ?? '') }}</textarea>
                                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) ($property->base_price ?? 0) }}" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="max_guests" type="number" min="0" max="10000" value="{{ (int) ($property->max_guests ?? 0) }}" data-property-edit-scope="capacity">

                                                                <input class="ops-input" name="area_value" type="number" min="5" max="100000" step="0.01" value="{{ (string) ($propertyDetails['area_value'] ?? '') }}" placeholder="Area Value (sqft)" data-property-edit-scope="stay">
                                                                <input name="area_unit" type="hidden" value="sqft" data-property-edit-scope="stay">
                                                                <input name="measurement_system" type="hidden" value="imperial" data-property-edit-scope="stay">
                                                                <input class="ops-input" name="bedroom_count" type="number" min="0" max="1000" value="{{ (string) ($propertyDetails['bedroom_count'] ?? '') }}" placeholder="Bedrooms" data-property-edit-scope="accommodation">
                                                                <input class="ops-input" name="capacity_value" type="number" min="1" max="20000" value="{{ (string) ($propertyDetails['capacity_value'] ?? '') }}" placeholder="Capacity" data-property-edit-scope="capacity">
                                                                <input class="ops-input" name="service_radius_km" type="number" min="0" max="5000" step="0.1" value="{{ (string) ($propertyDetails['service_radius_km'] ?? '') }}" placeholder="Service Radius (km)" data-property-edit-scope="service">
                                                                @php
                                                                    $transportModeEdit = strtolower(trim((string) ($propertyDetails['transport_mode'] ?? '')));
                                                                    $knownTransportModes = $transportModeOptionsCollection
                                                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                        ->filter(fn ($item) => $item !== '')
                                                                        ->values()
                                                                        ->all();
                                                                @endphp
                                                                <select class="ops-select" name="transport_mode" data-property-edit-scope="transport">
                                                                    <option value="" @selected($transportModeEdit === '')>Transport Mode</option>
                                                                    @if ($transportModeEdit !== '' && !in_array($transportModeEdit, $knownTransportModes, true))
                                                                        <option value="{{ $transportModeEdit }}" selected>{{ ucfirst($transportModeEdit) }} (existing)</option>
                                                                    @endif
                                                                    @foreach ($transportModeOptionGroups as $groupKey => $groupItems)
                                                                        @php
                                                                            $groupLabel = $groupKey === 'marine'
                                                                                ? 'Vessel / Marine'
                                                                                : ($groupKey === 'land' ? 'Vehicle / Land' : ucfirst(str_replace('_', ' ', (string) $groupKey)));
                                                                        @endphp
                                                                        <optgroup label="{{ $groupLabel }}">
                                                                            @foreach ($groupItems as $groupItem)
                                                                                @php
                                                                                    $groupValue = strtolower(trim((string) ($groupItem['value'] ?? '')));
                                                                                    $groupText = trim((string) ($groupItem['label'] ?? $groupValue));
                                                                                @endphp
                                                                                @if ($groupValue !== '' && $groupText !== '')
                                                                                    <option value="{{ $groupValue }}" @selected($transportModeEdit === $groupValue)>{{ $groupText }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                                <input class="ops-input" name="vehicle_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_name'] ?? '') }}" placeholder="Vehicle / Vessel Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="registration_plate" type="text" maxlength="80" value="{{ (string) ($propertyDetails['registration_plate'] ?? '') }}" placeholder="Registration Plate" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_name" type="text" maxlength="120" value="{{ (string) ($propertyDetails['contact_name'] ?? '') }}" placeholder="Contact Name" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="contact_number" type="text" maxlength="60" value="{{ (string) ($propertyDetails['contact_number'] ?? '') }}" placeholder="Contact Number" data-property-edit-scope="transport">
                                                                <select class="ops-select" name="transport_trip_type" data-property-edit-scope="transport">
                                                                    <option value="" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === '')>Trip Type</option>
                                                                    <option value="one_way" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'one_way')>Pickup to Dropoff (One-way)</option>
                                                                    <option value="round_trip" @selected((string) ($propertyDetails['transport_trip_type'] ?? '') === 'round_trip')>Round Trip</option>
                                                                </select>
                                                                <select class="ops-select" name="transport_pricing_model" data-property-edit-scope="transport">
                                                                    <option value="per_trip" @selected((string) ($propertyDetails['transport_pricing_model'] ?? 'per_trip') === 'per_trip')>Per Trip</option>
                                                                    <option value="hourly" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'hourly')>Hourly Hire</option>
                                                                    <option value="daily" @selected((string) ($propertyDetails['transport_pricing_model'] ?? '') === 'daily')>Daily Hire</option>
                                                                </select>
                                                                <input class="ops-input" name="hourly_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['hourly_rate'] ?? '') }}" placeholder="Hourly Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="daily_rate" type="number" min="0" step="0.01" value="{{ (string) ($propertyDetails['daily_rate'] ?? '') }}" placeholder="Daily Rate (MVR)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="pickup_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['pickup_location'] ?? '') }}" placeholder="Pickup Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="dropoff_location" type="text" maxlength="190" value="{{ (string) ($propertyDetails['dropoff_location'] ?? '') }}" placeholder="Dropoff Location" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_state'] ?? '') }}" placeholder="Departure State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_departure_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_departure_city'] ?? '') }}" placeholder="Departure City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_state" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_state'] ?? '') }}" placeholder="Arrival State / Atoll" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="transport_arrival_city" type="text" maxlength="120" value="{{ (string) ($propertyDetails['transport_arrival_city'] ?? '') }}" placeholder="Arrival City / Island" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_area_port_jetty" type="text" maxlength="190" value="{{ (string) ($propertyDetails['departure_area_port_jetty'] ?? '') }}" placeholder="Departure Area / Port / Jetty" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_date" type="date" value="{{ (string) ($propertyDetails['departure_date'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="departure_time" type="time" value="{{ (string) ($propertyDetails['departure_time'] ?? '') }}" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="reporting_lead_minutes" type="number" min="0" max="720" step="1" value="{{ (string) ($propertyDetails['reporting_lead_minutes'] ?? '') }}" placeholder="Report Before Departure (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="trip_duration_minutes" type="number" min="5" max="1440" value="{{ (string) ($propertyDetails['trip_duration_minutes'] ?? '') }}" placeholder="Trip Duration (min)" data-property-edit-scope="transport">
                                                                <input class="ops-input" name="excursion_duration_minutes" type="number" min="30" max="1440" value="{{ (string) ($propertyDetails['excursion_duration_minutes'] ?? '') }}" placeholder="Excursion Duration (min)" data-property-edit-scope="excursion">
                                                                <select class="ops-select" name="excursion_difficulty" data-property-edit-scope="excursion">
                                                                    <option value="" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === '')>Difficulty</option>
                                                                    <option value="easy" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'easy')>Easy</option>
                                                                    <option value="moderate" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'moderate')>Moderate</option>
                                                                    <option value="hard" @selected((string) ($propertyDetails['excursion_difficulty'] ?? '') === 'hard')>Hard</option>
                                                                </select>
                                                                <select class="ops-select" name="workspace_type" data-property-edit-scope="workspace">
                                                                    <option value="" @selected((string) ($propertyDetails['workspace_type'] ?? '') === '')>Workspace Type</option>
                                                                    <option value="shared" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'shared')>Shared</option>
                                                                    <option value="private" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'private')>Private</option>
                                                                    <option value="cabin" @selected((string) ($propertyDetails['workspace_type'] ?? '') === 'cabin')>Cabin</option>
                                                                </select>
                                                                <input class="ops-input" name="internet_speed_mbps" type="number" min="1" max="10000" step="1" value="{{ (string) ($propertyDetails['internet_speed_mbps'] ?? '') }}" placeholder="Internet Speed (Mbps)" data-property-edit-scope="workspace">
                                                                <div class="ops-form-grid" data-property-edit-scope="workspace">
                                                                    <p class="small" style="margin:0;">Free Amenities (tick all)</p>
                                                                    <div class="feature-checklist" style="margin-bottom:8px;">
                                                                        @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                            @php
                                                                                $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                            @endphp
                                                                            <label class="feature-item"><input type="checkbox" name="workspace_amenities_free[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityFreeValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                        @endforeach
                                                                    </div>
                                                                    <p class="small" style="margin:0;">Paid Amenities (tick all)</p>
                                                                    <div class="feature-checklist">
                                                                    @foreach ($workspaceAmenityCatalog as $workspaceAmenityKey => $workspaceAmenityLabel)
                                                                        @php
                                                                            $workspaceAmenityKeyNormalized = strtolower(trim((string) $workspaceAmenityKey));
                                                                        @endphp
                                                                        <label class="feature-item"><input type="checkbox" name="workspace_amenities_paid[]" value="{{ $workspaceAmenityKey }}" @checked(in_array($workspaceAmenityKeyNormalized, $workspaceAmenityPaidValues, true))> {{ $workspaceAmenityLabel }}</label>
                                                                    @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="ops-form-grid" data-property-edit-scope="stay">
                                                                    <p class="small" style="margin:0;">Transfer Options and Charges (Per Pax)</p>
                                                                    @foreach ($transferOptionCatalog as $transferOptionKey => $transferOptionLabel)
                                                                        @php
                                                                            $transferEditRate = '';
                                                                            if (array_key_exists($transferOptionKey, $transferRates)) {
                                                                                $transferEditRate = (string) $transferRates[$transferOptionKey];
                                                                            }
                                                                        @endphp
                                                                        <label class="feature-item" style="display:flex; align-items:center; gap:8px;">
                                                                            <input type="checkbox" name="transfer_options[]" value="{{ $transferOptionKey }}" @checked(in_array($transferOptionKey, $transferOptionValues, true))>
                                                                            <span>{{ $transferOptionLabel }}</span>
                                                                        </label>
                                                                        <input class="ops-input" name="transfer_rates[{{ $transferOptionKey }}]" type="number" min="0" step="0.01" value="{{ $transferEditRate }}" placeholder="{{ $transferOptionLabel }} per pax (MVR)">
                                                                    @endforeach
                                                                </div>
                                                                <input class="ops-input" name="day_visit_start_time" type="time" value="{{ (string) ($propertyDetails['day_visit_start_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="day_visit_end_time" type="time" value="{{ (string) ($propertyDetails['day_visit_end_time'] ?? '') }}" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="included_access" type="text" maxlength="2000" value="{{ (string) ($propertyDetails['included_access'] ?? '') }}" placeholder="Included Access" data-property-edit-scope="day_visit">
                                                                <input class="ops-input" name="cuisine_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['cuisine_type'] ?? '') }}" placeholder="Cuisine Type" data-property-edit-scope="restaurant">
                                                                <select class="ops-select" name="meal_service" data-property-edit-scope="restaurant">
                                                                    <option value="" @selected((string) ($propertyDetails['meal_service'] ?? '') === '')>Meal Service</option>
                                                                    <option value="breakfast" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'breakfast')>Breakfast</option>
                                                                    <option value="lunch" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'lunch')>Lunch</option>
                                                                    <option value="dinner" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'dinner')>Dinner</option>
                                                                    <option value="all_day" @selected((string) ($propertyDetails['meal_service'] ?? '') === 'all_day')>All Day</option>
                                                                </select>
                                                                <input class="ops-input" name="minimum_age" type="number" min="0" max="120" value="{{ (string) ($propertyDetails['minimum_age'] ?? '') }}" placeholder="Minimum Age" data-property-edit-scope="vehicle">
                                                                <input class="ops-input" name="vehicle_type" type="text" maxlength="120" value="{{ (string) ($propertyDetails['vehicle_type'] ?? '') }}" placeholder="Vehicle Type" data-property-edit-scope="rental">
                                                                <select class="ops-select" name="transmission_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['transmission_type'] ?? '') === '')>Transmission</option>
                                                                    <option value="automatic" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'automatic')>Automatic</option>
                                                                    <option value="manual" @selected((string) ($propertyDetails['transmission_type'] ?? '') === 'manual')>Manual</option>
                                                                </select>
                                                                <select class="ops-select" name="fuel_type" data-property-edit-scope="rental">
                                                                    <option value="" @selected((string) ($propertyDetails['fuel_type'] ?? '') === '')>Fuel Type</option>
                                                                    <option value="petrol" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'petrol')>Petrol</option>
                                                                    <option value="diesel" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'diesel')>Diesel</option>
                                                                    <option value="electric" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'electric')>Electric</option>
                                                                    <option value="hybrid" @selected((string) ($propertyDetails['fuel_type'] ?? '') === 'hybrid')>Hybrid</option>
                                                                </select>

                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    @foreach ($accommodationFacilityOptionsCollection as $facilityOption)
                                                                        @php
                                                                            $facilityValue = trim((string) ($facilityOption['value'] ?? ''));
                                                                            $facilityLabel = trim((string) ($facilityOption['label'] ?? $facilityValue));
                                                                        @endphp
                                                                        @if ($facilityValue !== '' && $facilityLabel !== '')
                                                                            <label class="feature-item"><input type="checkbox" name="property_amenities[]" value="{{ $facilityValue }}" @checked(in_array($facilityValue, $propertyAmenityValues, true))> {{ $facilityLabel }}</label>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <div class="feature-checklist" data-property-edit-scope="accommodation">
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="wheelchair_access" @checked(in_array('wheelchair_access', $propertyFeatureValues, true))> Wheelchair Access</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="elevator" @checked(in_array('elevator', $propertyFeatureValues, true))> Elevator</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="family_friendly" @checked(in_array('family_friendly', $propertyFeatureValues, true))> Family Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="pet_friendly" @checked(in_array('pet_friendly', $propertyFeatureValues, true))> Pet Friendly</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="beachfront" @checked(in_array('beachfront', $propertyFeatureValues, true))> Beachfront</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="sea_view" @checked(in_array('sea_view', $propertyFeatureValues, true))> Sea View</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="safety_certified" @checked(in_array('safety_certified', $propertyFeatureValues, true))> Safety Certified</label>
                                                                    <label class="feature-item"><input type="checkbox" name="property_features[]" value="kids_play_area" @checked(in_array('kids_play_area', $propertyFeatureValues, true))> Kids Play Area</label>
                                                                </div>

                                                                <select class="ops-select" name="status" required>
                                                                    <option value="active" @selected((string) ($property->status ?? '') === 'active')>Active</option>
                                                                    <option value="inactive" @selected((string) ($property->status ?? '') === 'inactive')>Inactive</option>
                                                                </select>
                                                                <div class="inline-actions">
                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Listing</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-property-edit data-property-edit-id="{{ $propertyId }}">Cancel Edit</button>
                                                                </div>
                                                            </form>
                                                            <div class="media-upload-row" data-property-media-panel="{{ $propertyId }}" hidden>
                                                                <form class="inline-table-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                    @csrf
                                                                    <input type="hidden" name="entity_type" value="property">
                                                                    <input type="hidden" name="entity_id" value="{{ $propertyId }}">
                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $property->name }} photo" placeholder="Photo alt text" required>
                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                    <p class="small" style="grid-column:1 / -1; margin:0;">Upload standard: JPG/PNG/WebP, any dimensions accepted, max 2MB per image. Recommended quality source: around 1600x900.</p>
                                                                    <button class="btn btn-secondary" type="submit">Upload</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-property-media="{{ $propertyId }}">Close</button>
                                                                </form>
                                                                @if ($propertyMediaItems->isEmpty())
                                                                    <p class="ops-empty">No listing photos uploaded yet.</p>
                                                                @else
                                                                    <div class="gallery-grid">
                                                                        @foreach ($propertyMediaItems as $media)
                                                                            @php
                                                                                $mediaUrl = '/media/vendor/' . (int) ($media->id ?? 0) . '/banner';
                                                                            @endphp
                                                                            <article class="gallery-card">
                                                                                <img src="{{ $mediaUrl }}" alt="{{ (string) ($media->alt_text ?? $property->name) }}">
                                                                                <div style="padding:6px 8px; font-size:0.72rem; color:#35506a;">
                                                                                    {{ (string) ($media->alt_text ?? 'Listing photo') }}
                                                                                    @if ((bool) ($media->is_primary ?? false))
                                                                                        <span class="ops-chip" style="margin-left:6px;">Primary</span>
                                                                                    @else
                                                                                        <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary" style="margin-top:6px;">
                                                                                            @csrf
                                                                                            <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                        </form>
                                                                                    @endif
                                                                                </div>
                                                                            </article>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @if ($categoryKey === 'accommodation')
                                                    <tr class="accommodation-room-stretch-row">
                                                        <td colspan="2">
                                                            <div class="accommodation-room-stretch">
                                                                <p class="property-subsection-head">Rooms Under This Property ({{ $propertyRooms->count() }})</p>
                                                                @if ($propertyRooms->isEmpty())
                                                                    <p class="ops-empty">No rooms for this listing yet.</p>
                                                                @else
                                                                    <div class="ops-table-wrap">
                                                                        <table class="ops-table is-compact room-management-table" aria-label="Rooms for property {{ $propertyId }}">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Room</th>
                                                                                    <th>Summary</th>
                                                                                    <th>Actions</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($propertyRooms as $room)
                                                                                    @php
                                                                                        $roomId = (int) ($room->id ?? 0);
                                                                                        $roomMediaItems = $roomMediaByRoomId->get($roomId, collect());
                                                                                        $roomAmenityValues = collect(explode(',', (string) ($room->amenities ?? '')))
                                                                                            ->map(static fn ($token) => trim((string) $token))
                                                                                            ->filter(static fn ($token) => $token !== '')
                                                                                            ->values()
                                                                                            ->all();
                                                                                        $bathroomAmenityValues = collect(explode(',', (string) ($room->bathroom_amenities ?? '')))
                                                                                            ->map(static fn ($token) => trim((string) $token))
                                                                                            ->filter(static fn ($token) => $token !== '')
                                                                                            ->values()
                                                                                            ->all();
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>
                                                                                            <div class="listing-summary-line">
                                                                                                <strong>{{ $room->name }}</strong>
                                                                                                <span class="ops-chip">Room ID {{ $roomId }}</span>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="room-summary-line">Qty: {{ (int) ($room->quantity ?? 0) }} | Max: {{ (int) ($room->max_occupancy ?? 0) }} | Base: {{ $property->currency ?? 'MVR' }} {{ number_format((float) ($room->base_price ?? 0), 2) }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div class="inline-actions listing-actions-inline">
                                                                                                <button class="btn btn-secondary" type="button" data-open-room-edit data-room-edit-id="{{ $roomId }}">Edit Room</button>
                                                                                                <button class="btn btn-secondary" type="button" data-toggle-room-media="{{ $roomId }}">Manage Media</button>
                                                                                                <form method="POST" action="/portal/vendor/rooms/{{ $roomId }}/delete" onsubmit="return confirm('Remove this room category?');">
                                                                                                    @csrf
                                                                                                    <button class="btn btn-danger" type="submit">Remove Room</button>
                                                                                                </form>
                                                                                            </div>
                                                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/rooms/{{ $roomId }}/update" data-room-edit-form="{{ $roomId }}" hidden>
                                                                                                @csrf
                                                                                                <input class="ops-input" name="name" type="text" maxlength="160" value="{{ (string) ($room->name ?? '') }}" required>
                                                                                                <input class="ops-input" name="quantity" type="number" min="1" max="10000" value="{{ (int) ($room->quantity ?? 1) }}" required>
                                                                                                <input class="ops-input" name="max_occupancy" type="number" min="1" max="50" value="{{ (int) ($room->max_occupancy ?? 1) }}" required>
                                                                                                <input class="ops-input" name="extra_person_capacity" type="number" min="0" max="20" value="{{ (int) ($room->extra_person_capacity ?? 0) > 0 ? (int) ($room->extra_person_capacity ?? 0) : '' }}" placeholder="Extra adult capacity">
                                                                                                <input class="ops-input" name="child_capacity" type="number" min="0" max="20" value="{{ (int) ($room->child_capacity ?? 0) > 0 ? (int) ($room->child_capacity ?? 0) : '' }}" placeholder="Child capacity">
                                                                                                <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ (float) ($room->base_price ?? 0) > 0 ? (float) ($room->base_price ?? 0) : '' }}" placeholder="Base room price">
                                                                                                <input class="ops-input" name="extra_person_price" type="number" min="0" step="0.01" value="{{ (float) ($room->extra_person_price ?? 0) > 0 ? (float) ($room->extra_person_price ?? 0) : '' }}" placeholder="Extra adult price">
                                                                                                <input class="ops-input" name="child_price" type="number" min="0" step="0.01" value="{{ (float) ($room->child_price ?? 0) > 0 ? (float) ($room->child_price ?? 0) : '' }}" placeholder="Child price">
                                                                                                @php
                                                                                                    $roomBedTypeCurrent = strtolower(trim((string) ($room->bed_type ?? '')));
                                                                                                    $knownRoomBedTypes = $roomBedTypeOptionsCollection
                                                                                                        ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                                                        ->filter(fn ($item) => $item !== '')
                                                                                                        ->values()
                                                                                                        ->all();
                                                                                                @endphp
                                                                                                <select class="ops-select" name="bed_type">
                                                                                                    <option value="" @selected($roomBedTypeCurrent === '')>Bed Type</option>
                                                                                                    @if ($roomBedTypeCurrent !== '' && !in_array($roomBedTypeCurrent, $knownRoomBedTypes, true))
                                                                                                        <option value="{{ $roomBedTypeCurrent }}" selected>{{ ucfirst(str_replace('_', ' ', $roomBedTypeCurrent)) }} (existing)</option>
                                                                                                    @endif
                                                                                                    @foreach ($roomBedTypeOptionsCollection as $roomBedTypeOption)
                                                                                                        @php
                                                                                                            $roomBedTypeValue = strtolower(trim((string) ($roomBedTypeOption['value'] ?? '')));
                                                                                                            $roomBedTypeLabel = trim((string) ($roomBedTypeOption['label'] ?? $roomBedTypeValue));
                                                                                                        @endphp
                                                                                                        @if ($roomBedTypeValue !== '' && $roomBedTypeLabel !== '')
                                                                                                            <option value="{{ $roomBedTypeValue }}" @selected($roomBedTypeCurrent === $roomBedTypeValue)>{{ $roomBedTypeLabel }}</option>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </select>
                                                                                                <select class="ops-select" name="bathroom_type">
                                                                                                    <option value="" @selected((string) ($room->bathroom_type ?? '') === '')>Bathroom Type</option>
                                                                                                    <option value="ensuite" @selected((string) ($room->bathroom_type ?? '') === 'ensuite')>Ensuite</option>
                                                                                                    <option value="private_external" @selected((string) ($room->bathroom_type ?? '') === 'private_external')>Private External</option>
                                                                                                    <option value="shared" @selected((string) ($room->bathroom_type ?? '') === 'shared')>Shared</option>
                                                                                                </select>
                                                                                                <input class="ops-input" name="bathroom_count" type="number" min="0" max="20" value="{{ (int) ($room->bathroom_count ?? 0) > 0 ? (int) ($room->bathroom_count ?? 0) : '' }}" placeholder="Bathroom Count">
                                                                                                <div class="feature-checklist">
                                                                                                    @foreach ($roomAmenityOptionsCollection as $roomAmenityOption)
                                                                                                        @php
                                                                                                            $roomAmenityValue = trim((string) ($roomAmenityOption['value'] ?? ''));
                                                                                                            $roomAmenityLabel = trim((string) ($roomAmenityOption['label'] ?? $roomAmenityValue));
                                                                                                        @endphp
                                                                                                        @if ($roomAmenityValue !== '' && $roomAmenityLabel !== '')
                                                                                                            <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="{{ $roomAmenityValue }}" @checked(in_array($roomAmenityValue, $roomAmenityValues, true))> {{ $roomAmenityLabel }}</label>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <div class="feature-checklist">
                                                                                                    @foreach ($bathroomAmenityOptionsCollection as $bathroomAmenityOption)
                                                                                                        @php
                                                                                                            $bathroomAmenityValue = trim((string) ($bathroomAmenityOption['value'] ?? ''));
                                                                                                            $bathroomAmenityLabel = trim((string) ($bathroomAmenityOption['label'] ?? $bathroomAmenityValue));
                                                                                                        @endphp
                                                                                                        @if ($bathroomAmenityValue !== '' && $bathroomAmenityLabel !== '')
                                                                                                            <label class="feature-item"><input type="checkbox" name="bathroom_amenities[]" value="{{ $bathroomAmenityValue }}" @checked(in_array($bathroomAmenityValue, $bathroomAmenityValues, true))> {{ $bathroomAmenityLabel }}</label>
                                                                                                        @endif
                                                                                                    @endforeach
                                                                                                </div>
                                                                                                <div class="inline-actions">
                                                                                                    <button class="btn btn-secondary js-row-update" type="submit">Update Room</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-room-edit data-room-edit-id="{{ $roomId }}">Cancel Edit</button>
                                                                                                </div>
                                                                                            </form>
                                                                                            <div class="media-upload-row" data-room-media-panel="{{ $roomId }}" hidden>
                                                                                                <form class="inline-table-form" method="POST" action="/portal/vendor/media/upload" enctype="multipart/form-data" data-media-upload-form>
                                                                                                    @csrf
                                                                                                    <input type="hidden" name="entity_type" value="room">
                                                                                                    <input type="hidden" name="entity_id" value="{{ $roomId }}">
                                                                                                    <input type="hidden" name="primary_upload_index" value="0" data-media-primary-index>
                                                                                                    <input class="ops-input" name="alt_text" type="text" maxlength="190" value="{{ $room->name }} photo" placeholder="Photo alt text" required>
                                                                                                    <div class="media-dropzone" data-media-dropzone>Drag and drop photos here, or click to choose files.</div>
                                                                                                    <input class="ops-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required data-media-input>
                                                                                                    <div class="media-upload-preview" data-media-preview></div>
                                                                                                    <p class="small" style="grid-column:1 / -1; margin:0;">Upload standard: JPG/PNG/WebP, any dimensions accepted, max 2MB per image. Recommended quality source: around 1600x900.</p>
                                                                                                    <button class="btn btn-secondary" type="submit">Upload</button>
                                                                                                    <button class="btn btn-secondary" type="button" data-close-room-media="{{ $roomId }}">Close</button>
                                                                                                </form>
                                                                                                @if ($roomMediaItems->isEmpty())
                                                                                                    <p class="ops-empty">No room photos uploaded yet.</p>
                                                                                                @else
                                                                                                    <div class="gallery-grid">
                                                                                                        @foreach ($roomMediaItems as $media)
                                                                                                            @php
                                                                                                                $roomMediaUrl = '/media/vendor/' . (int) ($media->id ?? 0) . '/banner';
                                                                                                            @endphp
                                                                                                            <article class="gallery-card">
                                                                                                                <img src="{{ $roomMediaUrl }}" alt="{{ (string) ($media->alt_text ?? $room->name) }}">
                                                                                                                <div style="padding:6px 8px; font-size:0.72rem; color:#35506a;">
                                                                                                                    {{ (string) ($media->alt_text ?? 'Room photo') }}
                                                                                                                    @if ((bool) ($media->is_primary ?? false))
                                                                                                                        <span class="ops-chip" style="margin-left:6px;">Primary</span>
                                                                                                                    @else
                                                                                                                        <form method="POST" action="/portal/vendor/media/{{ (int) ($media->id ?? 0) }}/primary" style="margin-top:6px;">
                                                                                                                            @csrf
                                                                                                                            <button class="btn btn-secondary" type="submit">Set Primary</button>
                                                                                                                        </form>
                                                                                                                    @endif
                                                                                                                </div>
                                                                                                            </article>
                                                                                                        @endforeach
                                                                                                    </div>
                                                                                                @endif
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if ($categoryKey === 'accommodation')
                                                    @php
                                                        $showInlineRoomRow = $showCreateRoomForm && (string) old('vendor_property_id') === (string) $propertyId;
                                                    @endphp
                                                    <tr data-inline-room-row="{{ $propertyId }}" @if (!$showInlineRoomRow) hidden @endif>
                                                        <td colspan="2">
                                                            <form class="inline-table-form update-row-form" method="POST" action="/portal/vendor/rooms/create" data-inline-room-form="{{ $propertyId }}">
                                                                @csrf
                                                                <input type="hidden" name="room_form_intent" value="1">
                                                                <input type="hidden" name="vendor_property_id" value="{{ $propertyId }}">
                                                                <div class="ops-form-grid">
                                                                    <div class="ops-field">
                                                                        <label>Accommodation Listing</label>
                                                                        <input class="ops-input" type="text" value="{{ $property->name }} (ID {{ $propertyId }})" readonly>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Name</label>
                                                                        <input class="ops-input" name="name" type="text" maxlength="160" value="{{ $showInlineRoomRow ? old('name') : '' }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Quantity</label>
                                                                        <input class="ops-input" name="quantity" type="number" min="1" max="10000" value="{{ $showInlineRoomRow ? old('quantity', 1) : 1 }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Base Occupancy (Adults)</label>
                                                                        <input class="ops-input" name="max_occupancy" type="number" min="1" max="50" value="{{ $showInlineRoomRow ? old('max_occupancy', 1) : 1 }}" required>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Capacity</label>
                                                                        <input class="ops-input" name="extra_person_capacity" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('extra_person_capacity', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Capacity</label>
                                                                        <input class="ops-input" name="child_capacity" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('child_capacity', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Room Base Price (MVR)</label>
                                                                        <input class="ops-input" name="base_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('base_price', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Extra Adult Price (MVR)</label>
                                                                        <input class="ops-input" name="extra_person_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('extra_person_price', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Child Price (MVR)</label>
                                                                        <input class="ops-input" name="child_price" type="number" min="0" step="0.01" value="{{ $showInlineRoomRow ? old('child_price', '') : '' }}">
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bed Type</label>
                                                                        @php
                                                                            $roomBedTypeOld = strtolower(trim((string) ($showInlineRoomRow ? old('bed_type') : '')));
                                                                            $knownRoomBedTypes = $roomBedTypeOptionsCollection
                                                                                ->map(fn ($item) => strtolower(trim((string) ($item['value'] ?? ''))))
                                                                                ->filter(fn ($item) => $item !== '')
                                                                                ->values()
                                                                                ->all();
                                                                        @endphp
                                                                        <select class="ops-select" name="bed_type">
                                                                            <option value="" @selected($roomBedTypeOld === '')>Select</option>
                                                                            @if ($roomBedTypeOld !== '' && !in_array($roomBedTypeOld, $knownRoomBedTypes, true))
                                                                                <option value="{{ $roomBedTypeOld }}" selected>{{ ucfirst(str_replace('_', ' ', $roomBedTypeOld)) }} (existing)</option>
                                                                            @endif
                                                                            @foreach ($roomBedTypeOptionsCollection as $roomBedTypeOption)
                                                                                @php
                                                                                    $roomBedTypeValue = strtolower(trim((string) ($roomBedTypeOption['value'] ?? '')));
                                                                                    $roomBedTypeLabel = trim((string) ($roomBedTypeOption['label'] ?? $roomBedTypeValue));
                                                                                @endphp
                                                                                @if ($roomBedTypeValue !== '' && $roomBedTypeLabel !== '')
                                                                                    <option value="{{ $roomBedTypeValue }}" @selected($roomBedTypeOld === $roomBedTypeValue)>{{ $roomBedTypeLabel }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bathroom Type</label>
                                                                        <select class="ops-select" name="bathroom_type">
                                                                            <option value="" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === '')>Select</option>
                                                                            <option value="ensuite" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'ensuite')>Ensuite</option>
                                                                            <option value="private_external" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'private_external')>Private External</option>
                                                                            <option value="shared" @selected(($showInlineRoomRow ? old('bathroom_type') : '') === 'shared')>Shared</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="ops-field">
                                                                        <label>Bathroom Count</label>
                                                                        <input class="ops-input" name="bathroom_count" type="number" min="0" max="20" value="{{ $showInlineRoomRow ? old('bathroom_count', 1) : 1 }}">
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Room Amenities</label>
                                                                        <div class="feature-checklist">
                                                                            @foreach ($roomAmenityOptionsCollection as $roomAmenityOption)
                                                                                @php
                                                                                    $roomAmenityValue = trim((string) ($roomAmenityOption['value'] ?? ''));
                                                                                    $roomAmenityLabel = trim((string) ($roomAmenityOption['label'] ?? $roomAmenityValue));
                                                                                @endphp
                                                                                @if ($roomAmenityValue !== '' && $roomAmenityLabel !== '')
                                                                                    <label class="feature-item"><input type="checkbox" name="room_amenities[]" value="{{ $roomAmenityValue }}" @checked(in_array($roomAmenityValue, $oldRoomAmenities, true))> {{ $roomAmenityLabel }}</label>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div class="ops-field ops-field-wide">
                                                                        <label>Bathroom Amenities</label>
                                                                        <div class="feature-checklist">
                                                                            @foreach ($bathroomAmenityOptionsCollection as $bathroomAmenityOption)
                                                                                @php
                                                                                    $bathroomAmenityValue = trim((string) ($bathroomAmenityOption['value'] ?? ''));
                                                                                    $bathroomAmenityLabel = trim((string) ($bathroomAmenityOption['label'] ?? $bathroomAmenityValue));
                                                                                @endphp
                                                                                @if ($bathroomAmenityValue !== '' && $bathroomAmenityLabel !== '')
                                                                                    <label class="feature-item"><input type="checkbox" name="bathroom_amenities[]" value="{{ $bathroomAmenityValue }}" @checked(in_array($bathroomAmenityValue, $oldBathroomAmenities, true))> {{ $bathroomAmenityLabel }}</label>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="inline-actions" style="margin-top:10px;">
                                                                    <button class="btn btn-primary" type="submit">Save Room</button>
                                                                    <button class="btn btn-secondary" type="button" data-close-inline-room-row="{{ $propertyId }}">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        @include('vendor-portal.partials.category-operations')

        @include('vendor-portal.partials.pricing')

        @include('vendor-portal.partials.billing-collection')

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
            const backToListingsFromCreate = document.getElementById("backToListingsFromCreate");
            const propertyCreateForm = document.getElementById("propertyCreateForm");
            const propertyCreateFormTitle = document.getElementById("propertyCreateFormTitle");
            const propertyCreateFormSubtitle = document.getElementById("propertyCreateFormSubtitle");
            const propertyCreateSubmitButton = document.getElementById("propertyCreateSubmitButton");
            const propertyCategorySelect = document.getElementById("property_listing_category");
            const propertyTypeSelect = document.getElementById("property_type");
            const propertyCategoryScopeNote = document.getElementById("propertyCategoryScopeNote");
            const propertyBasePriceLabel = document.querySelector('label[for="property_base_price"]');
            const propertyMaxGuestsLabel = document.querySelector('label[for="property_max_guests"]');
            const propertyCapacityLabel = document.querySelector('label[for="property_capacity_value"]');
            const transportModeInput = document.getElementById("property_transport_mode");
            const transportPricingHint = document.getElementById("transportPricingHint");
            const transportPricingModelSelect = document.getElementById("property_transport_pricing_model");
            const transportLandOnlyFields = Array.from(document.querySelectorAll("[data-transport-land-only]"));
            const transportMarineOnlyFields = Array.from(document.querySelectorAll("[data-transport-marine-only]"));
            const categoryScopedFields = Array.from(document.querySelectorAll("[data-category-scope]"));
            const categoryViewPanels = Array.from(document.querySelectorAll('[data-category-view]'));
            const openRoomCreateForm = document.getElementById("openRoomCreateForm");
            const closeRoomCreateForm = document.getElementById("closeRoomCreateForm");
            const roomCreateForm = document.getElementById("roomCreateForm");
            const roomPropertySelect = document.getElementById("room_vendor_property_id");
            const roomQuickOpenButtons = Array.from(document.querySelectorAll("[data-open-room-form]"));
            const inlineRoomRows = Array.from(document.querySelectorAll("[data-inline-room-row]"));
            const inlineRoomCloseButtons = Array.from(document.querySelectorAll("[data-close-inline-room-row]"));
            const propertyEditButtons = Array.from(document.querySelectorAll('[data-open-property-edit]'));
            const propertyEditCancelButtons = Array.from(document.querySelectorAll('[data-close-property-edit]'));
            const roomEditButtons = Array.from(document.querySelectorAll('[data-open-room-edit]'));
            const roomEditCancelButtons = Array.from(document.querySelectorAll('[data-close-room-edit]'));
            const propertyMediaToggleButtons = Array.from(document.querySelectorAll('[data-toggle-property-media]'));
            const propertyMediaCloseButtons = Array.from(document.querySelectorAll('[data-close-property-media]'));
            const roomMediaToggleButtons = Array.from(document.querySelectorAll('[data-toggle-room-media]'));
            const roomMediaCloseButtons = Array.from(document.querySelectorAll('[data-close-room-media]'));
            const listingCategoryShortcutButtons = Array.from(document.querySelectorAll('[data-listing-category-shortcut]'));
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
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                        openRoomForm: true,
                    },
                    {
                        title: "Photos and media",
                        hint: "Upload property and room photos.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
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

            let panelStateInitialized = false;

            function initializePanelStateSafe() {
                if (panelStateInitialized) {
                    return;
                }
                panelStateInitialized = true;

                const hashPanelKey = resolvePanelFromHash(window.location.hash || "#overview");
                const initialPanelKey = serverPanelKey && validPanelKeys.has(serverPanelKey) ? serverPanelKey : hashPanelKey;
                listingWizardPanelStep = listingPanelStepFromWizardStep(listingWizardStep);
                showPanelGroup(initialPanelKey);

                if (initialPanelKey === "listings") {
                    if (serverPanelKey === "listings") {
                        activateListingWizardStep(listingWizardStep, true);
                    } else {
                        applyListingWizardVisibility();
                    }
                }
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
                    3: "vendorPropertiesSection",
                    4: "vendorPropertiesSection"
                };
                const targetId = stepTargets[safeStep] || "vendorPropertiesSection";
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;
                targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            function listingPanelStepFromWizardStep(step) {
                return 1;
            }

            function setListingPanelsHidden(hidden) {
                listingStepPanels.forEach((panel) => {
                    panel.hidden = hidden;
                });
            }

            function applyListingWizardVisibility() {
                const activeStepPanel = 1;
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

            const FALLBACK_LOCATION_TREE = {
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

            let locationTreeCache = FALLBACK_LOCATION_TREE;
            let locationTreePromise = null;

            function getCurrentLocationTree() {
                return locationTreeCache || FALLBACK_LOCATION_TREE;
            }

            function applyLocationTree(data) {
                if (!data || typeof data !== "object" || Array.isArray(data)) {
                    return getCurrentLocationTree();
                }
                locationTreeCache = data;
                window.__vendorPortalLocationTree = data;
                try {
                    window.sessionStorage.setItem("vendor_portal_location_tree_v1", JSON.stringify(data));
                } catch (error) {
                    // Ignore storage failures and continue with in-memory cache.
                }
                return locationTreeCache;
            }

            function getLocationTree() {
                if (window.__vendorPortalLocationTree && typeof window.__vendorPortalLocationTree === "object") {
                    locationTreeCache = window.__vendorPortalLocationTree;
                    return Promise.resolve(locationTreeCache);
                }

                if (locationTreePromise) {
                    return locationTreePromise;
                }

                locationTreePromise = new Promise(function (resolve) {
                    let restoredFromSession = false;

                    try {
                        const cachedPayload = window.sessionStorage.getItem("vendor_portal_location_tree_v1");
                        if (cachedPayload) {
                            const parsed = JSON.parse(cachedPayload);
                            applyLocationTree(parsed);
                            restoredFromSession = true;
                            resolve(getCurrentLocationTree());
                        }
                    } catch (error) {
                        restoredFromSession = false;
                    }

                    if (restoredFromSession) {
                        return;
                    }

                    fetch("{{ asset('data/location-tree.json') }}", { cache: "force-cache" })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error("Location tree request failed with status " + response.status);
                            }
                            return response.json();
                        })
                        .then(function (payload) {
                            resolve(applyLocationTree(payload));
                        })
                        .catch(function () {
                            resolve(getCurrentLocationTree());
                        });
                });

                return locationTreePromise;
            }

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
                const locationTree = getCurrentLocationTree();
                const states = Object.keys(locationTree[country] || {});
                rebuildSelect(locationState, states, "Select state/province");
                const selectedState = locationState.dataset.selectedValue || "";
                ensureSelectHasOption(locationState, selectedState);
                if (selectedState && Array.from(locationState.options).some((option) => option.value === selectedState)) {
                    locationState.value = selectedState;
                } else {
                    locationState.value = states[0] || "";
                }
                const cities = (locationTree[country] || {})[locationState.value] || [];
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
                const locationTree = getCurrentLocationTree();
                const cities = (locationTree[country] || {})[locationState.value] || [];
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

            const COUNTRY_MAP_CENTER = {
                maldives: [3.2028, 73.2207, 8],
                "sri lanka": [7.8731, 80.7718, 8],
                india: [20.5937, 78.9629, 5],
            };

            let locationMapContext = null;
            const mapGeocodeCache = new Map();
            let mapLookupRequestId = 0;

            function refreshLocationMapViewport() {
                if (!locationMapContext || !locationMapContext.map) {
                    return;
                }
                const map = locationMapContext.map;
                setTimeout(function () {
                    map.invalidateSize();
                    centerMapForLocationSelection(false);
                }, 120);
            }

            window.__vendorPortalRefreshLocationMap = refreshLocationMapViewport;

            function fallbackMapView(countryRaw) {
                const key = String(countryRaw || '').trim().toLowerCase();
                return COUNTRY_MAP_CENTER[key] || [4.1755, 73.5093, 9];
            }

            function locationSelectionPayload() {
                const country = String(locationCountry && locationCountry.value || '').trim();
                const state = String(locationState && locationState.value || '').trim();
                const city = String(locationCity && locationCity.value || '').trim();
                const queryParts = [city, state, country].filter(Boolean);
                const query = queryParts.join(', ');
                const hasCity = city !== '';
                const hasState = state !== '';
                return { country, state, city, query, hasCity, hasState };
            }

            async function geocodeMapSelection(query) {
                const cacheKey = String(query || '').trim().toLowerCase();
                if (cacheKey === '') {
                    return null;
                }
                if (mapGeocodeCache.has(cacheKey)) {
                    return mapGeocodeCache.get(cacheKey);
                }

                const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(query);
                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                        cache: 'force-cache',
                    });
                    if (!response.ok) {
                        return null;
                    }
                    const rows = await response.json();
                    if (!Array.isArray(rows) || rows.length === 0) {
                        return null;
                    }
                    const first = rows[0] || {};
                    const lat = Number(first.lat);
                    const lng = Number(first.lon);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return null;
                    }
                    const point = [lat, lng];
                    mapGeocodeCache.set(cacheKey, point);
                    return point;
                } catch (error) {
                    return null;
                }
            }

            async function centerMapForLocationSelection(forceLookup) {
                if (!locationMapContext || !locationMapContext.map) {
                    return;
                }

                const map = locationMapContext.map;
                const payload = locationSelectionPayload();
                const fallback = fallbackMapView(payload.country);
                const fallbackPoint = [fallback[0], fallback[1]];
                const fallbackZoom = payload.hasCity ? 11 : (payload.hasState ? 9 : fallback[2]);

                // Move immediately so the map always responds even if geocoding is slow.
                map.flyTo(fallbackPoint, fallbackZoom, { animate: true, duration: 0.28 });

                if (payload.query === '') {
                    return;
                }

                const requestId = ++mapLookupRequestId;
                let targetPoint = null;
                if (forceLookup || payload.hasCity || payload.hasState) {
                    targetPoint = await geocodeMapSelection(payload.query);
                }
                if (requestId !== mapLookupRequestId) {
                    return;
                }

                if (!targetPoint) {
                    return;
                }

                const zoom = payload.hasCity ? 13 : (payload.hasState ? 10 : fallback[2]);
                map.flyTo(targetPoint, zoom, { animate: true, duration: 0.45 });
            }

            function normalizeCategoryKey(value) {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
            }

            function categoryScopesFor(category) {
                const normalized = normalizeCategoryKey(category);

                if (normalized === "accommodation") {
                    return ["stay", "accommodation", "geo"];
                }

                if (normalized === "transport") {
                    return ["capacity", "transport", "geo"];
                }

                if (normalized === "excursion") {
                    return ["capacity", "service", "excursion", "geo"];
                }

                if (normalized === "remote_workspace") {
                    return ["stay", "capacity", "workspace", "geo"];
                }

                if (normalized === "resort_day_visit") {
                    return ["capacity", "day_visit", "geo"];
                }

                if (normalized === "restaurant") {
                    return ["capacity", "restaurant", "geo"];
                }

                if (normalized === "vehicle_rental") {
                    return ["vehicle", "capacity", "rental", "geo"];
                }

                return ["stay", "accommodation", "capacity", "service", "vehicle", "transport", "excursion", "workspace", "day_visit", "restaurant", "rental", "geo"];
            }

            function isMarineTransportMode(value) {
                const mode = String(value || "").trim().toLowerCase();
                return /(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/.test(mode);
            }

            function refreshTransportFieldLabels() {
                if (!propertyCategorySelect) {
                    return;
                }

                const normalizedCategory = normalizeCategoryKey(propertyCategorySelect.value);
                const isTransportCategory = normalizedCategory === "transport";
                const isRemoteWorkspaceCategory = normalizedCategory === "remote_workspace";
                const isMarine = isMarineTransportMode(transportModeInput ? transportModeInput.value : "");
                const selectedPricingModel = transportPricingModelSelect ? String(transportPricingModelSelect.value || "per_trip") : "per_trip";

                if (propertyBasePriceLabel) {
                    propertyBasePriceLabel.textContent = isTransportCategory
                        ? (isMarine ? "Price Per Seat (MVR)" : "Price Per Trip (MVR)")
                        : (isRemoteWorkspaceCategory ? "Booking Fee Per Guest (MVR)" : "Base Price (MVR)");
                }

                if (propertyCapacityLabel) {
                    propertyCapacityLabel.textContent = isTransportCategory
                        ? (isMarine ? "Seat Capacity" : "Max Passengers Per Trip")
                        : (isRemoteWorkspaceCategory ? "Workspace Capacity (seats/desks)" : "Capacity");
                }

                if (propertyMaxGuestsLabel) {
                    propertyMaxGuestsLabel.textContent = isTransportCategory
                        ? (isMarine ? "Seat Capacity (Legacy)" : "Max Passengers (Legacy)")
                        : (isRemoteWorkspaceCategory ? "Max Bookable Guests" : "Max Guests");
                }

                if (transportPricingHint) {
                    transportPricingHint.textContent = isTransportCategory
                        ? (isMarine
                            ? "Marine transport mode detected: pricing is per seat. Define pickup and dropoff, then select one-way or round-trip."
                            : "Land transport mode detected: choose per-trip, hourly, or daily pricing and set max passengers per trip.")
                        : "Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.";
                }

                transportLandOnlyFields.forEach((field) => {
                    const shouldShow = isTransportCategory && !isMarine;
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });

                transportMarineOnlyFields.forEach((field) => {
                    const shouldShow = isTransportCategory && isMarine;
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });

                const hourlyField = document.getElementById("property_hourly_rate");
                const dailyField = document.getElementById("property_daily_rate");
                if (hourlyField) {
                    const showHourly = isTransportCategory && !isMarine && selectedPricingModel === "hourly";
                    hourlyField.disabled = !showHourly;
                    if (hourlyField.parentElement) {
                        hourlyField.parentElement.hidden = !showHourly;
                        hourlyField.parentElement.style.display = showHourly ? '' : 'none';
                    }
                }
                if (dailyField) {
                    const showDaily = isTransportCategory && !isMarine && selectedPricingModel === "daily";
                    dailyField.disabled = !showDaily;
                    if (dailyField.parentElement) {
                        dailyField.parentElement.hidden = !showDaily;
                        dailyField.parentElement.style.display = showDaily ? '' : 'none';
                    }
                }
            }

            function refreshCategoryViewPanels() {
                if (!propertyCategorySelect || categoryViewPanels.length === 0) {
                    return;
                }
                const activeCategory = normalizeCategoryKey(propertyCategorySelect.value);
                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view'));
                    panel.hidden = panelCategory !== activeCategory;
                });
            }

            function categoryMetaFor(category) {
                const normalized = normalizeCategoryKey(category);
                const fallbackLabel = propertyCategorySelect
                    ? (propertyCategorySelect.options[propertyCategorySelect.selectedIndex]?.textContent || 'Listing')
                    : 'Listing';

                const categoryMeta = {
                    accommodation: {
                        title: 'Accommodation Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Accommodation Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'property',
                    },
                    transport: {
                        title: 'Transport Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Transport Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    excursion: {
                        title: 'Excursion Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Excursion Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    remote_workspace: {
                        title: 'Remote Workspace Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Remote Workspace Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    resort_day_visit: {
                        title: 'Resort Day Visit Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Resort Day Visit Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    restaurant: {
                        title: 'Restaurant Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Restaurant Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    vehicle_rental: {
                        title: 'Vehicle Rental Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Vehicle Rental Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                };

                return categoryMeta[normalized] || {
                    title: fallbackLabel + ' Enlisting',
                    subtitle: 'Fill required fields and save.',
                    submit: 'Save ' + fallbackLabel + ' Listing',
                    note: 'Fill required fields and save.',
                    propertyType: null,
                };
            }

            function applyCategoryFormMeta(category, forceType) {
                const meta = categoryMetaFor(category);
                if (propertyCreateFormTitle) {
                    propertyCreateFormTitle.textContent = meta.title;
                }
                if (propertyCreateFormSubtitle) {
                    propertyCreateFormSubtitle.textContent = meta.subtitle;
                }
                if (propertyCreateSubmitButton) {
                    propertyCreateSubmitButton.textContent = meta.submit;
                }
                if (propertyCategoryScopeNote) {
                    propertyCategoryScopeNote.textContent = meta.note;
                }
                if (forceType && propertyTypeSelect && meta.propertyType) {
                    ensureSelectHasOption(propertyTypeSelect, meta.propertyType);
                    propertyTypeSelect.value = meta.propertyType;
                }
            }

            function ensureAutoCategorySelected(preferredCategory) {
                if (!propertyCategorySelect) {
                    return '';
                }
                const preferred = normalizeCategoryKey(preferredCategory || propertyCategorySelect.getAttribute('data-default-category') || 'accommodation');
                if (preferred !== '') {
                    const matched = Array.from(propertyCategorySelect.options)
                        .find((option) => normalizeCategoryKey(option.value) === preferred);
                    if (matched) {
                        propertyCategorySelect.value = matched.value;
                    }
                }
                if ((!propertyCategorySelect.value || String(propertyCategorySelect.value).trim() === '') && propertyCategorySelect.options.length > 0) {
                    propertyCategorySelect.value = propertyCategorySelect.options[0].value;
                }
                return String(propertyCategorySelect.value || '');
            }

            function refreshPropertyCategoryFields() {
                if (!propertyCategorySelect || categoryScopedFields.length === 0) return;
                const activeCategory = ensureAutoCategorySelected('');
                const activeScopes = categoryScopesFor(activeCategory);
                categoryScopedFields.forEach((field) => {
                    const scopes = String(field.getAttribute("data-category-scope") || "")
                        .split(",")
                        .map((value) => value.trim().toLowerCase())
                        .filter(Boolean);
                    if (scopes.length === 0) {
                        field.hidden = false;
                        field.style.display = '';
                        return;
                    }
                    const shouldShow = scopes.some((scope) => activeScopes.includes(scope));
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });
                refreshCategoryViewPanels();
                applyCategoryFormMeta(activeCategory, false);
                refreshTransportFieldLabels();
            }

            function applyPropertyCategoryFilter(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || 'all');
                propertyListingRows.forEach((row) => {
                    const rowCategory = normalizeCategoryKey(row.getAttribute('data-listing-category') || '');
                    const shouldShow = normalizedCategory === 'all' || rowCategory === normalizedCategory;
                    row.hidden = !shouldShow;
                });

            }

            function openPropertyFlowWithCategory(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || '');
                window.location.hash = 'listings';
                showPanelGroup('listings');
                activateListingWizardStep(1, true);

                if (propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                }
                if (closePropertyCreateForm) {
                    closePropertyCreateForm.hidden = false;
                }
                if (propertyCategorySelect) {
                    const selectedCategory = ensureAutoCategorySelected(normalizedCategory);
                    propertyCategorySelect.dispatchEvent(new Event('change'));
                    applyCategoryFormMeta(selectedCategory, true);
                }
                if (document.getElementById('property_name')) {
                    document.getElementById('property_name').focus();
                }

                refreshLocationMapViewport();

                applyPropertyCategoryFilter(normalizedCategory || 'all');
            }

            function isFieldVisibleForValidation(field) {
                if (!field || field.disabled || field.type === 'hidden') {
                    return false;
                }
                if (field.closest('[hidden]')) {
                    return false;
                }
                return field.offsetParent !== null;
            }

            function applyFieldValidationState(field) {
                if (!field) {
                    return true;
                }

                const visible = isFieldVisibleForValidation(field);
                const shouldValidate = visible && field.required;
                const isInvalid = shouldValidate && !field.checkValidity();

                field.classList.toggle('is-invalid', isInvalid);
                const fieldWrap = field.closest('.ops-field');
                if (fieldWrap) {
                    fieldWrap.classList.toggle('has-invalid', isInvalid);
                }

                return !isInvalid;
            }

            function validatePropertyCreateForm(showNativeMessage) {
                if (!propertyCreateForm) {
                    return true;
                }

                const requiredFields = Array.from(propertyCreateForm.querySelectorAll('input, select, textarea'))
                    .filter((field) => field.required);

                let firstInvalid = null;
                let allValid = true;

                requiredFields.forEach((field) => {
                    const valid = applyFieldValidationState(field);
                    if (!valid && !firstInvalid) {
                        firstInvalid = field;
                    }
                    if (!valid) {
                        allValid = false;
                    }
                });

                if (!allValid && firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (showNativeMessage && typeof firstInvalid.reportValidity === 'function') {
                        firstInvalid.reportValidity();
                    }
                }

                return allValid;
            }

            function refreshBillingLocationSelectors() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const locationTree = getCurrentLocationTree();
                const states = Object.keys(locationTree[country] || {});
                const previousState = billingState.dataset.selectedValue || billingState.value;
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;

                rebuildSelect(billingState, states, "Select state/province");
                ensureSelectHasOption(billingState, previousState);

                if (previousState && Array.from(billingState.options).some((option) => option.value === previousState)) {
                    billingState.value = previousState;
                } else if (states.length > 0) {
                    billingState.value = states[0];
                }

                const cities = (locationTree[country] || {})[billingState.value] || [];
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
                const locationTree = getCurrentLocationTree();
                const cities = (locationTree[country] || {})[billingState.value] || [];
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
                const map = window.L.map(mapEl, {
                    preferCanvas: true,
                    zoomControl: true,
                    worldCopyJump: true,
                    inertia: true,
                    fadeAnimation: false,
                    markerZoomAnimation: false,
                }).setView([defaultLat, defaultLng], 11);

                const osmLayer = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    keepBuffer: 4,
                    updateWhenIdle: true,
                    updateWhenZooming: false,
                    attribution: "&copy; OpenStreetMap contributors"
                });

                osmLayer.addTo(map);

                let marker = window.L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
                locationMapContext = { map, marker };

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

                marker.on("dragend", function () {
                    updateLocationFromMap(marker.getLatLng());
                });

                setTimeout(function () {
                    map.invalidateSize();
                }, 180);

                centerMapForLocationSelection(false);
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

            initializePanelStateSafe();

            const saveTokenButton = document.getElementById("saveToken");
            const clearTokenButton = document.getElementById("clearToken");

            if (saveTokenButton) {
                saveTokenButton.addEventListener("click", saveToken);
            }
            if (clearTokenButton) {
                clearTokenButton.addEventListener("click", clearToken);
            }
            if (refreshSummaryBtn) {
                refreshSummaryBtn.addEventListener("click", refreshSummary);
            }
            if (tokenInput) {
                tokenInput.addEventListener("keydown", function (event) {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        saveToken();
                    }
                });
            }
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
                    refreshPropertyCategoryFields();
                    const propertyNameInput = document.getElementById("property_name");
                    if (propertyNameInput) {
                        propertyNameInput.focus();
                    }
                    refreshLocationMapViewport();
                });
            }

            if (propertyCreateForm) {
                propertyCreateForm.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (!field.required) {
                        return;
                    }
                    field.addEventListener('input', function () {
                        applyFieldValidationState(field);
                    });
                    field.addEventListener('change', function () {
                        applyFieldValidationState(field);
                    });
                    field.addEventListener('blur', function () {
                        applyFieldValidationState(field);
                    });
                });

                propertyCreateForm.addEventListener('submit', function (event) {
                    if (!validatePropertyCreateForm(true)) {
                        event.preventDefault();
                    }
                });
            }

            if (propertyCreateSubmitButton && propertyCreateForm) {
                propertyCreateSubmitButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (!validatePropertyCreateForm(true)) {
                        return;
                    }
                    if (typeof propertyCreateForm.requestSubmit === 'function') {
                        propertyCreateForm.requestSubmit();
                    } else {
                        propertyCreateForm.submit();
                    }
                });
            }

            if (closePropertyCreateForm && propertyCreateForm) {
                closePropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    closePropertyCreateForm.hidden = true;
                });
            }

            if (backToListingsFromCreate && propertyCreateForm) {
                backToListingsFromCreate.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    if (closePropertyCreateForm) closePropertyCreateForm.hidden = true;
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(1, true);
                });
            }

            roomQuickOpenButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-property-id") || "").trim();
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(1, false);

                    const targetRow = document.querySelector('[data-inline-room-row="' + propertyId + '"]');
                    if (!targetRow) {
                        return;
                    }

                    inlineRoomRows.forEach((row) => {
                        if (row !== targetRow) {
                            row.hidden = true;
                        }
                    });

                    targetRow.hidden = false;
                    const firstInput = targetRow.querySelector('input[name="name"]');
                    if (firstInput) {
                        firstInput.focus();
                    }
                    targetRow.scrollIntoView({ behavior: "smooth", block: "nearest" });
                });
            });

            inlineRoomCloseButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-close-inline-room-row") || "").trim();
                    if (!propertyId) {
                        return;
                    }
                    const targetRow = document.querySelector('[data-inline-room-row="' + propertyId + '"]');
                    if (targetRow) {
                        targetRow.hidden = true;
                    }
                });
            });

            function applyPropertyEditScope(form, category) {
                if (!form) {
                    return;
                }
                const activeScopes = categoryScopesFor(category);
                form.querySelectorAll('[data-property-edit-scope]').forEach((field) => {
                    const scope = String(field.getAttribute('data-property-edit-scope') || '').trim().toLowerCase();
                    const shouldShow = scope !== '' && activeScopes.includes(scope);
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    if ('disabled' in field) {
                        field.disabled = !shouldShow;
                    }
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });
            }

            function openEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                const category = String(form.getAttribute('data-property-edit-category') || '').trim();
                if (category !== '') {
                    applyPropertyEditScope(form, category);
                }
                form.hidden = false;
                const row = form.closest('tr');
                if (row) {
                    row.classList.add('is-editing');
                }
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
                const row = form.closest('tr');
                if (row) {
                    row.classList.remove('is-editing');
                }
            }

            propertyEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    const category = String(button.getAttribute('data-property-edit-category') || '').trim();
                    const selector = '[data-property-edit-form="' + editId + '"]';
                    const form = document.querySelector(selector);
                    if (form && category !== '') {
                        applyPropertyEditScope(form, category);
                    }
                    openEditForm(selector);
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

            propertyMediaToggleButtons.forEach((button) => {
                if (button.dataset.mediaToggleBound === '1') {
                    return;
                }
                button.dataset.mediaToggleBound = '1';
                button.addEventListener('click', function () {
                    const propertyId = String(button.getAttribute('data-toggle-property-media') || '').trim();
                    if (!propertyId) {
                        return;
                    }
                    const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                    if (!panel) {
                        return;
                    }
                    panel.hidden = !panel.hidden;
                    if (!panel.hidden) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });

            propertyMediaCloseButtons.forEach((button) => {
                if (button.dataset.mediaCloseBound === '1') {
                    return;
                }
                button.dataset.mediaCloseBound = '1';
                button.addEventListener('click', function () {
                    const propertyId = String(button.getAttribute('data-close-property-media') || '').trim();
                    if (!propertyId) {
                        return;
                    }
                    const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                    if (panel) {
                        panel.hidden = true;
                    }
                });
            });

            roomMediaToggleButtons.forEach((button) => {
                if (button.dataset.mediaToggleBound === '1') {
                    return;
                }
                button.dataset.mediaToggleBound = '1';
                button.addEventListener('click', function () {
                    const roomId = String(button.getAttribute('data-toggle-room-media') || '').trim();
                    if (!roomId) {
                        return;
                    }
                    const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                    if (!panel) {
                        return;
                    }
                    panel.hidden = !panel.hidden;
                    if (!panel.hidden) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });

            roomMediaCloseButtons.forEach((button) => {
                if (button.dataset.mediaCloseBound === '1') {
                    return;
                }
                button.dataset.mediaCloseBound = '1';
                button.addEventListener('click', function () {
                    const roomId = String(button.getAttribute('data-close-room-media') || '').trim();
                    if (!roomId) {
                        return;
                    }
                    const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                    if (panel) {
                        panel.hidden = true;
                    }
                });
            });

            function initMediaUploadForms() {
                document.querySelectorAll('[data-media-upload-form]').forEach((form, formIndex) => {
                    if (form.dataset.mediaUploaderBound === '1') {
                        return;
                    }
                    form.dataset.mediaUploaderBound = '1';

                    const dropzone = form.querySelector('[data-media-dropzone]');
                    const fileInput = form.querySelector('[data-media-input]');
                    const preview = form.querySelector('[data-media-preview]');
                    const primaryIndexInput = form.querySelector('[data-media-primary-index]');
                    if (!dropzone || !fileInput || !preview || !primaryIndexInput) {
                        return;
                    }

                    const radioName = 'media_primary_picker_' + formIndex;

                    function syncFilesFromList(fileList) {
                        if (typeof DataTransfer === 'undefined') {
                            return;
                        }
                        const transfer = new DataTransfer();
                        fileList.forEach((file) => transfer.items.add(file));
                        fileInput.files = transfer.files;
                    }

                    function removeFileAt(indexToRemove) {
                        const files = Array.from(fileInput.files || []);
                        if (indexToRemove < 0 || indexToRemove >= files.length) {
                            return;
                        }

                        const nextFiles = files.filter((_, index) => index !== indexToRemove);
                        let currentPrimary = parseInt(primaryIndexInput.value || '0', 10) || 0;
                        if (indexToRemove < currentPrimary) {
                            currentPrimary -= 1;
                        } else if (indexToRemove === currentPrimary) {
                            currentPrimary = Math.max(0, currentPrimary - 1);
                        }
                        if (nextFiles.length === 0) {
                            currentPrimary = 0;
                        } else {
                            currentPrimary = Math.min(currentPrimary, nextFiles.length - 1);
                        }

                        primaryIndexInput.value = String(currentPrimary);
                        syncFilesFromList(nextFiles);
                        renderPreview();
                    }

                    function renderPreview() {
                        preview.innerHTML = '';
                        const files = Array.from(fileInput.files || []);
                        if (files.length === 0) {
                            primaryIndexInput.value = '0';
                            return;
                        }

                        const currentPrimary = Math.max(0, Math.min(files.length - 1, parseInt(primaryIndexInput.value || '0', 10) || 0));
                        primaryIndexInput.value = String(currentPrimary);

                        files.forEach((file, index) => {
                            const item = document.createElement('article');
                            item.className = 'media-upload-item';

                            const img = document.createElement('img');
                            img.alt = file.name;
                            img.src = URL.createObjectURL(file);
                            img.onload = function () {
                                URL.revokeObjectURL(img.src);
                            };

                            const meta = document.createElement('div');
                            meta.className = 'media-upload-meta';

                            const name = document.createElement('p');
                            name.className = 'small';
                            name.style.margin = '0';
                            name.textContent = file.name;

                            const label = document.createElement('label');
                            label.className = 'media-primary-select';

                            const radio = document.createElement('input');
                            radio.type = 'radio';
                            radio.name = radioName;
                            radio.value = String(index);
                            radio.checked = index === currentPrimary;
                            radio.addEventListener('change', function () {
                                primaryIndexInput.value = String(index);
                            });

                            const text = document.createElement('span');
                            text.textContent = 'Primary';

                            const removeButton = document.createElement('button');
                            removeButton.type = 'button';
                            removeButton.className = 'media-remove-btn';
                            removeButton.textContent = 'Remove';
                            removeButton.addEventListener('click', function () {
                                removeFileAt(index);
                            });

                            label.appendChild(radio);
                            label.appendChild(text);
                            meta.appendChild(name);
                            meta.appendChild(label);
                            meta.appendChild(removeButton);
                            item.appendChild(img);
                            item.appendChild(meta);
                            preview.appendChild(item);
                        });
                    }

                    fileInput.addEventListener('change', function () {
                        renderPreview();
                    });

                    dropzone.addEventListener('click', function () {
                        fileInput.click();
                    });

                    dropzone.addEventListener('dragover', function (event) {
                        event.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });

                    dropzone.addEventListener('dragleave', function () {
                        dropzone.classList.remove('is-dragover');
                    });

                    dropzone.addEventListener('drop', function (event) {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragover');
                        const droppedFiles = Array.from((event.dataTransfer && event.dataTransfer.files) ? event.dataTransfer.files : []);
                        if (droppedFiles.length === 0) {
                            return;
                        }
                        syncFilesFromList(droppedFiles);
                        renderPreview();
                    });

                    renderPreview();
                });
            }

            initMediaUploadForms();

            listingCategoryShortcutButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const categoryKey = String(button.getAttribute('data-listing-category-shortcut') || '');
                    openPropertyFlowWithCategory(categoryKey);
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
                getLocationTree().then(function () {
                    refreshLocationSelectors();
                    centerMapForLocationSelection(true);
                });
                locationCountry.addEventListener("change", function () {
                    refreshLocationSelectors();
                    centerMapForLocationSelection(true);
                });
                locationState.addEventListener("change", function () {
                    refreshCitySelector();
                    centerMapForLocationSelection(true);
                });
                locationCity.addEventListener("change", function () {
                    centerMapForLocationSelection(true);
                });
            }

            if (propertyCategorySelect) {
                refreshPropertyCategoryFields();
                propertyCategorySelect.addEventListener("change", refreshPropertyCategoryFields);
            }

            if (transportModeInput) {
                transportModeInput.addEventListener("input", refreshTransportFieldLabels);
                transportModeInput.addEventListener("change", refreshTransportFieldLabels);
            }

            if (transportPricingModelSelect) {
                transportPricingModelSelect.addEventListener("change", refreshTransportFieldLabels);
            }

            if (billingCountry && billingState && billingCity) {
                billingState.dataset.selectedValue = "{{ old('billing_state', optional($vendorBilling)->billing_state ?? '') }}";
                billingCity.dataset.selectedValue = "{{ old('billing_city', optional($vendorBilling)->billing_city ?? '') }}";
                refreshBillingLocationSelectors();
                getLocationTree().then(function () {
                    refreshBillingLocationSelectors();
                });
                billingCountry.addEventListener("change", function () {
                    refreshBillingLocationSelectors();
                });
                billingState.addEventListener("change", function () {
                    refreshBillingCitySelector();
                });
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

            initializePanelStateSafe();
            restoreGuidedWizardState();
            renderGuidedWizard();
            applyPropertyCategoryFilter('all');
        })();
    </script>
    <script>
        (function () {
            function normalizeCategoryKey(value) {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
            }

            function initOpsCategoryToggles() {
                const toggles = Array.from(document.querySelectorAll('[data-ops-category-toggle]'));
                if (toggles.length === 0) {
                    return;
                }

                const groups = new Map();

                function setExpanded(toggle, content, expanded) {
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    content.hidden = !expanded;
                    const card = toggle.closest('.ops-category-card');
                    if (card) {
                        card.classList.toggle('is-collapsed', !expanded);
                    }
                }

                toggles.forEach((toggle) => {
                    const targetId = String(toggle.getAttribute('data-ops-target') || '').trim();
                    if (targetId === '') {
                        return;
                    }

                    const content = document.getElementById(targetId);
                    if (!content) {
                        return;
                    }

                    const groupKey = String(toggle.getAttribute('data-ops-group') || 'ops').trim() || 'ops';
                    if (!groups.has(groupKey)) {
                        groups.set(groupKey, []);
                    }
                    groups.get(groupKey).push({ toggle, content });

                    toggle.addEventListener('click', function () {
                        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                        setExpanded(toggle, content, !isExpanded);
                    });
                });

                groups.forEach((entries) => {
                    entries.forEach((entry, index) => {
                        setExpanded(entry.toggle, entry.content, index === 0);
                    });
                });
            }

            function initFallbackPanelNavigation() {
                const navLinks = Array.from(document.querySelectorAll('.portal-nav a[data-panel-key]'));
                const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
                if (navLinks.length === 0 || panelGroups.length === 0) {
                    return;
                }

                const validKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));

                function resolvePanelKey(hashValue) {
                    const panelKey = String(hashValue || "").replace(/^#/, "").trim().toLowerCase();
                    return validKeys.has(panelKey) ? panelKey : "overview";
                }

                function showPanel(panelKey) {
                    panelGroups.forEach((panel) => {
                        panel.hidden = (panel.getAttribute('data-panel-group') || '') !== panelKey;
                    });
                    navLinks.forEach((link) => {
                        const isActive = String(link.dataset.panelKey || '') === panelKey;
                        link.classList.toggle('is-active', isActive);
                    });
                }

                navLinks.forEach((link) => {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                        if (!panelKey) {
                            return;
                        }
                        window.location.hash = panelKey;
                        showPanel(panelKey);
                    });
                });

                window.addEventListener('hashchange', function () {
                    showPanel(resolvePanelKey(window.location.hash));
                });

                showPanel(resolvePanelKey(window.location.hash || '#overview'));
            }

            function initFallbackListingActions() {
                const openPropertyCreateForm = document.getElementById('openPropertyCreateForm');
                const closePropertyCreateForm = document.getElementById('closePropertyCreateForm');
                const backToListingsFromCreate = document.getElementById('backToListingsFromCreate');
                const propertyCreateForm = document.getElementById('propertyCreateForm');
                const propertyCategorySelect = document.getElementById('property_listing_category');
                const propertyCategoryScopeNote = document.getElementById('propertyCategoryScopeNote');
                const propertyCreateFormTitle = document.getElementById('propertyCreateFormTitle');
                const propertyCreateFormSubtitle = document.getElementById('propertyCreateFormSubtitle');
                const propertyCreateSubmitButton = document.getElementById('propertyCreateSubmitButton');
                const propertyTypeSelect = document.getElementById('property_type');
                const propertyBasePriceLabel = document.querySelector('label[for="property_base_price"]');
                const propertyMaxGuestsLabel = document.querySelector('label[for="property_max_guests"]');
                const propertyCapacityLabel = document.querySelector('label[for="property_capacity_value"]');
                const transportModeInput = document.getElementById('property_transport_mode');
                const transportPricingHint = document.getElementById('transportPricingHint');
                const transportPricingModelSelect = document.getElementById('property_transport_pricing_model');
                const transportLandOnlyFields = Array.from(document.querySelectorAll('[data-transport-land-only]'));
                const transportMarineOnlyFields = Array.from(document.querySelectorAll('[data-transport-marine-only]'));
                const categoryScopedFields = Array.from(document.querySelectorAll('[data-category-scope]'));
                const categoryViewPanels = Array.from(document.querySelectorAll('[data-category-view]'));
                const roomCreateForm = document.getElementById('roomCreateForm');
                const closeRoomCreateForm = document.getElementById('closeRoomCreateForm');
                const roomPropertySelect = document.getElementById('room_vendor_property_id');
                const pricingNameInput = document.getElementById('pricing_name');
                const pricingTypeInput = document.getElementById('pricing_type');
                const pricingValueInput = document.getElementById('pricing_value');
                const pricingStartsInput = document.getElementById('pricing_starts');
                const pricingEndsInput = document.getElementById('pricing_ends');
                const pricingPropertyInput = document.getElementById('pricing_property_id');
                const pricingServiceInput = document.getElementById('pricing_service_id');
                const pricingRoomInput = document.getElementById('pricing_room_id');
                const availabilityForms = Array.from(document.querySelectorAll('[data-availability-form]'));
                const transferRateForms = Array.from(document.querySelectorAll('[data-transfer-rate-form]'));

                function categoryScopesFor(category) {
                    const normalized = normalizeCategoryKey(category);
                    if (normalized === 'accommodation') return ['stay', 'accommodation', 'geo'];
                    if (normalized === 'transport') return ['capacity', 'transport', 'geo'];
                    if (normalized === 'excursion') return ['capacity', 'service', 'excursion', 'geo'];
                    if (normalized === 'remote_workspace') return ['stay', 'capacity', 'workspace', 'geo'];
                    if (normalized === 'resort_day_visit') return ['capacity', 'day_visit', 'geo'];
                    if (normalized === 'restaurant') return ['capacity', 'restaurant', 'geo'];
                    if (normalized === 'vehicle_rental') return ['vehicle', 'capacity', 'rental', 'geo'];
                    return ['stay', 'accommodation', 'capacity', 'service', 'vehicle', 'transport', 'excursion', 'workspace', 'day_visit', 'restaurant', 'rental', 'geo'];
                }

                function categoryMetaFor(category) {
                    const normalized = normalizeCategoryKey(category);
                    const metaMap = {
                        accommodation: ['Accommodation Enlisting', 'Fill required fields and save.', 'Save Accommodation Listing', 'Fill required fields and save.', 'property'],
                        transport: ['Transport Enlisting', 'Fill required fields and save.', 'Save Transport Listing', 'Fill required fields and save.', 'service'],
                        excursion: ['Excursion Enlisting', 'Fill required fields and save.', 'Save Excursion Listing', 'Fill required fields and save.', 'service'],
                        remote_workspace: ['Remote Workspace Enlisting', 'Fill required fields and save.', 'Save Remote Workspace Listing', 'Fill required fields and save.', 'service'],
                        resort_day_visit: ['Resort Day Visit Enlisting', 'Fill required fields and save.', 'Save Resort Day Visit Listing', 'Fill required fields and save.', 'service'],
                        restaurant: ['Restaurant Enlisting', 'Fill required fields and save.', 'Save Restaurant Listing', 'Fill required fields and save.', 'service'],
                        vehicle_rental: ['Vehicle Rental Enlisting', 'Fill required fields and save.', 'Save Vehicle Rental Listing', 'Fill required fields and save.', 'service']
                    };
                    return metaMap[normalized] || ['Listing Enlisting', 'Fill required fields and save.', 'Save Listing', 'Fill required fields and save.', 'service'];
                }

                function ensureAutoCategorySelected(preferredCategory) {
                    if (!propertyCategorySelect) return '';
                    const preferred = normalizeCategoryKey(preferredCategory || propertyCategorySelect.getAttribute('data-default-category') || 'accommodation');
                    if (preferred !== '') {
                        const matched = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === preferred);
                        if (matched) {
                            propertyCategorySelect.value = matched.value;
                        }
                    }
                    if ((!propertyCategorySelect.value || String(propertyCategorySelect.value).trim() === '') && propertyCategorySelect.options.length > 0) {
                        propertyCategorySelect.value = propertyCategorySelect.options[0].value;
                    }
                    return String(propertyCategorySelect.value || '');
                }

                function isMarineTransportMode(value) {
                    const mode = String(value || '').trim().toLowerCase();
                    return /(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/.test(mode);
                }

                function refreshTransportFieldLabels() {
                    if (!propertyCategorySelect) {
                        return;
                    }

                    const normalizedCategory = normalizeCategoryKey(propertyCategorySelect.value);
                    const isTransportCategory = normalizedCategory === 'transport';
                    const isRemoteWorkspaceCategory = normalizedCategory === 'remote_workspace';
                    const isMarine = isMarineTransportMode(transportModeInput ? transportModeInput.value : '');
                    const selectedPricingModel = transportPricingModelSelect ? String(transportPricingModelSelect.value || 'per_trip') : 'per_trip';

                    if (propertyBasePriceLabel) {
                        propertyBasePriceLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Price Per Seat (MVR)' : 'Price Per Trip (MVR)')
                            : (isRemoteWorkspaceCategory ? 'Booking Fee Per Guest (MVR)' : 'Base Price (MVR)');
                    }
                    if (propertyCapacityLabel) {
                        propertyCapacityLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Seat Capacity' : 'Max Passengers Per Trip')
                            : (isRemoteWorkspaceCategory ? 'Workspace Capacity (seats/desks)' : 'Capacity');
                    }
                    if (propertyMaxGuestsLabel) {
                        propertyMaxGuestsLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Seat Capacity (Legacy)' : 'Max Passengers (Legacy)')
                            : (isRemoteWorkspaceCategory ? 'Max Bookable Guests' : 'Max Guests');
                    }
                    if (transportPricingHint) {
                        transportPricingHint.textContent = isTransportCategory
                            ? (isMarine
                                ? 'Marine transport mode detected: pricing is per seat. Define pickup and dropoff, then select one-way or round-trip.'
                                : 'Land transport mode detected: choose per-trip, hourly, or daily pricing and set max passengers per trip.')
                                : 'Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.';
                    }

                    transportLandOnlyFields.forEach((field) => {
                        const shouldShow = isTransportCategory && !isMarine;
                        field.hidden = !shouldShow;
                        field.style.display = shouldShow ? '' : 'none';
                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = !shouldShow;
                        });
                    });

                    transportMarineOnlyFields.forEach((field) => {
                        const shouldShow = isTransportCategory && isMarine;
                        field.hidden = !shouldShow;
                        field.style.display = shouldShow ? '' : 'none';
                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = !shouldShow;
                        });
                    });

                    const hourlyField = document.getElementById('property_hourly_rate');
                    const dailyField = document.getElementById('property_daily_rate');
                    if (hourlyField) {
                        const showHourly = isTransportCategory && !isMarine && selectedPricingModel === 'hourly';
                        hourlyField.disabled = !showHourly;
                        if (hourlyField.parentElement) {
                            hourlyField.parentElement.hidden = !showHourly;
                            hourlyField.parentElement.style.display = showHourly ? '' : 'none';
                        }
                    }
                    if (dailyField) {
                        const showDaily = isTransportCategory && !isMarine && selectedPricingModel === 'daily';
                        dailyField.disabled = !showDaily;
                        if (dailyField.parentElement) {
                            dailyField.parentElement.hidden = !showDaily;
                            dailyField.parentElement.style.display = showDaily ? '' : 'none';
                        }
                    }
                }

                function applyCategoryMode(category) {
                    const normalized = normalizeCategoryKey(category);
                    const activeScopes = categoryScopesFor(normalized);
                    const meta = categoryMetaFor(normalized);

                    categoryScopedFields.forEach((field) => {
                        const scopes = String(field.getAttribute('data-category-scope') || '')
                            .split(',')
                            .map((item) => item.trim().toLowerCase())
                            .filter(Boolean);
                        const shouldShow = scopes.length === 0 || scopes.some((scope) => activeScopes.includes(scope));
                        field.hidden = !shouldShow;
                        field.style.display = shouldShow ? '' : 'none';
                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            if (!input.hasAttribute('data-preserve-enabled')) {
                                input.setAttribute('data-preserve-enabled', input.disabled ? '1' : '0');
                            }
                            input.disabled = !shouldShow;
                        });
                    });

                    categoryViewPanels.forEach((panel) => {
                        panel.hidden = normalizeCategoryKey(panel.getAttribute('data-category-view') || '') !== normalized;
                    });

                    if (propertyCreateFormTitle) propertyCreateFormTitle.textContent = meta[0];
                    if (propertyCreateFormSubtitle) propertyCreateFormSubtitle.textContent = meta[1];
                    if (propertyCreateSubmitButton) propertyCreateSubmitButton.textContent = meta[2];
                    if (propertyCategoryScopeNote) propertyCategoryScopeNote.textContent = meta[3];
                    if (propertyTypeSelect) propertyTypeSelect.value = meta[4];
                    refreshTransportFieldLabels();
                }

                if (openPropertyCreateForm && propertyCreateForm) {
                    openPropertyCreateForm.addEventListener('click', function () {
                        propertyCreateForm.hidden = false;
                        if (closePropertyCreateForm) {
                            closePropertyCreateForm.hidden = false;
                        }
                        applyCategoryMode(ensureAutoCategorySelected(''));
                        if (typeof window.__vendorPortalRefreshLocationMap === 'function') {
                            window.__vendorPortalRefreshLocationMap();
                        }
                    });
                }

                if (closePropertyCreateForm && propertyCreateForm) {
                    closePropertyCreateForm.addEventListener('click', function () {
                        propertyCreateForm.hidden = true;
                        closePropertyCreateForm.hidden = true;
                    });
                }

                if (backToListingsFromCreate && propertyCreateForm) {
                    backToListingsFromCreate.addEventListener('click', function () {
                        propertyCreateForm.hidden = true;
                        if (closePropertyCreateForm) {
                            closePropertyCreateForm.hidden = true;
                        }
                        window.location.hash = 'listings';
                    });
                }

                document.querySelectorAll('[data-listing-category-shortcut]').forEach((button) => {
                    button.addEventListener('click', function () {
                        if (propertyCreateForm) {
                            propertyCreateForm.hidden = false;
                        }
                        if (closePropertyCreateForm) {
                            closePropertyCreateForm.hidden = false;
                        }

                        const categoryKey = normalizeCategoryKey(button.getAttribute('data-listing-category-shortcut') || '');
                        if (propertyCategorySelect && categoryKey) {
                            let option = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === categoryKey);
                            if (!option) {
                                option = document.createElement('option');
                                option.value = categoryKey;
                                option.textContent = categoryKey;
                                propertyCategorySelect.appendChild(option);
                            }
                            propertyCategorySelect.value = ensureAutoCategorySelected(option.value);
                            applyCategoryMode(propertyCategorySelect.value);
                        }

                        window.location.hash = 'listings';
                        if (typeof window.__vendorPortalRefreshLocationMap === 'function') {
                            window.__vendorPortalRefreshLocationMap();
                        }
                    });
                });

                document.querySelectorAll('[data-open-room-form]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-property-id') || '').trim();
                        if (roomCreateForm) {
                            roomCreateForm.hidden = false;
                        }
                        if (closeRoomCreateForm) {
                            closeRoomCreateForm.hidden = false;
                        }
                        if (roomPropertySelect && propertyId) {
                            let option = Array.from(roomPropertySelect.options).find((item) => String(item.value) === propertyId);
                            if (!option) {
                                option = document.createElement('option');
                                option.value = propertyId;
                                option.textContent = '#' + propertyId;
                                roomPropertySelect.appendChild(option);
                            }
                            roomPropertySelect.value = propertyId;
                        }
                        window.location.hash = 'listings';
                        if (roomCreateForm) {
                            roomCreateForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                function applyAvailabilityTargetSelectionFor(form) {
                    if (!form) return;
                    const targetSelect = form.querySelector('[data-availability-target]');
                    if (!targetSelect) return;

                    const selectedOption = targetSelect.options[targetSelect.selectedIndex] || null;
                    const propertyId = selectedOption ? String(selectedOption.getAttribute('data-property-id') || '').trim() : '';
                    const serviceId = selectedOption ? String(selectedOption.getAttribute('data-service-id') || '').trim() : '';
                    const roomId = selectedOption ? String(selectedOption.getAttribute('data-room-id') || '').trim() : '';
                    const routeName = selectedOption ? String(selectedOption.getAttribute('data-route-name') || '').trim() : '';

                    const propertyInput = form.querySelector('[data-availability-role="property"]');
                    const serviceInput = form.querySelector('[data-availability-role="service"]');
                    const roomInput = form.querySelector('[data-availability-role="room"]');
                    const routeInput = form.querySelector('[data-availability-role="route"]');

                    if (propertyInput) propertyInput.value = propertyId;
                    if (serviceInput) serviceInput.value = serviceId;
                    if (roomInput) roomInput.value = roomId;
                    if (routeInput && routeName !== '' && String(routeInput.value || '').trim() === '') {
                        routeInput.value = routeName;
                    }
                }

                function parseTransferOptions(rawValue) {
                    return String(rawValue || '')
                        .split(',')
                        .map((token) => token.trim().toLowerCase())
                        .filter((token) => token !== '');
                }

                function applyTransferRateSelectionFor(form) {
                    if (!form) {
                        return;
                    }

                    const targetSelect = form.querySelector('[data-transfer-rate-target]');
                    if (!targetSelect) {
                        return;
                    }

                    const selectedOption = targetSelect.options[targetSelect.selectedIndex] || null;
                    const configuredOptions = parseTransferOptions(selectedOption ? selectedOption.getAttribute('data-transfer-options') : '');
                    const configuredOptionSet = new Set(configuredOptions);
                    const hasListingSelected = String(targetSelect.value || '').trim() !== '';

                    const optionChecks = Array.from(form.querySelectorAll('[data-transfer-option-check]'));
                    const rateInputs = Array.from(form.querySelectorAll('[data-transfer-rate-input]'));

                    optionChecks.forEach((check) => {
                        const transferKey = String(check.value || '').trim().toLowerCase();
                        const isConfiguredForListing = hasListingSelected && configuredOptionSet.has(transferKey);
                        check.disabled = !isConfiguredForListing;
                        check.checked = isConfiguredForListing;
                    });

                    rateInputs.forEach((input) => {
                        const transferKey = String(input.getAttribute('data-transfer-rate-input') || '').trim().toLowerCase();
                        const isConfiguredForListing = hasListingSelected && configuredOptionSet.has(transferKey);
                        input.disabled = !isConfiguredForListing;
                        input.value = '';

                        if (!isConfiguredForListing || !selectedOption) {
                            return;
                        }

                        const rateAttr = selectedOption.getAttribute('data-transfer-rate-' + transferKey);
                        const rateValue = Number(rateAttr);
                        if (Number.isFinite(rateValue) && rateValue > 0) {
                            input.value = String(rateValue);
                        }
                    });
                }

                availabilityForms.forEach((form) => {
                    const targetSelect = form.querySelector('[data-availability-target]');
                    if (!targetSelect) return;
                    targetSelect.addEventListener('change', function () {
                        applyAvailabilityTargetSelectionFor(form);
                    });

                    form.addEventListener('submit', function (event) {
                        const listingCategoryInput = form.querySelector('input[name="listing_category"]');
                        const listingCategory = String(listingCategoryInput ? listingCategoryInput.value : '').trim().toLowerCase();
                        if (listingCategory !== 'accommodation') {
                            if (targetSelect.setCustomValidity) {
                                targetSelect.setCustomValidity('');
                            }
                            return;
                        }

                        const roomInput = form.querySelector('[data-availability-role="room"]');
                        const selectedRoomId = String(roomInput ? roomInput.value : '').trim();
                        if (selectedRoomId !== '') {
                            if (targetSelect.setCustomValidity) {
                                targetSelect.setCustomValidity('');
                            }
                            return;
                        }

                        event.preventDefault();
                        if (targetSelect.setCustomValidity) {
                            targetSelect.setCustomValidity('Please select a room for accommodation availability.');
                        }
                        if (targetSelect.reportValidity) {
                            targetSelect.reportValidity();
                        }
                        targetSelect.focus();
                    });

                    const closedSelect = form.querySelector('select[name="is_closed"]');
                    if (closedSelect) {
                        closedSelect.dataset.userTouched = 'false';
                        closedSelect.addEventListener('change', function () {
                            closedSelect.dataset.userTouched = 'true';
                        });
                    }

                    applyAvailabilityTargetSelectionFor(form);
                });

                transferRateForms.forEach((form) => {
                    const targetSelect = form.querySelector('[data-transfer-rate-target]');
                    if (!targetSelect) {
                        return;
                    }

                    targetSelect.addEventListener('change', function () {
                        applyTransferRateSelectionFor(form);
                    });

                    applyTransferRateSelectionFor(form);
                });

                const availabilityFormByKey = new Map();
                availabilityForms.forEach((form) => {
                    const formKey = String(form.getAttribute('data-availability-form') || '').trim();
                    if (formKey !== '') {
                        availabilityFormByKey.set(formKey, form);
                    }
                });

                function expandCategoryCardFor(element) {
                    const card = element ? element.closest('.ops-category-card') : null;
                    if (!card) {
                        return;
                    }

                    const toggle = card.querySelector('[data-ops-category-toggle]');
                    if (!toggle) {
                        return;
                    }

                    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        return;
                    }

                    const targetId = String(toggle.getAttribute('data-ops-target') || '').trim();
                    if (targetId !== '') {
                        const content = document.getElementById(targetId);
                        if (content) {
                            content.hidden = false;
                        }
                    }
                    toggle.setAttribute('aria-expanded', 'true');
                    card.classList.remove('is-collapsed');
                }

                function todayIsoDate() {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }

                document.querySelectorAll('[data-availability-pick-target]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const formKey = String(button.getAttribute('data-availability-form-key') || '').trim();
                        const targetValue = String(button.getAttribute('data-target-value') || '').trim();
                        if (formKey === '' || targetValue === '') {
                            return;
                        }

                        const form = availabilityFormByKey.get(formKey);
                        if (!form) {
                            return;
                        }

                        const targetSelect = form.querySelector('[data-availability-target]');
                        if (!targetSelect) {
                            return;
                        }

                        const hasOption = Array.from(targetSelect.options).some((option) => String(option.value) === targetValue);
                        if (!hasOption) {
                            return;
                        }

                        expandCategoryCardFor(button);
                        targetSelect.value = targetValue;
                        applyAvailabilityTargetSelectionFor(form);

                        const dateInput = form.querySelector('input[name="slot_date"]');
                        if (dateInput && String(dateInput.value || '').trim() === '') {
                            dateInput.value = todayIsoDate();
                        }

                        const closedSelect = form.querySelector('select[name="is_closed"]');
                        if (closedSelect && closedSelect.dataset.userTouched !== 'true') {
                            closedSelect.value = '0';
                        }

                        const inventoryInput = form.querySelector('input[name="inventory"]');
                        if (inventoryInput) {
                            inventoryInput.focus({ preventScroll: true });
                            inventoryInput.select();
                        }

                        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    });
                });

                document.querySelectorAll('[data-price-suggestion]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const targetType = String(button.getAttribute('data-target-type') || '').trim().toLowerCase();
                        const targetId = String(button.getAttribute('data-target-id') || '').trim();
                        const ruleType = String(button.getAttribute('data-rule-type') || '').trim();
                        const ruleValue = String(button.getAttribute('data-rule-value') || '').trim();
                        const targetLabel = String(button.getAttribute('data-target-label') || '').trim();

                        if (pricingTypeInput && ruleType !== '') {
                            pricingTypeInput.value = ruleType;
                        }
                        if (pricingValueInput && ruleValue !== '') {
                            pricingValueInput.value = ruleValue;
                        }
                        if (pricingNameInput) {
                            const title = targetLabel !== '' ? targetLabel : (targetType + ' ' + targetId);
                            pricingNameInput.value = (ruleType === 'weekend_markup' ? 'Weekend uplift: ' : 'Promo: ') + title;
                        }

                        if (pricingPropertyInput) pricingPropertyInput.value = '';
                        if (pricingServiceInput) pricingServiceInput.value = '';
                        if (pricingRoomInput) pricingRoomInput.value = '';

                        if (targetType === 'property' && pricingPropertyInput) {
                            pricingPropertyInput.value = targetId;
                        } else if (targetType === 'service' && pricingServiceInput) {
                            pricingServiceInput.value = targetId;
                        } else if (targetType === 'room' && pricingRoomInput) {
                            pricingRoomInput.value = targetId;
                        }

                        const today = new Date();
                        const plusThirty = new Date();
                        plusThirty.setDate(today.getDate() + 30);
                        const nextFriday = new Date(today);
                        const dayOfWeek = nextFriday.getDay();
                        const daysUntilFriday = (5 - dayOfWeek + 7) % 7;
                        nextFriday.setDate(nextFriday.getDate() + (daysUntilFriday === 0 ? 7 : daysUntilFriday));
                        const nextSaturday = new Date(nextFriday);
                        nextSaturday.setDate(nextFriday.getDate() + 1);
                        const isoDay = (date) => {
                            const y = date.getFullYear();
                            const m = String(date.getMonth() + 1).padStart(2, '0');
                            const d = String(date.getDate()).padStart(2, '0');
                            return y + '-' + m + '-' + d;
                        };

                        if (pricingStartsInput && !pricingStartsInput.value && ruleType === 'weekend_markup') {
                            pricingStartsInput.value = isoDay(nextFriday);
                        } else if (pricingStartsInput && !pricingStartsInput.value) {
                            pricingStartsInput.value = isoDay(today);
                        }
                        if (pricingEndsInput && !pricingEndsInput.value && ruleType === 'weekend_markup') {
                            pricingEndsInput.value = isoDay(nextSaturday);
                        }
                        if (pricingEndsInput && !pricingEndsInput.value && (ruleType === 'promo_discount' || ruleType === 'demand_discount')) {
                            pricingEndsInput.value = isoDay(plusThirty);
                        }

                        const pricingSection = document.getElementById('vendorPricingSection');
                        if (pricingSection) {
                            pricingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                document.querySelectorAll('[data-toggle-property-media]').forEach((button) => {
                    if (button.dataset.mediaToggleBound === '1') {
                        return;
                    }
                    button.dataset.mediaToggleBound = '1';
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-toggle-property-media') || '').trim();
                        if (!propertyId) return;
                        const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                        if (!panel) return;
                        panel.hidden = !panel.hidden;
                    });
                });

                document.querySelectorAll('[data-close-property-media]').forEach((button) => {
                    if (button.dataset.mediaCloseBound === '1') {
                        return;
                    }
                    button.dataset.mediaCloseBound = '1';
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-close-property-media') || '').trim();
                        if (!propertyId) return;
                        const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                        if (panel) panel.hidden = true;
                    });
                });

                document.querySelectorAll('[data-toggle-room-media]').forEach((button) => {
                    if (button.dataset.mediaToggleBound === '1') {
                        return;
                    }
                    button.dataset.mediaToggleBound = '1';
                    button.addEventListener('click', function () {
                        const roomId = String(button.getAttribute('data-toggle-room-media') || '').trim();
                        if (!roomId) return;
                        const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                        if (!panel) return;
                        panel.hidden = !panel.hidden;
                    });
                });

                document.querySelectorAll('[data-close-room-media]').forEach((button) => {
                    if (button.dataset.mediaCloseBound === '1') {
                        return;
                    }
                    button.dataset.mediaCloseBound = '1';
                    button.addEventListener('click', function () {
                        const roomId = String(button.getAttribute('data-close-room-media') || '').trim();
                        if (!roomId) return;
                        const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                        if (panel) panel.hidden = true;
                    });
                });

                document.querySelectorAll('[data-open-property-edit]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                        if (!editId) return;
                        const form = document.querySelector('[data-property-edit-form="' + editId + '"]');
                        if (form) {
                            const category = normalizeCategoryKey(form.getAttribute('data-property-edit-category') || button.getAttribute('data-property-edit-category') || '');
                            const activeScopes = categoryScopesFor(category);
                            form.querySelectorAll('[data-property-edit-scope]').forEach((field) => {
                                const scope = normalizeCategoryKey(field.getAttribute('data-property-edit-scope') || '');
                                const shouldShow = activeScopes.includes(scope);
                                field.hidden = !shouldShow;
                                field.style.display = shouldShow ? '' : 'none';
                                if ('disabled' in field) {
                                    field.disabled = !shouldShow;
                                }
                                field.querySelectorAll('input, select, textarea').forEach((input) => {
                                    input.disabled = !shouldShow;
                                });
                            });
                            form.hidden = false;
                        }
                    });
                });

                document.querySelectorAll('[data-close-property-edit]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                        if (!editId) return;
                        const form = document.querySelector('[data-property-edit-form="' + editId + '"]');
                        if (form) form.hidden = true;
                    });
                });

                if (propertyCategorySelect) {
                    propertyCategorySelect.addEventListener('change', function () {
                        applyCategoryMode(propertyCategorySelect.value);
                    });
                    applyCategoryMode(ensureAutoCategorySelected(''));
                }

                if (transportModeInput) {
                    transportModeInput.addEventListener('input', refreshTransportFieldLabels);
                    transportModeInput.addEventListener('change', refreshTransportFieldLabels);
                }

                if (transportPricingModelSelect) {
                    transportPricingModelSelect.addEventListener('change', refreshTransportFieldLabels);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    initFallbackPanelNavigation();
                    initFallbackListingActions();
                    initOpsCategoryToggles();
                });
            } else {
                initFallbackPanelNavigation();
                initFallbackListingActions();
                initOpsCategoryToggles();
            }
        })();
    </script>
</body>
</html>
