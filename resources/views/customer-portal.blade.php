<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --bg: #f0f2f5;
            --ink: #16212e;
            --muted: #6b7a8a;
            --card: #ffffff;
            --line: #e0e6ed;
            --brand: #0f6179;
            --brand-soft: #e8f4f9;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
            min-height: 100vh;
        }

        /* ── Layout ──────────────────────────────────────────────── */
        .portal-wrap {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        .portal-sidebar {
            width: 248px;
            flex-shrink: 0;
            background: #ffffff;
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
        }

        .sidebar-header {
            padding: 18px 16px 14px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: #ddeef9;
            color: #1d5a7e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .sidebar-member-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a2f41;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-member-email {
            font-size: 0.72rem;
            color: #7a90a4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav {
            padding: 8px 0;
            flex: 1;
            overflow-y: auto;
        }

        /* Collapsible nav group */
        .nav-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 16px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #1a2f41;
            cursor: pointer;
            user-select: none;
            border: none;
            background: none;
            width: 100%;
            font-family: inherit;
        }

        .nav-group-header:hover { background: #f4f8fc; }

        .nav-chevron {
            color: #8a9aaa;
            font-size: 0.7rem;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .nav-group.is-open .nav-chevron { transform: rotate(180deg); }

        .nav-group-body { display: none; }
        .nav-group.is-open .nav-group-body { display: block; }

        /* Sub-links inside a group */
        .nav-link {
            display: block;
            padding: 7px 16px 7px 32px;
            font-size: 0.81rem;
            color: #4a6275;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            border-left: 3px solid transparent;
        }

        .nav-link:hover { background: #f4f8fc; color: #0f5f79; }

        .nav-link.is-active {
            background: #ebf4fb;
            color: #0a4a65;
            font-weight: 700;
            border-left-color: #0f6179;
        }

        /* Top-level standalone nav items */
        .nav-item {
            display: block;
            padding: 9px 16px;
            font-size: 0.84rem;
            color: #1a2f41;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-weight: 600;
        }

        .nav-item:hover { background: #f4f8fc; }

        .nav-item.is-active {
            background: #ebf4fb;
            color: #0a4a65;
            font-weight: 700;
        }

        .nav-divider {
            height: 1px;
            background: var(--line);
            margin: 6px 8px;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--line);
            display: grid;
            gap: 8px;
        }

        .sidebar-home-link {
            display: block;
            width: 100%;
            border: 1px solid #cfe0eb;
            border-radius: 8px;
            padding: 8px 12px;
            background: #f5fbff;
            color: #1f5877;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            text-align: left;
        }

        .sidebar-home-link:hover {
            background: #ebf4fb;
            border-color: #b7d3e2;
            color: #0d4f6d;
        }

        .sidebar-logout {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 12px;
            background: #ffffff;
            color: #3d5870;
            font-size: 0.82rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
        }

        .sidebar-logout:hover {
            background: #fff5f5;
            border-color: #f0b4b4;
            color: #7b2525;
        }

        /* ── Main content ────────────────────────────────────────── */
        .portal-main {
            flex: 1;
            min-width: 0;
            padding: 24px 28px 48px;
        }

        /* ── Portal sections ─────────────────────────────────────── */
        .portal-section { display: none; }
        .portal-section.is-active { display: block; }

        /* ── Section header row ──────────────────────────────────── */
        .section-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .section-title-row h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #16212e;
        }

        .title-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #c8e4f5;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.76rem;
            color: #1a5a7a;
            background: #e8f4fb;
        }

        .title-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title-action-link {
            font-size: 0.8rem;
            color: #1a6abf;
            text-decoration: none;
        }

        .title-action-link:hover { text-decoration: underline; }

        .title-divider { color: #c0ccda; }

        /* ── Booking status tabs ─────────────────────────────────── */
        .booking-tabs {
            display: flex;
            border-bottom: 2px solid var(--line);
            margin-bottom: 16px;
        }

        .booking-tab {
            padding: 10px 20px;
            font-size: 0.84rem;
            font-weight: 600;
            color: #5a6a7a;
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
        }

        .booking-tab:hover { color: #1a2f41; }

        .booking-tab.is-active {
            color: #0d4f6a;
            border-bottom-color: #0f6179;
        }

        /* ── Category filter pills ───────────────────────────────── */
        .category-filter-bar {
            display: flex;
            gap: 2px;
            overflow-x: auto;
            background: #e4eaf0;
            border-radius: 10px;
            padding: 3px;
            margin-bottom: 16px;
            scrollbar-width: none;
        }

        .category-filter-bar::-webkit-scrollbar { display: none; }

        .category-filter-btn {
            white-space: nowrap;
            padding: 7px 13px;
            border: none;
            background: transparent;
            color: #4a6278;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 7px;
            font-family: inherit;
            flex-shrink: 0;
        }

        .category-filter-btn:hover { background: #ffffff88; }

        .category-filter-btn.is-active {
            background: #ffffff;
            color: #0d4a65;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        /* ── Booking cards ───────────────────────────────────────── */
        .booking-list { display: grid; gap: 12px; }

        .booking-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .booking-card-meta-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 0.78rem;
            color: #5a6a7a;
            gap: 8px;
            flex-wrap: wrap;
        }

        .booking-number-link {
            color: #1a6abf;
            text-decoration: none;
            font-weight: 700;
        }

        .booking-number-link:hover { text-decoration: underline; }

        .booking-status-badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 11px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .bs-cancelled  { background: #fff0f0; color: #a33030; border: 1px solid #f5c0c0; }
        .bs-cancel-requested { background: #fff7ea; color: #8b5a00; border: 1px solid #f1d19d; }
        .bs-confirmed  { background: #e8f8ef; color: #1a6e3a; border: 1px solid #a8dfc0; }
        .bs-pending    { background: #fff6e0; color: #7a5c00; border: 1px solid #f5d98a; }
        .bs-completed  { background: #f0f2f5; color: #4a5a6a; border: 1px solid #c8d4df; }

        .booking-card-body {
            display: grid;
            grid-template-columns: 104px 1fr;
        }

        .booking-card-thumb-wrap {
            width: 104px;
            border-right: 1px solid var(--line);
            background: linear-gradient(135deg, #d7e8f5 0%, #c6dded 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7a9ab4;
            font-size: 1.8rem;
            min-height: 90px;
        }

        .booking-card-thumb-wrap img {
            width: 104px;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .booking-card-info {
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .booking-card-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .booking-card-property {
            font-size: 1rem;
            font-weight: 700;
            color: #16212e;
        }

        .booking-card-price {
            font-size: 1rem;
            font-weight: 700;
            color: #16212e;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .booking-card-meta {
            font-size: 0.8rem;
            color: #5a6a7a;
            line-height: 1.45;
        }

        .booking-card-guest {
            font-size: 0.8rem;
            color: #3d5870;
            font-weight: 600;
        }

        .booking-message-thread {
            margin-top: 10px;
            border: 1px solid #d1e4f0;
            border-radius: 12px;
            background: #f6fbff;
            overflow: hidden;
        }

        .booking-message-thread-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 6px;
            padding: 9px 12px;
            background: #e8f3fb;
            border-bottom: 1px solid #d1e4f0;
        }

        .booking-message-thread-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #13425e;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .booking-message-policy-badge {
            font-size: 0.7rem;
            font-weight: 600;
            color: #2e6e40;
            background: #e4f5eb;
            border: 1px solid #b6dfc4;
            border-radius: 999px;
            padding: 2px 8px;
            white-space: nowrap;
        }

        .booking-message-policy-notice {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px 12px;
            background: #fff8ec;
            border-bottom: 1px solid #ffe4a0;
            font-size: 0.75rem;
            color: #5c3d00;
            line-height: 1.4;
        }

        .booking-message-policy-notice i {
            color: #c7800a;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .booking-message-list {
            padding: 10px 12px;
            display: grid;
            gap: 8px;
            max-height: 320px;
            overflow-y: auto;
        }

        .booking-message-empty {
            font-size: 0.77rem;
            color: #8aa2b3;
            text-align: center;
            padding: 8px 0;
        }

        .booking-msg {
            display: grid;
            gap: 2px;
            padding: 8px 10px;
            border-radius: 8px;
            max-width: 85%;
            font-size: 0.77rem;
        }

        .booking-msg--mine {
            background: #e0f0fb;
            border: 1px solid #c2ddf0;
            margin-left: auto;
            text-align: right;
        }

        .booking-msg--theirs {
            background: #fff;
            border: 1px solid #dce9f2;
            margin-right: auto;
        }

        .booking-msg--flagged {
            border-color: #e8b4b4;
            background: #fff5f5;
        }

        .booking-msg-sender {
            font-weight: 700;
            color: #174b6a;
            font-size: 0.72rem;
        }

        .booking-msg-text {
            color: #2b4558;
            line-height: 1.45;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .booking-msg-time {
            font-size: 0.68rem;
            color: #8aa2b3;
        }

        .booking-msg-report-form {
            margin: 0;
            padding: 0;
        }

        .booking-msg-report-btn {
            background: none;
            border: none;
            color: #b03030;
            font-size: 0.68rem;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .booking-msg-report-btn:hover {
            text-decoration: underline;
        }

        .booking-message-compose {
            padding: 10px 12px;
            border-top: 1px solid #d1e4f0;
            display: grid;
            gap: 8px;
        }

        .booking-message-textarea {
            width: 100%;
            border: 1px solid #c5daea;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.77rem;
            resize: vertical;
            color: #2b4558;
            background: #fff;
            box-sizing: border-box;
        }

        .booking-message-textarea:focus {
            outline: none;
            border-color: #3a8ec9;
        }

        .booking-message-compose-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .booking-message-compose-note {
            font-size: 0.68rem;
            color: #8aa2b3;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .booking-message-send-btn {
            font-size: 0.77rem;
            padding: 6px 16px;
        }

        .message-center-list {
            display: grid;
            gap: 14px;
        }

        .message-center-card {
            border: 1px solid #d4e3ef;
            border-radius: 12px;
            background: #f9fcff;
            overflow: hidden;
        }

        .message-center-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px 14px;
            background: #eef5fb;
            border-bottom: 1px solid #d4e3ef;
        }

        .message-center-subject {
            font-size: 0.84rem;
            font-weight: 700;
            color: #193a52;
        }

        .message-center-meta {
            font-size: 0.76rem;
            color: #5a748a;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .message-center-jump {
            font-size: 0.72rem;
            color: #0f5f79;
            font-weight: 700;
            text-decoration: none;
        }

        .message-center-jump:hover {
            text-decoration: underline;
        }

        .booking-refund-timeline {
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid #dbe6ef;
            border-radius: 10px;
            background: #f8fbfd;
        }

        .booking-refund-timeline-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #16354b;
            margin-bottom: 8px;
        }

        .booking-refund-timeline-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .booking-refund-timeline-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 0.78rem;
            color: #486176;
        }

        .booking-refund-timeline-item strong {
            color: #1a3244;
        }

        .booking-refund-timeline-empty {
            color: #7a8d9d;
            font-style: italic;
        }

        .booking-refund-note {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fff4ea;
            border: 1px solid #f0d0ae;
            color: #76451f;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .booking-card-actions {
            padding: 10px 16px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            border-top: 1px solid var(--line);
            flex-wrap: wrap;
        }

        .btn-outline {
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 6px 16px;
            background: white;
            color: #3d5870;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline:hover { border-color: #a8c0d0; background: #f5faff; }

        .btn-brand {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 6px 16px;
            background: linear-gradient(135deg, #0f6179 0%, #1e7d90 100%);
            color: white;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
        }

        .btn-brand:hover { opacity: 0.92; }

        .booking-empty {
            padding: 40px 20px;
            text-align: center;
            color: #7a8a9a;
            background: #f9fbfd;
            border: 1px dashed #d0dce8;
            border-radius: 10px;
        }

        .booking-empty i { font-size: 2rem; color: #b0c0d0; display: block; margin-bottom: 10px; }

        /* ── My Cards ────────────────────────────────────────────── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }

        .payment-card {
            background: linear-gradient(135deg, #1e3a56 0%, #0f5f7c 100%);
            border-radius: 14px;
            padding: 20px;
            color: #ecf6ff;
            min-height: 148px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .payment-card-chip { font-size: 1.1rem; opacity: 0.85; }
        .payment-card-network { position: absolute; top: 16px; right: 16px; font-size: 1rem; opacity: 0.7; }
        .payment-card-number { font-size: 1rem; letter-spacing: 0.15em; font-weight: 700; margin-top: 12px; }
        .payment-card-row { display: flex; justify-content: space-between; font-size: 0.75rem; opacity: 0.7; margin-top: 6px; }

        .add-card-tile {
            border: 2px dashed #c8d8e8;
            border-radius: 14px;
            padding: 20px;
            min-height: 148px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #5a7a94;
            cursor: pointer;
            background: #f8fbff;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .add-card-tile:hover { border-color: #8ab4cc; background: #eef6fc; }

        /* ── Generic section card ────────────────────────────────── */
        .section-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 20px;
        }

        .section-card h3 {
            font-size: 1rem;
            color: #1a2f41;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }

        /* ── Form layout ─────────────────────────────────────────── */
        .profile-avatar-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
        }

        .profile-avatar-large {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            background: linear-gradient(135deg, #d4ecfa 0%, #c8e4f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a5070;
            flex-shrink: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-field { display: grid; gap: 5px; }
        .form-field.full-width { grid-column: 1 / -1; }

        .form-field label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #3a5060;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            border: 1px solid #c8d8e4;
            border-radius: 8px;
            padding: 9px 11px;
            font-size: 0.88rem;
            font-family: inherit;
            color: #1a2f41;
            background: #f9fbff;
        }

        .form-footer {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* ── Empty states ────────────────────────────────────────── */
        .empty-state {
            padding: 44px 20px;
            text-align: center;
            color: #7a8a9a;
            background: #f9fbfd;
            border: 1px dashed #d0dce8;
            border-radius: 10px;
        }

        .empty-state i { font-size: 2.2rem; color: #b0c0d0; display: block; margin-bottom: 12px; }
        .empty-state p { font-size: 0.88rem; }

        /* ── Subscriptions ───────────────────────────────────────── */
        .pref-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: #2a4060;
            cursor: pointer;
            padding: 8px 0;
            border-bottom: 1px solid #f0f4f8;
        }

        .pref-row:last-child { border-bottom: none; }
        .pref-row input[type="checkbox"] { width: 18px; height: 18px; accent-color: #0f6179; flex-shrink: 0; }

        /* ── No-auth banner ──────────────────────────────────────── */
        .no-auth-banner {
            background: white;
            border: 1px solid #c8d8f0;
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            margin-bottom: 24px;
        }

        .no-auth-banner h2 { font-size: 1.2rem; color: #1a2f41; margin-bottom: 8px; }
        .no-auth-banner p  { color: #5a7088; font-size: 0.9rem; margin-bottom: 16px; }
        .no-auth-banner .btn-row { display: flex; gap: 10px; justify-content: center; }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 860px) {
            .portal-sidebar { width: 220px; }
            .portal-main { padding: 16px 16px 32px; }
        }

        @media (max-width: 680px) {
            .portal-wrap { flex-direction: column; }
            .portal-sidebar { width: 100%; height: auto; position: static; }
            .booking-card-body { grid-template-columns: 80px 1fr; }
            .booking-card-thumb-wrap { width: 80px; }
            .form-grid { grid-template-columns: 1fr; }
            .section-title-row h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    @php
        $customerBookingsByCategory = collect($customerBookingsByCategory ?? []);
        $bookingCategoryMeta        = collect($bookingCategoryMeta ?? []);
        $allBookings                = collect($allBookings ?? $customerBookingsByCategory->flatten(1)->sortByDesc('created_at')->values());
        $bookingStatusCounts        = $bookingStatusCounts ?? ['all' => 0, 'awaiting_payment' => 0, 'upcoming' => 0, 'awaiting_review' => 0];
        $customerProfile            = is_array($customerProfile ?? null) ? $customerProfile : [];
        $customerLoggedIn           = (bool) session('portal_customer_authenticated', false);
        $customerName               = trim((string) session('portal_customer_user', 'Customer'));
        $profileName                = trim((string) ($customerProfile['name'] ?? $customerName));
        $profileEmail               = trim((string) ($customerProfile['email'] ?? ''));
        $profileMemberSince         = trim((string) ($customerProfile['member_since'] ?? '-'));
        $profileInitials            = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $profileName), 0, 2) ?: 'CU');

        $today = now()->startOfDay();

        $awaitingPaymentBookings = $allBookings->filter(
            fn ($b) => strtolower((string) ($b['payment_status'] ?? '')) === 'unpaid'
                    && !in_array(strtolower((string) ($b['status'] ?? '')), ['cancelled', 'canceled'])
        )->values();

        $upcomingBookings = $allBookings->filter(
            fn ($b) => $b['start_at'] !== '-'
                    && \Carbon\Carbon::parse((string) $b['start_at'])->startOfDay()->greaterThanOrEqualTo($today)
        )->values();

        $awaitingReviewBookings = $allBookings->filter(
            fn ($b) => !in_array(strtolower((string) ($b['status'] ?? '')), ['pending', 'cancelled', 'canceled'])
                    && ($b['end_at'] === '-' || \Carbon\Carbon::parse((string) $b['end_at'])->isPast())
        )->values();

        $bookingsByTab = [
            'all'              => $allBookings,
            'awaiting_payment' => $awaitingPaymentBookings,
            'upcoming'         => $upcomingBookings,
            'awaiting_review'  => $awaitingReviewBookings,
        ];

        $statusTabDefs = [
            'all'              => 'All',
            'awaiting_payment' => 'Awaiting Payment',
            'upcoming'         => 'Upcoming',
            'awaiting_review'  => 'Awaiting Review',
        ];
    @endphp

    <div class="portal-wrap">

        {{-- ───────────── Sidebar ──────────────────────────────── --}}
        <aside class="portal-sidebar" aria-label="Customer navigation">

            <div class="sidebar-header">
                <div class="sidebar-avatar" aria-hidden="true">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div style="min-width:0;">
                    <div class="sidebar-member-name">{{ $profileName ?: 'Member' }}</div>
                    @if ($profileEmail !== '')
                        <div class="sidebar-member-email">{{ $profileEmail }}</div>
                    @endif
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Account sections">

                {{-- My bookings collapsible --}}
                <div class="nav-group is-open" data-nav-group="bookings-group">
                    <button class="nav-group-header" type="button" data-group-toggle="bookings-group">
                        <span>My bookings</span>
                        <i class="fa-solid fa-chevron-up nav-chevron"></i>
                    </button>
                    <div class="nav-group-body">
                        <button class="nav-link is-active" type="button" data-section="bookings" data-booking-category="all">All</button>
                        @foreach ($bookingCategoryMeta as $categoryKey => $category)
                            <button class="nav-link" type="button" data-section="bookings" data-booking-category="{{ $categoryKey }}">
                                {{ (string) ($category['label'] ?? ucfirst(str_replace('_', ' ', (string) $categoryKey))) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="nav-divider"></div>

                <button class="nav-item" type="button" data-section="messages">Messages</button>
                <button class="nav-item" type="button" data-section="saved">Saved</button>
                <button class="nav-item" type="button" data-section="my-posts">My posts</button>
                <button class="nav-item" type="button" data-section="price-alerts">Price alerts</button>
                <button class="nav-item" type="button" data-section="my-cards">My cards</button>
                <button class="nav-item" type="button" data-section="gift-cards">Gift cards</button>
                <button class="nav-item" type="button" data-section="promo-codes">Promo codes</button>

                <div class="nav-divider"></div>

                {{-- Account collapsible --}}
                <div class="nav-group is-open" data-nav-group="account-group">
                    <button class="nav-group-header" type="button" data-group-toggle="account-group">
                        <span>Account</span>
                        <i class="fa-solid fa-chevron-up nav-chevron"></i>
                    </button>
                    <div class="nav-group-body">
                        <button class="nav-link" type="button" data-section="profile">Profile</button>
                        <button class="nav-link" type="button" data-section="frequent-traveller">Frequent Traveller Info</button>
                        <button class="nav-link" type="button" data-section="contact-info">Contact info</button>
                        <button class="nav-link" type="button" data-section="receipt-options">Receipt &amp; invoice options</button>
                        <button class="nav-link" type="button" data-section="subscriptions">Subscriptions</button>
                    </div>
                </div>

            </nav>

            <div class="sidebar-footer">
                <a class="sidebar-home-link" href="/">
                    <i class="fa-solid fa-house" style="margin-right:6px;"></i>Back to Home
                </a>
                @if ($customerLoggedIn)
                    <form method="POST" action="/portal/customer/logout">
                        @csrf
                        <button class="sidebar-logout" type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket" style="margin-right:6px;"></i>Sign out
                        </button>
                    </form>
                @else
                    <a href="/portal/customer/login" style="display:block; text-align:center; background:linear-gradient(135deg,#0f6179,#1e7d90); color:#fff; border-radius:8px; padding:9px 14px; text-decoration:none; font-weight:700; font-size:0.84rem;">Sign in</a>
                @endif
            </div>
        </aside>

        {{-- ───────────── Main content ─────────────────────────── --}}
        <main class="portal-main">

            @if (!$customerLoggedIn)
                <div class="no-auth-banner">
                    <h2>Sign in to view your account</h2>
                    <p>Sign in to manage your bookings, saved properties, and account details.</p>
                    <div class="btn-row">
                        <a class="btn-outline" href="/">Browse freely</a>
                        <a class="btn-brand" href="/portal/customer/login">Sign in</a>
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div style="background:#e8f8ef; border:1px solid #a8dfc0; border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:0.84rem; color:#1a6e3a;">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('portal_notice'))
                <div style="background:#eef7ff; border:1px solid #c7dff1; border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:0.84rem; color:#1c5574;">
                    {{ session('portal_notice') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="background:#fff5f5; border:1px solid #efc3c3; border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:0.84rem; color:#8d2d2d;">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ────────── My Bookings ──────────────────────────── --}}
            <section class="portal-section is-active" data-portal-section="bookings">
                <div class="section-title-row">
                    <h1>My Bookings</h1>
                    <span class="title-badge"><i class="fa-solid fa-shield-halved" style="color:#1a6abf;"></i> Travel Worry-free Guarantee</span>
                    <div class="title-actions">
                        <a class="title-action-link" href="#">Can't find your booking?</a>
                        <span class="title-divider">|</span>
                        <a class="title-action-link" href="#" title="Download bookings"><i class="fa-solid fa-download"></i></a>
                    </div>
                </div>

                {{-- Status tabs --}}
                <div class="booking-tabs" role="tablist" aria-label="Booking status filter">
                    @foreach ($statusTabDefs as $tabKey => $tabLabel)
                        <button class="booking-tab {{ $loop->first ? 'is-active' : '' }}" type="button" role="tab" data-booking-tab="{{ $tabKey }}">
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>

                {{-- Category filter pills --}}
                <div class="category-filter-bar" id="bookingCategoryBar" aria-label="Filter by booking category">
                    <button class="category-filter-btn is-active" type="button" data-category-pill="all">All</button>
                    @foreach ($bookingCategoryMeta as $catKey => $cat)
                        <button class="category-filter-btn" type="button" data-category-pill="{{ $catKey }}">
                            {{ (string) ($cat['label'] ?? ucfirst(str_replace('_', ' ', (string) $catKey))) }}
                        </button>
                    @endforeach
                </div>

                {{-- One panel per status tab --}}
                @foreach ($statusTabDefs as $tabKey => $tabLabel)
                    @php $tabBookings = $bookingsByTab[$tabKey] ?? collect(); @endphp
                    <div data-tab-panel="{{ $tabKey }}" {{ !$loop->first ? 'hidden' : '' }}>
                        @if ($tabBookings->isEmpty())
                            <div class="booking-empty">
                                <i class="fa-regular fa-calendar-xmark"></i>
                                No bookings in this category yet.
                            </div>
                        @else
                            <div class="booking-list" data-booking-list="{{ $tabKey }}">
                                @foreach ($tabBookings as $booking)
                                    @php
                                        $bStatus       = strtolower(trim((string) ($booking['status'] ?? 'pending')));
                                        $bStatusClass  = match($bStatus) {
                                            'confirmed'           => 'bs-confirmed',
                                            'cancel_requested'    => 'bs-cancel-requested',
                                            'cancelled','canceled' => 'bs-cancelled',
                                            'completed'           => 'bs-completed',
                                            default               => 'bs-pending',
                                        };
                                        $bStart = (string) ($booking['start_at'] ?? '-');
                                        $bEnd   = (string) ($booking['end_at'] ?? '-');
                                        $bNights = ($bStart !== '-' && $bEnd !== '-')
                                            ? max(1, (int) \Carbon\Carbon::parse($bStart)->diffInDays(\Carbon\Carbon::parse($bEnd)))
                                            : null;
                                        $bStartLabel = $bStart !== '-' ? \Carbon\Carbon::parse($bStart)->format('M j') : '-';
                                        $bEndLabel   = $bEnd   !== '-' ? \Carbon\Carbon::parse($bEnd)->format('M j')   : '-';
                                        $bCreated    = (string) ($booking['created_at'] ?? '-');
                                        $bCreatedLabel = $bCreated !== '-' ? \Carbon\Carbon::parse($bCreated)->format('F j, Y') : '-';
                                    @endphp
                                    <article class="booking-card" data-booking-category="{{ (string) ($booking['category_key'] ?? 'accommodation') }}">

                                        <div class="booking-card-meta-bar">
                                            <span>
                                                Booking No.&nbsp;
                                                <a class="booking-number-link" href="/booking/checkout/{{ (int) ($booking['id'] ?? 0) }}">#{{ str_pad((string) (int) ($booking['id'] ?? 0), 6, '0', STR_PAD_LEFT) }}</a>
                                                &nbsp;|&nbsp; Booking Date: {{ $bCreatedLabel }}
                                            </span>
                                            <span class="booking-status-badge {{ $bStatusClass }}">{{ strtoupper($bStatus) }}</span>
                                        </div>

                                        <div class="booking-card-body">
                                            <div class="booking-card-thumb-wrap" aria-hidden="true">
                                                <i class="fa-solid fa-building"></i>
                                            </div>
                                            <div class="booking-card-info">
                                                <div class="booking-card-title-row">
                                                    <span class="booking-card-property">{{ (string) ($booking['property_name'] ?? 'Property') }}</span>
                                                    <span class="booking-card-price">{{ (string) ($booking['currency'] ?? 'MVR') }} {{ number_format((float) ($booking['total_amount'] ?? 0), 2) }}</span>
                                                </div>
                                                <div class="booking-card-meta">
                                                    {{ $bStartLabel }} – {{ $bEndLabel }}
                                                    @if ($bNights !== null)
                                                        &middot; {{ $bNights }} night{{ $bNights !== 1 ? 's' : '' }}
                                                    @endif
                                                </div>
                                                @if (trim((string) ($booking['service_label'] ?? '')) !== '')
                                                    <div class="booking-card-meta">{{ (string) $booking['service_label'] }}</div>
                                                @endif
                                                <div class="booking-card-guest">
                                                    {{ (string) ($booking['category_label'] ?? '') }}
                                                    &middot; Payment: {{ strtoupper((string) ($booking['payment_status'] ?? 'UNPAID')) }}
                                                    @if (trim((string) ($booking['refund_status'] ?? '')) !== '')
                                                        <br>Refund: {{ strtoupper((string) ($booking['refund_status'] ?? '')) }}
                                                        @if (trim((string) ($booking['refund_case_ref'] ?? '')) !== '')
                                                            ({{ (string) ($booking['refund_case_ref'] ?? '') }})
                                                        @endif
                                                        @if ((bool) ($booking['refund_sla_escalated'] ?? false))
                                                            <span style="color:#a22f2f;font-weight:700;">&middot; Escalated</span>
                                                        @endif
                                                    @endif
                                                </div>
                                                @php
                                                    $bookingContactAvailable = (bool) ($booking['booking_contact_available'] ?? false);
                                                    $vendorDisplayName = trim((string) ($booking['vendor_display_name'] ?? ''));
                                                    $supportEmail = trim((string) ($booking['support_email'] ?? 'support@workation.mv'));
                                                    $bookingId = (int) ($booking['id'] ?? 0);
                                                    $bookingMessages = $reservationMessagesByReservation[$bookingId] ?? collect();
                                                @endphp
                                                @if ($bookingContactAvailable)
                                                    {{-- ── In-platform message thread ──────────────────────────── --}}
                                                    <div class="booking-message-thread" id="booking-{{ $bookingId }}">
                                                        <div class="booking-message-thread-header">
                                                            <span class="booking-message-thread-title"><i class="fa-regular fa-message"></i> Messages with Vendor{{ $vendorDisplayName !== '' ? ' (' . e($vendorDisplayName) . ')' : '' }}</span>
                                                            <span class="booking-message-policy-badge">All communication stays on Workation</span>
                                                        </div>
                                                        <div class="booking-message-policy-notice">
                                                            <i class="fa-solid fa-shield-halved"></i>
                                                            <strong>Never pay or share contact details outside Workation.</strong>
                                                            If a vendor asks you to pay outside this platform or share personal contact details, use the <em>Report</em> button below.
                                                        </div>
                                                        <div class="booking-message-list">
                                                            @forelse ($bookingMessages as $msg)
                                                                @php
                                                                    $msgRole = (string) ($msg->sender_role ?? 'vendor');
                                                                    $msgName = e(trim((string) ($msg->sender_display_name ?? ($msgRole === 'customer' ? 'You' : 'Vendor'))));
                                                                    $msgText = e(trim((string) ($msg->message_text ?? '')));
                                                                    $msgDate = trim((string) ($msg->created_at ?? ''));
                                                                    $msgDate = $msgDate !== '' ? \Carbon\Carbon::parse($msgDate)->format('M j, g:i A') : '';
                                                                    $msgId   = (int) ($msg->id ?? 0);
                                                                    $isFlagged = (bool) ($msg->is_flagged ?? false);
                                                                    $isMine  = $msgRole === 'customer';
                                                                @endphp
                                                                <div class="booking-msg booking-msg--{{ $isMine ? 'mine' : 'theirs' }}{{ $isFlagged ? ' booking-msg--flagged' : '' }}">
                                                                    <span class="booking-msg-sender">{{ $msgName }}</span>
                                                                    <span class="booking-msg-text">{{ $msgText }}</span>
                                                                    <span class="booking-msg-time">{{ $msgDate }}</span>
                                                                    @if (!$isMine)
                                                                        <form method="POST" action="/customer/bookings/{{ $bookingId }}/messages/{{ $msgId }}/report" class="booking-msg-report-form">
                                                                            @csrf
                                                                            <input type="hidden" name="report_reason" value="Customer reported this message as containing off-platform contact or payment request.">
                                                                            <button type="submit" class="booking-msg-report-btn" title="Report this message as off-platform contact attempt" onclick="return confirm('Report this message as an off-platform request?');">
                                                                                <i class="fa-solid fa-flag"></i> Report
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <p class="booking-message-empty">No messages yet. Use the form below to contact your vendor.</p>
                                                            @endforelse
                                                        </div>
                                                        <form method="POST" action="/customer/bookings/{{ $bookingId }}/messages" class="booking-message-compose">
                                                            @csrf
                                                            <textarea name="message_text" class="booking-message-textarea" rows="3" maxlength="2000" placeholder="Type your message here... (Do not share phone numbers, email addresses, or payment details)" required></textarea>
                                                            <div class="booking-message-compose-footer">
                                                                <span class="booking-message-compose-note"><i class="fa-solid fa-lock"></i> Monitored for your protection. No contact details allowed.</span>
                                                                <span style="display:flex; align-items:center; gap:8px;">
                                                                    <a class="message-center-jump" href="#messages">Open full message center</a>
                                                                    <button type="submit" class="btn-brand booking-message-send-btn">Send Message</button>
                                                                </span>
                                                            </div>
                                                        </form>
                                                    </div>
                                                @endif
                                                @if (trim((string) ($booking['refund_status'] ?? '')) !== '')
                                                    @php
                                                        $refundRequestedAt = trim((string) ($booking['refund_requested_at'] ?? ''));
                                                        $refundReviewStartedAt = trim((string) ($booking['refund_review_started_at'] ?? ''));
                                                        $refundApprovedAt = trim((string) ($booking['refund_approved_at'] ?? ''));
                                                        $refundCompletedAt = trim((string) ($booking['refund_completed_at'] ?? ''));
                                                        $refundRejectedAt = trim((string) ($booking['refund_rejected_at'] ?? ''));
                                                        $refundResolutionNotes = trim((string) ($booking['refund_resolution_notes'] ?? ''));
                                                        $formatRefundTimelineDate = static function (string $value): string {
                                                            return $value !== '' ? \Carbon\Carbon::parse($value)->format('M j, Y g:i A') : 'Pending';
                                                        };
                                                    @endphp
                                                    <div class="booking-refund-timeline">
                                                        <div class="booking-refund-timeline-title">Cancellation / Refund Request Timeline</div>
                                                        <ul class="booking-refund-timeline-list">
                                                            <li class="booking-refund-timeline-item">
                                                                <strong>Requested</strong>
                                                                <span>{{ $formatRefundTimelineDate($refundRequestedAt) }}</span>
                                                            </li>
                                                            <li class="booking-refund-timeline-item">
                                                                <strong>Under Review</strong>
                                                                <span class="{{ $refundReviewStartedAt === '' ? 'booking-refund-timeline-empty' : '' }}">{{ $formatRefundTimelineDate($refundReviewStartedAt) }}</span>
                                                            </li>
                                                            <li class="booking-refund-timeline-item">
                                                                <strong>Approved</strong>
                                                                <span class="{{ $refundApprovedAt === '' ? 'booking-refund-timeline-empty' : '' }}">{{ $formatRefundTimelineDate($refundApprovedAt) }}</span>
                                                            </li>
                                                            <li class="booking-refund-timeline-item">
                                                                <strong>{{ $refundRejectedAt !== '' ? 'Rejected' : 'Refund Completed' }}</strong>
                                                                <span class="{{ $refundRejectedAt === '' && $refundCompletedAt === '' ? 'booking-refund-timeline-empty' : '' }}">{{ $refundRejectedAt !== '' ? $formatRefundTimelineDate($refundRejectedAt) : $formatRefundTimelineDate($refundCompletedAt) }}</span>
                                                            </li>
                                                        </ul>
                                                        @if ($refundRejectedAt !== '' && $refundResolutionNotes !== '')
                                                            <div class="booking-refund-note">
                                                                <strong>Why it was rejected:</strong> {{ $refundResolutionNotes }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="booking-card-actions">
                                            @php $bookingPaymentStatus = strtolower((string) ($booking['payment_status'] ?? 'unpaid')); @endphp
                                            @php $bookingStatus = strtolower((string) ($booking['status'] ?? 'pending')); @endphp
                                            @php $isPaidBooking = strtoupper((string) ($booking['payment_status'] ?? 'UNPAID')) === 'PAID'; @endphp
                                            @php $isCancelledBooking = in_array($bookingStatus, ['cancelled', 'canceled', 'cancel_requested'], true); @endphp
                                            @php $canDeleteBooking = $bookingPaymentStatus !== 'paid'; @endphp
                                            <a class="btn-outline" href="/customer/bookings/{{ (int) ($booking['id'] ?? 0) }}/confirmation.pdf">Reservation PDF</a>
                                            @if ($isPaidBooking)
                                                <a class="btn-outline" href="/customer/bookings/{{ (int) ($booking['id'] ?? 0) }}/invoice.pdf">Invoice PDF</a>
                                            @else
                                                <a class="btn-brand" href="/booking/checkout/{{ (int) ($booking['id'] ?? 0) }}">Pay Now</a>
                                            @endif
                                            @if ($canDeleteBooking)
                                                <form method="POST" action="/customer/bookings/{{ (int) ($booking['id'] ?? 0) }}/delete" onsubmit="return confirm('Remove this booking from your portal list?');">
                                                    @csrf
                                                    <button class="btn-outline" type="submit">Delete</button>
                                                </form>
                                            @else
                                                <button class="btn-outline" type="button" disabled title="Paid bookings remain visible until refund/dispute flow is complete.">Delete</button>
                                            @endif
                                            @if (!$isCancelledBooking)
                                                <form method="POST" action="/customer/bookings/{{ (int) ($booking['id'] ?? 0) }}/cancel" onsubmit="return confirm('{{ $isPaidBooking ? 'Do you want to request a refund for this paid booking?' : 'Do you want to cancel this booking?' }}');">
                                                    @csrf
                                                    <button class="btn-outline" type="submit">{{ $isPaidBooking ? 'Request Refund' : 'Cancel Booking' }}</button>
                                                </form>
                                            @endif
                                            <a class="btn-outline" href="/">Similar deals</a>
                                            <a class="btn-brand" href="/">Book Again</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>

            {{-- ────────── Messages (Dedicated Section) ──────────────────── --}}
            <section class="portal-section" data-portal-section="messages" id="messages">
                <div class="section-title-row">
                    <h1>Messages</h1>
                    <span class="title-badge"><i class="fa-solid fa-link"></i> Linked to booking reference by default</span>
                </div>
                @php
                    $messagingBookings = $allBookings->filter(static fn ($booking) => (bool) ($booking['booking_contact_available'] ?? false))->values();
                @endphp
                @if ($messagingBookings->isEmpty())
                    <div class="empty-state">
                        <i class="fa-regular fa-message"></i>
                        <p>You currently do not have any active booking conversations.</p>
                    </div>
                @else
                    <div class="message-center-list">
                        @foreach ($messagingBookings as $booking)
                            @php
                                $bookingId = (int) ($booking['id'] ?? 0);
                                $bookingMessages = $reservationMessagesByReservation[$bookingId] ?? collect();
                                $vendorDisplayName = trim((string) ($booking['vendor_display_name'] ?? 'Vendor'));
                                $propertyName = trim((string) ($booking['property_name'] ?? 'Property'));
                                $serviceLabel = trim((string) ($booking['service_label'] ?? ''));
                                $subject = 'RSV-' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT) . ' · ' . ($serviceLabel !== '' ? $serviceLabel : $propertyName);
                            @endphp
                            <article class="message-center-card" id="message-booking-{{ $bookingId }}">
                                <header class="message-center-card-head">
                                    <div>
                                        <div class="message-center-subject">Subject: {{ $subject }}</div>
                                        <div class="message-center-meta">
                                            <span>Supplier: {{ $vendorDisplayName !== '' ? $vendorDisplayName : 'Vendor' }}</span>
                                            <span>Booking: #{{ str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT) }}</span>
                                            <span>{{ $propertyName }}</span>
                                        </div>
                                    </div>
                                    <a class="message-center-jump" href="/booking/checkout/{{ $bookingId }}">Open booking</a>
                                </header>

                                <div class="booking-message-policy-notice">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <strong>Keep communication and payments inside Workation.</strong>
                                    Messages are linked to booking {{ str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT) }}.
                                </div>

                                <div class="booking-message-list">
                                    @forelse ($bookingMessages as $msg)
                                        @php
                                            $msgRole = (string) ($msg->sender_role ?? 'vendor');
                                            $msgName = e(trim((string) ($msg->sender_display_name ?? ($msgRole === 'customer' ? 'You' : 'Vendor'))));
                                            $msgText = e(trim((string) ($msg->message_text ?? '')));
                                            $msgDate = trim((string) ($msg->created_at ?? ''));
                                            $msgDate = $msgDate !== '' ? \Carbon\Carbon::parse($msgDate)->format('M j, g:i A') : '';
                                            $msgId   = (int) ($msg->id ?? 0);
                                            $isFlagged = (bool) ($msg->is_flagged ?? false);
                                            $isMine  = $msgRole === 'customer';
                                        @endphp
                                        <div class="booking-msg booking-msg--{{ $isMine ? 'mine' : 'theirs' }}{{ $isFlagged ? ' booking-msg--flagged' : '' }}">
                                            <span class="booking-msg-sender">{{ $msgName }}</span>
                                            <span class="booking-msg-text">{{ $msgText }}</span>
                                            <span class="booking-msg-time">{{ $msgDate }}</span>
                                            @if (!$isMine)
                                                <form method="POST" action="/customer/bookings/{{ $bookingId }}/messages/{{ $msgId }}/report" class="booking-msg-report-form">
                                                    @csrf
                                                    <input type="hidden" name="report_reason" value="Customer reported this message as containing off-platform contact or payment request.">
                                                    <button type="submit" class="booking-msg-report-btn" title="Report this message as off-platform contact attempt" onclick="return confirm('Report this message as an off-platform request?');">
                                                        <i class="fa-solid fa-flag"></i> Report
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="booking-message-empty">No messages yet. Start the conversation below.</p>
                                    @endforelse
                                </div>

                                <form method="POST" action="/customer/bookings/{{ $bookingId }}/messages" class="booking-message-compose">
                                    @csrf
                                    <textarea name="message_text" class="booking-message-textarea" rows="4" maxlength="2000" placeholder="Message supplier about {{ $subject }}" required></textarea>
                                    <div class="booking-message-compose-footer">
                                        <span class="booking-message-compose-note"><i class="fa-solid fa-link"></i> Default subject: {{ $subject }}</span>
                                        <button type="submit" class="btn-brand booking-message-send-btn">Send Message</button>
                                    </div>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ────────── Saved / Favourites ──────────────────── --}}
            <section class="portal-section" data-portal-section="saved">
                <div class="section-title-row"><h1>Saved</h1></div>
                <div class="empty-state">
                    <i class="fa-regular fa-heart"></i>
                    <p>Properties and experiences you save while browsing will appear here.</p>
                    <a class="btn-outline" href="/" style="display:inline-block; margin-top:14px;">Start browsing</a>
                </div>
            </section>

            {{-- ────────── My Posts (reviews) ──────────────────── --}}
            <section class="portal-section" data-portal-section="my-posts">
                <div class="section-title-row"><h1>My Posts</h1></div>
                <div class="empty-state">
                    <i class="fa-regular fa-star"></i>
                    <p>Reviews and ratings you've submitted to vendors and service providers appear here.</p>
                </div>
            </section>

            {{-- ────────── Price Alerts ─────────────────────────── --}}
            <section class="portal-section" data-portal-section="price-alerts">
                <div class="section-title-row"><h1>Price Alerts</h1></div>
                <div class="empty-state">
                    <i class="fa-regular fa-bell"></i>
                    <p>Set price alerts for properties and services to get notified when rates drop.</p>
                </div>
            </section>

            {{-- ────────── My Cards ─────────────────────────────── --}}
            <section class="portal-section" data-portal-section="my-cards">
                <div class="section-title-row"><h1>My Cards</h1></div>
                <div class="cards-grid">
                    <div class="add-card-tile" role="button" tabindex="0">
                        <i class="fa-solid fa-plus" style="font-size:1.3rem;"></i>
                        Add a credit or debit card
                    </div>
                </div>
                <p style="margin-top:14px; font-size:0.78rem; color:#8a9aaa;">
                    <i class="fa-solid fa-lock" style="margin-right:4px;"></i>
                    Saved cards are encrypted and stored securely. They are never shared with third parties.
                </p>
            </section>

            {{-- ────────── Gift Cards ───────────────────────────── --}}
            <section class="portal-section" data-portal-section="gift-cards">
                <div class="section-title-row"><h1>Gift Cards</h1></div>
                <div class="empty-state">
                    <i class="fa-solid fa-gift"></i>
                    <p>Redeem a Workation gift card or check your remaining balance here.</p>
                </div>
            </section>

            {{-- ────────── Promo Codes ──────────────────────────── --}}
            <section class="portal-section" data-portal-section="promo-codes">
                <div class="section-title-row"><h1>Promo Codes</h1></div>
                <div class="empty-state">
                    <i class="fa-solid fa-tag"></i>
                    <p>Your available and past promo codes will appear here.</p>
                </div>
            </section>

            {{-- ────────── Profile ──────────────────────────────── --}}
            <section class="portal-section" data-portal-section="profile">
                <div class="section-title-row"><h1>Profile</h1></div>
                <div class="section-card">
                    <div class="profile-avatar-row">
                        <div class="profile-avatar-large" aria-hidden="true">{{ $profileInitials }}</div>
                        <div>
                            <p style="font-size:1rem; font-weight:700; color:#1a2f41;">{{ $profileName }}</p>
                            <p style="font-size:0.82rem; color:#5a7088; margin-top:4px;">Member since {{ $profileMemberSince }}</p>
                        </div>
                    </div>
                    <form method="POST" action="/portal/customer/profile/update">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="profileFirstName">First name</label>
                                <input id="profileFirstName" name="first_name" type="text" value="{{ explode(' ', $profileName)[0] ?? '' }}" placeholder="First name">
                            </div>
                            <div class="form-field">
                                <label for="profileLastName">Last name</label>
                                <input id="profileLastName" name="last_name" type="text" value="{{ implode(' ', array_slice(explode(' ', $profileName), 1)) }}" placeholder="Last name">
                            </div>
                            <div class="form-field">
                                <label for="profileEmail">Email</label>
                                <input id="profileEmail" name="email" type="email" value="{{ $profileEmail }}" placeholder="Email address">
                            </div>
                            <div class="form-field">
                                <label for="profilePhone">Phone</label>
                                <input id="profilePhone" name="phone" type="tel" value="{{ old('phone', (string) ($customerProfile['phone'] ?? '')) }}" placeholder="Phone number">
                            </div>
                            <div class="form-field">
                                <label for="profileDob">Date of birth</label>
                                <input id="profileDob" name="dob" type="date" value="{{ old('dob', (string) ($customerProfile['dob'] ?? '')) }}">
                            </div>
                            <div class="form-field">
                                <label for="profileNationality">Nationality</label>
                                <input id="profileNationality" name="nationality" type="text" value="{{ old('nationality', (string) ($customerProfile['nationality'] ?? '')) }}" placeholder="Your nationality">
                            </div>
                            <div class="form-field">
                                <label for="profileGender">Gender</label>
                                <select id="profileGender" name="gender">
                                    <option value="" @selected(old('gender', (string) ($customerProfile['gender'] ?? '')) === '')>Prefer not to say</option>
                                    <option value="male" @selected(old('gender', (string) ($customerProfile['gender'] ?? '')) === 'male')>Male</option>
                                    <option value="female" @selected(old('gender', (string) ($customerProfile['gender'] ?? '')) === 'female')>Female</option>
                                    <option value="other" @selected(old('gender', (string) ($customerProfile['gender'] ?? '')) === 'other')>Other</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="profileLanguage">Preferred language</label>
                                <select id="profileLanguage" name="preferred_language">
                                    <option value="en" @selected(old('preferred_language', (string) ($customerProfile['preferred_language'] ?? 'en')) === 'en')>English</option>
                                    <option value="dv" @selected(old('preferred_language', (string) ($customerProfile['preferred_language'] ?? 'en')) === 'dv')>Dhivehi</option>
                                    <option value="ar" @selected(old('preferred_language', (string) ($customerProfile['preferred_language'] ?? 'en')) === 'ar')>Arabic</option>
                                    <option value="zh" @selected(old('preferred_language', (string) ($customerProfile['preferred_language'] ?? 'en')) === 'zh')>Chinese</option>
                                </select>
                            </div>
                            <div class="form-field full-width">
                                <label for="profileAddressLine">Address Line</label>
                                <input id="profileAddressLine" name="address_line" type="text" value="{{ old('address_line', (string) ($customerProfile['address_line'] ?? '')) }}" placeholder="Street / House / Building">
                            </div>
                            <div class="form-field full-width">
                                @include('components.atoll-island-select', [
                                    'fieldNameAtoll' => 'address_atoll_id',
                                    'fieldNameIsland' => 'address_island_id',
                                    'labelAtoll' => 'Address Atoll',
                                    'labelIsland' => 'Address Island',
                                    'selectedAtoll' => old('address_atoll_id', (string) ($customerProfile['address_atoll_id'] ?? '')),
                                    'selectedIsland' => old('address_island_id', (string) ($customerProfile['address_island_id'] ?? '')),
                                    'requiredAtoll' => false,
                                    'requiredIsland' => false,
                                    'cssClass' => 'profile-input',
                                ])
                            </div>
                        </div>
                        <div class="form-footer">
                            <button class="btn-brand" type="submit" style="padding:9px 22px; border-radius:8px;">Save changes</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- ────────── Frequent Traveller Info ──────────────── --}}
            <section class="portal-section" data-portal-section="frequent-traveller">
                <div class="section-title-row"><h1>Frequent Traveller Info</h1></div>
                <div class="section-card">
                    <h3>Travel Documents &amp; Loyalty Programmes</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ftPassport">Passport number</label>
                            <input id="ftPassport" name="passport_number" type="text" placeholder="Passport No.">
                        </div>
                        <div class="form-field">
                            <label for="ftPassportExpiry">Passport expiry</label>
                            <input id="ftPassportExpiry" name="passport_expiry" type="date">
                        </div>
                        <div class="form-field">
                            <label for="ftMembership">Loyalty / frequent traveller no.</label>
                            <input id="ftMembership" name="loyalty_number" type="text" placeholder="Membership number">
                        </div>
                        <div class="form-field">
                            <label for="ftPreferredSeat">Preferred seat</label>
                            <select id="ftPreferredSeat" name="preferred_seat">
                                <option value="">No preference</option>
                                <option value="window">Window</option>
                                <option value="aisle">Aisle</option>
                                <option value="middle">Middle</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="ftDiet">Dietary preference</label>
                            <select id="ftDiet" name="dietary_preference">
                                <option value="">No special diet</option>
                                <option value="vegetarian">Vegetarian</option>
                                <option value="vegan">Vegan</option>
                                <option value="halal">Halal</option>
                                <option value="gluten_free">Gluten-free</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="ftEmergencyContact">Emergency contact number</label>
                            <input id="ftEmergencyContact" name="emergency_contact" type="tel" placeholder="Emergency contact">
                        </div>
                    </div>
                    <div class="form-footer">
                        <button class="btn-brand" type="button" style="padding:9px 22px; border-radius:8px;">Save</button>
                    </div>
                </div>
            </section>

            {{-- ────────── Contact Info ─────────────────────────── --}}
            <section class="portal-section" data-portal-section="contact-info">
                <div class="section-title-row"><h1>Contact Info</h1></div>
                <div class="section-card">
                    <h3>How we reach you</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="ciEmail">Email address</label>
                            <input id="ciEmail" name="contact_email" type="email" value="{{ $profileEmail }}" placeholder="Your email">
                        </div>
                        <div class="form-field">
                            <label for="ciPhone">Mobile number</label>
                            <input id="ciPhone" name="contact_phone" type="tel" placeholder="Mobile number">
                        </div>
                        <div class="form-field full-width">
                            <label for="ciAddress">Mailing address</label>
                            <input id="ciAddress" name="address" type="text" placeholder="Street address">
                        </div>
                        <div class="form-field">
                            <label for="ciCity">City</label>
                            <input id="ciCity" name="city" type="text" placeholder="City">
                        </div>
                        <div class="form-field">
                            <label for="ciCountry">Country</label>
                            <input id="ciCountry" name="country" type="text" placeholder="Country">
                        </div>
                    </div>
                    <div class="form-footer">
                        <button class="btn-brand" type="button" style="padding:9px 22px; border-radius:8px;">Save</button>
                    </div>
                </div>
            </section>

            {{-- ────────── Receipt & Invoice Options ───────────── --}}
            <section class="portal-section" data-portal-section="receipt-options">
                <div class="section-title-row"><h1>Receipt &amp; Invoice Options</h1></div>
                <div class="section-card">
                    <h3>Billing preferences</h3>
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label for="roName">Name on receipt</label>
                            <input id="roName" name="receipt_name" type="text" value="{{ $profileName }}" placeholder="Full name or company name">
                        </div>
                        <div class="form-field full-width">
                            <label for="roVat">VAT / tax registration number</label>
                            <input id="roVat" name="vat_number" type="text" placeholder="Optional">
                        </div>
                        <div class="form-field full-width">
                            <label for="roBilling">Billing address</label>
                            <input id="roBilling" name="billing_address" type="text" placeholder="Billing address">
                        </div>
                        <div class="form-field">
                            <label for="roFormat">Preferred receipt format</label>
                            <select id="roFormat" name="receipt_format">
                                <option value="email">Email PDF</option>
                                <option value="download">Downloadable link</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button class="btn-brand" type="button" style="padding:9px 22px; border-radius:8px;">Save</button>
                    </div>
                </div>
            </section>

            {{-- ────────── Subscriptions ───────────────────────── --}}
            <section class="portal-section" data-portal-section="subscriptions">
                <div class="section-title-row"><h1>Subscriptions</h1></div>
                <div class="section-card">
                    <h3>Communication preferences</h3>
                    <label class="pref-row"><input type="checkbox" checked> Booking confirmations and status updates</label>
                    <label class="pref-row"><input type="checkbox" checked> Price drop alerts for saved properties</label>
                    <label class="pref-row"><input type="checkbox"> Promotional offers and new listings</label>
                    <label class="pref-row"><input type="checkbox"> Weekly travel inspiration newsletter</label>
                    <label class="pref-row"><input type="checkbox"> Tips and recommendations from Workation team</label>
                    <div class="form-footer">
                        <button class="btn-brand" type="button" style="padding:9px 22px; border-radius:8px;">Save preferences</button>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <script>
        (function () {
            'use strict';

            const allSections = Array.from(document.querySelectorAll('[data-portal-section]'));
            const allNavButtons = Array.from(document.querySelectorAll('[data-section]'));

            // ── Activate a portal section ─────────────────────────────
            function activateSection(sectionKey) {
                allSections.forEach(function (s) {
                    s.classList.toggle('is-active', s.getAttribute('data-portal-section') === sectionKey);
                });
            }

            // ── Highlight active sidebar link ─────────────────────────
            function setActiveNav(sectionKey, bookingCategory) {
                allNavButtons.forEach(function (btn) {
                    const btnSection  = btn.getAttribute('data-section') || '';
                    const btnCategory = btn.getAttribute('data-booking-category') || '';

                    if (btnCategory) {
                        // sub-link: match both section + category
                        btn.classList.toggle('is-active', btnSection === sectionKey && btnCategory === (bookingCategory || 'all'));
                    } else {
                        btn.classList.toggle('is-active', btnSection === sectionKey && sectionKey !== 'bookings');
                    }
                });
            }

            // ── Current state ─────────────────────────────────────────
            let activeTab      = 'all';
            let activeCategory = 'all';

            const categoryPills = Array.from(document.querySelectorAll('[data-category-pill]'));

            // ── Filter booking cards by tab + category ────────────────
            function filterBookings(tabKey, categoryKey) {
                activeTab      = tabKey      || 'all';
                activeCategory = categoryKey || 'all';

                // Show correct tab panel
                document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                    panel.hidden = panel.getAttribute('data-tab-panel') !== activeTab;
                });

                // Filter cards inside that panel
                const visiblePanel = document.querySelector('[data-tab-panel="' + activeTab + '"]');
                if (visiblePanel) {
                    visiblePanel.querySelectorAll('[data-booking-category]').forEach(function (card) {
                        const cardCat = card.getAttribute('data-booking-category') || '';
                        card.style.display = (activeCategory === 'all' || cardCat === activeCategory) ? '' : 'none';
                    });
                }

                // Sync category pills
                categoryPills.forEach(function (pill) {
                    pill.classList.toggle('is-active', pill.getAttribute('data-category-pill') === activeCategory);
                });
            }

            // ── Sidebar navigation clicks ─────────────────────────────
            allNavButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const sectionKey  = btn.getAttribute('data-section')          || 'bookings';
                    const catKey      = btn.getAttribute('data-booking-category') || '';

                    activateSection(sectionKey);

                    if (sectionKey === 'bookings') {
                        const cat = catKey || 'all';
                        filterBookings(activeTab, cat);
                        setActiveNav(sectionKey, cat);
                    } else {
                        setActiveNav(sectionKey, '');
                    }
                });
            });

            // ── Booking status tabs ───────────────────────────────────
            const bookingTabs = Array.from(document.querySelectorAll('[data-booking-tab]'));
            bookingTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const tabKey = tab.getAttribute('data-booking-tab') || 'all';
                    bookingTabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); });
                    filterBookings(tabKey, activeCategory);
                });
            });

            // ── Category filter pills ─────────────────────────────────
            categoryPills.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    const cat = pill.getAttribute('data-category-pill') || 'all';
                    filterBookings(activeTab, cat);
                    // Sync sidebar nav link
                    setActiveNav('bookings', cat);
                });
            });

            // ── Nav group collapse / expand ───────────────────────────
            document.querySelectorAll('[data-group-toggle]').forEach(function (header) {
                header.addEventListener('click', function () {
                    const groupName = header.getAttribute('data-group-toggle');
                    const group = document.querySelector('[data-nav-group="' + groupName + '"]');
                    if (group) { group.classList.toggle('is-open'); }
                });
            });

            // ── Hash-based deep linking ───────────────────────────────
            const hashMap = {
                '#bookings':           ['bookings',           'all'],
                '#messages':           ['messages',           ''],
                '#saved':              ['saved',              ''],
                '#favourites':         ['saved',              ''],
                '#my-posts':           ['my-posts',           ''],
                '#posts':              ['my-posts',           ''],
                '#price-alerts':       ['price-alerts',       ''],
                '#alerts':             ['price-alerts',       ''],
                '#my-cards':           ['my-cards',           ''],
                '#gift-cards':         ['gift-cards',         ''],
                '#promos':             ['promo-codes',        ''],
                '#profile':            ['profile',            ''],
                '#frequent-traveller': ['frequent-traveller', ''],
                '#contact-info':       ['contact-info',       ''],
                '#receipt-options':    ['receipt-options',    ''],
                '#subscriptions':      ['subscriptions',      ''],
            };

            function applyHash() {
                const entry = hashMap[window.location.hash];
                if (!entry) { return; }
                const [sectionKey, catKey] = entry;
                activateSection(sectionKey);
                if (sectionKey === 'bookings') {
                    filterBookings(activeTab, catKey || 'all');
                    setActiveNav(sectionKey, catKey || 'all');
                } else {
                    setActiveNav(sectionKey, '');
                }
            }

            window.addEventListener('hashchange', applyHash);
            applyHash();
        })();
    </script>
</body>
</html>