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
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 25;
            padding: 0 28px;
        }

        .brand {
            margin: 0;
            text-decoration: none;
            font-size: 1.65rem;
            line-height: 1;
            letter-spacing: -0.04em;
            color: var(--brand);
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .brand small {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            display: block;
            padding: 6px 14px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--muted);
            border-radius: 6px;
            transition: color .15s;
        }

        .nav-links a.is-active {
            color: var(--brand);
        }

        .nav-links a:hover {
            color: var(--ink);
        }

        /* ── Page wrap ── */
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto 60px;
        }

        /* ── Directory header ── */
        .dir-heading {
            padding: 44px 0 6px;
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
            max-width: 360px;
            padding: 10px 16px;
            border: 1.5px solid var(--line);
            border-radius: 40px;
            font-size: 0.88rem;
            font-family: inherit;
            outline: none;
            background: var(--surface);
            color: var(--ink);
            transition: border-color .15s;
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

        /* ── Islands grid ── */
        .island-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 28px 20px;
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

        .island-name {
            font-size: 0.97rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
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
            .island-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (max-width: 720px) {
            .header-bar { padding: 0 16px; }
            .island-grid { grid-template-columns: repeat(3, 1fr); gap: 20px 12px; }
            .island-avatar { width: 100px; height: 100px; }
        }

        @media (max-width: 480px) {
            .island-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

@php
    use Illuminate\Support\Str;
    /** @var \Illuminate\Support\Collection $atolls */
    /** @var \Illuminate\Support\Collection $islands */
    /** @var string|null $activeAtollSlug */
    /** @var string|null $activeIslandType */

    $activeAtoll = $atolls->first(fn ($a) => ($a->slug ?? Str::slug($a->name)) === $activeAtollSlug);
    $typeLabelMap = [
        'inhabited' => 'Inhabited',
        'uninhabited' => 'Uninhabited',
        'resort' => 'Resort',
    ];
    $activeTypeLabel = $activeIslandType !== null ? ($typeLabelMap[$activeIslandType] ?? null) : null;
    $pageTitle = $activeAtoll ? 'Islands of ' . $activeAtoll->name . ' Atoll' : 'Islands of Maldives';
    if ($activeTypeLabel !== null) {
        $pageTitle .= ' · ' . $activeTypeLabel . ' Islands';
    }
    $pageSubtitle = $activeAtoll
        ? 'Explore all islands within the ' . $activeAtoll->name . ' administrative atoll.'
        : 'Discover the inhabited and uninhabited islands across all atolls of the Maldives.';

    $islandFilterHref = function (?string $atollSlug, ?string $islandType) {
        $basePath = $atollSlug ? '/islands/atoll/' . $atollSlug : '/islands';
        if ($islandType === null || trim($islandType) === '') {
            return $basePath;
        }
        return $basePath . '?type=' . urlencode($islandType);
    };
@endphp

<header class="header-bar" aria-label="Site navigation">
    <a class="brand" href="/">
        Workation
        <small>Blog</small>
    </a>
    <nav class="nav-links" aria-label="Site sections">
        <a href="/blog">Things to Do</a>
        <a href="/blog/category/attractions">Attractions</a>
        <a href="/blog/category/stay">Stay</a>
        <a class="is-active" href="/islands">Islands</a>
    </nav>
</header>

<main class="page">

    <div class="dir-heading">
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageSubtitle }}</p>
    </div>

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

    {{-- Island type filter chips --}}
    <div class="type-filter" role="navigation" aria-label="Filter by island type">
        <a class="type-chip {{ $activeIslandType === null ? 'is-active' : '' }}" href="{{ $islandFilterHref($activeAtollSlug, null) }}">All Types</a>
        <a class="type-chip {{ $activeIslandType === 'inhabited' ? 'is-active' : '' }}" href="{{ $islandFilterHref($activeAtollSlug, 'inhabited') }}">Inhabited</a>
        <a class="type-chip {{ $activeIslandType === 'uninhabited' ? 'is-active' : '' }}" href="{{ $islandFilterHref($activeAtollSlug, 'uninhabited') }}">Uninhabited</a>
        <a class="type-chip {{ $activeIslandType === 'resort' ? 'is-active' : '' }}" href="{{ $islandFilterHref($activeAtollSlug, 'resort') }}">Resort</a>
    </div>

    {{-- Atoll filter chips --}}
    <div class="atoll-filter" role="navigation" aria-label="Filter by atoll">
        <a
            class="atoll-chip {{ $activeAtollSlug === null ? 'is-active' : '' }}"
            href="{{ $islandFilterHref(null, $activeIslandType) }}"
        >All Atolls</a>
        @foreach ($atolls as $atoll)
            @php
                $atollSlug = $atoll->slug ?? Str::slug($atoll->name);
            @endphp
            <a
                class="atoll-chip {{ $activeAtollSlug === $atollSlug ? 'is-active' : '' }}"
                href="{{ $islandFilterHref($atollSlug, $activeIslandType) }}"
            >{{ $atoll->name }}</a>
        @endforeach
    </div>

    {{-- Islands grid --}}
    <section class="island-grid" id="island-grid" aria-label="Islands list">
        @forelse ($islands as $island)
            @php
                $islandSlug   = $island->slug ?? Str::slug($island->name);
                $atollLabel   = $island->atoll ? $island->atoll->name : null;
                $atollCode    = $island->atoll ? ($island->atoll->code ?? strtoupper(substr($island->atoll->name ?? '', 0, 3))) : '';
                $distanceHint = $island->distance_from_airport_km ? $island->distance_from_airport_km . ' KM' : null;
                $airportHint  = $island->nearest_airport_name ?? null;

                $hintParts = [];
                if ($atollLabel) $hintParts[] = $atollLabel . ' atoll';
                if ($distanceHint && $airportHint) $hintParts[] = '– ' . $distanceHint . ' from VIA ' . $airportHint;
                elseif ($distanceHint) $hintParts[] = '– ' . $distanceHint;
                $hintText = implode(' ', $hintParts);
            @endphp
            <a
                class="island-card"
                href="/islands/{{ $islandSlug }}"
                data-name="{{ strtolower($island->name) }}"
                data-atoll="{{ strtolower($atollLabel ?? '') }}"
                aria-label="{{ $island->name }}"
            >
                <div class="island-avatar">
                    @if ($island->photo_path)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($island->photo_path) }}"
                            alt="{{ $island->name }} aerial view"
                            loading="lazy"
                        >
                    @else
                        <div class="avatar-placeholder" aria-hidden="true">🏝</div>
                    @endif
                </div>
                <div class="island-meta">
                    @if ($atollCode)
                        <span class="island-atoll-code">{{ $atollCode }}.</span>
                    @endif
                    <span class="island-name">{{ $island->name }}</span>
                    @if ($hintText)
                        <span class="island-hint">{{ $hintText }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="empty-state">No islands found for this filter.</div>
        @endforelse
    </section>

</main>

@include('partials.global-site-footer')

<script>
    (function () {
        const input = document.getElementById('island-search');
        const cards = Array.from(document.querySelectorAll('.island-card'));

        if (!input) return;

        input.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            cards.forEach(function (card) {
                const name  = (card.dataset.name  || '').toLowerCase();
                const atoll = (card.dataset.atoll || '').toLowerCase();
                const match = !q || name.includes(q) || atoll.includes(q);
                card.dataset.hidden = match ? 'false' : 'true';
            });
        });
    })();
</script>

</body>
</html>