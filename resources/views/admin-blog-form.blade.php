<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mode === 'edit' ? 'Edit Blog Post' : 'Create Blog Post' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
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

        .EasyMDEContainer {
            border: 1px solid #cbdbe8;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .EasyMDEContainer .CodeMirror {
            min-height: 360px;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: #1c3e56;
        }

        .EasyMDEContainer .editor-toolbar {
            border: 0;
            border-bottom: 1px solid #d8e6f0;
            background: #f8fbff;
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

        .btn.danger {
            border-color: #e2b4bb;
            background: #fff1f3;
            color: #982a35;
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
            $coverUrl = $coverPath !== '' ? blogResolveCoverImageUrl($coverPath) : '';
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
            $selectedTagInput = trim((string) old('blog_tag_input', ''));
            if ($selectedTagInput === '' && count($selectedTagSlugs) > 0) {
                $selectedTagInput = implode(', ', $selectedTagSlugs);
            }
            $articleImageRaw = $isEdit ? (array) ($post->article_images ?? []) : [];
            $articleImagePaths = [
                trim((string) ($articleImageRaw[0] ?? '')),
                trim((string) ($articleImageRaw[1] ?? '')),
                trim((string) ($articleImageRaw[2] ?? '')),
            ];
            $articleImageUrls = array_map(
                static fn (string $p) => $p !== '' ? blogResolveCoverImageUrl($p) : '',
                $articleImagePaths
            );

            foreach ($selectedTagSlugs as $selectedSlug) {
                if (!array_key_exists($selectedSlug, $blogTagOptions)) {
                    $blogTagOptions[$selectedSlug] = [
                        'label' => \Illuminate\Support\Str::headline(str_replace('-', ' ', $selectedSlug)),
                    ];
                }
            }
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
                    <p class="hint">Choose category manually to control where this article appears (Travel picks, Ocean Paths, Calm Escapes, Islands Guide).</p>
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
                    <input name="blog_tag_input" type="text" value="{{ $selectedTagInput }}" placeholder="Add custom tags separated by commas (for example: family travel, overwater villa, reef tips)">
                    <p class="hint">Tags are dynamic. Use presets or add custom tags separated by commas. If none selected, tags are inferred from content text.</p>
                </div>

                <div class="field wide">
                    <label for="blog_content">Content</label>
                    <textarea id="blog_content" name="content" required>{{ old('content', $isEdit ? $post->content : '') }}</textarea>
                    <p class="hint">Paragraph editor is enabled with formatting toolbar. Content is saved as markdown and supports: <code>## Heading</code>, <code>### Subheading</code>, <code>**bold**</code>, <code>*italic*</code>, <code>[Link](https://example.com)</code>, <code>![Caption](https://...)</code>, and <code>[image:/storage/path.jpg|Caption]</code>.</p>
                </div>

                    <div id="blog-inline-image-manager" style="display:none; margin-top:-8px;">
                        <p class="hint" style="font-weight:600; margin-bottom:6px;">Inline images uploaded this session — click Delete to remove from storage:</p>
                        <div id="blog-inline-images-list" style="display:flex; flex-wrap:wrap; gap:10px;"></div>
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
                                    <div class="field wide">
                                        <label>Article images (optional — up to 3)</label>
                                        <p class="hint">Upload up to 3 images. Each slot now shows a thumbnail as soon as you choose a file. Existing images can be removed, and choosing a new file replaces that slot on save.</p>
                                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px; margin-top:4px;">
                                            @foreach ([0, 1, 2] as $slot)
                                                @php
                                                    $slotUrl = $articleImageUrls[$slot] ?? '';
                                                    $slotHasExisting = $slotUrl !== '';
                                                    $slotResolvedUrl = ($slotHasExisting && $isEdit) ? ('/media/blog/' . $post->id . '/article/' . $slot) : '';
                                                @endphp
                                                <div data-article-image-slot style="border:1px solid #ccd8e4; border-radius:10px; padding:10px; background:#f8fbff; display:grid; gap:8px;">
                                                    <label style="font-size:0.82rem; font-weight:700; color:#4a6678; display:block;">Image {{ $slot + 1 }}</label>
                                                    <div data-article-image-preview-wrapper @if (!$slotHasExisting) hidden @endif style="border:1px solid #ccd8e4; border-radius:6px; overflow:hidden; background:#dfeaf2;">
                                                        <img
                                                            data-article-image-preview
                                                            src="{{ $slotUrl }}"
                                                            data-original-src="{{ $slotUrl }}"
                                                            alt="Article image {{ $slot + 1 }}"
                                                            loading="lazy"
                                                            style="display:block; width:100%; height:140px; object-fit:cover;"
                                                        >
                                                    </div>
                                                    <p class="hint" data-article-image-status style="margin:0; min-height:32px;">
                                                        @if ($slotHasExisting)
                                                            Current image loaded. Choose a new file below to replace it.
                                                        @else
                                                            No image selected for this slot yet.
                                                        @endif
                                                    </p>
                                                    <div data-article-image-url-row @if (!($slotHasExisting && $isEdit)) hidden @endif style="display:grid; gap:4px;">
                                                        <span style="font-size:0.72rem; font-weight:700; color:#4a6678; text-transform:uppercase; letter-spacing:.04em;">Image URL</span>
                                                        <div style="display:flex; gap:6px; align-items:center;">
                                                            <input
                                                                type="text"
                                                                data-article-image-url
                                                                readonly
                                                                value="{{ $slotResolvedUrl }}"
                                                                style="flex:1; font-size:0.75rem; font-family:monospace; padding:4px 8px; border:1px solid #ccd8e4; border-radius:6px; background:#eef4fa; color:#1a3a4f; cursor:text;"
                                                            >
                                                            <button type="button" class="btn-link" data-article-image-copy style="padding:4px 10px; font-size:0.75rem; white-space:nowrap;">Copy</button>
                                                        </div>
                                                    </div>
                                                    @if ($slotHasExisting)
                                                        <label class="check" data-article-image-remove-row style="font-size:0.78rem; margin:0;">
                                                            <input type="checkbox" name="remove_article_image_{{ $slot }}" value="1" @checked(old('remove_article_image_' . $slot) == '1') data-article-image-remove>
                                                            Remove current image {{ $slot + 1 }} on save
                                                        </label>
                                                    @endif
                                                    <input type="file" name="article_image_{{ $slot }}" accept="image/*" data-article-image-input style="font-size:0.82rem;">
                                                    <button type="button" class="btn-link" data-article-image-clear hidden style="width:max-content; padding:6px 10px;">Clear selected file</button>
                                                    @if ($isEdit)
                                                        <button
                                                            type="button"
                                                            class="btn-link"
                                                            data-article-image-save-now
                                                            data-post-id="{{ $post->id }}"
                                                            data-slot="{{ $slot }}"
                                                            disabled
                                                            style="width:max-content; padding:6px 10px; color:#0f6079;"
                                                        >Save image now</button>
                                                    @endif
                                                    @if ($slotHasExisting && $isEdit)
                                                        <button
                                                            type="button"
                                                            class="btn-link"
                                                            data-article-image-insert
                                                            data-insert-url="{{ $slotResolvedUrl }}"
                                                            data-insert-label="Gallery image {{ $slot + 1 }}"
                                                            style="width:max-content; padding:6px 10px; color:#1a6694;"
                                                        >↗ Insert into article</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @php
                                            $currentGalleryPos = old('gallery_position', $isEdit ? ($post->gallery_position ?? 'after_intro') : 'after_intro');
                                        @endphp
                                        <div style="margin-top:12px;">
                                            <label for="gallery_position" style="font-size:0.82rem; font-weight:700; color:#4a6678; display:block; margin-bottom:4px;">Gallery position</label>
                                            <select id="gallery_position" name="gallery_position" style="font-size:0.84rem; padding:6px 10px; border:1px solid #ccd8e4; border-radius:8px; background:#fff; color:#334a5e; width:100%; max-width:320px;">
                                                <option value="after_intro"      @selected($currentGalleryPos === 'after_intro')>After intro / before article body</option>
                                                <option value="after_first_h2"  @selected($currentGalleryPos === 'after_first_h2')>After first section heading (H2)</option>
                                                <option value="after_second_h2" @selected($currentGalleryPos === 'after_second_h2')>After second section heading (H2)</option>
                                                <option value="end"             @selected($currentGalleryPos === 'end')>At the end of the article</option>
                                            </select>
                                            <p class="hint" style="margin-top:4px;">Controls where the photo gallery appears inside the article on the reader-facing page.</p>
                                        </div>
                                    </div>

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

        @if ($isEdit)
            <form class="card" method="POST" action="{{ '/portal/admin/blog/' . $post->id . '/delete' }}" onsubmit="return confirm('Delete this blog post permanently?');">
                @csrf
                <p class="hint">Delete removes the article and its uploaded cover image.</p>
                <div class="actions">
                    <button class="btn danger" type="submit">Delete Post</button>
                </div>
            </form>
        @endif

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

        <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
        <script>
            (function () {
                const contentElement = document.getElementById('blog_content');
                if (!contentElement || typeof EasyMDE === 'undefined') {
                    return;
                }

                const editor = new EasyMDE({
                    element: contentElement,
                    autofocus: false,
                    spellChecker: false,
                    uploadImage: true,
                    autosave: {
                        enabled: false,
                    },
                    status: ['lines', 'words'],
                    minHeight: '360px',
                    placeholder: 'Write your story in paragraphs, headings, and rich text...',
                    toolbar: [
                        'heading-2',
                        'heading-3',
                        '|',
                        'bold',
                        'italic',
                        'strikethrough',
                        '|',
                        'quote',
                        'unordered-list',
                        'ordered-list',
                        '|',
                        'link',
                        'image',
                        '|',
                        'preview',
                        'side-by-side',
                        'fullscreen',
                        '|',
                        'guide',
                    ],
                    imageUploadFunction: function (file, onSuccess, onError) {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const formData = new FormData();
                        formData.append('image', file);

                        fetch('/portal/admin/blog/upload-image', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Upload failed (' + response.status + ')');
                                }

                                return response.json();
                            })
                            .then(function (payload) {
                                const imageUrl = String(payload?.url || '').trim();
                                if (!imageUrl) {
                                    throw new Error('Upload did not return an image URL.');
                                }

                                onSuccess(imageUrl);
                                    const storedPath = String(payload?.path || '').trim();
                                    if (storedPath) {
                                        addInlineImageToManager(imageUrl, storedPath);
                                    }
                            })
                            .catch(function (error) {
                                onError(error?.message || 'Unable to upload image.');
                            });
                    },
                });

                const form = contentElement.closest('form');
                if (form) {
                    form.addEventListener('submit', function () {
                        contentElement.value = editor.value();
                    });
                }

                    function addInlineImageToManager(imageUrl, storedPath) {
                        const manager = document.getElementById('blog-inline-image-manager');
                        const list    = document.getElementById('blog-inline-images-list');
                        if (!manager || !list) return;

                        manager.style.display = '';

                        const item = document.createElement('div');
                        item.style.cssText = 'position:relative;border:1px solid #ccc;border-radius:4px;overflow:hidden;width:90px;text-align:center;background:#f9f9f9;';
                        item.dataset.storedPath = storedPath;

                        const img = document.createElement('img');
                        img.src = imageUrl;
                        img.alt = '';
                        img.style.cssText = 'display:block;width:90px;height:70px;object-fit:cover;';

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = 'Delete';
                        btn.style.cssText = 'display:block;width:100%;background:#c53030;color:#fff;border:none;padding:3px 0;font-size:11px;cursor:pointer;';
                        btn.addEventListener('click', function () {
                            if (!confirm('Delete this image from storage?\n\nAny existing references to it in the content will become broken images.')) return;
                            deleteInlineImage(storedPath, item);
                        });

                        item.appendChild(img);
                        item.appendChild(btn);
                        list.appendChild(item);
                    }

                    function deleteInlineImage(storedPath, itemEl) {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        fetch('/portal/admin/blog/delete-inline-image', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ path: storedPath }),
                            credentials: 'same-origin',
                        })
                        .then(function (resp) {
                            if (resp.ok) {
                                itemEl.remove();
                                const list = document.getElementById('blog-inline-images-list');
                                const manager = document.getElementById('blog-inline-image-manager');
                                if (list && manager && list.children.length === 0) {
                                    manager.style.display = 'none';
                                }
                            } else {
                                alert('Could not delete the image. Please try again.');
                            }
                        })
                        .catch(function () {
                            alert('Network error while deleting. Please try again.');
                        });
                    }

                    document.querySelectorAll('[data-article-image-slot]').forEach(function (slotEl) {
                        const fileInput = slotEl.querySelector('[data-article-image-input]');
                        const previewWrapper = slotEl.querySelector('[data-article-image-preview-wrapper]');
                        const previewImage = slotEl.querySelector('[data-article-image-preview]');
                        const statusText = slotEl.querySelector('[data-article-image-status]');
                        const clearButton = slotEl.querySelector('[data-article-image-clear]');
                        const removeCheckbox = slotEl.querySelector('[data-article-image-remove]');
                        const insertButton = slotEl.querySelector('[data-article-image-insert]');
                        const saveNowButton = slotEl.querySelector('[data-article-image-save-now]');
                        const urlRow       = slotEl.querySelector('[data-article-image-url-row]');
                        const urlInput     = slotEl.querySelector('[data-article-image-url]');
                        const copyButton   = slotEl.querySelector('[data-article-image-copy]');
                        let originalSrc    = previewImage ? String(previewImage.getAttribute('data-original-src') || '').trim() : '';
                        let stableUrl      = urlInput ? String(urlInput.getAttribute('value') || '').trim() : '';

                        if (insertButton) {
                            insertButton.addEventListener('click', function () {
                                const url   = String(insertButton.getAttribute('data-insert-url')   || '').trim();
                                const label = String(insertButton.getAttribute('data-insert-label') || 'Gallery image').trim();
                                if (!url || typeof editor === 'undefined') return;
                                editor.codemirror.focus();
                                editor.codemirror.replaceSelection('![' + label + '](' + url + ')');
                            });
                        }

                        if (copyButton && urlInput) {
                            copyButton.addEventListener('click', function () {
                                const val = String(urlInput.value || '').trim();
                                if (!val) return;
                                navigator.clipboard.writeText(val).then(function () {
                                    const prev = copyButton.textContent;
                                    copyButton.textContent = 'Copied!';
                                    setTimeout(function () { copyButton.textContent = prev; }, 1500);
                                }).catch(function () {
                                    urlInput.select();
                                    document.execCommand('copy');
                                });
                            });
                        }

                        if (!fileInput || !previewWrapper || !statusText || !clearButton) {
                            return;
                        }

                        let currentObjectUrl = '';

                        function releaseObjectUrl() {
                            if (currentObjectUrl !== '') {
                                URL.revokeObjectURL(currentObjectUrl);
                                currentObjectUrl = '';
                            }
                        }

                        function updateSaveNowState() {
                            if (!saveNowButton) return;
                            const hasFile = !!(fileInput.files && fileInput.files[0]);
                            saveNowButton.disabled = !hasFile;
                        }

                        function restoreOriginalState() {
                            releaseObjectUrl();
                            fileInput.value = '';
                            clearButton.hidden = true;
                            updateSaveNowState();
                            if (removeCheckbox) {
                                removeCheckbox.disabled = false;
                            }

                            if (previewImage && originalSrc !== '') {
                                previewImage.src = originalSrc;
                                previewWrapper.hidden = false;
                                statusText.textContent = 'Current image loaded. Choose a new file below to replace it.';
                            } else {
                                if (previewImage) {
                                    previewImage.removeAttribute('src');
                                }
                                previewWrapper.hidden = true;
                                statusText.textContent = 'No image selected for this slot yet.';
                            }
                            if (urlRow && urlInput) {
                                if (stableUrl !== '') {
                                    urlInput.value = stableUrl;
                                    urlRow.hidden = false;
                                } else {
                                    urlInput.value = '';
                                    urlRow.hidden = true;
                                }
                            }
                        }

                        fileInput.addEventListener('change', function () {
                            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                            if (!file) {
                                restoreOriginalState();
                                return;
                            }

                            releaseObjectUrl();
                            currentObjectUrl = URL.createObjectURL(file);
                            updateSaveNowState();
                            if (previewImage) {
                                previewImage.src = currentObjectUrl;
                            }
                            previewWrapper.hidden = false;
                            clearButton.hidden = false;
                            if (removeCheckbox) {
                                removeCheckbox.checked = false;
                                removeCheckbox.disabled = true;
                            }
                            statusText.textContent = 'Selected: ' + file.name + '. Saving will replace this slot image.';
                            if (urlRow && urlInput) {
                                if (stableUrl !== '') {
                                    // replacing an existing image — URL stays the same after save
                                    urlInput.value = stableUrl;
                                    urlRow.hidden = false;
                                } else {
                                    // brand-new slot — URL only exists after saving
                                    urlInput.value = '(save the post to get the URL)';
                                    urlRow.hidden = false;
                                }
                            }
                        });

                        clearButton.addEventListener('click', function () {
                            restoreOriginalState();
                        });

                        if (saveNowButton) {
                            saveNowButton.addEventListener('click', function () {
                                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                                if (!file) {
                                    return;
                                }

                                const postId = String(saveNowButton.getAttribute('data-post-id') || '').trim();
                                const slot = String(saveNowButton.getAttribute('data-slot') || '').trim();
                                if (!postId || slot === '') {
                                    return;
                                }

                                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                                const formData = new FormData();
                                formData.append('image', file);

                                const previousLabel = saveNowButton.textContent || 'Save image now';
                                saveNowButton.textContent = 'Saving...';
                                saveNowButton.disabled = true;

                                fetch('/portal/admin/blog/' + encodeURIComponent(postId) + '/article/' + encodeURIComponent(slot) + '/upload', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': token,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: formData,
                                    credentials: 'same-origin',
                                })
                                .then(function (resp) {
                                    if (!resp.ok) {
                                        return resp.json().catch(function () {
                                            return { message: 'Could not save slot image.' };
                                        }).then(function (payload) {
                                            throw new Error(String(payload?.message || 'Could not save slot image.'));
                                        });
                                    }

                                    return resp.json();
                                })
                                .then(function (payload) {
                                    const savedUrl = String(payload?.url || '').trim();
                                    if (!savedUrl) {
                                        throw new Error('Image saved but URL is missing.');
                                    }

                                    releaseObjectUrl();
                                    fileInput.value = '';
                                    clearButton.hidden = true;
                                    updateSaveNowState();

                                    stableUrl = savedUrl;
                                    originalSrc = savedUrl;

                                    if (previewImage) {
                                        previewImage.src = savedUrl;
                                        previewImage.setAttribute('data-original-src', savedUrl);
                                    }
                                    previewWrapper.hidden = false;

                                    if (urlRow && urlInput) {
                                        urlInput.value = savedUrl;
                                        urlRow.hidden = false;
                                    }

                                    if (insertButton) {
                                        insertButton.setAttribute('data-insert-url', savedUrl);
                                        insertButton.hidden = false;
                                    }

                                    if (removeCheckbox) {
                                        removeCheckbox.checked = false;
                                        removeCheckbox.disabled = false;
                                    }

                                    statusText.textContent = 'Saved. URL is now ready to copy or insert into the article.';
                                })
                                .catch(function (error) {
                                    statusText.textContent = error?.message || 'Could not save image now. Please try again.';
                                })
                                .finally(function () {
                                    saveNowButton.textContent = previousLabel;
                                    updateSaveNowState();
                                });
                            });
                        }

                        updateSaveNowState();
                    });
            })();
        </script>
    </main>
</body>
</html>