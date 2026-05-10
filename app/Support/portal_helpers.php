<?php

use App\Models\User;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (!function_exists('workationApiBase')) {
    function workationApiBase(): string
    {
        return rtrim((string) env('WORKATION_API_BASE_URL', 'https://api.workation.mv'), '/');
    }
}

if (!function_exists('portalConfig')) {
    function portalConfig(string $portal): array
    {
        if ($portal === 'admin') {
            return [
                'session_key' => 'portal_admin_authenticated',
                'name' => 'Admin',
                'allowed_roles' => ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE'],
            ];
        }

        return [
            'session_key' => 'portal_vendor_authenticated',
            'name' => 'Vendor',
            'allowed_roles' => ['VENDOR'],
        ];
    }
}

if (!function_exists('portalRoutePath')) {
    function portalRoutePath(string $portal): string
    {
        return $portal === 'admin' ? adminPortalEntryPath() : '/vendor';
    }
}

if (!function_exists('adminPortalEntryPath')) {
    function adminPortalEntryPath(?string $page = null): string
    {
        $configuredPath = firstNonEmptyEnv([
            'PORTAL_ADMIN_ENTRY_PATH',
            'WORKATION_ADMIN_ENTRY_PATH',
            'ADMIN_ENTRY_PATH',
        ]);

        $slug = trim($configuredPath, " \t\n\r\0\x0B/");
        if ($slug === '') {
            $slug = 'ops-console-3k9m2q7x';
        }

        $basePath = '/' . $slug;
        $normalizedPage = strtolower(trim((string) ($page ?? '')));

        return $normalizedPage === '' ? $basePath : ($basePath . '/' . rawurlencode($normalizedPage));
    }
}

if (!function_exists('vendorPortalCategoryMap')) {
    function vendorPortalCategoryMap(): array
    {
        return [
            'accommodation' => 'Accommodation',
            'sea_transport' => 'Sea Transport & Ferries',
            'land_transport' => 'Land Transport',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspaces',
            'resort_day_visit' => 'Resort Day Visits',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
            'water_sports' => 'Water Sports',
            'conference_room' => 'Conference Rooms',
            'liveaboard' => 'Liveaboard / Safari',
        ];
    }
}

if (!function_exists('firstNonEmptyEnv')) {
    function firstNonEmptyEnv(array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) env($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('bootstrapPasswordMatches')) {
    function bootstrapPasswordMatches(string $expected, string $provided): bool
    {
        if ($expected === '') {
            return false;
        }

        $isHash = str_starts_with($expected, '$2y$') || str_starts_with($expected, '$argon2');
        if ($isHash) {
            return Hash::check($provided, $expected);
        }

        return hash_equals($expected, $provided);
    }
}

if (!function_exists('normalizePortalRoleValue')) {
    function normalizePortalRoleValue(string $role): string
    {
        $normalized = strtoupper(trim($role));
        return $normalized === 'ADMIN_FINACE' ? 'ADMIN_FINANCE' : $normalized;
    }
}

if (!function_exists('generatePortalUsernameFromEmail')) {
    function generatePortalUsernameFromEmail(string $email): string
    {
        $baseUsername = \Illuminate\Support\Str::of(strtolower((string) \Illuminate\Support\Str::before($email, '@')))
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->value();

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }
}

if (!function_exists('generateUniqueBlogSlug')) {
    function generateUniqueBlogSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug(trim($title));
        if ($baseSlug === '') {
            $baseSlug = 'post';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (true) {
            $query = BlogPost::query()->where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }
}

if (!function_exists('blogResolveCoverImageUrl')) {
    if (!function_exists('blogMediaCandidatePaths')) {
        function blogMediaCandidatePaths(?string $storedValue): array
        {
            $value = trim(str_replace('\\', '/', (string) ($storedValue ?? '')));
            if ($value === '') {
                return [];
            }

            $normalizeDiskPath = static function (string $path): string {
                $normalized = trim(str_replace('\\', '/', $path));
                if ($normalized === '') {
                    return '';
                }

                if (preg_match('#/storage/app/public/(.+)$#i', $normalized, $matches) === 1) {
                    $normalized = (string) ($matches[1] ?? '');
                } elseif (preg_match('#/public/storage/(.+)$#i', $normalized, $matches) === 1) {
                    $normalized = (string) ($matches[1] ?? '');
                } elseif (preg_match('~/(blog/(?:inline/[^?#\s]+|\d+/(?:cover|article_[0-2])(?:\.[^/?#]+)?))~i', $normalized, $matches) === 1) {
                    $normalized = (string) ($matches[1] ?? '');
                }

                $normalized = ltrim($normalized, '/');
                if (Str::startsWith($normalized, 'public/')) {
                    $normalized = Str::after($normalized, 'public/');
                }
                if (Str::startsWith($normalized, 'storage/')) {
                    $normalized = Str::after($normalized, 'storage/');
                }

                return ltrim($normalized, '/');
            };

            $paths = [$value, $normalizeDiskPath($value)];

            if (Str::startsWith($value, ['http://', 'https://'])) {
                $urlPath = trim((string) parse_url($value, PHP_URL_PATH));
                if ($urlPath !== '') {
                    $paths[] = $urlPath;
                    $paths[] = $normalizeDiskPath($urlPath);
                }
            }

            return collect($paths)
                ->map(static fn ($path) => trim((string) $path))
                ->filter(static fn ($path) => $path !== '')
                ->unique()
                ->values()
                ->all();
        }
    }

    function blogResolveCoverImageUrl(?string $coverImagePath): string
    {
        $value = str_replace('\\', '/', trim((string) $coverImagePath));
        if ($value === '') {
            return '';
        }

        // Normalize any inline blog image path to proxy route.
        // Handles values like:
        // - blog/inline/2026/..jpg
        // - /storage/blog/inline/..jpg
        // - https://.../blog/inline/..jpg
        if (preg_match('~(?:^|/)(blog/inline/[^?#\s]+)~i', $value, $inlineMatch) === 1) {
            return '/media/blog-inline/' . ltrim((string) ($inlineMatch[1] ?? ''), '/');
        }

        if (in_array(Str::lower($value), ['null', 'undefined', 'false'], true)) {
            return '';
        }
        if ((Str::startsWith($value, '"') && Str::endsWith($value, '"')) || (Str::startsWith($value, "'") && Str::endsWith($value, "'"))) {
            $value = trim($value, "\"'");
        }
        if (Str::startsWith($value, '[') && Str::endsWith($value, ']')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0])) {
                $value = trim($decoded[0]);
            }
        }
        if ($value === '') {
            return '';
        }

        // Our own proxy URLs — serve as-is
        if (Str::startsWith($value, '/media/')) {
            return $value;
        }

        // Rewrite legacy direct S3 inline-image URLs to go through our proxy
        // e.g. https://bucket.s3.region.amazonaws.com/blog/inline/… → /media/blog-inline/blog/inline/…
        $s3BucketName = trim((string) (config('filesystems.disks.s3.bucket') ?? ''));
        if (
            $s3BucketName !== '' &&
            Str::startsWith($value, ['https://', 'http://']) &&
            str_contains($value, $s3BucketName) &&
            preg_match('~/blog/inline/[^?#\s]+~', $value, $inlineMatch) === 1
        ) {
            return '/media/blog-inline' . $inlineMatch[0];
        }

        // Rewrite cover image paths to stable proxy URLs.
        // Do not return direct/signed S3 links for blog media, because they can expire.
        if (preg_match('~(?:^|/)(blog/(\d+)/cover\.[a-z0-9]+)(?:[?#].*)?$~i', $value, $matches) === 1) {
            $postId = (int) ($matches[2] ?? 0);
            if ($postId > 0) {
                return '/media/blog/' . $postId . '/cover';
            }
        }

        // Rewrite article gallery image paths to stable proxy URLs.
        // Do not return direct/signed S3 links for blog media, because they can expire.
        if (preg_match('~(?:^|/)(blog/(\d+)/article_([0-2])\.[a-z0-9]+)(?:[?#].*)?$~i', $value, $matches) === 1) {
            $postId = (int) ($matches[2] ?? 0);
            $slot   = (int) ($matches[3] ?? 0);
            if ($postId > 0) {
                return '/media/blog/' . $postId . '/article/' . $slot;
            }
        }

        // All other external URLs — normalize to https where possible.
        if (Str::startsWith($value, ['https://', 'http://', '//'])) {
            if (Str::startsWith($value, 'http://')) {
                return 'https://' . ltrim(substr($value, 7), '/');
            }

            if (Str::startsWith($value, '//')) {
                return 'https:' . $value;
            }

            return $value;
        }

        $portalMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
        if ($portalMediaDisk === '') {
            $portalMediaDisk = 'public';
        }
        $diskNames = array_values(array_unique(array_filter([$portalMediaDisk, 'public'])));

        $portalMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
        if ($portalMediaDisk === '') {
            $portalMediaDisk = 'public';
        }
        $diskNames = array_values(array_unique(array_filter([$portalMediaDisk, 'public'])));

        if (preg_match('#/storage/app/public/(.+)$#i', $value, $matches) === 1) {
            $value = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $value, $matches) === 1) {
            $value = (string) ($matches[1] ?? '');
        }

        if (Str::startsWith($value, ['/storage/'])) {
            $storageRelative = ltrim(Str::after($value, '/storage/'), '/');
            if ($storageRelative !== '') {
                foreach ($diskNames as $diskName) {
                    try {
                        $disk = Storage::disk($diskName);
                    } catch (\Throwable $exception) {
                        continue;
                    }

                    if ($disk->exists($storageRelative)) {
                        return (string) $disk->url($storageRelative);
                    }
                }

                return '/storage/' . $storageRelative;
            }
        }

        $value = ltrim($value, '/');

        if (Str::startsWith($value, ['storage/'])) {
            $storageRelative = ltrim(Str::after($value, 'storage/'), '/');
            if ($storageRelative !== '') {
                foreach ($diskNames as $diskName) {
                    try {
                        $disk = Storage::disk($diskName);
                    } catch (\Throwable $exception) {
                        continue;
                    }

                    if ($disk->exists($storageRelative)) {
                        return (string) $disk->url($storageRelative);
                    }
                }

                return '/storage/' . $storageRelative;
            }
        }

        if (Str::startsWith($value, ['public/'])) {
            $value = Str::after($value, 'public/');
        }

        if (Str::startsWith($value, ['blog/'])) {
            $relativePath = ltrim($value, '/');

            if (preg_match('~^blog/(\d+)/cover\.[a-z0-9]+$~i', $relativePath, $matches) === 1) {
                $postId = (int) ($matches[1] ?? 0);
                if ($postId > 0) {
                    return '/media/blog/' . $postId . '/cover';
                }
            }

            if (preg_match('~^blog/(\d+)/article_([0-2])\.[a-z0-9]+$~i', $relativePath, $matches) === 1) {
                $postId = (int) ($matches[1] ?? 0);
                $slot = (int) ($matches[2] ?? 0);
                if ($postId > 0) {
                    return '/media/blog/' . $postId . '/article/' . $slot;
                }
            }

            foreach ($diskNames as $diskName) {
                try {
                    $disk = Storage::disk($diskName);
                } catch (\Throwable $exception) {
                    continue;
                }

                if ($disk->exists($relativePath)) {
                    return (string) $disk->url($relativePath);
                }
            }

            return '';
        }

        $relativePath = ltrim($value, '/');
        if ($relativePath === '') {
            return '';
        }

        foreach ($diskNames as $diskName) {
            try {
                $disk = Storage::disk($diskName);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($disk->exists($relativePath)) {
                return (string) $disk->url($relativePath);
            }
        }

        return '';
    }
}

if (!function_exists('blogRenderInlineMarkup')) {
    function blogRenderInlineMarkup(?string $text): string
    {
        $value = trim((string) $text);
        if ($value === '') {
            return '';
        }

        $value = e($value);

        // Render markdown image syntax within regular paragraph blocks.
        $value = preg_replace_callback('/!\[(.*?)\]\(((?:https?:\/\/|\/|storage\/|blog\/|media\/)[^\s\)]+)(?:\s+"[^"]*")?\)/i', static function (array $matches): string {
            $alt = trim((string) ($matches[1] ?? ''));
            $source = trim((string) ($matches[2] ?? ''));
            if ($source === '') {
                return $matches[0];
            }

            $resolved = blogResolveCoverImageUrl($source);
            if ($resolved === '') {
                return $matches[0];
            }

            return '<img class="inline-image" src="' . e($resolved) . '" alt="' . e($alt) . '" loading="lazy">';
        }, $value) ?? $value;

        $value = preg_replace_callback('/\[image:\s*([^\]|]+?)(?:\s*\|\s*(.+?))?\]/i', static function (array $matches): string {
            $source = trim((string) ($matches[1] ?? ''));
            $alt = trim((string) ($matches[2] ?? ''));
            if ($source === '') {
                return $matches[0];
            }

            $resolved = blogResolveCoverImageUrl($source);
            if ($resolved === '') {
                return $matches[0];
            }

            return '<img class="inline-image" src="' . e($resolved) . '" alt="' . e($alt) . '" loading="lazy">';
        }, $value) ?? $value;

        $value = preg_replace_callback('/\[(.+?)\]\((https?:\/\/[^\s\)]+|\/[^\s\)]+)\)/', static function (array $matches): string {
            $label = trim((string) ($matches[1] ?? ''));
            $href = trim((string) ($matches[2] ?? ''));
            if ($label === '' || $href === '') {
                return $matches[0];
            }

            $safeHref = e($href);
            $isExternal = Str::startsWith($href, ['http://', 'https://']);

            return '<a href="' . $safeHref . '"' . ($isExternal ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $label . '</a>';
        }, $value) ?? $value;

        $value = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $value) ?? $value;
        $value = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $value) ?? $value;
        $value = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $value) ?? $value;
        $value = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $value) ?? $value;

        return nl2br($value, false);
    }
}

if (!function_exists('blogBuildRenderableContentBlocks')) {
    function blogBuildRenderableContentBlocks(?string $content, ?string $fallbackAlt = null): array
    {
        $rawContent = trim((string) $content);
        if ($rawContent === '') {
            return [];
        }

        $normalizeImageUrl = static function (string $candidate): string {
            $value = trim($candidate);
            if ($value === '') {
                return '';
            }

            return blogResolveCoverImageUrl($value);
        };

        $rawBlocks = preg_split('/\R{2,}/', $rawContent) ?: [];
        $blocks = [];
        foreach ($rawBlocks as $block) {
            $trimmed = trim((string) $block);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^!\[(.*?)\]\(((?:https?:\/\/|\/|storage\/|blog\/|media\/)[^\s\)]+)(?:\s+"[^"]*")?\)$/i', $trimmed, $matches) === 1) {
                $imageUrl = $normalizeImageUrl((string) ($matches[2] ?? ''));
                if ($imageUrl !== '') {
                    $blocks[] = [
                        'type' => 'image',
                        'alt' => trim((string) ($matches[1] ?? '')),
                        'url' => $imageUrl,
                    ];
                    continue;
                }
            }

            if (preg_match('/^\[image:\s*([^\]|]+?)(?:\s*\|\s*(.+?))?\]$/i', $trimmed, $matches) === 1) {
                $imageUrl = $normalizeImageUrl((string) ($matches[1] ?? ''));
                if ($imageUrl !== '') {
                    $blocks[] = [
                        'type' => 'image',
                        'alt' => trim((string) ($matches[2] ?? $fallbackAlt ?? '')),
                        'url' => $imageUrl,
                    ];
                    continue;
                }
            }

            if (preg_match('/^####\s+(.+)$/', $trimmed, $matches) === 1) {
                $blocks[] = ['type' => 'h4', 'text' => (string) ($matches[1] ?? '')];
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $trimmed, $matches) === 1) {
                $blocks[] = ['type' => 'h3', 'text' => (string) ($matches[1] ?? '')];
                continue;
            }

            if (preg_match('/^#{1,2}\s+(.+)$/', $trimmed, $matches) === 1) {
                $blocks[] = ['type' => 'h2', 'text' => (string) ($matches[1] ?? '')];
                continue;
            }

            $blocks[] = ['type' => 'p', 'text' => $trimmed];
        }

        return $blocks;
    }
}

if (!function_exists('supportedVendorSocialProviders')) {
    function supportedVendorSocialProviders(): array
    {
        return ['google', 'facebook', 'apple'];
    }
}

if (!function_exists('vendorSocialRedirectUrl')) {
    function vendorSocialRedirectUrl(string $provider): string
    {
        return (string) config('services.' . $provider . '.redirect', url('/portal/vendor/oauth/' . $provider . '/callback'));
    }
}

if (!function_exists('isVendorSocialProviderConfigured')) {
    function isVendorSocialProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google' => trim((string) config('services.google.client_id', '')) !== ''
                && trim((string) config('services.google.client_secret', '')) !== '',
            'facebook' => trim((string) config('services.facebook.client_id', '')) !== ''
                && trim((string) config('services.facebook.client_secret', '')) !== '',
            'apple' => trim((string) config('services.apple.client_id', '')) !== ''
                && trim((string) config('services.apple.team_id', '')) !== ''
                && trim((string) config('services.apple.key_id', '')) !== ''
                && trim((string) config('services.apple.private_key', '')) !== '',
            default => false,
        };
    }
}

if (!function_exists('vendorSocialHealthSnapshot')) {
    function vendorSocialHealthSnapshot(): array
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        $providers = [];
        foreach (supportedVendorSocialProviders() as $provider) {
            $redirect = vendorSocialRedirectUrl($provider);
            $redirectHost = strtolower((string) parse_url($redirect, PHP_URL_HOST));

            $providers[$provider] = [
                'configured' => isVendorSocialProviderConfigured($provider),
                'redirect' => $redirect,
                'redirect_uses_https' => str_starts_with(strtolower($redirect), 'https://'),
                'redirect_host_matches_app' => $appHost !== '' && $redirectHost === $appHost,
            ];
        }

        return [
            'ok' => true,
            'app_url' => $appUrl,
            'providers' => $providers,
        ];
    }
}

if (!function_exists('vendorEmailOtpCacheKey')) {
    function vendorEmailOtpCacheKey(string $email): string
    {
        return 'vendor_email_otp:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('vendorNormalizePhoneNumber')) {
    function vendorNormalizePhoneNumber(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        $hasPlusPrefix = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return '';
        }

        return $hasPlusPrefix ? '+' . $digits : $digits;
    }
}

if (!function_exists('vendorResolveOtpIdentifier')) {
    function vendorResolveOtpIdentifier(string $identifier): array
    {
        $value = trim($identifier);
        if ($value === '') {
            return [
                'channel' => 'invalid',
                'normalized' => '',
            ];
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return [
                'channel' => 'email',
                'normalized' => strtolower($value),
            ];
        }

        $normalizedPhone = vendorNormalizePhoneNumber($value);
        $phoneDigits = preg_replace('/\D+/', '', $normalizedPhone) ?? '';
        if (strlen($phoneDigits) >= 7 && strlen($phoneDigits) <= 15) {
            return [
                'channel' => 'phone',
                'normalized' => $normalizedPhone,
            ];
        }

        return [
            'channel' => 'invalid',
            'normalized' => '',
        ];
    }
}

if (!function_exists('vendorOtpCacheKeyForIdentifier')) {
    function vendorOtpCacheKeyForIdentifier(string $channel, string $normalized): string
    {
        return 'vendor_otp:' . $channel . ':' . sha1($normalized);
    }
}

if (!function_exists('vendorDeliverOtpCode')) {
    function vendorDeliverOtpCode(string $channel, string $destination, string $otpCode): void
    {
        if ($channel === 'email') {
            Mail::raw(
                "Your Workation vendor verification code is {$otpCode}. This code expires in 10 minutes.",
                function ($message) use ($destination): void {
                    $message->to($destination)->subject('Your Workation verification code');
                }
            );
            return;
        }

        if ($channel !== 'phone') {
            throw new \RuntimeException('Unsupported OTP delivery channel.');
        }

        $twilioSid = trim((string) env('TWILIO_ACCOUNT_SID', ''));
        $twilioToken = trim((string) env('TWILIO_AUTH_TOKEN', ''));
        $twilioFrom = trim((string) env('TWILIO_FROM_NUMBER', ''));
        $twilioWhatsappFrom = trim((string) env('TWILIO_WHATSAPP_FROM', ''));
        $twilioWhatsappContentSid = trim((string) env('TWILIO_WHATSAPP_CONTENT_SID', ''));
        $phoneChannel = strtolower(trim((string) env('TWILIO_PHONE_CHANNEL', 'sms')));
        $useWhatsApp = in_array($phoneChannel, ['whatsapp', 'wa', 'auto'], true);
        $normalizePhoneE164 = static function (string $value): string {
            $candidate = trim($value);
            $candidate = str_starts_with($candidate, 'whatsapp:')
                ? substr($candidate, strlen('whatsapp:'))
                : $candidate;

            $digitsOnly = preg_replace('/\D+/', '', $candidate) ?? '';
            if ($digitsOnly === '') {
                return '';
            }

            return '+' . $digitsOnly;
        };

        if ($twilioSid === '' || $twilioToken === '') {
            throw new \RuntimeException('Phone OTP is not configured. Missing TWILIO_ACCOUNT_SID or TWILIO_AUTH_TOKEN.');
        }

        if ($useWhatsApp) {

            if ($twilioWhatsappFrom === '' && $phoneChannel !== 'auto') {
                throw new \RuntimeException('WhatsApp OTP is enabled but TWILIO_WHATSAPP_FROM is missing.');
            }

            $normalizeWhatsAppAddress = static function (string $value): string {
                $candidate = trim($value);
                $candidate = str_starts_with($candidate, 'whatsapp:')
                    ? substr($candidate, strlen('whatsapp:'))
                    : $candidate;

                $digitsOnly = preg_replace('/\D+/', '', $candidate) ?? '';
                if ($digitsOnly === '') {
                    return '';
                }

                return 'whatsapp:+' . $digitsOnly;
            };

            $whatsAppTo = $normalizeWhatsAppAddress($destination);
            $whatsAppFrom = $normalizeWhatsAppAddress($twilioWhatsappFrom);
            if ($whatsAppTo !== '' && $whatsAppFrom !== '') {

                $payload = [
                    'From' => $whatsAppFrom,
                    'To' => $whatsAppTo,
                ];

                $baseEndpoint = 'https://api.twilio.com/2010-04-01/Accounts/' . $twilioSid . '/Messages.json';

                $templatePayload = $payload;
                if ($twilioWhatsappContentSid !== '') {
                    $templatePayload['ContentSid'] = $twilioWhatsappContentSid;
                    $templatePayload['ContentVariables'] = json_encode(['1' => $otpCode]);
                } else {
                    $templatePayload['Body'] = 'Your Workation vendor verification code is ' . $otpCode . '. It expires in 10 minutes.';
                }

                $waResponse = Http::withBasicAuth($twilioSid, $twilioToken)
                    ->asForm()
                    ->post($baseEndpoint, $templatePayload);

                if (!$waResponse->successful() && $twilioWhatsappContentSid !== '') {
                    // If template-based send fails (e.g., sandbox/template mismatch), try plain body once.
                    $fallbackPayload = $payload;
                    $fallbackPayload['Body'] = 'Your Workation vendor verification code is ' . $otpCode . '. It expires in 10 minutes.';
                    $waFallbackResponse = Http::withBasicAuth($twilioSid, $twilioToken)
                        ->asForm()
                        ->post($baseEndpoint, $fallbackPayload);

                    if ($waFallbackResponse->successful()) {
                        return;
                    }

                    $fallbackJson = $waFallbackResponse->json();
                    $fallbackMessage = is_array($fallbackJson) ? (string) ($fallbackJson['message'] ?? '') : '';
                    $fallbackCode = is_array($fallbackJson) ? (string) ($fallbackJson['code'] ?? '') : '';
                    throw new \RuntimeException(
                        'WhatsApp OTP delivery failed with status ' . $waFallbackResponse->status()
                        . ($fallbackCode !== '' ? ' (code ' . $fallbackCode . ')' : '')
                        . ($fallbackMessage !== '' ? ': ' . $fallbackMessage : '.')
                    );
                }

                if ($waResponse->successful()) {
                    return;
                }
            }

            // If WhatsApp is selected but cannot send (or in auto mode), attempt SMS fallback if configured.
            if ($twilioFrom !== '') {
                $smsTo = $normalizePhoneE164($destination);
                if ($smsTo !== '') {
                    $smsFallbackResponse = Http::withBasicAuth($twilioSid, $twilioToken)
                        ->asForm()
                        ->post('https://api.twilio.com/2010-04-01/Accounts/' . $twilioSid . '/Messages.json', [
                            'From' => $twilioFrom,
                            'To' => $smsTo,
                            'Body' => 'Your Workation vendor verification code is ' . $otpCode . '. It expires in 10 minutes.',
                        ]);

                    if ($smsFallbackResponse->successful()) {
                        return;
                    }

                    $smsFallbackJson = $smsFallbackResponse->json();
                    $smsFallbackMessage = is_array($smsFallbackJson) ? (string) ($smsFallbackJson['message'] ?? '') : '';
                    $smsFallbackCode = is_array($smsFallbackJson) ? (string) ($smsFallbackJson['code'] ?? '') : '';
                    throw new \RuntimeException(
                        'Phone OTP delivery failed on both WhatsApp and SMS. Last status ' . $smsFallbackResponse->status()
                        . ($smsFallbackCode !== '' ? ' (code ' . $smsFallbackCode . ')' : '')
                        . ($smsFallbackMessage !== '' ? ': ' . $smsFallbackMessage : '.')
                    );
                }
            }

            throw new \RuntimeException('WhatsApp OTP delivery failed and SMS fallback is not configured.');
        }

        if ($twilioFrom === '') {
            throw new \RuntimeException('SMS OTP is enabled but TWILIO_FROM_NUMBER is missing.');
        }

        $smsResponse = Http::withBasicAuth($twilioSid, $twilioToken)
            ->asForm()
            ->post('https://api.twilio.com/2010-04-01/Accounts/' . $twilioSid . '/Messages.json', [
                'From' => $twilioFrom,
                'To' => $destination,
                'Body' => 'Your Workation vendor verification code is ' . $otpCode . '. It expires in 10 minutes.',
            ]);

        if (!$smsResponse->successful()) {
            throw new \RuntimeException('Phone OTP delivery failed with status ' . $smsResponse->status() . '.');
        }
    }
}

if (!function_exists('workationMessageContentFilter')) {
    /**
     * Inspect a user-supplied message for forbidden content (phone numbers —
     * including those spelled out alphabetically — email addresses, off-platform
     * payment instructions, and contact-redirect phrases).
     *
     * Returns an array:
     *   blocked  => bool   – true if the message must be rejected
     *   reason   => string – human-readable explanation (empty when not blocked)
     *   pattern  => string – short tag of the matched rule (for logging)
     */
    function workationMessageContentFilter(string $text): array
    {
        $lower = mb_strtolower($text, 'UTF-8');

        // ── 1. Raw numeric phone patterns ─────────────────────────────────────
        // Matches 7-12 consecutive digits, possibly separated by spaces, dashes or dots.
        if (preg_match('/\b\d[\d\s\-\.]{5,11}\d\b/', $text)) {
            return ['blocked' => true, 'reason' => 'Sharing phone numbers is not allowed on this platform. Please keep all communication within Workation.', 'pattern' => 'numeric_phone'];
        }

        // ── 2. International format (+960 777 1234 etc.) ──────────────────────
        if (preg_match('/\+\d{1,4}[\s\-]?\d{3,}/', $text)) {
            return ['blocked' => true, 'reason' => 'Sharing phone numbers is not allowed on this platform. Please keep all communication within Workation.', 'pattern' => 'intl_phone'];
        }

        // ── 3. Alphabetically spelled-out digits ──────────────────────────────
        // Replace English number words with their digit equivalents, then check
        // whether any run of ≥ 7 adjacent digits forms (i.e. a phone number).
        $wordMap = [
            'zero' => '0', 'one' => '1', 'two' => '2', 'three' => '3',
            'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7',
            'eight' => '8', 'nine' => '9',
        ];
        $digitized = preg_replace_callback(
            '/\b(zero|one|two|three|four|five|six|seven|eight|nine)\b/i',
            static fn ($m) => $wordMap[strtolower($m[1])],
            $lower
        );
        // Collapse digit tokens that are only separated by whitespace or punctuation
        $collapsed = preg_replace('/(\d)[\s\-\.\/\\\\,]+(\d)/', '$1$2', $digitized ?? '');
        $collapsed = preg_replace('/(\d)[\s\-\.\/\\\\,]+(\d)/', '$1$2', $collapsed ?? '');
        if (preg_match('/\d{7,12}/', $collapsed ?? '')) {
            return ['blocked' => true, 'reason' => 'Sharing phone numbers (including those spelled out in words) is not allowed on this platform.', 'pattern' => 'spelled_phone'];
        }

        // ── 4. Email addresses ────────────────────────────────────────────────
        if (preg_match('/[a-z0-9._%+\-]+\s*@\s*[a-z0-9.\-]+\s*\.\s*[a-z]{2,}/i', $text)) {
            return ['blocked' => true, 'reason' => 'Sharing email addresses is not allowed on this platform. Please keep all communication within Workation.', 'pattern' => 'email_address'];
        }
        foreach (['gmail', 'yahoo mail', 'hotmail', 'outlook.com', 'icloud.com', 'protonmail', 'at the rate', 'dot com', 'dot net', 'dot mv'] as $signal) {
            if (str_contains($lower, $signal)) {
                return ['blocked' => true, 'reason' => 'Your message contains references to external contact information which is not allowed.', 'pattern' => 'email_signal'];
            }
        }

        // ── 5. Off-platform payment instructions ─────────────────────────────
        foreach ([
            'pay me', 'send me money', 'send money', 'bank transfer', 'bank account',
            'account number', 'bml account', 'mib account', 'iban', 'transfer money',
            'wire transfer', 'pay outside', 'pay directly', 'pay cash', 'cash payment',
            'pay in cash', 'personal account', 'my account',
        ] as $signal) {
            if (str_contains($lower, $signal)) {
                return ['blocked' => true, 'reason' => 'Messages requesting off-platform payment are not allowed. All payments must go through Workation.', 'pattern' => 'payment_redirect'];
            }
        }

        // ── 6. Contact-redirect phrases ───────────────────────────────────────
        foreach ([
            'my number is', 'my mobile', 'my phone', 'call me at', 'call me on',
            'reach me at', 'contact me at', 'contact me on', 'whatsapp me',
            'my whatsapp', 'message me on', 'add me on', 'find me on telegram',
            'my telegram', 'my viber', 'dm me', 'text me at', 'signal me',
            'my instagram', 'my facebook', 'contact me outside',
        ] as $signal) {
            if (str_contains($lower, $signal)) {
                return ['blocked' => true, 'reason' => 'Requesting communication outside Workation is not allowed. Please use this message thread for all booking inquiries.', 'pattern' => 'contact_redirect'];
            }
        }

        return ['blocked' => false, 'reason' => '', 'pattern' => ''];
    }
}

if (!function_exists('portalCanonicalHostRedirect')) {
    function portalCanonicalHostRedirect(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        if (strtolower((string) config('app.env', 'production')) !== 'production') {
            return null;
        }

        $appUrl = trim((string) config('app.url', ''));
        $canonicalHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if ($canonicalHost === '') {
            return null;
        }

        $requestHost = strtolower((string) $request->getHost());
        if ($requestHost === '' || $requestHost === $canonicalHost) {
            return null;
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $canonicalScheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
        if ($canonicalScheme === '') {
            $canonicalScheme = $request->getScheme();
        }

        return redirect()->to($canonicalScheme . '://' . $canonicalHost . $request->getRequestUri(), 302);
    }
}

if (!function_exists('canReviewVendorRegistrations')) {
    function canReviewVendorRegistrations(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = normalizePortalRoleValue((string) session('portal_admin_role', ''));
        return in_array($role, ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('currentPortalAdminRole')) {
    function currentPortalAdminRole(): string
    {
        return normalizePortalRoleValue((string) session('portal_admin_role', ''));
    }
}

if (!function_exists('canManageVendorUsers')) {
    function canManageVendorUsers(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('canCreateVendorUsers')) {
    function canCreateVendorUsers(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('canApproveVendorRegistrationRequest')) {
    function canApproveVendorRegistrationRequest(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('canApproveVendorDeleteRequest')) {
    function canApproveVendorDeleteRequest(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return currentPortalAdminRole() === 'ADMIN_SUPER';
    }
}

if (!function_exists('canRequestVendorDeleteApproval')) {
    function canRequestVendorDeleteApproval(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('portalActionRequestsEnabled')) {
    function portalActionRequestsEnabled(): bool
    {
        return Schema::hasTable('portal_admin_action_requests');
    }
}

if (!function_exists('createPortalActionRequest')) {
    function createPortalActionRequest(
        string $actionType,
        ?int $targetUserId,
        ?int $targetRegistrationId,
        ?string $targetIdentifier,
        ?string $reason,
        ?array $payload = null
    ): int {
        return (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => $actionType,
            'requested_by_user_id' => is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null,
            'requested_by_role' => (string) session('portal_admin_role', ''),
            'target_user_id' => $targetUserId,
            'target_registration_id' => $targetRegistrationId,
            'target_identifier' => $targetIdentifier,
            'reason' => $reason,
            'payload' => $payload ? json_encode($payload) : null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (!function_exists('portalAdminAuditLog')) {
    function portalAdminAuditLog(string $action, array $context = []): void
    {
        if (!Schema::hasTable('portal_admin_audit_logs')) {
            return;
        }

        $actorUserId = session('portal_admin_user_id');
        $actorRole = session('portal_admin_role');
        $actorName = session('portal_admin_user');

        $targetUserId = $context['target_user_id'] ?? null;
        $targetIdentifier = $context['target_identifier'] ?? null;
        $targetRole = $context['target_role'] ?? null;
        unset($context['target_user_id'], $context['target_identifier'], $context['target_role']);

        try {
            DB::table('portal_admin_audit_logs')->insert([
                'actor_user_id' => is_numeric($actorUserId) ? (int) $actorUserId : null,
                'actor_name' => is_string($actorName) ? $actorName : null,
                'actor_role' => is_string($actorRole) ? $actorRole : null,
                'action' => $action,
                'target_user_id' => is_numeric($targetUserId) ? (int) $targetUserId : null,
                'target_identifier' => is_string($targetIdentifier) ? $targetIdentifier : null,
                'target_role' => is_string($targetRole) ? $targetRole : null,
                'details' => empty($context) ? null : json_encode($context),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write portal admin audit log.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('portalPreferredMediaOutputFormat')) {
    function portalPreferredMediaOutputFormat(): array
    {
        if (function_exists('imagewebp')) {
            return [
                'extension' => 'webp',
                'mime' => 'image/webp',
            ];
        }

        return [
            'extension' => 'jpg',
            'mime' => 'image/jpeg',
        ];
    }
}

if (!function_exists('portalCreateImageResourceFromFile')) {
    function portalCreateImageResourceFromFile(string $filePath, string $mimeType)
    {
        if ($filePath === '' || !is_file($filePath)) {
            return null;
        }

        $mime = strtolower(trim($mimeType));
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            return @imagecreatefromjpeg($filePath) ?: null;
        }
        if ($mime === 'image/png') {
            return @imagecreatefrompng($filePath) ?: null;
        }
        if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($filePath) ?: null;
        }

        return null;
    }
}

if (!function_exists('portalResizeImageToFill')) {
    function portalResizeImageToFill($sourceImage, int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight)
    {
        if (!is_resource($sourceImage) && !($sourceImage instanceof \GdImage)) {
            return null;
        }
        if ($sourceWidth <= 0 || $sourceHeight <= 0 || $targetWidth <= 0 || $targetHeight <= 0) {
            return null;
        }

        $sourceAspect = $sourceWidth / $sourceHeight;
        $targetAspect = $targetWidth / $targetHeight;

        $cropWidth = $sourceWidth;
        $cropHeight = $sourceHeight;
        $sourceX = 0;
        $sourceY = 0;

        if ($sourceAspect > $targetAspect) {
            $cropWidth = (int) round($sourceHeight * $targetAspect);
            $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
        } elseif ($sourceAspect < $targetAspect) {
            $cropHeight = (int) round($sourceWidth / $targetAspect);
            $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $destinationImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($destinationImage === false) {
            return null;
        }

        imagealphablending($destinationImage, false);
        imagesavealpha($destinationImage, true);
        $transparent = imagecolorallocatealpha($destinationImage, 0, 0, 0, 127);
        imagefill($destinationImage, 0, 0, $transparent);

        imagecopyresampled(
            $destinationImage,
            $sourceImage,
            0,
            0,
            $sourceX,
            $sourceY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        return $destinationImage;
    }
}

if (!function_exists('portalWriteMediaVariant')) {
    function portalWriteMediaVariant($image, string $relativePath, string $extension): bool
    {
        if ((!is_resource($image) && !($image instanceof \GdImage)) || $relativePath === '') {
            return false;
        }

        $ext = strtolower(trim($extension));
        ob_start();
        $encoded = false;
        if ($ext === 'webp' && function_exists('imagewebp')) {
            $encoded = (bool) @imagewebp($image, null, 84);
        } else {
            $encoded = (bool) @imagejpeg($image, null, 86);
        }

        $binary = ob_get_clean();
        if (!$encoded || !is_string($binary) || $binary === '') {
            return false;
        }

        $diskName = portalManagedMediaDiskName();
        $disk = Storage::disk($diskName);
        $contentType = $ext === 'webp' ? 'image/webp' : 'image/jpeg';

        // Bucket Owner Enforced buckets reject requests that set an ACL, so do not
        // include 'visibility' in the options. Try with ContentType first, then bare.
        $writeAttempts = [
            ['ContentType' => $contentType],
            [],
        ];

        foreach ($writeAttempts as $options) {
            try {
                if ($disk->put($relativePath, $binary, $options)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // try next option set
            }
        }

        return false;
    }
}

if (!function_exists('portalManagedMediaDiskName')) {
    function portalManagedMediaDiskName(): string
    {
        $disk = trim((string) config('filesystems.portal_media_disk', config('filesystems.vendor_media_disk', 'public')));

        return $disk !== '' ? $disk : 'public';
    }
}

if (!function_exists('portalManagedMediaRelativePath')) {
    function portalManagedMediaRelativePath(?string $storedValue): ?string
    {
        $value = trim(str_replace('\\', '/', (string) ($storedValue ?? '')));
        if ($value === '') {
            return null;
        }

        if (preg_match('#/storage/app/public/(.+)$#i', $value, $matches) === 1) {
            $value = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $value, $matches) === 1) {
            $value = (string) ($matches[1] ?? '');
        } elseif (str_starts_with($value, '/storage/')) {
            $value = (string) substr($value, strlen('/storage/'));
        } elseif (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = trim(str_replace('\\', '/', (string) parse_url($value, PHP_URL_PATH)));
            if (preg_match('#^/storage/(.+)$#i', $path, $matches) === 1) {
                $value = (string) ($matches[1] ?? '');
            } else {
                return null;
            }
        }

        $value = ltrim($value, '/');
        if (str_starts_with($value, 'public/')) {
            $value = substr($value, 7);
        }
        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, 8);
        }

        $value = ltrim($value, '/');

        return $value !== '' ? $value : null;
    }
}

if (!function_exists('portalResolveHeroStoredValue')) {
    function portalResolveHeroStoredValue(string $slot): string
    {
        $normalizedSlot = strtolower(trim($slot));
        if ($normalizedSlot === '' || !preg_match('/^[a-z0-9_-]+$/', $normalizedSlot)) {
            return '';
        }

        if ($normalizedSlot === 'home') {
            if (Schema::hasTable('portal_finance_settings')) {
                $storedValue = trim((string) (DB::table('portal_finance_settings')
                    ->where('setting_key', 'home_hero_image_url')
                    ->value('value_string') ?? ''));
                if ($storedValue !== '') {
                    return $storedValue;
                }
            }

            return trim((string) env('HOME_HERO_IMAGE_URL', ''));
        }

        if (!Schema::hasTable('portal_finance_settings')) {
            return '';
        }

        $normalizeSettingSuffix = static function (string $value): string {
            return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
        };

        $slotVariants = array_values(array_unique(array_filter([
            $normalizedSlot,
            str_replace('-', '_', $normalizedSlot),
            str_replace('_', '-', $normalizedSlot),
        ], static fn ($value) => is_string($value) && trim($value) !== '')));
        $settingKeys = array_map(static fn (string $variant) => 'catalog_hero_image_' . $variant, $slotVariants);

        $storedValuesByKey = DB::table('portal_finance_settings')
            ->whereIn('setting_key', $settingKeys)
            ->pluck('value_string', 'setting_key');

        foreach ($settingKeys as $key) {
            $candidateValue = trim((string) ($storedValuesByKey[$key] ?? ''));
            if ($candidateValue !== '') {
                return $candidateValue;
            }
        }

        $targetSuffix = $normalizeSettingSuffix($normalizedSlot);
        $allCategoryHeroRows = DB::table('portal_finance_settings')
            ->where('setting_key', 'like', 'catalog_hero_image_%')
            ->get(['setting_key', 'value_string']);

        foreach ($allCategoryHeroRows as $row) {
            $rowKey = trim((string) ($row->setting_key ?? ''));
            $rowValue = trim((string) ($row->value_string ?? ''));
            if ($rowKey === '' || $rowValue === '') {
                continue;
            }

            $rowSuffix = trim((string) Str::after($rowKey, 'catalog_hero_image_'));
            if ($rowSuffix === '') {
                continue;
            }

            if ($normalizeSettingSuffix($rowSuffix) === $targetSuffix) {
                return $rowValue;
            }
        }

        return '';
    }
}

if (!function_exists('portalHeroStoredValueForSlot')) {
    function portalHeroStoredValueForSlot(string $slot): string
    {
        $normalizedSlot = strtolower(trim($slot));
        if ($normalizedSlot === '' || !preg_match('/^[a-z0-9_-]+$/', $normalizedSlot)) {
            return '';
        }

        $cacheKey = 'portal-hero-slot:' . $normalizedSlot;

        try {
            return (string) cache()->remember($cacheKey, now()->addSeconds(60), static function () use ($normalizedSlot) {
                return portalResolveHeroStoredValue($normalizedSlot);
            });
        } catch (\Throwable $e) {
            return portalResolveHeroStoredValue($normalizedSlot);
        }
    }
}

if (!function_exists('portalForgetHeroSlotCache')) {
    function portalForgetHeroSlotCache(?string $slot): void
    {
        $normalizedSlot = strtolower(trim((string) ($slot ?? '')));
        if ($normalizedSlot === '' || !preg_match('/^[a-z0-9_-]+$/', $normalizedSlot)) {
            return;
        }

        try {
            cache()->forget('portal-hero-slot:' . $normalizedSlot);
        } catch (\Throwable $e) {
            // ignore cache store failures; DB remains source of truth
        }
    }
}

if (!function_exists('portalManagedMediaUrlFromPath')) {
    function portalManagedMediaUrlFromPath(?string $storedValue): ?string
    {
        $value = trim((string) ($storedValue ?? ''));
        if ($value === '') {
            return null;
        }

        // Keep application media proxy routes untouched.
        // Rewriting these to Storage::url() can produce private S3 URLs that 403.
        if (str_starts_with($value, '/media/')) {
            return $value;
        }

        if (str_starts_with($value, 'http://')) {
            return 'https://' . ltrim(substr($value, 7), '/');
        }

        if (str_starts_with($value, 'https://')) {
            return $value;
        }

        // Paths stored with '__public__/' prefix were written to the local public disk
        // as a fallback when the managed (S3) disk was unavailable.
        if (str_starts_with($value, '__public__/')) {
            $localPath = ltrim(substr($value, strlen('__public__/')), '/');
            if ($localPath === '') {
                return null;
            }

            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $localPath)));
            return '/media/portal-public/' . $encodedPath;
        }

        $relativePath = portalManagedMediaRelativePath($value);
        if ($relativePath === null) {
            return null;
        }

        $diskName = portalManagedMediaDiskName();
        if ($diskName === 'public') {
            return Storage::disk('public')->url($relativePath);
        }

        try {
            $useTemporaryUrls = (bool) config('filesystems.portal_media_use_temporary_urls', false);
            if ($useTemporaryUrls) {
                $ttlMinutes = max(1, (int) config('filesystems.portal_media_temporary_url_ttl_minutes', 30));
                return Storage::disk($diskName)->temporaryUrl($relativePath, now()->addMinutes($ttlMinutes));
            }

            return Storage::disk($diskName)->url($relativePath);
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve managed portal media URL.', [
                'disk' => $diskName,
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

if (!function_exists('portalDeleteManagedPublicAsset')) {
    function portalDeleteManagedPublicAsset(?string $storedValue, string $managedPrefix = 'portal-admin/hero-images/'): void
    {
        $value = trim((string) ($storedValue ?? ''));
        if ($value === '') {
            return;
        }

        $allowedPrefixes = [
            $managedPrefix,
            'blog/inline/' . ltrim($managedPrefix, '/'),
        ];

        // Files stored with '__public__/' prefix live on the local public disk.
        if (str_starts_with($value, '__public__/')) {
            $localPath = ltrim(substr($value, strlen('__public__/')), '/');
            $localMatches = false;
            foreach ($allowedPrefixes as $prefix) {
                if ($localPath !== '' && str_starts_with($localPath, $prefix)) {
                    $localMatches = true;
                    break;
                }
            }

            if ($localMatches) {
                try {
                    Storage::disk('public')->delete($localPath);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete public disk portal media asset.', ['path' => $localPath, 'error' => $e->getMessage()]);
                }
            }
            return;
        }

        $relativePath = portalManagedMediaRelativePath($value) ?? '';

        $matchesManagedPrefix = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                $matchesManagedPrefix = true;
                break;
            }
        }

        if ($relativePath === '' || !$matchesManagedPrefix) {
            return;
        }

        try {
            Storage::disk(portalManagedMediaDiskName())->delete($relativePath);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete managed portal media asset.', [
                'path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('portalNormalizeDestinationMediaKey')) {
    function portalNormalizeDestinationMediaKey(?string $value): string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return '';
        }

        $normalized = \Illuminate\Support\Str::ascii($normalized);
        $normalized = str_replace(['%20', '+'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        return str_replace(' ', '_', $normalized);
    }
}

if (!function_exists('portalAtlasCapitalBadges')) {
    /**
     * Resolve capital badges for an island/city name.
     *
     * @return array<int, array{key:string,label:string}>
     */
    function portalAtlasCapitalBadges(?string $islandName, ?string $atollName = null): array
    {
        $normalize = static function (?string $value): string {
            $raw = strtolower(trim((string) ($value ?? '')));
            if ($raw === '') {
                return '';
            }

            $ascii = \Illuminate\Support\Str::ascii($raw);
            $ascii = preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '';
            return trim(preg_replace('/\s+/', ' ', $ascii) ?? '');
        };

        $nameKey = $normalize($islandName);
        if ($nameKey === '') {
            return [];
        }

        $badges = [];
        $seen = [];

        // Prefer DB-managed flags when available so admin form controls are authoritative.
        if (
            Schema::hasTable('islands')
            && Schema::hasColumn('islands', 'is_country_capital')
            && Schema::hasColumn('islands', 'is_atoll_capital')
        ) {
            static $capitalFlagRows = null;
            if ($capitalFlagRows === null) {
                try {
                    $capitalFlagRows = \Illuminate\Support\Facades\DB::table('islands')
                        ->leftJoin('atolls', 'atolls.id', '=', 'islands.atoll_id')
                        ->select([
                            'islands.name as island_name',
                            'atolls.name as atoll_name',
                            'islands.is_country_capital',
                            'islands.is_atoll_capital',
                        ])
                        ->where(function ($query) {
                            $query->where('islands.is_country_capital', true)
                                ->orWhere('islands.is_atoll_capital', true);
                        })
                        ->get();
                } catch (\Throwable $e) {
                    $capitalFlagRows = collect();
                }
            }

            $isCountryCapital = false;
            $isAtollCapital = false;
            $atollKey = $normalize($atollName);

            foreach ($capitalFlagRows as $row) {
                $rowNameKey = $normalize((string) ($row->island_name ?? ''));
                if ($rowNameKey === '' || $rowNameKey !== $nameKey) {
                    continue;
                }

                if ((bool) ($row->is_country_capital ?? false)) {
                    $isCountryCapital = true;
                }

                if ((bool) ($row->is_atoll_capital ?? false)) {
                    $rowAtollKey = $normalize((string) ($row->atoll_name ?? ''));
                    if ($atollKey === '' || $rowAtollKey === '' || $rowAtollKey === $atollKey) {
                        $isAtollCapital = true;
                    }
                }
            }

            if ($isCountryCapital) {
                $badges[] = ['key' => 'country-capital', 'label' => 'Country Capital'];
                $seen['country-capital'] = true;
            }

            if ($isAtollCapital) {
                $badges[] = ['key' => 'atoll-capital', 'label' => 'Atoll Capital'];
                $seen['atoll-capital'] = true;
            }

            if (!empty($badges)) {
                return $badges;
            }
        }

        $countryCapitals = config('atlas_capitals.country_capitals', []);
        $countryCapitalKeys = collect(is_array($countryCapitals) ? $countryCapitals : [])
            ->map(static fn ($item) => $normalize((string) $item))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();

        if (in_array($nameKey, $countryCapitalKeys, true)) {
            $badges[] = ['key' => 'country-capital', 'label' => 'Country Capital'];
            $seen['country-capital'] = true;
        }

        $atollCapitalMap = config('atlas_capitals.atoll_capitals', []);
        if (is_array($atollCapitalMap)) {
            $atollKey = $normalize($atollName);
            $resolvedAtollCapital = null;

            if ($atollKey !== '') {
                foreach ($atollCapitalMap as $rawAtoll => $rawCapital) {
                    if ($normalize((string) $rawAtoll) !== $atollKey) {
                        continue;
                    }

                    if (is_array($rawCapital)) {
                        foreach ($rawCapital as $candidate) {
                            $candidateKey = $normalize((string) $candidate);
                            if ($candidateKey !== '' && $candidateKey === $nameKey) {
                                $resolvedAtollCapital = $candidateKey;
                                break;
                            }
                        }
                    } else {
                        $candidateKey = $normalize((string) $rawCapital);
                        if ($candidateKey !== '' && $candidateKey === $nameKey) {
                            $resolvedAtollCapital = $candidateKey;
                        }
                    }

                    break;
                }
            }

            if ($resolvedAtollCapital !== null && !isset($seen['atoll-capital'])) {
                $badges[] = ['key' => 'atoll-capital', 'label' => 'Atoll Capital'];
                $seen['atoll-capital'] = true;
            }
        }

        return $badges;
    }
}

if (!function_exists('portalStoreAdminHeroImage')) {
    if (!function_exists('portalStoreManagedOriginalImage')) {
        function portalStoreManagedOriginalImage($file, string $baseDirectory, string $slot): ?string
        {
            if (!$file || !method_exists($file, 'getPathname')) {
                return null;
            }

            $directory = trim($baseDirectory, '/') . '/' . trim($slot, '/');
            $extension = strtolower(trim((string) $file->getClientOriginalExtension()));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $mime = strtolower(trim((string) ($file->getMimeType() ?? '')));
                $extension = match ($mime) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
            }

            $filename = now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
            $relativePath = $directory . '/' . $filename;
            $diskName = portalManagedMediaDiskName();
            $disk = Storage::disk($diskName);

            $binary = @file_get_contents((string) $file->getPathname());
            if (is_string($binary) && $binary !== '') {
                $contentType = match ($extension) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };

                $writeAttempts = [
                    ['ContentType' => $contentType],
                    [],
                ];

                $candidatePaths = [$relativePath];

                foreach ($candidatePaths as $candidatePath) {
                    foreach ($writeAttempts as $options) {
                        try {
                            if ($disk->put($candidatePath, $binary, $options)) {
                                return $candidatePath;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Managed media original upload fallback failed.', [
                                'disk' => $diskName,
                                'path' => $candidatePath,
                                'options' => $options,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Final fallback: write to local public disk when the managed (S3) disk is
            // unavailable. Path is prefixed with '__public__/' so URL resolution and
            // delete helpers know which disk owns the file.
            if ($diskName !== 'public') {
                $publicDisk = Storage::disk('public');
                try {
                    if (is_string($binary) && $binary !== '' && $publicDisk->put($relativePath, $binary, [])) {
                        return '__public__/' . $relativePath;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Managed media original upload: public disk fallback also failed.', [
                        'path' => $relativePath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return null;
        }
    }

    function portalStoreAdminHeroImage($file, string $slot): ?string
    {
        if (!$file || !method_exists($file, 'getPathname')) {
            return null;
        }

        $imageSize = @getimagesize((string) $file->getPathname());
        if (!is_array($imageSize) || count($imageSize) < 2) {
            return null;
        }

        $sourceWidth = (int) $imageSize[0];
        $sourceHeight = (int) $imageSize[1];
        $sourceImage = portalCreateImageResourceFromFile(
            (string) $file->getPathname(),
            (string) ($file->getMimeType() ?? '')
        );

        if ($sourceImage === null) {
            return portalStoreManagedOriginalImage($file, 'portal-admin/hero-images', $slot);
        }

        $format = portalPreferredMediaOutputFormat();
        $extension = (string) ($format['extension'] ?? 'jpg');
        $targetImage = portalResizeImageToFill($sourceImage, $sourceWidth, $sourceHeight, 2560, 1440);
        $relativePath = 'portal-admin/hero-images/' . trim($slot, '/') . '/' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $written = $targetImage !== null ? portalWriteMediaVariant($targetImage, $relativePath, $extension) : false;

        if (is_resource($sourceImage) || $sourceImage instanceof \GdImage) {
            imagedestroy($sourceImage);
        }
        if (is_resource($targetImage) || $targetImage instanceof \GdImage) {
            imagedestroy($targetImage);
        }

        if (!$written) {
            return portalStoreManagedOriginalImage($file, 'portal-admin/hero-images', $slot);
        }

        return $relativePath;
    }
}

if (!function_exists('portalStoreAdminDestinationImage')) {
    function portalStoreAdminDestinationImage($file, string $slot): ?string
    {
        if (!$file || !method_exists($file, 'getPathname')) {
            return null;
        }

        $imageSize = @getimagesize((string) $file->getPathname());
        if (!is_array($imageSize) || count($imageSize) < 2) {
            return null;
        }

        $sourceWidth = (int) $imageSize[0];
        $sourceHeight = (int) $imageSize[1];
        $sourceImage = portalCreateImageResourceFromFile(
            (string) $file->getPathname(),
            (string) ($file->getMimeType() ?? '')
        );

        if ($sourceImage === null) {
            return portalStoreManagedOriginalImage($file, 'portal-admin/destination-images', $slot);
        }

        $format = portalPreferredMediaOutputFormat();
        $extension = (string) ($format['extension'] ?? 'jpg');
        $targetImage = portalResizeImageToFill($sourceImage, $sourceWidth, $sourceHeight, 1600, 900);
        $relativePath = 'portal-admin/destination-images/' . trim($slot, '/') . '/' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $written = $targetImage !== null ? portalWriteMediaVariant($targetImage, $relativePath, $extension) : false;

        if (is_resource($sourceImage) || $sourceImage instanceof \GdImage) {
            imagedestroy($sourceImage);
        }
        if (is_resource($targetImage) || $targetImage instanceof \GdImage) {
            imagedestroy($targetImage);
        }

        if (!$written) {
            return portalStoreManagedOriginalImage($file, 'portal-admin/destination-images', $slot);
        }

        return $relativePath;
    }
}

if (!function_exists('portalVendorNormalizeListingCategory')) {
    function portalVendorNormalizeListingCategory(?string $value): string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim((string) preg_replace('/_+/', '_', $normalized), '_');

        return match ($normalized) {
            'marine_transport', 'sea_transport', 'land_transport' => 'transport',
            default => $normalized,
        };
    }
}

if (!function_exists('portalVendorListingPublishChecklist')) {
    function portalVendorListingPublishChecklist($listing, array $details = [], int $mediaCount = 0, int $roomCount = 0): array
    {
        $category = portalVendorNormalizeListingCategory((string) (data_get($listing, 'listing_category') ?? ($details['listing_category'] ?? '')));
        $name = trim((string) data_get($listing, 'name', ''));
        $description = trim((string) data_get($listing, 'description', ''));
        $status = strtolower(trim((string) data_get($listing, 'status', 'active')));
        $location = trim((string) data_get($listing, 'location', ''));
        $basePrice = (float) data_get($listing, 'base_price', 0);
        $maxGuests = (int) data_get($listing, 'max_guests', 0);
        $capacityValue = is_numeric($details['capacity_value'] ?? null) ? (int) $details['capacity_value'] : 0;

        $missing = [];

        if ($name === '') {
            $missing[] = 'Add a listing name';
        }
        if ($description === '') {
            $missing[] = 'Add a listing description';
        }
        if ($status !== 'active') {
            $missing[] = 'Set listing status to Active';
        }
        if ($mediaCount < 1) {
            $missing[] = 'Upload at least one listing photo';
        }

        if ($category === 'transport') {
            $pickup = trim((string) ($details['pickup_location'] ?? ''));
            $dropoff = trim((string) ($details['dropoff_location'] ?? ''));
            if ($pickup === '' || $dropoff === '') {
                $missing[] = 'Complete pickup and dropoff route details';
            }
        } elseif ($location === '' && trim((string) ($details['location_city'] ?? '')) === '' && trim((string) ($details['location_state'] ?? '')) === '') {
            $missing[] = 'Add the listing location';
        }

        if ($category === 'accommodation') {
            if ($roomCount < 1) {
                $missing[] = 'Add at least one room category';
            }
            if (trim((string) ($details['meal_plan'] ?? '')) === '') {
                $missing[] = 'Choose an accommodation meal plan';
            }
            if (!is_numeric($details['extra_guest_fee'] ?? null)) {
                $missing[] = 'Set accommodation extra guest fee policy';
            }
            if (!is_numeric($details['child_fee'] ?? null)) {
                $missing[] = 'Set accommodation child fee policy';
            }
            if (trim((string) ($details['child_policy'] ?? '')) === '') {
                $missing[] = 'Add accommodation child policy';
            }
        } else {
            $effectiveCapacity = max($maxGuests, $capacityValue);
            if ($effectiveCapacity <= 0) {
                $missing[] = 'Set guest capacity';
            }
        }

        if ($category === 'transport') {
            $hourlyRate = (float) ($details['hourly_rate'] ?? 0);
            $dailyRate = (float) ($details['daily_rate'] ?? 0);
            if ($basePrice <= 0 && $hourlyRate <= 0 && $dailyRate <= 0) {
                $missing[] = 'Set at least one fare or hire rate';
            }
            if (trim((string) ($details['transport_mode'] ?? '')) === '') {
                $missing[] = 'Select a transport mode';
            }
            if (trim((string) ($details['contact_name'] ?? '')) === '' || trim((string) ($details['contact_number'] ?? '')) === '') {
                $missing[] = 'Add transport contact name and number';
            }
            if (!is_numeric($details['trip_duration_minutes'] ?? null) || (int) ($details['trip_duration_minutes'] ?? 0) <= 0) {
                $missing[] = 'Add trip duration estimate';
            }
            if (trim((string) ($details['schedule_start_time'] ?? '')) === '' || trim((string) ($details['schedule_end_time'] ?? '')) === '') {
                $missing[] = 'Set transport operating schedule start and end times';
            }
            if (!is_numeric($details['booking_cutoff_minutes'] ?? null)) {
                $missing[] = 'Set transport booking cutoff time';
            }
            if (trim((string) ($details['boarding_instructions'] ?? '')) === '') {
                $missing[] = 'Add transport boarding instructions';
            }
        } elseif ($category !== 'accommodation' && $basePrice <= 0) {
            $missing[] = 'Set a base price';
        }

        if (in_array($category, ['excursion', 'water_sports'], true)) {
            if (trim((string) ($details['excursion_type'] ?? '')) === '') {
                $missing[] = 'Choose an excursion type';
            }
            if (!is_numeric($details['excursion_duration_minutes'] ?? null) || (int) ($details['excursion_duration_minutes'] ?? 0) <= 0) {
                $missing[] = 'Add excursion duration';
            }
            if (trim((string) ($details['safety_waiver_required'] ?? '')) === '') {
                $missing[] = 'Set whether safety waiver is required';
            }
            if (trim((string) ($details['weather_cancellation_policy'] ?? '')) === '') {
                $missing[] = 'Add weather cancellation policy';
            }
            if (!is_array($details['equipment_included'] ?? null) || count((array) ($details['equipment_included'] ?? [])) < 1) {
                $missing[] = 'Select at least one included equipment item';
            }
        }

        if ($category === 'remote_workspace') {
            if (trim((string) ($details['workspace_type'] ?? '')) === '') {
                $missing[] = 'Choose workspace type';
            }
            if (!is_numeric($details['internet_speed_mbps'] ?? null) || (float) ($details['internet_speed_mbps'] ?? 0) <= 0) {
                $missing[] = 'Add internet speed';
            }
        }

        if ($category === 'resort_day_visit') {
            if (trim((string) ($details['day_visit_start_time'] ?? '')) === '' || trim((string) ($details['day_visit_end_time'] ?? '')) === '') {
                $missing[] = 'Set day-visit start and end time';
            }
        }

        if ($category === 'restaurant') {
            if (trim((string) ($details['cuisine_type'] ?? '')) === '') {
                $missing[] = 'Add cuisine type';
            }
            if (trim((string) ($details['meal_service'] ?? '')) === '') {
                $missing[] = 'Choose meal service window';
            }
        }

        if ($category === 'vehicle_rental') {
            if (trim((string) ($details['vehicle_type'] ?? '')) === '') {
                $missing[] = 'Choose vehicle type';
            }
            if (trim((string) ($details['transmission_type'] ?? '')) === '') {
                $missing[] = 'Choose transmission type';
            }
            if (trim((string) ($details['fuel_type'] ?? '')) === '') {
                $missing[] = 'Choose fuel type';
            }
        }

        $missing = array_values(array_unique(array_filter($missing, static fn ($item): bool => trim((string) $item) !== '')));

        return [
            'ready' => $missing === [],
            'missing' => $missing,
            'missing_count' => count($missing),
            'category' => $category,
            'media_count' => max(0, $mediaCount),
            'room_count' => max(0, $roomCount),
        ];
    }
}