<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facebook Data Deletion Instructions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; color: #111827; line-height: 1.6; }
        main { max-width: 760px; }
        h1 { margin-bottom: 0.5rem; }
        p, li { font-size: 1rem; }
        code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 4px; }

        .footer-links {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .footer-links a {
            text-decoration: none;
            border: 1px solid #d0dae4;
            border-radius: 10px;
            background: #ffffff;
            color: #20415d;
            padding: 9px 10px;
            font-weight: 700;
            font-size: 0.82rem;
            text-align: center;
        }
    </style>
</head>
<body>
<main>
    <h1>Facebook Data Deletion Instructions</h1>
    <p>If you want your Workation vendor account data removed, use one of these options:</p>
    <ol>
        <li>Open the Workation partners portal and request account deletion from support.</li>
        <li>Email our support team with your registered email and mention "Facebook data deletion".</li>
        <li>Or trigger deletion from Facebook, which calls our callback endpoint and returns a confirmation code.</li>
    </ol>

    <p>Data deletion callback endpoint:</p>
    <p><code>/portal/vendor/oauth/facebook/data-deletion</code></p>

    <p>After a valid deletion request, we acknowledge and process account-related data deletion according to platform policy.</p>

    <footer class="footer-links" aria-label="Global support links">
        <a href="/terms-of-service">Terms of Service</a>
        <a href="/privacy-policy">Privacy Policy</a>
        <a href="mailto:support@workation.mv">Email Support</a>
        <a href="https://api.workation.mv/api/v1/ops/runbooks" target="_blank" rel="noopener">Operations Runbooks</a>
    </footer>
</main>
</body>
</html>
