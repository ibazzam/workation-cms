<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f1f5ef;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #194356;
            --hero-2: #0e6b74;
            --hero-3: #34a272;
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
            background:
                radial-gradient(circle at 8% 10%, #d7f4e7 0, #d7f4e700 32%),
                radial-gradient(circle at 90% 10%, #d9f1ff 0, #d9f1ff00 35%),
                var(--bg);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 18px 34px;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 48%, var(--hero-3) 100%);
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

        .auth-bar {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
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
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
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
            border: 0;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f6d5f;
            color: #fff;
        }

        .btn-secondary {
            background: #edf2f8;
            color: #183452;
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

        .endpoint button {
            border: 0;
            border-radius: 8px;
            background: #0e6b5f;
            color: #fff;
            font-weight: 700;
            padding: 7px 10px;
            cursor: pointer;
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
            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <span class="eyebrow">Partner Access</span>
            <h1>Vendor Portal</h1>
            <p>Use a valid vendor bearer token to check vendor-facing APIs and account-level data.</p>
            <div class="hero-links">
                <a class="hero-link" href="/">Back to Home</a>
                <a class="hero-link" href="/admin">Go to Admin Portal</a>
                <a class="hero-link" href="{{ $apiBase }}/api/v1/ops/metrics" target="_blank" rel="noopener">Open Public Metrics</a>
            </div>
            <div class="auth-bar">
                <span class="auth-user">Signed in as {{ $portalUser }}</span>
                <form method="POST" action="/portal/vendor/logout">
                    @csrf
                    <button class="logout" type="submit">Log Out</button>
                </form>
            </div>
        </section>

        <section class="layout">
            <article class="card">
                <p class="label">Auth</p>
                <input id="tokenInput" class="token-input" type="password" placeholder="Paste vendor JWT bearer token">
                <div>
                    <button id="saveToken" class="btn btn-primary" type="button">Save Token</button>
                    <button id="clearToken" class="btn btn-secondary" type="button">Clear</button>
                </div>
                <div id="tokenState" class="state warn">TOKEN NOT SET</div>
            </article>

            <article class="card">
                <p class="label">Vendor API Actions</p>
                <div class="endpoint">
                    <code>GET /api/v1/auth/me</code>
                    <button type="button" data-path="/api/v1/auth/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/bookings</code>
                    <button type="button" data-path="/api/v1/bookings">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/loyalty/me</code>
                    <button type="button" data-path="/api/v1/loyalty/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/vendor/me/settlements/report</code>
                    <button type="button" data-path="/api/v1/payments/vendor/me/settlements/report">Run</button>
                </div>
                <pre id="output">Ready. Save token, then run an endpoint.</pre>
            </article>
        </section>
    </main>

    <script>
        (function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "https://api.workation.mv";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const output = document.getElementById("output");

            const SESSION_KEY = "workation_vendor_token";

            function setState(type, text) {
                tokenState.className = "state " + type;
                tokenState.textContent = text;
            }

            function getToken() {
                return sessionStorage.getItem(SESSION_KEY) || "";
            }

            function saveToken() {
                const value = (tokenInput.value || "").trim();
                if (!value) {
                    setState("warn", "TOKEN NOT SET");
                    return;
                }
                sessionStorage.setItem(SESSION_KEY, value);
                tokenInput.value = "";
                setState("ok", "TOKEN SAVED");
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
            }

            async function run(path) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    output.textContent = "Save a vendor token first.";
                    return;
                }

                output.textContent = "Loading " + path + " ...";
                try {
                    const res = await fetch(apiBase + path, {
                        method: "GET",
                        headers: {
                            "Authorization": "Bearer " + token,
                            "Accept": "application/json"
                        },
                        cache: "no-store"
                    });
                    const text = await res.text();
                    let parsed = text;
                    try {
                        parsed = JSON.stringify(JSON.parse(text), null, 2);
                    } catch (error) {
                        // Keep plain text if response is not JSON.
                    }
                    output.textContent = "Status: " + res.status + "\n\n" + parsed;
                    if (res.ok) {
                        setState("ok", "TOKEN VALID");
                    } else if (res.status === 401 || res.status === 403) {
                        setState("err", "TOKEN INVALID FOR VENDOR");
                    } else {
                        setState("warn", "REQUEST COMPLETED WITH WARNINGS");
                    }
                } catch (error) {
                    setState("err", "REQUEST FAILED");
                    output.textContent = "Network/CORS error. Ensure API allows origin https://www.workation.mv\n\n" + String(error);
                }
            }

            document.getElementById("saveToken").addEventListener("click", saveToken);
            document.getElementById("clearToken").addEventListener("click", clearToken);
            document.querySelectorAll("button[data-path]").forEach((button) => {
                button.addEventListener("click", function () {
                    run(button.getAttribute("data-path"));
                });
            });

            if (getToken()) {
                setState("ok", "TOKEN READY");
            }
        })();
    </script>
</body>
</html>
