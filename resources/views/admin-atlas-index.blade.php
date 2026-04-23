<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Island Atlas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #13212f; }
        .wrap { width: min(1200px, calc(100% - 24px)); margin: 24px auto 40px; }
        .head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; border: 1px solid #cfd8e3; text-decoration: none; color: #102033; background: #fff; font-weight: 600; }
        .btn.primary { background: #0f7f4f; color: #fff; border-color: #0f7f4f; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .card { background: #fff; border: 1px solid #dbe3ed; border-radius: 12px; overflow: hidden; }
        .card h2 { margin: 0; padding: 14px; font-size: 1rem; border-bottom: 1px solid #edf2f7; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #edf2f7; font-size: 0.88rem; vertical-align: top; }
        .thumb { width: 50px; height: 34px; object-fit: cover; border-radius: 6px; background: #d9e3ee; }
        .cap-badges { display: inline-flex; gap: 6px; flex-wrap: wrap; margin-top: 4px; }
        .cap-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 7px; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; border: 1px solid transparent; }
        .cap-badge.country-capital { background: #ecf3ff; border-color: #c8daf9; color: #194a8f; }
        .cap-badge.atoll-capital { background: #e9f8ef; border-color: #c4e7d1; color: #14613d; }
        .row-actions { display: flex; gap: 6px; }
        .btn.small { padding: 6px 10px; font-size: 0.78rem; }
        form { margin: 0; }
        .notice { margin-bottom: 10px; padding: 10px 12px; border-radius: 8px; background: #eaf9ef; border: 1px solid #b7e7c4; color: #0f6b31; }
        .errors { margin-bottom: 10px; padding: 10px 12px; border-radius: 8px; background: #fff2f2; border: 1px solid #f5c2c2; color: #842029; }
        @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@php
    $resolveAtlasPhotoUrl = static function (?string $rawPath): string {
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

        if (str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        if (str_starts_with($path, '/media/') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (str_starts_with($path, 'media/') || str_starts_with($path, 'storage/')) {
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

    $thumbFallbackSvg = "data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27400%27 height=%27240%27 viewBox=%270 0 400 240%27%3E%3Cdefs%3E%3ClinearGradient id=%27g%27 x1=%270%27 y1=%270%27 x2=%271%27 y2=%271%27%3E%3Cstop offset=%270%25%27 stop-color=%27%23d6e4ef%27/%3E%3Cstop offset=%27100%25%27 stop-color=%27%23c7d8e6%27/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%27400%27 height=%27240%27 fill=%27url(%23g)%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dominant-baseline=%27middle%27 fill=%27%233e5668%27 font-family=%27Arial%27 font-size=%2722%27%3ENo%20image%3C/text%3E%3C/svg%3E";
@endphp
<div class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0 0 4px;">Island Atlas Management</h1>
            <p style="margin:0;color:#4b637a;">Manage atolls and islands used across blog atlas, vendor, customer, and search forms.</p>
        </div>
        <div class="actions">
            <a class="btn" href="/admin?page=media">Back to Admin</a>
            <a class="btn primary" href="/portal/admin/atlas/atolls/create">New Atoll</a>
            <a class="btn primary" href="/portal/admin/atlas/islands/create">New Island</a>
        </div>
    </div>

    @if (session('portal_notice'))
        <div class="notice">{{ session('portal_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif

    <div class="grid">
        <section class="card" aria-label="Atolls list">
            <h2>Atolls ({{ $atolls->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($atolls as $atoll)
                    <tr>
                        <td>
                            @if(!empty($atoll->photo_path))
                                @php $atollPhoto = $resolveAtlasPhotoUrl($atoll->photo_path); @endphp
                                @if($atollPhoto !== '')
                                    <img class="thumb" src="{{ $atollPhoto }}" alt="{{ $atoll->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $thumbFallbackSvg }}';">
                                @else
                                    <span style="color:#8aa; font-size:0.76rem;">No image</span>
                                @endif
                            @else
                                <span style="color:#8aa; font-size:0.76rem;">No image</span>
                            @endif
                        </td>
                        <td>{{ $atoll->name }}</td>
                        <td>{{ $atoll->code ?? '-' }}</td>
                        <td>{{ $atoll->slug ?? '-' }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="btn small" href="/portal/admin/atlas/atolls/{{ (int) $atoll->id }}/edit">Edit</a>
                                <form method="POST" action="/portal/admin/atlas/atolls/{{ (int) $atoll->id }}/delete" onsubmit="return confirm('Delete this atoll?');">
                                    @csrf
                                    <button class="btn small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No atolls found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card" aria-label="Islands list">
            <h2>Islands ({{ $islands->count() }})</h2>
            <table>
                <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Atoll</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($islands as $island)
                    <tr>
                        <td>
                            @if(!empty($island->photo_path))
                                @php $islandPhoto = $resolveAtlasPhotoUrl($island->photo_path); @endphp
                                @if($islandPhoto !== '')
                                    <img class="thumb" src="{{ $islandPhoto }}" alt="{{ $island->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $thumbFallbackSvg }}';">
                                @else
                                    <span style="color:#8aa; font-size:0.76rem;">No image</span>
                                @endif
                            @else
                                <span style="color:#8aa; font-size:0.76rem;">No image</span>
                            @endif
                        </td>
                        <td>
                            {{ $island->name }}
                            @php
                                $islandCapitalBadges = portalAtlasCapitalBadges((string) ($island->name ?? ''), (string) (optional($island->atoll)->name ?? ''));
                            @endphp
                            @if(!empty($islandCapitalBadges))
                                <span class="cap-badges">
                                    @foreach($islandCapitalBadges as $badge)
                                        <span class="cap-badge {{ (string) ($badge['key'] ?? '') }}">{{ (string) ($badge['label'] ?? '') }}</span>
                                    @endforeach
                                </span>
                            @endif
                        </td>
                        <td>{{ optional($island->atoll)->name ?? '-' }}</td>
                        <td>{{ $island->island_type ?? '-' }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="btn small" href="/portal/admin/atlas/islands/{{ (int) $island->id }}/edit">Edit</a>
                                <form method="POST" action="/portal/admin/atlas/islands/{{ (int) $island->id }}/delete" onsubmit="return confirm('Delete this island?');">
                                    @csrf
                                    <button class="btn small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No islands found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
</body>
</html>
