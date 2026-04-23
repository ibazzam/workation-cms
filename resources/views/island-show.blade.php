<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $island->name }} – Islands of Maldives – Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f5f7f8;
            --ink: #111f2a;
            --muted: #4e6679;
            --line: #d8dfe4;
            --surface: #ffffff;
            --brand: #43be66;
        }

        * { box-sizing: border-box; }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
            margin-bottom: 24px;
        }

        .hero-meta .hero-pill {
            background: #edf7f1;
            color: var(--brand);
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-meta .hero-pill--main {
            background: var(--brand);
            color: #ffffff;
        }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--surface);
        }

        /* ── Header ── */
        .fact-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .fact-card {
            background: #f7fcf8;
            border: 1px solid var(--line);
            padding: 18px 20px;
            border-radius: 18px;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
        }

        .fact-label {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .fact-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
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

        body.is-header-hidden .header-bar {
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
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            display: block;
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

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--muted);
            padding: 14px 28px;
            border-bottom: 1px solid var(--line);
        }

        .breadcrumb a {
            color: var(--muted);
            text-decoration: none;
        }

        .breadcrumb a:hover { color: var(--brand); }

        .breadcrumb .sep { font-size: 0.7rem; }

        .breadcrumb .current {
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* ── Hero / island profile ── */
        .island-hero {
            display: grid;
            grid-template-columns: 54fr 46fr;
            min-height: 520px;
            align-items: stretch;
        }

        .hero-image-col {
            overflow: hidden;
            background: var(--line);
        }

        .hero-image-col img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hero-image-col .image-placeholder {
            width: 100%;
            height: 100%;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            background: #e3f2e9;
        }

        .hero-info-col {
            padding: 36px 48px 36px 44px;
            display: flex;
            flex-direction: column;
            gap: 0;
            border-left: 1px solid var(--line);
        }

        /* Share row */
        .share-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 28px;
        }

        .share-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1.5px solid var(--line);
            background: var(--surface);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--ink);
            font-size: 1rem;
            transition: border-color .15s, background .15s;
        }

        .share-btn:hover {
            border-color: var(--brand);
            background: #f0fbf3;
        }

        /* Atoll code + name */
        .atoll-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--muted);
            margin: 0 0 6px;
        }

        .island-title {
            font-size: 2.6rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            margin: 0 0 20px;
        }

        .island-description {
            font-size: 0.97rem;
            line-height: 1.7;
            color: #2e4355;
            margin: 0 0 32px;
        }

        /* Stats */
        .island-stats {
            display: flex;
            flex-direction: column;
            gap: 0;
            border-top: 1px solid var(--line);
        }

        .stat-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 500;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
        }

        /* ── Category cards ── */
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .cat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding: 48px 0 0;
        }

        .cat-card {
            border-radius: 16px;
            background: #edf7f1;
            padding: 24px 22px 20px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: background .15s;
        }

        .cat-card:hover { background: #d8f0e2; }

        .cat-card.coming-soon {
            background: #f4fce8;
            cursor: default;
            pointer-events: none;
        }

        .cat-card .cat-type {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .cat-card .cat-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--brand);
            leading-trim: both;
        }

        .cat-card .cat-cta {
            margin-top: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ink);
        }

        /* ── Related islands ── */
        .related-section {
            padding: 56px 0 0;
        }

        .related-section h2 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin: 0 0 28px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px 16px;
            padding-bottom: 60px;
        }

        .related-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: inherit;
            gap: 10px;
        }

        .related-avatar {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--line);
            transition: box-shadow .2s;
            flex-shrink: 0;
        }

        .related-card:hover .related-avatar {
            box-shadow: 0 0 0 4px var(--brand);
        }

        .related-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .related-avatar .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e3f2e9;
            font-size: 1.8rem;
        }

        .related-meta { display: flex; flex-direction: column; gap: 2px; }
        .related-atoll-code { font-size: 0.73rem; color: var(--muted); font-weight: 500; }
        .related-name       { font-size: 0.93rem; font-weight: 700; }
        .related-hint       { font-size: 0.73rem; color: var(--muted); line-height: 1.4; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .island-hero { grid-template-columns: 1fr; }
            .hero-image-col { min-height: 280px; }
            .hero-info-col { padding: 28px 20px; border-left: none; border-top: 1px solid var(--line); }
            .island-title { font-size: 2rem; }
            .cat-cards { grid-template-columns: repeat(2, 1fr); }
            .related-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 560px) {
            .header-bar { padding: 0 16px; }
            .cat-cards { grid-template-columns: 1fr 1fr; }
            .related-grid { grid-template-columns: repeat(2, 1fr); }
            .related-avatar { width: 90px; height: 90px; }
        }
    </style>
</head>
<body>

@php
    /** @var \App\Models\Island $island */
    /** @var \Illuminate\Support\Collection $relatedIslands */

    $atoll      = $island->atoll;
    $atollName  = $atoll ? $atoll->name : null;
    $atollSlug  = $atoll ? ($atoll->slug ?? \Illuminate\Support\Str::slug($atoll->name)) : null;
    $atollCode  = $atoll ? ($atoll->code ?? strtoupper(substr($atoll->name ?? '', 0, 3))) : null;

    $atlasCuratedDestinationImages = [
        'male' => '/images/home/destinations/male-city.svg',
        'male_city' => '/images/home/destinations/male-city.svg',
        'hulhumale' => '/images/home/destinations/hulhumale-seafront.svg',
        'hulhumale_seafront' => '/images/home/destinations/hulhumale-seafront.svg',
        'thulusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'ukulhas' => '/images/home/destinations/ukulhas-island.svg',
        'ukulhas_island' => '/images/home/destinations/ukulhas-island.svg',
        'dhigurah' => '/images/home/destinations/dhigurah-island.svg',
        'dhigurah_island' => '/images/home/destinations/dhigurah-island.svg',
    ];

    $atlasDestinationOverrides = [];
    if (\Illuminate\Support\Facades\Schema::hasTable('portal_destination_media_overrides')) {
        $atlasDestinationOverrides = \Illuminate\Support\Facades\DB::table('portal_destination_media_overrides')
            ->pluck('image_value', 'destination_key')
            ->all();
    }

    $defaultIslandPhotoUrl = '/media/blog/1/cover';

    $resolveMediaUrl = static function (?string $rawPath): string {
        $path = trim((string) $rawPath);
        if ($path === '') {
            return '';
        }

        // Handle accidental JSON payloads or quoted strings persisted in photo_path.
        $decoded = json_decode($path, true);
        if (is_array($decoded)) {
            $path = trim((string) ($decoded['path'] ?? $decoded['url'] ?? $decoded['photo_path'] ?? ''));
            if ($path === '') {
                return '';
            }
        }

        $path = trim($path, " \t\n\r\0\x0B\"'");
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        if (preg_match('#^/?media/blog/(\d+)(?:/cover)?/?$#i', $path, $matches) === 1) {
            return '/media/blog/' . $matches[1] . '/cover';
        }

        if (preg_match('#^/?blog/(\d+)(?:/cover)?/?$#i', $path, $matches) === 1) {
            return '/media/blog/' . $matches[1] . '/cover';
        }

        if (preg_match('/^\d+$/', $path) === 1) {
            return '/media/blog/' . $path . '/cover';
        }

        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        if (str_starts_with($path, 'media/')) {
            return '/' . ltrim($path, '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return '/' . ltrim($path, '/');
        }

        if (str_contains($path, '/media/')) {
            $pos = strpos($path, '/media/');
            if ($pos !== false) {
                $mediaPath = substr($path, $pos);
                if (preg_match('#^/media/blog/(\d+)(?:/cover)?/?$#i', $mediaPath, $matches) === 1) {
                    return '/media/blog/' . $matches[1] . '/cover';
                }
                return $mediaPath;
            }
        }

        if (str_contains($path, '/storage/')) {
            $pos = strpos($path, '/storage/');
            if ($pos !== false) {
                return substr($path, $pos);
            }
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return str_starts_with($path, 'http://') ? ('https://' . ltrim(substr($path, 7), '/')) : $path;
        }

        if (str_starts_with($path, '/media/') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        $managed = portalManagedMediaUrlFromPath($path);
        if (is_string($managed) && trim($managed) !== '') {
            return $managed;
        }

        $normalized = ltrim(str_replace(['public/', 'storage/'], '', str_replace('\\', '/', $path)), '/');
        return \Illuminate\Support\Facades\Storage::disk('public')->url($normalized);
    };

    $resolveIslandDisplayImage = static function ($islandRecord, $atollRecord = null) use ($resolveMediaUrl, $atlasDestinationOverrides, $atlasCuratedDestinationImages, $defaultIslandPhotoUrl): string {
        $directImage = $resolveMediaUrl((string) ($islandRecord->photo_path ?? ''));
        if ($directImage !== '') {
            return $directImage;
        }

        $candidates = [
            portalNormalizeDestinationMediaKey((string) ($islandRecord->name ?? '')),
            portalNormalizeDestinationMediaKey((string) ($islandRecord->slug ?? '')),
            portalNormalizeDestinationMediaKey((string) ($islandRecord->name ?? '') . ' island'),
            portalNormalizeDestinationMediaKey((string) ($atollRecord->name ?? '')),
            portalNormalizeDestinationMediaKey((string) ($atollRecord->name ?? '') . ' atoll'),
        ];

        foreach ($candidates as $candidateKey) {
            if ($candidateKey === '') {
                continue;
            }

            $overrideValue = trim((string) ($atlasDestinationOverrides[$candidateKey] ?? ''));
            if ($overrideValue !== '') {
                $overrideUrl = portalManagedMediaUrlFromPath($overrideValue) ?? '';
                if ($overrideUrl !== '') {
                    return $overrideUrl;
                }
            }

            $curatedUrl = trim((string) ($atlasCuratedDestinationImages[$candidateKey] ?? ''));
            if ($curatedUrl !== '') {
                return $curatedUrl;
            }
        }

        $atollImage = $resolveMediaUrl((string) ($atollRecord->photo_path ?? ''));
        if ($atollImage !== '') {
            return $atollImage;
        }

        return $defaultIslandPhotoUrl;
    };

    $coverUrl = $resolveIslandDisplayImage($island, $atoll);
    $coverFallback = "data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%271600%27 height=%27900%27 viewBox=%270 0 1600 900%27%3E%3Cdefs%3E%3ClinearGradient id=%27g%27 x1=%270%27 y1=%270%27 x2=%271%27 y2=%271%27%3E%3Cstop offset=%270%25%27 stop-color=%27%23d8ece1%27/%3E%3Cstop offset=%27100%25%27 stop-color=%27%23c5dfd1%27/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%271600%27 height=%27900%27 fill=%27url(%23g)%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dominant-baseline=%27middle%27 fill=%27%23395c4c%27 font-family=%27Arial%27 font-size=%2742%27%3EIsland%20Photo%3C/text%3E%3C/svg%3E";
    $capitalBadges = portalAtlasCapitalBadges((string) ($island->name ?? ''), (string) ($atollName ?? ''));

    $shareUrl     = url('/islands/' . ($island->slug ?? \Illuminate\Support\Str::slug($island->name)));
    $shareTitle   = urlencode($island->name . ' – Maldives Island Directory on Workation');
    $shareLinks   = [
        ['label' => 'Facebook', 'icon' => 'fb',  'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl)],
        ['label' => 'Twitter',  'icon' => 'tw',  'href' => 'https://twitter.com/intent/tweet?text=' . $shareTitle . '&url=' . urlencode($shareUrl)],
        ['label' => 'LinkedIn', 'icon' => 'li',  'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($shareUrl)],
    ];

    // Category cards linking to blog categories
    $catCards = [
        ['type' => 'THINGS',   'title' => 'Things You Can Do',  'cta' => 'Read', 'href' => '/blog/category/things-to-do',  'soon' => false],
        ['type' => 'REACH',    'title' => 'Top Attractions',    'cta' => 'Read', 'href' => '/blog/category/attractions',   'soon' => false],
        ['type' => 'STAY',     'title' => 'Places To Stay',     'cta' => 'Read', 'href' => '/blog/category/stay',          'soon' => false],
        ['type' => 'VR 360',   'title' => 'Virtual Tour',       'cta' => 'Coming Soon', 'href' => '#',               'soon' => true],
    ];
@endphp

<header class="header-bar" aria-label="Site navigation">
    <a class="brand" href="/">
        Workation
        <small>Blog</small>
    </a>
    <nav class="nav-links" aria-label="Site sections">
        <a href="/blog">Travel picks</a>
        <a href="/blog/category/attractions">Ocean Paths</a>
        <a href="/blog/category/stay">Calm Escapes</a>
        <a class="is-active" href="/islands">Islands Guide</a>
    </nav>
</header>

{{-- Breadcrumb --}}
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/">🏠</a>
    <span class="sep">›</span>
    <a href="/islands">Directory</a>
    @if ($atollName)
        <span class="sep">›</span>
        <a href="/islands/atoll/{{ $atollSlug }}">{{ $atollName }}</a>
    @endif
    <span class="sep">›</span>
    <span class="current">{{ strtoupper($island->name) }}</span>
</nav>

{{-- Island profile hero --}}
<section class="island-hero" aria-label="{{ $island->name }} profile">

    <div class="hero-image-col">
        @if ($coverUrl !== '')
            <img src="{{ $coverUrl }}" alt="{{ $island->name }} aerial photograph" loading="eager" fetchpriority="high" onerror="if(!this.dataset.fb && '{{ $defaultIslandPhotoUrl }}' !== '' && this.src !== '{{ $defaultIslandPhotoUrl }}'){this.dataset.fb='1';this.src='{{ $defaultIslandPhotoUrl }}';}else{this.onerror=null;this.src='{{ $coverFallback }}';}">
        @else
            <div class="image-placeholder" aria-hidden="true">🏝</div>
        @endif
    </div>

    <div class="hero-info-col">

        {{-- Share buttons --}}
        <div class="share-row" aria-label="Share this page">
            @foreach ($shareLinks as $s)
                <a class="share-btn" href="{{ $s['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="Share on {{ $s['label'] }}">
                    @if ($s['icon'] === 'fb')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    @elseif ($s['icon'] === 'tw')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4l16 16M20 4 4 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                    @elseif ($s['icon'] === 'li')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="hero-meta">
            @if ($atollName)
                <span class="hero-pill">{{ $atollName }} atoll</span>
            @endif
            <span class="hero-pill hero-pill--main">
                @if ($island->island_type === 'resort')
                    Resort island
                @elseif ($island->island_type === 'inhabited')
                    Inhabited island
                @elseif ($island->island_type === 'uninhabited')
                    Uninhabited island
                @elseif ($island->is_inhabited)
                    Inhabited island
                @else
                    Uninhabited island
                @endif
            </span>
            @if (!empty($capitalBadges))
                @foreach ($capitalBadges as $badge)
                    <span class="hero-pill">{{ (string) ($badge['label'] ?? '') }}</span>
                @endforeach
            @endif
        </div>
        <h1 class="island-title">{{ $island->name }}</h1>

        @if ($island->description)
            <p class="island-description">{{ $island->description }}</p>
        @elseif ($island->wikipedia_title)
            <p class="island-description">
                {{ $island->name }} is an island in the Maldives
                @if ($atollName)
                    , located in the {{ $atollName }} administrative atoll.
                @endif
                @if ($island->is_inhabited)
                    It is an inhabited island.
                @else
                    It is an uninhabited island.
                @endif
            </p>
        @endif

        {{-- Island facts --}}
        @if ($island->population !== null || $island->nearest_airport_name || $island->distance_from_airport_km !== null)
            <div class="fact-grid" aria-label="Island facts">
            @if ($island->population !== null)
                <div class="fact-card">
                    <span class="fact-label">Population</span>
                    <span class="fact-value">{{ number_format($island->population) }}</span>
                </div>
            @endif
            @if ($island->nearest_airport_name)
                <div class="fact-card">
                    <span class="fact-label">Nearest Airport</span>
                    <span class="fact-value">{{ $island->nearest_airport_name }}</span>
                </div>
            @endif
            @if ($island->distance_from_airport_km !== null)
                <div class="fact-card">
                    <span class="fact-label">Distance from airport</span>
                    <span class="fact-value">{{ $island->distance_from_airport_km }} km</span>
                </div>
            @endif
            </div>
        @endif

    </div>
</section>

{{-- Category cards + related islands --}}
<div class="page">

    {{-- Category cards --}}
    <div class="cat-cards" role="navigation" aria-label="Explore island content">
        @foreach ($catCards as $card)
            <a
                class="cat-card {{ $card['soon'] ? 'coming-soon' : '' }}"
                href="{{ $card['href'] }}"
                @if ($card['soon']) aria-disabled="true" @endif
            >
                <span class="cat-type">{{ $card['type'] }}</span>
                <span class="cat-title">{{ $card['title'] }}</span>
                <span class="cat-cta">{{ $card['cta'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Related islands in same atoll --}}
    @if ($relatedIslands->isNotEmpty())
        <section class="related-section" aria-label="More islands in this atoll">
            <h2>More islands in {{ $atollName ?? 'this Atoll' }}</h2>

            <div class="related-grid">
                @foreach ($relatedIslands as $rel)
                    @php
                        $relSlug      = $rel->slug ?? \Illuminate\Support\Str::slug($rel->name);
                        $relAtollCode = $rel->atoll ? ($rel->atoll->code ?? strtoupper(substr($rel->atoll->name ?? '', 0, 3))) : '';
                        $relHint      = $rel->distance_from_airport_km && $rel->nearest_airport_name
                            ? ($rel->atoll->name ?? '') . ' atoll – ' . $rel->distance_from_airport_km . ' KM from VIA ' . $rel->nearest_airport_name
                            : ($rel->atoll->name ?? null);
                        $relPhoto     = $resolveIslandDisplayImage($rel, $rel->atoll ?? null);
                    @endphp
                    <a class="related-card" href="/islands/{{ $relSlug }}" aria-label="{{ $rel->name }}">
                        <div class="related-avatar">
                            @if ($relPhoto !== '')
                                <img src="{{ $relPhoto }}" alt="{{ $rel->name }}" loading="lazy" onerror="if(!this.dataset.fb && '{{ $defaultIslandPhotoUrl }}' !== '' && this.src !== '{{ $defaultIslandPhotoUrl }}'){this.dataset.fb='1';this.src='{{ $defaultIslandPhotoUrl }}';}else{this.onerror=null;this.src='{{ $coverFallback }}';}">
                            @else
                                <div class="avatar-placeholder" aria-hidden="true">🏝</div>
                            @endif
                        </div>
                        <div class="related-meta">
                            @if ($relAtollCode)
                                <span class="related-atoll-code">{{ $relAtollCode }}.</span>
                            @endif
                            <span class="related-name">{{ $rel->name }}</span>
                            @if ($relHint)
                                <span class="related-hint">{{ $relHint }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</div>

@include('partials.global-site-footer')

<script>
    (function () {
        const header = document.querySelector('.header-bar');
        if (!header) return;

        let lastY = window.scrollY || 0;
        function syncHeaderState() {
            const currentY = window.scrollY || 0;
            const threshold = Math.max(56, header.offsetHeight - 4);
            const hide = currentY > threshold && currentY > lastY;
            document.body.classList.toggle('is-header-hidden', hide);
            lastY = currentY;
        }

        window.addEventListener('scroll', syncHeaderState, { passive: true });
        window.addEventListener('resize', syncHeaderState);
        syncHeaderState();
    })();
</script>

</body>
</html>