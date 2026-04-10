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
        .row-actions { display: flex; gap: 6px; }
        .btn.small { padding: 6px 10px; font-size: 0.78rem; }
        form { margin: 0; }
        .notice { margin-bottom: 10px; padding: 10px 12px; border-radius: 8px; background: #eaf9ef; border: 1px solid #b7e7c4; color: #0f6b31; }
        .errors { margin-bottom: 10px; padding: 10px 12px; border-radius: 8px; background: #fff2f2; border: 1px solid #f5c2c2; color: #842029; }
        @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
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
                                <img class="thumb" src="{{ Storage::disk('public')->url($atoll->photo_path) }}" alt="{{ $atoll->name }}">
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
                                <img class="thumb" src="{{ Storage::disk('public')->url($island->photo_path) }}" alt="{{ $island->name }}">
                            @else
                                <span style="color:#8aa; font-size:0.76rem;">No image</span>
                            @endif
                        </td>
                        <td>{{ $island->name }}</td>
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
