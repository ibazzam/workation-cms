<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $portalName ?? 'Portal' }} Forgot Password | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --ink: #182433; --muted: #5a6677; --line: #d6dfe7; }
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
        h1 { margin: 8px 0 6px; font-size: 1.6rem; line-height: 1.15; }
        p { margin: 0 0 14px; color: var(--muted); }
        label { display: block; margin-bottom: 6px; font-size: 0.86rem; font-weight: 600; }
        input {
            width: 100%;
            border: 1px solid #c8d2de;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .msg {
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.86rem;
            border: 1px solid #a6d8b6;
            background: #e8f8ee;
            color: #0d5a2a;
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
        .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        button {
            border: 0;
            background: #0f6288;
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }
        a { text-decoration: none; color: #18466e; font-weight: 600; font-size: 0.9rem; }

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
    </style>
</head>
<body>
    <main class="frame">
    <section class="card">
        <span class="eyebrow">Secure Access</span>
        <h1>Forgot {{ $portalName ?? 'Portal' }} Password</h1>
        <p>Enter your {{ strtolower($portalName ?? 'portal') }} account email and we will send a secure reset link.</p>

        @if (session('status'))
            <div class="msg">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/portal/{{ $portal ?? 'admin' }}/forgot-password">
            @csrf
            <label for="email">{{ $portalName ?? 'Portal' }} Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>

            <div class="actions">
                <button type="submit">Send Reset Link</button>
                <a href="/portal/{{ $portal ?? 'admin' }}/login">Back to Login</a>
            </div>
        </form>
    </section>

    <footer class="footer-links" aria-label="Global support links">
        <a href="/terms-of-service">Terms of Service</a>
        <a href="/privacy-policy">Privacy Policy</a>
        <a href="mailto:support@workation.mv">Email Support</a>
        <a href="https://api.workation.mv/api/v1/ops/runbooks" target="_blank" rel="noopener">Operations Runbooks</a>
    </footer>
    </main>
</body>
</html>
