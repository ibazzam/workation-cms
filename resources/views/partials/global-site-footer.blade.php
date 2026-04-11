@once
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endonce

<style>
    .wf-site-footer {
        margin-top: 16px;
        border-top: 1px solid #c8d8e5;
        padding-top: 14px;
    }

    /* Single unified container — sections separated by vertical dividers, not individual cards */
    .wf-footer-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
        background: linear-gradient(162deg, #ffffff 0%, #f3f8fc 100%);
        border: 1px solid #d0e0eb;
        border-radius: 12px;
        box-shadow: 0 10px 20px rgba(21, 63, 94, 0.06);
        overflow: hidden;
    }

    .wf-footer-col {
        padding: 16px 20px;
        border-left: 1px solid #d0e0eb;
    }

    .wf-footer-col:first-child {
        border-left: none;
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

    /* Full-width social bar at the bottom of the unified container */
    .wf-footer-social-bar {
        grid-column: 1 / -1;
        border-top: 1px solid #d0e0eb;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
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
        width: 36px;
        height: 36px;
        border: 1px solid #cfe0eb;
        border-radius: 50%;
        background: #ffffff;
        color: #214b68;
        font-size: 0.96rem;
        transition: border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .wf-social-links a:hover {
        border-color: #89adc5;
        color: #0f6179;
        box-shadow: 0 4px 10px rgba(21, 63, 94, 0.12);
        transform: translateY(-1px);
    }

    /* 2-column: vertical divider on even columns, horizontal divider between rows */
    @media (max-width: 980px) {
        .wf-footer-grid {
            grid-template-columns: 1fr 1fr;
        }

        .wf-footer-col {
            border-left: none;
        }

        .wf-footer-col:nth-child(even) {
            border-left: 1px solid #d0e0eb;
        }

        .wf-footer-col:nth-child(n+3) {
            border-top: 1px solid #d0e0eb;
        }
    }

    /* Single column: only horizontal dividers between stacked sections */
    @media (max-width: 640px) {
        .wf-footer-grid {
            grid-template-columns: 1fr;
        }

        .wf-footer-col,
        .wf-footer-col:nth-child(even) {
            border-left: none;
        }

        .wf-footer-col:nth-child(n+3) {
            border-top: none;
        }

        .wf-footer-col:not(:first-child) {
            border-top: 1px solid #d0e0eb;
        }
    }
</style>

<footer class="wf-site-footer" aria-label="Global footer links">
    <div class="wf-footer-grid">
        <section class="wf-footer-col" aria-label="Explore links">
            <h2 class="wf-footer-title">Explore</h2>
            <ul class="wf-footer-links">
                <li><a href="/">Home</a></li>
                <li><a href="/blog">Travel picks</a></li>
            </ul>
        </section>

        <section class="wf-footer-col" aria-label="Portal links">
            <h2 class="wf-footer-title">Join Us</h2>
            <ul class="wf-footer-links">
                <li><a href="/vendor">Partner Login</a></li>
                <li><a href="/admin">Admin Login</a></li>
                <li><a href="/portal/vendor/register">Become a Partner</a></li>
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
                <li><a href="/portal/customer/forgot-password">Account Help</a></li>
                <li><a href="/privacy-policy">Privacy and Data Requests</a></li>
            </ul>
        </section>

        <div class="wf-footer-social-bar" aria-label="Social media links">
            <ul class="wf-social-links">
                <li><a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook" title="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a></li>
                <li><a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a></li>
                <li><a href="https://www.linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn" title="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a></li>
                <li><a href="https://x.com/" target="_blank" rel="noopener" aria-label="X" title="X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a></li>
                <li><a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube" title="YouTube"><i class="fa-brands fa-youtube" aria-hidden="true"></i></a></li>
            </ul>
        </div>
    </div>
    <p class="wf-footer-note">&copy; 2024 WORKATION&trade; &mdash; All rights reserved.</p>
</footer>