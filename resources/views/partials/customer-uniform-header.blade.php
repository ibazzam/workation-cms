@php
    $injectUniformHeaderStyles = isset($injectUniformHeaderStyles) ? (bool) $injectUniformHeaderStyles : true;
    $injectUniformHeaderScripts = isset($injectUniformHeaderScripts) ? (bool) $injectUniformHeaderScripts : true;
    $headerHideOnScroll = isset($headerHideOnScroll) ? (bool) $headerHideOnScroll : true;
    $headerRevealAtTopOnly = isset($headerRevealAtTopOnly) ? (bool) $headerRevealAtTopOnly : false;
    $headerNeedsSpacer = isset($headerNeedsSpacer) ? (bool) $headerNeedsSpacer : true;
    $headerMode = trim((string) ($headerMode ?? 'default'));
    $headerShowSearch = isset($headerShowSearch) ? (bool) $headerShowSearch : false;
    $headerSearchAction = trim((string) ($headerSearchAction ?? '/catalog/accommodation'));
    $headerSearchValue = trim((string) ($headerSearchValue ?? ''));
    $headerSearchPlaceholder = trim((string) ($headerSearchPlaceholder ?? 'Destinations, islands, hotels, and experiences'));
    $headerCategoryLinks = collect($headerCategoryLinks ?? [])->filter(static fn ($item) => is_array($item))->values();
    $headerHasBlogLink = $headerCategoryLinks->contains(static function ($item): bool {
        return trim((string) ($item['url'] ?? '')) === '/blog';
    });
    $headerActiveCategoryKey = trim((string) ($headerActiveCategoryKey ?? ''));
    $headerSubline = trim((string) ($headerSubline ?? 'Maldives Travel Market'));
    $headerCheckoutContext = is_array($headerCheckoutContext ?? null) ? $headerCheckoutContext : [];

    $customerLoggedIn = (bool) session('portal_customer_authenticated', false);
    $customerName = trim((string) session('portal_customer_user', 'Customer'));
    $headerContinueUrl = trim((string) ($headerContinueUrl ?? request()->fullUrl()));
    $checkoutProperty = trim((string) ($headerCheckoutContext['property'] ?? ''));
    $checkoutDates = trim((string) ($headerCheckoutContext['dates'] ?? ''));
    $checkoutGuests = trim((string) ($headerCheckoutContext['guests'] ?? ''));

    $headerMenuPanelId = 'customerMenuPanel_' . substr(md5((string) request()->path()), 0, 8);
    $headerLinksPanelId = 'customerLinksPanel_' . substr(md5((string) request()->path() . '_links'), 0, 8);
@endphp

@if ($injectUniformHeaderStyles)
<style>
    .uniform-header {
        position: sticky;
        top: 0;
        z-index: 1050;
        min-height: 72px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 16px;
        border-bottom: 1px solid #d4e2ec;
        background: #ffffff;
        background-color: #ffffff !important;
        opacity: 1;
        box-shadow: 0 6px 18px rgba(13, 43, 67, 0.08);
        transition: transform 0.22s ease, opacity 0.22s ease;
    }

    body.is-header-hidden .uniform-header[data-hide-on-scroll="1"] {
        transform: translateY(-108%);
        opacity: 0;
        pointer-events: none;
    }

    .uniform-header-spacer {
        height: 8px;
    }

    .uniform-header-main {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        flex: 1 1 auto;
    }

    .uniform-header-brand {
        text-decoration: none;
        color: #02193f;
        font-size: 1.7rem;
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 1;
        white-space: nowrap;
    }

    .uniform-header-brand-wrap {
        display: grid;
        gap: 2px;
        flex: 0 0 auto;
    }

    .uniform-header-subline {
        margin: 1px 0 0;
        font-size: 0.7rem;
        color: #71869a;
        white-space: nowrap;
    }

    .uniform-header-links {
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .uniform-header-links::-webkit-scrollbar {
        display: none;
    }

    .uniform-header-link {
        text-decoration: none;
        border: 1px solid #d2e1ec;
        border-radius: 999px;
        padding: 6px 10px;
        background: #f7fbff;
        color: #244d67;
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .uniform-header-link.is-active {
        background: #0f6179;
        border-color: #0f6179;
        color: #ffffff;
    }

    .uniform-header-links-toggle {
        display: none;
        border: 1px solid #c9d9e6;
        border-radius: 10px;
        padding: 7px 10px;
        background: #ffffff;
        color: #244c66;
        font-size: 0.78rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .uniform-header-search-mini {
        display: flex;
        align-items: center;
        min-width: 240px;
        width: min(420px, 100%);
        border: 1px solid #c7d9e5;
        border-radius: 10px;
        overflow: hidden;
        background: #f8fcff;
    }

    .uniform-header-search-mini input {
        border: 0;
        background: transparent;
        padding: 9px 10px;
        font: inherit;
        min-width: 0;
        width: 100%;
        color: #1f425b;
    }

    .uniform-header-search-mini button {
        border: 0;
        background: #0f6179;
        color: #ffffff;
        font: inherit;
        font-size: 0.8rem;
        font-weight: 700;
        height: 38px;
        padding: 0 10px;
        cursor: pointer;
    }

    .uniform-header-auth {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }

    .uniform-auth-link {
        text-decoration: none;
        border: 1px solid #c9d9e6;
        border-radius: 10px;
        padding: 7px 10px;
        background: #ffffff;
        color: #244c66;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .uniform-auth-link.primary {
        border-color: #0f6179;
        background: #0f6179;
        color: #ffffff;
    }

    .uniform-account-menu {
        position: relative;
    }

    .uniform-account-toggle {
        border: 1px solid #c9d9e6;
        border-radius: 10px;
        padding: 7px 10px;
        background: #ffffff;
        color: #244c66;
        font-size: 0.78rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
    }

    .uniform-account-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: min(270px, calc(100vw - 24px));
        border: 1px solid #c9ddeb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 18px 30px rgba(15, 50, 77, 0.2);
        overflow: hidden;
        z-index: 1200;
    }

    .uniform-account-panel-head {
        padding: 11px 12px;
        border-bottom: 1px solid #d8e6f0;
        background: #f5fbff;
    }

    .uniform-account-panel-head p {
        margin: 0;
    }

    .uniform-account-panel-links {
        display: grid;
        padding: 8px;
        gap: 3px;
    }

    .uniform-account-panel-links a {
        text-decoration: none;
        border-radius: 8px;
        padding: 8px 9px;
        color: #264c66;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .uniform-account-panel-links a:hover {
        background: #eff7fc;
    }

    .uniform-account-panel-foot {
        border-top: 1px solid #d8e6f0;
        padding: 8px;
    }

    .uniform-account-logout {
        width: 100%;
        border: 1px solid #d4e0ea;
        border-radius: 8px;
        padding: 8px 9px;
        background: #ffffff;
        color: #37516a;
        font-size: 0.8rem;
        font-weight: 700;
        font-family: inherit;
        text-align: left;
        cursor: pointer;
    }

    .uniform-checkout-context {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .uniform-checkout-chip {
        border: 1px solid #d2e1ec;
        border-radius: 999px;
        background: #f6fbff;
        color: #234a66;
        padding: 5px 9px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    @media (max-width: 920px) {
        .uniform-header {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            padding: 10px 12px;
        }

        .uniform-header-main {
            width: 100%;
            flex-wrap: wrap;
        }

        .uniform-header-links-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .uniform-header-links {
            display: none;
            width: 100%;
            overflow-x: visible;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: 2px;
        }

        .uniform-header-links.is-open {
            display: flex;
        }

        .uniform-header-search-mini {
            min-width: 0;
            width: 100%;
        }

        .uniform-header-auth {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .uniform-header-auth::-webkit-scrollbar {
            display: none;
        }
    }
</style>
@endif

<header class="header-bar uniform-header{{ $headerHideOnScroll ? '' : ' is-static' }}" data-uniform-header data-hide-on-scroll="{{ $headerHideOnScroll ? '1' : '0' }}" aria-label="Member header">
    <div class="uniform-header-main">
        <div class="uniform-header-brand-wrap">
            <a class="uniform-header-brand" href="/">Workation</a>
            <p class="uniform-header-subline">{{ $headerSubline }}</p>
        </div>

        @if ($headerMode === 'checkout')
            <div class="uniform-checkout-context" aria-label="Checkout context">
                @if ($checkoutProperty !== '')
                    <span class="uniform-checkout-chip">{{ $checkoutProperty }}</span>
                @endif
                @if ($checkoutDates !== '')
                    <span class="uniform-checkout-chip">{{ $checkoutDates }}</span>
                @endif
                @if ($checkoutGuests !== '')
                    <span class="uniform-checkout-chip">{{ $checkoutGuests }}</span>
                @endif
            </div>
        @else
            <button class="uniform-header-links-toggle" type="button" data-header-links-toggle aria-expanded="false" aria-controls="{{ $headerLinksPanelId }}">Categories</button>
            <nav id="{{ $headerLinksPanelId }}" class="uniform-header-links" aria-label="Primary categories">
                @foreach ($headerCategoryLinks as $item)
                    @php
                        $itemKey = trim((string) ($item['key'] ?? ''));
                        $itemUrl = trim((string) ($item['url'] ?? '/'));
                        $itemTitle = trim((string) ($item['title'] ?? 'Category'));
                    @endphp
                    <a class="uniform-header-link{{ $headerActiveCategoryKey !== '' && $headerActiveCategoryKey === $itemKey ? ' is-active' : '' }}" href="{{ $itemUrl }}">{{ $itemTitle }}</a>
                @endforeach
                @if (!$headerHasBlogLink)
                    <a class="uniform-header-link" href="/blog">Blog</a>
                @endif
            </nav>

            @if ($headerShowSearch)
                <form class="uniform-header-search-mini header-search-mini" method="GET" action="{{ $headerSearchAction }}" aria-label="Quick destination search">
                    <input type="search" name="q" value="{{ $headerSearchValue }}" placeholder="{{ $headerSearchPlaceholder }}">
                    <button type="submit" aria-label="Search destinations">Search</button>
                </form>
            @endif
        @endif
    </div>

    <div class="uniform-header-auth customer-auth">
        @if ($customerLoggedIn)
            <div class="uniform-account-menu account-menu" data-customer-menu>
                <button class="uniform-account-toggle account-menu-toggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="{{ $headerMenuPanelId }}">Welcome, {{ $customerName }}</button>
                <div id="{{ $headerMenuPanelId }}" class="uniform-account-panel account-menu-panel" role="menu" hidden>
                    <div class="uniform-account-panel-head">
                        <p><strong>Hi, {{ $customerName }}</strong></p>
                        <p style="margin-top:3px;font-size:0.75rem;color:#5d778d;">Great to see you again.</p>
                    </div>
                    <div class="uniform-account-panel-links">
                        <a href="/customer#bookings" role="menuitem">My Bookings</a>
                        <a href="/customer" role="menuitem">Manage my account</a>
                        <a href="/customer#promos" role="menuitem">Promo codes</a>
                        <a href="/customer#favourites" role="menuitem">Favourites</a>
                    </div>
                    <div class="uniform-account-panel-foot">
                        <form method="POST" action="/portal/customer/logout" style="margin:0;">
                            @csrf
                            <button class="uniform-account-logout" type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <a class="uniform-auth-link primary" href="{{ '/portal/customer/login?continue=' . urlencode($headerContinueUrl) }}">Sign in/register</a>
        @endif
    </div>
</header>

@if ($headerNeedsSpacer)
    <div class="uniform-header-spacer" aria-hidden="true"></div>
@endif

@if ($injectUniformHeaderScripts)
<script>
    (function () {
        const headers = Array.from(document.querySelectorAll('[data-uniform-header]'));
        if (headers.length === 0) {
            return;
        }

        const revealAtTopOnly = {{ $headerRevealAtTopOnly ? 'true' : 'false' }};

        const lastScrollByHeader = new WeakMap();
        headers.forEach(function (header) {
            lastScrollByHeader.set(header, window.scrollY || 0);
        });

        function syncHeaderScrollState(header) {
            if (header.getAttribute('data-hide-on-scroll') !== '1') {
                document.body.classList.remove('is-header-hidden');
                return;
            }

            const currentY = window.scrollY || 0;
            const lastScrollY = lastScrollByHeader.get(header) || 0;
            if (revealAtTopOnly) {
                document.body.classList.toggle('is-header-hidden', currentY > 0);
                lastScrollByHeader.set(header, currentY);
                return;
            }

            const isDesktop = window.matchMedia('(min-width: 921px)').matches;
            const isScrollingDown = currentY > lastScrollY;
            const hideThreshold = Math.max(62, header.offsetHeight + 8);

            document.body.classList.toggle('is-header-hidden', isDesktop && currentY > hideThreshold && isScrollingDown);
            lastScrollByHeader.set(header, currentY);
        }

        window.addEventListener('scroll', function () {
            headers.forEach(syncHeaderScrollState);
        }, { passive: true });

        window.addEventListener('resize', function () {
            headers.forEach(syncHeaderScrollState);
        });

        headers.forEach(syncHeaderScrollState);

        headers.forEach(function (header) {
            const menuRoot = header.querySelector('[data-customer-menu]');
            const linksToggle = header.querySelector('[data-header-links-toggle]');
            const linksPanelId = linksToggle ? linksToggle.getAttribute('aria-controls') : '';
            const linksPanel = linksPanelId ? document.getElementById(linksPanelId) : null;

            if (linksToggle && linksPanel) {
                linksPanel.classList.remove('is-open');
                linksPanel.removeAttribute('hidden');

                linksToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    const willOpen = !linksPanel.classList.contains('is-open');
                    linksPanel.classList.toggle('is-open', willOpen);
                    linksToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                window.addEventListener('resize', function () {
                    if (window.matchMedia('(min-width: 921px)').matches) {
                        linksPanel.classList.remove('is-open');
                        linksToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            if (!menuRoot) {
                return;
            }

            const menuToggle = menuRoot.querySelector('.account-menu-toggle');
            const menuPanel = menuRoot.querySelector('.account-menu-panel');
            if (!menuToggle || !menuPanel) {
                return;
            }

            function setMenuOpen(isOpen) {
                menuPanel.hidden = !isOpen;
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            menuToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setMenuOpen(menuPanel.hidden);
            });

            document.addEventListener('click', function (event) {
                if (!menuRoot.contains(event.target)) {
                    setMenuOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setMenuOpen(false);
                }
            });
        });
    })();
</script>
@endif