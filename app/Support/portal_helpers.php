<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

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

        $useWhatsApp = in_array($phoneChannel, ['whatsapp', 'wa'], true);


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
          
            if ($twilioWhatsappFrom === '') {
                throw new \RuntimeException('WhatsApp OTP is enabled but TWILIO_WHATSAPP_FROM is missing.');
            }

            $whatsAppTo = str_starts_with($destination, 'whatsapp:') ? $destination : 'whatsapp:' . ltrim($destination, '+');
            if (!str_starts_with($whatsAppTo, 'whatsapp:+')) {
                $whatsAppTo = 'whatsapp:+' . ltrim(str_replace('whatsapp:', '', $whatsAppTo), '+');
            }

            $payload = [
                'From' => $twilioWhatsappFrom,
                'To' => $whatsAppTo,
            ];

            if ($twilioWhatsappContentSid !== '') {
                $payload['ContentSid'] = $twilioWhatsappContentSid;
                $payload['ContentVariables'] = json_encode(['1' => $otpCode]);
            } else {
                // Sandbox/testing fallback if no template SID has been configured.
                $payload['Body'] = 'Your Workation vendor verification code is ' . $otpCode . '. It expires in 10 minutes.';
            }

            $waResponse = Http::withBasicAuth($twilioSid, $twilioToken)
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . $twilioSid . '/Messages.json', $payload);

            if (!$waResponse->successful()) {
                throw new \RuntimeException('WhatsApp OTP delivery failed with status ' . $waResponse->status() . '.');
            }

            return;
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
