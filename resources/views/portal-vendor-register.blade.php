<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partner Registration | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --ink: #222222;
            --muted: #5a5a5a;
            --line: #d9d9d9;
            --brand: #0f6288;
            --brand-dark: #0c4f6d;
            --bg: #f7f7f7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            min-height: 100vh;
            background: var(--bg);
        }

        .wrap {
            max-width: 640px;
            margin: 0 auto;
            padding: 44px 16px;
        }

        .shell {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
        }

        .shell-head {
            text-align: center;
            font-weight: 700;
            font-size: 1.45rem;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .card {
            padding: 22px;
        }

        .eyebrow {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
        }

        h1 {
            margin: 8px 0 8px;
            font-size: 2rem;
            line-height: 1.15;
        }

        p {
            margin: 0 0 14px;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .field {
            display: block;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field-note {
            margin-top: 5px;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.35;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #c8d2de;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            background: #fff;
        }

        .error {
            color: #8a1010;
            background: #ffe8e8;
            border: 1px solid #ffcaca;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.86rem;
        }

        .ok {
            color: #0d5a2a;
            background: #e8f8ee;
            border: 1px solid #a6d8b6;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.86rem;
        }

        .hint {
            color: #303030;
            background: #fafafa;
            border: 1px solid #ececec;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 14px;
            font-size: 0.84rem;
            line-height: 1.35;
        }

        .social-health {
            color: #213547;
            background: #eef6ff;
            border: 1px solid #c9def8;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.84rem;
            line-height: 1.35;
        }

        .social-health strong {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            letter-spacing: 0.01em;
        }

        .social-auth {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 2px 0 14px;
        }

        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: 1px solid #c8d2de;
            border-radius: 10px;
            background: #ffffff;
            color: #1d2b38;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 10px 12px;
            text-decoration: none;
        }

        .social-btn:hover {
            border-color: #a9bbcf;
            background: #f7fbff;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 12px;
        }

        .stack {
            display: grid;
            gap: 10px;
        }

        .otp-shell {
            border: 1px solid #d8e1ec;
            border-radius: 12px;
            background: #fbfdff;
            padding: 12px;
            margin-bottom: 12px;
        }

        .otp-title {
            margin: 0 0 6px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.95rem;
        }

        .subtle {
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.35;
            margin: 0;
        }

        .switch-row {
            display: flex;
            gap: 8px;
            margin: 10px 0 12px;
            flex-wrap: wrap;
        }

        .switch-chip {
            border: 1px solid #c8d2de;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #24415f;
            background: #fff;
        }

        .switch-chip.active {
            background: #edf6ff;
            border-color: #b8d3ef;
        }

        button {
            border: 0;
            background: var(--brand);
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }

        button:hover {
            background: var(--brand-dark);
        }

        a {
            text-decoration: none;
            color: #18466e;
            font-weight: 600;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .wrap { padding: 20px 12px; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="shell">
            <div class="shell-head">Log in or sign up as partner</div>
            <div class="card">
            <span class="eyebrow">Partner Onboarding</span>
            <h1>Welcome to Workation</h1>

            <div class="social-auth">
                <a class="social-btn" href="/portal/vendor/oauth/google/redirect" aria-label="Continue with Google">Continue with Google</a>
                <a class="social-btn" href="/portal/vendor/oauth/apple/redirect" aria-label="Continue with Apple">Continue with Apple</a>
                <a class="social-btn" href="/portal/vendor/oauth/facebook/redirect" aria-label="Continue with Facebook">Continue with Facebook</a>
                <a class="social-btn" href="#email-auth" aria-label="Continue with Email">Continue with Email</a>
            </div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="ok">{{ session('status') }}</div>
            @endif

            @if (session('oauth_retry_guidance'))
                <div class="hint">{{ session('oauth_retry_guidance') }}</div>
            @endif

            <div id="social-health" class="social-health" role="status" aria-live="polite">
                Checking social login status...
            </div>

            <div class="hint">
                If a social login fails, retry once. If it still fails, continue with Google or email login while provider verification finishes.
            </div>

            <section id="email-auth" class="otp-shell" aria-label="Email OTP Login and Signup">
                <h2 class="otp-title">Continue with Email OTP</h2>
                <p class="subtle">Use your email to get a 6-digit OTP. Existing vendors will log in. First-time vendors can complete a minimal registration and continue.</p>

                <div class="switch-row" aria-hidden="true">
                    <span class="switch-chip active">Log in</span>
                    <span class="switch-chip">Sign up</span>
                </div>

                <form class="stack" method="POST" action="/portal/vendor/email-otp/send">
                    @csrf
                    <div class="field">
                        <label for="otp_email">Email Address</label>
                        <input id="otp_email" name="email" type="email" value="{{ old('email', session('otp_email')) }}" required>
                    </div>

                    <div class="actions">
                        <button type="submit">Send 6-digit OTP</button>
                    </div>
                </form>

                <form class="stack" method="POST" action="/portal/vendor/email-otp/verify" style="margin-top:12px;">
                    @csrf
                    <div class="field">
                        <label for="verify_email">Email Address</label>
                        <input id="verify_email" name="email" type="email" value="{{ old('email', session('otp_email')) }}" required>
                    </div>

                    <div class="field">
                        <label for="otp_code">OTP Code</label>
                        <input id="otp_code" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required>
                        <p class="field-note">Enter the OTP sent to your email. Code expires in 10 minutes.</p>
                    </div>

                    <div class="field">
                        <label for="legal_name">Legal Name (required for first-time registration)</label>
                        <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name') }}" placeholder="Contact legal name">
                    </div>

                    <div class="field">
                        <label for="contact_phone">Contact Number (required for first-time registration)</label>
                        <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone') }}" placeholder="+960...">
                    </div>

                    <div class="field field-full">
                        <label>
                            <input type="checkbox" name="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }} style="width:auto;margin-right:6px;">
                            I agree to the Terms of Service and Privacy Policy (required for first-time registration).
                        </label>
                    </div>

                    <div class="actions">
                        <button type="submit">Verify and Continue</button>
                    </div>
                </form>
            </section>

            <div class="actions" style="margin-top:10px;justify-content:space-between;">
                <a href="/">Back to Home</a>
                <a href="/terms-of-service">Terms</a>
            </div>
            </div>
        </section>
    </main>
</body>
<script>
    (function () {
        var statusBox = document.getElementById('social-health');
        if (!statusBox) {
            return;
        }

        fetch('/portal/vendor/oauth/health', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Health endpoint unavailable');
                }
                return response.json();
            })
            .then(function (payload) {
                var providers = payload && payload.providers ? payload.providers : {};
                var ordered = ['google', 'facebook', 'apple'];
                var parts = [];

                ordered.forEach(function (provider) {
                    var current = providers[provider] || {};
                    var configured = current.configured === true;
                    var secure = current.redirect_uses_https === true;
                    var hostMatch = current.redirect_host_matches_app === true;
                    var state = configured && secure && hostMatch ? 'OK' : 'Needs attention';
                    parts.push(provider.charAt(0).toUpperCase() + provider.slice(1) + ': ' + state);
                });

                statusBox.innerHTML = '<strong>Social Login Status</strong><br>' + parts.join(' | ');
            })
            .catch(function () {
                statusBox.textContent = 'Social login status is temporarily unavailable. You can still continue with email signup.';
            });
    })();
</script>
</html>

