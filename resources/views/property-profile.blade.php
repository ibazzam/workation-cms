<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Property') }} | Workation</title>
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
            padding: 0;
            outline: none;
        }

        .top-search-btn {
            border: 1px solid #0f6179;
            background: #0f6179;
            color: #ffffff;
            border-radius: 8px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .top-search-btn:hover { filter: brightness(1.04); }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px 6px;
            margin-bottom: 10px;
            font-size: 0.78rem;
            color: #5f7488;
        }

        .breadcrumb a {
            color: #0f6179;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb a:hover { text-decoration: underline; }

        .breadcrumb span:last-child { color: #264d66; font-weight: 700; }

        .hero {
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(130deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: 14px 16px;
            box-shadow: 0 20px 36px rgba(15, 88, 113, 0.22);
        }

        .hero,
        .share-card {
            display: none;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hero h1 { margin: 0; font-size: clamp(1.2rem, 2.3vw, 1.8rem); }

        .hero-stars {
            color: #ffd978;
            font-size: 0.95rem;
            letter-spacing: 0.07em;
            text-shadow: 0 1px 0 rgba(54, 34, 0, 0.25);
        }

        .hero .sub { margin: 7px 0 0; font-size: 0.92rem; color: #d8f4f8; }

        .hero-cta-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select-rooms-btn {
            text-decoration: none;
            border: 1px solid rgba(174, 215, 255, 0.8);
            background: linear-gradient(135deg, #2d73f0 0%, #1a66d8 100%);
            color: #f6fbff;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .select-rooms-btn:hover { filter: brightness(1.04); }

        .address-bar {
            margin-top: 10px;
            border: 1px solid rgba(225, 248, 252, 0.4);
            border-radius: 10px;
            background: rgba(6, 70, 87, 0.22);
            padding: 8px 10px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.84rem;
        }

        .address-main {
            display: grid;
            gap: 2px;
        }

        .map-link {
            color: #cfeeff;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .map-link:hover { text-decoration: underline; }

        .price-chip {
            border: 1px solid rgba(255, 219, 165, 0.7);
            border-radius: 999px;
            background: rgba(255, 214, 138, 0.24);
            padding: 4px 10px;
            color: #fff7ea;
            font-weight: 700;
            white-space: nowrap;
        }

        .share-card {
            margin-top: 10px;
            border: 1px solid #cfe1ec;
            border-radius: 14px;
            background: #ffffff;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .share-label {
            font-size: 0.78rem;
            color: #3f6278;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .share-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .share-links a,
        .share-links button {
            border: 1px solid #b8d9e2;
            background: #f8fdff;
            color: #0f6179;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font: inherit;
            font-size: 0.92rem;
            text-decoration: none;
            cursor: pointer;
            padding: 0;
        }

        .share-links a:hover,
        .share-links button:hover { background: #eef8fc; }

        .hero-rating {
            border: 1px solid rgba(225, 248, 252, 0.4);
            border-radius: 12px;
            padding: 8px 11px;
            background: rgba(6, 70, 87, 0.26);
            font-size: 0.82rem;
            color: #ebfbff;
            white-space: nowrap;
        }

        .hero-stats {
            margin-top: 11px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .hero-stat {
            border: 1px solid rgba(225, 248, 252, 0.38);
            border-radius: 11px;
            background: rgba(7, 74, 93, 0.23);
            padding: 9px 10px;
        }

        .hero-stat .k { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #cfeff4; }
        .hero-stat .v { margin-top: 2px; font-size: 0.9rem; font-weight: 700; color: #f1fcff; }

        .layout {
            margin-top: 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            align-items: start;
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

        .rooms-section .section {
            border: none;
            padding: 0;
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
            background: #eff7fb;
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

        .property-summary-stars {
            color: #f3a337;
            letter-spacing: 0.08em;
            font-size: 0.9rem;
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

        .summary-review-link {
            color: #0f6179;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .summary-review-link:hover { text-decoration: underline; }

        .summary-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .summary-share {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .summary-share a,
        .summary-share button {
            border: 1px solid #cfe1ec;
            background: #f8fdff;
            color: #0f6179;
            border-radius: 999px;
            height: 30px;
            min-width: 30px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font: inherit;
            font-size: 0.78rem;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
            gap: 6px;
        }

        .summary-share a:hover,
        .summary-share button:hover { background: #eef8fc; }

        .summary-tag {
            border: 1px solid #d6e4ee;
            border-radius: 999px;
            background: #f8fbfe;
            color: #2d4e64;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 9px;
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

        .property-summary-price-total {
            color: #1d4c67;
            font-size: 0.83rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .property-summary-price-note {
            color: #4f6f85;
            font-size: 0.76rem;
            line-height: 1.25;
        }

        .property-summary-price-soldout {
            border: 1px solid #f0b7b7;
            border-radius: 999px;
            background: #fff2f2;
            color: #a53a3a;
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
        }

        .property-summary-price-soldout.is-hidden {
            display: none;
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

        .info-section {
            margin-top: 12px;
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: start;
        }

        .info-main {
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .block-title {
            margin: 0;
            font-size: 1.06rem;
            color: #1d435c;
        }

        .highlights-grid {
            margin-top: 9px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .highlight-item {
            border: 1px solid #d8e6ef;
            background: #f4f9fd;
            border-radius: 11px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.79rem;
            color: #2e536a;
            min-height: 44px;
        }

        .description-section {
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #f8fcff;
            padding: 16px;
        }

        .description-section h2 {
            margin: 0 0 8px;
            font-size: 1.08rem;
        }

        .description-text {
            margin: 0;
            color: #39586d;
            font-size: 0.92rem;
            line-height: 1.75;
            white-space: pre-line;
        }

        .description-note {
            margin: 8px 0 0;
            color: #5d7487;
            font-size: 0.77rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px dashed #d6e6ef;
            font-size: 0.85rem;
            color: #33536a;
        }

        .summary-row:last-child { border-bottom: 0; }

        .facility-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
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

        .amenities-head .facility-icon {
            border: 1px solid #c6dced;
            border-radius: 999px;
            background: #e8f4fc;
            color: #195170;
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

        .amenity-list li .facility-icon {
            flex: 0 0 16px;
            width: 16px;
            height: 16px;
            font-size: 0.72rem;
            color: #1d5a7a;
        }

        .amenity-list-item {
            display: inline-grid;
            align-items: center;
            gap: 4px;
            min-width: 0;
        }

        .amenity-pill {
            border: 1px solid #b9d9e8;
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 0.62rem;
            font-weight: 600;
            color: #1e607e;
            background: #e5f3fb;
            white-space: nowrap;
            justify-self: start;
        }

        .facility-item {
            border: 1px solid #cfe0eb;
            background: linear-gradient(135deg, #f5fbf8 0%, #edf6ff 100%);
            color: #24516b;
            border-radius: 11px;
            font-size: 0.81rem;
            line-height: 1.35;
            padding: 9px 10px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .facility-icon {
              display: inline-flex;
              align-items: center;
              justify-content: center;
              flex: 0 0 18px;
              width: 18px;
              height: 18px;
              font-size: 0.85rem;
              color: #0f6179;
              margin-top: 1px;
              line-height: 1;
        }

        .facility-icon svg {
            width: 12px;
            height: 12px;
            stroke: #0f6179;
            fill: none;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        .facility-icon span {
            display: inline-block;
            font-size: 1rem;
            line-height: 1;
            margin: 0;
        }

        .facility-text {
            color: #2c5168;
            font-weight: 500;
        }

        .review-card {
            border: 1px solid #d4e5ef;
            border-radius: 14px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .review-score {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .review-score-badge {
            min-width: 58px;
            text-align: center;
            border-radius: 10px;
            padding: 5px 8px;
            background: #1a66d8;
            color: #f3f7ff;
            font-weight: 700;
            font-size: 1rem;
        }

        .review-score-text {
            color: #1d4d94;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .review-note {
            margin: 0;
            color: #2f4f66;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .review-link {
            color: #1f63c5;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .review-link:hover { text-decoration: underline; }

        .guest-reviews-section {
            margin-top: 12px;
        }

        .guest-reviews-layout {
            margin-top: 10px;
            display: grid;
            grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
            gap: 12px;
        }

        .guest-reviews-summary {
            border: 1px solid #d7e6ef;
            border-radius: 12px;
            background: #f8fcff;
            padding: 12px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .guest-score {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .guest-score-badge {
            border-radius: 10px;
            background: #1a66d8;
            color: #f3f7ff;
            font-weight: 700;
            font-size: 1rem;
            padding: 6px 9px;
            min-width: 68px;
            text-align: center;
        }

        .guest-score-label {
            color: #1d4d94;
            font-size: 1rem;
            font-weight: 700;
        }

        .guest-reviews-feed {
            border: 1px solid #d7e6ef;
            border-radius: 12px;
            background: #ffffff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .guest-reviews-feed h3 {
            margin: 0;
            font-size: 1rem;
            color: #204860;
        }

        .guest-review-item {
            border-top: 1px dashed #d8e6ef;
            padding-top: 10px;
            display: grid;
            gap: 6px;
        }

        .guest-review-item:first-of-type {
            border-top: 0;
            padding-top: 0;
        }

        .guest-review-head {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .guest-avatar {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: #dbeaf5;
            color: #1f4f69;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .guest-name {
            font-size: 0.86rem;
            font-weight: 700;
            color: #244b64;
        }

        .guest-date {
            font-size: 0.76rem;
            color: #5d7487;
        }

        .guest-rating {
            font-size: 0.76rem;
            color: #1f63c5;
            font-weight: 600;
        }

        .guest-comment {
            margin: 0;
            color: #35566c;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .guest-review-item.is-hidden {
            display: none;
        }

        .reviews-load-more {
            margin-top: 12px;
            display: flex;
            justify-content: center;
        }

        .reviews-load-btn {
            font-size: 0.82rem;
            padding: 8px 16px;
        }

        .discount-badge {
            display: inline-block;
            background: #e74c3c;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 3px 6px;
            border-radius: 4px;
            margin-top: 2px;
        }

        .room-amenity-icon {
              display: inline-flex;
              align-items: center;
              justify-content: center;
              width: 16px;
              height: 16px;
              flex: 0 0 16px;
              margin-right: 4px;
              color: #3a82aa;
        }

        .room-amenity-icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
            vertical-align: middle;
        }

            .room-amenity-icon i {
                font-size: 0.72rem;
                color: currentColor;
            }

        .room-offer-row.is-hidden {
            display: none;
        }

        .room-offer-expand {
            display: flex;
            justify-content: center;
            margin-top: 8px;
        }

        .room-offer-expand-btn {
            padding: 6px 12px;
            font-size: 0.75rem;
        }

        .room-details-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .room-details-modal-overlay.is-active {
            display: flex;
        }

        .room-details-modal {
            background: #ffffff;
            border-radius: 16px;
            width: min(1180px, calc(100% - 24px));
            max-width: 90%;
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

        .room-details-modal-close:hover {
            color: #000;
        }

        .room-details-content {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.92fr);
            gap: 20px;
            padding: 24px;
            align-items: start;
        }

        .room-details-gallery {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-auto-rows: 156px;
            gap: 12px;
            align-content: start;
        }

        .room-details-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #eef5fb;
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
            border: 1px solid var(--brand-line);
            border-radius: 14px;
            background: var(--brand-soft-2);
        }

        .room-details-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f3d52;
            margin: 0;
        }

        .room-bed-info {
            font-size: 0.9rem;
            color: #2c5169;
            font-weight: 600;
        }

        .room-bed-note {
            margin: 0;
            font-size: 0.82rem;
            color: #51697b;
            line-height: 1.5;
        }

        .room-summary-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .room-summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #d3e4ef;
            border-radius: 999px;
            background: #ffffff;
            color: #264f68;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 5px 9px;
        }

        .room-summary-badge i {
            color: var(--brand);
        }

        .room-details-description {
            margin: 0;
            color: #3f5e72;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .room-meal-plans {
            display: grid;
            gap: 10px;
        }

        .room-section-label {
            margin: 0;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #234b64;
        }

        .room-meal-plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
        }

        .room-meal-plan-card {
            border: 1px solid #d7e6f0;
            border-radius: 8px;
            background: #ffffff;
            padding: 12px 14px;
            display: grid;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(15, 68, 97, 0.06);
            align-items: center;
            justify-items: center;
            text-align: center;
        }

        .room-meal-plan-card.is-primary {
            border-color: #0f6179;
            background: linear-gradient(160deg, #edf6fc 0%, #f7fbff 100%);
            box-shadow: 0 4px 12px rgba(15, 97, 121, 0.15);
        }

        .room-meal-plan-code {
            font-size: 0.75rem;
            font-weight: 800;
            color: #0f6179;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .room-meal-plan-name {
            margin: 0;
            color: #35586e;
            font-size: 0.72rem;
            line-height: 1.3;
            font-weight: 600;
        }

        .room-quick-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 12px;
            border: 1px solid #e0ebf3;
            border-radius: 8px;
            background: #f8fcff;
        }

        .room-quick-spec-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: #2c5169;
        }

        .room-quick-spec-item svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
            flex: 0 0 16px;
        }

        .room-amenities-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            align-items: start;
        }

        .room-amenity-category {
            display: grid;
            gap: 8px;
            padding: 10px;
            border: 1px solid #d9e7f0;
            border-radius: 10px;
            background: #fbfdff;
        }

        .room-amenity-category-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: #0f3d52;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding-bottom: 6px;
            border-bottom: 1px dashed #d9e7f0;
        }

        .room-amenity-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .room-amenity-item {
            list-style: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            color: #2c5169;
            border: 1px solid #dbe8f1;
            border-radius: 999px;
            background: #f6fbff;
            padding: 5px 8px;
            max-width: 100%;
        }

        .room-amenity-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            line-height: 1.25;
        }

        .room-amenity-icon {
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 1px solid #d2e3ee;
            background: #f3f9fd;
            color: #1f5a79;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            flex: 0 0 16px;
        }

        .room-amenity-badge {
            display: inline-block;
            background: #e8f4f8;
            color: #0f6179;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 4px;
            white-space: nowrap;
        }

        .room-policy-stack {
            display: grid;
            gap: 10px;
        }

        .room-policy-section {
            padding: 12px;
            border: 1px solid #e0ebf3;
            border-radius: 8px;
            background: #fffaf5;
        }

        .room-policy-title {
            font-size: 0.68rem;
            font-weight: 700;
            color: #8b4513;
            margin: 0 0 6px 0;
        }

        .room-policy-text {
            font-size: 0.8rem;
            color: #5d5d5d;
            line-height: 1.4;
            margin: 0;
        }

        .room-select-btn {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, #3a7af2 0%, #1f63d0 100%);
            color: #ffffff;
            border: 1px solid #2f6ed8;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.9rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            margin-top: 12px;
        }

        .room-select-btn:hover {
            filter: brightness(1.05);
        }

        @media (max-width: 1080px) {
            .room-details-content {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 16px;
            }

            .room-details-sidebar {
                max-height: none;
                overflow: visible;
                padding-right: 0;
            }

            .room-amenities-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 760px) {
            .room-details-gallery {
                grid-template-columns: 1fr;
                grid-auto-rows: 180px;
            }

            .room-details-gallery img:first-child {
                grid-column: auto;
                grid-row: span 1;
            }

            .room-amenities-grid {
                grid-template-columns: 1fr;
            }

            .room-quick-specs,
            .room-meal-plan-grid {
                grid-template-columns: 1fr;
            }
        }

        .policies-section { margin-top: 20px; }

        .hero-availability {
            margin-top: 12px;
            border: 1px solid rgba(225, 248, 252, 0.45);
            border-radius: 14px;
            background: rgba(6, 70, 87, 0.28);
            padding: 12px 14px;
        }

        .hero-avail-label {
            margin: 0 0 8px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #cfeff4;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .hero-avail-form {
            display: grid;
            grid-template-columns: 1.4fr 130px 130px 70px 70px 70px auto;
            gap: 8px;
            align-items: end;
        }

        .hero-avail-field {
            display: grid;
            gap: 4px;
        }

        .hero-avail-field label {
            font-size: 0.66rem;
            color: #d4f0f6;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .hero-avail-field input {
            width: 100%;
            border: 1px solid rgba(150, 210, 230, 0.6);
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
            color: #103247;
            background: #f8fdff;
            font-size: 0.82rem;
        }

        .hero-avail-btn {
            border: 1px solid #f6d19a;
            background: linear-gradient(135deg, #ffc76f 0%, #f3a337 100%);
            color: #57350b;
            border-radius: 8px;
            padding: 9px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            font-size: 0.82rem;
            align-self: end;
        }

        .hero-avail-btn:hover { filter: brightness(1.04); }

        .hero-transfer-shell {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed rgba(205, 236, 244, 0.45);
            display: grid;
            gap: 8px;
        }

        .hero-transfer-field {
            display: grid;
            gap: 4px;
            max-width: 320px;
        }

        .hero-transfer-field label {
            font-size: 0.66rem;
            color: #d4f0f6;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .hero-transfer-select {
            width: 100%;
            border: 1px solid rgba(150, 210, 230, 0.6);
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
            color: #103247;
            background: #f8fdff;
            font-size: 0.82rem;
        }

        .hero-transfer-summary {
            margin: 0;
            color: #d4f0f6;
            font-size: 0.78rem;
        }

        .transfer-policy-grid {
            margin-top: 10px;
            display: grid;
            gap: 12px;
        }

        .transfer-policy-intro {
            margin: 0;
            color: #46657c;
            font-size: 0.86rem;
            line-height: 1.6;
        }

        .transfer-rate-card {
            border: 1px solid #dbe7f0;
            border-radius: 14px;
            background: #fbfdff;
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .transfer-rate-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .transfer-rate-title {
            margin: 0;
            font-size: 1rem;
            color: #183e58;
        }

        .transfer-rate-note {
            margin: 4px 0 0;
            color: #5a7488;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .transfer-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cfe0eb;
            border-radius: 999px;
            padding: 6px 10px;
            background: #edf6f3;
            color: #24516b;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .transfer-rate-table {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .transfer-rate-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .transfer-rate-box {
            border: 1px solid #dbe7f0;
            border-radius: 12px;
            background: #f7fbff;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .transfer-rate-box h3 {
            margin: 0;
            font-size: 0.86rem;
            color: #214964;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .transfer-rate-line {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            color: #36586d;
            font-size: 0.84rem;
        }

        .transfer-rate-table-compact {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            color: #214964;
        }

        .transfer-rate-table-compact thead th {
            text-align: left;
            font-size: 0.74rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #577589;
            padding: 0 0 6px;
            border-bottom: 1px solid #d7e5f0;
        }

        .transfer-rate-table-compact tbody td {
            padding: 9px 0;
            border-bottom: 1px solid #e3edf5;
            vertical-align: middle;
        }

        .transfer-rate-table-compact tbody tr:last-child td {
            border-bottom: none;
            padding-bottom: 2px;
        }

        .transfer-mode {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #244a62;
            font-weight: 600;
        }

        .transfer-mode .icon {
            width: 18px;
            text-align: center;
            color: #2f6b89;
        }

        .transfer-rate-amount {
            color: #123b56;
            font-weight: 700;
            white-space: nowrap;
        }

        .transfer-base-note {
            margin: 4px 0 0;
            font-size: 0.78rem;
            color: #5a7488;
        }

        .transfer-policy-summary {
            margin: 0;
            color: #567185;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .policies-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 10px;
        }

        .policy-label {
            color: #214964;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .policy-value {
            color: #36586d;
            font-size: 0.85rem;
            line-height: 1.5;
            border-bottom: 1px dashed #d7e6ef;
            padding-bottom: 8px;
        }

        .surroundings-card {
            border-top: 1px solid #dce9f2;
            padding-top: 10px;
            display: grid;
            gap: 8px;
        }

        .surroundings-title {
            margin: 0;
            font-size: 0.97rem;
            color: #204860;
        }

        .surroundings-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 7px;
        }

        .surroundings-list li {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            color: #35566c;
            font-size: 0.84rem;
            line-height: 1.4;
        }

        .surroundings-list li::before {
            content: 'o';
            border: 1px solid #9cbfd3;
            width: 15px;
            height: 15px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.56rem;
            color: #2a607f;
            margin-top: 1px;
            flex: 0 0 15px;
        }

        .rooms-section { margin-top: 12px; }

        .nearby-properties-section {
            margin-top: 12px;
        }

        .nearby-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nearby-head h2 {
            margin: 0;
        }

        .nearby-radius-controls {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .nearby-radius-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 30px;
            min-width: 56px;
            padding: 0 10px;
            border: 1px solid #c8dceb;
            border-radius: 999px;
            background: #f7fbfe;
            color: #21506b;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
        }

        .nearby-radius-chip:hover {
            background: #edf7fc;
        }

        .nearby-radius-chip.is-active {
            border-color: #0f6179;
            background: #0f6179;
            color: #ffffff;
        }

        .nearby-empty {
            margin-top: 10px;
            border: 1px solid #d5e7f2;
            border-radius: 12px;
            background: #f8fcff;
            color: #345a73;
            padding: 12px;
            font-size: 0.82rem;
            line-height: 1.45;
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
            color: #4f6678;
            flex-wrap: wrap;
        }

        .nearby-price {
            font-weight: 700;
            color: #10344b;
        }
        .rooms-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .rooms-sub { margin: 0; color: #5d7487; font-size: 0.83rem; }

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

        .guest-reviews-section h2 {
            padding-top: 0;
            padding-bottom: 14px;
        }

        .guest-reviews-section,
        .location-section,
        .policies-section {
            border: 1px solid #d4e5ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: start;
        }

        .guest-reviews-section h2,
        .location-section h2,
        .policies-section h2 {
            margin: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        .location-section {
            margin-top: 12px;
        }

        .location-layout {
            margin-top: 10px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 0.95fr);
            gap: 12px;
            align-items: start;
        }

        .location-copy {
            color: #37566b;
            font-size: 0.9rem;
            line-height: 1.6;
            display: grid;
            gap: 8px;
        }

        .location-map {
            border: 1px solid #c7dbea;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(145deg, #f7fbff 0%, #edf5fb 100%);
            min-height: 320px;
            box-shadow: 0 10px 26px rgba(22, 58, 84, 0.12);
            padding: 6px;
        }

        #property-location-map {
            width: 100%;
            height: 100%;
            min-height: 308px;
            border-radius: 10px;
        }

        .location-map-caption {
            color: #3f5c73;
            font-size: 0.82rem;
            margin-top: 10px;
            font-weight: 600;
        }

        .rooms-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .room-card {
            border: 1px solid #dbe7f0;
            border-radius: 13px;
            overflow: hidden;
            background: #fbfdff;
            display: grid;
            grid-template-columns: minmax(230px, 260px) minmax(0, 1fr);
            align-items: stretch;
        }

        .room-card.is-cheapest {
            border-color: #7fb5d4;
            box-shadow: 0 0 0 2px rgba(36, 116, 156, 0.18);
        }

        .room-media-link {
            display: block;
            height: 100%;
        }

        .room-media {
            position: relative;
            background: linear-gradient(135deg, #d9ebf4 0%, #f0f7fc 100%);
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

        .room-tag i {
            margin-right: 5px;
            font-size: 0.7rem;
        }

        .room-body { padding: 12px; display: grid; gap: 10px; }
        .room-body h3 { margin: 0; font-size: 1rem; color: #153f59; }

        .room-name-link {
            color: #153f59;
            text-decoration: none;
        }

        .room-name-link:hover { text-decoration: underline; }

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

        .room-choices {
            display: grid;
            gap: 5px;
            color: #2c5169;
            font-size: 0.8rem;
        }

        .room-choices span::before {
            content: '•';
            margin-right: 6px;
            color: #1b72aa;
            font-weight: 700;
        }

        .room-option-title {
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            color: #0f6179 !important;
        }

        .room-option-title::before {
            content: '' !important;
            margin-right: 0 !important;
        }

        .room-option-subtitle {
            font-size: 0.78rem !important;
            color: #5a7a8a !important;
        }

        .room-option-subtitle::before {
            content: '' !important;
            margin-right: 0 !important;
        }

        .room-plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
            border: 1px solid #d6e5ef;
            border-radius: 999px;
            background: #f5fbff;
            color: #204b64;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
        }

        .room-plan-badge::before {
            content: '' !important;
            margin-right: 0 !important;
        }

        .room-breakfast-highlight {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
        }

        .room-breakfast-highlight::before {
            content: '' !important;
            margin-right: 0 !important;
        }

        .room-breakfast-highlight.is-included {
            border: 1px solid #9fd3bc;
            background: #eaf8f0;
            color: #1f6d4d;
        }

        .room-breakfast-highlight.is-not-included {
            border: 1px solid #ecd2a8;
            background: #fff7eb;
            color: #8a5d1e;
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

        .room-sleeps-icons .fa-user {
            font-size: 0.84rem;
        }

        .room-sleeps-icons .room-sleeps-child {
            font-size: 0.7rem;
            opacity: 0.95;
        }

        .room-occupancy-inline {
            display: inline-flex;
            align-items: flex-end;
            gap: 2px;
            color: #1f6f95;
            margin: 0 4px;
        }

        .room-occupancy-inline .fa-user {
            font-size: 0.84rem;
        }

        .room-occupancy-inline .room-sleeps-child {
            font-size: 0.7rem;
            opacity: 0.95;
        }

        .room-price-box {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .room-price-old {
            color: #6b8294;
            font-size: 0.74rem;
            text-decoration: line-through;
        }

        .room-price-now {
            font-size: 1.55rem;
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

        .room-soldout-badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid #f0b7b7;
            border-radius: 999px;
            background: #fff2f2;
            color: #a53a3a;
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            margin-top: 6px;
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

        .muted { color: var(--muted); font-size: 0.8rem; line-height: 1.35; }

        @media (max-width: 1080px) {
            .top-search-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .top-search-btn {
                grid-column: 1 / -1;
                width: 100%;
            }

            .property-summary-shell {
                grid-template-columns: 1fr;
            }

            .property-summary-price {
                justify-items: start;
                text-align: left;
            }

            .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }

            body.is-header-hidden .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }

            .hero-avail-form { grid-template-columns: 1fr 120px 120px 65px 65px auto; }
            .layout { grid-template-columns: 1fr; }
            .info-section { grid-template-columns: 1fr; }
            .guest-reviews-layout { grid-template-columns: 1fr; }
            .highlights-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .gallery-shell { grid-template-columns: 1fr; }
            .gallery-thumbs {
                grid-template-columns: repeat(6, minmax(0, 1fr));
                max-height: none;
            }
            .amenities-columns { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .facility-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .room-card { grid-template-columns: minmax(220px, 260px) minmax(0, 1fr); }
            .room-offer-head,
            .room-offer-row { grid-template-columns: minmax(0, 1fr) 76px minmax(170px, 0.9fr); }
            .policies-grid { grid-template-columns: 1fr; }
            .transfer-rate-table { grid-template-columns: 1fr; }
            .transfer-rate-grid { grid-template-columns: 1fr; }
            .nearby-grid { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
            .location-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 680px) {
            .top-search-shell {
                padding: 8px;
            }

            body.is-header-hidden .top-search-shell {
                top: var(--property-header-offset);
            }

            .top-search-form {
                grid-template-columns: 1fr;
            }

            .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            body.is-header-hidden .section-tabs {
                top: calc(var(--property-header-offset) + var(--property-search-shell-height));
            }

            .section-tab {
                flex: 0 0 auto;
            }

            .hero-avail-form { grid-template-columns: 1fr; }
            .hero-avail-btn { grid-column: 1 / -1; width: 100%; }
            .hero-stats { grid-template-columns: 1fr; }
            .address-bar { flex-direction: column; align-items: flex-start; }
            .hero-cta-wrap { width: 100%; justify-content: space-between; }
            .gallery-banner-wrap,
            .gallery-banner,
            .gallery-banner-placeholder {
                min-height: 230px;
            }
            .gallery-thumbs { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .highlights-grid { grid-template-columns: 1fr; }
            .amenities-columns { grid-template-columns: 1fr; }
            .transfer-rate-head { flex-direction: column; }
            .room-card { grid-template-columns: 1fr; }
            .room-media { min-height: 210px; }
            .room-body { padding: 10px; }
            .room-offer-head,
            .room-offer-row { grid-template-columns: 1fr; }
            .room-offer-head span,
            .room-offer-row > div { border-right: 0; }
            .room-price-box { grid-template-columns: 1fr; }
            .room-side-details li { align-items: flex-start; }
            .facility-grid { grid-template-columns: 1fr; }
            .nearby-grid { grid-template-columns: 1fr; }
            .nearby-card { grid-template-rows: 176px auto; }
            .nearby-card-media { height: 176px; }
            .nearby-card-body { padding: 12px; }

            .location-map {
                min-height: 260px;
                padding: 5px;
            }

            #property-location-map {
                min-height: 248px;
            }
        }

        /* Uniform Icon System Styles - Consistent across all pages */
        .uniform-icon {
            display: inline-block;
            font-size: 1em;
            line-height: 1;
            margin: 0;
            padding: 0;
            vertical-align: middle;
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
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $headerCategoryKey = str_replace('_', '-', strtolower(trim((string) ($property->listing_category ?? 'accommodation'))));
        $headerCategoryLinks = [
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
        ];
    @endphp

    @include('partials.customer-uniform-header', [
        'injectUniformHeaderStyles' => true,
        'injectUniformHeaderScripts' => true,
        'headerNeedsSpacer' => false,
        'headerHideOnScroll' => true,
        'headerShowSearch' => false,
        'headerSearchAction' => '/catalog/' . $headerCategoryKey,
        'headerSearchValue' => '',
        'headerCategoryLinks' => $headerCategoryLinks,
        'headerActiveCategoryKey' => $headerCategoryKey,
        'headerContinueUrl' => (string) request()->fullUrl(),
    ])

    @php
        $propertyMedia = $propertyMedia ?? collect();
        $rooms = $rooms ?? collect();
        $roomMediaByRoom = $roomMediaByRoom ?? collect();
        $propertyFacilities = $propertyFacilities ?? collect();
        $nearbyRadiusKm = (float) ($nearbyRadiusKm ?? 25);
        if (!is_finite($nearbyRadiusKm) || $nearbyRadiusKm <= 0) {
            $nearbyRadiusKm = 25;
        }
        $nearbyUsesCoordinateRadius = (bool) ($nearbyUsesCoordinateRadius ?? false);
        $nearbyRadiusLabel = rtrim(rtrim(number_format($nearbyRadiusKm, 1), '0'), '.');
        $nearbyRadiusOptions = collect([5, 10, 25, 50]);
        if (!$nearbyRadiusOptions->contains((int) round($nearbyRadiusKm))) {
            $nearbyRadiusOptions->push((int) round($nearbyRadiusKm));
        }
        $nearbyRadiusOptions = $nearbyRadiusOptions->unique()->sort()->values();
        $nearbyQueryBase = request()->query();
        unset($nearbyQueryBase['nearby_radius_km']);
        $nearbySectionVisible = $nearbyProperties->isNotEmpty() || $nearbyUsesCoordinateRadius;
        $nearbyProperties = collect($nearbyProperties ?? [])->map(static function ($item) {
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
        $guestReviews = collect($guestReviews ?? [])->map(static function ($review) {
            if (is_array($review)) {
                return $review;
            }

            if (is_object($review)) {
                return [
                    'name' => (string) ($review->name ?? ''),
                    'comment' => (string) ($review->comment ?? ''),
                    'rating' => (float) ($review->rating ?? 0),
                    'date' => (string) ($review->date ?? ''),
                ];
            }

            return [];
        })->filter(static fn ($review) => trim((string) ($review['comment'] ?? '')) !== '')->values();
        $locationLine = trim((string) ($locationLine ?? ''));
        $ratingValue = (float) ($ratingValue ?? 0);
        $ratingUsers = (int) ($ratingUsers ?? 0);
        $prefill = $prefill ?? ['checkin' => '', 'checkout' => '', 'rooms' => 1, 'adults' => 2, 'children' => 0];
        $prefillCheckin = trim((string) ($prefill['checkin'] ?? ''));
        $prefillCheckout = trim((string) ($prefill['checkout'] ?? ''));
        $prefillAdults = max(1, (int) ($prefill['adults'] ?? 2));
        $prefillChildren = max(0, (int) ($prefill['children'] ?? 0));
        $prefillRooms = max(1, (int) ($prefill['rooms'] ?? 1));
        $prefillStayNights = 1;
        if ($prefillCheckin !== '' && $prefillCheckout !== '') {
            try {
                $prefillStayNights = max(1, \Carbon\Carbon::parse($prefillCheckin)->diffInDays(\Carbon\Carbon::parse($prefillCheckout)));
            } catch (\Throwable $e) {
                $prefillStayNights = 1;
            }
        }
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
        $basePriceNumeric = (float) ($property->base_price ?? 0);
        if ($basePriceNumeric <= 0 && function_exists('workationDerivedListingBasePrice')) {
            $basePriceNumeric = (float) workationDerivedListingBasePrice($property);
        }
        $basePrice = number_format(max(0, $basePriceNumeric), 2);
        $propertyDetails = [];
        if (isset($property->listing_details)) {
            $decodedPropertyDetails = json_decode((string) ($property->listing_details ?? ''), true);
            if (is_array($decodedPropertyDetails)) {
                $propertyDetails = $decodedPropertyDetails;
            }
        }
        $description = trim((string) ($property->description ?? ''));
        $listingCategory = strtoupper(str_replace('_', ' ', (string) ($property->listing_category ?? 'ACCOMMODATION')));
        $rawPropertyType = trim((string) (
            $propertyDetails['property_type']
            ?? $propertyDetails['accommodation_type']
            ?? $property->property_type
            ?? $property->type
            ?? ''
        ));
        if ($rawPropertyType === '' && strtolower(trim((string) ($property->listing_category ?? ''))) === 'accommodation') {
            $rawPropertyType = 'accommodation';
        }
        $propertyTypeKey = strtolower(str_replace(['-', ' '], '_', $rawPropertyType));
        $propertyTypeMap = [
            'hotel' => 'Hotel',
            'resort' => 'Resort',
            'guest_house' => 'Guest House',
            'guesthouse' => 'Guest House',
            'service_apartment' => 'Service Apartment',
            'serviced_apartment' => 'Service Apartment',
            'apartment' => 'Apartment',
            'accommodation' => 'Accommodation',
        ];
        $propertyTypeLabel = $propertyTypeMap[$propertyTypeKey] ?? ($rawPropertyType !== ''
            ? ucwords(str_replace('_', ' ', $propertyTypeKey))
            : '');
        $starCount = $ratingValue > 0 ? max(1, min(5, (int) round($ratingValue))) : 4;
        $starString = str_repeat('★', $starCount) . str_repeat('☆', 5 - $starCount);
        $ratingOutOfTen = $ratingValue > 0 ? min(10, $ratingValue * 2) : 0;
        $reviewLabel = $ratingOutOfTen >= 9.0 ? 'Excellent' : ($ratingOutOfTen >= 8.0 ? 'Great' : ($ratingOutOfTen > 0 ? 'Good' : 'No rating yet'));
        $detailsMapLat = $propertyDetails['map_latitude'] ?? $propertyDetails['latitude'] ?? $propertyDetails['lat'] ?? $propertyDetails['location_lat'] ?? $propertyDetails['geo_lat'] ?? null;
        $detailsMapLng = $propertyDetails['map_longitude'] ?? $propertyDetails['longitude'] ?? $propertyDetails['lng'] ?? $propertyDetails['location_lng'] ?? $propertyDetails['geo_lng'] ?? null;
        $rawMapLat = $detailsMapLat ?? $property->map_latitude ?? $property->latitude ?? $property->lat ?? $property->location_lat ?? $property->geo_lat ?? null;
        $rawMapLng = $detailsMapLng ?? $property->map_longitude ?? $property->longitude ?? $property->lng ?? $property->location_lng ?? $property->geo_lng ?? null;
        $mapLat = is_numeric($rawMapLat) ? (float) $rawMapLat : null;
        $mapLng = is_numeric($rawMapLng) ? (float) $rawMapLng : null;
        $hasExactCoordinates = $mapLat !== null && $mapLng !== null && $mapLat >= -90 && $mapLat <= 90 && $mapLng >= -180 && $mapLng <= 180;
        $mapQueryParts = array_values(array_filter([
            trim((string) ($property->name ?? '')),
            $locationLine,
            'Maldives',
        ], static fn ($value) => trim((string) $value) !== ''));
        $mapQuery = implode(', ', $mapQueryParts);
        if ($mapQuery === '') {
            $mapQuery = 'Maldives';
        }
        $mapUrl = $hasExactCoordinates
            ? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($mapLat . ',' . $mapLng))
            : ('https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery));
        $pricedRooms = $rooms->filter(static function ($room): bool {
            $value = $room->base_price_per_night ?? ($room->base_price ?? null);
            return is_numeric($value) && (float) $value > 0;
        });

        $cheapestPricedRoom = $pricedRooms
            ->sortBy(static fn ($room) => (float) ($room->base_price_per_night ?? ($room->base_price ?? INF)))
            ->first();

        $cheapestRoom = $cheapestPricedRoom
            ?? $rooms->sortBy(static fn ($room) => (float) ($room->base_price_per_night ?? ($room->base_price ?? INF)))->first();

        $cheapestRoomId = $cheapestRoom ? (int) ($cheapestRoom->id ?? 0) : 0;
        $cheapestRoomPrice = $cheapestPricedRoom ? number_format((float) ($cheapestPricedRoom->base_price_per_night ?? ($cheapestPricedRoom->base_price ?? 0)), 2) : null;
        $startingPriceValue = $cheapestPricedRoom
            ? (float) ($cheapestPricedRoom->base_price_per_night ?? ($cheapestPricedRoom->base_price ?? 0))
            : 0;
        $startingPriceCurrency = strtoupper(trim((string) ($cheapestPricedRoom->currency ?? $property->currency ?? 'MVR')));
        if ($startingPriceCurrency === '') {
            $startingPriceCurrency = 'MVR';
        }
        $startingPrice = number_format($startingPriceValue, 2);
        $startingTotalPrice = number_format($startingPriceValue * $prefillStayNights, 2);
        $displayFromCurrency = $startingPriceValue > 0 ? $startingPriceCurrency : $currency;
        $displayFromPrice = $startingPriceValue > 0 ? $startingPrice : $basePrice;
        $hasBookableRate = $pricedRooms->isNotEmpty();
        $selectRoomsTarget = $cheapestRoomId > 0 ? ('#room-' . $cheapestRoomId) : '#rooms-section';
        $transferOptionCatalog = [
            'speedboat' => 'Speedboat transfer',
            'domestic_flight' => 'Domestic flight transfer',
            'seaplane' => 'Seaplane transfer',
            'ferry' => 'Public ferry transfer',
            'private_transfer' => 'Private transfer',
        ];
        $transferOptions = collect(is_array($propertyDetails['transfer_options'] ?? null) ? $propertyDetails['transfer_options'] : [])
            ->map(static function ($item) {
                if (is_array($item)) {
                    return strtolower(trim((string) ($item['code'] ?? '')));
                }

                return strtolower(trim((string) $item));
            })
            ->filter(static fn ($item) => $item !== '')
            ->unique()
            ->values();
        $transferRates = is_array($propertyDetails['transfer_rates'] ?? null) ? $propertyDetails['transfer_rates'] : [];
        $transferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null) ? $propertyDetails['transfer_rate_matrix'] : [];
        $transferModeKeys = $transferOptions
            ->merge(collect(array_keys($transferRates))->map(static fn ($item) => strtolower(trim((string) $item))))
            ->merge(collect(array_keys($transferRateMatrix))->map(static fn ($item) => strtolower(trim((string) $item))))
            ->filter(static fn ($item) => $item !== '')
            ->unique()
            ->values();

        $transferChoices = $transferModeKeys
            ->map(static function (string $transferKey) use ($transferOptionCatalog, $transferRates, $transferRateMatrix) {
                $matrix = is_array($transferRateMatrix[$transferKey] ?? null)
                    ? $transferRateMatrix[$transferKey]
                    : [];
                $fallbackRate = is_numeric($transferRates[$transferKey] ?? null) ? (float) $transferRates[$transferKey] : 0.0;
                $localAdultRate = is_numeric($matrix['local_adult_charge'] ?? null)
                    ? (float) $matrix['local_adult_charge']
                    : 0.0;
                $localChildRate = is_numeric($matrix['local_child_charge'] ?? null)
                    ? (float) $matrix['local_child_charge']
                    : 0.0;
                $foreignAdultRate = is_numeric($matrix['foreign_adult_charge'] ?? null)
                    ? (float) $matrix['foreign_adult_charge']
                    : $fallbackRate;
                $foreignChildRate = is_numeric($matrix['foreign_child_charge'] ?? null)
                    ? (float) $matrix['foreign_child_charge']
                    : 0.0;
                $baseLocalRate = is_numeric($matrix['base_charge_local'] ?? null)
                    ? (float) $matrix['base_charge_local']
                    : 0.0;
                $baseForeignRate = is_numeric($matrix['base_charge_foreign'] ?? null)
                    ? (float) $matrix['base_charge_foreign']
                    : 0.0;

                return [
                    'key' => $transferKey,
                    'label' => $transferOptionCatalog[$transferKey] ?? ucwords(str_replace('_', ' ', $transferKey)),
                    'local_adult_rate' => max(0, $localAdultRate),
                    'local_child_rate' => max(0, $localChildRate),
                    'foreign_adult_rate' => max(0, $foreignAdultRate),
                    'foreign_child_rate' => max(0, $foreignChildRate),
                    'base_charge_local' => max(0, $baseLocalRate),
                    'base_charge_foreign' => max(0, $baseForeignRate),
                    'adult_rate' => max(0, $foreignAdultRate),
                    'child_rate' => max(0, $foreignChildRate),
                ];
            })
            ->values();
        $transferModeIconCatalog = [
            'speedboat' => 'fa-solid fa-ship',
            'shared_speedboat' => 'fa-solid fa-ship',
            'private_speedboat' => 'fa-solid fa-ship',
            'domestic_flight' => 'fa-solid fa-plane-departure',
            'domestic-plane' => 'fa-solid fa-plane-departure',
            'seaplane' => 'fa-solid fa-plane',
            'ferry' => 'fa-solid fa-ferry',
            'public_ferry' => 'fa-solid fa-ferry',
            'private_transfer' => 'fa-solid fa-car-side',
            'airport_pickup' => 'fa-solid fa-car-side',
            'airport_dropoff' => 'fa-solid fa-car-side',
            'airport_roundtrip' => 'fa-solid fa-car-side',
            'inter_island' => 'fa-solid fa-route',
            'resort_shuttle' => 'fa-solid fa-van-shuttle',
        ];
        $resolveTransferModeIcon = static function (string $modeKey) use ($transferModeIconCatalog): string {
            $normalized = strtolower(trim($modeKey));
            if ($normalized !== '' && isset($transferModeIconCatalog[$normalized])) {
                return (string) $transferModeIconCatalog[$normalized];
            }

            if (str_contains($normalized, 'speedboat') || str_contains($normalized, 'boat')) {
                return 'fa-solid fa-ship';
            }
            if (str_contains($normalized, 'seaplane') || str_contains($normalized, 'flight') || str_contains($normalized, 'plane')) {
                return 'fa-solid fa-plane-departure';
            }
            if (str_contains($normalized, 'ferry')) {
                return 'fa-solid fa-ferry';
            }
            if (str_contains($normalized, 'shuttle') || str_contains($normalized, 'airport') || str_contains($normalized, 'transfer')) {
                return 'fa-solid fa-car-side';
            }

            return 'fa-solid fa-route';
        };
        $transferBaseLocal = max(0, (float) ($propertyDetails['transfer_base_local'] ?? 0));
        $transferBaseForeign = max(0, (float) ($propertyDetails['transfer_base_foreign'] ?? 0));
        $shareUrl = url()->current();
        $shareText = trim((string) ($property->name ?? 'Property')) . ' on Workation';
        $shareEncodedText = urlencode($shareText . ' ' . $shareUrl);
        $shareEncodedUrl = urlencode($shareUrl);
        $surroundings = collect(array_filter(array_map('trim', explode(',', $locationLine))))
            ->take(3)
            ->map(static fn ($segment) => 'Nearby: ' . $segment)
            ->values();
        if ($surroundings->isEmpty()) {
            $surroundings = collect([
                'Nearby dining, shopping, and local experiences',
                'Easy access routes for guest transport options',
                'Good base for exploring nearby attractions',
            ]);
        }
        $highlights = $propertyFacilities->take(6)->values();
        $amenityCategory = static function (string $facility): string {
            $value = strtolower($facility);
            if (str_contains($value, 'restaurant') || str_contains($value, 'bar') || str_contains($value, 'drink') || str_contains($value, 'breakfast')) {
                return 'Food & Drink';
            }
            if (str_contains($value, 'pool') || str_contains($value, 'spa') || str_contains($value, 'massage') || str_contains($value, 'wellness') || str_contains($value, 'sauna') || str_contains($value, 'fitness') || str_contains($value, 'gym') || str_contains($value, 'water')) {
                return 'Health & Wellness';
            }
            if (str_contains($value, 'wifi') || str_contains($value, 'internet') || str_contains($value, 'business') || str_contains($value, 'front desk') || str_contains($value, 'currency')) {
                return 'Services';
            }
            if (str_contains($value, 'park') || str_contains($value, 'airport') || str_contains($value, 'taxi') || str_contains($value, 'transport')) {
                return 'Transport & Parking';
            }
            if (str_contains($value, 'child') || str_contains($value, 'kids') || str_contains($value, 'play')) {
                return 'Family';
            }

            return 'More Amenities';
        };

        $amenityBuckets = collect([
            'Most Popular' => collect(),
            'Food & Drink' => collect(),
            'Health & Wellness' => collect(),
            'Services' => collect(),
            'Transport & Parking' => collect(),
            'Family' => collect(),
            'More Amenities' => collect(),
        ]);

        foreach ($propertyFacilities as $facility) {
            $facilityText = trim((string) $facility);
            if ($facilityText === '') {
                continue;
            }

            $category = $amenityCategory($facilityText);

            if ($amenityBuckets->has($category)) {
                $amenityBuckets->put($category, $amenityBuckets->get($category)->push($facilityText));
            }
        }

        if ($amenityBuckets->get('Most Popular')->isEmpty()) {
            $amenityBuckets->put('Most Popular', $propertyFacilities->take(8)->map(static fn ($item) => trim((string) $item))->filter()->values());
        }

        $orderedAmenityGroups = collect([
            'Most Popular',
            'Food & Drink',
            'Health & Wellness',
            'Services',
            'Transport & Parking',
            'Family',
            'More Amenities',
        ])->map(static fn ($name) => [
            'name' => $name,
            'items' => $amenityBuckets->get($name, collect())->unique()->take($name === 'Most Popular' ? 8 : 10)->values(),
        ])->filter(static fn ($group) => collect($group['items'])->isNotEmpty())->values();
        $amenityDisplay = static function (string $amenity): string {
            $value = trim($amenity);
            if ($value === '') {
                return 'Amenity';
            }

            $value = preg_replace('/[_-]+/', ' ', $value) ?? $value;
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
            $value = trim($value);
            $value = ucwords(strtolower($value));

            $value = str_ireplace('Wifi', 'Wi-Fi', $value);
            $value = str_ireplace('Tv', 'TV', $value);
            $value = str_ireplace('Ac ', 'AC ', $value);

            return $value;
        };
        
        // Use uniform icon system for consistency across the application
        $facilitySvg = static function (string $facility): string {
            $iconClass = \App\Support\UniformIconSystem::getAmenityIcon($facility);
            return '<i class="' . htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
        };
    @endphp

    <section class="top-search-shell" aria-label="Search property stay options">
        <div class="top-search-inner">
            <form method="GET" action="" class="top-search-form" id="propertyTopSearch">
                <div class="top-search-field">
                    <label for="topProperty">Location</label>
                    <input id="topProperty" type="text" name="property_name" value="{{ (string) ($property->name ?? '') }}" readonly>
                </div>
                <div class="top-search-field">
                    <label for="topCheckin">Check-in</label>
                    <input id="topCheckin" type="date" name="checkin" min="{{ (string) ($todayDate ?? now()->toDateString()) }}" value="{{ (string) ($prefill['checkin'] ?? '') }}">
                </div>
                <div class="top-search-field">
                    <label for="topCheckout">Check-out</label>
                    <input id="topCheckout" type="date" name="checkout" min="{{ (string) ($todayDate ?? now()->toDateString()) }}" value="{{ (string) ($prefill['checkout'] ?? '') }}">
                </div>
                <div class="top-search-field">
                    <label for="topGuests">Guests</label>
                    <input id="topGuests" type="text" value="{{ (int) ($prefill['adults'] ?? 2) }} adults, {{ (int) ($prefill['children'] ?? 0) }} children, {{ (int) ($prefill['rooms'] ?? 1) }} room" readonly>
                    <input type="hidden" name="rooms" value="{{ (int) ($prefill['rooms'] ?? 1) }}">
                    <input type="hidden" name="adults" value="{{ (int) ($prefill['adults'] ?? 2) }}">
                    <input type="hidden" name="children" value="{{ (int) ($prefill['children'] ?? 0) }}">
                </div>
                <button type="submit" class="top-search-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
            </form>
        </div>
    </section>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span aria-hidden="true">›</span>
            <a href="/catalog/accommodation">Accommodation</a>
            <span aria-hidden="true">›</span>
            <span>{{ (string) ($property->name ?? 'Property') }}</span>
        </nav>

        <section class="hero" aria-label="Property summary">
            <div class="hero-top">
                <div>
                    <div class="hero-title-row">
                        <h1>{{ (string) ($property->name ?? 'Property') }}</h1>
                        <span class="hero-stars" aria-label="Property stars">{{ $starString }}</span>
                    </div>
                        @if ($propertyTypeLabel !== '')
                            <span class="property-type-badge" style="margin-top:6px;display:inline-flex;">
                                <i class="fa-solid fa-hotel" aria-hidden="true"></i>{{ $propertyTypeLabel }}
                            </span>
                        @endif
                    <p class="sub">{{ $locationLine !== '' ? $locationLine : 'Address details will be updated shortly.' }}</p>
                </div>
                <div class="hero-cta-wrap">
                    <div class="hero-rating">
                        {{ $ratingOutOfTen > 0 ? (number_format($ratingOutOfTen, 1) . ' / 10') : 'No rating yet' }}{{ $ratingUsers > 0 ? (' • ' . $ratingUsers . ' reviews') : '' }}
                    </div>
                    <a class="select-rooms-btn" href="{{ $selectRoomsTarget }}">Select Rooms{{ $cheapestRoomPrice ? (' from ' . $currency . ' ' . $cheapestRoomPrice) : '' }}</a>
                </div>
            </div>

            <div class="address-bar" aria-label="Property address and price">
                <div class="address-main">
                    <span>{{ $locationLine !== '' ? $locationLine : 'Address details will be updated shortly.' }}</span>
                    <a class="map-link" href="{{ $mapUrl }}" target="_blank" rel="noopener">Show on map</a>
                </div>
                <span class="price-chip">From {{ $displayFromCurrency }} {{ $displayFromPrice }} / night</span>
            </div>

            <div class="hero-stats">
                <div class="hero-stat"><div class="k">Category</div><div class="v">{{ $listingCategory }}</div></div>
                <div class="hero-stat"><div class="k">Starting Price</div><div class="v">{{ $displayFromCurrency }} {{ $displayFromPrice }}</div></div>
                <div class="hero-stat"><div class="k">Available Rooms</div><div class="v">{{ $rooms->count() }}</div></div>
            </div>

            <div class="hero-availability" aria-label="Check availability">
                <p class="hero-avail-label">Check Availability</p>
                <form method="GET" action="" class="hero-avail-form" id="propertyAvailabilityForm">
                    <div class="hero-avail-field">
                        <label for="availProperty">Property</label>
                        <input id="availProperty" type="text" name="property_name" value="{{ (string) ($property->name ?? '') }}" readonly style="cursor:default;">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availCheckin">Check-in</label>
                        <input id="availCheckin" type="date" name="checkin" min="{{ (string) ($todayDate ?? now()->toDateString()) }}" value="{{ (string) ($prefill['checkin'] ?? '') }}">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availCheckout">Check-out</label>
                        <input id="availCheckout" type="date" name="checkout" min="{{ (string) ($todayDate ?? now()->toDateString()) }}" value="{{ (string) ($prefill['checkout'] ?? '') }}">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availRooms">Rooms</label>
                        <input id="availRooms" type="number" name="rooms" min="1" value="{{ (int) ($prefill['rooms'] ?? 1) }}">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availAdults">Adults</label>
                        <input id="availAdults" type="number" name="adults" min="1" value="{{ (int) ($prefill['adults'] ?? 2) }}">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availChildren">Children</label>
                        <input id="availChildren" type="number" name="children" min="0" value="{{ (int) ($prefill['children'] ?? 0) }}">
                    </div>
                    <button type="submit" class="hero-avail-btn">Search</button>
                </form>
                <p class="wizard-note" data-availability-date-error style="display:none; margin:10px 0 0; color:#8b1f2f; font-weight:600;"></p>
                @if ($transferChoices->isNotEmpty())
                    <div class="hero-transfer-shell" aria-label="Transfer options">
                        <div class="hero-transfer-field">
                            <label for="transferOptionSelect">Transfer Option</label>
                            <span id="transferOptionIcon" class="transfer-pill" style="align-self:flex-start;"><i class="fa-solid fa-route" aria-hidden="true"></i> Mode icon</span>
                            <select id="transferOptionSelect" class="hero-transfer-select">
                                <option value="">No transfer</option>
                                @foreach ($transferChoices as $transferChoice)
                                    @php
                                        $transferChoiceIcon = $resolveTransferModeIcon((string) ($transferChoice['key'] ?? ''));
                                    @endphp
                                    <option
                                        value="{{ $transferChoice['key'] }}"
                                        data-adult-rate="{{ number_format((float) ($transferChoice['adult_rate'] ?? 0), 2, '.', '') }}"
                                        data-child-rate="{{ number_format((float) ($transferChoice['child_rate'] ?? 0), 2, '.', '') }}"
                                        data-icon-class="{{ $transferChoiceIcon }}"
                                    >
                                        {{ $transferChoice['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="hero-transfer-summary" data-transfer-summary>
                            Optional transfer can be added to room reservation. Full local and foreigner fare details are listed in the Transfer Policy &amp; Rates section below.
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <div class="layout">
                <section id="property-gallery-section" class="section" aria-label="Property gallery">
                    <h2>Property Gallery</h2>
                    @php
                        $galleryItems = $propertyMedia->take(12)->values();
                        $initialBanner = $galleryItems->isNotEmpty() ? ($mediaUrl($galleryItems->first(), 'banner') ?? '') : '';
                    @endphp
                    <div class="gallery-shell" data-property-gallery>
                        @if ($galleryItems->isNotEmpty())
                            <div class="gallery-banner-wrap">
                                @php
                                    $gallerySvgFallback = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22420%22 viewBox=%220 0 900 420%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23d7ebf8%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23c7deef%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22900%22 height=%22420%22 fill=%22url(%23g)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23406582%22 font-family=%22Arial%22 font-size=%2228%22%3EProperty%20Image%3C%2Ftext%3E%3C%2Fsvg%3E";
                                @endphp
                                <img id="propertyGalleryBanner" class="gallery-banner" src="{{ $initialBanner ?: $gallerySvgFallback }}" data-fallback-src="{{ $gallerySvgFallback }}" alt="Property image 1" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $gallerySvgFallback }}';}">
                            </div>
                            <div class="gallery-thumbs" role="list" aria-label="Property image thumbnails">
                                @foreach ($galleryItems as $index => $media)
                                    @php
                                        $thumbUrl = $mediaUrl($media, 'thumb') ?? $mediaUrl($media, 'banner') ?? '';
                                        $bannerUrl = $mediaUrl($media, 'banner') ?? $thumbUrl;
                                    @endphp
                                    <button
                                        type="button"
                                        class="gallery-thumb{{ $loop->first ? ' is-active' : '' }}"
                                        data-banner-src="{{ $bannerUrl }}"
                                        data-banner-alt="Property image {{ $index + 1 }}"
                                        aria-label="Show image {{ $index + 1 }}"
                                    >
                                        <img src="{{ $thumbUrl }}" alt="Property thumbnail {{ $index + 1 }}" loading="lazy" onerror="if(!this.dataset.fallbackApplied){this.dataset.fallbackApplied='1';this.src='{{ $bannerUrl }}';}else{this.onerror=null;this.src='{{ $gallerySvgFallback }}';}">
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="gallery-banner-wrap">
                                <div class="gallery-banner-placeholder">Property images will appear here soon.</div>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="property-summary-shell" aria-label="Property overview">
                    <div class="property-summary-main">
                        <span class="property-summary-stars" aria-label="Star ranking">{{ $starString }}</span>
                        <h1 class="property-summary-title">{{ (string) ($property->name ?? 'Property') }}</h1>
                        @if ($propertyTypeLabel !== '')
                            <span class="property-type-badge"><i class="fa-solid fa-hotel" aria-hidden="true"></i>{{ $propertyTypeLabel }}</span>
                        @endif
                        <div class="property-summary-address">
                            <span>{{ $locationLine !== '' ? $locationLine : 'Address details will be updated shortly.' }}</span>
                            @if ($propertyTypeLabel !== '')
                                <span> · </span>
                                <span>{{ $propertyTypeLabel }}</span>
                            @endif
                            <span> · </span>
                            <a href="{{ $mapUrl }}" target="_blank" rel="noopener">Map</a>
                        </div>
                        <div class="property-summary-reviews">
                            <span class="summary-rating-chip">{{ $ratingOutOfTen > 0 ? number_format($ratingOutOfTen, 1) . '/10' : 'N/A' }} {{ $reviewLabel }}</span>
                            <a class="summary-review-link" href="#guest-reviews-section">{{ $ratingUsers > 0 ? number_format($ratingUsers) : '0' }} reviews</a>
                        </div>

                        <div class="summary-share" aria-label="Share this property">
                            <a href="https://wa.me/?text={{ $shareEncodedText }}" target="_blank" rel="noopener" title="Share on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>Share</span></a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareEncodedUrl }}" target="_blank" rel="noopener" title="Share on Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareEncodedUrl }}" target="_blank" rel="noopener" title="Share on LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
                            <button type="button" data-copy-share-link="{{ $shareUrl }}" title="Copy link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <aside class="property-summary-price" aria-label="Rate summary">
                        <span class="k">Starting from</span>
                        <span class="v">{{ $startingPriceCurrency }} {{ $startingPrice }}</span>
                        <span class="sub">per night · taxes may apply</span>
                        <span class="property-summary-price-total" data-starting-total data-starting-currency="{{ $startingPriceCurrency }}" data-starting-nightly="{{ number_format($startingPriceValue, 2, '.', '') }}">
                            {{ $startingPriceCurrency }} {{ $startingTotalPrice }} total 1 room, {{ $prefillStayNights }} night{{ $prefillStayNights !== 1 ? 's' : '' }}
                        </span>
                        <span class="property-summary-price-note">including taxes and fees estimate</span>
                        <span class="property-summary-price-soldout{{ $hasBookableRate ? ' is-hidden' : '' }}" data-starting-soldout>Sold out for selected dates</span>
                        <a class="cta" href="{{ $selectRoomsTarget }}">View rooms</a>
                    </aside>
                </section>

        <nav class="section-tabs" aria-label="Property content navigation" data-section-nav>
            <a class="section-tab" href="#property-gallery-section">Photos</a>
            <a class="section-tab" href="#services-amenities-section">Amenities</a>
            <a class="section-tab" href="#rooms-section">Rooms</a>
            <a class="section-tab" href="#guest-reviews-section">Reviews</a>
            <a class="section-tab" href="#location-section">Location</a>
            <a class="section-tab" href="#transfer-policy-section">Transfer Rates</a>
            <a class="section-tab" href="#policies-section">Property Policies</a>
        </nav>

                <section id="services-amenities-section" class="info-section" aria-label="Property highlights, amenities and description">
                    <div class="info-main">
                        <div>
                            <h2 class="block-title">Highlights</h2>
                            <div class="highlights-grid">
                                @forelse ($highlights as $highlight)
                                    @php
                                        $highlightRaw = (string) $highlight;
                                        $highlightLabel = $amenityDisplay($highlightRaw);
                                    @endphp
                                    <div class="highlight-item">
                                        <span class="facility-icon" aria-hidden="true">{!! $facilitySvg($highlightRaw) !!}</span>
                                        <span>{{ $highlightLabel }}</span>
                                    </div>
                                @empty
                                    <div class="highlight-item"><span class="facility-icon" aria-hidden="true">{!! $facilitySvg('generic') !!}</span><span>Prime location and guest-friendly services</span></div>
                                    <div class="highlight-item"><span class="facility-icon" aria-hidden="true">{!! $facilitySvg('generic') !!}</span><span>Well-prepared rooms with practical amenities</span></div>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <h2 class="block-title">Amenities</h2>
                            <div class="amenities-board" aria-label="Services and amenities">
                                <p class="amenities-head"><span class="facility-icon" aria-hidden="true">{!! $facilitySvg('generic') !!}</span>Services & Amenities</p>
                                <div class="amenities-columns">
                                    @forelse ($orderedAmenityGroups as $group)
                                        <section class="amenity-group" aria-label="{{ $group['name'] }}">
                                            <h3 class="amenity-group-title">{{ $group['name'] }}</h3>
                                            <ul class="amenity-list">
                                                @foreach ($group['items'] as $amenity)
                                                    @php
                                                        $amenityRaw = trim((string) $amenity);
                                                        $amenityText = $amenityRaw;
                                                        $amenityLower = strtolower($amenityText);
                                                        $tag = null;
                                                        if (str_contains($amenityLower, 'free')) {
                                                            $tag = 'Free';
                                                            $amenityText = trim(str_ireplace('free', '', $amenityText));
                                                        } elseif (str_contains($amenityLower, 'additional charge')) {
                                                            $tag = 'Additional charge';
                                                            $amenityText = trim(str_ireplace('additional charge', '', $amenityText));
                                                        } elseif (str_contains($amenityLower, 'off-site')) {
                                                            $tag = 'Off-site';
                                                            $amenityText = trim(str_ireplace('off-site', '', $amenityText));
                                                        }
                                                        $amenityDisplayText = $amenityDisplay($amenityText !== '' ? $amenityText : $amenityRaw);
                                                    @endphp
                                                    <li>
                                                        <span class="facility-icon" aria-hidden="true">{!! $facilitySvg($amenityRaw) !!}</span>
                                                        <span class="amenity-list-item">
                                                            <span>{{ $amenityDisplayText }}</span>
                                                            @if ($tag)
                                                                <span class="amenity-pill">{{ $tag }}</span>
                                                            @endif
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </section>
                                    @empty
                                        <section class="amenity-group">
                                            <h3 class="amenity-group-title">Amenities</h3>
                                            <ul class="amenity-list">
                                                <li><span class="facility-icon" aria-hidden="true">{!! $facilitySvg('generic') !!}</span><span class="amenity-list-item">Facility and amenity details will be updated soon.</span></li>
                                            </ul>
                                        </section>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @php
                            $descriptionWordCount = str_word_count((string) $description);
                            $descriptionMaxWords = 500;
                            $descriptionRendered = $description !== ''
                                ? Str::words($description, $descriptionMaxWords)
                                : 'This property blends island comfort with practical conveniences for both short and extended stays. Guests can expect thoughtfully prepared rooms, easy access to surrounding highlights, and a host team focused on smooth arrivals and reliable support throughout the trip. The environment is designed to be welcoming for couples, families, and small groups, with a balance of privacy and shared lifestyle spaces depending on room category. Operational details such as check-in support, on-site guidance, and experience coordination are handled in a straightforward way so planning remains simple before and during the stay. The property continues to update service details to ensure amenities, meal options, and transport preferences remain clear at booking time.';
                        @endphp

                        <section class="description-section" aria-label="Property description">
                            <h2>Property Description</h2>
                            <p class="description-text">{{ $descriptionRendered }}</p>
                            <p class="description-note">Displayed description target: 200-500 words. Current text word count: {{ $descriptionWordCount > 0 ? $descriptionWordCount : str_word_count($descriptionRendered) }}.</p>
                        </section>
                    </div>

                </section>
        </div>

        <section id="rooms-section" class="section rooms-section" aria-label="Available rooms">
            <div class="rooms-head">
                <h2>Available Rooms</h2>
                <p class="rooms-sub">Choose a room type to view full profile and proceed with booking options.</p>
            </div>
            <div class="rooms-grid">
                @forelse ($rooms as $room)
                    @php
                        $roomId = (int) ($room->id ?? 0);
                        $roomMedia = collect($roomMediaByRoom->get($roomId, collect()));
                        $roomThumb = $roomMedia->isNotEmpty() ? $mediaUrl($roomMedia->first(), 'thumb') : null;
                        $roomImages = $roomMedia
                            ->map(static function ($media) use ($mediaUrl) {
                                return $mediaUrl($media, 'banner') ?? $mediaUrl($media, 'thumb');
                            })
                            ->filter(static fn ($url) => is_string($url) && trim($url) !== '')
                            ->values();
                        $amenitiesText = strtolower((string) ($room->amenities ?? ''));
                        $roomCurrency = strtoupper((string) ($room->currency ?? $currency));
                        $bedType = trim((string) ($room->bed_type ?? 'Standard Bed'));
                        $roomSizeSqm = (int) ($room->room_size_sqm ?? 0);
                        $floorInfo = trim((string) ($room->floor_info ?? ''));
                        $hasWindow = (int) ($room->has_window ?? 1) === 1;
                        $nonSmoking = (int) ($room->non_smoking ?? 1) === 1;
                        $childPolicy = trim((string) ($room->child_policy ?? ''));
                        $extraBedPolicy = trim((string) ($room->extra_bed_policy ?? ''));
                        $roomAmenitiesRaw = collect(preg_split('/[,\n]+/', (string) ($room->amenities ?? '')) ?: [])->map(static fn ($item) => trim((string) $item))->filter()->values();
                        $bathAmenitiesRaw = collect(preg_split('/[,\n]+/', (string) ($room->bathroom_amenities ?? '')) ?: [])->map(static fn ($item) => trim((string) $item))->filter()->values();
                        $roomQuery = [
                            'checkin' => $prefillCheckin,
                            'checkout' => $prefillCheckout,
                            'rooms' => $prefillRooms,
                            'adults' => $prefillAdults,
                            'children' => $prefillChildren,
                        ];
                        $roomLink = '/room/' . $roomId . '?' . http_build_query($roomQuery);
                        $defaultNightlyRate = (float) ($room->base_price_per_night ?? ($room->base_price ?? 0));
                        $visitorIsLocal = ($visitorResidency ?? 'foreign_national') === 'local_resident';

                        // Returns the visitor-appropriate rate: local column when visitor is a local
                        // resident and a local rate is configured; otherwise returns the foreign rate.
                        $resolveVisitorRate = static function (float $foreignRate, float $localRate, float $fallback) use ($visitorIsLocal): float {
                            if ($visitorIsLocal && $localRate > 0) {
                                return $localRate;
                            }
                            return $foreignRate > 0 ? $foreignRate : $fallback;
                        };

                        $roomOnlyNightlyRate = $resolveVisitorRate(
                            (float) ($room->meal_plan_room_only_price ?? 0),
                            (float) ($room->meal_plan_room_only_price_local ?? 0),
                            $defaultNightlyRate
                        );

                        $rateOptions = collect([
                            [
                                'meal_plan' => 'Room Only',
                                'title' => 'Room Only',
                                'subtitle' => 'Room only (no meals)',
                                'nightly' => $roomOnlyNightlyRate,
                            ],
                            [
                                'meal_plan' => 'BB',
                                'title' => 'Bed & Breakfast',
                                'subtitle' => 'Bed & Breakfast included',
                                'nightly' => $resolveVisitorRate(
                                    (float) (($room->meal_plan_bb_price ?? 0) > 0 ? ($room->meal_plan_bb_price ?? 0) : ($room->meal_plan_breakfast_price ?? 0)),
                                    (float) ($room->meal_plan_bb_price_local ?? 0),
                                    0.0
                                ),
                            ],
                            [
                                'meal_plan' => 'HB',
                                'title' => 'Half Board',
                                'subtitle' => 'Half Board included',
                                'nightly' => $resolveVisitorRate(
                                    (float) ($room->meal_plan_hb_price ?? 0),
                                    (float) ($room->meal_plan_hb_price_local ?? 0),
                                    0.0
                                ),
                            ],
                            [
                                'meal_plan' => 'FB',
                                'title' => 'Full Board',
                                'subtitle' => 'Full Board included',
                                'nightly' => $resolveVisitorRate(
                                    (float) ($room->meal_plan_fb_price ?? 0),
                                    (float) ($room->meal_plan_fb_price_local ?? 0),
                                    0.0
                                ),
                            ],
                            [
                                'meal_plan' => 'All Inclusive',
                                'title' => 'All Inclusive',
                                'subtitle' => 'All Inclusive package',
                                'nightly' => $resolveVisitorRate(
                                    (float) ($room->meal_plan_ai_price ?? 0),
                                    (float) ($room->meal_plan_ai_price_local ?? 0),
                                    0.0
                                ),
                            ],
                        ])->filter(static fn ($item) => (float) ($item['nightly'] ?? 0) > 0)->values();

                        if ($rateOptions->isEmpty() && $defaultNightlyRate > 0) {
                            $rateOptions = collect([[
                                'meal_plan' => 'Room Only',
                                'title' => 'Room Only',
                                'subtitle' => 'Standard room rate',
                                'nightly' => $defaultNightlyRate,
                            ]]);
                        }
                            // Foreign visitors see USD (from _usd columns); locals see MVR (from _local columns).
                            $roomCurrency = $visitorIsLocal ? 'MVR' : 'USD';

                            // Resolve the rate for the visitor's segment.
                            // Foreign: use _usd column (exact USD). Local: use _local column (MVR).
                            // Falls back to $fallback (base_price in MVR) converted as needed.
                            $resolveVisitorRate = static function (float $foreignUsd, float $localMvr, float $fallback) use ($visitorIsLocal, $mvrUsdRate): float {
                                if ($visitorIsLocal) {
                                    return $localMvr > 0 ? $localMvr : ($mvrUsdRate > 0 ? round($fallback / $mvrUsdRate, 2) : $fallback);
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
                                    'subtitle' => 'Room only (no meals)',
                                    'nightly' => $roomOnlyNightlyRate,
                                ],
                                [
                                    'meal_plan' => 'BB',
                                    'title' => 'Bed & Breakfast',
                                    'subtitle' => 'Bed & Breakfast included',
                                    'nightly' => $resolveVisitorRate(
                                        (float) ($room->meal_plan_bb_price_usd ?? 0),
                                        (float) ($room->meal_plan_bb_price_local ?? 0),
                                        0.0
                                    ),
                                ],
                                [
                                    'meal_plan' => 'HB',
                                    'title' => 'Half Board',
                                    'subtitle' => 'Half Board included',
                                    'nightly' => $resolveVisitorRate(
                                        (float) ($room->meal_plan_hb_price_usd ?? 0),
                                        (float) ($room->meal_plan_hb_price_local ?? 0),
                                        0.0
                                    ),
                                ],
                                [
                                    'meal_plan' => 'FB',
                                    'title' => 'Full Board',
                                    'subtitle' => 'Full Board included',
                                    'nightly' => $resolveVisitorRate(
                                        (float) ($room->meal_plan_fb_price_usd ?? 0),
                                        (float) ($room->meal_plan_fb_price_local ?? 0),
                                        0.0
                                    ),
                                ],
                                [
                                    'meal_plan' => 'All Inclusive',
                                    'title' => 'All Inclusive',
                                    'subtitle' => 'All Inclusive package',
                                    'nightly' => $resolveVisitorRate(
                                        (float) ($room->meal_plan_ai_price_usd ?? 0),
                                        (float) ($room->meal_plan_ai_price_local ?? 0),
                                        0.0
                                    ),
                                ],
                            ])->filter(static fn ($item) => (float) ($item['nightly'] ?? 0) > 0)->values();

                            if ($rateOptions->isEmpty() && $defaultNightlyRate > 0) {
                                $rateOptions = collect([[
                                    'meal_plan' => 'Room Only',
                                    'title' => 'Room Only',
                                    'subtitle' => 'Standard room rate',
                                    'nightly' => $defaultNightlyRate,
                                ]]);
                            }

                        $rateOptions = $rateOptions->map(static function ($option) {
                            $mealPlan = strtolower(trim((string) ($option['meal_plan'] ?? '')));
                            $hasBreakfast = in_array($mealPlan, ['bb', 'hb', 'fb', 'all inclusive'], true);
                            $icon = match ($mealPlan) {
                                'room only' => 'fa-solid fa-bed',
                                'bb' => 'fa-solid fa-mug-hot',
                                'hb' => 'fa-solid fa-utensils',
                                'fb' => 'fa-solid fa-bowl-food',
                                'all inclusive' => 'fa-solid fa-champagne-glasses',
                                default => 'fa-solid fa-utensils',
                            };

                            $option['breakfast_label'] = $hasBreakfast ? 'With Breakfast' : 'Without Breakfast';
                            $option['has_breakfast'] = $hasBreakfast;
                            $option['icon'] = $icon;
                            return $option;
                        })->values();

                        $primaryNightlyRateRaw = (float) ($rateOptions->first()['nightly'] ?? $defaultNightlyRate);
                        $roomPrice = number_format($primaryNightlyRateRaw, 2);
                        $breakfastLabel = (string) ($rateOptions->first()['meal_plan'] ?? 'Room Only');
                        $maxOccupancy = max(1, (int) ($room->max_occupancy ?? 1));
                        $adultFigureCount = min(2, $maxOccupancy);
                        $childFigureCount = max(0, $maxOccupancy - $adultFigureCount);
                        $isRoomSoldOut = (
                            (property_exists($room, 'is_available') && (int) ($room->is_available ?? 1) === 0)
                            || (property_exists($room, 'available') && (int) ($room->available ?? 1) === 0)
                            || (property_exists($room, 'is_bookable') && (int) ($room->is_bookable ?? 1) === 0)
                            || (property_exists($room, 'available_rooms') && (int) ($room->available_rooms ?? 1) <= 0)
                            || (property_exists($room, 'inventory_count') && (int) ($room->inventory_count ?? 1) <= 0)
                            || (property_exists($room, 'total_rooms') && property_exists($room, 'bookings_count') && (int) ($room->bookings_count ?? 0) >= (int) ($room->total_rooms ?? 0) && (int) ($room->total_rooms ?? 0) > 0)
                        );
                        $amenities = collect([
                                (string) ($room->bed_type ?? ''),
                                ...(preg_split('/[,\n]+/', (string) ($room->amenities ?? '')) ?: []),
                                ...(preg_split('/[,\n]+/', (string) ($room->bathroom_amenities ?? '')) ?: []),
                            ])
                            ->map(static fn ($item) => trim((string) $item))
                            ->filter(static fn ($item) => $item !== '')
                            ->unique()
                            ->take(3)
                            ->values();
                    @endphp
                    <article
                        id="room-{{ $roomId }}"
                        class="room-card{{ $cheapestRoomId === $roomId ? ' is-cheapest' : '' }}"
                        data-room-id="{{ $roomId }}"
                        data-room-name="{{ (string) ($room->name ?? 'Room') }}"
                        data-room-bed="{{ $bedType }}"
                        data-room-size="{{ $roomSizeSqm }}"
                        data-room-floor="{{ $floorInfo }}"
                        data-room-has-window="{{ $hasWindow ? '1' : '0' }}"
                        data-room-non-smoking="{{ $nonSmoking ? '1' : '0' }}"
                        data-room-child-policy="{{ $childPolicy }}"
                        data-room-extra-bed-policy="{{ $extraBedPolicy }}"
                        data-room-description="{{ trim((string) ($room->description ?? '')) }}"
                        data-room-meal-plan="{{ trim((string) ($room->meal_plan ?? '')) }}"
                        data-room-currency="{{ $roomCurrency }}"
                        data-room-link="{{ $roomLink }}"
                        data-room-images='@json($roomImages->all())'
                        data-room-amenities='@json($roomAmenitiesRaw->all())'
                        data-bathroom-amenities='@json($bathAmenitiesRaw->all())'
                    >
                        <a class="room-media-link" href="#" data-open-room-modal="{{ $roomId }}">
                            <div class="room-media">
                                <img src="{{ $roomThumb ?? '' }}" alt="{{ (string) ($room->name ?? 'Room') }}" loading="lazy">
                                <span class="room-tag"><i class="{{ (string) ($rateOptions->first()['icon'] ?? 'fa-solid fa-utensils') }}" aria-hidden="true"></i>{{ $breakfastLabel }}</span>
                            </div>
                        </a>
                        <div class="room-body">
                            <h3><a class="room-name-link" href="#" data-open-room-modal="{{ $roomId }}">{{ (string) ($room->name ?? 'Room') }}</a></h3>
                            <div class="room-offer-table" aria-label="Room rate options" data-room-id="{{ $roomId }}">
                                <div class="room-offer-head">
                                    <span>Your Choices</span>
                                    <span>Sleeps</span>
                                    <span>Today's Price</span>
                                </div>
                                @foreach ($rateOptions as $rateIndex => $rateOption)
                                    @php
                                        $nightlyRateRaw = (float) ($rateOption['nightly'] ?? 0);
                                        $ratePrice = number_format($nightlyRateRaw, 2);
                                        $rateOldPrice = number_format($nightlyRateRaw * 1.08, 2);
                                        $rateTotalRaw = $nightlyRateRaw * $prefillStayNights;
                                        $reserveLink = '/room/' . $roomId . '?' . http_build_query(array_merge($roomQuery, [
                                            'rate_nightly' => number_format($nightlyRateRaw, 2, '.', ''),
                                            'meal_plan' => (string) ($rateOption['meal_plan'] ?? 'Room Only'),
                                            'guest_residency' => $visitorResidency ?? 'foreign_national',
                                        ]));
                                        $rateOldPriceValue = floatval($rateOldPrice);
                                        $ratePriceValue = floatval($ratePrice);
                                        $rateDiscountPercent = $rateOldPriceValue > 0
                                            ? round((($rateOldPriceValue - $ratePriceValue) / $rateOldPriceValue) * 100)
                                            : 0;
                                    @endphp
                                    <div class="room-offer-row{{ $rateIndex > 0 ? ' is-hidden' : '' }}">
                                        <div class="room-choices">
                                            <span class="room-option-title">{{ (string) ($rateOption['title'] ?? 'Room Only') }}</span>
                                            <span class="room-option-subtitle">{{ (string) ($rateOption['subtitle'] ?? 'Rate details available') }}</span>
                                            <span class="room-plan-badge"><i class="{{ (string) ($rateOption['icon'] ?? 'fa-solid fa-utensils') }}" aria-hidden="true"></i>{{ (string) ($rateOption['meal_plan'] ?? 'Rate Plan') }}</span>
                                            <span class="room-breakfast-highlight {{ (bool) ($rateOption['has_breakfast'] ?? false) ? 'is-included' : 'is-not-included' }}"><i class="fa-solid fa-mug-hot" aria-hidden="true"></i>{{ (string) ($rateOption['breakfast_label'] ?? 'Without Breakfast') }}</span>
                                            <span>Instant confirmation</span>
                                            <span>Prepay online or pay at property</span>
                                        </div>
                                        <div class="room-sleeps" aria-label="Sleeps {{ $maxOccupancy }} guests">
                                            <span class="room-sleeps-icons" aria-hidden="true">
                                                @for ($i = 0; $i < $adultFigureCount; $i++)
                                                    <i class="fa-solid fa-user"></i>
                                                @endfor
                                                @for ($i = 0; $i < $childFigureCount; $i++)
                                                    <i class="fa-solid fa-child-reaching room-sleeps-child"></i>
                                                @endfor
                                            </span>
                                        </div>
                                        <div>
                                            <div class="room-price-box">
                                                <div>
                                                    <div class="room-price-old">{{ $roomCurrency }} {{ $rateOldPrice }}</div>
                                                    <div class="room-price-now">{{ $roomCurrency }} {{ $ratePrice }}</div>
                                                    @if (($visitorResidency ?? 'foreign_national') === 'local_resident')
                                                        <div class="room-price-local-badge">Local Rate</div>
                                                    @elseif (($mvrUsdRate ?? 0) > 0)
                                                        <div class="room-price-usd-hint">≈ USD {{ number_format($nightlyRateRaw / $mvrUsdRate, 0) }} / night</div>
                                                    @endif
                                                                    <div class="room-price-old">{{ $roomCurrency }} {{ $rateOldPrice }}</div>
                                                                    <div class="room-price-now">{{ $roomCurrency }} {{ $ratePrice }}</div>
                                                                    @if ($visitorIsLocal)
                                                                        <div class="room-price-local-badge">Local Rate</div>
                                                                    @elseif (($mvrUsdRate ?? 0) > 0)
                                                                        <div class="room-price-usd-hint">= MVR {{ number_format($nightlyRateRaw * $mvrUsdRate, 0) }} / night</div>
                                                                    @endif
                                                    <div class="room-price-summary" data-rate-summary data-rate-currency="{{ $roomCurrency }}" data-nightly-rate="{{ number_format($nightlyRateRaw, 2, '.', '') }}">
                                                        {{ $roomCurrency }} {{ number_format($rateTotalRaw, 2) }} total 1 room, {{ $prefillStayNights }} night{{ $prefillStayNights !== 1 ? 's' : '' }}
                                                    </div>
                                                    <div class="room-price-summary-note">incl. taxes &amp; fees</div>
                                                    @if ($isRoomSoldOut)
                                                        <span class="room-soldout-badge" data-rate-soldout>Sold out for selected dates</span>
                                                    @endif
                                                    @if ($rateDiscountPercent > 0)
                                                        <span class="discount-badge">{{ $rateDiscountPercent }}% off</span>
                                                    @endif
                                                </div>
                                                <a class="reserve-btn" href="{{ $reserveLink }}" data-base-room-link="{{ $roomLink }}" data-rate-nightly="{{ number_format($nightlyRateRaw, 2, '.', '') }}" data-meal-plan="{{ (string) ($rateOption['meal_plan'] ?? 'Room Only') }}" @if($isRoomSoldOut) aria-disabled="true" style="pointer-events:none;opacity:.45;" @endif>Reserve</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                @if ($rateOptions->count() > 1)
                                    <div class="room-offer-expand">
                                        <button class="room-offer-expand-btn" data-expand-toggle="room-{{ $roomId }}">
                                            Show More Rates
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <ul class="room-side-details">
                                <li><span class="room-amenity-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v.5M3 12h.5M20.5 12h.5M12 20v.5M5.5 5.5l.35.35M18.15 18.15l.35.35M18.5 5.5l-.35.35M5.85 18.15l-.35.35"/><circle cx="12" cy="12" r="4"/></svg></span><span>{{ $bedType }}</span></li>
                                <li><span class="room-amenity-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11h18M6 6h12v8H6zM8 18h8"/></svg></span><span>Occupancy:<span class="room-occupancy-inline" aria-label="Sleeps {{ $maxOccupancy }} guests">@for ($i = 0; $i < $adultFigureCount; $i++)<i class="fa-solid fa-user"></i>@endfor @for ($i = 0; $i < $childFigureCount; $i++)<i class="fa-solid fa-child-reaching room-sleeps-child"></i>@endfor</span></span></li>
                                @foreach ($amenities as $amenity)
                                    <li><span class="room-amenity-icon" aria-hidden="true">{!! $facilitySvg($amenity) !!}</span><span>{{ $amenity }}</span></li>
                                @endforeach
                                <li><a class="room-name-link" href="#" data-open-room-modal="{{ $roomId }}">Room Details</a></li>
                            </ul>
                        </div>
                    </article>
                @empty
                    <article class="room-card"><div class="room-body"><h3>No rooms yet</h3><span class="muted">Room inventory for this property will be published soon.</span></div></article>
                @endforelse
            </div>
        </section>

        <section id="guest-reviews-section" class="section guest-reviews-section" aria-label="Guest reviews">
            <h2>Guest Reviews</h2>
            <div class="guest-reviews-layout">
                <aside class="guest-reviews-summary" aria-label="Guest review summary">
                    <div class="guest-score">
                        <span class="guest-score-badge">{{ $ratingOutOfTen > 0 ? number_format($ratingOutOfTen, 1) . '/10' : 'N/A' }}</span>
                        <span class="guest-score-label">{{ $reviewLabel }}</span>
                    </div>
                    <span class="muted">{{ $ratingUsers > 0 ? number_format($ratingUsers) . ' total reviews' : 'Reviews will appear after guests submit ratings.' }}</span>
                    <a class="review-link" href="#guest-reviews-section">Show all reviews</a>
                </aside>

                <div class="guest-reviews-feed" aria-label="Guest review list" data-total-reviews="{{ $guestReviews->count() }}">
                    <h3>What guests say</h3>
                    @forelse ($guestReviews as $review)
                        @php
                            $reviewName = trim((string) ($review['name'] ?? 'Guest User'));
                            $reviewName = $reviewName !== '' ? $reviewName : 'Guest User';
                            $reviewInitial = strtoupper(substr($reviewName, 0, 1));
                            $reviewDate = trim((string) ($review['date'] ?? ''));
                            $reviewRating = (float) ($review['rating'] ?? 0);
                            $isHidden = $loop->index >= 5;
                        @endphp
                        <article class="guest-review-item{{ $isHidden ? ' is-hidden' : '' }}" data-review-index="{{ $loop->index }}">
                            <div class="guest-review-head">
                                <span class="guest-avatar">{{ $reviewInitial !== '' ? $reviewInitial : 'G' }}</span>
                                <span class="guest-name">{{ $reviewName }}</span>
                                @if ($reviewDate !== '')
                                    <span class="guest-date">{{ \Illuminate\Support\Str::limit($reviewDate, 20) }}</span>
                                @endif
                                @if ($reviewRating > 0)
                                    <span class="guest-rating">{{ number_format($reviewRating, 1) }}/5</span>
                                @endif
                            </div>
                            <p class="guest-comment">{{ (string) ($review['comment'] ?? '') }}</p>
                        </article>
                    @empty
                        <article class="guest-review-item">
                            <p class="guest-comment">No guest reviews published yet. Ratings and comments will be displayed here automatically once customers submit reviews.</p>
                        </article>
                    @endforelse
                    @if ($guestReviews->count() > 5)
                        <div class="reviews-load-more">
                            <button class="reviews-load-btn" data-load-more-reviews>
                                Show {{ $guestReviews->count() - 5 }} more reviews
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section id="location-section" class="section location-section" aria-label="Property location">
            <h2>Location</h2>
            <div class="location-layout">
                <div class="location-copy">
                    <p>{{ $locationLine !== '' ? $locationLine : 'Location details are being updated.' }}</p>
                    <p>This property is connected to major nearby points of interest and transport access, making arrivals and day trips easier for guests.</p>
                    <a class="summary-review-link" href="{{ $mapUrl }}" target="_blank" rel="noopener">More location info</a>
                </div>
                <div>
                    <div class="location-map">
                        <div id="property-location-map"
                            data-lat="{{ $hasExactCoordinates ? $mapLat : '' }}"
                            data-lng="{{ $hasExactCoordinates ? $mapLng : '' }}"
                            data-query="{{ !$hasExactCoordinates ? $mapQuery : '' }}"
                            aria-label="Property location map"
                        ></div>
                    </div>
                    <p class="location-map-caption">{{ $locationLine !== '' ? $locationLine : 'Map location' }}</p>
                </div>
            </div>
        </section>

        @if ($transferChoices->isNotEmpty())
            <section id="transfer-policy-section" class="section transfer-policy-section" aria-label="Transfer policy and rates">
                <h2>Transfer Policy &amp; Rates</h2>
                <div class="transfer-policy-grid">
                    <p class="transfer-policy-intro">Available transfer modes for this property are visible here before room selection. Guests still choose their transfer during the booking flow after selecting a room, and the applicable fare is calculated according to whether the guest is a local resident or a foreign national.</p>
                    <article class="transfer-rate-card">
                        <div class="transfer-rate-head">
                            <div>
                                <h3 class="transfer-rate-title">Transfer Fare Matrix</h3>
                                <p class="transfer-rate-note">Mode of transport and per-passenger fares by residency type.</p>
                            </div>
                            <span class="transfer-pill"><i class="fa-solid fa-route" aria-hidden="true"></i> Available at checkout</span>
                        </div>
                        <div class="transfer-rate-grid">
                            <div class="transfer-rate-box">
                                <h3>Local Resident</h3>
                                <table class="transfer-rate-table-compact" aria-label="Local resident transfer fares">
                                    <thead>
                                        <tr>
                                            <th>Mode of transport</th>
                                            <th>Adult fare</th>
                                            <th>Child fare</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transferChoices as $transferChoice)
                                            @php
                                                $modeKey = (string) ($transferChoice['key'] ?? '');
                                                $modeIcon = $resolveTransferModeIcon($modeKey);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="transfer-mode">
                                                        <i class="icon {{ $modeIcon }}" aria-hidden="true"></i>
                                                        <span>{{ (string) ($transferChoice['label'] ?? 'Transfer') }}</span>
                                                    </span>
                                                </td>
                                                <td><span class="transfer-rate-amount">{{ $currency }} {{ number_format((float) ($transferChoice['local_adult_rate'] ?? 0), 2) }}</span></td>
                                                <td><span class="transfer-rate-amount">{{ $currency }} {{ number_format((float) ($transferChoice['local_child_rate'] ?? 0), 2) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($transferBaseLocal > 0)
                                    <p class="transfer-base-note">Base local charge: <strong>{{ $currency }} {{ number_format($transferBaseLocal, 2) }}</strong></p>
                                @endif
                            </div>
                            <div class="transfer-rate-box">
                                <h3>Foreigner</h3>
                                <table class="transfer-rate-table-compact" aria-label="Foreigner transfer fares">
                                    <thead>
                                        <tr>
                                            <th>Mode of transport</th>
                                            <th>Adult fare</th>
                                            <th>Child fare</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transferChoices as $transferChoice)
                                            @php
                                                $modeKey = (string) ($transferChoice['key'] ?? '');
                                                $modeIcon = $resolveTransferModeIcon($modeKey);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="transfer-mode">
                                                        <i class="icon {{ $modeIcon }}" aria-hidden="true"></i>
                                                        <span>{{ (string) ($transferChoice['label'] ?? 'Transfer') }}</span>
                                                    </span>
                                                </td>
                                                <td><span class="transfer-rate-amount">{{ $currency }} {{ number_format((float) ($transferChoice['foreign_adult_rate'] ?? 0), 2) }}</span></td>
                                                <td><span class="transfer-rate-amount">{{ $currency }} {{ number_format((float) ($transferChoice['foreign_child_rate'] ?? 0), 2) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($transferBaseForeign > 0)
                                    <p class="transfer-base-note">Base foreigner charge: <strong>{{ $currency }} {{ number_format($transferBaseForeign, 2) }}</strong></p>
                                @endif
                            </div>
                        </div>
                        <p class="transfer-policy-summary">Transfer selection remains part of the existing booking flow, so this section is informational and does not change the current checkout behavior.</p>
                    </article>
                </div>
            </section>
        @endif

        @php
            $policyCheckIn = trim((string) ($propertyDetails['check_in_time'] ?? ''));
            $policyCheckOut = trim((string) ($propertyDetails['check_out_time'] ?? ''));
            $policyChild = trim((string) ($propertyDetails['child_policy'] ?? ''));
            $policyHouseRules = trim((string) ($propertyDetails['house_rules'] ?? ''));
            $policyCancellation = trim((string) ($propertyDetails['cancellation_policy'] ?? ''));
            $policyEarlyCheckIn = trim((string) ($propertyDetails['early_check_in_allowed'] ?? ''));
            $policyLateCheckOut = trim((string) ($propertyDetails['late_check_out_allowed'] ?? ''));
            $policyMinNights = isset($propertyDetails['minimum_nights']) && is_numeric($propertyDetails['minimum_nights'])
                ? max(1, (int) $propertyDetails['minimum_nights'])
                : null;

            $policyCheckInOutText = ($policyCheckIn !== '' || $policyCheckOut !== '')
                ? ('Check-in ' . ($policyCheckIn !== '' ? ('from ' . $policyCheckIn) : 'time available on request')
                    . ' and check-out '
                    . ($policyCheckOut !== '' ? ('before ' . $policyCheckOut) : 'time available on request')
                    . '.')
                : 'Check-in and check-out timing details will be confirmed by the property.';

            $policyFlexText = collect([
                $policyEarlyCheckIn !== '' ? ('Early check-in: ' . str_replace('_', ' ', ucfirst($policyEarlyCheckIn))) : null,
                $policyLateCheckOut !== '' ? ('Late check-out: ' . str_replace('_', ' ', ucfirst($policyLateCheckOut))) : null,
                $policyMinNights !== null ? ('Minimum stay: ' . $policyMinNights . ' night' . ($policyMinNights > 1 ? 's' : '')) : null,
            ])->filter()->implode(' | ');

            $policyBookingNotes = trim((string) (
                $propertyDetails['booking_notes']
                ?? $propertyDetails['booking_note']
                ?? $propertyDetails['additional_booking_notes']
                ?? ''
            ));

            if ($policyBookingNotes === '') {
                $noteBits = collect([
                    trim((string) ($propertyDetails['deposit_policy'] ?? '')),
                    trim((string) ($propertyDetails['pet_policy'] ?? '')),
                    trim((string) ($propertyDetails['breakfast_policy'] ?? '')),
                ])->filter()->values();
                $policyBookingNotes = $noteBits->implode(' ');
            }
        @endphp

        <section id="policies-section" class="section policies-section" aria-label="Property policies">
            <h2>Property Policies</h2>
            <div class="policies-grid">
                <div class="policy-label">Check-in and Check-out</div>
                <div class="policy-value">
                    {{ $policyCheckInOutText }}
                    @if ($policyFlexText !== '')
                        {{ ' ' . $policyFlexText }}.
                    @endif
                </div>

                <div class="policy-label">Child Policies</div>
                <div class="policy-value">{{ $policyChild !== '' ? $policyChild : 'Children are welcome. Additional fees may apply based on room occupancy and selected meal plan.' }}</div>

                <div class="policy-label">House Rules</div>
                <div class="policy-value">{{ $policyHouseRules !== '' ? $policyHouseRules : 'Property-specific house rules will be shared during booking confirmation.' }}</div>

                <div class="policy-label">Cancellation Policy</div>
                <div class="policy-value">{{ $policyCancellation !== '' ? $policyCancellation : 'Cancellation terms depend on selected room offer and seasonality.' }}</div>

                <div class="policy-label">Booking Notes</div>
                <div class="policy-value">{{ $policyBookingNotes !== '' ? $policyBookingNotes : 'No additional booking notes have been provided by this property.' }}</div>
            </div>
        </section>

        @if ($nearbySectionVisible)
            <section id="nearby-properties-section" class="section nearby-properties-section" aria-label="Nearby properties">
                <div class="nearby-head">
                    <h2>
                        Nearby Properties
                        @if ($nearbyUsesCoordinateRadius)
                            within {{ $nearbyRadiusLabel }} km
                        @endif
                    </h2>
                    @if ($nearbyUsesCoordinateRadius)
                        <div class="nearby-radius-controls" aria-label="Nearby radius options">
                            @foreach ($nearbyRadiusOptions as $radiusOption)
                                @php
                                    $radiusValue = (int) $radiusOption;
                                    $radiusUrl = url()->current() . '?' . http_build_query(array_merge($nearbyQueryBase, ['nearby_radius_km' => $radiusValue])) . '#nearby-properties-section';
                                    $isActiveRadius = abs($nearbyRadiusKm - $radiusValue) < 0.51;
                                @endphp
                                <a class="nearby-radius-chip{{ $isActiveRadius ? ' is-active' : '' }}" href="{{ $radiusUrl }}">{{ $radiusValue }} km</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($nearbyProperties->isNotEmpty())
                    <div class="nearby-grid">
                        @foreach ($nearbyProperties as $nearby)
                            @php
                                $nearbyName = trim((string) ($nearby['name'] ?? 'Property'));
                                $nearbyUrl = trim((string) ($nearby['url'] ?? ''));
                                $nearbyLocationLine = trim((string) ($nearby['location_line'] ?? 'Maldives'));
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
                            <a class="nearby-card" href="{{ $nearbyUrl !== '' ? $nearbyUrl : ('/property/' . (int) ($nearby['id'] ?? 0)) }}" aria-label="Open {{ $nearbyName }}">
                                <img class="nearby-card-media" src="{{ $nearbyThumb !== '' ? $nearbyThumb : $nearbyThumbFallback }}" alt="{{ $nearbyName }}" loading="lazy" onerror="if(!this.src.startsWith('data:')){this.onerror=null;this.src='{{ $nearbyThumbFallback }}';}">
                                <div class="nearby-card-body">
                                    <span class="nearby-location">{{ $nearbyLocationLine }}</span>
                                    <h3 class="nearby-name">{{ $nearbyName }}</h3>
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
                    <p class="nearby-empty">No properties found within {{ $nearbyRadiusLabel }} km of this property pin. Try a larger radius.</p>
                @endif
            </section>
        @endif

        @include('partials.global-site-footer')
    </main>

    <!-- Room Details Modal -->
    <div class="room-details-modal-overlay" id="roomDetailsModal" data-room-modal>
        <div class="room-details-modal">
            <button class="room-details-modal-close" data-close-modal>×</button>
            <div id="roomModalContent" class="room-details-content">
                <!-- Room details will be populated here -->
            </div>
        </div>
    </div>

    <!-- Room Modal Template (hidden) -->
    <template id="roomDetailsTemplate">
        <div class="room-details-gallery" data-gallery></div>
        <div class="room-details-sidebar">
            <div class="room-details-header">
                <h2 class="room-details-title" data-title></h2>
                <div class="room-bed-info" data-bedinfo></div>
                <div class="room-summary-badges" data-summary-badges></div>
                <p class="room-details-description" data-description></p>
                <p class="room-bed-note" data-note></p>
            </div>
            <div class="room-meal-plans" data-meal-plans></div>
            <div class="room-quick-specs" data-specs></div>
            <div class="room-amenities-grid" data-amenities></div>
            <div class="room-policy-stack" data-policies></div>
            <a class="room-select-btn" data-select-btn href="#">Select Room</a>
        </div>
    </template>

    <script>
        (function () {
            const gallery = document.querySelector('[data-property-gallery]');
            if (!gallery) {
                return;
            }

            const banner = document.getElementById('propertyGalleryBanner');
            const thumbs = Array.from(gallery.querySelectorAll('.gallery-thumb'));
            if (!banner || thumbs.length === 0) {
                return;
            }

            let current = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains('is-active')));
            let rotateTimer = null;
            const brokenBannerSources = new Set();
            const fallbackBannerSrc = String(banner.getAttribute('data-fallback-src') || '');

            function normalizedSrc(value) {
                return String(value || '').trim();
            }

            function nextValidIndex(startIndex) {
                if (thumbs.length === 0) {
                    return -1;
                }

                for (let offset = 0; offset < thumbs.length; offset += 1) {
                    const candidateIndex = (startIndex + offset + thumbs.length) % thumbs.length;
                    const candidate = thumbs[candidateIndex];
                    if (!candidate) {
                        continue;
                    }

                    const candidateSrc = normalizedSrc(candidate.dataset.bannerSrc);
                    if (candidateSrc === '' || brokenBannerSources.has(candidateSrc)) {
                        continue;
                    }

                    return candidateIndex;
                }

                return -1;
            }

            function usableThumbCount() {
                return thumbs.filter((thumb) => {
                    const src = normalizedSrc(thumb.dataset.bannerSrc);
                    return src !== '' && !brokenBannerSources.has(src);
                }).length;
            }

            function setActive(index) {
                const target = thumbs[index];
                if (!target) {
                    return;
                }

                const requestedSrc = normalizedSrc(target.dataset.bannerSrc);
                if (requestedSrc === '' || brokenBannerSources.has(requestedSrc)) {
                    const replacementIndex = nextValidIndex(index + 1);
                    if (replacementIndex === -1) {
                        if (fallbackBannerSrc !== '') {
                            banner.onerror = null;
                            banner.src = fallbackBannerSrc;
                        }
                        return;
                    }

                    setActive(replacementIndex);
                    return;
                }

                thumbs.forEach((thumb, thumbIndex) => {
                    const active = thumbIndex === index;
                    thumb.classList.toggle('is-active', active);
                    thumb.setAttribute('aria-current', active ? 'true' : 'false');
                });

                banner.onerror = function () {
                    const failedSrc = normalizedSrc(target.dataset.bannerSrc);
                    if (failedSrc !== '') {
                        brokenBannerSources.add(failedSrc);
                    }

                    const replacementIndex = nextValidIndex(index + 1);
                    if (replacementIndex !== -1) {
                        setActive(replacementIndex);
                        return;
                    }

                    this.onerror = null;
                    if (fallbackBannerSrc !== '') {
                        this.src = fallbackBannerSrc;
                    }
                };

                banner.src = requestedSrc;
                banner.alt = target.dataset.bannerAlt || 'Property image';
                current = index;
            }

            function startRotation() {
                if (rotateTimer) {
                    clearInterval(rotateTimer);
                }

                if (usableThumbCount() < 2) {
                    return;
                }

                rotateTimer = setInterval(() => {
                    const next = nextValidIndex(current + 1);
                    if (next === -1) {
                        clearInterval(rotateTimer);
                        rotateTimer = null;
                        return;
                    }
                    setActive(next);
                }, 6000);
            }

            thumbs.forEach((thumb, index) => {
                thumb.addEventListener('click', () => {
                    setActive(index);
                    startRotation();
                });
            });

            setActive(current);
            startRotation();
        })();

        // Reviews Load More
        (function () {
            const loadMoreBtn = document.querySelector('[data-load-more-reviews]');
            if (!loadMoreBtn) {
                return;
            }

            const reviewsFeed = loadMoreBtn.closest('.guest-reviews-feed');
            if (!reviewsFeed) {
                return;
            }

            loadMoreBtn.addEventListener('click', function () {
                const hiddenReviews = Array.from(reviewsFeed.querySelectorAll('.guest-review-item.is-hidden'));
                hiddenReviews.forEach((review) => {
                    review.classList.remove('is-hidden');
                });
                loadMoreBtn.style.display = 'none';
            });
        })();

        // Room Rate Expansion
        (function () {
            const expandButtons = document.querySelectorAll('[data-expand-toggle]');
            expandButtons.forEach((btn) => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const roomId = this.dataset.expandToggle;
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
        })();

        // Room Details Modal
        (function () {
            const modal = document.getElementById('roomDetailsModal');
            const roomDetailLinks = document.querySelectorAll('[data-open-room-modal]');
            const closeBtn = modal?.querySelector('[data-close-modal]');
            const template = document.getElementById('roomDetailsTemplate');
            const contentArea = document.getElementById('roomModalContent');

            if (!modal || !roomDetailLinks || !template) {
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
                return token
                    .replace(/[_-]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .replace(/\b\w/g, (char) => char.toUpperCase())
                    .replace(/\bWifi\b/gi, 'Wi-Fi')
                    .replace(/\bTv\b/g, 'TV');
            }

            function mealPlanMeta(value) {
                const normalized = String(value || '').toLowerCase().replace(/[_-]+/g, ' ').trim();

                if (['room only', 'ro', 'without breakfast'].includes(normalized)) {
                    return { code: 'RO', title: 'Room Only' };
                }
                if (['bed breakfast', 'bb', 'with breakfast'].includes(normalized)) {
                    return { code: 'BB', title: 'Bed and Breakfast' };
                }
                if (['half board', 'hb'].includes(normalized)) {
                    return { code: 'HB', title: 'Half Board' };
                }
                if (['full board', 'fb'].includes(normalized)) {
                    return { code: 'FB', title: 'Full Board' };
                }
                if (['all inclusive', 'ai'].includes(normalized)) {
                    return { code: 'AI', title: 'All Inclusive' };
                }

                return { code: titleCaseToken(value || 'Plan'), title: titleCaseToken(value || 'Plan') };
            }

            function collectMealPlans(roomCard) {
                const rows = Array.from(roomCard.querySelectorAll('.room-offer-row'));
                const plans = [];

                rows.forEach((row, index) => {
                    const choiceSpans = Array.from(row.querySelectorAll('.room-choices span')).map((node) => node.textContent.trim()).filter(Boolean);
                    const planText = choiceSpans[0] || roomCard.dataset.roomMealPlan || '';
                    const meta = mealPlanMeta(planText);
                    const priceText = row.querySelector('.room-price-now')?.textContent?.trim() || '';
                    const description = choiceSpans[1] || 'Rate details available at booking';

                    if (meta.title === '') {
                        return;
                    }

                    plans.push({
                        code: meta.code,
                        title: meta.title,
                        description,
                        priceText,
                        primary: index === 0,
                    });
                });

                if (plans.length === 0) {
                    const fallbackMeta = mealPlanMeta(roomCard.dataset.roomMealPlan || 'room_only');
                    plans.push({
                        code: fallbackMeta.code,
                        title: fallbackMeta.title,
                        description: 'Selected room plan details will be confirmed during reservation.',
                        priceText: '',
                        primary: true,
                    });
                }

                return plans.filter((plan, index, list) => list.findIndex((item) => item.code === plan.code) === index);
            }

            function buildMealPlansHtml(plans) {
                return `
                    <p class="room-section-label">Meal Plans</p>
                    <div class="room-meal-plan-grid">
                        ${plans.map((plan) => `
                            <div class="room-meal-plan-card${plan.primary ? ' is-primary' : ''}">
                                <span class="room-meal-plan-code">${plan.code}</span>
                                <p class="room-meal-plan-name">${plan.title}</p>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            function amenityIconClass(token) {
                const value = String(token || '').toLowerCase().replace(/[_-]+/g, ' ').trim();

                if (/(wheelchair|accessible|ada|disabled|accessibility)/.test(value)) {
                    return 'fa-solid fa-wheelchair';
                }
                if (/(^|\b)(a\/c|ac|air\s*condition(?:ing)?|aircon|cooling)(\b|$)/.test(value)) {
                    return 'fa-solid fa-snowflake';
                }
                if (/(wifi|wi-fi|internet|broadband|connection)/.test(value)) {
                    return 'fa-solid fa-wifi';
                }
                if (/(bathroom|shower|bath|toilet|sink|bidet)/.test(value)) {
                    return 'fa-solid fa-shower';
                }
                if (/(coffee|tea|kettle|water|minibar|bar)/.test(value)) {
                    return 'fa-solid fa-mug-hot';
                }
                if (/(tv|television|entertainment|movie|streaming)/.test(value)) {
                    return 'fa-solid fa-tv';
                }
                if (/(desk|workspace|office|work|table|chair)/.test(value)) {
                    return 'fa-solid fa-desktop';
                }

                return 'fa-solid fa-star';
            }

            function categorizeRoomAmenity(token) {
                const value = String(token || '').toLowerCase();
                if (value.includes('tooth') || value.includes('shampoo') || value.includes('soap') || value.includes('razor') || value.includes('dryer') || value.includes('slipper') || value.includes('toiletr')) {
                    return 'Toiletries';
                }
                if (value.includes('wifi') || value.includes('internet') || value.includes('telephone') || value.includes('tv') || value.includes('smart')) {
                    return 'Internet and communications';
                }
                if (value.includes('coffee') || value.includes('kettle') || value.includes('bar') || value.includes('water') || value.includes('tea')) {
                    return 'Food and Drink';
                }
                if (value.includes('bath') || value.includes('shower') || value.includes('toilet') || value.includes('bidet') || value.includes('hot_water')) {
                    return 'Bathroom';
                }
                return 'Room amenities';
            }

            function buildAmenityCategoryHtml(categoryTitle, items, roomId) {
                if (items.length === 0) {
                    return '';
                }

                return `
                    <div class="room-amenity-category">
                        <div class="room-amenity-category-title">${categoryTitle}</div>
                        <ul class="room-amenity-list">
                            ${items.map((item, idx) => {
                                const safeItem = titleCaseToken(item);
                                const badge = /\bfree\b/i.test(safeItem) ? '<span class="room-amenity-badge">Free</span>' : '';
                                const iconClass = amenityIconClass(item);
                                return `
                                    <li class="room-amenity-item">
                                        <span class="room-amenity-label"><span class="room-amenity-icon" aria-hidden="true"><i class="${iconClass}"></i></span><span>${safeItem}</span>${badge}</span>
                                    </li>
                                `;
                            }).join('')}
                        </ul>
                    </div>
                `;
            }

            function buildRoomData(roomCard) {
                const roomId = roomCard.dataset.roomId || '';
                const roomName = roomCard.dataset.roomName || 'Room';
                const roomBed = roomCard.dataset.roomBed || 'Standard Bed';
                const roomSize = parseInt(roomCard.dataset.roomSize || '0', 10) || 0;
                const roomFloor = roomCard.dataset.roomFloor || '';
                const roomHasWindow = roomCard.dataset.roomHasWindow === '1';
                const roomNonSmoking = roomCard.dataset.roomNonSmoking !== '0';
                const roomChildPolicy = roomCard.dataset.roomChildPolicy || 'Children of all ages can stay in this room.';
                const roomExtraBedPolicy = roomCard.dataset.roomExtraBedPolicy || 'Extra beds and cots are not available for this room type.';
                const roomDescription = roomCard.dataset.roomDescription || '';
                const roomMealPlan = roomCard.dataset.roomMealPlan || '';
                const roomCurrency = roomCard.dataset.roomCurrency || 'MVR';
                const roomLink = roomCard.dataset.roomLink || '#';
                const roomImages = parseJsonArray(roomCard.dataset.roomImages);
                const roomAmenities = parseJsonArray(roomCard.dataset.roomAmenities);
                const bathroomAmenities = parseJsonArray(roomCard.dataset.bathroomAmenities);
                const maxOccupancy = roomCard.querySelector('.room-sleeps')?.textContent?.trim() || '1';
                const mealPlans = collectMealPlans(roomCard);

                return {
                    roomId,
                    roomName,
                    roomBed,
                    roomSize,
                    roomFloor,
                    roomHasWindow,
                    roomNonSmoking,
                    roomChildPolicy,
                    roomExtraBedPolicy,
                    roomDescription,
                    roomMealPlan,
                    roomCurrency,
                    roomLink,
                    roomImages,
                    roomAmenities,
                    bathroomAmenities,
                    maxOccupancy,
                    mealPlans,
                };
            }

            function populateModal(roomId) {
                const roomCard = document.querySelector(`.room-card[data-room-id="${roomId}"]`);
                if (!roomCard) {
                    return;
                }

                const roomData = buildRoomData(roomCard);

                contentArea.innerHTML = '';
                contentArea.className = 'room-details-content';
                const clone = template.content.cloneNode(true);

                const gallery = clone.querySelector('[data-gallery]');
                if (gallery) {
                    gallery.innerHTML = '';
                    const imagesToShow = roomData.roomImages.length > 0 ? roomData.roomImages.slice(0, 4) : [''];
                    imagesToShow.forEach((src, index) => {
                        const img = document.createElement('img');
                        img.src = src;
                        img.alt = roomData.roomName + ' image ' + (index + 1);
                        img.loading = 'lazy';
                        gallery.appendChild(img);
                    });
                }

                clone.querySelector('[data-title]').textContent = roomData.roomName;
                clone.querySelector('[data-bedinfo]').textContent = `${titleCaseToken(roomData.roomBed)} • Sleeps ${roomData.maxOccupancy}`;
                const summaryBadges = clone.querySelector('[data-summary-badges]');
                if (summaryBadges) {
                    const badgeItems = [
                        { icon: 'fa-regular fa-user', label: `Sleeps ${roomData.maxOccupancy}` },
                        roomData.roomHasWindow ? { icon: 'fa-regular fa-square', label: 'Window' } : null,
                        roomData.roomNonSmoking ? { icon: 'fa-solid fa-ban-smoking', label: 'Non-smoking' } : null,
                        roomData.roomSize > 0 ? { icon: 'fa-solid fa-ruler-combined', label: `${roomData.roomSize}m²` } : null,
                    ].filter(Boolean);

                    summaryBadges.innerHTML = badgeItems.map((badge) => `<span class="room-summary-badge"><i class="${badge.icon}" aria-hidden="true"></i><span>${badge.label}</span></span>`).join('');
                }

                const descriptionEl = clone.querySelector('[data-description]');
                if (descriptionEl) {
                    descriptionEl.textContent = roomData.roomDescription !== ''
                        ? roomData.roomDescription
                        : `${roomData.roomName} features ${titleCaseToken(roomData.roomBed)}, practical in-room comforts, and a straightforward stay setup for couples, families, or short work trips.`;
                }

                clone.querySelector('[data-note]').textContent = roomData.roomMealPlan !== ''
                    ? `Meal plan: ${mealPlanMeta(roomData.roomMealPlan).title}. Extra bed and child policies are listed below.`
                    : 'Meal plan and extra bed conditions vary by selected rate. Full room and child policy details are listed below.';

                const mealPlansDiv = clone.querySelector('[data-meal-plans]');
                if (mealPlansDiv) {
                    mealPlansDiv.innerHTML = buildMealPlansHtml(roomData.mealPlans);
                }

                const specsDiv = clone.querySelector('[data-specs]');
                if (specsDiv) {
                    const roomSizeText = roomData.roomSize > 0 ? `${roomData.roomSize}m²` : 'Size not specified';
                    const floorText = roomData.roomFloor !== '' ? `Floor ${roomData.roomFloor}` : 'Floor not specified';
                    specsDiv.innerHTML = `
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M9 3h6v2H9z"/><path d="M7 5h10v12H7z"/><path d="M8 18h8v2H8z"/></svg>
                            <span>${roomData.roomHasWindow ? 'Has window(s)' : 'No window(s)'}</span>
                        </div>
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M3 10h18M3 14h18M3 18h18"/></svg>
                            <span>${roomData.roomNonSmoking ? 'Non-smoking' : 'Smoking allowed'}</span>
                        </div>
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9m0 16c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7m.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                            <span>${roomSizeText} | ${floorText}</span>
                        </div>
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1010 10A10 10 0 0012 2m6.5 6.5H13V8h5.5v2.5zm-3 8h-3v3h3v-3z"/></svg>
                            <span>Free Wi-Fi</span>
                        </div>
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M12 2c-5.33 4.55-8 8.48-8 11.8 0 4.98 3.8 8.2 8 8.2s8-3.22 8-8.2c0-3.32-2.67-7.25-8-11.8z"/></svg>
                            <span>Air conditioning</span>
                        </div>
                        <div class="room-quick-spec-item">
                            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2m0 16H5V5h14v14z"/></svg>
                            <span>Private Bathroom</span>
                        </div>
                    `;
                }

                const amenitiesDiv = clone.querySelector('[data-amenities]');
                if (amenitiesDiv) {
                    const categories = {
                        'Toiletries': [],
                        'Internet and communications': [],
                        'Food and Drink': [],
                        'Bathroom': [],
                        'Room amenities': [],
                    };

                    roomData.roomAmenities.forEach((token) => {
                        const category = categorizeRoomAmenity(token);
                        categories[category].push(token);
                    });
                    roomData.bathroomAmenities.forEach((token) => {
                        categories['Bathroom'].push(token);
                    });

                    const orderedCategories = [
                        'Toiletries',
                        'Internet and communications',
                        'Food and Drink',
                        'Bathroom',
                        'Room amenities',
                    ];

                    let categoriesHtml = '';
                    orderedCategories.forEach((categoryTitle) => {
                        const uniqueItems = Array.from(new Set(categories[categoryTitle]));
                        categoriesHtml += buildAmenityCategoryHtml(categoryTitle, uniqueItems, roomData.roomId);
                    });

                    if (categoriesHtml.trim() === '') {
                        categoriesHtml = buildAmenityCategoryHtml('Room amenities', ['Amenities will be updated soon'], roomData.roomId);
                    }

                    amenitiesDiv.innerHTML = categoriesHtml;
                }

                const policiesDiv = clone.querySelector('[data-policies]');
                if (policiesDiv) {
                    policiesDiv.innerHTML = `
                        <div class="room-policy-section">
                            <h4 class="room-policy-title">Child Policies</h4>
                            <p class="room-policy-text">${roomData.roomChildPolicy}</p>
                        </div>
                        <div class="room-policy-section">
                            <h4 class="room-policy-title">Room Policy</h4>
                            <p class="room-policy-text">${roomData.roomMealPlan !== '' ? `This room is currently offered under ${mealPlanMeta(roomData.roomMealPlan).title}. Final inclusions depend on the selected rate option.` : 'Meal plan and inclusions may vary by selected room rate. Please review the rate option before reserving.'}</p>
                        </div>
                        <div class="room-policy-section">
                            <h4 class="room-policy-title">Cots and Extra Beds</h4>
                            <p class="room-policy-text">${roomData.roomExtraBedPolicy}</p>
                        </div>
                    `;
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

            modal?.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('is-active');
                }
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
                    sectionTabs.style.setProperty('scroll-margin-top', `calc(var(--property-header-offset) + var(--property-search-shell-height) + 12px)`);
                }
            };

            syncStickyMetrics();
            window.addEventListener('resize', syncStickyMetrics);
            window.addEventListener('load', syncStickyMetrics);

            const topCheckin = document.getElementById('topCheckin');
            const topCheckout = document.getElementById('topCheckout');
            const topGuests = document.getElementById('topGuests');
            const topRooms = document.querySelector('#propertyTopSearch input[name="rooms"]');
            const topAdults = document.querySelector('#propertyTopSearch input[name="adults"]');
            const topChildren = document.querySelector('#propertyTopSearch input[name="children"]');
            const availCheckin = document.getElementById('availCheckin');
            const availCheckout = document.getElementById('availCheckout');
            const availRooms = document.getElementById('availRooms');
            const availAdults = document.getElementById('availAdults');
            const availChildren = document.getElementById('availChildren');
            const transferOptionSelect = document.getElementById('transferOptionSelect');
            const transferOptionIcon = document.getElementById('transferOptionIcon');
            const transferSummary = document.querySelector('[data-transfer-summary]');
            const startingTotal = document.querySelector('[data-starting-total]');
            const startingSoldout = document.querySelector('[data-starting-soldout]');
            const rateSummaries = Array.from(document.querySelectorAll('[data-rate-summary]'));
            const reserveButtons = Array.from(document.querySelectorAll('.reserve-btn[data-base-room-link]'));
            const dateValidationMessage = document.querySelector('[data-availability-date-error]');
            const todayDate = @json((string) ($todayDate ?? now()->toDateString()));
            const unavailableDates = @json($unavailableDates ?? ['blocked' => [], 'reserved' => []]);
            const blockedDateSet = new Set(Array.isArray(unavailableDates?.blocked) ? unavailableDates.blocked : []);
            const reservedDateSet = new Set(Array.isArray(unavailableDates?.reserved) ? unavailableDates.reserved : []);

            if (startingTotal || rateSummaries.length > 0 || reserveButtons.length > 0) {
                const pluralize = (value, singular) => `${value} ${singular}${value === 1 ? '' : 's'}`;
                const clampInt = (value, minimum) => {
                    const parsed = Number(value);
                    if (!Number.isFinite(parsed)) return minimum;
                    return Math.max(minimum, Math.floor(parsed));
                };
                const toDateOnly = (value) => {
                    const normalized = String(value || '').trim();
                    if (normalized === '') {
                        return '';
                    }

                    if (normalized.length >= 10) {
                        return normalized.slice(0, 10);
                    }

                    return normalized;
                };

                const setDateValidationError = (message) => {
                    const normalizedMessage = String(message || '').trim();
                    [topCheckin, topCheckout, availCheckin, availCheckout].forEach((input) => {
                        if (!input) {
                            return;
                        }
                        input.setCustomValidity(normalizedMessage);
                    });

                    if (!dateValidationMessage) {
                        return;
                    }

                    dateValidationMessage.textContent = normalizedMessage;
                    dateValidationMessage.style.display = normalizedMessage === '' ? 'none' : 'block';
                };

                const validateDateRange = (checkinValue, checkoutValue) => {
                    const checkinDate = toDateOnly(checkinValue);
                    const checkoutDate = toDateOnly(checkoutValue);

                    if (checkinDate === '' || checkoutDate === '') {
                        setDateValidationError('');
                        return true;
                    }

                    if (checkinDate < todayDate) {
                        setDateValidationError('Check-in date cannot be in the past.');
                        return false;
                    }

                    if (checkoutDate <= checkinDate) {
                        setDateValidationError('Check-out date must be after check-in date.');
                        return false;
                    }

                    let cursor = new Date(checkinDate + 'T00:00:00');
                    const checkout = new Date(checkoutDate + 'T00:00:00');
                    while (cursor < checkout) {
                        const dateKey = cursor.toISOString().slice(0, 10);
                        if (blockedDateSet.has(dateKey) || reservedDateSet.has(dateKey)) {
                            setDateValidationError(`Selected dates include unavailable day (${dateKey}). Please choose different dates.`);
                            return false;
                        }
                        cursor.setDate(cursor.getDate() + 1);
                    }

                    setDateValidationError('');
                    return true;
                };

                const applyReserveButtonDateState = (isDateRangeValid) => {
                    reserveButtons.forEach((button) => {
                        const wasDateDisabled = button.dataset.dateDisabled === '1';
                        const isVendorDisabled = button.getAttribute('aria-disabled') === 'true' && !wasDateDisabled;

                        if (!isDateRangeValid && !isVendorDisabled) {
                            button.dataset.dateDisabled = '1';
                            button.setAttribute('aria-disabled', 'true');
                            button.style.pointerEvents = 'none';
                            button.style.opacity = '.45';
                            return;
                        }

                        if (isDateRangeValid && wasDateDisabled) {
                            button.dataset.dateDisabled = '0';
                            button.removeAttribute('aria-disabled');
                            button.style.pointerEvents = '';
                            button.style.opacity = '';
                        }
                    });
                };

                const calculateNights = (checkinValue, checkoutValue) => {
                    if (!checkinValue || !checkoutValue) {
                        return 1;
                    }

                    const checkinDate = new Date(checkinValue + 'T00:00:00');
                    const checkoutDate = new Date(checkoutValue + 'T00:00:00');
                    if (Number.isNaN(checkinDate.getTime()) || Number.isNaN(checkoutDate.getTime())) {
                        return 1;
                    }

                    const diffMs = checkoutDate.getTime() - checkinDate.getTime();
                    const nights = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
                    return nights > 0 ? nights : 1;
                };

                const syncTopGuestText = (adultsCount, childrenCount, roomsCount) => {
                    if (!topGuests) {
                        return;
                    }

                    topGuests.value = `${adultsCount} adults, ${childrenCount} children, ${pluralize(roomsCount, 'room')}`;
                };

                const syncFromAvailability = () => {
                    if (topCheckin && availCheckin) topCheckin.value = availCheckin.value;
                    if (topCheckout && availCheckout) topCheckout.value = availCheckout.value;
                    if (topRooms && availRooms) topRooms.value = String(clampInt(availRooms.value, 1));
                    if (topAdults && availAdults) topAdults.value = String(clampInt(availAdults.value, 1));
                    if (topChildren && availChildren) topChildren.value = String(clampInt(availChildren.value, 0));
                };

                const syncAvailabilityDates = () => {
                    if (availCheckin && topCheckin) availCheckin.value = topCheckin.value;
                    if (availCheckout && topCheckout) availCheckout.value = topCheckout.value;
                };

                const addDaysToDateString = (dateString, days) => {
                    const normalized = toDateOnly(dateString);
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

                const syncCheckoutBounds = () => {
                    const checkinValue = toDateOnly(topCheckin?.value || availCheckin?.value || '');
                    const minCheckout = checkinValue !== '' ? addDaysToDateString(checkinValue, 1) : todayDate;

                    [topCheckout, availCheckout].forEach((checkoutInput) => {
                        if (!checkoutInput) {
                            return;
                        }

                        checkoutInput.min = minCheckout;
                        const currentValue = toDateOnly(checkoutInput.value || '');
                        if (currentValue !== '' && currentValue < minCheckout) {
                            checkoutInput.value = minCheckout;
                        }
                    });
                };

                const refreshTotals = () => {
                    const checkinValue = (topCheckin?.value || '').trim();
                    const checkoutValue = (topCheckout?.value || '').trim();
                    const roomsCount = clampInt(topRooms?.value || 1, 1);
                    const adultsCount = clampInt(topAdults?.value || 1, 1);
                    const childrenCount = clampInt(topChildren?.value || 0, 0);
                    const nights = calculateNights(checkinValue, checkoutValue);
                    const selectedTransferOption = transferOptionSelect?.value || '';
                    const selectedTransferElement = transferOptionSelect
                        ? transferOptionSelect.options[transferOptionSelect.selectedIndex]
                        : null;
                    const transferAdultRate = Number(selectedTransferElement?.dataset?.adultRate || 0);
                    const transferChildRate = Number(selectedTransferElement?.dataset?.childRate || 0);
                    const transferIconClass = String(selectedTransferElement?.dataset?.iconClass || 'fa-solid fa-route').trim() || 'fa-solid fa-route';
                    const transferTotal = (adultsCount * transferAdultRate) + (childrenCount * transferChildRate);

                    syncTopGuestText(adultsCount, childrenCount, roomsCount);
                    const isDateRangeValid = validateDateRange(checkinValue, checkoutValue);

                    if (transferSummary) {
                        if (selectedTransferOption !== '') {
                            transferSummary.textContent = `Transfer charge estimate: MVR ${transferTotal.toFixed(2)} total (${adultsCount} adults, ${childrenCount} children).`;
                        } else {
                            transferSummary.textContent = 'Optional transfer can be added to room reservation.';
                        }
                    }

                    if (transferOptionIcon) {
                        if (selectedTransferOption !== '') {
                            transferOptionIcon.innerHTML = `<i class="${transferIconClass}" aria-hidden="true"></i> Selected mode`;
                        } else {
                            transferOptionIcon.innerHTML = '<i class="fa-solid fa-route" aria-hidden="true"></i> Mode icon';
                        }
                    }

                    if (startingTotal) {
                        const currencyCode = String(startingTotal.dataset.startingCurrency || 'MVR').trim() || 'MVR';
                        const nightlyRate = Number(startingTotal.dataset.startingNightly || 0);
                        const total = nightlyRate * nights;
                        startingTotal.textContent = `${currencyCode} ${total.toFixed(2)} total 1 room, ${pluralize(nights, 'night')}`;
                    }

                    rateSummaries.forEach((summary) => {
                        const currencyCode = String(summary.dataset.rateCurrency || 'MVR').trim() || 'MVR';
                        const nightlyRate = Number(summary.dataset.nightlyRate || 0);
                        const total = nightlyRate * nights;
                        summary.textContent = `${currencyCode} ${total.toFixed(2)} total 1 room, ${pluralize(nights, 'night')}`;
                    });

                    reserveButtons.forEach((button) => {
                        const baseRoomLink = String(button.dataset.baseRoomLink || '').trim();
                        if (baseRoomLink === '') {
                            return;
                        }

                        const nightlyRate = Number(button.dataset.rateNightly || 0).toFixed(2);
                        const mealPlan = String(button.dataset.mealPlan || '').trim();
                        const url = new URL(baseRoomLink, window.location.origin);
                        url.searchParams.set('checkin', checkinValue);
                        url.searchParams.set('checkout', checkoutValue);
                        url.searchParams.set('rooms', String(roomsCount));
                        url.searchParams.set('adults', String(adultsCount));
                        url.searchParams.set('children', String(childrenCount));
                        url.searchParams.set('rate_nightly', nightlyRate);
                        if (mealPlan !== '') {
                            url.searchParams.set('meal_plan', mealPlan);
                        }
                        if (selectedTransferOption !== '') {
                            url.searchParams.set('transfer_option', selectedTransferOption);
                            url.searchParams.set('transfer_adult_rate', transferAdultRate.toFixed(2));
                            url.searchParams.set('transfer_child_rate', transferChildRate.toFixed(2));
                            url.searchParams.set('transfer_total', transferTotal.toFixed(2));
                        } else {
                            url.searchParams.delete('transfer_option');
                            url.searchParams.delete('transfer_adult_rate');
                            url.searchParams.delete('transfer_child_rate');
                            url.searchParams.delete('transfer_total');
                        }

                        button.href = url.pathname + '?' + url.searchParams.toString();
                    });

                    applyReserveButtonDateState(isDateRangeValid);

                    if (startingSoldout) {
                        const hasEnabledReserve = reserveButtons.some((button) => button.getAttribute('aria-disabled') !== 'true');
                        startingSoldout.classList.toggle('is-hidden', hasEnabledReserve);
                    }
                };

                [topCheckin, topCheckout].forEach((input) => {
                    if (!input) return;
                    input.addEventListener('change', () => {
                        syncAvailabilityDates();
                        syncCheckoutBounds();
                        refreshTotals();
                    });
                });

                [availCheckin, availCheckout, availRooms, availAdults, availChildren].forEach((input) => {
                    if (!input) return;
                    input.addEventListener('input', () => {
                        syncFromAvailability();
                        syncCheckoutBounds();
                        refreshTotals();
                    });
                    input.addEventListener('change', () => {
                        syncFromAvailability();
                        syncCheckoutBounds();
                        refreshTotals();
                    });
                });

                if (transferOptionSelect) {
                    transferOptionSelect.addEventListener('change', refreshTotals);
                }

                syncCheckoutBounds();
                refreshTotals();
            }

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
                link.addEventListener('click', () => {
                    activate(link);
                });
            });

            window.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync);
            sync();
        })();

        (function () {
            const copyShareBtn = document.querySelector('[data-copy-share-link]');
            if (!copyShareBtn) {
                return;
            }

            copyShareBtn.addEventListener('click', async function () {
                const shareUrl = this.getAttribute('data-copy-share-link') || window.location.href;
                try {
                    await navigator.clipboard.writeText(shareUrl);
                    this.style.background = '#d7f0e4';
                    setTimeout(() => {
                        this.style.background = '#f8fdff';
                    }, 1500);
                } catch (e) {
                    window.prompt('Copy this link', shareUrl);
                }
            });
        })();
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const mapEl = document.getElementById('property-location-map');
            if (!mapEl || typeof window.L === 'undefined') return;
            const lat = parseFloat(mapEl.dataset.lat);
            const lng = parseFloat(mapEl.dataset.lng);
            const hasCoords = !isNaN(lat) && !isNaN(lng);
            const defaultLat = hasCoords ? lat : 3.2028;
            const defaultLng = hasCoords ? lng : 73.2207;
            const zoom = hasCoords ? 14 : 7;
            const map = window.L.map(mapEl, { scrollWheelZoom: false, zoomControl: true })
                .setView([defaultLat, defaultLng], zoom);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);
            if (hasCoords) {
                window.L.marker([defaultLat, defaultLng]).addTo(map);
            }
        })();
    </script>
</body>
</html>