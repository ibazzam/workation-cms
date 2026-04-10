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
            --bg: #eceeef;
            --surface: #ffffff;
            --line: #d6dee5;
            --ink: #122130;
            --muted: #536a7c;
            --chip-line: #59d074;
            --chip-text: #3eb860;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: var(--bg);
            color: var(--ink);
        }

        .page {
            width: min(1180px, calc(100% - 30px));
            margin: 0 auto 30px;
        }

        .header-bar {
            min-height: 98px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 25;
            padding: 0 8px;
        }

        .brand {
            margin: 0;
            text-decoration: none;
            font-size: 2.15rem;
            line-height: 1;
            letter-spacing: -0.045em;
            color: #52be72;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .brand small {
            color: #1b2832;
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links a {
            text-decoration: none;
            color: #152632;
            font-size: 1.02rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.01em;
            padding: 8px 10px;
            border-radius: 8px;
        }

        .nav-links a:hover {
            background: #ebf7ef;
            color: #27964a;
        }

        .hero {
            margin-top: 16px;
            border: 1px solid #c8d8e4;
            border-radius: 22px;
            overflow: hidden;
            padding: 28px;
            background: linear-gradient(135deg, #ecf7f0 0%, #dff2e8 45%, #d6ecdf 100%);
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
            background: rgba(255, 255, 255, 0.72);
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
            <a class="brand" href="/">
                Workation
                <small>Blog</small>
            </a>
            <nav class="nav-links" aria-label="Blog categories">
                @foreach ($blogCategories as $slug => $meta)
                    @php
                        $href = $slug === 'things-to-do' ? '/blog' : '/blog/category/' . $slug;
                    @endphp
                    <a href="{{ $href }}">{{ $meta['label'] ?? \Illuminate\Support\Str::headline($slug) }}</a>
                @endforeach
            </nav>
        </header>

        <section class="hero" aria-label="Tag directory">
            <h1>Explore Blog Tags</h1>
            <p>Pick a topic tag to open its dedicated page with hero stories and matching articles.</p>
        </section>

        <section class="tags-grid" aria-label="Tags list">
            @forelse ($tagDirectory as $tag)
                <a class="tag-card" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">
                    <h2 class="tag-title">{{ $tag['label'] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) ($tag['slug'] ?? 'Tag'))) }}</h2>
                    <p class="tag-count">{{ (int) ($tag['count'] ?? 0) }} stories</p>
                    <span class="tag-chip">Open tag page</span>
                </a>
            @empty
                <p>No tags are available yet.</p>
            @endforelse
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
