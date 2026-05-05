<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $categoryMeta['label'] }} Catalogue | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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
            --listing-thumb-width: 210px;
            --listing-thumb-height: 192px;
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

        .page.category-accommodation {
            width: calc(100vw - 24px);
            max-width: none;
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
            transition: transform 0.22s ease, opacity 0.22s ease;
            z-index: 10;
            backdrop-filter: blur(2px);
        }

        .page.is-header-hidden .header-bar {
            transform: translateY(-110%);
            opacity: 0;
            pointer-events: none;
        }

        .header-category-tabs {
            width: auto;
            margin-top: 0;
            flex: 1 1 auto;
            min-width: 0;
            order: 0;
        }

        .header-main {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 12px;
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
            display: none !important;
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

        .header-bar .header-search-mini {
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

        .header-bar .header-search-mini input {
            border: 0;
            background: transparent;
            padding: 9px 12px;
            font: inherit;
            min-width: 0;
            width: 100%;
            color: #244057;
        }

        .header-bar .header-search-mini button {
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
            flex-wrap: nowrap;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .header-link {
            text-decoration: none;
            color: #e9f5ff;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
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
            background: #1a7a68;
            border-color: #136357;
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
            min-height: 500px;
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
            top: 10px;
            bottom: auto;
            left: 0;
            right: 0;
            margin: 0 auto;
            transform: none;
            width: min(1180px, calc(100% - 24px));
            z-index: 2;
            display: grid;
            gap: 12px;
        }

        .page.category-accommodation .hero-banner-content {
            width: 100%;
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
            position: sticky;
            top: 8px;
            z-index: 50;
            width: 100%;
            padding: 0;
            transform: none;
        }

        .search-sticky-wrap.is-fixed {
            position: fixed;
            top: var(--sticky-search-top, 80px);
            left: 50%;
            transform: translateX(-50%);
            width: min(1180px, calc(100% - 24px));
            z-index: 1040;
        }

        .page.category-accommodation .search-sticky-wrap.is-fixed {
            top: var(--sticky-search-top, 0px);
            left: 0;
            transform: none;
            width: 100vw;
            max-width: none;
        }

        .page.category-accommodation .search-box {
            border-radius: 0;
        }

        .page.category-accommodation .search-sticky-wrap.is-fixed .search-box {
            border-left: 0;
            border-right: 0;
            border-top: 0;
            box-shadow: 0 10px 24px rgba(14, 41, 92, 0.18);
        }

        .search-sticky-wrap.is-fixed .search-box {
            box-shadow: 0 12px 30px rgba(14, 41, 92, 0.25);
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
            grid-template-columns: 1fr;
            align-items: stretch;
            gap: 8px;
            overflow: visible;
            box-shadow: 0 12px 26px rgba(14, 41, 92, 0.2);
            position: static;
            z-index: auto;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .search-box > .grid {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .search-box > .actions {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .search-primary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .search-primary-grid,
        .search-filter-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .search-filter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #dce7ef;
            padding-top: 10px;
        }

        .filter-actions-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .search-submit-btn {
            height: 42px;
            padding: 0 18px;
            white-space: nowrap;
        }

        .search-box::-webkit-scrollbar {
            height: 8px;
        }

        .search-box::-webkit-scrollbar-thumb {
            background: #bfd4e2;
            border-radius: 999px;
        }

        .catalog-section-title {
            display: none;
            margin: 16px 0 8px;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
        }

        .load-more-btn {
            width: 100%;
            padding: 12px;
            border: 1px solid #d2dce5;
            border-radius: 6px;
            background: #ffffff;
            color: #2a4154;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .load-more-btn:hover {
            border-color: #b9c5d1;
            background: #f5fbfd;
        }

        .catalog-grid {
            margin-top: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .card {
            border-bottom: 1px solid #e8ecf0;
            border-radius: 0;
            background: #ffffff;
            overflow: visible;
            padding: 12px;
            display: flex;
            flex-direction: row;
            gap: 12px;
            align-items: flex-start;
        }

        .card:hover {
            background: #f9fbfd;
        }

        .card-link {
            display: flex;
            flex-direction: row;
            color: inherit;
            text-decoration: none;
            position: relative;
            width: 100%;
            gap: 12px;
        }

        .card img {
            width: var(--listing-thumb-width);
            height: var(--listing-thumb-height);
            object-fit: cover;
            background: #edf4fb;
            display: block;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .card-body {
            padding: 0;
            display: flex;
            flex-direction: row;
            gap: 8px;
            flex: 1;
            min-width: 0;
            justify-content: space-between;
        }

        .card-main-col {
            flex: 1 1 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .card-meta-right {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            text-align: right;
        }

        .card-meta-right .card-review {
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
            margin-bottom: 0;
        }

        .card-city {
            color: #7b8d99;
            font-size: 0.7rem;
            line-height: 1;
            margin-bottom: 1px;
        }

        .card h3 {
            margin: 0;
            font-size: 0.85rem;
            line-height: 1.1;
            color: #1a2f43;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1px;
        }

        .card-stars {
            display: inline-flex;
            align-items: center;
            gap: 1px;
            color: #f3a337;
            font-size: 0.65rem;
            min-height: 12px;
            margin-bottom: 2px;
        }

        .card-review {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #587085;
            font-size: 0.65rem;
            margin-bottom: 3px;
        }

        .card-rating-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 50%;
            background: #2fa58a;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1;
            flex-direction: column;
            flex-shrink: 0;
        }

        .card-price {
            margin-top: 2px;
            color: #1a2f43;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 2px;
            line-height: 1;
        }

        .card-price .price-local {
            display: block;
            color: #1a2f43;
        }

        .card-price .price-foreign {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            color: #0f6179;
            margin-top: 2px;
        }

        .card-offer {
            color: #0f6179;
            font-size: 0.65rem;
            margin-bottom: 6px;
        }

        .card-type-chip {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 100%;
            padding: 3px 8px;
            border-radius: 999px;
            background: #e8f2f8;
            color: #1e5672;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-desc {
            display: none;
            margin: 0;
            color: #4f677a;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .card-time {
            display: none;
            color: #345469;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .card-action-btn {
            align-self: flex-start;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            background: #2fa58a;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s ease;
        }

        .card-action-btn:hover {
            background: #27917a;
        }

        .grid {
            display: flex;
            flex-wrap: nowrap;
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
            flex: 0 0 84px;
            width: 84px;
        }

        .field.field-medium {
            flex: 0 0 164px;
            width: 164px;
        }

        .field.field-date {
            flex: 0 0 172px;
            width: 172px;
        }

        .field.field-long {
            flex: 0 0 220px;
            width: 220px;
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
            flex-wrap: nowrap;
            gap: 8px;
            align-items: center;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .filter-popup-toggle {
            border: 1px solid #b9ccda;
            border-radius: 8px;
            background: #f4f9fc;
            color: #1b4f6e;
            height: 36px;
            padding: 0 12px;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
        }

        .filter-popup-toggle:hover {
            background: #e7f2f9;
        }

        .filter-popup-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(9, 27, 43, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px;
            z-index: 1100;
        }

        .filter-popup-backdrop[hidden] {
            display: none !important;
        }

        .filter-popup {
            width: min(860px, calc(100vw - 24px));
            max-height: calc(100vh - 28px);
            overflow: auto;
            border: 1px solid #cddfea;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(9, 34, 54, 0.35);
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .filter-popup-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .filter-popup-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: #123d59;
        }

        .filter-popup-close {
            border: 1px solid #c8dae7;
            border-radius: 8px;
            background: #f7fbff;
            color: #234a66;
            height: 34px;
            padding: 0 10px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .filter-popup-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .filter-popup-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .catalog-results-layout {
            display: grid;
            grid-template-columns: 0.65fr 1fr;
            gap: 0;
            align-items: start;
            height: calc(100vh - 220px);
            isolation: isolate;
        }

        .page.category-accommodation .catalog-results-layout {
            grid-template-columns: 0.72fr 1.28fr;
            height: var(--catalog-results-height, calc(100vh - 170px));
            min-height: 600px;
            position: sticky;
            top: var(--catalog-results-top, 0px);
        }

        .catalog-results-list {
            display: flex;
            flex-direction: column;
            gap: 0;
            min-width: 0;
            overflow-y: auto;
            height: 100%;
            border-right: 1px solid #e0e8f0;
            background: #ffffff;
            overscroll-behavior: auto;
        }

        .page.category-accommodation .catalog-results-list {
            margin-top: 0;
            height: 100%;
            max-height: none;
            overflow-y: auto;
        }

        .catalog-show-more-wrap {
            padding: 12px;
            border-top: 1px solid #e4edf4;
            background: #ffffff;
            position: static;
            z-index: 2;
        }

        .catalog-map-panel {
            position: relative;
            top: 0;
            border: 0;
            border-radius: 0;
            background: #ffffff;
            overflow: hidden;
            box-shadow: none;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .page.category-accommodation .catalog-map-panel {
            position: relative;
            top: 0;
            align-self: start;
        }

        .catalog-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid #dce8f0;
            padding: 10px 12px;
            font-size: 0.78rem;
            color: #294f68;
            font-weight: 700;
            background: #f5fbff;
        }

        .catalog-map-head-main {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .map-radius-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .map-radius-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 26px;
            padding: 0 9px;
            border-radius: 999px;
            border: 1px solid #c4d9e8;
            background: #ffffff;
            color: #2a536e;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .map-radius-chip:hover {
            background: #eef7fd;
        }

        .map-radius-chip.is-active {
            border-color: #0f6179;
            background: #0f6179;
            color: #ffffff;
        }

        .catalog-map-head strong {
            color: #123f5d;
            font-size: 0.83rem;
        }

        .category-map-wrap {
            position: relative;
            flex: 1;
            min-height: 420px;
        }

        #categoryResultsMap {
            width: 100%;
            height: 100%;
            background: #e7f1f7;
            flex: 1;
        }

        .map-search-area-btn {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 520;
            border: 1px solid #0f7e72;
            border-radius: 999px;
            background: #0f9a88;
            color: #ffffff;
            height: 34px;
            padding: 0 14px;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(15, 97, 121, 0.28);
            white-space: nowrap;
        }

        .page.category-accommodation .card-link-accommodation {
            display: grid;
            grid-template-columns: var(--listing-thumb-width) minmax(0, 1fr) 170px;
            gap: 12px;
            align-items: start;
        }

        .page.category-accommodation .card-body-accommodation {
            display: contents;
        }

        .page.category-accommodation .card-main {
            min-width: 0;
            display: grid;
            gap: 6px;
            align-content: start;
        }

        .page.category-accommodation .card-side {
            min-width: 0;
            display: grid;
            gap: 8px;
            justify-items: end;
            align-content: start;
            text-align: right;
        }

        .page.category-accommodation .card-side .card-action-btn {
            align-self: start;
        }

        .page.category-accommodation .card {
            padding: 14px;
        }

        .page.category-accommodation .card-city {
            font-size: 0.8rem;
            margin-bottom: 4px;
        }

        .page.category-accommodation .card-city::before {
            font-size: 0.72rem;
        }

        .page.category-accommodation .card h3 {
            font-size: 1rem;
            line-height: 1.2;
            -webkit-line-clamp: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .page.category-accommodation .card-stars {
            font-size: 0.78rem;
            min-height: 14px;
        }

        .page.category-accommodation .card-review {
            font-size: 0.76rem;
            gap: 8px;
        }

        .page.category-accommodation .card-price {
            font-size: 1.04rem;
            line-height: 1.2;
        }

        .page.category-accommodation .card-offer {
            font-size: 0.76rem;
        }

        .page.category-accommodation .card-action-btn {
            font-size: 0.8rem;
            padding: 7px 14px;
        }

        .page.category-accommodation .wf-site-footer {
            display: none;
        }

        .page.category-default .catalog-results-layout {
            display: block;
            height: auto;
        }

        .page.category-default .catalog-results-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            height: auto;
            overflow: visible;
            border-right: 0;
            background: transparent;
        }

        .page.category-default .page-body-split {
            margin-top: 32px;
        }

        .page.category-default .catalog-section-title {
            display: block;
            grid-column: 1 / -1;
        }

        .page.category-default .catalog-grid {
            display: contents;
        }

        .page.category-default .card {
            border: 1px solid #d4e5ef;
            border-radius: 14px;
            background: #f8fcff;
            overflow: hidden;
            display: block;
            height: 100%;
            transition: box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .page.category-default .card:hover {
            border-color: #9ecad8;
            box-shadow: 0 4px 16px rgba(14, 86, 111, 0.12);
        }

        .page.category-default .card-link {
            display: grid;
            grid-template-rows: 180px auto;
            height: 100%;
        }

        .page.category-default .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            transition: transform 0.25s ease;
        }

        .page.category-default .card:hover img {
            transform: scale(1.03);
        }

        .page.category-default .card-body {
            display: flex;
            flex-direction: column;
            row-gap: 4px;
            align-items: stretch;
            padding: 10px 12px;
        }

        .page.category-default .card-main-col,
        .page.category-default .card-meta-right {
            width: 100%;
        }

        .page.category-default .card-meta-right {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            text-align: left;
            margin-top: 4px;
            flex-wrap: wrap;
            gap: 4px;
        }

        .page.category-default .card-meta-right .card-review {
            flex-direction: row;
            align-items: center;
            gap: 6px;
        }

        .page.category-default .card-price {
            margin-top: 0;
            text-align: right;
            font-size: 0.82rem;
        }

        .page.category-default .card h3 {
            font-size: 0.94rem;
            -webkit-line-clamp: 2;
            line-height: 1.32;
            margin-bottom: 1px;
        }

        .page.category-default .card-city {
            font-size: 0.71rem;
            display: flex;
            align-items: center;
            gap: 4px;
            color: #6f879b;
        }

        .page.category-default .card-type-chip {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #0f6179;
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
        }

        .page.category-default .card-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #4f677a;
            font-size: 0.76rem;
            line-height: 1.45;
            margin: 0;
        }

        .page.category-default .card-stars {
            display: none;
        }

        .page.category-default .card-time {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eef6fb;
            color: #1e5672;
            font-size: 0.71rem;
            font-weight: 600;
            border-radius: 5px;
            padding: 3px 8px;
            width: fit-content;
        }

        .page.category-default .card-review {
            margin-top: 2px;
        }

        .page.category-default .card-action-btn {
            margin-top: 6px;
            padding: 8px 14px;
            font-size: 0.78rem;
            align-self: flex-start;
        }

        .page.category-default .card h3 {
            font-size: 0.92rem;
            -webkit-line-clamp: 2;
            margin-bottom: 2px;
        }

        .page.category-default .card-city {
            font-size: 0.72rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page.category-default .card-desc {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #4f677a;
            font-size: 0.76rem;
            line-height: 1.45;
            margin: 0;
        }

        .page.category-default .card-stars {
            display: none;
        }

        .page.category-default .card-time {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eef6fb;
            color: #1e5672;
            font-size: 0.71rem;
            font-weight: 600;
            border-radius: 5px;
            padding: 3px 8px;
            width: fit-content;
        }

        .page.category-default .card-review {
            margin-top: 2px;
        }

        .page.category-default .card-action-btn {
            margin-top: 6px;
            padding: 8px 14px;
            font-size: 0.78rem;
            align-self: flex-start;
        }

        .page.category-default .catalog-map-panel {
            display: none;
        }

        .leaflet-control-zoom {
            border: 0 !important;
            box-shadow: none !important;
            margin-right: 10px !important;
            margin-top: 10px !important;
        }

        .leaflet-control-zoom a {
            width: 40px !important;
            height: 40px !important;
            line-height: 38px !important;
            border: 1px solid #c9d7e2 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            color: #2a4154 !important;
            font-size: 22px !important;
            font-weight: 700;
            margin-bottom: 6px !important;
            box-shadow: 0 4px 10px rgba(17, 44, 63, 0.2);
        }

        .leaflet-control-zoom a:hover {
            background: #f4f9fc !important;
        }

        .price-marker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d2dce5;
            border-radius: 999px;
            background: #ffffff;
            color: #1a2f43;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            padding: 6px 9px;
            box-shadow: 0 3px 8px rgba(20, 45, 73, 0.2);
            white-space: nowrap;
            transform: translateY(-2px);
            transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
        }

        .price-marker:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 14px rgba(20, 45, 73, 0.3);
        }

        .price-marker.is-compact {
            font-size: 11px;
            padding: 5px 8px;
        }

        .price-marker.is-super-compact {
            font-size: 10px;
            padding: 4px 7px;
        }

        .price-marker.is-active {
            border-color: #0f6179;
            background: #0f6179;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(15, 97, 121, 0.45);
        }

        .leaflet-popup-content-wrapper {
            border-radius: 12px;
        }

        .leaflet-popup-content {
            margin: 10px 12px;
        }

        .map-empty {
            padding: 14px;
            font-size: 0.82rem;
            color: #43627a;
        }

        @media (max-width: 1100px) {
            .page.category-accommodation {
                width: calc(100% - 18px);
            }

            .page.category-default .catalog-results-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .page.category-accommodation .catalog-results-layout {
                grid-template-columns: 1fr;
                height: auto;
                position: relative;
                top: auto;
            }

            .page.category-accommodation .catalog-results-list {
                height: auto;
                max-height: none;
                margin-top: 10px;
                overflow-y: visible;
                border-right: 0;
            }

            .page.category-accommodation .catalog-map-panel {
                height: 380px;
                min-height: 380px;
                border-top: 1px solid #dce8f0;
                position: relative;
            }

            .page.category-accommodation .category-map-wrap {
                min-height: 320px;
            }

            .search-sticky-wrap.is-fixed {
                width: 100%;
            }
        }

        @media (max-width: 820px) {
            :root {
                --listing-thumb-width: 156px;
                --listing-thumb-height: 148px;
            }

            .search-box > .grid,
            .search-box > .actions {
                overflow: visible;
            }

            .search-primary-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .search-primary-grid,
            .search-filter-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                overflow: visible;
            }

            .search-submit-btn {
                width: 100%;
            }

            .search-filter-row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .filter-actions-inline {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .field.field-short,
            .field.field-medium,
            .field.field-date,
            .field.field-long {
                flex: 1 1 auto;
                width: auto;
                min-width: 0;
            }

            .page.category-accommodation .card-link-accommodation {
                grid-template-columns: var(--listing-thumb-width) minmax(0, 1fr);
                gap: 10px;
            }

            .page.category-accommodation .card-body-accommodation {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 8px;
            }

            .page.category-accommodation .card-side {
                justify-items: start;
                text-align: left;
                gap: 4px;
            }

            .page.category-accommodation .card-side .card-action-btn {
                justify-self: start;
            }

            .page.category-accommodation .catalog-map-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .page.category-accommodation .map-radius-controls {
                justify-content: flex-start;
            }
        }



        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .page.category-default .catalog-results-list {
                grid-template-columns: 1fr;
            }

            .page.category-default .card img {
                height: 180px;
            }

            .page.category-default .card-body {
                flex-direction: column;
            }

            .page.category-default .card-meta-right {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                text-align: left;
                flex-wrap: wrap;
            }

            .page.category-default .card-price {
                text-align: left;
            }

            .search-primary-grid,
            .search-filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions-inline {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions-inline > * {
                width: 100%;
                text-align: center;
            }

            .page.category-accommodation {
                width: calc(100% - 18px);
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

            .search-sticky-wrap.is-fixed {
                position: static;
                left: auto;
                top: auto;
                width: 100%;
                transform: none;
            }

            .catalog-results-layout {
                grid-template-columns: 1fr;
            }

            .page.category-accommodation .catalog-results-list {
                margin-top: 8px;
            }

            .catalog-map-panel {
                position: relative;
                top: auto;
            }

            .page.category-accommodation .catalog-map-panel {
                height: 320px;
                min-height: 320px;
            }

            .page.category-accommodation .category-map-wrap {
                min-height: 260px;
            }

            #categoryResultsMap {
                height: 300px;
            }

            .page.category-accommodation .card-link-accommodation {
                grid-template-columns: 1fr;
            }

            .page.category-accommodation .card img {
                width: 100%;
                max-width: 100%;
                height: 180px;
            }

            .page.category-accommodation .card-main,
            .page.category-accommodation .card-side {
                justify-items: start;
                text-align: left;
            }

            .filter-popup-grid {
                grid-template-columns: 1fr;
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
                gap: 8px;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding-bottom: 2px;
            }

            .customer-auth::-webkit-scrollbar {
                display: none;
            }

            .header-main {
                width: 100%;
                gap: 8px;
                display: grid;
                grid-template-columns: 1fr;
            }

            .header-category-tabs {
                width: 100%;
                flex: 0 0 100%;
                margin-top: 0;
                order: 2;
            }

            .header-links {
                gap: 8px;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
                scrollbar-width: none;
                padding-bottom: 2px;
            }

            .header-links::-webkit-scrollbar {
                display: none;
            }

            .header-link,
            .auth-link,
            .account-menu {
                flex: 0 0 auto;
                white-space: nowrap;
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
                padding: 10px 0 14px;
            }

            .hero-banner-content {
                position: relative;
                left: auto;
                transform: none;
                width: calc(100% - 18px);
                top: auto;
                bottom: auto;
                margin: 0 auto;
                gap: 8px;
            }

            .search-box {
                margin-top: 0;
                padding: 10px;
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

            .field.field-short,
            .field.field-medium,
            .field.field-date,
            .field.field-long {
                flex: 1 1 auto;
                width: 100%;
                min-width: 0;
            }

            .actions {
                padding-right: 4px;
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
        ]);
        $catalogProperties = $catalogProperties ?? collect();
        $catalogPropertyMediaByProperty = $catalogPropertyMediaByProperty ?? collect();
        $atollOptions = $atollOptions ?? collect();
        $islandOptions = $islandOptions ?? collect();
        $visitorResidency = trim((string) ($visitorResidency ?? 'foreign_national'));
        $visitorIsLocal = $visitorResidency === 'local_resident';
        $mvrUsdRate = max(0.0, (float) ($mvrUsdRate ?? env('MVR_USD_RATE', 15.42)));
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

    <main class="page {{ $categoryKey === 'accommodation' ? 'category-accommodation' : 'category-default' }}" data-api-base="{{ $apiBase }}" data-category-key="{{ $categoryKey }}">
        <section class="journey-hero" aria-label="Category hero and quick navigation">
            @include('partials.customer-uniform-header', [
                'injectUniformHeaderStyles' => true,
                'injectUniformHeaderScripts' => true,
                'headerNeedsSpacer' => false,
                'headerHideOnScroll' => true,
                'headerShowSearch' => false,
                'headerSearchAction' => '/catalog/' . (string) $categoryKey,
                'headerSearchValue' => (string) ($filters['q'] ?? ''),
                'headerCategoryLinks' => $catalogCategoryLinks
                    ->map(static fn (array $item) => [
                        'key' => (string) ($item['key'] ?? ''),
                        'title' => (string) ($item['title'] ?? 'Category'),
                        'url' => (string) ($item['url'] ?? ('/catalog/' . (string) ($item['key'] ?? 'accommodation'))),
                    ])
                    ->values()
                    ->all(),
                'headerActiveCategoryKey' => (string) $categoryKey,
                'headerContinueUrl' => (string) $customerContinueUrl,
            ])

            <div class="hero-banner" aria-label="Category banner and quick filters">
                <img class="hero-banner-image" src="{{ $categoryHeroImageUrl }}" alt="{{ (string) ($categoryMeta['label'] ?? 'Category') }} banner" loading="eager" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ $categoryHeroFallback }}';">
                <div class="hero-banner-content">
                    <div class="search-sticky-wrap">
                        <form class="search-box" method="GET" action="/catalog/{{ $categoryKey }}" id="categorySearchForm">
            @if ($categoryKey === 'accommodation')
                <div class="search-primary-row">
                    <div class="grid search-primary-grid">
                        <div class="field field-long">
                            <label for="q">Search</label>
                            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Atoll, island, place, or property name">
                        </div>
                        <div class="field field-date"><label for="checkin">Check-in Date</label><input id="checkin" name="checkin" type="date" value="{{ $filters['checkin'] ?? '' }}"></div>
                        <div class="field field-date"><label for="checkout">Check-out Date</label><input id="checkout" name="checkout" type="date" value="{{ $filters['checkout'] ?? '' }}"></div>
                        <div class="field field-short"><label for="adults">Adults / Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
                        <div class="field field-short"><label for="children">Children</label><input id="children" name="children" type="number" min="0" value="{{ $filters['children'] ?? 0 }}"></div>
                        <div class="field field-short"><label for="rooms">Rooms</label><input id="rooms" name="rooms" type="number" min="1" value="{{ $filters['rooms'] ?? 1 }}"></div>
                    </div>
                    <button class="primary search-submit-btn" type="submit">Search</button>
                </div>
                <div class="search-filter-row">
                    <div class="grid search-filter-grid">
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
                                <option value="distance_nearest" {{ ($filters['sort'] ?? '') === 'distance_nearest' ? 'selected' : '' }}>Nearest Distance</option>
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
                    <div class="filter-actions-inline">
                        <button class="filter-popup-toggle" type="button" id="openFilterPopup"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Filters</button>
                        <a href="/catalog/{{ $categoryKey }}">Clear all filters</a>
                    </div>
                </div>
            @elseif (!in_array($categoryKey, ['marine-transport', 'land-transport'], true))
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
                            <option value="distance_nearest" {{ ($filters['sort'] ?? '') === 'distance_nearest' ? 'selected' : '' }}>Nearest Distance</option>
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
            @endif

            @if ($categoryKey === 'marine-transport' || $categoryKey === 'land-transport')
                <div class="grid">
                    <div class="field field-long">
                        <label for="origin_point">From</label>
                        <select id="origin_point" name="origin_point">
                            <option value="">All origins</option>
                            @foreach (($transportDestinationOptions ?? collect()) as $destinationOption)
                                <option value="{{ $destinationOption }}" {{ ($filters['origin_point'] ?? '') === $destinationOption ? 'selected' : '' }}>{{ $destinationOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-long">
                        <label for="destination_point">To</label>
                        <select id="destination_point" name="destination_point">
                            <option value="">All destinations</option>
                            @foreach (($transportDestinationOptions ?? collect()) as $destinationOption)
                                <option value="{{ $destinationOption }}" {{ ($filters['destination_point'] ?? '') === $destinationOption ? 'selected' : '' }}>{{ $destinationOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field field-date"><label for="travel_date">Departure Date</label><input id="travel_date" name="travel_date" type="date" value="{{ $filters['travel_date'] ?? '' }}"></div>
                    <div class="field field-date"><label for="return_date">Return Date (Optional)</label><input id="return_date" name="return_date" type="date" value="{{ $filters['return_date'] ?? '' }}"></div>
                    <div class="field field-short"><label for="adults">No. of Pax</label><input id="adults" name="adults" type="number" min="1" value="{{ $filters['adults'] ?? 2 }}"></div>
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

            @if ($categoryKey !== 'accommodation')
                <div class="actions">
                    <button class="filter-popup-toggle" type="button" id="openFilterPopup"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Filters</button>
                    <button class="primary" type="submit">Apply Filters</button>
                    <a href="/catalog/{{ $categoryKey }}">Clear all filters</a>
                </div>
            @endif

            <div class="filter-popup-backdrop" id="filterPopupBackdrop" hidden>
                <div class="filter-popup" role="dialog" aria-modal="true" aria-labelledby="filterPopupTitle">
                    <div class="filter-popup-head">
                        <h3 class="filter-popup-title" id="filterPopupTitle">Refine Results</h3>
                        <button class="filter-popup-close" type="button" id="closeFilterPopup">Close</button>
                    </div>
                    <div class="filter-popup-grid">
                        <div class="field">
                            <label for="min_rating">Minimum Rating</label>
                            <select id="min_rating" name="min_rating">
                                <option value="">Any Rating</option>
                                <option value="9" {{ (string) ($filters['min_rating'] ?? '') === '9' ? 'selected' : '' }}>9.0+</option>
                                <option value="8" {{ (string) ($filters['min_rating'] ?? '') === '8' ? 'selected' : '' }}>8.0+</option>
                                <option value="7" {{ (string) ($filters['min_rating'] ?? '') === '7' ? 'selected' : '' }}>7.0+</option>
                                <option value="6" {{ (string) ($filters['min_rating'] ?? '') === '6' ? 'selected' : '' }}>6.0+</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="min_reviews">Minimum Reviews</label>
                            <select id="min_reviews" name="min_reviews">
                                <option value="">Any</option>
                                <option value="10" {{ (string) ($filters['min_reviews'] ?? '') === '10' ? 'selected' : '' }}>10+</option>
                                <option value="50" {{ (string) ($filters['min_reviews'] ?? '') === '50' ? 'selected' : '' }}>50+</option>
                                <option value="100" {{ (string) ($filters['min_reviews'] ?? '') === '100' ? 'selected' : '' }}>100+</option>
                                <option value="250" {{ (string) ($filters['min_reviews'] ?? '') === '250' ? 'selected' : '' }}>250+</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="amenities">Amenities / Keywords</label>
                            <input id="amenities" name="amenities" type="text" value="{{ $filters['amenities'] ?? '' }}" placeholder="pool, spa, wifi, parking">
                        </div>
                        <div class="field">
                            <label for="availability_only">Availability</label>
                            <select id="availability_only" name="availability_only">
                                <option value="">All</option>
                                <option value="1" {{ (string) ($filters['availability_only'] ?? '') === '1' ? 'selected' : '' }}>Only Available</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="distance_km">Max Distance (km)</label>
                            <input id="distance_km" name="distance_km" type="number" min="0" step="0.1" value="{{ $filters['distance_km'] ?? '' }}" placeholder="e.g. 25">
                        </div>
                        <div class="field">
                            <label for="sort_popup">Sort</label>
                            <select id="sort_popup" data-mirror-target="sort">
                                <option value="recommended" {{ ($filters['sort'] ?? '') === 'recommended' ? 'selected' : '' }}>Recommended</option>
                                <option value="most_wanted" {{ ($filters['sort'] ?? '') === 'most_wanted' ? 'selected' : '' }}>Most Wanted</option>
                                <option value="most_booked" {{ ($filters['sort'] ?? '') === 'most_booked' ? 'selected' : '' }}>Most Booked</option>
                                <option value="highest_reviews" {{ ($filters['sort'] ?? '') === 'highest_reviews' ? 'selected' : '' }}>Highest Reviews</option>
                                <option value="price_low_high" {{ ($filters['sort'] ?? '') === 'price_low_high' ? 'selected' : '' }}>Price Low to High</option>
                                <option value="price_high_low" {{ ($filters['sort'] ?? '') === 'price_high_low' ? 'selected' : '' }}>Price High to Low</option>
                                <option value="distance_nearest" {{ ($filters['sort'] ?? '') === 'distance_nearest' ? 'selected' : '' }}>Nearest Distance</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="user_lat">Search Center Latitude</label>
                            <input id="user_lat" name="user_lat" type="number" step="0.000001" value="{{ $filters['user_lat'] ?? '' }}" placeholder="4.1755">
                        </div>
                        <div class="field">
                            <label for="user_lng">Search Center Longitude</label>
                            <input id="user_lng" name="user_lng" type="number" step="0.000001" value="{{ $filters['user_lng'] ?? '' }}" placeholder="73.5093">
                        </div>
                    </div>
                    <div class="filter-popup-actions">
                        <a href="/catalog/{{ $categoryKey }}">Clear all filters</a>
                        <button class="primary" type="submit">Apply Filters</button>
                    </div>
                </div>
            </div>
                        </form>
                    </div>
                </div>
            </div>

        </section>

        <div class="page-body-split">
            <div class="page-main-content">

        @if ($catalogProperties->isEmpty())
            <div class="empty">No listings found for this category and selected filters yet.</div>
        @else
            <div class="catalog-results-layout">
                <div class="catalog-results-list">
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
                            $isExcursionCard = $categoryKey === 'excursion';
                            $cardDetails = [];
                            $cardDetailsSource = $property->listing_details ?? ($property->details ?? null);
                            if (is_array($cardDetailsSource)) {
                                $cardDetails = $cardDetailsSource;
                            } elseif (is_object($cardDetailsSource)) {
                                $cardDetails = (array) $cardDetailsSource;
                            } elseif (is_string($cardDetailsSource) && trim($cardDetailsSource) !== '') {
                                $decodedCardDetails = json_decode($cardDetailsSource, true);
                                if (is_array($decodedCardDetails)) {
                                    $cardDetails = $decodedCardDetails;
                                }
                            }
                            $fromIsland = trim((string) ($property->island ?? ($cardDetails['island'] ?? '')));
                            $fromCity = trim((string) ($property->city ?? ($cardDetails['location_city'] ?? ($cardDetails['city'] ?? ''))));
                            $fromAtoll = trim((string) ($property->atoll ?? ($cardDetails['location_state'] ?? ($cardDetails['atoll'] ?? ''))));
                            $fromCountry = trim((string) ($property->location_country ?? $property->country ?? ($cardDetails['location_country'] ?? 'Maldives')));
                            $originParts = array_values(array_filter([
                                $fromIsland !== '' ? $fromIsland : $fromCity,
                                $fromAtoll,
                                $fromCountry,
                            ], static fn ($item): bool => trim((string) $item) !== ''));
                            $originLabel = $originParts !== [] ? implode(', ', $originParts) : 'Maldives';
                            $activityType = trim((string) (
                                (property_exists($property, 'activity_type') ? $property->activity_type : '')
                                ?: ($cardDetails['activity_type'] ?? '')
                                ?: (property_exists($property, 'excursion_type') ? $property->excursion_type : '')
                                ?: ($cardDetails['excursion_type'] ?? '')
                                ?: (property_exists($property, 'tour_type') ? $property->tour_type : '')
                            ));
                            $activityName = trim((string) (
                                (property_exists($property, 'activity_name') ? $property->activity_name : '')
                                ?: (property_exists($property, 'listing_name') ? $property->listing_name : '')
                                ?: ($property->name ?? 'Listing')
                            ));
                            $descriptionSource = trim((string) (
                                (property_exists($property, 'short_description') ? $property->short_description : '')
                                ?: ($cardDetails['short_description'] ?? '')
                                ?: (property_exists($property, 'tagline') ? $property->tagline : '')
                                ?: ($property->description ?? '')
                            ));
                            $shortDescription = \Illuminate\Support\Str::limit(strip_tags($descriptionSource), 120);

                            $formatTimeLabel = static function ($rawValue): string {
                                $value = trim((string) $rawValue);
                                if ($value === '') {
                                    return '';
                                }

                                $timestamp = strtotime($value);
                                if ($timestamp !== false) {
                                    return date('H:i', $timestamp);
                                }

                                if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
                                    return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
                                }

                                return $value;
                            };

                            $startTimeRaw =
                                (property_exists($property, 'start_time') ? $property->start_time : null)
                                ?? ($cardDetails['start_time'] ?? null)
                                ?? (property_exists($property, 'departure_time') ? $property->departure_time : null)
                                ?? ($cardDetails['departure_time'] ?? null)
                                ?? (property_exists($property, 'activity_start_time') ? $property->activity_start_time : null)
                                ?? ($cardDetails['activity_start_time'] ?? null)
                                ?? (property_exists($property, 'start_at') ? $property->start_at : null);
                            $endTimeRaw =
                                (property_exists($property, 'end_time') ? $property->end_time : null)
                                ?? ($cardDetails['end_time'] ?? null)
                                ?? (property_exists($property, 'return_time') ? $property->return_time : null)
                                ?? ($cardDetails['return_time'] ?? null)
                                ?? (property_exists($property, 'activity_end_time') ? $property->activity_end_time : null)
                                ?? ($cardDetails['activity_end_time'] ?? null)
                                ?? (property_exists($property, 'end_at') ? $property->end_at : null);

                            $startTimeLabel = $formatTimeLabel($startTimeRaw);
                            $endTimeLabel = $formatTimeLabel($endTimeRaw);
                            $starRank = max(0, min(5, (int) round((float) ($property->star_rating ?? $property->stars ?? $property->hotel_stars ?? 0))));
                            $reviewScoreRaw = (float) ($property->rating ?? $property->average_rating ?? 0);
                            $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                            $reviewCount = (int) ($property->reviews_count ?? 0);
                            $detailUrl = $categoryKey === 'accommodation'
                                ? ('/property/' . $propertyId)
                                : ('/category-booking/' . $categoryKey . '/' . $propertyId);
                            $includesBreakfast = (bool) (
                                $property->breakfast_included
                                ?? $property->includes_breakfast
                                ?? $property->has_breakfast
                                ?? false
                            );
                            $mealPlan = trim((string) (
                                $property->meal_plan
                                ?? $property->board_type
                                ?? ''
                            ));
                            $promotionMessage = trim((string) (
                                $property->promotion_message
                                ?? $property->promo_text
                                ?? $property->offer_text
                                ?? ''
                            ));
                            $offerSummary = $promotionMessage !== ''
                                ? $promotionMessage
                                : ($mealPlan !== ''
                                    ? ucwords(str_replace(['_', '-'], ' ', $mealPlan))
                                    : ($includesBreakfast ? 'Breakfast included' : 'Without breakfast'));
                            $actionLabel = $categoryKey === 'accommodation' ? 'View Deal' : 'Book Now';
                            // Dual-currency pricing for non-accommodation service cards
                            $priceLocal = $categoryKey !== 'accommodation'
                                ? max(0.0, (float) ($cardDetails['price_local'] ?? $price))
                                : $price;
                            $priceUsdRaw = $categoryKey !== 'accommodation'
                                ? ($cardDetails['price_usd'] ?? $cardDetails['price_foreign'] ?? null)
                                : null;
                            $priceUsd = ($priceUsdRaw !== null && is_numeric($priceUsdRaw) && (float) $priceUsdRaw > 0)
                                ? (float) $priceUsdRaw
                                : null;
                            if ($categoryKey === 'excursion' || $categoryKey === 'water_sports') {
                                $excLocalAdult = (float) ($cardDetails['adult_price_local'] ?? 0);
                                $excForeignAdult = (float) ($cardDetails['adult_price_foreign'] ?? 0);
                                if ($priceLocal <= 0 && $excLocalAdult > 0) {
                                    $priceLocal = $excLocalAdult;
                                }
                                if ($priceUsd === null && $excForeignAdult > 0) {
                                    $priceUsd = $excForeignAdult;
                                }
                            }
                            $convertByResidency = static function (float $amount, string $sourceCurrency) use ($visitorIsLocal, $mvrUsdRate): array {
                                $value = max(0.0, $amount);
                                $currencyCode = strtoupper(trim($sourceCurrency));
                                if ($currencyCode === '') {
                                    $currencyCode = 'MVR';
                                }

                                if ($visitorIsLocal) {
                                    if ($currencyCode === 'USD' && $mvrUsdRate > 0) {
                                        return ['currency' => 'MVR', 'amount' => round($value * $mvrUsdRate, 2)];
                                    }
                                    return ['currency' => 'MVR', 'amount' => round($value, 2)];
                                }

                                if ($currencyCode === 'MVR' && $mvrUsdRate > 0) {
                                    return ['currency' => 'USD', 'amount' => round($value / $mvrUsdRate, 2)];
                                }

                                return ['currency' => 'USD', 'amount' => round($value, 2)];
                            };
                            $baseDisplay = $convertByResidency($price, (string) ($property->currency ?? 'MVR'));
                            $primaryDisplayCurrency = (string) ($baseDisplay['currency'] ?? ($visitorIsLocal ? 'MVR' : 'USD'));
                            $primaryDisplayPrice = (float) ($baseDisplay['amount'] ?? 0);
                            $secondaryDisplay = null;
                            if ($categoryKey !== 'accommodation') {
                                if ($visitorIsLocal) {
                                    if ($priceLocal > 0) {
                                        $primaryDisplayCurrency = 'MVR';
                                        $primaryDisplayPrice = $priceLocal;
                                    }
                                    if ($priceUsd !== null && $priceUsd > 0) {
                                        $secondaryDisplay = ['currency' => 'USD', 'amount' => $priceUsd];
                                    }
                                } else {
                                    if ($priceUsd !== null && $priceUsd > 0) {
                                        $primaryDisplayCurrency = 'USD';
                                        $primaryDisplayPrice = $priceUsd;
                                    } elseif ($priceLocal > 0 && $mvrUsdRate > 0) {
                                        $primaryDisplayCurrency = 'USD';
                                        $primaryDisplayPrice = round($priceLocal / $mvrUsdRate, 2);
                                    }
                                    if ($priceLocal > 0) {
                                        $secondaryDisplay = ['currency' => 'MVR', 'amount' => $priceLocal];
                                    }
                                }
                            }
                        @endphp
                        @php
                            $propertyDetails = [];
                            $detailsSources = [
                                $property->listing_details ?? null,
                                $property->details ?? null,
                            ];
                            foreach ($detailsSources as $detailsSource) {
                                if (is_array($detailsSource)) {
                                    $propertyDetails = $detailsSource;
                                    break;
                                }
                                if (is_object($detailsSource)) {
                                    $propertyDetails = (array) $detailsSource;
                                    break;
                                }
                                if (is_string($detailsSource) && trim($detailsSource) !== '') {
                                    $decodedDetails = json_decode($detailsSource, true);
                                    if (is_array($decodedDetails)) {
                                        $propertyDetails = $decodedDetails;
                                        break;
                                    }
                                }
                            }
                            $rawLat = $propertyDetails['map_latitude'] ?? $property->map_latitude ?? $property->latitude ?? $property->lat ?? $property->location_lat ?? $property->geo_lat ?? null;
                            $rawLng = $propertyDetails['map_longitude'] ?? $property->map_longitude ?? $property->longitude ?? $property->lng ?? $property->location_lng ?? $property->geo_lng ?? null;
                            $lat = is_numeric($rawLat) ? (float) $rawLat : null;
                            $lng = is_numeric($rawLng) ? (float) $rawLng : null;
                        @endphp
                        <article
                            class="card"
                            data-property-card
                            data-id="{{ $propertyId }}"
                            data-name="{{ e((string) ($property->name ?? 'Listing')) }}"
                            data-city="{{ e((string) ($property->city ?? '')) }}"
                            data-island="{{ e((string) ($property->island ?? '')) }}"
                            data-atoll="{{ e((string) ($property->atoll ?? '')) }}"
                            data-price="{{ number_format($primaryDisplayPrice, 2, '.', '') }}"
                            data-currency="{{ e($primaryDisplayCurrency) }}"
                            data-lat="{{ $lat !== null ? $lat : '' }}"
                            data-lng="{{ $lng !== null ? $lng : '' }}"
                        >
                            @if ($categoryKey === 'accommodation')
                            <a class="card-link card-link-accommodation" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                                @php
                                    $resolvedImage = ($thumbUrl && trim($thumbUrl) !== '')
                                        ? (string) $thumbUrl
                                        : ($bannerUrl ?: ($fallbackImage !== '' ? $fallbackImage : $svgFallback));
                                    $accIsland  = trim((string) ($property->island ?? ($propertyDetails['island'] ?? '')));
                                    $accCity    = trim((string) ($property->city ?? ($propertyDetails['location_city'] ?? ($propertyDetails['city'] ?? ''))));
                                    $accAtoll   = trim((string) ($property->atoll ?? ($propertyDetails['location_state'] ?? ($propertyDetails['atoll'] ?? ''))));
                                    $accCountry = trim((string) ($property->location_country ?? $property->country ?? ($propertyDetails['location_country'] ?? 'Maldives')));
                                    $accLocationParts = array_values(array_filter([
                                        $accIsland !== '' ? $accIsland : $accCity,
                                        $accAtoll,
                                        $accCountry,
                                    ], static fn ($item): bool => trim((string) $item) !== ''));
                                    $accommodationLocationLabel = $accLocationParts !== [] ? implode(', ', $accLocationParts) : 'Maldives';
                                @endphp
                                <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $svgFallback }}';};" alt="{{ (string) ($property->name ?? 'Listing image') }}" loading="lazy">
                                <div class="card-body card-body-accommodation">
                                    <div class="card-main">
                                        <span class="card-city">{{ $accommodationLocationLabel }}</span>
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
                                    </div>
                                    <div class="card-side">
                                        <div class="card-price">
                                            @if ($primaryDisplayPrice > 0)
                                                From {{ $primaryDisplayCurrency }} {{ number_format($primaryDisplayPrice, 2) }}
                                            @else
                                                Price on request
                                            @endif
                                        </div>
                                        <div class="card-offer">{{ $offerSummary }}</div>
                                        <span class="card-action-btn">{{ $actionLabel }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                            </a>
                            @else
                            <a class="card-link" href="{{ $detailUrl }}" aria-label="Open {{ (string) ($property->name ?? 'listing') }} profile">
                                @php
                                    $resolvedImage = ($thumbUrl && trim($thumbUrl) !== '')
                                        ? (string) $thumbUrl
                                        : ($bannerUrl ?: ($fallbackImage !== '' ? $fallbackImage : $svgFallback));
                                    $cardEyebrow = $isExcursionCard
                                        ? ($activityType !== '' ? str_replace('_', ' ', $activityType) : 'Excursion')
                                        : (string) ($categoryMeta['label'] ?? ucfirst(str_replace('_', ' ', (string) $categoryKey)));
                                @endphp
                                <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $svgFallback }}';};" alt="{{ (string) ($property->name ?? 'Listing image') }}" loading="lazy">
                                <div class="card-body">
                                    <div class="card-main-col">
                                        <span class="card-type-chip">{{ $cardEyebrow }}</span>
                                        <h3>{{ $isExcursionCard ? $activityName : (string) ($property->name ?? 'Listing') }}</h3>
                                        <span class="card-city"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $originLabel }}</span>
                                        @if ($shortDescription !== '')
                                            <p class="card-desc">{{ $shortDescription }}</p>
                                        @endif
                                        @if ($startTimeLabel !== '' || $endTimeLabel !== '')
                                            <div class="card-time">
                                                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                                @if ($startTimeLabel !== '' && $endTimeLabel !== '')
                                                    {{ $startTimeLabel }} - {{ $endTimeLabel }}
                                                @elseif ($startTimeLabel !== '')
                                                    Starts {{ $startTimeLabel }}
                                                @else
                                                    Ends {{ $endTimeLabel }}
                                                @endif
                                            </div>
                                        @endif
                                        <div class="card-stars" aria-label="Star ranking">
                                            @if ($starRank > 0)
                                                @for ($i = 0; $i < $starRank; $i++)
                                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                @endfor
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-meta-right">
                                        <div class="card-review">
                                            <span class="card-rating-badge">{{ $reviewScore }}</span>
                                            <span>{{ number_format($reviewCount) }} reviews</span>
                                        </div>
                                        <div class="card-price">
                                            @if ($primaryDisplayPrice > 0)
                                                <span class="price-local">From {{ $primaryDisplayCurrency }} {{ number_format($primaryDisplayPrice, 2) }}</span>
                                                @if (is_array($secondaryDisplay) && (float) ($secondaryDisplay['amount'] ?? 0) > 0)
                                                    <span class="price-foreign">≈ {{ (string) ($secondaryDisplay['currency'] ?? 'MVR') }} {{ number_format((float) ($secondaryDisplay['amount'] ?? 0), 2) }}</span>
                                                @endif
                                            @else
                                                Price on request
                                            @endif
                                        </div>
                                        <span class="card-action-btn">{{ $actionLabel }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                            </a>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endforeach
                    <div class="catalog-show-more-wrap" id="catalogShowMoreWrap" hidden>
                        <button class="load-more-btn" id="catalogShowMoreButton" type="button">Show more listings</button>
                    </div>
                </div>
                <aside class="catalog-map-panel" aria-label="Map of filtered category results">
                    @php
                        $mapRadiusOptions = [5, 10, 25, 50];
                        $mapBaseQuery = request()->query();
                        unset($mapBaseQuery['distance_km']);
                        $activeMapRadius = is_numeric($filters['distance_km'] ?? null)
                            ? (float) ($filters['distance_km'] ?? 0)
                            : 25.0;
                        if ($activeMapRadius <= 0) {
                            $activeMapRadius = 25.0;
                        }
                    @endphp
                    <div class="catalog-map-head">
                        <div class="catalog-map-head-main">
                            <strong>Map View</strong>
                        </div>
                        <div class="map-radius-controls" aria-label="Map search radius">
                            @foreach ($mapRadiusOptions as $radiusOption)
                                @php
                                    $radiusValue = (float) $radiusOption;
                                    $radiusQuery = array_merge($mapBaseQuery, [
                                        'distance_km' => $radiusOption,
                                        'sort' => (string) ($filters['sort'] ?? '') !== '' ? (string) ($filters['sort'] ?? '') : 'distance_nearest',
                                    ]);
                                    $radiusUrl = url()->current() . '?' . http_build_query($radiusQuery);
                                    $isActiveRadius = abs($activeMapRadius - $radiusValue) < 0.51;
                                @endphp
                                <a class="map-radius-chip{{ $isActiveRadius ? ' is-active' : '' }}" href="{{ $radiusUrl }}" data-radius-km="{{ $radiusOption }}">{{ $radiusOption }} km</a>
                            @endforeach
                        </div>
                        <span id="mapResultCount">0 results</span>
                    </div>
                    <div class="category-map-wrap">
                        <button type="button" class="map-search-area-btn" id="mapSearchAreaButton">Search in this area</button>
                        <div id="categoryResultsMap" role="application" aria-label="Filtered listing locations"></div>
                    </div>
                    <div class="map-empty" id="categoryMapEmpty" hidden>No map points available for the selected filters. Try broadening your search.</div>
                </aside>
            </div>
        @endif

        @include('partials.global-site-footer')
            </div>{{-- /page-main-content --}}
        </div>{{-- /page-body-split --}}
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        (function () {
            const page = document.querySelector('.page');
            const header = document.querySelector('.header-bar');
            if (!page || !header) {
                return;
            }

            if (header.matches('[data-uniform-header]')) {
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
            const searchForm = document.getElementById('categorySearchForm');
            const openPopupButton = document.getElementById('openFilterPopup');
            const closePopupButton = document.getElementById('closeFilterPopup');
            const popupBackdrop = document.getElementById('filterPopupBackdrop');
            const headerSearchInput = document.querySelector('.header-search-mini input[type="search"]');
            const headerSearchButton = document.querySelector('.header-search-mini button');
            const searchField = document.getElementById('q');

            if (searchForm && openPopupButton && closePopupButton && popupBackdrop) {
                const togglePopup = function (isOpen) {
                    popupBackdrop.hidden = !isOpen;
                    document.body.style.overflow = isOpen ? 'hidden' : '';
                };

                openPopupButton.addEventListener('click', function () {
                    togglePopup(true);
                });

                closePopupButton.addEventListener('click', function () {
                    togglePopup(false);
                });

                popupBackdrop.addEventListener('click', function (event) {
                    if (event.target === popupBackdrop) {
                        togglePopup(false);
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        togglePopup(false);
                    }
                });
            }

            if (headerSearchInput && headerSearchButton && searchForm && searchField) {
                const submitHeaderSearch = function () {
                    searchField.value = String(headerSearchInput.value || '').trim();
                    searchForm.submit();
                };

                headerSearchButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    submitHeaderSearch();
                });

                headerSearchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitHeaderSearch();
                    }
                });
            }

            if (searchField && searchForm) {
                let debounceTimer = null;
                searchField.addEventListener('input', function () {
                    const value = String(searchField.value || '').trim();
                    if (debounceTimer !== null) {
                        window.clearTimeout(debounceTimer);
                    }
                    debounceTimer = window.setTimeout(function () {
                        if (value.length >= 2 || value === '') {
                            searchForm.submit();
                        }
                    }, 600);
                });
            }

            if (searchForm) {
                const autoSubmitSelectors = searchForm.querySelectorAll('select[name="sort"], #sort_popup, #availability_only, #min_rating, #min_reviews');
                autoSubmitSelectors.forEach(function (element) {
                    element.addEventListener('change', function () {
                        const mirrorTarget = element.getAttribute('data-mirror-target');
                        if (mirrorTarget) {
                            const targetField = searchForm.querySelector('[name="' + mirrorTarget + '"]');
                            if (targetField) {
                                targetField.value = element.value;
                            }
                        }
                        searchForm.submit();
                    });
                });

                const sortField = searchForm.querySelector('select[name="sort"]');
                const sortPopup = document.getElementById('sort_popup');
                if (sortField && sortPopup) {
                    sortPopup.value = sortField.value;
                    sortField.addEventListener('change', function () {
                        sortPopup.value = sortField.value;
                    });
                }
            }

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
            const listRoot = document.querySelector('.catalog-results-list');
            const showMoreWrap = document.getElementById('catalogShowMoreWrap');
            const showMoreButton = document.getElementById('catalogShowMoreButton');
            if (!listRoot || !showMoreWrap || !showMoreButton) {
                return;
            }

            const pageSize = 20;
            const cards = Array.from(listRoot.querySelectorAll('[data-property-card]'));
            if (cards.length <= pageSize) {
                showMoreWrap.hidden = true;
                return;
            }

            const sectionGrids = Array.from(listRoot.querySelectorAll('.catalog-grid'));
            const sectionTitles = Array.from(listRoot.querySelectorAll('.catalog-section-title'));
            let visibleCount = pageSize;

            function syncSectionVisibility() {
                sectionGrids.forEach(function (grid, index) {
                    const hasVisible = Array.from(grid.querySelectorAll('[data-property-card]')).some(function (card) {
                        return !card.hidden;
                    });
                    grid.hidden = !hasVisible;
                    if (sectionTitles[index]) {
                        sectionTitles[index].hidden = !hasVisible;
                    }
                });
            }

            function applyVisibleWindow() {
                cards.forEach(function (card, index) {
                    const hidden = index >= visibleCount;
                    card.hidden = hidden;
                    card.setAttribute('data-hidden-by-pager', hidden ? '1' : '0');
                });

                syncSectionVisibility();

                const remaining = Math.max(0, cards.length - visibleCount);
                if (remaining > 0) {
                    showMoreWrap.hidden = false;
                    showMoreButton.textContent = 'Show more listings (' + remaining + ' left)';
                } else {
                    showMoreWrap.hidden = true;
                }

                window.dispatchEvent(new CustomEvent('catalog:cards-visibility-updated'));
            }

            showMoreButton.addEventListener('click', function () {
                visibleCount = Math.min(cards.length, visibleCount + pageSize);
                applyVisibleWindow();
            });

            applyVisibleWindow();
        })();
    </script>

    <script>
        (function () {
            const stickyWrap = document.querySelector('.search-sticky-wrap');
            const heroBanner = document.querySelector('.hero-banner');
            const pageRoot = document.querySelector('.page.category-accommodation');

            if (!stickyWrap || !heroBanner || !pageRoot) {
                return;
            }

            const mobileQuery = window.matchMedia('(max-width: 680px)');

            function updateStickySearch() {
                if (mobileQuery.matches) {
                    stickyWrap.classList.remove('is-fixed');
                    stickyWrap.style.removeProperty('--sticky-search-top');
                    pageRoot.style.removeProperty('--catalog-results-top');
                    pageRoot.style.removeProperty('--catalog-results-height');
                    return;
                }

                const currentY = window.scrollY || 0;
                const header = document.querySelector('[data-uniform-header]');
                const headerBottom = header
                    ? Math.max(0, Math.round(header.getBoundingClientRect().bottom))
                    : 0;
                const heroBottom = heroBanner.getBoundingClientRect().bottom;
                const isAccommodation = pageRoot.classList.contains('category-accommodation');
                const shouldFix = isAccommodation
                    ? currentY > 0
                    : (heroBottom <= (headerBottom + 30));

                stickyWrap.classList.toggle('is-fixed', shouldFix);
                if (shouldFix) {
                    stickyWrap.style.setProperty('--sticky-search-top', String(headerBottom) + 'px');
                    const stickyHeight = Math.round(stickyWrap.getBoundingClientRect().height);
                    const resultsTop = headerBottom + stickyHeight;
                    const resultsHeight = Math.max(420, window.innerHeight - resultsTop);
                    pageRoot.style.setProperty('--catalog-results-top', String(resultsTop) + 'px');
                    pageRoot.style.setProperty('--catalog-results-height', String(resultsHeight) + 'px');
                } else {
                    stickyWrap.style.removeProperty('--sticky-search-top');
                    pageRoot.style.removeProperty('--catalog-results-top');
                    pageRoot.style.removeProperty('--catalog-results-height');
                }
            }

            window.addEventListener('scroll', updateStickySearch, { passive: true });
            window.addEventListener('resize', updateStickySearch);
            if (mobileQuery.addEventListener) {
                mobileQuery.addEventListener('change', updateStickySearch);
            }

            updateStickySearch();
        })();
    </script>

    <script>
        (function () {
            const mapContainer = document.getElementById('categoryResultsMap');
            const mapEmpty = document.getElementById('categoryMapEmpty');
            const countLabel = document.getElementById('mapResultCount');
            const pageRoot = document.querySelector('.page');
            const mapSearchAreaButton = document.getElementById('mapSearchAreaButton');
            const isAccommodationCatalog = !!(pageRoot && pageRoot.classList.contains('category-accommodation'));

            function hashText(value) {
                let hash = 0;
                const text = String(value || '');
                for (let i = 0; i < text.length; i += 1) {
                    hash = ((hash << 5) - hash) + text.charCodeAt(i);
                    hash |= 0;
                }
                return Math.abs(hash);
            }

            function fallbackCoords(item) {
                const key = String(item.island || item.city || item.atoll || item.name || 'maldives').toLowerCase();
                const seed = hashText(key);
                // Maldives center: 4.1755°N, 73.5093°E
                // Bounds approximately: 3.2°N to 5.0°N latitude, 72.0°E to 75.0°E longitude
                const baseLat = 4.1755;
                const baseLng = 73.5093;
                const latOffset = ((seed % 900) / 1000) - 0.45;  // Range: -0.45 to +0.45
                const lngOffset = (((Math.floor(seed / 900)) % 1500) / 1000) - 0.75;  // Range: -0.75 to +0.75

                return {
                    lat: baseLat + latOffset,
                    lng: baseLng + lngOffset,
                };
            }

            function parseCardData(card) {
                const linkEl = card.querySelector('.card-link');
                const parseCoordinate = function (rawValue) {
                    if (rawValue === null || typeof rawValue === 'undefined') {
                        return null;
                    }
                    const normalized = String(rawValue).trim().replace(',', '.');
                    if (normalized === '') {
                        return null;
                    }
                    const value = Number(normalized);
                    return Number.isFinite(value) ? value : null;
                };
                const latRaw = parseCoordinate(card.getAttribute('data-lat'));
                const lngRaw = parseCoordinate(card.getAttribute('data-lng'));

                return {
                    id: String(card.getAttribute('data-id') || ''),
                    name: String(card.getAttribute('data-name') || 'Listing'),
                    city: String(card.getAttribute('data-city') || ''),
                    island: String(card.getAttribute('data-island') || ''),
                    atoll: String(card.getAttribute('data-atoll') || ''),
                    currency: String(card.getAttribute('data-currency') || 'MVR'),
                    price: Number(card.getAttribute('data-price') || 0),
                    url: linkEl ? linkEl.getAttribute('href') : '',
                    lat: latRaw,
                    lng: lngRaw,
                    card: card,
                };
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function shortPriceLabel(currency, priceValue) {
                const normalized = Number(priceValue || 0);
                if (!Number.isFinite(normalized) || normalized <= 0) {
                    return escapeHtml(currency) + ' -';
                }

                if (normalized >= 1000) {
                    return escapeHtml(currency) + ' ' + Math.round(normalized).toLocaleString();
                }

                return escapeHtml(currency) + ' ' + normalized.toFixed(0);
            }

            if (!mapContainer || typeof window.L === 'undefined') {
                return;
            }

            const cards = Array.from(document.querySelectorAll('[data-property-card]'));
            const uniqueItems = new Map();
            cards.forEach(function (card, index) {
                const item = parseCardData(card);
                if (item.id === '') {
                    item.id = 'listing-' + String(index);
                }

                if (uniqueItems.has(item.id)) {
                    return;
                }
                uniqueItems.set(item.id, item);
            });

            const items = Array.from(uniqueItems.values());
            if (items.length === 0) {
                mapContainer.hidden = true;
                if (mapEmpty) {
                    mapEmpty.hidden = false;
                }
                return;
            }

            const map = window.L.map(mapContainer, {
                zoomControl: true,
                scrollWheelZoom: true,
                center: [4.1755, 73.5093],
                zoom: 8,
            });

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const markerClassByDensity = items.length > 80
                ? 'is-super-compact'
                : (items.length > 45 ? 'is-compact' : '');
            const shouldUseFallbackCoords = true;
            const bounds = [];
            const markerById = new Map();
            const markerElementById = new Map();
            let mappedCount = 0;

            function setMarkerActive(propertyId, isActive) {
                const markerElement = markerElementById.get(propertyId);
                if (!markerElement) {
                    return;
                }
                markerElement.classList.toggle('is-active', isActive);
            }

            items.forEach(function (item) {
                let coords = null;
                if (item.lat !== null && item.lng !== null) {
                    coords = { lat: item.lat, lng: item.lng };
                } else if (shouldUseFallbackCoords) {
                    coords = fallbackCoords(item);
                }

                if (!coords) {
                    return;
                }

                mappedCount += 1;

                bounds.push([coords.lat, coords.lng]);

                const iconHtml = '<span class="price-marker ' + markerClassByDensity + '" data-marker-id="' + escapeHtml(item.id) + '">' + shortPriceLabel(item.currency, item.price) + '</span>';
                const markerIcon = window.L.divIcon({
                    className: 'price-pill-icon',
                    html: iconHtml,
                    iconSize: [1, 1],
                    iconAnchor: [0, 0],
                });

                const marker = window.L.marker([coords.lat, coords.lng], { icon: markerIcon }).addTo(map);
                markerById.set(item.id, marker);

                const locationLine = [item.island, item.city, item.atoll].filter(function (value) { return value !== ''; }).join(', ');
                const popupHtml = [
                    '<div style="min-width:190px">',
                    '<strong>' + escapeHtml(item.name) + '</strong>',
                    locationLine ? '<div style="margin-top:4px;color:#4f6a7f;font-size:12px">' + escapeHtml(locationLine) + '</div>' : '',
                    '<div style="margin-top:6px;font-size:12px">From ' + escapeHtml(item.currency) + ' ' + Number(item.price || 0).toFixed(2) + '</div>',
                    item.url ? '<a href="' + escapeHtml(item.url) + '" style="display:inline-block;margin-top:8px;font-size:12px;color:#0f6179;font-weight:700;text-decoration:none">Open listing</a>' : '',
                    '</div>',
                ].join('');

                marker.bindPopup(popupHtml);

                marker.on('popupopen', function () {
                    setMarkerActive(item.id, true);
                });

                marker.on('popupclose', function () {
                    setMarkerActive(item.id, false);
                });

                marker.on('mouseover', function () {
                    setMarkerActive(item.id, true);
                });

                marker.on('mouseout', function () {
                    const popupIsOpen = marker.isPopupOpen && marker.isPopupOpen();
                    if (!popupIsOpen) {
                        setMarkerActive(item.id, false);
                    }
                });

                marker.on('click', function () {
                    setMarkerActive(item.id, true);
                });
            });

            if (countLabel) {
                countLabel.textContent = mappedCount + (mappedCount === 1 ? ' result' : ' results');
            }

            if (bounds.length === 0) {
                mapContainer.hidden = true;
                if (mapEmpty) {
                    mapEmpty.hidden = false;
                    mapEmpty.textContent = 'No exact coordinates are available for these listings yet.';
                }
                if (mapSearchAreaButton) {
                    mapSearchAreaButton.hidden = true;
                }
                return;
            }

            window.requestAnimationFrame(function () {
                const markerElements = mapContainer.querySelectorAll('.price-marker[data-marker-id]');
                markerElements.forEach(function (el) {
                    const markerId = String(el.getAttribute('data-marker-id') || '');
                    if (markerId !== '') {
                        markerElementById.set(markerId, el);
                    }
                });
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [26, 26] });
            } else {
                map.setView(bounds[0], 11);
            }

            window.addEventListener('resize', function () {
                map.invalidateSize();
            });

            if (mapSearchAreaButton) {
                mapSearchAreaButton.addEventListener('click', function () {
                    const mapCenter = map.getCenter();
                    const mapBounds = map.getBounds();
                    const northEast = mapBounds.getNorthEast();
                    const southWest = mapBounds.getSouthWest();
                    const deltaLat = Math.abs(northEast.lat - southWest.lat);
                    const approxDistanceKm = Math.max(1, Math.round((deltaLat * 111) / 2));
                    const activeRadiusChip = document.querySelector('.map-radius-chip.is-active[data-radius-km]');
                    const chipRadiusKm = activeRadiusChip ? Number(activeRadiusChip.getAttribute('data-radius-km')) : null;
                    const selectedDistanceKm = Number.isFinite(chipRadiusKm) && chipRadiusKm > 0
                        ? Math.round(chipRadiusKm)
                        : approxDistanceKm;

                    const url = new URL(window.location.href);
                    url.searchParams.set('user_lat', mapCenter.lat.toFixed(6));
                    url.searchParams.set('user_lng', mapCenter.lng.toFixed(6));
                    url.searchParams.set('distance_km', String(selectedDistanceKm));
                    if (!url.searchParams.get('sort')) {
                        url.searchParams.set('sort', 'distance_nearest');
                    }
                    window.location.href = url.toString();
                });
            }

            items.forEach(function (item) {
                if (!item.card) {
                    return;
                }

                item.card.addEventListener('mouseenter', function () {
                    const marker = markerById.get(item.id);
                    if (!marker) {
                        return;
                    }
                    setMarkerActive(item.id, true);
                });

                item.card.addEventListener('mouseleave', function () {
                    const marker = markerById.get(item.id);
                    if (!marker) {
                        return;
                    }
                    const popupIsOpen = marker.isPopupOpen && marker.isPopupOpen();
                    if (!popupIsOpen) {
                        setMarkerActive(item.id, false);
                    }
                });
            });

            if (pageRoot) {
                pageRoot.classList.add('has-side-map');
            }
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

                    const atollRow = (Array.isArray(atolls) ? atolls : []).find(function (atoll) {
                        return Number(atoll && atoll.id ? atoll.id : 0) === Number(atollId);
                    });

                    const embeddedNames = Array.isArray(atollRow && atollRow.islands ? atollRow.islands : null)
                        ? atollRow.islands
                            .map(function (island) { return String(island && island.name ? island.name : '').trim(); })
                            .filter(function (name) { return name !== ''; })
                        : [];

                    if (embeddedNames.length > 0) {
                        islandsByAtollName.set(atollName, embeddedNames);
                        embeddedNames.forEach(function (name) { allIslandNames.add(name); });
                        return;
                    }

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

    <script>
        (function () {
            const searchForm = document.getElementById('categorySearchForm');
            if (!searchForm) {
                return;
            }

            const now = new Date();
            now.setHours(0, 0, 0, 0);
            const todayString = now.toISOString().slice(0, 10);

            const dateInputs = Array.from(searchForm.querySelectorAll('input[type="date"], input[type="datetime-local"]'));
            dateInputs.forEach(function (input) {
                if (input.type === 'datetime-local') {
                    input.min = todayString + 'T00:00';
                } else {
                    input.min = todayString;
                }
            });

            const syncDatePair = function (startInput, endInput, allowEqual) {
                if (!startInput || !endInput) {
                    return;
                }

                const addDays = function (dateString, days) {
                    const normalized = String(dateString || '').trim();
                    if (normalized === '') {
                        return '';
                    }

                    const dt = new Date(normalized + 'T00:00:00');
                    if (Number.isNaN(dt.getTime())) {
                        return '';
                    }

                    dt.setDate(dt.getDate() + days);
                    return dt.toISOString().slice(0, 10);
                };

                const strictCheckoutPair = startInput.id === 'checkin' && endInput.id === 'checkout';

                const sync = function () {
                    const startValue = String(startInput.value || '').trim();
                    const startDateOnly = startValue.slice(0, 10);
                    const minimumEnd = startDateOnly !== ''
                        ? (strictCheckoutPair ? addDays(startDateOnly, 1) : startValue)
                        : (endInput.type === 'datetime-local' ? todayString + 'T00:00' : todayString);
                    endInput.min = minimumEnd;

                    if (startDateOnly !== '' && startDateOnly < todayString) {
                        startInput.setCustomValidity('Date cannot be in the past.');
                    } else {
                        startInput.setCustomValidity('');
                    }

                    const endValue = String(endInput.value || '').trim();
                    if (startValue !== '' && endValue !== '') {
                        const boundary = String(endInput.min || '').trim();
                        const isInvalid = boundary !== '' ? endValue < boundary : (allowEqual ? endValue < startValue : endValue <= startValue);
                        endInput.setCustomValidity(isInvalid
                            ? (strictCheckoutPair ? 'Check-out date must be after check-in date.' : 'End date must be after start date.')
                            : '');

                        if (isInvalid && boundary !== '') {
                            endInput.value = boundary;
                        }
                    } else {
                        endInput.setCustomValidity('');
                    }
                };

                startInput.addEventListener('change', sync);
                startInput.addEventListener('input', sync);
                endInput.addEventListener('change', sync);
                endInput.addEventListener('input', sync);
                sync();
            };

            syncDatePair(document.getElementById('checkin'), document.getElementById('checkout'), false);
            syncDatePair(document.getElementById('travel_date'), document.getElementById('return_date'), true);
            syncDatePair(document.getElementById('workspace_start'), document.getElementById('workspace_end'), true);
            syncDatePair(document.getElementById('pickup_date'), document.getElementById('return_date_rental'), true);
        })();
    </script>
</body>
</html>