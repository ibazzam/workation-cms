<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partners Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            width: min(1180px, calc(100% - 24px));
            max-width: none;
            margin: 14px auto 28px;
            padding: 10px 0 20px;
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

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .hero-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .hero-links {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .auth-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .hero-highlights {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .hero-highlight {
            border: 1px solid rgba(202, 236, 241, 0.28);
            border-radius: 12px;
            background: rgba(6, 49, 65, 0.22);
            padding: 10px 12px;
        }

        .hero-highlight-label {
            margin: 0;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #c9edf2;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .hero-highlight-value {
            margin: 6px 0 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
        }

        .hero-highlight-meta {
            margin: 5px 0 0;
            font-size: 0.76rem;
            color: #d5eef1;
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
            gap: 4px;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            max-height: calc(100vh - 16px);
            overflow-y: auto;
        }

        .vendor-nav-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 4px 10px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e5edf3;
        }

        .vendor-nav-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: linear-gradient(135deg, #d5ecf5 0%, #b9deea 100%);
            color: #0e4a64;
            font-size: 0.82rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .vendor-nav-user-meta {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .vendor-nav-user-name {
            margin: 0;
            font-size: 0.84rem;
            font-weight: 700;
            color: #163042;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .vendor-nav-user-email {
            margin: 0;
            font-size: 0.72rem;
            color: #6f8598;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-group-header {
            width: 100%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 6px;
            color: #1a3247;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
        }

        .nav-group-header:hover {
            background: #f3f8fc;
            border-radius: 8px;
        }

        .nav-chevron {
            font-size: 0.72rem;
            color: #7990a5;
            transition: transform 0.2s ease;
        }

        .nav-group-header[aria-expanded="false"] .nav-chevron {
            transform: rotate(-90deg);
        }

        .nav-group-body {
            display: grid;
            gap: 3px;
            padding: 0 0 4px 0;
        }

        .nav-group-body:not(.is-open) {
            display: none;
        }

        .portal-content {
            min-width: 0;
            width: 100%;
        }

        .portal-content > section,
        .portal-content > div {
            width: 100%;
        }

        .portal-nav a,
        .nav-item-link,
        .nav-sub-link {
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e4456;
            background: #f7fbff;
            transition: all 0.15s ease;
        }

        .nav-group-title {
            margin: 10px 4px 4px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6a7f93;
        }

        .portal-nav a.prominent,
        .nav-item-link.prominent,
        .nav-sub-link.prominent {
            border-color: #0f6b74;
            background: #e8f7f8;
            color: #0d4f56;
        }

        .nav-sub-link {
            margin-left: 12px;
            font-size: 0.79rem;
            font-weight: 600;
            color: #35566f;
            background: #fbfdff;
        }

        .portal-nav a:hover,
        .nav-item-link:hover,
        .nav-sub-link:hover {
            border-color: #cddce8;
            background: #eef7fd;
            color: #124967;
        }

        .portal-nav a.is-active,
        .nav-item-link.is-active,
        .nav-sub-link.is-active {
            border-color: #0f6b74;
            background: #e8f7f8;
            color: #0d4f56;
        }

        .nav-divider {
            height: 1px;
            margin: 5px 4px;
            background: #e4ebf1;
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

        .reports-grid {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .report-card {
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #ffffff;
            padding: 12px;
        }

        .report-card h3 {
            margin: 0 0 6px;
            font-size: 0.96rem;
            color: #173754;
        }

        .report-card p {
            margin: 0;
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.45;
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

        .form-error-banner {
            background: #fff0ef;
            border: 1px solid #f0b7b3;
            border-radius: 8px;
            color: #7a2020;
            font-size: 0.84rem;
            padding: 8px 12px;
            margin-bottom: 12px;
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

        .listing-management-table tr.is-media-open .listing-cell-main {
            display: none;
        }

        .listing-management-table tr.is-media-open .listing-cell-actions-cell {
            width: 100%;
        }

        .room-management-table tr.is-editing td:nth-child(1),
        .room-management-table tr.is-editing td:nth-child(2) {
            display: none;
        }

        .room-management-table tr.is-editing td:nth-child(3) {
            width: 100%;
        }

        .room-management-table tr.is-media-open td:nth-child(1),
        .room-management-table tr.is-media-open td:nth-child(2) {
            display: none;
        }

        .room-management-table tr.is-media-open td:nth-child(3) {
            width: 100%;
        }

        .listing-summary-line strong {
            margin-right: 4px;
        }

        .listing-actions-inline {
            align-items: center;
        }

        .listing-actions-compact {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .listing-actions-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 5px;
            align-items: center;
        }

        .listing-actions-compact .btn {
            margin: 0;
            padding: 4px 10px;
            font-size: 0.78rem;
            line-height: 1.4;
            border-radius: 6px;
            white-space: nowrap;
        }

        .listing-actions-compact form {
            margin: 0;
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
        .update-row-form[data-property-edit-category="water_sports"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="water_sports"] [data-property-edit-scope="service"],
        .update-row-form[data-property-edit-category="water_sports"] [data-property-edit-scope="excursion"],
        .update-row-form[data-property-edit-category="water_sports"] [data-property-edit-scope="geo"],
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

        /* Conference room scopes */
        .update-row-form[data-property-edit-category="conference_room"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="conference_room"] [data-property-edit-scope="conference"],
        .update-row-form[data-property-edit-category="conference_room"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="conference_room"] [data-property-edit-scope="geo"] {
            display: revert;
        }

        /* Policies scope: cancellation policy shown per bookable category */
        .update-row-form[data-property-edit-category="accommodation"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="transport"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="excursion"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="water_sports"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="resort_day_visit"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="restaurant"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="vehicle_rental"] [data-property-edit-scope="policies"] {
            display: revert;
        }

        .btn-danger {
            background: #a33535;
            color: #fff;
        }

        .gallery-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
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
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            display: grid;
            grid-template-rows: auto 1fr;
            box-shadow: 0 6px 18px rgba(17, 43, 68, 0.08);
        }

        .gallery-card img {
            width: 100%;
            aspect-ratio: 4 / 3;
            height: auto;
            object-fit: cover;
            display: block;
            background: #edf2f7;
        }

        .gallery-card-body {
            padding: 8px;
            display: grid;
            gap: 8px;
        }

        .gallery-card-title {
            margin: 0;
            font-size: 0.76rem;
            color: #35506a;
            line-height: 1.35;
        }

        .gallery-card-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 6px;
        }

        .gallery-card-actions form {
            margin: 0;
        }

        .gallery-edit-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 6px;
            width: 100%;
        }

        .gallery-edit-form input {
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 0.75rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1d3045;
            background: #fff;
        }

        .gallery-delete-form .btn-danger {
            width: auto;
            margin-top: 0;
            margin-left: 0;
        }

        .listing-publish-hint {
            margin: 6px 0 0;
            font-size: 0.76rem;
            color: #486276;
        }

        .publish-readiness-box {
            margin-top: 12px;
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            padding: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            display: grid;
            gap: 10px;
        }

        .publish-readiness-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .publish-readiness-list {
            margin: 0;
            padding-left: 18px;
            color: #3d5568;
            font-size: 0.8rem;
            display: grid;
            gap: 4px;
        }

        .publish-readiness-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
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
        }

        .btn-secondary {
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
            .hero-actions {
                align-items: flex-start;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .progress-grid {
                grid-template-columns: 1fr 1fr;
            }

            .hero-highlights,
            .reports-grid {
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

            .media-upload-row {
                padding: 10px;
            }

            .media-upload-row .inline-table-form {
                grid-template-columns: 1fr;
            }

            .gallery-edit-form {
                grid-template-columns: 1fr;
            }

            .gallery-card-actions,
            .publish-readiness-actions {
                justify-content: stretch;
            }

            .gallery-card-actions form,
            .gallery-edit-form .btn,
            .publish-readiness-actions form,
            .publish-readiness-actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .ops-metrics {
                grid-template-columns: 1fr 1fr;
            }

            .progress-grid {
                grid-template-columns: 1fr;
            }

            .hero-highlights,
            .reports-grid {
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

            .listing-management-table .inline-actions,
            .room-management-table .inline-actions,
            .listing-management-table .inline-status-form,
            .room-management-table .inline-status-form,
            .gallery-card-actions,
            .publish-readiness-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .listing-management-table .inline-actions .btn,
            .listing-management-table .inline-actions form,
            .room-management-table .inline-actions .btn,
            .room-management-table .inline-actions form,
            .gallery-card-actions form,
            .gallery-card-actions .btn,
            .media-upload-row .btn,
            .publish-readiness-actions form,
            .publish-readiness-actions .btn {
                width: 100%;
            }

            .media-dropzone {
                padding: 14px 10px;
                font-size: 0.76rem;
            }

            .media-upload-preview,
            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .publish-readiness-box {
                padding: 10px;
            }
        }
    </style>
    @include('partials.uniform-buttons')
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
        $vendorCanManageListings = (bool) ($vendorCanManageListings ?? false);
        $vendorTaxComponents = $vendorTaxComponents ?? collect();
        $vendorEngagement = is_array($vendorEngagement ?? null) ? $vendorEngagement : [];
        $engagementInquiriesTable = (string) ($vendorEngagement['inquiries_table'] ?? '');
        $engagementInquiries = collect($vendorEngagement['inquiries'] ?? []);
        $engagementReviewsTable = (string) ($vendorEngagement['reviews_table'] ?? '');
        $engagementReviews = collect($vendorEngagement['reviews'] ?? []);
        $engagementPromotions = collect($vendorEngagement['promotions'] ?? []);
        $engagementLoyaltyTable = (string) ($vendorEngagement['loyalty_table'] ?? '');
        $engagementLoyaltyPrograms = collect($vendorEngagement['loyalty_programs'] ?? []);
        $engagementLoyalCustomers = collect($vendorEngagement['loyal_customers'] ?? []);
        $categorySet = collect($selectedVendorCategories)->flip();
        $supportsAccommodation = $categorySet->has('accommodation');
        $hasSelectedCategories = count($selectedVendorCategories) > 0;
        $listingWizardStep = (int) session('listing_wizard_step', 1);
        $listingWizardStep = max(1, min(4, $listingWizardStep));
        $portalPageQuery = strtolower(trim((string) request()->query('page', '')));
        $activePortalPage = in_array($portalPageQuery, ['overview', 'reports', 'profile', 'listings', 'reservations', 'operations', 'availability', 'pricing', 'billing', 'engagement', 'promotions'], true)
            ? $portalPageQuery
            : 'overview';
        $panelFromPageQuery = match ($activePortalPage) {
            'profile' => 'profile',
            'listings' => 'listings',
            'reservations', 'operations', 'availability', 'pricing' => 'reservations',
            'billing' => 'billing',
            'engagement', 'promotions' => 'engagement',
            'reports', 'overview' => 'overview',
            default => '',
        };
        $showProfilePage = $activePortalPage === 'profile';
        $showListingsPage = $activePortalPage === 'listings';
        $showReservationsPage = in_array($activePortalPage, ['reservations', 'operations', 'availability'], true);
        $showPricingPage = $activePortalPage === 'pricing';
        $showBillingPage = $activePortalPage === 'billing';
        $showEngagementPage = in_array($activePortalPage, ['engagement', 'promotions'], true);
        $showOverviewPage = in_array($activePortalPage, ['overview', 'reports'], true);
        $forcedPanelKey = (string) session('portal_active_panel', $panelFromPageQuery);
        $forcedListingMode = strtolower(trim((string) session('portal_listing_mode', '')));
        $forcedListingCategory = strtolower(trim((string) session('portal_listing_category', '')));
        $forcedMediaPanelType = strtolower(trim((string) session('portal_media_panel_type', '')));
        $forcedMediaPanelId = (int) session('portal_media_panel_id', 0);
        $propertyMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'property';
        });
        $roomMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'room';
        });
        $listingCategoryViewOrder = ['accommodation', 'marine_transport', 'land_transport', 'water_sports', 'excursion', 'remote_workspace', 'conference_room', 'resort_day_visit', 'restaurant', 'vehicle_rental'];
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, [
            'marine_transport' => 'Marine Transport',
            'land_transport' => 'Land Transport',
            'conference_room' => 'Conference Rooms',
        ]);
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
            $rawCategory = strtolower(trim((string) ($property->listing_category ?? '')));
            if ($rawCategory !== 'transport') {
                return $rawCategory;
            }

            $details = [];
            if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                $decoded = json_decode((string) $property->listing_details, true);
                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $transportMode = strtolower(trim((string) ($details['transport_mode'] ?? '')));
            return preg_match('/(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/', $transportMode)
                ? 'marine_transport'
                : 'land_transport';
        });
        $propertyLookupById = $vendorProperties->keyBy('id');
        $roomLookupById = $vendorRoomCategories->keyBy('id');
        $showCreatePropertyForm = old('property_form_intent') === '1' || $forcedListingMode === 'create';
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
        $vendorListingCount = (int) ($vendorProperties->count() + $vendorServices->count());
        $vendorActiveListingCount = (int) ($vendorProperties->where('status', 'active')->count() + $vendorServices->where('status', 'active')->count());
        $vendorPendingReservationsCount = (int) $vendorReservations->filter(fn ($reservation) => strtolower(trim((string) ($reservation->status ?? ''))) === 'pending')->count();
        $vendorConfirmedReservationsCount = (int) $vendorReservations->filter(fn ($reservation) => in_array(strtolower(trim((string) ($reservation->status ?? ''))), ['confirmed', 'upcoming'], true))->count();
        $vendorCompletedReservationsCount = (int) $vendorReservations->filter(fn ($reservation) => strtolower(trim((string) ($reservation->status ?? ''))) === 'completed')->count();
        $vendorAverageBookingValue = $vendorReservations->count() > 0 ? round($grossCollectionsTotal / max(1, $vendorReservations->count()), 2) : 0.0;
        $vendorUnresolvedCareCount = (int) $engagementInquiries->whereNotIn('status', ['resolved', 'closed', 'replied'])->count();
        $vendorPendingReviewResponses = (int) $engagementReviews->filter(fn ($row) => trim((string) ($row['response'] ?? '')) === '')->count();
        $vendorRefundCases = $vendorReservations->filter(function ($reservation) {
            $status = strtolower(trim((string) ($reservation->status ?? '')));
            $paymentStatus = strtolower(trim((string) ($reservation->payment_status ?? '')));
            return in_array($status, ['cancelled', 'canceled', 'refunded'], true) || $paymentStatus === 'refunded';
        });
        $vendorRefundCaseCount = (int) $vendorRefundCases->count();
        $vendorRefundExposureTotal = (float) $vendorRefundCases->sum(fn ($reservation) => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0));
        $vendorGoLiveProgress = $vendorListingCount > 0
            ? min(100, (int) round((($vendorActiveListingCount > 0 ? 35 : 0) + ($vendorPricingRules->count() > 0 ? 20 : 0) + ($vendorAvailability->count() > 0 ? 20 : 0) + ($vendorBilling ? 25 : 0))))
            : 0;
    @endphp
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <div class="hero-top">
                <div class="hero-head">
                    <span class="eyebrow">Vendor Workspace</span>
                    <h1>My Listings</h1>
                    <p>Manage listings, reservations, availability, pricing, reports, payouts, refunds, and customer care from one vendor dashboard.</p>
                    <div class="hero-links">
                        <a class="hero-link" href="/">Back to Home</a>
                        <a class="hero-link" href="/admin">Go to Admin Portal</a>
                        <a class="hero-link" href="#vendorPropertiesSection">Open My Listings</a>
                        <a class="hero-link" href="#vendorDailyCollectionSection">Open Billing &amp; Refunds</a>
                    </div>
                </div>
                <div class="hero-actions">
                    <div class="auth-bar">
                        <span class="auth-user">Signed in as {{ $portalUser }}</span>
                        <form method="POST" action="/portal/vendor/logout">
                            @csrf
                            <button class="logout" type="submit">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="hero-highlights" aria-label="Vendor dashboard highlights">
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Live Listings</p>
                    <p class="hero-highlight-value">{{ $vendorActiveListingCount }} / {{ $vendorListingCount }}</p>
                    <p class="hero-highlight-meta">Active listings ready for reservations</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Reservations in Flow</p>
                    <p class="hero-highlight-value">{{ $vendorPendingReservationsCount + $vendorConfirmedReservationsCount }}</p>
                    <p class="hero-highlight-meta">Pending and confirmed guest reservations</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Gross Earnings</p>
                    <p class="hero-highlight-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    <p class="hero-highlight-meta">Revenue tracked across current vendor bookings</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Go-Live Progress</p>
                    <p class="hero-highlight-value">{{ $vendorGoLiveProgress }}%</p>
                    <p class="hero-highlight-meta">Listings, pricing, availability, and billing readiness</p>
                </article>
            </div>
        </section>

        @if ($showOverviewPage)
        <section class="card" data-panel-group="overview" aria-label="Vendor operating scope" style="margin-top:10px;">
            <p class="label">Vendor Action Center</p>
            <p class="small" style="margin-top:0;">Use this home page as the working dashboard for listing growth, reservation handling, payout tracking, and customer care follow-up.</p>
            <div class="ops-metrics" style="margin-top:10px;">
                <article class="ops-metric">
                    <p class="metric-label">My Listings</p>
                    <p class="metric-value">Create / Edit / Archive</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Reservations</p>
                    <p class="metric-value">Manage / Confirm / Update</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Availability</p>
                    <p class="metric-value">Category-wise calendar</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Pricing</p>
                    <p class="metric-value">Rates / tariffs / offers</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Customer Care</p>
                    <p class="metric-value">Complaints / reviews / replies</p>
                </article>
            </div>
        </section>
        @endif

        <div class="portal-shell">
        @include('vendor-portal.partials.sidebar')

        <div class="portal-content">

        @if ($showOverviewPage)
            @include('vendor-portal.partials.overview')
        @endif

        @if (session('portal_notice'))
            <div class="notice" role="status" aria-live="polite">{{ session('portal_notice') }}</div>
        @endif

        @if ($errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first('profile') }}</div>
        @endif

        @if ($errors->any() && !$errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if ($showProfilePage)
            @include('vendor-portal.partials.profile')
        @endif

        @if ($showBillingPage || $showProfilePage)
            @include('vendor-portal.partials.billing-settings')
        @endif

        @if ($showListingsPage)
                @include('vendor-portal.partials.listings-console')
        @endif

        @if ($showReservationsPage)
            @include('vendor-portal.partials.category-operations')
        @endif

        @if ($showPricingPage)
            @include('vendor-portal.partials.pricing')
        @endif

        @if ($showBillingPage)
            @include('vendor-portal.partials.billing-collection')
        @endif

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
                    <code>GET /api/v1/bookings (customer reservations)</code>
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


        @if ($showEngagementPage)
            @include('vendor-portal.partials.engagement')
        @endif
        @include('partials.global-site-footer')
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
            const vendorNavGroupToggles = Array.from(document.querySelectorAll('[data-vendor-nav-toggle]'));
            const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
            const listingStepPanels = Array.from(document.querySelectorAll('[data-listing-step]'));
            const categoryOpsCards = Array.from(document.querySelectorAll('[data-ops-category-section]'));
            const validPanelKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));
            const forcedListingMode = "{{ $forcedListingMode }}";
            const forcedListingCategory = "{{ $forcedListingCategory }}";
            const locationCountry = document.getElementById("location_country");
            const locationState = document.getElementById("location_state");
            const locationCity = document.getElementById("location_city");
            const mapLatitude = document.getElementById("map_latitude");
            const mapLongitude = document.getElementById("map_longitude");
            const mapPlaceId = document.getElementById("map_place_id");
            const billingCountry = document.getElementById("billing_country");
            const billingState = document.getElementById("billing_state");
            const billingCity = document.getElementById("billing_city");
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
            const serverPanelKey = "{{ in_array($forcedPanelKey, ['overview', 'profile', 'listings', 'billing', 'reservations', 'engagement', 'api'], true) ? $forcedPanelKey : '' }}";
            const forcedMediaPanelType = "{{ in_array($forcedMediaPanelType, ['property', 'room'], true) ? $forcedMediaPanelType : '' }}";
            const forcedMediaPanelId = Number("{{ $forcedMediaPanelId }}") || 0;
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
                const candidates = navLinks.filter((link) => {
                    return (link.dataset.panelKey || "") === panelKey;
                });

                if (candidates.length === 0) {
                    setExactActiveNavLink(null);
                    return;
                }

                const currentUrl = new URL(window.location.href);
                const currentPath = currentUrl.pathname.replace(/\/+$/, "") || "/";
                const currentSearch = currentUrl.search;
                let bestLink = candidates[0];
                let bestScore = -1;

                candidates.forEach((link) => {
                    let score = 0;
                    let linkUrl = null;
                    try {
                        linkUrl = new URL(String(link.getAttribute("href") || ""), window.location.origin);
                    } catch (error) {
                        linkUrl = null;
                    }

                    if (linkUrl) {
                        const linkPath = linkUrl.pathname.replace(/\/+$/, "") || "/";
                        const linkSearch = linkUrl.search;
                        if (linkPath === currentPath) {
                            score += 3;
                        }
                        if (linkSearch === currentSearch) {
                            score += 2;
                        }

                        const linkPage = linkUrl.searchParams.get("page");
                        const currentPage = currentUrl.searchParams.get("page");
                        if (linkPage && currentPage && linkPage === currentPage) {
                            score += 1;
                        }
                    }

                    if (score > bestScore) {
                        bestScore = score;
                        bestLink = link;
                    }
                });

                setExactActiveNavLink(bestLink);
            }

            function setExactActiveNavLink(activeLink) {
                navLinks.forEach((link) => {
                    link.classList.toggle("is-active", !!activeLink && link === activeLink);
                });
            }

            function showPanelGroup(panelKey) {
                const hasMatchingPanel = panelGroups.some((panel) => {
                    return (panel.getAttribute("data-panel-group") || "") === panelKey;
                });
                if (!hasMatchingPanel) {
                    setActiveNavLink(panelKey);
                    return;
                }

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

            function normalizeVendorOpsCategoryKey(categoryKey) {
                const normalized = normalizeCategoryKey(categoryKey || '');
                if (normalized === 'transport') {
                    return 'marine_transport';
                }
                return normalized;
            }

            function applyVendorCategoryOperationsFilter(categoryKey) {
                const normalized = normalizeVendorOpsCategoryKey(categoryKey || 'all');
                if (categoryOpsCards.length === 0) {
                    return;
                }

                categoryOpsCards.forEach((card) => {
                    const sectionKey = String(card.getAttribute('data-ops-category-section') || '');
                    const cardCategory = normalizeVendorOpsCategoryKey(sectionKey.replace('category-operations-', ''));
                    card.hidden = normalized !== 'all' && cardCategory !== normalized;
                });

                if (normalized === 'all') {
                    return;
                }

                const activeCard = categoryOpsCards.find((card) => {
                    const sectionKey = String(card.getAttribute('data-ops-category-section') || '');
                    return normalizeVendorOpsCategoryKey(sectionKey.replace('category-operations-', '')) === normalized;
                });

                if (!activeCard) {
                    return;
                }

                const toggle = activeCard.querySelector('[data-ops-category-toggle]');
                if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
                    toggle.click();
                }
                activeCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

                    // Source Maldives Atoll/Island data from shared API so vendor listings stay in sync
                    // with the same atlas used by blog and customer-facing forms.
                    fetch('/api/atoll-island/atolls', { cache: 'no-store' })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Atoll list request failed with status ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (atolls) {
                            if (!Array.isArray(atolls) || atolls.length === 0) {
                                throw new Error('No atolls returned from API');
                            }

                            const atollRequests = atolls.map(function (atoll) {
                                const atollId = Number(atoll && atoll.id ? atoll.id : 0);
                                const atollName = String(atoll && atoll.name ? atoll.name : '').trim();
                                if (atollId <= 0 || atollName === '') {
                                    return Promise.resolve(null);
                                }

                                return fetch('/api/atoll-island/atolls/' + atollId + '/islands', { cache: 'no-store' })
                                    .then(function (islandsResponse) {
                                        if (!islandsResponse.ok) {
                                            return [];
                                        }
                                        return islandsResponse.json();
                                    })
                                    .then(function (islands) {
                                        const islandNames = Array.isArray(islands)
                                            ? islands
                                                .map(function (island) {
                                                    return String(island && island.name ? island.name : '').trim();
                                                })
                                                .filter(function (name) { return name !== ''; })
                                            : [];

                                        return {
                                            atollName: atollName,
                                            islandNames: islandNames,
                                        };
                                    })
                                    .catch(function () {
                                        return {
                                            atollName: atollName,
                                            islandNames: [],
                                        };
                                    });
                            });

                            return Promise.all(atollRequests);
                        })
                        .then(function (atollIslandRows) {
                            const maldivesTree = {};
                            (atollIslandRows || []).forEach(function (row) {
                                if (!row || !row.atollName) {
                                    return;
                                }
                                maldivesTree[row.atollName] = Array.isArray(row.islandNames) ? row.islandNames : [];
                            });

                            if (Object.keys(maldivesTree).length === 0) {
                                resolve(getCurrentLocationTree());
                                return;
                            }

                            const mergedTree = {
                                ...FALLBACK_LOCATION_TREE,
                                Maldives: maldivesTree,
                            };
                            resolve(applyLocationTree(mergedTree));
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
                    return ["stay", "accommodation", "policies", "geo"];
                }

                if (normalized === "transport" || normalized === "marine_transport" || normalized === "land_transport") {
                    return ["capacity", "transport", "policies", "geo"];
                }

                if (normalized === "excursion") {
                    return ["capacity", "service", "excursion", "policies", "geo"];
                }

                if (normalized === "water_sports") {
                    return ["capacity", "service", "excursion", "policies", "geo"];
                }

                if (normalized === "remote_workspace") {
                    return ["stay", "capacity", "workspace", "geo"];
                }

                if (normalized === "conference_room") {
                    return ["capacity", "conference", "policies", "geo"];
                }

                if (normalized === "resort_day_visit") {
                    return ["capacity", "day_visit", "policies", "geo"];
                }

                if (normalized === "restaurant") {
                    return ["capacity", "restaurant", "policies", "geo"];
                }

                if (normalized === "vehicle_rental") {
                    return ["vehicle", "capacity", "rental", "policies", "geo"];
                }

                return ["stay", "accommodation", "capacity", "service", "vehicle", "transport", "excursion", "workspace", "day_visit", "restaurant", "rental", "conference", "policies", "geo"];
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
                const isTransportCategory = normalizedCategory === "transport" || normalizedCategory === "marine_transport" || normalizedCategory === "land_transport";
                const isRemoteWorkspaceCategory = normalizedCategory === "remote_workspace";
                const isMarine = normalizedCategory === "marine_transport"
                    || (normalizedCategory !== "land_transport" && isMarineTransportMode(transportModeInput ? transportModeInput.value : ""));
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

            function applyCategorySectionFilter(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || 'all');
                if (categoryViewPanels.length === 0) {
                    return;
                }

                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view') || '');
                    panel.hidden = normalizedCategory !== 'all' && panelCategory !== normalizedCategory;
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
                        title: 'Marine or Land Transport Enlisting',
                        subtitle: 'Choose the transport mode and save the listing.',
                        submit: 'Save Transport Listing',
                        note: 'Use marine mode for boats and ferries, or land mode for cars and vans.',
                        propertyType: 'service',
                    },
                    marine_transport: {
                        title: 'Marine Transport Enlisting',
                        subtitle: 'Capture water transfer details and save.',
                        submit: 'Save Marine Transport Listing',
                        note: 'Use marine transport fields for speedboats, ferries, and vessel transfers.',
                        propertyType: 'service',
                    },
                    land_transport: {
                        title: 'Land Transport Enlisting',
                        subtitle: 'Capture vehicle transfer details and save.',
                        submit: 'Save Land Transport Listing',
                        note: 'Use land transport fields for cars, vans, and local ground transfers.',
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
                    conference_room: {
                        title: 'Conference Room Enlisting',
                        subtitle: 'Capture venue basics, capacity, and save.',
                        submit: 'Save Conference Room Listing',
                        note: 'Use this for meeting rooms, halls, and event spaces.',
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
                    let matched = Array.from(propertyCategorySelect.options)
                        .find((option) => normalizeCategoryKey(option.value) === preferred);
                    if (!matched && (preferred === 'marine_transport' || preferred === 'land_transport')) {
                        matched = Array.from(propertyCategorySelect.options)
                            .find((option) => normalizeCategoryKey(option.value) === 'transport');
                    }
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
                    if (transportModeInput && (normalizedCategory === 'marine_transport' || normalizedCategory === 'land_transport')) {
                        transportModeInput.value = normalizedCategory === 'marine_transport' ? 'speedboat' : 'car';
                    }
                    propertyCategorySelect.dispatchEvent(new Event('change'));
                    applyCategoryFormMeta(selectedCategory, true);
                }
                if (document.getElementById('property_name')) {
                    document.getElementById('property_name').focus();
                }

                refreshLocationMapViewport();

                applyPropertyCategoryFilter(normalizedCategory || 'all');
                applyCategorySectionFilter(normalizedCategory || 'all');
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
                let invalidCount = 0;

                requiredFields.forEach((field) => {
                    const valid = applyFieldValidationState(field);
                    if (!valid && !firstInvalid) {
                        firstInvalid = field;
                    }
                    if (!valid) {
                        allValid = false;
                        invalidCount++;
                    }
                });

                const errorBanner = document.getElementById('propertyCreateFormError');
                if (errorBanner) {
                    if (!allValid) {
                        const noun = invalidCount === 1 ? 'field' : 'fields';
                        errorBanner.textContent = invalidCount + ' required ' + noun + ' must be completed before saving.';
                        errorBanner.hidden = false;
                    } else {
                        errorBanner.hidden = true;
                    }
                }

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
                    const href = String(link.getAttribute("href") || "").trim();
                    const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                    if (!panelKey) return;

                    const categoryTarget = normalizeVendorOpsCategoryKey(String(link.getAttribute('data-vendor-category-target') || ''));
                    const listingAction = String(link.getAttribute('data-vendor-listing-action') || '').trim().toLowerCase();

                    if (href !== "" && !href.startsWith("#")) {
                        return;
                    }

                    event.preventDefault();

                    window.location.hash = panelKey;
                    showPanelGroup(panelKey);

                    if (panelKey === 'listings') {
                        if (categoryTarget !== '') {
                            openPropertyFlowWithCategory(categoryTarget);
                        } else if (listingAction === 'create') {
                            const defaultCreateCategory = forcedListingCategory || normalizeVendorOpsCategoryKey((categoryViewPanels[0] && categoryViewPanels[0].getAttribute('data-category-view')) || 'accommodation');
                            openPropertyFlowWithCategory(defaultCreateCategory);
                        } else {
                            applyPropertyCategoryFilter('all');
                            applyCategorySectionFilter('all');
                        }
                    }

                    if (panelKey === 'reservations') {
                        applyVendorCategoryOperationsFilter(categoryTarget !== '' ? categoryTarget : 'all');
                    }

                    setExactActiveNavLink(link);
                });
            });

            vendorNavGroupToggles.forEach((toggle) => {
                toggle.addEventListener('click', function () {
                    const groupKey = String(toggle.getAttribute('data-vendor-nav-toggle') || '').trim();
                    if (groupKey === '') {
                        return;
                    }
                    const body = document.querySelector('[data-vendor-nav-group="' + groupKey + '"]');
                    if (!body) {
                        return;
                    }
                    const isOpen = body.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });

            window.addEventListener("hashchange", function () {
                showPanelGroup(resolvePanelFromHash(window.location.hash));
            });

            applyVendorCategoryOperationsFilter('all');

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
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.toggle('is-media-open', !panel.hidden);
                    }
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
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.remove('is-media-open');
                        }
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
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.toggle('is-media-open', !panel.hidden);
                    }
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
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.remove('is-media-open');
                        }
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
            applyCategorySectionFilter('all');

            if (serverPanelKey === 'listings') {
                if (forcedListingMode === 'create') {
                    openPropertyFlowWithCategory(forcedListingCategory || 'accommodation');
                } else if (forcedListingMode === 'manage') {
                    applyPropertyCategoryFilter(forcedListingCategory || 'all');
                    applyCategorySectionFilter(forcedListingCategory || 'all');
                } else if (forcedListingCategory !== '') {
                    applyPropertyCategoryFilter(forcedListingCategory);
                    applyCategorySectionFilter(forcedListingCategory);
                }
            }

            if (forcedMediaPanelId > 0 && (forcedMediaPanelType === 'property' || forcedMediaPanelType === 'room')) {
                const panelSelector = forcedMediaPanelType === 'property'
                    ? '[data-property-media-panel="' + String(forcedMediaPanelId) + '"]'
                    : '[data-room-media-panel="' + String(forcedMediaPanelId) + '"]';
                const panel = document.querySelector(panelSelector);
                if (panel) {
                    panel.hidden = false;
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.add('is-media-open');
                    }
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
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
                    const hasMatchingPanel = panelGroups.some((panel) => {
                        return (panel.getAttribute('data-panel-group') || '') === panelKey;
                    });
                    if (!hasMatchingPanel) {
                        return;
                    }

                    panelGroups.forEach((panel) => {
                        panel.hidden = (panel.getAttribute('data-panel-group') || '') !== panelKey;
                    });
                    // Nav link highlighting is handled exclusively by the primary script's setActiveNavLink
                }

                navLinks.forEach((link) => {
                    link.addEventListener('click', function (event) {
                        const href = String(link.getAttribute('href') || '').trim();
                        if (href !== '' && !href.startsWith('#')) {
                            return;
                        }
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

                const fallbackInitialKey =
                    (typeof serverPanelKey !== 'undefined' && serverPanelKey && validKeys.has(serverPanelKey))
                        ? serverPanelKey
                        : resolvePanelKey(window.location.hash);
                showPanel(fallbackInitialKey);
            }

            function initFallbackListingActions() {
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
                    if (normalized === 'accommodation') return ['stay', 'accommodation', 'policies', 'geo'];
                    if (normalized === 'transport' || normalized === 'marine_transport' || normalized === 'land_transport') return ['capacity', 'transport', 'policies', 'geo'];
                    if (normalized === 'excursion') return ['capacity', 'service', 'excursion', 'policies', 'geo'];
                    if (normalized === 'water_sports') return ['capacity', 'service', 'excursion', 'policies', 'geo'];
                    if (normalized === 'remote_workspace') return ['stay', 'capacity', 'workspace', 'geo'];
                    if (normalized === 'conference_room') return ['capacity', 'conference', 'policies', 'geo'];
                    if (normalized === 'resort_day_visit') return ['capacity', 'day_visit', 'policies', 'geo'];
                    if (normalized === 'restaurant') return ['capacity', 'restaurant', 'policies', 'geo'];
                    if (normalized === 'vehicle_rental') return ['vehicle', 'capacity', 'rental', 'policies', 'geo'];
                    return ['stay', 'accommodation', 'capacity', 'service', 'vehicle', 'transport', 'excursion', 'workspace', 'day_visit', 'restaurant', 'rental', 'conference', 'policies', 'geo'];
                }

                function categoryMetaFor(category) {
                    const normalized = normalizeCategoryKey(category);
                    const metaMap = {
                        accommodation: ['Accommodation Enlisting', 'Fill required fields and save.', 'Save Accommodation Listing', 'Fill required fields and save.', 'property'],
                        transport: ['Marine or Land Transport Enlisting', 'Choose the transport mode and save the listing.', 'Save Transport Listing', 'Use marine mode for boats and ferries, or land mode for cars and vans.', 'service'],
                        marine_transport: ['Marine Transport Enlisting', 'Capture water transfer details and save.', 'Save Marine Transport Listing', 'Use marine transport fields for speedboats, ferries, and vessel transfers.', 'service'],
                        land_transport: ['Land Transport Enlisting', 'Capture vehicle transfer details and save.', 'Save Land Transport Listing', 'Use land transport fields for cars, vans, and local ground transfers.', 'service'],
                        water_sports: ['Water Sports Enlisting', 'Fill required fields and save.', 'Save Water Sports Listing', 'Use excursion/service fields for diving, snorkeling, and activity packages.', 'service'],
                        excursion: ['Excursion Enlisting', 'Fill required fields and save.', 'Save Excursion Listing', 'Fill required fields and save.', 'service'],
                        remote_workspace: ['Remote Workspace Enlisting', 'Fill required fields and save.', 'Save Remote Workspace Listing', 'Fill required fields and save.', 'service'],
                        conference_room: ['Conference Room Enlisting', 'Capture venue basics, capacity, and save.', 'Save Conference Room Listing', 'Use this for meeting rooms, halls, and event spaces.', 'service'],
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
                        let matched = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === preferred);
                        if (!matched && (preferred === 'marine_transport' || preferred === 'land_transport')) {
                            matched = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === 'transport');
                        }
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

                function applyCategorySectionFilter(categoryKey) {
                    const normalized = normalizeCategoryKey(categoryKey || 'all');
                    categoryViewPanels.forEach((panel) => {
                        const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view') || '');
                        panel.hidden = normalized !== 'all' && panelCategory !== normalized;
                    });
                }

                function refreshTransportFieldLabels() {
                    if (!propertyCategorySelect) {
                        return;
                    }

                    const normalizedCategory = normalizeCategoryKey(propertyCategorySelect.value);
                    const isTransportCategory = normalizedCategory === 'transport' || normalizedCategory === 'marine_transport' || normalizedCategory === 'land_transport';
                    const isRemoteWorkspaceCategory = normalizedCategory === 'remote_workspace';
                    const isMarine = normalizedCategory === 'marine_transport'
                        || (normalizedCategory !== 'land_transport' && isMarineTransportMode(transportModeInput ? transportModeInput.value : ''));
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

                document.querySelectorAll('[data-listing-category-shortcut]').forEach((button) => {
                    button.addEventListener('click', function () {
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
                        if (propertyCreateForm) {
                            propertyCreateForm.hidden = false;
                            propertyCreateForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
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
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.toggle('is-media-open', !panel.hidden);
                        }
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
                        if (panel) {
                            panel.hidden = true;
                            const row = panel.closest('tr');
                            if (row) {
                                row.classList.remove('is-media-open');
                            }
                        }
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
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.toggle('is-media-open', !panel.hidden);
                        }
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
                        if (panel) {
                            panel.hidden = true;
                            const row = panel.closest('tr');
                            if (row) {
                                row.classList.remove('is-media-open');
                            }
                        }
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
