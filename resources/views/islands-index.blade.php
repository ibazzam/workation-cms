<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Islands of Maldives – Workation</title>
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
            --chip-line: #5ad176;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        /* ── Header ── */
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
            list-style: none;
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

        /* ── Page wrap ── */
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto 60px;
        }

        .hero-stage {
            position: relative;
            margin: 0 auto 28px;
            min-height: 420px;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(3, 27, 46, 0.28) 0%, rgba(3, 27, 46, 0.72) 100%),
                url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            display: grid;
            place-items: center;
        }

        .hero-stage::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 27, 46, 0.12) 0%, rgba(3, 27, 46, 0.72) 100%);
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #ffffff;
            padding: 0 24px;
            max-width: 840px;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.8rem, 5vw, 4.6rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
        }

        .dir-heading {
            padding: 12px 0 6px;
        }

        .dir-heading h1 {
            margin: 0 0 4px;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .dir-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        /* ── Search bar ── */
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 20px;
        }

        .search-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--muted);
            white-space: nowrap;
        }

        .search-input {
            flex: 1;
            max-width: 540px;
            width: 100%;
            padding: 12px 18px;
            border: 1.5px solid var(--line);
            border-radius: 40px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            background: var(--surface);
            color: var(--ink);
            transition: border-color .15s, box-shadow .15s;
        }

        .search-input:focus {
            border-color: var(--brand);
        }

        .search-input::placeholder {
            color: #aab5bf;
        }

        /* ── Atoll filter chips ── */
        .atoll-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 32px;
        }

        .type-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin: 0 0 20px;
        }

        .stats-card {
            border: 1.5px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
            padding: 10px 12px;
        }

        .stats-card strong {
            display: block;
            font-size: 1.2rem;
            line-height: 1;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        .stats-card span {
            display: block;
            margin-top: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .atoll-chip {
            padding: 6px 18px;
            border-radius: 40px;
            border: 1.5px solid var(--line);
            background: var(--surface);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }

        .atoll-chip:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .atoll-chip.is-active {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .type-chip {
            padding: 6px 16px;
            border-radius: 40px;
            border: 1.5px solid var(--line);
            background: var(--surface);
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
        }

        .type-chip:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .type-chip.is-active {
            background: #e7f7ed;
            color: #127a33;
            border-color: #7fce95;
        }

        /* ── Atoll sections ── */
        .atolls-container {
            display: flex;
            flex-direction: column;
            gap: 36px;
        }

        .atoll-section {
            border: 1.5px solid var(--line);
            border-radius: 12px;
            background: var(--surface);
            overflow: hidden;
            transition: box-shadow .2s;
        }

        .atoll-section[data-hidden="true"] {
            display: none;
        }

        .atoll-header {
            padding: 18px 22px;
            background: #f9fafb;
            border-bottom: 1.5px solid var(--line);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            user-select: none;
        }

        .atoll-header:hover {
            background: #f0f4f7;
        }

        .atoll-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .atoll-header-toggle {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: transform .2s;
            flex-shrink: 0;
        }

        .atoll-section[data-expanded="true"] .atoll-header-toggle {
            transform: rotate(180deg);
        }

        .atoll-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
            line-height: 1.25;
        }

        .atoll-counts {
            display: flex;
            gap: 14px;
            font-size: 0.81rem;
            color: var(--muted);
            margin-top: 4px;
        }

        .atoll-count-item {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .atoll-count-number {
            font-weight: 700;
            color: var(--ink);
        }

        .atoll-body {
            padding: 22px;
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s ease-out;
        }

        .atoll-section[data-expanded="true"] .atoll-body {
            max-height: 10000px;
            transition: max-height .3s ease-in;
        }

        .atoll-type-section {
            margin-bottom: 28px;
        }

        .atoll-type-section:last-child {
            margin-bottom: 0;
        }

        .atoll-type-heading {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--line);
        }

        .atoll-type-heading.inhabited {
            border-bottom-color: #43be66;
        }

        .atoll-type-heading.uninhabited {
            border-bottom-color: #f59e0b;
        }

        .atoll-type-heading.resort {
            border-bottom-color: #8b5cf6;
        }

        /* ── Islands grid ── */
        .island-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px 16px;
        }

        .island-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: inherit;
            gap: 12px;
        }

        .island-card[data-hidden="true"] {
            display: none;
        }

        .island-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--line);
            flex-shrink: 0;
            transition: box-shadow .2s;
        }

        .island-card:hover .island-avatar {
            box-shadow: 0 0 0 4px var(--brand);
        }

        .island-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .island-avatar .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e3f2e9;
            font-size: 2rem;
        }

        .island-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .island-atoll-code {
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 500;
        }

        .island-name-row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .island-name {
            font-size: 0.97rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
        }

        .island-capital-mark {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 22px;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.64rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .island-capital-mark.country-capital {
            background: #ecf3ff;
            border-color: #c8daf9;
            color: #194a8f;
        }

        .island-capital-mark.atoll-capital {
            background: #e9f8ef;
            border-color: #c4e7d1;
            color: #14613d;
        }

        .island-hint {
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.4;
        }

        /* ── Empty state ── */
        .empty-state {
            grid-column: 1 / -1;
            padding: 60px 0;
            text-align: center;
            color: var(--muted);
            font-size: 1rem;
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .island-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 720px) {
            .header-bar {
                min-height: auto;
                padding: 10px 16px;
                gap: 10px;
                flex-direction: column;
                align-items: stretch;
                justify-content: flex-start;
            }

            .brand {
                font-size: 1.58rem;
            }

            .nav-links {
                width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                gap: 8px;
                padding-bottom: 2px;
            }

            .nav-links::-webkit-scrollbar {
                display: none;
            }

            .nav-links a {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .stats-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .island-grid { grid-template-columns: repeat(2, 1fr); gap: 16px 12px; }
            .island-avatar { width: 90px; height: 90px; }
            .atoll-header { padding: 16px 14px; }
            .atoll-body { padding: 16px; }
            .atoll-title { font-size: 1.1rem; }
        }

        @media (max-width: 480px) {
            .header-bar {
                padding: 10px 12px;
            }

            .brand {
                font-size: 1.5rem;
            }

            .nav-links a {
                font-size: 0.76rem;
                padding: 7px 9px;
            }

            .island-grid { grid-template-columns: 1fr; }
            .island-avatar { width: 80px; height: 80px; }
            .atoll-header-content { flex-direction: column; align-items: flex-start; gap: 8px; }
            .atoll-title { font-size: 1rem; }
            .atoll-counts { font-size: 0.75rem; }
        }
    </style>
</head>
<body>

@php
    use Illuminate\Support\Str;
    /** @var \Illuminate\Support\Collection $atolls */
    /** @var \Illuminate\Support\Collection $groupedIslands */
    /** @var string|null $activeIslandType */
    /** @var array|null $islandStats */

    // Ensure islands are grouped by atoll and type
    $groupedByAtoll = $groupedIslands ?? collect();
    
    $typeLabelMap = [
        'inhabited' => 'Local / Inhabited',
        'uninhabited' => 'Uninhabited',
        'resort' => 'Resort',
    ];
    $typeColors = [
        'inhabited' => '#10b981',
        'uninhabited' => '#f59e0b',
        'resort' => '#8b5cf6',
    ];
    $typeEmoji = [
        'inhabited' => '🏘️',
        'uninhabited' => '🏝️',
        'resort' => '🏨',
    ];

    $activeTypeLabel = $activeIslandType !== null ? ($typeLabelMap[$activeIslandType] ?? null) : null;
    $pageTitle = 'Island Atlas · Maldives';
    if ($activeTypeLabel !== null) {
        $pageTitle .= ' · ' . $activeTypeLabel;
    }
    $pageSubtitle = 'Explore all 1079 islands directly organized by their administrative atolls. Select an atoll to view inhabited, uninhabited, and resort islands.';

    $islandStats = is_array($islandStats ?? null) ? $islandStats : [
        'atolls_total' => 0,
        'islands_total' => 0,
        'inhabited_total' => 0,
        'resort_total' => 0,
        'uninhabited_total' => 0,
    ];

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

    $defaultIslandPhotoUrl = '';

    $resolveIslandImageUrl = static function (?string $rawPath): string {
        $path = trim((string) ($rawPath ?? ''));
        if ($path === '') {
            return '';
        }

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

        if (str_starts_with($path, 'http://')) {
            return 'https://' . ltrim(substr($path, 7), '/');
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

        if (str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        if (str_starts_with($path, '/media/') || str_starts_with($path, '/storage/')) {
            if (preg_match('#^/media/blog/(\d+)(?:/cover)?/?$#i', $path, $matches) === 1) {
                return '/media/blog/' . $matches[1] . '/cover';
            }
            return $path;
        }

        if (str_starts_with($path, 'media/') || str_starts_with($path, 'storage/')) {
            if (preg_match('#^media/blog/(\d+)(?:/cover)?/?$#i', $path, $matches) === 1) {
                return '/media/blog/' . $matches[1] . '/cover';
            }
            return '/' . ltrim($path, '/');
        }

        $atlasCandidate = ltrim(str_replace('\\', '/', $path), '/');
        if (preg_match('#^(?:public/|storage/)?atlas/(?:islands|atolls)/#i', $atlasCandidate) === 1) {
            if (str_starts_with($atlasCandidate, 'public/')) {
                $atlasCandidate = substr($atlasCandidate, 7);
            }
            if (str_starts_with($atlasCandidate, 'storage/')) {
                $atlasCandidate = substr($atlasCandidate, 8);
            }
            $atlasCandidate = ltrim($atlasCandidate, '/');
            if ($atlasCandidate !== '') {
                $encodedAtlasPath = implode('/', array_map('rawurlencode', explode('/', $atlasCandidate)));
                return '/media/portal-public/' . $encodedAtlasPath;
            }
        }

        $managed = portalManagedMediaUrlFromPath($path);
        if (is_string($managed) && trim($managed) !== '') {
            return $managed;
        }

        $normalized = ltrim(str_replace(['public/', 'storage/'], '', str_replace('\\', '/', $path)), '/');
        return \Illuminate\Support\Facades\Storage::disk('public')->url($normalized);
    };

    $islandImageFallback = "data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27400%27 height=%27400%27 viewBox=%270 0 400 400%27%3E%3Cdefs%3E%3ClinearGradient id=%27g%27 x1=%270%27 y1=%270%27 x2=%271%27 y2=%271%27%3E%3Cstop offset=%270%25%27 stop-color=%27%23d8e9f2%27/%3E%3Cstop offset=%27100%25%27 stop-color=%27%23c8dceb%27/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%27400%27 height=%27400%27 fill=%27url(%23g)%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dominant-baseline=%27middle%27 fill=%27%233e5b71%27 font-family=%27Arial%27 font-size=%2726%27%3ENo%20image%3C/text%3E%3C/svg%3E";

    $resolveIslandCardImage = static function ($island, $atoll) use ($resolveIslandImageUrl, $atlasDestinationOverrides, $atlasCuratedDestinationImages, $defaultIslandPhotoUrl): string {
        $directImage = $resolveIslandImageUrl((string) ($island->photo_path ?? ''));
        if ($directImage !== '') {
            return $directImage;
        }

        $candidates = [
            portalNormalizeDestinationMediaKey((string) ($island->name ?? '')),
            portalNormalizeDestinationMediaKey((string) ($island->slug ?? '')),
            portalNormalizeDestinationMediaKey((string) ($island->name ?? '') . ' island'),
            portalNormalizeDestinationMediaKey((string) ($atoll->name ?? '')),
            portalNormalizeDestinationMediaKey((string) ($atoll->name ?? '') . ' atoll'),
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

        $atollImage = $resolveIslandImageUrl((string) ($atoll->photo_path ?? ''));
        if ($atollImage !== '') {
            return $atollImage;
        }

        return $defaultIslandPhotoUrl;
    };
@endphp

<header class="header-bar" aria-label="Site navigation">
    <a class="brand" href="/blog">
        Workation
        <small>Blog</small>
    </a>
    <nav class="nav-links" aria-label="Site sections">
        <a href="/blog">The collection</a>
        <a href="/blog/category/things-to-do">Travel picks</a>
        <a href="/blog/category/attractions">Ocean paths</a>
        <a href="/blog/category/stay">Calm escapes</a>
        <a class="is-active" href="/islands">Islands Guide</a>
    </nav>
</header>

<main class="page">
    <section class="hero-stage" aria-label="Explore islands hero">
        <div class="hero-copy">
            <h1>Explore islands of the Maldives</h1>
        </div>
    </section>

    <div class="dir-heading">
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageSubtitle }}</p>
    </div>

    <section class="stats-strip" aria-label="Island inventory summary">
        <article class="stats-card"><strong>{{ (int) ($islandStats['atolls_total'] ?? 0) }}</strong><span>Atolls</span></article>
        <article class="stats-card"><strong>{{ (int) ($islandStats['islands_total'] ?? 0) }}</strong><span>Total Islands</span></article>
        <article class="stats-card"><strong>{{ (int) ($islandStats['inhabited_total'] ?? 0) }}</strong><span>Local / Inhabited</span></article>
        <article class="stats-card"><strong>{{ (int) ($islandStats['resort_total'] ?? 0) }}</strong><span>Resort Islands</span></article>
        <article class="stats-card"><strong>{{ (int) ($islandStats['uninhabited_total'] ?? 0) }}</strong><span>Uninhabited</span></article>
    </section>

    {{-- Search --}}
    <div class="search-wrap">
        <span class="search-label">Search for Islands</span>
        <input
            class="search-input"
            type="search"
            id="island-search"
            placeholder="Search islands..."
            autocomplete="off"
            aria-label="Search islands"
        >
    </div>

    {{-- Atoll filter chips --}}
    <section class="atoll-filter" role="navigation" aria-label="Filter by atoll">
        @php
            $currentTypeQuery = request()->query('type');
            $typeQueryParam = $currentTypeQuery !== null ? ('?type=' . urlencode($currentTypeQuery)) : '';
            $allAtollsHref = '/islands' . $typeQueryParam;
            $activeAtollBase = $activeAtollSlug ? '/islands/atoll/' . urlencode($activeAtollSlug) : '/islands';
        @endphp

        <a class="atoll-chip {{ $activeAtollSlug === null ? 'is-active' : '' }}" href="{{ $allAtollsHref }}">All Atolls</a>
        @foreach ($atolls as $atoll)
            @php
                $atollSlug = $atoll->slug ?? Str::slug($atoll->name);
                $atollHref = '/islands/atoll/' . urlencode($atollSlug) . $typeQueryParam;
            @endphp
            <a class="atoll-chip {{ $activeAtollSlug === $atollSlug ? 'is-active' : '' }}" href="{{ $atollHref }}">{{ $atoll->name }}</a>
        @endforeach
    </section>

    {{-- Island type filter chips --}}
    <div class="type-filter" role="navigation" aria-label="Filter by island type">
        <a class="type-chip {{ $activeIslandType === null ? 'is-active' : '' }}" href="{{ $activeAtollBase }}">All Types</a>
        <a class="type-chip {{ $activeIslandType === 'inhabited' ? 'is-active' : '' }}" href="{{ $activeAtollBase . '?type=inhabited' }}">Local / Inhabited</a>
        <a class="type-chip {{ $activeIslandType === 'uninhabited' ? 'is-active' : '' }}" href="{{ $activeAtollBase . '?type=uninhabited' }}">Uninhabited</a>
        <a class="type-chip {{ $activeIslandType === 'resort' ? 'is-active' : '' }}" href="{{ $activeAtollBase . '?type=resort' }}">Resort</a>
    </div>

    {{-- Atolls Container --}}
    <section class="atolls-container" id="atolls-container" aria-label="Island atolls">
        @php
            $atollSections = $activeAtollSlug !== null
                ? $atolls->filter(fn ($a) => $groupedByAtoll->get($a->id, collect())->isNotEmpty())
                : $atolls;
        @endphp

        @forelse ($atollSections as $atoll)
            @php
                $atollSlug = $atoll->slug ?? Str::slug($atoll->name);
                $islandsInAtoll = $groupedByAtoll->get($atoll->id, collect());
                $typeGroups = $islandsInAtoll->groupBy('island_type');
                
                // Count by type
                $inhabCount = $typeGroups->get('inhabited', collect())->count();
                $uninhabCount = $typeGroups->get('uninhabited', collect())->count();
                $resortCount = $typeGroups->get('resort', collect())->count();
                $totalInAtoll = $islandsInAtoll->count();

                // Default expanded state (all atolls expanded on page load)
                $isExpanded = true;
            @endphp
            <div class="atoll-section" data-expanded="{{ $isExpanded ? 'true' : 'false' }}" data-atoll-id="{{ $atoll->id }}" data-atoll-name="{{ strtolower($atoll->name) }}">
                <div class="atoll-header" role="button" tabindex="0" aria-expanded="{{ $isExpanded ? 'true' : 'false' }}" aria-controls="atoll-body-{{ $atoll->id }}">
                    <div class="atoll-header-content">
                        <div class="atoll-header-toggle" aria-hidden="true">⌄</div>
                        <div>
                            <h2 class="atoll-title">{{ $atoll->name }} Atoll</h2>
                            <div class="atoll-counts">
                                @if ($inhabCount > 0)
                                    <span class="atoll-count-item"><span class="atoll-count-number">{{ $inhabCount }}</span> Inhabited</span>
                                @endif
                                @if ($uninhabCount > 0)
                                    <span class="atoll-count-item"><span class="atoll-count-number">{{ $uninhabCount }}</span> Uninhabited</span>
                                @endif
                                @if ($resortCount > 0)
                                    <span class="atoll-count-item"><span class="atoll-count-number">{{ $resortCount }}</span> Resort</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--muted); font-weight: 600;">
                        {{ $totalInAtoll }} islands
                    </div>
                </div>

                <div class="atoll-body" id="atoll-body-{{ $atoll->id }}">
                    {{-- Islands by type --}}
                    @php $typeOrder = ['inhabited', 'uninhabited', 'resort']; @endphp
                    @foreach ($typeOrder as $typeKey)
                        @php
                            $typeIslands = $typeGroups->get($typeKey, collect());
                            $typeLabel = $typeLabelMap[$typeKey] ?? $typeKey;
                        @endphp
                        @if ($typeIslands->isNotEmpty())
                            <div class="atoll-type-section">
                                <h3 class="atoll-type-heading {{ $typeKey }}">
                                    {{ $typeEmoji[$typeKey] ?? '' }} {{ $typeLabel }} ({{ $typeIslands->count() }})
                                </h3>
                                <div class="island-grid">
                                    @foreach ($typeIslands as $island)
                                        @php
                                            $islandSlug = $island->slug ?? Str::slug($island->name);
                                            $distanceHint = $island->distance_from_airport_km ? $island->distance_from_airport_km . ' KM' : null;
                                            $airportHint = $island->nearest_airport_name ?? null;
                                            $capitalBadges = portalAtlasCapitalBadges((string) ($island->name ?? ''), (string) ($atoll->name ?? ''));
                                            $islandImageUrl = $resolveIslandCardImage($island, $atoll);
                                            
                                            $hintParts = [];
                                            if ($distanceHint && $airportHint) $hintParts[] = $distanceHint . ' from ' . $airportHint;
                                            elseif ($distanceHint) $hintParts[] = $distanceHint;
                                            $hintText = implode(' ', $hintParts);
                                            $primaryCapitalBadge = $capitalBadges[0] ?? null;
                                        @endphp
                                        <a
                                            class="island-card"
                                            href="/islands/{{ $islandSlug }}"
                                            data-name="{{ strtolower($island->name) }}"
                                            data-atoll="{{ strtolower($atoll->name) }}"
                                            data-type="{{ $typeKey }}"
                                            aria-label="{{ $island->name }}"
                                        >
                                            <div class="island-avatar">
                                                @if ($islandImageUrl !== '')
                                                    <img
                                                        src="{{ $islandImageUrl }}"
                                                        alt="{{ $island->name }} aerial view"
                                                        loading="lazy"
                                                        onerror="if(!this.dataset.fb && '{{ $defaultIslandPhotoUrl }}' !== '' && this.src !== '{{ $defaultIslandPhotoUrl }}'){this.dataset.fb='1';this.src='{{ $defaultIslandPhotoUrl }}';}else{this.onerror=null;this.src='{{ $islandImageFallback }}';}"
                                                    >
                                                @else
                                                    <div class="avatar-placeholder" aria-hidden="true">{{ $typeEmoji[$typeKey] ?? '🏝' }}</div>
                                                @endif
                                            </div>
                                            <div class="island-meta">
                                                <span class="island-name-row">
                                                    <span class="island-name">{{ $island->name }}</span>
                                                    @if ($primaryCapitalBadge)
                                                        <span class="island-capital-mark {{ (string) ($primaryCapitalBadge['key'] ?? '') }}" aria-label="{{ (string) ($primaryCapitalBadge['label'] ?? '') }}">
                                                            <span aria-hidden="true">{{ (string) (($primaryCapitalBadge['key'] ?? '') === 'country-capital' ? '★' : '⌂') }}</span>
                                                            <span>{{ (string) (($primaryCapitalBadge['key'] ?? '') === 'country-capital' ? 'Capital' : 'Atoll capital') }}</span>
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($hintText)
                                                    <span class="island-hint">{{ $hintText }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state">No atolls found.</div>
        @endforelse
    </section>

</main>

@include('partials.global-site-footer')

<script>
    (function () {
        const header = document.querySelector('.header-bar');
        if (header) {
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
        }

        // Atoll expand/collapse
        document.querySelectorAll('.atoll-header').forEach(function (header) {
            header.addEventListener('click', function () {
                const section = this.closest('.atoll-section');
                const isExpanded = section.dataset.expanded === 'true';
                section.dataset.expanded = isExpanded ? 'false' : 'true';
                this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            });

            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    header.click();
                }
            });
        });

        // Search islands and atolls
        const searchInput = document.getElementById('island-search');
        const atollSections = Array.from(document.querySelectorAll('.atoll-section'));
        const islandCards = Array.from(document.querySelectorAll('.island-card'));

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                
                atollSections.forEach(function (section) {
                    const atollName = section.dataset.atollName || '';
                    const cardsInSection = Array.from(section.querySelectorAll('.island-card'));
                    
                    let hasVisibleCard = false;
                    cardsInSection.forEach(function (card) {
                        const name = (card.dataset.name || '').toLowerCase();
                        const match = !q || name.includes(q) || atollName.includes(q);
                        card.dataset.hidden = match ? 'false' : 'true';
                        if (match) hasVisibleCard = true;
                    });

                    // Show section if it matches atoll name or has visible cards
                    const atollMatch = !q || atollName.includes(q);
                    section.dataset.hidden = (atollMatch || hasVisibleCard) ? 'false' : 'true';
                });
            });
        }

        // Type filtering based on URL query
        const params = new URLSearchParams(window.location.search);
        const typeFilter = params.get('type');
        if (typeFilter) {
            islandCards.forEach(function (card) {
                const cardType = card.dataset.type || '';
                card.dataset.hidden = cardType !== typeFilter ? 'true' : 'false';
            });
        }
    })();
</script>

</body>
</html>