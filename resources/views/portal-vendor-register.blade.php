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
                <a class="social-btn" href="/portal/vendor/login" aria-label="Continue with Google">Continue with Google</a>
                <a class="social-btn" href="/portal/vendor/login" aria-label="Continue with Apple">Continue with Apple</a>
                <a class="social-btn" href="/portal/vendor/login" aria-label="Continue with Facebook">Continue with Facebook</a>
                <a class="social-btn" href="/portal/vendor/login" aria-label="Continue with Email">Continue with Email</a>
            </div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="ok">{{ session('status') }}</div>
            @endif

            <form method="POST" action="/portal/vendor/register">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label for="contact_name">Your Name</label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required>
                    </div>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="field">
                        <label for="phone">Phone Number</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required>
                    </div>

                    <div class="field">
                        <label for="vendor_type">Service Category</label>
                        <select id="vendor_type" name="vendor_type" required>
                            <option value="" {{ old('vendor_type') === null || old('vendor_type') === '' ? 'selected' : '' }}>Choose category</option>
                            <option value="accommodation" {{ old('vendor_type') === 'accommodation' ? 'selected' : '' }}>Accommodation</option>
                            <option value="transport" {{ old('vendor_type') === 'transport' ? 'selected' : '' }}>Transport</option>
                            <option value="restaurant" {{ old('vendor_type') === 'restaurant' ? 'selected' : '' }}>Restaurant</option>
                            <option value="excursions" {{ old('vendor_type') === 'excursions' ? 'selected' : '' }}>Excursions</option>
                            <option value="vehicle_rental" {{ old('vendor_type') === 'vehicle_rental' ? 'selected' : '' }}>Vehicle Rental</option>
                            <option value="small_service" {{ old('vendor_type') === 'small_service' ? 'selected' : '' }}>Small Service</option>
                            <option value="major_vendor" {{ old('vendor_type') === 'major_vendor' ? 'selected' : '' }}>Major Vendor</option>
                            <option value="other" {{ old('vendor_type') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="field field-full">
                        <label for="business_name">Business or Service Name (Optional for now)</label>
                        <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}">
                    </div>

                    <div class="field">
                        <label for="business_registration_number">Business Registration Number (Optional)</label>
                        <input id="business_registration_number" name="business_registration_number" type="text" value="{{ old('business_registration_number') }}">
                    </div>

                    <div class="field">
                        <label for="license_number">License Number (Optional)</label>
                        <input id="license_number" name="license_number" type="text" value="{{ old('license_number') }}">
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Continue</button>
                </div>

                <div class="actions" style="margin-top:10px;justify-content:space-between;">
                    <a href="/portal/vendor/login">Already joined? Continue with email</a>
                    <a href="/">Back to Home</a>
                </div>
            </form>
            </div>
        </section>
    </main>
</body>
</html>
