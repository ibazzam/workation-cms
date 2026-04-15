<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoryMeta['label'] }} Catalogue | Workation</title>
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
            --brand: #0f6179;
            --brand-strong: #0b4f66;
            --accent: #f3a337;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .page {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto 28px;
        }

        .header-bar {
            min-height: 84px;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 0 24px;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            margin: 0;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            transition: none;
            z-index: 10;
            backdrop-filter: none;
        }

        .page.is-header-hidden .header-bar {
            transform: none;
            opacity: 1;
            pointer-events: auto;
        }

        .header-category-tabs {
            width: 100%;
            margin-top: 0;
            flex: 1 1 auto;
            min-width: 0;
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
            flex: 1 1 auto;
        }

        .header-brand-wrap {
            display: grid;
            gap: 2px;
            align-content: center;
            flex-shrink: 0;
            padding-left: 6px;
        }

        .header-menu-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid rgba(217, 235, 245, 0.4);
            border-radius: 10px;
            background: rgba(245, 252, 255, 0.18);
            color: #e7f8ff;
            font-size: 0.82rem;
        }

        .header-brand {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 900;
            color: #f3fbff;
            letter-spacing: -0.04em;
            line-height: 1;
            text-shadow: 0 4px 16px rgba(8, 30, 85, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .header-brand small {
            color: rgba(233, 248, 255, 0.82);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 800;
            text-shadow: none;
        }

        .header-brand-link {
            color: #f3fbff;
            text-decoration: none;
        }

        .header-brand-link:hover {
            color: #ffffff;
        }

        .header-subline {
            margin: 1px 0 0;
            font-size: 0.7rem;
            color: rgba(235, 246, 255, 0.9);
            white-space: nowrap;
        }

        .header-search-mini {
            display: none;
            align-items: center;
            min-width: 0;
            width: min(460px, 100%);
            border: 1px solid rgba(209, 232, 245, 0.32);
            border-radius: 10px;
            overflow: hidden;
            background: rgba(246, 252, 255, 0.92);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.55);
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

        .header-search-mini button {
            border: 0;
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            cursor: pointer;
            flex-shrink: 0;
        }

        .customer-auth {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .header-link {
            text-decoration: none;
            color: #e9f5ff;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .header-links {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 2px;
            min-width: 0;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .header-links::-webkit-scrollbar {
            display: none;
        }

        .header-link:hover {
            border-color: rgba(233, 248, 255, 0.35);
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
        }

        .auth-link {
            text-decoration: none;
            border: 1px solid rgba(225, 242, 251, 0.55);
            border-radius: 10px;
            padding: 7px 12px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
        }

        .auth-link:hover {
            background: rgba(255, 255, 255, 0.24);
            color: #ffffff;
        }

        .auth-link.primary {
            background: linear-gradient(135deg, #0f6179 0%, #1e7d90 100%);
            border-color: #0f6179;
            color: #ffffff;
        }

        .account-menu {
            position: relative;
        }

        .account-menu-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(226, 243, 251, 0.62);
            border-radius: 11px;
            padding: 6px 10px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(8, 33, 49, 0.22);
        }

        .account-avatar {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
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

        .floating-sidebar {
            display: none;
        }

        .page-body-split {
            display: block;
            margin-top: 0;
        }

        .page-main-content {
            flex: 1;
            min-width: 0;
        }

        .sidebar-title {
            margin: 0 0 8px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4e6d83;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .top-links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .top-link {
            text-decoration: none;
            border: 1px solid #d4e3ee;
            border-radius: 10px;
            background: #f8fcff;
            color: #19405b;
            padding: 9px 10px;
            font-size: 0.8rem;
            line-height: 1.28;
            font-weight: 600;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-height: 56px;
            justify-content: center;
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .top-link:hover {
            border-color: #8db5cf;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(34, 86, 120, 0.16);
        }

        .top-link.is-active {
            border-color: #6ca6c3;
            background: #ebf6ff;
            box-shadow: 0 4px 10px rgba(22, 70, 102, 0.12);
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
                margin-bottom: 10px;
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

        .journey-hero {
            position: relative;
            border: 0;
            border-radius: 0;
            overflow: visible;
            background: none;
            margin-top: 0;
            padding: 0;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
        }

        .hero-banner {
            position: relative;
            min-height: 360px;
            border-radius: 0;
            overflow: hidden;
            background: linear-gradient(140deg, #1a57c4 0%, #3d7de8 48%, #7fa7ff 100%);
            box-shadow: none;
        }

        .hero-banner::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(7, 34, 95, 0.24) 0%, rgba(7, 34, 95, 0.4) 100%);
            pointer-events: none;
        }

        .hero-banner-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .hero-banner-content {
            position: absolute;
            top: 96px;
            left: 50%;
            transform: translateX(-50%);
            width: min(1120px, calc(100% - 56px));
            z-index: 2;
            display: grid;
            gap: 12px;
        }

        .hero-banner-title {
            margin: 0;
            color: #f5fbff;
            font-size: clamp(1.8rem, 3.2vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            text-shadow: 0 6px 24px rgba(6, 27, 74, 0.35);
        }

        .header-category-tabs .header-link.is-active {
            background: rgba(255, 255, 255, 0.94);
            color: #154e71;
        }

        .search-sticky-wrap {
            position: static;
            z-index: 2;
            width: 100%;
            padding: 0;
            transform: none;
        }

        .hero {
            border: 1px solid rgba(212, 236, 245, 0.24);
            border-radius: 12px;
            background: transparent;
            color: #ecfcff;
            padding: 6px 2px 2px;
        }

        .hero p {
            margin: 0;
        }

        .hero h1 {
            margin: 6px 0;
            font-size: clamp(1.4rem, 2.6vw, 2.4rem);
        }

        .hero-links {
            margin-top: 10px;
            display: flex;
            flex-wrap: nowrap;
            gap: 7px;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .hero-links::-webkit-scrollbar {
            display: none;
        }

        .hero-links a {
            text-decoration: none;
            border: 1px solid rgba(214, 244, 248, 0.44);
            background: rgba(4, 64, 83, 0.2);
            color: #eafcff;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .hero-links a.is-active {
            background: rgba(255, 255, 255, 0.96);
            border-color: rgba(255, 255, 255, 0.96);
            color: #0f6179;
        }

        .search-box {
            margin-top: 0;
            border: 1px solid #d5deeb;
            border-radius: 10px;
            background: var(--surface);
            padding: 12px;
            display: grid;
            gap: 8px;
            overflow: hidden;
            box-shadow: 0 12px 26px rgba(14, 41, 92, 0.2);
            position: static;
            z-index: auto;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .catalog-section-title {
            margin: 16px 0 8px;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: flex-end;
            min-width: 0;
        }

        .field {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .field.field-short {
            flex: 0 0 68px;
            width: 68px;
        }

        .field.field-medium {
            flex: 0 1 140px;
            min-width: 110px;
        }

        .field.field-date {
            flex: 0 0 148px;
            width: 148px;
        }

        .field.field-long {
            flex: 1 1 160px;
            min-width: 130px;
        }

        .field label {
            display: block;
            margin-bottom: 3px;
            font-size: 0.7rem;
            color: #4b6378;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .field input,
        .field select {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border: 1px solid #c8d8e5;
            border-radius: 8px;
            padding: 5px 8px;
            font: inherit;
            font-size: 0.875rem;
            height: 36px;
            line-height: 1.4;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .field input[type="date"],
        .field input[type="datetime-local"] {
            appearance: none;
            -webkit-appearance: none;
            display: block;
            min-height: 36px;
            padding-right: 4px;
            font-size: 14px;
            overflow: hidden;
        }
        
        .field select {
            padding: 4px 8px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .section-title {
            margin: 14px 0 0;
            font-size: 1rem;
        }

        .catalog-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .card {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #fbfdff;
            overflow: hidden;
        }

        .card-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .card img {
            width: 100%;
            height: 184px;
            object-fit: cover;
            background: #edf4fb;
            display: block;
        }

        .card-body {
            padding: 10px;
            display: grid;
            gap: 6px;
        }

        .card-city {
            color: #698094;
            font-size: 0.7rem;
            line-height: 1;
        }

        .card h3 {
            margin: 0;
            font-size: 1rem;
            line-height: 1.3;
            color: #102f45;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-stars {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            color: #f3a337;
            font-size: 0.72rem;
            min-height: 14px;
        }

        .card-review {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #587085;
            font-size: 0.73rem;
        }

        .card-rating-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 18px;
            padding: 0 6px;
            border-radius: 6px;
            background: #1f4fd6;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1;
        }

        .card-price {
            margin-top: 2px;
            color: #0d2e44;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .empty {
            margin-top: 10px;
            border: 1px dashed #cddbe8;
            background: #f7fbff;
            color: #3f5a72;
            border-radius: 12px;
            padding: 14px;
            font-size: 0.88rem;
        }

        .island-context-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            border: 1px solid #b6daea;
            border-radius: 10px;
            background: #eaf6fb;
            color: #1a4b62;
            padding: 9px 12px;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .island-context-note i {
            color: #0f6179;
            margin-top: 2px;
            flex: 0 0 14px;
            text-align: center;
        }

        @media (max-width: 1040px) {
            .page {
                width: calc(100% - 20px);
                margin: 0 auto 30px;
            }

            .header-main {
                flex-wrap: wrap;
            }

            .header-category-tabs {
                justify-content: flex-start;
            }

            .header-search-mini {
                width: 100%;
                order: 3;
            }

            .page-body-split {
                display: block;
            }

            .header-bar {
                padding: 10px 14px;
            }

            .journey-hero {
                padding: 0;
            }

            .hero-banner {
                min-height: 332px;
            }

            .hero-banner-content {
                width: calc(100% - 28px);
                top: 88px;
            }

            .catalog-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .field.field-short {
                flex: 0 0 64px;
                width: 64px;
            }

            .field.field-date {
                flex: 0 0 138px;
                width: 138px;
            }

        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .page.is-header-hidden .header-bar {
                transform: none;
                opacity: 1;
                pointer-events: auto;
            }

            .search-sticky-wrap {
                position: static;
                top: auto;
                z-index: auto;
                margin-top: 0;
                transform: none;
                width: 100%;
            }

            .header-bar {
                flex-direction: column;
                align-items: stretch;
                position: absolute;
                width: 100%;
                margin: 0;
                padding: 10px 12px;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
            }

            .header-main {
                width: 100%;
                gap: 10px;
            }

            .header-category-tabs {
                width: 100%;
                flex: 0 0 100%;
            }

            .header-brand {
                font-size: 1.65rem;
            }

            .header-brand-wrap {
                padding-left: 0;
            }

            .mobile-category-nav {
                display: block;
            }

            .journey-hero {
                margin-top: 0;
                padding: 0;
            }

            .hero-banner {
                min-height: 0;
                overflow: visible;
                padding: 126px 0 14px;
            }

            .hero-banner-content {
                position: relative;
                left: auto;
                transform: none;
                width: calc(100% - 18px);
                top: auto;
                margin: 0 auto;
                gap: 8px;
            }

            .search-box {
                margin-top: 4px;
            }

            .hero-banner-title {
                font-size: clamp(1.45rem, 7.4vw, 2rem);
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

            .top-links {
                grid-template-columns: 1fr;
            }

            .catalog-grid {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .field.field-short,
            .field.field-medium,
            .field.field-date,
            .field.field-long {
                grid-column: auto;
            }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body class="category-display-page">
    @php
        $filters = $filters ?? [];
        $categoryKey = $categoryKey ?? 'accommodation';
        $categoryMeta = $categoryMeta ?? ['label' => 'Catalogue', 'subtitle' => ''];
        $customerLoggedIn = (bool) session('portal_customer_authenticated', false);
        $customerName = trim((string) session('portal_customer_user', 'Customer'));
        $customerContinueUrl = request()->fullUrl();
        $catalogCategoryLinks = collect([
            ['key' => 'accommodation',    'icon' => 'fa-solid fa-hotel',          'title' => 'Accommodation',   'subtitle' => 'Hotels, resorts, villas'],
            ['key' => 'marine-transport',  'icon' => 'fa-solid fa-water',          'title' => 'Marine Transport','subtitle' => 'Speedboats & water transfers'],
            ['key' => 'land-transport',    'icon' => 'fa-solid fa-van-shuttle',    'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers'],
            ['key' => 'excursion',        'icon' => 'fa-solid fa-compass',       'title' => 'Excursion',       'subtitle' => 'Tours and activities'],
            ['key' => 'remote_workspace', 'icon' => 'fa-solid fa-laptop',         'title' => 'Remote Workspace','subtitle' => 'Work-friendly spaces'],
            ['key' => 'conference_room',  'icon' => 'fa-solid fa-object-group',   'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces'],
            ['key' => 'resort_day_visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit','subtitle' => 'Day-use resort offers'],
            ['key' => 'restaurant',       'icon' => 'fa-solid fa-utensils',       'title' => 'Restaurant',      'subtitle' => 'Dining experiences'],
            ['key' => 'vehicle_rental',   'icon' => 'fa-solid fa-car',            'title' => 'Vehicle Rental',  'subtitle' => 'Cars and local rentals'],
        ]);
        $catalogProperties = $catalogProperties ?? collect();
        $catalogPropertyMediaByProperty = $catalogPropertyMediaByProperty ?? collect();
        $atollOptions = $atollOptions ?? collect();
        $islandOptions = $islandOptions ?? collect();
        $popularityScore = static function ($property): float {
            $bookings = (float) ($property->booking_count ?? $property->bookings_count ?? 0);
            $reviews = (float) ($property->reviews_count ?? 0);
            $rating = (float) ($property->rating ?? $property->average_rating ?? 0);
            $views = (float) ($property->view_count ?? 0);

            return ($bookings * 6.0) + ($reviews * 3.0) + ($rating * 10.0) + ($views * 0.2);
        };

        $popularOverall = $catalogProperties
            ->sortByDesc(static fn ($property) => $popularityScore($property))
            ->take(8)
            ->values();

        $popularByAtoll = $catalogProperties
            ->filter(static fn ($property) => trim((string) ($property->atoll ?? '')) !== '')
            ->groupBy(static fn ($property) => trim((string) ($property->atoll ?? '')))
            ->map(static fn ($group) => $group->sortByDesc(static fn ($property) => $popularityScore($property))->take(6)->values())
            ->filter(static fn ($group) => $group->isNotEmpty())
            ->take(3);

        $popularByIsland = $catalogProperties
            ->filter(static fn ($property) => trim((string) ($property->island ?? '')) !== '')
            ->groupBy(static fn ($property) => trim((string) ($property->island ?? '')))
            ->map(static fn ($group) => $group->sortByDesc(static fn ($property) => $popularityScore($property))->take(6)->values())
            ->filter(static fn ($group) => $group->isNotEmpty())
            ->take(3);
        $mediaVariantUrl = static function ($media, string $variant = 'banner'): ?string {
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
        $categoryHeroImageUrl = trim((string) ($categoryMeta['hero_image_url'] ?? ''));
        if ($categoryHeroImageUrl === '') {
            $categoryHeroKey = trim((string) ($categoryKey ?? ''));
            if ($categoryHeroKey !== '') {
                $categoryHeroImageUrl = '/media/portal/hero/' . $categoryHeroKey;
            }
        }
        $categoryHeroFallback = "data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%271800%27 height=%27720%27 viewBox=%270 0 1800 720%27%3E%3Cdefs%3E%3ClinearGradient id=%27g%27 x1=%270%27 y1=%270%27 x2=%271%27 y2=%271%27%3E%3Cstop offset=%270%25%27 stop-color=%27%230f6d8f%27/%3E%3Cstop offset=%2748%25%27 stop-color=%27%231d88a8%27/%3E%3Cstop offset=%27100%25%27 stop-color=%27%233fb8d1%27/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%271800%27 height=%27720%27 fill=%27url(%23g)%27/%3E%3Cpath d=%27M0 490 C260 440 430 565 700 520 C930 482 1150 350 1410 400 C1580 432 1700 505 1800 548 L1800 720 L0 720 Z%27 fill=%27rgba(255,255,255,0.22)%27/%3E%3Cpath d=%27M0 560 C200 510 410 610 640 588 C860 567 1020 500 1240 515 C1490 532 1670 606 1800 655 L1800 720 L0 720 Z%27 fill=%27rgba(255,255,255,0.30)%27/%3E%3C/svg%3E";
        if ($categoryHeroImageUrl === '') {
            $categoryHeroImageUrl = $categoryHeroFallback;
        }
    @endphp

    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="journey-hero" aria-label="Category hero and quick navigation">
            <header class="header-bar" aria-label="Member account actions">
                <div class="header-main">
                    <div class="header-brand-wrap">
                        <a class="header-brand header-brand-link" href="/">Workation</a>
                        <p class="header-subline">Maldives Travel Market</p>
                    </div>
                    <nav class="header-links header-category-tabs" aria-label="Category tabs in header">
                        @foreach ($catalogCategoryLinks as $item)
                            <a class="header-link{{ $categoryKey === ($item['key'] ?? '') ? ' is-active' : '' }}" href="{{ '/catalog/' . ($item['key'] ?? 'accommodation') }}">{{ $item['title'] ?? 'Category' }}</a>
                        @endforeach
                        <a class="header-link" href="/blog">Travel picks</a>
                    </nav>
                    <div class="header-search-mini" aria-label="Quick destination search">
                        <input type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Destinations, islands, hotels, and experiences">
                        <button type="button" aria-label="Search destinations"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </div>
                <div class="customer-auth">
                    @if ($customerLoggedIn)
                        <a class="header-link" href="/customer#bookings">My bookings</a>
                        <div class="account-menu" data-customer-menu>
                            <button class="account-menu-toggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="customerMenuPanelCatalog">
                                <span class="account-avatar" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                                <span>Welcome, {{ $customerName }}</span>
                                <i class="fa-solid fa-chevron-down account-chevron" aria-hidden="true"></i>
                            </button>
                            <div id="customerMenuPanelCatalog" class="account-menu-panel" role="menu" hidden>
                                <div class="account-panel-head">
                                    <p class="account-panel-greet">Hi, {{ $customerName }}</p>
                                    <p class="account-panel-note">Great to see you again.</p>
                                </div>
                                <div class="account-panel-links">
                                    <a class="account-panel-link" href="/customer#bookings" role="menuitem">My Bookings</a>
                                    <a class="account-panel-link" href="/customer" role="menuitem">Manage my account</a>
                                    <a class="account-panel-link" href="/customer#promos" role="menuitem">Promo codes</a>
                                    <a class="account-panel-link" href="/customer#favourites" role="menuitem">Favourites</a>
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

            <div class="hero-banner" aria-label="Category banner and quick filters">
                <img class="hero-banner-image" src="{{ $categoryHeroImageUrl }}" alt="{{ (string) ($categoryMeta['label'] ?? 'Category') }} banner" loading="eager" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ $categoryHeroFallback }}';">
                <div class="hero-banner-content">
                    <h1 class="hero-banner-title">{{ (string) ($categoryMeta['label'] ?? 'Category') }}.</h1>
                    <div class="search-sticky-wrap">
                        <form class="search-box" method="GET" action="/catalog/{{ $categoryKey }}">
            <div class="grid">
                <div class="field field-long">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Atoll, island, place, or property name">
                </div>
                <div class="field field-medium">
                    <label for="atoll">Atoll</label>
                    <select id="atoll" name="atoll">
                        <option value="">All Atolls</option>
                        @foreach ($atollOptions as $atoll)
                            <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field field-medium">
                    <label for="island">Island / City</label>
                    <select id="island" name="island">
                        <option value="">All Islands/Cities</option>
                        @foreach ($islandOptions as $island)
                            <option value="{{ $island }}" {{ ($filters['island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field field-medium">
                    <label for="sort">Sort</label>
                    <select id="sort" name="sort">
                        <option value="recommended" {{ ($filters['sort'] ?? '') === 'recommended' ? 'selected' : '' }}>Recommended</option>
                        <option value="most_wanted" {{ ($filters['sort'] ?? '') === 'most_wanted' ? 'selected' : '' }}>Most Wanted</option>
                        <option value="most_booked" {{ ($filters['sort'] ?? '') === 'most_booked' ? 'selected' : '' }}>Most Booked</option>
                        <option value="highest_reviews" {{ ($filters['sort'] ?? '') === 'highest_reviews' ? 'selected' : '' }}>Highest Reviews</option>
                        <option value="price_low_high" {{ ($filters['sort'] ?? '') === 'price_low_high' ? 'selected' : '' }}>Price Low to High</option>
                        <option value="price_high_low" {{ ($filters['sort'] ?? '') === 'price_high_low' ? 'selected' : '' }}>Price High to Low</option>
                    </select>
                </div>
                <div class="field field-short">
                    <label for="min_price">Min Price</label>
                    <input id="min_price" name="min_price" type="number" min="0" value="{{ $filters['min_price'] ?? 0 }}">
                </div>
                <div class="field field-short">
                    <label for="max_price">Max Price</label>
                    <input id="max_price" name="max_price" type="number" min="0" value="{{ $filters['max_price'] ?? 0 }}">
                </div>
            </div>

            @if ($categoryKey === 'accommodation')
                <div class="grid">
                    <div class="field field-date"><label for="checkin">Check-in Date</label><input id="checkin" name="checkin" type="date" value="{{ $filters['checkin'] ?? '' }}"></div>
                    <div class="field field-date"><label for="checkout">Check-out Date</label><input id="checkout" name="checkout" type="date" value="{{ $filters['checkout'] ?? '' }}"></div>
                    <div class="field field-short"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                    <div class="field field-short"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                    <div class="field field-short"><label for="rooms">Rooms</label><input id="rooms" name="rooms" type="number" min="1" value="{{ $filters['rooms'] ?? 1 }}"></div>
                </div>
            @elseif ($categoryKey === 'marine-transport' || $categoryKey === 'land-transport')
                <div class="grid">
                    <div class="field field-long"><label for="origin_point">From (Island/Location)</label><input id="origin_point" name="origin_point" type="text" value="{{ $filters['origin_point'] ?? '' }}"></div>
                    <div class="field field-long"><label for="destination_point">To (Island/Location)</label><input id="destination_point" name="destination_point" type="text" value="{{ $filters['destination_point'] ?? '' }}"></div>
                    <div class="field field-date"><label for="travel_date">Travel Date</label><input id="travel_date" name="travel_date" type="date" value="{{ $filters['travel_date'] ?? '' }}"></div>
                    <div class="field field-date"><label for="return_date">Return Date</label><input id="return_date" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}"></div>
                    <div class="field field-short"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                    <div class="field field-short"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                </div>
            @elseif ($categoryKey === 'restaurant')
                <div class="island-context-note" style="margin-bottom:8px;">
                    <i class="fa-solid fa-water" aria-hidden="true"></i>
                    <span>Restaurants in the Maldives are <strong>island-specific</strong>. Select the island where you are currently staying or planning to visit to see what’s available at that location.</span>
                </div>
                <div class="grid">
                    <div class="field field-medium">
                        <label for="current_island">Your Current Island / Stay Location</label>
                        <select id="current_island" name="current_island">
                            <option value="">All Islands</option>
                            @foreach ($islandOptions as $island)
                                <option value="{{ $island }}" {{ ($filters['current_island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="atoll_restaurant">Atoll (Optional)</label>
                        <select id="atoll_restaurant" name="atoll">
                            <option value="">All Atolls</option>
                            @foreach ($atollOptions as $atoll)
                                <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-date">
                        <label for="reservation_datetime">Reservation Date &amp; Time</label>
                        <input id="reservation_datetime" name="reservation_datetime" type="datetime-local" value="{{ $filters['reservation_datetime'] ?? '' }}">
                    </div>
                    <div class="field field-short">
                        <label for="party_size">Party Size</label>
                        <input id="party_size" name="party_size" type="number" min="1" value="{{ $filters['party_size'] ?? 2 }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'excursion')
                <div class="grid">
                    <div class="field field-medium">
                        <label for="activity_type">Activity Type</label>
                        <select id="activity_type" name="activity_type">
                            <option value="">All Activities</option>
                            <option value="water_sports" {{ ($filters['activity_type'] ?? '') === 'water_sports' ? 'selected' : '' }}>Water Sports</option>
                            <option value="cultural" {{ ($filters['activity_type'] ?? '') === 'cultural' ? 'selected' : '' }}>Cultural</option>
                            <option value="adventure" {{ ($filters['activity_type'] ?? '') === 'adventure' ? 'selected' : '' }}>Adventure</option>
                            <option value="relaxation" {{ ($filters['activity_type'] ?? '') === 'relaxation' ? 'selected' : '' }}>Relaxation</option>
                            <option value="wildlife" {{ ($filters['activity_type'] ?? '') === 'wildlife' ? 'selected' : '' }}>Wildlife</option>
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="difficulty">Difficulty Level</label>
                        <select id="difficulty" name="difficulty">
                            <option value="">All Levels</option>
                            <option value="easy" {{ ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="moderate" {{ ($filters['difficulty'] ?? '') === 'moderate' ? 'selected' : '' }}>Moderate</option>
                            <option value="challenging" {{ ($filters['difficulty'] ?? '') === 'challenging' ? 'selected' : '' }}>Challenging</option>
                        </select>
                    </div>
                    <div class="field field-date">
                        <label for="excursion_date">Excursion Date</label>
                        <input id="excursion_date" name="excursion_date" type="date" value="{{ $filters['excursion_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'remote_workspace')
                <div class="grid">
                    <div class="field field-medium">
                        <label for="workspace_type_filter">Workspace Type</label>
                        <select id="workspace_type_filter" name="workspace_type_filter">
                            <option value="">All Types</option>
                            <option value="coworking" {{ ($filters['workspace_type_filter'] ?? '') === 'coworking' ? 'selected' : '' }}>Co-working Space</option>
                            <option value="cafe" {{ ($filters['workspace_type_filter'] ?? '') === 'cafe' ? 'selected' : '' }}>Cafe / Coffee Shop</option>
                            <option value="library" {{ ($filters['workspace_type_filter'] ?? '') === 'library' ? 'selected' : '' }}>Library</option>
                            <option value="private" {{ ($filters['workspace_type_filter'] ?? '') === 'private' ? 'selected' : '' }}>Private Office</option>
                            <option value="resort" {{ ($filters['workspace_type_filter'] ?? '') === 'resort' ? 'selected' : '' }}>Resort Workspace</option>
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="internet_speed">Internet Speed</label>
                        <select id="internet_speed" name="internet_speed">
                            <option value="">Any Speed</option>
                            <option value="high" {{ ($filters['internet_speed'] ?? '') === 'high' ? 'selected' : '' }}>High Speed (100+ Mbps)</option>
                            <option value="standard" {{ ($filters['internet_speed'] ?? '') === 'standard' ? 'selected' : '' }}>Standard (50+ Mbps)</option>
                            <option value="basic" {{ ($filters['internet_speed'] ?? '') === 'basic' ? 'selected' : '' }}>Basic (20+ Mbps)</option>
                        </select>
                    </div>
                    <div class="field field-date">
                        <label for="workspace_start">Start Date</label>
                        <input id="workspace_start" name="workspace_start" type="date" value="{{ $filters['workspace_start'] ?? '' }}">
                    </div>
                    <div class="field field-date">
                        <label for="workspace_end">End Date</label>
                        <input id="workspace_end" name="workspace_end" type="date" value="{{ $filters['workspace_end'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'conference_room')
                <div class="grid">
                    <div class="field field-medium">
                        <label for="conference_event_type">Event Type</label>
                        <select id="conference_event_type" name="conference_event_type">
                            <option value="">All Event Types</option>
                            <option value="meeting" {{ ($filters['conference_event_type'] ?? '') === 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="training" {{ ($filters['conference_event_type'] ?? '') === 'training' ? 'selected' : '' }}>Training</option>
                            <option value="seminar" {{ ($filters['conference_event_type'] ?? '') === 'seminar' ? 'selected' : '' }}>Seminar</option>
                            <option value="conference" {{ ($filters['conference_event_type'] ?? '') === 'conference' ? 'selected' : '' }}>Conference</option>
                            <option value="workshop" {{ ($filters['conference_event_type'] ?? '') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                        </select>
                    </div>
                    <div class="field field-short">
                        <label for="conference_capacity">Minimum Capacity (Attendees)</label>
                        <input id="conference_capacity" name="conference_capacity" type="number" min="1" value="{{ $filters['conference_capacity'] ?? 0 }}">
                    </div>
                    <div class="field field-date">
                        <label for="conference_date">Event Date</label>
                        <input id="conference_date" name="conference_date" type="date" value="{{ $filters['conference_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'resort_day_visit')
                <div class="grid">
                    <div class="field field-medium">
                        <label for="time_slot">Time Slot</label>
                        <select id="time_slot" name="time_slot">
                            <option value="">Any Time</option>
                            <option value="morning" {{ ($filters['time_slot'] ?? '') === 'morning' ? 'selected' : '' }}>Morning Half-day (6am-12pm)</option>
                            <option value="afternoon" {{ ($filters['time_slot'] ?? '') === 'afternoon' ? 'selected' : '' }}>Afternoon Half-day (12pm-6pm)</option>
                            <option value="evening" {{ ($filters['time_slot'] ?? '') === 'evening' ? 'selected' : '' }}>Evening (3pm-9pm)</option>
                            <option value="fullday" {{ ($filters['time_slot'] ?? '') === 'fullday' ? 'selected' : '' }}>Full Day</option>
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="facility_type">Facility Type</label>
                        <select id="facility_type" name="facility_type">
                            <option value="">All Facilities</option>
                            <option value="beach" {{ ($filters['facility_type'] ?? '') === 'beach' ? 'selected' : '' }}>Beach Access</option>
                            <option value="pool" {{ ($filters['facility_type'] ?? '') === 'pool' ? 'selected' : '' }}>Swimming Pool</option>
                            <option value="spa" {{ ($filters['facility_type'] ?? '') === 'spa' ? 'selected' : '' }}>Spa & Wellness</option>
                            <option value="water_sports" {{ ($filters['facility_type'] ?? '') === 'water_sports' ? 'selected' : '' }}>Water Sports</option>
                            <option value="dining" {{ ($filters['facility_type'] ?? '') === 'dining' ? 'selected' : '' }}>Dining Experience</option>
                        </select>
                    </div>
                    <div class="field field-date">
                        <label for="visit_date">Visit Date</label>
                        <input id="visit_date" name="visit_date" type="date" value="{{ $filters['visit_date'] ?? '' }}">
                    </div>
                </div>
            @elseif ($categoryKey === 'vehicle_rental')
                <div class="island-context-note" style="margin-bottom:8px;">
                    <i class="fa-solid fa-water" aria-hidden="true"></i>
                    <span>Vehicle and vessel hire in the Maldives is <strong>island-specific</strong>. Select your pickup island to find available cars, motorcycles, speedboats, and private vessel hire at that location.</span>
                </div>
                <div class="grid">
                    <div class="field field-medium">
                        <label for="pickup_island">Pickup Island</label>
                        <select id="pickup_island" name="pickup_island">
                            <option value="">All Islands</option>
                            @foreach ($islandOptions as $island)
                                <option value="{{ $island }}" {{ ($filters['pickup_island'] ?? '') === $island ? 'selected' : '' }}>{{ $island }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="atoll_rental">Atoll (Optional)</label>
                        <select id="atoll_rental" name="atoll">
                            <option value="">All Atolls</option>
                            @foreach ($atollOptions as $atoll)
                                <option value="{{ $atoll }}" {{ ($filters['atoll'] ?? '') === $atoll ? 'selected' : '' }}>{{ $atoll }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-medium">
                        <label for="vehicle_kind">Vehicle / Vessel Type</label>
                        <select id="vehicle_kind" name="vehicle_kind">
                            <option value="">All Types</option>
                            <option value="car" {{ ($filters['vehicle_kind'] ?? '') === 'car' ? 'selected' : '' }}>Car / 4x4</option>
                            <option value="motorcycle" {{ ($filters['vehicle_kind'] ?? '') === 'motorcycle' ? 'selected' : '' }}>Motorcycle / Scooter</option>
                            <option value="bicycle" {{ ($filters['vehicle_kind'] ?? '') === 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                            <option value="speedboat" {{ ($filters['vehicle_kind'] ?? '') === 'speedboat' ? 'selected' : '' }}>Speedboat</option>
                            <option value="vessel" {{ ($filters['vehicle_kind'] ?? '') === 'vessel' ? 'selected' : '' }}>Private Vessel / Dhoni</option>
                            <option value="yacht" {{ ($filters['vehicle_kind'] ?? '') === 'yacht' ? 'selected' : '' }}>Yacht / Charter</option>
                        </select>
                    </div>
                    <div class="field field-date">
                        <label for="pickup_date">Pickup Date</label>
                        <input id="pickup_date" name="pickup_date" type="date" value="{{ $filters['pickup_date'] ?? '' }}">
                    </div>
                    <div class="field field-date">
                        <label for="return_date_rental">Return Date</label>
                        <input id="return_date_rental" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}">
                    </div>
                    <div class="field field-short">
                        <label for="adults_rental">Passengers / Pax</label>
                        <input id="adults_rental" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}">
                    </div>
                </div>
            @endif

            <div class="actions">
                <button class="primary" type="submit">Apply Filters</button>
                <a href="/catalog/{{ $categoryKey }}">Reset</a>
            </div>
                        </form>
                    </div>
                </div>
            </div>

        </section>

        <div class="page-body-split">
            <div class="page-main-content">

        <h2 class="section-title">Available Portfolio Items</h2>
        @if ($catalogProperties->isEmpty())
            <div class="empty">No listings found for this category and selected filters yet.</div>
        @else
            @php
                $catalogSections = collect();
                if ($popularOverall->isNotEmpty()) {
                    $catalogSections->push(['title' => 'Popular in this category', 'items' => $popularOverall]);
                }
                foreach ($popularByAtoll as $atollName => $items) {
                    $catalogSections->push(['title' => 'Popular in ' . $atollName . ' Atoll', 'items' => $items]);
                }
                foreach ($popularByIsland as $islandName => $items) {
                    $catalogSections->push(['title' => 'Popular in ' . $islandName, 'items' => $items]);
                }
            @endphp

            @foreach ($catalogSections as $section)
                <h3 class="catalog-section-title">{{ $section['title'] }}</h3>
                <section class="catalog-grid" aria-label="Category listing catalogue section">
                    @foreach (($section['items'] ?? collect()) as $property)
                        @php
                            $propertyId = (int) ($property->id ?? 0);
                            $mediaItems = collect($catalogPropertyMediaByProperty->get($propertyId, collect()));
                            $primaryMedia = $mediaItems->first();
                            $bannerUrl = $primaryMedia ? $mediaVariantUrl($primaryMedia, 'banner') : null;
                            $thumbUrl = $primaryMedia ? $mediaVariantUrl($primaryMedia, 'thumb') : null;
                            $fallbackPath = trim((string) ($primaryMedia->file_path ?? ''));
                            $fallbackImage = '';
                            if ($bannerUrl !== null && trim($bannerUrl) !== '') {
                                $fallbackImage = (string) $bannerUrl;
                            }
                            if ($fallbackPath !== '') {
                                if (str_starts_with($fallbackPath, 'http://') || str_starts_with($fallbackPath, 'https://')) {
                                    if ($fallbackImage === '') {
                                        $fallbackImage = $fallbackPath;
                                    }
                                } else {
                                    if ($fallbackImage === '') {
                                        $fallbackImage = '/storage/' . ltrim(str_replace('public/', '', str_replace('storage/', '', str_replace('\\', '/', $fallbackPath))), '/');
                                    }
                                }
                            }
                            if (str_starts_with($fallbackImage, 'http://')) {
                                $fallbackImage = 'https://' . ltrim(substr($fallbackImage, 7), '/');
                            }
                            $svgFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3ENo%20image%3C%2Ftext%3E%3C%2Fsvg%3E";
                            $price = (float) ($property->base_price ?? 0);
                            $cityName = trim((string) ($property->city ?? $property->island ?? $property->atoll ?? ''));
                            $starRank = max(0, min(5, (int) round((float) ($property->star_rating ?? $property->stars ?? $property->hotel_stars ?? 0))));
                            $reviewScoreRaw = (float) ($property->rating ?? $property->average_rating ?? 0);
                            $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                            $reviewCount = (int) ($property->reviews_count ?? 0);
                            $detailUrl = $categoryKey === 'accommodation'
                                ? ('/property/' . $propertyId)
                                : ('/category-booking/' . $categoryKey . '/' . $propertyId);
                            $actionLabel = match ($categoryKey) {
                                'accommodation'     => 'Open Listing Profile',
                                'restaurant'        => 'Reserve a Table',
                                'vehicle_rental'    => 'Hire Vehicle / Vessel',
                                'marine-transport'  => 'Book Marine Transfer',
                                'land-transport'    => 'Book Land Transfer',
                                'excursion'         => 'Book Excursion',
                                'conference_room'   => 'Book Conference Room',
                                'resort_day_visit'  => 'Book Day Visit',
                                'remote_workspace'  => 'Book Workspace',
                                default             => 'Proceed to Booking',
                            };
                        @endphp
                        <article class="card">
                            <a class="card-link" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                                @php
                                    $resolvedImage = ($thumbUrl && trim($thumbUrl) !== '')
                                        ? (string) $thumbUrl
                                        : ($bannerUrl ?: ($fallbackImage !== '' ? $fallbackImage : $svgFallback));
                                @endphp
                                <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $svgFallback }}';};" alt="{{ (string) ($property->name ?? 'Listing image') }}" loading="lazy">
                                <div class="card-body">
                                    <span class="card-city">{{ $cityName !== '' ? $cityName : 'Maldives' }}</span>
                                    <h3>{{ (string) ($property->name ?? 'Listing') }}</h3>
                                    <div class="card-stars" aria-label="Star ranking">
                                        @if ($starRank > 0)
                                            @for ($i = 0; $i < $starRank; $i++)
                                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                                            @endfor
                                        @endif
                                    </div>
                                    <div class="card-review">
                                        <span class="card-rating-badge">{{ $reviewScore }}</span>
                                        <span>{{ number_format($reviewCount) }} reviews</span>
                                    </div>
                                    <div class="card-price">From {{ strtoupper((string) ($property->currency ?? 'MVR')) }} {{ number_format($price, 2) }}</div>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </section>
            @endforeach
        @endif

        @include('partials.global-site-footer')
            </div>{{-- /page-main-content --}}
        </div>{{-- /page-body-split --}}
    </main>

    <script>
        (function () {
            const page = document.querySelector('.page');
            const header = document.querySelector('.header-bar');
            if (!page || !header) {
                return;
            }

            let lastScrollY = window.scrollY || 0;

            function syncHeaderScrollState() {
                const revealThreshold = Math.max(56, header.offsetHeight - 4);
                const currentY = window.scrollY || 0;
                const isDesktop = window.matchMedia('(min-width: 1041px)').matches;
                const isScrollingDown = currentY > lastScrollY;

                page.classList.toggle('is-header-hidden', isDesktop && currentY > revealThreshold && isScrollingDown);
                lastScrollY = currentY;
            }

            window.addEventListener('scroll', syncHeaderScrollState, { passive: true });
            window.addEventListener('resize', syncHeaderScrollState);
            syncHeaderScrollState();
        })();
    </script>

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
            async function fetchJson(url) {
                const response = await fetch(url, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Request failed: ' + response.status);
                }
                return response.json();
            }

            function rebuildSelect(selectEl, options, placeholder, selectedValue) {
                if (!selectEl) {
                    return;
                }

                const selected = String(selectedValue || '').trim();
                selectEl.innerHTML = '';

                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = placeholder;
                selectEl.appendChild(emptyOption);

                (options || []).forEach(function (value) {
                    const normalized = String(value || '').trim();
                    if (normalized === '') {
                        return;
                    }
                    const option = document.createElement('option');
                    option.value = normalized;
                    option.textContent = normalized;
                    if (selected !== '' && normalized === selected) {
                        option.selected = true;
                    }
                    selectEl.appendChild(option);
                });

                if (selected !== '' && !Array.from(selectEl.options).some(function (opt) { return opt.value === selected; })) {
                    const fallbackOption = document.createElement('option');
                    fallbackOption.value = selected;
                    fallbackOption.textContent = selected;
                    fallbackOption.selected = true;
                    selectEl.appendChild(fallbackOption);
                }
            }

            async function initAtollIslandSearch() {
                const primaryAtoll = document.getElementById('atoll');
                const primaryIsland = document.getElementById('island');
                const restaurantAtoll = document.getElementById('atoll_restaurant');
                const restaurantIsland = document.getElementById('current_island');
                const rentalAtoll = document.getElementById('atoll_rental');
                const rentalIsland = document.getElementById('pickup_island');

                const atollSelectors = [primaryAtoll, restaurantAtoll, rentalAtoll].filter(Boolean);
                const islandSelectors = [primaryIsland, restaurantIsland, rentalIsland].filter(Boolean);

                if (atollSelectors.length === 0 && islandSelectors.length === 0) {
                    return;
                }

                let atolls = [];
                try {
                    atolls = await fetchJson('/api/atoll-island/atolls');
                } catch (error) {
                    return;
                }

                const atollNameById = new Map();
                const atollIdByName = new Map();
                (Array.isArray(atolls) ? atolls : []).forEach(function (atoll) {
                    const id = Number(atoll && atoll.id ? atoll.id : 0);
                    const name = String(atoll && atoll.name ? atoll.name : '').trim();
                    if (id <= 0 || name === '') {
                        return;
                    }
                    atollNameById.set(id, name);
                    atollIdByName.set(name, id);
                });

                atollSelectors.forEach(function (selectEl) {
                    const selected = String(selectEl.value || '').trim();
                    rebuildSelect(
                        selectEl,
                        Array.from(atollIdByName.keys()),
                        'All Atolls',
                        selected
                    );
                });

                const islandsByAtollName = new Map();
                const allIslandNames = new Set();

                await Promise.all(Array.from(atollIdByName.entries()).map(async function (entry) {
                    const atollName = entry[0];
                    const atollId = entry[1];
                    try {
                        const islands = await fetchJson('/api/atoll-island/atolls/' + atollId + '/islands');
                        const names = (Array.isArray(islands) ? islands : [])
                            .map(function (island) { return String(island && island.name ? island.name : '').trim(); })
                            .filter(function (name) { return name !== ''; });
                        islandsByAtollName.set(atollName, names);
                        names.forEach(function (name) { allIslandNames.add(name); });
                    } catch (error) {
                        islandsByAtollName.set(atollName, []);
                    }
                }));

                function updatePrimaryIslandOptions() {
                    if (!primaryIsland || !primaryAtoll) {
                        return;
                    }
                    const selectedAtollName = String(primaryAtoll.value || '').trim();
                    const selectedIsland = String(primaryIsland.value || '').trim();
                    const islandNames = selectedAtollName !== ''
                        ? (islandsByAtollName.get(selectedAtollName) || [])
                        : Array.from(allIslandNames);
                    rebuildSelect(primaryIsland, islandNames, 'All Islands/Cities', selectedIsland);
                }

                function updateRestaurantIslandOptions() {
                    if (!restaurantIsland || !restaurantAtoll) {
                        return;
                    }
                    const selectedAtollName = String(restaurantAtoll.value || '').trim();
                    const selectedIsland = String(restaurantIsland.value || '').trim();
                    const islandNames = selectedAtollName !== ''
                        ? (islandsByAtollName.get(selectedAtollName) || [])
                        : Array.from(allIslandNames);
                    rebuildSelect(restaurantIsland, islandNames, 'All Islands', selectedIsland);
                }

                function updateRentalIslandOptions() {
                    if (!rentalIsland || !rentalAtoll) {
                        return;
                    }
                    const selectedAtollName = String(rentalAtoll.value || '').trim();
                    const selectedIsland = String(rentalIsland.value || '').trim();
                    const islandNames = selectedAtollName !== ''
                        ? (islandsByAtollName.get(selectedAtollName) || [])
                        : Array.from(allIslandNames);
                    rebuildSelect(rentalIsland, islandNames, 'All Islands', selectedIsland);
                }

                if (primaryAtoll) {
                    primaryAtoll.addEventListener('change', updatePrimaryIslandOptions);
                    updatePrimaryIslandOptions();
                }

                if (restaurantAtoll) {
                    restaurantAtoll.addEventListener('change', updateRestaurantIslandOptions);
                    updateRestaurantIslandOptions();
                }

                if (rentalAtoll) {
                    rentalAtoll.addEventListener('change', updateRentalIslandOptions);
                    updateRentalIslandOptions();
                }
            }

            initAtollIslandSearch();
        })();
    </script>
</body>
</html>
