<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Liveaboard') }} | Workation</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --brand: #0f6179;
            --brand-soft: #edf6fc;
            --brand-soft-2: #f7fbff;
            --brand-line: #d4e5ef;
            --brand-ink: #1f4f6b;
            --brand-shadow: rgba(15, 97, 121, 0.12);
            --accent: #f3a337;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        :root {
            --property-header-offset: 74px;
            --property-search-shell-height: 74px;
        }

        body.is-header-hidden {
            --property-header-offset: 0px;
        }

        .page { width: min(1180px, calc(100% - 24px)); margin: 14px auto 28px; }

        .top-search-shell {
            position: sticky;
            top: var(--property-header-offset);
            z-index: 60;
            border: 1px solid #d4e5ef;
            border-radius: 0;
            background: #ffffff;
            padding: 10px;
            box-shadow: none;
            margin-bottom: 0;
            width: 100%;
        }

        body.is-header-hidden .top-search-shell {
            top: var(--property-header-offset);
        }

        .top-search-inner {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto;
        }

        .top-search-form {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(140px, 1fr)) auto;
            gap: 8px;
            align-items: center;
        }

        .top-search-field {
            border: 1px solid #c6d7e4;
            border-radius: 8px;
            padding: 8px 10px;
            background: #fbfdff;
            color: #17344a;
            display: grid;
            gap: 2px;
        }

        .top-search-field label {
            font-size: 0.68rem;
            color: #5f7488;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .top-search-field input,
        .top-search-field select {
            border: 0;
            background: transparent;
            font: inherit;
            font-size: 0.88rem;
            color: #17344a;
            outline: none;
            padding: 0;
        }

        .top-search-btn {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 8px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        #property-gallery-section {
            padding: 12px;
            background: #ffffff;
            border: 1px solid #d4e5ef !important;
            border-radius: 14px;
            margin-top: 0;
        }

        #property-gallery-section > h2 {
            display: none;
        }

        #property-gallery-section .gallery-shell {
            margin-top: 0;
        }

        .section {
            border: none;
            border-top: 1px solid #f0f4f8;
            border-radius: 0;
            background: transparent;
            padding: 0;
            margin-top: 20px;
        }

        .section:first-of-type {
            border-top: none;
            margin-top: 0;
        }

        .section h2 {
            margin: 0;
            font-size: 1.04rem;
            padding-top: 20px;
            padding-bottom: 14px;
        }

        .section:first-of-type h2 {
            padding-top: 0;
        }

        #services-amenities-section,
        #stopovers-section,
        #cabins-section,
        #guest-reviews-section,
        #policies-section,
        #similar-liveaboards-section {
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: start;
        }

        #services-amenities-section h2,
        #stopovers-section h2,
        #cabins-section h2,
        #guest-reviews-section h2,
        #policies-section h2,
        #similar-liveaboards-section h2 {
            margin: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        .gallery-shell {
            margin-top: 14px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 260px;
            gap: 10px;
            align-items: start;
        }

        .gallery-banner-wrap {
            border-radius: 13px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #ffffff;
            min-height: 360px;
        }

        .gallery-banner {
            width: 100%;
            height: 100%;
            min-height: 360px;
            object-fit: cover;
            display: block;
        }

        .gallery-banner-placeholder {
            min-height: 360px;
            display: grid;
            place-items: center;
            color: #5d7487;
            font-size: 0.88rem;
            background: #f3f8fc;
        }

        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            max-height: 360px;
            overflow: auto;
            padding-right: 2px;
        }

        .property-summary-shell {
            margin-top: 12px;
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(220px, 0.7fr);
            gap: 14px;
            align-items: start;
        }

        .property-summary-main {
            display: grid;
            gap: 8px;
        }

        .property-summary-title {
            margin: 0;
            font-size: clamp(1.25rem, 2vw, 1.65rem);
            color: #1a3347;
        }

        .property-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #c6dded;
            background: #eef7ff;
            color: #1f4f6b;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .property-summary-address {
            color: #3a5568;
            font-size: 0.9rem;
        }

        .property-summary-address a {
            color: #0f6179;
            text-decoration: none;
            font-weight: 600;
        }

        .property-summary-address a:hover { text-decoration: underline; }

        .property-summary-reviews {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .summary-rating-chip {
            border-radius: 999px;
            background: #edf6fc;
            border: 1px solid #cde1ef;
            color: #214a64;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 10px;
        }

        .property-summary-price {
            border: 1px solid #d4e5ef;
            border-radius: 12px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 8px;
            justify-items: end;
            text-align: right;
        }

        .property-summary-price .k {
            color: #5f7488;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
        }

        .property-summary-price .v {
            color: #17344a;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .property-summary-price .sub {
            color: #4c6477;
            font-size: 0.76rem;
        }

        .property-summary-price .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #0f6179;
            background: #0f6179;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 700;
            padding: 9px 14px;
            min-width: 120px;
        }

        .property-summary-price .cta:hover { filter: brightness(1.04); }

        .gallery-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cfe1ec;
            background: #eff7fb;
            padding: 0;
            margin: 0;
            cursor: pointer;
            min-height: 78px;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            min-height: 78px;
            object-fit: cover;
            display: block;
        }

        .gallery-thumb.is-active {
            border-color: #1d848c;
            box-shadow: 0 0 0 2px rgba(29, 132, 140, 0.25);
        }

        .nearby-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 320px));
            justify-content: start;
            gap: 10px;
        }

        .nearby-card {
            border: 1px solid #d4e5ef;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: grid;
            grid-template-rows: 156px auto;
            min-width: 0;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .nearby-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(16, 52, 75, 0.12);
        }

        .nearby-card-media {
            width: 100%;
            height: 156px;
            object-fit: cover;
            background: #eaf2f8;
            display: block;
        }

        .nearby-card-body {
            padding: 10px;
            display: grid;
            gap: 6px;
        }

        .nearby-location {
            font-size: 0.72rem;
            color: #4a90a4;
            font-weight: 600;
        }

        .nearby-name {
            margin: 0;
            font-size: 0.9rem;
            color: #18384e;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .nearby-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 0.74rem;
            color: #5b7287;
        }

        .nearby-price {
            color: #17344a;
            font-weight: 700;
        }

        .nearby-empty {
            color: #5f7488;
            font-size: 0.9rem;
        }

        @media (max-width: 767px) {
            .nearby-grid { grid-template-columns: 1fr; }
            .nearby-card { grid-template-rows: 176px auto; }
            .nearby-card-media { height: 176px; }
            .nearby-card-body { padding: 12px; }
        }

        .amenities-board {
            margin-top: 10px;
            border: 1px solid #d7e6f0;
            border-radius: 14px;
            background: linear-gradient(160deg, #f7fbff 0%, #f1f8fe 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .amenities-head {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #173f57;
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.01em;
        }

        .amenities-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            align-items: start;
        }

        .amenity-group {
            border: 1px solid #d2e3ef;
            border-radius: 12px;
            background: #ffffff;
            padding: 11px;
            display: grid;
            gap: 9px;
            align-content: start;
            align-self: start;
            box-shadow: 0 5px 14px rgba(15, 68, 97, 0.06);
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        .amenity-group:hover {
            border-color: #b9d5e6;
            box-shadow: 0 8px 20px rgba(15, 68, 97, 0.1);
        }

        .amenity-group-title {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1f4f6b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding-bottom: 7px;
            border-bottom: 1px dashed #d6e6f2;
        }

        .amenity-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .amenity-list li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2f556e;
            font-size: 0.78rem;
            line-height: 1.25;
            border: 1px solid #d6e7f2;
            border-radius: 999px;
            background: #f7fbff;
            padding: 5px 9px;
            max-width: 100%;
        }

        .facility-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 16px;
            width: 16px;
            height: 16px;
            font-size: 0.72rem;
            color: #1d5a7a;
            line-height: 1;
        }

        .rooms-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .room-card {
            border: 1px solid #d6e5ef;
            border-radius: 14px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .room-title {
            margin: 0;
            font-size: 0.95rem;
            color: #1f4f6b;
            font-weight: 700;
        }

        .room-details {
            display: grid;
            gap: 6px;
            font-size: 0.8rem;
            color: #3a5568;
        }

        .detail-line {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #cabins-section .rooms-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .cabin-room-card {
            border: 1px solid #dbe7f0;
            border-radius: 13px;
            overflow: hidden;
            background: #fbfdff;
            display: grid;
            grid-template-columns: minmax(230px, 260px) minmax(0, 1fr);
            align-items: stretch;
            padding: 0;
        }

        .room-media-link {
            display: block;
            height: 100%;
        }

        .room-media {
            position: relative;
            background: linear-gradient(135deg, #d9ebf4 0%, #f0f7fc 100%);
            min-height: 220px;
        }

        .room-media img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .room-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 999px;
            background: rgba(14, 70, 92, 0.72);
            color: #f2fcff;
            font-size: 0.71rem;
            padding: 4px 8px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .room-tag i { margin-right: 5px; font-size: 0.7rem; }

        .room-body { padding: 12px; display: grid; gap: 10px; }
        .room-body h3 { margin: 0; font-size: 1rem; color: #153f59; }

        .room-name-link {
            color: #153f59;
            text-decoration: none;
        }

        .room-name-link:hover { text-decoration: underline; }

        .room-details-link-prominent {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 800;
            color: #0f6179;
            text-decoration: underline;
            text-underline-offset: 2px;
            letter-spacing: 0.01em;
        }

        .room-details-link-prominent:hover {
            color: #0b4f66;
        }

        .room-offer-table {
            border: 1px solid #d8e7f1;
            border-radius: 11px;
            overflow: hidden;
            background: #ffffff;
        }

        .room-offer-head,
        .room-offer-row {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) 88px minmax(190px, 0.9fr);
        }

        .room-offer-head {
            background: #f3f8fc;
            color: #274d65;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .room-offer-head span,
        .room-offer-row > div {
            padding: 9px 10px;
            border-right: 1px solid #e0ebf3;
        }

        .room-offer-head span:last-child,
        .room-offer-row > div:last-child { border-right: 0; }

        .room-offer-row {
            border-top: 1px solid #e0ebf3;
            align-items: center;
        }

        .room-offer-row.is-hidden { display: none; }

        .room-choices {
            display: grid;
            gap: 5px;
            color: #2c5169;
            font-size: 0.8rem;
        }

        .room-option-title {
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            color: #0f6179 !important;
        }

        .room-option-subtitle {
            font-size: 0.78rem !important;
            color: #5a7a8a !important;
        }

        .room-sleeps {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            color: #244960;
            font-size: 0.84rem;
            font-weight: 700;
            text-align: center;
        }

        .room-sleeps-icons {
            display: inline-flex;
            align-items: flex-end;
            gap: 2px;
            color: #1f6f95;
        }

        .room-sleeps-icons .fa-user { font-size: 0.84rem; }
        .room-sleeps-icons .room-sleeps-child { font-size: 0.7rem; opacity: 0.95; }

        .room-price-box {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .room-price-now {
            font-size: 1.35rem;
            font-weight: 800;
            color: #10344b;
            line-height: 1;
        }

        .room-price-summary {
            margin-top: 4px;
            color: #254b63;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .room-price-summary-note {
            margin-top: 1px;
            color: #4f6f85;
            font-size: 0.78rem;
            line-height: 1.3;
        }

        .reserve-btn {
            text-decoration: none;
            border: 1px solid #0f6179;
            border-radius: 8px;
            background: linear-gradient(135deg, #0f6179 0%, #1d848c 100%);
            color: #ecfcff;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 9px 15px;
            white-space: nowrap;
        }

        .room-side-details {
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .room-side-details li {
            list-style: none;
            display: flex;
            gap: 7px;
            color: #3d5f74;
            font-size: 0.8rem;
            align-items: center;
        }

        .room-side-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #3a82aa;
            flex: 0 0 7px;
        }

        .room-offer-expand {
            display: flex;
            justify-content: center;
            margin-top: 8px;
            padding-bottom: 8px;
        }

        .room-offer-expand-btn {
            padding: 6px 12px;
            font-size: 0.75rem;
            border: 1px solid #bdd8ea;
            border-radius: 999px;
            background: #f8fcff;
            color: #1d5a7a;
            font-weight: 700;
            cursor: pointer;
        }

        .room-details-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .room-details-modal-overlay.is-active { display: flex; }

        .room-details-modal {
            background: #ffffff;
            border-radius: 16px;
            width: min(1080px, calc(100% - 24px));
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .room-details-modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            z-index: 1001;
        }

        .room-details-content {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
            gap: 20px;
            padding: 24px;
            align-items: start;
        }

        .room-details-gallery {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-auto-rows: 156px;
            gap: 10px;
            align-content: start;
        }

        .room-details-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #d6e7f3;
            background: #f2f8fc;
        }

        .room-details-gallery img:first-child {
            grid-column: 1 / -1;
            grid-row: span 2;
        }

        .room-details-sidebar {
            display: grid;
            gap: 14px;
            align-content: start;
            max-height: 85vh;
            overflow-y: auto;
            padding-right: 12px;
        }

        .room-details-header {
            display: grid;
            gap: 10px;
            padding: 14px;
            border: 1px solid #d4e5ef;
            border-radius: 14px;
            background: #f7fbff;
        }

        .room-details-title {
            margin: 0;
            color: #153f59;
            font-size: 1.25rem;
        }

        .room-bed-info {
            margin-top: 6px;
            color: #3f6277;
            font-size: 0.9rem;
        }

        .room-details-description {
            margin: 12px 0 0;
            color: #3a5b70;
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .room-select-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 14px;
            text-decoration: none;
            border: 1px solid #0f6179;
            border-radius: 8px;
            background: linear-gradient(135deg, #0f6179 0%, #1d848c 100%);
            color: #ecfcff;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 10px 16px;
        }

        .review-card {
            border: 1px solid #d4e5ef;
            border-radius: 14px;
            background: #f8fcff;
            padding: 14px;
            margin-bottom: 12px;
        }

        .review-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            gap: 10px;
        }

        .review-author {
            font-weight: 700;
            color: #1f4f6b;
            font-size: 0.88rem;
        }

        .review-rating {
            color: #f3a337;
            font-size: 0.85rem;
        }

        .review-body {
            color: #39586d;
            font-size: 0.88rem;
            line-height: 1.5;
            margin: 0;
        }

        .section-tabs {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            border: 1px solid #d6e5ef;
            border-radius: 12px;
            background: #f8fcff;
            padding: 8px;
            position: sticky;
            top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            z-index: 58;
            backdrop-filter: blur(3px);
        }

        .section-tab {
            text-decoration: none;
            color: #2c4f66;
            border: 1px solid #d3e2ec;
            border-radius: 999px;
            background: #ffffff;
            padding: 6px 11px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .section-tab.is-active,
        .section-tab:hover {
            border-color: #0f6179;
            color: #0f6179;
            background: #eef7fc;
        }

        .policies-grid {
            display: grid;
            grid-template-columns: minmax(140px, 0.45fr) minmax(0, 1fr);
            gap: 10px 14px;
            align-items: start;
        }

        .policy-label {
            color: #35586e;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .policy-value {
            color: #3f6075;
            font-size: 0.86rem;
            line-height: 1.65;
        }

        .empty-state {
            border: 1px dashed #c6dde5;
            border-radius: 12px;
            background: #f5f9fb;
            padding: 16px;
            color: #4a677a;
            font-size: 0.9rem;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .gallery-shell { grid-template-columns: 1fr; }
            .property-summary-shell { grid-template-columns: 1fr; }
            .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }
            body.is-header-hidden .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }
        }

        @media (max-width: 768px) {
            .top-search-form { grid-template-columns: 1fr; }
            .gallery-thumbs { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .gallery-banner { min-height: 250px; }
            .gallery-banner-wrap { min-height: 250px; }
            .rooms-grid { grid-template-columns: 1fr; }
            .amenities-columns { grid-template-columns: 1fr; }
            .cabin-room-card { grid-template-columns: 1fr; }
            .room-offer-head,
            .room-offer-row { grid-template-columns: minmax(0, 1fr); }
            .room-offer-head span:nth-child(2),
            .room-offer-head span:nth-child(3) { display: none; }
            .room-offer-row > div { border-right: 0; border-top: 1px solid #e0ebf3; }
            .room-offer-row > div:first-child { border-top: 0; }
            .room-details-content { grid-template-columns: 1fr; }
            .room-details-gallery img { height: 140px; }
            .room-details-gallery img:first-child {
                grid-column: auto;
                grid-row: span 1;
            }
            .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
                overflow-x: auto;
                flex-wrap: nowrap;
            }
            body.is-header-hidden .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }
            .section-tab { flex: 0 0 auto; }
            .policies-grid { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
@php
    $headerCategoryKey = 'liveaboard';
    $headerCategoryLinks = [
        ['key' => 'accommodation', 'icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'url' => '/catalog/accommodation'],
        ['key' => 'resort-day-visit', 'icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'url' => '/catalog/resort_day_visit'],
        ['key' => 'liveaboard', 'icon' => 'fa-solid fa-ship', 'title' => 'Liveaboard', 'url' => '/catalog/liveaboard'],
        ['key' => 'excursion', 'icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'url' => '/catalog/excursion'],
        ['key' => 'sea-transport', 'icon' => 'fa-solid fa-ferry', 'title' => 'Sea Transport', 'url' => '/catalog/sea-transport'],
        ['key' => 'land-transport', 'icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'url' => '/catalog/land-transport'],
        ['key' => 'vehicle-rental', 'icon' => 'fa-solid fa-car-side', 'title' => 'Vehicle Rentals', 'url' => '/catalog/vehicle_rental'],
        ['key' => 'remote-workspace', 'icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'url' => '/catalog/remote_workspace'],
        ['key' => 'conference-room', 'icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'url' => '/catalog/conference_room'],
    ];

    // Extract liveaboard data
    $startPoint = trim((string) ($listingDetails['start_point'] ?? ''));
    $endPoint = trim((string) ($listingDetails['end_point'] ?? ''));
    $journeyDays = (int) ($listingDetails['journey_duration_days'] ?? 0);
    $cabinCount = (int) ($listingDetails['cabin_count'] ?? 0);
    $vesselName = trim((string) ($listingDetails['vessel_name'] ?? ''));
    $description = trim((string) ($property->description ?? ''));
    $rating = (float) ($property->star_rating ?? $property->stars ?? 0);
    $ratingCount = (int) ($property->reviews_count ?? 0);
    $stopovers = is_array($listingDetails['stopovers'] ?? null) ? $listingDetails['stopovers'] : [];

    // Extract amenities
    $amenitiesRaw = [];
    foreach (['amenities', 'services', 'facilities', 'highlights'] as $amenityKey) {
        $value = $listingDetails[$amenityKey] ?? null;
        if (is_array($value)) {
            $amenitiesRaw = array_merge($amenitiesRaw, $value);
            continue;
        }
        if (is_string($value) && trim($value) !== '') {
            $amenitiesRaw = array_merge($amenitiesRaw, preg_split('/[\r\n,]+/', $value) ?: []);
        }
    }
    $amenities = collect($amenitiesRaw)->map(static fn ($item) => trim((string) $item))->filter()->unique()->values();

    // Pricing: prefer lowest cabin rate (same source used by cabin table), then fallback to route-level minPrice.
    $visitorIsLocal = ($visitorResidency ?? 'foreign_national') === 'local_resident';
    $heroCurrency = $visitorIsLocal ? 'MVR' : 'USD';

    $resolveVisitorRate = static function (float $foreignUsd, float $localMvr, float $fallback) use ($visitorIsLocal, $mvrUsdRate): float {
        if ($visitorIsLocal) {
            return $localMvr > 0 ? $localMvr : $fallback;
        }

        return $foreignUsd > 0 ? $foreignUsd : ($mvrUsdRate > 0 ? round($fallback / $mvrUsdRate, 2) : 0.0);
    };

    $minRoomRate = collect($rooms ?? [])->flatMap(static function ($room) use ($resolveVisitorRate) {
        $fallback = (float) ($room->base_price_per_night ?? ($room->base_price ?? 0));

        $rates = [
            $resolveVisitorRate((float) ($room->meal_plan_room_only_price_usd ?? 0), (float) ($room->meal_plan_room_only_price_local ?? 0), $fallback),
            $resolveVisitorRate((float) ($room->meal_plan_bb_price_usd ?? 0), (float) ($room->meal_plan_bb_price_local ?? 0), 0.0),
            $resolveVisitorRate((float) ($room->meal_plan_hb_price_usd ?? 0), (float) ($room->meal_plan_hb_price_local ?? 0), 0.0),
            $resolveVisitorRate((float) ($room->meal_plan_fb_price_usd ?? 0), (float) ($room->meal_plan_fb_price_local ?? 0), 0.0),
            $resolveVisitorRate((float) ($room->meal_plan_ai_price_usd ?? 0), (float) ($room->meal_plan_ai_price_local ?? 0), 0.0),
        ];

        return collect($rates)->filter(static fn (float $value): bool => $value > 0);
    })->min();

    $resolvedHeroMinPrice = (is_numeric($minRoomRate) && (float) $minRoomRate > 0)
        ? (float) $minRoomRate
        : (float) ($minPrice ?? 0);

    $displayPrice = $resolvedHeroMinPrice > 0 ? number_format($resolvedHeroMinPrice, 2) : 'POA';

    // Gallery
    $galleryFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22420%22 viewBox=%220 0 900 420%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22420%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2228%22%3ELiveaboard%20Image%3C%2Ftext%3E%3C%2Fsvg%3E";
    $gallery = !empty($galleryMedia) ? collect($galleryMedia)->filter()->values() : collect([$galleryFallback]);
    $initialBanner = $gallery->first() ?: $galleryFallback;

    $ratingStr = $rating > 0 ? number_format($rating, 1) : 'N/A';
    $ratingLabel = $ratingCount === 1 ? 'review' : 'reviews';
    $bookingPropertyId = (int) ($property->vendor_property_id ?? 0);
    if ($bookingPropertyId <= 0) {
        $bookingPropertyId = (int) ($property->id ?? 0);
    }
@endphp

@include('partials.customer-uniform-header', [
    'injectUniformHeaderStyles' => true,
    'injectUniformHeaderScripts' => true,
    'headerNeedsSpacer' => false,
    'headerHideOnScroll' => true,
    'headerShowSearch' => false,
    'headerCategoryLinks' => $headerCategoryLinks,
    'headerActiveCategoryKey' => $headerCategoryKey,
])

<section class="top-search-shell" aria-label="Search liveaboard journey options">
    <div class="top-search-inner">
        <form method="GET" action="" class="top-search-form">
            <div class="top-search-field">
                <label for="topJourney">Journey</label>
                <input id="topJourney" type="text" value="{{ (string) ($property->name ?? '') }}" readonly>
            </div>
            <div class="top-search-field">
                <label for="topStart">Start Date</label>
                <input id="topStart" type="date" name="journey_date" min="{{ (string) now()->toDateString() }}" value="{{ trim((string) request()->query('journey_date', now()->toDateString())) }}">
            </div>
            <div class="top-search-field">
                <label for="topEnd">Duration</label>
                <input id="topEnd" type="text" value="{{ $journeyDays > 0 ? $journeyDays . ' days' : 'Variable' }}" readonly>
            </div>
            <div class="top-search-field">
                <label for="topGuests">Guests</label>
                <input id="topGuests" type="text" value="{{ $cabinCount > 0 ? $cabinCount . ' cabins' : 'Available' }}" readonly>
            </div>
            <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </div>
</section>

<main class="page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">›</span>
        <a href="/catalog/liveaboard">Liveaboard</a>
        <span aria-hidden="true">›</span>
        <span>{{ (string) ($property->name ?? 'Journey') }}</span>
    </nav>

    <section id="property-gallery-section" class="section" aria-label="Liveaboard gallery">
        <h2>Gallery</h2>
        <div class="gallery-shell" data-property-gallery>
            <div class="gallery-banner-wrap">
                <img id="propertyGalleryBanner" class="gallery-banner" src="{{ $initialBanner }}" alt="Liveaboard journey image" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $galleryFallback }}';}">
            </div>
            <div class="gallery-thumbs" role="list" aria-label="Liveaboard journey thumbnails">
                @foreach ($gallery as $index => $media)
                    <button type="button" class="gallery-thumb{{ $loop->first ? ' is-active' : '' }}" data-banner-src="{{ $media }}" aria-label="Show image {{ $index + 1 }}">
                        <img src="{{ $media }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" onerror="if(!this.dataset.fallbackApplied){this.dataset.fallbackApplied='1';this.src='{{ $media }}';}else{this.onerror=null;this.src='{{ $galleryFallback }}';}">
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="property-summary-shell" aria-label="Journey overview">
        <div class="property-summary-main">
            <span class="property-summary-stars" style="color: #f3a337; letter-spacing: 0.08em; font-size: 0.9rem;">
                <i class="fa-solid fa-star"></i> {{ $ratingStr }}
            </span>
            <h1 class="property-summary-title">{{ (string) ($property->name ?? 'Liveaboard Journey') }}</h1>
            <span class="property-type-badge"><i class="fa-solid fa-ship"></i> Liveaboard Journey</span>
            <div class="property-summary-address">
                @if ($startPoint || $endPoint)
                    <span><i class="fa-solid fa-location-dot"></i> {{ $startPoint ?: '?' }} → {{ $endPoint ?: '?' }}</span>
                    @if ($journeyDays > 0)
                        <span> · </span>
                        <span>{{ $journeyDays }} days</span>
                    @endif
                @else
                    <span>Route details coming soon.</span>
                @endif
            </div>
            <div class="property-summary-reviews">
                <span class="summary-rating-chip"><i class="fa-solid fa-star"></i> {{ $ratingStr }} · {{ $ratingCount }} {{ $ratingLabel }}</span>
            </div>
        </div>

        <aside class="property-summary-price" aria-label="Journey pricing">
            <span class="k">Starting from</span>
            <span class="v">{{ $heroCurrency }} {{ $displayPrice }}</span>
            <span class="sub">per person</span>
            <a class="cta" href="#cabins-section"><i class="fa-solid fa-calendar-check"></i> Book Journey</a>
        </aside>
    </section>

    <nav class="section-tabs" aria-label="Journey content navigation" data-section-nav>
        <a class="section-tab" href="#property-gallery-section">Photos</a>
        <a class="section-tab" href="#services-amenities-section">Amenities</a>
        <a class="section-tab" href="#stopovers-section">Trip Route &amp; Schedule</a>
        <a class="section-tab" href="#cabins-section">Cabins</a>
        <a class="section-tab" href="#guest-reviews-section">Reviews</a>
        <a class="section-tab" href="#policies-section">Policies</a>
    </nav>

    <section id="services-amenities-section" class="section" aria-label="Journey amenities">
        <h2>Amenities & Services</h2>
        @if ($amenities->isNotEmpty())
            <div class="amenities-board">
                <h3 class="amenities-head"><i class="fa-solid fa-sparkles"></i> What's Included</h3>
                <div class="amenities-columns">
                    @php
                        $grouped = $amenities->chunk(ceil($amenities->count() / 3));
                    @endphp
                    @foreach ($grouped as $group)
                        <div class="amenity-group">
                            <ul class="amenity-list">
                                @foreach ($group as $amenity)
                                    <li>
                                        <span class="facility-icon"><i class="fa-solid fa-check"></i></span>
                                        <span>{{ $amenity }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="empty-state">Amenities and services will be listed shortly.</div>
        @endif
    </section>

    <section id="stopovers-section" class="section" aria-label="Trip route and schedule">
        <h2>Trip Route &amp; Schedule</h2>
        @php
            $tripRouteItems = collect($listingDetails['route_schedules'] ?? [])->filter(static fn ($item) => is_array($item))->values();
            $tripStopItems = collect($stopovers ?? [])->values();
        @endphp
        @if ($tripRouteItems->isNotEmpty() || $tripStopItems->isNotEmpty())
            <div class="rooms-grid">
                @foreach ($tripRouteItems as $idx => $leg)
                    @php
                        $origin = trim((string) ($leg['origin'] ?? 'Departure'));
                        $destination = trim((string) ($leg['destination'] ?? 'Destination'));
                        $dep = trim((string) ($leg['dep_time'] ?? ''));
                        $arr = trim((string) ($leg['arr_time'] ?? ''));
                        $routeCode = trim((string) ($leg['route_code'] ?? ''));
                    @endphp
                    <article class="room-card">
                        <h3 class="room-title">Leg {{ $idx + 1 }}: {{ $origin }} → {{ $destination }}</h3>
                        <p class="detail-line" style="margin: 0; color: #5a7589; font-size: 0.85rem; line-height: 1.4;">
                            @if ($dep !== '' || $arr !== '')
                                {{ $dep !== '' ? ('Departure: ' . $dep) : 'Departure: TBD' }}{{ $arr !== '' ? (' · Arrival: ' . $arr) : '' }}
                            @else
                                Schedule time will be confirmed by the operator.
                            @endif
                            @if ($routeCode !== '')
                                {{ ' · Route ' . $routeCode }}
                            @endif
                        </p>
                    </article>
                @endforeach

                @foreach ($tripStopItems as $index => $stop)
                    @php
                        $stopName = is_array($stop) ? ($stop['name'] ?? 'Stop ' . ($index + 1)) : (is_object($stop) ? ($stop->name ?? 'Stop ' . ($index + 1)) : 'Stop ' . ($index + 1));
                        $stopDesc = is_array($stop) ? ($stop['description'] ?? '') : (is_object($stop) ? ($stop->description ?? '') : '');
                    @endphp
                    <article class="room-card">
                        <h3 class="room-title">Stop {{ $index + 1 }}: {{ $stopName }}</h3>
                        <p class="detail-line" style="margin: 0; color: #5a7589; font-size: 0.85rem; line-height: 1.4;">
                            {{ $stopDesc !== '' ? $stopDesc : 'Scenic stop during the journey route.' }}
                        </p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">Trip route and journey schedule will be published soon.</div>
        @endif
    </section>

    <section id="cabins-section" class="section rooms-section" aria-label="Available cabins">
        <h2>Cabin Options</h2>
        <div class="rooms-grid">
            @forelse ($rooms as $room)
                @php
                    $roomId = (int) ($room->id ?? 0);
                    $roomName = trim((string) ($room->name ?? 'Cabin'));
                    $bedType = trim((string) ($room->bed_type ?? 'Standard Bed'));
                    $maxOccupancy = max(1, (int) ($room->max_occupancy ?? 2));
                    $roomMedia = collect($roomMediaByRoom->get($roomId, collect()));
                    $roomImages = $roomMedia->map(static fn ($url) => trim((string) $url))->filter(static fn ($url) => $url !== '')->values();
                    $roomThumb = $roomImages->first();

                    $visitorIsLocal = ($visitorResidency ?? 'foreign_national') === 'local_resident';
                    $roomCurrency = $visitorIsLocal ? 'MVR' : 'USD';

                    $defaultNightlyRate = (float) ($room->base_price_per_night ?? ($room->base_price ?? 0));
                    $resolveVisitorRate = static function (float $foreignUsd, float $localMvr, float $fallback) use ($visitorIsLocal, $mvrUsdRate): float {
                        if ($visitorIsLocal) {
                            return $localMvr > 0 ? $localMvr : $fallback;
                        }
                        return $foreignUsd > 0 ? $foreignUsd : ($mvrUsdRate > 0 ? round($fallback / $mvrUsdRate, 2) : 0.0);
                    };

                    $roomOnlyNightlyRate = $resolveVisitorRate(
                        (float) ($room->meal_plan_room_only_price_usd ?? 0),
                        (float) ($room->meal_plan_room_only_price_local ?? 0),
                        $defaultNightlyRate
                    );

                    $rateOptions = collect([
                        [
                            'meal_plan' => 'Room Only',
                            'title' => 'Room Only',
                            'subtitle' => 'Cabin only (no meals)',
                            'nightly' => $roomOnlyNightlyRate,
                            'icon' => 'fa-solid fa-bed',
                        ],
                        [
                            'meal_plan' => 'BB',
                            'title' => 'Bed & Breakfast',
                            'subtitle' => 'Breakfast included',
                            'nightly' => $resolveVisitorRate((float) ($room->meal_plan_bb_price_usd ?? 0), (float) ($room->meal_plan_bb_price_local ?? 0), 0.0),
                            'icon' => 'fa-solid fa-mug-hot',
                        ],
                        [
                            'meal_plan' => 'HB',
                            'title' => 'Half Board',
                            'subtitle' => 'Half board included',
                            'nightly' => $resolveVisitorRate((float) ($room->meal_plan_hb_price_usd ?? 0), (float) ($room->meal_plan_hb_price_local ?? 0), 0.0),
                            'icon' => 'fa-solid fa-utensils',
                        ],
                        [
                            'meal_plan' => 'FB',
                            'title' => 'Full Board',
                            'subtitle' => 'Full board included',
                            'nightly' => $resolveVisitorRate((float) ($room->meal_plan_fb_price_usd ?? 0), (float) ($room->meal_plan_fb_price_local ?? 0), 0.0),
                            'icon' => 'fa-solid fa-bowl-food',
                        ],
                        [
                            'meal_plan' => 'All Inclusive',
                            'title' => 'All Inclusive',
                            'subtitle' => 'Journey package with meals & services',
                            'nightly' => $resolveVisitorRate((float) ($room->meal_plan_ai_price_usd ?? 0), (float) ($room->meal_plan_ai_price_local ?? 0), 0.0),
                            'icon' => 'fa-solid fa-champagne-glasses',
                        ],
                    ])->filter(static fn ($item) => (float) ($item['nightly'] ?? 0) > 0)->values();

                    if ($rateOptions->isEmpty() && $roomOnlyNightlyRate > 0) {
                        $rateOptions = collect([[
                            'meal_plan' => 'Room Only',
                            'title' => 'Room Only',
                            'subtitle' => 'Standard cabin rate',
                            'nightly' => $roomOnlyNightlyRate,
                            'icon' => 'fa-solid fa-bed',
                        ]]);
                    }

                    $amenitiesText = collect(preg_split('/[,\n]+/', (string) ($room->amenities ?? '')) ?: [])
                        ->map(static fn ($item) => trim((string) $item))
                        ->filter()->values();

                    $packagePax = max(1, min(4, (int) (
                        $room->package_person_count
                        ?? $room->package_occupancy
                        ?? $room->price_basis_persons
                        ?? 1
                    )));

                    $amenityLookup = $amenitiesText
                        ->map(static fn ($item) => strtolower(trim((string) $item)))
                        ->filter(static fn ($item) => $item !== '')
                        ->values();

                    $roomHighlights = collect([
                        [
                            'label' => $bedType !== '' ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $bedType)) : 'Queen',
                            'icon' => 'fa-solid fa-bed',
                        ],
                        [
                            'label' => 'Up to ' . $maxOccupancy . ' guests',
                            'icon' => 'fa-solid fa-users',
                        ],
                    ]);

                    if ($amenityLookup->contains(static fn ($item) => str_contains($item, 'air') && str_contains($item, 'condition'))) {
                        $roomHighlights->push(['label' => 'Air conditioned', 'icon' => 'fa-solid fa-snowflake']);
                    } elseif ($amenityLookup->contains(static fn ($item) => str_contains($item, 'sea_view') || str_contains($item, 'sea view'))) {
                        $roomHighlights->push(['label' => 'Sea view', 'icon' => 'fa-solid fa-water']);
                    } elseif ($amenityLookup->contains(static fn ($item) => str_contains($item, 'smart_tv') || str_contains($item, 'smart tv') || $item === 'tv')) {
                        $roomHighlights->push(['label' => 'Smart TV', 'icon' => 'fa-solid fa-tv']);
                    } elseif ($amenityLookup->contains(static fn ($item) => str_contains($item, 'mini_bar') || str_contains($item, 'mini bar'))) {
                        $roomHighlights->push(['label' => 'Mini bar', 'icon' => 'fa-solid fa-martini-glass-citrus']);
                    }

                    $roomHighlights = $roomHighlights
                        ->filter(static fn ($item) => is_array($item) && trim((string) ($item['label'] ?? '')) !== '')
                        ->unique(static fn ($item) => strtolower(trim((string) ($item['label'] ?? ''))))
                        ->take(3)
                        ->values();

                    $roomDescription = trim((string) ($room->description ?? ''));
                @endphp
                <article class="room-card cabin-room-card" data-room-id="{{ $roomId }}" data-room-name="{{ $roomName }}" data-room-bed="{{ $bedType }}" data-room-description="{{ $roomDescription }}" data-room-currency="{{ $roomCurrency }}" data-room-images='@json($roomImages->all())' data-room-amenities='@json($amenitiesText->all())'>
                    <a href="#" class="room-media-link" data-open-room-modal="{{ $roomId }}" title="View {{ $roomName }} details">
                        <div class="room-media">
                            @if ($roomThumb)
                                <img src="{{ $roomThumb }}" alt="{{ $roomName }}" loading="lazy">
                            @else
                                <i class="fa-solid fa-door-open" style="font-size: 2.5rem; color: #0f6179; opacity: 0.3;"></i>
                            @endif
                            <span class="room-tag"><i class="{{ (string) (($rateOptions->first()['icon'] ?? 'fa-solid fa-bed')) }}" aria-hidden="true"></i>{{ (string) (($rateOptions->first()['meal_plan'] ?? 'Room Only')) }}</span>
                        </div>
                    </a>
                    <div class="room-body">
                        <h3><a href="#" class="room-name-link" data-open-room-modal="{{ $roomId }}">{{ $roomName }}</a></h3>
                        
                        <div class="room-offer-table">
                            <div class="room-offer-head">
                                <span>Your Choices</span>
                                <span>Sleeps</span>
                                <span>Today's Price</span>
                            </div>
                            @foreach ($rateOptions as $rateIndex => $rateOption)
                                @php
                                    $nightlyRateRaw = (float) ($rateOption['nightly'] ?? 0);
                                    $ratePrice = number_format($nightlyRateRaw, 2);
                                @endphp
                                <div class="room-offer-row{{ $rateIndex > 0 ? ' is-hidden' : '' }}">
                                    <div class="room-choices">
                                        <span class="room-option-title">{{ (string) ($rateOption['title'] ?? 'Room Only') }}</span>
                                        <span class="room-option-subtitle">{{ (string) ($rateOption['subtitle'] ?? 'Rate details available') }}</span>
                                    </div>
                                    <div>
                                        <span class="room-sleeps">
                                            <span class="room-sleeps-icons">
                                                @for ($i = 0; $i < $packagePax; $i++)
                                                    <i class="fa-solid fa-user"></i>
                                                @endfor
                                            </span>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="room-price-box">
                                            <div>
                                                <div class="room-price-now">{{ $roomCurrency }} {{ $ratePrice }}</div>
                                                <div class="room-price-summary">Per person</div>
                                                <div class="room-price-summary-note">All-inclusive package: room, meals, transfer</div>
                                            </div>
                                            <button type="button" class="reserve-btn" data-quick-reserve-btn data-room-id="{{ $roomId }}" data-room-name="{{ $roomName }}" data-meal-plan="{{ (string) ($rateOption['meal_plan'] ?? 'Room Only') }}" data-nightly-rate="{{ number_format($nightlyRateRaw, 2, '.', '') }}">Reserve</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if ($rateOptions->count() > 1)
                                <div class="room-offer-expand">
                                    <button class="room-offer-expand-btn" data-expand-toggle="room-{{ $roomId }}">Show More Rates</button>
                                </div>
                            @endif
                        </div>

                        <ul class="room-side-details">
                            @foreach ($roomHighlights as $highlight)
                                <li><i class="{{ (string) ($highlight['icon'] ?? 'fa-solid fa-circle-check') }}" aria-hidden="true" style="color:#1f6f95; width:14px;"></i><span>{{ (string) ($highlight['label'] ?? '') }}</span></li>
                            @endforeach
                            <li><a class="room-name-link room-details-link-prominent" href="#" data-open-room-modal="{{ $roomId }}"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Room Details</a></li>
                        </ul>
                    </div>
                </article>
            @empty
                <article class="room-card"><div class="room-body"><h3>Cabin Options</h3><span class="muted">Cabin inventory will be published soon. All-inclusive pricing includes meals, activities, and journey logistics.</span></div></article>
            @endforelse
        </div>
    </section>

    <form id="liveaboardQuickReserveForm" method="POST" action="/booking/reserve-category" style="display:none;">
        @csrf
        <input type="hidden" name="category_key" value="liveaboard">
        <input type="hidden" name="property_id" value="{{ $bookingPropertyId }}">
        <input type="hidden" name="service_start_date" id="quickReserveStartDate" value="{{ trim((string) request()->query('journey_date', now()->toDateString())) }}">
        <input type="hidden" name="service_end_date" id="quickReserveEndDate" value="{{ trim((string) request()->query('journey_date', now()->toDateString())) }}">
        <input type="hidden" name="adults" value="1">
        <input type="hidden" name="children" value="0">
        <input type="hidden" name="infants" value="0">
        <input type="hidden" name="guest_residency" value="{{ (string) ($visitorResidency ?? 'foreign_national') }}">
        <input type="hidden" name="boarding_point" value="{{ $startPoint !== '' ? $startPoint : 'Departure Point' }}">
        <input type="hidden" name="disembark_point" value="{{ $endPoint !== '' ? $endPoint : 'Destination Point' }}">
        <input type="hidden" name="service_notes" id="quickReserveServiceNotes" value="">
    </form>

    <section id="guest-reviews-section" class="section" aria-label="Guest reviews">
        <h2>Guest Reviews</h2>
        @if ($ratingCount > 0)
            <div class="review-card">
                <div class="review-head">
                    <span class="review-author">Verified Guest</span>
                    <span class="review-rating"><i class="fa-solid fa-star"></i> {{ min(5, max(1, round($rating))) }}/5</span>
                </div>
                <p class="review-body">Excellent liveaboard experience with attentive crew, smooth sailing, and memorable stops. Highly recommended for diving and marine adventures.</p>
            </div>
            @if ($ratingCount > 1)
                <div class="review-card">
                    <div class="review-head">
                        <span class="review-author">+ {{ $ratingCount - 1 }} more reviews</span>
                    </div>
                    <p class="review-body" style="text-align: center; color: #7a8fa3; font-style: italic;">Reviews from other guests will be displayed here. Visit our platform to read full guest feedback.</p>
                </div>
            @endif
        @else
            <div class="empty-state">Reviews from guests will appear here after their journey.</div>
        @endif
    </section>

    <section id="policies-section" class="section policies-section" aria-label="Journey policies">
        <h2>Journey Policies</h2>
        <div class="policies-grid">
            <div class="policy-label">Journey Policy</div>
            <div class="policy-value">{{ trim((string) ($listingDetails['journey_policy'] ?? 'Route and timing may vary based on weather and sea conditions.')) }}</div>

            <div class="policy-label">Cancellation</div>
            <div class="policy-value">{{ trim((string) ($listingDetails['cancellation_policy'] ?? 'Flexible cancellation up to 7 days before departure.')) }}</div>

            <div class="policy-label">Boarding</div>
            <div class="policy-value">{{ trim((string) ($listingDetails['boarding_policy'] ?? 'Arrive 1 hour before departure with valid passport and travel documents.')) }}</div>

            <div class="policy-label">Safety</div>
            <div class="policy-value">{{ trim((string) ($listingDetails['safety_policy'] ?? 'All guests must comply with vessel safety procedures and crew instructions.')) }}</div>
        </div>
    </section>

    <section id="similar-liveaboards-section" class="nearby-properties-section" aria-label="Similar liveaboard journeys">
        <div class="nearby-head">
            <h2>Similar Liveaboard Journeys</h2>
        </div>

        @php
            $similarProperties = collect($similarProperties ?? [])->map(static function ($item) {
                if (is_array($item)) {
                    return $item;
                }
                if (is_object($item)) {
                    return [
                        'id' => (int) ($item->id ?? 0),
                        'name' => (string) ($item->name ?? ''),
                        'base_price' => (float) ($item->base_price ?? 0),
                        'currency' => (string) ($item->currency ?? 'MVR'),
                        'location_line' => (string) ($item->location_line ?? 'Maldives'),
                        'distance_km' => isset($item->distance_km) ? (float) $item->distance_km : null,
                        'url' => (string) ($item->url ?? ''),
                        'thumbnail_url' => (string) ($item->thumbnail_url ?? ''),
                    ];
                }
                return [];
            })->filter(static fn ($item) => is_array($item) && (int) ($item['id'] ?? 0) > 0)->values();
        @endphp

        @if ($similarProperties->isNotEmpty())
            <div class="nearby-grid">
                @foreach ($similarProperties as $nearby)
                    @php
                        $nearbyCurrency = strtoupper(trim((string) ($nearby['currency'] ?? 'MVR')));
                        $nearbyBasePrice = (float) ($nearby['base_price'] ?? 0);
                        $nearbyPrice = number_format($nearbyBasePrice, 2);
                        $nearbyPriceLabel = $nearbyBasePrice > 0
                            ? ('From ' . $nearbyCurrency . ' ' . $nearbyPrice)
                            : 'Price on request';
                        $nearbyDistance = isset($nearby['distance_km']) ? (float) $nearby['distance_km'] : null;
                        $nearbyThumb = trim((string) ($nearby['thumbnail_url'] ?? ''));
                        $nearbyThumbFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22300%22 viewBox=%220 0 600 300%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d9e9f4%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c6ddec%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22600%22 height=%22300%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%233e6078%22 font-family=%22Arial%22 font-size=%2224%22%3ENearby%20Property%3C/text%3E%3C/svg%3E";
                    @endphp
                    <a href="{{ $nearby['url'] ?? '#' }}" class="nearby-card" title="{{ $nearby['name'] ?? '' }}" aria-label="Open {{ $nearby['name'] ?? 'Property' }}">
                        <img src="{{ $nearbyThumb !== '' ? $nearbyThumb : $nearbyThumbFallback }}" alt="{{ $nearby['name'] ?? 'Property' }}" class="nearby-card-media" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $nearbyThumbFallback }}';}">
                        <div class="nearby-card-body">
                            <span class="nearby-location">{{ $nearby['location_line'] ?? 'Maldives' }}</span>
                            <h3 class="nearby-name">{{ $nearby['name'] ?? 'Journey' }}</h3>
                            <div class="nearby-meta">
                                <span class="nearby-price">{{ $nearbyPriceLabel }}</span>
                                @if ($nearbyDistance !== null)
                                    <span>{{ number_format($nearbyDistance, 1) }} km away</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="nearby-empty">
                More similar liveaboard journeys will be displayed here soon. Check back for additional options!
            </div>
        @endif
    </section>

    <div class="room-details-modal-overlay" id="roomDetailsModal" data-room-modal>
        <div class="room-details-modal">
            <button class="room-details-modal-close" data-close-modal>×</button>
            <div id="roomModalContent" class="room-details-content"></div>
        </div>
    </div>

    <template id="roomDetailsTemplate">
        <div class="room-details-gallery" data-gallery></div>
        <div class="room-details-sidebar">
            <div class="room-details-header">
                <h2 class="room-details-title" data-title></h2>
                <div class="room-bed-info" data-bedinfo></div>
                <p class="room-details-description" data-description></p>
            </div>
            <div class="room-amenities-grid" data-amenities></div>
            <a class="room-select-btn" data-select-btn href="#">Reserve Cabin</a>
        </div>
    </template>
</main>

@include('partials.global-site-footer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        (function () {
            const gallery = document.querySelector('[data-property-gallery]');
            if (!gallery) {
                return;
            }

            const banner = gallery.querySelector('#propertyGalleryBanner');
            const thumbs = gallery.querySelectorAll('.gallery-thumb');
            if (!banner || thumbs.length === 0) {
                return;
            }

            thumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    const newSrc = thumb.dataset.bannerSrc;
                    thumbs.forEach((t) => t.classList.remove('is-active'));
                    thumb.classList.add('is-active');
                    if (newSrc) {
                        banner.src = newSrc;
                    }
                });
            });
        })();

        (function () {
            const root = document.documentElement;
            const topSearchShell = document.querySelector('.top-search-shell');
            const sectionTabs = document.querySelector('.section-tabs');

            const syncStickyMetrics = () => {
                if (!topSearchShell) {
                    return;
                }

                const shellHeight = Math.max(0, Math.ceil(topSearchShell.getBoundingClientRect().height));
                if (shellHeight > 0) {
                    root.style.setProperty('--property-search-shell-height', `${shellHeight}px`);
                }

                if (sectionTabs) {
                    sectionTabs.style.setProperty('scroll-margin-top', 'calc(var(--property-header-offset) + var(--property-search-shell-height) + 12px)');
                }
            };

            syncStickyMetrics();
            window.addEventListener('resize', syncStickyMetrics);
            window.addEventListener('load', syncStickyMetrics);
        })();

        (function () {
            const root = document.documentElement;
            const nav = document.querySelector('[data-section-nav]');
            if (!nav) {
                return;
            }

            const links = Array.from(nav.querySelectorAll('.section-tab[href^="#"]'));
            if (links.length === 0) {
                return;
            }

            const targets = links
                .map((link) => {
                    const id = String(link.getAttribute('href') || '').replace('#', '');
                    const el = id ? document.getElementById(id) : null;
                    return el ? { link, el } : null;
                })
                .filter(Boolean);

            if (targets.length === 0) {
                return;
            }

            const activate = (activeLink) => {
                links.forEach((link) => {
                    link.classList.toggle('is-active', link === activeLink);
                });
            };

            const sync = () => {
                const headerOffset = parseInt(getComputedStyle(root).getPropertyValue('--property-header-offset'), 10) || 0;
                const shellHeight = parseInt(getComputedStyle(root).getPropertyValue('--property-search-shell-height'), 10) || 0;
                const marker = window.scrollY + headerOffset + shellHeight + 48;
                let current = targets[0];
                for (const entry of targets) {
                    if (entry.el.offsetTop <= marker) {
                        current = entry;
                    } else {
                        break;
                    }
                }
                activate(current.link);
            };

            links.forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = String(link.getAttribute('href') || '').replace('#', '');
                    const target = targetId ? document.getElementById(targetId) : null;
                    if (!target) {
                        return;
                    }

                    const headerOffset = parseInt(getComputedStyle(root).getPropertyValue('--property-header-offset'), 10) || 0;
                    const shellHeight = parseInt(getComputedStyle(root).getPropertyValue('--property-search-shell-height'), 10) || 0;
                    const targetY = Math.max(0, target.getBoundingClientRect().top + window.scrollY - headerOffset - shellHeight - 16);
                    window.scrollTo({ top: targetY, behavior: 'smooth' });
                    activate(link);
                });
            });

            window.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync);
            sync();
        })();

        (function () {
            const reserveButtons = document.querySelectorAll('[data-quick-reserve-btn]');
            const quickReserveForm = document.getElementById('liveaboardQuickReserveForm');
            const startInput = document.getElementById('topStart');
            const startDateField = document.getElementById('quickReserveStartDate');
            const endDateField = document.getElementById('quickReserveEndDate');
            const serviceNotesField = document.getElementById('quickReserveServiceNotes');
            const journeyDays = Number(@json((int) ($journeyDays ?? 0)));
            const today = @json((string) now()->toDateString());

            if (!quickReserveForm || reserveButtons.length === 0) {
                return;
            }

            const toDateOnly = (value) => {
                const normalized = String(value || '').trim();
                return normalized.length >= 10 ? normalized.slice(0, 10) : normalized;
            };

            const addDays = (dateString, days) => {
                const normalized = toDateOnly(dateString);
                if (normalized === '') {
                    return normalized;
                }
                const date = new Date(normalized + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    return normalized;
                }
                date.setDate(date.getDate() + Math.max(0, days));
                return date.toISOString().slice(0, 10);
            };

            reserveButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const startDate = toDateOnly(startInput?.value || today || '');
                    const endDate = addDays(startDate, journeyDays > 1 ? (journeyDays - 1) : 0);

                    if (startDateField) {
                        startDateField.value = startDate;
                    }
                    if (endDateField) {
                        endDateField.value = endDate;
                    }

                    const roomName = String(button.dataset.roomName || 'Cabin').trim();
                    const mealPlan = String(button.dataset.mealPlan || 'Room Only').trim();
                    const nightlyRate = String(button.dataset.nightlyRate || '').trim();
                    if (serviceNotesField) {
                        serviceNotesField.value = `Room: ${roomName} | Plan: ${mealPlan}${nightlyRate !== '' ? (' | Rate: ' + nightlyRate) : ''}`;
                    }

                    quickReserveForm.submit();
                });
            });
        })();

        // Room Rate Expansion
        document.querySelectorAll('[data-expand-toggle]').forEach((btn) => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const table = this.closest('.room-offer-table');
                if (!table) {
                    return;
                }

                const rows = Array.from(table.querySelectorAll('.room-offer-row')).slice(1);
                if (rows.length === 0) {
                    return;
                }

                const nowHidden = !rows[0].classList.contains('is-hidden');
                rows.forEach((row) => {
                    row.classList.toggle('is-hidden', nowHidden);
                });

                this.textContent = nowHidden ? 'Show More Rates' : 'Show Less Rates';
            });
        });

        // Room Details Modal
        (function () {
            const modal = document.getElementById('roomDetailsModal');
            const roomDetailLinks = document.querySelectorAll('[data-open-room-modal]');
            const closeBtn = modal?.querySelector('[data-close-modal]');
            const template = document.getElementById('roomDetailsTemplate');
            const contentArea = document.getElementById('roomModalContent');

            if (!modal || !template || !contentArea || roomDetailLinks.length === 0) {
                return;
            }

            function parseJsonArray(value) {
                if (!value) {
                    return [];
                }
                try {
                    const parsed = JSON.parse(value);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            }

            function titleCaseToken(token) {
                return String(token || '')
                    .replace(/[_-]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .replace(/\b\w/g, (char) => char.toUpperCase());
            }

            function amenityIconClass(token) {
                const normalized = String(token || '').toLowerCase().trim();
                if (normalized.includes('air') && normalized.includes('condition')) return 'fa-solid fa-snowflake';
                if (normalized.includes('sea view') || normalized.includes('sea_view') || normalized.includes('ocean')) return 'fa-solid fa-water';
                if (normalized.includes('tv')) return 'fa-solid fa-tv';
                if (normalized.includes('mini bar') || normalized.includes('mini_bar') || normalized.includes('bar')) return 'fa-solid fa-martini-glass-citrus';
                if (normalized.includes('housekeeping') || normalized.includes('clean')) return 'fa-solid fa-broom';
                if (normalized.includes('safety')) return 'fa-solid fa-shield';
                if (normalized.includes('wifi') || normalized.includes('wi-fi') || normalized.includes('internet')) return 'fa-solid fa-wifi';
                return 'fa-solid fa-circle-check';
            }

            function buildRoomData(roomCard) {
                const roomId = roomCard.dataset.roomId || '';
                const roomName = roomCard.dataset.roomName || 'Cabin';
                const roomBed = roomCard.dataset.roomBed || 'Standard Bed';
                const roomDescription = roomCard.dataset.roomDescription || '';
                const roomImages = parseJsonArray(roomCard.dataset.roomImages);
                const roomAmenities = parseJsonArray(roomCard.dataset.roomAmenities);
                const roomLink = roomCard.querySelector('.reserve-btn')?.getAttribute('href') || '#';
                const maxOccupancy = roomCard.querySelector('.room-side-details li:nth-child(2) span:last-child')?.textContent?.trim() || 'Up to 2 guests';

                return {
                    roomId,
                    roomName,
                    roomBed,
                    roomDescription,
                    roomImages,
                    roomAmenities,
                    roomLink,
                    maxOccupancy,
                };
            }

            function populateModal(roomId) {
                const roomCard = document.querySelector(`.room-card[data-room-id="${roomId}"]`);
                if (!roomCard) {
                    return;
                }

                const roomData = buildRoomData(roomCard);
                contentArea.innerHTML = '';

                const clone = template.content.cloneNode(true);

                const galleryEl = clone.querySelector('[data-gallery]');
                if (galleryEl) {
                    const images = roomData.roomImages.length > 0 ? roomData.roomImages.slice(0, 4) : [''];
                    galleryEl.innerHTML = images.map((src, index) => `<img src="${src}" alt="${roomData.roomName} image ${index + 1}" loading="lazy">`).join('');
                }

                const titleEl = clone.querySelector('[data-title]');
                if (titleEl) {
                    titleEl.textContent = roomData.roomName;
                }

                const bedInfoEl = clone.querySelector('[data-bedinfo]');
                if (bedInfoEl) {
                    bedInfoEl.textContent = `${titleCaseToken(roomData.roomBed)} • ${roomData.maxOccupancy}`;
                }

                const descriptionEl = clone.querySelector('[data-description]');
                if (descriptionEl) {
                    descriptionEl.textContent = roomData.roomDescription !== ''
                        ? roomData.roomDescription
                        : `${roomData.roomName} includes comfortable bedding and curated onboard amenities for a complete liveaboard journey.`;
                }

                const amenitiesEl = clone.querySelector('[data-amenities]');
                if (amenitiesEl) {
                    const amenities = roomData.roomAmenities.length > 0 ? roomData.roomAmenities : ['Air conditioned cabin', 'Onboard housekeeping', 'Safety equipment'];
                    amenitiesEl.innerHTML = `<ul class="room-side-details">${amenities.map((item) => `<li><i class="${amenityIconClass(item)}" aria-hidden="true" style="color:#1f6f95; width:14px;"></i><span>${titleCaseToken(item)}</span></li>`).join('')}</ul>`;
                }

                const selectBtn = clone.querySelector('[data-select-btn]');
                if (selectBtn) {
                    selectBtn.href = roomData.roomLink;
                }

                contentArea.appendChild(clone);
            }

            roomDetailLinks.forEach((link) => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const roomId = this.dataset.openRoomModal;
                    populateModal(roomId);
                    modal.classList.add('is-active');
                });
            });

            closeBtn?.addEventListener('click', function () {
                modal.classList.remove('is-active');
            });

            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('is-active');
                }
            });
        })();
    });
</script>
</body>
</html>