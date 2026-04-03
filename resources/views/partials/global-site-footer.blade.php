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
        border: 1px solid #d6e3ec;
        border-radius: 12px;
        background: #f8fcff;
        padding: 11px;
    }

    .wf-footer-title {
        margin: 0 0 8px;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #37526a;
        font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
    }

    .wf-footer-links {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 6px;
    }

    .wf-footer-links a {
        text-decoration: none;
        color: #1c4666;
        font-size: 0.84rem;
        font-weight: 600;
    }

    .wf-footer-links a:hover {
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .wf-footer-note {
        margin-top: 10px;
        font-size: 0.78rem;
        color: #567086;
        text-align: center;
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
                <li><a href="/customer">Customer Portal</a></li>
                <li><a href="/customer?category=Accommodation">Stays and Properties</a></li>
                <li><a href="/customer?category=Transport">Transport Services</a></li>
                <li><a href="/customer?category=Experiences">Experiences and Tours</a></li>
            </ul>
        </section>

        <section class="wf-footer-col" aria-label="Portal links">
            <h2 class="wf-footer-title">Portals</h2>
            <ul class="wf-footer-links">
                <li><a href="/portal/customer/login">Customer Login</a></li>
                <li><a href="/portal/customer/register">Customer Registration</a></li>
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
        </section>
    </div>
    <p class="wf-footer-note">&copy; WORKATION &trade; &mdash; All rights reserved.</p>
</footer>
