<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'edit' ? 'Edit Blog Post' : 'Create Blog Post' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f4f8fb;
            --ink: #15283b;
            --muted: #5f788c;
            --line: #ccdce8;
            --card: #fff;
            --brand: #0f6079;
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
            padding: 18px 0 28px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--card);
            padding: 12px;
        }

        .title {
            margin: 0;
            color: var(--brand);
            font-size: 1.3rem;
        }

        .btn-link {
            text-decoration: none;
            border: 1px solid #cfdeea;
            border-radius: 10px;
            padding: 8px 12px;
            background: #f8fbff;
            color: #1d4560;
            font-size: 0.83rem;
            font-weight: 700;
        }

        .card {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--card);
            padding: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.wide {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.79rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #49657b;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        input[type="text"], select, textarea {
            border: 1px solid #cbdbe8;
            border-radius: 10px;
            padding: 10px 11px;
            font: inherit;
            color: #1c3e56;
            width: 100%;
            background: #fff;
        }

        textarea {
            min-height: 320px;
            resize: vertical;
            line-height: 1.6;
        }

        .checks {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .check {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.84rem;
            color: #24475f;
        }

        .actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid #cfdeea;
            border-radius: 10px;
            padding: 10px 13px;
            background: #f8fbff;
            color: #1b4360;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn.primary {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }

        .error {
            margin-top: 12px;
            border: 1px solid #efc4c9;
            border-radius: 11px;
            background: #fff0f2;
            color: #8f2b34;
            padding: 10px 12px;
        }

        .hint {
            margin: 0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .tag-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #c8d8e6;
            border-radius: 999px;
            background: #f8fbff;
            padding: 7px 11px;
            font-size: 0.82rem;
            color: #234760;
        }

        .cover-preview {
            margin-top: 8px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #d3e2ed;
            max-width: 360px;
        }

        .cover-preview img {
            width: 100%;
            display: block;
            object-fit: cover;
        }

        @media (max-width: 740px) {
            .grid { grid-template-columns: 1fr; }
            textarea { min-height: 260px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="top" aria-label="Blog form header">
            <h1 class="title">{{ $mode === 'edit' ? 'Edit Blog Post' : 'Create Blog Post' }}</h1>
            <a class="btn-link" href="/portal/admin/blog">Back to Blog Manager</a>
        </header>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @php
            $isEdit = $mode === 'edit' && $post;
            $formAction = $isEdit ? ('/portal/admin/blog/' . $post->id) : '/portal/admin/blog';
            $coverPath = $isEdit ? trim((string) ($post->cover_image_path ?? '')) : '';
            $coverUrl = '';
            if ($coverPath !== '') {
                $coverPath = str_replace('\\', '/', $coverPath);
                if (\Illuminate\Support\Str::startsWith($coverPath, ['storage/'])) {
                    $coverPath = '/' . ltrim($coverPath, '/');
                }
                if (\Illuminate\Support\Str::startsWith($coverPath, ['public/'])) {
                    $coverPath = (string) \Illuminate\Support\Str::after($coverPath, 'public/');
                }
                $coverUrl = \Illuminate\Support\Str::startsWith($coverPath, ['https://', 'http://', '//', '/'])
                    ? $coverPath
                    : (string) \Illuminate\Support\Facades\Storage::disk('public')->url($coverPath);
            }
            $portalRole = strtoupper((string) session('portal_admin_role', ''));
            $isMediaRole = $portalRole === 'ADMIN_MEDIA';
            $canReview = (bool) ($canEditorialReview ?? false);
            $editorialStatus = $isEdit ? strtolower(trim((string) ($post->editorial_status ?? 'draft'))) : 'draft';
            $blogCategoryOptions = is_array($blogCategories ?? null) ? $blogCategories : blogCategoryDefinitions();
            $blogTagOptions = is_array($blogTags ?? null) ? $blogTags : blogTagDefinitions();
            $selectedCategorySlug = trim((string) old('blog_category_slug', $isEdit ? ((string) ($post->blog_category_slug ?? '')) : ''));
            $selectedTagSlugs = collect(old('blog_tag_slugs', $isEdit ? ($post->blog_tag_slugs ?? []) : []))
                ->map(fn ($slug) => \Illuminate\Support\Str::slug((string) $slug))
                ->filter(fn ($slug) => $slug !== '')
                ->values()
                ->all();
        @endphp

        <form class="card" method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
            @csrf

            <div class="grid">
                <div class="field wide">
                    <label for="blog_title">Title</label>
                    <input id="blog_title" name="title" type="text" maxlength="180" required value="{{ old('title', $isEdit ? $post->title : '') }}">
                </div>

                <div class="field wide">
                    <label for="blog_excerpt">Excerpt (optional)</label>
                    <input id="blog_excerpt" name="excerpt" type="text" maxlength="420" value="{{ old('excerpt', $isEdit ? ($post->excerpt ?? '') : '') }}">
                </div>

                <div class="field">
                    <label for="blog_category_slug">Article Category</label>
                    <select id="blog_category_slug" name="blog_category_slug">
                        <option value="">Auto detect from content</option>
                        @foreach ($blogCategoryOptions as $slug => $meta)
                            <option value="{{ $slug }}" @selected($selectedCategorySlug === (string) $slug)>
                                {{ $meta['label'] ?? \Illuminate\Support\Str::headline((string) $slug) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="hint">Choose category manually to control where this article appears (Trip Ideas, Blue Trails, Sleep + Slow, Island Atlas).</p>
                </div>

                <div class="field">
                    <label>Article Tags</label>
                    <div class="tag-picker">
                        @foreach ($blogTagOptions as $slug => $meta)
                            <label class="tag-pill">
                                <input type="checkbox" name="blog_tag_slugs[]" value="{{ $slug }}" @checked(in_array((string) $slug, $selectedTagSlugs, true))>
                                {{ $meta['label'] ?? \Illuminate\Support\Str::headline((string) $slug) }}
                            </label>
                        @endforeach
                    </div>
                    <p class="hint">Tags drive discovery pages and tag filters. If none selected, tags are inferred from content text.</p>
                </div>

                <div class="field wide">
                    <label for="blog_content">Content</label>
                    <textarea id="blog_content" name="content" required>{{ old('content', $isEdit ? $post->content : '') }}</textarea>
                    <p class="hint">Use plain text with line breaks. For mid-article images use <code>![Caption](https://...)</code> or <code>[image:/storage/path.jpg]</code>. Use <code>## Heading</code> for section titles.</p>
                </div>

                <div class="field">
                    <label for="blog_cover">Cover image (optional)</label>
                    <input id="blog_cover" name="cover_image" type="file" accept="image/*">
                    <p class="hint">Upload from your device. Supported: JPG, PNG, WEBP. The system stores and serves this image in blog index and category cards.</p>

                    @if ($coverUrl !== '')
                        <div class="cover-preview">
                            <img src="{{ $coverUrl }}" alt="Current cover image" loading="lazy">
                        </div>
                        <label class="check" style="margin-top:8px;">
                            <input type="checkbox" name="remove_cover_image" value="1" @checked(old('remove_cover_image') == '1')>
                            Remove current cover image
                        </label>
                    @endif
                </div>

                <div class="field">
                    <label>Publishing</label>
                    <div class="checks">
                        @if (!$isMediaRole)
                            <label class="check">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" @checked((string) old('is_published', $isEdit && (bool) ($post->is_published ?? false) ? '1' : '0') === '1')>
                                Published
                            </label>
                        @else
                            <input type="hidden" name="is_published" value="0">
                            <span class="check">Editorial Status: {{ strtoupper(str_replace('_', ' ', $editorialStatus)) }}</span>
                        @endif
                        <label class="check">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" @checked((string) old('is_featured', $isEdit && (bool) ($post->is_featured ?? false) ? '1' : '0') === '1')>
                            Featured post
                        </label>
                    </div>
                </div>

                @if ($isMediaRole)
                    <div class="field wide">
                        <p class="hint">As ADMIN_MEDIA, saving this post submits it for super-admin editorial review before publishing.</p>
                    </div>
                @endif
            </div>

            <div class="actions">
                <a class="btn-link" href="/portal/admin/blog">Cancel</a>
                <button class="btn primary" type="submit">{{ $isEdit ? 'Save Changes' : 'Create Post' }}</button>
            </div>
        </form>

        @if ($isEdit && $canReview)
            <form class="card" method="POST" action="{{ '/portal/admin/blog/' . $post->id . '/review' }}">
                @csrf
                <div class="grid">
                    <div class="field wide">
                        <label for="editorial_notes">Editorial Notes</label>
                        <textarea id="editorial_notes" name="editorial_notes" rows="5">{{ old('editorial_notes', (string) ($post->editorial_notes ?? '')) }}</textarea>
                        <p class="hint">These notes are visible to content authors and should explain approval or rejection reasons.</p>
                    </div>
                    <div class="field">
                        <label>Editorial Decision</label>
                        <div class="checks">
                            <label class="check">
                                <input type="radio" name="decision" value="approve" required>
                                Approve + Publish
                            </label>
                            <label class="check">
                                <input type="radio" name="decision" value="reject" required>
                                Reject
                            </label>
                        </div>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn primary" type="submit">Submit Editorial Review</button>
                </div>
            </form>
        @endif
    </main>
</body>
</html>