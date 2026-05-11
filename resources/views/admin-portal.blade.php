<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --bg: #edf4f2;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #183d64;
            --hero-2: #116b86;
            --hero-3: #1a9a7f;
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
                radial-gradient(circle at 8% 10%, #d4ebff 0, #d4ebff00 32%),
                radial-gradient(circle at 90% 10%, #dff5e8 0, #dff5e800 35%),
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

        .portal-shell {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            min-height: calc(100vh - 86px);
        }

        .portal-content {
            min-width: 0;
            width: 100%;
        }

        .portal-content > section,
        .portal-content > div,
        .portal-content > button,
        .portal-content > footer {
            width: 100%;
        }

        .hero-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .auth-bar {
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

        .portal-nav {
            position: sticky;
            top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            max-height: calc(100vh - 16px);
            overflow-y: auto;
        }

        .admin-nav-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 4px 10px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e5edf3;
        }

        .admin-nav-avatar {
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

        .admin-nav-user-meta {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .admin-nav-user-name {
            margin: 0;
            font-size: 0.84rem;
            font-weight: 700;
            color: #163042;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-nav-user-role {
            margin: 0;
            font-size: 0.72rem;
            color: #6f8598;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-group-title {
            margin: 8px 4px 2px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6f8598;
        }

        .portal-nav a {
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

        .portal-nav a:hover {
            border-color: #cddce8;
            background: #eef7fd;
            color: #124967;
        }

        .portal-nav a.prominent {
            background: #ebf4fb;
            color: #0a4a65;
            border-color: #bcd8ea;
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

        .permissions-section {
            margin-top: 12px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .permissions-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .permission-card {
            border: 1px solid #d7dee6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .permission-card.current {
            border-color: #3d88b0;
            box-shadow: inset 0 0 0 1px #9bc6dd;
            background: #f2f9fd;
        }

        .permission-title {
            margin: 0 0 5px;
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #20415f;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .permission-summary {
            margin: 0 0 7px;
            font-size: 0.82rem;
            color: #2c4055;
            line-height: 1.35;
        }

        .permission-list {
            margin: 0;
            padding-left: 16px;
            font-size: 0.8rem;
            color: #30485f;
            line-height: 1.35;
        }

        .widget-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .widget-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .widget-title {
            margin: 0 0 6px;
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            color: var(--muted);
            text-transform: uppercase;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .widget-value {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: #173754;
        }

        .widget-sub {
            margin-top: 5px;
            font-size: 0.78rem;
            color: var(--muted);
        }

        .alert-list {
            margin: 0;
            padding-left: 18px;
            color: #5f3a06;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .health-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 7px;
        }

        .health-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.82rem;
            color: #203345;
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 12px;
        }

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

        .manage {
            margin-top: 14px;
        }

        .notice {
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid #b7e2c3;
            background: #eaf9ef;
            color: #135028;
            padding: 10px 12px;
            font-size: 0.88rem;
        }

        .error-box {
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid #f0b7b3;
            background: #fff0ef;
            color: #731e1a;
            padding: 10px 12px;
            font-size: 0.88rem;
        }

        .role-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid #c8d4df;
            background: #f2f7fb;
            color: #1b3856;
        }

        .user-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 18px;
        }

        .group-title {
            margin: 14px 0 8px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .group-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }

        .group-search {
            flex: 1 1 280px;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
        }

        .group-select-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .bulk-delete-btn {
            border: 0;
            border-radius: 8px;
            background: #8a1f1f;
            color: #fff;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .bulk-delete-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .user-select {
            width: 16px;
            height: 16px;
            margin: 0 2px 0 0;
        }

        .user-row {
            /* Remove box-shadow and margin-bottom to prevent nesting */
            border: 1px solid #d7dee6;
            border-radius: 10px;
            padding: 14px 12px;
            background: #fff;
            margin-bottom: 0;
            box-shadow: none;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .user-row:not(:last-child) {
            margin-bottom: 0;
        }

        .user-head {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .user-name {
            font-weight: 700;
        }

        .small {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .token-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.8rem;
            line-height: 1.35;
        }

        .manage-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            align-items: end;
        }

        .manage-form label {
            font-size: 0.75rem;
            color: var(--muted);
            display: block;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .manage-form input,
        .manage-form select {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
        }

        .manage-form button {
            border: 0;
            border-radius: 8px;
            background: #155f83;
            color: #fff;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .finance-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .finance-card {
            border: 1px solid #d7dee6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .finance-card .metric-label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .finance-card .metric-value {
            margin: 5px 0 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #173754;
        }

        .finance-layout {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .finance-form {
            border: 1px solid #d7dee6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .finance-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .finance-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .finance-field-wide {
            grid-column: 1 / -1;
        }

        .finance-field label {
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .finance-field input,
        .finance-field select,
        .finance-field textarea {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
            color: #1d3045;
        }

        .finance-field textarea {
            min-height: 86px;
            resize: vertical;
        }

        .finance-table-wrap {
            margin-top: 10px;
            border: 1px solid #d7dee6;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .finance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .finance-table th,
        .finance-table td {
            text-align: left;
            border-bottom: 1px solid #edf2f8;
            padding: 8px 9px;
            font-size: 0.8rem;
            color: #233247;
            vertical-align: top;
        }

        .finance-table th {
            background: #f8fbff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #456077;
            font-size: 0.7rem;
        }

        .finance-table tr:last-child td {
            border-bottom: 0;
        }

        .finance-empty {
            padding: 10px;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .registration-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
        }

        .registration-row {
            border: 1px solid #d7dee6;
            border-radius: 10px;
            background: #fff;
            padding: 12px;
        }

        .registration-head {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .doc-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .doc-link {
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 6px 9px;
            font-size: 0.8rem;
            background: #f5f9fc;
            color: #1e405f;
            font-weight: 700;
        }

        .registration-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }

        .registration-actions input {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.86rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
            margin-bottom: 7px;
        }

        .registration-actions textarea {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.86rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
            min-height: 74px;
            resize: vertical;
            margin-bottom: 7px;
        }

        .registration-actions button {
            border: 0;
            border-radius: 8px;
            color: #fff;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-approve {
            background: #0f6d47;
        }

        .btn-reject {
            background: #8a1f1f;
        }

        .audit-list {
            margin-top: 10px;
            border: 1px solid #d7dee6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .audit-row {
            display: grid;
            grid-template-columns: 170px 170px 1fr;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #edf2f8;
            align-items: start;
        }

        .audit-row:last-child {
            border-bottom: 0;
        }

        .audit-when {
            font-size: 0.78rem;
            color: var(--muted);
            white-space: nowrap;
        }

        .audit-actor {
            font-size: 0.8rem;
            color: #1e293b;
            font-weight: 700;
        }

        .audit-details {
            font-size: 0.82rem;
            color: #233247;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .audit-empty {
            padding: 10px 12px;
            font-size: 0.82rem;
            color: var(--muted);
        }


        @media (max-width: 980px) {
            .manage-form {
                grid-template-columns: 1fr 1fr;
            }

            .registration-actions {
                grid-template-columns: 1fr;
            }

            .widget-grid {
                grid-template-columns: 1fr 1fr;
            }

            .audit-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }
        }

        @media (max-width: 900px) {
            .portal-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .hero-actions {
                align-items: flex-start;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .widget-grid {
                grid-template-columns: 1fr;
            }

            .finance-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .finance-layout,
            .finance-form-grid {
                grid-template-columns: 1fr;
            }

            .permissions-grid {
                grid-template-columns: 1fr;
            }

            .portal-nav {
                position: static;
                overflow-x: auto;
                overflow-y: visible;
                white-space: normal;
            }
        }
        .prominent {
            font-size: 1.05rem;
            font-weight: 700;
            box-shadow: 0 2px 12px rgba(220, 38, 38, 0.08);
            border-width: 2px;
            animation: fade-in 0.4s;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    <main class="page" data-api-base="{{ $apiBase }}">
        @php
            $adminPage = strtolower((string) ($adminPage ?? 'overview'));
        @endphp
        <section class="hero">
            <div class="hero-top">
                <div>
                    <span class="eyebrow">Internal Access</span>
                    <h1>Admin Portal</h1>
                    <p>Monitor platform health, finance moderation, vendor onboarding, audit history, and operational controls from one unified admin workspace.</p>
                    <div class="hero-links">
                        <a class="hero-link" href="/">Back to Home</a>
                        <a class="hero-link" href="/vendor">Go to Partners Portal</a>
                        <a class="hero-link" href="{{ adminPortalEntryPath('finance') }}">Open Finance</a>
                        <a class="hero-link" href="{{ adminPortalEntryPath('moderation') }}">Review Vendors</a>
                        <a class="hero-link" href="/portal/admin/blog">Manage Blog</a>
                    </div>
                </div>
                <div class="hero-actions">
                    <div class="auth-bar">
                        <span class="auth-user">Signed in as {{ $portalUser }}</span>
                        <span class="role-pill">Role: {{ $portalRole }}</span>
                        <form method="POST" action="/portal/admin/logout">
                            @csrf
                            <button class="logout" type="submit">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <div class="portal-shell">
        <nav class="portal-nav" aria-label="Admin navigation">
            <div class="admin-nav-head">
                <div class="admin-nav-avatar" aria-hidden="true">{{ strtoupper(substr((string) ($portalUser ?? 'A'), 0, 1)) }}</div>
                <div class="admin-nav-user-meta">
                    <p class="admin-nav-user-name">{{ $portalUser }}</p>
                    <p class="admin-nav-user-role">{{ $portalRole }}</p>
                </div>
            </div>

            <p class="nav-group-title">Overview</p>
            @if (in_array('overview', $adminAllowedPages))
            <a class="{{ $adminPage === 'overview' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('overview') }}">Dashboard</a>
            @endif
            @if (in_array('permissions', $adminAllowedPages))
            <a class="{{ $adminPage === 'permissions' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('permissions') }}">Role Permissions</a>
            @endif
            @if (in_array('audit', $adminAllowedPages))
            <a class="{{ $adminPage === 'audit' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('audit') }}">Audit History</a>
            @endif

            <p class="nav-group-title">Finance &amp; Catalog</p>
            @if (in_array('finance', $adminAllowedPages) && $canModerateFinance)
            <a class="{{ $adminPage === 'finance' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('finance') }}">Finance Moderation</a>
            @endif
            @if (in_array('media', $adminAllowedPages) && $canManageVendorUsers)
            <a class="{{ $adminPage === 'media' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('media') }}">Hero Image Settings</a>
            @endif
            @if (in_array('catalog', $adminAllowedPages) && $canManageVendorUsers)
            <a class="{{ $adminPage === 'catalog' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('catalog') }}">Listing Options</a>
            @endif

            @if (in_array('content', $adminAllowedPages) && (($canManageContent ?? false) === true))
            <p class="nav-group-title">Content &amp; Media</p>
            <a class="{{ $adminPage === 'content' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('content') }}">Content Hub</a>
            <a href="/portal/admin/blog">Blog Manager</a>
            <a href="/portal/admin/atlas">Island Atlas Manager</a>
            <a href="/portal/admin/newsletter">Newsletter Manager</a>
            <a href="/portal/admin/announcement">Announcement Manager</a>
            @endif

            @if (in_array('moderation', $adminAllowedPages) || in_array('listings', $adminAllowedPages))
            <p class="nav-group-title">Moderation &amp; Vendors</p>
            @endif
            @if (in_array('moderation', $adminAllowedPages))
            <a class="{{ $adminPage === 'moderation' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('moderation') }}" data-open-panel="moderationPanel" data-toggle-button="toggleModerationBtn">Moderation</a>
            @endif
            @if (in_array('listings', $adminAllowedPages) && $canModerateListings)
            <a class="{{ $adminPage === 'listings' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('listings') }}">Listing Moderation</a>
            @endif

            @if (in_array('tools', $adminAllowedPages))
            <p class="nav-group-title">Tools</p>
            <a class="{{ $adminPage === 'tools' ? 'prominent' : '' }}" href="{{ adminPortalEntryPath('tools') }}">Session + API</a>
            @endif
        </nav>

        <div class="portal-content">

        <section class="widget-grid" id="dashboardWidgets" style="{{ $adminPage === 'overview' ? '' : 'display:none;' }}">
            <article class="widget-card">
                <p class="widget-title">Portal Users</p>
                <p class="widget-value">{{ $dashboardStats['total_users'] }}</p>
                <div class="widget-sub">Admins: {{ $dashboardStats['admin_users'] }} · Vendors: {{ $dashboardStats['vendor_users'] }}</div>
            </article>
            <article class="widget-card">
                <p class="widget-title">Account Status</p>
                <p class="widget-value">{{ $dashboardStats['active_users'] }}</p>
                <div class="widget-sub">Active users · Suspended: {{ $dashboardStats['suspended_users'] }}</div>
            </article>
            <article class="widget-card">
                <p class="widget-title">Vendor Requests</p>
                <p class="widget-value">{{ $dashboardStats['pending_vendor_registrations'] }}</p>
                <div class="widget-sub">Pending registration reviews</div>
            </article>
            <article class="widget-card">
                <p class="widget-title">24h Activity</p>
                <p class="widget-value">{{ $recentAuditCount }}</p>
                <div class="widget-sub">Audit events in the last 24 hours</div>
            </article>
            <article class="widget-card">
                <p class="widget-title">System Health</p>
                <ul class="health-list">
                    <li class="health-item">
                        <span>Database</span>
                        <span class="state {{ $systemHealth['db_connected'] ? 'ok' : 'err' }}">{{ $systemHealth['db_connected'] ? 'OK' : 'DOWN' }}</span>
                    </li>
                    <li class="health-item">
                        <span>Audit Table</span>
                        <span class="state {{ $systemHealth['audit_table_ready'] ? 'ok' : 'warn' }}">{{ $systemHealth['audit_table_ready'] ? 'READY' : 'MISSING' }}</span>
                    </li>
                    <li class="health-item">
                        <span>Manage Access</span>
                        <span class="state {{ $systemHealth['manage_permission'] ? 'ok' : 'warn' }}">{{ $systemHealth['manage_permission'] ? 'GRANTED' : 'LIMITED' }}</span>
                    </li>
                </ul>
            </article>
        </section>

        @if ($alerts->isNotEmpty() && $adminPage === 'overview')
            <section class="card" style="margin-top:12px;">
                <p class="label">Operational Alerts</p>
                <ul class="alert-list">
                    @foreach ($alerts as $alert)
                        <li>{{ $alert }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="permissions-section" id="rolePermissionsPanel" style="{{ $adminPage === 'permissions' ? '' : 'display:none;' }}">
            <p class="label">Role Permissions</p>
            @if ($currentRolePermissions)
                <p class="small">Current session role: <span class="role-pill">{{ $currentRolePermissions['label'] }}</span> — {{ $currentRolePermissions['summary'] }}</p>
            @else
                <p class="small">Current session role: <span class="role-pill">{{ strtoupper((string) $portalRole) }}</span></p>
            @endif
            <div class="permissions-grid">
                @foreach ($rolePermissions as $roleCode => $roleMeta)
                    <article class="permission-card {{ strtoupper((string) $portalRole) === $roleCode || (strtoupper((string) $portalRole) === 'ADMIN_FINACE' && $roleCode === 'ADMIN_FINANCE') ? 'current' : '' }}">
                        <p class="permission-title">{{ $roleMeta['label'] }}</p>
                        <p class="permission-summary">{{ $roleMeta['summary'] }}</p>
                        <ul class="permission-list">
                            @foreach ($roleMeta['capabilities'] as $capability)
                                <li>{{ $capability }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="card" id="contentPanel" style="{{ $adminPage === 'content' ? '' : 'display:none;' }}">
            <p class="label">Content Hub</p>
            <p class="small">Manage blogs, newsletters, PR content, and announcements from this workspace.</p>

            @if (($canEditorialReview ?? false) === true)
                <div class="notice-box" style="margin-top:10px;">Editorial reviewer mode enabled. You can approve or reject blog submissions from ADMIN_MEDIA.</div>
            @else
                <div class="notice-box" style="margin-top:10px;">Content author mode enabled. Your blog edits are submitted for editorial review.</div>
            @endif

            <div class="quick-actions" style="margin-top:12px; display:flex; flex-wrap:wrap; gap:10px;">
                <a class="btn-link" href="/portal/admin/blog">Open Blog Manager</a>
                <a class="btn-link" href="/portal/admin/blog/create">Write Blog Post</a>
                <a class="btn-link" href="/portal/admin/atlas">Open Island Atlas Manager</a>
                <a class="btn-link" href="/portal/admin/atlas/islands/create">Create Island Record</a>
                <a class="btn-link" href="/portal/admin/newsletter">Open Newsletters</a>
                <a class="btn-link" href="/portal/admin/newsletter/create">Create Newsletter</a>
                <a class="btn-link" href="/portal/admin/announcement">Open Announcements</a>
                <a class="btn-link" href="/portal/admin/announcement/create">Create Announcement</a>
            </div>
        </section>

        <section class="card manage" id="financeModerationPanel" style="{{ $adminPage === 'finance' ? '' : 'display:none;' }}">
            <p class="label">Finance Moderation</p>
            <p class="small">Only ADMIN_SUPER and ADMIN_FINANCE can adjust commission rates and apply billing-level moderation for daily collections and vendor payouts.</p>

            <div class="finance-grid">
                <article class="finance-card">
                    <p class="metric-label">Gross Total</p>
                    <p class="metric-value">{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) ($financeSummary['gross_total'] ?? 0), 2) }}</p>
                </article>
                <article class="finance-card">
                    <p class="metric-label">Collected Total</p>
                    <p class="metric-value">{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) ($financeSummary['collected_total'] ?? 0), 2) }}</p>
                </article>
                <article class="finance-card">
                    <p class="metric-label">Commission Total</p>
                    <p class="metric-value">{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) ($financeSummary['commission_total'] ?? 0), 2) }}</p>
                </article>
                <article class="finance-card">
                    <p class="metric-label">Net Vendor Payout</p>
                    <p class="metric-value">{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) ($financeSummary['net_payout_total'] ?? 0), 2) }}</p>
                </article>
            </div>

            @if (!$canModerateFinance)
                <div class="error-box" style="margin-top:10px;">Finance moderation is read-only for this role. Ask ADMIN_SUPER or ADMIN_FINANCE to apply adjustments.</div>
            @else
                <div class="finance-layout">
                    <form class="finance-form" method="POST" action="/portal/admin/finance/commission/update">
                        @csrf
                        <p class="label">Commission Settings</p>
                        <div class="finance-form-grid">
                            <div class="finance-field">
                                <label for="commission_rate_percent">Commission Rate (%)</label>
                                <input id="commission_rate_percent" name="commission_rate_percent" type="number" step="0.01" min="0" max="100" value="{{ old('commission_rate_percent', $financeCommissionRate) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="default_currency">Default Currency</label>
                                <input id="default_currency" name="default_currency" type="text" maxlength="8" value="{{ old('default_currency', $financeCurrency) }}" required>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Update Commission Settings</button>
                    </form>

                    <form class="finance-form" method="POST" action="/portal/admin/finance/policy/update">
                        @csrf
                        <p class="label">Reservation Policy Settings</p>
                        <p class="small">Use this to update Maldives tax policy thresholds and any default transfer fallback values when government or supplier rules change.</p>
                        <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                            <button
                                class="btn btn-secondary"
                                type="submit"
                                formaction="/portal/admin/finance/policy/apply-maldives-defaults"
                                formmethod="post"
                                onclick="return confirm('Apply Maldives default tax policy and overwrite current policy values?');"
                            >Apply Maldives Defaults</button>
                        </div>
                        <div class="finance-form-grid">
                            <div class="finance-field">
                                <label for="green_tax_room_threshold">Green Tax Room Threshold</label>
                                <input id="green_tax_room_threshold" name="green_tax_room_threshold" type="number" min="1" max="10000" value="{{ old('green_tax_room_threshold', (int) ($financeReservationPolicy['green_tax_room_threshold'] ?? 50)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_local_adult_rate">Default Local Adult Transfer</label>
                                <input id="transfer_default_local_adult_rate" name="transfer_default_local_adult_rate" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_local_adult_rate', (float) ($financeReservationPolicy['transfer_default_local_adult_rate'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_local_child_rate">Default Local Child Transfer</label>
                                <input id="transfer_default_local_child_rate" name="transfer_default_local_child_rate" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_local_child_rate', (float) ($financeReservationPolicy['transfer_default_local_child_rate'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_foreign_adult_rate">Default Foreign Adult Transfer</label>
                                <input id="transfer_default_foreign_adult_rate" name="transfer_default_foreign_adult_rate" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_foreign_adult_rate', (float) ($financeReservationPolicy['transfer_default_foreign_adult_rate'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_foreign_child_rate">Default Foreign Child Transfer</label>
                                <input id="transfer_default_foreign_child_rate" name="transfer_default_foreign_child_rate" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_foreign_child_rate', (float) ($financeReservationPolicy['transfer_default_foreign_child_rate'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_base_local">Default Local Transfer Base</label>
                                <input id="transfer_default_base_local" name="transfer_default_base_local" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_base_local', (float) ($financeReservationPolicy['transfer_default_base_local'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="transfer_default_base_foreign">Default Foreign Transfer Base</label>
                                <input id="transfer_default_base_foreign" name="transfer_default_base_foreign" type="number" step="0.0001" min="0" max="1000000" value="{{ old('transfer_default_base_foreign', (float) ($financeReservationPolicy['transfer_default_base_foreign'] ?? 0)) }}" required>
                            </div>
                            <div class="finance-field finance-field-wide">
                                <label>Taxable Categories</label>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-top:8px;">
                                    @foreach (($financeTaxableCategoryOptions ?? []) as $categoryKey => $categoryLabel)
                                        <label style="display:flex; align-items:center; gap:8px; font-weight:500;">
                                            <input
                                                type="checkbox"
                                                name="taxable_categories[]"
                                                value="{{ $categoryKey }}"
                                                {{ in_array($categoryKey, old('taxable_categories', (array) ($financeReservationPolicy['taxable_categories'] ?? [])), true) ? 'checked' : '' }}
                                            >
                                            <span>{{ $categoryLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Update Reservation Policy</button>
                    </form>

                    <form class="finance-form" method="POST" action="/portal/admin/finance/tax-components/upsert">
                        @csrf
                        <p class="label">Tax Components (Admin Moderation)</p>
                        <div class="finance-form-grid">
                            <div class="finance-field">
                                <label for="tax_component_code">Tax Code</label>
                                <input id="tax_component_code" name="code" type="text" maxlength="80" placeholder="example: climate_levy" required>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_label">Tax Label</label>
                                <input id="tax_component_label" name="label" type="text" maxlength="190" placeholder="Climate Levy" required>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_mode">Calculation</label>
                                <select id="tax_component_mode" name="calculation_mode" required>
                                    <option value="percent_subtotal">Percent of Subtotal</option>
                                    <option value="per_guest_per_night">Per Guest Per Night</option>
                                    <option value="flat_booking">Flat Per Booking</option>
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_rate">Default Rate</label>
                                <input id="tax_component_rate" name="default_rate" type="number" step="0.0001" min="0" max="1000000" required>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_applies_to">Applies To</label>
                                <select id="tax_component_applies_to" name="applies_to" required>
                                    <option value="all">All Guests</option>
                                    <option value="local_resident">Local Resident</option>
                                    <option value="foreign_national">Foreign National</option>
                                </select>
                            </div>
                            <div class="finance-field finance-field-wide">
                                <label for="tax_component_applies_to_categories_csv">Applies To Categories (comma-separated keys)</label>
                                <input id="tax_component_applies_to_categories_csv" name="applies_to_categories_csv" type="text" maxlength="1000" placeholder="accommodation,restaurant,excursion">
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_active">Active</label>
                                <select id="tax_component_active" name="active" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_service_flag">Service Charge Flag</label>
                                <select id="tax_component_service_flag" name="is_service_charge" required>
                                    <option value="0">Tax</option>
                                    <option value="1">Service Charge</option>
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_exclude_infants">Exclude Infants (Under 2)</label>
                                <select id="tax_component_exclude_infants" name="exclude_infants" required>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_min_rooms">Min Room Count (optional)</label>
                                <input id="tax_component_min_rooms" name="min_room_count" type="number" min="0" max="10000">
                            </div>
                            <div class="finance-field">
                                <label for="tax_component_max_rooms">Max Room Count (optional)</label>
                                <input id="tax_component_max_rooms" name="max_room_count" type="number" min="0" max="10000">
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Save Tax Component</button>
                    </form>

                    <form class="finance-form" method="POST" action="/portal/admin/finance/adjustments/create">
                        @csrf
                        <p class="label">Create Finance Adjustment</p>
                        <div class="finance-form-grid">
                            <div class="finance-field">
                                <label for="finance_vendor_user_id">Vendor</label>
                                <select id="finance_vendor_user_id" name="vendor_user_id" required>
                                    <option value="">Select vendor</option>
                                    @foreach ($vendorPortalUsers as $vendorUser)
                                        <option value="{{ $vendorUser->id }}">{{ $vendorUser->name ?: 'Vendor #' . $vendorUser->id }} ({{ $vendorUser->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="finance_applies_on">Applies On</label>
                                <input id="finance_applies_on" name="applies_on" type="date" value="{{ old('applies_on', now()->toDateString()) }}" required>
                            </div>
                            <div class="finance-field">
                                <label for="finance_adjustment_type">Adjustment Type</label>
                                <select id="finance_adjustment_type" name="adjustment_type" required>
                                    <option value="manual_bonus">Manual Bonus</option>
                                    <option value="manual_penalty">Manual Penalty</option>
                                    <option value="commission_credit">Commission Credit</option>
                                    <option value="commission_debit">Commission Debit</option>
                                    <option value="payout_hold">Payout Hold</option>
                                    <option value="payout_release">Payout Release</option>
                                </select>
                            </div>
                            <div class="finance-field">
                                <label for="finance_amount">Amount (+/-)</label>
                                <input id="finance_amount" name="amount" type="number" step="0.01" min="-10000000" max="10000000" required>
                            </div>
                            <div class="finance-field">
                                <label for="finance_currency">Currency</label>
                                <input id="finance_currency" name="currency" type="text" maxlength="8" value="{{ old('currency', $financeCurrency) }}">
                            </div>
                            <div class="finance-field">
                                <label for="finance_invoice_reference">Invoice Reference (optional)</label>
                                <input id="finance_invoice_reference" name="invoice_reference" type="text" maxlength="64" value="{{ old('invoice_reference') }}">
                            </div>
                            <div class="finance-field finance-field-wide">
                                <label for="finance_reason">Moderation Reason</label>
                                <textarea id="finance_reason" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Apply Finance Adjustment</button>
                    </form>
                </div>
            @endif

            <div class="finance-table-wrap">
                <table class="finance-table" aria-label="Tax components moderation table">
                    <thead>
                        <tr>
                            <th>Tax Component Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($financeTaxComponents ?? collect())->take(120) as $taxComponent)
                            @php
                                $taxCode = strtolower(trim((string) ($taxComponent['code'] ?? '')));
                                $taxCategoriesCsv = collect((array) ($taxComponent['applies_to_categories'] ?? []))
                                    ->map(static fn ($value) => strtolower(trim((string) $value)))
                                    ->filter()
                                    ->implode(',');
                            @endphp
                            <tr>
                                <td>
                                    <form method="POST" action="/portal/admin/finance/tax-components/upsert" style="display:grid; gap:8px; min-width:950px;">
                                        @csrf
                                        <div style="display:grid; grid-template-columns:160px 220px 160px 130px 180px 110px 110px 130px 120px 120px auto auto; gap:8px; align-items:end;">
                                            <div>
                                                <label class="small">Code</label>
                                                <input type="text" name="code" maxlength="80" value="{{ $taxCode }}" required>
                                            </div>
                                            <div>
                                                <label class="small">Label</label>
                                                <input type="text" name="label" maxlength="190" value="{{ (string) ($taxComponent['label'] ?? '-') }}" required>
                                            </div>
                                            <div>
                                                <label class="small">Calculation</label>
                                                <select name="calculation_mode" required>
                                                    <option value="percent_subtotal" {{ (string) ($taxComponent['calculation_mode'] ?? '') === 'percent_subtotal' ? 'selected' : '' }}>Percent</option>
                                                    <option value="per_guest_per_night" {{ (string) ($taxComponent['calculation_mode'] ?? '') === 'per_guest_per_night' ? 'selected' : '' }}>Per Guest/Night</option>
                                                    <option value="flat_booking" {{ (string) ($taxComponent['calculation_mode'] ?? '') === 'flat_booking' ? 'selected' : '' }}>Flat Booking</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="small">Rate</label>
                                                <input type="number" name="default_rate" step="0.0001" min="0" max="1000000" value="{{ number_format((float) ($taxComponent['default_rate'] ?? 0), 4, '.', '') }}" required>
                                            </div>
                                            <div>
                                                <label class="small">Applies To</label>
                                                <select name="applies_to" required>
                                                    <option value="all" {{ (string) ($taxComponent['applies_to'] ?? '') === 'all' ? 'selected' : '' }}>All Guests</option>
                                                    <option value="local_resident" {{ (string) ($taxComponent['applies_to'] ?? '') === 'local_resident' ? 'selected' : '' }}>Local</option>
                                                    <option value="foreign_national" {{ (string) ($taxComponent['applies_to'] ?? '') === 'foreign_national' ? 'selected' : '' }}>Foreign</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="small">Categories CSV</label>
                                                <input type="text" name="applies_to_categories_csv" maxlength="1000" value="{{ $taxCategoriesCsv }}" placeholder="all categories when blank">
                                            </div>
                                            <div>
                                                <label class="small">Active</label>
                                                <select name="active" required>
                                                    <option value="1" {{ ((bool) ($taxComponent['active'] ?? false)) ? 'selected' : '' }}>Yes</option>
                                                    <option value="0" {{ !((bool) ($taxComponent['active'] ?? false)) ? 'selected' : '' }}>No</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="small">Type</label>
                                                <select name="is_service_charge" required>
                                                    <option value="0" {{ !((bool) ($taxComponent['is_service_charge'] ?? false)) ? 'selected' : '' }}>Tax</option>
                                                    <option value="1" {{ ((bool) ($taxComponent['is_service_charge'] ?? false)) ? 'selected' : '' }}>Service</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="small">Exclude Infants</label>
                                                <select name="exclude_infants" required>
                                                    <option value="0" {{ !((bool) ($taxComponent['exclude_infants'] ?? false)) ? 'selected' : '' }}>No</option>
                                                    <option value="1" {{ ((bool) ($taxComponent['exclude_infants'] ?? false)) ? 'selected' : '' }}>Yes</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="small">Min Rooms</label>
                                                <input type="number" name="min_room_count" min="0" max="10000" value="{{ $taxComponent['min_room_count'] !== null ? (int) $taxComponent['min_room_count'] : '' }}">
                                            </div>
                                            <div>
                                                <label class="small">Max Rooms</label>
                                                <input type="number" name="max_room_count" min="0" max="10000" value="{{ $taxComponent['max_room_count'] !== null ? (int) $taxComponent['max_room_count'] : '' }}">
                                            </div>
                                            <div>
                                                <button class="btn btn-primary" type="submit">Save</button>
                                            </div>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="/portal/admin/finance/tax-components/delete" onsubmit="return confirm('Delete this tax component?');">
                                        @csrf
                                        <input type="hidden" name="code" value="{{ $taxCode }}">
                                        <button class="btn btn-secondary" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="finance-empty">No tax components configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="finance-table-wrap">
                <table class="finance-table" aria-label="Daily payout moderation ledger">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Transactions</th>
                            <th>Gross</th>
                            <th>Collected</th>
                            <th>Commission</th>
                            <th>Adjustments</th>
                            <th>Net Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($financeDailyRows->take(80) as $financeRow)
                            <tr>
                                <td>{{ $financeRow['collection_day'] }}</td>
                                <td>{{ $financeRow['vendor_name'] }}<br>{{ $financeRow['vendor_email'] }}</td>
                                <td>{{ $financeRow['transactions_count'] }}</td>
                                <td>{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) $financeRow['gross_total'], 2) }}</td>
                                <td>{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) $financeRow['collected_total'], 2) }}</td>
                                <td>{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) $financeRow['commission_amount'], 2) }}</td>
                                <td>{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) $financeRow['adjustment_amount'], 2) }}</td>
                                <td>{{ strtoupper((string) $financeCurrency) }} {{ number_format((float) $financeRow['net_payout'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="finance-empty">No finance ledger rows yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="finance-table-wrap">
                <table class="finance-table" aria-label="Finance adjustment history">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Vendor</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Invoice Ref</th>
                            <th>Status</th>
                            <th>Moderator</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($financeAdjustments->take(80) as $adjustment)
                            <tr>
                                <td>{{ $adjustment->applies_on }}<br>{{ $adjustment->created_at }}</td>
                                <td>{{ $adjustment->vendor_name ?: 'Vendor #' . $adjustment->vendor_user_id }}<br>{{ $adjustment->vendor_email }}</td>
                                <td>{{ strtoupper((string) $adjustment->adjustment_type) }}</td>
                                <td>{{ strtoupper((string) $adjustment->currency) }} {{ number_format((float) $adjustment->amount, 2) }}</td>
                                <td>{{ $adjustment->invoice_reference ?: 'N/A' }}</td>
                                <td>{{ strtoupper((string) $adjustment->status) }}</td>
                                <td>{{ $adjustment->moderated_by_name ?: 'System' }}<br>{{ $adjustment->moderated_by_role ?: '' }}</td>
                                <td>{{ $adjustment->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="finance-empty">No finance adjustments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card manage" id="heroImageSettingsPanel" style="{{ $adminPage === 'media' ? '' : 'display:none;' }}">
            <p class="label">Hero Image Settings</p>
            <p class="small">Update the homepage banner and category catalogue hero images. Each slot saves independently — upload an image, paste an HTTPS URL, or remove the current one.</p>
            <div style="margin-top:10px;padding:10px 12px;border:1px solid #cfe0ec;border-radius:10px;background:#f4f9fd;">
                <p style="margin:0 0 6px;font-weight:700;font-size:0.82rem;color:#18435c;">Upload guidance</p>
                <p class="small" style="margin:0;line-height:1.45;">
                    Recommended: <strong>3200 x 1800</strong> (16:9). Minimum for desktop quality: <strong>2560 x 1440</strong>.
                    Maximum file size: <strong>4 MB</strong>. Supported formats: <strong>JPG, PNG, WebP</strong>.
                </p>
            </div>

            @if ($adminPage === 'media')
                @if (session('portal_notice'))
                    <div class="notice prominent" style="margin-top:12px;">{{ session('portal_notice') }}</div>
                @endif
                @if ($errors->any())
                    <div class="error-box prominent" style="margin-top:12px;">{{ $errors->first() }}</div>
                @endif
            @endif

            @if (!$canManageVendorUsers)
                <div class="error-box" style="margin-top:10px;">Only ADMIN_SUPER or ADMIN can update hero image settings.</div>
            @else
                {{-- Homepage banner slot --}}
                @php
                    $homeHeroStoredValue = trim((string) ($homeHeroAdminStoredValue ?? ''));
                    $homeHeroExternalValue = preg_match('#^https?://#i', $homeHeroStoredValue) === 1 ? $homeHeroStoredValue : '';
                @endphp
                <div style="padding:16px;border:1px solid #d7e0e6;border-radius:12px;background:#f9fbfc;margin-top:16px;">
                    <p style="font-weight:600;margin:0 0 10px;">Homepage Banner</p>
                    @if (!empty($homeHeroAdminImageUrl))
                        <img src="{{ $homeHeroAdminImageUrl }}" alt="Homepage banner preview" style="display:block;width:100%;max-width:540px;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid #d7e0e6;margin-bottom:8px;background:#eef4f7;">
                        <p class="small" style="margin:0 0 12px;">Source: {{ $homeHeroExternalValue !== '' ? 'External URL' : 'Managed upload' }}</p>
                    @else
                        <p class="small" style="margin:0 0 12px;">No banner configured yet.</p>
                    @endif
                    <form method="POST" action="/portal/admin/media-hero/update" enctype="multipart/form-data" style="margin-bottom:10px;">
                        @csrf
                        <label for="home_hero_image_file" style="display:block;margin-bottom:4px;font-size:0.85rem;">Upload new image (JPG, PNG, WebP · max 4 MB · 16:9 recommended)</label>
                        <input id="home_hero_image_file" name="home_hero_image_file" type="file" accept="image/png,image/jpeg,image/webp" style="display:block;margin-bottom:12px;">
                        <label for="home_hero_image_url" style="display:block;margin-bottom:4px;font-size:0.85rem;">Or paste an external HTTPS URL</label>
                        <input id="home_hero_image_url" name="home_hero_image_url" type="text" maxlength="2048" value="{{ old('home_hero_image_url', $homeHeroExternalValue) }}" placeholder="https://cdn.example.com/homepage-banner.jpg" style="display:block;width:100%;max-width:540px;margin-bottom:12px;">
                        <button class="btn btn-primary" type="submit">Update Homepage Banner</button>
                    </form>
                    @if (!empty($homeHeroAdminImageUrl))
                        <form method="POST" action="/portal/admin/media-hero/update" style="display:inline;">
                            @csrf
                            <input type="hidden" name="home_hero_image_clear" value="1">
                            <button class="btn" type="submit" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;" onclick="return confirm('Remove the homepage banner? This cannot be undone.')">Remove Image</button>
                        </form>
                    @endif
                </div>

                {{-- Per-category hero slots --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-top:16px;">
                    @foreach (($catalogHeroAdminCategories ?? []) as $categoryKey => $categoryLabel)
                        @php
                            $fieldName = 'catalog_hero_image_' . str_replace('-', '_', (string) $categoryKey);
                            $fieldValue = (string) data_get($catalogHeroAdminImages ?? [], $categoryKey, '');
                            $fieldStoredValue = trim((string) data_get($catalogHeroAdminStoredValues ?? [], $categoryKey, ''));
                            $fieldExternalValue = preg_match('#^https?://#i', $fieldStoredValue) === 1 ? $fieldStoredValue : '';
                        @endphp
                        <div style="padding:16px;border:1px solid #d7e0e6;border-radius:12px;background:#f9fbfc;">
                            <p style="font-weight:600;margin:0 0 10px;">{{ $categoryLabel }}</p>
                            @if ($fieldValue !== '')
                                <img src="{{ $fieldValue }}" alt="{{ $categoryLabel }} hero preview" style="display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid #d7e0e6;margin-bottom:8px;background:#eef4f7;">
                                <p class="small" style="margin:0 0 12px;">Source: {{ $fieldExternalValue !== '' ? 'External URL' : 'Managed upload' }}</p>
                            @else
                                <p class="small" style="margin:0 0 12px;">No image configured yet.</p>
                            @endif
                            <form method="POST" action="/portal/admin/media-hero/update" enctype="multipart/form-data" style="margin-bottom:10px;">
                                @csrf
                                <label for="{{ $fieldName }}_file" style="display:block;margin-bottom:4px;font-size:0.85rem;">Upload new image (max 4 MB · 16:9 recommended)</label>
                                <input id="{{ $fieldName }}_file" name="{{ $fieldName }}_file" type="file" accept="image/png,image/jpeg,image/webp" style="display:block;margin-bottom:12px;">
                                <label for="{{ $fieldName }}" style="display:block;margin-bottom:4px;font-size:0.85rem;">Or paste an HTTPS URL</label>
                                <input id="{{ $fieldName }}" name="{{ $fieldName }}" type="text" maxlength="2048" value="{{ old($fieldName, $fieldExternalValue) }}" placeholder="https://cdn.example.com/{{ $categoryKey }}.jpg" style="display:block;width:100%;margin-bottom:12px;">
                                <button class="btn btn-primary" type="submit" style="font-size:0.82rem;">Update {{ $categoryLabel }}</button>
                            </form>
                            @if ($fieldValue !== '')
                                <form method="POST" action="/portal/admin/media-hero/update" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="{{ $fieldName }}_clear" value="1">
                                    <button class="btn" type="submit" style="font-size:0.82rem;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;" onclick="return confirm('Remove the {{ $categoryLabel }} hero image?')">Remove</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div style="padding:16px;border:1px solid #d7e0e6;border-radius:12px;background:#f9fbfc;margin-top:18px;">
                    <p style="font-weight:600;margin:0 0 8px;">Homepage Destination Image Overrides</p>
                    <p class="small" style="margin:0 0 12px;line-height:1.5;">Trending and Loved destination cards now choose images automatically from live listing media. Use an override only when you want a specific island, city, or atoll image to replace the automatic choice.</p>

                    <form method="POST" action="/portal/admin/media-destination/update" enctype="multipart/form-data" style="margin-bottom:14px;">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end;">
                            <div>
                                <label for="destination_name" style="display:block;margin-bottom:4px;font-size:0.85rem;">Destination Name</label>
                                <input id="destination_name" name="destination_name" type="text" maxlength="190" value="{{ old('destination_name') }}" placeholder="Maafushi, Baa Atoll, Male City" style="display:block;width:100%;">
                            </div>
                            <div>
                                <label for="destination_type" style="display:block;margin-bottom:4px;font-size:0.85rem;">Destination Type</label>
                                <select id="destination_type" name="destination_type" style="display:block;width:100%;">
                                    <option value="destination">Destination</option>
                                    <option value="island" {{ old('destination_type') === 'island' ? 'selected' : '' }}>Island</option>
                                    <option value="atoll" {{ old('destination_type') === 'atoll' ? 'selected' : '' }}>Atoll</option>
                                    <option value="city" {{ old('destination_type') === 'city' ? 'selected' : '' }}>City</option>
                                </select>
                            </div>
                        </div>
                        <label for="destination_image_file" style="display:block;margin:12px 0 4px;font-size:0.85rem;">Upload replacement image (JPG, PNG, WebP · max 4 MB · 16:9 recommended)</label>
                        <input id="destination_image_file" name="destination_image_file" type="file" accept="image/png,image/jpeg,image/webp" style="display:block;margin-bottom:12px;">
                        <label for="destination_image_url" style="display:block;margin-bottom:4px;font-size:0.85rem;">Or paste an external HTTPS URL</label>
                        <input id="destination_image_url" name="destination_image_url" type="text" maxlength="2048" value="{{ old('destination_image_url') }}" placeholder="https://cdn.example.com/destinations/maafushi.jpg" style="display:block;width:100%;max-width:640px;margin-bottom:12px;">
                        <button class="btn btn-primary" type="submit">Save Destination Override</button>
                    </form>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
                        @forelse (($destinationMediaOverrides ?? collect()) as $override)
                            <div style="padding:14px;border:1px solid #d7e0e6;border-radius:12px;background:#ffffff;">
                                @if (!empty($override['image_url']))
                                    <img src="{{ $override['image_url'] }}" alt="{{ $override['destination_name'] ?? 'Destination' }} override preview" style="display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid #d7e0e6;margin-bottom:10px;background:#eef4f7;">
                                @endif
                                <p style="margin:0 0 4px;font-weight:700;color:#173e5b;">{{ $override['destination_name'] ?? 'Destination' }}</p>
                                <p class="small" style="margin:0 0 6px;">Type: {{ ucfirst(str_replace('_', ' ', $override['destination_type'] ?? 'destination')) }}</p>
                                <p class="small" style="margin:0 0 10px;word-break:break-word;">Key: {{ $override['destination_key'] ?? '' }}</p>
                                <form method="POST" action="/portal/admin/media-destination/update" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="destination_name" value="{{ $override['destination_name'] ?? '' }}">
                                    <input type="hidden" name="destination_type" value="{{ $override['destination_type'] ?? 'destination' }}">
                                    <input type="hidden" name="destination_key" value="{{ $override['destination_key'] ?? '' }}">
                                    <input type="hidden" name="destination_image_clear" value="1">
                                    <button class="btn" type="submit" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;" onclick="return confirm('Remove the override for {{ $override['destination_name'] ?? 'this destination' }}?')">Remove Override</button>
                                </form>
                            </div>
                        @empty
                            <div style="padding:14px;border:1px dashed #c7d6e2;border-radius:12px;background:#ffffff;grid-column:1 / -1;">
                                <p class="small" style="margin:0;">No destination overrides saved yet. Homepage destination cards will continue using automatic representative listing media until you add one.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                @php
                    $blogAd = is_array($blogSidebarAdSettings ?? null) ? $blogSidebarAdSettings : [];
                    $blogAdTitle = (string) ($blogAd['title'] ?? 'Charter a vessel?');
                    $blogAdBrand = (string) ($blogAd['brand'] ?? 'workation');
                    $blogAdCtaLabel = (string) ($blogAd['cta_label'] ?? 'Explore now');
                    $blogAdCtaUrl = (string) ($blogAd['cta_url'] ?? '/catalog/marine-transport');
                    $blogAdImageUrl = (string) ($blogAd['image_url'] ?? '');
                @endphp
                <div style="padding:16px;border:1px solid #d7e0e6;border-radius:12px;background:#f9fbfc;margin-top:18px;">
                    <p style="font-weight:600;margin:0 0 8px;">Blog Article Sidebar Ad</p>
                    <p class="small" style="margin:0 0 12px;line-height:1.5;">Configure the ad card shown on blog article pages. Supports custom title, brand text, CTA label, CTA link, and image.</p>

                    @if ($blogAdImageUrl !== '')
                        <img src="{{ $blogAdImageUrl }}" alt="Blog sidebar ad preview" style="display:block;width:100%;max-width:320px;aspect-ratio:1/1;object-fit:cover;border-radius:10px;border:1px solid #d7e0e6;margin-bottom:10px;background:#eef4f7;">
                    @endif

                    <form method="POST" action="/portal/admin/media-blog-ad/update" enctype="multipart/form-data" style="margin-bottom:12px;">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end;">
                            <div>
                                <label for="blog_sidebar_ad_title" style="display:block;margin-bottom:4px;font-size:0.85rem;">Headline</label>
                                <input id="blog_sidebar_ad_title" name="blog_sidebar_ad_title" type="text" maxlength="190" value="{{ old('blog_sidebar_ad_title', $blogAdTitle) }}" placeholder="Charter a vessel?" style="display:block;width:100%;">
                            </div>
                            <div>
                                <label for="blog_sidebar_ad_brand" style="display:block;margin-bottom:4px;font-size:0.85rem;">Brand Label</label>
                                <input id="blog_sidebar_ad_brand" name="blog_sidebar_ad_brand" type="text" maxlength="120" value="{{ old('blog_sidebar_ad_brand', $blogAdBrand) }}" placeholder="workation" style="display:block;width:100%;">
                            </div>
                            <div>
                                <label for="blog_sidebar_ad_cta_label" style="display:block;margin-bottom:4px;font-size:0.85rem;">CTA Label</label>
                                <input id="blog_sidebar_ad_cta_label" name="blog_sidebar_ad_cta_label" type="text" maxlength="120" value="{{ old('blog_sidebar_ad_cta_label', $blogAdCtaLabel) }}" placeholder="Explore now" style="display:block;width:100%;">
                            </div>
                            <div>
                                <label for="blog_sidebar_ad_cta_url" style="display:block;margin-bottom:4px;font-size:0.85rem;">CTA URL</label>
                                <input id="blog_sidebar_ad_cta_url" name="blog_sidebar_ad_cta_url" type="text" maxlength="2048" value="{{ old('blog_sidebar_ad_cta_url', $blogAdCtaUrl) }}" placeholder="/catalog/marine-transport" style="display:block;width:100%;">
                            </div>
                        </div>

                        <label for="blog_sidebar_ad_image_file" style="display:block;margin:12px 0 4px;font-size:0.85rem;">Upload ad image (JPG, PNG, WebP · max 4 MB)</label>
                        <input id="blog_sidebar_ad_image_file" name="blog_sidebar_ad_image_file" type="file" accept="image/png,image/jpeg,image/webp" style="display:block;margin-bottom:12px;">

                        <label for="blog_sidebar_ad_image_url" style="display:block;margin-bottom:4px;font-size:0.85rem;">Or paste an external HTTPS image URL</label>
                        <input id="blog_sidebar_ad_image_url" name="blog_sidebar_ad_image_url" type="text" maxlength="2048" value="{{ old('blog_sidebar_ad_image_url') }}" placeholder="https://cdn.example.com/blog/sidebar-ad.jpg" style="display:block;width:100%;max-width:640px;margin-bottom:12px;">

                        <button class="btn btn-primary" type="submit">Save Blog Ad Settings</button>
                    </form>

                    @if ($blogAdImageUrl !== '')
                        <form method="POST" action="/portal/admin/media-blog-ad/update" style="display:inline;">
                            @csrf
                            <input type="hidden" name="blog_sidebar_ad_title" value="{{ $blogAdTitle }}">
                            <input type="hidden" name="blog_sidebar_ad_brand" value="{{ $blogAdBrand }}">
                            <input type="hidden" name="blog_sidebar_ad_cta_label" value="{{ $blogAdCtaLabel }}">
                            <input type="hidden" name="blog_sidebar_ad_cta_url" value="{{ $blogAdCtaUrl }}">
                            <input type="hidden" name="blog_sidebar_ad_image_clear" value="1">
                            <button class="btn" type="submit" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;" onclick="return confirm('Remove the blog sidebar ad image?')">Remove Ad Image</button>
                        </form>
                    @endif
                </div>
            @endif
        </section>

        <section class="card manage" id="listingOptionCatalogPanel" style="{{ $adminPage === 'catalog' ? '' : 'display:none;' }}">
            <p class="label">Listing Option Catalog</p>
            <p class="small">Maintain vendor form options from database so transport modes, accommodation facilities, and room amenities can be expanded without code changes.</p>

            @if (!$canManageVendorUsers)
                <div class="error-box" style="margin-top:10px;">Only ADMIN_SUPER or ADMIN can update these catalog values.</div>
            @else
                <form class="finance-form" method="POST" action="/portal/admin/listing-options/upsert" style="margin-top:10px;">
                    @csrf
                    <div class="finance-form-grid">
                        <div class="finance-field">
                            <label for="listing_option_type">Option Type</label>
                            <select id="listing_option_type" name="option_type" required>
                                <option value="transport_mode">Transport Mode</option>
                                <option value="accommodation_facility">Accommodation Facility</option>
                                <option value="property_amenity">Property Amenity</option>
                                <option value="property_feature">Property Feature</option>
                                <option value="room_amenity">Room Amenity</option>
                                <option value="bathroom_amenity">Bathroom Amenity</option>
                                <option value="room_bed_type">Room Bed Type</option>
                                <option value="transfer_option">Transfer Option</option>
                                <option value="excursion_type">Excursion Type</option>
                                <option value="restaurant_meal_service">Restaurant Meal Service</option>
                                <option value="vehicle_rental_type">Vehicle Rental Type</option>
                            </select>
                        </div>
                        <div class="finance-field">
                            <label for="listing_option_group">Group (optional)</label>
                            <input id="listing_option_group" name="option_group" type="text" maxlength="80" placeholder="marine, land, core, room">
                        </div>
                        <div class="finance-field">
                            <label for="listing_option_value">Option Value</label>
                            <input id="listing_option_value" name="option_value" type="text" maxlength="120" placeholder="speedboat, wifi, smart_tv" required>
                        </div>
                        <div class="finance-field">
                            <label for="listing_option_label">Option Label</label>
                            <input id="listing_option_label" name="option_label" type="text" maxlength="190" placeholder="Speedboat, Wi-Fi, Smart TV" required>
                        </div>
                        <div class="finance-field">
                            <label for="listing_option_sort">Sort Order</label>
                            <input id="listing_option_sort" name="sort_order" type="number" min="0" max="100000" value="100">
                        </div>
                        <div class="finance-field">
                            <label for="listing_option_active">Status</label>
                            <select id="listing_option_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Save Listing Option</button>
                </form>
            @endif

            <div class="finance-table-wrap" style="margin-top:10px;">
                <table class="finance-table" aria-label="Listing option catalog table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Label</th>
                            <th>Group</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($listingOptionCatalog->take(240) as $option)
                            @php
                                $optionFormId = 'listingOptionRowForm' . (int) $option->id;
                            @endphp
                            <tr>
                                <td>{{ strtoupper((string) $option->option_type) }}</td>
                                <td>{{ $option->option_value }}</td>
                                <td>
                                    @if ($canManageVendorUsers)
                                        <input form="{{ $optionFormId }}" name="option_label" type="text" maxlength="190" value="{{ $option->option_label }}" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 8px;font-size:0.82rem;">
                                    @else
                                        {{ $option->option_label }}
                                    @endif
                                </td>
                                <td>
                                    @if ($canManageVendorUsers)
                                        <input form="{{ $optionFormId }}" name="option_group" type="text" maxlength="80" value="{{ $option->option_group }}" placeholder="group" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 8px;font-size:0.82rem;">
                                    @else
                                        {{ $option->option_group ?: 'N/A' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($canManageVendorUsers)
                                        <input form="{{ $optionFormId }}" name="sort_order" type="number" min="0" max="100000" value="{{ (int) ($option->sort_order ?? 0) }}" style="width:92px;border:1px solid #c8d3df;border-radius:8px;padding:7px 8px;font-size:0.82rem;">
                                    @else
                                        {{ (int) ($option->sort_order ?? 0) }}
                                    @endif
                                </td>
                                <td>
                                    @if ($canManageVendorUsers)
                                        <select form="{{ $optionFormId }}" name="is_active" style="width:110px;border:1px solid #c8d3df;border-radius:8px;padding:7px 8px;font-size:0.82rem;background:#fff;">
                                            <option value="1" @selected((bool) ($option->is_active ?? false))>ACTIVE</option>
                                            <option value="0" @selected(!(bool) ($option->is_active ?? false))>INACTIVE</option>
                                        </select>
                                    @else
                                        {{ (bool) ($option->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($canManageVendorUsers)
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <form id="{{ $optionFormId }}" method="POST" action="/portal/admin/listing-options/upsert" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="option_type" value="{{ $option->option_type }}">
                                            <input type="hidden" name="option_value" value="{{ $option->option_value }}">
                                            <button class="btn btn-primary" type="submit" style="margin-top:0;padding:6px 10px;">Save</button>
                                        </form>
                                        <form method="POST" action="/portal/admin/listing-options/{{ $option->id }}/delete" onsubmit="return confirm('Remove this listing option?');" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-secondary" type="submit" style="margin-top:0;padding:6px 10px;">Delete</button>
                                        </form>
                                        </div>
                                    @else
                                        Read-only
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="finance-empty">No listing option catalog entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if (session('portal_notice') && $adminPage !== 'media')
            <div class="notice prominent" id="successBox">{{ session('portal_notice') }}</div>
        @endif
        <section class="card" id="sessionDebug" style="{{ $adminPage === 'tools' ? '' : 'display:none;' }}">
            <p class="label">Session Debug</p>
            <pre style="background:#f7f7f7;border-radius:8px;padding:8px;font-size:0.9rem;">portal_admin_authenticated: {{ session('portal_admin_authenticated') ? 'true' : 'false' }}
        portal_admin_role: {{ session('portal_admin_role') ?? 'null' }}
        portal_admin_user: {{ session('portal_admin_user') ?? 'null' }}
        portal_admin_user_id: {{ session('portal_admin_user_id') ?? 'null' }}

        canManageUsers: {{ var_export($canManageUsers, true) }}
        canManageVendorUsers: {{ var_export($canManageVendorUsers, true) }}
        canCreateVendorUsers: {{ var_export($canCreateVendorUsers, true) }}
        canReviewVendorRegistrations: {{ var_export($canReviewVendorRegistrations, true) }}
        canRequestVendorDeleteApproval: {{ var_export($canRequestVendorDeleteApproval, true) }}
            canModerateListings: {{ var_export($canModerateListings, true) }}
        </pre>
        </section>
        @if ($errors->any() && $adminPage !== 'media')
            <div class="error-box prominent" id="errorBox">{{ $errors->first() }}</div>
        @endif

        <section class="layout" id="authApiSection" style="{{ $adminPage === 'tools' ? '' : 'display:none;' }}">
            <article class="card">
                <p class="label">Auth</p>
                <input id="tokenInput" class="token-input" type="password" placeholder="Paste admin JWT bearer token">
                <div>
                    <button id="saveToken" class="btn btn-primary" type="button">Save Token</button>
                    <button id="clearToken" class="btn btn-secondary" type="button">Clear</button>
                </div>
                <div id="tokenState" class="state warn">TOKEN NOT SET</div>
                <div id="tokenMeta" class="token-meta">Token is stored only in this browser tab session.</div>
            </article>

            <article class="card">
                <p class="label">Admin API Actions</p>
                <div class="endpoint">
                    <code>GET /api/v1/auth/admin/ping</code>
                    <button type="button" data-path="/api/v1/auth/admin/ping">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/ops/alerts</code>
                    <button type="button" data-path="/api/v1/ops/alerts">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/ops/runbooks</code>
                    <button type="button" data-path="/api/v1/ops/runbooks">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/admin/jobs/health</code>
                    <button type="button" data-path="/api/v1/payments/admin/jobs/health">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/admin/reconcile/status</code>
                    <button type="button" data-path="/api/v1/payments/admin/reconcile/status">Run</button>
                </div>
                <pre id="output">Ready. Save token, then run an endpoint.</pre>
            </article>
        </section>

        <button id="toggleModerationBtn" class="btn btn-primary" type="button" style="{{ $adminPage === 'moderation' ? 'margin-bottom:18px;' : 'display:none;' }}">Show Moderation Panel</button>
        <section class="card manage" id="moderationPanel" style="{{ $adminPage === 'moderation' ? '' : 'display:none;' }}">
            <p class="label">Portal User Moderation</p>
            @if (!$canManageUsers && !$canManageVendorUsers && !$canReviewVendorRegistrations)
                <p class="small">Current role cannot manage users/vendors or review vendor registrations.</p>
            @else
                @if ($canReviewVendorRegistrations)
                    <p class="small">Review partner onboarding requests and approve or reject access. Property/service verification and quality checks happen after partners upload listings.</p>

                    @if ($canApproveVendorRegistrationRequest || $canApproveVendorDeleteRequest)
                        <p class="group-title">Pending Action Requests</p>

                        @if ($canApproveVendorRegistrationRequest)
                            <div class="registration-grid" style="margin-bottom:12px;">
                                @forelse ($pendingVendorRegistrationApprovalRequests as $approvalRequest)
                                    <div class="registration-row">
                                        <div class="registration-head">
                                            <span class="user-name">Vendor Registration Approval Request</span>
                                            <span class="role-pill">PENDING</span>
                                            <span class="small">{{ $approvalRequest->business_name ?: 'Unknown Business' }} | {{ $approvalRequest->registration_email ?: $approvalRequest->target_identifier }}</span>
                                        </div>
                                        <div class="small">Service Category: {{ ucwords(str_replace('_', ' ', (string) ($approvalRequest->vendor_type ?: 'other'))) }}</div>
                                        <div class="small">Requested by: {{ $approvalRequest->requested_by_name ?: 'Unknown' }}{{ $approvalRequest->requested_by_role ? ' (' . $approvalRequest->requested_by_role . ')' : '' }}</div>
                                        @if (!empty($approvalRequest->reason))
                                            <div class="small">Reason: {{ $approvalRequest->reason }}</div>
                                        @endif
                                        <div class="registration-actions">
                                            <form method="POST" action="/portal/admin/action-requests/{{ $approvalRequest->id }}/approve">
                                                @csrf
                                                <button class="btn-approve" type="submit">Approve Request</button>
                                            </form>
                                            <form method="POST" action="/portal/admin/action-requests/{{ $approvalRequest->id }}/reject">
                                                @csrf
                                                <label class="small" for="reject_registration_request_{{ $approvalRequest->id }}">Rejection reason</label>
                                                <textarea id="reject_registration_request_{{ $approvalRequest->id }}" name="reason" required placeholder="Explain why this request is rejected"></textarea>
                                                <button class="btn-reject" type="submit">Reject Request</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="user-row">
                                        <div class="small">No pending vendor registration approval requests.</div>
                                    </div>
                                @endforelse
                            </div>
                        @endif

                        @if ($canApproveVendorRegistrationRequest)
                            <div class="registration-grid" style="margin-bottom:12px;">
                                @forelse ($pendingVendorCategoryRequests as $categoryRequest)
                                    @php
                                        $categoryPayload = [];
                                        if (!empty($categoryRequest->payload)) {
                                            $decodedCategoryPayload = json_decode((string) $categoryRequest->payload, true);
                                            if (is_array($decodedCategoryPayload)) {
                                                $categoryPayload = $decodedCategoryPayload;
                                            }
                                        }
                                        $requestAction = strtolower(trim((string) ($categoryPayload['request_action'] ?? 'subscribe')));
                                        $requestedCategories = collect($categoryPayload['categories'] ?? [])->map(static fn ($value) => (string) $value)->filter()->values();
                                        $requestedDocuments = collect($categoryPayload['documents'] ?? [])->filter(static fn ($value) => is_array($value))->values();
                                        $requiredDocuments = collect($categoryPayload['required_documents'] ?? [])->map(static fn ($value) => (string) $value)->filter()->values();
                                        $requestedCategoryLabels = $requestedCategories->map(static function (string $categoryKey) use ($vendorCategoryMap): string {
                                            return (string) ($vendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
                                        })->values();
                                        $requestActionLabel = match ($requestAction) {
                                            'open' => 'Open Category',
                                            'release' => 'Release Category',
                                            default => 'Subscribe Category',
                                        };
                                    @endphp
                                    <div class="registration-row">
                                        <div class="registration-head">
                                            <span class="user-name">Vendor Category Update Request</span>
                                            <span class="role-pill">PENDING</span>
                                            <span class="small">{{ $categoryRequest->target_username ?: $categoryRequest->target_identifier }} | {{ $categoryRequest->target_email ?: 'n/a' }}</span>
                                        </div>
                                        <div class="small"><strong>Requested action:</strong> {{ $requestActionLabel }}</div>
                                        <div class="small"><strong>Requested categories:</strong> {{ $requestedCategoryLabels->isNotEmpty() ? $requestedCategoryLabels->implode(', ') : 'None provided' }}</div>
                                        @if (!empty($categoryRequest->target_vendor_id))
                                            <div class="small"><strong>Vendor ID:</strong> {{ $categoryRequest->target_vendor_id }}</div>
                                        @endif
                                        <div class="small">Requested by: {{ $categoryRequest->requested_by_name ?: 'Unknown' }}{{ $categoryRequest->requested_by_role ? ' (' . $categoryRequest->requested_by_role . ')' : '' }}</div>
                                        @if (!empty($categoryRequest->reason))
                                            <div class="small">Reason: {{ $categoryRequest->reason }}</div>
                                        @endif
                                        @if ($requiredDocuments->isNotEmpty())
                                            <div class="small"><strong>Required docs checklist:</strong> {{ $requiredDocuments->implode(' | ') }}</div>
                                        @endif
                                        <div class="doc-links">
                                            @forelse ($requestedDocuments as $documentIndex => $documentItem)
                                                @php
                                                    $documentName = (string) ($documentItem['name'] ?? ('Document ' . ($documentIndex + 1)));
                                                    $documentUrl = (string) ($documentItem['url'] ?? '');
                                                @endphp
                                                @if ($documentUrl !== '')
                                                    <a class="doc-link" href="{{ $documentUrl }}" target="_blank" rel="noopener">View {{ $documentName }}</a>
                                                @endif
                                            @empty
                                                <span class="small">No supporting documents attached in this request payload.</span>
                                            @endforelse
                                        </div>
                                        <div class="registration-actions">
                                            <form method="POST" action="/portal/admin/action-requests/{{ $categoryRequest->id }}/approve">
                                                @csrf
                                                <button class="btn-approve" type="submit">Approve Category Update</button>
                                            </form>
                                            <form method="POST" action="/portal/admin/action-requests/{{ $categoryRequest->id }}/reject">
                                                @csrf
                                                <label class="small" for="reject_category_request_{{ $categoryRequest->id }}">Rejection reason</label>
                                                <textarea id="reject_category_request_{{ $categoryRequest->id }}" name="reason" required placeholder="Explain why this category update request is rejected"></textarea>
                                                <button class="btn-reject" type="submit">Reject Category Update</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="user-row">
                                        <div class="small">No pending vendor category update requests.</div>
                                    </div>
                                @endforelse
                            </div>
                        @endif

                        @if ($canApproveVendorDeleteRequest)
                            <div class="registration-grid" style="margin-bottom:12px;">
                                @forelse ($pendingVendorDeleteRequests as $deleteRequest)
                                    <div class="registration-row">
                                        <div class="registration-head">
                                            <span class="user-name">Vendor Delete Approval Request</span>
                                            <span class="role-pill">PENDING</span>
                                            <span class="small">{{ $deleteRequest->target_username ?: 'unknown-user' }} | {{ $deleteRequest->target_email ?: $deleteRequest->target_identifier }}</span>
                                        </div>
                                        <div class="small">Requested by: {{ $deleteRequest->requested_by_name ?: 'Unknown' }}{{ $deleteRequest->requested_by_role ? ' (' . $deleteRequest->requested_by_role . ')' : '' }}</div>
                                        @if (!empty($deleteRequest->target_vendor_id))
                                            <div class="small">Vendor ID: {{ $deleteRequest->target_vendor_id }}</div>
                                        @endif
                                        @if (!empty($deleteRequest->reason))
                                            <div class="small">Reason: {{ $deleteRequest->reason }}</div>
                                        @endif
                                        <div class="registration-actions">
                                            <form method="POST" action="/portal/admin/action-requests/{{ $deleteRequest->id }}/approve">
                                                @csrf
                                                <button class="btn-approve" type="submit">Approve Vendor Delete</button>
                                            </form>
                                            <form method="POST" action="/portal/admin/action-requests/{{ $deleteRequest->id }}/reject">
                                                @csrf
                                                <label class="small" for="reject_delete_request_{{ $deleteRequest->id }}">Rejection reason</label>
                                                <textarea id="reject_delete_request_{{ $deleteRequest->id }}" name="reason" required placeholder="Explain why this delete request is rejected"></textarea>
                                                <button class="btn-reject" type="submit">Reject Request</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="user-row">
                                        <div class="small">No pending vendor delete requests.</div>
                                    </div>
                                @endforelse
                            </div>
                        @endif

                        @if ($canApproveVendorRegistrationRequest)
                            <p class="group-title">Vendor Category Request History (Approved / Rejected)</p>
                            <div class="registration-grid" style="margin-bottom:12px;">
                                @forelse ($vendorCategoryRequestHistory as $historyRequest)
                                    @php
                                        $historyPayload = [];
                                        if (!empty($historyRequest->payload)) {
                                            $decodedHistoryPayload = json_decode((string) $historyRequest->payload, true);
                                            if (is_array($decodedHistoryPayload)) {
                                                $historyPayload = $decodedHistoryPayload;
                                            }
                                        }
                                        $historyAction = strtolower(trim((string) ($historyPayload['request_action'] ?? 'subscribe')));
                                        $historyRequestedCategories = collect($historyPayload['categories'] ?? [])->map(static fn ($value) => (string) $value)->filter()->values();
                                        $historyRequestedCategoryLabels = $historyRequestedCategories->map(static function (string $categoryKey) use ($vendorCategoryMap): string {
                                            return (string) ($vendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
                                        })->values();
                                        $historyDocuments = collect($historyPayload['documents'] ?? [])->filter(static fn ($value) => is_array($value))->values();
                                        $historyActionLabel = match ($historyAction) {
                                            'open' => 'Open Category',
                                            'release' => 'Release Category',
                                            default => 'Subscribe Category',
                                        };
                                        $historyStatus = strtoupper((string) ($historyRequest->status ?? 'UNKNOWN'));
                                    @endphp
                                    <div class="registration-row">
                                        <div class="registration-head">
                                            <span class="user-name">Vendor Category Update Request</span>
                                            <span class="role-pill">{{ $historyStatus }}</span>
                                            <span class="small">{{ $historyRequest->target_username ?: $historyRequest->target_identifier }} | {{ $historyRequest->target_email ?: 'n/a' }}</span>
                                        </div>
                                        <div class="small"><strong>Requested action:</strong> {{ $historyActionLabel }}</div>
                                        <div class="small"><strong>Requested categories:</strong> {{ $historyRequestedCategoryLabels->isNotEmpty() ? $historyRequestedCategoryLabels->implode(', ') : 'None provided' }}</div>
                                        @if (!empty($historyRequest->target_vendor_id))
                                            <div class="small"><strong>Vendor ID:</strong> {{ $historyRequest->target_vendor_id }}</div>
                                        @endif
                                        <div class="small">Requested by: {{ $historyRequest->requested_by_name ?: 'Unknown' }}{{ $historyRequest->requested_by_role ? ' (' . $historyRequest->requested_by_role . ')' : '' }}</div>
                                        <div class="small">Reviewed by: {{ $historyRequest->approved_by_name ?: 'Unknown' }}{{ $historyRequest->approved_by_role ? ' (' . $historyRequest->approved_by_role . ')' : '' }} · {{ $historyRequest->approved_at ? \Illuminate\Support\Carbon::parse($historyRequest->approved_at)->format('Y-m-d H:i:s') : 'N/A' }}</div>
                                        @if (!empty($historyRequest->reason))
                                            <div class="small">Request note: {{ $historyRequest->reason }}</div>
                                        @endif
                                        @if (!empty($historyRequest->rejection_reason))
                                            <div class="small" style="color:#b91c1c;"><strong>Rejection reason:</strong> {{ $historyRequest->rejection_reason }}</div>
                                        @endif
                                        <div class="doc-links">
                                            @forelse ($historyDocuments as $historyDocumentIndex => $historyDocument)
                                                @php
                                                    $historyDocumentName = (string) ($historyDocument['name'] ?? ('Document ' . ($historyDocumentIndex + 1)));
                                                    $historyDocumentUrl = (string) ($historyDocument['url'] ?? '');
                                                @endphp
                                                @if ($historyDocumentUrl !== '')
                                                    <a class="doc-link" href="{{ $historyDocumentUrl }}" target="_blank" rel="noopener">View {{ $historyDocumentName }}</a>
                                                @endif
                                            @empty
                                                <span class="small">No supporting documents attached in this request payload.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <div class="user-row">
                                        <div class="small">No vendor category request history yet.</div>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    @endif

                    <p class="group-title" id="vendorRegistrationsPanel">Pending Vendor Registrations ({{ $pendingVendorRegistrations->count() }})</p>
                    <div class="registration-grid">
                        @forelse ($pendingVendorRegistrations as $registration)
                            <div class="registration-row">
                                <div class="registration-head">
                                    <span class="user-name">{{ $registration->business_name }}</span>
                                    <span class="role-pill">PENDING REVIEW</span>
                                    <span class="small">Contact: {{ $registration->contact_name }} | {{ $registration->email }}</span>
                                </div>
                                <div class="small">Service Category: {{ ucwords(str_replace('_', ' ', (string) ($registration->vendor_type ?: 'other'))) }}</div>
                                <div class="small">Business Reg #: {{ $registration->business_registration_number ?: 'N/A' }} | License #: {{ $registration->license_number ?: 'N/A' }}</div>
                                <div class="small">Listing quality-check status: Pending partner listing upload</div>
                                @if (!empty($registration->phone))
                                    <div class="small">Phone: {{ $registration->phone }}</div>
                                @endif
                                <div class="doc-links">
                                    @if (!empty($registration->business_license_document_path))
                                        <a class="doc-link" href="/portal/admin/vendor-registrations/{{ $registration->id }}/document/business_license" target="_blank" rel="noopener">View License Document</a>
                                    @endif
                                    @if (!empty($registration->verification_document_path))
                                        <a class="doc-link" href="/portal/admin/vendor-registrations/{{ $registration->id }}/document/verification" target="_blank" rel="noopener">View Verification Document</a>
                                    @endif
                                </div>
                                <div class="registration-actions">
                                    <form method="POST" action="/portal/admin/vendor-registrations/{{ $registration->id }}/approve">
                                        @csrf
                                        <label class="small" for="approve_vendor_id_{{ $registration->id }}">Vendor ID to assign</label>
                                        <input id="approve_vendor_id_{{ $registration->id }}" name="portal_vendor_id" type="text" required placeholder="e.g. VENDOR-{{ $registration->id }}">
                                        <label class="small" for="approve_notes_{{ $registration->id }}">Approval notes (optional)</label>
                                        <textarea id="approve_notes_{{ $registration->id }}" name="approval_notes" placeholder="Internal review notes"></textarea>
                                        @if ($canApproveVendorRegistrationRequest)
                                            <button class="btn-approve" type="submit">Approve and Enable Access</button>
                                        @else
                                            <button class="btn-approve" type="submit">Submit Approval Request</button>
                                        @endif
                                    </form>
                                    <form method="POST" action="/portal/admin/vendor-registrations/{{ $registration->id }}/reject">
                                        @csrf
                                        <label class="small" for="reject_notes_{{ $registration->id }}">Rejection reason (required)</label>
                                        <textarea id="reject_notes_{{ $registration->id }}" name="review_notes" required placeholder="Explain why this request was rejected"></textarea>
                                        <button class="btn-reject" type="submit">Reject Registration</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="user-row">
                                <div class="small">No pending vendor registration requests.</div>
                            </div>
                        @endforelse
                    </div>

                    <p class="group-title" id="vendorRegistrationHistoryPanel">Vendor Registration History (Approved and Rejected)</p>
                    <div class="registration-grid">
                        @forelse ($vendorRegistrationHistory as $historyRow)
                            <div class="registration-row">
                                <div class="registration-head">
                                    <span class="user-name">{{ $historyRow->business_name }}</span>
                                    <span class="role-pill">{{ strtoupper((string) $historyRow->status) }}</span>
                                    <span class="small">Contact: {{ $historyRow->contact_name }} | {{ $historyRow->email }}</span>
                                </div>
                                <div class="small">Service Category: {{ ucwords(str_replace('_', ' ', (string) ($historyRow->vendor_type ?: 'other'))) }}</div>
                                <div class="small">Business Reg #: {{ $historyRow->business_registration_number ?: 'N/A' }} | License #: {{ $historyRow->license_number ?: 'N/A' }}</div>
                                <div class="small">Reviewed by: {{ $historyRow->reviewed_by_name ?: 'Unknown' }}{{ $historyRow->reviewed_by_role ? ' (' . $historyRow->reviewed_by_role . ')' : '' }} · {{ $historyRow->reviewed_at ? \Illuminate\Support\Carbon::parse($historyRow->reviewed_at)->format('Y-m-d H:i:s') : 'N/A' }}</div>
                                @if (!empty($historyRow->approved_username) || !empty($historyRow->approved_vendor_id))
                                    <div class="small">Approved portal account: {{ $historyRow->approved_username ?: 'n/a' }}{{ $historyRow->approved_vendor_id ? ' · Vendor ID: ' . $historyRow->approved_vendor_id : '' }}</div>
                                @endif
                                @if (!empty($historyRow->review_notes))
                                    <div class="small">Review notes: {{ \Illuminate\Support\Str::limit((string) $historyRow->review_notes, 260) }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="user-row">
                                <div class="small">No approved or rejected vendor registrations yet.</div>
                            </div>
                        @endforelse
                    </div>
                @else
                    <p class="small">Current role cannot review vendor registrations. Any admin role except ADMIN_FINANCE can approve or reject vendor requests.</p>
                @endif

                @if ($canManageUsers || $canManageVendorUsers)
                    @if ($canManageUsers)
                        <p class="small">Change role permissions and suspend/reactivate accounts directly in the application.</p>
                    @else
                        @if ($canRequestVendorDeleteApproval)
                            <p class="small">You can manage VENDOR accounts (enable/suspend, vendor ID). Vendor delete requires ADMIN_SUPER approval.</p>
                        @else
                            <p class="small">You can manage VENDOR accounts (enable/suspend, vendor ID). Vendor delete is restricted.</p>
                        @endif
                    @endif
                @if ($canManageUsers || $canCreateVendorUsers)
                    <!-- User Creation Form (AJAX, with role/status) -->
                    <form class="manage-form" id="userCreateForm" autocomplete="off" style="margin-bottom:18px;background:#f7f7f7;padding:14px;border-radius:10px;">
                        @csrf
                        <div style="margin-bottom:8px;">
                            <label>Name</label>
                            <input name="name" required placeholder="Full Name">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>Email</label>
                            <input name="email" type="email" required placeholder="Email">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>Role</label>
                            <select name="portal_role" required>
                                @if ($canManageUsers)
                                    <option value="ADMIN">ADMIN</option>
                                    <option value="ADMIN_SUPER">ADMIN_SUPER</option>
                                    <option value="ADMIN_CARE">ADMIN_CARE</option>
                                    <option value="ADMIN_FINANCE">ADMIN_FINANCE</option>
                                    <option value="ADMIN_MEDIA">ADMIN_MEDIA</option>
                                @endif
                                <option value="VENDOR">VENDOR</option>
                            </select>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>Vendor ID</label>
                            <input name="portal_vendor_id" placeholder="Required for VENDOR">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>Status</label>
                            <select name="portal_enabled" required>
                                <option value="1">ACTIVE</option>
                                <option value="0">SUSPENDED</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit">Create User</button>
                        </div>
                    </form>
                    <div id="userCreateNotice" style="display:none;margin-bottom:12px;"></div>
                @else
                    <p class="small">Current role cannot create users.</p>
                @endif
                <!-- Existing Users Moderation -->
                @if ($canManageUsers)
                <p class="group-title">Admin Users ({{ $adminPortalUsers->count() }})</p>
                <div class="group-toolbar" data-group="admin">
                    <input class="group-search" type="search" id="adminUserSearch" placeholder="Search admin users by username, name, email, or role" data-target-list="adminUserList">
                    <label class="group-select-wrap" for="adminSelectAll">
                        <input class="group-select-all" type="checkbox" id="adminSelectAll" data-group="admin">
                        Select all visible
                    </label>
                    <form method="POST" action="/portal/admin/users/bulk-delete" class="bulk-delete-form" data-group="admin">
                        @csrf
                        <div class="bulk-user-ids" data-group="admin"></div>
                        <button class="bulk-delete-btn" type="submit" data-group="admin" disabled>Delete Selected (0)</button>
                    </form>
                </div>
                <div class="user-list" id="adminUserList">
                    @forelse ($adminPortalUsers as $managedUser)
                        <div class="user-row" data-user-id="{{ $managedUser->id }}">
                            <div class="user-head">
                                <input class="user-select" type="checkbox" data-group="admin" value="{{ $managedUser->id }}" aria-label="Select user {{ $managedUser->username ?: $managedUser->email }}">
                                <span class="user-name">{{ $managedUser->username ?: 'no-username' }}</span>
                                <span class="role-pill">{{ $managedUser->portal_role ?: 'NONE' }}</span>
                                <span class="small">{{ $managedUser->name }} | {{ $managedUser->email }}</span>
                                @if (!$managedUser->portal_enabled)
                                    <span class="state err">SUSPENDED</span>
                                @else
                                    <span class="state ok">ACTIVE</span>
                                @endif
                                <span style="flex:1 1 auto;"></span>
                                <button type="button" class="btn btn-secondary edit-user-btn" data-user-id="{{ $managedUser->id }}" style="margin-left:auto;">Edit</button>
                                @if ($canRequestVendorDeleteApproval)
                                    <form method="POST" action="/portal/admin/users/{{ $managedUser->id }}/delete" style="display:inline; margin-left:8px;">
                                        @csrf
                                        @method('DELETE')
                                        @if ($canApproveVendorDeleteRequest)
                                            <button type="submit" class="btn btn-secondary delete-user-btn" onclick="return confirm('Are you sure you want to delete this vendor user?');">Delete</button>
                                        @else
                                            <button type="submit" class="btn btn-secondary delete-user-btn" onclick="return confirm('Submit vendor delete request for ADMIN_SUPER approval?');">Request Delete Approval</button>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </div>
                        <!-- Only one edit-user-form per user, outside user-row, to avoid merge conflicts and ensure flat structure -->
                        <div class="edit-user-form" id="edit-user-form-{{ $managedUser->id }}" style="display:none;">
                            <form class="manage-form" method="POST" action="/portal/admin/users/{{ $managedUser->id }}/manage">
                                @csrf
                                <div>
                                    <label>Role</label>
                                    <select name="portal_role">
                                        <option value="ADMIN" @selected($managedUser->portal_role === 'ADMIN')>ADMIN</option>
                                        <option value="ADMIN_SUPER" @selected($managedUser->portal_role === 'ADMIN_SUPER')>ADMIN_SUPER</option>
                                        <option value="ADMIN_CARE" @selected($managedUser->portal_role === 'ADMIN_CARE')>ADMIN_CARE</option>
                                        <option value="ADMIN_FINANCE" @selected(in_array($managedUser->portal_role, ['ADMIN_FINANCE', 'ADMIN_FINACE'], true))>ADMIN_FINANCE</option>
                                        <option value="ADMIN_MEDIA" @selected($managedUser->portal_role === 'ADMIN_MEDIA')>ADMIN_MEDIA</option>
                                        <option value="VENDOR" @selected($managedUser->portal_role === 'VENDOR')>VENDOR</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Status</label>
                                    <select name="portal_enabled">
                                        <option value="1" @selected($managedUser->portal_enabled)>ACTIVE</option>
                                        <option value="0" @selected(!$managedUser->portal_enabled)>SUSPENDED</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vendor ID</label>
                                    <input name="portal_vendor_id" value="{{ $managedUser->portal_vendor_id ?? '' }}" placeholder="Required for VENDOR">
                                </div>
                                <div>
                                    <button type="submit">Save</button>
                                    <button type="button" class="btn btn-secondary cancel-edit-btn" data-user-id="{{ $managedUser->id }}">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="user-row">
                            <div class="small">No admin users found.</div>
                        </div>
                    @endforelse
                </div>
                @endif

                <p class="group-title">Vendor Users ({{ $vendorPortalUsers->count() }})</p>
                <div class="group-toolbar" data-group="vendor">
                    <input class="group-search" type="search" id="vendorUserSearch" placeholder="Search vendor users by username, name, email, or role" data-target-list="vendorUserList">
                    @if ($canRequestVendorDeleteApproval || $canApproveVendorDeleteRequest)
                        <label class="group-select-wrap" for="vendorSelectAll">
                            <input class="group-select-all" type="checkbox" id="vendorSelectAll" data-group="vendor">
                            Select all visible
                        </label>
                        <form method="POST" action="/portal/admin/users/bulk-delete" class="bulk-delete-form" data-group="vendor">
                            @csrf
                            <div class="bulk-user-ids" data-group="vendor"></div>
                            <button class="bulk-delete-btn" type="submit" data-group="vendor" disabled>Delete Selected (0)</button>
                        </form>
                    @endif
                </div>
                <div class="user-list" id="vendorUserList">
                    @forelse ($vendorPortalUsers as $managedUser)
                        <div class="user-row" data-user-id="{{ $managedUser->id }}">
                            <div class="user-head">
                                @if ($canRequestVendorDeleteApproval || $canApproveVendorDeleteRequest)
                                    <input class="user-select" type="checkbox" data-group="vendor" value="{{ $managedUser->id }}" aria-label="Select user {{ $managedUser->username ?: $managedUser->email }}">
                                @endif
                                <span class="user-name">{{ $managedUser->username ?: 'no-username' }}</span>
                                <span class="role-pill">{{ $managedUser->portal_role ?: 'NONE' }}</span>
                                <span class="small">{{ $managedUser->name }} | {{ $managedUser->email }}</span>
                                @if (!$managedUser->portal_enabled)
                                    <span class="state err">SUSPENDED</span>
                                @else
                                    <span class="state ok">ACTIVE</span>
                                @endif
                                <span style="flex:1 1 auto;"></span>
                                <button type="button" class="btn btn-secondary edit-user-btn" data-user-id="{{ $managedUser->id }}" style="margin-left:auto;">Edit</button>
                                <form method="POST" action="/portal/admin/users/{{ $managedUser->id }}/delete" style="display:inline; margin-left:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary delete-user-btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="edit-user-form" id="edit-user-form-{{ $managedUser->id }}" style="display:none;">
                            <form class="manage-form" method="POST" action="/portal/admin/users/{{ $managedUser->id }}/manage">
                                @csrf
                                @php
                                    $vendorVerificationStatus = strtolower(trim((string) ($managedUser->vendor_verification_status ?? 'pending')));
                                    $vendorApprovedRaw = $managedUser->vendor_approved_service_categories ?? '[]';
                                    $vendorApprovedDecoded = is_string($vendorApprovedRaw) ? json_decode($vendorApprovedRaw, true) : $vendorApprovedRaw;
                                    $vendorApprovedCategories = is_array($vendorApprovedDecoded) ? $vendorApprovedDecoded : [];
                                @endphp
                                <div>
                                    <label>Role</label>
                                    @if ($canManageUsers)
                                        <select name="portal_role">
                                            <option value="ADMIN" @selected($managedUser->portal_role === 'ADMIN')>ADMIN</option>
                                            <option value="ADMIN_SUPER" @selected($managedUser->portal_role === 'ADMIN_SUPER')>ADMIN_SUPER</option>
                                            <option value="ADMIN_CARE" @selected($managedUser->portal_role === 'ADMIN_CARE')>ADMIN_CARE</option>
                                            <option value="ADMIN_FINANCE" @selected(in_array($managedUser->portal_role, ['ADMIN_FINANCE', 'ADMIN_FINACE'], true))>ADMIN_FINANCE</option>
                                            <option value="ADMIN_MEDIA" @selected($managedUser->portal_role === 'ADMIN_MEDIA')>ADMIN_MEDIA</option>
                                            <option value="VENDOR" @selected($managedUser->portal_role === 'VENDOR')>VENDOR</option>
                                        </select>
                                    @else
                                        <input type="hidden" name="portal_role" value="VENDOR">
                                        <input value="VENDOR" readonly>
                                    @endif
                                </div>
                                <div>
                                    <label>Status</label>
                                    <select name="portal_enabled">
                                        <option value="1" @selected($managedUser->portal_enabled)>ACTIVE</option>
                                        <option value="0" @selected(!$managedUser->portal_enabled)>SUSPENDED</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vendor ID</label>
                                    <input name="portal_vendor_id" value="{{ $managedUser->portal_vendor_id ?? '' }}" placeholder="Required for VENDOR">
                                </div>
                                <div>
                                    <label>Verification Status</label>
                                    <select name="vendor_verification_status">
                                        <option value="pending" @selected($vendorVerificationStatus === 'pending')>PENDING</option>
                                        <option value="under_review" @selected($vendorVerificationStatus === 'under_review')>UNDER_REVIEW</option>
                                        <option value="approved" @selected($vendorVerificationStatus === 'approved')>APPROVED</option>
                                        <option value="rejected" @selected($vendorVerificationStatus === 'rejected')>REJECTED</option>
                                        <option value="suspended" @selected($vendorVerificationStatus === 'suspended')>SUSPENDED</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Approved Service Categories</label>
                                    <div class="category-grid" style="margin-top:6px;">
                                        @foreach (($vendorCategoryMap ?? []) as $categoryKey => $categoryLabel)
                                            <label class="category-item" style="min-height:auto; padding:6px 8px;">
                                                <input type="checkbox" name="vendor_approved_service_categories[]" value="{{ $categoryKey }}" @checked(in_array($categoryKey, $vendorApprovedCategories, true))>
                                                <span>{{ $categoryLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <label>Verification Notes</label>
                                    <textarea name="vendor_verification_notes" rows="3" placeholder="Explain what was checked, what is missing, and approval conditions.">{{ old('vendor_verification_notes', (string) ($managedUser->vendor_verification_notes ?? '')) }}</textarea>
                                </div>
                                <div>
                                    <label>Cross-check Validation</label>
                                    <div class="category-grid" style="margin-top:6px;">
                                        <label class="category-item" style="min-height:auto; padding:6px 8px;">
                                            <input type="checkbox" name="crosscheck_business_profile" value="1">
                                            <span>Business profile docs checked</span>
                                        </label>
                                        <label class="category-item" style="min-height:auto; padding:6px 8px;">
                                            <input type="checkbox" name="crosscheck_service_profile" value="1">
                                            <span>Service capability checked</span>
                                        </label>
                                        <label class="category-item" style="min-height:auto; padding:6px 8px;">
                                            <input type="checkbox" name="crosscheck_id_proof" value="1">
                                            <span>Contact ID proof checked</span>
                                        </label>
                                        <label class="category-item" style="min-height:auto; padding:6px 8px;">
                                            <input type="checkbox" name="sole_proprietor_name_override" value="1">
                                            <span>Sole proprietor personal-name override</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label>Rejection Reason</label>
                                    <textarea name="vendor_rejection_reason" rows="2" placeholder="Required when status is REJECTED.">{{ old('vendor_rejection_reason', (string) ($managedUser->vendor_verification_rejection_reason ?? '')) }}</textarea>
                                </div>
                                <div>
                                    <label>Missing Documents</label>
                                    <textarea name="vendor_missing_documents" rows="2" placeholder="List one document per line or comma-separated.">{{ old('vendor_missing_documents', (string) ($managedUser->vendor_verification_missing_documents ?? '')) }}</textarea>
                                </div>
                                <div>
                                    <label>Contact Person Verification</label>
                                    <select name="vendor_contact_verified">
                                        <option value="0" @selected(empty($managedUser->vendor_contact_verified_at))>NOT VERIFIED</option>
                                        <option value="1" @selected(!empty($managedUser->vendor_contact_verified_at))>VERIFIED</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit">Save</button>
                                    <button type="button" class="btn btn-secondary cancel-edit-btn" data-user-id="{{ $managedUser->id }}">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="user-row">
                            <div class="small">No vendor users found.</div>
                        </div>
                    @endforelse
                </div>
                @else
                    <p class="small">User create/edit/delete remains restricted to Super Admin and Admin vendor-management scope.</p>
                @endif
            @endif
        </section>

        @if ($canModerateListings)
        <section class="card manage" id="listingModerationPanel" style="{{ $adminPage === 'listings' ? 'margin-top:14px;' : 'display:none;' }}">
                        <p class="label">Listing Moderation</p>
                        <p class="small">Review and approve or reject individual vendor listings. Only approved listings are open for guest bookings. ADMIN_FINANCE cannot access this panel.</p>

                        <p class="group-title">Pending Review ({{ count($pendingModerationListings) }})</p>
                        <div class="registration-grid" style="margin-bottom:16px;">
                            @forelse ($pendingModerationListings as $listing)
                                <div class="registration-row">
                                    <div class="registration-head">
                                        <span class="user-name">{{ $listing->listing_name ?: ('Listing #' . $listing->id) }}</span>
                                        <span class="role-pill">PENDING REVIEW</span>
                                        <span class="small">Vendor: {{ $listing->vendor_name ?: $listing->vendor_email ?: 'Unknown' }}</span>
                                    </div>
                                    <div class="small">Category: {{ ucwords(str_replace('_', ' ', (string) ($listing->listing_category ?: 'general'))) }}</div>
                                    @if (!empty($listing->listing_submitted_for_review_at))
                                        <div class="small">Submitted: {{ \Illuminate\Support\Carbon::parse($listing->listing_submitted_for_review_at)->format('Y-m-d H:i') }}</div>
                                    @endif
                                    <div class="small" style="margin-top:4px;">
                                        <a href="/portal/admin/listings/{{ $listing->id }}/preview?category={{ urlencode((string) ($listing->listing_category ?: '')) }}" target="_blank" rel="noopener">Open Pre-Approval Preview</a>
                                    </div>
                                    <div class="registration-actions">
                                        <form method="POST" action="/portal/admin/listings/{{ $listing->id }}/approve">
                                            @csrf
                                            <input type="hidden" name="listing_category" value="{{ (string) ($listing->listing_category ?: '') }}">
                                            <label class="small" for="approve_listing_notes_{{ $listing->id }}">Approval notes (optional)</label>
                                            <textarea id="approve_listing_notes_{{ $listing->id }}" name="admin_notes" placeholder="Internal notes for this approval"></textarea>
                                            <button class="btn-approve" type="submit">Approve Listing</button>
                                        </form>
                                        <form method="POST" action="/portal/admin/listings/{{ $listing->id }}/reject">
                                            @csrf
                                            <input type="hidden" name="listing_category" value="{{ (string) ($listing->listing_category ?: '') }}">
                                            <label class="small" for="reject_listing_notes_{{ $listing->id }}">Rejection reason <span style="color:red">*</span></label>
                                            <textarea id="reject_listing_notes_{{ $listing->id }}" name="admin_notes" required placeholder="Explain what must be resolved before the listing can be approved"></textarea>
                                            <label class="small" for="reject_listing_missing_docs_{{ $listing->id }}">Missing documents (optional)</label>
                                            <textarea id="reject_listing_missing_docs_{{ $listing->id }}" name="missing_documents" placeholder="List missing documents if any are required to proceed"></textarea>
                                            <button class="btn-reject" type="submit">Reject Listing</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="user-row">
                                    <div class="small">No listings pending review.</div>
                                </div>
                            @endforelse
                        </div>

                        <p class="group-title" id="listingModerationHistoryPanel">Moderation History ({{ count($listingModerationHistory) }})</p>
                        <div class="registration-grid">
                            @forelse ($listingModerationHistory as $listing)
                                <div class="registration-row">
                                    <div class="registration-head">
                                        <span class="user-name">{{ $listing->listing_name ?: ('Listing #' . $listing->id) }}</span>
                                        @php
                                            $histChipClass = match(strtolower((string) ($listing->listing_moderation_status ?? ''))) {
                                                'approved' => 'ok',
                                                'rejected', 'suspended' => 'err',
                                                default => 'warn',
                                            };
                                        @endphp
                                        <span class="state {{ $histChipClass }}">{{ strtoupper((string) ($listing->listing_moderation_status ?? 'unknown')) }}</span>
                                        <span class="small">Vendor: {{ $listing->vendor_name ?: $listing->vendor_email ?: 'Unknown' }}</span>
                                    </div>
                                    <div class="small">Category: {{ ucwords(str_replace('_', ' ', (string) ($listing->listing_category ?: 'general'))) }}</div>
                                    <div class="small" style="margin-top:4px;">
                                        <a href="/portal/admin/listings/{{ $listing->id }}/preview?category={{ urlencode((string) ($listing->listing_category ?: '')) }}" target="_blank" rel="noopener">Open Listing Preview</a>
                                    </div>
                                    @if (!empty($listing->listing_approved_at))
                                        <div class="small">Actioned: {{ \Illuminate\Support\Carbon::parse($listing->listing_approved_at)->format('Y-m-d H:i') }} by {{ $listing->approved_by_name ?: 'Unknown' }}</div>
                                    @endif
                                    @if (!empty($listing->listing_admin_notes))
                                        <div class="small">Notes: {{ $listing->listing_admin_notes }}</div>
                                    @endif
                                </div>
                            @empty
                                <div class="user-row">
                                    <div class="small">No listing moderation history yet.</div>
                                </div>
                            @endforelse
                        </div>
                    </section>
        @endif

        <section class="card" id="auditPanel" style="{{ $adminPage === 'audit' ? 'margin-top:14px;' : 'display:none;' }}">
            <p class="label">Admin Activity History</p>

            <p class="small">Latest moderation actions performed from this admin portal.</p>
            <div class="audit-list">
                @forelse ($auditLogs as $auditLog)
                    <div class="audit-row">
                        <div class="audit-when">{{ \Illuminate\Support\Carbon::parse($auditLog->created_at)->format('Y-m-d H:i:s') }}</div>
                        <div class="audit-actor">{{ $auditLog->actor_name ?: 'Unknown Actor' }}<br><span class="small">{{ $auditLog->actor_role ?: 'UNKNOWN' }}</span></div>
                        <div class="audit-details">
                            <strong>{{ strtoupper(str_replace('_', ' ', (string) $auditLog->action)) }}</strong>
                            @if (!empty($auditLog->target_identifier))
                                · target: {{ $auditLog->target_identifier }}
                            @endif
                            @if (!empty($auditLog->target_role))
                                ({{ $auditLog->target_role }})
                            @endif
                            @if (!empty($auditLog->details))
                                <div class="small">{{ \Illuminate\Support\Str::limit((string) $auditLog->details, 280) }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="audit-empty">No audit entries yet. Actions will appear here after user create/update/delete operations.</div>
                @endforelse
            </div>
        </section>

        </div>
        </div>
        @include('partials.global-site-footer')
    </main>

    <script>
    // User creation AJAX logic (with role/status)
    document.addEventListener('DOMContentLoaded', function () {
        var userCreateForm = document.getElementById('userCreateForm');
        var userCreateNotice = document.getElementById('userCreateNotice');
        if (userCreateForm) {
            userCreateForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                userCreateNotice.style.display = 'none';
                userCreateNotice.textContent = '';
                const name = userCreateForm.elements['name'].value.trim();
                const email = userCreateForm.elements['email'].value.trim();
                const portal_role = userCreateForm.elements['portal_role'].value;
                const portal_enabled = userCreateForm.elements['portal_enabled'].value;
                if (!name || !email || !portal_role || !portal_enabled) {
                    userCreateNotice.style.display = 'block';
                    userCreateNotice.style.color = '#b91c1c';
                    userCreateNotice.textContent = 'All fields are required.';
                    return;
                }
                try {
                    const csrfToken = userCreateForm.querySelector('input[name="_token"]')?.value || '';
                    const res = await fetch('/portal/admin/users/create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, email, portal_role, portal_enabled })
                    });
                    if (res.ok) {
                        const data = await res.json().catch(() => ({}));
                        userCreateNotice.style.display = 'block';
                        userCreateNotice.style.color = '#166534';
                        userCreateNotice.textContent = data.message || 'User created successfully.';
                        userCreateForm.reset();
                    } else {
                        const data = await res.json().catch(() => ({}));
                        const errors = data.errors ? Object.values(data.errors).flat() : [];
                        userCreateNotice.style.display = 'block';
                        userCreateNotice.style.color = '#b91c1c';
                        userCreateNotice.textContent = errors[0] || data.message || 'Failed to create user.';
                    }
                } catch (err) {
                    userCreateNotice.style.display = 'block';
                    userCreateNotice.style.color = '#b91c1c';
                    userCreateNotice.textContent = 'Network error.';
                }
            });
        }
    });
                // User list edit/cancel logic
                document.addEventListener('DOMContentLoaded', function () {
                    var editButtons = document.querySelectorAll('.edit-user-btn');
                    var cancelButtons = document.querySelectorAll('.cancel-edit-btn');
                    var forms = document.querySelectorAll('.edit-user-form');
                    editButtons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var userId = btn.getAttribute('data-user-id');
                            // Hide all forms first
                            forms.forEach(function(f) { f.style.display = 'none'; });
                            // Show only this user's form
                            var form = document.getElementById('edit-user-form-' + userId);
                            if (form) form.style.display = 'block';
                        });
                    });
                    cancelButtons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var userId = btn.getAttribute('data-user-id');
                            var form = document.getElementById('edit-user-form-' + userId);
                            if (form) form.style.display = 'none';
                        });
                    });
                });
        // Toggle moderation panel on button click
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('toggleModerationBtn');
            var panel = document.getElementById('moderationPanel');
            if (btn && panel) {
                btn.addEventListener('click', function () {
                    if (panel.style.display === 'none') {
                        panel.style.display = 'block';
                        btn.textContent = 'Hide Moderation Panel';
                    } else {
                        panel.style.display = 'none';
                        btn.textContent = 'Show Moderation Panel';
                    }
                });
            }
        });
                // Ensure feedback messages are always visible and scroll into view
                window.addEventListener('DOMContentLoaded', function () {
                    var successBox = document.getElementById('successBox');
                    var errorBox = document.getElementById('errorBox');
                    if (successBox) {
                        successBox.style.display = 'block';
                        successBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (errorBox) {
                        errorBox.style.display = 'block';
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                // Client-side validation for moderation form
                document.querySelectorAll('.manage-form').forEach(function(form) {
                    form.addEventListener('submit', function(e) {
                        var role = form.querySelector('[name="portal_role"]') ? form.querySelector('[name="portal_role"]').value : null;
                        var enabled = form.querySelector('[name="portal_enabled"]') ? form.querySelector('[name="portal_enabled"]').value : null;
                        var vendorId = form.querySelector('[name="portal_vendor_id"]') ? form.querySelector('[name="portal_vendor_id"]').value : null;
                        if (!role || !enabled) {
                            e.preventDefault();
                            alert('Role and status are required.');
                            return false;
                        }
                        if (role === 'VENDOR' && !vendorId.trim()) {
                            e.preventDefault();
                            alert('Vendor ID is required for VENDOR role.');
                            return false;
                        }
                    });
                });

                document.addEventListener('DOMContentLoaded', function () {
                    var navLinks = document.querySelectorAll('[data-open-panel]');
                    navLinks.forEach(function (link) {
                        link.addEventListener('click', function () {
                            var panelId = link.getAttribute('data-open-panel');
                            var buttonId = link.getAttribute('data-toggle-button');
                            var panel = panelId ? document.getElementById(panelId) : null;
                            var button = buttonId ? document.getElementById(buttonId) : null;
                            if (panel && panel.style.display === 'none') {
                                panel.style.display = 'block';
                                if (button) {
                                    button.textContent = 'Hide Moderation Panel';
                                }
                            }
                        });
                    });
                });

                // Search + bulk selection/delete controls for grouped user lists.
                document.addEventListener('DOMContentLoaded', function () {
                    var groups = ['admin', 'vendor'];

                    function getVisibleCheckboxes(group) {
                        return Array.from(document.querySelectorAll('.user-select[data-group="' + group + '"]')).filter(function (checkbox) {
                            var row = checkbox.closest('.user-row');
                            return row && row.style.display !== 'none';
                        });
                    }

                    function updateBulkState(group) {
                        var selected = getVisibleCheckboxes(group).filter(function (checkbox) {
                            return checkbox.checked;
                        });
                        var button = document.querySelector('.bulk-delete-btn[data-group="' + group + '"]');
                        var selectAll = document.querySelector('.group-select-all[data-group="' + group + '"]');
                        var visible = getVisibleCheckboxes(group);
                        if (button) {
                            button.disabled = selected.length === 0;
                            button.textContent = 'Delete Selected (' + selected.length + ')';
                        }
                        if (selectAll) {
                            selectAll.checked = visible.length > 0 && selected.length === visible.length;
                            selectAll.indeterminate = selected.length > 0 && selected.length < visible.length;
                        }
                    }

                    function renderBulkUserIds(group) {
                        var holder = document.querySelector('.bulk-user-ids[data-group="' + group + '"]');
                        if (!holder) return;
                        var selectedIds = getVisibleCheckboxes(group)
                            .filter(function (checkbox) { return checkbox.checked; })
                            .map(function (checkbox) { return checkbox.value; });
                        holder.innerHTML = selectedIds.map(function (id) {
                            return '<input type="hidden" name="user_ids[]" value="' + id + '">';
                        }).join('');
                    }

                    function applyFilter(group, term) {
                        var q = (term || '').toLowerCase();
                        var rows = document.querySelectorAll('#' + group + 'UserList .user-row[data-user-id]');
                        rows.forEach(function (row) {
                            var text = (row.textContent || '').toLowerCase();
                            var visible = !q || text.indexOf(q) !== -1;
                            row.style.display = visible ? '' : 'none';
                            if (!visible) {
                                var checkbox = row.querySelector('.user-select[data-group="' + group + '"]');
                                if (checkbox) checkbox.checked = false;
                            }
                        });
                        renderBulkUserIds(group);
                        updateBulkState(group);
                    }

                    groups.forEach(function (group) {
                        var searchInput = document.querySelector('.group-search[data-target-list="' + group + 'UserList"]');
                        var selectAll = document.querySelector('.group-select-all[data-group="' + group + '"]');
                        var form = document.querySelector('.bulk-delete-form[data-group="' + group + '"]');

                        if (searchInput) {
                            searchInput.addEventListener('input', function () {
                                applyFilter(group, searchInput.value);
                            });
                        }

                        document.querySelectorAll('.user-select[data-group="' + group + '"]').forEach(function (checkbox) {
                            checkbox.addEventListener('change', function () {
                                renderBulkUserIds(group);
                                updateBulkState(group);
                            });
                        });

                        if (selectAll) {
                            selectAll.addEventListener('change', function () {
                                var shouldCheck = !!selectAll.checked;
                                getVisibleCheckboxes(group).forEach(function (checkbox) {
                                    checkbox.checked = shouldCheck;
                                });
                                renderBulkUserIds(group);
                                updateBulkState(group);
                            });
                        }

                        if (form) {
                            form.addEventListener('submit', function (e) {
                                renderBulkUserIds(group);
                                var selectedCount = getVisibleCheckboxes(group).filter(function (checkbox) {
                                    return checkbox.checked;
                                }).length;
                                if (selectedCount === 0) {
                                    e.preventDefault();
                                    return;
                                }
                                if (!window.confirm('Are you sure you want to delete ' + selectedCount + ' selected user(s)?')) {
                                    e.preventDefault();
                                }
                            });
                        }

                        updateBulkState(group);
                    });
                });

        (function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const tokenMeta = document.getElementById("tokenMeta");
            const output = document.getElementById("output");

            const SESSION_KEY = "workation_admin_token";

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
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
                setMeta("Token removed from this tab session.");
            }

            async function run(path, triggerButton) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    setMeta("Save an admin token before running requests.");
                    output.textContent = "Save an admin token first.";
                    return;
                }

                const verdict = evaluateToken(token);
                if (!verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    output.textContent = "Token is expired or invalid. Save a fresh admin token first.";
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
                        setState("err", "TOKEN INVALID FOR ADMIN");
                        setMeta("The API rejected this token for admin access.");
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
            } else {
                setMeta("Token is stored only in this browser tab session.");
            }
        })();
    </script>
</body>
</html>