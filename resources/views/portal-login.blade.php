<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $portal === 'customer' ? 'Member' : $portalName }} Portal Login | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --ink: #182433;
            --muted: #425164;
            --line: #d6dfe7;
            --focus: #0b6aa2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 15% 10%, #d8ece9 0, #d8ece900 35%),
                radial-gradient(circle at 85% 8%, #e4e9ff 0, #e4e9ff00 33%),
                linear-gradient(120deg, #edf5f1 0%, #f2ede5 100%);
        }

        .card {
            width: min(460px, 92vw);
            background: #fffefb;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 22px 44px rgba(20, 38, 58, 0.14);
        }

        .frame {
            width: min(640px, 94vw);
        }

        .eyebrow {
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
        }

        h1 {
            margin: 8px 0 6px;
            font-size: 1.7rem;
            line-height: 1.15;
        }

        p {
            margin: 0 0 14px;
            color: var(--muted);
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        input {
            width: 100%;
            border: 1px solid #c8d2de;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        input:focus-visible,
        button:focus-visible,
        a:focus-visible {
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

        .hint {
            color: #1f4a67;
            background: #e9f4ff;
            border: 1px solid #bedcf5;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.84rem;
            line-height: 1.35;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .social-auth {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 10px 0 12px;
        }

        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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

        .social-btn .social-icon {
            font-size: 1rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .social-btn.social-google { border-color: #d9d9d9; color: #444; }
        .social-btn.social-google .social-icon { color: #4285f4; }
        .social-btn.social-facebook { border-color: #b5c5d6; color: #1877f2; }
        .social-btn.social-facebook .social-icon { color: #1877f2; }
        .social-btn.social-apple { border-color: #c8cdd3; color: #111; }
        .social-btn.social-apple .social-icon { color: #111; }

        .social-btn.disabled {
            opacity: 0.55;
            pointer-events: none;
            cursor: not-allowed;
        }

        a {
            text-decoration: none;
            color: #18466e;
            font-weight: 600;
            font-size: 0.9rem;
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

    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $socialProviders = is_array($socialProviders ?? null) ? $socialProviders : [];
        $portalDisplayName = $portal === 'customer' ? 'Member' : $portalName;
        $socialIconMap = [
            'google'   => ['class' => 'fa-brands fa-google',    'css' => 'social-google'],
            'facebook' => ['class' => 'fa-brands fa-facebook-f','css' => 'social-facebook'],
            'apple'    => ['class' => 'fa-brands fa-apple',     'css' => 'social-apple'],
        ];
    @endphp
    <main class="frame">
    <section class="card">
        <span class="eyebrow">Secure Access</span>
        <h1>{{ $portalDisplayName }} Portal Login</h1>
        <p>Sign in with your assigned portal account username and password.</p>

        @if ($portal === 'vendor')
            <div class="hint">Vendor access requires an enabled account with <strong>VENDOR</strong> role. Too many failed attempts will temporarily lock login for security.</div>
        @elseif ($portal === 'customer')
            <div class="hint">Member access uses your registered email and password from the member signup flow.</div>
        @else
            <div class="hint">Admin access is restricted to enabled admin roles. Repeated failed attempts are rate-limited for security.</div>
        @endif

        @if ($errors->any())
            <div class="error" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="error" role="status" aria-live="polite" style="color:#0d5a2a;border-color:#a6d8b6;background:#e8f8ee;">{{ session('status') }}</div>
        @endif

        @if ($portal === 'customer' && session('pending_verification_email'))
            <div class="hint" role="status" aria-live="polite">
                Email verification is required before member login.
                <form method="POST" action="/portal/customer/verify-email/resend" style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('pending_verification_email') }}">
                    <button type="submit">Resend Verification Email</button>
                </form>
            </div>
        @endif

        @if (in_array($portal, ['customer', 'vendor'], true) && !empty($socialProviders))
            <div class="social-auth" aria-label="Social sign in options">
                @foreach ($socialProviders as $provider => $meta)
                    @php
                        $isConfigured = (bool) ($meta['configured'] ?? false);
                        $redirectUrl = (string) ($meta['redirect'] ?? '#');
                    @endphp
                    @php
                        $providerName  = ucfirst((string) $provider);
                        $iconEntry     = $socialIconMap[(string) $provider] ?? ['class' => 'fa-solid fa-link', 'css' => ''];
                        $iconClass     = $iconEntry['class'];
                        $cssClass      = $iconEntry['css'];
                    @endphp
                    <a class="social-btn {{ $cssClass }} {{ $isConfigured ? '' : 'disabled' }}"
                       href="{{ $redirectUrl }}"
                       aria-label="Continue with {{ $providerName }}">
                        <i class="{{ $iconClass }} social-icon" aria-hidden="true"></i>
                        Continue with {{ $providerName }}
                    </a>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/portal/{{ $portal }}/login" aria-describedby="portal-login-hint">
            @csrf
            @if ($portal === 'customer' && trim((string) request()->query('continue', '')) !== '')
                <input type="hidden" name="continue" value="{{ trim((string) request()->query('continue', '')) }}">
            @endif
            <p id="portal-login-hint" class="sr-only">Enter your username and password, then submit to sign in.</p>
            <label for="username">{{ $portal === 'customer' ? 'Email' : 'Username' }}</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <div class="actions">
                <button id="loginBtn" type="submit">Log In</button>
                <a href="/">Back to Home</a>
                <a href="/portal/{{ $portal }}/forgot-password">Forgot Password?</a>
                @if ($portal === 'vendor')
                    <a href="/portal/vendor/register">Become a Partner</a>
                @elseif ($portal === 'customer')
                    <a href="/portal/customer/register">Create Member Account</a>
                @endif
            </div>
        </form>
    </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('form[action^="/portal/"]');
            var button = document.getElementById('loginBtn');
            if (!form || !button) {
                return;
            }

            form.addEventListener('submit', function () {
                button.disabled = true;
                button.textContent = 'Signing In...';
            });
        });
    </script>
</body>
</html>