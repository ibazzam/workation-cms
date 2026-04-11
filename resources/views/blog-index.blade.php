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
            --bg: #e9efec;
            --ink: #0d1f2a;
            --muted: #5c6f7d;
            --line: #ccd8de;
            --surface: #f8fbf9;
            --surface-strong: #ffffff;
            --brand: #007e7a;
            --brand-ink: #0a4f5f;
            --accent: #b4ff00;
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
            width: min(1240px, calc(100% - 26px));
            margin: 0 auto 34px;
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
            padding-left: max(14px, calc((100vw - 1240px) / 2 + 8px));
            padding-right: max(14px, calc((100vw - 1240px) / 2 + 8px));
            background: transparent;
            border-bottom: 0;
            backdrop-filter: none;
        }

        .brand {
            margin: 0;
            text-decoration: none;
            font-size: 2rem;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #ffffff;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 5px 18px rgba(0, 0, 0, 0.35);
        }

        .brand small {
            color: #f3fbf9;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links a {
            text-decoration: none;
            color: #f9ffff;
            font-size: 0.84rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid transparent;
            text-shadow: 0 4px 16px rgba(0, 0, 0, 0.33);
        }

        .nav-links a.is-active {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.65);
            background: rgba(0, 19, 28, 0.28);
        }

        .nav-links a:hover {
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(7, 24, 36, 0.34);
        }

        .hero-stage {
            margin-top: -84px;
            position: relative;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            border-radius: 0;
            overflow: hidden;
            min-height: 720px;
            background: linear-gradient(150deg, #abc7d8 0%, #8fb6cb 100%);
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
            background: linear-gradient(180deg, rgba(9, 20, 29, 0.15) 20%, rgba(7, 16, 23, 0.7) 100%);
        }

        .hero-copy {
            position: absolute;
            z-index: 2;
            left: clamp(20px, 5vw, 64px);
            right: clamp(20px, 5vw, 64px);
            bottom: clamp(22px, 5vw, 70px);
            color: #ffffff;
            text-align: center;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(1.8rem, 4.2vw, 4rem);
            line-height: 1.06;
            letter-spacing: -0.03em;
            text-shadow: 0 8px 28px rgba(0, 0, 0, 0.42);
        }

        .hero-copy a {
            text-decoration: none;
            color: inherit;
        }

        .teaser-row {
            margin: -38px auto 0;
            width: min(1080px, calc(100% - 24px));
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            position: relative;
            z-index: 5;
        }

        .teaser-card {
            padding: 18px;
            border-radius: 16px;
            background: var(--surface-strong);
            border: 1px solid #dbe8ed;
            box-shadow: 0 14px 28px rgba(20, 41, 55, 0.08);
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 0.84rem;
            color: #4e6978;
            margin-bottom: 10px;
        }

        .meta-row strong {
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #1d3443;
            font-size: 0.8rem;
        }

        .teaser-card h2 {
            margin: 0;
            font-size: clamp(1.15rem, 1.8vw, 1.95rem);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .teaser-card h2 a {
            text-decoration: none;
            color: inherit;
        }

        .campaign-strip {
            margin: 56px auto 0;
            border-radius: 16px;
            padding: 24px clamp(16px, 3vw, 44px);
            background: linear-gradient(90deg, var(--accent) 0%, #d6ff61 65%, #c7ff2f 100%);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: center;
            color: #053843;
        }

        .campaign-strip h3 {
            margin: 0;
            font-size: clamp(1.8rem, 3.2vw, 3rem);
            line-height: 1.04;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .campaign-strip p {
            margin: 8px 0 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #165062;
        }

        .campaign-cta {
            text-decoration: none;
            border-radius: 999px;
            background: #072f3d;
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 12px 20px;
            white-space: nowrap;
        }

        .work-event-bridge {
            margin-top: 20px;
            padding: clamp(16px, 2.8vw, 28px);
            border-radius: 16px;
            border: 1px solid #c8d8de;
            background: linear-gradient(135deg, #eef8f6 0%, #edf4fb 55%, #f7faef 100%);
        }

        .work-event-bridge h3 {
            margin: 0;
            font-size: clamp(1.6rem, 2.9vw, 2.7rem);
            line-height: 1.08;
            letter-spacing: -0.02em;
        }

        .work-event-bridge p {
            margin: 10px 0 0;
            color: #39586a;
            max-width: 920px;
            font-size: 0.98rem;
        }

        .bridge-links {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .bridge-link {
            text-decoration: none;
            color: inherit;
            border: 1px solid #ccdae2;
            border-radius: 14px;
            background: #ffffff;
            padding: 14px;
            display: block;
        }

        .bridge-link strong {
            display: block;
            font-size: 1.04rem;
            color: #113245;
            letter-spacing: -0.01em;
        }

        .bridge-link span {
            display: block;
            margin-top: 6px;
            color: #4f6b7a;
            font-size: 0.9rem;
        }

        .bridge-link:hover {
            border-color: #94b2c1;
            background: #f4fafe;
        }

        .section-head {
            margin: 74px 0 8px;
            text-align: center;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(2.3rem, 4vw, 4rem);
            line-height: 1.04;
            letter-spacing: -0.03em;
        }

        .section-head p {
            margin: 10px auto 0;
            max-width: 700px;
            color: #455e6f;
            font-size: 1rem;
        }

        .feature-split {
            margin-top: 26px;
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
            gap: 22px;
            align-items: stretch;
        }

        .feature-photo {
            border-radius: 22px;
            overflow: hidden;
            min-height: 640px;
            background: linear-gradient(145deg, #cee0eb 0%, #b8d3e5 100%);
        }

        .feature-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .feature-list {
            display: grid;
            gap: 16px;
        }

        .feature-item {
            padding: 8px 0;
            border-bottom: 1px solid #cfdde5;
        }

        .feature-index {
            color: var(--brand-ink);
            font-weight: 800;
            font-size: 0.95rem;
            margin-right: 8px;
        }

        .feature-item h3 {
            margin: 8px 0 0;
            font-size: clamp(1.18rem, 2.3vw, 2.1rem);
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
            gap: 16px;
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
            .teaser-row {
                grid-template-columns: 1fr;
                margin-top: 16px;
                width: 100%;
            }

            .campaign-strip {
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
            if ($postId > 0) {
                return '/media/blog/' . $postId . '/cover';
            }

            $candidate = trim((string) ($post->cover_image_url ?? ''));
            if ($candidate === '') {
                $candidate = trim((string) ($post->cover_image_path ?? ''));
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
                    </article>
                @endforeach
            </section>

            <section class="campaign-strip" aria-label="Editorial campaign">
                <div>
                    <h3>Find island stories worth your next detour</h3>
                    <p>Original Maldives notes by Workation editors, from ferry routes to quiet reef corners.</p>
                </div>
                <a class="campaign-cta" href="/blog/tags">Open Story Map</a>
            </section>

            <section class="work-event-bridge" aria-label="Workspace and conference in Maldives">
                <h3>Why host team events in Maldives islands and resorts?</h3>
                <p>Use this blog as the narrative layer for event planning: combine work-friendly island spaces, resort logistics, and meeting-ready venues so teams can plan conferences, retreats, and workshops with location context instead of just listings.</p>
                <div class="bridge-links">
                    <a class="bridge-link" href="/catalog/remote_workspace">
                        <strong>Explore Remote Workspaces</strong>
                        <span>Co-working setups, private work zones, and productivity-focused island stays.</span>
                    </a>
                    <a class="bridge-link" href="/catalog/conference_room">
                        <strong>Explore Conference & Event Venues</strong>
                        <span>Meeting rooms, training halls, and resort event spaces ready for business gatherings.</span>
                    </a>
                </div>
            </section>

            <section class="section-head" aria-label="Feature heading">
                <h2>Featured Dispatch</h2>
                <p>Our editorial board picks the stories that best capture local pace, sea color, and island character.</p>
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
                <h2>Play, Pause, Repeat</h2>
                <p>A smaller set of visual-first stories designed for fast browsing before your next booking decision.</p>
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

            <section class="archive-grid" aria-label="Archive story cards">
                @foreach ($archivePosts as $post)
                    @php
                        $archiveImage = $postImageUrl($post);
                    @endphp
                    <a class="archive-card" href="{{ '/blog/' . $post->slug }}">
                        <div class="archive-media">
                            @if ($archiveImage !== '')
                                <img src="{{ $archiveImage }}" alt="{{ $post->title }} archive image" loading="lazy">
                            @endif
                        </div>
                        <div class="meta-row" style="margin-top: 10px; margin-bottom: 0;">
                            <strong>{{ $postCategoryLabel($post) }}</strong>
                            <span>{{ $postDate($post) }}</span>
                        </div>
                        <h5>{{ $postTitleShort($post) }}</h5>
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
        @else
            <div class="hero-empty">No stories are currently available for this filter.</div>
        @endif

        @include('partials.global-site-footer')
    </main>
</body>
</html>