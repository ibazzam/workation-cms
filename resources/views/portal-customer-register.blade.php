<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Registration | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
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
            width: min(520px, 92vw);
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

        .status {
            color: #0d5a2a;
            background: #e8f8ee;
            border: 1px solid #a6d8b6;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.86rem;
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
            margin: 0 0 12px;
        }

        .social-btn {
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
    </style>
    @include('partials.uniform-buttons')
</head>
<body>
    @php
        $socialProviders = is_array($socialProviders ?? null) ? $socialProviders : [];
    @endphp
    <section class="card">
        <span class="eyebrow">Customer Access</span>
        <h1>Create Customer Account</h1>
        <p>Register with your name, email, and password to continue to the customer portal.</p>

        @if ($errors->any())
            <div class="error" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="status" role="status" aria-live="polite">{{ session('status') }}</div>
        @endif

        @if (!empty($socialProviders))
            <div class="social-auth" aria-label="Social registration and sign in options">
                @foreach ($socialProviders as $provider => $meta)
                    @php
                        $isConfigured = (bool) ($meta['configured'] ?? false);
                        $redirectUrl = (string) ($meta['redirect'] ?? '#');
                    @endphp
                    <a class="social-btn {{ $isConfigured ? '' : 'disabled' }}" href="{{ $redirectUrl }}" aria-label="Continue with {{ ucfirst((string) $provider) }}">Continue with {{ ucfirst((string) $provider) }}</a>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/portal/customer/register">
            @csrf
            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <div class="actions">
                <button type="submit">Create Account</button>
                <a href="/portal/customer/login">Customer Login</a>
                <a href="/">Back to Home</a>
            </div>
        </form>
    </section>
</body>
</html>