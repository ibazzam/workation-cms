    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #ffffff;
            --line: #d7e0e6;
            --hero-1: #16334a;
            --hero-2: #1d4a69;
            --hero-3: #2f7a7f;
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
            background: linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
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
            margin-bottom: 6px;
        }

        .hero h1 {
            margin: 0 0 4px;
            font-size: clamp(1.2rem, 2vw, 1.65rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 720px;
            font-size: 0.82rem;
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .hero-links {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(211, 235, 244, 0.35);
        }

        .auth-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .hero-highlights {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
            font-weight: 700;
        }

        .hero-link:hover {
            border-color: #d9f5fa;
            background: rgba(12, 59, 90, 0.56);
        }

        .portal-shell {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 256px minmax(0, 1fr);
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
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #ffffff;
            max-height: calc(100vh - 16px);
            overflow-y: auto;
        }

        .vendor-trust-strip {
            margin-top: 10px;
        }

        .vendor-trust-strip-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .vendor-trust-strip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .vendor-trust-metric {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .vendor-trust-chips {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .vendor-status-chip {
            border: 1px solid #d0dbe5;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 0.74rem;
            font-weight: 700;
            color: #35566f;
            background: #fff;
        }

        .vendor-status-chip.is-approved {
            border-color: #93c8a6;
            color: #1f5d36;
            background: #edf9f0;
        }

        .vendor-status-chip.is-pending {
            border-color: #f0c48d;
            color: #7d4b0a;
            background: #fff4e4;
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

        .nav-group + .nav-group {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #edf3f7;
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

        .nav-locked-note {
            margin: 0;
            border: 1px dashed #d5c399;
            border-radius: 8px;
            padding: 9px;
            background: #fff7ea;
            color: #72500f;
            font-size: 0.76rem;
            line-height: 1.4;
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

        .portal-content > .ops-section:first-child {
            margin-top: 0;
        }

        .page-listing-form .portal-shell {
            grid-template-columns: 208px minmax(0, 1fr);
            gap: 8px;
        }

        .page-listing-form .ops-category-card {
            padding: 8px;
        }

        .page-listing-form .ops-header {
            margin-bottom: 8px;
        }

        .page-listing-form .ops-form {
            padding: 8px;
        }

        .page-listing-form .listing-form-section {
            padding: 12px;
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
            background: #ffffff;
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
            margin-left: 0;
            padding-left: 18px;
            font-size: 0.79rem;
            font-weight: 600;
            color: #35566f;
            background: #ffffff;
        }

        .portal-nav a:hover,
        .nav-item-link:hover,
        .nav-sub-link:hover {
            border-color: #cddce8;
            background: #f5f9fd;
            color: #124967;
        }

        .portal-nav a.is-active,
        .nav-item-link.is-active,
        .nav-sub-link.is-active {
            border-color: #0f6b74;
            background: #ebf6f8;
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

        .vendor-workspace-nav {
            margin-top: 12px;
            margin-bottom: 12px;
        }

        .workspace-tabs {
            display: flex;
            border-bottom: 2px solid #d8e2eb;
            margin-bottom: 10px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .workspace-tabs::-webkit-scrollbar {
            display: none;
        }

        .workspace-tab {
            padding: 10px 20px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #5a6b7c;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            text-decoration: none;
        }

        .workspace-tab:hover {
            color: #1a2f41;
        }

        .workspace-tab.is-active {
            color: #0d4f6a;
            border-bottom-color: #0f6179;
        }

        .workspace-category-tabs {
            display: flex;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #b8c8d8 transparent;
            background: #e4eaf0;
            border-radius: 10px;
            padding: 3px;
            flex-wrap: nowrap;
        }

        .workspace-category-tab {
            white-space: nowrap;
            line-height: 1.2;
            text-align: center;
            padding: 7px 10px;
            border: none;
            background: transparent;
            color: #4a6278;
            font-size: 0.76rem;
            font-weight: 700;
            border-radius: 7px;
            text-decoration: none;
            flex: 0 0 auto;
        }

        .workspace-category-tab:hover {
            background: #ffffff88;
        }

        .workspace-category-tab.is-active {
            background: #ffffff;
            color: #0d4a65;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
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

        .activity-timeline {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .activity-timeline-item {
            border: 1px solid #dce5ed;
            border-left-width: 4px;
            border-radius: 10px;
            background: #fff;
            padding: 8px 10px;
        }

        .activity-timeline-item.kind-reservation {
            border-left-color: #3a7ca5;
        }

        .activity-timeline-item.kind-pricing {
            border-left-color: #a55f3a;
        }

        .activity-timeline-item.kind-listing {
            border-left-color: #2f8c63;
        }

        .activity-timeline-time {
            margin: 0;
            font-size: 0.72rem;
            color: #6f8598;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .activity-timeline-title {
            margin: 4px 0 0;
            font-size: 0.88rem;
            font-weight: 700;
            color: #173754;
        }

        .activity-timeline-detail {
            margin: 3px 0 0;
            font-size: 0.8rem;
            color: #4f667b;
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

        .listing-form-stack {
            display: grid;
            gap: 14px;
        }

        .listing-form-section {
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .listing-form-section-head {
            display: grid;
            gap: 4px;
        }

        .listing-form-section-head h4 {
            margin: 0;
            font-size: 0.98rem;
            color: #19384f;
        }

        .listing-form-section-head p {
            margin: 0;
            color: #557186;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .listing-transfer-table {
            display: grid;
            gap: 8px;
        }

        .listing-transfer-head,
        .listing-transfer-row {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) repeat(4, minmax(120px, 1fr));
            gap: 8px;
            align-items: center;
        }

        .listing-transfer-head {
            padding: 0 2px;
            color: #587285;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .listing-transfer-row {
            border: 1px solid #dce6ee;
            border-radius: 10px;
            padding: 10px;
            background: #f9fcff;
        }

        .listing-transfer-option {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .listing-transfer-option label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 0.85rem;
            color: #223b51;
            text-transform: none;
            letter-spacing: normal;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .listing-transfer-option small {
            color: #597386;
            font-size: 0.76rem;
            line-height: 1.4;
        }

        .listing-transfer-rate {
            display: grid;
            gap: 4px;
        }

        .listing-transfer-rate span {
            color: #587285;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .listing-form-note {
            margin: 0;
            color: #587285;
            font-size: 0.78rem;
            line-height: 1.5;
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

        .map-picker [data-edit-map-wrap] {
            width: 100%;
            height: 260px;
            background: #eef4f9;
            display: block;
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

        .edit-map-picker {
            display: grid;
            gap: 6px;
            margin: 8px 0 10px;
        }

        .edit-map-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1a3a52;
        }

        .edit-map-wrap {
            width: 100%;
            height: 240px;
            border-radius: 8px;
            border: 1px solid #c8d8e4;
            overflow: hidden;
            background: #eef4f9;
        }

        .edit-map-coords {
            font-size: 0.74rem;
            color: #4b6075;
            font-style: italic;
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

        .availability-calendar {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fbfdff;
            padding: 10px;
        }

        .availability-calendar-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            color: #1f3346;
        }

        .availability-calendar-nav {
            border: 1px solid #c8d8e4;
            background: #fff;
            color: #2b445c;
            border-radius: 8px;
            min-width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 0.9rem;
            line-height: 1;
        }

        .availability-calendar-nav:hover {
            background: #f1f7fd;
            border-color: #9ab1c6;
        }

        .availability-calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .availability-calendar-pill {
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 0.7rem;
            border: 1px solid #d0dbe5;
            background: #fff;
            color: #2a4259;
        }

        .availability-calendar-pill.is-open {
            border-color: #95b5a7;
            background: #edf8f2;
            color: #215640;
        }

        .availability-calendar-pill.is-blocked {
            border-color: #f1c07f;
            background: #fff4e4;
            color: #7d4b0a;
        }

        .availability-calendar-pill.is-booked {
            border-color: #cfd8e3;
            background: #edf1f6;
            color: #4a5b6f;
        }

        .availability-calendar-pill.is-selected {
            border-color: #95a8d8;
            background: #eef2ff;
            color: #2f3f73;
        }

        .availability-calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
            margin-bottom: 6px;
            color: #5a7088;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.66rem;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            text-align: center;
        }

        .availability-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
        }

        .availability-calendar-day {
            border: 1px solid #d7e0e6;
            border-radius: 8px;
            min-height: 36px;
            background: #fff;
            color: #294258;
            cursor: pointer;
            font-size: 0.78rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .availability-calendar-day.is-open {
            background: #f7fff9;
            border-color: #b8dcc9;
        }

        .availability-calendar-day.is-blocked {
            background: #fff3e0;
            border-color: #f0c48d;
            color: #75420a;
        }

        .availability-calendar-day.is-booked {
            background: #e9edf3;
            border-color: #ced7e2;
            color: #5d6e81;
            cursor: not-allowed;
        }

        .availability-calendar-day.is-outside {
            opacity: 0.58;
        }

        .availability-calendar-day.is-today {
            box-shadow: 0 0 0 2px rgba(78, 99, 173, 0.24);
        }

        .availability-calendar-day.is-disabled-target {
            opacity: 0.45;
            cursor: not-allowed;
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

        .availability-ops-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
        }

        .availability-ops-grid .ops-form-availability {
            grid-column: 1 / -1;
        }

        @media (max-width: 1180px) {
            .availability-ops-grid {
                grid-template-columns: 1fr;
            }
        }

        .ops-form {
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
        }

        .ops-form-availability {
            padding: 10px 12px 12px;
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

        .ops-form-grid-compact {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px 10px;
            align-items: start;
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
            font-size: 0.7rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .ops-field-compact-note .small {
            margin: 2px 0 0;
            line-height: 1.25;
        }

        .ops-input,
        .ops-select,
        .ops-textarea {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.82rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1d3045;
            background: #fff;
            min-height: 38px;
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
            min-height: 74px;
            resize: vertical;
        }

        .availability-inline-note {
            margin: 8px 0 0;
            text-align: right;
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

        /* ── Vendor in-platform messaging ─────────────────────────────── */
        .vendor-msg-cell {
            min-width: 200px;
            max-width: 260px;
            vertical-align: top;
        }

        .vendor-msg-details {
            margin-bottom: 8px;
        }

        .vendor-msg-summary {
            font-size: 0.75rem;
            font-weight: 700;
            color: #174b6a;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .vendor-msg-unread-badge {
            background: #d94f2e;
            color: #fff;
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 0.67rem;
            font-weight: 700;
        }

        .vendor-msg-list {
            margin-top: 6px;
            display: grid;
            gap: 5px;
            max-height: 220px;
            overflow-y: auto;
        }

        .vendor-msg-bubble {
            padding: 6px 8px;
            border-radius: 7px;
            font-size: 0.73rem;
            display: grid;
            gap: 2px;
        }

        .vendor-msg-bubble--sent {
            background: #e4f0fb;
            border: 1px solid #c2d9ef;
            margin-left: 16px;
        }

        .vendor-msg-bubble--received {
            background: #fff;
            border: 1px solid #dce9f2;
            margin-right: 16px;
        }

        .vendor-msg-bubble--flagged {
            background: #fff5f5;
            border-color: #e8b4b4;
        }

        .vendor-msg-meta {
            font-size: 0.67rem;
            color: #7a9ab0;
        }

        .vendor-msg-body {
            color: #2b4558;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .vendor-msg-flag-notice {
            font-size: 0.67rem;
            color: #b03030;
            font-weight: 600;
        }

        .vendor-msg-none {
            font-size: 0.72rem;
            color: #9ab2c2;
            display: block;
            margin-bottom: 6px;
        }

        .vendor-msg-reply-form {
            display: grid;
            gap: 4px;
        }

        .vendor-msg-textarea {
            width: 100%;
            border: 1px solid #c5daea;
            border-radius: 6px;
            padding: 5px 7px;
            font-size: 0.73rem;
            resize: vertical;
            color: #2b4558;
            background: #fff;
            box-sizing: border-box;
        }

        .vendor-msg-textarea:focus {
            outline: none;
            border-color: #3a8ec9;
        }

        .vendor-msg-reply-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .vendor-msg-policy-note {
            font-size: 0.65rem;
            color: #9ab2c2;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .vendor-msg-send-btn {
            font-size: 0.72rem;
            padding: 4px 10px;
            white-space: nowrap;
        }

        .vendor-message-center {
            margin-top: 12px;
            border: 1px solid #d8e6f0;
            border-radius: 10px;
            background: #f8fbfe;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .vendor-message-center-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .vendor-message-center-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: #133b55;
        }

        .vendor-message-center-note {
            font-size: 0.72rem;
            color: #5f7d93;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vendor-message-center-list {
            display: grid;
            gap: 10px;
        }

        .vendor-message-thread {
            border: 1px solid #d6e3ed;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .vendor-message-thread-head {
            padding: 8px 10px;
            border-bottom: 1px solid #e3edf4;
            background: #eef5fb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .vendor-message-thread-subject {
            font-size: 0.74rem;
            font-weight: 700;
            color: #184662;
        }

        .vendor-message-thread-meta {
            font-size: 0.7rem;
            color: #648196;
        }

        .vendor-message-thread-body {
            padding: 10px;
            display: grid;
            gap: 8px;
        }

        .vendor-msg-open-center {
            font-size: 0.71rem;
            color: #1a6285;
            text-decoration: none;
            font-weight: 700;
        }

        .vendor-msg-open-center:hover {
            text-decoration: underline;
        }
        /* ── End vendor messaging ─────────────────────────────────────── */

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
            min-width: 0;
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

        .listing-management-table tr.is-editing {
            display: block;
            width: 100%;
        }

        .listing-management-table tr.is-editing .listing-cell-actions-cell {
            display: block;
            width: 100%;
        }

        .listing-management-table tr.is-editing .listing-cell-actions {
            grid-template-columns: 1fr;
            width: 100%;
        }

        .listing-management-table tr.is-media-open .listing-cell-main {
            display: none;
        }

        .listing-management-table tr.is-media-open .listing-cell-actions-cell {
            width: 100%;
        }

        .listing-edit-stretch-row td {
            padding-top: 8px;
            padding-bottom: 10px;
            background: #ffffff;
        }

        .listing-edit-stretch {
            width: 100%;
            min-width: 0;
        }

        .listing-edit-stretch .update-row-form.inline-table-form {
            width: 100%;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            min-width: 0;
        }

        .listing-actions-row > * {
            min-width: 0;
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
            min-width: 0;
        }

        .update-row-form .ops-textarea,
        .update-row-form .edit-map-picker,
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
        .inline-table-form .btn {
            margin-top: 0;
            width: 100%;
            margin-left: 0;
        }

        .media-panel-form {
            display: grid;
            gap: 6px;
        }

        .media-panel-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            width: 100%;
        }

        .media-panel-bar .btn {
            width: auto;
            margin: 0;
        }

        .media-panel-bar.gallery-toolbar {
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .media-panel-hint {
            margin: 0;
            font-size: 0.73rem;
            color: #5a7a8e;
        }

        .gallery-media-form {
            width: 100%;
            box-sizing: border-box;
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
        .update-row-form[data-property-edit-category="marine_transport"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="marine_transport"] [data-property-edit-scope="transport"],
        .update-row-form[data-property-edit-category="marine_transport"] [data-property-edit-scope="geo"],
        .update-row-form[data-property-edit-category="land_transport"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="land_transport"] [data-property-edit-scope="transport"],
        .update-row-form[data-property-edit-category="land_transport"] [data-property-edit-scope="geo"],
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
        .update-row-form[data-property-edit-category="marine_transport"] [data-property-edit-scope="policies"],
        .update-row-form[data-property-edit-category="land_transport"] [data-property-edit-scope="policies"],
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
            margin-top: 6px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
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
            box-shadow: 0 2px 8px rgba(17, 43, 68, 0.08);
        }

        .gallery-card img {
            width: 100%;
            aspect-ratio: 1 / 1;
            height: auto;
            object-fit: cover;
            display: block;
            background: #edf2f7;
        }

        .gallery-card-body {
            padding: 6px;
            display: grid;
            gap: 6px;
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

        .gallery-toolbar {
            align-items: center;
        }

        .gallery-toolbar .feature-item {
            font-size: 0.76rem;
            color: #31506a;
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

            .vendor-trust-strip-grid {
                grid-template-columns: 1fr;
            }

            .ops-grid,
            .ops-form-grid {
                grid-template-columns: 1fr;
            }

            .listing-transfer-head {
                display: none;
            }

            .listing-transfer-row {
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
                grid-template-columns: 1fr;
            }

            .update-row-form .ops-form-grid {
                grid-template-columns: 1fr;
            }

            .listing-management-table,
            .room-management-table {
                table-layout: fixed;
            }

            .listing-cell-main,
            .listing-cell-actions-cell,
            .listing-cell-actions {
                min-width: 0;
            }

            .listing-summary-line {
                overflow-wrap: anywhere;
            }

            .listing-actions-row {
                flex-wrap: wrap;
                row-gap: 6px;
            }

            .listing-actions-compact .btn {
                max-width: 100%;
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
                overflow: visible;
                white-space: normal;
                flex-direction: column;
                flex-wrap: nowrap;
            }

            .billing-ledger-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .media-upload-row {
                padding: 10px;
                min-width: 0;
            }

            .gallery-card-actions,
            .publish-readiness-actions {
                justify-content: stretch;
            }

            .gallery-card-actions form,
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
            .gallery-toolbar,
            .publish-readiness-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .listing-actions-row {
                flex-direction: column;
                align-items: stretch;
            }

            .listing-actions-row > form,
            .listing-actions-row > .btn,
            .listing-actions-row > .ops-chip {
                width: 100%;
                text-align: center;
            }

            .listing-management-table .inline-actions .btn,
            .listing-management-table .inline-actions form,
            .room-management-table .inline-actions .btn,
            .room-management-table .inline-actions form,
            .gallery-card-actions form,
            .gallery-card-actions .btn,
            .publish-readiness-actions form,
            .publish-readiness-actions .btn {
                width: 100%;
            }

            .media-dropzone {
                padding: 14px 10px;
                font-size: 0.76rem;
            }

            .media-upload-preview {
                grid-template-columns: 1fr;
            }

            .publish-readiness-box {
                padding: 10px;
            }
        }
    </style>
    @include('partials.uniform-buttons')