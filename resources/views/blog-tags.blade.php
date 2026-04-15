<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog Tags | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #e9efec;
            --surface: #f8fbf9;
            --surface-soft: #eaf3f1;
            --line: #ccd8de;
            --ink: #0d1f2a;
            --muted: #5c6f7d;
            --brand: #007e7a;
            --chip-line: #8fd38a;
            --chip-text: #1a7034;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% -10%, #f8ffde 0%, rgba(248, 255, 222, 0) 33%),
                linear-gradient(180deg, #f1f5f2 0%, var(--bg) 100%);
        }

        .page {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto 28px;
        }

        .header-bar {
            min-height: 84px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 40;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            padding: 0 24px;
            background: #ffffff;
            border-bottom: 1px solid rgba(15, 23, 36, 0.06);
        }

        .page.is-header-hidden .header-bar {
            transform: translateY(calc(-100% - 2px));
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
        }

        .brand {
            margin: 0;
            text-decoration: none;
            font-size: 1.7rem;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #02193f;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand small {
            color: #5b6672;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links a {
            text-decoration: none;
            color: #0f1724;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
        }

        .nav-links a.is-active {
            color: #02193f;
            border-color: rgba(2, 25, 63, 0.14);
            background: rgba(2, 25, 63, 0.06);
        }

        .nav-links a:hover {
            border-color: rgba(2, 25, 63, 0.12);
            background: rgba(2, 25, 63, 0.04);
        }

        .hero {
            margin-top: 16px;
            border: 1px solid #c6d7de;
            border-radius: 22px;
            overflow: hidden;
            padding: 28px;
            background: linear-gradient(130deg, #edf9f7 0%, #e8f5fb 48%, #eff5ee 100%);
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.06;
            letter-spacing: -0.03em;
        }

        .hero p {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 1.04rem;
        }

        .tags-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .tag-card {
            border: 1px solid #cbd9e4;
            border-radius: 14px;
            background: #ffffff;
            padding: 12px;
            text-decoration: none;
            color: inherit;
        }

        .tag-title {
            margin: 0;
            font-size: 1.08rem;
            line-height: 1.25;
        }

        .tag-count {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .tag-chip {
            display: inline-flex;
            margin-top: 10px;
            border: 1px solid var(--chip-line);
            border-radius: 999px;
            color: var(--chip-text);
            font-size: 0.82rem;
            font-weight: 700;
            padding: 6px 11px;
            background: #f0faf3;
        }

        @media (max-width: 1020px) {
            .tags-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 740px) {
            .header-bar {
                min-height: auto;
                padding: 14px 8px;
                flex-direction: column;
                align-items: flex-start;
            }

            .brand {
                font-size: 1.82rem;
            }

            .nav-links {
                justify-content: flex-start;
            }

            .tags-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header-bar" aria-label="Blog category header">
            <a class="brand" href="/blog">
                Workation
                <small>Blog</small>
            </a>
            <nav class="nav-links" aria-label="Blog categories">
                <a href="/blog">The collection</a>
                @foreach ($blogCategories as $slug => $meta)
                    @php
                        $href = $slug === 'islands' ? '/islands' : ('/blog/category/' . $slug);
                        $navLabel = (string) ($meta['label'] ?? \Illuminate\Support\Str::headline($slug));
                    @endphp
                    <a href="{{ $href }}">{{ $navLabel }}</a>
                @endforeach
            </nav>
        </header>

        <section class="hero" aria-label="Tag directory">
            <h1>Story Compass</h1>
            <p>Jump into tag clusters curated around reefs, routes, island culture, and stay ideas.</p>
        </section>

        <section class="tags-grid" aria-label="Tags list">
            @forelse ($tagDirectory as $tag)
                <a class="tag-card" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">
                    <h2 class="tag-title">{{ $tag['label'] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) ($tag['slug'] ?? 'Tag'))) }}</h2>
                    <p class="tag-count">{{ (int) ($tag['count'] ?? 0) }} stories</p>
                    <span class="tag-chip">View cluster</span>
                </a>
            @empty
                <p>No tags are available yet.</p>
            @endforelse
        </section>

        @include('partials.global-site-footer')
    </main>
<script>
    (function () {
        const page = document.querySelector('.page');
        const header = document.querySelector('.header-bar');
        if (!page || !header) return;

        let lastY = window.scrollY || 0;
        function syncHeaderState() {
            const currentY = window.scrollY || 0;
            const threshold = Math.max(56, header.offsetHeight - 4);
            const hide = currentY > threshold && currentY > lastY;
            page.classList.toggle('is-header-hidden', hide);
            lastY = currentY;
        }

        window.addEventListener('scroll', syncHeaderState, { passive: true });
        window.addEventListener('resize', syncHeaderState);
        syncHeaderState();
    })();
</script>

</body>
</html>