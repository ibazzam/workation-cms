<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Registration | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --ink: #192433;
            --muted: #5a6778;
            --line: #d6dfe8;
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
            padding: 20px;
        }

        .card {
            width: min(760px, 94vw);
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
            margin: 8px 0 8px;
            font-size: 1.75rem;
            line-height: 1.15;
        }

        p {
            margin: 0 0 14px;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .field {
            display: block;
        }

        .field-full {
            grid-column: 1 / -1;
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
            color: #1f4a67;
            background: #e9f4ff;
            border: 1px solid #bedcf5;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 14px;
            font-size: 0.84rem;
            line-height: 1.35;
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

        @media (max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <section class="card">
        <span class="eyebrow">Vendor Onboarding</span>
        <h1>Vendor Self Registration</h1>
        <p>Submit your business details, license, and verification documents. Admins will review your request before vendor portal access is granted.</p>

        <div class="hint">Approval is required before login is enabled. You will receive a password setup link after admin approval.</div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif

        <form method="POST" action="/portal/vendor/register" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div class="field">
                    <label for="business_name">Business Name</label>
                    <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" required>
                </div>

                <div class="field">
                    <label for="contact_name">Contact Name</label>
                    <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required>
                </div>

                <div class="field">
                    <label for="email">Business Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="field">
                    <label for="phone">Phone (Optional)</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                </div>

                <div class="field">
                    <label for="business_registration_number">Business Registration Number</label>
                    <input id="business_registration_number" name="business_registration_number" type="text" value="{{ old('business_registration_number') }}" required>
                </div>

                <div class="field">
                    <label for="license_number">License Number</label>
                    <input id="license_number" name="license_number" type="text" value="{{ old('license_number') }}" required>
                </div>

                <div class="field field-full">
                    <label for="business_license_document">Business License Document (PDF/JPG/PNG, max 10MB)</label>
                    <input id="business_license_document" name="business_license_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="field field-full">
                    <label for="verification_document">Additional Verification Document (Optional, PDF/JPG/PNG, max 10MB)</label>
                    <input id="verification_document" name="verification_document" type="file" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

            <div class="actions">
                <button type="submit">Submit Registration</button>
                <a href="/portal/vendor/login">Back to Vendor Login</a>
                <a href="/">Back to Home</a>
            </div>
        </form>
    </section>
</body>
</html>
