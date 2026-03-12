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
            --sand: #efe4d2;
            --seafoam: #d9e9e4;
            --ink: #19232f;
            --muted: #5e6978;
            --card: #fffefb;
            --line: #d8dee6;
            --hero-1: #123550;
            --hero-2: #0f6d80;
            --hero-3: #1f9a86;
            --ok-text: #0b5c2a;
            --ok-bg: #d8f7e2;
            --warn-text: #7a4606;
            --warn-bg: #ffeccd;
            --down-text: #6d1111;
            --down-bg: #ffe0de;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 20%, #c8e8df 0, #c8e8df00 35%),
                radial-gradient(circle at 88% 12%, #ffe7bf 0, #ffe7bf00 32%),
                linear-gradient(110deg, var(--seafoam) 0%, var(--sand) 100%);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 28px 20px 42px;
        }

        .hero {
            background: linear-gradient(135deg, var(--hero-1) 0%, var(--hero-2) 46%, var(--hero-3) 100%);
            color: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 24px 46px rgba(18, 38, 58, 0.2);
            position: relative;
            overflow: hidden;
            animation: rise-in 500ms ease-out both;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -80px;
            top: -80px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.24) 0%, rgba(255, 255, 255, 0) 65%);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 10px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #cdebef;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(1.7rem, 3.1vw, 2.65rem);
            letter-spacing: 0.01em;
            line-height: 1.1;
        }

        .hero p {
            margin: 0;
            color: #dbf2f0;
            max-width: 780px;
            line-height: 1.5;
            font-size: 1.02rem;
        }

        .cta {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border-radius: 11px;
            padding: 10px 15px;
            font-weight: 700;
            display: inline-block;
            transition: transform 180ms ease, box-shadow 180ms ease;
        }

        .btn-primary {
            background: #fff;
            color: #073d36;
            box-shadow: 0 6px 16px rgba(11, 36, 54, 0.2);
        }

        .btn-secondary {
            border: 1px solid #b5dde0;
            color: #e8f8f7;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .hero-meta {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .meta-pill {
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 6px 9px;
            border-radius: 999px;
            border: 1px solid rgba(206, 238, 235, 0.5);
            color: #e3f5f2;
            background: rgba(12, 52, 73, 0.28);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 16px;
            border: 1px solid var(--line);
            box-shadow: 0 2px 0 rgba(39, 58, 79, 0.04);
            animation: rise-in 460ms ease-out both;
        }

        .kpi { grid-column: span 3; }
        .wide { grid-column: span 6; }

        .label {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.13em;
            margin-bottom: 6px;
        }

        .value {
            font-size: 1.9rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .status {
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-block;
            letter-spacing: 0.03em;
            padding: 5px 9px;
            border-radius: 999px;
            margin-top: 8px;
        }

        .ok { color: var(--ok-text); background: var(--ok-bg); }
        .warn { color: var(--warn-text); background: var(--warn-bg); }
        .down { color: var(--down-text); background: var(--down-bg); }

        .list {
            margin: 0;
            padding-left: 18px;
            line-height: 1.6;
        }

        .list li {
            margin-bottom: 6px;
        }

        .list a {
            color: #0f4f8f;
            text-underline-offset: 2px;
        }

        .list a:hover {
            color: #093965;
        }

        .kpi:nth-child(1) { animation-delay: 70ms; }
        .kpi:nth-child(2) { animation-delay: 120ms; }
        .kpi:nth-child(3) { animation-delay: 170ms; }
        .kpi:nth-child(4) { animation-delay: 220ms; }

        @keyframes rise-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .kpi, .wide { grid-column: span 12; }
            .hero { padding: 24px; }
            .value { font-size: 1.75rem; }
        }

        .footer-nav {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .footer-link {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0b446a;
            text-decoration: none;
            padding: 8px 10px;
            border: 1px solid #bfd2df;
            border-radius: 10px;
            background: #f8fbff;
        }

        .footer-link:hover {
            background: #edf5fd;
        }
    </style>
</head>
<body>
    <div class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <span class="eyebrow">Production Surface</span>
            <h1>Workation Maldives Launch Console</h1>
            <p>
                The platform is live with strict preflight checks passing, alert routing configured,
                and launch governance complete. This page replaces the default framework splash and
                surfaces current runtime activity.
            </p>
            <div class="cta">
                <a class="btn btn-primary" href="{{ $apiBase }}/api/v1/health" target="_blank" rel="noopener">Open API Health</a>
                <a class="btn btn-secondary" href="https://github.com/ibazzam/workation-cms/actions/runs/22991556615" target="_blank" rel="noopener">Strict Preflight Evidence</a>
            </div>
            <div class="hero-meta">
                <span class="meta-pill">Strict Gate: Passed</span>
                <span class="meta-pill">Routing: Pager, Slack, Email</span>
                <span class="meta-pill">Domain: workation.mv</span>
            </div>
        </section>

        <section class="grid">
            <article class="card kpi">
                <div class="label">Homepage Runtime</div>
                <div class="value" id="pageTime">--</div>
                <div class="status ok" id="pageStatus">ACTIVE</div>
            </article>

            <article class="card kpi">
                <div class="label">API Health</div>
                <div class="value" id="healthCode">checking</div>
                <div class="status warn" id="healthState">PENDING</div>
            </article>

            <article class="card kpi">
                <div class="label">Preflight</div>
                <div class="value">PASS</div>
                <div class="status ok">RUN 22991556615</div>
            </article>

            <article class="card kpi">
                <div class="label">Alert Routing</div>
                <div class="value">PASS</div>
                <div class="status ok">PAGER/SLACK/EMAIL</div>
            </article>

            <article class="card wide">
                <div class="label">Recent Activity</div>
                <ul class="list">
                    @foreach ($activityLinks as $item)
                        <li><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </article>

            <article class="card wide">
                <div class="label">Launch Artifacts</div>
                <ul class="list">
                    @foreach ($artifactLinks as $item)
                        <li><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </article>
        </section>

        <footer class="footer-nav" aria-label="Portal links">
            <a class="footer-link" href="/admin">Admin Portal</a>
            <a class="footer-link" href="/vendor">Vendor Portal</a>
            <a class="footer-link" href="{{ $apiBase }}/api/v1/ops/metrics" target="_blank" rel="noopener">Public Metrics</a>
        </footer>
    </div>

    <script>
        (function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "https://api.workation.mv";
            const pageTime = document.getElementById("pageTime");
            const healthCode = document.getElementById("healthCode");
            const healthState = document.getElementById("healthState");

            function stamp() {
                const now = new Date();
                pageTime.textContent = now.toISOString().replace("T", " ").replace(".000Z", " UTC");
            }

            async function probeHealth() {
                try {
                    const response = await fetch(apiBase + "/api/v1/health", { cache: "no-store" });
                    healthCode.textContent = String(response.status);
                    if (response.ok) {
                        healthState.textContent = "ONLINE";
                        healthState.className = "status ok";
                    } else {
                        healthState.textContent = "DEGRADED";
                        healthState.className = "status warn";
                    }
                } catch (error) {
                    healthCode.textContent = "n/a";
                    healthState.textContent = "UNREACHABLE";
                    healthState.className = "status down";
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
