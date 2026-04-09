<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'edit' ? 'Edit Newsletter' : 'Create Newsletter' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root { --bg:#f4f8fb; --ink:#15283b; --line:#ccdce8; --card:#fff; --brand:#0f6079; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Outfit","Trebuchet MS",sans-serif; color:var(--ink); background:var(--bg); }
        .page { width:min(1180px, calc(100% - 24px)); margin:14px auto 28px; }
        .top { display:flex; justify-content:space-between; align-items:center; border:1px solid var(--line); border-radius:14px; background:var(--card); padding:12px; }
        .title { margin:0; color:var(--brand); font-size:1.3rem; }
        .btn-link { text-decoration:none; border:1px solid #cfdeea; border-radius:10px; padding:8px 12px; background:#f8fbff; color:#1d4560; font-size:.83rem; font-weight:700; }
        .card { margin-top:14px; border:1px solid var(--line); border-radius:14px; background:var(--card); padding:14px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .field { display:grid; gap:6px; }
        .wide { grid-column:1 / -1; }
        label { font-size:.79rem; text-transform:uppercase; letter-spacing:.05em; color:#49657b; font-family:"Space Grotesk","Trebuchet MS",sans-serif; }
        input[type="text"], input[type="datetime-local"], select, textarea { border:1px solid #cbdbe8; border-radius:10px; padding:10px 11px; font:inherit; width:100%; }
        textarea { min-height:280px; resize:vertical; line-height:1.6; }
        .actions { margin-top:14px; display:flex; gap:8px; flex-wrap:wrap; }
        .btn { border:1px solid #cfdeea; border-radius:10px; padding:10px 13px; background:#f8fbff; color:#1b4360; font-size:.84rem; font-weight:700; cursor:pointer; }
        .btn.primary { border-color:var(--brand); background:var(--brand); color:#fff; }
        .error { margin-top:12px; border:1px solid #efc4c9; border-radius:11px; background:#fff0f2; color:#8f2b34; padding:10px 12px; }
        @media (max-width:740px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="page">
    @php
        $isEdit = $mode === 'edit' && $newsletter;
        $action = $isEdit ? ('/portal/admin/newsletter/' . $newsletter->id) : '/portal/admin/newsletter';
    @endphp

    <header class="top">
        <h1 class="title">{{ $isEdit ? 'Edit Newsletter' : 'Create Newsletter' }}</h1>
        <a class="btn-link" href="/portal/admin/newsletter">Back to Newsletter Manager</a>
    </header>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form class="card" method="POST" action="{{ $action }}">
        @csrf
        <div class="grid">
            <div class="field wide">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" maxlength="220" required value="{{ old('title', $isEdit ? $newsletter->title : '') }}">
            </div>

            <div class="field wide">
                <label for="subject">Subject</label>
                <input id="subject" name="subject" type="text" maxlength="300" required value="{{ old('subject', $isEdit ? $newsletter->subject : '') }}">
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    @php $status = old('status', $isEdit ? ($newsletter->status ?? 'draft') : 'draft'); @endphp
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="scheduled" @selected($status === 'scheduled')>Scheduled</option>
                    <option value="sent" @selected($status === 'sent')>Sent</option>
                    <option value="archived" @selected($status === 'archived')>Archived</option>
                </select>
            </div>

            <div class="field">
                <label for="audience">Audience</label>
                <select id="audience" name="audience">
                    @php $aud = old('audience', $isEdit ? ($newsletter->audience ?? 'all') : 'all'); @endphp
                    <option value="all" @selected($aud === 'all')>All</option>
                    <option value="members" @selected($aud === 'members')>Members</option>
                    <option value="partners" @selected($aud === 'partners')>Partners</option>
                </select>
            </div>

            <div class="field wide">
                <label for="scheduled_at">Scheduled At</label>
                <input id="scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', $isEdit && $newsletter->scheduled_at ? $newsletter->scheduled_at->format('Y-m-d\\TH:i') : '') }}">
            </div>

            <div class="field wide">
                <label for="body">Body</label>
                <textarea id="body" name="body" required>{{ old('body', $isEdit ? $newsletter->body : '') }}</textarea>
            </div>
        </div>

        <div class="actions">
            <a class="btn-link" href="/portal/admin/newsletter">Cancel</a>
            <button class="btn primary" type="submit">{{ $isEdit ? 'Save Newsletter' : 'Create Newsletter' }}</button>
        </div>
    </form>
</main>
</body>
</html>
