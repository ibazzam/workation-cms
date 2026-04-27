<?php

use App\Models\User;
use App\Models\BlogPost;
use App\Support\CheckoutPaymentRouter;
use App\Support\ReservationPricingPolicy;
use App\Support\ReservationSettlementCalculator;
use App\Support\UniformIconSystem;
use App\Support\VendorPropertyCompatibilityReader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

if (!function_exists('blogCategoryDefinitions')) {
    function blogCategoryDefinitions(): array
    {
        return [
            'things-to-do' => ['label' => 'Travel picks'],
            'attractions' => ['label' => 'Ocean Paths'],
            'stay' => ['label' => 'Calm Escapes'],
            'islands' => ['label' => 'Islands Guide'],
        ];
    }
}

if (!function_exists('blogTagDefinitions')) {
    function blogTagDefinitions(): array
    {
        $definitions = [
            'snorkeling' => ['label' => 'Snorkeling', 'keywords' => ['snorkel', 'snorkeling', 'reef']],
            'scuba-diving' => ['label' => 'Scuba Diving', 'keywords' => ['scuba', 'diving', 'dive']],
            'nature-and-outdoors' => ['label' => 'Nature and outdoors', 'keywords' => ['nature', 'outdoor', 'mangrove', 'biosphere']],
            'beach' => ['label' => 'Beach', 'keywords' => ['beach', 'shore', 'coast']],
            'island' => ['label' => 'Island', 'keywords' => ['island', 'atoll']],
            'hotel' => ['label' => 'Hotel', 'keywords' => ['hotel', 'resort', 'guesthouse', 'villa', 'stay']],
            'culture' => ['label' => 'Culture', 'keywords' => ['culture', 'local', 'eid', 'heritage']],
            'wildlife' => ['label' => 'Wildlife', 'keywords' => ['wildlife', 'shark', 'manta', 'whale', 'fish']],
            'excursion' => ['label' => 'Excursion', 'keywords' => ['excursion', 'trip', 'tour', 'adventure']],
        ];

        // Merge existing stored tags so tag navigation stays dynamic and not limited to presets.
        if (Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'blog_tag_slugs')) {
            $storedTagValues = DB::table('blog_posts')
                ->whereNotNull('blog_tag_slugs')
                ->limit(500)
                ->pluck('blog_tag_slugs');

            foreach ($storedTagValues as $storedTagValue) {
                foreach (blogNormalizeTagSlugs($storedTagValue) as $storedSlug) {
                    if (!array_key_exists($storedSlug, $definitions)) {
                        $definitions[$storedSlug] = [
                            'label' => Str::headline(str_replace('-', ' ', $storedSlug)),
                            'keywords' => [],
                        ];
                    }
                }
            }
        }

        return $definitions;
    }
}

if (!function_exists('blogBuildTagSlugsFromInput')) {
    function blogBuildTagSlugsFromInput(array $validated): array
    {
        $checkboxTags = blogNormalizeTagSlugs($validated['blog_tag_slugs'] ?? []);
        $typedTags = blogNormalizeTagSlugs($validated['blog_tag_input'] ?? '');

        return collect(array_merge($checkboxTags, $typedTags))
            ->map(fn (string $slug): string => Str::slug($slug))
            ->filter(fn (string $slug): bool => $slug !== '')
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('blogNormalizeTagSlugs')) {
    function blogNormalizeTagSlugs($value): array
    {
        $candidate = $value;
        if (is_string($candidate)) {
            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $candidate = $decoded;
            } else {
                $candidate = preg_split('/[\s,]+/', $candidate) ?: [];
            }
        }

        if (!is_array($candidate)) {
            return [];
        }

        return collect($candidate)
            ->map(fn ($slug) => Str::slug((string) $slug))
            ->filter(fn (string $slug) => $slug !== '')
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('blogInferCategorySlug')) {
    function blogInferCategorySlug($post): string
    {
        $haystack = Str::lower(trim(implode(' ', [
            (string) ($post->title ?? ''),
            (string) ($post->excerpt ?? ''),
            (string) Str::limit(strip_tags((string) ($post->content ?? '')), 400),
        ])));

        if ($haystack !== '') {
            if (Str::contains($haystack, ['hotel', 'resort', 'guesthouse', 'villa', 'stay', 'accommodation'])) {
                return 'stay';
            }

            if (Str::contains($haystack, ['reef', 'snorkel', 'dive', 'diving', 'shark', 'manta', 'whale', 'mangrove', 'biosphere', 'nature', 'excursion'])) {
                return 'attractions';
            }

            if (Str::contains($haystack, ['island', 'atoll', 'islets'])) {
                return 'islands';
            }
        }

        return 'things-to-do';
    }
}

if (!function_exists('blogInferTagSlugs')) {
    function blogInferTagSlugs($post): array
    {
        $tagDefinitions = blogTagDefinitions();
        $haystack = Str::lower(trim(implode(' ', [
            (string) ($post->title ?? ''),
            (string) ($post->excerpt ?? ''),
            (string) Str::limit(strip_tags((string) ($post->content ?? '')), 650),
        ])));

        $matchedTags = [];
        foreach ($tagDefinitions as $slug => $meta) {
            $keywords = is_array($meta['keywords'] ?? null) ? $meta['keywords'] : [];
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && Str::contains($haystack, Str::lower((string) $keyword))) {
                    $matchedTags[] = $slug;
                    break;
                }
            }
        }

        $matchedTags = array_values(array_unique($matchedTags));
        if (count($matchedTags) === 0) {
            $fallback = blogInferCategorySlug($post) === 'stay' ? 'hotel' : 'nature-and-outdoors';
            $matchedTags[] = $fallback;
        }

        return array_slice($matchedTags, 0, 4);
    }
}

if (!function_exists('blogHydratePostsWithMeta')) {
    function blogHydratePostsWithMeta($posts)
    {
        $categories = blogCategoryDefinitions();
        $tagDefinitions = blogTagDefinitions();
        $validCategorySlugs = array_keys($categories);
        $validTagSlugs = array_keys($tagDefinitions);

        return $posts->map(function ($post) use ($categories, $tagDefinitions) {
            $explicitCategorySlug = trim(Str::lower((string) ($post->blog_category_slug ?? '')));
            $explicitCategorySlug = str_replace('_', '-', $explicitCategorySlug);
            if ($explicitCategorySlug === 'things-to-do' || $explicitCategorySlug === 'things-to-dos') {
                $explicitCategorySlug = 'things-to-do';
            }
            $categorySlug = in_array($explicitCategorySlug, array_keys($categories), true)
                ? $explicitCategorySlug
                : blogInferCategorySlug($post);

            $explicitTagSlugs = blogNormalizeTagSlugs($post->blog_tag_slugs ?? []);
            $tagSlugs = collect($explicitTagSlugs)->values()->all();
            if (count($tagSlugs) === 0) {
                $tagSlugs = blogInferTagSlugs($post);
            }

            $rawCoverSource = trim((string) ($post->cover_image_url ?? ''));
            if ($rawCoverSource === '') {
                $rawCoverSource = trim((string) ($post->cover_image_path ?? ''));
            }
            $post->cover_image_url = blogResolveCoverImageUrl($rawCoverSource);

            $post->blog_category_slug = $categorySlug;
            $post->blog_category_label = (string) ($categories[$categorySlug]['label'] ?? 'Things to Do');
            $post->blog_tag_slugs = $tagSlugs;
            $post->blog_tags = collect($tagSlugs)
                ->map(function (string $slug) use ($tagDefinitions): array {
                    return [
                        'slug' => $slug,
                        'label' => (string) ($tagDefinitions[$slug]['label'] ?? Str::headline(str_replace('-', ' ', $slug))),
                    ];
                })
                ->values()
                ->all();

            return $post;
        })->values();
    }
}

if (!function_exists('queryPublishedBlogPosts')) {
    function queryPublishedBlogPosts()
    {
        return BlogPost::query()
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }
}

if (!function_exists('buildBlogIndexPayload')) {
    function buildBlogIndexPayload(?string $activeCategory = null, ?string $activeTag = null): array
    {
        $activeCategory = is_string($activeCategory) ? trim(Str::lower($activeCategory)) : null;
        $activeTag = is_string($activeTag) ? trim(Str::lower($activeTag)) : null;
        $categories = blogCategoryDefinitions();
        $tagDefinitions = blogTagDefinitions();

        if ($activeCategory !== null && !array_key_exists($activeCategory, $categories)) {
            abort(404);
        }

        $posts = collect();
        if (Schema::hasTable('blog_posts')) {
            $posts = queryPublishedBlogPosts()->limit(80)->get();
            $posts = blogHydratePostsWithMeta($posts);
        }

        if ($activeCategory !== null) {
            $posts = $posts->filter(function ($post) use ($activeCategory) {
                return (string) ($post->blog_category_slug ?? '') === $activeCategory;
            })->values();
        }

        if ($activeTag !== null) {
            $posts = $posts->filter(function ($post) use ($activeTag) {
                $tagSlugs = is_array($post->blog_tag_slugs ?? null) ? $post->blog_tag_slugs : [];

                return in_array($activeTag, $tagSlugs, true);
            })->values();
        }

        $featuredPost = $posts->first(function ($post) {
            return (bool) ($post->is_featured ?? false);
        }) ?? $posts->first();

        $featuredPosts = $posts
            ->filter(static function ($post): bool {
                return (bool) ($post->is_featured ?? false);
            })
            ->values();

        if ($featuredPosts->isEmpty()) {
            $featuredPosts = $posts->take(5)->values();
        }

        $categoryStoryPosts = collect(array_keys($categories))
            ->map(function (string $slug) use ($posts) {
                return $posts->first(function ($post) use ($slug) {
                    return (string) ($post->blog_category_slug ?? '') === $slug;
                });
            })
            ->filter(static function ($post): bool {
                return $post !== null;
            })
            ->values();

        if ($categoryStoryPosts->isEmpty()) {
            $categoryStoryPosts = $posts->take(4)->values();
        }

        $tagStats = [];
        foreach ($posts as $post) {
            $tags = is_array($post->blog_tags ?? null) ? $post->blog_tags : [];
            foreach ($tags as $tag) {
                $tagSlug = (string) ($tag['slug'] ?? '');
                if ($tagSlug === '') {
                    continue;
                }

                if (!array_key_exists($tagSlug, $tagStats)) {
                    $tagStats[$tagSlug] = [
                        'slug' => $tagSlug,
                        'label' => (string) ($tag['label'] ?? Str::headline(str_replace('-', ' ', $tagSlug))),
                        'count' => 0,
                    ];
                }
                $tagStats[$tagSlug]['count']++;
            }
        }

        usort($tagStats, function (array $a, array $b): int {
            if ($a['count'] === $b['count']) {
                return strcmp((string) $a['label'], (string) $b['label']);
            }

            return $b['count'] <=> $a['count'];
        });

        $activeTagLabel = null;
        if ($activeTag !== null) {
            foreach ($tagStats as $entry) {
                if ((string) ($entry['slug'] ?? '') === $activeTag) {
                    $activeTagLabel = (string) ($entry['label'] ?? '');
                    break;
                }
            }

            if ($activeTagLabel === null || $activeTagLabel === '') {
                $activeTagLabel = (string) ($tagDefinitions[$activeTag]['label'] ?? Str::headline(str_replace('-', ' ', $activeTag)));
            }
        }

        return [
            'apiBase' => workationApiBase(),
            'posts' => $posts,
            'featuredPost' => $featuredPost,
            'featuredPosts' => $featuredPosts,
            'categoryStoryPosts' => $categoryStoryPosts,
            'blogCategories' => $categories,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
            'activeTagLabel' => $activeTagLabel,
            'tagDirectory' => $tagStats,
        ];
    }
}

if (!function_exists('blogSidebarAdSettings')) {
    function blogSidebarAdSettings(): array
    {
        $defaults = [
            'title' => 'Charter a vessel?',
            'brand' => 'workation',
            'cta_label' => 'Explore now',
            'cta_url' => '/catalog/marine-transport',
            'image_url' => '',
        ];

        if (!Schema::hasTable('portal_finance_settings')) {
            return $defaults;
        }

        $settings = DB::table('portal_finance_settings')
            ->whereIn('setting_key', [
                'blog_sidebar_ad_title',
                'blog_sidebar_ad_brand',
                'blog_sidebar_ad_cta_label',
                'blog_sidebar_ad_cta_url',
                'blog_sidebar_ad_image',
            ])
            ->pluck('value_string', 'setting_key');

        $title = trim((string) ($settings->get('blog_sidebar_ad_title') ?? $defaults['title']));
        $brand = trim((string) ($settings->get('blog_sidebar_ad_brand') ?? $defaults['brand']));
        $ctaLabel = trim((string) ($settings->get('blog_sidebar_ad_cta_label') ?? $defaults['cta_label']));
        $ctaUrl = trim((string) ($settings->get('blog_sidebar_ad_cta_url') ?? $defaults['cta_url']));
        $imageStoredValue = trim((string) ($settings->get('blog_sidebar_ad_image') ?? ''));

        return [
            'title' => $title !== '' ? $title : $defaults['title'],
            'brand' => $brand !== '' ? $brand : $defaults['brand'],
            'cta_label' => $ctaLabel !== '' ? $ctaLabel : $defaults['cta_label'],
            'cta_url' => $ctaUrl !== '' ? $ctaUrl : $defaults['cta_url'],
            'image_url' => portalManagedMediaUrlFromPath($imageStoredValue) ?? '',
        ];
    }
}

// Blog article listing pages share the same template because categories and individual tag lists are visually aligned.
Route::get('/blog', function () {
    return view('blog-index', buildBlogIndexPayload());
});

Route::get('/blog/category/{category}', function (string $category) {
    return view('blog-index', buildBlogIndexPayload($category, null));
});

Route::get('/blog/tag/{tag}', function (string $tag) {
    return view('blog-index', buildBlogIndexPayload(null, $tag));
});

// The tag directory overview page has its own dedicated layout.
Route::get('/blog/tags', function () {
    $payload = buildBlogIndexPayload();

    return view('blog-tags', [
        'apiBase' => $payload['apiBase'] ?? workationApiBase(),
        'tagDirectory' => $payload['tagDirectory'] ?? [],
        'blogCategories' => $payload['blogCategories'] ?? blogCategoryDefinitions(),
    ]);
});

Route::get('/blog/{slug}', function (string $slug) {
    if (!Schema::hasTable('blog_posts')) {
        abort(404);
    }

    $post = BlogPost::query()
        ->where('slug', $slug)
        ->where('is_published', true)
        ->where(function ($query) {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        })
        ->firstOrFail();

    $relatedPosts = BlogPost::query()
        ->where('id', '!=', (int) $post->id)
        ->where('is_published', true)
        ->where(function ($query) {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        })
        ->orderByDesc('published_at')
        ->orderByDesc('created_at')
        ->limit(3)
        ->get();

    $post = blogHydratePostsWithMeta(collect([$post]))->first();
    $relatedPosts = blogHydratePostsWithMeta($relatedPosts);

    $articleImages = collect(array_filter((array) ($post->article_images ?? [])))
        ->map(static fn ($path) => blogResolveCoverImageUrl((string) $path))
        ->filter(static fn ($url) => $url !== '')
        ->values();

    return view('blog-show', [
        'apiBase' => workationApiBase(),
        'post' => $post,
        'relatedPosts' => $relatedPosts,
        'blogCategories' => blogCategoryDefinitions(),
        'blogSidebarAd' => blogSidebarAdSettings(),
        'articleImages' => $articleImages,
    ]);
});