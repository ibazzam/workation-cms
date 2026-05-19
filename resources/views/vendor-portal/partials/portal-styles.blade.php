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

        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
        }

        .page {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 12px 16px;
        }

        .hero {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #ffffff;
            border: 1px solid #d7e0e6;
            border-radius: 0 0 10px 10px;
            color: #1c2e40;
            padding: 8px 12px;
            box-shadow: 0 6px 16px rgba(18, 38, 58, 0.08);
        }

        .eyebrow {
            display: inline-block;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.64rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5f778e;
            margin-bottom: 1px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(0.98rem, 1.4vw, 1.2rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 2px 0 0;
            color: #64798d;
            max-width: 840px;
            font-size: 0.76rem;
        }

        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .hero-actions {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            gap: 8px;
        }

        .hero-links {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding-top: 6px;
            border-top: 1px solid #e5edf3;
        }

        .auth-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .hero-highlights {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .hero-highlight {
            border: 1px solid #d9e4ec;
            border-radius: 8px;
            background: #f8fbff;
            padding: 6px 8px;
            min-width: 180px;
        }

        .hero-highlight-label {
            margin: 0;
            font-size: 0.62rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #5f778e;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .hero-highlight-value {
            margin: 2px 0 0;
            font-size: 0.92rem;
            font-weight: 800;
            color: #233d53;
        }

        .hero-highlight-meta {
            margin: 2px 0 0;
            font-size: 0.68rem;
            color: #607990;
        }

        .auth-user {
            font-size: 0.73rem;
            border: 1px solid #d2dee8;
            border-radius: 999px;
            padding: 5px 8px;
            background: #f5f9fd;
            color: #29465f;
        }

        .hero-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 8px;
            font-size: 0.72rem;
            font-weight: 800;
            border: 1px solid #d2dee8;
            background: #f6fbff;
            color: #2f4e67;
        }

        .hero-status-pill.is-ok {
            border-color: rgba(147, 214, 173, 0.5);
            background: rgba(17, 99, 62, 0.26);
        }

        .hero-status-pill.is-warn {
            border-color: rgba(240, 208, 128, 0.5);
            background: rgba(125, 82, 9, 0.24);
        }

        .hero-status-pill.is-err {
            border-color: rgba(240, 183, 179, 0.5);
            background: rgba(109, 17, 17, 0.22);
        }

        .logout {
            border: 1px solid #d2dee8;
            border-radius: 8px;
            padding: 5px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #20415d;
            background: #ffffff;
            cursor: pointer;
        }

        .hero-link {
            color: #29506f;
            text-decoration: none;
            border: 1px solid #d2dee8;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 0.74rem;
            background: #f8fbff;
            font-weight: 700;
        }

        .hero-link:hover {
            border-color: #b8ccdd;
            background: #eff6fc;
        }

        .hero-links,
        .hero-highlights {
            display: none;
        }

        .portal-shell {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 248px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            min-height: calc(100vh - 64px);
        }

        .portal-nav {
            position: sticky;
            top: 54px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px;
            border: 1px solid #d9e3ec;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfd 100%);
            box-shadow: 0 10px 24px rgba(16, 39, 63, 0.06);
            max-height: calc(100vh - 64px);
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
            padding: 7px 6px;
            color: #304b63;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: none;
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

        @media (min-width: 901px) {
            .nav-group-body,
            .nav-group-body:not(.is-open) {
                display: grid;
            }
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

        .page-listing-form .ops-field label {
            font-size: 0.92rem;
            font-weight: 700;
            color: #23445f;
        }

        .page-listing-form .ops-input,
        .page-listing-form .ops-select,
        .page-listing-form .ops-textarea {
            min-height: 44px;
            font-size: 0.95rem;
        }

        .listing-form-quality-strip {
            margin: 0 0 12px;
            border: 1px solid #d3deec;
            border-radius: 12px;
            background: linear-gradient(180deg, #f6faff 0%, #ffffff 100%);
            padding: 10px;
        }

        .listing-form-quality-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .listing-form-quality-grid article {
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
        }

        .listing-form-quality-grid .small {
            color: #35556f;
            font-size: 0.8rem;
        }

        .portal-nav a,
        .nav-item-link,
        .nav-sub-link {
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 7px 9px;
            font-size: 0.79rem;
            font-weight: 700;
            color: #2e4c66;
            background: transparent;
            transition: all 0.15s ease;
        }

        .nav-group-header i,
        .nav-item-link i,
        .nav-sub-link i {
            width: 14px;
            text-align: center;
            color: #65809a;
            margin-right: 6px;
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
            border-color: #8fb0c8;
            background: #ebf3fb;
            color: #173e5c;
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
            border-color: #c7d7e6;
            background: #f5f9fd;
            color: #173f5f;
        }

        .portal-nav a.is-active,
        .nav-item-link.is-active,
        .nav-sub-link.is-active {
            border-color: #90afc7;
            background: #ebf3fb;
            color: #173d5b;
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

        .simple-home-strip {
            border: 1px solid #d8e2eb;
            border-radius: 12px;
            background: #f9fcff;
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
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

        .summary-grid-compact {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .summary-grid-compact .summary-card {
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .overview-actions-card {
            margin-top: 12px;
        }

        .overview-actions-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
        }

        .overview-actions-head .small {
            margin: 0;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .overview-actions-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .overview-action {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #d7e0e6;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
            text-decoration: none;
            color: inherit;
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .overview-action:hover {
            transform: translateY(-1px);
            border-color: #b9c9d8;
            box-shadow: 0 8px 24px rgba(18, 42, 63, 0.08);
        }

        .overview-action-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #19324a;
        }

        .overview-action-copy {
            font-size: 0.82rem;
            line-height: 1.4;
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

        .workspace-command-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(260px, 0.85fr);
            gap: 10px;
            align-items: start;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #dde5ed;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(18, 42, 63, 0.04);
        }

        .workspace-command-eyebrow {
            margin: 0 0 6px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #547389;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .workspace-command-title {
            margin: 0;
            font-size: clamp(1rem, 1.6vw, 1.25rem);
            line-height: 1.15;
            color: #173550;
        }

        .workspace-command-copy {
            margin: 6px 0 0;
            font-size: 0.8rem;
            line-height: 1.5;
            color: #5f7181;
            max-width: 760px;
        }

        .workspace-command-meta {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .workspace-command-meta-item {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            background: #eef5f9;
            color: #35556d;
            font-size: 0.76rem;
            font-weight: 700;
            border: 1px solid #d3e2eb;
        }

        .workspace-command-chips {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .workspace-command-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid #cfe0ea;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #31566d;
            background: #fafdff;
        }

        .workspace-command-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .workspace-command-action {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 10px 12px;
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #ffffff;
            color: #17344d;
            font-size: 0.83rem;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .workspace-command-action:hover {
            transform: translateY(-1px);
            border-color: #b5c7d8;
            box-shadow: 0 8px 20px rgba(18, 42, 63, 0.08);
        }

        .vendor-workspace-nav {
            margin-top: 12px;
            margin-bottom: 12px;
        }

        .workspace-tabs {
            display: flex;
            border-bottom: 2px solid #d8e2eb;
            margin-bottom: 10px;
            flex-wrap: nowrap;
            gap: 4px;
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
            margin-bottom: 0;
            white-space: nowrap;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .workspace-tab:hover {
            color: #1a2f41;
        }

        .workspace-tab.is-active {
            color: #0d4f6a;
            border-bottom-color: #0f6179;
            border-color: #d3dfe9;
            background: #ffffff;
        }

        .workspace-category-tabs {
            display: flex;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
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

        /* Match customer card styles for vendor cards */
        .vendor-booking-card-list {
            display: grid;
            gap: 12px;
        }
        .vendor-booking-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }
        .vendor-booking-meta-bar {
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
        .vendor-booking-ref {
            color: #1a6abf;
            font-weight: 700;
        }
        .vendor-booking-body {
            display: grid;
            grid-template-columns: 104px 1fr;
        }
        .vendor-booking-thumb {
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
        .vendor-booking-thumb img {
            width: 104px;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .vendor-booking-info {
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .vendor-booking-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .vendor-booking-title {
            font-size: 1rem;
            font-weight: 700;
            color: #16212e;
        }
        .vendor-booking-line {
            font-size: 0.8rem;
            color: #5a6a7a;
            line-height: 1.45;
        }
        .vendor-booking-actions {
            border-top: 1px solid var(--line);
            padding: 10px 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            background: #fbfdff;
        }
        .vendor-booking-actions .btn {
            min-height: 34px;
            padding: 7px 11px;
            font-size: 0.78rem;
            line-height: 1.15;
            border-radius: 8px;
        }
        .vendor-booking-actions form {
            margin: 0;
        }

        .vendor-reservation-advanced {
            margin-top: 10px;
            border: 1px solid #d7e0e6;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .vendor-reservation-advanced > summary {
            cursor: pointer;
            padding: 10px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #23445f;
            background: #f7fbff;
            border-bottom: 1px solid #e4edf5;
            list-style: none;
        }

        .vendor-reservation-advanced > summary::-webkit-details-marker {
            display: none;
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
            overflow-x: auto;
            overflow-y: hidden;
        }

        .payout-table {
            width: 100%;
            min-width: 980px;
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
            margin: 0 0 12px;
            display: flex;
            gap: 2px;
            overflow-x: auto;
            background: #e4eaf0;
            border-radius: 10px;
            padding: 3px;
            scrollbar-width: none;
        }

        .panel-links::-webkit-scrollbar {
            display: none;
        }

        .panel-links a {
            text-decoration: none;
            white-space: nowrap;
            padding: 7px 13px;
            border: none;
            background: transparent;
            color: #4a6278;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 7px;
            flex-shrink: 0;
        }

        .panel-links a:hover {
            background: #ffffff88;
            color: #2f4f67;
        }

        .panel-links a.is-active {
            background: #ffffff;
            color: #0d4a65;
            font-weight: 700;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .reservation-command-bar {
            display: none;
        }

        .reservation-command {
            display: none;
        }

        .reservation-command:hover {
            display: none;
        }

        .ops-section--reservations .billing-ledger-grid {
            gap: 10px;
            margin-bottom: 12px;
        }

        .ops-section--reservations .billing-ledger-card {
            border-color: #d3e0eb;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(23, 51, 78, 0.05);
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            padding: 10px;
        }

        .ops-section--reservations .billing-ledger-card .metric-label {
            color: #55728b;
            font-size: 0.7rem;
        }

        .ops-section--reservations .billing-ledger-card .metric-value {
            font-size: 1.08rem;
            color: #14324b;
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

        .listing-price-band .listing-transfer-head,
        .listing-price-band .listing-transfer-row {
            grid-template-columns: minmax(200px, 2fr) repeat(2, minmax(120px, 1fr));
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

        .availability-engine-shell {
            margin: 10px 0 12px;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.9fr);
            gap: 10px;
            align-items: start;
        }

        .availability-engine-main,
        .availability-engine-channel-panel {
            border: 1px solid #d7e0e6;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px;
        }

        .availability-engine-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .availability-inventory-highlights {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .availability-inventory-card {
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #f9fcff;
            padding: 9px;
        }

        .availability-inventory-card .metric-label {
            margin: 0;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #56708a;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .availability-inventory-card .metric-value {
            margin: 6px 0 0;
            font-size: 1.08rem;
            font-weight: 800;
            color: #17344d;
        }

        .availability-inventory-card .small {
            margin: 4px 0 0;
            color: #5b6d7f;
        }

        .availability-inventory-table-wrap {
            max-height: 340px;
            overflow-y: auto;
        }

        .availability-channel-kpis {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .availability-channel-kpi {
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #f9fcff;
            padding: 8px;
        }

        .availability-channel-kpi .metric-label {
            margin: 0;
            font-size: 0.68rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #59748b;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .availability-channel-kpi .metric-value {
            margin: 5px 0 0;
            font-size: 1rem;
            font-weight: 800;
            color: #17344d;
        }

        .availability-channel-feed {
            margin-top: 8px;
            display: grid;
            gap: 8px;
            max-height: 428px;
            overflow-y: auto;
        }

        .availability-channel-event {
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #f8fbff;
            padding: 8px;
        }

        .availability-channel-event-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .availability-channel-event-actions {
            margin-top: 7px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .availability-channel-event-actions .btn {
            min-height: 30px;
            padding: 5px 8px;
            font-size: 0.74rem;
        }

        .migration-value-strip {
            margin: 10px 0 10px;
            border: 1px solid #cfe0eb;
            border-radius: 12px;
            background: linear-gradient(180deg, #f5fbff 0%, #ffffff 100%);
            padding: 10px;
        }

        .migration-value-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .migration-value-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .migration-value-card {
            border: 1px solid #d5e4ef;
            border-radius: 10px;
            background: #ffffff;
            padding: 9px;
        }

        .migration-value-card .metric-label {
            margin: 0;
            font-size: 0.69rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #55718a;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .migration-value-card .metric-value {
            margin: 6px 0 0;
            font-size: 1.08rem;
            font-weight: 800;
            color: #15344d;
        }

        .migration-value-card .small {
            margin: 4px 0 0;
            color: #5b6f82;
        }

        .availability-ops-grid .ops-form-availability {
            grid-column: 1 / -1;
        }

        @media (max-width: 1180px) {
            .availability-ops-grid {
                grid-template-columns: 1fr;
            }

            .availability-engine-shell {
                grid-template-columns: 1fr;
            }

            .availability-inventory-highlights {
                grid-template-columns: 1fr;
            }

            .migration-value-grid {
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

        .reservation-ops-table {
            width: 100%;
            min-width: 100%;
            table-layout: fixed;
        }

        .reservation-ops-table--enterprise {
            min-width: 1380px;
            table-layout: auto;
            border-collapse: separate;
            border-spacing: 0;
        }

        .reservation-ops-table--enterprise tbody td {
            background: #ffffff;
        }

        .reservation-ops-table--enterprise tbody tr:nth-child(4n + 1) td,
        .reservation-ops-table--enterprise tbody tr:nth-child(4n + 2) td {
            border-bottom-color: #e8eff6;
        }

        .reservation-cell-sticky-left {
            position: sticky;
            left: 0;
            z-index: 3;
            box-shadow: 8px 0 12px -10px rgba(24, 52, 77, 0.24);
            min-width: 210px;
        }

        .reservation-cell-sticky-right {
            position: sticky;
            right: 0;
            z-index: 3;
            box-shadow: -8px 0 12px -10px rgba(24, 52, 77, 0.24);
            min-width: 210px;
            background: #f9fcff;
        }

        .reservation-ops-table--enterprise thead .reservation-cell-sticky-left,
        .reservation-ops-table--enterprise thead .reservation-cell-sticky-right {
            z-index: 4;
            background: #f8fbff;
        }

        .reservation-row-toggle {
            width: 100%;
            margin-bottom: 8px;
        }

        .vendor-booking-actions .reservation-row-toggle {
            width: auto;
            margin-bottom: 0;
        }

        .reservation-ops-table .reservation-action--details {
            display: inline-flex;
            margin-bottom: 8px;
        }

        .reservation-ops-table .reservation-action--manage {
            margin-top: 0;
        }

        .reservation-ops-table .reservation-action--danger {
            margin-top: 8px;
            display: inline-block;
        }

        .reservation-detail-row td {
            background: #f8fbff;
            border-top: none;
        }

        .reservation-detail-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            padding: 6px 2px;
        }

        .reservation-detail-grid > div {
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #ffffff;
            padding: 8px 10px;
        }

        .reservation-print-actions {
            margin-top: 8px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .reservation-print-actions a {
            text-decoration: none;
            border: 1px solid #ccdae7;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #1e4563;
            background: #f6fbff;
        }

        .reservation-print-actions a:hover {
            border-color: #9fb8cd;
            background: #eef6fd;
        }

        .reservation-manage-box {
            margin-top: 10px;
            border: 1px solid #d8e3ee;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px;
        }

        .inline-status-form--detail {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) minmax(220px, 1fr) auto;
            gap: 8px;
            align-items: stretch;
        }

        .inline-status-form--detail .btn {
            white-space: nowrap;
            min-height: 34px;
            padding: 7px 11px;
            font-size: 0.78rem;
            line-height: 1.15;
            border-radius: 8px;
        }

        .reservation-ops-table th,
        .reservation-ops-table td {
            white-space: normal;
            word-break: break-word;
            line-height: 1.35;
        }

        .reservation-ops-table th:last-child,
        .reservation-ops-table td:last-child {
            min-width: 180px;
            width: 180px;
        }

        .reservation-ops-table .inline-status-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            align-items: stretch;
        }

        .reservation-ops-table .inline-status-form .ops-select,
        .reservation-ops-table .inline-status-form .ops-input,
        .reservation-ops-table .inline-status-form .btn,
        .reservation-ops-table td:last-child > form .btn {
            width: 100%;
            margin: 0;
        }

        .reservation-ops-table .inline-status-form {
            margin-top: 0;
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

        .billing-row-print-links {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .billing-row-print-links a {
            text-decoration: none;
            border: 1px solid #cbd9e6;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #214761;
            background: #f8fbff;
        }

        .billing-row-print-links a:hover {
            border-color: #9eb7cc;
            background: #edf5fc;
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

        .listing-setup-wizard {
            margin-top: 12px;
            border: 1px solid #d6e2ec;
            border-radius: 14px;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            padding: 12px;
            display: grid;
            gap: 12px;
        }

        .listing-setup-wizard-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .listing-setup-wizard-label {
            margin: 0;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #59748a;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .listing-setup-wizard h3 {
            margin: 2px 0 0;
            font-size: 1.06rem;
            color: #173550;
        }

        .listing-setup-wizard p {
            margin: 5px 0 0;
            font-size: 0.82rem;
            color: #566f82;
            line-height: 1.45;
            max-width: 760px;
        }

        .listing-setup-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .listing-setup-step {
            border: 1px solid #d8e3ec;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 8px;
            align-items: flex-start;
        }

        .listing-setup-step-index {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 1px solid #b7cce0;
            background: #eef4fb;
            color: #294b66;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .listing-setup-step-title {
            margin: 0;
            font-size: 0.81rem;
            font-weight: 700;
            color: #1f3f5b;
        }

        .listing-setup-step-copy {
            margin: 3px 0 0;
            font-size: 0.75rem;
            color: #577288;
            line-height: 1.35;
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

        .listing-card-head {
            display: grid;
            grid-template-columns: 88px 1fr;
            gap: 10px;
            align-items: stretch;
        }

        .listing-card-thumb {
            width: 88px;
            min-height: 84px;
            border: 1px solid #dce7f0;
            border-radius: 9px;
            background: linear-gradient(135deg, #d7e8f5 0%, #c6dded 100%);
            color: #7a9ab4;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 1.4rem;
        }

        .listing-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .listing-card-main {
            min-width: 0;
            display: grid;
            gap: 4px;
            align-content: start;
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

        .listing-management-table {
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .listing-management-table thead th {
            background: transparent;
            border-bottom: none;
            padding-bottom: 2px;
        }

        .listing-management-table tbody td {
            background: #ffffff;
            border-top: 1px solid #dce7f0;
            border-bottom: 1px solid #dce7f0;
            padding-top: 11px;
            padding-bottom: 11px;
        }

        .listing-management-table tbody td:first-child {
            border-left: 1px solid #dce7f0;
            border-radius: 12px 0 0 12px;
        }

        .listing-management-table tbody td:last-child {
            border-right: 1px solid #dce7f0;
            border-radius: 0 12px 12px 0;
        }

        .listing-summary-line {
            gap: 7px;
        }

        .listing-summary-line strong {
            font-size: 0.95rem;
            color: #193852;
        }

        .listing-actions-compact .btn {
            padding: 5px 10px;
            border-radius: 7px;
            font-size: 0.77rem;
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
            flex-wrap: wrap;
            gap: 5px;
            align-items: center;
            min-width: 0;
        }

        .listing-actions-row > * {
            min-width: 0;
        }

        .listing-actions-break {
            flex-basis: 100%;
            width: 100%;
            height: 0;
            margin: 0;
            padding: 0;
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

        /* Listing cards: stronger visual parity with customer booking cards */
        .category-listing-section .ops-table-wrap {
            border: none;
            background: transparent;
            box-shadow: none;
            padding: 0;
        }

        .category-listing-section .listing-management-table {
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .category-listing-section .listing-management-table tbody tr[data-property-row] {
            display: block;
            background: #ffffff;
            border: 1px solid #d8e2eb;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(17, 41, 62, 0.04);
        }

        .category-listing-section .listing-management-table thead {
            display: none;
        }

        .category-listing-section .listing-management-table tbody td {
            background: #ffffff;
            border: 0;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .category-listing-section .listing-management-table tbody tr[data-property-row] > td:first-child {
            display: block;
            width: 100%;
            border-radius: 0;
            padding-left: 12px;
            padding-right: 12px;
        }

        .category-listing-section .listing-management-table tbody tr[data-property-row] > td:last-child {
            display: block;
            width: 100%;
            border-top: 1px solid #d8e2eb;
            border-radius: 0;
            padding: 10px 12px;
        }

        .category-listing-section .listing-cell-actions {
            align-content: center;
        }

        .category-listing-section .listing-cell-actions-cell {
            background: #ffffff;
        }

        .category-listing-section .listing-actions-row {
            justify-content: flex-end;
            gap: 6px;
        }

        .category-listing-section .listing-actions-compact .btn,
        .category-listing-section .listing-actions-compact form .btn,
        .category-listing-section .listing-actions-row > .btn,
        .category-listing-section .listing-actions-row > form .btn {
            padding: 7px 11px;
            min-height: 34px;
            min-width: 118px;
            font-size: 0.77rem;
            line-height: 1.15;
            border-radius: 8px;
            white-space: nowrap;
            text-align: center;
            justify-content: center;
        }

        .category-listing-section .listing-actions-row > form .btn.btn-danger {
            min-width: 132px;
        }

        .category-listing-section .listing-actions-compact form {
            margin: 0;
        }

        .category-listing-section .listing-card-thumb {
            border-radius: 8px;
            min-height: 82px;
        }

        .category-listing-section .listing-card-main .listing-summary-line strong {
            font-size: 1rem;
            color: #16212e;
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
        .update-row-form[data-property-edit-category="sea_transport"] [data-property-edit-scope="capacity"],
        .update-row-form[data-property-edit-category="sea_transport"] [data-property-edit-scope="transport"],
        .update-row-form[data-property-edit-category="sea_transport"] [data-property-edit-scope="geo"],
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
        .update-row-form[data-property-edit-category="sea_transport"] [data-property-edit-scope="policies"],
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

        .portal-nav-mobile-toggle {
            display: none;
        }

        @media (max-width: 900px) {
            .hero-top {
                flex-wrap: wrap;
            }

            .simple-home-strip {
                align-items: flex-start;
            }

            .reservation-ops-table th:last-child,
            .reservation-ops-table td:last-child {
                min-width: 170px;
                width: 170px;
            }

            .workspace-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .workspace-category-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .hero-actions {
                align-items: flex-start;
                width: 100%;
            }

            .listing-setup-steps {
                grid-template-columns: 1fr;
            }

            .reservation-command-bar {
                grid-template-columns: 1fr 1fr;
            }

            .reservation-detail-grid {
                grid-template-columns: 1fr;
            }

            .inline-status-form--detail {
                grid-template-columns: 1fr;
            }

            .inline-status-form--detail .btn {
                justify-self: stretch;
            }

            .reservation-cell-sticky-left,
            .reservation-cell-sticky-right {
                position: static;
                box-shadow: none;
                min-width: 0;
            }

            .auth-bar {
                align-items: flex-start;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .summary-grid-compact,
            .overview-actions-grid {
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
            .ops-form-grid,
            .ops-form-grid-compact {
                grid-template-columns: 1fr;
            }

            .overview-actions-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .workspace-command-bar {
                grid-template-columns: 1fr;
            }

            .workspace-command-actions {
                grid-template-columns: 1fr 1fr;
            }

            .vendor-booking-actions .inline-status-form {
                grid-template-columns: 1fr;
            }

            .vendor-booking-actions .inline-status-form .btn {
                justify-self: stretch;
            }

            .listing-card-head {
                grid-template-columns: 74px 1fr;
            }

            .listing-card-thumb {
                width: 74px;
                min-height: 74px;
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

            .category-listing-section .listing-management-table tbody td:first-child,
            .category-listing-section .listing-management-table tbody td:last-child {
                width: auto;
            }

            .category-listing-section .listing-management-table tbody tr[data-property-row] > td:first-child,
            .category-listing-section .listing-management-table tbody tr[data-property-row] > td:last-child {
                width: 100%;
            }

            .category-listing-section .listing-actions-row {
                justify-content: stretch;
            }

            .vendor-booking-actions .reservation-action--details,
            .vendor-booking-actions .reservation-action--danger,
            .vendor-booking-actions .reservation-action--danger .btn,
            .category-listing-section .listing-actions-row > .btn,
            .category-listing-section .listing-actions-row > form .btn {
                min-width: 0;
            }

            .listing-actions-compact .btn {
                max-width: 100%;
            }

            .guided-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .listing-form-quality-grid {
                grid-template-columns: 1fr;
            }

            .page-listing-form .ops-input,
            .page-listing-form .ops-select,
            .page-listing-form .ops-textarea {
                font-size: 1rem;
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
                gap: 0;
            }

            .portal-nav-mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 10px 14px;
                background: #ffffff;
                border: 1px solid var(--line);
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.92rem;
                font-weight: 600;
                color: var(--ink);
                margin-bottom: 6px;
            }

            .portal-nav-mobile-toggle:hover {
                background: #f5fafc;
            }

            .portal-nav-toggle-icon {
                transition: transform 0.2s ease;
            }

            .portal-nav-mobile-toggle[aria-expanded="true"] .portal-nav-toggle-icon {
                transform: rotate(180deg);
            }

            .portal-nav {
                position: static;
                display: none;
                overflow-y: auto;
                white-space: normal;
                flex-direction: column;
                flex-wrap: nowrap;
                max-height: calc(100vh - 120px);
                margin-bottom: 8px;
                border-radius: 8px;
            }

            .portal-nav.is-mobile-open {
                display: flex;
            }

            .listing-edit-stretch .update-row-form.inline-table-form {
                grid-template-columns: 1fr;
            }

            .ops-table-wrap td {
                max-width: 100vw;
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

            /* Listing-form page: override the higher-specificity portal-shell grid
               so the sidebar collapses to a single column on mobile */
            .page-listing-form .portal-shell {
                grid-template-columns: 1fr;
                gap: 0;
            }

            /* On the listing-form page the sidebar toggle must be visible */
            .page-listing-form .portal-nav-mobile-toggle {
                display: flex;
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

            .workspace-command-actions {
                grid-template-columns: 1fr;
            }

            .vendor-booking-body {
                grid-template-columns: 1fr;
            }

            .vendor-booking-thumb {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid var(--line);
                min-height: 62px;
            }

            .reservation-command-bar {
                grid-template-columns: 1fr;
            }

            .workspace-command-meta {
                flex-direction: column;
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

            /* Prevent iOS auto-zoom on input focus */
            .ops-input,
            .ops-textarea,
            .ops-select,
            .update-row-form input,
            .update-row-form select,
            .update-row-form textarea {
                font-size: 16px;
            }

            /* Prevent deeply nested inline forms from overflowing viewport */
            .listing-edit-stretch-row td,
            .listing-edit-stretch-row td[colspan] {
                display: block;
                width: 100%;
                max-width: 100vw;
                box-sizing: border-box;
                overflow-x: auto;
                padding: 6px 4px;
            }

            .listing-edit-stretch {
                min-width: 0;
            }
        }
    </style>
    @include('partials.uniform-buttons')