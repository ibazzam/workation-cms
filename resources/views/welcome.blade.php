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
            --brand-strong: #0b4f66;
            --brand-soft: #dff1f6;
            --accent: #f3a337;
            --accent-soft: #fff3df;
            --search-control-height: 56px;
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
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto 28px;
            max-width: none;
            position: relative;
        }

        .floating-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100dvh;
            overflow-y: auto;
            scrollbar-width: thin;
            z-index: 200;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 24px;
            border-top: 0;
            border-right: 0;
            border-bottom: 1px solid #d8e3ec;
            border-left: 0;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 24px rgba(22, 64, 93, 0.06);
            margin: 0;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            position: sticky;
            top: 0;
            transition: transform 0.22s ease, opacity 0.22s ease;
            z-index: 980;
            backdrop-filter: blur(10px);
        }

        .page.is-header-hidden .header-bar {
            transform: translateY(calc(-100% - 2px));
            opacity: 0;
            pointer-events: none;
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 0 0 auto;
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
            display: grid;
            gap: 2px;
            align-content: center;
            flex-shrink: 0;
            padding-left: 6px;
        }

        .header-brand {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand);
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .header-brand-link {
            color: inherit;
            text-decoration: none;
        }

        .header-brand-link:hover {
            color: var(--brand-strong);
        }

        .header-subline {
            margin: 1px 0 0;
            font-size: 0.7rem;
            color: #71869a;
            white-space: nowrap;
        }

        .header-search-mini {
            display: none;
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
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            cursor: pointer;
            flex-shrink: 0;
        }

        .header-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
            min-width: 0;
            flex: 1 1 auto;
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

        .page-body-split {
            display: block;
            padding-left: 262px;
        }

        .page-main-content {
            flex: 1;
            min-width: 0;
        }

        .page-with-sidebar {
            display: contents;
        }

        .sidebar-fixed {
            display: none;
        }

        .sidebar-shell {
            border: 1px solid #c9ddeb;
            border-radius: 0;
            border-top: 0;
            border-left: 0;
            background: linear-gradient(160deg, #ffffff 0%, #f5f9fc 100%);
            padding: 0;
            box-shadow: inset 0 1px 0 #ffffff;
        }

        .sidebar-brand {
            display: grid;
            gap: 2px;
            padding: 10px 12px;
            border-bottom: 1px solid #d6e4ef;
            background: linear-gradient(180deg, #f8fcff 0%, #f1f8fd 100%);
            opacity: 1;
            transform: none;
            pointer-events: auto;
        }

        .sidebar-brand-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            color: var(--brand);
            text-decoration: none;
        }

        .sidebar-brand-title:hover {
            color: var(--brand-strong);
        }

        .sidebar-brand-subline {
            margin: 0;
            font-size: 0.7rem;
            color: #71869a;
            white-space: nowrap;
        }

        .page.is-header-hidden .floating-sidebar {
            top: 0;
            height: 100dvh;
        }

        .sidebar-title {
            margin: 10px 10px 8px;
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
            margin-left: auto;
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
            padding: 0 10px 10px;
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
            margin-top: 10px;
            margin-bottom: 18px;
            color: #e8f5f9;
            padding: 20px 20px 54px;
            min-height: 340px;
            width: 100%;
            position: relative;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: none;
            box-shadow: none;
        }

        .search-section-full-width::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 calc(50% - 50vw + 12px);
            width: calc(100vw - 24px);
            background:
                var(--home-hero-image, none),
                linear-gradient(135deg, #1550be 0%, #3c78e0 52%, #89b0ff 100%),
                radial-gradient(circle at 120% -10%, rgba(243, 163, 55, 0.08) 0%, rgba(243, 163, 55, 0) 40%),
                radial-gradient(circle at 15% 85%, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0) 35%);
            background-size: cover, cover, auto, auto;
            background-position: center center, center center, center center, center center;
            background-repeat: no-repeat;
            border-radius: 14px;
            pointer-events: none;
            z-index: 0;
        }

        .search-section-full-width::after {
            content: '';
            position: absolute;
            inset: 0 auto 0 calc(50% - 50vw + 12px);
            width: calc(100vw - 24px);
            background: linear-gradient(180deg, rgba(10, 33, 88, 0.2) 0%, rgba(10, 33, 88, 0.36) 100%);
            border-radius: 14px;
            pointer-events: none;
            z-index: 0;
        }

        .search-eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.72rem;
            color: #dce9ff;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            position: relative;
            z-index: 1;
        }

        .search-title {
            margin: 7px 0 0;
            font-size: clamp(1.45rem, 2.4vw, 2.3rem);
            line-height: 1.08;
            max-width: 760px;
            font-weight: 800;
            letter-spacing: -0.03em;
            position: relative;
            z-index: 1;
        }

        .search-support-strip {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            color: #e6f1ff;
            font-size: 0.75rem;
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
            margin: 14px auto 0;
            width: min(960px, 100%);
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #dde5ee;
            box-shadow: 0 20px 38px rgba(22, 49, 97, 0.2);
            padding: 10px 12px;
            overflow: visible;
        }

        .search-category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            width: 100%;
            padding: 10px;
            border-radius: 14px 14px 0 0;
            background: linear-gradient(180deg, var(--brand-strong) 0%, var(--brand) 100%);
            overflow: visible;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
            position: static;
            top: auto;
            z-index: 970;
        }

        .search-category-tab {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            background: rgba(7, 36, 51, 0.2);
            color: rgba(255,255,255,0.94);
            padding: 7px 11px;
            font: inherit;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            line-height: 1.15;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .search-category-tab i {
            flex: 0 0 auto;
            font-size: 0.74rem;
            opacity: 0.9;
        }

        .search-category-tab span {
            display: inline-block;
            max-width: 18ch;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }

        .search-category-tab:hover {
            background: rgba(255,255,255,0.14);
            border-color: rgba(255,255,255,0.4);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .search-category-tab.is-active {
            background: #ffffff;
            border-color: #ffffff;
            color: var(--brand);
            font-weight: 700;
        }

        .search-form {
            margin-top: 0;
            display: block;
            min-width: 0;
            overflow: visible;
            border-radius: 0 0 18px 18px;
            background: #ffffff;
        }

        .search-form.is-accommodation .search-inline-row {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 0.9fr) minmax(0, 0.9fr) minmax(0, 1.1fr) minmax(102px, 0.65fr);
            gap: 7px;
            align-items: stretch;
            padding: 12px 14px;
        }

        .search-form.is-accommodation .search-inline-row > * {
            min-width: 0;
        }

        .search-inline-row {
            display: flex;
            align-items: stretch;
            gap: 8px;
            padding: 12px 14px;
            overflow: visible;
        }

        .search-field-shell {
            display: block;
            min-width: 280px;
            flex: 1 1 340px;
            padding: 0;
            border-bottom: 0;
        }

        .search-primary-field {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
            border: 1px solid #d8e3ec;
            border-radius: 10px;
            overflow: hidden;
            background: #f9fbfd;
            height: var(--search-control-height);
            min-height: var(--search-control-height);
            max-height: var(--search-control-height);
            padding: 5px 9px;
            box-sizing: border-box;
        }

        .search-primary-field label {
            display: block;
            margin: 0;
            font-size: 0.56rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b8299;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-weight: 700;
            line-height: 1.1;
        }

        .search-primary-field input {
            border-radius: 0 !important;
            padding: 2px 0 0 !important;
            font-size: 0.84rem;
        }

        .search-dynamic-fields {
            margin-top: 0;
            display: none;
            grid-column: auto;
            grid-template-columns: none;
            gap: 8px;
            min-width: 0;
            padding: 0;
            background: transparent;
        }

        .search-dynamic-fields.is-active {
            display: flex;
            align-items: stretch;
            flex-wrap: nowrap;
            flex: 1 1 auto;
        }

        #accommodationFields.is-active {
            display: grid;
            grid-template-columns: repeat(3, minmax(150px, 1fr));
            gap: 8px;
            flex: 1 1 auto;
        }

        .search-form.is-accommodation #accommodationFields.is-active {
            display: contents;
        }

        .guest-picker {
            position: relative;
        }

        .guest-summary-btn {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            color: #103247;
            font: inherit;
            font-size: 0.86rem;
            padding: 2px 0 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            white-space: nowrap;
        }

        .guest-summary-btn [data-guest-summary-text] {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .guest-summary-btn i {
            color: #6b8299;
            font-size: 0.78rem;
            flex-shrink: 0;
        }

        .guest-popover {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 100%;
            min-width: 0;
            border: 1px solid #c9ddeb;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 12px 28px rgba(16, 50, 84, 0.2);
            padding: 6px;
            z-index: 80;
        }

        .guest-popover[hidden] {
            display: none !important;
        }

        .guest-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            padding: 1px 0;
        }

        .guest-label {
            color: #20415b;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .guest-counter {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .guest-counter button {
            width: 20px;
            height: 20px;
            min-width: 20px;
            border-radius: 999px;
            border: 1px solid #b7cddd;
            background: #f5fbff;
            color: #1d4b6a;
            font-size: 0.7rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .guest-counter input {
            width: 26px;
            text-align: center;
            border: 0;
            padding: 0;
            font-size: 0.76rem;
            font-weight: 700;
            color: #103247;
            background: transparent;
        }

        .search-dynamic-fields[hidden] {
            display: none !important;
        }

        .search-dynamic-fields .field {
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            gap: 2px;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #d8e3ec;
            border-radius: 10px;
            padding: 5px 8px;
            background: #f9fbfd;
            height: var(--search-control-height);
            min-height: var(--search-control-height);
            max-height: var(--search-control-height);
            box-sizing: border-box;
        }

        .search-dynamic-fields .field > input,
        .search-dynamic-fields .field > select {
            align-self: end;
            line-height: 1.15;
        }

        .search-dynamic-fields.is-active .field {
            flex: 1 1 0;
        }

        .search-dynamic-fields .field.guest-picker {
            overflow: visible;
            position: relative;
            z-index: 40;
        }

        .search-dynamic-fields .field label {
            display: block;
            margin: 0;
            font-size: 0.56rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #6b8299;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-weight: 600;
        }

        .search-form select,
        .search-form input {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border: 0;
            border-radius: 0;
            padding: 8px 10px;
            font: inherit;
            font-size: 0.88rem;
            color: #103247;
            background: transparent;
            box-sizing: border-box;
        }

        .search-dynamic-fields select,
        .search-dynamic-fields input {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border: 0;
            border-radius: 0;
            padding: 2px 0 0;
            font-size: 0.86rem;
            font: inherit;
            color: #103247;
            background: transparent;
            box-sizing: border-box;
        }

        .search-dynamic-fields input::placeholder {
            color: #99aec4;
        }

        .search-form input[type="date"],
        .search-dynamic-fields input[type="date"] {
            appearance: none;
            -webkit-appearance: none;
            display: block;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            padding: 2px 0;
            overflow: hidden;
            font-size: 0.86rem;
            cursor: pointer;
        }

        .search-form input[type="number"],
        .search-dynamic-fields input[type="number"] {
            padding: 2px 6px;
            font-size: 0.86rem;
        }

        .guest-counter input[type="number"] {
            width: 40px;
            padding: 0;
            text-align: center;
        }

        .search-form button {
            min-width: 0;
            width: auto;
            justify-self: stretch;
            box-shadow: none;
            border-radius: 10px;
        }

        .search-submit-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 0;
            border-top: 0;
            background: transparent;
            flex: 0 0 auto;
            align-items: stretch;
            min-width: 0;
            width: auto;
        }

        .search-submit-row button {
            border-radius: 10px;
            padding: 0 12px;
            font-size: 0.84rem;
            font-weight: 600;
            min-width: 0;
            width: 136px;
            height: var(--search-control-height);
            min-height: var(--search-control-height);
            max-height: var(--search-control-height);
            white-space: nowrap;
        }

        .search-form.is-accommodation .search-submit-row,
        .search-form.is-accommodation .search-submit-row button {
            width: 100%;
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
            min-height: 286px;
            grid-template-rows: 164px auto;
        }

        .item-card-media {
            position: relative;
            width: 100%;
            height: 164px;
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
            gap: 6px;
            align-content: start;
            background: #fbfdff;
        }

        .item-card-city {
            color: #698094;
            font-size: 0.7rem;
            line-height: 1;
            text-transform: none;
        }

        .item-card-title {
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

        .item-card-stars {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            color: #f3a337;
            font-size: 0.72rem;
            min-height: 14px;
        }

        .item-card-review {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #587085;
            font-size: 0.73rem;
        }

        .item-card-rating-badge {
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

        .item-card-price {
            margin-top: 2px;
            color: #0d2e44;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .item-card-price span {
            color: #6a8094;
            font-size: 0.74rem;
            font-weight: 500;
            margin-left: 2px;
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
                margin: 0 auto 30px;
            }

            .page-body-split {
                display: block;
                padding-left: 242px;
            }

            .floating-sidebar {
                width: 230px;
            }

            .sidebar-brand {
                opacity: 1;
                transform: none;
                pointer-events: auto;
            }

            .header-bar {
                border-bottom-color: #d8e3ec;
            }

            .header-main {
                flex-wrap: wrap;
                flex: 1 1 auto;
            }

            .header-search-mini {
                width: 100%;
                order: 3;
            }

            .header-links {
                order: 4;
                flex-basis: 100%;
                justify-content: flex-start;
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

            .search-inline-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px 14px;
                overflow: visible;
            }

            .search-form.is-accommodation .search-inline-row {
                grid-template-columns: 1fr;
            }

            .search-field-shell,
            .search-submit-row {
                grid-column: auto;
            }

            .search-form:not(.is-accommodation) .search-submit-row {
                width: 100%;
            }

            .search-form:not(.is-accommodation) .search-submit-row button {
                width: 100%;
            }

            .search-field-shell {
                min-width: 0;
                padding: 0;
                border-bottom: 0;
            }

            .search-primary-field {
                grid-template-columns: 130px minmax(0, 1fr);
            }

            .search-form.is-accommodation .search-primary-field {
                grid-template-columns: minmax(0, 1fr);
            }

            .search-dynamic-fields {
                grid-template-columns: repeat(6, minmax(0, 1fr));
                padding: 0;
            }

            .search-dynamic-fields.is-active {
                display: grid;
                gap: 10px;
            }

            #accommodationFields.is-active {
                grid-template-columns: repeat(3, minmax(0, 1fr));
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

            .search-category-tabs {
                position: static;
                top: auto;
                flex-wrap: nowrap;
                gap: 6px;
                overflow-x: auto;
                overflow-y: hidden;
                scrollbar-width: thin;
                -webkit-overflow-scrolling: touch;
            }

            .search-category-tabs::-webkit-scrollbar {
                height: 4px;
            }

            .search-category-tabs::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.35);
                border-radius: 999px;
            }

            .search-category-tab {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 680px) {
            .page {
                width: calc(100% - 18px);
                margin: 10px auto 22px;
            }

            .page-body-split {
                padding-left: 0;
            }

            .floating-sidebar {
                display: none;
            }

            .page.is-header-hidden .header-bar {
                transform: none;
                opacity: 1;
                pointer-events: auto;
            }

            .mobile-category-nav {
                display: block;
            }

            .header-bar {
                flex-direction: column;
                align-items: stretch;
                position: static;
                width: 100%;
                margin-left: 0;
                margin-right: 0;
                padding: 10px 12px;
                border: 1px solid #d8e3ec;
                border-radius: 14px;
            }

            .customer-auth {
                width: 100%;
                justify-content: flex-start;
            }

            .header-main {
                width: 100%;
                gap: 10px;
            }

            .header-brand-wrap {
                padding-left: 0;
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

            .search-form:not(.is-accommodation) .search-submit-row {
                width: 100%;
            }

            .search-form:not(.is-accommodation) .search-submit-row button {
                width: 100%;
            }

            .search-inline-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px;
                overflow: visible;
            }

            .search-section-full-width {
                overflow: visible;
                padding: 16px 14px 16px;
                min-height: 0;
            }

            .search-section-full-width::before,
            .search-section-full-width::after {
                inset: 0 auto 0 calc(50% - 50vw + 8px);
                width: calc(100vw - 16px);
            }

            .search-field-shell {
                min-width: 0;
            }

            .search-category-tabs {
                margin: 18px 0 12px;
                width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                overflow-y: hidden;
                flex-wrap: nowrap;
                gap: 6px;
                padding: 8px;
            }

            .search-category-tab {
                padding: 7px 10px;
                font-size: 0.74rem;
            }

            .search-category-tab i {
                font-size: 0.72rem;
            }

            .search-shell {
                padding: 12px;
                border-radius: 16px;
            }

            .search-dynamic-fields {
                grid-template-columns: 1fr;
                padding: 0;
            }

            .search-dynamic-fields.is-active {
                display: grid;
                gap: 8px;
            }

            #accommodationFields.is-active {
                grid-template-columns: 1fr;
            }

            .guest-popover {
                position: static;
                min-width: 0;
                margin-top: 8px;
                box-shadow: none;
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
        $homeHeroBackgroundUrl = trim((string) ($homeHeroBackgroundUrl ?? ''));
        $homeTopCategoryLinks = $homeTopCategoryLinks ?? collect();
        $homeCatalogCategoryLinks = $homeTopCategoryLinks
            ->filter(function ($link) {
                $url = (string) ($link['url'] ?? '');
                return preg_match('#^/catalog/#', $url) === 1;
            })
            ->values();
        $homeDefaultCategoryUrl = '/catalog/accommodation';
        $firstCategoryLink = $homeTopCategoryLinks->first(function ($link) {
            $url = (string) ($link['url'] ?? '');
            return preg_match('#/catalog/([a-z_-]+)#', $url) === 1;
        });
        if (is_array($firstCategoryLink) && trim((string) ($firstCategoryLink['url'] ?? '')) !== '') {
            $homeDefaultCategoryUrl = (string) $firstCategoryLink['url'];
        }
        $homeDefaultCategoryKey = preg_match('#/catalog/([a-z_-]+)#', $homeDefaultCategoryUrl, $categoryMatch)
            ? (string) ($categoryMatch[1] ?? 'accommodation')
            : 'accommodation';
        $homePromoBanner = $homePromoBanner ?? ['message' => 'Promotions coming soon.', 'url' => '/catalog/accommodation', 'cta' => 'View Promotions'];
        $homeTrendingChips = $homeTrendingChips ?? collect();
        $homeBrowseCards = $homeBrowseCards ?? collect();
        $homeTrendingCards = $homeTrendingCards ?? collect();
        $homeWeekendDealCards = $homeWeekendDealCards ?? collect();
        $homeLovedCards = $homeLovedCards ?? collect();
        $cardSvgFallback = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22520%22 viewBox=%220 0 900 520%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22520%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2234%22%3EImage unavailable%3C/text%3E%3C/svg%3E';
    @endphp

    <main class="page" data-api-base="{{ $apiBase }}">
        <header class="header-bar" aria-label="Member account actions">
            <div class="header-main">
                <div class="header-brand-wrap">
                    <div>
                        <a class="header-brand header-brand-link" href="/">Workation</a>
                        <p class="header-subline">Maldives Travel Market</p>
                    </div>
                </div>
                <div class="header-search-mini" aria-label="Quick destination search">
                    <input type="search" placeholder="Destinations, islands, hotels, and experiences">
                    <button type="button" aria-label="Search destinations"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </div>
            <div class="customer-auth">
                @if ($customerLoggedIn)
                    <a class="header-link" href="/customer#bookings">My bookings</a>
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

        <div class="page-body-split">
            <aside class="floating-sidebar sidebar-shell" aria-label="Category sidebar">
                <div class="sidebar-brand" aria-label="Sidebar workation logo">
                    <a class="sidebar-brand-title" href="/">Workation</a>
                    <p class="sidebar-brand-subline">Maldives Travel Market</p>
                </div>
                <p class="sidebar-title">Browse Categories</p>
                <section class="top-links" aria-label="Top categories">
                    @foreach ($homeCatalogCategoryLinks as $link)
                        @php
                            $linkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                            $categoryKeyFromUrl = preg_match('#/catalog/([a-z_-]+)#', $linkUrl, $categoryMatch) ? (string) ($categoryMatch[1] ?? '') : '';
                        @endphp
                        <a class="top-link floating-link" data-category-key="{{ $categoryKeyFromUrl }}" href="{{ $linkUrl }}"><span class="top-link-head"><i class="{{ $link['icon'] ?? 'fa-solid fa-location-dot' }}"></i>{{ $link['title'] ?? 'Category' }}</span><span>{{ $link['subtitle'] ?? '' }}</span></a>
                    @endforeach
                </section>
            </aside>

            <div class="page-main-content">
                <details class="mobile-category-nav" aria-label="Mobile category quick links">
                    <summary class="mobile-category-toggle">Browse Categories</summary>
                    <div class="mobile-category-row">
                        @foreach ($homeCatalogCategoryLinks as $link)
                            @php
                                $mobileLinkUrl = (string) ($link['url'] ?? '/catalog/accommodation');
                            @endphp
                            <a class="mobile-category-link" href="{{ $mobileLinkUrl }}"><i class="{{ $link['icon'] ?? 'fa-solid fa-location-dot' }}" aria-hidden="true"></i><span>{{ $link['title'] ?? 'Category' }}</span></a>
                        @endforeach
                    </div>
                </details>

                <div class="search-section-full-width" aria-label="Smart category search" @if ($homeHeroBackgroundUrl !== '') style="--home-hero-image: url('{{ $homeHeroBackgroundUrl }}');" @endif>
                    <p class="search-eyebrow">Plan Your Dream Maldives Escape</p>
                    <h1 class="search-title">Search stays, transfers, and island experiences with a travel-first booking flow.</h1>
                    <div class="search-support-strip" aria-label="Trust signals">
                        <span class="search-support-item"><i class="fa-solid fa-shield-heart"></i>Secure payment</span>
                        <span class="search-support-item"><i class="fa-solid fa-headset"></i>Fast customer support</span>
                        <span class="search-support-item"><i class="fa-solid fa-bolt"></i>Instant category search</span>
                    </div>
                    <div class="search-shell">
                        <form id="homeCatalogSearchForm" class="search-form" action="{{ '/catalog/' . $homeDefaultCategoryKey }}" method="get">
                            <input id="categorySelect" name="category" type="hidden" value="{{ $homeDefaultCategoryKey }}">
                            
                            <div class="search-category-tabs" aria-label="Travel search categories">
                                @foreach ($homeCatalogCategoryLinks as $index => $link)
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

                            <div class="search-inline-row">

                            <div class="search-field-shell">
                                <div class="search-primary-field">
                                    <label for="homeSearchDestination">Destination</label>
                                    <input id="homeSearchDestination" type="search" name="q" placeholder="City, airport, island, landmark, hotel, or service name" aria-label="Search query">
                                </div>
                            </div>

                            <!-- Accommodation Fields -->
                            <div id="accommodationFields" class="search-dynamic-fields is-active" data-fields-for="accommodation" aria-hidden="false">
                                <div class="field"><label for="checkin">Check-in</label><input id="checkin" name="checkin" type="date"></div>
                                <div class="field"><label for="checkout">Check-out</label><input id="checkout" name="checkout" type="date"></div>
                                <div class="field guest-picker" data-guest-picker>
                                    <label for="guestSummary">Rooms and guests</label>
                                    <button id="guestSummary" class="guest-summary-btn" type="button" aria-haspopup="dialog" aria-expanded="false">
                                        <span data-guest-summary-text>1 room, 2 adults, 0 children</span>
                                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="guest-popover" data-guest-popover hidden>
                                        <div class="guest-row">
                                            <span class="guest-label">Rooms</span>
                                            <div class="guest-counter">
                                                <button type="button" data-counter-action="decrement" data-counter-target="rooms">-</button>
                                                <input id="rooms" name="rooms" type="number" min="1" value="1">
                                                <button type="button" data-counter-action="increment" data-counter-target="rooms">+</button>
                                            </div>
                                        </div>
                                        <div class="guest-row">
                                            <span class="guest-label">Adults</span>
                                            <div class="guest-counter">
                                                <button type="button" data-counter-action="decrement" data-counter-target="adults">-</button>
                                                <input id="adults" name="adults" type="number" min="1" value="2">
                                                <button type="button" data-counter-action="increment" data-counter-target="adults">+</button>
                                            </div>
                                        </div>
                                        <div class="guest-row">
                                            <span class="guest-label">Children</span>
                                            <div class="guest-counter">
                                                <button type="button" data-counter-action="decrement" data-counter-target="children">-</button>
                                                <input id="children" name="children" type="number" min="0" value="0">
                                                <button type="button" data-counter-action="increment" data-counter-target="children">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Marine Transport Fields -->
                            <div id="marineTransportFields" class="search-dynamic-fields" data-fields-for="marine-transport" hidden aria-hidden="true">
                                <div class="field"><label for="marineTripType">Trip Type</label><select id="marineTripType" name="trip_type"><option value="one_way">One Way</option><option value="round_trip">Round Trip</option></select></div>
                                <div class="field"><label for="marineFrom">From Island</label><input id="marineFrom" name="from" type="text" placeholder="Departure island"></div>
                                <div class="field"><label for="marineTo">To Island</label><input id="marineTo" name="to" type="text" placeholder="Arrival island"></div>
                                <div class="field"><label for="marineDeparture">Departure</label><input id="marineDeparture" name="travel_date" type="date"></div>
                                <div class="field"><label for="marineReturn">Return</label><input id="marineReturn" name="return_date" type="date"></div>
                                <div class="field"><label for="marineAdults">Adults</label><input id="marineAdults" name="adults" type="number" min="1" value="2"></div>
                            </div>

                            <!-- Land Transport Fields -->
                            <div id="landTransportFields" class="search-dynamic-fields" data-fields-for="land-transport" hidden aria-hidden="true">
                                <div class="field"><label for="landTripType">Trip Type</label><select id="landTripType" name="trip_type"><option value="one_way">One Way</option><option value="round_trip">Round Trip</option></select></div>
                                <div class="field"><label for="landFrom">From</label><input id="landFrom" name="from" type="text" placeholder="Pickup location"></div>
                                <div class="field"><label for="landTo">To</label><input id="landTo" name="to" type="text" placeholder="Dropoff location"></div>
                                <div class="field"><label for="landDeparture">Pickup Date</label><input id="landDeparture" name="travel_date" type="date"></div>
                                <div class="field"><label for="landReturn">Return Date</label><input id="landReturn" name="return_date" type="date"></div>
                                <div class="field"><label for="landAdults">Adults</label><input id="landAdults" name="adults" type="number" min="1" value="2"></div>
                                <div class="field"><label for="vehicleType">Vehicle</label><input id="vehicleType" name="vehicle_type" type="text" placeholder="Car, Van, Bike"></div>
                            </div>

                            <!-- Excursion Fields -->
                            <div id="excursionFields" class="search-dynamic-fields" data-fields-for="excursion" hidden aria-hidden="true">
                                <div class="field"><label for="excursionDate">Date</label><input id="excursionDate" name="date" type="date"></div>
                                <div class="field"><label for="excursionParticipants">Participants</label><input id="excursionParticipants" name="participants" type="number" min="1" value="2"></div>
                                <div class="field"><label for="activityType">Activity Type</label><select id="activityType" name="activity_type"><option value="">All Types</option><option value="water">Water Sports</option><option value="land">Land Tours</option><option value="cultural">Cultural</option></select></div>
                                <div class="field"><label for="excPrice">Max Price</label><input id="excPrice" name="max_price" type="number" min="0" placeholder="5000"></div>
                            </div>

                            <!-- Remote Workspace Fields -->
                            <div id="remoteWorkspaceFields" class="search-dynamic-fields" data-fields-for="remote_workspace" hidden aria-hidden="true">
                                <div class="field"><label for="workCheckIn">Check-in</label><input id="workCheckIn" name="checkin" type="date"></div>
                                <div class="field"><label for="workCheckOut">Check-out</label><input id="workCheckOut" name="checkout" type="date"></div>
                                <div class="field"><label for="workSpaces">Workspaces Needed</label><input id="workSpaces" name="workspaces" type="number" min="1" value="1"></div>
                                <div class="field"><label for="workType">Workspace Type</label><select id="workType" name="workspace_type"><option value="">All Types</option><option value="desk">Dedicated Desk</option><option value="office">Private Office</option><option value="villa">Villa Office</option></select></div>
                            </div>

                            <!-- Conference Room Fields -->
                            <div id="conferenceRoomFields" class="search-dynamic-fields" data-fields-for="conference_room" hidden aria-hidden="true">
                                <div class="field"><label for="confCheckIn">Event Date</label><input id="confCheckIn" name="date" type="date"></div>
                                <div class="field"><label for="confDuration">Duration (Hours)</label><input id="confDuration" name="duration" type="number" min="1" value="8"></div>
                                <div class="field"><label for="confAttendees">Attendees</label><input id="confAttendees" name="attendees" type="number" min="1" value="20"></div>
                                <div class="field"><label for="confType">Room Type</label><select id="confType" name="room_type"><option value="">All Types</option><option value="meeting">Meeting</option><option value="boardroom">Boardroom</option><option value="ballroom">Ballroom</option></select></div>
                            </div>

                            <!-- Resort Day Visit Fields -->
                            <div id="resortDayVisitFields" class="search-dynamic-fields" data-fields-for="resort_day_visit" hidden aria-hidden="true">
                                <div class="field"><label for="resortDate">Visit Date</label><input id="resortDate" name="date" type="date"></div>
                                <div class="field"><label for="resortGuests">Guests</label><input id="resortGuests" name="guests" type="number" min="1" value="2"></div>
                                <div class="field"><label for="resortStartTime">Arrival Time</label><input id="resortStartTime" name="start_time" type="time"></div>
                                <div class="field"><label for="resortPackage">Package</label><select id="resortPackage" name="package"><option value="">All Packages</option><option value="beach">Beach</option><option value="water">Water Sports</option><option value="spa">Spa & Wellness</option></select></div>
                            </div>

                            <!-- Restaurant Fields -->
                            <div id="restaurantFields" class="search-dynamic-fields" data-fields-for="restaurant" hidden aria-hidden="true">
                                <div class="field"><label for="restDate">Date</label><input id="restDate" name="date" type="date"></div>
                                <div class="field"><label for="restTime">Time</label><input id="restTime" name="time" type="time"></div>
                                <div class="field"><label for="restGuests">Guests</label><input id="restGuests" name="guests" type="number" min="1" value="2"></div>
                                <div class="field"><label for="cuisine">Cuisine Type</label><select id="cuisine" name="cuisine"><option value="">All Cuisines</option><option value="maldivian">Maldivian</option><option value="asian">Asian</option><option value="seafood">Seafood</option><option value="international">International</option></select></div>
                            </div>

                            <!-- Vehicle Rental Fields -->
                            <div id="vehicleRentalFields" class="search-dynamic-fields" data-fields-for="vehicle_rental" hidden aria-hidden="true">
                                <div class="field"><label for="rentalPickup">Pickup Date</label><input id="rentalPickup" name="pickup_date" type="date"></div>
                                <div class="field"><label for="rentalReturn">Return Date</label><input id="rentalReturn" name="return_date" type="date"></div>
                                <div class="field"><label for="rentalVehicleType">Vehicle Type</label><select id="rentalVehicleType" name="vehicle_type"><option value="">All Types</option><option value="car">Car</option><option value="suv">SUV</option><option value="van">Van</option><option value="bike">Bike</option></select></div>
                                <div class="field"><label for="age">Driver Age</label><input id="age" name="driver_age" type="number" min="18" value="30"></div>
                            </div>

                            <div class="search-submit-row">
                                <button class="primary" type="submit"><i class="fa-solid fa-magnifying-glass" style="margin-right:8px;"></i>Search</button>
                            </div>

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
                        if (str_starts_with($fallbackImage, 'http://')) {
                            $fallbackImage = 'https://' . ltrim(substr($fallbackImage, 7), '/');
                        }
                        $primaryImage = trim((string) ($card['image_url'] ?? ''));
                        if (str_starts_with($primaryImage, 'http://')) {
                            $primaryImage = 'https://' . ltrim(substr($primaryImage, 7), '/');
                        }
                        $resolvedImage = $primaryImage !== '' ? $primaryImage : ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback);
                        $cityName = trim((string) ($card['city'] ?? $card['location'] ?? $card['island'] ?? ''));
                        if ($cityName === '') {
                            $cityName = trim((string) ($card['subtitle'] ?? ''));
                        }
                        $starRank = max(0, min(5, (int) round((float) ($card['star_rating'] ?? $card['stars'] ?? 0))));
                        $reviewScoreRaw = (float) ($card['review_score'] ?? $card['rating'] ?? 0);
                        $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                        $reviewCount = (int) ($card['review_count'] ?? $card['reviews_count'] ?? 0);
                        $priceLabel = trim((string) ($card['price_label'] ?? $card['price'] ?? $card['meta'] ?? 'See details'));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Category' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <span class="item-card-city">{{ $cityName !== '' ? $cityName : 'Maldives' }}</span>
                            <h3 class="item-card-title">{{ $card['title'] ?? 'Category' }}</h3>
                            <div class="item-card-stars" aria-label="Star ranking">
                                @if ($starRank > 0)
                                    @for ($i = 0; $i < $starRank; $i++)
                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    @endfor
                                @endif
                            </div>
                            <div class="item-card-review">
                                <span class="item-card-rating-badge">{{ $reviewScore }}</span>
                                <span>{{ number_format($reviewCount) }} reviews</span>
                            </div>
                            <div class="item-card-price">From {{ $priceLabel }}</div>
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
                        if (str_starts_with($fallbackImage, 'http://')) {
                            $fallbackImage = 'https://' . ltrim(substr($fallbackImage, 7), '/');
                        }
                        $primaryImage = trim((string) ($card['image_url'] ?? ''));
                        if (str_starts_with($primaryImage, 'http://')) {
                            $primaryImage = 'https://' . ltrim(substr($primaryImage, 7), '/');
                        }
                        $resolvedImage = $primaryImage !== '' ? $primaryImage : ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback);
                        $cityName = trim((string) ($card['city'] ?? $card['location'] ?? $card['island'] ?? ''));
                        if ($cityName === '') {
                            $cityName = trim((string) ($card['subtitle'] ?? ''));
                        }
                        $starRank = max(0, min(5, (int) round((float) ($card['star_rating'] ?? $card['stars'] ?? 0))));
                        $reviewScoreRaw = (float) ($card['review_score'] ?? $card['rating'] ?? 0);
                        $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                        $reviewCount = (int) ($card['review_count'] ?? $card['reviews_count'] ?? 0);
                        $priceLabel = trim((string) ($card['price_label'] ?? $card['price'] ?? $card['meta'] ?? 'See details'));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Trending Destination' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <span class="item-card-city">{{ $cityName !== '' ? $cityName : 'Maldives' }}</span>
                            <h3 class="item-card-title">{{ $card['title'] ?? 'Trending Destination' }}</h3>
                            <div class="item-card-stars" aria-label="Star ranking">
                                @if ($starRank > 0)
                                    @for ($i = 0; $i < $starRank; $i++)
                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    @endfor
                                @endif
                            </div>
                            <div class="item-card-review">
                                <span class="item-card-rating-badge">{{ $reviewScore }}</span>
                                <span>{{ number_format($reviewCount) }} reviews</span>
                            </div>
                            <div class="item-card-price">From {{ $priceLabel }}</div>
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
                        if (str_starts_with($fallbackImage, 'http://')) {
                            $fallbackImage = 'https://' . ltrim(substr($fallbackImage, 7), '/');
                        }
                        $primaryImage = trim((string) ($card['image_url'] ?? ''));
                        if (str_starts_with($primaryImage, 'http://')) {
                            $primaryImage = 'https://' . ltrim(substr($primaryImage, 7), '/');
                        }
                        $resolvedImage = $primaryImage !== '' ? $primaryImage : ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback);
                        $cityName = trim((string) ($card['city'] ?? $card['location'] ?? $card['island'] ?? ''));
                        if ($cityName === '') {
                            $cityName = trim((string) ($card['subtitle'] ?? ''));
                        }
                        $starRank = max(0, min(5, (int) round((float) ($card['star_rating'] ?? $card['stars'] ?? 0))));
                        $reviewScoreRaw = (float) ($card['review_score'] ?? $card['rating'] ?? 0);
                        $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                        $reviewCount = (int) ($card['review_count'] ?? $card['reviews_count'] ?? 0);
                        $priceLabel = trim((string) ($card['price_label'] ?? $card['price'] ?? $card['meta'] ?? 'See details'));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Weekend Deal' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <span class="item-card-city">{{ $cityName !== '' ? $cityName : 'Maldives' }}</span>
                            <h3 class="item-card-title">{{ $card['title'] ?? 'Weekend Deal' }}</h3>
                            <div class="item-card-stars" aria-label="Star ranking">
                                @if ($starRank > 0)
                                    @for ($i = 0; $i < $starRank; $i++)
                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    @endfor
                                @endif
                            </div>
                            <div class="item-card-review">
                                <span class="item-card-rating-badge">{{ $reviewScore }}</span>
                                <span>{{ number_format($reviewCount) }} reviews</span>
                            </div>
                            <div class="item-card-price">From {{ $priceLabel }}</div>
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
                        if (str_starts_with($fallbackImage, 'http://')) {
                            $fallbackImage = 'https://' . ltrim(substr($fallbackImage, 7), '/');
                        }
                        $primaryImage = trim((string) ($card['image_url'] ?? ''));
                        if (str_starts_with($primaryImage, 'http://')) {
                            $primaryImage = 'https://' . ltrim(substr($primaryImage, 7), '/');
                        }
                        $resolvedImage = $primaryImage !== '' ? $primaryImage : ($fallbackImage !== '' ? $fallbackImage : $cardSvgFallback);
                        $cityName = trim((string) ($card['city'] ?? $card['location'] ?? $card['island'] ?? ''));
                        if ($cityName === '') {
                            $cityName = trim((string) ($card['subtitle'] ?? ''));
                        }
                        $starRank = max(0, min(5, (int) round((float) ($card['star_rating'] ?? $card['stars'] ?? 0))));
                        $reviewScoreRaw = (float) ($card['review_score'] ?? $card['rating'] ?? 0);
                        $reviewScore = $reviewScoreRaw > 0 ? number_format($reviewScoreRaw, 1) : 'N/A';
                        $reviewCount = (int) ($card['review_count'] ?? $card['reviews_count'] ?? 0);
                        $priceLabel = trim((string) ($card['price_label'] ?? $card['price'] ?? $card['meta'] ?? 'See details'));
                    @endphp
                    <a class="item-card" href="{{ $card['url'] ?? '/customer' }}">
                        <div class="item-card-media">
                            <img src="{{ $resolvedImage }}" onerror="if(!this.dataset.fb && '{{ $fallbackImage }}' !== '' && this.src !== '{{ $fallbackImage }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $fallbackImage }}';}else{this.onerror=null;this.src='{{ $cardSvgFallback }}';};" alt="{{ $card['title'] ?? 'Loved Place' }} thumbnail" loading="lazy">
                        </div>
                        <div class="item-card-body">
                            <span class="item-card-city">{{ $cityName !== '' ? $cityName : 'Maldives' }}</span>
                            <h3 class="item-card-title">{{ $card['title'] ?? 'Loved Place' }}</h3>
                            <div class="item-card-stars" aria-label="Star ranking">
                                @if ($starRank > 0)
                                    @for ($i = 0; $i < $starRank; $i++)
                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    @endfor
                                @endif
                            </div>
                            <div class="item-card-review">
                                <span class="item-card-rating-badge">{{ $reviewScore }}</span>
                                <span>{{ number_format($reviewCount) }} reviews</span>
                            </div>
                            <div class="item-card-price">From {{ $priceLabel }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="home-footer-skin">
            @include('partials.global-site-footer')
        </div>
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

            function syncSidebarBrandReveal() {
                const revealThreshold = Math.max(56, header.offsetHeight - 4);
                const currentY = window.scrollY || 0;
                const isDesktop = window.matchMedia('(min-width: 1041px)').matches;
                const isScrollingDown = currentY > lastScrollY;
                const shouldHideHeader = isDesktop && currentY > revealThreshold && isScrollingDown;

                page.classList.toggle('is-header-hidden', shouldHideHeader);

                lastScrollY = currentY;
            }

            window.addEventListener('scroll', syncSidebarBrandReveal, { passive: true });
            window.addEventListener('resize', syncSidebarBrandReveal);
            syncSidebarBrandReveal();
        })();
    </script>

    <script>
        (function () {
            const form = document.getElementById('homeCatalogSearchForm');
            const categorySelect = document.getElementById('categorySelect');
            const categoryTabs = Array.from(document.querySelectorAll('[data-home-category-tab]'));
            
            // Map all individual field containers
            const fieldSets = {
                'accommodation': document.getElementById('accommodationFields'),
                'marine-transport': document.getElementById('marineTransportFields'),
                'land-transport': document.getElementById('landTransportFields'),
                'excursion': document.getElementById('excursionFields'),
                'remote_workspace': document.getElementById('remoteWorkspaceFields'),
                'conference_room': document.getElementById('conferenceRoomFields'),
                'resort_day_visit': document.getElementById('resortDayVisitFields'),
                'restaurant': document.getElementById('restaurantFields'),
                'vehicle_rental': document.getElementById('vehicleRentalFields')
            };

            if (!form || !categorySelect) {
                return;
            }

            function normalizeCategoryKey(category) {
                return String(category || '').toLowerCase().replace(/-/g, '_').trim();
            }

            function toggleFields() {
                const category = String(categorySelect.value || 'accommodation').toLowerCase();
                const normalizedCategory = normalizeCategoryKey(category);
                const isAccommodation = normalizedCategory === 'accommodation';
                
                // Update form action
                const displayCategory = normalizedCategory.replace(/_/g, '-');
                form.setAttribute('action', '/catalog/' + displayCategory);
                form.classList.toggle('is-accommodation', isAccommodation);

                // Hide all field sets and disable their inputs
                Object.keys(fieldSets).forEach(function (key) {
                    const el = fieldSets[key];
                    if (!el) return;
                    
                    const isActive = key === normalizedCategory;
                    el.hidden = !isActive;
                    el.classList.toggle('is-active', isActive);
                    el.setAttribute('aria-hidden', isActive ? 'false' : 'true');

                    el.querySelectorAll('input, select, textarea').forEach(function (control) {
                        control.disabled = !isActive;
                    });
                });

                // Update active tab styling
                categoryTabs.forEach(function (tab) {
                    const tabCategory = normalizeCategoryKey(tab.getAttribute('data-home-category-tab') || '');
                    tab.classList.toggle('is-active', tabCategory === normalizedCategory);
                });
            }

            function initGuestPicker() {
                const picker = document.querySelector('[data-guest-picker]');
                if (!picker) {
                    return;
                }

                const summaryButton = picker.querySelector('.guest-summary-btn');
                const summaryText = picker.querySelector('[data-guest-summary-text]');
                const popover = picker.querySelector('[data-guest-popover]');
                const roomsInput = picker.querySelector('#rooms');
                const adultsInput = picker.querySelector('#adults');
                const childrenInput = picker.querySelector('#children');

                if (!summaryButton || !summaryText || !popover || !roomsInput || !adultsInput || !childrenInput) {
                    return;
                }

                function updateSummary() {
                    const rooms = Number(roomsInput.value || 1);
                    const adults = Number(adultsInput.value || 2);
                    const children = Number(childrenInput.value || 0);
                    summaryText.textContent = rooms + ' room' + (rooms === 1 ? '' : 's') + ', ' + adults + ' adult' + (adults === 1 ? '' : 's') + ', ' + children + ' children';
                }

                function normalizeInputValue(input) {
                    const min = Number(input.getAttribute('min') || 0);
                    const raw = String(input.value || '').trim();
                    const parsed = Number(raw);
                    const safeValue = Number.isFinite(parsed) ? Math.max(min, Math.floor(parsed)) : min;
                    input.value = String(safeValue);
                }

                function setPopoverOpen(isOpen) {
                    popover.hidden = !isOpen;
                    summaryButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }

                function updateCounterValue(button) {
                    const action = String(button.getAttribute('data-counter-action') || '');
                    const target = String(button.getAttribute('data-counter-target') || '');
                    const input = picker.querySelector('#' + target);
                    if (!input) {
                        return;
                    }

                    const min = Number(input.getAttribute('min') || 0);
                    const current = Number(input.value || min);
                    const next = action === 'increment' ? current + 1 : Math.max(min, current - 1);
                    input.value = String(next);
                    updateSummary();
                }

                picker.querySelectorAll('[data-counter-action]').forEach(function (btn) {
                    function handleCounterActivate(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        updateCounterValue(btn);
                    }

                    btn.addEventListener('click', handleCounterActivate);
                    btn.addEventListener('touchstart', handleCounterActivate, { passive: false });
                    btn.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            handleCounterActivate(event);
                        }
                    });
                });

                popover.addEventListener('click', function (event) {
                    const button = event.target.closest('[data-counter-action]');
                    if (!button || !popover.contains(button)) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    updateCounterValue(button);
                });

                [roomsInput, adultsInput, childrenInput].forEach(function (input) {
                    input.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });

                    input.addEventListener('focus', function () {
                        setPopoverOpen(true);
                    });

                    input.addEventListener('input', updateSummary);

                    input.addEventListener('change', function () {
                        normalizeInputValue(input);
                        updateSummary();
                    });

                    input.addEventListener('blur', function () {
                        normalizeInputValue(input);
                        updateSummary();
                    });
                });

                summaryButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setPopoverOpen(popover.hidden);
                });

                document.addEventListener('click', function (event) {
                    if (!picker.contains(event.target)) {
                        setPopoverOpen(false);
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        setPopoverOpen(false);
                    }
                });

                updateSummary();
            }

            // Attach category tab click handlers
            categoryTabs.forEach(function (tab) {
                tab.addEventListener('click', function (event) {
                    event.preventDefault();
                    const categoryKey = String(tab.getAttribute('data-home-category-tab') || '').toLowerCase();
                    if (!categoryKey) {
                        return;
                    }

                    categorySelect.value = categoryKey;
                    toggleFields();
                });
            });

            // Initialize on load
            toggleFields();
            initGuestPicker();
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
</body>
</html>