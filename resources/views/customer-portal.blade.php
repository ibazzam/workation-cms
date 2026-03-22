<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f2f5fb;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #194a73;
            --hero-2: #2f6e9f;
            --hero-3: #66a2d4;
            --ok: #0b5c2a;
            --ok-bg: #d8f7e2;
            --warn: #7a4606;
            --warn-bg: #ffeccd;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at 12% 10%, #d8ecff 0, #d8ecff00 34%), var(--bg);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 18px 34px;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 52%, var(--hero-3) 100%);
            border-radius: 18px;
            color: #fff;
            padding: 24px;
            box-shadow: 0 22px 44px rgba(18, 38, 58, 0.2);
        }

        .eyebrow {
            display: inline-block;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #d7f2f5;
            margin-bottom: 10px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.45rem, 2.8vw, 2.3rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 780px;
        }

        .hero-links {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hero-link {
            color: #ecfbff;
            text-decoration: none;
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 0.82rem;
            background: rgba(11, 49, 75, 0.32);
        }

        .portal-nav {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .portal-nav a {
            text-decoration: none;
            border: 1px solid #c8d4df;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1f4a53;
            background: #f4faf8;
        }

        .summary-grid {
            margin-top: 14px;
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
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f3346;
        }

        .summary-meta {
            margin: 6px 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

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

        .empty {
            border: 1px dashed #c8d3df;
            border-radius: 10px;
            padding: 12px;
            color: var(--muted);
            font-size: 0.85rem;
            background: #f9fcff;
        }

        .list {
            margin: 0;
            padding-left: 18px;
            line-height: 1.45;
            color: #29435b;
            font-size: 0.84rem;
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

        @media (max-width: 900px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Customer Experience</span>
            <h1>Customer Portal</h1>
            <p>Manage bookings, payment receipts, profile updates, and notifications from one customer dashboard.</p>
            <div class="hero-links">
                <a class="hero-link" href="/">Back to Home</a>
                <a class="hero-link" href="/vendor">Vendor Portal</a>
                <a class="hero-link" href="/admin">Admin Portal</a>
            </div>
        </section>

        <nav class="portal-nav" aria-label="Customer navigation">
            <a href="#customerSummary">Summary</a>
            <a href="#bookingsCard">Bookings</a>
            <a href="#paymentsCard">Payments</a>
            <a href="#notificationsCard">Notifications</a>
        </nav>

        <section id="customerSummary" class="summary-grid" aria-label="Customer dashboard summary">
            <article class="summary-card">
                <p class="summary-label">Upcoming Trips</p>
                <p class="summary-value">{{ $summary['upcoming_bookings'] }}</p>
                <p class="summary-meta">Confirmed future bookings</p>
            </article>
            <article class="summary-card">
                <p class="summary-label">Completed Trips</p>
                <p class="summary-value">{{ $summary['completed_bookings'] }}</p>
                <p class="summary-meta">Booking history completed</p>
            </article>
            <article class="summary-card">
                <p class="summary-label">Payment Receipts</p>
                <p class="summary-value">{{ $summary['receipts_available'] }}</p>
                <p class="summary-meta">Downloadable receipts available</p>
            </article>
            <article class="summary-card">
                <p class="summary-label">Notification Status</p>
                <p class="summary-value"><span class="status-pill {{ $summary['notification_state'] === 'ACTIVE' ? 'ok' : 'warn' }}">{{ $summary['notification_state'] }}</span></p>
                <p class="summary-meta">Messages and booking updates</p>
            </article>
        </section>

        <section class="layout">
            <article id="bookingsCard" class="card">
                <p class="label">Bookings</p>
                <div class="empty">Bookings layout is ready. Connect this panel to customer booking API payloads for live itinerary cards and detail links.</div>
            </article>
            <article id="paymentsCard" class="card">
                <p class="label">Payments and Receipts</p>
                <ul class="list">
                    <li>Recent payment timeline with status badges</li>
                    <li>Receipt download links for settled invoices</li>
                    <li>Support escalation entry for failed charges</li>
                </ul>
            </article>
            <article id="notificationsCard" class="card">
                <p class="label">Notifications Center</p>
                <div class="empty">No new notifications. This section is ready for booking updates, reminders, and support responses.</div>
            </article>
            <article class="card">
                <p class="label">Account and Support</p>
                <ul class="list">
                    <li><a href="/terms-of-service">Terms of Service</a></li>
                    <li><a href="/privacy-policy">Privacy Policy</a></li>
                    <li><a href="mailto:support@workation.mv">Contact Support</a></li>
                </ul>
            </article>
        </section>
    </main>
</body>
</html>
