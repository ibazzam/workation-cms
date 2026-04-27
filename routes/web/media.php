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

Route::get('/media/portal-public/{path}', function (string $path) {
    $cleanPath = ltrim(str_replace(['..', '\\'], '', $path), '/');
    if ($cleanPath === '') {
        abort(404);
    }

    $binary = null;
    $mime = '';

    try {
        $publicDisk = Storage::disk('public');
        if ($publicDisk->exists($cleanPath)) {
            $binary = $publicDisk->get($cleanPath);
            try {
                $mime = (string) ($publicDisk->mimeType($cleanPath) ?: '');
            } catch (\Throwable $e) {
                $mime = '';
            }
        }
    } catch (\Throwable $e) {
        $binary = null;
    }

    if ((!is_string($binary) || $binary === '') && Storage::disk('local')->exists('public/' . $cleanPath)) {
        $localPath = 'public/' . $cleanPath;
        $binary = Storage::disk('local')->get($localPath);
        try {
            $mime = (string) (Storage::disk('local')->mimeType($localPath) ?: '');
        } catch (\Throwable $e) {
            $mime = '';
        }
    }

    if (!is_string($binary) || $binary === '') {
        // For legacy/stale managed hero URLs, redirect to canonical hero proxy
        // so the browser does not keep hard-failing on a removed file path.
        if (preg_match('#^portal-admin/hero-images/([a-z0-9_-]+)/#i', $cleanPath, $m) === 1) {
            $slot = strtolower(trim((string) ($m[1] ?? '')));
            if ($slot !== '') {
                return redirect('/media/portal/hero/' . $slot, 302);
            }
        }

        abort(404);
    }

    return response($binary, 200, [
        'Content-Type' => $mime !== '' ? $mime : 'image/jpeg',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*');

Route::get('/media/portal/hero/{slot}', function (string $slot) {
    $placeholderResponse = static function () {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900"><defs><linearGradient id="gh" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#dce8ef"/><stop offset="100%" stop-color="#c5d7e3"/></linearGradient></defs><rect width="1600" height="900" fill="url(#gh)"/><text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#40607a" font-family="Arial" font-size="34">Hero image unavailable</text></svg>';
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    };

    $normalizedSlot = strtolower(trim((string) $slot));
    if (!preg_match('/^[a-z0-9_-]+$/', $normalizedSlot)) {
        return $placeholderResponse();
    }

    $storedValue = portalHeroStoredValueForSlot($normalizedSlot);

    if ($storedValue === '') {
        return $placeholderResponse();
    }

    if (Str::startsWith($storedValue, ['http://', 'https://'])) {
        try {
            $remoteResponse = Http::retry(1, 200)
                ->timeout(10)
                ->withHeaders([
                    'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'User-Agent' => 'WorkationHeroMediaProxy/1.0',
                ])
                ->get($storedValue);
            if ($remoteResponse->successful() && $remoteResponse->body() !== '') {
                return response($remoteResponse->body(), 200, [
                    'Content-Type' => trim((string) $remoteResponse->header('Content-Type', 'image/jpeg')),
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        } catch (\Throwable $e) {
            // fall through to placeholder
        }

        return $placeholderResponse();
    }

    // Paths stored with '__public__/' prefix were written to the local public disk
    // as a fallback when the managed (S3) disk was unavailable. Strip the prefix
    // FIRST so disk existence checks use the correct relative path.
    if (str_starts_with($storedValue, '__public__/')) {
        $relativePath = ltrim(substr($storedValue, strlen('__public__/')), '/');
    } else {
        $relativePath = portalManagedMediaRelativePath($storedValue);
    }

    if ($relativePath === null || $relativePath === '') {
        return $placeholderResponse();
    }

    $candidateRelativePaths = [$relativePath];
    if (str_starts_with($relativePath, 'blog/inline/')) {
        $candidateRelativePaths[] = ltrim(Str::after($relativePath, 'blog/inline/'), '/');
    } else {
        $candidateRelativePaths[] = 'blog/inline/' . ltrim($relativePath, '/');
    }
    $candidateRelativePaths = array_values(array_unique(array_filter($candidateRelativePaths, static fn ($path) => is_string($path) && trim($path) !== '')));

    // ETag derived from the stored path — unique per upload (path includes timestamp+random bytes).
    // Allows browsers to skip re-downloading the same image bytes without serving stale content
    // after an upload (new path → new ETag → cache miss → full download).
    $etag = '"' . md5($storedValue) . '"';
    $ifNoneMatch = request()->header('If-None-Match', '');
    if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
        return response('', 304, [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
        ]);
    }

    $assetCacheKey = 'portal-hero-asset:' . md5($storedValue);
    $cacheHeroAsset = static function (string $binary, string $mime) use ($assetCacheKey): void {
        if ($binary === '' || strlen($binary) > 2500000) {
            return;
        }

        try {
            cache()->put($assetCacheKey, [
                'binary' => $binary,
                'mime' => $mime,
            ], now()->addMinutes(10));
        } catch (\Throwable $e) {
            // ignore cache store failures; storage remains source of truth
        }
    };

    try {
        $cachedHeroAsset = cache()->get($assetCacheKey);
        if (is_array($cachedHeroAsset)) {
            $cachedBinary = (string) ($cachedHeroAsset['binary'] ?? '');
            $cachedMime = trim((string) ($cachedHeroAsset['mime'] ?? 'image/jpeg'));
            if ($cachedBinary !== '') {
                return response($cachedBinary, 200, [
                    'Content-Type' => $cachedMime !== '' ? $cachedMime : 'image/jpeg',
                    'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
                    'ETag' => $etag,
                ]);
            }
        }
    } catch (\Throwable $e) {
        // ignore cache read failures and continue to storage lookup
    }

    // Infer MIME from extension — avoids a second S3 HeadObject call per request.
    $inferMime = static function (string $path): string {
        return match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/jpeg',
        };
    };

    $portalDiskName = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($portalDiskName === '') {
        $portalDiskName = 'public';
    }
    $diskNames = array_values(array_unique(array_filter([$portalDiskName, 'public'])));

    foreach ($diskNames as $diskName) {
        try {
            $disk = Storage::disk($diskName);
            foreach ($candidateRelativePaths as $candidatePath) {
                // Skip exists() check (separate HeadObject) — get() returns null when absent.
                $binary = $disk->get($candidatePath);
                if (!is_string($binary) || $binary === '') {
                    continue;
                }

                $mime = $inferMime($candidatePath);
                $cacheHeroAsset($binary, $mime);
                return response($binary, 200, [
                    'Content-Type' => $mime,
                    'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
                    'ETag' => $etag,
                ]);
            }
        } catch (\Throwable $e) {
            continue;
        }
    }

    foreach ($candidateRelativePaths as $candidatePath) {
        $localPublicPath = 'public/' . ltrim($candidatePath, '/');
        if (!Storage::disk('local')->exists($localPublicPath)) {
            continue;
        }

        $binary = Storage::disk('local')->get($localPublicPath);
        if (is_string($binary) && $binary !== '') {
            $mime = $inferMime($localPublicPath);

            // Self-heal legacy fallback records: when a hero points to __public__/..., try
            // promoting the local file into managed storage and update the settings row.
            if (str_starts_with($storedValue, '__public__/') && $portalDiskName !== 'public' && Schema::hasTable('portal_finance_settings')) {
                try {
                    $managedDisk = Storage::disk($portalDiskName);
                    $candidatePaths = $candidateRelativePaths;

                    $writeOptions = [
                        ['ContentType' => $mime],
                        [],
                    ];

                    $promotedPath = null;
                    foreach ($candidatePaths as $candidatePath) {
                        foreach ($writeOptions as $options) {
                            try {
                                if ($managedDisk->put($candidatePath, $binary, $options)) {
                                    $promotedPath = $candidatePath;
                                    break 2;
                                }
                            } catch (\Throwable $e) {
                                // try next option/path
                            }
                        }
                    }

                    if (is_string($promotedPath) && $promotedPath !== '') {
                        $settingKey = $normalizedSlot === 'home'
                            ? 'home_hero_image_url'
                            : 'catalog_hero_image_' . str_replace('-', '_', $normalizedSlot);

                        DB::table('portal_finance_settings')->updateOrInsert(
                            ['setting_key' => $settingKey],
                            [
                                'value_string' => $promotedPath,
                                'updated_by_user_id' => is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        portalForgetHeroSlotCache($normalizedSlot);
                    }
                } catch (\Throwable $e) {
                    // keep serving local file even if promotion fails
                }
            }

            $cacheHeroAsset($binary, $mime);
            return response($binary, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
                'ETag' => $etag,
            ]);
        }
    }

    $managedUrl = portalManagedMediaUrlFromPath($storedValue);
    if (is_string($managedUrl) && trim($managedUrl) !== '' && !Str::startsWith($managedUrl, ['/media/'])) {
        return redirect()->away($managedUrl, 302);
    }

    return $placeholderResponse();
})->where('slot', '[A-Za-z0-9_-]+');

Route::get('/media/blog/{post}/cover', function (int $post) {
    $placeholderResponse = static function () {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="720" viewBox="0 0 1200 720">
    <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#dce8ef"/>
            <stop offset="100%" stop-color="#c5d7e3"/>
        </linearGradient>
    </defs>
    <rect width="1200" height="720" fill="url(#g)"/>
    <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#40607a" font-family="Arial" font-size="34">Blog image unavailable</text>
</svg>
SVG;

        return response($svg, 404, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    };

    if (!Schema::hasTable('blog_posts')) {
        return $placeholderResponse();
    }

    $coverColumns = ['id', 'cover_image_path'];
    if (Schema::hasColumn('blog_posts', 'cover_image_url')) {
        $coverColumns[] = 'cover_image_url';
    }

    $blogPost = BlogPost::query()->find($post, $coverColumns);
    if (!$blogPost) {
        return $placeholderResponse();
    }

    $coverSources = [];
    if (isset($blogPost->cover_image_url)) {
        $coverSources[] = trim(str_replace('\\', '/', (string) ($blogPost->cover_image_url ?? '')));
    }
    $coverSources[] = trim(str_replace('\\', '/', (string) ($blogPost->cover_image_path ?? '')));
    $coverSources = array_values(array_unique(array_filter($coverSources, static fn ($value) => $value !== '')));

    if ($coverSources === []) {
        return $placeholderResponse();
    }

    $candidatePaths = [];
    foreach ($coverSources as $source) {
        $candidatePaths = array_merge($candidatePaths, blogMediaCandidatePaths($source));
    }
    $candidatePaths = array_values(array_unique(array_filter($candidatePaths)));

    // Legacy records can hold extension-less or proxy-style cover values.
    // Probe the conventional cover filename on disk so mobile/desktop render consistently.
    $coverFallbackCandidates = [];
    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'] as $ext) {
        $coverFallbackCandidates[] = 'blog/' . $post . '/cover.' . $ext;
    }
    $candidatePaths = array_values(array_unique(array_filter(array_merge($candidatePaths, $coverFallbackCandidates))));

    $resolvedBinary = null;
    $resolvedMimeType = '';

    $blogCoverPortalDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($blogCoverPortalDisk === '') {
        $blogCoverPortalDisk = 'public';
    }
    $blogCoverDiskNames = array_values(array_unique(array_filter([$blogCoverPortalDisk, 'public'])));

    foreach ($blogCoverDiskNames as $diskName) {
        try {
            $candidateDisk = Storage::disk($diskName);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('blog_cover_proxy: disk init failed', ['disk' => $diskName, 'error' => $e->getMessage()]);
            continue;
        }
        foreach ($candidatePaths as $path) {
            try {
                $pathExists = $candidateDisk->exists($path);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('blog_cover_proxy: exists() failed', ['disk' => $diskName, 'path' => $path, 'error' => $e->getMessage()]);
                continue;
            }
            if (!$pathExists) {
                continue;
            }

            try {
                $resolvedBinary = $candidateDisk->get($path);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('blog_cover_proxy: get() failed', ['disk' => $diskName, 'path' => $path, 'error' => $e->getMessage()]);
                $resolvedBinary = null;
                continue;
            }

            try {
                $resolvedMimeType = (string) ($candidateDisk->mimeType($path) ?: '');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('blog_cover_proxy: mimeType() failed', ['disk' => $diskName, 'path' => $path, 'error' => $e->getMessage()]);
                $resolvedMimeType = '';
            }
            break 2;
        }
    }

    if (!is_string($resolvedBinary) || $resolvedBinary === '') {
        $localDisk = Storage::disk('local');
        foreach ($candidatePaths as $path) {
            foreach ([$path, 'public/' . ltrim($path, '/')] as $localPath) {
                if (!$localDisk->exists($localPath)) {
                    continue;
                }

                $resolvedBinary = $localDisk->get($localPath);
                try {
                    $resolvedMimeType = (string) ($localDisk->mimeType($localPath) ?: '');
                } catch (\Throwable $e) {
                    $resolvedMimeType = '';
                }
                break 2;
            }
        }
    }

    if (!is_string($resolvedBinary) || $resolvedBinary === '') {
        $postFolder = 'blog/' . $post;
        foreach ($blogCoverDiskNames as $diskName) {
            try {
                $disk = Storage::disk($diskName);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$disk->exists($postFolder)) {
                continue;
            }

            $files = (array) $disk->files($postFolder);
            $normalizedFiles = collect($files)
                ->map(static fn ($file) => trim((string) $file))
                ->filter(static fn ($file) => $file !== '')
                ->values();

            foreach ($normalizedFiles as $file) {
                $basename = Str::lower((string) basename($file));
                if (!Str::startsWith($basename, 'cover.')) {
                    continue;
                }

                if (!$disk->exists($file)) {
                    continue;
                }

                $resolvedBinary = $disk->get($file);
                try {
                    $resolvedMimeType = (string) ($disk->mimeType($file) ?: '');
                } catch (\Throwable $e) {
                    $resolvedMimeType = '';
                }
                break 2;
            }

            // Legacy fallback: some older records store arbitrary image names in blog/{postId}.
            // If no cover.* exists, serve the first image-like file to avoid total image loss.
            foreach ($normalizedFiles as $file) {
                $basename = Str::lower((string) basename($file));
                if (!preg_match('/\.(jpg|jpeg|png|webp|gif|avif)$/i', $basename)) {
                    continue;
                }

                if (!$disk->exists($file)) {
                    continue;
                }

                $resolvedBinary = $disk->get($file);
                try {
                    $resolvedMimeType = (string) ($disk->mimeType($file) ?: '');
                } catch (\Throwable $e) {
                    $resolvedMimeType = '';
                }
                break 2;
            }
        }
    }

    if (!is_string($resolvedBinary) || $resolvedBinary === '') {
        foreach ($coverSources as $source) {
            if (!Str::startsWith($source, ['http://', 'https://'])) {
                continue;
            }

            try {
                $remoteResponse = Http::retry(1, 200)
                    ->timeout(10)
                    ->withHeaders([
                        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                        'User-Agent' => 'WorkationBlogMediaProxy/1.0',
                    ])
                    ->get($source);
            } catch (\Throwable $exception) {
                continue;
            }

            if (!$remoteResponse->successful() || $remoteResponse->body() === '') {
                continue;
            }

            return response($remoteResponse->body(), 200, [
                'Content-Type' => trim((string) $remoteResponse->header('Content-Type', 'image/jpeg')),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    if (!is_string($resolvedBinary) || $resolvedBinary === '') {
        foreach ($candidatePaths as $candidatePath) {
            $managedUrl = portalManagedMediaUrlFromPath($candidatePath);
            if (is_string($managedUrl) && trim($managedUrl) !== '' && !Str::startsWith($managedUrl, ['/media/'])) {
                return redirect()->away($managedUrl, 302);
            }
        }

        foreach ($coverSources as $source) {
            $managedUrl = portalManagedMediaUrlFromPath($source);
            if (is_string($managedUrl) && trim($managedUrl) !== '' && !Str::startsWith($managedUrl, ['/media/'])) {
                return redirect()->away($managedUrl, 302);
            }
        }

        return $placeholderResponse();
    }

    return response($resolvedBinary, 200, [
        'Content-Type' => $resolvedMimeType !== '' ? $resolvedMimeType : 'image/jpeg',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
});

// Quick test endpoint to verify database/env values without any resolution
Route::get('/portal/admin/hero-test', function () {
    return response()->json([
        'env_HOME_HERO_IMAGE_URL' => env('HOME_HERO_IMAGE_URL', '(not set)'),
        'db_value' => Schema::hasTable('portal_finance_settings') 
            ? DB::table('portal_finance_settings')->where('setting_key', 'home_hero_image_url')->value('value_string')
            : '(no table)',
        'timestamp' => now()->toIso8601String(),
        'proxy_url' => '/media/portal/hero/home',
    ]);
});

Route::get('/portal/admin/s3-test', function () {
    $diskName = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($diskName === '') {
        $diskName = 'public';
    }

    $result = [
        'configured_disk' => $diskName,
        'aws_bucket' => env('AWS_BUCKET', '(not set)'),
        'aws_region' => env('AWS_DEFAULT_REGION', '(not set)'),
        'has_credentials' => (env('AWS_ACCESS_KEY_ID') && env('AWS_SECRET_ACCESS_KEY')) ? true : false,
        'app_config_cached' => app()->configurationIsCached(),
    ];

    if ($diskName === 's3') {
        try {
            $loadedS3Config = (array) config('filesystems.disks.s3', []);
            $result['loaded_s3_visibility'] = array_key_exists('visibility', $loadedS3Config)
                ? $loadedS3Config['visibility']
                : '(unset)';
            $result['loaded_s3_directory_visibility'] = array_key_exists('directory_visibility', $loadedS3Config)
                ? $loadedS3Config['directory_visibility']
                : '(unset)';
            $loadedOptions = (array) ($loadedS3Config['options'] ?? []);
            $result['loaded_s3_options_acl'] = $loadedOptions['ACL'] ?? ($loadedOptions['acl'] ?? '(unset)');

            $disk = Storage::disk('s3');
            $throwingDisk = null;
            try {
                $s3Config = (array) config('filesystems.disks.s3', []);
                $s3Config['throw'] = true;
                $throwingDisk = Storage::build($s3Config);
            } catch (\Throwable $e) {
                $result['throw_probe_init_error'] = $e->getMessage();
                $result['throw_probe_init_exception'] = get_class($e);
            }

            $testCases = [
                ['path' => 's3-test-' . now()->timestamp . '.txt', 'options' => []],
                ['path' => 'portal-admin/hero-images/home/s3-test-' . now()->timestamp . '.txt', 'options' => []],
                ['path' => 'portal-admin/hero-images/home/s3-test-' . now()->timestamp . '.jpg', 'options' => ['ContentType' => 'image/jpeg']],
                ['path' => 'blog/inline/portal-admin/hero-images/home/s3-test-' . now()->timestamp . '.jpg', 'options' => ['ContentType' => 'image/jpeg']],
            ];

            $writes = [];
            foreach ($testCases as $case) {
                $path = (string) ($case['path'] ?? '');
                $options = (array) ($case['options'] ?? []);
                try {
                    $ok = $disk->put($path, 's3 test payload', $options);
                    $row = [
                        'path' => $path,
                        'ok' => (bool) $ok,
                        'options' => $options,
                    ];

                    if (!$ok && $throwingDisk !== null) {
                        try {
                            $throwingDisk->put($path, 's3 test payload', $options);
                            $row['throw_probe_ok'] = true;
                        } catch (\Throwable $e) {
                            $row['throw_probe_ok'] = false;
                            $row['throw_probe_error'] = $e->getMessage();
                            $row['throw_probe_exception'] = get_class($e);
                        }
                    }

                    $writes[] = $row;
                    if ($ok) {
                        try {
                            $disk->delete($path);
                        } catch (\Throwable $e) {
                            // ignore cleanup failure in diagnostics
                        }
                    }
                } catch (\Throwable $e) {
                    $writes[] = [
                        'path' => $path,
                        'ok' => false,
                        'options' => $options,
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ];
                }
            }

            $result['write_tests'] = $writes;
            $result['write_status'] = collect($writes)->contains(fn ($row) => !empty($row['ok'])) ? 'PARTIAL_OR_SUCCESS' : 'FAILED';
        } catch (\Throwable $e) {
            $result['write_error'] = $e->getMessage();
            $result['exception'] = get_class($e);
        }
    }

    return response()->json($result);
});

// /portal/admin/blog/{post}/cover-debug — admin-only diagnostic for cover proxy failures
Route::get('/portal/admin/blog/{post}/cover-debug', function (int $post) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $coverColumns = ['id', 'cover_image_path'];
    if (Schema::hasColumn('blog_posts', 'cover_image_url')) {
        $coverColumns[] = 'cover_image_url';
    }

    $blogPost = BlogPost::query()->find($post, $coverColumns);
    if (!$blogPost) {
        return response()->json(['error' => 'Post not found', 'post_id' => $post]);
    }

    $portalDiskName = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($portalDiskName === '') {
        $portalDiskName = 'public';
    }

    $coverSources = [];
    if (isset($blogPost->cover_image_url)) {
        $v = trim(str_replace('\\', '/', (string) ($blogPost->cover_image_url ?? '')));
        if ($v !== '') { $coverSources[] = $v; }
    }
    $v = trim(str_replace('\\', '/', (string) ($blogPost->cover_image_path ?? '')));
    if ($v !== '') { $coverSources[] = $v; }
    $coverSources = array_values(array_unique($coverSources));

    $candidatePaths = [];
    foreach ($coverSources as $source) {
        $candidatePaths = array_merge($candidatePaths, blogMediaCandidatePaths($source));
    }
    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'] as $ext) {
        $candidatePaths[] = 'blog/' . $post . '/cover.' . $ext;
    }
    $candidatePaths = array_values(array_unique(array_filter($candidatePaths)));

    $diskNames = array_values(array_unique(array_filter([$portalDiskName, 'public', 'local'])));
    $diskResults = [];

    foreach ($diskNames as $diskName) {
        $diskResults[$diskName] = ['status' => 'unknown', 'paths_checked' => []];
        try {
            $disk = Storage::disk($diskName);
            $diskResults[$diskName]['status'] = 'disk_ok';
        } catch (\Throwable $e) {
            $diskResults[$diskName]['status'] = 'disk_init_failed: ' . $e->getMessage();
            continue;
        }
        foreach ($candidatePaths as $path) {
            try {
                $exists = $disk->exists($path);
                $diskResults[$diskName]['paths_checked'][$path] = $exists ? 'EXISTS' : 'not_found';
                if ($exists) {
                    try {
                        $size = $disk->size($path);
                        $diskResults[$diskName]['paths_checked'][$path] = 'EXISTS (size=' . $size . ')';
                    } catch (\Throwable $e) {
                        // ignore size error
                    }
                }
            } catch (\Throwable $e) {
                $diskResults[$diskName]['paths_checked'][$path] = 'error: ' . $e->getMessage();
            }
        }
        try {
            $postFolder = 'blog/' . $post;
            if ($disk->exists($postFolder)) {
                $files = $disk->files($postFolder);
                $diskResults[$diskName]['folder_files'] = $files;
            } else {
                $diskResults[$diskName]['folder_exists'] = false;
            }
        } catch (\Throwable $e) {
            $diskResults[$diskName]['folder_error'] = $e->getMessage();
        }
    }

    return response()->json([
        'post_id' => $post,
        'cover_image_path' => $blogPost->cover_image_path,
        'cover_image_url' => $blogPost->cover_image_url ?? '(no column)',
        'portal_media_disk_config' => $portalDiskName,
        'env_PORTAL_MEDIA_DISK' => env('PORTAL_MEDIA_DISK', '(not set)'),
        'env_VENDOR_MEDIA_DISK' => env('VENDOR_MEDIA_DISK', '(not set)'),
        'env_AWS_BUCKET' => env('AWS_BUCKET', '(not set)'),
        'env_AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION', '(not set)'),
        'env_AWS_ENDPOINT' => env('AWS_ENDPOINT', '(not set)'),
        'cover_sources' => $coverSources,
        'candidate_paths' => $candidatePaths,
        'disk_results' => $diskResults,
    ]);
});

// /media/blog/{post}/article/{slot} — proxy for per-post article gallery images (slot 0-2)
Route::get('/media/blog/{post}/article/{slot}', function (int $post, int $slot) {
    $placeholderResponse = static function () {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#d4e3ee"/><text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#607d8b" font-family="Arial" font-size="24">Image unavailable</text></svg>';
        return response($svg, 404, ['Content-Type' => 'image/svg+xml; charset=UTF-8', 'Cache-Control' => 'no-store']);
    };

    if ($slot < 0 || $slot > 2) {
        return $placeholderResponse();
    }

    if (!Schema::hasTable('blog_posts')) {
        return $placeholderResponse();
    }

    $blogPost = BlogPost::query()->find($post, ['id', 'article_images']);
    if (!$blogPost) {
        return $placeholderResponse();
    }

    $articleImages = (array) ($blogPost->article_images ?? []);
    $storedPath = trim((string) ($articleImages[$slot] ?? ''));
    if ($storedPath === '') {
        return $placeholderResponse();
    }

    $blogArticleDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($blogArticleDisk === '') {
        $blogArticleDisk = 'public';
    }
    $diskNames = array_values(array_unique(array_filter([$blogArticleDisk, 'public'])));

    $candidatePaths = blogMediaCandidatePaths($storedPath);

    foreach ($diskNames as $diskName) {
        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $e) {
            continue;
        }
        foreach ($candidatePaths as $candidatePath) {
            if (!$disk->exists($candidatePath)) {
                continue;
            }
            $binary = $disk->get($candidatePath);
            $mime = (string) ($disk->mimeType($candidatePath) ?: 'image/jpeg');
            return response($binary, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }
    }

    if (Str::startsWith($storedPath, ['http://', 'https://'])) {
        try {
            $remoteResponse = Http::retry(1, 200)->timeout(10)->get($storedPath);
        } catch (\Throwable $exception) {
            return $placeholderResponse();
        }

        if ($remoteResponse->successful() && $remoteResponse->body() !== '') {
            return response($remoteResponse->body(), 200, [
                'Content-Type' => trim((string) $remoteResponse->header('Content-Type', 'image/jpeg')),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    foreach ($candidatePaths as $candidatePath) {
        $managedUrl = portalManagedMediaUrlFromPath($candidatePath);
        if (is_string($managedUrl) && trim($managedUrl) !== '' && !Str::startsWith($managedUrl, ['/media/'])) {
            return redirect()->away($managedUrl, 302);
        }
    }

    $managedFromStoredPath = portalManagedMediaUrlFromPath($storedPath);
    if (is_string($managedFromStoredPath) && trim($managedFromStoredPath) !== '' && !Str::startsWith($managedFromStoredPath, ['/media/'])) {
        return redirect()->away($managedFromStoredPath, 302);
    }

    return $placeholderResponse();
});

// ─────────────────────────────────────────────────────────────
// Islands & Atolls Directory
    // Blog inline image proxy
    // /media/blog-inline/{path} — streams inline images uploaded via EasyMDE
    // ─────────────────────────────────────────────────────────────
    Route::get('/media/blog-inline/{path}', function (string $path) {
        // Normalise and guard against path traversal
        $cleanPath = ltrim(str_replace(['..', '\\'], '', $path), '/');
        if ($cleanPath === '') {
            abort(404);
        }

        $candidatePaths = [];
        if (Str::startsWith($cleanPath, 'blog/inline/')) {
            $candidatePaths[] = $cleanPath;
        } elseif (Str::startsWith($cleanPath, 'blog-inline/')) {
            $candidatePaths[] = 'blog/inline/' . ltrim(Str::after($cleanPath, 'blog-inline/'), '/');
            $candidatePaths[] = $cleanPath;
        } else {
            // Legacy payloads may store only date/file segments (e.g. 2026/04/file.jpg).
            $candidatePaths[] = 'blog/inline/' . ltrim($cleanPath, '/');
            $candidatePaths[] = $cleanPath;
        }
        $candidatePaths = array_values(array_unique(array_filter($candidatePaths, static fn ($v) => trim((string) $v) !== '')));

        $mediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
        if ($mediaDisk === '') {
            $mediaDisk = 'public';
        }

        $resolvedDisk = null;
        $resolvedPath = '';
        foreach (array_values(array_unique([$mediaDisk, 'public'])) as $candidateDiskName) {
            try {
                $candidateDisk = Storage::disk($candidateDiskName);
            } catch (\Throwable $exception) {
                continue;
            }

            foreach ($candidatePaths as $candidatePath) {
                if ($candidateDisk->exists($candidatePath)) {
                    $resolvedDisk = $candidateDisk;
                    $resolvedPath = $candidatePath;
                    break 2;
                }
            }
        }

        if ($resolvedDisk === null) {
            abort(404);
        }

        $mimeType = (string) ($resolvedDisk->mimeType($resolvedPath) ?: 'image/jpeg');

        return response()->stream(static function () use ($resolvedDisk, $resolvedPath) {
            $stream = $resolvedDisk->readStream($resolvedPath);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
                return;
            }

            // Fallback for drivers where readStream may return false unexpectedly.
            echo (string) $resolvedDisk->get($resolvedPath);
        }, 200, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    })->where('path', '.+');

    Route::post('/portal/admin/blog/delete-inline-image', function (Request $request) {
        if (!session()->get('portal_admin_authenticated', false)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!canManageContent()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $path = trim((string) ($request->input('path') ?? ''));
        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            return response()->json(['message' => 'Invalid path.'], 422);
        }

        $normalizedDeletePaths = [];
        if (Str::startsWith($path, 'blog/inline/')) {
            $normalizedDeletePaths[] = $path;
        } elseif (Str::startsWith($path, 'blog-inline/')) {
            $normalizedDeletePaths[] = 'blog/inline/' . ltrim(Str::after($path, 'blog-inline/'), '/');
            $normalizedDeletePaths[] = $path;
        } else {
            $normalizedDeletePaths[] = 'blog/inline/' . ltrim($path, '/');
            $normalizedDeletePaths[] = ltrim($path, '/');
        }
        $normalizedDeletePaths = array_values(array_unique(array_filter($normalizedDeletePaths, static fn ($v) => trim((string) $v) !== '')));

        $mediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
        if ($mediaDisk === '') {
            $mediaDisk = 'public';
        }

        $deleteAttempted = false;
        foreach (array_values(array_unique([$mediaDisk, 'public'])) as $candidateDiskName) {
            foreach ($normalizedDeletePaths as $candidatePath) {
                try {
                    Storage::disk($candidateDiskName)->delete($candidatePath);
                    $deleteAttempted = true;
                } catch (\Throwable $exception) {
                    continue;
                }
            }
        }

        if (!$deleteAttempted) {
            return response()->json(['message' => 'Delete failed.'], 500);
        }

        return response()->json(['deleted' => true]);
    });

require __DIR__ . '/islands.php';

require __DIR__ . '/catalog.php';

require __DIR__ . '/property.php';

require __DIR__ . '/room.php';

require __DIR__ . '/booking.php';
