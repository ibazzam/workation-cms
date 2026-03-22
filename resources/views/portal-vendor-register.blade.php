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
            --muted: #424242;
            --line: #d9d9d9;
            --brand: #0f6288;
            --brand-dark: #0c4f6d;
            --bg: #f7f7f7;
            --focus: #0b6aa2;
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

        .footer-links {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .footer-links a {
            text-decoration: none;
            border: 1px solid #c8d3df;
            border-radius: 10px;
            background: #fff;
            color: #20415d;
            padding: 9px 10px;
            font-weight: 700;
            font-size: 0.82rem;
            text-align: center;
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

        input:focus-visible,
        select:focus-visible,
        button:focus-visible,
        a:focus-visible,
        .social-btn:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--focus) 28%, transparent);
            outline-offset: 2px;
            box-shadow: 0 0 0 2px #ffffff;
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

        .social-btn svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
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
            @php
                $currentMode = isset($mode) ? (string) $mode : 'email';
                $signupPayload = isset($minimalPayload) && is_array($minimalPayload) ? $minimalPayload : [];
                $prefillIdentifier = (string) old('identifier', (string) old('email', (string) session('otp_identifier', (string) session('otp_email', (string) ($signupPayload['email'] ?? '')))));
                $prefillGivenName = (string) old('given_name', (string) ($signupPayload['given_name'] ?? ''));
                $prefillFamilyName = (string) old('family_name', (string) ($signupPayload['family_name'] ?? ''));
                $prefillContactPhone = (string) old('contact_phone', (string) ($signupPayload['contact_phone'] ?? ''));
                $providerLabel = ucfirst((string) ($signupPayload['provider'] ?? 'email'));
                $verifiedContact = (string) ($signupPayload['provider'] ?? '') === 'phone'
                    ? (string) ($signupPayload['contact_phone'] ?? '')
                    : (string) ($signupPayload['email'] ?? '');
            @endphp
            <span class="eyebrow">Partner Onboarding</span>
            <h1>Welcome to Workation</h1>

            @if ($errors->any())
                <div class="error" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="ok" role="status" aria-live="polite">{{ session('status') }}</div>
            @endif

            @if (session('oauth_retry_guidance'))
                <div class="hint" role="status" aria-live="polite">{{ session('oauth_retry_guidance') }}</div>
            @endif

            @if (session('otp_delivery_guidance'))
                <div class="hint" role="status" aria-live="polite">{{ session('otp_delivery_guidance') }}</div>
            @endif

            @if ($currentMode === 'email')
                <section id="email-auth" class="otp-shell" aria-label="Email Login and Signup">
                    <h2 class="otp-title">Continue with Email or Phone</h2>
                    <p class="subtle">Enter your email address or phone number and we will send a 6-digit OTP. Existing vendors log in after OTP verification.</p>

                    <form class="stack" method="POST" action="/portal/vendor/email-otp/send">
                        @csrf
                        <div class="field">
                            <label for="otp_identifier">Email Address or Phone Number</label>
                            <input id="otp_identifier" name="identifier" type="text" value="{{ $prefillIdentifier }}" placeholder="name@example.com or +960..." autocomplete="username" required>
                        </div>

                        <div class="actions">
                            <button type="submit">Continue</button>
                        </div>
                    </form>
                </section>

                <div id="social-health" class="social-health" role="status" aria-live="polite">
                    Checking social login status...
                </div>

                <div class="social-auth">
                    <a class="social-btn" href="/portal/vendor/oauth/google/redirect" aria-label="Continue with Google" title="Continue with Google">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.5 3.9-5.4 3.9-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.9 1.5l2.7-2.6C16.9 3.3 14.6 2.4 12 2.4 6.7 2.4 2.4 6.7 2.4 12S6.7 21.6 12 21.6c6.9 0 9.1-4.8 9.1-7.3 0-.5-.1-.8-.1-1.2H12z"/>
                        </svg>
                        <span class="sr-only">Continue with Google</span>
                    </a>
                    <a class="social-btn" href="/portal/vendor/oauth/apple/redirect" aria-label="Continue with Apple" title="Continue with Apple">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="#111111" d="M16.8 12.7c0-2.3 1.9-3.3 2-3.4-1.1-1.6-2.8-1.8-3.4-1.8-1.5-.2-2.9.9-3.7.9-.8 0-2-.9-3.2-.9-1.7 0-3.2 1-4.1 2.5-1.8 3.1-.5 7.7 1.3 10.3.9 1.3 1.9 2.7 3.3 2.6 1.3-.1 1.8-.8 3.4-.8s2 .8 3.4.8c1.4 0 2.3-1.3 3.2-2.6 1-1.4 1.4-2.7 1.4-2.8-.1 0-2.7-1-2.7-4.8zM14.4 5.9c.7-.8 1.1-1.9 1-3-.9 0-2.1.6-2.8 1.4-.6.7-1.2 1.9-1 3 1 .1 2.1-.5 2.8-1.4z"/>
                        </svg>
                        <span class="sr-only">Continue with Apple</span>
                    </a>
                    <a class="social-btn" href="/portal/vendor/oauth/facebook/redirect" aria-label="Continue with Facebook" title="Continue with Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="#1877F2" d="M24 12a12 12 0 1 0-13.9 11.8v-8.3H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 1-2 1.9V12h3.4l-.5 3.5h-2.9v8.3A12 12 0 0 0 24 12z"/>
                        </svg>
                        <span class="sr-only">Continue with Facebook</span>
                    </a>
                </div>

                <div class="hint">
                    If a social login fails, retry once. If it still fails, continue with Google or email login while provider verification finishes.
                </div>
            @elseif ($currentMode === 'otp')
                <section class="otp-shell" aria-label="OTP Verification">
                    <h2 class="otp-title">Verify OTP</h2>
                    <p class="subtle">Enter the 6-digit OTP sent to <strong>{{ $prefillIdentifier }}</strong>. Code expires in 10 minutes.</p>

                    <form class="stack" method="POST" action="/portal/vendor/email-otp/verify">
                        @csrf
                        <div class="field">
                            <label for="verify_identifier">Email Address or Phone Number</label>
                            <input id="verify_identifier" name="identifier" type="text" value="{{ $prefillIdentifier }}" autocomplete="username" required>
                        </div>

                        <div class="field">
                            <label for="otp_code">OTP Code</label>
                            <input id="otp_code" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required>
                        </div>

                        <div class="actions">
                            <button type="submit">Verify and Continue</button>
                        </div>
                    </form>

                    <div class="actions" style="margin-top:10px;">
                        <a href="/portal/vendor/register?mode=email">Use another email or phone</a>
                    </div>
                </section>
            @else
                <section class="otp-shell" aria-label="Minimal Vendor Registration">
                    <h2 class="otp-title">Complete Minimal Registration</h2>
                    <p class="subtle">{{ $providerLabel }} verified. Complete these required details to finish signup.</p>

                    <form class="stack" method="POST" action="/portal/vendor/minimal-register">
                        @csrf
                        <div class="field">
                            <label for="reg_contact">Verified Contact</label>
                            <input id="reg_contact" type="text" value="{{ $verifiedContact }}" disabled>
                        </div>

                        <div class="field">
                            <label for="given_name">Given Name / First Name</label>
                            <input id="given_name" name="given_name" type="text" value="{{ $prefillGivenName }}" required>
                        </div>

                        <div class="field">
                            <label for="family_name">Family Name / Surname</label>
                            <input id="family_name" name="family_name" type="text" value="{{ $prefillFamilyName }}" required>
                        </div>

                        <div class="field">
                            <label for="contact_phone">Contact Number</label>
                            <input id="contact_phone" name="contact_phone" type="text" value="{{ $prefillContactPhone }}" placeholder="+960..." autocomplete="tel" required>
                        </div>

                        <div class="field field-full">
                            <label for="agree_terms">
                                <input id="agree_terms" type="checkbox" name="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }} style="width:auto;margin-right:6px;">
                                I agree to the Terms of Service and Privacy Policy.
                            </label>
                        </div>

                        <div class="actions">
                            <button type="submit">Agree and Register</button>
                        </div>
                    </form>
                </section>
            @endif

            </div>
        </section>

        <footer class="footer-links" aria-label="Global support links">
            <a href="/terms-of-service">Terms of Service</a>
            <a href="/privacy-policy">Privacy Policy</a>
            <a href="mailto:support@workation.mv">Email Support</a>
            <a href="https://api.workation.mv/api/v1/ops/runbooks" target="_blank" rel="noopener">Operations Runbooks</a>
        </footer>
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

