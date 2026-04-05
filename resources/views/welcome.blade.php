<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --surface-soft: #edf6f3;
            --brand: #0f6179;
            --brand-soft: #dff1f6;
            --accent: #f3a337;
            --accent-soft: #fff3df;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                linear-gradient(180deg, #f8fbff 0%, #f4f8fc 26%, #f7fbf8 100%);
        }

        .page {
            margin: 8px 12px 30px 270px;
            width: calc(100% - 282px);
            max-width: none;
            position: relative;
        }

        .floating-sidebar {
            position: fixed;
            left: 12px;
            top: 56px;
            width: 250px;
            height: calc(100vh - 68px);
            overflow-y: auto;
            z-index: 900;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 8px 14px;
            border: 1px solid #d8e3ec;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 24px rgba(22, 64, 93, 0.06);
            margin-bottom: 12px;
            position: sticky;
            top: 8px;
            z-index: 980;
            backdrop-filter: blur(10px);
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            flex: 1;
        }

        .header-menu-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid #d8e5ef;
            border-radius: 10px;
            background: #f9fcff;
            color: #61778c;
            font-size: 0.82rem;
        }

        .header-brand-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .header-brand {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #2a5bff;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .header-subline {
            margin: 1px 0 0;
            font-size: 0.7rem;
            color: #71869a;
            white-space: nowrap;
        }

        .header-search-mini {
            display: flex;
            align-items: center;
            min-width: 0;
            width: min(460px, 100%);
            border: 1px solid #d8e3ec;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }

        .header-search-mini input {
            border: 0;
            background: transparent;
            padding: 9px 12px;
            font: inherit;
            min-width: 0;
            width: 100%;
            color: #244057;
        }

        .header-search-mini input::placeholder {
            color: #9aabbb;
        }

        .header-search-mini button {
            border: 0;
            width: 38px;
            height: 38px;
            background: #2a5bff;
            color: #ffffff;
            cursor: pointer;
            flex-shrink: 0;
        }

        .header-links {
            display: flex;
            align-items: center;
            gap: 2px;
            min-width: 0;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .header-links::-webkit-scrollbar {
            display: none;
        }

        .header-link {
            text-decoration: none;
            color: #1d3449;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 7px 9px;
            border-radius: 8px;
            white-space: nowrap;
        }

        .header-link:hover {
            background: #f4f8fc;
            color: #154e71;
        }

        .hero-layout {
            display: grid;
            grid-template-columns: minmax(220px, 250px) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .page-with-sidebar {
            display: contents;
        }

        .sidebar-fixed {
            display: none;
        }

        .sidebar-shell {
            border: 1px solid #c9ddeb;
            border-radius: 16px;
            background: linear-gradient(160deg, #ffffff 0%, #f5f9fc 100%);
            padding: 10px;
            box-shadow: inset 0 1px 0 #ffffff;
        }

        .sidebar-title {
            margin: 0 0 8px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4e6d83;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .customer-auth {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .account-menu {
            position: relative;
        }

        .auth-link {
            text-decoration: none;
            border: 1px solid #c9dbea;
            border-radius: 10px;
            padding: 7px 12px;
            background: #f6fbff;
            color: #19466a;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
        }

        .auth-link.primary {
            background: linear-gradient(135deg, #0f6179 0%, #1e7d90 100%);
            border-color: #0f6179;
            color: #ffffff;
        }

        .auth-btn {
            border: 1px solid #d2dde8;
            border-radius: 10px;
            padding: 7px 12px;
            background: #ffffff;
            color: #385772;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        .account-menu-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #c9dbea;
            border-radius: 11px;
            padding: 6px 10px;
            background: #ffffff;
            color: #173e5b;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(20, 63, 90, 0.08);
        }

        .account-menu-toggle:hover {
            border-color: #9fbcd0;
        }

        .account-avatar {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #e8f5fb 0%, #d4ebf7 100%);
            color: #1e5a7e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
        }

        .account-chevron {
            color: #65829a;
            font-size: 0.68rem;
            transition: transform 0.2s ease;
        }

        .account-menu.is-open .account-chevron {
            transform: rotate(180deg);
        }

        .account-menu-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: min(290px, calc(100vw - 24px));
            border: 1px solid #c9ddeb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 20px 34px rgba(15, 50, 77, 0.2);
            overflow: hidden;
            z-index: 950;
        }

        .account-panel-head {
            padding: 12px 14px;
            border-bottom: 1px solid #d8e6f0;
            background: linear-gradient(140deg, #f7fbff 0%, #edf6fb 100%);
        }

        .account-panel-greet {
            margin: 0;
            color: #1f4d6f;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .account-panel-note {
            margin: 3px 0 0;
            color: #5d778d;
            font-size: 0.75rem;
        }

        .account-panel-links {
            display: grid;
            padding: 8px;
            gap: 2px;
        }

        .account-panel-link {
            text-decoration: none;
            border-radius: 9px;
            padding: 9px 10px;
            color: #264c66;
            font-size: 0.81rem;
            font-weight: 600;
        }

        .account-panel-link:hover {
            background: #eff7fc;
        }

        .account-panel-foot {
            border-top: 1px solid #d8e6f0;
            padding: 8px;
            background: #fbfdff;
        }

        .account-panel-logout {
            width: 100%;
            border: 1px solid #d4e0ea;
            border-radius: 9px;
            padding: 9px 10px;
            background: #ffffff;
            color: #37516a;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            text-align: left;
            cursor: pointer;
        }

        .account-panel-logout:hover {
            border-color: #b5ccdd;
            background: #f4f9fd;
        }

        .top-links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .top-link {
            text-decoration: none;
            border: 1px solid #edf3f7;
            border-radius: 10px;
            background: #ffffff;
            color: #19405b;
            padding: 8px 10px;
            font-size: 0.78rem;
            line-height: 1.28;
            font-weight: 600;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-height: 48px;
            justify-content: center;
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .top-link:hover {
            border-color: #8db5cf;
            background: #f8fbff;
            box-shadow: 0 6px 14px rgba(34, 86, 120, 0.08);
        }

        .top-link span {
            color: #5e7388;
            font-size: 0.73rem;
            font-weight: 500;
        }

            .top-link-head {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .top-link-head i {
                font-size: 0.9rem;
                color: #0f6179;
                width: 16px;
                text-align: center;
                flex: 0 0 16px;
            }

            .mobile-category-nav {
                display: none;
                margin: 0 0 12px;
                border: 1px solid #c9ddeb;
                border-radius: 14px;
                background: linear-gradient(160deg, #f8fcff 0%, #eef6fb 100%);
                padding: 9px;
            }

            .mobile-category-toggle {
                list-style: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.09em;
                color: #4e6d83;
                font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            }

            .mobile-category-toggle::-webkit-details-marker {
                display: none;
            }

            .mobile-category-toggle::after {
                content: '+';
                font-size: 1rem;
                line-height: 1;
                color: #0f6179;
            }

            .mobile-category-nav[open] .mobile-category-toggle::after {
                content: '-';
            }

            .mobile-category-row {
                display: none;
                flex-wrap: nowrap;
                gap: 7px;
                overflow-x: auto;
                overflow-y: hidden;
                padding-top: 8px;
                padding-bottom: 2px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }

            .mobile-category-nav[open] .mobile-category-row {
                display: flex;
            }

            .mobile-category-link {
                text-decoration: none;
                border: 1px solid #d4e3ee;
                border-radius: 999px;
                background: #f8fcff;
                color: #19405b;
                padding: 7px 10px;
                font-size: 0.75rem;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                flex: 0 0 auto;
            }

        .search-section-full-width {
            margin-top: 0;
            margin-bottom: 18px;
            border: 1px solid #d6e2ee;
            border-radius: 22px;
            background:
                linear-gradient(115deg, rgba(28, 88, 243, 0.86) 0%, rgba(23, 136, 228, 0.76) 48%, rgba(16, 98, 164, 0.68) 100%),
                radial-gradient(circle at 20% 18%, rgba(255,255,255,0.22) 0, rgba(255,255,255,0) 34%),
                radial-gradient(circle at 80% 25%, rgba(255,255,255,0.18) 0, rgba(255,255,255,0) 28%),
                linear-gradient(180deg, #326de5 0%, #2158cb 100%);
            color: #ecfcff;
            padding: 28px 28px 110px;
            box-shadow: 0 24px 44px rgba(32, 72, 155, 0.18);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .search-section-full-width::before {
            content: '';
            position: absolute;
            inset: auto -10% -24% 42%;
            height: 62%;
            background:
                radial-gradient(circle at 25% 48%, rgba(255,255,255,0.22) 0, rgba(255,255,255,0.02) 28%, rgba(255,255,255,0) 55%),
                linear-gradient(180deg, rgba(227, 241, 255, 0.24) 0%, rgba(197, 224, 255, 0) 100%);
            clip-path: polygon(0 100%, 18% 62%, 32% 72%, 47% 48%, 60% 62%, 74% 32%, 88% 58%, 100% 18%, 100% 100%);
            pointer-events: none;
            opacity: 0.9;
        }

        .search-eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.72rem;
            color: #dce9ff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .search-title {
            margin: 10px 0 0;
            font-size: clamp(1.85rem, 3vw, 3rem);
            line-height: 1.08;
            max-width: 760px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .search-support-strip {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            color: #e6f1ff;
            font-size: 0.8rem;
            position: relative;
            z-index: 1;
        }

        .search-support-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .search-shell {
            position: relative;
            z-index: 1;
            margin: 26px auto 0;
            width: min(980px, 100%);
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #dde5ee;
            box-shadow: 0 20px 38px rgba(22, 49, 97, 0.2);
            padding: 16px 14px 14px;
        }

        .search-category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: -30px auto 14px;
            width: fit-content;
            max-width: 100%;
            padding: 6px;
            border-radius: 999px;
            background: rgba(35, 46, 62, 0.82);
            box-shadow: 0 10px 20px rgba(18, 31, 57, 0.2);
        }

        .search-category-tab {
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: rgba(255,255,255,0.86);
            padding: 8px 14px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .search-category-tab.is-active {
            background: #ffffff;
            color: #16344d;
        }

        .search-form {
            margin-top: 0;
            display: grid;
            grid-template-columns: minmax(18ch, 1.25fr) minmax(16ch, 1fr) minmax(16ch, 1fr) minmax(16ch, 1fr) auto;
            gap: 10px;
            align-items: start;
            min-width: 0;
            overflow: hidden;
        }

        .search-field-shell {
            display: flex;
            align-items: stretch;
            min-width: 0;
            grid-column: span 1;
        }

        .search-primary-field {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            width: 100%;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .search-primary-field select {
            border-right: 1px solid #e2e9f1 !important;
            border-radius: 0 !important;
        }

        .search-primary-field input {
            border-radius: 0 !important;
        }

        .search-dynamic-fields {
            margin-top: 0;
            display: grid;
            grid-column: 1 / -1;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 8px;
            min-width: 0;
            overflow: hidden;
            background: #ffffff;
        }

        .search-dynamic-fields.is-active {
            display: grid;
        }

        .search-dynamic-fields .field {
            display: grid;
            gap: 4px;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            padding: 8px 10px;
            background: #ffffff;
        }

        .search-dynamic-fields .field.field-short {
            grid-column: span 2;
        }

        .search-dynamic-fields .field.field-medium {
            grid-column: span 3;
        }

        .search-dynamic-fields .field.field-date {
            grid-column: span 3;
        }

        .search-dynamic-fields .field.field-long {
            grid-column: span 4;
        }

        .search-dynamic-fields .field label {
            font-size: 0.68rem;
            letter-spacing: 0.04em;
            text-transform: none;
            color: #7a8ea2;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .search-form select,
        .search-form input {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            color: #103247;
            background: #ffffff;
            box-sizing: border-box;
        }

        .search-dynamic-fields select,
        .search-dynamic-fields input {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 3px 0 0;
            font: inherit;
            color: #103247;
            background: transparent;
            box-sizing: border-box;
        }

        .search-form input[type="date"],
        .search-dynamic-fields input[type="date"] {
            appearance: none;
            -webkit-appearance: none;
            display: block;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            padding-right: 8px;
            overflow: hidden;
        }

        .search-dynamic-fields input[type="date"] {
            min-height: 44px;
            font-size: 16px;
            line-height: 1.2;
        }

        .search-form input[type="date"],
        .search-dynamic-fields input[type="date"] {
            min-height: 36px;
            padding-right: 10px;
            overflow: hidden;
        }

        .search-form button {
            min-width: 0;
            width: auto;
            justify-self: stretch;
            box-shadow: none;
        }

        .search-submit-row {
            grid-column: auto;
            display: flex;
            justify-content: stretch;
            align-self: stretch;
            margin-top: 0;
        }

        .search-submit-row button {
            width: 100%;
            border-radius: 12px;
            min-height: 100%;
            padding: 12px 18px;
            font-size: 0.92rem;
        }

        .search-actions {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-actions a {
            color: #dff7fb;
            font-size: 0.8rem;
            text-decoration: none;
            border: 1px solid rgba(214, 244, 248, 0.45);
            border-radius: 10px;
            padding: 9px 12px;
            background: rgba(4, 64, 83, 0.22);
        }

        .search-options {
            margin-top: 9px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .search-options a {
            text-decoration: none;
            color: #eafcff;
            border: 1px solid rgba(214, 244, 248, 0.5);
            background: rgba(4, 64, 83, 0.25);
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.76rem;
        }

        .promo-banner {
            margin-top: 0;
            border: 1px solid #f3d2a4;
            border-radius: 14px;
            background: linear-gradient(95deg, #fff6e4 0%, #ffefd6 48%, #ffe5bf 100%);
            color: #5b3c13;
            padding: 12px 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .promo-banner strong {
            font-size: 0.95rem;
        }

        .promo-banner a {
            text-decoration: none;
            border: 1px solid #e5be86;
            background: #fff;
            color: #68410f;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .section {
            margin-top: 13px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 14px;
        }

        .section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }

        .section-title {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0.03em;
        }

        .section-sub {
            margin: 0;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .browse-grid,
        .trending-grid,
        .deal-grid,
        .loved-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .item-card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #f5fbff;
            text-decoration: none;
            color: #1b3f58;
            display: grid;
            overflow: hidden;
            min-height: 210px;
            grid-template-rows: 128px auto;
        }

        .item-card-media {
            position: relative;
            width: 100%;
            height: 128px;
            background: linear-gradient(140deg, #d6edf1 0%, #bfdfeb 45%, #ffe3be 100%);
            overflow: hidden;
        }

        .item-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-card-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(7, 35, 52, 0.35), rgba(7, 35, 52, 0.05));
        }

        .item-card-body {
            padding: 10px;
            display: grid;
            gap: 4px;
            align-content: start;
            background: #fbfdff;
        }

        .item-card strong {
            font-size: 0.95rem;
            line-height: 1.28;
            color: #133b55;
        }

        .item-card span {
            color: #5b7185;
            font-size: 0.79rem;
            line-height: 1.35;
        }

        .item-card-meta {
            color: #2b617e;
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .home-footer-skin {
            margin-top: 8px;
            border-radius: 16px;
            background: linear-gradient(165deg, #f1f8fc 0%, #f9fcff 100%);
            border: 1px solid #d2e2ee;
            padding: 12px;
        }

        .home-footer-skin .wf-site-footer {
            margin-top: 0;
            border-top: 0;
            padding-top: 0;
        }

        .home-footer-skin .wf-footer-col {
            background: #ffffff;
            border-color: #d2e2ee;
        }

        .home-footer-skin .wf-footer-note {
            margin: 10px 2px 0;
            font-size: 0.75rem;
            color: #597286;
        }

        .chip-row {
            margin-top: 8px;
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .chip {
            border: 1px solid #cfe0eb;
            background: var(--surface-soft);
            color: #24516b;
            border-radius: 999px;
            font-size: 0.76rem;
            padding: 5px 10px;
        }

        /* Uniform Icon System Styles */
        .uniform-icon {
            display: inline-block;
            font-size: 1em;
            line-height: 1;
            margin: 0;
            padding: 0;
        }

        .uniform-icon-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: inherit;
        }

        .uniform-icon-label .uniform-icon {
            font-size: 1.2em;
            flex-shrink: 0;
        }

        .uniform-label {
            display: inline;
            font-size: inherit;
        }


        @media (max-width: 1040px) {
            .page {
                width: calc(100% - 28px);
                margin: 14px auto 30px;
            }

            .floating-sidebar {
                position: static;
                width: 100%;
                height: auto;
                margin-bottom: 12px;
            }

            .header-bar {
                position: static;
            }

            .header-main {
                flex-wrap: wrap;
            }

            .header-search-mini {
                width: 100%;
                order: 3;
            }

            .header-links {
                order: 4;
                flex-basis: 100%;
            }

            .page-with-sidebar {
                display: contents;
            }

            .sidebar-fixed {
                position: static;
                width: 100%;
                flex-shrink: auto;
            }

            .top-links {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .top-link {
                text-align: center;
            }

            .browse-grid,
            .trending-grid,
            .deal-grid,
            .loved-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .search-form {
                grid-template-columns: 1fr 1fr;
            }

            .search-field-shell,
            .search-submit-row {
                grid-column: 1 / -1;
            }

            .search-primary-field {
                grid-template-columns: 130px minmax(0, 1fr);
            }

            .search-dynamic-fields {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }

            .search-dynamic-fields .field.field-short {
                grid-column: span 2;
            }

            .search-dynamic-fields .field.field-medium,
            .search-dynamic-fields .field.field-date {
                grid-column: span 3;
            }

            .search-dynamic-fields .field.field-long {
                grid-column: span 6;
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .floating-sidebar {
                display: none;
            }

            .mobile-category-nav {
                display: block;
            }

            .header-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
            }

            .header-main {
                width: 100%;
                gap: 10px;
            }

            .header-brand {
                font-size: 1.65rem;
            }

            .header-links {
                width: 100%;
            }

            .account-menu-panel {
                left: 0;
                right: auto;
                width: min(340px, calc(100vw - 36px));
            }

            .page-with-sidebar {
                flex-direction: column;
            }

            .sidebar-fixed {
                position: static;
                width: 100%;
            }

            .top-links {
                grid-template-columns: 1fr;
                position: static;
            }

            .top-link {
                min-height: 56px;
                font-size: 0.76rem;
                text-align: left;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .search-section-full-width {
                overflow: hidden;
                padding: 22px 16px 20px;
            }

            .search-category-tabs {
                margin: 18px 0 12px;
                width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .search-shell {
                padding: 12px;
                border-radius: 16px;
            }

            .search-primary-field {
                grid-template-columns: 1fr;
            }

            .search-primary-field select {
                border-right: 0 !important;
                border-bottom: 1px solid #e2e9f1 !important;
            }

            .search-dynamic-fields {
                grid-template-columns: 1fr;
            }

            .search-dynamic-fields .field.field-short,
            .search-dynamic-fields .field.field-medium,
            .search-dynamic-fields .field.field-date,
            .search-dynamic-fields .field.field-long {
                grid-column: auto;
            }

            #accommodationFields {
                grid-template-columns: 1fr;
            }

            .mobile-category-row {
                flex-direction: column;
                gap: 8px;
                overflow-x: visible;
                overflow-y: visible;
            }

            .mobile-category-link {
                width: 100%;
                justify-content: flex-start;
            }
            
            .browse-grid,
            .trending-grid,
            .deal-grid,
            .loved-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $customerLoggedIn = (bool) session('portal_customer_authenticated', false);
        $customerName = trim((string) session('portal_customer_user', 'Customer'));
        $customerContinueUrl = request()->fullUrl();
        $homeTopCategoryLinks = $homeTopCategoryLinks ?? collect();
        $homePromoBanner = $homePromoBanner ?? ['message' => 'Promotions coming soon.', 'url' => '/catalog/accommodation', 'cta' => 'View Promotions'];
        $homeTrendingChips = $homeTrendingChips ?? collect();
        $homeBrowseCards = $homeBrowseCards ?? collect();
        $homeTrendingCards = $homeTrendingCards ?? collect();
        $homeWeekendDealCards = $homeWeekendDealCards ?? collect();
        $homeLovedCards = $homeLovedCards ?? collect();
        $cardSvgFallback = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';
    @endphp

    <aside class="floating-sidebar sidebar-shell" aria-label="Category sidebar">
        <p class="sidebar-title">Browse Categories</p>
        <section class="top-links" aria-label="Top categories">
            @foreach ($homeTopCategoryLinks as $link)
                @php
                    $linkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                    $categoryKeyFromUrl = preg_match('#/catalog/([a-z_-]+)#', $linkUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                @endphp
                <a class="top-link floating-link" data-category-key="{{ $categoryKeyFromUrl }}" href="{{ $linkUrl }}"><span class="top-link-head"><i class="{{ $link['icon'] ?? 'fa-solid fa-location-dot' }}"></i>{{ $link['title'] ?? 'Category' }}</span><span>{{ $link['subtitle'] ?? '' }}</span></a>
            @endforeach
        </section>
    </aside>

    <main class="page" data-api-base="{{ $apiBase }}">
        <header class="header-bar" aria-label="Member account actions">
            <div class="header-main">
                <span class="header-menu-button" aria-hidden="true"><i class="fa-solid fa-bars"></i></span>
                <div class="header-brand-wrap">
                    <div>
                        <p class="header-brand">Workation</p>
                        <p class="header-subline">Maldives travel marketplace</p>
                    </div>
                </div>
                <div class="header-search-mini" aria-label="Quick destination search">
                    <input type="search" placeholder="Destinations, islands, hotels, and experiences">
                    <button type="button" aria-label="Search destinations"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                <nav class="header-links" aria-label="Primary site navigation">
                    <a class="header-link" href="/catalog/accommodation">Hotels &amp; Homes</a>
                    <a class="header-link" href="/catalog/transport">Flights</a>
                    <a class="header-link" href="/catalog/marine-transport">Trains</a>
                    <a class="header-link" href="/catalog/land-transport">Car services</a>
                    <a class="header-link" href="/catalog/service">Attractions &amp; Tours</a>
                </nav>
            </div>
            <div class="customer-auth">
                <a class="header-link" href="/customer#bookings">My bookings</a>
                @if ($customerLoggedIn)
                    <div class="account-menu" data-customer-menu>
                        <button class="account-menu-toggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="customerMenuPanel">
                            <span class="account-avatar" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                            <span>Welcome, {{ $customerName }}</span>
                            <i class="fa-solid fa-chevron-down account-chevron" aria-hidden="true"></i>
                        </button>
                        <div id="customerMenuPanel" class="account-menu-panel" role="menu" hidden>
                            <div class="account-panel-head">
                                <p class="account-panel-greet">Hi, {{ $customerName }}</p>
                                <p class="account-panel-note">Great to see you again.</p>
                            </div>
                            <div class="account-panel-links">
                                <a class="account-panel-link" href="/customer#bookings" role="menuitem">My Bookings</a>
                                <a class="account-panel-link" href="/customer" role="menuitem">Manage my account</a>
                                <a class="account-panel-link" href="/customer#promos" role="menuitem">Promo codes</a>
                                <a class="account-panel-link" href="/customer#favourites" role="menuitem">Favourites</a>
                                <a class="account-panel-link" href="/customer#posts" role="menuitem">My posts</a>
                                <a class="account-panel-link" href="/customer#alerts" role="menuitem">Flight price alerts</a>
                            </div>
                            <div class="account-panel-foot">
                                <form method="POST" action="/portal/customer/logout" style="margin:0;">
                                    @csrf
                                    <button class="account-panel-logout" type="submit">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a class="auth-link" href="{{ '/portal/customer/login?continue=' . urlencode($customerContinueUrl) }}">Member Login</a>
                    <a class="auth-link primary" href="{{ '/portal/customer/register?continue=' . urlencode($customerContinueUrl) }}">Member Registration</a>
                @endif
            </div>
        </header>

        <details class="mobile-category-nav" aria-label="Mobile category quick links">
            <summary class="mobile-category-toggle">Browse Categories</summary>
            <div class="mobile-category-row">
                @foreach ($homeTopCategoryLinks as $link)
                    @php
                        $mobileLinkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                    @endphp
                    <a class="mobile-category-link" href="{{ $mobileLinkUrl }}"><i class="{{ $link['icon'] ?? 'fa-solid fa-location-dot' }}" aria-hidden="true"></i><span>{{ $link['title'] ?? 'Category' }}</span></a>
                @endforeach
            </div>
        </details>

        <div class="search-section-full-width" aria-label="Smart category search">
            <p class="search-eyebrow">Plan Your Dream Maldives Escape</p>
            <h1 class="search-title">Search stays, transfers, and island experiences with a travel-first booking flow.</h1>
            <div class="search-support-strip" aria-label="Trust signals">
                <span class="search-support-item"><i class="fa-solid fa-shield-heart"></i>Secure payment</span>
                <span class="search-support-item"><i class="fa-solid fa-headset"></i>Fast customer support</span>
                <span class="search-support-item"><i class="fa-solid fa-bolt"></i>Instant category search</span>
            </div>
            <div class="search-shell">
                <div class="search-category-tabs" aria-label="Travel search categories">
                    @foreach ($homeTopCategoryLinks->take(5) as $index => $link)
                        @php
                            $tabUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                            $tabCategoryKey = preg_match('#/catalog/([a-z_-]+)#', $tabUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                        @endphp
                        <button class="search-category-tab{{ $index === 0 ? ' is-active' : '' }}" type="button" data-home-category-tab="{{ $tabCategoryKey }}">
                            <i class="{{ $link['icon'] ?? 'fa-solid fa-location-dot' }}" aria-hidden="true"></i>
                            <span>{{ $link['title'] ?? 'Category' }}</span>
                        </button>
                    @endforeach
                </div>
                <form id="homeCatalogSearchForm" class="search-form" action="/catalog/accommodation" method="get">
                    <div class="search-field-shell">
                        <div class="search-primary-field">
                            <select id="categorySelect" name="category" aria-label="Select category">
                                @foreach ($homeTopCategoryLinks as $link)
                                    @php
                                        $linkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                                        $categoryKeyFromUrl = preg_match('#/catalog/([a-z_-]+)#', $linkUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                                    @endphp
                                    <option value="{{ $categoryKeyFromUrl }}">{{ $link['title'] ?? 'Category' }}</option>
                                @endforeach
                            </select>
                            <input type="search" name="q" placeholder="City, airport, island, landmark, hotel, or service name" aria-label="Search query">
                        </div>
                    </div>

                    <div id="accommodationFields" class="search-dynamic-fields is-active" data-fields-for="accommodation" aria-hidden="false">
                        <div class="field field-date"><label for="checkin">Check-in</label><input id="checkin" name="checkin" type="date"></div>
                        <div class="field field-date"><label for="checkout">Check-out</label><input id="checkout" name="checkout" type="date"></div>
                        <div class="field field-short"><label for="adults">Adults</label><input id="adults" name="adults" type="number" min="1" value="2"></div>
                        <div class="field field-short"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="0"></div>
                        <div class="field field-short"><label for="rooms">Rooms</label><input id="rooms" name="rooms" type="number" min="1" value="1"></div>
                    </div>

                    <div id="transportFields" class="search-dynamic-fields" data-fields-for="transport" hidden aria-hidden="true">
                        <div class="field field-medium"><label for="transportMode">Transport Mode</label><select id="transportMode" name="transport_mode"><option value="marine">Marine Transport</option><option value="land">Land Transport</option></select></div>
                        <div class="field field-medium" id="transportTripTypeField"><label for="transportTripType">Trip Type</label><select id="transportTripType" name="trip_type"><option value="one_way">One Way</option><option value="round_trip">Round Trip</option></select></div>
                        <div class="field field-long"><label for="transportFrom">From</label><input id="transportFrom" name="from" type="text" placeholder="Atoll or island"></div>
                        <div class="field field-long"><label for="transportTo">To</label><input id="transportTo" name="to" type="text" placeholder="Atoll or island"></div>
                        <div class="field field-date" id="transportDepartureDateField"><label for="travelDate">Departure</label><input id="travelDate" name="travel_date" type="date"></div>
                        <div class="field field-date" id="transportReturnDateField"><label for="returnDate">Return</label><input id="returnDate" name="return_date" type="date"></div>
                        <div class="field field-short"><label for="transportAdults">Adults</label><input id="transportAdults" name="adults" type="number" min="1" value="2"></div>
                        <div class="field field-short"><label for="transportChildren">Children</label><input id="transportChildren" name="children" type="number" min="0" value="0"></div>
                        <div class="field field-medium" id="transportVehicleTypeField"><label for="vehicleType">Vehicle Type</label><input id="vehicleType" name="vehicle_type" type="text" placeholder="Car, Van, Bike"></div>
                    </div>

                    <div id="serviceFields" class="search-dynamic-fields" data-fields-for="service" hidden aria-hidden="true">
                        <div class="field field-medium">
                            <label for="serviceAtoll">Atoll</label>
                            <select id="serviceAtoll" name="atoll">
                                <option value="">All Atolls</option>
                            </select>
                        </div>
                        <div class="field field-medium">
                            <label for="serviceIsland">Island</label>
                            <select id="serviceIsland" name="island">
                                <option value="">All Islands</option>
                            </select>
                        </div>
                        <div class="field field-short"><label for="minPrice">Min Price</label><input id="minPrice" name="min_price" type="number" min="0" placeholder="0"></div>
                        <div class="field field-short"><label for="maxPrice">Max Price</label><input id="maxPrice" name="max_price" type="number" min="0" placeholder="5000"></div>
                    </div>

                    <div class="search-submit-row">
                        <button class="primary" type="submit"><i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Search</button>
                    </div>
                </form>
            </div>
        </div>

        <section class="promo-banner" aria-label="Offers and promotions">
            <strong>{{ $homePromoBanner['message'] ?? 'Promotions coming soon.' }}</strong>
            <a class="primary" href="{{ $homePromoBanner['url'] ?? '/catalog/accommodation' }}">{{ $homePromoBanner['cta'] ?? 'View Promotions' }}</a>
        </section>

        <section class="section" aria-label="Browse by category, property, or service">
            <div class="section-head">
                <h2 class="section-title">Browse by Category / Property / Service</h2>
                <p class="section-sub">Quick entry points for what guests usually need first.</p>
            </div>
            <div class="browse-grid">
                @foreach ($homeBrowseCards as $card)
                    @php
                        $fallbackImage = (string) ($card['fallback_image_url'] ?? '');
                        $resolvedImage = (string) ($card['image_url'] ?? ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Category' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <strong>{{ $card['title'] ?? 'Category' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Explore listings in this category.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Trending destinations islands cities atolls">
            <div class="section-head">
                <h2 class="section-title">Trending Destinations: Islands, Cities, and Atolls</h2>
                <p class="section-sub">High-interest places guests are checking now.</p>
            </div>
            <div class="chip-row" aria-label="Trending filters">
                @foreach ($homeTrendingChips as $chip)
                    <span class="chip">{{ $chip }}</span>
                @endforeach
            </div>
            <div class="trending-grid">
                @foreach ($homeTrendingCards as $card)
                    @php
                        $fallbackImage = (string) ($card['fallback_image_url'] ?? '');
                        $resolvedImage = (string) ($card['image_url'] ?? ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Trending Destination' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <strong>{{ $card['title'] ?? 'Trending Destination' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Trending destination currently popular with guests.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Deals for the weekend">
            <div class="section-head">
                <h2 class="section-title">Deals for the Weekend</h2>
                <p class="section-sub">Easy picks for short breaks and quick getaways.</p>
            </div>
            <div class="deal-grid">
                @foreach ($homeWeekendDealCards as $card)
                    @php
                        $fallbackImage = (string) ($card['fallback_image_url'] ?? '');
                        $resolvedImage = (string) ($card['image_url'] ?? ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Weekend Deal' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            @if (!empty($card['meta']))
                                <span class="item-card-meta">{{ $card['meta'] }}</span>
                            @endif
                            <strong>{{ $card['title'] ?? 'Weekend Deal' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Recommended weekend offer for quick getaways.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section" aria-label="Places guests loved most">
            <div class="section-head">
                <h2 class="section-title">Places Guests Loved Most</h2>
                <p class="section-sub">Based on repeat views and top user interest.</p>
            </div>
            <div class="loved-grid">
                @foreach ($homeLovedCards as $card)
                    @php
                        $fallbackImage = (string) ($card['fallback_image_url'] ?? '');
                        $resolvedImage = (string) ($card['image_url'] ?? ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Loved Place' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            @if (!empty($card['meta']))
                                <span class="item-card-meta">{{ $card['meta'] }}</span>
                            @endif
                            <strong>{{ $card['title'] ?? 'Loved Place' }}</strong>
                            <span>{{ $card['subtitle'] ?? 'Highly rated by guests and repeat visitors.' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="home-footer-skin">
            @include('partials.global-site-footer')
        </div>
    </main>

    <script>
        (function () {
            const menuRoot = document.querySelector('[data-customer-menu]');
            if (!menuRoot) {
                return;
            }

            const menuToggle = menuRoot.querySelector('.account-menu-toggle');
            const menuPanel = menuRoot.querySelector('.account-menu-panel');
            if (!menuToggle || !menuPanel) {
                return;
            }

            function setMenuOpen(isOpen) {
                menuRoot.classList.toggle('is-open', isOpen);
                menuPanel.hidden = !isOpen;
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            menuToggle.addEventListener('click', function (event) {
                event.preventDefault();
                setMenuOpen(menuPanel.hidden);
            });

            document.addEventListener('click', function (event) {
                if (!menuRoot.contains(event.target)) {
                    setMenuOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setMenuOpen(false);
                }
            });
        })();
    </script>

    <script>
        (function () {
            const form = document.getElementById('homeCatalogSearchForm');
            const categorySelect = document.getElementById('categorySelect');
            const accommodationFields = document.getElementById('accommodationFields');
            const transportFields = document.getElementById('transportFields');
            const serviceFields = document.getElementById('serviceFields');
            const transportMode = document.getElementById('transportMode');
            const transportTripType = document.getElementById('transportTripType');
            const transportTripTypeField = document.getElementById('transportTripTypeField');
            const transportReturnDateField = document.getElementById('transportReturnDateField');
            const transportVehicleTypeField = document.getElementById('transportVehicleTypeField');
            const transportReturnDateInput = document.getElementById('returnDate');
            const transportVehicleTypeInput = document.getElementById('vehicleType');
            const serviceAtollSelect = document.getElementById('serviceAtoll');
            const serviceIslandSelect = document.getElementById('serviceIsland');
            const apiBase = String(document.querySelector('.page')?.getAttribute('data-api-base') || '').replace(/\/$/, '');
            const topCategoryLinks = Array.from(document.querySelectorAll('.top-link[data-category-key]'));
            const categoryTabs = Array.from(document.querySelectorAll('[data-home-category-tab]'));

            if (!form || !categorySelect || !accommodationFields || !transportFields || !serviceFields) {
                return;
            }

            function setFieldActive(fieldWrapper, isActive) {
                if (!fieldWrapper) {
                    return;
                }

                fieldWrapper.hidden = !isActive;
                fieldWrapper.querySelectorAll('input, select, textarea').forEach(function (control) {
                    control.disabled = !isActive;
                });
            }

            function fillSelect(select, options, emptyLabel) {
                if (!select) {
                    return;
                }

                const currentValue = String(select.value || '');
                select.innerHTML = '';

                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = emptyLabel;
                select.appendChild(emptyOption);

                options.forEach(function (entry) {
                    const option = document.createElement('option');
                    option.value = String(entry.value ?? '');
                    option.textContent = String(entry.label ?? entry.value ?? '');
                    select.appendChild(option);
                });

                if (currentValue !== '') {
                    const hasValue = Array.from(select.options).some(function (option) {
                        return String(option.value) === currentValue;
                    });

                    if (hasValue) {
                        select.value = currentValue;
                    }
                }
            }

            async function loadAtolls() {
                if (!serviceAtollSelect || !apiBase) {
                    return;
                }

                try {
                    const response = await fetch(apiBase + '/atolls', { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) {
                        return;
                    }

                    const rows = await response.json();
                    const options = Array.isArray(rows)
                        ? rows
                            .map(function (row) {
                                return {
                                    value: String(row.id ?? ''),
                                    label: String(row.name ?? '')
                                };
                            })
                            .filter(function (row) {
                                return row.value !== '' && row.label !== '';
                            })
                        : [];

                    fillSelect(serviceAtollSelect, options, 'All Atolls');
                } catch (error) {
                    // Keep default empty options when API is unavailable.
                }
            }

            async function loadIslandsByAtoll(atollId) {
                if (!serviceIslandSelect || !apiBase) {
                    return;
                }

                if (!atollId) {
                    fillSelect(serviceIslandSelect, [], 'All Islands');
                    return;
                }

                try {
                    const response = await fetch(apiBase + '/islands?atollId=' + encodeURIComponent(String(atollId)), {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) {
                        fillSelect(serviceIslandSelect, [], 'All Islands');
                        return;
                    }

                    const rows = await response.json();
                    const options = Array.isArray(rows)
                        ? rows
                            .map(function (row) {
                                return {
                                    value: String(row.name ?? ''),
                                    label: String(row.name ?? '')
                                };
                            })
                            .filter(function (row) {
                                return row.value !== '';
                            })
                        : [];

                    fillSelect(serviceIslandSelect, options, 'All Islands');
                } catch (error) {
                    fillSelect(serviceIslandSelect, [], 'All Islands');
                }
            }

            function toggleTransportModeFields() {
                if (!transportMode || !transportTripType) {
                    return;
                }

                const mode = String(transportMode.value || 'marine').toLowerCase();
                const tripType = String(transportTripType.value || 'one_way').toLowerCase();
                const isLand = mode === 'land';
                const isMarineRoundTrip = mode === 'marine' && tripType === 'round_trip';

                setFieldActive(transportTripTypeField, !isLand);
                setFieldActive(transportVehicleTypeField, isLand);
                setFieldActive(transportReturnDateField, isLand || isMarineRoundTrip);

                if (!isLand && !isMarineRoundTrip && transportReturnDateInput) {
                    transportReturnDateInput.value = '';
                }

                if (!isLand && transportVehicleTypeInput) {
                    transportVehicleTypeInput.value = '';
                }
            }

            function resolveGroup(category) {
                if (category === 'accommodation') {
                    return 'accommodation';
                }

                if (category === 'marine-transport' || category === 'land-transport' || category === 'transport') {
                    return 'transport';
                }

                return 'service';
            }

            function toggleFields() {
                const category = String(categorySelect.value || 'accommodation').toLowerCase();
                const group = resolveGroup(category);
                const groups = [
                    { key: 'accommodation', el: accommodationFields },
                    { key: 'transport', el: transportFields },
                    { key: 'service', el: serviceFields }
                ];

                form.setAttribute('action', '/catalog/' + category);

                groups.forEach(function (entry) {
                    const isActive = entry.key === group;
                    entry.el.hidden = !isActive;
                    entry.el.classList.toggle('is-active', isActive);
                    entry.el.setAttribute('aria-hidden', isActive ? 'false' : 'true');

                    entry.el.querySelectorAll('input, select, textarea').forEach(function (control) {
                        control.disabled = !isActive;
                    });
                });

                if (group === 'transport') {
                    if (transportMode) {
                        transportMode.value = category === 'land-transport' ? 'land' : 'marine';
                    }
                    toggleTransportModeFields();
                }

                if (categoryTabs.length > 0) {
                    categoryTabs.forEach(function (tab) {
                        tab.classList.toggle('is-active', String(tab.getAttribute('data-home-category-tab') || '').toLowerCase() === category);
                    });
                }
            }

            if (transportMode) {
                transportMode.addEventListener('change', toggleTransportModeFields);
            }

            if (transportTripType) {
                transportTripType.addEventListener('change', toggleTransportModeFields);
            }

            if (serviceAtollSelect) {
                serviceAtollSelect.addEventListener('change', function () {
                    loadIslandsByAtoll(serviceAtollSelect.value || '');
                });
            }

            if (topCategoryLinks.length > 0) {
                topCategoryLinks.forEach(function (link) {
                    // Skip floating sidebar links - let them navigate naturally
                    if (link.classList.contains('floating-link')) {
                        return;
                    }

                    link.addEventListener('click', function (event) {
                        const categoryKey = String(link.getAttribute('data-category-key') || '').toLowerCase();
                        if (!categoryKey || !categorySelect.querySelector('option[value="' + categoryKey + '"]')) {
                            return;
                        }

                        event.preventDefault();
                        categorySelect.value = categoryKey;
                        toggleFields();
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });
            }

            if (categoryTabs.length > 0) {
                categoryTabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        const categoryKey = String(tab.getAttribute('data-home-category-tab') || '').toLowerCase();
                        if (!categoryKey || !categorySelect.querySelector('option[value="' + categoryKey + '"]')) {
                            return;
                        }

                        categorySelect.value = categoryKey;
                        toggleFields();
                    });
                });
            }

            categorySelect.addEventListener('change', toggleFields);
            loadAtolls();
            loadIslandsByAtoll('');
            toggleFields();
        })();
    </script>
</body>
</html>