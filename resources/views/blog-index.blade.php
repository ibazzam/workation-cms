<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workation Blog</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #152738;
            --muted: #587084;
            --line: #cfdeea;
            --brand: #0d5f7b;
            --brand-soft: #e3f0f6;
            --card: #ffffff;
            --accent: #f3a13a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at 12% 4%, #fef9ef 0%, #f4f8fb 36%, #eef6fb 100%);
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
            gap: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(9px);
        }

        .brand {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--brand);
            text-decoration: none;
        }

        .topnav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .topnav a {
            text-decoration: none;
            color: #20445b;
            border: 1px solid #d3e0ea;
            border-radius: 10px;
            padding: 7px 11px;
            font-size: 0.85rem;
            font-weight: 700;
            background: #f8fbff;
        }

        .hero {
            margin-top: 14px;
            border-radius: 18px;
            border: 1px solid #c8dce9;
            background: linear-gradient(140deg, #0f6079 0%, #123f5b 100%);
            color: #eaf7ff;
            padding: 24px;
        }

        .eyebrow {
            margin: 0;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #d0ebf9;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .hero h1 {
            margin: 8px 0 10px;
            font-size: clamp(1.6rem, 4vw, 2.3rem);
            line-height: 1.1;
        }

        .hero p {
            margin: 0;
            max-width: 680px;
            color: #d9edf8;
        }

        .feature {
            margin-top: 16px;
            border: 1px solid #c7dcec;
            border-radius: 16px;
            overflow: hidden;
            background: var(--card);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 0.95fr);
        }

        .feature-media {
            min-height: 280px;
            background: linear-gradient(135deg, #dbeaf4 0%, #c5dff0 100%);
        }

        .feature-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .feature-body {
            padding: 18px;
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .chip {
            display: inline-flex;
            width: fit-content;
            padding: 4px 9px;
            border-radius: 999px;
            background: #e7f2f8;
            color: #185070;
            font-size: 0.73rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .feature-title {
            margin: 0;
            font-size: 1.48rem;
            line-height: 1.22;
        }

        .feature-meta {
            margin: 0;
            color: var(--muted);
            font-size: 0.83rem;
        }

        .feature-excerpt {
            margin: 0;
            color: #294861;
            line-height: 1.6;
        }

        .feature-cta {
            width: fit-content;
            text-decoration: none;
            border: 0;
            border-radius: 11px;
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            padding: 10px 15px;
        }

        .grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px solid #cdddea;
            border-radius: 14px;
            overflow: hidden;
            background: var(--card);
            display: grid;
            min-height: 100%;
        }

        .card-media {
            display: block;
            height: 178px;
            background: linear-gradient(145deg, #e6eef6 0%, #d2e5f1 100%);
        }

        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .card-body {
            padding: 13px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .card-title {
            margin: 0;
            font-size: 1.03rem;
            line-height: 1.3;
        }

        .card-title a {
            text-decoration: none;
            color: #112f45;
        }

        .card-title a:hover {
            color: #0d5f7b;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .card-meta {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .card-excerpt {
            margin: 0;
            color: #34536a;
            font-size: 0.89rem;
            line-height: 1.55;
        }

        .empty {
            margin-top: 18px;
            border: 1px dashed #c6d8e6;
            border-radius: 13px;
            background: #f6fbff;
            padding: 16px;
            color: #49647a;
        }

        @media (max-width: 960px) {
            .feature {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar" aria-label="Blog navigation">
            <a class="brand" href="/">Workation Blog</a>
            <nav class="topnav" aria-label="Blog top links">
                <a href="/">Home</a>
                <a href="/things-to-do">Things to Do</a>
                <a href="/catalog/accommodation">Stays</a>
            </nav>
        </header>

        <section class="hero" aria-label="Blog heading">
            <p class="eyebrow">Travel Journal</p>
            <h1>Stories, guides, and hand-picked island ideas for your next Maldives escape.</h1>
            <p>Read curated itineraries, local insights, and practical tips from the Workation team.</p>
        </section>

        @if ($featuredPost)
            @php
                $featuredImageUrl = trim((string) \Illuminate\Support\Facades\Storage::disk('public')->url((string) ($featuredPost->cover_image_path ?? '')));
            @endphp
            <article class="feature" aria-label="Featured story">
                <div class="feature-media">
                    @if (trim((string) ($featuredPost->cover_image_path ?? '')) !== '')
                        <img src="{{ $featuredImageUrl }}" alt="{{ $featuredPost->title }} cover image" loading="lazy">
                    @endif
                </div>
                <div class="feature-body">
                    <span class="chip">Featured</span>
                    <h2 class="feature-title">{{ $featuredPost->title }}</h2>
                    <p class="feature-meta">Published {{ optional($featuredPost->published_at)->format('M d, Y') ?? optional($featuredPost->created_at)->format('M d, Y') }}</p>
                    <p class="feature-excerpt">{{ $featuredPost->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $featuredPost->content), 220) }}</p>
                    <a class="feature-cta" href="{{ '/blog/' . $featuredPost->slug }}">Read story</a>
                </div>
            </article>
        @endif

        @if ($posts->isEmpty())
            <div class="empty">No stories are published yet. Check back soon for guides and island inspiration.</div>
        @else
            <section class="grid" aria-label="Published blog posts">
                @foreach ($posts as $post)
                    @php
                        $coverPath = trim((string) ($post->cover_image_path ?? ''));
                        $coverUrl = $coverPath !== '' ? (string) \Illuminate\Support\Facades\Storage::disk('public')->url($coverPath) : '';
                    @endphp
                    <article class="card">
                        <a class="card-media" href="{{ '/blog/' . $post->slug }}" aria-label="{{ $post->title }}">
                            @if ($coverUrl !== '')
                                <img src="{{ $coverUrl }}" alt="{{ $post->title }} cover image" loading="lazy">
                            @endif
                        </a>
                        <div class="card-body">
                            <h3 class="card-title"><a href="{{ '/blog/' . $post->slug }}">{{ $post->title }}</a></h3>
                            <p class="card-meta">{{ optional($post->published_at)->format('M d, Y') ?? optional($post->created_at)->format('M d, Y') }}</p>
                            <p class="card-excerpt">{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 140) }}</p>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        @include('partials.global-site-footer')
    </main>
</body>
</html>
