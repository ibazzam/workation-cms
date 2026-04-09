<style>
    .wf-site-footer {
        margin-top: 16px;
        border-top: 1px solid #c8d8e5;
        padding-top: 14px;
    }

    .wf-footer-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .wf-footer-col {
        border: 1px solid #d0e0eb;
        border-radius: 12px;
        background: linear-gradient(162deg, #ffffff 0%, #f3f8fc 100%);
        box-shadow: 0 10px 20px rgba(21, 63, 94, 0.06);
        padding: 12px;
    }

    .wf-footer-title {
        margin: 0 0 10px;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #37526a;
        font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        position: relative;
        padding-bottom: 7px;
    }

    .wf-footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 44px;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f6179 0%, #2d8ea2 100%);
    }

    .wf-footer-links {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 7px;
    }

    .wf-footer-links a {
        text-decoration: none;
        color: #1c4666;
        font-size: 0.84rem;
        font-weight: 600;
        transition: color 0.18s ease;
    }

    .wf-footer-links a:hover {
        color: #0f6179;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .wf-footer-note {
        margin-top: 10px;
        font-size: 0.78rem;
        color: #567086;
        text-align: center;
    }

    .wf-footer-social {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #dbe7ef;
    }

    .wf-footer-social-title {
        margin: 0 0 8px;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #4c687f;
        font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
    }

    .wf-social-links {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .wf-social-links a {
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0 10px;
        border: 1px solid #cfe0eb;
        border-radius: 999px;
        background: #ffffff;
        color: #214b68;
        font-size: 0.76rem;
        font-weight: 700;
        transition: border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }

    .wf-social-links a:hover {
        border-color: #89adc5;
        color: #0f6179;
        box-shadow: 0 4px 10px rgba(21, 63, 94, 0.12);
    }

    @media (max-width: 980px) {
        .wf-footer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 640px) {
        .wf-footer-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<footer class="wf-site-footer" aria-label="Global footer links">
    <div class="wf-footer-grid">
        <section class="wf-footer-col" aria-label="Explore links">
            <h2 class="wf-footer-title">Explore</h2>
            <ul class="wf-footer-links">
                <li><a href="/">Home</a></li>
                <li><a href="/customer">Member Portal</a></li>
                <li><a href="/customer?category=Accommodation">Stays and Properties</a></li>
                <li><a href="/blog">Things to Do</a></li>
                <li><a href="/catalog/marine-transport">Marine Transport</a></li>
                <li><a href="/catalog/land-transport">Land Transport</a></li>
                <li><a href="/customer?category=Experiences">Experiences and Tours</a></li>
            </ul>
        </section>

        <section class="wf-footer-col" aria-label="Portal links">
            <h2 class="wf-footer-title">Portals</h2>
            <ul class="wf-footer-links">
                <li><a href="/portal/customer/login">Member Login</a></li>
                <li><a href="/portal/customer/register">Member Registration</a></li>
                <li><a href="/vendor">Vendor Portal</a></li>
                <li><a href="/admin">Admin Portal</a></li>
            </ul>
        </section>

        <section class="wf-footer-col" aria-label="Company links">
            <h2 class="wf-footer-title">About Us</h2>
            <ul class="wf-footer-links">
                <li><a href="/">Our Story</a></li>
                <li><a href="/terms-of-service">Terms of Service</a></li>
                <li><a href="/privacy-policy">Privacy Policy</a></li>
            </ul>
        </section>

        <section class="wf-footer-col" aria-label="Support links">
            <h2 class="wf-footer-title">Support</h2>
            <ul class="wf-footer-links">
                <li><a href="mailto:support@workation.mv">Email Support</a></li>
                <li><a href="{{ $apiBase ?? workationApiBase() }}/api/v1/health" target="_blank" rel="noopener">API Health</a></li>
                <li><a href="{{ $apiBase ?? workationApiBase() }}/api/v1/ops/runbooks" target="_blank" rel="noopener">Runbooks</a></li>
            </ul>
            <div class="wf-footer-social" aria-label="Social media links">
                <p class="wf-footer-social-title">Follow Us</p>
                <ul class="wf-social-links">
                    <li><a href="https://www.facebook.com/" target="_blank" rel="noopener">Facebook</a></li>
                    <li><a href="https://www.instagram.com/" target="_blank" rel="noopener">Instagram</a></li>
                    <li><a href="https://www.linkedin.com/" target="_blank" rel="noopener">LinkedIn</a></li>
                    <li><a href="https://x.com/" target="_blank" rel="noopener">X</a></li>
                    <li><a href="https://www.youtube.com/" target="_blank" rel="noopener">YouTube</a></li>
                </ul>
            </div>
        </section>
    </div>
    <p class="wf-footer-note">&copy; 2024 WORKATION&trade; &mdash; All rights reserved.</p>
</footer>