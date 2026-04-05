<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} - Workation Blog</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f5f8fb;
            --ink: #172a3b;
            --muted: #5a7388;
            --line: #d1deea;
            --brand: #0f6079;
            --card: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #f8fbff 0%, #f1f7fb 100%);
        }

        .page {
            width: min(1180px, calc(100% - 24px));
            margin: 14px auto 28px;
            padding: 18px 0 0;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 11px 13px;
            background: rgba(255, 255, 255, 0.94);
        }

        .topbar a {
            text-decoration: none;
            color: #1d4760;
            font-weight: 700;
            font-size: 0.86rem;
            border: 1px solid #d3e1ec;
            border-radius: 10px;
            padding: 7px 10px;
            background: #f8fbff;
        }

        .article {
            margin-top: 15px;
            border: 1px solid #c8dbe8;
            border-radius: 18px;
            background: var(--card);
            overflow: hidden;
        }

        .cover {
            display: block;
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            background: linear-gradient(140deg, #d8e8f3 0%, #c3dcee 100%);
        }

        .article-body {
            padding: 18px;
        }

        .eyebrow {
            margin: 0;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4a6983;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        h1 {
            margin: 7px 0 8px;
            font-size: clamp(1.5rem, 3.8vw, 2.3rem);
            line-height: 1.14;
        }

        .meta {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 0.83rem;
        }

        .content {
            color: #213d52;
            line-height: 1.75;
            font-size: 1rem;
            white-space: normal;
        }

        .related {
            margin-top: 15px;
            border: 1px solid #cbdcea;
            border-radius: 14px;
            background: #f9fcff;
            padding: 14px;
        }

        .related h2 {
            margin: 0 0 8px;
            font-size: 1rem;
        }

        .related-grid {
            display: grid;
            gap: 8px;
        }

        .related-item {
            border: 1px solid #d7e4ee;
            border-radius: 11px;
            padding: 9px 10px;
            background: #fff;
            text-decoration: none;
            color: #16374f;
            font-weight: 600;
        }

        .related-item:hover {
            border-color: #aac8de;
            color: #0f6079;
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar" aria-label="Blog nav">
            <a href="/">Workation Home</a>
            <a href="/blog">Back to Blog</a>
        </header>

        <article class="article" aria-label="Blog post">
            @php
                $coverPath = trim((string) ($post->cover_image_path ?? ''));
                $coverUrl = $coverPath !== '' ? (string) \Illuminate\Support\Facades\Storage::disk('public')->url($coverPath) : '';
            @endphp

            @if ($coverUrl !== '')
                <img class="cover" src="{{ $coverUrl }}" alt="{{ $post->title }} cover image" loading="lazy">
            @endif

            <div class="article-body">
                <p class="eyebrow">Workation Journal</p>
                <h1>{{ $post->title }}</h1>
                <p class="meta">Published {{ optional($post->published_at)->format('M d, Y') ?? optional($post->created_at)->format('M d, Y') }}</p>
                <div class="content">{!! nl2br(e((string) $post->content)) !!}</div>
            </div>
        </article>

        @if ($relatedPosts->isNotEmpty())
            <aside class="related" aria-label="Related stories">
                <h2>Related stories</h2>
                <div class="related-grid">
                    @foreach ($relatedPosts as $relatedPost)
                        <a class="related-item" href="{{ '/blog/' . $relatedPost->slug }}">{{ $relatedPost->title }}</a>
                    @endforeach
                </div>
            </aside>
        @endif

        @include('partials.global-site-footer')
    </main>
</body>
</html>
