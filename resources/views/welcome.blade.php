<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation Maldives</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg-a: #e8f7f4;
            --bg-b: #fff8e9;
            --ink: #16212f;
            --muted: #526274;
            --line: #d6e0ea;
            --card: #ffffff;
            --brand-a: #0f4f7e;
            --brand-b: #0f7f7d;
            --brand-c: #f6a53e;
            --ok: #0f6a33;
            --ok-bg: #ddf8e5;
            --warn: #864d04;
            --warn-bg: #fff0d0;
            --down: #841f1f;
            --down-bg: #ffdede;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background:
                radial-gradient(circle at 9% 9%, #c7ece5 0, #c7ece500 30%),
                radial-gradient(circle at 88% 8%, #ffe4b6 0, #ffe4b600 28%),
                linear-gradient(120deg, var(--bg-a) 0%, var(--bg-b) 100%);
        }

        .page {
            width: min(1200px, 100% - 28px);
            margin: 18px auto 30px;
        }

        .hero {
            border-radius: 24px;
            padding: 28px;
            color: #fff;
            background: linear-gradient(136deg, var(--brand-a) 0%, var(--brand-b) 52%, #2a9f9a 100%);
            box-shadow: 0 24px 56px rgba(14, 55, 78, 0.25);
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .hero::before {
            width: 220px;
            height: 220px;
            right: -80px;
            top: -80px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 68%);
        }

        .hero::after {
            width: 300px;
            height: 300px;
            left: -150px;
            bottom: -170px;
            background: radial-gradient(circle, rgba(255, 204, 114, 0.3) 0%, rgba(255, 204, 114, 0) 72%);
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 0.78rem;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            color: #d5f4f6;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(1.7rem, 3.4vw, 2.9rem);
            line-height: 1.08;
            max-width: 880px;
        }

        .hero p {
            margin: 12px 0 0;
            color: #daf6f7;
            max-width: 760px;
            font-size: 1.02rem;
            line-height: 1.5;
        }

        .hero-cta {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            text-decoration: none;
            border-radius: 12px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: transform 180ms ease, box-shadow 180ms ease;
            display: inline-block;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary {
            background: #ffffff;
            color: #0f4b4a;
            box-shadow: 0 10px 22px rgba(11, 45, 63, 0.24);
        }

        .btn-soft {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(216, 245, 248, 0.45);
            color: #f3fdff;
        }

        .hero-pills {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pill {
            border: 1px solid rgba(216, 245, 248, 0.42);
            background: rgba(8, 58, 72, 0.28);
            color: #dff7f8;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .strip {
            margin-top: 12px;
            border-radius: 14px;
            border: 1px solid #f2d4ab;
            background: linear-gradient(90deg, #fff5df 0%, #fff2dd 50%, #ffedd4 100%);
            color: #5f3f18;
            padding: 10px 12px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .quick-links {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .quick-link {
            border: 1px solid var(--line);
            border-radius: 14px;
            text-decoration: none;
            color: #193348;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 5px;
            box-shadow: 0 5px 12px rgba(23, 45, 67, 0.06);
        }

        .quick-link small {
            color: #5d6f83;
            font-size: 0.8rem;
        }

        .grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
        }

        .kpi { grid-column: span 3; }
        .half { grid-column: span 6; }

        .label {
            margin: 0;
            font-size: 0.73rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .value {
            margin-top: 8px;
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.05;
        }

        .status {
            margin-top: 8px;
            display: inline-block;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .ok { color: var(--ok); background: var(--ok-bg); }
        .warn { color: var(--warn); background: var(--warn-bg); }
        .down { color: var(--down); background: var(--down-bg); }

        .steps {
            margin: 12px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .steps li {
            border: 1px solid #e2ebf3;
            border-radius: 12px;
            padding: 10px;
            display: grid;
            grid-template-columns: 26px 1fr;
            gap: 8px;
            align-items: start;
            color: #33495f;
            font-size: 0.9rem;
        }

        .steps b {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: #edf6ff;
            border: 1px solid #d4e5f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            color: #22517a;
            font-size: 0.84rem;
        }

        .list {
            margin: 10px 0 0;
            padding-left: 18px;
            font-size: 0.9rem;
            color: #365168;
            line-height: 1.55;
        }

        .list a {
            color: #115f9a;
            text-underline-offset: 2px;
        }

        .footer-nav {
            margin-top: 16px;
            border-top: 1px solid #cad6e3;
            padding-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-link {
            text-decoration: none;
            border: 1px solid #c8d6e5;
            border-radius: 10px;
            padding: 8px 10px;
            background: #f6fbff;
            font-size: 0.8rem;
            color: #19466a;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        @media (max-width: 980px) {
            .quick-links { grid-template-columns: 1fr 1fr; }
            .kpi, .half { grid-column: span 12; }
        }

        @media (max-width: 700px) {
            .page { width: calc(100% - 20px); }
            .hero { padding: 20px; }
            .hero p { font-size: 0.94rem; }
            .quick-links { grid-template-columns: 1fr; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <span class="eyebrow">Your Island Travel Playground</span>
            <h1>Discover work-friendly stays, island trips, and transport in one joyful flow.</h1>
            <p>
                Workation Maldives brings accommodation, transfers, excursions, and remote workspace options together so families,
                solo travelers, and teams can plan in minutes.
            </p>
            <div class="hero-cta">
                <a class="btn btn-primary" href="/customer">Start Exploring</a>
                <a class="btn btn-soft" href="/customer#discoverListings">Find Stays and Rooms</a>
                <a class="btn btn-soft" href="/portal/vendor/register?mode=email">Become a Vendor</a>
            </div>
            <div class="hero-pills">
                <span class="pill">Live Listings</span>
                <span class="pill">Family Friendly</span>
                <span class="pill">Fast Booking Journey</span>
            </div>
        </section>

        <p class="strip">Simple by design: pick destination, compare options, choose room, and confirm with clear pricing.</p>

        <section class="quick-links" aria-label="Primary routes">
            <a class="quick-link" href="/customer">
                <strong>Customer Portal</strong>
                <small>Browse and compare listings instantly</small>
            </a>
            <a class="quick-link" href="/vendor">
                <strong>Vendor Portal</strong>
                <small>Manage listings, rooms, and media</small>
            </a>
            <a class="quick-link" href="/admin">
                <strong>Admin Portal</strong>
                <small>Oversee operations and governance</small>
            </a>
            <a class="quick-link" href="/users">
                <strong>Users Console</strong>
                <small>Portal account controls and access</small>
            </a>
        </section>

        <section class="grid" aria-label="Live platform confidence">
            <article class="card kpi">
                <p class="label">Server Time</p>
                <p class="value" id="pageTime">--</p>
                <span class="status ok">ACTIVE</span>
            </article>
            <article class="card kpi">
                <p class="label">API Health</p>
                <p class="value" id="healthCode">checking</p>
                <span class="status warn" id="healthState">PENDING</span>
            </article>
            <article class="card kpi">
                <p class="label">Preflight</p>
                <p class="value">PASS</p>
                <span class="status ok">STRICT GATE</span>
            </article>
            <article class="card kpi">
                <p class="label">Alert Routing</p>
                <p class="value">PASS</p>
                <span class="status ok">PAGER | SLACK | EMAIL</span>
            </article>

            <article class="card half">
                <p class="label">How It Works</p>
                <ul class="steps">
                    <li><b>1</b><span>Open Customer Portal and choose what you need: stay, transport, or experience.</span></li>
                    <li><b>2</b><span>Filter by category, price, and location so only relevant options remain.</span></li>
                    <li><b>3</b><span>Pick your best match and proceed with clear room-level pricing.</span></li>
                </ul>
            </article>

            <article class="card half">
                <p class="label">Operations Evidence</p>
                <ul class="list">
                    @foreach ($activityLinks as $item)
                        <li><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
                <ul class="list">
                    @foreach ($artifactLinks as $item)
                        <li><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </article>
        </section>

        <footer class="footer-nav" aria-label="Support links">
            <a class="footer-link" href="/terms-of-service">Terms</a>
            <a class="footer-link" href="/privacy-policy">Privacy</a>
            <a class="footer-link" href="mailto:support@workation.mv">Support Email</a>
            <a class="footer-link" href="{{ $apiBase }}/api/v1/health" target="_blank" rel="noopener">API Health</a>
            <a class="footer-link" href="{{ $apiBase }}/api/v1/ops/runbooks" target="_blank" rel="noopener">Runbooks</a>
        </footer>
    </main>

    <script>
        (function () {
            const root = document.querySelector('.page');
            const apiBase = root ? root.getAttribute('data-api-base') : 'https://api.workation.mv';
            const pageTime = document.getElementById('pageTime');
            const healthCode = document.getElementById('healthCode');
            const healthState = document.getElementById('healthState');

            function stamp() {
                if (!pageTime) {
                    return;
                }

                const now = new Date();
                pageTime.textContent = now.toISOString().replace('T', ' ').replace('.000Z', ' UTC');
            }

            async function probeHealth() {
                if (!healthCode || !healthState) {
                    return;
                }

                try {
                    const response = await fetch(apiBase + '/api/v1/health', { cache: 'no-store' });
                    healthCode.textContent = String(response.status);
                    if (response.ok) {
                        healthState.textContent = 'ONLINE';
                        healthState.className = 'status ok';
                    } else {
                        healthState.textContent = 'DEGRADED';
                        healthState.className = 'status warn';
                    }
                } catch (e) {
                    healthCode.textContent = 'n/a';
                    healthState.textContent = 'UNREACHABLE';
                    healthState.className = 'status down';
                }
            }

            stamp();
            probeHealth();
            setInterval(stamp, 1000);
            setInterval(probeHealth, 30000);
        })();
    </script>
</body>
</html>
