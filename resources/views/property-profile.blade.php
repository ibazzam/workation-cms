<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (string) ($property->name ?? 'Property') }} | Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f3f8f5;
            --ink: #152738;
            --muted: #5f7488;
            --line: #d5e2ec;
            --surface: #ffffff;
            --brand: #0f6179;
            --accent: #f3a337;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 0%, #d8ece6 0, #d8ece600 30%),
                radial-gradient(circle at 92% 6%, #ffe7c6 0, #ffe7c600 30%),
                var(--bg);
        }

        .page { width: min(1180px, calc(100% - 24px)); margin: 14px auto 28px; }

        .hero {
            border: 1px solid #cbe0ea;
            border-radius: 18px;
            background: linear-gradient(130deg, #0f6179 0%, #1d848c 58%, #2f9891 100%);
            color: #ecfcff;
            padding: 14px 16px;
            box-shadow: 0 20px 36px rgba(15, 88, 113, 0.22);
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
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            align-items: start;
        }

        .section {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            padding: 14px;
        }

        .section h2 { margin: 0; font-size: 1.04rem; }

        .gallery-shell {
            margin-top: 10px;
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
            grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.9fr);
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
            border: 1px solid #d8e6ef;
            border-radius: 13px;
            background: #f8fbfe;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .amenities-head {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1d425a;
            font-size: 0.98rem;
            font-weight: 700;
            margin: 0;
        }

        .amenities-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .amenity-group {
            border: 1px solid #dbe8f1;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .amenity-group-title {
            margin: 0;
            font-size: 0.86rem;
            font-weight: 700;
            color: #234c66;
        }

        .amenity-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .amenity-list li {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            color: #35566c;
            font-size: 0.81rem;
            line-height: 1.35;
        }

        .amenity-list-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .amenity-pill {
            border: 1px solid #cce1ec;
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 0.67rem;
            font-weight: 600;
            color: #2a607c;
            background: #ecf6fb;
            white-space: nowrap;
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
            width: 19px;
            height: 19px;
            border-radius: 6px;
            border: 1px solid #9cc2d6;
            background: linear-gradient(135deg, #0f6179 0%, #2e8e8f 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 19px;
            margin-top: 1px;
            font-size: 0.9rem;
            line-height: 1;
        }

        .facility-icon svg {
            width: 12px;
            height: 12px;
            stroke: #e9faff;
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
            text-decoration: none;
            border: 1px solid #3a82aa;
            border-radius: 8px;
            background: #ffffff;
            color: #3a82aa;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 8px 16px;
            cursor: pointer;
        }

        .reviews-load-btn:hover {
            background: #f0f7fc;
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
            display: inline-block;
            width: 14px;
            height: 14px;
            flex: 0 0 14px;
            margin-right: 6px;
        }

        .room-amenity-icon svg {
            width: 100%;
            height: 100%;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
            vertical-align: middle;
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
            border: 1px solid #d8e7f1;
            background: #f8fcff;
            color: #3a82aa;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }

        .room-offer-expand-btn:hover {
            background: #f3f8fc;
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
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 24px;
        }

        .room-details-gallery {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .room-details-gallery img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
        }

        .room-details-sidebar {
            display: grid;
            gap: 20px;
            align-content: start;
            max-height: 85vh;
            overflow-y: auto;
            padding-right: 12px;
        }

        .room-details-header {
            display: grid;
            gap: 8px;
        }

        .room-details-title {
            font-size: 1.3rem;
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
            font-size: 0.8rem;
            color: #666;
            line-height: 1.4;
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

        .room-amenity-category {
            display: grid;
            gap: 8px;
        }

        .room-amenity-category-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f3d52;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .room-amenity-list {
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .room-amenity-item {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #2c5169;
        }

        .room-amenity-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #3a82aa;
        }

        .room-amenity-item label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex: 1;
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

        .room-policy-section {
            padding: 12px;
            border: 1px solid #e0ebf3;
            border-radius: 8px;
            background: #fffaf5;
        }

        .room-policy-title {
            font-size: 0.85rem;
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
        }

        .policies-section { margin-top: 12px; }

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
        .rooms-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .rooms-sub { margin: 0; color: #5d7487; font-size: 0.83rem; }

        .section-tabs {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            border: 1px solid #d6e5ef;
            border-radius: 12px;
            background: #f8fcff;
            padding: 8px;
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

        .room-sleeps {
            color: #244960;
            font-size: 0.84rem;
            font-weight: 700;
            text-align: center;
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

        .reserve-btn {
            text-decoration: none;
            border: 1px solid #2f6ed8;
            border-radius: 8px;
            background: linear-gradient(135deg, #3a7af2 0%, #1f63d0 100%);
            color: #f4f8ff;
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
            .hero-avail-form { grid-template-columns: 1fr 120px 120px 65px 65px auto; }
        }

        @media (max-width: 680px) {
            .hero-avail-form { grid-template-columns: 1fr 1fr; }
            .hero-avail-btn { grid-column: 1 / -1; width: 100%; }
        }
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
        }

        @media (max-width: 680px) {
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
            .room-card { grid-template-columns: 1fr; }
            .room-media { min-height: 210px; }
            .room-offer-head,
            .room-offer-row { grid-template-columns: 1fr; }
            .room-offer-head span,
            .room-offer-row > div { border-right: 0; }
            .room-price-box { grid-template-columns: 1fr; }
            .facility-grid { grid-template-columns: 1fr; }
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
</head>
<body>
    @php
        $propertyMedia = $propertyMedia ?? collect();
        $rooms = $rooms ?? collect();
        $roomMediaByRoom = $roomMediaByRoom ?? collect();
        $propertyFacilities = $propertyFacilities ?? collect();
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
        $mediaUrl = $mediaUrl ?? static fn () => null;
        $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
        $basePrice = number_format((float) ($property->base_price ?? 0), 2);
        $description = trim((string) ($property->description ?? ''));
        $listingCategory = strtoupper(str_replace('_', ' ', (string) ($property->listing_category ?? 'ACCOMMODATION')));
        $starCount = $ratingValue > 0 ? max(1, min(5, (int) round($ratingValue))) : 4;
        $starString = str_repeat('★', $starCount) . str_repeat('☆', 5 - $starCount);
        $ratingOutOfTen = $ratingValue > 0 ? min(10, $ratingValue * 2) : 0;
        $reviewLabel = $ratingOutOfTen >= 9.0 ? 'Excellent' : ($ratingOutOfTen >= 8.0 ? 'Great' : ($ratingOutOfTen > 0 ? 'Good' : 'No rating yet'));
        $mapQuery = $locationLine !== '' ? $locationLine : ((string) ($property->name ?? 'Workation Maldives'));
        $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($mapQuery);
        $cheapestRoom = $rooms->sortBy(static fn ($room) => (float) ($room->base_price ?? INF))->first();
        $cheapestRoomId = $cheapestRoom ? (int) ($cheapestRoom->id ?? 0) : 0;
        $cheapestRoomPrice = $cheapestRoom ? number_format((float) ($cheapestRoom->base_price ?? 0), 2) : null;
        $selectRoomsTarget = $cheapestRoomId > 0 ? ('#room-' . $cheapestRoomId) : '#rooms-section';
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
            $icon = \App\Support\UniformIconSystem::getAmenityIcon($facility);
            return '<span role="img" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>';
        };
    @endphp

    <main class="page">
        <section class="hero" aria-label="Property summary">
            <div class="hero-top">
                <div>
                    <div class="hero-title-row">
                        <h1>{{ (string) ($property->name ?? 'Property') }}</h1>
                        <span class="hero-stars" aria-label="Property stars">{{ $starString }}</span>
                    </div>
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
                <span class="price-chip">From {{ $currency }} {{ $basePrice }} / night</span>
            </div>

            <div class="hero-stats">
                <div class="hero-stat"><div class="k">Category</div><div class="v">{{ $listingCategory }}</div></div>
                <div class="hero-stat"><div class="k">Starting Price</div><div class="v">{{ $currency }} {{ $basePrice }}</div></div>
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
                        <input id="availCheckin" type="date" name="checkin" value="{{ (string) ($prefill['checkin'] ?? '') }}">
                    </div>
                    <div class="hero-avail-field">
                        <label for="availCheckout">Check-out</label>
                        <input id="availCheckout" type="date" name="checkout" value="{{ (string) ($prefill['checkout'] ?? '') }}">
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
            </div>
        </section>

        <div class="layout">
                <section class="section" aria-label="Property gallery">
                    <h2>Property Gallery</h2>
                    @php
                        $galleryItems = $propertyMedia->take(12)->values();
                        $initialBanner = $galleryItems->isNotEmpty() ? ($mediaUrl($galleryItems->first(), 'banner') ?? '') : '';
                    @endphp
                    <div class="gallery-shell" data-property-gallery>
                        @if ($galleryItems->isNotEmpty())
                            <div class="gallery-banner-wrap">
                                <img id="propertyGalleryBanner" class="gallery-banner" src="{{ $initialBanner }}" alt="Property image 1" loading="lazy" onerror="this.onerror=null; this.style.display='none';">
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
                                        <img src="{{ $thumbUrl }}" alt="Property thumbnail {{ $index + 1 }}" loading="lazy" onerror="this.onerror=null; this.src='{{ $bannerUrl }}';">
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

                    <aside class="review-card" aria-label="Review and ratings">
                        <h2 class="block-title">Review & Ratings</h2>
                        <div class="review-score">
                            <span class="review-score-badge">{{ $ratingOutOfTen > 0 ? number_format($ratingOutOfTen, 1) . '/10' : 'N/A' }}</span>
                            <span class="review-score-text">{{ $reviewLabel }}</span>
                        </div>
                        <p class="review-note">
                            {{ $ratingUsers > 0 ? ($ratingUsers . ' guest reviews available for this property.') : 'Reviews are being collected. Be the first to leave feedback after your stay.' }}
                        </p>
                        <a class="review-link" href="#guest-reviews-section">{{ $ratingUsers > 0 ? number_format($ratingUsers) : '0' }} reviews</a>
                        <div class="summary-row"><span>Total Reviews</span><strong>{{ $ratingUsers > 0 ? number_format($ratingUsers) : '0' }}</strong></div>
                        <div class="summary-row"><span>Average Score</span><strong>{{ $ratingOutOfTen > 0 ? number_format($ratingOutOfTen, 1) . ' / 10' : 'N/A' }}</strong></div>
                        <div class="summary-row"><span>Category</span><strong>{{ $listingCategory }}</strong></div>

                        <section class="surroundings-card" aria-label="Surroundings">
                            <h3 class="surroundings-title">Surroundings</h3>
                            <ul class="surroundings-list">
                                @foreach ($surroundings as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                            <a class="review-link" href="{{ $mapUrl }}" target="_blank" rel="noopener">View on map</a>
                        </section>
                    </aside>
                </section>
        </div>

        <nav class="section-tabs" aria-label="Property content navigation">
            <a class="section-tab" href="#rooms-section">Rooms</a>
            <a class="section-tab" href="#guest-reviews-section">Guest Reviews</a>
            <a class="section-tab" href="#services-amenities-section">Services & Amenities</a>
            <a class="section-tab" href="#policies-section">Policies</a>
        </nav>

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
                        $hasBreakfast = str_contains($amenitiesText, 'breakfast');
                        $breakfastLabel = $hasBreakfast ? 'With Breakfast' : 'Without Breakfast';
                        $roomCurrency = strtoupper((string) ($room->currency ?? $currency));
                        $roomPrice = number_format((float) ($room->base_price ?? 0), 2);
                        $bedType = trim((string) ($room->bed_type ?? 'Standard Bed'));
                        $roomSizeSqm = (int) ($room->room_size_sqm ?? 0);
                        $floorInfo = trim((string) ($room->floor_info ?? ''));
                        $hasWindow = (int) ($room->has_window ?? 1) === 1;
                        $nonSmoking = (int) ($room->non_smoking ?? 1) === 1;
                        $childPolicy = trim((string) ($room->child_policy ?? ''));
                        $extraBedPolicy = trim((string) ($room->extra_bed_policy ?? ''));
                        $roomAmenitiesRaw = collect(preg_split('/[,\n]+/', (string) ($room->amenities ?? '')) ?: [])->map(static fn ($item) => trim((string) $item))->filter()->values();
                        $bathAmenitiesRaw = collect(preg_split('/[,\n]+/', (string) ($room->bathroom_amenities ?? '')) ?: [])->map(static fn ($item) => trim((string) $item))->filter()->values();
                        $roomLink = '/room/' . $roomId . '?checkin=' . urlencode((string) ($prefill['checkin'] ?? '')) . '&checkout=' . urlencode((string) ($prefill['checkout'] ?? '')) . '&rooms=' . (int) ($prefill['rooms'] ?? 1) . '&adults=' . (int) ($prefill['adults'] ?? 2) . '&children=' . (int) ($prefill['children'] ?? 0);
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
                        data-room-link="{{ $roomLink }}"
                        data-room-images='@json($roomImages->all())'
                        data-room-amenities='@json($roomAmenitiesRaw->all())'
                        data-bathroom-amenities='@json($bathAmenitiesRaw->all())'
                    >
                        <a class="room-media-link" href="#" data-open-room-modal="{{ $roomId }}">
                            <div class="room-media">
                                <img src="{{ $roomThumb ?? '' }}" alt="{{ (string) ($room->name ?? 'Room') }}" loading="lazy">
                                <span class="room-tag">{{ $breakfastLabel }}</span>
                            </div>
                        </a>
                        <div class="room-body">
                            <h3><a class="room-name-link" href="#" data-open-room-modal="{{ $roomId }}">{{ (string) ($room->name ?? 'Room') }}</a></h3>
                            @php
                                $roomOldPrice = number_format(((float) ($room->base_price ?? 0)) * 1.08, 2);
                            @endphp
                            <div class="room-offer-table" aria-label="Room rate options" data-room-id="{{ $roomId }}">
                                <div class="room-offer-head">
                                    <span>Your Choices</span>
                                    <span>Sleeps</span>
                                    <span>Today's Price</span>
                                </div>
                                <div class="room-offer-row">
                                    <div class="room-choices">
                                        <span>{{ $breakfastLabel }}</span>
                                        <span>{{ $hasBreakfast ? 'Free breakfast included' : 'Breakfast optional' }}</span>
                                        <span>Instant confirmation</span>
                                        <span>Prepay online or pay at property</span>
                                    </div>
                                    <div class="room-sleeps">{{ (int) ($room->max_occupancy ?? 1) }}</div>
                                    <div>
                                        <div class="room-price-box">
                                            <div>
                                                <div class="room-price-old">{{ $roomCurrency }} {{ $roomOldPrice }}</div>
                                                <div class="room-price-now">{{ $roomCurrency }} {{ $roomPrice }}</div>
                                                @php
                                                    $roomOldPriceValue = floatval($roomOldPrice);
                                                    $roomPriceValue = floatval($roomPrice);
                                                    $discountPercent = $roomOldPriceValue > 0
                                                        ? round((($roomOldPriceValue - $roomPriceValue) / $roomOldPriceValue) * 100)
                                                        : 0;
                                                @endphp
                                                @if ($discountPercent > 0)
                                                    <span class="discount-badge">{{ $discountPercent }}% off</span>
                                                @endif
                                            </div>
                                            <a class="reserve-btn" href="{{ $roomLink }}">Reserve</a>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $alternateBreakfastLabel = $hasBreakfast ? 'Without Breakfast' : 'With Breakfast';
                                    $alternatePrice = $hasBreakfast 
                                        ? number_format(((float) ($room->base_price ?? 0)) * 0.85, 2)
                                        : number_format(((float) ($room->base_price ?? 0)) * 1.15, 2);
                                    $alternateOldPrice = number_format((floatval($alternatePrice)) * 1.08, 2);
                                @endphp
                                <div class="room-offer-row is-hidden">
                                    <div class="room-choices">
                                        <span>{{ $alternateBreakfastLabel }}</span>
                                        <span>{{ $hasBreakfast ? 'Breakfast optional' : 'Free breakfast included' }}</span>
                                        <span>Instant confirmation</span>
                                        <span>Prepay online or pay at property</span>
                                    </div>
                                    <div class="room-sleeps">{{ (int) ($room->max_occupancy ?? 1) }}</div>
                                    <div>
                                        <div class="room-price-box">
                                            <div>
                                                <div class="room-price-old">{{ $roomCurrency }} {{ $alternateOldPrice }}</div>
                                                <div class="room-price-now">{{ $roomCurrency }} {{ $alternatePrice }}</div>
                                                @php
                                                    $alternateOldPriceValue = floatval($alternateOldPrice);
                                                    $alternatePriceValue = floatval($alternatePrice);
                                                    $altDiscountPercent = $alternateOldPriceValue > 0
                                                        ? round((($alternateOldPriceValue - $alternatePriceValue) / $alternateOldPriceValue) * 100)
                                                        : 0;
                                                @endphp
                                                @if ($altDiscountPercent > 0)
                                                    <span class="discount-badge">{{ $altDiscountPercent }}% off</span>
                                                @endif
                                            </div>
                                            <a class="reserve-btn" href="{{ $roomLink }}">Reserve</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="room-offer-expand">
                                    <button class="room-offer-expand-btn" data-expand-toggle="room-{{ $roomId }}">
                                        Show More Rates
                                    </button>
                                </div>
                            </div>

                            <ul class="room-side-details">
                                <li><span class="room-amenity-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v.5M3 12h.5M20.5 12h.5M12 20v.5M5.5 5.5l.35.35M18.15 18.15l.35.35M18.5 5.5l-.35.35M5.85 18.15l-.35.35"/><circle cx="12" cy="12" r="4"/></svg></span><span>{{ $bedType }}</span></li>
                                <li><span class="room-amenity-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11h18M6 6h12v8H6zM8 18h8"/></svg></span><span>Occupancy: {{ (int) ($room->max_occupancy ?? 1) }} guests</span></li>
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

        <section id="policies-section" class="section policies-section" aria-label="Property policies">
            <h2>Property Policies</h2>
            <div class="policies-grid">
                <div class="policy-label">Check-in and Check-out</div>
                <div class="policy-value">Check-in after 15:00 and check-out before 12:00. Front desk support may be available 24/7 depending on operations.</div>

                <div class="policy-label">Child Policies</div>
                <div class="policy-value">Children are welcome. Additional fees may apply based on room occupancy and selected meal plan.</div>

                <div class="policy-label">Cots and Extra Beds</div>
                <div class="policy-value">Cot and extra bed availability depends on room category and should be confirmed during reservation.</div>

                <div class="policy-label">Breakfast</div>
                <div class="policy-value">Breakfast inclusion varies by selected room offer. Please check the room choice details before reserving.</div>

                <div class="policy-label">Deposit Policy</div>
                <div class="policy-value">A deposit may be required to secure selected offers during peak periods.</div>

                <div class="policy-label">Pets</div>
                <div class="policy-value">Please contact property support for the latest pet policy before arrival.</div>
            </div>
        </section>

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
                <p class="room-bed-note" data-note></p>
            </div>
            <div class="room-quick-specs" data-specs></div>
            <div data-amenities></div>
            <div data-policies></div>
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

            function setActive(index) {
                const target = thumbs[index];
                if (!target) {
                    return;
                }

                thumbs.forEach((thumb, thumbIndex) => {
                    const active = thumbIndex === index;
                    thumb.classList.toggle('is-active', active);
                    thumb.setAttribute('aria-current', active ? 'true' : 'false');
                });

                banner.src = target.dataset.bannerSrc || banner.src;
                banner.alt = target.dataset.bannerAlt || 'Property image';
                current = index;
            }

            function startRotation() {
                if (rotateTimer) {
                    clearInterval(rotateTimer);
                }

                if (thumbs.length < 2) {
                    return;
                }

                rotateTimer = setInterval(() => {
                    const next = (current + 1) % thumbs.length;
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
                                return `
                                    <li class="room-amenity-item">
                                        <input type="checkbox" id="amenity-${roomId}-${categoryTitle.replace(/\s+/g, '-').toLowerCase()}-${idx}" checked disabled>
                                        <label for="amenity-${roomId}-${categoryTitle.replace(/\s+/g, '-').toLowerCase()}-${idx}">${safeItem}${badge}</label>
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
                const roomLink = roomCard.dataset.roomLink || '#';
                const roomImages = parseJsonArray(roomCard.dataset.roomImages);
                const roomAmenities = parseJsonArray(roomCard.dataset.roomAmenities);
                const bathroomAmenities = parseJsonArray(roomCard.dataset.bathroomAmenities);
                const maxOccupancy = roomCard.querySelector('.room-sleeps')?.textContent?.trim() || '1';

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
                    roomLink,
                    roomImages,
                    roomAmenities,
                    bathroomAmenities,
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
                clone.querySelector('[data-note]').textContent = roomData.roomExtraBedPolicy;

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
    </script>
</body>
</html>