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
            --bg: #ffffff;
            --ink: #0f1724;
            --muted: #5b6672;
            --line: #d7dee5;
            --surface: #fdfdfd;
            --surface-strong: #ffffff;
            --brand: #02193f;
            --brand-ink: #07192f;
            --accent: #1e63ff;
            --highlight: #d7ff20;
            --panel: #f7f9fb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: #ffffff;
        }

        .page {
            width: min(1240px, calc(100% - 32px));
            margin: 0 auto 42px;
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
            color: var(--brand);
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand small {
            color: var(--muted);
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
            color: var(--ink);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
        }

        .nav-links a.is-active {
            color: var(--brand);
            border-color: rgba(2, 25, 63, 0.14);
            background: rgba(2, 25, 63, 0.06);
        }

        .nav-links a:hover {
            border-color: rgba(2, 25, 63, 0.12);
            background: rgba(2, 25, 63, 0.04);
        }

        .hero-stage {
            position: relative;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            border-radius: 0;
            overflow: hidden;
            min-height: 680px;
            background: #f3f6f8;
        }

        .hero-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            position: absolute;
            inset: 0;
        }

        .hero-stage::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 36, 0.12) 0%, rgba(15, 23, 36, 0.55) 100%);
        }

        .hero-copy {
            position: absolute;
            z-index: 2;
            left: 24px;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            text-align: center;
            max-width: 960px;
            margin: 0 auto;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.8rem, 4.3vw, 4.8rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
            text-shadow: 0 22px 45px rgba(15, 23, 36, 0.26);
        }

        .hero-copy a {
            text-decoration: none;
            color: inherit;
        }

        .teaser-row {
            margin: -48px auto 0;
            width: min(1180px, calc(100% - 32px));
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            position: relative;
            z-index: 5;
        }

        .teaser-card {
            padding: 24px;
            border-radius: 24px;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 36, 0.08);
            box-shadow: 0 20px 55px rgba(15, 23, 36, 0.07);
        }

        .teaser-card h2 {
            margin: 0;
            font-size: clamp(1.2rem, 1.8vw, 1.9rem);
            line-height: 1.22;
            letter-spacing: -0.02em;
            margin-top: 12px;
        }

        .teaser-card .read-more {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
            text-decoration: none;
            font-weight: 700;
            color: var(--brand);
            font-size: 0.92rem;
        }

        .teaser-card .read-more::after {
            content: '›';
            display: inline-block;
            transform: translateX(1px);
        }

        .category-hero-grid {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .category-hero-card {
            position: relative;
            border-radius: 26px;
            overflow: hidden;
            min-height: 420px;
            background: #09192b;
        }

        .category-hero-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .category-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(4, 14, 26, 0.12) 0%, rgba(4, 14, 26, 0.82) 100%);
            display: grid;
            align-items: end;
            padding: 28px;
        }

        .category-hero-overlay h2 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(1.75rem, 2.6vw, 2.8rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .tag-header {
            margin: 42px auto 0;
            width: min(980px, calc(100% - 32px));
            text-align: center;
        }

        .tag-header-box {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 130px;
            padding: 0 28px;
            border-radius: 30px;
            background: #eef3fb;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 36, 0.08);
        }

        .tag-header-box h1 {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 3.1rem);
            line-height: 1.05;
            color: var(--ink);
        }

        .category-list-heading {
            margin-top: 58px;
            font-size: clamp(2.1rem, 3.8vw, 3.2rem);
            line-height: 1.02;
        }

        .category-list {
            margin-top: 28px;
            display: grid;
            gap: 24px;
        }

        .category-list-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 280px);
            gap: 22px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(15, 23, 36, 0.08);
        }

        .category-list-preview {
            width: 100%;
            height: 100%;
            min-height: 150px;
            border-radius: 20px;
            overflow: hidden;
            background: #f4f8fb;
        }

        .category-list-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .category-list-content h3 {
            margin: 0;
            font-size: clamp(1.5rem, 2vw, 1.95rem);
            line-height: 1.15;
        }

        .category-list-meta {
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.94rem;
        }

        .category-tags {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .category-tag {
            display: inline-flex;
            padding: 8px 14px;
            border: 1px solid var(--brand);
            border-radius: 999px;
            color: var(--brand);
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            background: rgba(30, 99, 255, 0.08);
        }

        .ad-banner {
            margin: 56px auto 0;
            border-radius: 24px;
            background: linear-gradient(90deg, #d9ff26 0%, #d7f44f 100%);
            padding: 28px 26px;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 24px;
        }

        .ad-banner h3 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.8rem);
            letter-spacing: -0.04em;
            color: #071a34;
        }

        .ad-banner .ad-copy {
            color: #071a34;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .ad-badges {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .ad-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #ffffff;
            border-radius: 999px;
            color: #071a34;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(2, 25, 63, 0.08);
        }

        .section-head {
            margin: 74px auto 18px;
            text-align: center;
            max-width: 760px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(2.4rem, 4vw, 4.4rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
        }

        .section-head p {
            margin: 14px auto 0;
            max-width: 640px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.8;
        }

        .feature-split {
            margin-top: 20px;
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
            gap: 28px;
            align-items: start;
        }

        .feature-photo {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            min-height: 520px;
            background: var(--panel);
        }

        .feature-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .feature-list {
            display: grid;
            gap: 18px;
        }

        .feature-item {
            padding: 18px 0;
            border-bottom: 1px solid rgba(15, 23, 36, 0.08);
        }

        .feature-index {
            color: var(--brand);
            font-weight: 800;
            font-size: 0.95rem;
            margin-right: 10px;
        }

        .feature-item h3 {
            margin: 10px 0 0;
            font-size: clamp(1.3rem, 2vw, 2rem);
            line-height: 1.18;
            letter-spacing: -0.02em;
        }

        .feature-item h3 a {
            text-decoration: none;
            color: inherit;
        }

        .drift-grid {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .drift-card {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .drift-media {
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 4 / 3;
            background: linear-gradient(150deg, #c5d9e8 0%, #afcbe0 100%);
        }

        .drift-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .drift-card h4 {
            margin: 12px 0 0;
            font-size: clamp(1.05rem, 1.8vw, 1.55rem);
            line-height: 1.24;
            letter-spacing: -0.01em;
        }

        .archive-grid {
            margin-top: 42px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .archive-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .archive-media {
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 1.5 / 1;
            background: linear-gradient(160deg, #c6dae9 0%, #aec9dc 100%);
        }

        .archive-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .archive-card h5 {
            margin: 10px 0 0;
            font-size: clamp(1rem, 1.5vw, 1.45rem);
            line-height: 1.25;
            letter-spacing: -0.01em;
        }

        .island-loop {
            margin: 78px 0 26px;
            text-align: center;
        }

        .island-loop h2 {
            margin: 0;
            font-size: clamp(2rem, 3.7vw, 3.6rem);
            letter-spacing: -0.02em;
        }

        .loop-grid {
            margin: 26px auto 0;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 20px;
            width: min(1140px, calc(100% - 30px));
        }

        .loop-item {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .loop-media {
            width: min(160px, 100%);
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            border-radius: 999px;
            overflow: hidden;
            background: linear-gradient(160deg, #bfd6e8 0%, #a9c8dd 100%);
            border: 4px solid rgba(250, 252, 250, 0.85);
            box-shadow: 0 8px 26px rgba(14, 44, 58, 0.12);
        }

        .loop-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .loop-item span {
            display: block;
            margin-top: 12px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #203949;
        }

        .loop-cta {
            margin-top: 28px;
        }

        .loop-cta a {
            display: inline-block;
            text-decoration: none;
            border-radius: 999px;
            background: #1f51ff;
            color: #ffffff;
            font-weight: 700;
            padding: 11px 24px;
        }

        .hero-empty {
            display: grid;
            place-items: center;
            text-align: center;
            border-radius: 20px;
            margin-top: 14px;
            padding: 40px 20px;
            color: #33566e;
            border: 1px dashed #b5c8d8;
            background: #f6fbff;
            min-height: 280px;
        }

        @media (max-width: 1180px) {
            .archive-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .teaser-row,
            .category-hero-grid {
                grid-template-columns: 1fr;
                margin-top: 16px;
                width: 100%;
            }

            .category-list-item {
                grid-template-columns: 1fr;
            }

            .ad-banner {
                grid-template-columns: 1fr;
            }

            .bridge-links {
                grid-template-columns: 1fr;
            }

            .feature-split {
                grid-template-columns: 1fr;
            }

            .feature-photo {
                min-height: 390px;
            }

            .drift-grid {
                grid-template-columns: 1fr;
            }

            .archive-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .loop-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 740px) {
            .header-bar {
                min-height: auto;
                padding: 14px 4px;
                flex-direction: column;
                align-items: flex-start;
            }

            .brand {
                font-size: 1.84rem;
            }

            .hero-stage {
                margin-top: 0;
                min-height: 430px;
            }

            .hero-copy {
                text-align: left;
            }

            .section-head {
                text-align: left;
            }

            .section-head p {
                margin-left: 0;
            }

            .archive-grid,
            .loop-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    @php
        $allPosts = $posts->values();
        $featuredPosts = collect($featuredPosts ?? [])->values();
        $categoryStoryPosts = collect($categoryStoryPosts ?? [])->values();

        $postImageUrl = function ($post): string {
            $postId = (int) ($post->id ?? 0);
            $candidate = trim((string) ($post->cover_image_url ?? ''));
            if ($candidate === '') {
                $candidate = trim((string) ($post->cover_image_path ?? ''));
            }

            if ($postId > 0 && $candidate !== '') {
                return '/media/blog/' . $postId . '/cover';
            }

            if ($candidate === '') {
                return '';
            }

            return blogResolveCoverImageUrl($candidate);
        };

        $activeCategorySlug = $activeCategory ?: 'all';
        $activeCategoryLabel = $activeCategorySlug === 'all'
            ? 'The collection'
            : (string) ($blogCategories[$activeCategorySlug]['label'] ?? 'Stories');
        $contextLabel = $activeTag ? ('Tag: ' . ($activeTagLabel ?: \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) $activeTag)))) : $activeCategoryLabel;
        $heroLead = $allPosts->first();
        $heroBackgroundPost = $allPosts
            ->filter(fn ($post) => $postImageUrl($post) !== '')
            ->shuffle()
            ->first();
        $useCategoryHero = !$activeTag && $activeCategorySlug !== 'all';
        $useTagHero = $activeTag !== null;
        $categoryHeroPosts = $useCategoryHero ? $allPosts->take(3)->values() : collect([]);
        $categoryListPosts = $useCategoryHero ? $allPosts->slice(3)->values() : collect([]);
        $tagHeroPosts = $useTagHero ? $allPosts->take(3)->values() : collect([]);
        $tagListPosts = $useTagHero ? $allPosts->slice(3)->values() : collect([]);
        $teaserPosts = $allPosts->slice(1, 3)->values();
        $featureLead = $featuredPosts->first() ?? $heroLead;
        $featureList = $featuredPosts->slice(1, 4)->values();
        $driftPosts = $categoryStoryPosts->take(3)->values();
        $archivePosts = $allPosts->slice(12, 8)->values();
        $loopPosts = $allPosts->filter(function ($post) {
            $isIslandsCategory = (string) ($post->blog_category_slug ?? '') === 'islands';
            $hasImage = trim((string) ($post->cover_image_url ?? $post->cover_image_path ?? '')) !== '';

            return $isIslandsCategory && $hasImage;
        })->take(10)->values();

        if ($teaserPosts->isEmpty()) {
            $teaserPosts = $allPosts->take(3)->values();
        }

        if ($featureList->isEmpty()) {
            $featureList = $allPosts
                ->filter(function ($post) use ($featureLead) {
                    return !$featureLead || (int) ($post->id ?? 0) !== (int) ($featureLead->id ?? 0);
                })
                ->take(4)
                ->values();
        }

        if ($driftPosts->isEmpty()) {
            $driftPosts = $allPosts
                ->groupBy(function ($post) {
                    return (string) ($post->blog_category_slug ?? '');
                })
                ->map(function ($group) {
                    return $group->first();
                })
                ->values()
                ->take(3);

            if ($driftPosts->isEmpty()) {
                $driftPosts = $allPosts->skip(1)->take(3)->values();
            }
        }

        if ($archivePosts->isEmpty()) {
            $archivePosts = $allPosts->skip(2)->take(8)->values();
        }

        $postDate = function ($post): string {
            return optional($post->published_at)->format('M d, Y - l')
                ?? optional($post->created_at)->format('M d, Y - l')
                ?? 'Upcoming';
        };

        $postCategoryLabel = function ($post) use ($activeCategoryLabel): string {
            return (string) ($post->blog_category_label ?? $activeCategoryLabel);
        };

        $postTitleShort = function ($post): string {
            return \Illuminate\Support\Str::limit((string) ($post->title ?? ''), 62);
        };
    @endphp

    <main class="page">
        <header class="header-bar" aria-label="Blog category header">
            <a class="brand" href="/blog">
                Workation
                <small>Blog</small>
            </a>
            <nav class="nav-links" aria-label="Blog categories">
                @php
                    $isAllActive = !$activeTag && $activeCategorySlug === 'all';
                @endphp
                <a class="{{ $isAllActive ? 'is-active' : '' }}" href="/blog">The collection</a>
                @foreach ($blogCategories as $slug => $meta)
                    @php
                        $isActiveCategory = !$activeTag && ($activeCategorySlug === $slug);
                        $categoryHref = $slug === 'islands' ? '/islands' : ('/blog/category/' . $slug);
                        $navLabel = (string) ($meta['label'] ?? \Illuminate\Support\Str::headline($slug));
                    @endphp
                    <a class="{{ $isActiveCategory ? 'is-active' : '' }}" href="{{ $categoryHref }}">{{ $navLabel }}</a>
                @endforeach
            </nav>
        </header>

        @if ($heroLead)
            @php
                $heroImage = $postImageUrl($heroBackgroundPost ?? $heroLead);
            @endphp
            @if ($useTagHero && $tagHeroPosts->isNotEmpty())
                <section class="tag-header" aria-label="Tag hero label">
                    <div class="tag-header-box">
                        <h1>{{ $activeTagLabel }}</h1>
                    </div>
                </section>

                <section class="category-hero-grid" aria-label="Tag hero split cards">
                    @foreach ($tagHeroPosts as $post)
                        @php $heroImageUrl = $postImageUrl($post); @endphp
                        <a class="category-hero-card" href="{{ '/blog/' . $post->slug }}">
                            @if ($heroImageUrl !== '')
                                <img src="{{ $heroImageUrl }}" alt="{{ $post->title }} hero image" loading="eager">
                            @endif
                            <div class="category-hero-overlay">
                                <h2>{{ $post->title }}</h2>
                            </div>
                        </a>
                    @endforeach
                </section>

                <section class="section-head" aria-label="Tag stories heading">
                    <h2 class="category-list-heading">More Stories About {{ $activeTagLabel }}</h2>
                </section>

                <section class="category-list" aria-label="Tag articles list">
                    @foreach ($tagListPosts as $post)
                        @php
                            $postTags = collect($post->blog_tags ?? [])->take(5)->values();
                            $previewImage = $postImageUrl($post);
                        @endphp
                        <article class="category-list-item">
                            <a class="category-list-preview" href="{{ '/blog/' . $post->slug }}">
                                @if ($previewImage !== '')
                                    <img src="{{ $previewImage }}" alt="{{ $post->title }} thumbnail" loading="lazy">
                                @endif
                            </a>
                            <div class="category-list-content">
                                <p class="category-list-meta">{{ $postDate($post) }}</p>
                                <h3><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h3>
                                @if ($postTags->isNotEmpty())
                                    <div class="category-tags">
                                        @foreach ($postTags as $tag)
                                            <a class="category-tag" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">{{ $tag['label'] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) ($tag['slug'] ?? ''))) }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            @elseif ($useCategoryHero && $categoryHeroPosts->isNotEmpty())
                <section class="category-hero-grid" aria-label="Category hero split cards">
                    @foreach ($categoryHeroPosts as $post)
                        @php $heroImageUrl = $postImageUrl($post); @endphp
                        <a class="category-hero-card" href="{{ '/blog/' . $post->slug }}">
                            @if ($heroImageUrl !== '')
                                <img src="{{ $heroImageUrl }}" alt="{{ $post->title }} hero image" loading="eager">
                            @endif
                            <div class="category-hero-overlay">
                                <h2>{{ $post->title }}</h2>
                            </div>
                        </a>
                    @endforeach
                </section>

                <section class="section-head" aria-label="Category stories heading">
                    <h2 class="category-list-heading">More Stories About {{ $activeCategoryLabel }}</h2>
                </section>

                <section class="category-list" aria-label="Category articles list">
                    @foreach ($categoryListPosts as $post)
                        @php
                            $postTags = collect($post->blog_tags ?? [])->take(5)->values();
                            $previewImage = $postImageUrl($post);
                        @endphp
                        <article class="category-list-item">
                            <a class="category-list-preview" href="{{ '/blog/' . $post->slug }}">
                                @if ($previewImage !== '')
                                    <img src="{{ $previewImage }}" alt="{{ $post->title }} thumbnail" loading="lazy">
                                @endif
                            </a>
                            <div class="category-list-content">
                                <p class="category-list-meta">{{ $postDate($post) }}</p>
                                <h3><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h3>
                                @if ($postTags->isNotEmpty())
                                    <div class="category-tags">
                                        @foreach ($postTags as $tag)
                                            <a class="category-tag" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">{{ $tag['label'] ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', (string) ($tag['slug'] ?? ''))) }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            @else
                <section class="hero-stage" aria-label="Lead blog story">
                    @if ($heroImage !== '')
                        <img src="{{ $heroImage }}" alt="{{ $heroLead->title }} lead image" loading="eager">
                    @endif
                    <div class="hero-copy">
                        <h1><a href="{{ '/blog/' . $heroLead->slug }}">{{ $heroLead->title }}</a></h1>
                    </div>
                </section>

                <section class="teaser-row" aria-label="Quick picks">
                    @foreach ($teaserPosts as $post)
                        <article class="teaser-card">
                            <div class="meta-row">
                                <strong>{{ $postCategoryLabel($post) }}</strong>
                                <span>{{ $postDate($post) }}</span>
                            </div>
                            <h2><a href="{{ '/blog/' . $post->slug }}">{{ $postTitleShort($post) }}</a></h2>
                            <a class="read-more" href="{{ '/blog/' . $post->slug }}">Read more</a>
                        </article>
                    @endforeach
                </section>

                <section class="ad-banner" aria-label="Advertisement banner">
                <div>
                    <h3>Find sea transport in Maldives</h3>
                    <p class="ad-copy">Book faster transfers, explore routes, and keep island journeys simple with the right local app.</p>
                </div>
                <div class="ad-badges">
                    <a class="ad-badge" href="#">Google Play</a>
                    <a class="ad-badge" href="#">App Store</a>
                </div>
            </section>

            <section class="section-head" aria-label="Feature heading">
                <h2>Featured</h2>
                <p>Experience Maldives through captivating stories and breathtaking visuals.</p>
            </section>

            <section class="feature-split" aria-label="Featured split layout">
                <article class="feature-photo">
                    @if ($featureLead && $postImageUrl($featureLead) !== '')
                        <a href="{{ '/blog/' . $featureLead->slug }}"><img src="{{ $postImageUrl($featureLead) }}" alt="{{ $featureLead->title }} featured image" loading="lazy"></a>
                    @endif
                </article>
                <div class="feature-list">
                    @foreach ($featureList as $post)
                        <article class="feature-item">
                            <div class="meta-row">
                                <span class="feature-index">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <strong>{{ $postCategoryLabel($post) }}</strong>
                                <span>{{ $postDate($post) }}</span>
                            </div>
                            <h3><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h3>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section-head" aria-label="Secondary stories heading">
                <h2>Play. Reset. Reboost.</h2>
                <p>From adrenaline-pumping outdoor escapes to indulgent culinary discoveries, our featured articles bring you a taste of the world’s most exciting islands.</p>
            </section>

            <section class="drift-grid" aria-label="Three story cards">
                @foreach ($driftPosts as $post)
                    @php
                        $driftImage = $postImageUrl($post);
                    @endphp
                    <a class="drift-card" href="{{ '/blog/' . $post->slug }}">
                        <div class="drift-media">
                            @if ($driftImage !== '')
                                <img src="{{ $driftImage }}" alt="{{ $post->title }} card image" loading="lazy">
                            @endif
                        </div>
                        <div class="meta-row" style="margin-top: 10px; margin-bottom: 0;">
                            <strong>{{ $postCategoryLabel($post) }}</strong>
                            <span>{{ $postDate($post) }}</span>
                        </div>
                        <h4>{{ $postTitleShort($post) }}</h4>
                    </a>
                @endforeach
            </section>

            <section class="island-loop" aria-label="Explore islands by stories">
                <h2>Explore Islands</h2>
                <div class="loop-grid">
                    @foreach ($loopPosts as $post)
                        @php
                            $loopImage = $postImageUrl($post);
                        @endphp
                        <a class="loop-item" href="{{ '/blog/' . $post->slug }}">
                            <div class="loop-media">
                                @if ($loopImage !== '')
                                    <img src="{{ $loopImage }}" alt="{{ $post->title }} loop image" loading="lazy">
                                @endif
                            </div>
                            <span>{{ $postTitleShort($post) }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="loop-cta">
                    <a href="/islands">Open Island Atlas</a>
                </div>
            </section>
            @endif
        @else
            <div class="hero-empty">No stories are currently available for this filter.</div>
        @endif

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