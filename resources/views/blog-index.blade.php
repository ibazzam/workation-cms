<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation Blog</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #eceeef;
            --ink: #111f2a;
            --muted: #4e6679;
            --line: #d8dfe4;
            --surface: #ffffff;
            --brand: #43be66;
            --chip-line: #5ad176;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
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

        .nav-links a.is-active {
            color: var(--brand);
        }

        .nav-links a:hover {
            background: #ebf7ef;
            color: #27964a;
        }

        .hero-grid {
            margin-top: 0;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            min-height: 680px;
        }

        .hero-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, #c3d6e4 0%, #a9c4d7 100%);
        }

        .hero-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(7, 18, 24, 0.2) 16%, rgba(6, 14, 18, 0.57) 100%);
        }

        .hero-card h1,
        .hero-card h2 {
            margin: 0;
            position: absolute;
            left: 28px;
            right: 28px;
            bottom: 110px;
            z-index: 2;
            color: #ffffff;
            font-size: clamp(1.6rem, 2.85vw, 3rem);
            line-height: 1.15;
            letter-spacing: -0.02em;
            text-align: center;
            text-shadow: 0 6px 26px rgba(0, 0, 0, 0.33);
        }

        .hero-card a {
            text-decoration: none;
            color: inherit;
        }

        .hero-empty {
            display: grid;
            place-items: center;
            text-align: center;
            padding: 20px;
            color: #33566e;
            border: 1px dashed #b5c8d8;
            background: #f6fbff;
            min-height: 380px;
        }

        .list-section {
            margin: 66px auto 0;
            max-width: 980px;
        }

        .list-title {
            margin: 0 0 24px;
            font-size: clamp(2rem, 3.4vw, 3.25rem);
            line-height: 1.08;
            letter-spacing: -0.02em;
        }

        .list-title small {
            display: block;
            margin-top: 8px;
            font-size: 1rem;
            color: var(--muted);
            font-weight: 500;
            letter-spacing: 0;
        }

        .story-list {
            display: grid;
            gap: 40px;
        }

        .story-item {
            display: grid;
            grid-template-columns: 126px minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .story-thumb {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            background: linear-gradient(145deg, #cee0eb 0%, #c0d6e4 100%);
        }

        .story-thumb img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 7px;
            color: #3f5b71;
            font-size: 0.84rem;
        }

        .meta-row strong {
            text-transform: uppercase;
            font-size: 0.83rem;
            letter-spacing: 0.04em;
            color: #1c3140;
        }

        .story-item h3 {
            margin: 0;
            font-size: clamp(1.15rem, 2.1vw, 2rem);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .story-item h3 a {
            text-decoration: none;
            color: inherit;
        }

        .tags {
            margin-top: 11px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-chip {
            text-decoration: none;
            border: 1px solid var(--chip-line);
            border-radius: 12px;
            color: #44bd65;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 6px 14px;
            line-height: 1.1;
            background: rgba(255, 255, 255, 0.55);
        }

        .tag-chip:hover {
            background: #e8f8ed;
            color: #2e9f4d;
        }

        .toolbar {
            margin: 16px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar a {
            text-decoration: none;
            border: 1px solid #c7d7e3;
            border-radius: 11px;
            color: #1e3850;
            font-size: 0.84rem;
            font-weight: 700;
            padding: 8px 11px;
            background: #f7fbff;
        }

        .toolbar a:hover {
            border-color: #95bad1;
            background: #edf6fd;
        }

        @media (max-width: 1160px) {
            .hero-grid {
                min-height: 560px;
            }

            .hero-card h1,
            .hero-card h2 {
                bottom: 76px;
            }
        }

        @media (max-width: 920px) {
            .hero-grid {
                grid-template-columns: 1fr;
                min-height: 0;
            }

            .hero-card {
                min-height: 380px;
            }

            .hero-card h1,
            .hero-card h2 {
                bottom: 34px;
                left: 16px;
                right: 16px;
                text-align: left;
            }

            .story-item {
                grid-template-columns: 98px minmax(0, 1fr);
                gap: 10px;
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

            .list-section {
                margin-top: 34px;
            }

            .story-list {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    @php
        $allPosts = $posts->values();
        $activeCategorySlug = $activeCategory ?: 'things-to-do';
        $activeCategoryLabel = (string) ($blogCategories[$activeCategorySlug]['label'] ?? 'Things to Do');
        $contextLabel = $activeTag ? ('Tag: ' . ($activeTagLabel ?: \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) $activeTag)))) : $activeCategoryLabel;
        $heroPosts = $allPosts->take(3)->values();
        $listPosts = $allPosts->skip(3)->values();

        if ($listPosts->isEmpty()) {
            $listPosts = $allPosts->values();
        }

        $postImageUrl = function ($post): string {
            $coverPath = trim((string) ($post->cover_image_path ?? ''));
            if ($coverPath === '') {
                return '';
            }

            return (string) \Illuminate\Support\Facades\Storage::disk('public')->url($coverPath);
        };

        $postDate = function ($post): string {
            return optional($post->published_at)->format('M d, Y - l')
                ?? optional($post->created_at)->format('M d, Y - l')
                ?? 'Upcoming';
        };

        $postCategoryLabel = function ($post) use ($activeCategoryLabel): string {
            return (string) ($post->blog_category_label ?? $activeCategoryLabel);
        };
    @endphp

    <main class="page">
        <header class="header-bar" aria-label="Blog category header">
            <a class="brand" href="/">
                Workation
                <small>Blog</small>
            </a>
            <nav class="nav-links" aria-label="Blog categories">
                @foreach ($blogCategories as $slug => $meta)
                    @php
                        $isActiveCategory = !$activeTag && ($activeCategorySlug === $slug);
                        $categoryHref = $slug === 'things-to-do' ? '/blog' : '/blog/category/' . $slug;
                    @endphp
                    <a class="{{ $isActiveCategory ? 'is-active' : '' }}" href="{{ $categoryHref }}">{{ $meta['label'] ?? \Illuminate\Support\Str::headline($slug) }}</a>
                @endforeach
                <a href="/islands">Islands</a>
            </nav>
        </header>

        @if ($heroPosts->isNotEmpty())
            <section class="hero-grid" aria-label="Top stories in this category">
                @foreach ($heroPosts as $post)
                    @php
                        $coverUrl = $postImageUrl($post);
                    @endphp
                    <article class="hero-card">
                        @if ($coverUrl !== '')
                            <img src="{{ $coverUrl }}" alt="{{ $post->title }} hero image" loading="eager">
                        @endif
                        <h{{ $loop->first ? '1' : '2' }}><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h{{ $loop->first ? '1' : '2' }}>
                    </article>
                @endforeach
            </section>
        @else
            <div class="hero-empty">No stories are currently available for this filter.</div>
        @endif

        <section class="list-section" aria-label="Story listing">
            <h2 class="list-title">
                {{ $activeTag ? 'Stories for ' . ($activeTagLabel ?: \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) $activeTag))) : 'More Stories About ' . $contextLabel }}
                <small>{{ $activeTag ? 'Browse stories tagged in this topic.' : 'Curated picks across ' . $contextLabel . '.' }}</small>
            </h2>

            <div class="toolbar">
                <a href="/blog">All stories</a>
                <a href="/blog/tags">Explore all tags</a>
                @if ($activeTag)
                    <a href="{{ '/blog/tag/' . $activeTag }}">Current tag: {{ $activeTagLabel ?: \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) $activeTag)) }}</a>
                @endif
            </div>

            <div class="story-list">
                @forelse ($listPosts as $post)
                    @php
                        $thumbUrl = $postImageUrl($post);
                        $tags = collect($post->blog_tags ?? [])->take(5)->values();
                    @endphp
                    <article class="story-item">
                        <a class="story-thumb" href="{{ '/blog/' . $post->slug }}" aria-label="{{ $post->title }}">
                            @if ($thumbUrl !== '')
                                <img src="{{ $thumbUrl }}" alt="{{ $post->title }} thumbnail" loading="lazy">
                            @endif
                        </a>
                        <div>
                            <div class="meta-row">
                                <strong>{{ $postCategoryLabel($post) }}</strong>
                                <span>{{ $postDate($post) }}</span>
                            </div>
                            <h3><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h3>
                            @if ($tags->isNotEmpty())
                                <div class="tags" aria-label="Blog tags">
                                    @foreach ($tags as $tag)
                                        <a class="tag-chip" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">{{ $tag['label'] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) ($tag['slug'] ?? 'tag'))) }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="hero-empty">No matching stories found for this view yet.</div>
                @endforelse
            </div>
        </section>

        @include('partials.global-site-footer')
    </main>
</body>
</html>
