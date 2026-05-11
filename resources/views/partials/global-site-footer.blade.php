@once
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endonce

@php
    // Get dynamic branding info
    $logoAndTagline = function_exists('workationLogoAndTaglineProfile') ? workationLogoAndTaglineProfile() : [];
    $footerBrandLogo = trim((string) ($logoAndTagline['logo_url'] ?? ''));
    $footerBrandName = trim((string) ($logoAndTagline['brand_name'] ?? 'Workation'));
    $footerBrandLogoAlt = trim((string) ($logoAndTagline['logo_alt'] ?? 'Workation logo'));
    $footerBrandTagline = trim((string) ($logoAndTagline['tagline'] ?? 'Maldives Travel Market'));
@endphp

<style>
    .wf-site-footer {
        margin-top: 28px;
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0) 34%),
            linear-gradient(135deg, #E2F7F2 0%, #D6EFE9 100%);
        border-top: 1px solid rgba(162, 201, 192, 0.52);
        color: #1f3f49;
    }

    .wf-footer-shell {
        width: min(1240px, calc(100% - 32px));
        margin: 0 auto;
        padding: 28px 0 18px;
    }

    .wf-footer-top {
        display: block;
        padding-bottom: 20px;
    }

    .wf-footer-brand-block {
        display: grid;
        gap: 6px;
    }

    .wf-footer-brand {
        display: inline-flex;
        align-items: center;
        color: #17343d;
        text-decoration: none;
        font-size: 1.7rem;
        line-height: 1;
        letter-spacing: -0.04em;
        font-weight: 900;
    }

    .wf-footer-kicker {
        margin: 0;
        font-size: 0.78rem;
        color: rgba(31, 63, 73, 0.78);
        letter-spacing: 0.06em;
    }

    .wf-footer-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
        border-top: 1px solid rgba(162, 201, 192, 0.3);
        border-bottom: 1px solid rgba(162, 201, 192, 0.3);
    }

    .wf-footer-col {
        padding: 18px 20px;
        border-left: 1px solid rgba(162, 201, 192, 0.24);
    }

    .wf-footer-col:first-child {
        border-left: none;
    }

    .wf-footer-title {
        margin: 0 0 10px;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: rgba(31, 63, 73, 0.78);
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
        background: linear-gradient(90deg, #79b8a8 0%, #b7ddd4 100%);
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
        color: #2fa58a;
        font-size: 0.84rem;
        font-weight: 600;
        transition: color 0.18s ease, opacity 0.18s ease;
    }

    .wf-footer-links a:hover {
        color: #27917a;
        opacity: 0.86;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .wf-footer-note {
        margin: 0;
        font-size: 0.78rem;
        color: rgba(31, 63, 73, 0.76);
    }

    .wf-footer-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding-top: 16px;
        flex-wrap: wrap;
    }

    .wf-social-links {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .wf-social-links a {
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid rgba(162, 201, 192, 0.35);
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.58);
        color: #1f3f49;
        font-size: 0.96rem;
        transition: border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .wf-social-links a:hover {
        border-color: rgba(121, 166, 156, 0.6);
        color: #17343d;
        box-shadow: 0 8px 18px rgba(23, 52, 61, 0.12);
        transform: translateY(-1px);
    }

    @media (max-width: 980px) {
        .wf-footer-top,
        .wf-footer-bottom {
            align-items: flex-start;
            flex-direction: column;
        }

        .wf-footer-grid {
            grid-template-columns: 1fr 1fr;
        }

        .wf-footer-col {
            border-left: none;
        }

        .wf-footer-col:nth-child(even) {
            border-left: 1px solid rgba(162, 201, 192, 0.24);
        }

        .wf-footer-col:nth-child(n+3) {
            border-top: 1px solid rgba(162, 201, 192, 0.24);
        }
    }

    @media (max-width: 640px) {
        .wf-footer-shell {
            width: min(100%, calc(100% - 24px));
            padding-top: 24px;
        }

        .wf-footer-brand {
            font-size: 1.5rem;
        }

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
            border-top: 1px solid rgba(162, 201, 192, 0.24);
        }
    }
</style>

<footer class="wf-site-footer" aria-label="Global footer links">
    <div class="wf-footer-shell">
        <div class="wf-footer-top">
            <div class="wf-footer-brand-block">
                @if ($footerBrandLogo !== '')
                    <a class="wf-footer-brand" href="/" title="{{ $footerBrandLogoAlt }}" style="display: inline-flex; align-items: center; gap: 6px;">
                        <img src="{{ $footerBrandLogo }}" alt="{{ $footerBrandLogoAlt }}" style="max-height: 36px; width: auto;" onerror="this.style.display='none';var fb=this.parentElement.querySelector('[data-footer-brand-fallback]');if(fb){fb.style.display='inline';}">
                        <span data-footer-brand-fallback style="display:none;">{{ $footerBrandName }}</span>
                    </a>
                @else
                    <a class="wf-footer-brand" href="/">{{ $footerBrandName }}</a>
                @endif
                <p class="wf-footer-kicker">{{ $footerBrandTagline }}</p>
            </div>
        </div>

        <div class="wf-footer-grid">
            <section class="wf-footer-col" aria-label="Explore links">
                <h2 class="wf-footer-title">Explore</h2>
                <ul class="wf-footer-links">
                    <li><a href="/catalog/accommodation">Accommodation</a></li>
                    <li><a href="/catalog/excursion">Excursions</a></li>
                    <li><a href="/islands">Island Directory</a></li>
                    <li><a href="/blog">Blog</a></li>
                </ul>
            </section>

            <section class="wf-footer-col" aria-label="Portal links">
                <h2 class="wf-footer-title">Join Us</h2>
                <ul class="wf-footer-links">
                    <li><a href="/vendor">Partner Login</a></li>
                    <li><a href="/portal/vendor/register">Become a Partner</a></li>
                    <li><a href="/portal/customer/register">Member Registration</a></li>
                </ul>
            </section>

            <section class="wf-footer-col" aria-label="Company links">
                <h2 class="wf-footer-title">About Workation</h2>
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
        </div>

        <div class="wf-footer-bottom">
            <p class="wf-footer-note">&copy; 2024 WORKATION&trade; All rights reserved.</p>
            <ul class="wf-social-links">
                <li><a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook" title="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a></li>
                <li><a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a></li>
                <li><a href="https://www.linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn" title="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a></li>
                <li><a href="https://x.com/" target="_blank" rel="noopener" aria-label="X" title="X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a></li>
                <li><a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube" title="YouTube"><i class="fa-brands fa-youtube" aria-hidden="true"></i></a></li>
            </ul>
        </div>
    </div>
</footer>