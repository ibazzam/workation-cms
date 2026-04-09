<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Blog Manager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #132739;
            --muted: #5b7589;
            --line: #cbdce8;
            --card: #fff;
            --brand: #0e607a;
            --danger: #b3303a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .page {
            width: min(1180px, calc(100% - 24px));
            margin: 14px auto 28px;
            padding: 18px 0 26px;
        }

        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--card);
            padding: 12px;
        }

        .title {
            margin: 0;
            font-size: 1.35rem;
            color: var(--brand);
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border: 1px solid #ccdce8;
            border-radius: 10px;
            padding: 8px 12px;
            color: #1e455e;
            background: #f9fcff;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .btn.primary {
            border-color: #0e607a;
            background: #0e607a;
            color: #fff;
        }

        .notice {
            margin-top: 12px;
            border-radius: 11px;
            padding: 10px 12px;
            border: 1px solid #cae2cf;
            background: #edf9f0;
            color: #226038;
            font-size: 0.9rem;
        }

        .error {
            margin-top: 12px;
            border-radius: 11px;
            padding: 10px 12px;
            border: 1px solid #f0c6cb;
            background: #fff0f2;
            color: #8f2a33;
            font-size: 0.9rem;
        }

        .table-wrap {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: auto;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #e1ebf2;
            text-align: left;
            font-size: 0.84rem;
            vertical-align: top;
        }

        th {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #4f6a7f;
            background: #f8fbff;
        }

        tr:last-child td { border-bottom: 0; }

        .status {
            display: inline-flex;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #ecf5fb;
            color: #1f5575;
        }

        .status.draft {
            background: #f3f3f3;
            color: #566477;
        }

        .status.featured {
            background: #fff3dc;
            color: #8d5307;
        }

        .post-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="top" aria-label="Blog manager header">
            <h1 class="title">Blog Manager</h1>
            <div class="actions">
                <a class="btn" href="/admin">Back to Admin</a>
                <a class="btn primary" href="/portal/admin/blog/create">New Post</a>
            </div>
        </header>

        @if (session('portal_notice'))
            <div class="notice">{{ session('portal_notice') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <section class="table-wrap" aria-label="Blog posts table">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Editorial</th>
                        <th>Featured</th>
                        <th>Published At</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>{{ $post->title }}</td>
                            <td>{{ $post->slug }}</td>
                            <td>
                                <span class="status {{ (bool) ($post->is_published ?? false) ? '' : 'draft' }}">
                                    {{ (bool) ($post->is_published ?? false) ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td>
                                @php $editorial = strtolower(trim((string) ($post->editorial_status ?? 'draft'))); @endphp
                                <span class="status {{ $editorial === 'approved' ? '' : 'draft' }}">
                                    {{ strtoupper(str_replace('_', ' ', $editorial)) }}
                                </span>
                            </td>
                            <td>
                                @if ((bool) ($post->is_featured ?? false))
                                    <span class="status featured">Featured</span>
                                @else
                                    <span class="status draft">No</span>
                                @endif
                            </td>
                            <td>{{ optional($post->published_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ optional($post->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>
                                <div class="post-actions">
                                    <a class="btn" href="{{ '/portal/admin/blog/' . $post->id . '/edit' }}">Edit</a>
                                    @if (($canEditorialReview ?? false) && in_array(strtolower(trim((string) ($post->editorial_status ?? 'draft'))), ['pending_review', 'rejected'], true))
                                        <a class="btn" href="{{ '/portal/admin/blog/' . $post->id . '/edit' }}">Review</a>
                                    @endif
                                    @if ((bool) ($post->is_published ?? false))
                                        <a class="btn" href="{{ '/blog/' . $post->slug }}" target="_blank" rel="noopener">View</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No blog posts yet. Create your first post.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
