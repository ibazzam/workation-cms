<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcement Manager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f4f8fb; --ink:#142a3c; --line:#cbdae6; --card:#fff; --brand:#0e607a; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px, calc(100% - 24px)); margin:14px auto 28px; }
        .top { display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--line); border-radius:14px; background:var(--card); padding:12px; }
        .title { margin:0; color:var(--brand); font-size:1.3rem; }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .btn { text-decoration:none; border:1px solid #ccdce8; border-radius:10px; padding:8px 12px; background:#f9fcff; color:#1f4560; font-size:.84rem; font-weight:700; }
        .btn.primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .notice { margin-top:12px; border:1px solid #cde3d2; background:#edf9f0; color:#245f3a; border-radius:10px; padding:10px 12px; }
        .error { margin-top:12px; border:1px solid #efc4ca; background:#fff0f2; color:#8f2a33; border-radius:10px; padding:10px 12px; }
        .table-wrap { margin-top:14px; border:1px solid var(--line); border-radius:14px; overflow:auto; background:#fff; }
        table { width:100%; border-collapse:collapse; min-width:860px; }
        th, td { padding:10px; border-bottom:1px solid #e2ebf2; text-align:left; font-size:.84rem; }
        th { font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; color:#4f6a7f; background:#f8fbff; }
        tr:last-child td { border-bottom:0; }
    </style>
</head>
<body>
<main class="page">
    <header class="top">
        <h1 class="title">Announcement Manager</h1>
        <div class="actions">
            <a class="btn" href="/admin/content">Back to Content Hub</a>
            <a class="btn primary" href="/portal/admin/announcement/create">New Announcement</a>
        </div>
    </header>

    @if (session('portal_notice'))
        <div class="notice">{{ session('portal_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <section class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Expires</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($announcements as $announcement)
                    <tr>
                        <td>{{ $announcement->title }}</td>
                        <td>{{ strtoupper((string) ($announcement->type ?? 'internal')) }}</td>
                        <td>{{ strtoupper((string) ($announcement->status ?? 'draft')) }}</td>
                        <td>{{ optional($announcement->published_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ optional($announcement->expires_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ optional($announcement->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <a class="btn" href="{{ '/portal/admin/announcement/' . $announcement->id . '/edit' }}">Edit</a>
                            <form method="POST" action="{{ '/portal/admin/announcement/' . $announcement->id . '/delete' }}" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit" onclick="return confirm('Delete this announcement?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No announcements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
