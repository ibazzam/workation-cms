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
        return $portal === 'admin' ? '/admin' : '/vendor';
    }
}

if (!function_exists('vendorPortalCategoryMap')) {
    function vendorPortalCategoryMap(): array
    {
        return [
            'accommodation' => 'Accommodation',
            'marine_transport' => 'Marine Transport',
            'land_transport' => 'Land Transport',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspaces',
            'resort_day_visit' => 'Resort Day Visits',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
            'water_sports' => 'Water Sports',
            'conference_room' => 'Conference Rooms',
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

        return Storage::disk(portalManagedMediaDiskName())->put($relativePath, $binary);
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

if (!function_exists('portalManagedMediaUrlFromPath')) {
    function portalManagedMediaUrlFromPath(?string $storedValue): ?string
    {
        $value = trim((string) ($storedValue ?? ''));
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://')) {
            return 'https://' . ltrim(substr($value, 7), '/');
        }

        if (str_starts_with($value, 'https://')) {
            return $value;
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

        $relativePath = portalManagedMediaRelativePath($value) ?? '';

        if ($relativePath === '' || !str_starts_with($relativePath, $managedPrefix)) {
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

if (!function_exists('portalStoreAdminHeroImage')) {
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
            return null;
        }

        $format = portalPreferredMediaOutputFormat();
        $extension = (string) ($format['extension'] ?? 'jpg');
        $targetImage = portalResizeImageToFill($sourceImage, $sourceWidth, $sourceHeight, 1600, 900);
        $relativePath = 'portal-admin/hero-images/' . trim($slot, '/') . '/' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $written = $targetImage !== null ? portalWriteMediaVariant($targetImage, $relativePath, $extension) : false;

        if (is_resource($sourceImage) || $sourceImage instanceof \GdImage) {
            imagedestroy($sourceImage);
        }
        if (is_resource($targetImage) || $targetImage instanceof \GdImage) {
            imagedestroy($targetImage);
        }

        if (!$written) {
            return null;
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
            'marine_transport', 'land_transport' => 'transport',
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
