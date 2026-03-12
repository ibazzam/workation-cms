<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation Maldives</title>
    <style>
        :root {
            --bg: #f5f3ef;
            --ink: #1f2328;
            --muted: #566071;
            --card: #ffffff;
            --brand: #0d8a7a;
            --brand-dark: #0a6257;
            --warn: #f78f1e;
            --ok: #16a34a;
            --down: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 15%, #d9efe8 0, #d9efe800 35%),
                radial-gradient(circle at 85% 5%, #ffe9c8 0, #ffe9c800 30%),
                var(--bg);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 28px 20px 36px;
        }

        .hero {
            background: linear-gradient(135deg, #123040 0%, #154d63 45%, #0d8a7a 100%);
            color: #fff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(21, 37, 56, 0.18);
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.6rem, 3vw, 2.5rem);
            letter-spacing: 0.02em;
        }

        .hero p {
            margin: 0;
            color: #dbf2f0;
            max-width: 760px;
            line-height: 1.45;
        }

        .cta {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
            display: inline-block;
        }

        .btn-primary {
            background: #fff;
            color: #073d36;
        }

        .btn-secondary {
            border: 1px solid #b5dde0;
            color: #e8f8f7;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
            border: 1px solid #dde4ea;
        }

        .kpi { grid-column: span 3; }
        .wide { grid-column: span 6; }

        .label {
            font-size: 0.78rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .value {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .status {
            font-size: 0.9rem;
            font-weight: 700;
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            margin-top: 6px;
        }

        .ok { color: #0b5c2a; background: #d8f7e2; }
        .warn { color: #7a4606; background: #ffeccd; }
        .down { color: #6d1111; background: #ffe0de; }

        .list { margin: 0; padding-left: 18px; line-height: 1.5; }
        .list li { margin-bottom: 6px; }

        @media (max-width: 900px) {
            .kpi, .wide { grid-column: span 12; }
        }
    </style>
</head>
<body>
    <div class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
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
