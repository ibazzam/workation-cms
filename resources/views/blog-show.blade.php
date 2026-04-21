<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} | Workation Blog</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #e9efec;
            --surface: #f8fbf9;
            --surface-soft: #eaf3f1;
            --ink: #0d1f2a;
            --muted: #5c6f7d;
            --line: #ccd8de;
            --brand: #007e7a;
            --chip-line: #8fd38a;
            --tag-ink: #1a7034;
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
            margin-top: 14px;
            position: relative;
            min-height: 720px;
            overflow: hidden;
            border: 1px solid #cadbe6;
        }

        .hero img {
            width: 100%;
            height: 100%;
            position: absolute;
            inset: 0;
            object-fit: cover;
            display: block;
        }

        .hero-fallback {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #c8ddea 0%, #b4cedf 100%);
        }

        .hero-title-card {
            position: absolute;
            z-index: 2;
            left: 16%;
            top: 18px;
            width: min(760px, calc(100% - 60px));
            background: #f6f8fa;
            padding: 18px 30px 28px;
            box-shadow: 0 14px 26px rgba(15, 37, 51, 0.16);
        }

        .meta-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 9px;
            color: #4b6175;
            font-size: 0.92rem;
        }

        .meta-row strong {
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #1f3342;
            font-size: 0.89rem;
        }

        .hero-title-card h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 4.05rem);
            line-height: 1.08;
            letter-spacing: -0.02em;
        }

        .content-wrap {
            margin: 0 auto;
            max-width: 1160px;
            border-top: 1px solid #d6dde3;
            padding-top: 6px;
        }

        .tag-share-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
            margin: 0 0 12px;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-chip {
            text-decoration: none;
            border: 1px solid var(--chip-line);
            border-radius: 11px;
            color: var(--tag-ink);
            font-size: 0.86rem;
            font-weight: 700;
            padding: 7px 14px;
            line-height: 1;
            background: #f0faf3;
        }

        .tag-chip.muted {
            border-color: #aebfcd;
            color: #8a9eaf;
            background: #f8fbfe;
        }

        .tag-chip:hover {
            background: #e2f3e7;
            color: #146a31;
        }

        .share-row {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .share-button {
            width: 44px;
            height: 44px;
            border: 1px solid #aab9c6;
            border-radius: 0;
            background: #f7fafc;
            color: #0f1f2b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.36rem;
            line-height: 1;
        }

        .share-button:hover {
            background: #edf4f9;
            border-color: #8ca3b5;
        }

        .article-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 34px;
        }

        .work-event-callout {
            margin: 10px 0 24px;
            border: 1px solid #cad8e1;
            border-radius: 14px;
            padding: 16px;
            background: linear-gradient(130deg, #eef8f6 0%, #edf4fb 55%, #f7faef 100%);
        }

        .work-event-callout h2 {
            margin: 0;
            font-size: clamp(1.2rem, 2.1vw, 1.72rem);
            line-height: 1.14;
            letter-spacing: -0.02em;
        }

        .work-event-callout p {
            margin: 8px 0 0;
            color: #3b5c6f;
            font-size: 0.95rem;
        }

        .work-event-links {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .work-event-links a {
            text-decoration: none;
            border: 1px solid #9ebbc8;
            border-radius: 999px;
            padding: 8px 13px;
            color: #0f3448;
            background: #f8fcff;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .work-event-links a:hover {
            border-color: #7da2b6;
            background: #eef7fd;
        }

        .content h2,
        .content h3,
        .content h4 {
            line-height: 1.62;
            color: #172a38;
        }

        .content p {
            margin: 0 0 22px;
        }

        .content .inline-image {
            display: block;
            width: 100%;
            margin: 14px 0;
            border: 1px solid #cfdae3;
            background: #d4e3ee;
            border-radius: 0;
        }

        .content h4 { font-size: clamp(1.1rem, 1.9vw, 1.45rem); }
        .content h2,
        .content h3 {
            margin: 30px 0 12px;
            line-height: 1.22;
            letter-spacing: -0.01em;
            color: #111f2b;
        }

        .content h2 { font-size: clamp(1.65rem, 3vw, 2.35rem); }
        .content h3 { font-size: clamp(1.35rem, 2.3vw, 1.95rem); }

        .article-gallery {
            display: grid;
            gap: 8px;
            margin: 22px 0 28px;
        }
        .article-gallery.has-1 { grid-template-columns: 1fr; }
        .article-gallery.has-2 { grid-template-columns: 1fr 1fr; }
        .article-gallery.has-3 { grid-template-columns: 1fr 1fr 1fr; }
        .article-gallery figure {
            margin: 0;
        }
        .article-gallery figure img {
            display: block;
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            border: 1px solid #cfdae3;
            border-radius: 4px;
            background: #d4e3ee;
        }
        @media (max-width: 640px) {
            .article-gallery.has-2 { grid-template-columns: 1fr; }
            .article-gallery.has-3 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 400px) {
            .article-gallery.has-3 { grid-template-columns: 1fr; }
        }

        .content figure {
            margin: 24px 0 26px;
        }

        .content figure img {
            display: block;
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #cfdae3;
            background: #d4e3ee;
        }

        .content figcaption {
            margin-top: 7px;
            color: #657d8f;
            font-size: 0.9rem;
        }

        .sidebar {
            padding-top: 0;
        }

        .ad-label {
            margin: 0 0 8px;
            color: #9ca7b2;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .ad-card {
            position: sticky;
            top: 114px;
            min-height: 700px;
            border: 1px solid #c2d3de;
            background: #b8f113;
            display: grid;
            align-content: space-between;
            padding: 24px;
            color: #163048;
        }

        .ad-head {
            margin: 0;
            font-size: clamp(2rem, 4.2vw, 3.8rem);
            line-height: 0.98;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            max-width: 250px;
        }

        .ad-media {
            width: 100%;
            border-radius: 14px;
            border: 1px solid rgba(22, 48, 72, 0.25);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.65);
        }

        .ad-media img {
            width: 100%;
            display: block;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }

        .ad-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ad-brand {
            margin: 0;
            font-size: 3rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .ad-stores {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ad-cta {
            border: 1px solid #1f3344;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 0.82rem;
            font-weight: 800;
            background: rgba(255,255,255,0.7);
            color: #143147;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ad-store {
            border: 1px solid #1f3344;
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            background: rgba(255,255,255,0.55);
        }

        .related {
            margin-top: 34px;
            padding-top: 34px;
            border-top: 1px solid #d6dde3;
        }

        .related h2 {
            margin: 0 0 20px;
            font-size: clamp(2rem, 3.3vw, 3.1rem);
            letter-spacing: -0.02em;
            line-height: 1.08;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .related-card {
            text-decoration: none;
            color: inherit;
        }

        .related-media {
            width: 100%;
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 1.2 / 1;
            background: linear-gradient(145deg, #cfdeea 0%, #bdd2e0 100%);
        }

        .related-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .related-card h3 {
            margin: 10px 0 0;
            font-size: clamp(1.2rem, 2vw, 1.85rem);
            line-height: 1.22;
            letter-spacing: -0.01em;
        }

        @media (max-width: 1100px) {
            .hero {
                min-height: 560px;
            }

            .hero-title-card {
                left: 6%;
                width: min(760px, calc(100% - 30px));
            }

            .article-layout {
                grid-template-columns: 1fr;
            }

            .ad-card {
                position: relative;
                top: auto;
                min-height: 340px;
            }

            .related-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 860px) {
            .header-bar {
                min-height: auto;
                padding: 12px 6px;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .brand {
                font-size: 1.5rem;
            }

            .brand small {
                font-size: 0.68rem;
                letter-spacing: 0.12em;
            }

            .nav-links {
                justify-content: flex-end;
                flex-wrap: nowrap;
                overflow-x: auto;
                max-width: 62vw;
                padding-bottom: 2px;
                scrollbar-width: thin;
            }

            .nav-links a {
                flex: 0 0 auto;
                font-size: 0.72rem;
                padding: 7px 10px;
                letter-spacing: 0.06em;
            }

            .hero {
                min-height: 280px;
                margin-top: 8px;
            }

            .hero-title-card {
                position: static;
                width: calc(100% - 12px);
                margin: 6px;
                padding: 14px 14px 18px;
                box-shadow: none;
            }

            .meta-row {
                font-size: 0.82rem;
                gap: 8px;
            }

            .meta-row strong {
                font-size: 0.8rem;
            }

            .hero-title-card h1 {
                font-size: clamp(1.55rem, 7vw, 2.15rem);
                line-height: 1.14;
            }

            .tag-share-row {
                grid-template-columns: 1fr;
                gap: 10px;
                margin-bottom: 10px;
            }

            .tags {
                gap: 7px;
            }

            .tag-chip {
                font-size: 0.78rem;
                padding: 6px 11px;
                border-radius: 9px;
            }

            .share-button {
                width: 38px;
                height: 38px;
                font-size: 1.08rem;
            }

            .share-row {
                justify-content: flex-start;
            }

            .content-wrap {
                padding-top: 4px;
            }

            .article-layout {
                gap: 20px;
            }

            .work-event-callout {
                margin: 8px 0 18px;
                padding: 12px;
            }

            .content {
                font-size: 1.03rem;
                line-height: 1.7;
            }

            .content p {
                margin-bottom: 18px;
            }

            .ad-label {
                font-size: 0.88rem;
            }

            .ad-card {
                min-height: 0;
                padding: 16px;
                gap: 12px;
            }

            .ad-head {
                font-size: clamp(1.5rem, 8vw, 2.1rem);
                max-width: none;
            }

            .ad-brand {
                font-size: 2rem;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .related {
                margin-top: 26px;
                padding-top: 24px;
            }

            .related h2 {
                margin-bottom: 14px;
                font-size: clamp(1.5rem, 7.5vw, 2.1rem);
                line-height: 1.14;
            }
        }

        @media (max-width: 560px) {
            .page {
                width: min(1180px, calc(100% - 12px));
                margin-bottom: 18px;
            }

            .header-bar {
                top: 0;
                padding: 10px 4px;
                gap: 8px;
            }

            .brand {
                font-size: 1.32rem;
            }

            .hero {
                min-height: 220px;
            }

            .hero-title-card {
                width: calc(100% - 8px);
                margin: 4px;
                padding: 12px;
            }

            .content-wrap {
                border-top: none;
                padding-top: 2px;
            }

            .share-row {
                gap: 6px;
            }

            .related-media {
                border-radius: 12px;
                aspect-ratio: 1.15 / 1;
            }

            .related-card h3 {
                font-size: clamp(1.08rem, 5vw, 1.4rem);
            }
        }
    </style>
</head>
<body>
    @php
        $rawCoverSrc = trim((string) ($post->cover_image_url ?? ''));
        if ($rawCoverSrc === '') {
            $rawCoverSrc = trim((string) ($post->cover_image_path ?? ''));
        }
        $postCoverUrl = $rawCoverSrc !== '' ? blogResolveCoverImageUrl($rawCoverSrc) : '';
        $postCategorySlug = (string) ($post->blog_category_slug ?? 'things-to-do');
        $postCategoryLabel = (string) ($post->blog_category_label ?? 'Travel picks');
        $sidebarAd = is_array($blogSidebarAd ?? null) ? $blogSidebarAd : [];
        $sidebarAdTitle = trim((string) ($sidebarAd['title'] ?? 'Charter a vessel?'));
        $sidebarAdBrand = trim((string) ($sidebarAd['brand'] ?? 'workation'));
        $sidebarAdCtaLabel = trim((string) ($sidebarAd['cta_label'] ?? 'Explore now'));
        $sidebarAdCtaUrl = trim((string) ($sidebarAd['cta_url'] ?? '/catalog/marine-transport'));
        $sidebarAdImageUrl = trim((string) ($sidebarAd['image_url'] ?? ''));

        $postDate = optional($post->published_at)->format('M, Y - l')
            ?? optional($post->created_at)->format('M, Y - l')
            ?? 'Upcoming';

        $currentUrl = url('/blog/' . $post->slug);
        $encodedUrl = urlencode($currentUrl);
        $encodedTitle = urlencode((string) $post->title);

        $shareLinks = [
            ['label' => 'f', 'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl, 'title' => 'Share on Facebook'],
            ['label' => 'X', 'href' => 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle, 'title' => 'Share on X'],
            ['label' => 'in', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encodedUrl, 'title' => 'Share on LinkedIn'],
            ['label' => '↗', 'href' => 'mailto:?subject=' . $encodedTitle . '&body=' . $encodedUrl, 'title' => 'Share by Email'],
        ];

        $rawContent = trim((string) ($post->content ?? ''));
        $contentBlocks = blogBuildRenderableContentBlocks($rawContent, (string) ($post->title ?? 'Blog image'));

        $relatedCards = $relatedPosts->take(3)->values();
        $topicTags = collect($post->blog_tags ?? [])->take(5)->values();
        $contextChips = collect(preg_split('/\s+/', (string) ($post->title ?? '')) ?: [])
            ->map(function ($word): string {
                return trim(preg_replace('/[^A-Za-z]/', '', (string) $word));
            })
            ->filter(function (string $word): bool {
                return strlen($word) >= 5;
            })
            ->map(function (string $word): string {
                return \Illuminate\Support\Str::headline(\Illuminate\Support\Str::lower($word));
            })
            ->unique()
            ->take(4)
            ->values();
    @endphp

    <main class="page">
        <header class="header-bar" aria-label="Blog category header">
            <a class="brand" href="/blog">
                Workation
                <small>Blog</small>
            </a>
            <nav class="nav-links" aria-label="Blog categories">
                <a href="/">Home</a>
                <a href="/blog">The collection</a>
                @foreach ($blogCategories as $slug => $meta)
                    @php
                        $href = $slug === 'islands' ? '/islands' : ('/blog/category/' . $slug);
                        $navLabel = (string) ($meta['label'] ?? \Illuminate\Support\Str::headline($slug));
                    @endphp
                    <a class="{{ $slug === $postCategorySlug ? 'is-active' : '' }}" href="{{ $href }}">{{ $navLabel }}</a>
                @endforeach
            </nav>
        </header>

        <section class="hero" aria-label="Article hero">
            @if ($postCoverUrl !== '')
                <img src="{{ $postCoverUrl }}" alt="{{ $post->title }} cover image" loading="eager" fetchpriority="high">
            @else
                <div class="hero-fallback" aria-hidden="true"></div>
            @endif

            <div class="hero-title-card">
                <p class="meta-row">
                    <strong>{{ $postCategoryLabel }}</strong>
                    <span>{{ $postDate }}</span>
                </p>
                <h1>{{ $post->title }}</h1>
            </div>
        </section>

        <section class="content-wrap" aria-label="Article content and actions">
            <div class="tag-share-row">
                <div class="tags" aria-label="Article tags">
                    @foreach ($contextChips as $chip)
                        <span class="tag-chip muted">{{ $chip }}</span>
                    @endforeach
                    @foreach ($topicTags as $tag)
                        <a class="tag-chip" href="{{ '/blog/tag/' . ($tag['slug'] ?? '') }}">{{ $tag['label'] ?? 'Tag' }}</a>
                    @endforeach
                </div>

                <div class="share-row" aria-label="Share article">
                    @foreach ($shareLinks as $share)
                        <a class="share-button" href="{{ $share['href'] }}" target="_blank" rel="noopener noreferrer" title="{{ $share['title'] }}">{{ $share['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="article-layout">
                <article class="content" aria-label="Article body">
                    <section class="work-event-callout" aria-label="Conference and workspace planning links">
                        <h2>Planning a team retreat, workshop, or conference in Maldives?</h2>
                        <p>Pair this story context with live venue discovery for island workspaces and event-ready conference facilities.</p>
                        <div class="work-event-links">
                            <a href="/catalog/remote_workspace">Open Remote Workspace Listings</a>
                            <a href="/catalog/conference_room">Open Conference & Event Spaces</a>
                        </div>
                    </section>

                    @php
                        $galleryRendered  = false;
                        $galleryPos       = $post->gallery_position ?? 'after_intro';
                        $hasGallery       = isset($articleImages) && $articleImages->isNotEmpty();
                        $galleryHtml      = '';
                        if ($hasGallery) {
                            $cnt = $articleImages->count();
                            $galleryHtml = '<div class="article-gallery has-' . $cnt . '" aria-label="Article photos">';
                            foreach ($articleImages as $imgUrl) {
                                $galleryHtml .= '<figure><img src="' . e($imgUrl) . '" alt="' . e($post->title) . '" loading="eager"></figure>';
                            }
                            $galleryHtml .= '</div>';
                        }
                        $h2Seen = 0;
                    @endphp

                    {{-- Position: after_intro (before content body) --}}
                    @if ($hasGallery && $galleryPos === 'after_intro')
                        {!! $galleryHtml !!}
                        @php $galleryRendered = true; @endphp
                    @endif

                    @forelse ($contentBlocks as $block)
                        @if ($block['type'] === 'h2')
                            <h2>{!! blogRenderInlineMarkup((string) ($block['text'] ?? '')) !!}</h2>
                            @if ($hasGallery && !$galleryRendered)
                                @php $h2Seen++; @endphp
                                @if (($galleryPos === 'after_first_h2' && $h2Seen === 1) || ($galleryPos === 'after_second_h2' && $h2Seen === 2))
                                    {!! $galleryHtml !!}
                                    @php $galleryRendered = true; @endphp
                                @endif
                            @endif
                        @elseif ($block['type'] === 'h3')
                            <h3>{!! blogRenderInlineMarkup((string) ($block['text'] ?? '')) !!}</h3>
                        @elseif ($block['type'] === 'h4')
                            <h4>{!! blogRenderInlineMarkup((string) ($block['text'] ?? '')) !!}</h4>
                        @elseif ($block['type'] === 'image')
                            <figure>
                                <img src="{{ $block['url'] }}" alt="{{ $block['alt'] !== '' ? $block['alt'] : $post->title }}" loading="lazy">
                                @if ($block['alt'] !== '')
                                    <figcaption>{{ $block['alt'] }}</figcaption>
                                @endif
                            </figure>
                        @else
                            <p>{!! blogRenderInlineMarkup((string) ($block['text'] ?? '')) !!}</p>
                        @endif
                    @empty
                        <p>{{ $rawContent !== '' ? $rawContent : 'No content was provided for this article yet.' }}</p>
                    @endforelse

                    {{-- Position: end — or fallback if targeted H2 was never found --}}
                    @if ($hasGallery && !$galleryRendered)
                        {!! $galleryHtml !!}
                    @endif
                </article>

                <aside class="sidebar" aria-label="Article side panel">
                    <p class="ad-label">Ad by Workation</p>
                    <div class="ad-card">
                        @if ($sidebarAdImageUrl !== '')
                            <div class="ad-media">
                                <img src="{{ $sidebarAdImageUrl }}" alt="{{ $sidebarAdTitle !== '' ? $sidebarAdTitle : 'Promotional ad' }}" loading="lazy">
                            </div>
                        @endif
                        <p class="ad-head">{{ $sidebarAdTitle !== '' ? $sidebarAdTitle : 'Charter a vessel?' }}</p>
                        <div class="ad-foot">
                            <p class="ad-brand">{{ $sidebarAdBrand !== '' ? $sidebarAdBrand : 'workation' }}</p>
                            <div class="ad-stores">
                                <a class="ad-cta" href="{{ $sidebarAdCtaUrl !== '' ? $sidebarAdCtaUrl : '/catalog/marine-transport' }}">
                                    {{ $sidebarAdCtaLabel !== '' ? $sidebarAdCtaLabel : 'Explore now' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        @if ($relatedCards->isNotEmpty())
            <section class="related" aria-label="More stories in category">
                <h2>More Stories About {{ $postCategoryLabel }}</h2>
                <div class="related-grid">
                    @foreach ($relatedCards as $relatedPost)
                        @php
                            $rawRelatedSrc = trim((string) ($relatedPost->cover_image_url ?? ''));
                            if ($rawRelatedSrc === '') {
                                $rawRelatedSrc = trim((string) ($relatedPost->cover_image_path ?? ''));
                            }
                            $relatedCoverUrl = $rawRelatedSrc !== '' ? blogResolveCoverImageUrl($rawRelatedSrc) : '';
                            $relatedCategoryLabel = (string) ($relatedPost->blog_category_label ?? $postCategoryLabel);
                            $relatedDate = optional($relatedPost->published_at)->format('M d, Y - l')
                                ?? optional($relatedPost->created_at)->format('M d, Y - l')
                                ?? 'Upcoming';
                        @endphp
                        <a class="related-card" href="{{ '/blog/' . $relatedPost->slug }}">
                            <div class="related-media">
                                @if ($relatedCoverUrl !== '')
                                    <img src="{{ $relatedCoverUrl }}" alt="{{ $relatedPost->title }} image" loading="lazy">
                                @endif
                            </div>
                            <p class="meta-row" style="margin-top:10px; margin-bottom:7px;">
                                <strong>{{ $relatedCategoryLabel }}</strong>
                                <span>{{ $relatedDate }}</span>
                            </p>
                            <h3>{{ $relatedPost->title }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
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