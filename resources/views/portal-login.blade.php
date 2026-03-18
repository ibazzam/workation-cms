<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $portalName }} Portal Login | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --ink: #182433;
            --muted: #5a6677;
            --line: #d6dfe7;
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

        button {
            border: 0;
            background: #0f6288;
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        a {
            text-decoration: none;
            color: #18466e;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <section class="card">
        <span class="eyebrow">Secure Access</span>
        <h1>{{ $portalName }} Portal Login</h1>
        <p>Sign in with your assigned portal account username and password.</p>

        @if ($portal === 'vendor')
            <div class="hint">Vendor access requires an enabled account with <strong>VENDOR</strong> role. Too many failed attempts will temporarily lock login for security.</div>
        @else
            <div class="hint">Admin access is restricted to enabled admin roles. Repeated failed attempts are rate-limited for security.</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="error" style="color:#0d5a2a;border-color:#a6d8b6;background:#e8f8ee;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="/portal/{{ $portal }}/login">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <div class="actions">
                <button id="loginBtn" type="submit">Log In</button>
                <a href="/">Back to Home</a>
                <a href="/portal/{{ $portal }}/forgot-password">Forgot Password?</a>
                @if ($portal === 'vendor')
                    <a href="/portal/vendor/register">Register as Vendor</a>
                @endif
            </div>
        </form>
    </section>

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
