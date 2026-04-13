<?php

use App\Models\User;
use App\Models\BlogPost;
use App\Support\ReservationPricingPolicy;
use App\Support\UniformIconSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                'allowed_roles' => ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'ADMIN_MEDIA'],
            ];
        }

        if ($portal === 'customer') {
            return [
                'session_key' => 'portal_customer_authenticated',
                'name' => 'Member',
                'allowed_roles' => [],
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
        if ($portal === 'admin') {
            return '/admin';
        }

        if ($portal === 'customer') {
            return '/customer';
        }

        return '/vendor';
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
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        $aliases = [
            'ADMIN_FINACE' => 'ADMIN_FINANCE',
            'ADMINFINACE' => 'ADMIN_FINANCE',
            'ADMINFINANCE' => 'ADMIN_FINANCE',
            'ADMINMEDIA' => 'ADMIN_MEDIA',
            'MEDIA_ADMIN' => 'ADMIN_MEDIA',
        ];

        return $aliases[$normalized] ?? $normalized;
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
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }
}

if (!function_exists('sendPortalPasswordResetFallbackMail')) {
    function sendPortalPasswordResetFallbackMail(string $email, string $portal, string $resetUrl, ?string $displayName = null): bool
    {
        $normalizedPortal = in_array($portal, ['admin', 'vendor', 'customer'], true) ? $portal : 'admin';
        $portalLabel = ucfirst($normalizedPortal);
        $name = trim((string) $displayName);
        $greeting = $name !== '' ? "Hi {$name}," : 'Hello,';

        Mail::raw(
            "{$greeting}\n\nWe received a request to reset your {$portalLabel} account password on Workation.\n\nUse the link below to set a new password. This link expires in 60 minutes:\n\n{$resetUrl}\n\nIf you did not request a password reset, you can safely ignore this email. Your password will not change.\n\n— Workation Support",
            static function ($message) use ($email, $portalLabel) {
                $message->to($email)->subject('Reset Your ' . $portalLabel . ' Password | Workation');
            }
        );

        return true;
    }
}

if (!function_exists('supportedVendorSocialProviders')) {
    function supportedVendorSocialProviders(): array
    {
        return ['google', 'facebook', 'apple'];
    }
}

if (!function_exists('supportedCustomerSocialProviders')) {
    function supportedCustomerSocialProviders(): array
    {
        return ['google', 'facebook'];
    }
}

if (!function_exists('portalOAuthIntentSessionKey')) {
    function portalOAuthIntentSessionKey(string $provider): string
    {
        return 'portal_oauth_intent_' . strtolower(trim($provider));
    }
}

if (!function_exists('customerPostAuthRedirectSessionKey')) {
    function customerPostAuthRedirectSessionKey(): string
    {
        return 'customer_post_auth_redirect';
    }
}

if (!function_exists('normalizeCustomerPostAuthRedirect')) {
    function normalizeCustomerPostAuthRedirect(?string $target): ?string
    {
        $value = trim((string) $target);
        if ($value === '') {
            return null;
        }

        // Allow in-app relative URLs only.
        if (str_starts_with($value, '/')) {
            if (str_starts_with($value, '//') || str_starts_with($value, '/portal/')) {
                return null;
            }
            return $value;
        }

        // Allow absolute URLs only when host matches APP_URL.
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url', ''), PHP_URL_HOST));
        if ($host === '' || $appHost === '' || $host !== $appHost) {
            return null;
        }

        $path = (string) parse_url($value, PHP_URL_PATH);
        if ($path === '' || str_starts_with($path, '/portal/')) {
            return null;
        }

        $query = (string) parse_url($value, PHP_URL_QUERY);
        return $query !== '' ? ($path . '?' . $query) : $path;
    }
}

if (!function_exists('rememberCustomerPostAuthRedirect')) {
    function rememberCustomerPostAuthRedirect(Request $request): void
    {
        $candidate = normalizeCustomerPostAuthRedirect((string) $request->query('continue', ''));

        if ($candidate === null) {
            $candidate = normalizeCustomerPostAuthRedirect((string) $request->headers->get('referer', ''));
        }

        if ($candidate !== null) {
            $request->session()->put(customerPostAuthRedirectSessionKey(), $candidate);
        }
    }
}

if (!function_exists('consumeCustomerPostAuthRedirect')) {
    function consumeCustomerPostAuthRedirect(Request $request, string $fallback = '/'): string
    {
        $stored = normalizeCustomerPostAuthRedirect((string) $request->session()->pull(customerPostAuthRedirectSessionKey(), ''));
        return $stored ?: $fallback;
    }
}

if (!function_exists('customerSocialRedirectUrl')) {
    function customerSocialRedirectUrl(string $provider): string
    {
        $provider = strtolower(trim($provider));

        return (string) config(
            'services.' . $provider . '.customer_redirect',
            (string) config('services.' . $provider . '.redirect', url('/portal/customer/oauth/' . $provider . '/callback'))
        );
    }
}

if (!function_exists('isCustomerSocialProviderConfigured')) {
    function isCustomerSocialProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google' => trim((string) config('services.google.client_id', '')) !== ''
                && trim((string) config('services.google.client_secret', '')) !== '',
            'facebook' => trim((string) config('services.facebook.client_id', '')) !== ''
                && trim((string) config('services.facebook.client_secret', '')) !== '',
            default => false,
        };
    }
}

if (!function_exists('customerSocialProviderColumn')) {
    function customerSocialProviderColumn(string $provider): string
    {
        return match (strtolower(trim($provider))) {
            'google' => 'google_oauth_id',
            'facebook' => 'facebook_oauth_id',
            default => '',
        };
    }
}

if (!function_exists('customerVerificationStateCacheKey')) {
    function customerVerificationStateCacheKey(string $email): string
    {
        return 'customer_email_verified:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('customerProfileMetaCacheKey')) {
    function customerProfileMetaCacheKey(string $customerId): string
    {
        return 'customer_profile_meta:' . sha1(trim($customerId));
    }
}

if (!function_exists('customerTableName')) {
    function customerTableName(): string
    {
        return (new \App\Models\Customer())->getTable();
    }
}

if (!function_exists('customerConnectionName')) {
    function customerConnectionName(): ?string
    {
        return (new \App\Models\Customer())->getConnectionName();
    }
}

if (!function_exists('customerSchemaHasColumn')) {
    function customerSchemaHasColumn(string $column): bool
    {
        $connection = customerConnectionName();
        $table = customerTableName();

        return $connection
            ? Schema::connection($connection)->hasColumn($table, $column)
            : Schema::hasColumn($table, $column);
    }
}

if (!function_exists('customerTableInsert')) {
    function customerTableInsert(array $payload): void
    {
        $connection = customerConnectionName();
        $table = customerTableName();

        if ($connection) {
            DB::connection($connection)->table($table)->insert($payload);
            return;
        }

        DB::table($table)->insert($payload);
    }
}

if (!function_exists('customerVerificationTokenCacheKey')) {
    function customerVerificationTokenCacheKey(string $email): string
    {
        return 'customer_email_verify_token:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('customerEmailIsVerified')) {
    function customerEmailIsVerified(\App\Models\Customer $customer): bool
    {
        if (customerSchemaHasColumn('email_verified_at') && !empty($customer->email_verified_at)) {
            return true;
        }

        if (customerSchemaHasColumn('emailVerifiedAt') && !empty($customer->emailVerifiedAt)) {
            return true;
        }

        if (customerSchemaHasColumn('emailVerified') && (bool) ($customer->emailVerified ?? false)) {
            return true;
        }

        $email = strtolower(trim((string) ($customer->email ?? '')));
        if ($email === '') {
            return false;
        }

        return (bool) cache()->get(customerVerificationStateCacheKey($email), false);
    }
}

if (!function_exists('customerMarkEmailVerified')) {
    function customerMarkEmailVerified(\App\Models\Customer $customer): void
    {
        $email = strtolower(trim((string) ($customer->email ?? '')));
        if ($email === '') {
            return;
        }

        $now = now();
        $dirty = false;

        if (customerSchemaHasColumn('email_verified_at') && empty($customer->email_verified_at)) {
            $customer->email_verified_at = $now;
            $dirty = true;
        }
        if (customerSchemaHasColumn('emailVerifiedAt') && empty($customer->emailVerifiedAt)) {
            $customer->emailVerifiedAt = $now;
            $dirty = true;
        }
        if (customerSchemaHasColumn('emailVerified') && !(bool) ($customer->emailVerified ?? false)) {
            $customer->emailVerified = true;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }

        cache()->forever(customerVerificationStateCacheKey($email), true);
        cache()->forget(customerVerificationTokenCacheKey($email));
    }
}

if (!function_exists('customerIssueEmailVerificationToken')) {
    function customerIssueEmailVerificationToken(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));
        $token = Str::random(64);

        cache()->put(customerVerificationTokenCacheKey($normalizedEmail), [
            'hash' => Hash::make($token),
            'created_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        return $token;
    }
}

if (!function_exists('sendCustomerPortalRegistrationNotification')) {
    function sendCustomerPortalRegistrationNotification(string $email, string $name, bool $requireVerification = false): ?string
    {
        $recipient = strtolower(trim($email));
        if ($recipient === '') {
            return null;
        }

        $displayName = trim($name) !== '' ? trim($name) : 'Customer';
        $verificationToken = $requireVerification ? customerIssueEmailVerificationToken($recipient) : '';
        $verificationUrl = $verificationToken !== ''
            ? url('/portal/customer/verify-email?email=' . rawurlencode($recipient) . '&token=' . rawurlencode($verificationToken))
            : '';

        $body = "Hi {$displayName},\n\nYour Workation member account has been created successfully.";
        if ($verificationUrl !== '') {
            $body .= "\n\nBefore signing in, verify your email address using this secure link:\n{$verificationUrl}\n\nThis link expires in 24 hours.";
        } else {
            $body .= "\n\nYou can now sign in to your customer portal and start booking experiences.";
        }
        $body .= "\n\nIf you did not create this account, please contact support immediately.";

        try {
            Mail::raw(
                $body,
                static function ($message) use ($recipient) {
                    $message->to($recipient)->subject('Workation Member Account Verification');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer portal registration email.', [
                'email' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }

        return $verificationToken !== '' ? $verificationToken : null;
    }
}

if (!function_exists('findCustomerByEmail')) {
    function findCustomerByEmail(string $email): ?\App\Models\Customer
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        return \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->first();
    }
}

if (!function_exists('findActiveVendorByEmail')) {
    function findActiveVendorByEmail(string $email): ?\App\Models\User
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        return \App\Models\User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->where('portal_enabled', true)
            ->whereRaw('UPPER(portal_role) = ?', ['VENDOR'])
            ->first();
    }
}

if (!function_exists('upsertCustomerFromVendorIdentity')) {
    function upsertCustomerFromVendorIdentity(\App\Models\User $vendorUser, string $password): ?\App\Models\Customer
    {
        $email = strtolower(trim((string) $vendorUser->email));
        if ($email === '') {
            return null;
        }

        $customer = findCustomerByEmail($email);

        if (!$customer) {
            $now = now();
            $payload = [
                'email' => $email,
                'name' => trim((string) $vendorUser->name) !== '' ? trim((string) $vendorUser->name) : 'Customer',
                'password' => Hash::make($password),
            ];

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
            }
            if (customerSchemaHasColumn('createdAt')) {
                $payload['createdAt'] = $now;
            }
            if (customerSchemaHasColumn('updatedAt')) {
                $payload['updatedAt'] = $now;
            }
            if (customerSchemaHasColumn('created_at')) {
                $payload['created_at'] = $now;
            }
            if (customerSchemaHasColumn('updated_at')) {
                $payload['updated_at'] = $now;
            }

            if (customerSchemaHasColumn('email_verified_at')) {
                $payload['email_verified_at'] = $now;
            }
            if (customerSchemaHasColumn('emailVerifiedAt')) {
                $payload['emailVerifiedAt'] = $now;
            }
            if (customerSchemaHasColumn('emailVerified')) {
                $payload['emailVerified'] = true;
            }

            customerTableInsert($payload);
            $customer = findCustomerByEmail($email);
        }

        if (!$customer) {
            return null;
        }

        $needsSave = false;
        if (!Hash::check($password, (string) $customer->password)) {
            $customer->password = Hash::make($password);
            $needsSave = true;
        }
        if (trim((string) $customer->name) === '' && trim((string) $vendorUser->name) !== '') {
            $customer->name = trim((string) $vendorUser->name);
            $needsSave = true;
        }

        if ($needsSave) {
            $customer->save();
        }

        customerMarkEmailVerified($customer);

        return $customer;
    }
}

if (!function_exists('syncVendorPasswordFromCustomer')) {
    function syncVendorPasswordFromCustomer(\App\Models\User $vendorUser, string $password): void
    {
        if (Hash::check($password, (string) $vendorUser->password)) {
            return;
        }

        $vendorUser->password = Hash::make($password);
        $vendorUser->save();
    }
}

if (!function_exists('provisionCustomerAccountFromBooking')) {
    function provisionCustomerAccountFromBooking(string $email, string $name): ?\App\Models\Customer
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        $displayName = trim($name) !== '' ? trim($name) : 'Customer';
        $customer = findCustomerByEmail($normalizedEmail);
        $created = false;

        if (!$customer) {
            $now = now();
            $payload = [
                'email' => $normalizedEmail,
                'name' => $displayName,
                'password' => Hash::make(Str::random(40)),
            ];

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
            }
            if (customerSchemaHasColumn('createdAt')) {
                $payload['createdAt'] = $now;
            }
            if (customerSchemaHasColumn('updatedAt')) {
                $payload['updatedAt'] = $now;
            }
            if (customerSchemaHasColumn('created_at')) {
                $payload['created_at'] = $now;
            }
            if (customerSchemaHasColumn('updated_at')) {
                $payload['updated_at'] = $now;
            }

            customerTableInsert($payload);
            $customer = findCustomerByEmail($normalizedEmail);
            $created = true;
        }

        if (!$customer) {
            return null;
        }

        if (trim((string) ($customer->name ?? '')) === '' && $displayName !== '') {
            $customer->name = $displayName;
            $customer->save();
        }

        if ($created) {
            sendCustomerPortalRegistrationNotification($normalizedEmail, $displayName, true);

            try {
                $token = Password::broker('customer_users')->createToken($customer);
                $customer->sendPasswordResetNotification($token);
            } catch (\Throwable $e) {
                Log::warning('Failed to send customer password setup link after booking.', [
                    'email' => $normalizedEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $customer;
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

if (!function_exists('canModeratePortalFinance')) {
    function canModeratePortalFinance(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN_FINANCE'], true);
    }
}

if (!function_exists('canModerateListings')) {
    function canModerateListings(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('canManageContent')) {
    function canManageContent(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN_MEDIA'], true);
    }
}

if (!function_exists('canEditorialReview')) {
    function canEditorialReview(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return currentPortalAdminRole() === 'ADMIN_SUPER';
    }
}

if (!function_exists('portalFinancePolicySettingKey')) {
    function portalFinancePolicySettingKey(): string
    {
        return 'reservation_tax_transfer_policy';
    }
}

if (!function_exists('portalFinanceLoadReservationPolicy')) {
    function portalFinanceLoadReservationPolicy(): array
    {
        return ReservationPricingPolicy::loadPolicy();
    }
}

if (!function_exists('portalFinanceSaveReservationPolicy')) {
    function portalFinanceSaveReservationPolicy(array $policy, ?int $actorUserId = null): void
    {
        if (!Schema::hasTable('portal_finance_settings')) {
            return;
        }

        DB::table('portal_finance_settings')->updateOrInsert(
            ['setting_key' => portalFinancePolicySettingKey()],
            [
                'value_decimal' => null,
                'value_string' => null,
                'value_json' => json_encode(ReservationPricingPolicy::normalizePolicy($policy)),
                'updated_by_user_id' => $actorUserId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}

if (!function_exists('portalFinanceTaxComponents')) {
    function portalFinanceTaxComponents(?array $policy = null): array
    {
        $effectivePolicy = ReservationPricingPolicy::normalizePolicy($policy ?? portalFinanceLoadReservationPolicy());
        $components = $effectivePolicy['tax_components'] ?? [];

        return is_array($components) ? array_values($components) : [];
    }
}

if (!function_exists('portalFinanceUpsertTaxComponent')) {
    function portalFinanceUpsertTaxComponent(array $component, ?int $actorUserId = null): array
    {
        $policy = portalFinanceLoadReservationPolicy();
        $existing = portalFinanceTaxComponents($policy);
        $normalized = ReservationPricingPolicy::normalizeTaxComponents([$component]);
        if ($normalized === []) {
            return $policy;
        }

        $candidate = $normalized[0];
        $code = (string) ($candidate['code'] ?? '');

        $updated = [];
        $replaced = false;
        foreach ($existing as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ((string) ($row['code'] ?? '') === $code) {
                $updated[] = $candidate;
                $replaced = true;
            } else {
                $updated[] = $row;
            }
        }

        if (!$replaced) {
            $updated[] = $candidate;
        }

        $policy['tax_components'] = array_values($updated);
        portalFinanceSaveReservationPolicy($policy, $actorUserId);

        return $policy;
    }
}

if (!function_exists('portalFinanceDeleteTaxComponent')) {
    function portalFinanceDeleteTaxComponent(string $code, ?int $actorUserId = null): array
    {
        $policy = portalFinanceLoadReservationPolicy();
        $existing = portalFinanceTaxComponents($policy);
        $code = strtolower(trim($code));

        $policy['tax_components'] = array_values(array_filter($existing, static function ($row) use ($code): bool {
            return is_array($row) && strtolower(trim((string) ($row['code'] ?? ''))) !== $code;
        }));

        portalFinanceSaveReservationPolicy($policy, $actorUserId);

        return $policy;
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

if (!function_exists('vendorMediaStorageUrlFromPath')) {
    function vendorMediaStorageUrlFromPath(?string $path): ?string
    {
        $normalized = trim(str_replace('\\', '/', (string) $path));
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return $normalized;
        }

        if (preg_match('#/storage/app/public/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        $normalized = ltrim($normalized, '/');

        return $normalized !== '' ? ('/storage/' . $normalized) : null;
    }
}

if (!function_exists('getAvailableCategories')) {
    function getAvailableCategories(): array
    {
        $defaultCategories = [];
        
        // Get all categories from the uniform icon system
        $allCategoryIcons = UniformIconSystem::getAllCategoryIcons();
        foreach ($allCategoryIcons as $key => $info) {
            $defaultCategories[$key] = [
                'label' => $info['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'icon' => $info['icon'] ?? 'fa-solid fa-location-dot',
                'subtitle' => match ($key) {
                    'accommodation' => 'Hotels, villas, guesthouses',
                    'marine-transport' => 'Speedboats, ferries, and water transfers',
                    'land-transport' => 'Cars, vans, and local ground transfers',
                    'excursion' => 'Diving, snorkel, island tours',
                    'remote_workspace' => 'Wi-Fi, desks, quiet corners',
                    'conference_room' => 'Meeting and event spaces',
                    'resort_day_visit' => 'Day access and passes',
                    'restaurant' => 'Dining and local cuisine',
                    'vehicle_rental' => 'Cars, bikes, vans and more',
                    default => '',
                },
                'color' => $info['color'] ?? '#0f6179',
            ];
        }

        if (!Schema::hasTable('vendor_properties') || !Schema::hasColumn('vendor_properties', 'listing_category')) {
            return $defaultCategories;
        }

        try {
            $dbCategories = DB::table('vendor_properties')
                ->where('status', 'active')
                ->whereNotNull('listing_category')
                ->distinct()
                ->pluck('listing_category')
                ->filter(static fn ($cat) => !empty(trim((string) $cat)))
                ->map(static fn ($cat) => strtolower(trim((string) $cat)))
                ->unique()
                ->values();

            if ($dbCategories->isEmpty()) {
                return $defaultCategories;
            }

            $extraCategories = $dbCategories
                ->reject(static fn ($key) => array_key_exists($key, $defaultCategories))
                ->mapWithKeys(static fn ($key) => [
                    $key => [
                        'label' => ucfirst(str_replace(['_', '-'], ' ', $key)),
                        'icon' => 'fa-solid fa-location-dot',
                        'subtitle' => '',
                        'color' => '#0f6179',
                    ],
                ])
                ->toArray();

            return array_merge($defaultCategories, $extraCategories);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch available categories', ['error' => $e->getMessage()]);
            return $defaultCategories;
        }
    }
}

Route::get('/', function () {
    $apiBase = workationApiBase();
    $homeHeroBackgroundUrl = trim((string) env('HOME_HERO_IMAGE_URL', ''));
    $hasManagedHomeHeroImage = false;
    if (Schema::hasTable('portal_finance_settings')) {
        $managedHomeHeroImage = DB::table('portal_finance_settings')
            ->where('setting_key', 'home_hero_image_url')
            ->value('value_string');
        if (is_string($managedHomeHeroImage) && trim($managedHomeHeroImage) !== '') {
            $homeHeroBackgroundUrl = trim($managedHomeHeroImage);
            $hasManagedHomeHeroImage = true;
        }
    }

    if ($hasManagedHomeHeroImage) {
        $homeHeroBackgroundUrl = '/media/portal/hero/home';
    } else {
        $homeHeroBackgroundUrl = portalManagedMediaUrlFromPath($homeHeroBackgroundUrl) ?? $homeHeroBackgroundUrl;
    }

    if ($homeHeroBackgroundUrl === '') {
        $seasonalHeroPath = public_path('images/home-hero-seasonal.jpg');
        if (is_file($seasonalHeroPath)) {
            $homeHeroBackgroundUrl = '/images/home-hero-seasonal.jpg';
        }
    }

    // Keep home sidebar identical to category pages for uniform navigation.
    $homeTopCategoryLinks = collect([
        ['icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas', 'url' => '/catalog/accommodation'],
        ['icon' => 'fa-solid fa-water', 'title' => 'Marine Transport', 'subtitle' => 'Speedboats & water transfers', 'url' => '/catalog/marine-transport'],
        ['icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers', 'url' => '/catalog/land-transport'],
        ['icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'subtitle' => 'Tours and activities', 'url' => '/catalog/excursion'],
        ['icon' => 'fa-solid fa-map-location-dot', 'title' => 'Things to Do', 'subtitle' => 'Must-try island activities', 'url' => '/blog'],
        ['icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces', 'url' => '/catalog/remote_workspace'],
        ['icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces', 'url' => '/catalog/conference_room'],
        ['icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'subtitle' => 'Day-use resort offers', 'url' => '/catalog/resort_day_visit'],
        ['icon' => 'fa-solid fa-utensils', 'title' => 'Restaurant', 'subtitle' => 'Dining experiences', 'url' => '/catalog/restaurant'],
        ['icon' => 'fa-solid fa-car', 'title' => 'Vehicle Rental', 'subtitle' => 'Cars and local rentals', 'url' => '/catalog/vehicle_rental'],
    ]);

    $homePromoBanner = [
        'message' => '🎉 Offers & Promotions: Save up to 25% on selected stays and transfer bundles this week.',
        'url' => '/catalog/accommodation?sort=price_low_high',
        'cta' => 'View Promotions',
    ];

    $homeTrendingChips = collect(['Top Islands', 'Top Cities', 'Top Atolls', 'Newly Rising']);

    $homeCuratedDestinationImages = [
        'maafushi' => '/images/home/destinations/maafushi-island.svg',
        'maafushi_island' => '/images/home/destinations/maafushi-island.svg',
        'male' => '/images/home/destinations/male-city.svg',
        'male_city' => '/images/home/destinations/male-city.svg',
        'baa_atoll' => '/images/home/destinations/baa-atoll.svg',
        'ari_atoll' => '/images/home/destinations/ari-atoll.svg',
        'hulhumale' => '/images/home/destinations/hulhumale-seafront.svg',
        'hulhumale_seafront' => '/images/home/destinations/hulhumale-seafront.svg',
        'thulusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'ukulhas' => '/images/home/destinations/ukulhas-island.svg',
        'ukulhas_island' => '/images/home/destinations/ukulhas-island.svg',
        'dhigurah' => '/images/home/destinations/dhigurah-island.svg',
        'dhigurah_island' => '/images/home/destinations/dhigurah-island.svg',
    ];

    $homeDatabaseDestinationImages = [];
    if (Schema::hasTable('islands')) {
        $islandRows = DB::table('islands')
            ->select(['name', 'slug', 'photo_path'])
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->limit(1500)
            ->get();

        foreach ($islandRows as $row) {
            $imageUrl = portalManagedMediaUrlFromPath((string) ($row->photo_path ?? ''));
            if ($imageUrl === null || $imageUrl === '') {
                continue;
            }

            $nameKey = portalNormalizeDestinationMediaKey((string) ($row->name ?? ''));
            $slugKey = portalNormalizeDestinationMediaKey((string) ($row->slug ?? ''));
            $islandNameKey = $nameKey !== '' ? portalNormalizeDestinationMediaKey((string) ($row->name ?? '') . ' island') : '';

            foreach ([$nameKey, $slugKey, $islandNameKey] as $candidateKey) {
                if ($candidateKey === '' || array_key_exists($candidateKey, $homeDatabaseDestinationImages)) {
                    continue;
                }
                $homeDatabaseDestinationImages[$candidateKey] = $imageUrl;
            }
        }
    }

    if (Schema::hasTable('atolls')) {
        $atollRows = DB::table('atolls')
            ->select(['name', 'slug', 'code', 'photo_path'])
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->limit(300)
            ->get();

        foreach ($atollRows as $row) {
            $imageUrl = portalManagedMediaUrlFromPath((string) ($row->photo_path ?? ''));
            if ($imageUrl === null || $imageUrl === '') {
                continue;
            }

            $nameKey = portalNormalizeDestinationMediaKey((string) ($row->name ?? ''));
            $slugKey = portalNormalizeDestinationMediaKey((string) ($row->slug ?? ''));
            $atollNameKey = $nameKey !== '' ? portalNormalizeDestinationMediaKey((string) ($row->name ?? '') . ' atoll') : '';
            $codeKey = portalNormalizeDestinationMediaKey((string) ($row->code ?? ''));

            foreach ([$nameKey, $slugKey, $atollNameKey, $codeKey] as $candidateKey) {
                if ($candidateKey === '' || array_key_exists($candidateKey, $homeDatabaseDestinationImages)) {
                    continue;
                }
                $homeDatabaseDestinationImages[$candidateKey] = $imageUrl;
            }
        }
    }

    $homeDestinationMediaOverrides = collect();
    if (Schema::hasTable('portal_destination_media_overrides')) {
        $homeDestinationMediaOverrides = DB::table('portal_destination_media_overrides')
            ->orderBy('destination_name')
            ->pluck('image_value', 'destination_key');
    }

    $resolveHomeDestinationKey = static function (array $card): string {
        $candidates = [
            $card['title'] ?? null,
            $card['city'] ?? null,
            $card['location'] ?? null,
            $card['island'] ?? null,
            $card['atoll'] ?? null,
            $card['meta'] ?? null,
        ];

        $url = trim((string) ($card['url'] ?? ''));
        if ($url !== '') {
            $queryString = parse_url($url, PHP_URL_QUERY);
            if (is_string($queryString) && $queryString !== '') {
                parse_str($queryString, $queryParams);
                if (isset($queryParams['q'])) {
                    $candidates[] = $queryParams['q'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = portalNormalizeDestinationMediaKey(is_scalar($candidate) ? (string) $candidate : '');
            if ($normalized === '') {
                continue;
            }

            return $normalized;
        }

        return '';
    };

    $resolveHomeCuratedDestinationImage = static function (array $card) use ($homeCuratedDestinationImages, $homeDatabaseDestinationImages, $resolveHomeDestinationKey): ?string {
        $destinationKey = $resolveHomeDestinationKey($card);
        if ($destinationKey === '') {
            return null;
        }

        if (array_key_exists($destinationKey, $homeDatabaseDestinationImages)) {
            return $homeDatabaseDestinationImages[$destinationKey] ?? null;
        }

        return $homeCuratedDestinationImages[$destinationKey] ?? null;
    };

    $resolveHomeDestinationOverrideImage = static function (array $card) use ($homeDestinationMediaOverrides, $resolveHomeDestinationKey): ?string {
        $destinationKey = $resolveHomeDestinationKey($card);
        if ($destinationKey === '') {
            return null;
        }

        $storedValue = trim((string) ($homeDestinationMediaOverrides[$destinationKey] ?? ''));
        if ($storedValue === '') {
            return null;
        }

        return portalManagedMediaUrlFromPath($storedValue) ?? null;
    };

    $applyHomeDestinationImages = static function ($cards) use ($resolveHomeDestinationOverrideImage, $resolveHomeCuratedDestinationImage, $resolveHomeDestinationKey) {
        return collect($cards)->map(function ($card) use ($resolveHomeDestinationOverrideImage, $resolveHomeCuratedDestinationImage, $resolveHomeDestinationKey) {
            if (!is_array($card)) {
                return $card;
            }

            $destinationKey = $resolveHomeDestinationKey($card);
            if ($destinationKey !== '') {
                $card['destination_key'] = $destinationKey;
            }

            $overrideImage = $resolveHomeDestinationOverrideImage($card);
            if ($overrideImage !== null && $overrideImage !== '') {
                $card['image_url'] = $overrideImage;
                $card['fallback_image_url'] = $overrideImage;

                return $card;
            }

            $hasPrimaryImage = trim((string) ($card['image_url'] ?? '')) !== '';
            $hasFallbackImage = trim((string) ($card['fallback_image_url'] ?? '')) !== '';
            if ($hasPrimaryImage || $hasFallbackImage) {
                return $card;
            }

            $curatedImage = $resolveHomeCuratedDestinationImage($card);
            if ($curatedImage !== null && $curatedImage !== '') {
                $card['image_url'] = $curatedImage;
                $card['fallback_image_url'] = $curatedImage;
            }

            return $card;
        })->values();
    };

    $homeBrowseCards = collect([
        ['title' => 'Stay Options', 'subtitle' => 'Hotels, villas, guesthouses', 'url' => '/catalog/accommodation'],
        ['title' => 'Marine Transport', 'subtitle' => 'Speedboat, ferry, water transfer', 'url' => '/catalog/marine-transport'],
        ['title' => 'Land Transport', 'subtitle' => 'Car, van, and island transfers', 'url' => '/catalog/land-transport'],
        ['title' => 'Experiences', 'subtitle' => 'Diving, snorkel, island tours', 'url' => '/catalog/excursion'],
        ['title' => 'Work-Friendly', 'subtitle' => 'Wi-Fi, desks, quiet corners', 'url' => '/catalog/remote_workspace'],
        ['title' => 'Conference Rooms', 'subtitle' => 'Meeting and event-ready spaces', 'url' => '/catalog/conference_room'],
        ['title' => 'Deals Zone', 'subtitle' => 'Promotions and last-minute value', 'url' => '/catalog/accommodation?sort=price_low_high'],
    ]);

    $homeTrendingCards = collect([
        ['title' => 'Maafushi Island', 'subtitle' => 'Most searched for affordable island escapes.', 'url' => '/catalog/accommodation?q=Maafushi'],
        ['title' => 'Male City', 'subtitle' => 'Convenient urban stays and transfer access.', 'url' => '/catalog/accommodation?q=Male'],
        ['title' => 'Baa Atoll', 'subtitle' => 'Nature-rich stays and iconic snorkeling spots.', 'url' => '/catalog/accommodation?q=Baa+Atoll'],
        ['title' => 'Ari Atoll', 'subtitle' => 'Popular for diving and premium island resorts.', 'url' => '/catalog/accommodation?q=Ari+Atoll'],
    ]);

    $homeWeekendDealCards = collect([
        ['title' => '2-Night Beach Stay', 'subtitle' => 'Weekend promo with breakfast included.', 'url' => '/catalog/accommodation?q=beach&sort=price_low_high'],
        ['title' => 'Stay + Transfer Bundle', 'subtitle' => 'Save when you combine stay and transport.', 'url' => '/catalog/marine-transport?sort=price_low_high'],
        ['title' => 'Family Weekend Pack', 'subtitle' => 'Room upgrade and activity credits included.', 'url' => '/catalog/accommodation?q=family&sort=most_wanted'],
        ['title' => 'Couple Escape Offer', 'subtitle' => 'Curated stay options for a quick retreat.', 'url' => '/catalog/accommodation?q=couple&sort=highest_reviews'],
    ]);

    $homeLovedCards = collect([
        ['title' => 'Hulhumale Seafront', 'subtitle' => 'Consistently high ratings for convenience.', 'url' => '/catalog/accommodation?q=Hulhumale'],
        ['title' => 'Thulusdhoo Island', 'subtitle' => 'Guest favorite for surf culture and charm.', 'url' => '/catalog/accommodation?q=Thulusdhoo'],
        ['title' => 'Ukulhas Island', 'subtitle' => 'Loved for clean beaches and relaxed stays.', 'url' => '/catalog/accommodation?q=Ukulhas'],
        ['title' => 'Dhigurah Island', 'subtitle' => 'Strong demand for reef and marine experiences.', 'url' => '/catalog/accommodation?q=Dhigurah'],
    ]);

    $homeTrendingCards = $applyHomeDestinationImages($homeTrendingCards);
    $homeLovedCards = $applyHomeDestinationImages($homeLovedCards);

    $homeListingMediaByProperty = collect();

    if (Schema::hasTable('vendor_properties')) {
        $baseQuery = DB::table('vendor_properties')->where('status', 'active');
        $allProperties = $baseQuery->limit(300)->get();

        $propertyIds = $allProperties
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values();

        if (Schema::hasTable('vendor_listing_media') && $propertyIds->isNotEmpty()) {
            $mediaRows = DB::table('vendor_listing_media')
                ->where('entity_type', 'property')
                ->whereIn('entity_id', $propertyIds->all())
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->limit(1200)
                ->get();

            $homeListingMediaByProperty = $mediaRows->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
        }

        $resolveDirectMediaUrl = static function ($media): ?string {
            $filePath = trim((string) ($media->file_path ?? ''));
            if ($filePath === '') {
                return null;
            }

            if (!str_starts_with($filePath, 'http://') && !str_starts_with($filePath, 'https://')) {
                return null;
            }

            $resolved = trim((string) $filePath);

            if (str_starts_with($resolved, 'http://')) {
                $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
            }

            return $resolved;
        };

        $resolvePropertyImage = static function (int $propertyId) use ($homeListingMediaByProperty, $resolveDirectMediaUrl): ?string {
            if ($propertyId <= 0) {
                return null;
            }

            $mediaItems = collect($homeListingMediaByProperty->get($propertyId, collect()));
            $primaryMedia = $mediaItems->first();
            if (!$primaryMedia) {
                return null;
            }

            $directUrl = $resolveDirectMediaUrl($primaryMedia);
            if ($directUrl !== null && $directUrl !== '') {
                return $directUrl;
            }

            $mediaId = (int) ($primaryMedia->id ?? 0);
            if ($mediaId > 0) {
                return '/media/vendor/' . $mediaId . '/thumb';
            }

            return null;
        };

        $resolvePropertyFallbackImage = static function (int $propertyId) use ($homeListingMediaByProperty, $resolveDirectMediaUrl): ?string {
            if ($propertyId <= 0) {
                return null;
            }

            $mediaItems = collect($homeListingMediaByProperty->get($propertyId, collect()));
            $primaryMedia = $mediaItems->first();
            if (!$primaryMedia) {
                return null;
            }

            $directUrl = $resolveDirectMediaUrl($primaryMedia);
            if ($directUrl !== null && $directUrl !== '') {
                return $directUrl;
            }

            $mediaId = (int) ($primaryMedia->id ?? 0);
            if ($mediaId > 0) {
                return '/media/vendor/' . $mediaId . '/banner';
            }

            return null;
        };

        $propertyLocationLabel = static function ($property): string {
            $island = trim((string) ($property->island ?? ''));
            $city = trim((string) ($property->city ?? ''));
            $atoll = trim((string) ($property->atoll ?? ''));

            if ($island !== '' && $atoll !== '') {
                return $island . ', ' . $atoll;
            }

            if ($island !== '') {
                return $island;
            }

            if ($city !== '') {
                return $city;
            }

            return $atoll;
        };

        $propertyLocationValue = static function ($property): string {
            $island = trim((string) ($property->island ?? ''));
            if ($island !== '') {
                return $island;
            }

            $city = trim((string) ($property->city ?? ''));
            if ($city !== '') {
                return $city;
            }

            return trim((string) ($property->atoll ?? ''));
        };

        if (Schema::hasColumn('vendor_properties', 'listing_category')) {
            $categoryCounts = DB::table('vendor_properties')
                ->where('status', 'active')
                ->selectRaw("REPLACE(LOWER(listing_category), '-', '_') as category_key, COUNT(*) as total")
                ->groupBy('category_key')
                ->pluck('total', 'category_key');

            $normalizeHomeCategoryKey = static fn (?string $value): string => str_replace('-', '_', strtolower(trim((string) $value)));

            $categorySamples = $allProperties
                ->filter(static fn ($property) => trim((string) ($property->listing_category ?? '')) !== '')
                ->groupBy(static fn ($property) => $normalizeHomeCategoryKey((string) ($property->listing_category ?? ''))
                )->map(static fn ($group) => $group->first());

            $homeTopCategoryLinks = $homeTopCategoryLinks->map(function (array $card) use ($categoryCounts) {
                $key = strtolower(trim((string) ($card['title'] ?? '')));
                $categoryHint = match ($key) {
                    'accommodation' => 'accommodation',
                    'marine transport' => 'marine_transport',
                    'land transport' => 'land_transport',
                    'excursions' => 'excursion',
                    'remote workspace' => 'remote_workspace',
                    'conference rooms' => 'conference_room',
                    'resort day visit' => 'resort_day_visit',
                    'restaurant' => 'restaurant',
                    'vehicle rental' => 'vehicle_rental',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $total = (int) ($categoryCounts[$categoryHint] ?? 0);
                if ($total > 0) {
                    $card['subtitle'] = $total . ' active listings';
                }

                return $card;
            })->values();

            $homeBrowseCards = $homeBrowseCards->map(function (array $card) use ($categoryCounts) {
                $categoryHint = match ($card['title']) {
                    'Stay Options' => 'accommodation',
                    'Marine Transport' => 'marine_transport',
                    'Land Transport' => 'land_transport',
                    'Experiences' => 'excursion',
                    'Work-Friendly' => 'remote_workspace',
                    'Conference Rooms' => 'conference_room',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $total = (int) ($categoryCounts[$categoryHint] ?? 0);
                if ($total > 0) {
                    $card['subtitle'] = $total . ' active listings available';
                }

                return $card;
            });

            $homeBrowseCards = $homeBrowseCards->map(function (array $card) use ($categorySamples, $resolvePropertyImage, $resolvePropertyFallbackImage, $propertyLocationLabel) {
                $categoryHint = match ($card['title']) {
                    'Stay Options' => 'accommodation',
                    'Marine Transport' => 'marine_transport',
                    'Land Transport' => 'land_transport',
                    'Experiences' => 'excursion',
                    'Work-Friendly' => 'remote_workspace',
                    'Conference Rooms' => 'conference_room',
                    'Deals Zone' => 'accommodation',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $sample = $categorySamples->get($categoryHint);
                if (!$sample) {
                    return $card;
                }

                $sampleId = (int) ($sample->id ?? 0);
                $card['image_url'] = $resolvePropertyImage($sampleId);
                $card['fallback_image_url'] = $resolvePropertyFallbackImage($sampleId);
                $location = $propertyLocationLabel($sample);
                if ($location !== '') {
                    $card['subtitle'] = $location;
                }

                return $card;
            })->values();
        }

        $locationScores = [];
        foreach ($allProperties as $property) {
            $location = trim((string) ($property->island ?? ''));
            if ($location === '') {
                $location = trim((string) ($property->city ?? ''));
            }
            if ($location === '') {
                $location = trim((string) ($property->atoll ?? ''));
            }
            if ($location === '') {
                continue;
            }

            $key = strtolower($location);
            if (!array_key_exists($key, $locationScores)) {
                $locationScores[$key] = ['title' => $location, 'count' => 0, 'sample_property' => $property];
            }
            $locationScores[$key]['count']++;
        }

        if (!empty($locationScores)) {
            uasort($locationScores, static fn (array $a, array $b) => $b['count'] <=> $a['count']);
            $homeTrendingCards = collect(array_slice(array_values($locationScores), 0, 4))
                ->map(function (array $row) use ($resolvePropertyImage, $resolvePropertyFallbackImage) {
                    $sample = $row['sample_property'] ?? null;
                    $sampleId = (int) ($sample->id ?? 0);
                    $sampleCategory = strtolower(trim((string) ($sample->listing_category ?? 'accommodation')));

                    return [
                        'title' => $row['title'],
                        'subtitle' => $row['count'] . ' listings',
                        'url' => '/catalog/accommodation?q=' . urlencode($row['title']),
                        'image_url' => $resolvePropertyImage($sampleId),
                        'fallback_image_url' => $resolvePropertyFallbackImage($sampleId),
                        'category' => $sampleCategory,
                    ];
                })
                ->values();

            $homeTrendingCards = $applyHomeDestinationImages($homeTrendingCards);
        }

        $priceSorted = $allProperties
            ->filter(static fn ($property) => isset($property->base_price) && is_numeric($property->base_price))
            ->sortBy(static fn ($property) => (float) $property->base_price)
            ->values();

        if ($priceSorted->isNotEmpty()) {
            $homeWeekendDealCards = $priceSorted->take(4)->map(function ($property) use ($resolvePropertyImage, $resolvePropertyFallbackImage) {
                $name = trim((string) ($property->name ?? 'Weekend Offer'));
                $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
                $price = number_format((float) ($property->base_price ?? 0), 2);
                $propertyId = (int) ($property->id ?? 0);
                $place = trim((string) ($property->island ?? ''));
                if ($place === '') {
                    $place = trim((string) ($property->atoll ?? ''));
                }

                return [
                    'title' => $name,
                    'subtitle' => 'From ' . $currency . ' ' . $price,
                    'url' => '/property/' . $propertyId,
                    'image_url' => $resolvePropertyImage($propertyId),
                    'fallback_image_url' => $resolvePropertyFallbackImage($propertyId),
                    'meta' => $place,
                ];
            })->values();

            $lowestPrice = (float) ($priceSorted->first()->base_price ?? 0);
            $homePromoBanner = [
                'message' => '🎉 Offers & Promotions: Trending deals now live across stays and services from MVR ' . number_format($lowestPrice, 2) . '.',
                'url' => '/catalog/accommodation?sort=price_low_high',
                'cta' => 'Explore Deals',
            ];
        }

        $reviewColumns = ['review_score', 'rating_average', 'average_rating', 'rating'];
        $popularityColumns = ['bookings_count', 'total_bookings', 'wishlist_count', 'view_count'];
        $sortColumn = null;
        foreach (array_merge($reviewColumns, $popularityColumns) as $column) {
            if (Schema::hasColumn('vendor_properties', $column)) {
                $sortColumn = $column;
                break;
            }
        }

        if ($sortColumn !== null) {
            $lovedRows = DB::table('vendor_properties')
                ->where('status', 'active')
                ->orderByDesc($sortColumn)
                ->orderByDesc('updated_at')
                ->limit(120)
                ->get();

            if ($lovedRows->isNotEmpty()) {
                $lovedDestinationCards = [];
                $seenLovedDestinations = [];

                foreach ($lovedRows as $property) {
                    $location = $propertyLocationValue($property);
                    $destinationKey = portalNormalizeDestinationMediaKey($location);
                    if ($location === '' || $destinationKey === '' || isset($seenLovedDestinations[$destinationKey])) {
                        continue;
                    }

                    $seenLovedDestinations[$destinationKey] = true;
                    $score = (string) ($property->{$sortColumn} ?? '0');
                    $propertyId = (int) ($property->id ?? 0);
                    $lovedDestinationCards[] = [
                        'title' => $location,
                        'subtitle' => 'Score ' . $score,
                        'url' => '/catalog/accommodation?q=' . urlencode($location),
                        'image_url' => $resolvePropertyImage($propertyId),
                        'fallback_image_url' => $resolvePropertyFallbackImage($propertyId),
                        'meta' => trim((string) ($property->atoll ?? '')),
                    ];

                    if (count($lovedDestinationCards) >= 4) {
                        break;
                    }
                }

                if ($lovedDestinationCards !== []) {
                    $homeLovedCards = collect($lovedDestinationCards)->values();
                }

                $homeLovedCards = $applyHomeDestinationImages($homeLovedCards);
            }
        }
    }

    $featuredBlogPost = null;
    if (Schema::hasTable('blog_posts')) {
        $featuredBlogPost = BlogPost::query()
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->first();
    }

    return view('welcome', [
        'apiBase' => $apiBase,
        'homeHeroBackgroundUrl' => $homeHeroBackgroundUrl,
        'homeTopCategoryLinks' => $homeTopCategoryLinks,
        'homePromoBanner' => $homePromoBanner,
        'homeTrendingChips' => $homeTrendingChips,
        'homeBrowseCards' => $homeBrowseCards,
        'homeTrendingCards' => $homeTrendingCards,
        'homeWeekendDealCards' => $homeWeekendDealCards,
        'homeLovedCards' => $homeLovedCards,
        'featuredBlogPost' => $featuredBlogPost,
        'activityLinks' => [
            [
                'label' => 'Strict Live Preflight PASS - Run 22991556615',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22991556615',
            ],
            [
                'label' => 'Strict Live Preflight PASS - Run 22992285238',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22992285238',
            ],
            [
                'label' => 'Promotion Evidence - Run 22991538950',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22991538950',
            ],
        ],
        'artifactLinks' => [
            [
                'label' => 'Launch Approval Record (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/launch-final-approval-record-2026-03-18.md',
            ],
            [
                'label' => 'Production Verification Report (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/production-verification-report-2026-03-18.md',
            ],
            [
                'label' => 'Alert Routing Verification (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/alert-routing-verification-2026-03-18.md',
            ],
        ],
    ]);
});

Route::get('/privacy-policy', function () {
    return response()->view('privacy-policy');
});

Route::get('/terms-of-service', function () {
    return response()->view('terms-of-service');
});

Route::get('/things-to-do', function () {
    return redirect('/catalog/excursion?sort=most_wanted');
});

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

Route::get('/blog', function () {
    return view('blog-index', buildBlogIndexPayload());
});

Route::get('/blog/category/{category}', function (string $category) {
    return view('blog-index', buildBlogIndexPayload($category, null));
});

Route::get('/blog/tag/{tag}', function (string $tag) {
    return view('blog-index', buildBlogIndexPayload(null, $tag));
});

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

    $storedValue = '';
    if ($normalizedSlot === 'home') {
        if (Schema::hasTable('portal_finance_settings')) {
            $storedValue = trim((string) (DB::table('portal_finance_settings')
                ->where('setting_key', 'home_hero_image_url')
                ->value('value_string') ?? ''));
        }
        if ($storedValue === '') {
            $storedValue = trim((string) env('HOME_HERO_IMAGE_URL', ''));
        }
    } else {
        if (!Schema::hasTable('portal_finance_settings')) {
            return $placeholderResponse();
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
                $storedValue = $candidateValue;
                break;
            }
        }

        // Legacy compatibility: match odd historical key shapes by normalized suffix.
        if ($storedValue === '') {
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
                    $storedValue = $rowValue;
                    break;
                }
            }
        }
    }

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
            // Skip exists() check (separate HeadObject) — get() returns null when absent.
            $binary = $disk->get($relativePath);
            if (!is_string($binary) || $binary === '') {
                continue;
            }

            $mime = $inferMime($relativePath);
            return response($binary, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=300, stale-while-revalidate=3600',
                'ETag' => $etag,
            ]);
        } catch (\Throwable $e) {
            continue;
        }
    }

    $localPublicPath = 'public/' . ltrim($relativePath, '/');
    if (Storage::disk('local')->exists($localPublicPath)) {
        $binary = Storage::disk('local')->get($localPublicPath);
        if (is_string($binary) && $binary !== '') {
            $mime = $inferMime($localPublicPath);

            // Self-heal legacy fallback records: when a hero points to __public__/..., try
            // promoting the local file into managed storage and update the settings row.
            if (str_starts_with($storedValue, '__public__/') && $portalDiskName !== 'public' && Schema::hasTable('portal_finance_settings')) {
                try {
                    $managedDisk = Storage::disk($portalDiskName);
                    $candidatePaths = [$relativePath];
                    if (str_starts_with($relativePath, 'portal-admin/')) {
                        $candidatePaths[] = 'blog/inline/' . ltrim($relativePath, '/');
                    }
                    $candidatePaths = array_values(array_unique($candidatePaths));

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
                    }
                } catch (\Throwable $e) {
                    // keep serving local file even if promotion fails
                }
            }

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

// /portal/admin/hero-debug — diagnostic for hero media proxy failures
Route::get('/portal/admin/hero-debug', function () {
    // Temporarily no auth check for debugging

    $slot = 'home';
    $storedValue = '';
    if (Schema::hasTable('portal_finance_settings')) {
        $storedValue = trim((string) (DB::table('portal_finance_settings')
            ->where('setting_key', 'home_hero_image_url')
            ->value('value_string') ?? ''));
    }
    if ($storedValue === '') {
        $storedValue = trim((string) env('HOME_HERO_IMAGE_URL', ''));
    }

    $portalDiskName = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($portalDiskName === '') {
        $portalDiskName = 'public';
    }

    $relativePath = null;
    if ($storedValue !== '') {
        if (str_starts_with($storedValue, '__public__/')) {
            $relativePath = ltrim(substr($storedValue, strlen('__public__/')), '/');
        } else {
            $relativePath = portalManagedMediaRelativePath($storedValue);
        }
    }

    $diskNames = array_values(array_unique(array_filter([$portalDiskName, 'public', 'local'])));
    $diskResults = [];

    if ($relativePath !== null && $relativePath !== '') {
        foreach ($diskNames as $diskName) {
            $diskResults[$diskName] = ['status' => 'unknown', 'paths_checked' => []];
            try {
                $disk = Storage::disk($diskName);
                $diskResults[$diskName]['status'] = 'disk_ok';
            } catch (\Throwable $e) {
                $diskResults[$diskName]['status'] = 'disk_init_failed: ' . $e->getMessage();
                continue;
            }

            try {
                $exists = $disk->exists($relativePath);
                $diskResults[$diskName]['paths_checked'][$relativePath] = $exists ? 'EXISTS' : 'not_found';
                if ($exists) {
                    try {
                        $size = $disk->size($relativePath);
                        $diskResults[$diskName]['paths_checked'][$relativePath] = 'EXISTS (size=' . $size . ')';
                    } catch (\Throwable $e) {
                        // ignore size error
                    }
                }
            } catch (\Throwable $e) {
                $diskResults[$diskName]['paths_checked'][$relativePath] = 'error: ' . $e->getMessage();
            }
        }
    }

    return response()->json([
        'slot' => $slot,
        'stored_value' => $storedValue,
        'is_external_url' => Str::startsWith($storedValue, ['http://', 'https://']) ? true : false,
        'relative_path' => $relativePath,
        'portal_media_disk_config' => $portalDiskName,
        'env_PORTAL_MEDIA_DISK' => env('PORTAL_MEDIA_DISK', '(not set)'),
        'env_VENDOR_MEDIA_DISK' => env('VENDOR_MEDIA_DISK', '(not set)'),
        'env_HOME_HERO_IMAGE_URL' => env('HOME_HERO_IMAGE_URL', '(not set)'),
        'disk_results' => $diskResults,
        'proxy_route' => '/media/portal/hero/' . $slot,
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

    // ─────────────────────────────────────────────────────────────
    // Islands & Atolls Directory
    // /islands              – full index with atoll filter chips
// /islands/atoll/{slug} – atoll-filtered index
// /islands/{slug}       – individual island page
// ─────────────────────────────────────────────────────────────

if (!function_exists('buildIslandsIndexPayload')) {
    function buildIslandsIndexPayload(?string $activeAtollSlug, ?string $activeIslandType = null): array
    {
        $islandTypeAliases = [
            'local' => 'inhabited',
        ];
        $allowedIslandTypes = ['inhabited', 'uninhabited', 'resort'];
        $activeIslandType = is_string($activeIslandType) ? strtolower(trim($activeIslandType)) : null;
        if ($activeIslandType !== null && array_key_exists($activeIslandType, $islandTypeAliases)) {
            $activeIslandType = $islandTypeAliases[$activeIslandType];
        }
        if ($activeIslandType !== null && !in_array($activeIslandType, $allowedIslandTypes, true)) {
            $activeIslandType = null;
        }

        $atolls  = \App\Models\Atoll::query()->orderedByCode()->get();
        $query   = \App\Models\Island::with('atoll')->orderBy('atoll_id')->orderBy('name');

        if ($activeAtollSlug !== null) {
            // Match by atoll slug column first, fall back to name-slug
            $atoll = $atolls->first(fn ($a) =>
                ($a->slug ?? \Illuminate\Support\Str::slug($a->name)) === $activeAtollSlug
            );
            if ($atoll) {
                $query->where('atoll_id', $atoll->id);
            }
        }

        if ($activeIslandType !== null) {
            if ($activeIslandType === 'resort') {
                $query->where('island_type', 'resort');
            } elseif ($activeIslandType === 'inhabited') {
                $query->where(function ($typeQuery) {
                    $typeQuery->where('island_type', 'inhabited')
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('island_type')
                                ->where('is_inhabited', true);
                        });
                });
            } elseif ($activeIslandType === 'uninhabited') {
                $query->where(function ($typeQuery) {
                    $typeQuery->where('island_type', 'uninhabited')
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('island_type')
                                ->where('is_inhabited', false);
                        });
                });
            }
        }

        $islands = $query->get();

        // Group islands by atoll_id, then by island_type
        $groupedIslands = collect();
        foreach ($atolls as $atoll) {
            $atollIslands = $islands->where('atoll_id', $atoll->id);
            $groupedIslands->put($atoll->id, $atollIslands);
        }

        // Calculate stats from all islands (not filtered)
        $allIslands = \App\Models\Island::all();
        $islandStats = [
            'atolls_total' => (int) $atolls->count(),
            'islands_total' => (int) $allIslands->count(),
            'inhabited_total' => 0,
            'resort_total' => 0,
            'uninhabited_total' => 0,
        ];

        foreach ($allIslands as $island) {
            $typed = strtolower(trim((string) ($island->island_type ?? '')));
            if ($typed === 'resort') {
                $islandStats['resort_total']++;
            } elseif ($typed === 'inhabited') {
                $islandStats['inhabited_total']++;
            } elseif ($typed === 'uninhabited') {
                $islandStats['uninhabited_total']++;
            } elseif ((bool) ($island->is_inhabited ?? false)) {
                $islandStats['inhabited_total']++;
            } else {
                $islandStats['uninhabited_total']++;
            }
        }

        return [
            'islandStats' => $islandStats,
            'atolls' => $atolls,
            'groupedIslands' => $groupedIslands,
            'activeIslandType' => $activeIslandType,
        ];
    }
}

// must be before /islands/{slug} wildcard
Route::get('/islands/atoll/{atoll}', function (Request $request, string $atoll) {
    return view('islands-index', buildIslandsIndexPayload($atoll, $request->query('type')));
});

Route::get('/islands', function (Request $request) {
    return view('islands-index', buildIslandsIndexPayload(null, $request->query('type')));
});

Route::get('/islands/{slug}', function (string $slug) {
    // Try slug column first; fall back to name-derived slug match
    $island = \App\Models\Island::with('atoll')
        ->where('slug', $slug)
        ->first();

    if (!$island) {
        // Attempt name-based slug match (no DB index, iterate only when slug not set)
        $island = \App\Models\Island::with('atoll')
            ->whereNull('slug')
            ->get()
            ->first(fn ($i) => \Illuminate\Support\Str::slug($i->name) === $slug);
    }

    if (!$island) {
        abort(404);
    }

    $relatedIslands = collect();
    if ($island->atoll_id) {
        $relatedIslands = \App\Models\Island::with('atoll')
            ->where('atoll_id', $island->atoll_id)
            ->where('id', '!=', $island->id)
            ->orderBy('name')
            ->limit(5)
            ->get();
    }

    return view('island-show', [
        'island'         => $island,
        'relatedIslands' => $relatedIslands,
    ]);
});

Route::get('/catalog/{category}', function (Request $request, string $category) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas, and guesthouses.', 'hero_image_url' => ''],
        'marine-transport' => ['label' => 'Marine Transport', 'subtitle' => 'Speedboats, dhonis, and water transfers between islands.', 'hero_image_url' => ''],
        'land-transport' => ['label' => 'Land Transport', 'subtitle' => 'Cars, vans, and local ground transfers.', 'hero_image_url' => ''],
        'excursion' => ['label' => 'Excursion', 'subtitle' => 'Experiences, tours, and activity packages.', 'hero_image_url' => ''],
        'water_sports' => ['label' => 'Water Sports', 'subtitle' => 'Diving, snorkeling, and sea activity experiences.', 'hero_image_url' => ''],
        'remote_workspace' => ['label' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces and productivity stays.', 'hero_image_url' => ''],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'subtitle' => 'Hotel conference rooms, halls, and meeting spaces for events, training, seminars.', 'hero_image_url' => ''],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'subtitle' => 'Day access offers for top resort properties.', 'hero_image_url' => ''],
        'restaurant' => ['label' => 'Restaurant', 'subtitle' => 'Island-specific dining - find restaurants on your island.', 'hero_image_url' => ''],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'subtitle' => 'Cars, bikes, speedboats, and private vessel hire by island.', 'hero_image_url' => ''],
    ];

    $categoryKey = strtolower(trim($category));
    if (!array_key_exists($categoryKey, $categoryMap)) {
        abort(404);
    }

    // Map URL slug (hyphens) to DB listing_category value (underscores)
    $dbCategoryKey = str_replace('-', '_', $categoryKey);

    if (Schema::hasTable('portal_finance_settings')) {
        $normalizeSettingSuffix = static function (string $value): string {
            return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
        };

        $categoryKeyVariants = array_values(array_unique(array_filter([
            $categoryKey,
            str_replace('-', '_', $categoryKey),
            str_replace('_', '-', $categoryKey),
        ], static fn ($value) => is_string($value) && trim($value) !== '')));
        $categorySettingKeys = array_map(static fn (string $variant) => 'catalog_hero_image_' . $variant, $categoryKeyVariants);
        $managedCategoryHeroValues = DB::table('portal_finance_settings')
            ->whereIn('setting_key', $categorySettingKeys)
            ->pluck('value_string', 'setting_key');

        $managedCategoryHeroImage = '';
        foreach ($categorySettingKeys as $settingKey) {
            $candidateValue = trim((string) ($managedCategoryHeroValues[$settingKey] ?? ''));
            if ($candidateValue !== '') {
                $managedCategoryHeroImage = $candidateValue;
                break;
            }
        }

        // Legacy compatibility: tolerate historical key formats beyond _ / - variants.
        if ($managedCategoryHeroImage === '') {
            $targetSuffix = $normalizeSettingSuffix($categoryKey);
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
                    $managedCategoryHeroImage = $rowValue;
                    break;
                }
            }
        }

        if ($managedCategoryHeroImage !== '') {
            // Always use the slot proxy URL so category hero updates/removals are
            // reflected immediately without stale direct-object cache artifacts.
            $categoryMap[$categoryKey]['hero_image_url'] = '/media/portal/hero/' . $categoryKey;
        }
    }

    $resolvedCategoryHeroImage = trim((string) ($categoryMap[$categoryKey]['hero_image_url'] ?? ''));
    $resolvedCategoryHeroImage = portalManagedMediaUrlFromPath($resolvedCategoryHeroImage) ?? $resolvedCategoryHeroImage;
    $categoryMap[$categoryKey]['hero_image_url'] = $resolvedCategoryHeroImage;

    $queryText = trim((string) $request->query('q', ''));
    $atollFilter = trim((string) $request->query('atoll', ''));
    $islandFilter = trim((string) $request->query('island', ''));
    $currentIsland = trim((string) $request->query('current_island', ''));
    $pickupIsland = trim((string) $request->query('pickup_island', ''));
    $reservationDatetime = trim((string) $request->query('reservation_datetime', ''));
    $partySize = max(1, (int) $request->query('party_size', 2));
    $vehicleKind = trim((string) $request->query('vehicle_kind', ''));
    $activityType = trim((string) $request->query('activity_type', ''));
    $difficulty = trim((string) $request->query('difficulty', ''));
    $excursionDate = trim((string) $request->query('excursion_date', ''));
    $workspaceTypeFilter = trim((string) $request->query('workspace_type_filter', ''));
    $internetSpeed = trim((string) $request->query('internet_speed', ''));
    $workspaceStart = trim((string) $request->query('workspace_start', ''));
    $workspaceEnd = trim((string) $request->query('workspace_end', ''));
    $timeSlot = trim((string) $request->query('time_slot', ''));
    $facilityType = trim((string) $request->query('facility_type', ''));
    $visitDate = trim((string) $request->query('visit_date', ''));
    $conferenceEventType = trim((string) $request->query('conference_event_type', ''));
    $conferenceCapacity = (int) $request->query('conference_capacity', 0);
    $conferenceDate = trim((string) $request->query('conference_date', ''));
    $minPrice = (float) $request->query('min_price', 0);
    $maxPrice = (float) $request->query('max_price', 0);
    $sort = strtolower(trim((string) $request->query('sort', 'recommended')));

    // For island-specific categories (restaurant, vehicle_rental), fall back to
    // current_island or pickup_island when the generic island filter is not set.
    $effectiveIslandFilter = $islandFilter;
    if ($effectiveIslandFilter === '' && in_array($categoryKey, ['restaurant', 'vehicle_rental'], true)) {
        if ($currentIsland !== '') {
            $effectiveIslandFilter = $currentIsland;
        } elseif ($pickupIsland !== '') {
            $effectiveIslandFilter = $pickupIsland;
        }
    }

    $catalogProperties = collect();
    $catalogPropertyMediaByProperty = collect();
    $atollOptions = collect();
    $islandOptions = collect();

    if (Schema::hasTable('vendor_properties')) {
        $propertiesQuery = DB::table('vendor_properties')->where('status', 'active');
        if (Schema::hasColumn('vendor_properties', 'listing_category')) {
            $propertiesQuery->whereRaw('LOWER(listing_category) = ?', [$dbCategoryKey]);
        }

        $searchColumns = [];
        foreach (['name', 'listing_name', 'atoll', 'island', 'city', 'description'] as $candidateColumn) {
            if (Schema::hasColumn('vendor_properties', $candidateColumn)) {
                $searchColumns[] = $candidateColumn;
            }
        }

        if ($queryText !== '' && !empty($searchColumns)) {
            $propertiesQuery->where(function ($query) use ($searchColumns, $queryText) {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $query->where($column, 'like', '%' . $queryText . '%');
                    } else {
                        $query->orWhere($column, 'like', '%' . $queryText . '%');
                    }
                }
            });
        }

        if ($atollFilter !== '' && Schema::hasColumn('vendor_properties', 'atoll')) {
            $propertiesQuery->whereRaw('LOWER(atoll) = ?', [strtolower($atollFilter)]);
        }

        if ($effectiveIslandFilter !== '' && Schema::hasColumn('vendor_properties', 'island')) {
            $propertiesQuery->whereRaw('LOWER(island) = ?', [strtolower($effectiveIslandFilter)]);
        }

        if (Schema::hasColumn('vendor_properties', 'base_price')) {
            if ($minPrice > 0) {
                $propertiesQuery->where('base_price', '>=', $minPrice);
            }
            if ($maxPrice > 0) {
                $propertiesQuery->where('base_price', '<=', $maxPrice);
            }
        }

        $popularityColumns = ['bookings_count', 'total_bookings', 'wishlist_count', 'view_count'];
        $bookedColumns = ['bookings_count', 'total_bookings'];
        $reviewColumns = ['review_score', 'rating_average', 'average_rating', 'rating'];

        $firstExistingColumn = static function (array $columns): ?string {
            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_properties', $column)) {
                    return $column;
                }
            }

            return null;
        };

        $popularityColumn = $firstExistingColumn($popularityColumns);
        $bookedColumn = $firstExistingColumn($bookedColumns);
        $reviewColumn = $firstExistingColumn($reviewColumns);

        if ($sort === 'price_low_high' && Schema::hasColumn('vendor_properties', 'base_price')) {
            $propertiesQuery->orderBy('base_price');
        } elseif ($sort === 'price_high_low' && Schema::hasColumn('vendor_properties', 'base_price')) {
            $propertiesQuery->orderByDesc('base_price');
        } elseif ($sort === 'most_wanted' && $popularityColumn !== null) {
            $propertiesQuery->orderByDesc($popularityColumn);
        } elseif ($sort === 'most_booked' && $bookedColumn !== null) {
            $propertiesQuery->orderByDesc($bookedColumn);
        } elseif ($sort === 'highest_reviews' && $reviewColumn !== null) {
            $propertiesQuery->orderByDesc($reviewColumn);
        } else {
            $propertiesQuery->orderByDesc('updated_at');
        }

        $catalogProperties = $propertiesQuery->limit(80)->get();
        $propertyIds = $catalogProperties
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values();

        if (Schema::hasTable('vendor_listing_media') && $propertyIds->isNotEmpty()) {
            $mediaRows = DB::table('vendor_listing_media')
                ->where('entity_type', 'property')
                ->whereIn('entity_id', $propertyIds->all())
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->limit(600)
                ->get();

            $catalogPropertyMediaByProperty = $mediaRows->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
        }

        if (Schema::hasColumn('vendor_properties', 'atoll')) {
            $atollOptions = DB::table('vendor_properties')
                ->where('status', 'active')
                ->when(Schema::hasColumn('vendor_properties', 'listing_category'), function ($query) use ($dbCategoryKey) {
                    $query->whereRaw('LOWER(listing_category) = ?', [$dbCategoryKey]);
                })
                ->whereNotNull('atoll')
                ->where('atoll', '!=', '')
                ->distinct()
                ->orderBy('atoll')
                ->limit(120)
                ->pluck('atoll');
        }

        if (Schema::hasColumn('vendor_properties', 'island')) {
            $islandOptions = DB::table('vendor_properties')
                ->where('status', 'active')
                ->when(Schema::hasColumn('vendor_properties', 'listing_category'), function ($query) use ($dbCategoryKey) {
                    $query->whereRaw('LOWER(listing_category) = ?', [$dbCategoryKey]);
                })
                ->whereNotNull('island')
                ->where('island', '!=', '')
                ->distinct()
                ->orderBy('island')
                ->limit(120)
                ->pluck('island');
        }
    }

    return view('customer-category-catalog', [
        'apiBase' => workationApiBase(),
        'categoryKey' => $categoryKey,
        'categoryMeta' => $categoryMap[$categoryKey],
        'catalogProperties' => $catalogProperties,
        'catalogPropertyMediaByProperty' => $catalogPropertyMediaByProperty,
        'atollOptions' => $atollOptions,
        'islandOptions' => $islandOptions,
        'filters' => [
            'q' => $queryText,
            'atoll' => $atollFilter,
            'island' => $islandFilter,
            'current_island' => $currentIsland,
            'pickup_island' => $pickupIsland,
            'reservation_datetime' => $reservationDatetime,
            'party_size' => $partySize,
            'vehicle_kind' => $vehicleKind,
            'activity_type' => $activityType,
            'difficulty' => $difficulty,
            'excursion_date' => $excursionDate,
            'workspace_type_filter' => $workspaceTypeFilter,
            'internet_speed' => $internetSpeed,
            'workspace_start' => $workspaceStart,
            'workspace_end' => $workspaceEnd,
            'time_slot' => $timeSlot,
            'facility_type' => $facilityType,
            'visit_date' => $visitDate,
            'conference_event_type' => $conferenceEventType,
            'conference_capacity' => $conferenceCapacity,
            'conference_date' => $conferenceDate,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => $sort,
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'adults' => (int) $request->query('adults', 2),
            'children' => (int) $request->query('children', 0),
            'rooms' => (int) $request->query('rooms', 1),
            'origin_point' => trim((string) $request->query('origin_point', '')),
            'destination_point' => trim((string) $request->query('destination_point', '')),
            'travel_date' => trim((string) $request->query('travel_date', '')),
            'return_date' => trim((string) $request->query('return_date', '')),
            'pickup_date' => trim((string) $request->query('pickup_date', '')),
            'vehicle_type' => trim((string) $request->query('vehicle_type', '')), 
        ],
    ]);
});

Route::get('/property/{property}', function (Request $request, int $property) {
    if (!Schema::hasTable('vendor_properties')) {
        abort(404);
    }

    $propertyRow = DB::table('vendor_properties')
        ->where('id', $property)
        ->where('status', 'active')
        ->first();

    if (!$propertyRow) {
        abort(404);
    }

    $rooms = collect();
    if (Schema::hasTable('vendor_property_room_categories')) {
        $rooms = DB::table('vendor_property_room_categories')
            ->where('vendor_property_id', (int) $propertyRow->id)
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get();
    }

    $roomIds = $rooms->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn (int $id) => $id > 0)->values();
    $propertyMedia = collect();
    $roomMediaByRoom = collect();

    if (Schema::hasTable('vendor_listing_media')) {
        $mediaQuery = DB::table('vendor_listing_media');
        $mediaQuery->where(function ($query) use ($propertyRow, $roomIds) {
            $query->orWhere(function ($inner) use ($propertyRow) {
                $inner->where('entity_type', 'property')->where('entity_id', (int) $propertyRow->id);
            });

            if ($roomIds->isNotEmpty()) {
                $query->orWhere(function ($inner) use ($roomIds) {
                    $inner->where('entity_type', 'room')->whereIn('entity_id', $roomIds->all());
                });
            }
        });

        $mediaRows = $mediaQuery->orderByDesc('is_primary')->orderByDesc('created_at')->limit(500)->get();
        $propertyMedia = $mediaRows->filter(static fn ($m) => strtolower((string) ($m->entity_type ?? '')) === 'property')->values();
        $roomMediaByRoom = $mediaRows
            ->filter(static fn ($m) => strtolower((string) ($m->entity_type ?? '')) === 'room')
            ->groupBy(static fn ($m) => (int) ($m->entity_id ?? 0));
    }

    $mediaUrl = static function ($media, string $variant = 'banner'): ?string {
        $filePath = trim((string) ($media->file_path ?? ''));
        if ($filePath !== '' && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            $resolved = $filePath;
            $resolved = trim((string) $resolved);
            if ($resolved !== '') {
                if (str_starts_with($resolved, 'http://')) {
                    $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
                }

                return $resolved;
            }
        }

        $mediaId = (int) ($media->id ?? 0);
        if ($mediaId > 0) {
            return '/media/vendor/' . $mediaId . '/' . $variant;
        }

        return null;
    };

    $details = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($details)) {
        $details = [];
    }

    $facilityCandidates = [];
    foreach (['facilities', 'amenities', 'accommodation_facilities', 'property_amenities', 'property_features'] as $key) {
        if (!array_key_exists($key, $details)) {
            continue;
        }

        $value = $details[$key];
        if (is_array($value)) {
            $facilityCandidates = array_merge($facilityCandidates, $value);
        } elseif (is_string($value)) {
            $facilityCandidates = array_merge($facilityCandidates, preg_split('/[,\n]+/', $value) ?: []);
        }
    }

    $propertyFacilities = collect($facilityCandidates)
        ->map(static fn ($item) => trim((string) $item))
        ->filter(static fn ($item) => $item !== '')
        ->unique()
        ->values();

    $reviewColumn = collect(['review_score', 'rating_average', 'average_rating', 'rating'])
        ->first(static fn ($column) => Schema::hasColumn('vendor_properties', $column));
    $reviewCountColumn = collect(['review_count', 'rating_count', 'total_reviews'])
        ->first(static fn ($column) => Schema::hasColumn('vendor_properties', $column));

    $guestReviews = collect();
    $reviewTableCandidates = [
        'vendor_property_reviews',
        'property_reviews',
        'customer_reviews',
        'vendor_reviews',
    ];

    foreach ($reviewTableCandidates as $reviewTable) {
        if (!Schema::hasTable($reviewTable)) {
            continue;
        }

        $columns = Schema::getColumnListing($reviewTable);
        $propertyKey = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        $commentKey = collect(['review_comment', 'comment', 'review_text', 'feedback', 'notes'])
            ->first(static fn ($column) => in_array($column, $columns, true));

        if ($propertyKey === null || $commentKey === null) {
            continue;
        }

        $ratingKey = collect(['rating', 'rating_value', 'review_score', 'score'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        $nameKey = collect(['customer_name', 'guest_name', 'reviewer_name', 'name'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        $dateKey = collect(['created_at', 'reviewed_at', 'submitted_at', 'updated_at'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        $statusKey = collect(['status', 'review_status'])
            ->first(static fn ($column) => in_array($column, $columns, true));

        $reviewQuery = DB::table($reviewTable)->where($propertyKey, (int) $propertyRow->id);

        if ($statusKey !== null) {
            $reviewQuery->whereIn($statusKey, ['approved', 'published', 'active']);
        }

        if ($dateKey !== null) {
            $reviewQuery->orderByDesc($dateKey);
        } else {
            $reviewQuery->orderByDesc('id');
        }

        $rows = $reviewQuery->limit(8)->get();
        if ($rows->isEmpty()) {
            continue;
        }

        $guestReviews = $rows
            ->map(function ($row) use ($commentKey, $ratingKey, $nameKey, $dateKey) {
                $comment = trim((string) ($row->{$commentKey} ?? ''));
                if ($comment === '') {
                    return null;
                }

                return [
                    'name' => trim((string) ($nameKey ? ($row->{$nameKey} ?? '') : '')),
                    'comment' => $comment,
                    'rating' => $ratingKey ? (float) ($row->{$ratingKey} ?? 0) : 0.0,
                    'date' => $dateKey ? (string) ($row->{$dateKey} ?? '') : '',
                ];
            })
            ->filter()
            ->values();

        if ($guestReviews->isNotEmpty()) {
            break;
        }
    }

    $locationLine = trim(implode(', ', array_filter([
        trim((string) ($propertyRow->location ?? '')),
        trim((string) ($propertyRow->island ?? '')),
        trim((string) ($propertyRow->atoll ?? '')),
        trim((string) ($propertyRow->city ?? '')),
    ], static fn ($v) => $v !== '')));

    return view('property-profile', [
        'property' => $propertyRow,
        'propertyMedia' => $propertyMedia,
        'roomMediaByRoom' => $roomMediaByRoom,
        'rooms' => $rooms,
        'propertyFacilities' => $propertyFacilities,
        'locationLine' => $locationLine,
        'ratingValue' => $reviewColumn ? (float) ($propertyRow->{$reviewColumn} ?? 0) : 0,
        'ratingUsers' => $reviewCountColumn ? (int) ($propertyRow->{$reviewCountColumn} ?? 0) : 0,
        'guestReviews' => $guestReviews,
        'mediaUrl' => $mediaUrl,
        'prefill' => [
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'rooms' => max(1, (int) $request->query('rooms', 1)),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
        ],
    ]);
});

Route::get('/room/{room}', function (Request $request, int $room) {
    if (!Schema::hasTable('vendor_property_room_categories')) {
        abort(404);
    }

    $roomRow = DB::table('vendor_property_room_categories')->where('id', $room)->first();
    if (!$roomRow) {
        abort(404);
    }

    $propertyRow = Schema::hasTable('vendor_properties')
        ? DB::table('vendor_properties')->where('id', (int) ($roomRow->vendor_property_id ?? 0))->first()
        : null;

    if (!$propertyRow) {
        abort(404);
    }

    $roomMedia = collect();
    if (Schema::hasTable('vendor_listing_media')) {
        $roomMedia = DB::table('vendor_listing_media')
            ->where('entity_type', 'room')
            ->where('entity_id', (int) $roomRow->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();
    }

    $roomFeatures = collect(preg_split('/[,\n]+/', (string) ($roomRow->amenities ?? '')) ?: [])
        ->merge(collect(preg_split('/[,\n]+/', (string) ($roomRow->bathroom_amenities ?? '')) ?: []))
        ->map(static fn ($v) => trim((string) $v))
        ->filter(static fn ($v) => $v !== '')
        ->unique()
        ->values();

    $propertyDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($propertyDetails)) {
        $propertyDetails = [];
    }

    $transferOptions = collect($propertyDetails['transfer_options'] ?? [])->values();
    if ($transferOptions->isEmpty()) {
        $transferOptions = collect([
            ['code' => 'shared_speedboat', 'label' => 'Shared Speedboat', 'adult_charge' => 35, 'child_charge' => 20],
            ['code' => 'private_speedboat', 'label' => 'Private Speedboat', 'adult_charge' => 120, 'child_charge' => 80],
            ['code' => 'seaplane', 'label' => 'Seaplane', 'adult_charge' => 420, 'child_charge' => 280],
        ]);
    }

    $transferOptions = $transferOptions->map(function ($option) {
        $code = trim((string) ($option['code'] ?? Str::slug((string) ($option['label'] ?? 'transfer'))));
        $label = trim((string) ($option['label'] ?? 'Transfer Option'));
        $baseCharge = (float) ($option['base_charge'] ?? 0);
        $adultCharge = (float) ($option['adult_charge'] ?? ($option['charge'] ?? 0));
        $childCharge = (float) ($option['child_charge'] ?? 0);

        return [
            'code' => $code,
            'label' => $label,
            'base_charge' => $baseCharge,
            'adult_charge' => $adultCharge,
            'child_charge' => $childCharge,
        ];
    })->values();

    $pricingConfig = [
        'tax_rate' => (float) ($propertyDetails['tax_rate'] ?? 16),
        'discount_percent' => (float) ($propertyDetails['promotion_discount_percent'] ?? 0),
    ];

    $bookingPolicies = [
        'inclusives' => collect($propertyDetails['inclusives'] ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values()->all(),
        'cancellation_policy' => trim((string) ($propertyDetails['cancellation_policy'] ?? 'Free cancellation up to 72 hours before check-in.')),
    ];

    $mediaUrl = static function ($media, string $variant = 'banner'): ?string {
        $filePath = trim((string) ($media->file_path ?? ''));
        if ($filePath !== '' && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            $resolved = $filePath;
            $resolved = trim((string) $resolved);
            if ($resolved !== '') {
                if (str_starts_with($resolved, 'http://')) {
                    $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
                }

                return $resolved;
            }
        }

        $mediaId = (int) ($media->id ?? 0);
        if ($mediaId > 0) {
            return '/media/vendor/' . $mediaId . '/' . $variant;
        }

        return null;
    };

    $sessionGuestName = trim((string) session('portal_customer_user', ''));
    $nameParts = preg_split('/\s+/', $sessionGuestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $prefillFirstName = (string) ($nameParts[0] ?? '');
    $prefillLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

    return view('room-profile', [
        'room' => $roomRow,
        'property' => $propertyRow,
        'roomMedia' => $roomMedia,
        'roomFeatures' => $roomFeatures,
        'transferOptions' => $transferOptions,
        'pricingConfig' => $pricingConfig,
        'bookingPolicies' => $bookingPolicies,
        'mediaUrl' => $mediaUrl,
        'prefill' => [
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
            'primary_first_name' => $prefillFirstName,
            'primary_last_name' => $prefillLastName,
            'primary_nationality' => '',
            'primary_email' => trim((string) session('portal_customer_email', '')),
            'primary_mobile' => '',
        ],
    ]);
});

Route::post('/booking/reserve', function (Request $request) {
    $payload = $request->validate([
        'property_id' => ['required', 'integer', 'min:1'],
        'room_id' => ['required', 'integer', 'min:1'],
        'checkin' => ['required', 'date'],
        'checkout' => ['required', 'date', 'after:checkin'],
        'adults' => ['required', 'integer', 'min:1', 'max:20'],
        'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name' => ['required', 'string', 'max:80'],
        'primary_nationality' => ['required', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => ['required', 'email', 'max:190'],
        'primary_mobile' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'additional_guest_details' => ['nullable', 'string', 'max:4000'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
        'transfer_charge' => ['nullable', 'numeric', 'min:0'],
        'room_subtotal' => ['nullable', 'numeric', 'min:0'],
        'discount_amount' => ['nullable', 'numeric', 'min:0'],
        'tax_amount' => ['nullable', 'numeric', 'min:0'],
        'total_amount' => ['nullable', 'numeric', 'min:0'],
    ], [
        'primary_first_name.required' => 'Primary guest first name is required.',
        'primary_last_name.required' => 'Primary guest last name is required.',
        'primary_nationality.required' => 'Primary guest nationality is required.',
        'primary_email.required' => 'Primary guest email is required.',
        'primary_email.email' => 'Please enter a valid email address for the primary guest.',
        'primary_mobile.required' => 'Primary guest mobile is required.',
        'primary_mobile.regex' => 'Please enter a valid primary guest mobile number.',
        'checkout.after' => 'Checkout date must be after check-in date.',
    ], [
        'primary_first_name' => 'primary guest first name',
        'primary_last_name' => 'primary guest last name',
        'primary_nationality' => 'primary guest nationality',
        'primary_email' => 'primary guest email',
        'primary_mobile' => 'primary guest mobile',
    ]);

    $propertyRow = Schema::hasTable('vendor_properties')
        ? DB::table('vendor_properties')->where('id', (int) $payload['property_id'])->first()
        : null;
    $roomRow = Schema::hasTable('vendor_property_room_categories')
        ? DB::table('vendor_property_room_categories')->where('id', (int) $payload['room_id'])->first()
        : null;

    if (!$propertyRow || !$roomRow) {
        abort(404);
    }

    // Listing-level publish gate: only approved listings can accept bookings.
    if (Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        $listingModerationStatus = strtolower(trim((string) ($propertyRow->listing_moderation_status ?? 'draft')));
        if ($listingModerationStatus !== 'approved') {
            return back()->withErrors(['booking' => 'This listing is not yet available for bookings. It is currently under review or pending approval.']);
        }
    }

    $checkin = Carbon::parse((string) $payload['checkin']);
    $checkout = Carbon::parse((string) $payload['checkout']);
    $nights = max(1, $checkin->diffInDays($checkout));
    $adults = (int) $payload['adults'];
    $children = (int) ($payload['children'] ?? 0);
    $guestCount = $adults + $children;
    $nightlyRate = (float) ($roomRow->base_price ?? $propertyRow->base_price ?? 0);
    $roomSubtotal = $nightlyRate * $nights;

    $propertyDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($propertyDetails)) {
        $propertyDetails = [];
    }

    $discountPercent = (float) ($propertyDetails['promotion_discount_percent'] ?? 0);
    $transferOptionCode = trim((string) ($payload['transfer_option'] ?? ''));
    $transferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null)
        ? $propertyDetails['transfer_rate_matrix']
        : [];
    $legacyTransferRates = is_array($propertyDetails['transfer_rates'] ?? null)
        ? $propertyDetails['transfer_rates']
        : [];
    $transferOptions = collect($propertyDetails['transfer_options'] ?? [])->map(function ($option) use ($transferRateMatrix, $legacyTransferRates, $propertyDetails) {
        if (is_array($option)) {
            $code = strtolower(trim((string) ($option['code'] ?? '')));
            return $option + ['code' => $code];
        }

        $code = strtolower(trim((string) $option));
        $matrix = is_array($transferRateMatrix[$code] ?? null) ? $transferRateMatrix[$code] : [];
        $legacyRate = is_numeric($legacyTransferRates[$code] ?? null) ? (float) $legacyTransferRates[$code] : 0;

        return [
            'code' => $code,
            'label' => Str::headline(str_replace('_', ' ', $code)),
            'local_adult_charge' => (float) ($matrix['local_adult_charge'] ?? 0),
            'local_child_charge' => (float) ($matrix['local_child_charge'] ?? 0),
            'foreign_adult_charge' => (float) ($matrix['foreign_adult_charge'] ?? $legacyRate),
            'foreign_child_charge' => (float) ($matrix['foreign_child_charge'] ?? 0),
            'base_charge_local' => (float) ($propertyDetails['transfer_base_local'] ?? 0),
            'base_charge_foreign' => (float) ($propertyDetails['transfer_base_foreign'] ?? 0),
            'adult_charge' => $legacyRate,
            'child_charge' => 0,
        ];
    })->values()->all();
    $guestResidency = strtolower(trim((string) ($payload['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner((string) ($payload['primary_nationality'] ?? ''), null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $vendorTaxOverrides = [];
    if (isset($propertyDetails['vendor_tax_overrides']) && is_array($propertyDetails['vendor_tax_overrides'])) {
        $vendorTaxOverrides = $propertyDetails['vendor_tax_overrides'];
    }

    $roomCount = Schema::hasTable('vendor_property_room_categories')
        ? (int) DB::table('vendor_property_room_categories')->where('vendor_property_id', (int) $propertyRow->id)->count()
        : 0;

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => 'accommodation',
        'subtotal_amount' => $roomSubtotal,
        'discount_percent' => $discountPercent,
        'adults' => $adults,
        'children' => $children,
        'nights' => $nights,
        'room_count' => $roomCount,
        'primary_nationality' => (string) ($payload['primary_nationality'] ?? ''),
        'guest_residency' => $guestResidency,
        'transfer_option' => $transferOptionCode,
        'property_transfer_options' => $transferOptions,
        'transfer_charge_override' => $payload['transfer_charge'] ?? null,
        'vendor_tax_overrides' => $vendorTaxOverrides,
    ]);

    $discountAmount = (float) ($pricing['discount_amount'] ?? 0);
    $taxAmount = (float) ($pricing['total_tax_amount'] ?? 0);
    $transferCharge = (float) ($pricing['transfer_charge_total'] ?? 0);
    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? 0);

    $primaryFirstName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_first_name'])));
    $primaryLastName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_last_name'])));
    $primaryNationality = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_nationality'])));
    $primaryEmail = Str::lower(trim((string) $payload['primary_email']));
    $mobileRaw = trim((string) $payload['primary_mobile']);
    $primaryMobile = preg_replace('/[^0-9+]/', '', $mobileRaw) ?? $mobileRaw;
    $primaryMobile = preg_replace('/^\++/', '+', $primaryMobile) ?? $primaryMobile;
    $customerName = trim($primaryFirstName . ' ' . $primaryLastName);
    $customerEmail = $primaryEmail;
    $additionalGuestDetails = trim((string) ($payload['additional_guest_details'] ?? ''));

    provisionCustomerAccountFromBooking($customerEmail, $customerName);

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => (int) ($propertyRow->vendor_user_id ?? 0),
            'vendor_property_id' => (int) $propertyRow->id,
            'vendor_service_id' => null,
            'customer_name' => $customerName !== '' ? $customerName : 'Guest Customer',
            'customer_email' => $customerEmail !== '' ? $customerEmail : 'guest@workation.local',
            'start_at' => $checkin->copy()->startOfDay(),
            'end_at' => $checkout->copy()->startOfDay(),
            'guests' => max(1, $guestCount),
            'total_amount' => $totalAmount,
            'currency' => strtoupper(trim((string) ($roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'room_id' => (int) $roomRow->id,
                'room_name' => (string) ($roomRow->name ?? 'Room'),
                'adults' => $adults,
                'children' => $children,
                'primary_first_name' => $primaryFirstName,
                'primary_last_name' => $primaryLastName,
                'primary_nationality' => $primaryNationality,
                'guest_residency' => $guestResidency,
                'primary_email' => $primaryEmail,
                'primary_mobile' => $primaryMobile,
                'additional_guest_details' => $additionalGuestDetails,
                'transfer_option' => (string) ($pricing['transfer_option'] ?? $transferOptionCode),
                'transfer_option_label' => (string) ($pricing['transfer_option_label'] ?? ''),
                'transfer_charge' => $transferCharge,
                'transfer_charge_total' => $transferCharge,
                'transfer_local_adult_rate' => (float) ($pricing['transfer_local_adult_rate'] ?? 0),
                'transfer_local_child_rate' => (float) ($pricing['transfer_local_child_rate'] ?? 0),
                'transfer_foreign_adult_rate' => (float) ($pricing['transfer_foreign_adult_rate'] ?? 0),
                'transfer_foreign_child_rate' => (float) ($pricing['transfer_foreign_child_rate'] ?? 0),
                'transfer_applied_adult_rate' => (float) ($pricing['transfer_applied_adult_rate'] ?? 0),
                'transfer_applied_child_rate' => (float) ($pricing['transfer_applied_child_rate'] ?? 0),
                'nightly_rate' => $nightlyRate,
                'nights' => $nights,
                'room_subtotal' => $roomSubtotal,
                'subtotal_amount' => (float) ($pricing['subtotal_amount'] ?? $roomSubtotal),
                'discount_percent' => (float) ($pricing['discount_percent'] ?? $discountPercent),
                'discount_amount' => (float) ($pricing['discount_amount'] ?? $discountAmount),
                'discounted_subtotal' => (float) ($pricing['discounted_subtotal'] ?? max(0, $roomSubtotal - $discountAmount)),
                'service_charge_rate_percent' => (float) ($pricing['service_charge_rate_percent'] ?? 0),
                'service_charge_total' => (float) ($pricing['service_charge_total'] ?? 0),
                'green_tax_rate_per_person_per_night' => (float) ($pricing['green_tax_rate_per_person_per_night'] ?? 0),
                'green_tax_total' => (float) ($pricing['green_tax_total'] ?? 0),
                'tgst_rate_percent' => (float) ($pricing['tgst_rate_percent'] ?? 0),
                'tgst_total' => (float) ($pricing['tgst_total'] ?? 0),
                'gst_rate_percent' => (float) ($pricing['gst_rate_percent'] ?? 0),
                'gst_total' => (float) ($pricing['gst_total'] ?? 0),
                'total_tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_lines' => $pricing['tax_lines'] ?? [],
                'invoice_total_amount' => (float) ($pricing['invoice_total_amount'] ?? $totalAmount),
                'vendor_tax_overrides' => $vendorTaxOverrides,
                'policy_snapshot' => $pricing['policy_snapshot'] ?? [],
                'inclusives' => $propertyDetails['inclusives'] ?? [],
                'cancellation_policy' => (string) ($propertyDetails['cancellation_policy'] ?? ''),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId) : '')
        . '?property_id=' . (int) $propertyRow->id
        . '&room_id=' . (int) $roomRow->id
        . '&checkin=' . urlencode((string) $payload['checkin'])
        . '&checkout=' . urlencode((string) $payload['checkout'])
        . '&adults=' . $adults
        . '&children=' . $children
        . '&primary_first_name=' . urlencode($primaryFirstName)
        . '&primary_last_name=' . urlencode($primaryLastName)
        . '&primary_nationality=' . urlencode($primaryNationality)
        . '&guest_residency=' . urlencode($guestResidency)
        . '&primary_email=' . urlencode($primaryEmail)
        . '&primary_mobile=' . urlencode($primaryMobile)
        . '&additional_guest_details=' . urlencode($additionalGuestDetails)
        . '&transfer_option=' . urlencode($transferOptionCode)
        . '&transfer_charge=' . urlencode((string) $transferCharge)
        . '&room_subtotal=' . urlencode((string) $roomSubtotal)
        . '&discount_amount=' . urlencode((string) $discountAmount)
        . '&tax_amount=' . urlencode((string) $taxAmount)
        . '&discount_percent=' . urlencode((string) $discountPercent)
        . '&tax_rate=' . urlencode((string) (($pricing['gst_rate_percent'] ?? 0) + ($pricing['tgst_rate_percent'] ?? 0)))
        . '&tax_lines=' . urlencode(json_encode($pricing['tax_lines'] ?? []))
        . '&total=' . urlencode((string) $totalAmount)
        . '&inclusives=' . urlencode(json_encode($propertyDetails['inclusives'] ?? []))
        . '&cancellation_policy=' . urlencode((string) ($propertyDetails['cancellation_policy'] ?? ''));

    return redirect($checkoutUrl);
});

Route::get('/category-booking/{category}/{property}', function (Request $request, string $category, int $property) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in Date', 'end_label' => 'Check-out Date'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'start_label' => 'Event Date', 'end_label' => 'Event End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
    ];

    $categoryFieldMap = [
        'accommodation' => [
            ['key' => 'rooms', 'label' => 'Rooms', 'type' => 'number', 'required' => true, 'min' => 1],
        ],
        'marine-transport' => [
            ['key' => 'origin_point', 'label' => 'From', 'type' => 'text', 'required' => true],
            ['key' => 'destination_point', 'label' => 'To', 'type' => 'text', 'required' => true],
        ],
        'land-transport' => [
            ['key' => 'origin_point', 'label' => 'From', 'type' => 'text', 'required' => true],
            ['key' => 'destination_point', 'label' => 'To', 'type' => 'text', 'required' => true],
        ],
        'excursion' => [
            ['key' => 'excursion_type', 'label' => 'Excursion Type', 'type' => 'text', 'required' => true],
        ],
        'remote_workspace' => [
            ['key' => 'workspace_type', 'label' => 'Workspace Type', 'type' => 'text', 'required' => true],
        ],
        'conference_room' => [
            ['key' => 'event_type', 'label' => 'Event Type', 'type' => 'select', 'required' => true, 'options' => ['meeting' => 'Meeting', 'training' => 'Training', 'seminar' => 'Seminar', 'conference' => 'Conference', 'workshop' => 'Workshop']],
            ['key' => 'expected_capacity', 'label' => 'Expected Attendees', 'type' => 'number', 'required' => true, 'min' => 1],
        ],
        'resort_day_visit' => [
            ['key' => 'visit_package', 'label' => 'Visit Package', 'type' => 'text', 'required' => true],
        ],
        'restaurant' => [],
        'vehicle_rental' => [
            ['key' => 'vehicle_type', 'label' => 'Vehicle Type', 'type' => 'text', 'required' => true],
            ['key' => 'pickup_location', 'label' => 'Pickup Location', 'type' => 'text', 'required' => true],
            ['key' => 'dropoff_location', 'label' => 'Drop-off Location', 'type' => 'text', 'required' => true],
        ],
    ];

    $categoryKey = strtolower(trim($category));
    if (!array_key_exists($categoryKey, $categoryMap)) {
        abort(404);
    }

    // Map URL slug to DB value (hyphens -> underscores)
    $dbCategoryKey = str_replace('-', '_', $categoryKey);

    $categoryFields = collect($categoryFieldMap[$categoryKey] ?? [])->values();

    if (!Schema::hasTable('vendor_properties')) {
        abort(404);
    }

    $propertyQuery = DB::table('vendor_properties')
        ->where('id', $property)
        ->where('status', 'active');

    if (Schema::hasColumn('vendor_properties', 'listing_category')) {
        $propertyQuery->whereRaw('LOWER(listing_category) = ?', [$dbCategoryKey]);
    }

    $propertyRow = $propertyQuery->first();
    if (!$propertyRow) {
        abort(404);
    }

    $listingDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($listingDetails)) {
        $listingDetails = [];
    }

    $propertyMedia = collect();
    if (Schema::hasTable('vendor_listing_media')) {
        $propertyMedia = DB::table('vendor_listing_media')
            ->where('entity_type', 'property')
            ->where('entity_id', (int) ($propertyRow->id ?? 0))
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    $extractStringList = static function ($value): array {
        if (is_array($value)) {
            return collect($value)
                ->map(static fn ($item) => trim((string) $item))
                ->filter(static fn ($item) => $item !== '')
                ->values()
                ->all();
        }

        if (!is_string($value)) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', $value) ?: [])
            ->map(static fn ($item) => trim((string) $item))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();
    };

    $firstNonEmptyList = static function (array $sources, callable $extractor): array {
        foreach ($sources as $source) {
            $items = $extractor($source);
            if (!empty($items)) {
                return $items;
            }
        }

        return [];
    };

    $highlights = $firstNonEmptyList([
        $listingDetails['highlights'] ?? null,
        $listingDetails['key_highlights'] ?? null,
        $listingDetails['features'] ?? null,
    ], $extractStringList);

    if (empty($highlights)) {
        $highlights = match ($categoryKey) {
            'transport' => ['Fast booking confirmation', 'Flexible route support', 'Local operator coordination'],
            'excursion' => ['Curated local experiences', 'Safety-first operators', 'Flexible trip planning'],
            'remote_workspace' => ['Work-friendly environment', 'Reliable utility setup', 'Quiet productivity zones'],
            'resort_day_visit' => ['Day access convenience', 'Resort facilities included', 'Family-friendly options'],
            'restaurant' => ['Island-inspired dining', 'Reservation support', 'Group-friendly seating'],
            'vehicle_rental' => ['Clean and ready vehicles', 'Flexible pickup points', 'Simple booking flow'],
            default => ['Guest-focused service', 'Verified local operator', 'Easy booking process'],
        };
    }

    $servicesAndAmenities = $firstNonEmptyList([
        $listingDetails['amenities'] ?? null,
        $listingDetails['facilities'] ?? null,
        $listingDetails['services'] ?? null,
        $listingDetails['service_features'] ?? null,
    ], $extractStringList);

    $restaurantMenuItems = $firstNonEmptyList([
        $listingDetails['menu_items'] ?? null,
        $listingDetails['menu'] ?? null,
        $listingDetails['restaurant_menu'] ?? null,
        $listingDetails['dishes'] ?? null,
    ], $extractStringList);

    $descriptionText = trim((string) (
        $listingDetails['description']
        ?? $listingDetails['overview']
        ?? $propertyRow->description
        ?? ''
    ));

    if ($descriptionText === '') {
        $descriptionText = 'This listing is managed by a verified local operator and includes practical service details for straightforward planning. Availability, guest preferences, and service notes can be finalized during checkout.';
    }

    $vendorPolicy = [
        'opening_hours' => trim((string) (
            $listingDetails['opening_hours']
            ?? $listingDetails['operating_hours']
            ?? $listingDetails['business_hours']
            ?? ''
        )),
        'closing_hours' => trim((string) (
            $listingDetails['closing_hours']
            ?? $listingDetails['close_time']
            ?? $listingDetails['closing_time']
            ?? ''
        )),
        'cancellation_policy' => trim((string) (
            $listingDetails['cancellation_policy']
            ?? $listingDetails['cancellation_terms']
            ?? $listingDetails['cancellation']
            ?? ''
        )),
        'other_rules' => $extractStringList(
            $listingDetails['rules']
            ?? $listingDetails['house_rules']
            ?? $listingDetails['policies']
            ?? $listingDetails['terms']
            ?? $listingDetails['additional_rules']
            ?? null
        ),
    ];

    $taxRate = (float) ($listingDetails['tax_rate'] ?? 16);
    $discountPercent = (float) ($listingDetails['promotion_discount_percent'] ?? 0);

    $sessionGuestName = trim((string) session('portal_customer_user', ''));
    $nameParts = preg_split('/\s+/', $sessionGuestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $prefillFirstName = (string) ($nameParts[0] ?? '');
    $prefillLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

    return view('category-booking', [
        'categoryKey' => $categoryKey,
        'categoryLabel' => (string) ($categoryMap[$categoryKey]['label'] ?? 'Category'),
        'categoryFields' => $categoryFields,
        'dateLabels' => [
            'start' => (string) ($categoryMap[$categoryKey]['start_label'] ?? 'Service Start Date'),
            'end' => (string) ($categoryMap[$categoryKey]['end_label'] ?? 'Service End Date'),
        ],
        'property' => $propertyRow,
        'propertyMedia' => $propertyMedia,
        'highlights' => $highlights,
        'servicesAndAmenities' => $servicesAndAmenities,
        'restaurantMenuItems' => $restaurantMenuItems,
        'descriptionText' => $descriptionText,
        'vendorPolicy' => $vendorPolicy,
        'pricingConfig' => [
            'tax_rate' => $taxRate,
            'discount_percent' => $discountPercent,
        ],
        'prefill' => [
            'service_start_date' => trim((string) $request->query('service_start_date', '')),
            'service_end_date' => trim((string) $request->query('service_end_date', '')),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
            'primary_first_name' => $prefillFirstName,
            'primary_last_name' => $prefillLastName,
            'primary_nationality' => '',
            'guest_residency' => trim((string) $request->query('guest_residency', 'foreign_national')),
            'primary_email' => trim((string) session('portal_customer_email', '')),
            'primary_mobile' => '',
            'rooms' => max(1, (int) $request->query('rooms', 1)),
            'origin_point' => trim((string) $request->query('origin_point', '')),
            'destination_point' => trim((string) $request->query('destination_point', '')),
            'excursion_type' => trim((string) $request->query('excursion_type', '')),
            'workspace_type' => trim((string) $request->query('workspace_type', '')),
            'visit_package' => trim((string) $request->query('visit_package', '')),
            'meal_plan' => trim((string) $request->query('meal_plan', '')),
            'vehicle_type' => trim((string) $request->query('vehicle_type', '')),
            'pickup_location' => trim((string) $request->query('pickup_location', '')),
            'dropoff_location' => trim((string) $request->query('dropoff_location', '')),
            'service_notes' => trim((string) $request->query('service_notes', '')),
        ],
    ]);
});

Route::post('/booking/reserve-category', function (Request $request) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in', 'end_label' => 'Check-out'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
    ];

    $categoryFieldRules = [
        'accommodation' => [
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
        ],
        'marine-transport' => [
            'origin_point' => ['required', 'string', 'max:120'],
            'destination_point' => ['required', 'string', 'max:120'],
        ],
        'land-transport' => [
            'origin_point' => ['required', 'string', 'max:120'],
            'destination_point' => ['required', 'string', 'max:120'],
        ],
        'excursion' => [
            'excursion_type' => ['required', 'string', 'max:120'],
        ],
        'remote_workspace' => [
            'workspace_type' => ['required', 'string', 'max:120'],
        ],
        'conference_room' => [
            'event_type' => ['required', 'string', 'in:meeting,training,seminar,conference,workshop'],
            'expected_capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'required_facilities' => ['nullable', 'array'],
            'required_facilities.*' => ['string', 'max:60'],
        ],
        'resort_day_visit' => [
            'visit_package' => ['required', 'string', 'max:120'],
        ],
        'restaurant' => [],
        'vehicle_rental' => [
            'vehicle_type' => ['required', 'string', 'max:120'],
            'pickup_location' => ['required', 'string', 'max:120'],
            'dropoff_location' => ['required', 'string', 'max:120'],
        ],
    ];

    $categoryFieldLabels = [
        'rooms' => 'rooms',
        'origin_point' => 'from location',
        'destination_point' => 'to location',
        'excursion_type' => 'excursion type',
        'workspace_type' => 'workspace type',
        'event_type' => 'event type',
        'expected_capacity' => 'expected capacity',
        'required_facilities' => 'required facilities',
        'visit_package' => 'visit package',
        'meal_plan' => 'meal plan',
        'vehicle_type' => 'vehicle type',
        'pickup_location' => 'pickup location',
        'dropoff_location' => 'drop-off location',
    ];

    $requestedCategoryKey = strtolower(trim((string) $request->input('category_key', '')));
    $requestedCategoryMeta = $categoryMap[$requestedCategoryKey] ?? null;
    $startDateLabel = (string) ($requestedCategoryMeta['start_label'] ?? 'Service start date');
    $endDateLabel = (string) ($requestedCategoryMeta['end_label'] ?? 'Service end date');

    $baseRules = [
        'category_key' => ['required', 'string', 'in:' . implode(',', array_keys($categoryMap))],
        'property_id' => ['required', 'integer', 'min:1'],
        'service_start_date' => ['required', 'date'],
        'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
        'adults' => ['required', 'integer', 'min:1', 'max:20'],
        'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name' => ['required', 'string', 'max:80'],
        'primary_nationality' => ['required', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => ['required', 'email', 'max:190'],
        'primary_mobile' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'additional_guest_details' => ['nullable', 'string', 'max:4000'],
        'service_notes' => ['nullable', 'string', 'max:4000'],
    ];

    $payload = $request->validate(array_merge($baseRules, $categoryFieldRules[$requestedCategoryKey] ?? []), [
        'primary_first_name.required' => 'Primary guest first name is required.',
        'primary_last_name.required' => 'Primary guest last name is required.',
        'primary_nationality.required' => 'Primary guest nationality is required.',
        'primary_email.required' => 'Primary guest email is required.',
        'primary_email.email' => 'Please enter a valid email address for the primary guest.',
        'primary_mobile.required' => 'Primary guest mobile is required.',
        'primary_mobile.regex' => 'Please enter a valid primary guest mobile number.',
        'service_start_date.required' => $startDateLabel . ' is required.',
        'service_end_date.after_or_equal' => $endDateLabel . ' must be after or equal to ' . strtolower($startDateLabel) . '.',
    ], array_merge([
        'primary_first_name' => 'primary guest first name',
        'primary_last_name' => 'primary guest last name',
        'primary_nationality' => 'primary guest nationality',
        'primary_email' => 'primary guest email',
        'primary_mobile' => 'primary guest mobile',
    ], $categoryFieldLabels));

    if (!Schema::hasTable('vendor_properties')) {
        abort(404);
    }

    $categoryKey = strtolower(trim((string) $payload['category_key']));
    // Normalise hyphenated keys (from URL) to underscored DB values
    $dbCategoryKey = str_replace('-', '_', $categoryKey);
    $propertyQuery = DB::table('vendor_properties')
        ->where('id', (int) $payload['property_id'])
        ->where('status', 'active');

    if (Schema::hasColumn('vendor_properties', 'listing_category')) {
        $propertyQuery->whereRaw('LOWER(listing_category) = ?', [$dbCategoryKey]);
    }

    $propertyRow = $propertyQuery->first();
    if (!$propertyRow) {
        abort(404);
    }

    // Listing-level publish gate: only approved listings can accept bookings.
    if (Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        $listingModerationStatus = strtolower(trim((string) ($propertyRow->listing_moderation_status ?? 'draft')));
        if ($listingModerationStatus !== 'approved') {
            return back()->withErrors(['booking' => 'This listing is not yet available for bookings. It is currently under review or pending approval.']);
        }
    }

    $listingDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($listingDetails)) {
        $listingDetails = [];
    }

    $serviceStart = Carbon::parse((string) $payload['service_start_date'])->startOfDay();

    $serviceEndInput = trim((string) ($payload['service_end_date'] ?? ''));
    $serviceEnd = $serviceEndInput !== ''
        ? Carbon::parse($serviceEndInput)->startOfDay()
        : $serviceStart->copy();

    $units = max(1, $serviceStart->diffInDays($serviceEnd) + 1);
    $adults = (int) $payload['adults'];
    $children = (int) ($payload['children'] ?? 0);
    $guestCount = $adults + $children;

    $basePrice = (float) ($propertyRow->base_price ?? 0);
    $serviceSubtotal = $basePrice * $units;
    $discountPercent = (float) ($listingDetails['promotion_discount_percent'] ?? 0);
    $guestResidency = strtolower(trim((string) ($payload['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner((string) ($payload['primary_nationality'] ?? ''), null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $vendorTaxOverrides = [];
    if (isset($listingDetails['vendor_tax_overrides']) && is_array($listingDetails['vendor_tax_overrides'])) {
        $vendorTaxOverrides = $listingDetails['vendor_tax_overrides'];
    }

    $roomCount = Schema::hasTable('vendor_property_room_categories')
        ? (int) DB::table('vendor_property_room_categories')->where('vendor_property_id', (int) $propertyRow->id)->count()
        : 0;

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => $categoryKey,
        'subtotal_amount' => $serviceSubtotal,
        'discount_percent' => $discountPercent,
        'adults' => $adults,
        'children' => $children,
        'nights' => $units,
        'room_count' => $roomCount,
        'primary_nationality' => (string) ($payload['primary_nationality'] ?? ''),
        'guest_residency' => $guestResidency,
        'transfer_option' => '',
        'property_transfer_options' => $listingDetails['transfer_options'] ?? [],
        'vendor_tax_overrides' => $vendorTaxOverrides,
    ]);

    $discountAmount = (float) ($pricing['discount_amount'] ?? 0);
    $taxAmount = (float) ($pricing['total_tax_amount'] ?? 0);
    $transferCharge = (float) ($pricing['transfer_charge_total'] ?? 0);
    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? 0);

    $primaryFirstName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_first_name'])));
    $primaryLastName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_last_name'])));
    $primaryNationality = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_nationality'])));
    $primaryEmail = Str::lower(trim((string) $payload['primary_email']));
    $mobileRaw = trim((string) $payload['primary_mobile']);
    $primaryMobile = preg_replace('/[^0-9+]/', '', $mobileRaw) ?? $mobileRaw;
    $primaryMobile = preg_replace('/^\++/', '+', $primaryMobile) ?? $primaryMobile;
    $additionalGuestDetails = trim((string) ($payload['additional_guest_details'] ?? ''));
    $serviceNotes = trim((string) ($payload['service_notes'] ?? ''));

    $customerName = trim($primaryFirstName . ' ' . $primaryLastName);
    $customerEmail = $primaryEmail;
    $categoryLabel = (string) ($categoryMap[$categoryKey]['label'] ?? 'Category');

    provisionCustomerAccountFromBooking($customerEmail, $customerName);

    $categoryDetails = [];
    foreach (array_keys($categoryFieldRules[$categoryKey] ?? []) as $fieldKey) {
        $value = $payload[$fieldKey] ?? null;
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value !== null && $value !== '') {
            $categoryDetails[$fieldKey] = $value;
        }
    }

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => (int) ($propertyRow->vendor_user_id ?? 0),
            'vendor_property_id' => (int) $propertyRow->id,
            'vendor_service_id' => null,
            'customer_name' => $customerName !== '' ? $customerName : 'Guest Customer',
            'customer_email' => $customerEmail !== '' ? $customerEmail : 'guest@workation.local',
            'start_at' => $serviceStart,
            'end_at' => $serviceEnd,
            'guests' => max(1, $guestCount),
            'total_amount' => $totalAmount,
            'currency' => strtoupper(trim((string) ($propertyRow->currency ?? 'MVR'))),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'category_key' => $categoryKey,
                'category_label' => $categoryLabel,
                'service_label' => $categoryLabel,
                'service_start_date' => $serviceStart->toDateString(),
                'service_end_date' => $serviceEnd->toDateString(),
                'adults' => $adults,
                'children' => $children,
                'primary_first_name' => $primaryFirstName,
                'primary_last_name' => $primaryLastName,
                'primary_nationality' => $primaryNationality,
                'guest_residency' => $guestResidency,
                'primary_email' => $primaryEmail,
                'primary_mobile' => $primaryMobile,
                'additional_guest_details' => $additionalGuestDetails,
                'service_notes' => $serviceNotes,
                'category_details' => $categoryDetails,
                'room_subtotal' => $serviceSubtotal,
                'subtotal_amount' => (float) ($pricing['subtotal_amount'] ?? $serviceSubtotal),
                'discount_percent' => (float) ($pricing['discount_percent'] ?? $discountPercent),
                'discount_amount' => (float) ($pricing['discount_amount'] ?? $discountAmount),
                'discounted_subtotal' => (float) ($pricing['discounted_subtotal'] ?? max(0, $serviceSubtotal - $discountAmount)),
                'service_charge_rate_percent' => (float) ($pricing['service_charge_rate_percent'] ?? 0),
                'service_charge_total' => (float) ($pricing['service_charge_total'] ?? 0),
                'green_tax_rate_per_person_per_night' => (float) ($pricing['green_tax_rate_per_person_per_night'] ?? 0),
                'green_tax_total' => (float) ($pricing['green_tax_total'] ?? 0),
                'tgst_rate_percent' => (float) ($pricing['tgst_rate_percent'] ?? 0),
                'tgst_total' => (float) ($pricing['tgst_total'] ?? 0),
                'gst_rate_percent' => (float) ($pricing['gst_rate_percent'] ?? 0),
                'gst_total' => (float) ($pricing['gst_total'] ?? 0),
                'total_tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_lines' => $pricing['tax_lines'] ?? [],
                'transfer_option' => '',
                'transfer_charge' => $transferCharge,
                'transfer_charge_total' => $transferCharge,
                'invoice_total_amount' => (float) ($pricing['invoice_total_amount'] ?? $totalAmount),
                'vendor_tax_overrides' => $vendorTaxOverrides,
                'policy_snapshot' => $pricing['policy_snapshot'] ?? [],
                'inclusives' => $listingDetails['inclusives'] ?? [],
                'cancellation_policy' => (string) ($listingDetails['cancellation_policy'] ?? ''),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId) : '')
        . '?property_id=' . (int) $propertyRow->id
        . '&category_key=' . urlencode($categoryKey)
        . '&room_id=0'
        . '&checkin=' . urlencode($serviceStart->toDateString())
        . '&checkout=' . urlencode($serviceEnd->toDateString())
        . '&adults=' . $adults
        . '&children=' . $children
        . '&primary_first_name=' . urlencode($primaryFirstName)
        . '&primary_last_name=' . urlencode($primaryLastName)
        . '&primary_nationality=' . urlencode($primaryNationality)
        . '&guest_residency=' . urlencode($guestResidency)
        . '&primary_email=' . urlencode($primaryEmail)
        . '&primary_mobile=' . urlencode($primaryMobile)
        . '&additional_guest_details=' . urlencode($additionalGuestDetails)
        . '&transfer_option='
        . '&transfer_charge=' . urlencode((string) $transferCharge)
        . '&room_subtotal=' . urlencode((string) $serviceSubtotal)
        . '&discount_amount=' . urlencode((string) $discountAmount)
        . '&tax_amount=' . urlencode((string) $taxAmount)
        . '&discount_percent=' . urlencode((string) $discountPercent)
        . '&tax_rate=' . urlencode((string) (($pricing['gst_rate_percent'] ?? 0) + ($pricing['tgst_rate_percent'] ?? 0)))
        . '&tax_lines=' . urlencode(json_encode($pricing['tax_lines'] ?? []))
        . '&total=' . urlencode((string) $totalAmount)
        . '&inclusives=' . urlencode(json_encode($listingDetails['inclusives'] ?? []))
        . '&cancellation_policy=' . urlencode((string) ($listingDetails['cancellation_policy'] ?? ''));

    return redirect($checkoutUrl);
});

Route::get('/booking/checkout/{reservation?}', function (Request $request, ?int $reservation = null) {
    $categoryLabelMap = [
        'accommodation' => ['start' => 'Check-in', 'end' => 'Check-out'],
        'transport' => ['start' => 'Travel Date', 'end' => 'Return Date'],
        'excursion' => ['start' => 'Excursion Date', 'end' => 'Return Date'],
        'remote_workspace' => ['start' => 'Start Date', 'end' => 'End Date'],
        'resort_day_visit' => ['start' => 'Visit Date', 'end' => 'Return Date'],
        'restaurant' => ['start' => 'Reservation Date', 'end' => 'End Date'],
        'vehicle_rental' => ['start' => 'Pickup Date', 'end' => 'Return Date'],
    ];

    $reservationRow = null;
    if ($reservation !== null && Schema::hasTable('vendor_reservations')) {
        $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    }

    $propertyId = (int) $request->query('property_id', (int) ($reservationRow->vendor_property_id ?? 0));
    $propertyRow = Schema::hasTable('vendor_properties')
        ? DB::table('vendor_properties')->where('id', $propertyId)->first()
        : null;

    $roomId = (int) $request->query('room_id', 0);
    $roomName = '';
    if ($reservationRow && !empty($reservationRow->notes)) {
        $notes = json_decode((string) $reservationRow->notes, true);
        if (is_array($notes)) {
            $roomId = (int) ($notes['room_id'] ?? $roomId);
            $roomName = trim((string) ($notes['room_name'] ?? ''));
        }
    }

    $roomRow = Schema::hasTable('vendor_property_room_categories') && $roomId > 0
        ? DB::table('vendor_property_room_categories')->where('id', $roomId)->first()
        : null;

    if ($roomName === '' && $roomRow) {
        $roomName = trim((string) ($roomRow->name ?? 'Room'));
    }

    $reservationNotes = [];
    if ($reservationRow && !empty($reservationRow->notes)) {
        $decoded = json_decode((string) $reservationRow->notes, true);
        if (is_array($decoded)) {
            $reservationNotes = $decoded;
        }
    }

    $inclusivesQuery = trim((string) $request->query('inclusives', ''));
    $inclusives = [];
    if ($inclusivesQuery !== '') {
        $decodedInclusives = json_decode($inclusivesQuery, true);
        if (is_array($decodedInclusives)) {
            $inclusives = collect($decodedInclusives)->map(static fn ($v) => trim((string) $v))->filter()->values()->all();
        }
    }

    if (empty($inclusives) && !empty($reservationNotes['inclusives']) && is_array($reservationNotes['inclusives'])) {
        $inclusives = collect($reservationNotes['inclusives'])->map(static fn ($v) => trim((string) $v))->filter()->values()->all();
    }

    if ($roomName === '') {
        $roomName = trim((string) ($reservationNotes['service_label'] ?? 'Service'));
    }

    $categoryKey = strtolower(trim((string) $request->query('category_key', (string) ($reservationNotes['category_key'] ?? ''))));
    $dateLabels = ['start' => 'Check-in', 'end' => 'Check-out'];
    if ($categoryKey !== '' && array_key_exists($categoryKey, $categoryLabelMap)) {
        $dateLabels = $categoryLabelMap[$categoryKey];
    }

    $cancellationPolicy = trim((string) $request->query('cancellation_policy', ''));
    if ($cancellationPolicy === '') {
        $cancellationPolicy = trim((string) ($reservationNotes['cancellation_policy'] ?? 'Standard cancellation terms apply as per property policy.'));
    }

    $categoryDetailLabels = [
        'rooms' => 'Rooms',
        'transport_mode' => 'Transport Mode',
        'origin_point' => 'From',
        'destination_point' => 'To',
        'excursion_type' => 'Excursion Type',
        'workspace_type' => 'Workspace Type',
        'visit_package' => 'Visit Package',
        'meal_plan' => 'Meal Plan',
        'vehicle_type' => 'Vehicle Type',
        'pickup_location' => 'Pickup Location',
        'dropoff_location' => 'Drop-off Location',
    ];

    $categoryDetails = [];
    if (!empty($reservationNotes['category_details']) && is_array($reservationNotes['category_details'])) {
        foreach ($reservationNotes['category_details'] as $detailKey => $detailValue) {
            $normalizedKey = trim((string) $detailKey);
            $normalizedValue = trim((string) $detailValue);
            if ($normalizedKey === '' || $normalizedValue === '') {
                continue;
            }

            $categoryDetails[] = [
                'label' => (string) ($categoryDetailLabels[$normalizedKey] ?? Str::headline(str_replace('_', ' ', $normalizedKey))),
                'value' => $normalizedValue,
            ];
        }
    }

    $backUrl = '/customer';
    if ($roomRow) {
        $backUrl = '/room/' . (int) ($roomRow->id ?? 0);
    } elseif ($propertyRow && !empty($reservationNotes['category_key'])) {
        $backUrl = '/category-booking/' . urlencode((string) $reservationNotes['category_key']) . '/' . (int) ($propertyRow->id ?? 0);
    }

    $checkoutMediaUrl = null;
    if (Schema::hasTable('vendor_listing_media')) {
        $mediaRow = null;

        if ($roomId > 0) {
            $mediaRow = DB::table('vendor_listing_media')
                ->where('entity_type', 'room')
                ->where('entity_id', $roomId)
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->first(['id', 'file_path']);
        }

        if (!$mediaRow && $propertyId > 0) {
            $mediaRow = DB::table('vendor_listing_media')
                ->where('entity_type', 'property')
                ->where('entity_id', $propertyId)
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->first(['id', 'file_path']);
        }

        if ($mediaRow) {
            $mediaId = (int) ($mediaRow->id ?? 0);
            if ($mediaId > 0) {
                $checkoutMediaUrl = '/media/vendor/' . $mediaId . '/banner';
            } else {
                $checkoutMediaUrl = vendorMediaStorageUrlFromPath((string) ($mediaRow->file_path ?? ''));
            }
        }
    }

    return view('booking-checkout', [
        'reservation' => $reservationRow,
        'property' => $propertyRow,
        'room' => $roomRow,
        'roomName' => $roomName,
        'reservationNotes' => $reservationNotes,
        'inclusives' => $inclusives,
        'cancellationPolicy' => $cancellationPolicy,
        'categoryDetails' => $categoryDetails,
        'backUrl' => $backUrl,
        'checkoutMediaUrl' => $checkoutMediaUrl,
        'dateLabels' => $dateLabels,
        'summary' => [
            'checkin' => trim((string) $request->query('checkin', (string) ($reservationNotes['service_start_date'] ?? ''))),
            'checkout' => trim((string) $request->query('checkout', (string) ($reservationNotes['service_end_date'] ?? ''))),
            'adults' => max(1, (int) $request->query('adults', (int) ($reservationNotes['adults'] ?? 1))),
            'children' => max(0, (int) $request->query('children', (int) ($reservationNotes['children'] ?? 0))),
            'primary_first_name' => trim((string) $request->query('primary_first_name', (string) ($reservationNotes['primary_first_name'] ?? ''))),
            'primary_last_name' => trim((string) $request->query('primary_last_name', (string) ($reservationNotes['primary_last_name'] ?? ''))),
            'primary_nationality' => trim((string) $request->query('primary_nationality', (string) ($reservationNotes['primary_nationality'] ?? ''))),
            'guest_residency' => trim((string) $request->query('guest_residency', (string) ($reservationNotes['guest_residency'] ?? ''))),
            'primary_email' => trim((string) $request->query('primary_email', (string) ($reservationNotes['primary_email'] ?? (string) ($reservationRow->customer_email ?? '')))),
            'primary_mobile' => trim((string) $request->query('primary_mobile', (string) ($reservationNotes['primary_mobile'] ?? ''))),
            'additional_guest_details' => trim((string) $request->query('additional_guest_details', (string) ($reservationNotes['additional_guest_details'] ?? ''))),
            'transfer_option' => trim((string) $request->query('transfer_option', (string) ($reservationNotes['transfer_option'] ?? ''))),
            'transfer_option_label' => trim((string) $request->query('transfer_option_label', (string) ($reservationNotes['transfer_option_label'] ?? ''))),
            'transfer_charge' => (float) $request->query('transfer_charge', (float) ($reservationNotes['transfer_charge'] ?? 0)),
            'transfer_charge_total' => (float) $request->query('transfer_charge_total', (float) ($reservationNotes['transfer_charge_total'] ?? ($reservationNotes['transfer_charge'] ?? 0))),
            'transfer_local_adult_rate' => (float) $request->query('transfer_local_adult_rate', (float) ($reservationNotes['transfer_local_adult_rate'] ?? 0)),
            'transfer_local_child_rate' => (float) $request->query('transfer_local_child_rate', (float) ($reservationNotes['transfer_local_child_rate'] ?? 0)),
            'transfer_foreign_adult_rate' => (float) $request->query('transfer_foreign_adult_rate', (float) ($reservationNotes['transfer_foreign_adult_rate'] ?? 0)),
            'transfer_foreign_child_rate' => (float) $request->query('transfer_foreign_child_rate', (float) ($reservationNotes['transfer_foreign_child_rate'] ?? 0)),
            'transfer_applied_adult_rate' => (float) $request->query('transfer_applied_adult_rate', (float) ($reservationNotes['transfer_applied_adult_rate'] ?? 0)),
            'transfer_applied_child_rate' => (float) $request->query('transfer_applied_child_rate', (float) ($reservationNotes['transfer_applied_child_rate'] ?? 0)),
            'room_subtotal' => (float) $request->query('room_subtotal', (float) ($reservationNotes['room_subtotal'] ?? ($reservationNotes['subtotal_amount'] ?? 0))),
            'subtotal_amount' => (float) $request->query('subtotal_amount', (float) ($reservationNotes['subtotal_amount'] ?? 0)),
            'discount_amount' => (float) $request->query('discount_amount', (float) ($reservationNotes['discount_amount'] ?? 0)),
            'discounted_subtotal' => (float) $request->query('discounted_subtotal', (float) ($reservationNotes['discounted_subtotal'] ?? 0)),
            'service_charge_rate_percent' => (float) $request->query('service_charge_rate_percent', (float) ($reservationNotes['service_charge_rate_percent'] ?? 0)),
            'service_charge_total' => (float) $request->query('service_charge_total', (float) ($reservationNotes['service_charge_total'] ?? 0)),
            'green_tax_rate_per_person_per_night' => (float) $request->query('green_tax_rate_per_person_per_night', (float) ($reservationNotes['green_tax_rate_per_person_per_night'] ?? 0)),
            'green_tax_total' => (float) $request->query('green_tax_total', (float) ($reservationNotes['green_tax_total'] ?? 0)),
            'tgst_rate_percent' => (float) $request->query('tgst_rate_percent', (float) ($reservationNotes['tgst_rate_percent'] ?? 0)),
            'tgst_total' => (float) $request->query('tgst_total', (float) ($reservationNotes['tgst_total'] ?? 0)),
            'gst_rate_percent' => (float) $request->query('gst_rate_percent', (float) ($reservationNotes['gst_rate_percent'] ?? 0)),
            'gst_total' => (float) $request->query('gst_total', (float) ($reservationNotes['gst_total'] ?? 0)),
            'tax_amount' => (float) $request->query('tax_amount', (float) ($reservationNotes['tax_amount'] ?? ($reservationNotes['total_tax_amount'] ?? 0))),
            'total_tax_amount' => (float) $request->query('total_tax_amount', (float) ($reservationNotes['total_tax_amount'] ?? 0)),
            'tax_lines' => is_array($reservationNotes['tax_lines'] ?? null)
                ? $reservationNotes['tax_lines']
                : (json_decode((string) $request->query('tax_lines', '[]'), true) ?: []),
            'discount_percent' => (float) $request->query('discount_percent', (float) ($reservationNotes['discount_percent'] ?? 0)),
            'tax_rate' => (float) $request->query('tax_rate', (float) ($reservationNotes['tax_rate'] ?? 0)),
            'total' => (float) $request->query('total', (float) ($reservationNotes['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0))),
        ],
    ]);
});

Route::get('/customer', function () {
    $customerProperties = collect();
    $customerRoomsByProperty = collect();
    $propertyMediaByProperty = collect();
    $roomMediaByRoom = collect();

    if (Schema::hasTable('vendor_properties')) {
        $customerProperties = DB::table('vendor_properties')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->limit(24)
            ->get();
    }

    $propertyIds = $customerProperties->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn (int $id) => $id > 0)->values();

    if ($propertyIds->isNotEmpty() && Schema::hasTable('vendor_property_room_categories')) {
        $rooms = DB::table('vendor_property_room_categories')
            ->whereIn('vendor_property_id', $propertyIds->all())
            ->orderByDesc('updated_at')
            ->limit(400)
            ->get();

        $customerRoomsByProperty = $rooms->groupBy(static fn ($room) => (int) ($room->vendor_property_id ?? 0));
    }

    $roomIds = $customerRoomsByProperty
        ->flatten(1)
        ->pluck('id')
        ->map(static fn ($id) => (int) $id)
        ->filter(static fn (int $id) => $id > 0)
        ->values();

    if (Schema::hasTable('vendor_listing_media') && ($propertyIds->isNotEmpty() || $roomIds->isNotEmpty())) {
        $mediaQuery = DB::table('vendor_listing_media');

        $mediaQuery->where(function ($query) use ($propertyIds, $roomIds) {
            if ($propertyIds->isNotEmpty()) {
                $query->orWhere(function ($propertyQuery) use ($propertyIds) {
                    $propertyQuery
                        ->where('entity_type', 'property')
                        ->whereIn('entity_id', $propertyIds->all());
                });
            }

            if ($roomIds->isNotEmpty()) {
                $query->orWhere(function ($roomQuery) use ($roomIds) {
                    $roomQuery
                        ->where('entity_type', 'room')
                        ->whereIn('entity_id', $roomIds->all());
                });
            }
        });

        $mediaRows = $mediaQuery
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $propertyMediaByProperty = $mediaRows
            ->filter(static fn ($media) => strtolower((string) ($media->entity_type ?? '')) === 'property')
            ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));

        $roomMediaByRoom = $mediaRows
            ->filter(static fn ($media) => strtolower((string) ($media->entity_type ?? '')) === 'room')
            ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
    }

    $customerProfile = [
        'name' => trim((string) session('portal_customer_user', 'Customer')),
        'email' => '',
        'member_since' => '-',
        'phone' => '',
        'dob' => '',
        'nationality' => '',
        'gender' => '',
        'preferred_language' => 'en',
        'address_line' => '',
        'address_atoll_id' => '',
        'address_island_id' => '',
    ];

    $customerUserId = session('portal_customer_user_id');
    if (is_string($customerUserId) || is_numeric($customerUserId)) {
        try {
            $customerRecord = \App\Models\Customer::query()->where('id', (string) $customerUserId)->first();
            if ($customerRecord) {
                $customerProfile['name'] = trim((string) ($customerRecord->name ?? $customerProfile['name']));
                $customerProfile['email'] = strtolower(trim((string) ($customerRecord->email ?? '')));

                $createdAtRaw = $customerRecord->createdAt ?? $customerRecord->created_at ?? null;
                if ($createdAtRaw) {
                    $customerProfile['member_since'] = Carbon::parse((string) $createdAtRaw)->format('M Y');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to load customer profile context for customer portal.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    if (is_string($customerUserId) || is_numeric($customerUserId)) {
        $profileMeta = cache()->get(customerProfileMetaCacheKey((string) $customerUserId));
        if (is_array($profileMeta)) {
            $customerProfile['phone'] = trim((string) ($profileMeta['phone'] ?? ''));
            $customerProfile['dob'] = trim((string) ($profileMeta['dob'] ?? ''));
            $customerProfile['nationality'] = trim((string) ($profileMeta['nationality'] ?? ''));
            $customerProfile['gender'] = trim((string) ($profileMeta['gender'] ?? ''));
            $customerProfile['preferred_language'] = trim((string) ($profileMeta['preferred_language'] ?? 'en'));
            $customerProfile['address_line'] = trim((string) ($profileMeta['address_line'] ?? ''));
            $customerProfile['address_atoll_id'] = trim((string) ($profileMeta['address_atoll_id'] ?? ''));
            $customerProfile['address_island_id'] = trim((string) ($profileMeta['address_island_id'] ?? ''));
        }
    }

    $summary = [
        'upcoming_bookings' => 0,
        'completed_bookings' => 0,
        'receipts_available' => 0,
        'notification_state' => 'ACTIVE',
    ];

    $categoryMeta = [
        'accommodation'    => ['label' => 'Accommodation'],
        'marine_transport' => ['label' => 'Marine Transport'],
        'land_transport'   => ['label' => 'Land Transport'],
        'excursion'        => ['label' => 'Excursions'],
        'remote_workspace' => ['label' => 'Remote Workspace'],
        'resort_day_visit' => ['label' => 'Resort Day Visit'],
        'restaurant'       => ['label' => 'Restaurant'],
        'vehicle_rental'   => ['label' => 'Vehicle Rental'],
        'water_sports'     => ['label' => 'Water Sports'],
    ];

    $customerBookingsByCategory = collect(array_fill_keys(array_keys($categoryMeta), collect()));

    if (Schema::hasTable('vendor_reservations') && $customerProfile['email'] !== '') {
        $reservationRows = DB::table('vendor_reservations')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerProfile['email'])])
            ->orderByDesc('created_at')
            ->get(['id', 'vendor_property_id', 'start_at', 'end_at', 'status', 'payment_status', 'total_amount', 'currency', 'notes', 'created_at']);

        $propertyNamesById = collect();
        if (Schema::hasTable('vendor_properties')) {
            $reservationPropertyIds = $reservationRows
                ->pluck('vendor_property_id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values();

            if ($reservationPropertyIds->isNotEmpty()) {
                $propertyNamesById = DB::table('vendor_properties')
                    ->whereIn('id', $reservationPropertyIds->all())
                    ->get(['id', 'name', 'listing_category'])
                    ->keyBy('id');
            }
        }

        $today = now()->startOfDay();
        $summary['upcoming_bookings'] = $reservationRows->filter(function ($row) use ($today) {
            $startAt = $row->start_at ? Carbon::parse((string) $row->start_at)->startOfDay() : null;
            return $startAt && $startAt->greaterThanOrEqualTo($today);
        })->count();

        $summary['completed_bookings'] = $reservationRows->filter(function ($row) use ($today) {
            $endAt = $row->end_at ? Carbon::parse((string) $row->end_at)->startOfDay() : null;
            return $endAt && $endAt->lessThan($today);
        })->count();

        $summary['receipts_available'] = $reservationRows->filter(function ($row) {
            return strtolower((string) ($row->payment_status ?? '')) === 'paid';
        })->count();

        $categorized = $reservationRows->map(function ($row) use ($propertyNamesById, $categoryMeta) {
            $notes = json_decode((string) ($row->notes ?? ''), true);
            if (!is_array($notes)) {
                $notes = [];
            }

            $propertyId = (int) ($row->vendor_property_id ?? 0);
            $propertyRow = $propertyNamesById->get($propertyId);

            $categoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));
            if ($categoryKey === '' && $propertyRow) {
                $categoryKey = strtolower(trim((string) ($propertyRow->listing_category ?? '')));
            }
            if ($categoryKey === '' && !empty($notes['room_id'])) {
                $categoryKey = 'accommodation';
            }
            // Normalise transport variants from search form / legacy data
            if ($categoryKey === 'transport' || $categoryKey === 'marine-transport' || $categoryKey === 'marine_transport') {
                $categoryKey = 'marine_transport';
            } elseif ($categoryKey === 'land-transport') {
                $categoryKey = 'land_transport';
            }
            if (!array_key_exists($categoryKey, $categoryMeta)) {
                $categoryKey = 'accommodation';
            }

            $serviceLabel = trim((string) ($notes['service_label'] ?? $notes['room_name'] ?? ''));
            if ($serviceLabel === '') {
                $serviceLabel = (string) ($categoryMeta[$categoryKey]['label'] ?? 'Service');
            }

            return [
                'id' => (int) ($row->id ?? 0),
                'category_key' => $categoryKey,
                'category_label' => (string) ($categoryMeta[$categoryKey]['label'] ?? 'Category'),
                'property_name' => trim((string) ($propertyRow->name ?? 'Property')),
                'service_label' => $serviceLabel,
                'start_at' => $row->start_at ? Carbon::parse((string) $row->start_at)->format('Y-m-d') : '-',
                'end_at' => $row->end_at ? Carbon::parse((string) $row->end_at)->format('Y-m-d') : '-',
                'status' => strtoupper(trim((string) ($row->status ?? 'pending'))),
                'payment_status' => strtoupper(trim((string) ($row->payment_status ?? 'unpaid'))),
                'total_amount' => (float) ($row->total_amount ?? 0),
                'currency' => strtoupper(trim((string) ($row->currency ?? 'MVR'))),
                'created_at' => $row->created_at ? Carbon::parse((string) $row->created_at)->format('Y-m-d') : '-',
            ];
        });

        $customerBookingsByCategory = collect(array_keys($categoryMeta))
            ->mapWithKeys(function (string $categoryKey) use ($categorized) {
                return [$categoryKey => $categorized->where('category_key', $categoryKey)->values()];
            });
    }

    $allBookings = $customerBookingsByCategory->flatten(1)->sortByDesc('created_at')->values();
    $today = now()->startOfDay();
    $bookingStatusCounts = [
        'all'              => $allBookings->count(),
        'awaiting_payment' => $allBookings->filter(fn ($b) => strtolower((string) ($b['payment_status'] ?? '')) === 'unpaid' && !in_array(strtolower((string) ($b['status'] ?? '')), ['cancelled', 'canceled']))->count(),
        'upcoming'         => $allBookings->filter(fn ($b) => $b['start_at'] !== '-' && \Carbon\Carbon::parse((string) $b['start_at'])->startOfDay()->greaterThanOrEqualTo($today))->count(),
        'awaiting_review'  => $allBookings->filter(fn ($b) => !in_array(strtolower((string) ($b['status'] ?? '')), ['pending', 'cancelled', 'canceled']) && ($b['end_at'] === '-' || \Carbon\Carbon::parse((string) $b['end_at'])->isPast()))->count(),
    ];

    return view('customer-portal', [
        'summary' => $summary,
        'customerProfile' => $customerProfile,
        'customerBookingsByCategory' => $customerBookingsByCategory,
        'allBookings' => $allBookings,
        'bookingStatusCounts' => $bookingStatusCounts,
        'bookingCategoryMeta' => $categoryMeta,
        'customerProperties' => $customerProperties,
        'customerRoomsByProperty' => $customerRoomsByProperty,
        'propertyMediaByProperty' => $propertyMediaByProperty,
        'roomMediaByRoom' => $roomMediaByRoom,
    ]);
});

Route::get('/media/vendor/{media}/{variant?}', function (int $media, ?string $variant = 'banner') {
        $placeholderResponse = static function () {
                $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">
    <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#d7ebf8"/>
            <stop offset="100%" stop-color="#c7deef"/>
        </linearGradient>
    </defs>
    <rect width="900" height="520" fill="url(#g)"/>
    <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#406582" font-family="Arial" font-size="34">Image unavailable</text>
</svg>
SVG;

                return response($svg, 404, [
                        'Content-Type' => 'image/svg+xml; charset=UTF-8',
                        'Cache-Control' => 'no-store',
                ]);
        };

    if (!Schema::hasTable('vendor_listing_media')) {
                return $placeholderResponse();
    }

    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->first(['file_path', 'mime_type']);

    if (!$mediaRecord) {
        return $placeholderResponse();
    }

    $originalPath = trim((string) ($mediaRecord->file_path ?? ''));
    if ($originalPath === '') {
        return $placeholderResponse();
    }

    if (str_starts_with($originalPath, 'http://') || str_starts_with($originalPath, 'https://')) {
        $remoteCandidates = [$originalPath];
        if (str_starts_with($originalPath, 'http://')) {
            $remoteCandidates[] = 'https://' . ltrim(substr($originalPath, 7), '/');
        }

        foreach (array_unique($remoteCandidates) as $remoteUrl) {
            try {
                $remoteResponse = Http::retry(1, 200)
                    ->timeout(10)
                    ->withHeaders([
                        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                        'User-Agent' => 'WorkationMediaProxy/1.0',
                    ])
                    ->get($remoteUrl);
            } catch (\Throwable $exception) {
                continue;
            }

            if (!$remoteResponse->successful()) {
                continue;
            }

            $remoteBody = $remoteResponse->body();
            if ($remoteBody === '') {
                continue;
            }

            $remoteContentType = trim((string) $remoteResponse->header('Content-Type', ''));
            if ($remoteContentType === '') {
                $remoteContentType = (string) ($mediaRecord->mime_type ?? 'image/jpeg');
            }

            return response($remoteBody, 200, [
                'Content-Type' => $remoteContentType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return $placeholderResponse();
    }

    $normalizedVariant = strtolower(trim((string) $variant));
    if (!in_array($normalizedVariant, ['banner', 'thumb'], true)) {
        $normalizedVariant = 'banner';
    }

    $candidatePath = $originalPath;
    if ($normalizedVariant === 'thumb') {
        $candidatePath = preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $originalPath) ?? $originalPath;
    } else {
        $candidatePath = preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $originalPath) ?? $originalPath;
    }

    // Some legacy rows have only one generated variant. Try the opposite variant as a fallback.
    $alternateVariantPath = $normalizedVariant === 'thumb'
        ? (preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $originalPath) ?? $originalPath)
        : (preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $originalPath) ?? $originalPath);

    $normalizeDiskPath = static function (string $path): string {
        $normalized = trim(str_replace('\\', '/', $path));
        if ($normalized === '') {
            return '';
        }

        if (preg_match('#/storage/app/public/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        return ltrim($normalized, '/');
    };

    $candidatePaths = collect([
        $candidatePath,
        $alternateVariantPath,
        $originalPath,
        $normalizeDiskPath($candidatePath),
        $normalizeDiskPath($alternateVariantPath),
        $normalizeDiskPath($originalPath),
    ])->map(static fn ($path) => trim((string) $path))
      ->filter(static fn ($path) => $path !== '')
      ->unique()
      ->values()
      ->all();

    $resolvedBinary = null;
    $resolvedMimeType = '';

    $configuredMediaDisk = trim((string) config('filesystems.vendor_media_disk', 'public'));
    $diskNames = array_values(array_unique(array_filter([
        $configuredMediaDisk !== '' ? $configuredMediaDisk : null,
        'public',
    ])));

    foreach ($diskNames as $diskName) {
        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $exception) {
            continue;
        }

        foreach ($candidatePaths as $path) {
            if (!$disk->exists($path)) {
                continue;
            }

            $resolvedBinary = $disk->get($path);
            $resolvedMimeType = (string) ($disk->mimeType($path) ?: '');
            break 2;
        }
    }

    if ($resolvedBinary === null) {
        $localDisk = Storage::disk('local');
        foreach ($candidatePaths as $path) {
            foreach ([$path, 'public/' . ltrim($path, '/')] as $localPath) {
                if (!$localDisk->exists($localPath)) {
                    continue;
                }

                $resolvedBinary = $localDisk->get($localPath);
                $resolvedMimeType = (string) ($localDisk->mimeType($localPath) ?: '');
                break 2;
            }
        }
    }

    if ($resolvedBinary === null) {
        foreach ($candidatePaths as $path) {
            $absolutePath = str_replace('\\', '/', (string) $path);
            if (preg_match('#^[A-Za-z]:/#', $absolutePath) !== 1 && !str_starts_with($absolutePath, '/')) {
                continue;
            }

            if (!is_file($absolutePath) || !is_readable($absolutePath)) {
                continue;
            }

            $absoluteBinary = @file_get_contents($absolutePath);
            if ($absoluteBinary === false) {
                continue;
            }

            $resolvedBinary = $absoluteBinary;
            $absoluteMime = @mime_content_type($absolutePath);
            $resolvedMimeType = is_string($absoluteMime) ? $absoluteMime : '';
            break;
        }
    }

    if ($resolvedBinary === null) {
        return $placeholderResponse();
    }

    $mimeType = $resolvedMimeType !== '' ? $resolvedMimeType : ((string) ($mediaRecord->mime_type ?? 'image/jpeg'));

    return response($resolvedBinary, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
});

Route::get('/users', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    $portalUserSelectColumns = ['id', 'name', 'username', 'email', 'portal_role', 'portal_enabled', 'portal_vendor_id'];
    foreach ([
        'vendor_verification_status',
        'vendor_verification_notes',
        'vendor_approved_service_categories',
        'vendor_contact_verified_at',
        'vendor_verified_at',
    ] as $optionalColumn) {
        if (Schema::hasColumn('users', $optionalColumn)) {
            $portalUserSelectColumns[] = $optionalColumn;
        }
    }

    $query = strtolower(trim((string) $request->query('q', '')));
    $portalUsers = User::query()
        ->whereIn('portal_role', ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'ADMIN_MEDIA', 'VENDOR'])
        ->orderBy('portal_role')
        ->orderBy('username')
        ->get($portalUserSelectColumns);

    if ($query !== '') {
        $portalUsers = $portalUsers->filter(function (User $managedUser) use ($query) {
            $haystack = strtolower(implode(' ', [
                (string) ($managedUser->username ?? ''),
                (string) ($managedUser->name ?? ''),
                (string) ($managedUser->email ?? ''),
                (string) ($managedUser->portal_role ?? ''),
                (string) ($managedUser->portal_vendor_id ?? ''),
            ]));

            return str_contains($haystack, $query);
        })->values();
    }

    $adminUsers = $portalUsers->filter(function (User $managedUser) {
        return normalizePortalRoleValue((string) $managedUser->portal_role) !== 'VENDOR';
    })->values();

    $vendorUsers = $portalUsers->filter(function (User $managedUser) {
        return normalizePortalRoleValue((string) $managedUser->portal_role) === 'VENDOR';
    })->values();

    $customers = collect();
    try {
        $customerRows = \App\Models\Customer::query()
            ->orderByDesc('createdAt')
            ->limit(250)
            ->get();

        if ($query !== '') {
            $customerRows = $customerRows->filter(function ($customer) use ($query) {
                $haystack = strtolower(implode(' ', [
                    (string) ($customer->name ?? ''),
                    (string) ($customer->email ?? ''),
                ]));

                return str_contains($haystack, $query);
            })->values();
        }

        $customers = $customerRows;
    } catch (\Throwable $e) {
        Log::warning('Unable to load customer records for users console.', [
            'error' => $e->getMessage(),
        ]);
    }

    return view('users-customers-portal', [
        'query' => $query,
        'adminUsers' => $adminUsers,
        'vendorUsers' => $vendorUsers,
        'customers' => $customers,
        'summary' => [
            'admin_users' => $adminUsers->count(),
            'vendor_users' => $vendorUsers->count(),
            'customers' => $customers->count(),
            'suspended_users' => $portalUsers->where('portal_enabled', false)->count(),
        ],
    ]);
});

use Illuminate\Support\Facades\Auth;

Route::get('/admin', function (Request $request) {
    $portal = 'admin';
    $config = portalConfig($portal);
    if (!session()->get($config['session_key'], false)) {
        return redirect('/portal/' . $portal . '/login');
    }

    $user = Auth::user();
    $canManageUsers = Gate::allows('manage-portal-users', $user);
    $canManageVendorUsers = canManageVendorUsers();
    $canCreateVendorUsers = canCreateVendorUsers();
    $canReviewVendorRegistrations = canReviewVendorRegistrations();
    $canApproveVendorRegistrationRequest = canApproveVendorRegistrationRequest();
    $canApproveVendorDeleteRequest = canApproveVendorDeleteRequest();
    $canRequestVendorDeleteApproval = canRequestVendorDeleteApproval();
    $canModerateFinance = canModeratePortalFinance();
    $canModerateListings = canModerateListings();
    $canManageContent = canManageContent();
    $canEditorialReview = canEditorialReview();
    $portalUserSelectColumns = ['id', 'name', 'username', 'email', 'portal_role', 'portal_enabled', 'portal_vendor_id'];
    foreach ([
        'vendor_verification_status',
        'vendor_verification_notes',
        'vendor_approved_service_categories',
        'vendor_contact_verified_at',
        'vendor_verified_at',
    ] as $optionalColumn) {
        if (Schema::hasColumn('users', $optionalColumn)) {
            $portalUserSelectColumns[] = $optionalColumn;
        }
    }

    $portalUsers = User::query()
        ->whereIn('portal_role', ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'ADMIN_MEDIA', 'VENDOR'])
        ->orderBy('portal_role')
        ->orderBy('username')
        ->get($portalUserSelectColumns);

    $adminPortalUsers = $portalUsers
        ->filter(function (User $managedUser) {
            return strtoupper((string) $managedUser->portal_role) !== 'VENDOR';
        })
        ->values();

    $vendorPortalUsers = $portalUsers
        ->filter(function (User $managedUser) {
            return strtoupper((string) $managedUser->portal_role) === 'VENDOR';
        })
        ->values();

    $pendingVendorRegistrations = collect();
    if (Schema::hasTable('vendor_registration_requests')) {
        $pendingVendorRegistrations = DB::table('vendor_registration_requests')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(80)
            ->get();
    }

    $vendorRegistrationHistory = collect();
    if (Schema::hasTable('vendor_registration_requests')) {
        $vendorRegistrationHistory = DB::table('vendor_registration_requests as vrr')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'vrr.reviewed_by_user_id')
            ->leftJoin('users as approved_users', 'approved_users.id', '=', 'vrr.approved_user_id')
            ->whereIn('vrr.status', ['approved', 'rejected'])
            ->orderByDesc('vrr.reviewed_at')
            ->limit(120)
            ->get([
                'vrr.id',
                'vrr.business_name',
                'vrr.contact_name',
                'vrr.email',
                'vrr.vendor_type',
                'vrr.status',
                'vrr.review_notes',
                'vrr.reviewed_at',
                'vrr.license_number',
                'vrr.business_registration_number',
                'reviewers.name as reviewed_by_name',
                'reviewers.portal_role as reviewed_by_role',
                'approved_users.username as approved_username',
                'approved_users.portal_vendor_id as approved_vendor_id',
            ]);
    }

    $pendingVendorDeleteRequests = collect();
    $pendingVendorRegistrationApprovalRequests = collect();
    if (Schema::hasTable('portal_admin_action_requests')) {
        $pendingVendorDeleteRequests = DB::table('portal_admin_action_requests as par')
            ->leftJoin('users as requested_by', 'requested_by.id', '=', 'par.requested_by_user_id')
            ->leftJoin('users as target_user', 'target_user.id', '=', 'par.target_user_id')
            ->where('par.status', 'pending')
            ->where('par.action_type', 'vendor_delete')
            ->orderBy('par.created_at')
            ->limit(80)
            ->get([
                'par.id',
                'par.action_type',
                'par.reason',
                'par.target_user_id',
                'par.target_registration_id',
                'par.target_identifier',
                'par.created_at',
                'requested_by.name as requested_by_name',
                'requested_by.portal_role as requested_by_role',
                'target_user.username as target_username',
                'target_user.email as target_email',
                'target_user.portal_vendor_id as target_vendor_id',
            ]);

        $pendingVendorRegistrationApprovalRequests = DB::table('portal_admin_action_requests as par')
            ->leftJoin('users as requested_by', 'requested_by.id', '=', 'par.requested_by_user_id')
            ->leftJoin('vendor_registration_requests as vrr', 'vrr.id', '=', 'par.target_registration_id')
            ->where('par.status', 'pending')
            ->where('par.action_type', 'vendor_registration_approve')
            ->orderBy('par.created_at')
            ->limit(80)
            ->get([
                'par.id',
                'par.action_type',
                'par.reason',
                'par.target_user_id',
                'par.target_registration_id',
                'par.target_identifier',
                'par.payload',
                'par.created_at',
                'requested_by.name as requested_by_name',
                'requested_by.portal_role as requested_by_role',
                'vrr.business_name',
                'vrr.contact_name',
                'vrr.email as registration_email',
                'vrr.vendor_type',
            ]);
    }

    $rolePermissions = [
        'ADMIN_SUPER' => [
            'label' => 'ADMIN_SUPER',
            'summary' => 'Full control of portal users, roles, suspension, and audit visibility.',
            'capabilities' => [
                'Create, edit, suspend, and delete portal users',
                'Run bulk user actions',
                'View operational dashboard and audit history',
                'Access admin API actions from portal',
            ],
        ],
        'ADMIN' => [
            'label' => 'ADMIN',
            'summary' => 'General operational admin access without super-admin user governance.',
            'capabilities' => [
                'Access admin API actions from portal',
                'View dashboard widgets and system health',
                'View activity history for awareness',
            ],
        ],
        'ADMIN_CARE' => [
            'label' => 'ADMIN_CARE',
            'summary' => 'Customer and account-care oriented access for support operations.',
            'capabilities' => [
                'Access admin portal tooling for care workflows',
                'View user/account status indicators',
                'Escalate role or suspension changes to super admin',
            ],
        ],
        'ADMIN_FINANCE' => [
            'label' => 'ADMIN_FINANCE',
            'summary' => 'Finance and reconciliation focused access for payment operations.',
            'capabilities' => [
                'Use finance-related admin API checks',
                'Review reconciliation and job health endpoints',
                'Escalate user governance changes to super admin',
            ],
        ],
        'ADMIN_MEDIA' => [
            'label' => 'ADMIN_MEDIA',
            'summary' => 'Content operations for blogs, newsletters, PR, and announcements under editorial workflow.',
            'capabilities' => [
                'Create and edit blog posts',
                'Submit blog posts for editorial review',
                'Create and update newsletters',
                'Create and update announcements',
            ],
        ],
        'VENDOR' => [
            'label' => 'VENDOR',
            'summary' => 'Vendor portal access only (no admin moderation privileges).',
            'capabilities' => [
                'Access vendor portal APIs and account data',
            ],
        ],
    ];

    $currentPortalRole = strtoupper((string) session('portal_admin_role', 'ADMIN'));
    if ($currentPortalRole === 'ADMIN_FINACE') {
        $currentPortalRole = 'ADMIN_FINANCE';
    }

    $currentRolePermissions = $rolePermissions[$currentPortalRole] ?? null;

    $adminPageAliases = [
        'overview' => 'overview',
        'permissions' => 'permissions',
        'finance' => 'finance',
        'media' => 'media',
        'content' => 'content',
        'catalog' => 'catalog',
        'moderation' => 'moderation',
        'listings' => 'listings',
        'audit' => 'audit',
        'tools' => 'tools',
    ];

    $requestedPage = strtolower(trim((string) $request->query('page', 'overview')));
    $adminPage = $adminPageAliases[$requestedPage] ?? 'overview';

    $adminAllowedPages = ['overview', 'permissions', 'audit', 'tools', 'moderation'];
    if ($canModerateFinance) {
        $adminAllowedPages[] = 'finance';
    }
    if ($canManageVendorUsers) {
        $adminAllowedPages[] = 'media';
        $adminAllowedPages[] = 'catalog';
    }
    if ($canManageContent) {
        $adminAllowedPages[] = 'content';
    }
    if ($canModerateListings) {
        $adminAllowedPages[] = 'listings';
    }

    if (!in_array($adminPage, $adminAllowedPages, true)) {
        $adminPage = in_array('overview', $adminAllowedPages, true) ? 'overview' : (string) ($adminAllowedPages[0] ?? 'overview');
    }

    $dashboardStats = [
        'total_users' => $portalUsers->count(),
        'admin_users' => $adminPortalUsers->count(),
        'vendor_users' => $vendorPortalUsers->count(),
        'active_users' => $portalUsers->where('portal_enabled', true)->count(),
        'suspended_users' => $portalUsers->where('portal_enabled', false)->count(),
        'pending_vendor_registrations' => $pendingVendorRegistrations->count(),
    ];

    $financeCommissionRate = 12.0;
    $financeCurrency = 'MVR';
    $financeDailyRows = collect();
    $financeAdjustments = collect();
    $financeReservationPolicy = portalFinanceLoadReservationPolicy();
    $financeTaxComponents = collect(portalFinanceTaxComponents($financeReservationPolicy));

    if (Schema::hasTable('portal_finance_settings')) {
        $commissionRateSetting = DB::table('portal_finance_settings')
            ->where('setting_key', 'commission_rate_percent')
            ->value('value_decimal');
        if ($commissionRateSetting !== null) {
            $financeCommissionRate = max(0, min(100, (float) $commissionRateSetting));
        }

        $currencySetting = DB::table('portal_finance_settings')
            ->where('setting_key', 'default_currency')
            ->value('value_string');
        if (is_string($currencySetting) && trim($currencySetting) !== '') {
            $financeCurrency = strtoupper(substr(trim($currencySetting), 0, 8));
        }
    }

    if (Schema::hasTable('vendor_reservations')) {
        $financeDailyRows = DB::table('vendor_reservations as vr')
            ->leftJoin('users as vendor_users', 'vendor_users.id', '=', 'vr.vendor_user_id')
            ->selectRaw('DATE(vr.start_at) as collection_day')
            ->addSelect([
                'vr.vendor_user_id',
                'vendor_users.name as vendor_name',
                'vendor_users.email as vendor_email',
            ])
            ->selectRaw('COUNT(*) as transactions_count')
            ->selectRaw('SUM(vr.total_amount) as gross_total')
            ->selectRaw("SUM(CASE WHEN vr.payment_status = 'paid' THEN vr.total_amount ELSE 0 END) as collected_total")
            ->selectRaw("SUM(CASE WHEN vr.payment_status = 'paid' AND vr.status IN ('confirmed', 'completed') THEN vr.total_amount ELSE 0 END) as eligible_total")
            ->groupByRaw('DATE(vr.start_at), vr.vendor_user_id, vendor_users.name, vendor_users.email')
            ->orderByDesc('collection_day')
            ->limit(240)
            ->get();
    }

    if (Schema::hasTable('portal_finance_adjustments')) {
        $financeAdjustments = DB::table('portal_finance_adjustments as pfa')
            ->leftJoin('users as vendors', 'vendors.id', '=', 'pfa.vendor_user_id')
            ->leftJoin('users as moderators', 'moderators.id', '=', 'pfa.moderated_by_user_id')
            ->orderByDesc('pfa.applies_on')
            ->orderByDesc('pfa.created_at')
            ->limit(200)
            ->get([
                'pfa.id',
                'pfa.vendor_user_id',
                'pfa.applies_on',
                'pfa.adjustment_type',
                'pfa.amount',
                'pfa.currency',
                'pfa.invoice_reference',
                'pfa.reason',
                'pfa.status',
                'pfa.moderated_by_role',
                'pfa.created_at',
                'vendors.name as vendor_name',
                'vendors.email as vendor_email',
                'moderators.name as moderated_by_name',
            ]);
    }

    $adjustmentTotalsByVendorDay = collect();
    if (Schema::hasTable('portal_finance_adjustments')) {
        $adjustmentTotalsByVendorDay = DB::table('portal_finance_adjustments')
            ->selectRaw('vendor_user_id, applies_on, SUM(amount) as adjustment_total')
            ->where('status', 'approved')
            ->groupBy('vendor_user_id', 'applies_on')
            ->get()
            ->keyBy(function ($row): string {
                return (string) $row->vendor_user_id . '|' . (string) $row->applies_on;
            });
    }

    $commissionFactor = $financeCommissionRate / 100;
    $financeDailyRows = $financeDailyRows->map(function ($row) use ($adjustmentTotalsByVendorDay, $commissionFactor): array {
        $eligibleTotal = (float) ($row->eligible_total ?? 0);
        $grossTotal = (float) ($row->gross_total ?? 0);
        $collectedTotal = (float) ($row->collected_total ?? 0);
        $commissionAmount = round($eligibleTotal * $commissionFactor, 2);

        $lookupKey = (string) $row->vendor_user_id . '|' . (string) $row->collection_day;
        $adjustmentAmount = (float) optional($adjustmentTotalsByVendorDay->get($lookupKey))->adjustment_total;
        $netPayout = round($eligibleTotal - $commissionAmount + $adjustmentAmount, 2);

        return [
            'collection_day' => (string) ($row->collection_day ?? ''),
            'vendor_user_id' => (int) ($row->vendor_user_id ?? 0),
            'vendor_name' => (string) ($row->vendor_name ?? 'Unknown Vendor'),
            'vendor_email' => (string) ($row->vendor_email ?? ''),
            'transactions_count' => (int) ($row->transactions_count ?? 0),
            'gross_total' => $grossTotal,
            'collected_total' => $collectedTotal,
            'eligible_total' => $eligibleTotal,
            'commission_amount' => $commissionAmount,
            'adjustment_amount' => $adjustmentAmount,
            'net_payout' => $netPayout,
        ];
    });

    $financeSummary = [
        'gross_total' => (float) $financeDailyRows->sum('gross_total'),
        'collected_total' => (float) $financeDailyRows->sum('collected_total'),
        'commission_total' => (float) $financeDailyRows->sum('commission_amount'),
        'adjustment_total' => (float) $financeDailyRows->sum('adjustment_amount'),
        'net_payout_total' => (float) $financeDailyRows->sum('net_payout'),
        'settled_rows' => (int) $financeDailyRows->where('eligible_total', '>', 0)->count(),
    ];

    $listingOptionCatalog = collect();
    if (Schema::hasTable('portal_listing_option_catalog')) {
        $listingOptionCatalog = DB::table('portal_listing_option_catalog')
            ->orderBy('option_type')
            ->orderBy('sort_order')
            ->orderBy('option_label')
            ->limit(600)
            ->get();
    }

    $catalogHeroAdminCategories = [
        'accommodation' => 'Accommodation',
        'marine-transport' => 'Marine Transport',
        'land-transport' => 'Land Transport',
        'excursion' => 'Excursion',
        'remote_workspace' => 'Remote Workspace',
        'conference_room' => 'Conference & Meeting Spaces',
        'resort_day_visit' => 'Resort Day Visit',
        'restaurant' => 'Restaurant',
        'vehicle_rental' => 'Vehicle Rental',
    ];
    $homeHeroAdminImageUrl = '';
    $homeHeroAdminStoredValue = '';
    $catalogHeroAdminImages = collect($catalogHeroAdminCategories)
        ->mapWithKeys(static fn ($label, $key) => [$key => '']);
    $catalogHeroAdminStoredValues = collect($catalogHeroAdminCategories)
        ->mapWithKeys(static fn ($label, $key) => [$key => '']);
    $destinationMediaOverrides = collect();

    $blogSidebarAdSettings = [
        'title' => 'Charter a vessel?',
        'brand' => 'workation',
        'cta_label' => 'Explore now',
        'cta_url' => '/catalog/marine-transport',
        'image_stored_value' => '',
        'image_url' => '',
    ];

    if (Schema::hasTable('portal_finance_settings')) {
        $mediaSettingKeys = collect(array_keys($catalogHeroAdminCategories))
            ->map(static fn ($key) => 'catalog_hero_image_' . str_replace('-', '_', $key))
            ->prepend('home_hero_image_url')
            ->push('blog_sidebar_ad_title')
            ->push('blog_sidebar_ad_brand')
            ->push('blog_sidebar_ad_cta_label')
            ->push('blog_sidebar_ad_cta_url')
            ->push('blog_sidebar_ad_image')
            ->values();

        $mediaSettings = DB::table('portal_finance_settings')
            ->whereIn('setting_key', $mediaSettingKeys->all())
            ->pluck('value_string', 'setting_key');

        $homeHeroAdminStoredValue = trim((string) ($mediaSettings->get('home_hero_image_url') ?? ''));
        // Use canonical hero proxy for admin preview to ensure reliable playback on ephemeral filesystems.
        $homeHeroAdminImageUrl = ($homeHeroAdminStoredValue !== '' ? '/media/portal/hero/home' : '');

        $catalogHeroAdminStoredValues = collect($catalogHeroAdminCategories)
            ->mapWithKeys(function ($label, $key) use ($mediaSettings) {
                $settingKey = 'catalog_hero_image_' . str_replace('-', '_', $key);
                return [$key => trim((string) ($mediaSettings->get($settingKey) ?? ''))];
            });

        $catalogHeroAdminImages = $catalogHeroAdminStoredValues
            ->mapWithKeys(static function ($storedValue, $key) {
                // Use canonical hero proxy for admin preview to ensure reliable playback.
                return [$key => ($storedValue !== '' ? '/media/portal/hero/' . $key : '')];
            });

        $blogSidebarAdSettings = [
            'title' => trim((string) ($mediaSettings->get('blog_sidebar_ad_title') ?? 'Charter a vessel?')) ?: 'Charter a vessel?',
            'brand' => trim((string) ($mediaSettings->get('blog_sidebar_ad_brand') ?? 'workation')) ?: 'workation',
            'cta_label' => trim((string) ($mediaSettings->get('blog_sidebar_ad_cta_label') ?? 'Explore now')) ?: 'Explore now',
            'cta_url' => trim((string) ($mediaSettings->get('blog_sidebar_ad_cta_url') ?? '/catalog/marine-transport')) ?: '/catalog/marine-transport',
            'image_stored_value' => trim((string) ($mediaSettings->get('blog_sidebar_ad_image') ?? '')),
            'image_url' => portalManagedMediaUrlFromPath(trim((string) ($mediaSettings->get('blog_sidebar_ad_image') ?? ''))) ?? '',
        ];
    }

    if (Schema::hasTable('portal_destination_media_overrides')) {
        $destinationMediaOverrides = DB::table('portal_destination_media_overrides')
            ->orderBy('destination_name')
            ->limit(300)
            ->get()
            ->map(static function ($row) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'destination_key' => (string) ($row->destination_key ?? ''),
                    'destination_name' => (string) ($row->destination_name ?? ''),
                    'destination_type' => (string) ($row->destination_type ?? 'destination'),
                    'image_value' => (string) ($row->image_value ?? ''),
                    'image_url' => portalManagedMediaUrlFromPath((string) ($row->image_value ?? '')) ?? '',
                ];
            })
            ->values();
    }

    $systemHealth = [
        'db_connected' => false,
        'audit_table_ready' => Schema::hasTable('portal_admin_audit_logs'),
        'manage_permission' => $canManageUsers,
        'vendor_manage_permission' => $canManageVendorUsers,
        'vendor_create_permission' => $canCreateVendorUsers,
        'vendor_review_permission' => $canReviewVendorRegistrations,
        'vendor_registration_approval_permission' => $canApproveVendorRegistrationRequest,
        'vendor_delete_approval_permission' => $canApproveVendorDeleteRequest,
        'vendor_delete_request_permission' => $canRequestVendorDeleteApproval,
        'finance_moderation_permission' => $canModerateFinance,
        'finance_settings_table_ready' => Schema::hasTable('portal_finance_settings'),
        'finance_adjustments_table_ready' => Schema::hasTable('portal_finance_adjustments'),
    ];

    try {
        DB::connection()->getPdo();
        $systemHealth['db_connected'] = true;
    } catch (\Throwable $e) {
        Log::warning('Admin dashboard health check failed: database connection unavailable.', [
            'error' => $e->getMessage(),
        ]);
    }

    $auditLogs = collect();
    $recentAuditCount = 0;
    if (Schema::hasTable('portal_admin_audit_logs')) {
        $auditLogs = DB::table('portal_admin_audit_logs')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get([
                'id',
                'actor_name',
                'actor_role',
                'action',
                'target_identifier',
                'target_role',
                'details',
                'created_at',
            ]);

        $recentAuditCount = DB::table('portal_admin_audit_logs')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    $alerts = collect();
    if ($dashboardStats['suspended_users'] > 0) {
        $alerts->push('Suspended users detected: ' . $dashboardStats['suspended_users']);
    }
    if (!$systemHealth['audit_table_ready']) {
        $alerts->push('Audit log table is missing. Run migrations to enable activity history.');
    }
    if (!$systemHealth['db_connected']) {
        $alerts->push('Database connection health check failed.');
    }
    if (!$systemHealth['manage_permission']) {
        $alerts->push('Current role cannot manage portal users.');
    }
    if (!$canReviewVendorRegistrations && $dashboardStats['pending_vendor_registrations'] > 0) {
        $alerts->push('Pending vendor registrations exist, but current role cannot review them.');
    }
    if ($dashboardStats['pending_vendor_registrations'] > 0) {
        $alerts->push('Pending vendor registrations waiting for review: ' . $dashboardStats['pending_vendor_registrations']);
    }
    if (!$systemHealth['finance_settings_table_ready'] || !$systemHealth['finance_adjustments_table_ready']) {
        $alerts->push('Finance moderation tables are missing. Run migrations to enable frontend commission controls.');
    }

    // Listing moderation data (pending_review listings for admin panel)
    $pendingModerationListings = collect();
    $listingModerationHistory = collect();
    if (Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        $pendingModerationListings = DB::table('vendor_properties as vp')
            ->leftJoin('users as vu', 'vu.id', '=', 'vp.vendor_user_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'vp.listing_approved_by_user_id')
            ->where('vp.listing_moderation_status', 'pending_review')
            ->orderBy('vp.listing_submitted_for_review_at')
            ->limit(100)
            ->get([
                'vp.id',
                'vp.vendor_user_id',
                'vp.name as listing_name',
                'vp.listing_category',
                'vp.listing_moderation_status',
                'vp.listing_admin_notes',
                'vp.listing_submitted_for_review_at',
                'vp.created_at',
                'vu.name as vendor_name',
                'vu.email as vendor_email',
                'vu.portal_vendor_id',
            ]);

        $listingModerationHistory = DB::table('vendor_properties as vp')
            ->leftJoin('users as vu', 'vu.id', '=', 'vp.vendor_user_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'vp.listing_approved_by_user_id')
            ->whereIn('vp.listing_moderation_status', ['approved', 'rejected', 'suspended'])
            ->orderByDesc('vp.listing_approved_at')
            ->limit(80)
            ->get([
                'vp.id',
                'vp.vendor_user_id',
                'vp.name as listing_name',
                'vp.listing_category',
                'vp.listing_moderation_status',
                'vp.listing_admin_notes',
                'vp.listing_approved_at',
                'vu.name as vendor_name',
                'vu.email as vendor_email',
                'vu.portal_vendor_id',
                'approver.name as approved_by_name',
                'approver.portal_role as approved_by_role',
            ]);
    }

    if ($pendingModerationListings->isNotEmpty()) {
        $alerts->push('Listings pending moderation approval: ' . $pendingModerationListings->count());
    }

    return view('admin-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_admin_user', $config['name']),
        'portalRole' => session('portal_admin_role', 'ADMIN'),
        'canManageUsers' => $canManageUsers,
        'canManageVendorUsers' => $canManageVendorUsers,
        'canCreateVendorUsers' => $canCreateVendorUsers,
        'canReviewVendorRegistrations' => $canReviewVendorRegistrations,
        'canApproveVendorRegistrationRequest' => $canApproveVendorRegistrationRequest,
        'canApproveVendorDeleteRequest' => $canApproveVendorDeleteRequest,
        'canRequestVendorDeleteApproval' => $canRequestVendorDeleteApproval,
        'canModerateFinance' => $canModerateFinance,
        'canManageContent' => $canManageContent,
        'canEditorialReview' => $canEditorialReview,
        'portalUsers' => $portalUsers,
        'adminPortalUsers' => $adminPortalUsers,
        'vendorPortalUsers' => $vendorPortalUsers,
        'pendingVendorRegistrations' => $pendingVendorRegistrations,
        'vendorRegistrationHistory' => $vendorRegistrationHistory,
        'pendingVendorDeleteRequests' => $pendingVendorDeleteRequests,
        'pendingVendorRegistrationApprovalRequests' => $pendingVendorRegistrationApprovalRequests,
        'dashboardStats' => $dashboardStats,
        'systemHealth' => $systemHealth,
        'rolePermissions' => $rolePermissions,
        'currentRolePermissions' => $currentRolePermissions,
        'recentAuditCount' => $recentAuditCount,
        'alerts' => $alerts,
        'auditLogs' => $auditLogs,
        'financeCommissionRate' => $financeCommissionRate,
        'financeCurrency' => $financeCurrency,
        'financeSummary' => $financeSummary,
        'financeDailyRows' => $financeDailyRows,
        'financeAdjustments' => $financeAdjustments,
        'financeReservationPolicy' => $financeReservationPolicy,
        'financeTaxComponents' => $financeTaxComponents,
        'listingOptionCatalog' => $listingOptionCatalog,
        'homeHeroAdminImageUrl' => $homeHeroAdminImageUrl,
        'homeHeroAdminStoredValue' => $homeHeroAdminStoredValue,
        'catalogHeroAdminImages' => $catalogHeroAdminImages,
        'catalogHeroAdminStoredValues' => $catalogHeroAdminStoredValues,
        'catalogHeroAdminCategories' => $catalogHeroAdminCategories,
        'destinationMediaOverrides' => $destinationMediaOverrides,
        'blogSidebarAdSettings' => $blogSidebarAdSettings,
        'vendorCategoryMap' => vendorPortalCategoryMap(),
        'canModerateListings' => $canModerateListings,
        'pendingModerationListings' => $pendingModerationListings,
        'listingModerationHistory' => $listingModerationHistory,
        'adminPage' => $adminPage,
        'adminAllowedPages' => $adminAllowedPages,
    ]);
});

Route::get('/admin/{page}', function (Request $request, string $page) {
    $normalizedPage = strtolower(trim($page));

    return redirect()->to('/admin?page=' . urlencode($normalizedPage));
})->where('page', 'overview|permissions|finance|media|content|catalog|moderation|listings|audit|tools');

Route::get('/portal/admin', function (Request $request) {
    $page = strtolower(trim((string) $request->query('page', 'overview')));

    return redirect()->to('/admin?page=' . urlencode($page));
});

Route::get('/portal/admin/{page}', function (string $page) {
    $normalizedPage = strtolower(trim($page));

    return redirect()->to('/admin?page=' . urlencode($normalizedPage));
})->where('page', 'overview|permissions|finance|media|content|catalog|moderation|listings|audit|tools');

Route::post('/portal/admin/finance/commission/update', function (Request $request) {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can update commission settings.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return back()->withErrors(['auth' => 'Finance settings table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'commission_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        'default_currency' => ['nullable', 'string', 'min:3', 'max:8'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    DB::table('portal_finance_settings')->updateOrInsert(
        ['setting_key' => 'commission_rate_percent'],
        [
            'value_decimal' => round((float) $validated['commission_rate_percent'], 4),
            'value_string' => null,
            'value_json' => null,
            'updated_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    $currency = strtoupper(trim((string) ($validated['default_currency'] ?? 'MVR')));
    if ($currency === '') {
        $currency = 'MVR';
    }

    DB::table('portal_finance_settings')->updateOrInsert(
        ['setting_key' => 'default_currency'],
        [
            'value_decimal' => null,
            'value_string' => substr($currency, 0, 8),
            'value_json' => null,
            'updated_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    portalAdminAuditLog('finance_commission_updated', [
        'target_role' => 'ADMIN_FINANCE',
        'commission_rate_percent' => round((float) $validated['commission_rate_percent'], 4),
        'default_currency' => substr($currency, 0, 8),
    ]);

    return back()->with('portal_notice', 'Finance commission settings updated.');
});

Route::post('/portal/admin/finance/tax-components/upsert', function (Request $request) {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can update tax components.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return back()->withErrors(['auth' => 'Finance settings table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'code' => ['required', 'string', 'max:80'],
        'label' => ['required', 'string', 'max:190'],
        'calculation_mode' => ['required', Rule::in(['percent_subtotal', 'per_guest_per_night', 'flat_booking'])],
        'default_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'applies_to' => ['required', Rule::in(['all', 'local_resident', 'foreign_national'])],
        'active' => ['nullable', Rule::in(['0', '1'])],
        'is_service_charge' => ['nullable', Rule::in(['0', '1'])],
        'min_room_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'max_room_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    portalFinanceUpsertTaxComponent([
        'code' => (string) $validated['code'],
        'label' => (string) $validated['label'],
        'calculation_mode' => (string) $validated['calculation_mode'],
        'default_rate' => (float) $validated['default_rate'],
        'applies_to' => (string) $validated['applies_to'],
        'active' => (string) ($validated['active'] ?? '1') === '1',
        'is_service_charge' => (string) ($validated['is_service_charge'] ?? '0') === '1',
        'min_room_count' => $validated['min_room_count'] ?? null,
        'max_room_count' => $validated['max_room_count'] ?? null,
    ], $actorUserId);

    portalAdminAuditLog('finance_tax_component_upserted', [
        'target_role' => 'ADMIN_FINANCE',
        'tax_code' => (string) $validated['code'],
        'calculation_mode' => (string) $validated['calculation_mode'],
        'default_rate' => round((float) $validated['default_rate'], 4),
        'applies_to' => (string) $validated['applies_to'],
    ]);

    return back()->with('portal_notice', 'Tax component saved.');
});

Route::post('/portal/admin/finance/tax-components/delete', function (Request $request) {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can delete tax components.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return back()->withErrors(['auth' => 'Finance settings table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'code' => ['required', 'string', 'max:80'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    portalFinanceDeleteTaxComponent((string) $validated['code'], $actorUserId);

    portalAdminAuditLog('finance_tax_component_deleted', [
        'target_role' => 'ADMIN_FINANCE',
        'tax_code' => (string) $validated['code'],
    ]);

    return back()->with('portal_notice', 'Tax component deleted.');
});

Route::post('/portal/admin/finance/adjustments/create', function (Request $request) {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can create finance adjustments.']);
    }

    if (!Schema::hasTable('portal_finance_adjustments')) {
        return back()->withErrors(['auth' => 'Finance adjustments table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'vendor_user_id' => ['required', 'integer', 'exists:users,id'],
        'applies_on' => ['required', 'date'],
        'adjustment_type' => ['required', Rule::in(['manual_bonus', 'manual_penalty', 'commission_credit', 'commission_debit', 'payout_hold', 'payout_release'])],
        'amount' => ['required', 'numeric', 'min:-10000000', 'max:10000000'],
        'currency' => ['nullable', 'string', 'min:3', 'max:8'],
        'invoice_reference' => ['nullable', 'string', 'max:64'],
        'reason' => ['required', 'string', 'max:2000'],
    ]);

    $currency = strtoupper(trim((string) ($validated['currency'] ?? 'MVR')));
    if ($currency === '') {
        $currency = 'MVR';
    }

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $actorRole = currentPortalAdminRole();

    $newAdjustmentId = DB::table('portal_finance_adjustments')->insertGetId([
        'vendor_user_id' => (int) $validated['vendor_user_id'],
        'applies_on' => (string) $validated['applies_on'],
        'adjustment_type' => (string) $validated['adjustment_type'],
        'amount' => round((float) $validated['amount'], 2),
        'currency' => substr($currency, 0, 8),
        'invoice_reference' => trim((string) ($validated['invoice_reference'] ?? '')) ?: null,
        'reason' => trim((string) $validated['reason']),
        'status' => 'approved',
        'moderated_by_user_id' => $actorUserId,
        'moderated_by_role' => $actorRole,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $targetVendor = User::query()->find((int) $validated['vendor_user_id']);
    portalAdminAuditLog('finance_adjustment_created', [
        'target_user_id' => (int) $validated['vendor_user_id'],
        'target_identifier' => $targetVendor instanceof User ? (string) $targetVendor->email : null,
        'target_role' => $targetVendor instanceof User ? normalizePortalRoleValue((string) $targetVendor->portal_role) : 'VENDOR',
        'adjustment_id' => (int) $newAdjustmentId,
        'adjustment_type' => (string) $validated['adjustment_type'],
        'amount' => round((float) $validated['amount'], 2),
        'currency' => substr($currency, 0, 8),
        'applies_on' => (string) $validated['applies_on'],
    ]);

    return back()->with('portal_notice', 'Finance adjustment applied successfully.');
});

Route::post('/portal/admin/listing-options/upsert', function (Request $request) {
    if (!canManageVendorUsers()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN can manage listing option catalogs.']);
    }

    if (!Schema::hasTable('portal_listing_option_catalog')) {
        return back()->withErrors(['auth' => 'Listing option catalog table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'option_type' => ['required', Rule::in(['transport_mode', 'accommodation_facility', 'room_amenity'])],
        'option_value' => ['required', 'string', 'max:120'],
        'option_label' => ['required', 'string', 'max:190'],
        'option_group' => ['nullable', 'string', 'max:80'],
        'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        'is_active' => ['nullable', 'boolean'],
    ]);

    $optionValue = strtolower(trim((string) ($validated['option_value'] ?? '')));
    $optionValue = preg_replace('/\s+/', ' ', $optionValue) ?? $optionValue;
    if ($optionValue === '') {
        return back()->withErrors(['auth' => 'Option value cannot be empty.'])->withInput();
    }

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    DB::table('portal_listing_option_catalog')->updateOrInsert(
        [
            'option_type' => (string) $validated['option_type'],
            'option_value' => $optionValue,
        ],
        [
            'option_label' => trim((string) $validated['option_label']),
            'option_group' => trim((string) ($validated['option_group'] ?? '')) ?: null,
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'updated_by_user_id' => $actorUserId,
            'created_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    portalAdminAuditLog('listing_option_catalog.upsert', [
        'target_role' => 'VENDOR',
        'option_type' => (string) $validated['option_type'],
        'option_value' => $optionValue,
    ]);

    return back()->with('portal_notice', 'Listing option saved.');
});

Route::post('/portal/admin/listing-options/{option}/delete', function (int $option) {
    if (!canManageVendorUsers()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN can manage listing option catalogs.']);
    }

    if (!Schema::hasTable('portal_listing_option_catalog')) {
        return back()->withErrors(['auth' => 'Listing option catalog table is not ready. Run migrations first.']);
    }

    $targetOption = DB::table('portal_listing_option_catalog')->where('id', $option)->first();
    if (!$targetOption) {
        return back()->withErrors(['auth' => 'Listing option not found.']);
    }

    DB::table('portal_listing_option_catalog')->where('id', $option)->delete();

    portalAdminAuditLog('listing_option_catalog.delete', [
        'target_role' => 'VENDOR',
        'option_type' => (string) ($targetOption->option_type ?? ''),
        'option_value' => (string) ($targetOption->option_value ?? ''),
    ]);

    return back()->with('portal_notice', 'Listing option removed.');
});

Route::post('/portal/admin/media-hero/update', function (Request $request) {
    if (!canManageVendorUsers()) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN can update homepage and category hero images.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Settings table is not ready. Run migrations first.']);
    }

    $catalogHeroAdminCategories = [
        'accommodation' => 'Accommodation',
        'marine-transport' => 'Marine Transport',
        'land-transport' => 'Land Transport',
        'excursion' => 'Excursion',
        'remote_workspace' => 'Remote Workspace',
        'conference_room' => 'Conference & Meeting Spaces',
        'resort_day_visit' => 'Resort Day Visit',
        'restaurant' => 'Restaurant',
        'vehicle_rental' => 'Vehicle Rental',
    ];

    $validationRules = [
        'home_hero_image_url' => ['nullable', 'string', 'max:2048'],
        'home_hero_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'home_hero_image_clear' => ['nullable', 'boolean'],
    ];
    foreach (array_keys($catalogHeroAdminCategories) as $categoryKey) {
        $fieldName = 'catalog_hero_image_' . str_replace('-', '_', $categoryKey);
        $validationRules[$fieldName] = ['nullable', 'string', 'max:2048'];
        $validationRules[$fieldName . '_file'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        $validationRules[$fieldName . '_clear'] = ['nullable', 'boolean'];
    }

    $validated = $request->validate($validationRules);
    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    $existingSettings = DB::table('portal_finance_settings')
        ->where(function ($query) use ($catalogHeroAdminCategories) {
            $query->where('setting_key', 'home_hero_image_url');
            foreach (array_keys($catalogHeroAdminCategories) as $categoryKey) {
                $query->orWhere('setting_key', 'catalog_hero_image_' . str_replace('-', '_', $categoryKey));
            }
        })
        ->pluck('value_string', 'setting_key');

    $persistSetting = static function (string $settingKey, ?string $rawValue, ?int $updatedByUserId): void {
        $value = trim((string) ($rawValue ?? ''));
        if (str_starts_with($value, 'http://')) {
            $value = 'https://' . ltrim(substr($value, 7), '/');
        }

        DB::table('portal_finance_settings')->updateOrInsert(
            ['setting_key' => $settingKey],
            [
                'value_decimal' => null,
                'value_string' => $value !== '' ? $value : null,
                'value_json' => null,
                'updated_by_user_id' => $updatedByUserId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    };

    $resolveMediaSetting = function (string $settingKey, string $slot, string $urlField, string $fileField, string $clearField) use ($request, $validated, $existingSettings, $persistSetting, $actorUserId): void {
        $currentValue = trim((string) ($existingSettings->get($settingKey) ?? ''));
        $nextValue = $currentValue;
        $shouldClear = $request->boolean($clearField);
        $uploadedFile = $request->file($fileField);
        $submittedUrl = trim((string) ($validated[$urlField] ?? ''));

        if ($shouldClear) {
            portalDeleteManagedPublicAsset($currentValue);
            $nextValue = '';
        } elseif ($uploadedFile) {
            $storedPath = portalStoreAdminHeroImage($uploadedFile, $slot);
            if ($storedPath === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $fileField => 'Unable to process and store the uploaded image. Use a valid JPG, PNG, or WebP file.',
                ]);
            }

            // In production, managed hero media must not silently fall back to
            // ephemeral local storage because that causes broken homepage images.
            $managedDisk = portalManagedMediaDiskName();
            if ($managedDisk !== 'public' && str_starts_with($storedPath, '__public__/')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $fileField => 'Image upload reached local fallback storage. Managed media disk write failed; please retry and contact support if this persists.',
                ]);
            }

            if ($currentValue !== '' && $currentValue !== $storedPath) {
                portalDeleteManagedPublicAsset($currentValue);
            }

            $nextValue = $storedPath;
        } elseif ($submittedUrl !== '' && $submittedUrl !== $currentValue) {
            if ($currentValue !== '' && $currentValue !== $submittedUrl) {
                portalDeleteManagedPublicAsset($currentValue);
            }

            $nextValue = $submittedUrl;
        }

        $persistSetting($settingKey, $nextValue, $actorUserId);
    };

    $resolveMediaSetting('home_hero_image_url', 'home', 'home_hero_image_url', 'home_hero_image_file', 'home_hero_image_clear');
    foreach (array_keys($catalogHeroAdminCategories) as $categoryKey) {
        $fieldName = 'catalog_hero_image_' . str_replace('-', '_', $categoryKey);
        $resolveMediaSetting($fieldName, $categoryKey, $fieldName, $fieldName . '_file', $fieldName . '_clear');
    }

    portalAdminAuditLog('media_hero_settings.updated', [
        'target_role' => 'ADMIN',
        'updated_category_hero_keys' => array_keys($catalogHeroAdminCategories),
    ]);

    return redirect('/admin?page=media')->with('portal_notice', 'Hero image updated successfully.');
});

Route::post('/portal/admin/media-destination/update', function (Request $request) {
    if (!canManageVendorUsers()) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN can update destination image overrides.']);
    }

    if (!Schema::hasTable('portal_destination_media_overrides')) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Destination media override table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'destination_name' => ['required', 'string', 'max:190'],
        'destination_type' => ['nullable', Rule::in(['destination', 'island', 'atoll', 'city'])],
        'destination_image_url' => ['nullable', 'string', 'max:2048'],
        'destination_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'destination_image_clear' => ['nullable', 'boolean'],
        'destination_key' => ['nullable', 'string', 'max:190'],
    ]);

    $destinationName = trim((string) ($validated['destination_name'] ?? ''));
    $destinationKey = trim((string) ($validated['destination_key'] ?? ''));
    if ($destinationKey === '') {
        $destinationKey = portalNormalizeDestinationMediaKey($destinationName);
    }
    if ($destinationKey === '') {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Destination name is required to save an override.'])->withInput();
    }

    $currentRow = DB::table('portal_destination_media_overrides')
        ->where('destination_key', $destinationKey)
        ->first();

    $currentValue = trim((string) ($currentRow->image_value ?? ''));
    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $nextValue = $currentValue;
    $shouldClear = $request->boolean('destination_image_clear');
    $uploadedFile = $request->file('destination_image_file');
    $submittedUrl = trim((string) ($validated['destination_image_url'] ?? ''));

    if ($shouldClear) {
        portalDeleteManagedPublicAsset($currentValue, 'portal-admin/destination-images/');
        DB::table('portal_destination_media_overrides')->where('destination_key', $destinationKey)->delete();

        portalAdminAuditLog('media_destination_override.cleared', [
            'target_role' => 'ADMIN',
            'destination_key' => $destinationKey,
            'destination_name' => $destinationName,
        ]);

        return redirect('/admin?page=media')->with('portal_notice', 'Destination image override removed.');
    }

    if ($uploadedFile) {
        $storedPath = portalStoreAdminDestinationImage($uploadedFile, $destinationKey);
        if ($storedPath === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'destination_image_file' => 'Unable to process and store the uploaded destination image. Use a valid JPG, PNG, or WebP file.',
            ]);
        }

        if ($currentValue !== '' && $currentValue !== $storedPath) {
            portalDeleteManagedPublicAsset($currentValue, 'portal-admin/destination-images/');
        }

        $nextValue = $storedPath;
    } elseif ($submittedUrl !== '') {
        if ($currentValue !== '' && $currentValue !== $submittedUrl) {
            portalDeleteManagedPublicAsset($currentValue, 'portal-admin/destination-images/');
        }

        if (str_starts_with($submittedUrl, 'http://')) {
            $submittedUrl = 'https://' . ltrim(substr($submittedUrl, 7), '/');
        }

        $nextValue = $submittedUrl;
    }

    if ($nextValue === '') {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Upload an image or paste an HTTPS URL to create a destination override.'])->withInput();
    }

    DB::table('portal_destination_media_overrides')->updateOrInsert(
        ['destination_key' => $destinationKey],
        [
            'destination_name' => $destinationName,
            'destination_type' => trim((string) ($validated['destination_type'] ?? 'destination')) ?: 'destination',
            'image_value' => $nextValue,
            'updated_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    portalAdminAuditLog('media_destination_override.updated', [
        'target_role' => 'ADMIN',
        'destination_key' => $destinationKey,
        'destination_name' => $destinationName,
        'destination_type' => trim((string) ($validated['destination_type'] ?? 'destination')) ?: 'destination',
    ]);

    return redirect('/admin?page=media')->with('portal_notice', 'Destination image override saved.');
});

Route::post('/portal/admin/media-blog-ad/update', function (Request $request) {
    if (!canManageVendorUsers()) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN can update blog ad settings.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return redirect('/admin?page=media')->withErrors(['auth' => 'Settings table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'blog_sidebar_ad_title' => ['nullable', 'string', 'max:190'],
        'blog_sidebar_ad_brand' => ['nullable', 'string', 'max:120'],
        'blog_sidebar_ad_cta_label' => ['nullable', 'string', 'max:120'],
        'blog_sidebar_ad_cta_url' => ['nullable', 'string', 'max:2048'],
        'blog_sidebar_ad_image_url' => ['nullable', 'string', 'max:2048'],
        'blog_sidebar_ad_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'blog_sidebar_ad_image_clear' => ['nullable', 'boolean'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    $existingImageValue = trim((string) (DB::table('portal_finance_settings')
        ->where('setting_key', 'blog_sidebar_ad_image')
        ->value('value_string') ?? ''));

    $nextImageValue = $existingImageValue;
    $shouldClear = $request->boolean('blog_sidebar_ad_image_clear');
    $uploadedFile = $request->file('blog_sidebar_ad_image_file');
    $submittedImageUrl = trim((string) ($validated['blog_sidebar_ad_image_url'] ?? ''));

    if ($shouldClear) {
        portalDeleteManagedPublicAsset($existingImageValue);
        $nextImageValue = '';
    } elseif ($uploadedFile) {
        $storedPath = portalStoreAdminHeroImage($uploadedFile, 'blog_ad');
        if ($storedPath === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'blog_sidebar_ad_image_file' => 'Unable to process and store the uploaded image. Use a valid JPG, PNG, or WebP file.',
            ]);
        }

        if ($existingImageValue !== '' && $existingImageValue !== $storedPath) {
            portalDeleteManagedPublicAsset($existingImageValue);
        }

        $nextImageValue = $storedPath;
    } elseif ($submittedImageUrl !== '') {
        if (str_starts_with($submittedImageUrl, 'http://')) {
            $submittedImageUrl = 'https://' . ltrim(substr($submittedImageUrl, 7), '/');
        }

        if ($existingImageValue !== '' && $existingImageValue !== $submittedImageUrl) {
            portalDeleteManagedPublicAsset($existingImageValue);
        }

        $nextImageValue = $submittedImageUrl;
    }

    $persistSetting = static function (string $settingKey, ?string $value, ?int $updatedByUserId): void {
        $storedValue = trim((string) ($value ?? ''));
        DB::table('portal_finance_settings')->updateOrInsert(
            ['setting_key' => $settingKey],
            [
                'value_decimal' => null,
                'value_string' => $storedValue !== '' ? $storedValue : null,
                'value_json' => null,
                'updated_by_user_id' => $updatedByUserId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    };

    $persistSetting('blog_sidebar_ad_title', $validated['blog_sidebar_ad_title'] ?? null, $actorUserId);
    $persistSetting('blog_sidebar_ad_brand', $validated['blog_sidebar_ad_brand'] ?? null, $actorUserId);
    $persistSetting('blog_sidebar_ad_cta_label', $validated['blog_sidebar_ad_cta_label'] ?? null, $actorUserId);
    $persistSetting('blog_sidebar_ad_cta_url', $validated['blog_sidebar_ad_cta_url'] ?? null, $actorUserId);
    $persistSetting('blog_sidebar_ad_image', $nextImageValue, $actorUserId);

    portalAdminAuditLog('media_blog_sidebar_ad.updated', [
        'target_role' => 'ADMIN_MEDIA',
        'has_image' => $nextImageValue !== '',
    ]);

    return redirect('/admin?page=media')->with('portal_notice', 'Blog article ad settings updated.');
});

Route::get('/portal/admin/blog', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can manage blog posts.']);
    }

    $posts = collect();
    if (Schema::hasTable('blog_posts')) {
        $posts = BlogPost::query()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    return view('admin-blog-index', [
        'posts' => $posts,
        'canEditorialReview' => canEditorialReview(),
    ]);
});

Route::get('/portal/admin/blog/create', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create blog posts.']);
    }

    return view('admin-blog-form', [
        'mode' => 'create',
        'post' => null,
        'blogCategories' => blogCategoryDefinitions(),
        'blogTags' => blogTagDefinitions(),
        'canEditorialReview' => canEditorialReview(),
    ]);
});

Route::post('/portal/admin/blog', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create blog posts.']);
    }

    if (!Schema::hasTable('blog_posts')) {
        return back()->withErrors(['auth' => 'Blog table is not ready. Run migrations first.'])->withInput();
    }

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:180'],
        'excerpt' => ['nullable', 'string', 'max:420'],
        'content' => ['required', 'string', 'min:50'],
        'blog_category_slug' => ['nullable', Rule::in(array_keys(blogCategoryDefinitions()))],
        'blog_tag_slugs' => ['nullable', 'array'],
        'blog_tag_slugs.*' => ['string', 'max:80'],
        'blog_tag_input' => ['nullable', 'string', 'max:600'],
        'is_published' => ['nullable', 'boolean'],
        'is_featured' => ['nullable', 'boolean'],
        'cover_image' => ['nullable', 'image', 'max:6144'],
        'article_image_0' => ['nullable', 'image', 'max:6144'],
        'article_image_1' => ['nullable', 'image', 'max:6144'],
        'article_image_2' => ['nullable', 'image', 'max:6144'],
        'remove_article_image_0' => ['nullable', 'boolean'],
        'remove_article_image_1' => ['nullable', 'boolean'],
        'remove_article_image_2' => ['nullable', 'boolean'],
        'gallery_position'         => ['nullable', 'string', 'in:after_intro,after_first_h2,after_second_h2,end'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $isMediaRole = currentPortalAdminRole() === 'ADMIN_MEDIA';
    $slug = generateUniqueBlogSlug((string) $validated['title']);

    $post = new BlogPost();
    $post->title = trim((string) $validated['title']);
    $post->slug = $slug;
    $post->excerpt = trim((string) ($validated['excerpt'] ?? '')) ?: null;
    $post->content = (string) $validated['content'];
    if (Schema::hasColumn('blog_posts', 'blog_category_slug')) {
        $post->blog_category_slug = trim(Str::lower((string) ($validated['blog_category_slug'] ?? '')));
    }
    if (Schema::hasColumn('blog_posts', 'blog_tag_slugs')) {
        $post->blog_tag_slugs = blogBuildTagSlugsFromInput($validated);
    }
    $post->is_published = $isMediaRole ? false : (bool) ($validated['is_published'] ?? false);
    $post->is_featured = (bool) ($validated['is_featured'] ?? false);
    $post->published_at = $post->is_published ? now() : null;
    $post->editorial_status = $isMediaRole ? 'pending_review' : 'approved';
    $post->editorial_notes = null;
    $post->reviewed_by_user_id = null;
    $post->reviewed_at = null;
    $post->created_by_user_id = $actorUserId;
    $post->updated_by_user_id = $actorUserId;
    $post->save();

    if ($request->hasFile('cover_image')) {
        $coverFile = $request->file('cover_image');
        if ($coverFile !== null && $coverFile->isValid()) {
            $extension = strtolower((string) $coverFile->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }

            $blogMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
            if ($blogMediaDisk === '') {
                $blogMediaDisk = 'public';
            }

            $storagePath = 'blog/' . (int) $post->id . '/cover.' . $extension;
            $uploadResult = false;
            foreach (array_values(array_unique([$blogMediaDisk, 'public'])) as $candidateDisk) {
                try {
                    $uploadResult = Storage::disk($candidateDisk)->putFileAs('blog/' . (int) $post->id, $coverFile, 'cover.' . $extension);
                } catch (\Throwable $exception) {
                    $uploadResult = false;
                }
                if ($uploadResult !== false) {
                    break;
                }
            }
            if ($uploadResult === false) {
                \Illuminate\Support\Facades\Log::error('blog_cover_upload: putFileAs failed (create)', ['disk' => $blogMediaDisk, 'path' => $storagePath, 'post_id' => (int) $post->id]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cover_image' => 'Unable to store cover image right now. Please try again.',
                ]);
            }
            $post->cover_image_path = $storagePath;
            if (Schema::hasColumn('blog_posts', 'cover_image_url')) {
                $post->cover_image_url = blogResolveCoverImageUrl($storagePath);
            }
            $post->save();
        }
    }

    if ($post->is_featured) {
            if (Schema::hasColumn('blog_posts', 'article_images')) {
                $blogMediaDiskForArticle = trim((string) config('filesystems.portal_media_disk', 'public'));
                if ($blogMediaDiskForArticle === '') {
                    $blogMediaDiskForArticle = 'public';
                }
                $existingRawArticleImages = (array) ($post->article_images ?? []);
                $existingArticleImages = [];
                foreach ([0, 1, 2] as $slot) {
                    $existingArticleImages[$slot] = trim((string) ($existingRawArticleImages[$slot] ?? ''));
                }
                foreach ([0, 1, 2] as $slot) {
                    $fileKey = 'article_image_' . $slot;
                    if ($request->hasFile($fileKey)) {
                        $articleFile = $request->file($fileKey);
                        if ($articleFile !== null && $articleFile->isValid()) {
                            $ext = strtolower((string) $articleFile->getClientOriginalExtension()) ?: 'jpg';
                            $articlePath = 'blog/' . (int) $post->id . '/article_' . $slot . '.' . $ext;
                            $articleUploadOk = false;
                            foreach (array_values(array_unique([$blogMediaDiskForArticle, 'public'])) as $candidateDisk) {
                                try {
                                    $articleUploadOk = Storage::disk($candidateDisk)->putFileAs('blog/' . (int) $post->id, $articleFile, 'article_' . $slot . '.' . $ext) !== false;
                                } catch (\Throwable $exception) {
                                    $articleUploadOk = false;
                                }
                                if ($articleUploadOk) {
                                    break;
                                }
                            }
                            if (!$articleUploadOk) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    $fileKey => 'Unable to store article image right now. Please try again.',
                                ]);
                            }
                            $existingArticleImages[$slot] = $articlePath;
                        }
                    }
                }
                $post->article_images = [$existingArticleImages[0], $existingArticleImages[1], $existingArticleImages[2]];
                if (Schema::hasColumn('blog_posts', 'gallery_position')) {
                    $post->gallery_position = $validated['gallery_position'] ?? 'after_intro';
                }
                $post->save();
            }

        BlogPost::query()
            ->where('id', '!=', (int) $post->id)
            ->update(['is_featured' => false, 'updated_at' => now()]);
    }

    portalAdminAuditLog('blog_post_created', [
        'target_role' => 'ADMIN',
        'post_id' => (int) $post->id,
        'post_slug' => (string) $post->slug,
        'is_featured' => (bool) $post->is_featured,
        'is_published' => (bool) $post->is_published,
    ]);

    return redirect('/portal/admin/blog')->with('portal_notice', 'Blog post created successfully.');
});

Route::post('/portal/admin/blog/upload-image', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    if (!canManageContent()) {
        return response()->json(['message' => 'Only ADMIN_SUPER or ADMIN_MEDIA can upload blog images.'], 403);
    }

    $validated = $request->validate([
        'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:6144'],
    ]);

    $imageFile = $request->file('image');
    if ($imageFile === null || !$imageFile->isValid()) {
        return response()->json(['message' => 'Invalid image upload.'], 422);
    }

    $extension = strtolower((string) $imageFile->getClientOriginalExtension());
    if ($extension === '') {
        $extension = 'jpg';
    }

    $directory = 'blog/inline/' . now()->format('Y/m');
    $filename = (string) Str::uuid() . '.' . $extension;

    $mediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($mediaDisk === '') {
        $mediaDisk = 'public';
    }

    $uploadOk = false;
    foreach (array_values(array_unique([$mediaDisk, 'public'])) as $candidateDisk) {
        try {
            $uploadOk = Storage::disk($candidateDisk)->putFileAs($directory, $imageFile, $filename) !== false;
        } catch (\Throwable $exception) {
            $uploadOk = false;
        }
        if ($uploadOk) {
            break;
        }
    }

    if (!$uploadOk) {
        \Illuminate\Support\Facades\Log::error('blog_inline_upload: putFileAs failed', [
            'disk' => $mediaDisk,
            'directory' => $directory,
            'filename' => $filename,
        ]);

        return response()->json(['message' => 'Unable to store inline image right now. Please try again.'], 500);
    }
    $storedPath = $directory . '/' . $filename;

    return response()->json([
           'url' => '/media/blog-inline/' . $storedPath,
        'path' => $storedPath,
    ]);
});

Route::post('/portal/admin/blog/{post}/article/{slot}/upload', function (Request $request, int $post, int $slot) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    if (!canManageContent()) {
        return response()->json(['message' => 'Only ADMIN_SUPER or ADMIN_MEDIA can manage blog article images.'], 403);
    }

    if ($slot < 0 || $slot > 2) {
        return response()->json(['message' => 'Invalid article image slot.'], 422);
    }

    if (!Schema::hasTable('blog_posts') || !Schema::hasColumn('blog_posts', 'article_images')) {
        return response()->json(['message' => 'Article image storage is not configured.'], 422);
    }

    $blogPost = BlogPost::query()->find($post);
    if (!$blogPost) {
        return response()->json(['message' => 'Blog post not found.'], 404);
    }

    $validated = $request->validate([
        'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:6144'],
    ]);

    $imageFile = $request->file('image');
    if ($imageFile === null || !$imageFile->isValid()) {
        return response()->json(['message' => 'Invalid image upload.'], 422);
    }

    $extension = strtolower((string) $imageFile->getClientOriginalExtension());
    if ($extension === '') {
        $extension = 'jpg';
    }

    $blogMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($blogMediaDisk === '') {
        $blogMediaDisk = 'public';
    }

    $articlePath = 'blog/' . (int) $blogPost->id . '/article_' . $slot . '.' . $extension;

    $existingRawArticleImages = (array) ($blogPost->article_images ?? []);
    $existingArticleImages = [];
    foreach ([0, 1, 2] as $index) {
        $existingArticleImages[$index] = trim((string) ($existingRawArticleImages[$index] ?? ''));
    }

    $existingPath = trim((string) ($existingArticleImages[$slot] ?? ''));
    if ($existingPath !== '' && $existingPath !== $articlePath) {
        try {
            Storage::disk($blogMediaDisk)->delete($existingPath);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('blog_article_upload: failed to delete previous slot image', [
                'disk' => $blogMediaDisk,
                'path' => $existingPath,
                'post_id' => (int) $blogPost->id,
                'slot' => $slot,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    $uploadResult = false;
    foreach (array_values(array_unique([$blogMediaDisk, 'public'])) as $candidateDisk) {
        try {
            $uploadResult = Storage::disk($candidateDisk)->putFileAs('blog/' . (int) $blogPost->id, $imageFile, 'article_' . $slot . '.' . $extension);
        } catch (\Throwable $exception) {
            $uploadResult = false;
        }
        if ($uploadResult !== false) {
            break;
        }
    }
    if ($uploadResult === false) {
        \Illuminate\Support\Facades\Log::error('blog_article_upload: putFileAs failed', [
            'disk' => $blogMediaDisk,
            'path' => $articlePath,
            'post_id' => (int) $blogPost->id,
            'slot' => $slot,
        ]);

        return response()->json(['message' => 'Unable to store article image.'], 500);
    }

    $existingArticleImages[$slot] = $articlePath;
    $blogPost->article_images = [$existingArticleImages[0], $existingArticleImages[1], $existingArticleImages[2]];
    if (Schema::hasColumn('blog_posts', 'updated_by_user_id')) {
        $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
        $blogPost->updated_by_user_id = $actorUserId;
    }
    $blogPost->save();

    return response()->json([
        'url' => '/media/blog/' . (int) $blogPost->id . '/article/' . $slot,
        'path' => $articlePath,
        'slot' => $slot,
    ]);
});

Route::get('/portal/admin/blog/{post}/edit', function (int $post) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit blog posts.']);
    }

    if (!Schema::hasTable('blog_posts')) {
        return redirect('/portal/admin/blog')->withErrors(['auth' => 'Blog table is not ready. Run migrations first.']);
    }

    $blogPost = BlogPost::query()->findOrFail($post);

    return view('admin-blog-form', [
        'mode' => 'edit',
        'post' => $blogPost,
        'blogCategories' => blogCategoryDefinitions(),
        'blogTags' => blogTagDefinitions(),
        'canEditorialReview' => canEditorialReview(),
    ]);
});

Route::post('/portal/admin/blog/{post}', function (Request $request, int $post) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit blog posts.']);
    }

    if (!Schema::hasTable('blog_posts')) {
        return back()->withErrors(['auth' => 'Blog table is not ready. Run migrations first.'])->withInput();
    }

    $blogPost = BlogPost::query()->findOrFail($post);

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:180'],
        'excerpt' => ['nullable', 'string', 'max:420'],
        'content' => ['required', 'string', 'min:50'],
        'blog_category_slug' => ['nullable', Rule::in(array_keys(blogCategoryDefinitions()))],
        'blog_tag_slugs' => ['nullable', 'array'],
        'blog_tag_slugs.*' => ['string', 'max:80'],
        'blog_tag_input' => ['nullable', 'string', 'max:600'],
        'is_published' => ['nullable', 'boolean'],
        'is_featured' => ['nullable', 'boolean'],
        'cover_image' => ['nullable', 'image', 'max:6144'],
        'remove_cover_image' => ['nullable', 'boolean'],
        'article_image_0' => ['nullable', 'image', 'max:6144'],
        'article_image_1' => ['nullable', 'image', 'max:6144'],
        'article_image_2' => ['nullable', 'image', 'max:6144'],
        'remove_article_image_0' => ['nullable', 'boolean'],
        'remove_article_image_1' => ['nullable', 'boolean'],
        'remove_article_image_2' => ['nullable', 'boolean'],
        'gallery_position'         => ['nullable', 'string', 'in:after_intro,after_first_h2,after_second_h2,end'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $isMediaRole = currentPortalAdminRole() === 'ADMIN_MEDIA';

    $blogPost->title = trim((string) $validated['title']);
    $blogPost->slug = generateUniqueBlogSlug((string) $validated['title'], (int) $blogPost->id);
    $blogPost->excerpt = trim((string) ($validated['excerpt'] ?? '')) ?: null;
    $blogPost->content = (string) $validated['content'];
    if (Schema::hasColumn('blog_posts', 'blog_category_slug')) {
        $blogPost->blog_category_slug = trim(Str::lower((string) ($validated['blog_category_slug'] ?? '')));
    }
    if (Schema::hasColumn('blog_posts', 'blog_tag_slugs')) {
        $blogPost->blog_tag_slugs = blogBuildTagSlugsFromInput($validated);
    }
    $blogPost->is_published = $isMediaRole ? false : (bool) ($validated['is_published'] ?? false);
    $blogPost->is_featured = (bool) ($validated['is_featured'] ?? false);
    if ($isMediaRole) {
        $blogPost->editorial_status = 'pending_review';
        $blogPost->reviewed_by_user_id = null;
        $blogPost->reviewed_at = null;
    }

    if ($blogPost->is_published && $blogPost->published_at === null) {
        $blogPost->published_at = now();
    }
    if (!$blogPost->is_published) {
        $blogPost->published_at = null;
    }

    $blogMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
    if ($blogMediaDisk === '') {
        $blogMediaDisk = 'public';
    }

    if ((bool) ($validated['remove_cover_image'] ?? false)) {
        $existingPath = trim((string) ($blogPost->cover_image_path ?? ''));
        if ($existingPath !== '') {
            Storage::disk($blogMediaDisk)->delete($existingPath);
        }
        $blogPost->cover_image_path = null;
        if (Schema::hasColumn('blog_posts', 'cover_image_url')) {
            $blogPost->cover_image_url = null;
        }
    }

    if ($request->hasFile('cover_image')) {
        $coverFile = $request->file('cover_image');
        if ($coverFile !== null && $coverFile->isValid()) {
            $existingPath = trim((string) ($blogPost->cover_image_path ?? ''));
            if ($existingPath !== '') {
                Storage::disk($blogMediaDisk)->delete($existingPath);
            }

            $extension = strtolower((string) $coverFile->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }

            $storagePath = 'blog/' . (int) $blogPost->id . '/cover.' . $extension;
            $uploadResult = false;
            foreach (array_values(array_unique([$blogMediaDisk, 'public'])) as $candidateDisk) {
                try {
                    $uploadResult = Storage::disk($candidateDisk)->putFileAs('blog/' . (int) $blogPost->id, $coverFile, 'cover.' . $extension);
                } catch (\Throwable $exception) {
                    $uploadResult = false;
                }
                if ($uploadResult !== false) {
                    break;
                }
            }
            if ($uploadResult === false) {
                \Illuminate\Support\Facades\Log::error('blog_cover_upload: putFileAs failed (edit)', ['disk' => $blogMediaDisk, 'path' => $storagePath, 'post_id' => (int) $blogPost->id]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cover_image' => 'Unable to store cover image right now. Please try again.',
                ]);
            }
            $blogPost->cover_image_path = $storagePath;
            if (Schema::hasColumn('blog_posts', 'cover_image_url')) {
                $blogPost->cover_image_url = blogResolveCoverImageUrl($storagePath);
            }
        }
    }

    $blogPost->updated_by_user_id = $actorUserId;
    $blogPost->save();

    if ($blogPost->is_featured) {
            if (Schema::hasColumn('blog_posts', 'article_images')) {
                $blogMediaDiskForArticle = trim((string) config('filesystems.portal_media_disk', 'public'));
                if ($blogMediaDiskForArticle === '') {
                    $blogMediaDiskForArticle = 'public';
                }
                $existingRawArticleImages = (array) ($blogPost->article_images ?? []);
                $existingArticleImages = [];
                foreach ([0, 1, 2] as $slot) {
                    $existingArticleImages[$slot] = trim((string) ($existingRawArticleImages[$slot] ?? ''));
                }
                foreach ([0, 1, 2] as $slot) {
                    if ((bool) ($validated['remove_article_image_' . $slot] ?? false)) {
                        if ($existingArticleImages[$slot] !== '') {
                            Storage::disk($blogMediaDiskForArticle)->delete($existingArticleImages[$slot]);
                        }
                        $existingArticleImages[$slot] = '';
                    }
                    $fileKey = 'article_image_' . $slot;
                    if ($request->hasFile($fileKey)) {
                        $articleFile = $request->file($fileKey);
                        if ($articleFile !== null && $articleFile->isValid()) {
                            if ($existingArticleImages[$slot] !== '') {
                                Storage::disk($blogMediaDiskForArticle)->delete($existingArticleImages[$slot]);
                            }
                            $ext = strtolower((string) $articleFile->getClientOriginalExtension()) ?: 'jpg';
                            $articlePath = 'blog/' . (int) $blogPost->id . '/article_' . $slot . '.' . $ext;
                            $articleUploadOk = false;
                            foreach (array_values(array_unique([$blogMediaDiskForArticle, 'public'])) as $candidateDisk) {
                                try {
                                    $articleUploadOk = Storage::disk($candidateDisk)->putFileAs('blog/' . (int) $blogPost->id, $articleFile, 'article_' . $slot . '.' . $ext) !== false;
                                } catch (\Throwable $exception) {
                                    $articleUploadOk = false;
                                }
                                if ($articleUploadOk) {
                                    break;
                                }
                            }
                            if (!$articleUploadOk) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    $fileKey => 'Unable to store article image right now. Please try again.',
                                ]);
                            }
                            $existingArticleImages[$slot] = $articlePath;
                        }
                    }
                }
                $blogPost->article_images = [$existingArticleImages[0], $existingArticleImages[1], $existingArticleImages[2]];
                if (Schema::hasColumn('blog_posts', 'gallery_position')) {
                    $blogPost->gallery_position = $validated['gallery_position'] ?? 'after_intro';
                }
                $blogPost->save();
            }

        BlogPost::query()
            ->where('id', '!=', (int) $blogPost->id)
            ->update(['is_featured' => false, 'updated_at' => now()]);
    }

    portalAdminAuditLog('blog_post_updated', [
        'target_role' => 'ADMIN',
        'post_id' => (int) $blogPost->id,
        'post_slug' => (string) $blogPost->slug,
        'is_featured' => (bool) $blogPost->is_featured,
        'is_published' => (bool) $blogPost->is_published,
    ]);

    return redirect('/portal/admin/blog')->with('portal_notice', 'Blog post updated successfully.');
});

Route::post('/portal/admin/blog/{post}/delete', function (int $post) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can delete blog posts.']);
    }

    if (!Schema::hasTable('blog_posts')) {
        return redirect('/portal/admin/blog')->withErrors(['auth' => 'Blog table is not ready. Run migrations first.']);
    }

    $blogPost = BlogPost::query()->findOrFail($post);
    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $postId = (int) $blogPost->id;
    $postSlug = (string) $blogPost->slug;
    $coverPath = trim((string) ($blogPost->cover_image_path ?? ''));
    if ($coverPath !== '') {
        Storage::disk('public')->delete($coverPath);
    }
    Storage::disk('public')->deleteDirectory('blog/' . $postId);

    $blogPost->delete();

    portalAdminAuditLog('blog_post_deleted', [
        'target_role' => 'ADMIN',
        'post_id' => $postId,
        'post_slug' => $postSlug,
        'deleted_by_user_id' => $actorUserId,
    ]);

    return redirect('/portal/admin/blog')->with('portal_notice', 'Blog post deleted successfully.');
});

Route::post('/portal/admin/blog/{post}/review', function (Request $request, int $post) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canEditorialReview()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER can review blog content.']);
    }

    if (!Schema::hasTable('blog_posts')) {
        return back()->withErrors(['auth' => 'Blog table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'decision' => ['required', Rule::in(['approve', 'reject'])],
        'editorial_notes' => ['nullable', 'string', 'max:1500'],
    ]);

    $blogPost = BlogPost::query()->findOrFail($post);
    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    if ($validated['decision'] === 'approve') {
        $blogPost->editorial_status = 'approved';
        $blogPost->is_published = true;
        if ($blogPost->published_at === null) {
            $blogPost->published_at = now();
        }
    } else {
        $blogPost->editorial_status = 'rejected';
        $blogPost->is_published = false;
        $blogPost->published_at = null;
    }

    $blogPost->editorial_notes = trim((string) ($validated['editorial_notes'] ?? '')) ?: null;
    $blogPost->reviewed_by_user_id = $actorUserId;
    $blogPost->reviewed_at = now();
    $blogPost->updated_by_user_id = $actorUserId;
    $blogPost->save();

    portalAdminAuditLog('blog_post_reviewed', [
        'target_role' => 'ADMIN_SUPER',
        'post_id' => (int) $blogPost->id,
        'post_slug' => (string) $blogPost->slug,
        'decision' => (string) $validated['decision'],
    ]);

    return redirect('/portal/admin/blog')->with('portal_notice', $validated['decision'] === 'approve' ? 'Blog post approved.' : 'Blog post rejected with editorial notes.');
});

Route::get('/portal/admin/atlas', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can manage island atlas data.']);
    }

    if (!Schema::hasTable('atolls') || !Schema::hasTable('islands')) {
        return redirect('/admin')->withErrors(['auth' => 'Atolls/Islands tables are not ready. Run migrations first.']);
    }

    $atolls = \App\Models\Atoll::query()->orderedByCode()->get();
    $islands = \App\Models\Island::query()->with('atoll')->orderBy('name')->limit(1200)->get();

    return view('admin-atlas-index', [
        'atolls' => $atolls,
        'islands' => $islands,
    ]);
});

Route::get('/portal/admin/atlas/atolls/create', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create atolls.']);
    }

    return view('admin-atlas-form', [
        'mode' => 'create',
        'entity' => 'atoll',
        'record' => null,
        'atolls' => collect(),
    ]);
});

Route::post('/portal/admin/atlas/atolls', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create atolls.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'slug' => ['nullable', 'string', 'max:140'],
        'code' => ['nullable', 'string', 'max:20'],
        'description' => ['nullable', 'string', 'max:2000'],
        'wikipedia_title' => ['nullable', 'string', 'max:220'],
        'photo' => ['nullable', 'image', 'max:6144'],
    ]);

    $atoll = new \App\Models\Atoll();
    $atoll->name = trim((string) $validated['name']);
    if (Schema::hasColumn('atolls', 'slug')) {
        $baseSlug = trim((string) ($validated['slug'] ?? ''));
        if ($baseSlug === '') {
            $baseSlug = Str::slug($atoll->name);
        }
        $slug = $baseSlug;
        $counter = 2;
        while (\App\Models\Atoll::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $atoll->slug = $slug;
    }
    if (Schema::hasColumn('atolls', 'code')) {
        $atoll->code = trim((string) ($validated['code'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('atolls', 'description')) {
        $atoll->description = trim((string) ($validated['description'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('atolls', 'wikipedia_title')) {
        $atoll->wikipedia_title = trim((string) ($validated['wikipedia_title'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('atolls', 'source')) {
        $atoll->source = 'admin';
    }
    $atoll->save();

    if ($request->hasFile('photo') && Schema::hasColumn('atolls', 'photo_path')) {
        $photo = $request->file('photo');
        if ($photo && $photo->isValid()) {
            $extension = strtolower((string) $photo->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }
            $storagePath = 'atlas/atolls/' . (int) $atoll->id . '/cover.' . $extension;
            Storage::disk('public')->putFileAs('atlas/atolls/' . (int) $atoll->id, $photo, 'cover.' . $extension);
            $atoll->photo_path = $storagePath;
            $atoll->save();
        }
    }

    portalAdminAuditLog('atlas_atoll_created', [
        'target_role' => 'ADMIN',
        'atoll_id' => (int) $atoll->id,
        'atoll_name' => (string) $atoll->name,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Atoll created successfully.');
});

Route::get('/portal/admin/atlas/atolls/{atoll}/edit', function (int $atoll) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit atolls.']);
    }

    $record = \App\Models\Atoll::query()->findOrFail($atoll);

    return view('admin-atlas-form', [
        'mode' => 'edit',
        'entity' => 'atoll',
        'record' => $record,
        'atolls' => collect(),
    ]);
});

Route::post('/portal/admin/atlas/atolls/{atoll}', function (Request $request, int $atoll) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit atolls.']);
    }

    $record = \App\Models\Atoll::query()->findOrFail($atoll);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'slug' => ['nullable', 'string', 'max:140'],
        'code' => ['nullable', 'string', 'max:20'],
        'description' => ['nullable', 'string', 'max:2000'],
        'wikipedia_title' => ['nullable', 'string', 'max:220'],
        'photo' => ['nullable', 'image', 'max:6144'],
        'remove_photo' => ['nullable', 'boolean'],
    ]);

    $record->name = trim((string) $validated['name']);
    if (Schema::hasColumn('atolls', 'slug')) {
        $baseSlug = trim((string) ($validated['slug'] ?? ''));
        if ($baseSlug === '') {
            $baseSlug = Str::slug($record->name);
        }
        $slug = $baseSlug;
        $counter = 2;
        while (\App\Models\Atoll::query()->where('slug', $slug)->where('id', '!=', (int) $record->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $record->slug = $slug;
    }
    if (Schema::hasColumn('atolls', 'code')) {
        $record->code = trim((string) ($validated['code'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('atolls', 'description')) {
        $record->description = trim((string) ($validated['description'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('atolls', 'wikipedia_title')) {
        $record->wikipedia_title = trim((string) ($validated['wikipedia_title'] ?? '')) ?: null;
    }

    if ((bool) ($validated['remove_photo'] ?? false) && Schema::hasColumn('atolls', 'photo_path')) {
        $existingPath = trim((string) ($record->photo_path ?? ''));
        if ($existingPath !== '') {
            Storage::disk('public')->delete($existingPath);
        }
        $record->photo_path = null;
    }

    if ($request->hasFile('photo') && Schema::hasColumn('atolls', 'photo_path')) {
        $photo = $request->file('photo');
        if ($photo && $photo->isValid()) {
            $existingPath = trim((string) ($record->photo_path ?? ''));
            if ($existingPath !== '') {
                Storage::disk('public')->delete($existingPath);
            }
            $extension = strtolower((string) $photo->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }
            $storagePath = 'atlas/atolls/' . (int) $record->id . '/cover.' . $extension;
            Storage::disk('public')->putFileAs('atlas/atolls/' . (int) $record->id, $photo, 'cover.' . $extension);
            $record->photo_path = $storagePath;
        }
    }

    $record->save();

    portalAdminAuditLog('atlas_atoll_updated', [
        'target_role' => 'ADMIN',
        'atoll_id' => (int) $record->id,
        'atoll_name' => (string) $record->name,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Atoll updated successfully.');
});

Route::post('/portal/admin/atlas/atolls/{atoll}/delete', function (int $atoll) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can delete atolls.']);
    }

    $record = \App\Models\Atoll::query()->findOrFail($atoll);

    if (\App\Models\Island::query()->where('atoll_id', (int) $record->id)->exists()) {
        return redirect('/portal/admin/atlas')->withErrors(['auth' => 'Cannot delete atoll while islands are assigned. Reassign or delete islands first.']);
    }

    if (Schema::hasColumn('atolls', 'photo_path')) {
        $existingPath = trim((string) ($record->photo_path ?? ''));
        if ($existingPath !== '') {
            Storage::disk('public')->delete($existingPath);
        }
    }

    $recordName = (string) $record->name;
    $recordId = (int) $record->id;
    $record->delete();

    portalAdminAuditLog('atlas_atoll_deleted', [
        'target_role' => 'ADMIN',
        'atoll_id' => $recordId,
        'atoll_name' => $recordName,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Atoll deleted successfully.');
});

Route::get('/portal/admin/atlas/islands/create', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create islands.']);
    }

    $atolls = \App\Models\Atoll::query()->orderedByCode()->get();

    return view('admin-atlas-form', [
        'mode' => 'create',
        'entity' => 'island',
        'record' => null,
        'atolls' => $atolls,
    ]);
});

Route::post('/portal/admin/atlas/islands', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can create islands.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'slug' => ['nullable', 'string', 'max:180'],
        'local_name' => ['nullable', 'string', 'max:180'],
        'atoll_id' => ['required', 'integer'],
        'description' => ['nullable', 'string', 'max:3000'],
        'island_type' => ['required', Rule::in(['inhabited', 'uninhabited', 'resort'])],
        'is_inhabited' => ['nullable', 'boolean'],
        'wikipedia_title' => ['nullable', 'string', 'max:220'],
        'photo' => ['nullable', 'image', 'max:6144'],
    ]);

    $atollExists = \App\Models\Atoll::query()->where('id', (int) $validated['atoll_id'])->exists();
    if (!$atollExists) {
        return back()->withErrors(['atoll_id' => 'Selected atoll does not exist.'])->withInput();
    }

    $island = new \App\Models\Island();
    $island->name = trim((string) $validated['name']);
    $island->atoll_id = (int) $validated['atoll_id'];
    if (Schema::hasColumn('islands', 'slug')) {
        $baseSlug = trim((string) ($validated['slug'] ?? ''));
        if ($baseSlug === '') {
            $baseSlug = Str::slug($island->name);
        }
        $slug = $baseSlug;
        $counter = 2;
        while (\App\Models\Island::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $island->slug = $slug;
    }
    if (Schema::hasColumn('islands', 'local_name')) {
        $island->local_name = trim((string) ($validated['local_name'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('islands', 'description')) {
        $island->description = trim((string) ($validated['description'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('islands', 'island_type')) {
        $island->island_type = trim((string) $validated['island_type']);
    }
    if (Schema::hasColumn('islands', 'is_inhabited')) {
        if ($island->island_type === 'inhabited') {
            $island->is_inhabited = true;
        } elseif ($island->island_type === 'resort') {
            $island->is_inhabited = false;
        } else {
            $island->is_inhabited = (bool) ($validated['is_inhabited'] ?? false);
        }
    }
    if (Schema::hasColumn('islands', 'wikipedia_title')) {
        $island->wikipedia_title = trim((string) ($validated['wikipedia_title'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('islands', 'source')) {
        $island->source = 'admin';
    }
    $island->save();

    if ($request->hasFile('photo') && Schema::hasColumn('islands', 'photo_path')) {
        $photo = $request->file('photo');
        if ($photo && $photo->isValid()) {
            $extension = strtolower((string) $photo->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }
            $storagePath = 'atlas/islands/' . (int) $island->id . '/cover.' . $extension;
            Storage::disk('public')->putFileAs('atlas/islands/' . (int) $island->id, $photo, 'cover.' . $extension);
            $island->photo_path = $storagePath;
            $island->save();
        }
    }

    portalAdminAuditLog('atlas_island_created', [
        'target_role' => 'ADMIN',
        'island_id' => (int) $island->id,
        'island_name' => (string) $island->name,
        'atoll_id' => (int) $island->atoll_id,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Island created successfully.');
});

Route::get('/portal/admin/atlas/islands/{island}/edit', function (int $island) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit islands.']);
    }

    $record = \App\Models\Island::query()->findOrFail($island);
    $atolls = \App\Models\Atoll::query()->orderedByCode()->get();

    return view('admin-atlas-form', [
        'mode' => 'edit',
        'entity' => 'island',
        'record' => $record,
        'atolls' => $atolls,
    ]);
});

Route::post('/portal/admin/atlas/islands/{island}', function (Request $request, int $island) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can edit islands.']);
    }

    $record = \App\Models\Island::query()->findOrFail($island);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'slug' => ['nullable', 'string', 'max:180'],
        'local_name' => ['nullable', 'string', 'max:180'],
        'atoll_id' => ['required', 'integer'],
        'description' => ['nullable', 'string', 'max:3000'],
        'island_type' => ['required', Rule::in(['inhabited', 'uninhabited', 'resort'])],
        'is_inhabited' => ['nullable', 'boolean'],
        'wikipedia_title' => ['nullable', 'string', 'max:220'],
        'photo' => ['nullable', 'image', 'max:6144'],
        'remove_photo' => ['nullable', 'boolean'],
    ]);

    $atollExists = \App\Models\Atoll::query()->where('id', (int) $validated['atoll_id'])->exists();
    if (!$atollExists) {
        return back()->withErrors(['atoll_id' => 'Selected atoll does not exist.'])->withInput();
    }

    $record->name = trim((string) $validated['name']);
    $record->atoll_id = (int) $validated['atoll_id'];
    if (Schema::hasColumn('islands', 'slug')) {
        $baseSlug = trim((string) ($validated['slug'] ?? ''));
        if ($baseSlug === '') {
            $baseSlug = Str::slug($record->name);
        }
        $slug = $baseSlug;
        $counter = 2;
        while (\App\Models\Island::query()->where('slug', $slug)->where('id', '!=', (int) $record->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $record->slug = $slug;
    }
    if (Schema::hasColumn('islands', 'local_name')) {
        $record->local_name = trim((string) ($validated['local_name'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('islands', 'description')) {
        $record->description = trim((string) ($validated['description'] ?? '')) ?: null;
    }
    if (Schema::hasColumn('islands', 'island_type')) {
        $record->island_type = trim((string) $validated['island_type']);
    }
    if (Schema::hasColumn('islands', 'is_inhabited')) {
        if ($record->island_type === 'inhabited') {
            $record->is_inhabited = true;
        } elseif ($record->island_type === 'resort') {
            $record->is_inhabited = false;
        } else {
            $record->is_inhabited = (bool) ($validated['is_inhabited'] ?? false);
        }
    }
    if (Schema::hasColumn('islands', 'wikipedia_title')) {
        $record->wikipedia_title = trim((string) ($validated['wikipedia_title'] ?? '')) ?: null;
    }

    if ((bool) ($validated['remove_photo'] ?? false) && Schema::hasColumn('islands', 'photo_path')) {
        $existingPath = trim((string) ($record->photo_path ?? ''));
        if ($existingPath !== '') {
            Storage::disk('public')->delete($existingPath);
        }
        $record->photo_path = null;
    }

    if ($request->hasFile('photo') && Schema::hasColumn('islands', 'photo_path')) {
        $photo = $request->file('photo');
        if ($photo && $photo->isValid()) {
            $existingPath = trim((string) ($record->photo_path ?? ''));
            if ($existingPath !== '') {
                Storage::disk('public')->delete($existingPath);
            }
            $extension = strtolower((string) $photo->getClientOriginalExtension());
            if ($extension === '') {
                $extension = 'jpg';
            }
            $storagePath = 'atlas/islands/' . (int) $record->id . '/cover.' . $extension;
            Storage::disk('public')->putFileAs('atlas/islands/' . (int) $record->id, $photo, 'cover.' . $extension);
            $record->photo_path = $storagePath;
        }
    }

    $record->save();

    portalAdminAuditLog('atlas_island_updated', [
        'target_role' => 'ADMIN',
        'island_id' => (int) $record->id,
        'island_name' => (string) $record->name,
        'atoll_id' => (int) $record->atoll_id,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Island updated successfully.');
});

Route::post('/portal/admin/atlas/islands/{island}/delete', function (int $island) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }

    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_MEDIA can delete islands.']);
    }

    $record = \App\Models\Island::query()->findOrFail($island);

    if (Schema::hasColumn('islands', 'photo_path')) {
        $existingPath = trim((string) ($record->photo_path ?? ''));
        if ($existingPath !== '') {
            Storage::disk('public')->delete($existingPath);
        }
    }

    $recordName = (string) $record->name;
    $recordId = (int) $record->id;
    $record->delete();

    portalAdminAuditLog('atlas_island_deleted', [
        'target_role' => 'ADMIN',
        'island_id' => $recordId,
        'island_name' => $recordName,
    ]);

    return redirect('/portal/admin/atlas')->with('portal_notice', 'Island deleted successfully.');
});

Route::get('/portal/admin/newsletter', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage newsletters.']);
    }

    $newsletters = collect();
    if (Schema::hasTable('newsletters')) {
        $newsletters = \App\Models\Newsletter::query()->orderByDesc('updated_at')->limit(200)->get();
    }

    return view('admin-newsletter-index', ['newsletters' => $newsletters]);
});

Route::get('/portal/admin/newsletter/create', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage newsletters.']);
    }

    return view('admin-newsletter-form', ['mode' => 'create', 'newsletter' => null]);
});

Route::post('/portal/admin/newsletter', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage newsletters.']);
    }
    if (!Schema::hasTable('newsletters')) {
        return back()->withErrors(['auth' => 'Newsletters table is not ready. Run migrations first.'])->withInput();
    }

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:220'],
        'subject' => ['required', 'string', 'max:300'],
        'body' => ['required', 'string', 'min:20'],
        'audience' => ['nullable', Rule::in(['all', 'members', 'partners'])],
        'status' => ['nullable', Rule::in(['draft', 'scheduled', 'sent', 'archived'])],
        'scheduled_at' => ['nullable', 'date'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $newsletter = new \App\Models\Newsletter();
    $newsletter->title = trim((string) $validated['title']);
    $newsletter->subject = trim((string) $validated['subject']);
    $newsletter->body = (string) $validated['body'];
    $newsletter->audience = (string) ($validated['audience'] ?? 'all');
    $newsletter->status = (string) ($validated['status'] ?? 'draft');
    $newsletter->scheduled_at = !empty($validated['scheduled_at']) ? Carbon::parse((string) $validated['scheduled_at']) : null;
    $newsletter->created_by_user_id = $actorUserId;
    $newsletter->updated_by_user_id = $actorUserId;
    $newsletter->save();

    portalAdminAuditLog('newsletter_created', [
        'target_role' => currentPortalAdminRole(),
        'newsletter_id' => (int) $newsletter->id,
    ]);

    return redirect('/portal/admin/newsletter')->with('portal_notice', 'Newsletter saved.');
});

Route::get('/portal/admin/newsletter/{id}/edit', function (int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage newsletters.']);
    }

    return view('admin-newsletter-form', [
        'mode' => 'edit',
        'newsletter' => \App\Models\Newsletter::query()->findOrFail($id),
    ]);
});

Route::post('/portal/admin/newsletter/{id}', function (Request $request, int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage newsletters.']);
    }
    if (!Schema::hasTable('newsletters')) {
        return back()->withErrors(['auth' => 'Newsletters table is not ready. Run migrations first.'])->withInput();
    }

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:220'],
        'subject' => ['required', 'string', 'max:300'],
        'body' => ['required', 'string', 'min:20'],
        'audience' => ['nullable', Rule::in(['all', 'members', 'partners'])],
        'status' => ['nullable', Rule::in(['draft', 'scheduled', 'sent', 'archived'])],
        'scheduled_at' => ['nullable', 'date'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $newsletter = \App\Models\Newsletter::query()->findOrFail($id);
    $newsletter->title = trim((string) $validated['title']);
    $newsletter->subject = trim((string) $validated['subject']);
    $newsletter->body = (string) $validated['body'];
    $newsletter->audience = (string) ($validated['audience'] ?? 'all');
    $newsletter->status = (string) ($validated['status'] ?? 'draft');
    $newsletter->scheduled_at = !empty($validated['scheduled_at']) ? Carbon::parse((string) $validated['scheduled_at']) : null;
    $newsletter->updated_by_user_id = $actorUserId;
    $newsletter->save();

    portalAdminAuditLog('newsletter_updated', [
        'target_role' => currentPortalAdminRole(),
        'newsletter_id' => (int) $newsletter->id,
    ]);

    return redirect('/portal/admin/newsletter')->with('portal_notice', 'Newsletter updated.');
});

Route::post('/portal/admin/newsletter/{id}/delete', function (int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canEditorialReview()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER can delete newsletters.']);
    }

    if (Schema::hasTable('newsletters')) {
        \App\Models\Newsletter::query()->findOrFail($id)->delete();
    }

    portalAdminAuditLog('newsletter_deleted', [
        'target_role' => currentPortalAdminRole(),
        'newsletter_id' => $id,
    ]);

    return redirect('/portal/admin/newsletter')->with('portal_notice', 'Newsletter deleted.');
});

Route::get('/portal/admin/announcement', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }

    $announcements = collect();
    if (Schema::hasTable('announcements')) {
        $announcements = \App\Models\Announcement::query()->orderByDesc('updated_at')->limit(200)->get();
    }

    return view('admin-announcement-index', ['announcements' => $announcements]);
});

Route::get('/portal/admin/announcement/create', function () {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }

    return view('admin-announcement-form', ['mode' => 'create', 'announcement' => null]);
});

Route::post('/portal/admin/announcement', function (Request $request) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }
    if (!Schema::hasTable('announcements')) {
        return back()->withErrors(['auth' => 'Announcements table is not ready. Run migrations first.'])->withInput();
    }

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:220'],
        'type' => ['nullable', Rule::in(['internal', 'public', 'partner'])],
        'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        'content' => ['required', 'string', 'min:10'],
        'published_at' => ['nullable', 'date'],
        'expires_at' => ['nullable', 'date'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $announcement = new \App\Models\Announcement();
    $announcement->title = trim((string) $validated['title']);
    $announcement->type = (string) ($validated['type'] ?? 'internal');
    $announcement->status = (string) ($validated['status'] ?? 'draft');
    $announcement->content = (string) $validated['content'];
    $announcement->published_at = !empty($validated['published_at']) ? Carbon::parse((string) $validated['published_at']) : null;
    $announcement->expires_at = !empty($validated['expires_at']) ? Carbon::parse((string) $validated['expires_at']) : null;
    $announcement->created_by_user_id = $actorUserId;
    $announcement->updated_by_user_id = $actorUserId;
    $announcement->save();

    portalAdminAuditLog('announcement_created', [
        'target_role' => currentPortalAdminRole(),
        'announcement_id' => (int) $announcement->id,
    ]);

    return redirect('/portal/admin/announcement')->with('portal_notice', 'Announcement saved.');
});

Route::get('/portal/admin/announcement/{id}/edit', function (int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }

    return view('admin-announcement-form', [
        'mode' => 'edit',
        'announcement' => \App\Models\Announcement::query()->findOrFail($id),
    ]);
});

Route::post('/portal/admin/announcement/{id}', function (Request $request, int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }
    if (!Schema::hasTable('announcements')) {
        return back()->withErrors(['auth' => 'Announcements table is not ready. Run migrations first.'])->withInput();
    }

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:220'],
        'type' => ['nullable', Rule::in(['internal', 'public', 'partner'])],
        'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        'content' => ['required', 'string', 'min:10'],
        'published_at' => ['nullable', 'date'],
        'expires_at' => ['nullable', 'date'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $announcement = \App\Models\Announcement::query()->findOrFail($id);
    $announcement->title = trim((string) $validated['title']);
    $announcement->type = (string) ($validated['type'] ?? 'internal');
    $announcement->status = (string) ($validated['status'] ?? 'draft');
    $announcement->content = (string) $validated['content'];
    $announcement->published_at = !empty($validated['published_at']) ? Carbon::parse((string) $validated['published_at']) : null;
    $announcement->expires_at = !empty($validated['expires_at']) ? Carbon::parse((string) $validated['expires_at']) : null;
    $announcement->updated_by_user_id = $actorUserId;
    $announcement->save();

    portalAdminAuditLog('announcement_updated', [
        'target_role' => currentPortalAdminRole(),
        'announcement_id' => (int) $announcement->id,
    ]);

    return redirect('/portal/admin/announcement')->with('portal_notice', 'Announcement updated.');
});

Route::post('/portal/admin/announcement/{id}/delete', function (int $id) {
    if (!session()->get('portal_admin_authenticated', false)) {
        return redirect('/portal/admin/login');
    }
    if (!canManageContent()) {
        return redirect('/admin')->withErrors(['auth' => 'Only ADMIN_SUPER and ADMIN_MEDIA can manage announcements.']);
    }

    if (Schema::hasTable('announcements')) {
        \App\Models\Announcement::query()->findOrFail($id)->delete();
    }

    portalAdminAuditLog('announcement_deleted', [
        'target_role' => currentPortalAdminRole(),
        'announcement_id' => $id,
    ]);

    return redirect('/portal/admin/announcement')->with('portal_notice', 'Announcement deleted.');
});

    Route::post('/portal/admin/users/create', function (\Illuminate\Http\Request $request) {
        $canManageUsers = Gate::allows('manage-portal-users');
        $canCreateVendorUsers = canCreateVendorUsers();
        if (!$canManageUsers && !$canCreateVendorUsers) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Insufficient privileges to create portal users.'], 403);
            }

            return back()->withErrors(['auth' => 'Insufficient privileges to create portal users.']);
        }

        $request->merge([
            'portal_role' => normalizePortalRoleValue((string) $request->input('portal_role', '')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'portal_role' => 'required|in:ADMIN,ADMIN_SUPER,ADMIN_CARE,ADMIN_FINANCE,ADMIN_MEDIA,VENDOR',
            'portal_enabled' => 'required|boolean',
            'portal_vendor_id' => 'nullable|string|max:255',
        ]);

        $normalizedRole = normalizePortalRoleValue((string) $validated['portal_role']);

        if (!$canManageUsers && $normalizedRole !== 'VENDOR') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin role can only create VENDOR users.'], 403);
            }

            return back()->withErrors(['auth' => 'Admin role can only create VENDOR users.']);
        }

        $vendorId = trim((string) ($validated['portal_vendor_id'] ?? ''));
        if ($normalizedRole === 'VENDOR' && $vendorId === '') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vendor ID is required for VENDOR users.'], 422);
            }

            return back()->withErrors(['portal_vendor_id' => 'Vendor ID is required for VENDOR users.']);
        }

        $username = generatePortalUsernameFromEmail((string) $validated['email']);

        $user = new \App\Models\User();
        $user->name = $validated['name'];
        $user->username = $username;
        $user->email = $validated['email'];
        $user->portal_role = $normalizedRole;
        $user->portal_enabled = $validated['portal_enabled'];
        $user->portal_vendor_id = $normalizedRole === 'VENDOR' ? $vendorId : null;
        $user->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24));
        $user->save();

        $resetEmailSent = false;
        $resetEmailError = null;
        if ((bool) $user->portal_enabled) {
            $roleForReset = normalizePortalRoleValue((string) $user->portal_role);
            $portalForReset = $roleForReset === 'VENDOR' ? 'vendor' : 'admin';
            try {
                $token = Password::broker('backend_users')->createToken($user);
                $resetUrl = url('/portal/' . $portalForReset . '/reset-password/' . $token . '?email=' . rawurlencode((string) $user->email));
                $user->sendPasswordResetNotification($token);
                $resetEmailSent = true;
            } catch (\Throwable $e) {
                try {
                    if (isset($resetUrl) && $resetUrl !== '') {
                        sendPortalPasswordResetFallbackMail((string) $user->email, $portalForReset, $resetUrl, (string) ($user->name ?? ''));
                        $resetEmailSent = true;
                    }
                } catch (\Throwable $mailFallbackError) {
                    $resetEmailError = $mailFallbackError->getMessage();
                    Log::error('Failed to send portal user reset email after creation.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                        'fallback_error' => $mailFallbackError->getMessage(),
                    ]);
                }
            }
        }

        portalAdminAuditLog('user.created', [
            'target_user_id' => (int) $user->id,
            'target_identifier' => (string) ($user->username ?: $user->email),
            'target_role' => (string) $user->portal_role,
            'portal_enabled' => (bool) $user->portal_enabled,
            'reset_email_sent' => $resetEmailSent,
        ]);

        if ($request->expectsJson()) {
            $message = $resetEmailSent
                ? 'User created and password reset email sent.'
                : ($user->portal_enabled
                    ? 'User created, but password reset email could not be sent.'
                    : 'User created in suspended state. Reset email not sent.');

            return response()->json([
                'message' => $message,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'portal_role' => $user->portal_role,
                    'portal_enabled' => (bool) $user->portal_enabled,
                    'portal_vendor_id' => $user->portal_vendor_id,
                ],
                'password_reset_email_sent' => $resetEmailSent,
                'password_reset_email_error' => $resetEmailError,
            ], 201);
        }

        if ($resetEmailSent) {
            return back()->with('portal_notice', 'User created and reset email sent: ' . $user->username);
        }

        if ((bool) $user->portal_enabled) {
            return back()->withErrors(['email' => 'User created, but password reset email could not be sent. Please check mail configuration and logs.']);
        }

        return back()->with('portal_notice', 'User created in suspended state (no reset email sent): ' . $user->username);
    });

Route::delete('/portal/admin/users/{user}/delete', function (User $user) {
    $canManageUsers = Gate::allows('manage-portal-users');
    $canRequestVendorDelete = canRequestVendorDeleteApproval();
    $targetRole = normalizePortalRoleValue((string) $user->portal_role);
    if (!$canManageUsers && !($canRequestVendorDelete && $targetRole === 'VENDOR')) {
        abort(403);
    }
    // Prevent deleting your own account
    if ((int) session('portal_admin_user_id') === (int) $user->id) {
        portalAdminAuditLog('user.delete_blocked_self', [
            'target_user_id' => (int) $user->id,
            'target_identifier' => (string) ($user->username ?: $user->email),
            'target_role' => (string) $user->portal_role,
        ]);
        return back()->withErrors(['delete' => 'You cannot delete your own account.']);
    }

    $targetIdentifier = (string) ($user->username ?: $user->email);
    $targetRoleRaw = (string) $user->portal_role;
    $targetUserId = (int) $user->id;

    // Vendor deletion by non-super roles requires explicit ADMIN_SUPER approval.
    if ($targetRole === 'VENDOR' && !canApproveVendorDeleteRequest()) {
        if (portalActionRequestsEnabled()) {
            $existingPending = DB::table('portal_admin_action_requests')
                ->where('status', 'pending')
                ->where('action_type', 'vendor_delete')
                ->where('target_user_id', $targetUserId)
                ->exists();

            if ($existingPending) {
                return back()->withErrors(['delete' => 'A vendor delete approval request is already pending for this account.']);
            }

            $requestId = createPortalActionRequest(
                'vendor_delete',
                $targetUserId,
                null,
                $targetIdentifier,
                'Vendor deletion requires ADMIN_SUPER approval.',
            );

            portalAdminAuditLog('vendor_delete.requested', [
                'target_user_id' => $targetUserId,
                'target_identifier' => $targetIdentifier,
                'target_role' => $targetRoleRaw,
                'action_request_id' => $requestId,
            ]);

            return back()->with('portal_notice', 'Vendor delete request submitted for ADMIN_SUPER approval.');
        }

        return back()->withErrors(['delete' => 'Vendor delete approval workflow is not available until migrations are applied.']);
    }

    $user->delete();

    portalAdminAuditLog('user.deleted', [
        // The user row is deleted, so keep identifier/role for traceability and clear FK target_user_id.
        'target_user_id' => null,
        'target_identifier' => $targetIdentifier,
        'target_role' => $targetRoleRaw,
    ]);

    return back()->with('portal_notice', 'User deleted.');
});

Route::post('/portal/admin/users/bulk-delete', function (Request $request) {
    $canManageUsers = Gate::allows('manage-portal-users');
    $canRequestVendorDelete = canRequestVendorDeleteApproval();
    if (!$canManageUsers && !$canRequestVendorDelete) {
        abort(403);
    }

    $validated = $request->validate([
        'user_ids' => ['required', 'array', 'min:1'],
        'user_ids.*' => ['required', 'integer', 'exists:users,id'],
    ]);

    $ids = collect($validated['user_ids'])
        ->map(function ($id) {
            return (int) $id;
        })
        ->unique()
        ->values();

    $currentUserId = (int) session('portal_admin_user_id');
    if ($ids->contains($currentUserId)) {
        portalAdminAuditLog('user.bulk_delete_blocked_self', [
            'target_user_id' => $currentUserId,
            'target_identifier' => (string) session('portal_admin_user', 'current-user'),
            'ids_count' => $ids->count(),
        ]);
        return back()->withErrors(['delete' => 'You cannot bulk delete your own account.']);
    }

    $targetUsers = User::query()
        ->whereIn('id', $ids->all())
        ->get(['id', 'username', 'email', 'portal_role']);

    if (!$canManageUsers) {
        $nonVendorTargets = $targetUsers
            ->filter(function (User $managedUser) {
                return normalizePortalRoleValue((string) $managedUser->portal_role) !== 'VENDOR';
            })
            ->count();

        if ($nonVendorTargets > 0) {
            return back()->withErrors(['delete' => 'Admin role can only bulk delete VENDOR users.']);
        }
    }

    // Non-super roles can only submit vendor delete approval requests.
    if (!$canApproveVendorDeleteRequest()) {
        if (!portalActionRequestsEnabled()) {
            return back()->withErrors(['delete' => 'Vendor delete approval workflow is not available until migrations are applied.']);
        }

        $requestedCount = 0;
        foreach ($targetUsers as $managedUser) {
            $normalizedTargetRole = normalizePortalRoleValue((string) $managedUser->portal_role);
            if ($normalizedTargetRole !== 'VENDOR') {
                continue;
            }

            $existingPending = DB::table('portal_admin_action_requests')
                ->where('status', 'pending')
                ->where('action_type', 'vendor_delete')
                ->where('target_user_id', (int) $managedUser->id)
                ->exists();

            if ($existingPending) {
                continue;
            }

            $requestId = createPortalActionRequest(
                'vendor_delete',
                (int) $managedUser->id,
                null,
                (string) ($managedUser->username ?: $managedUser->email),
                'Bulk vendor deletion requires ADMIN_SUPER approval.',
            );

            portalAdminAuditLog('vendor_delete.requested', [
                'target_user_id' => (int) $managedUser->id,
                'target_identifier' => (string) ($managedUser->username ?: $managedUser->email),
                'target_role' => (string) $managedUser->portal_role,
                'action_request_id' => $requestId,
                'bulk_request' => true,
            ]);

            $requestedCount++;
        }

        return back()->with('portal_notice', 'Submitted ' . $requestedCount . ' vendor delete request(s) for ADMIN_SUPER approval.');
    }

    $deletedCount = User::query()->whereIn('id', $ids->all())->delete();

    portalAdminAuditLog('user.bulk_deleted', [
        'target_user_id' => null,
        'target_identifier' => 'bulk',
        'target_role' => null,
        'ids_count' => $ids->count(),
        'deleted_count' => $deletedCount,
        'targets' => $targetUsers->map(function (User $managedUser) {
            return [
                'id' => (int) $managedUser->id,
                'identifier' => (string) ($managedUser->username ?: $managedUser->email),
                'role' => (string) $managedUser->portal_role,
            ];
        })->values()->all(),
    ]);

    return back()->with('portal_notice', 'Deleted ' . $deletedCount . ' user(s).');
});

Route::get('/portal/{portal}/login', function (Request $request, string $portal) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    if ($portal === 'customer') {
        rememberCustomerPostAuthRedirect($request);
    }

    $config = portalConfig($portal);
    if (session()->get($config['session_key'], false)) {
        return $portal === 'customer'
            ? redirect('/')
            : redirect(portalRoutePath($portal));
    }

    $socialProviders = [];
    if ($portal === 'customer') {
        $socialProviders = collect(supportedCustomerSocialProviders())
            ->mapWithKeys(static fn (string $provider) => [
                $provider => [
                    'configured' => isCustomerSocialProviderConfigured($provider),
                    'redirect' => '/portal/customer/oauth/' . $provider . '/redirect',
                ],
            ])
            ->all();
    } elseif ($portal === 'vendor') {
        $socialProviders = collect(supportedVendorSocialProviders())
            ->filter(static fn (string $provider) => in_array($provider, ['google', 'facebook'], true))
            ->mapWithKeys(static fn (string $provider) => [
                $provider => [
                    'configured' => isVendorSocialProviderConfigured($provider),
                    'redirect' => '/portal/vendor/oauth/' . $provider . '/redirect',
                ],
            ])
            ->all();
    }

    return view('portal-login', [
        'portal' => $portal,
        'portalName' => $config['name'],
        'socialProviders' => $socialProviders,
    ]);
});

Route::get('/portal/vendor/register', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $mode = strtolower(trim((string) $request->query('mode', 'email')));
    if (!in_array($mode, ['email', 'otp', 'minimal'], true)) {
        $mode = 'email';
    }

    if ($mode === 'otp' && trim((string) session('otp_email', '')) === '') {
        return redirect('/portal/vendor/register?mode=email');
    }

    $minimalPayload = session('vendor_minimal_signup_payload');
    if ($mode === 'minimal' && !is_array($minimalPayload)) {
        return redirect('/portal/vendor/register?mode=email');
    }

    return view('portal-vendor-register', [
        'mode' => $mode,
        'minimalPayload' => is_array($minimalPayload) ? $minimalPayload : [],
    ]);
});

Route::get('/portal/vendor/oauth/health', function () {
    return response()->json(vendorSocialHealthSnapshot());
});

Route::post('/portal/vendor/email-otp/send', function (Request $request) {
    $validated = $request->validate([
        'identifier' => ['nullable', 'string', 'max:160'],
        'email' => ['nullable', 'string', 'max:160'],
    ]);

    $rawIdentifier = trim((string) ($validated['identifier'] ?? $validated['email'] ?? ''));
    $resolvedIdentifier = vendorResolveOtpIdentifier($rawIdentifier);
    $channel = (string) ($resolvedIdentifier['channel'] ?? 'invalid');
    $normalizedIdentifier = (string) ($resolvedIdentifier['normalized'] ?? '');

    if ($channel === 'invalid' || $normalizedIdentifier === '') {
        return back()->withErrors([
            'registration' => 'Enter a valid email address or phone number to continue.',
        ])->withInput();
    }

    $existingUser = null;
    if ($channel === 'email') {
        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
            ->first();
    } elseif (Schema::hasColumn('users', 'phone')) {
        $phoneWithoutPlus = ltrim($normalizedIdentifier, '+');
        $existingUser = User::query()
            ->where('phone', $normalizedIdentifier)
            ->orWhere('phone', $phoneWithoutPlus)
            ->first();
    }

    if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'registration' => 'This identifier is already linked to a non-vendor account. Please use the correct portal login.',
        ])->withInput();
    }

    $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $cacheKey = vendorOtpCacheKeyForIdentifier($channel, $normalizedIdentifier);

    cache()->put($cacheKey, [
        'hash' => Hash::make($otpCode),
        'attempts' => 0,
        'channel' => $channel,
        'destination' => $normalizedIdentifier,
        'created_at' => now()->toIso8601String(),
    ], now()->addMinutes(10));

    try {
        vendorDeliverOtpCode($channel, $normalizedIdentifier, $otpCode);
    } catch (\Throwable $e) {
        Log::warning('Failed to send vendor OTP.', [
            'channel' => $channel,
            'destination' => $normalizedIdentifier,
            'error' => $e->getMessage(),
        ]);

        $deliveryGuidance = 'Check Twilio configuration and try again.';
        $errorMessage = strtolower($e->getMessage());
        if (str_contains($errorMessage, 'whatsapp')) {
            $deliveryGuidance = 'WhatsApp delivery failed. Confirm sandbox join from your phone, TWILIO_WHATSAPP_FROM, and template ContentSid settings.';
        } elseif (str_contains($errorMessage, 'sms')) {
            $deliveryGuidance = 'SMS delivery failed. Confirm TWILIO_FROM_NUMBER is active for your account and destination region.';
        }

        return back()->withErrors([
            'registration' => 'Unable to send verification code right now. Please try again in a moment.',
        ])->with('otp_delivery_guidance', $deliveryGuidance)->withInput();
    }

    $response = redirect('/portal/vendor/register?mode=otp')
        ->with('status', 'A 6-digit verification code has been sent to your ' . ($channel === 'phone' ? 'phone number.' : 'email.'))
        ->with('otp_identifier', $normalizedIdentifier)
        ->with('otp_channel', $channel)
        ->with('otp_sent', true);

    if ($channel === 'email') {
        $response->with('otp_email', $normalizedIdentifier);
    }

    if (app()->environment('testing')) {
        $response->with('otp_test_code', $otpCode);
    }

    return $response;
});

Route::post('/portal/vendor/email-otp/verify', function (Request $request) {
    $validated = $request->validate([
        'identifier' => ['nullable', 'string', 'max:160'],
        'email' => ['nullable', 'string', 'max:160'],
        'otp' => ['required', 'digits:6'],
    ]);

    $rawIdentifier = trim((string) ($validated['identifier'] ?? $validated['email'] ?? ''));
    $resolvedIdentifier = vendorResolveOtpIdentifier($rawIdentifier);
    $channel = (string) ($resolvedIdentifier['channel'] ?? 'invalid');
    $normalizedIdentifier = (string) ($resolvedIdentifier['normalized'] ?? '');
    if ($channel === 'invalid' || $normalizedIdentifier === '') {
        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Enter the same valid email or phone number used for OTP.',
        ])->withInput();
    }

    $otp = trim((string) $validated['otp']);
    $cacheKey = vendorOtpCacheKeyForIdentifier($channel, $normalizedIdentifier);
    $cachedOtp = cache()->get($cacheKey);

    if (!is_array($cachedOtp) || empty($cachedOtp['hash'])) {
        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Verification code expired. Request a new 6-digit code and try again.',
        ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
    }

    $attempts = (int) ($cachedOtp['attempts'] ?? 0);
    if (!Hash::check($otp, (string) $cachedOtp['hash'])) {
        $attempts++;

        if ($attempts >= 5) {
            cache()->forget($cacheKey);
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'Too many invalid code attempts. Request a new code and try again.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        cache()->put($cacheKey, [
            'hash' => (string) $cachedOtp['hash'],
            'attempts' => $attempts,
            'created_at' => (string) ($cachedOtp['created_at'] ?? now()->toIso8601String()),
        ], now()->addMinutes(10));

        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Invalid verification code. Please check the 6-digit OTP and try again.',
        ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
    }

    cache()->forget($cacheKey);

    $portalUser = null;
    if ($channel === 'email') {
        $portalUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
            ->first();
    } elseif (Schema::hasColumn('users', 'phone')) {
        $phoneWithoutPlus = ltrim($normalizedIdentifier, '+');
        $portalUser = User::query()
            ->where('phone', $normalizedIdentifier)
            ->orWhere('phone', $phoneWithoutPlus)
            ->first();
    }

    if ($portalUser instanceof User) {
        if (normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'This email belongs to a non-vendor account. Please use the correct portal login.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        if (!(bool) $portalUser->portal_enabled) {
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'Your vendor account is currently disabled. Please contact support.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        $request->session()->regenerate();
        session([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $portalUser->name,
            'portal_vendor_user_id' => $portalUser->id,
            'portal_vendor_role' => $portalUser->portal_role,
        ]);

        Auth::login($portalUser);

        return redirect('/vendor')->with('portal_notice', 'Signed in successfully.');
    }

    session([
        'vendor_minimal_signup_payload' => [
            'email' => $channel === 'email'
                ? $normalizedIdentifier
                : ('phone_' . substr(md5($normalizedIdentifier), 0, 20) . '@relay.workation.local'),
            'provider' => $channel,
            'oauth_id' => null,
            'suggested_name' => '',
            'contact_phone' => $channel === 'phone' ? $normalizedIdentifier : '',
            'email_verified' => true,
        ],
    ]);

    return redirect('/portal/vendor/register?mode=minimal')->with('status', 'Email verified. Complete minimal registration to continue.');
});

Route::post('/portal/vendor/minimal-register', function (Request $request) {
    $payload = session('vendor_minimal_signup_payload');
    if (!is_array($payload)) {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'Start with email or social login to continue registration.',
        ]);
    }

    $validated = $request->validate([
        'given_name' => ['required', 'string', 'max:80'],
        'family_name' => ['required', 'string', 'max:80'],
        'contact_phone' => ['required', 'string', 'max:40'],
        'agree_terms' => ['accepted'],
    ]);

    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    $provider = strtolower(trim((string) ($payload['provider'] ?? 'email')));
    $oauthId = trim((string) ($payload['oauth_id'] ?? ''));

    if ($email === '') {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'Registration context expired. Please start again.',
        ]);
    }

    $portalUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($portalUser instanceof User && normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'This email is already linked to a non-vendor account.',
        ]);
    }

    if (!$portalUser instanceof User) {
        $portalUser = new User();
        $portalUser->email = $email;
        $portalUser->username = generatePortalUsernameFromEmail($email);
        $portalUser->password = Hash::make(Str::random(40));
    }

    $givenName = trim((string) $validated['given_name']);
    $familyName = trim((string) $validated['family_name']);
    $portalUser->name = trim($givenName . ' ' . $familyName);
    $portalUser->portal_role = 'VENDOR';
    $portalUser->portal_enabled = true;
    if (trim((string) $portalUser->portal_vendor_id) === '') {
        $prefix = $provider === 'email' ? 'EML' : strtoupper(substr($provider, 0, 3));
        $portalUser->portal_vendor_id = $prefix . '-' . strtoupper(substr(md5($email), 0, 8));
    }

    if (Schema::hasColumn('users', 'phone')) {
        $enteredPhone = vendorNormalizePhoneNumber((string) $validated['contact_phone']);
        $fallbackPhone = vendorNormalizePhoneNumber((string) ($payload['contact_phone'] ?? ''));
        $portalUser->phone = $enteredPhone !== '' ? $enteredPhone : $fallbackPhone;
    }

    if ($oauthId !== '') {
        $providerColumn = $provider . '_oauth_id';
        if (Schema::hasColumn('users', $providerColumn)) {
            $portalUser->{$providerColumn} = $oauthId;
        }
    }

    if (empty($portalUser->email_verified_at)) {
        $portalUser->email_verified_at = now();
    }

    $portalUser->save();

    session()->forget('vendor_minimal_signup_payload');

    $request->session()->regenerate();
    session([
        'portal_vendor_authenticated' => true,
        'portal_vendor_user' => $portalUser->name,
        'portal_vendor_user_id' => $portalUser->id,
        'portal_vendor_role' => $portalUser->portal_role,
    ]);

    Auth::login($portalUser);

    return redirect('/vendor')->with('portal_notice', 'Registration complete. Welcome to the vendor portal.');
});

Route::get('/portal/vendor/oauth/{provider}/redirect', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedVendorSocialProviders(), true)) {
        abort(404);
    }

    $request->session()->put(portalOAuthIntentSessionKey($provider), 'vendor');

    if (!isVendorSocialProviderConfigured($provider)) {
        return redirect('/portal/vendor/register')->withErrors([
            'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email signup for now.',
        ]);
    }

    $health = vendorSocialHealthSnapshot();
    $providerHealth = $health['providers'][$provider] ?? null;
    if (is_array($providerHealth)) {
        if (!$providerHealth['redirect_uses_https']) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is temporarily unavailable because redirect URL must use HTTPS. Please use email signup for now.',
            ])->with('oauth_retry_guidance', 'Set ' . strtoupper($provider) . '_REDIRECT_URI to an HTTPS URL and try again.');
        }
    }

    if ($provider === 'apple') {
        $state = Str::random(40);
        $request->session()->put('vendor_oauth_state_apple', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'response_mode' => 'query',
            'client_id' => (string) config('services.apple.client_id'),
            'redirect_uri' => vendorSocialRedirectUrl('apple'),
            'scope' => 'name email',
            'state' => $state,
        ]);

        return redirect()->away('https://appleid.apple.com/auth/authorize?' . $query);
    }

    if ($provider === 'facebook') {
        $facebookRedirect = Socialite::driver('facebook')
            ->redirectUrl(vendorSocialRedirectUrl('facebook'))
            ->setScopes(['public_profile'])
            ->stateless()
            ->redirect();

        $targetUrl = (string) $facebookRedirect->getTargetUrl();
        $targetUrl = preg_replace('/([?&]scope=)[^&]*/', '$1public_profile', $targetUrl) ?: $targetUrl;

        return redirect()->away($targetUrl);
    }

    return Socialite::driver($provider)->stateless()->redirect();
});

Route::get('/portal/vendor/oauth/{provider}/callback', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedVendorSocialProviders(), true)) {
        abort(404);
    }

    $intentKey = portalOAuthIntentSessionKey($provider);
    $oauthIntent = strtolower(trim((string) $request->session()->get($intentKey, '')));
    if ($oauthIntent === 'customer' && in_array($provider, supportedCustomerSocialProviders(), true)) {
        $request->session()->forget($intentKey);

        $queryString = (string) $request->getQueryString();
        $target = '/portal/customer/oauth/' . $provider . '/callback';
        if ($queryString !== '') {
            $target .= '?' . $queryString;
        }

        return redirect($target);
    }

    try {
        if ($provider === 'facebook' && trim((string) $request->query('error', '')) !== '') {
            $errorReason = trim((string) $request->query('error_reason', 'oauth_error'));
            $errorDescription = trim((string) $request->query('error_description', ''));
            $callbackHint = trim((string) $request->query('error_message', ''));
            $errorDetails = $errorDescription !== '' ? $errorDescription : $callbackHint;

            Log::warning('Facebook OAuth callback returned an explicit provider error.', [
                'reason' => $errorReason,
                'details' => $errorDetails,
            ]);

            return redirect('/portal/vendor/register')->withErrors([
                'registration' => 'Facebook sign-in was denied or not fully configured. Please retry once, then use Google or email while Facebook app setup is finalized.',
            ])->with('oauth_retry_guidance', 'Verify Facebook Valid OAuth Redirect URIs exactly match FACEBOOK_REDIRECT_URI, including scheme, host, and path.');
        }

        if (!isVendorSocialProviderConfigured($provider)) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email signup for now.',
            ]);
        }

        $providerColumn = $provider . '_oauth_id';
        if (!Schema::hasColumn('users', $providerColumn)) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => 'Social sign-in database columns are missing. Please run migrations and try again.',
            ]);
        }

        $oauthId = '';
        $email = '';
        $name = '';

        if ($provider === 'apple') {
            $expectedState = (string) $request->session()->pull('vendor_oauth_state_apple', '');
            $receivedState = (string) $request->query('state', '');
            if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
                throw new \RuntimeException('Invalid Apple sign-in state. Please try again.');
            }

            $code = trim((string) $request->query('code', ''));
            if ($code === '') {
                throw new \RuntimeException('Apple sign-in did not return an authorization code.');
            }

            $applePrivateKey = str_replace('\\n', "\n", (string) config('services.apple.private_key', ''));
            $appleClientSecret = JWT::encode([
                'iss' => (string) config('services.apple.team_id'),
                'iat' => time(),
                'exp' => time() + 300,
                'aud' => 'https://appleid.apple.com',
                'sub' => (string) config('services.apple.client_id'),
            ], $applePrivateKey, 'ES256', (string) config('services.apple.key_id'));

            $tokenResponse = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => vendorSocialRedirectUrl('apple'),
                'client_id' => (string) config('services.apple.client_id'),
                'client_secret' => $appleClientSecret,
            ]);

            if (!$tokenResponse->ok()) {
                throw new \RuntimeException('Apple token exchange failed.');
            }

            $idToken = trim((string) $tokenResponse->json('id_token', ''));
            if ($idToken === '') {
                throw new \RuntimeException('Apple sign-in did not return a valid identity token.');
            }

            $appleKeys = cache()->remember('vendor_oauth_apple_keys', 3600, function () {
                $response = Http::get('https://appleid.apple.com/auth/keys');
                if (!$response->ok()) {
                    throw new \RuntimeException('Unable to download Apple signing keys.');
                }

                return $response->json();
            });

            $decodedAppleToken = (array) JWT::decode($idToken, JWK::parseKeySet($appleKeys));
            $oauthId = trim((string) ($decodedAppleToken['sub'] ?? ''));
            $email = strtolower(trim((string) ($decodedAppleToken['email'] ?? '')));
            $name = trim((string) ($decodedAppleToken['name'] ?? ''));
        } else {
            try {
                $oauthUser = Socialite::driver($provider)
                    ->redirectUrl(vendorSocialRedirectUrl($provider))
                    ->stateless()
                    ->user();

                $oauthId = trim((string) $oauthUser->getId());
                $email = strtolower(trim((string) $oauthUser->getEmail()));
                $name = trim((string) ($oauthUser->getName() ?: ''));
            } catch (\Throwable $socialiteError) {
                if ($provider !== 'facebook') {
                    throw $socialiteError;
                }

                $authorizationCode = trim((string) $request->query('code', ''));
                if ($authorizationCode === '') {
                    throw $socialiteError;
                }

                $tokenResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/v19.0/oauth/access_token', [
                    'client_id' => (string) config('services.facebook.client_id'),
                    'client_secret' => (string) config('services.facebook.client_secret'),
                    'redirect_uri' => vendorSocialRedirectUrl('facebook'),
                    'code' => $authorizationCode,
                ]);

                $accessToken = trim((string) $tokenResponse->json('access_token', ''));
                if (!$tokenResponse->ok() || $accessToken === '') {
                    throw $socialiteError;
                }

                $profileResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/me', [
                    'fields' => 'id,name,email',
                    'access_token' => $accessToken,
                ]);

                if (!$profileResponse->ok()) {
                    throw $socialiteError;
                }

                $oauthId = trim((string) $profileResponse->json('id', ''));
                $email = strtolower(trim((string) $profileResponse->json('email', '')));
                $name = trim((string) $profileResponse->json('name', ''));

                Log::info('Vendor Facebook OAuth fallback exchange used after Socialite user retrieval failure.');
            }
        }

        if ($oauthId === '') {
            throw new \RuntimeException('Unable to resolve your social account identity.');
        }

        if ($email === '' && $provider === 'apple') {
            $email = 'apple_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($email === '' && $provider === 'facebook') {
            $email = 'facebook_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($email === '') {
            throw new \RuntimeException('Your social account did not provide an email address. Please use email signup.');
        }

        $portalUser = User::query()
            ->where($providerColumn, $oauthId)
            ->orWhereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($portalUser instanceof User) {
            if (normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('This email is already linked to a non-vendor account.');
            }
        }

        if ($name === '') {
            $name = trim((string) Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title());
        }
        if ($name === '') {
            $name = 'Vendor Partner';
        }

        if (!$portalUser instanceof User) {
            session([
                'vendor_minimal_signup_payload' => [
                    'email' => $email,
                    'provider' => $provider,
                    'oauth_id' => $oauthId,
                    'suggested_name' => $name,
                    'email_verified' => true,
                ],
            ]);

            return redirect('/portal/vendor/register?mode=minimal')->with('status', ucfirst($provider) . ' verified. Complete minimal registration to continue.');
        }

        $portalUser->name = $name;
        $portalUser->portal_role = 'VENDOR';
        $portalUser->portal_enabled = true;
        if (trim((string) $portalUser->portal_vendor_id) === '') {
            $portalUser->portal_vendor_id = strtoupper(substr($provider, 0, 3)) . '-' . strtoupper(substr(md5($oauthId), 0, 8));
        }
        $portalUser->{$providerColumn} = $oauthId;
        if (empty($portalUser->email_verified_at)) {
            $portalUser->email_verified_at = now();
        }
        $portalUser->save();

        $request->session()->regenerate();
        session([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $portalUser->name,
            'portal_vendor_user_id' => $portalUser->id,
            'portal_vendor_role' => $portalUser->portal_role,
        ]);

        Auth::login($portalUser);

        return redirect('/vendor')->with('portal_notice', 'Signed in successfully with ' . ucfirst($provider) . '.');
    } catch (\Throwable $e) {
        Log::warning('Vendor social login failed.', [
            'provider' => $provider,
            'error' => $e->getMessage(),
        ]);

        $registrationMessage = 'Unable to sign in with ' . ucfirst($provider) . '. Please use email signup or try again.';
        if ($provider === 'facebook') {
            $registrationMessage = 'Unable to sign in with Facebook. Please try again, and if it still fails use Google or email while we complete Facebook app verification.';
        }

        return redirect('/portal/vendor/register')->withErrors([
            'registration' => $registrationMessage,
        ])->with('oauth_retry_guidance', 'Tip: retry once, then use Google or email signup if the provider window reports URL/redirect issues.');
    }
});

Route::match(['GET', 'POST'], '/portal/vendor/oauth/facebook/data-deletion', function (Request $request) {
    $confirmationCode = (string) Str::uuid();
    $statusUrl = url('/portal/vendor/oauth/facebook/data-deletion/status/' . $confirmationCode);

    Log::info('Facebook data deletion callback received.', [
        'has_signed_request' => filled($request->input('signed_request')),
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'url' => $statusUrl,
        'confirmation_code' => $confirmationCode,
    ]);
});

Route::get('/portal/vendor/oauth/facebook/data-deletion/status/{confirmationCode}', function (string $confirmationCode) {
    return response()->json([
        'status' => 'success',
        'confirmation_code' => $confirmationCode,
        'message' => 'Facebook data deletion request has been acknowledged.',
    ]);
});

Route::get('/portal/vendor/oauth/facebook/data-deletion-instructions', function () {
    return response()->view('portal-facebook-data-deletion-instructions');
});

Route::post('/portal/vendor/register', function (Request $request) {
    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor self-registration is not available yet. Please contact support.',
        ])->withInput();
    }

    $validated = $request->validate([
        'contact_name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
        'phone' => ['required', 'string', 'max:40'],
        'vendor_type' => ['required', Rule::in(['accommodation', 'transport', 'restaurant', 'major_vendor', 'vehicle_rental', 'excursions', 'small_service', 'other'])],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $existingUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) === 'VENDOR') {
        return back()->withErrors([
            'email' => 'A vendor account with this email already exists. Please use vendor login or forgot password.',
        ])->withInput();
    }

    $existingPending = DB::table('vendor_registration_requests')
        ->whereRaw('LOWER(email) = ?', [$email])
        ->where('status', 'pending')
        ->exists();

    if ($existingPending) {
        return back()->withErrors([
            'email' => 'A registration request for this email is already under review.',
        ])->withInput();
    }

    $businessLicensePath = '';
    $verificationPath = null;
    $partnerName = trim((string) $validated['contact_name']);

    DB::table('vendor_registration_requests')->insert([
        'business_name' => $partnerName,
        'contact_name' => trim((string) $validated['contact_name']),
        'email' => $email,
        'phone' => trim((string) $validated['phone']),
        'vendor_type' => (string) $validated['vendor_type'],
        'business_registration_number' => '',
        'license_number' => '',
        'business_license_document_path' => $businessLicensePath,
        'verification_document_path' => $verificationPath,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('status', 'Registration submitted successfully. You can complete business and service verification after login by submitting your listings for review.');
});

Route::get('/portal/customer/register', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    rememberCustomerPostAuthRedirect($request);

    if (session()->get('portal_customer_authenticated', false)) {
        return redirect('/');
    }

    $socialProviders = collect(supportedCustomerSocialProviders())
        ->mapWithKeys(static fn (string $provider) => [
            $provider => [
                'configured' => isCustomerSocialProviderConfigured($provider),
                'redirect' => '/portal/customer/oauth/' . $provider . '/redirect',
            ],
        ])
        ->all();

    return view('portal-customer-register', [
        'socialProviders' => $socialProviders,
    ]);
});

Route::get('/portal/customer/oauth/{provider}/redirect', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedCustomerSocialProviders(), true)) {
        abort(404);
    }

    rememberCustomerPostAuthRedirect($request);

    $request->session()->put(portalOAuthIntentSessionKey($provider), 'customer');

    if (!isCustomerSocialProviderConfigured($provider)) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email registration for now.',
        ]);
    }

    if ($provider === 'facebook') {
        $facebookRedirect = Socialite::driver('facebook')
            ->redirectUrl(customerSocialRedirectUrl('facebook'))
            ->setScopes(['public_profile'])
            ->stateless()
            ->redirect();

        $targetUrl = (string) $facebookRedirect->getTargetUrl();
        $targetUrl = preg_replace('/([?&]scope=)[^&]*/', '$1public_profile', $targetUrl) ?: $targetUrl;

        return redirect()->away($targetUrl);
    }

    return Socialite::driver($provider)
        ->redirectUrl(customerSocialRedirectUrl($provider))
        ->stateless()
        ->redirect();
});

Route::get('/portal/customer/oauth/{provider}/callback', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedCustomerSocialProviders(), true)) {
        abort(404);
    }

    $intentKey = portalOAuthIntentSessionKey($provider);

    try {
        if ($provider === 'facebook' && trim((string) $request->query('error', '')) !== '') {
            return redirect('/portal/customer/register')->withErrors([
                'registration' => 'Facebook sign-in was denied. Please retry or use email registration.',
            ]);
        }

        if (!isCustomerSocialProviderConfigured($provider)) {
            return redirect('/portal/customer/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email registration for now.',
            ]);
        }

        $providerColumn = customerSocialProviderColumn($provider);
        $supportsProviderColumn = $providerColumn !== '' && customerSchemaHasColumn($providerColumn);

        $oauthId = '';
        $email = '';
        $name = '';

        try {
            $oauthUser = Socialite::driver($provider)
                ->redirectUrl(customerSocialRedirectUrl($provider))
                ->stateless()
                ->user();

            $oauthId = trim((string) $oauthUser->getId());
            $email = strtolower(trim((string) $oauthUser->getEmail()));
            $name = trim((string) ($oauthUser->getName() ?: ''));
        } catch (\Throwable $socialiteError) {
            if ($provider !== 'facebook') {
                throw $socialiteError;
            }

            $authorizationCode = trim((string) $request->query('code', ''));
            if ($authorizationCode === '') {
                throw $socialiteError;
            }

            $tokenResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'client_id' => (string) config('services.facebook.client_id'),
                'client_secret' => (string) config('services.facebook.client_secret'),
                'redirect_uri' => customerSocialRedirectUrl('facebook'),
                'code' => $authorizationCode,
            ]);

            $accessToken = trim((string) $tokenResponse->json('access_token', ''));
            if (!$tokenResponse->ok() || $accessToken === '') {
                throw $socialiteError;
            }

            $profileResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email',
                'access_token' => $accessToken,
            ]);

            if (!$profileResponse->ok()) {
                throw $socialiteError;
            }

            $oauthId = trim((string) $profileResponse->json('id', ''));
            $email = strtolower(trim((string) $profileResponse->json('email', '')));
            $name = trim((string) $profileResponse->json('name', ''));
        }

        if ($oauthId === '') {
            throw new \RuntimeException('Unable to resolve social account identity.');
        }

        if ($email === '') {
            $email = $provider . '_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($name === '') {
            $name = trim((string) Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title());
        }
        if ($name === '') {
            $name = 'Customer';
        }

        $customerUser = \App\Models\Customer::query()
            ->where(function ($query) use ($supportsProviderColumn, $providerColumn, $oauthId, $email) {
                if ($supportsProviderColumn) {
                    $query->where($providerColumn, $oauthId);
                }

                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();

        $createdCustomer = false;

        if (!$customerUser) {
            $now = now();
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ];

            if ($supportsProviderColumn) {
                $payload[$providerColumn] = $oauthId;
            }

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
            }

            if (customerSchemaHasColumn('email_verified_at')) {
                $payload['email_verified_at'] = $now;
            }
            if (customerSchemaHasColumn('emailVerifiedAt')) {
                $payload['emailVerifiedAt'] = $now;
            }
            if (customerSchemaHasColumn('emailVerified')) {
                $payload['emailVerified'] = true;
            }

            if (customerSchemaHasColumn('createdAt')) {
                $payload['createdAt'] = $now;
            }
            if (customerSchemaHasColumn('updatedAt')) {
                $payload['updatedAt'] = $now;
            }
            if (customerSchemaHasColumn('created_at')) {
                $payload['created_at'] = $now;
            }
            if (customerSchemaHasColumn('updated_at')) {
                $payload['updated_at'] = $now;
            }

            customerTableInsert($payload);
            $createdCustomer = true;

            $customerUser = \App\Models\Customer::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if (!$customerUser) {
            throw new \RuntimeException('Unable to initialize member account from social identity.');
        }

        $needsSave = false;
        if ($supportsProviderColumn && trim((string) ($customerUser->{$providerColumn} ?? '')) !== $oauthId) {
            $customerUser->{$providerColumn} = $oauthId;
            $needsSave = true;
        }
        if (trim((string) ($customerUser->name ?? '')) === '' && $name !== '') {
            $customerUser->name = $name;
            $needsSave = true;
        }
        if ($needsSave) {
            $customerUser->save();
        }

        if ($createdCustomer) {
            sendCustomerPortalRegistrationNotification($email, $name);
        }

        $request->session()->regenerate();
        session([
            'portal_customer_authenticated' => true,
            'portal_customer_user' => (string) ($customerUser->name ?? 'Customer'),
            'portal_customer_user_id' => (string) ($customerUser->id ?? ''),
            'portal_customer_role' => 'CUSTOMER',
            'portal_customer_email' => strtolower(trim((string) ($customerUser->email ?? ''))),
        ]);

        Auth::guard('customer')->login($customerUser);

        $request->session()->forget($intentKey);

        $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
        return redirect($postAuthRedirect)->with('status', 'Signed in successfully with ' . ucfirst($provider) . '. You can continue browsing and book normally.');
    } catch (\Throwable $e) {
        $request->session()->forget($intentKey);

        Log::warning('Customer social login failed.', [
            'provider' => $provider,
            'error' => $e->getMessage(),
        ]);

        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Unable to sign in with ' . ucfirst($provider) . '. Please use email registration or try again.',
        ]);
    }
});

Route::post('/portal/customer/profile/update', function (Request $request) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Please sign in to update your profile.',
        ]);
    }

    $customerUserId = (string) session('portal_customer_user_id', '');
    if ($customerUserId === '') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your profile. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'first_name' => ['nullable', 'string', 'max:80'],
        'last_name' => ['nullable', 'string', 'max:80'],
        'email' => ['nullable', 'email', 'max:160'],
        'phone' => ['nullable', 'string', 'max:60'],
        'dob' => ['nullable', 'date'],
        'nationality' => ['nullable', 'string', 'max:120'],
        'gender' => ['nullable', 'string', 'max:32'],
        'preferred_language' => ['nullable', 'string', 'max:16'],
        'address_line' => ['nullable', 'string', 'max:220'],
        'address_atoll_id' => ['nullable', 'string', 'max:64'],
        'address_island_id' => ['nullable', 'string', 'max:64'],
    ]);

    $customer = \App\Models\Customer::query()->where('id', $customerUserId)->first();
    if (!$customer) {
        return back()->withErrors([
            'profile' => 'Profile not found. Please sign in again.',
        ]);
    }

    $firstName = trim((string) ($validated['first_name'] ?? ''));
    $lastName = trim((string) ($validated['last_name'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName === '') {
        $fullName = trim((string) ($customer->name ?? 'Customer'));
    }

    $email = strtolower(trim((string) ($validated['email'] ?? (string) ($customer->email ?? ''))));
    if ($email !== '') {
        $existingWithEmail = \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $customerUserId)
            ->first();

        if ($existingWithEmail) {
            return back()->withErrors([
                'email' => 'This email is already used by another account.',
            ])->withInput();
        }
    }

    $customer->name = $fullName;
    if ($email !== '') {
        $customer->email = $email;
    }
    if (customerSchemaHasColumn('updatedAt')) {
        $customer->updatedAt = now();
    }
    if (customerSchemaHasColumn('updated_at')) {
        $customer->updated_at = now();
    }
    $customer->save();

    cache()->forever(customerProfileMetaCacheKey($customerUserId), [
        'phone' => trim((string) ($validated['phone'] ?? '')),
        'dob' => trim((string) ($validated['dob'] ?? '')),
        'nationality' => trim((string) ($validated['nationality'] ?? '')),
        'gender' => trim((string) ($validated['gender'] ?? '')),
        'preferred_language' => trim((string) ($validated['preferred_language'] ?? 'en')),
        'address_line' => trim((string) ($validated['address_line'] ?? '')),
        'address_atoll_id' => trim((string) ($validated['address_atoll_id'] ?? '')),
        'address_island_id' => trim((string) ($validated['address_island_id'] ?? '')),
    ]);

    session([
        'portal_customer_user' => $fullName,
        'portal_customer_email' => $email,
    ]);

    return redirect('/customer#profile')->with('status', 'Profile updated successfully.');
});

Route::post('/portal/customer/register', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $email = strtolower(trim((string) $validated['email']));

    $existingCustomer = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($existingCustomer) {
        return back()->withErrors([
            'email' => 'A member account with this email already exists. Please log in or reset password.',
        ])->withInput();
    }

    $now = now();
    $payload = [
        'name' => trim((string) $validated['name']),
        'email' => $email,
        'password' => Hash::make((string) $validated['password']),
    ];

    if (customerSchemaHasColumn('id')) {
        $payload['id'] = (string) Str::uuid();
    }

    if (customerSchemaHasColumn('createdAt')) {
        $payload['createdAt'] = $now;
    }
    if (customerSchemaHasColumn('updatedAt')) {
        $payload['updatedAt'] = $now;
    }
    if (customerSchemaHasColumn('created_at')) {
        $payload['created_at'] = $now;
    }
    if (customerSchemaHasColumn('updated_at')) {
        $payload['updated_at'] = $now;
    }

    customerTableInsert($payload);

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) $payload['name'], true);

    $response = redirect('/portal/customer/login')->with('status', 'Member registration successful. Please verify your email before signing in.');

    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/customer/verify-email', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $email = strtolower(trim((string) $request->query('email', '')));
    $token = trim((string) $request->query('token', ''));

    if ($email === '' || $token === '') {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid. Request a new verification email.',
        ]);
    }

    $cachedToken = cache()->get(customerVerificationTokenCacheKey($email));
    if (!is_array($cachedToken) || empty($cachedToken['hash']) || !Hash::check($token, (string) $cachedToken['hash'])) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid or expired. Request a new verification email.',
        ])->with('pending_verification_email', $email);
    }

    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Member account was not found for this verification link. Please register again.',
        ]);
    }

    customerMarkEmailVerified($customerUser);

    return redirect('/portal/customer/login')->with('status', 'Email verified successfully. You can now sign in.');
});

Route::post('/portal/customer/verify-email/resend', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email', 'max:160'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return back()->withErrors([
            'username' => 'No member account was found for this email address.',
        ]);
    }

    if (customerEmailIsVerified($customerUser)) {
        return back()->with('status', 'This customer email is already verified. You can sign in now.');
    }

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) ($customerUser->name ?? 'Customer'), true);

    $response = redirect('/portal/customer/login')->with('status', 'Member registration successful. Please verify your email before signing in.');

    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/customer/verify-email', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $email = strtolower(trim((string) $request->query('email', '')));
    $token = trim((string) $request->query('token', ''));

    if ($email === '' || $token === '') {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid. Request a new verification email.',
        ]);
    }

    $cachedToken = cache()->get(customerVerificationTokenCacheKey($email));
    if (!is_array($cachedToken) || empty($cachedToken['hash']) || !Hash::check($token, (string) $cachedToken['hash'])) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid or expired. Request a new verification email.',
        ])->with('pending_verification_email', $email);
    }

    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Member account was not found for this verification link. Please register again.',
        ]);
    }

    customerMarkEmailVerified($customerUser);

    return redirect('/portal/customer/login')->with('status', 'Email verified successfully. You can now sign in.');
});

Route::post('/portal/customer/verify-email/resend', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email', 'max:160'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return back()->withErrors([
            'username' => 'No member account was found for this email address.',
        ]);
    }

    if (customerEmailIsVerified($customerUser)) {
        return back()->with('status', 'This customer email is already verified. You can sign in now.');
    }

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) ($customerUser->name ?? 'Customer'), true);

    $response = back()->with('status', 'A new verification email has been sent. Please check your inbox.');
    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);

    return view('portal-forgot-password', [
        'portal' => $portal,
        'portalName' => $config['name'],
    ]);
});

Route::post('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'email' => ['required', 'string', 'max:190'],
    ]);

    $identifier = trim((string) $validated['email']);
    $identifierLower = strtolower($identifier);
    $config = portalConfig($portal);
    $allowedRoles = collect($config['allowed_roles'])
        ->map(function ($role) {
            return normalizePortalRoleValue((string) $role);
        })
        ->unique()
        ->values();

    $portalUser = null;
    $email = '';
    if ($allowedRoles->isNotEmpty()) {
        $portalUser = \App\Models\User::query()
            ->where(function ($query) use ($identifierLower) {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [$identifierLower]);

                if (Schema::hasColumn('users', 'username')) {
                    $query->orWhereRaw('LOWER(TRIM(username)) = ?', [$identifierLower]);
                }
            })
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            $isAllowedRole = $allowedRoles->contains($resolvedRole);
            if (!$isAllowedRole && $portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                $isAllowedRole = true;
            }

            if (!$isAllowedRole || !$portalUser->portal_enabled) {
                Log::info('Portal forgot-password user filtered out.', [
                    'portal' => $portal,
                    'email' => strtolower(trim((string) ($portalUser->email ?? ''))),
                    'resolved_role' => $resolvedRole,
                    'portal_enabled' => (bool) $portalUser->portal_enabled,
                ]);
                $portalUser = null;
            } else {
                $email = strtolower(trim((string) ($portalUser->email ?? '')));
            }
        }
    } else {
        $portalUser = findCustomerByEmail($identifierLower);
        if ($portalUser instanceof \App\Models\Customer) {
            $email = strtolower(trim((string) ($portalUser->email ?? '')));
        }
    }


    $response = back()->with('status', 'If the email is registered for a ' . strtolower($config['name']) . ' account, a reset link has been sent.');

    if ($portalUser && $email !== '') {
        $brokerName = ($portalUser instanceof \App\Models\User) ? 'backend_users' : 'customer_users';
        $broker = Password::broker($brokerName);
        $mailSent = false;

        try {
            $token = $broker->createToken($portalUser);
            $resetUrl = url('/portal/' . $portal . '/reset-password/' . $token . '?email=' . rawurlencode($email));

            // Use Laravel's reset notification pipeline so all portal users share one reliable delivery path.
            $portalUser->sendPasswordResetNotification($token);
            $mailSent = true;

            // Debug link available only in testing.
            if (app()->environment('testing')) {
                $response->with('password_reset_debug_link', $resetUrl);
            }
        } catch (\Throwable $e) {
            try {
                if (isset($resetUrl) && $resetUrl !== '') {
                    sendPortalPasswordResetFallbackMail($email, $portal, $resetUrl, (string) ($portalUser->name ?? ''));
                    $mailSent = true;
                }
            } catch (\Throwable $mailFallbackError) {
                Log::warning('Portal forgot-password mail failed.', [
                    'portal' => $portal,
                    'broker' => $brokerName,
                    'email' => $email,
                    'mail_sent' => $mailSent,
                    'error' => $e->getMessage(),
                    'fallback_error' => $mailFallbackError->getMessage(),
                ]);
            }
        }
    }

    return $response;
});

Route::get('/portal/{portal}/reset-password/{token}', function (Request $request, string $portal, string $token) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);

    return view('portal-reset-password', [
        'portal' => $portal,
        'portalName' => $config['name'],
        'token' => $token,
        'email' => (string) $request->query('email', ''),
    ]);
});

Route::post('/portal/{portal}/reset-password', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $config = portalConfig($portal);
    $allowedRoles = collect($config['allowed_roles'])
        ->map(function ($role) {
            return normalizePortalRoleValue((string) $role);
        })
        ->unique()
        ->values();

    $portalUser = null;
    if ($allowedRoles->isNotEmpty()) {
        $portalUser = \App\Models\User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            $isAllowedRole = $allowedRoles->contains($resolvedRole);
            if (!$isAllowedRole && $portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                $isAllowedRole = true;
            }

            if (!$isAllowedRole) {
                $portalUser = null;
            }
        }
    } else {
        $portalUser = \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    if (!$portalUser) {
        Log::warning('Portal password reset user resolution failed.', [
            'portal' => $portal,
            'email' => $email,
            'allowed_roles' => $allowedRoles->all(),
        ]);
        return back()->withErrors([
            'email' => 'Unable to reset password for this ' . strtolower($config['name']) . ' account.',
        ])->withInput($request->only('email'));
    }

    try {
        $broker = ($portalUser instanceof \App\Models\User)
            ? 'backend_users'
            : 'customer_users';
        $tokenTable = (string) config("auth.passwords.$broker.table", 'password_reset_tokens');
        // Password brokers store reset tokens in their configured token table/connection.
        // Do not force user-model connection here; it can differ in split-db setups.
        $tokenQuery = DB::table($tokenTable);

        $resetRow = $tokenQuery
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$resetRow) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $providedToken = (string) $validated['token'];
        $storedToken = (string) $resetRow->token;
        $tokenMatches = Hash::check($providedToken, $storedToken) || hash_equals($storedToken, $providedToken);
        if (!$tokenMatches) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $expireMinutes = (int) config("auth.passwords.$broker.expire", 60);
        $createdAt = Carbon::parse((string) $resetRow->created_at);
        if ($createdAt->addMinutes($expireMinutes)->isPast()) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $updates = [
            'password' => $portalUser instanceof \App\Models\User
                ? (string) $validated['password']
                : Hash::make((string) $validated['password']),
        ];

        $rememberTokenTable = $portalUser->getTable();
        $rememberTokenConnection = $portalUser->getConnectionName();
        $hasRememberTokenColumn = $rememberTokenConnection
            ? Schema::connection($rememberTokenConnection)->hasColumn($rememberTokenTable, 'remember_token')
            : Schema::hasColumn($rememberTokenTable, 'remember_token');
        if ($hasRememberTokenColumn) {
            $updates['remember_token'] = Str::random(60);
        }

        $portalUser->forceFill($updates)->save();
        DB::table($tokenTable)->whereRaw('LOWER(email) = ?', [$email])->delete();

        return redirect('/portal/' . $portal . '/login')->with('status', __('passwords.reset'));
    } catch (\Throwable $e) {
        Log::error('Portal password reset failed', [
            'portal' => $portal,
            'email' => $email,
            'error' => $e->getMessage(),
        ]);

        return back()->withErrors([
            'email' => 'Unable to reset password at the moment. Please request a new reset link and try again.',
        ])->withInput($request->only('email'));
    }
});

Route::post('/portal/{portal}/login', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $throttleKey = 'portal-login:' . $portal . '|' . strtolower(trim((string) $validated['username'])) . '|' . $request->ip();
    $maxAttempts = $portal === 'vendor' ? 5 : 7;
    $decaySeconds = $portal === 'vendor' ? 300 : 180;

    if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $portalLabel = $portal === 'vendor' ? 'Vendor' : ($portal === 'customer' ? 'Customer' : 'Admin');
        return back()->withErrors([
            'username' => $portalLabel . ' login temporarily locked due to repeated attempts. Try again in ' . $seconds . ' seconds.',
        ])->withInput($request->only('username'));
    }

    try {
        $config = portalConfig($portal);
        $username = trim((string) $validated['username']);
        $password = (string) $validated['password'];
        $usernameLower = strtolower($username);
        $normalizedAllowedRoles = collect($config['allowed_roles'])
            ->map(function ($role) {
                return normalizePortalRoleValue((string) $role);
            })
            ->unique()
            ->values()
            ->all();

        $portalUser = null;

        // Admin/vendor login: users table; customer login: User table with vendor bridge.
        if (in_array('ADMIN', $config['allowed_roles'], true) || in_array('VENDOR', $config['allowed_roles'], true)) {
            if (Schema::hasColumns('users', ['username', 'portal_enabled', 'portal_role'])) {
                $portalCandidates = \App\Models\User::query()
                    ->where(function ($query) use ($usernameLower) {
                        $query->whereRaw('LOWER(username) = ?', [$usernameLower])
                            ->orWhereRaw('LOWER(email) = ?', [$usernameLower]);
                    })
                    ->where('portal_enabled', true)
                    ->get();

                $portalUser = $portalCandidates->first(function (\App\Models\User $candidate) use ($portal, $normalizedAllowedRoles) {
                    $resolvedRole = normalizePortalRoleValue((string) $candidate->portal_role);

                    if ($portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                        return true;
                    }

                    return in_array($resolvedRole, $normalizedAllowedRoles, true);
                });

                if (
                    $portal === 'vendor'
                    && !$portalUser
                    && str_contains($usernameLower, '@')
                ) {
                    // Allow vendors to sign in with customer password if both identities share email.
                    $candidateVendor = findActiveVendorByEmail($usernameLower);
                    $candidateCustomer = findCustomerByEmail($usernameLower);

                    if (
                        $candidateVendor instanceof \App\Models\User
                        && $candidateCustomer instanceof \App\Models\Customer
                        && Hash::check($password, (string) $candidateCustomer->password)
                    ) {
                        syncVendorPasswordFromCustomer($candidateVendor, $password);
                        $portalUser = $candidateVendor;
                    }
                }
            }
        } else {
            $directCustomer = findCustomerByEmail($usernameLower);

            if ($directCustomer instanceof \App\Models\Customer && Hash::check($password, (string) $directCustomer->password)) {
                $portalUser = $directCustomer;

                // If this customer is also an active vendor, keep vendor password aligned for single credential use.
                $linkedVendor = findActiveVendorByEmail($usernameLower);
                if ($linkedVendor instanceof \App\Models\User) {
                    syncVendorPasswordFromCustomer($linkedVendor, $password);
                }
            } else {
                // Active vendors can always access customer portal with the same credentials.
                $linkedVendor = findActiveVendorByEmail($usernameLower);
                if ($linkedVendor instanceof \App\Models\User && Hash::check($password, (string) $linkedVendor->password)) {
                    $portalUser = upsertCustomerFromVendorIdentity($linkedVendor, $password);
                } else {
                    $portalUser = $directCustomer;
                }
            }
        }

        $isBootstrapAdmin = false;
        if ($portal === 'admin') {
            $bootstrapUsername = firstNonEmptyEnv([
                'PORTAL_ADMIN_USERNAME',
                'WORKATION_ADMIN_PORTAL_USERNAME',
                'ADMIN_PORTAL_USERNAME',
                'WORKATION_ADMIN_USERNAME',
                'ADMIN_USERNAME',
                'ADMIN_USER',
            ]);
            $bootstrapPassword = firstNonEmptyEnv([
                'PORTAL_ADMIN_PASSWORD',
                'WORKATION_ADMIN_PORTAL_PASSWORD',
                'ADMIN_PORTAL_PASSWORD',
                'WORKATION_ADMIN_PASSWORD',
                'ADMIN_PASSWORD',
                'ADMIN_PASS',
            ]);

            if ($bootstrapUsername !== '' && $bootstrapPassword !== '') {
                $isBootstrapAdmin = strtolower($bootstrapUsername) === $usernameLower
                    && bootstrapPasswordMatches($bootstrapPassword, $password);
            }
        }

        $isValidDbUser = $portalUser && Hash::check($password, (string) $portalUser->password);
        if (!$isValidDbUser && !$isBootstrapAdmin) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            Log::warning('Portal login failed.', [
                'portal' => $portal,
                'username' => $usernameLower,
                'ip' => $request->ip(),
            ]);

            $portalMessage = $portal === 'vendor'
                ? 'Invalid vendor username/password, or account access is not enabled.'
                : ($portal === 'customer' ? 'Invalid customer email or password.' : 'Invalid username or password.');

            return back()->withErrors([
                'username' => $portalMessage,
            ])->withInput($request->only('username'));
        }

        if (
            $portal === 'customer'
            && $portalUser instanceof \App\Models\Customer
            && !customerEmailIsVerified($portalUser)
            && !findActiveVendorByEmail((string) ($portalUser->email ?? ''))
        ) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            return back()->withErrors([
                'username' => 'Please verify your customer email before signing in.',
            ])->withInput($request->only('username'))
                ->with('pending_verification_email', strtolower(trim((string) ($portalUser->email ?? ''))));
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        $sessionUserName = $portalUser ? $portalUser->name : 'Bootstrap Admin';
        $sessionUserId = $portalUser ? $portalUser->id : null;
        $sessionRole = $portalUser ? $portalUser->portal_role : 'ADMIN_SUPER';

        session([
            $config['session_key'] => true,
            'portal_' . $portal . '_user' => $sessionUserName,
            'portal_' . $portal . '_user_id' => $sessionUserId,
            'portal_' . $portal . '_role' => $sessionRole,
        ]);

        if ($portal === 'customer' && $portalUser) {
            session([
                'portal_customer_email' => strtolower(trim((string) ($portalUser->email ?? ''))),
            ]);
        }

        // Log in with the guard that matches the current portal.
        if ($portalUser) {
            if ($portal === 'customer') {
                Auth::guard('customer')->login($portalUser);
            } else {
                Auth::guard('backend')->login($portalUser);
            }
        }

        if ($portal === 'customer') {
            $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
            return redirect($postAuthRedirect)->with('status', 'Signed in successfully. You can keep browsing and book as a customer.');
        }

        return redirect(portalRoutePath($portal));
    } catch (\Throwable $e) {
        RateLimiter::hit($throttleKey, $decaySeconds);
        Log::error('Portal login failed with exception.', [
            'portal' => $portal,
            'username' => strtolower(trim((string) $validated['username'])),
            'ip' => $request->ip(),
            'error' => $e->getMessage(),
        ]);

        return back()->withErrors([
            'username' => 'Unable to sign in right now. Please try again in a moment.',
        ])->withInput($request->only('username'));
    }
});

$handlePortalLogout = function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    if ($portal === 'customer') {
        Auth::guard('customer')->logout();
    } else {
        Auth::guard('backend')->logout();
    }
    session()->forget([$config['session_key'], 'portal_' . $portal . '_user', 'portal_' . $portal . '_user_id', 'portal_' . $portal . '_role']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    if ($portal === 'vendor') {
        return redirect('/portal/vendor/register?mode=email');
    }

    if ($portal === 'customer') {
        return redirect('/');
    }

    return redirect('/portal/' . $portal . '/login');
};

Route::match(['GET', 'POST'], '/portal/admin/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'admin');
});

Route::match(['GET', 'POST'], '/portal/vendor/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'vendor');
});

Route::match(['GET', 'POST'], '/portal/customer/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'customer');
});

Route::post('/portal/{portal}/logout', function (Request $request, string $portal) use ($handlePortalLogout) {
    return $handlePortalLogout($request, $portal);
});

Route::post('/portal/admin/users/{user}/manage', function (Request $request, User $user) {
    $canManageUsers = Gate::allows('manage-portal-users');
    $canManageVendorUsers = canManageVendorUsers();
    $currentRole = normalizePortalRoleValue((string) $user->portal_role);
    if (!$canManageUsers && !($canManageVendorUsers && $currentRole === 'VENDOR')) {
        abort(403);
    }

    $request->merge([
        'portal_role' => normalizePortalRoleValue((string) $request->input('portal_role', '')),
    ]);

    $validated = $request->validate([
        'portal_role' => ['required', 'in:ADMIN,ADMIN_SUPER,ADMIN_CARE,ADMIN_FINANCE,ADMIN_MEDIA,VENDOR'],
        'portal_enabled' => ['required', 'in:1,0'],
        'portal_vendor_id' => ['nullable', 'string', 'max:255'],
        'vendor_verification_status' => ['nullable', 'in:pending,under_review,approved,rejected,suspended'],
        'vendor_verification_notes' => ['nullable', 'string', 'max:2000'],
        'vendor_approved_service_categories' => ['nullable', 'array'],
        'vendor_approved_service_categories.*' => ['required', 'string', 'max:80'],
        'vendor_contact_verified' => ['nullable', 'in:1,0'],
    ]);

    $isSelf = (int) session('portal_admin_user_id') === (int) $user->id;
    $nextEnabled = $validated['portal_enabled'] === '1';
    if ($isSelf && !$nextEnabled) {
        return back()->withErrors([
            'portal_enabled' => 'You cannot suspend your own active session.',
        ]);
    }

    $nextRole = normalizePortalRoleValue((string) $validated['portal_role']);

    if (!$canManageUsers && $nextRole !== 'VENDOR') {
        return back()->withErrors([
            'portal_role' => 'Admin role can only manage VENDOR accounts.',
        ]);
    }

    if ($isSelf && $nextRole !== 'ADMIN_SUPER') {
        return back()->withErrors([
            'portal_role' => 'You cannot remove your own Super Admin role from this screen.',
        ]);
    }

    $vendorId = trim((string) ($validated['portal_vendor_id'] ?? ''));
    if ($nextRole === 'VENDOR' && $vendorId === '') {
        return back()->withErrors([
            'portal_vendor_id' => 'Vendor ID is required for VENDOR users.',
        ]);
    }

    $before = [
        'portal_role' => (string) $user->portal_role,
        'portal_enabled' => (bool) $user->portal_enabled,
        'portal_vendor_id' => $user->portal_vendor_id,
        'vendor_verification_status' => Schema::hasColumn('users', 'vendor_verification_status') ? (string) ($user->vendor_verification_status ?? 'pending') : null,
        'vendor_approved_service_categories' => Schema::hasColumn('users', 'vendor_approved_service_categories') ? (string) ($user->vendor_approved_service_categories ?? '[]') : null,
    ];

    $user->portal_role = $nextRole;
    $user->portal_enabled = $nextEnabled;
    $user->portal_vendor_id = ($nextRole === 'VENDOR' && $vendorId !== '') ? $vendorId : null;

    if ($nextRole === 'VENDOR') {
        $requestedApprovedCategories = collect($validated['vendor_approved_service_categories'] ?? [])
            ->map(static fn ($item) => strtolower(trim((string) $item)))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();

        $allowedCategories = array_keys(vendorPortalCategoryMap());
        $approvedCategories = array_values(array_unique(array_intersect($allowedCategories, $requestedApprovedCategories)));
        $verificationStatus = strtolower(trim((string) ($validated['vendor_verification_status'] ?? 'pending')));

        if ($verificationStatus === 'approved' && $approvedCategories === []) {
            return back()->withErrors([
                'vendor_approved_service_categories' => 'At least one approved service category is required when verification status is approved.',
            ])->withInput();
        }

        if (Schema::hasColumn('users', 'vendor_verification_status')) {
            $user->vendor_verification_status = $verificationStatus;
        }
        if (Schema::hasColumn('users', 'vendor_verification_notes')) {
            $user->vendor_verification_notes = trim((string) ($validated['vendor_verification_notes'] ?? ''));
        }
        if (Schema::hasColumn('users', 'vendor_approved_service_categories')) {
            $user->vendor_approved_service_categories = json_encode($approvedCategories);
        }
        if (Schema::hasColumn('users', 'vendor_verified_at')) {
            if ($verificationStatus === 'approved') {
                $user->vendor_verified_at = now();
            } elseif (in_array($verificationStatus, ['rejected', 'pending', 'under_review', 'suspended'], true)) {
                $user->vendor_verified_at = null;
            }
        }
        if (Schema::hasColumn('users', 'vendor_verified_by_user_id')) {
            if ($verificationStatus === 'approved') {
                $user->vendor_verified_by_user_id = (int) session('portal_admin_user_id', 0) ?: null;
            } elseif (in_array($verificationStatus, ['rejected', 'pending', 'under_review', 'suspended'], true)) {
                $user->vendor_verified_by_user_id = null;
            }
        }
        if (Schema::hasColumn('users', 'vendor_contact_verified_at')) {
            $contactVerified = (string) ($validated['vendor_contact_verified'] ?? '0') === '1';
            $user->vendor_contact_verified_at = $contactVerified ? now() : null;
        }
        if (Schema::hasColumn('users', 'vendor_contact_verified_by_user_id')) {
            $contactVerified = (string) ($validated['vendor_contact_verified'] ?? '0') === '1';
            $user->vendor_contact_verified_by_user_id = $contactVerified
                ? ((int) session('portal_admin_user_id', 0) ?: null)
                : null;
        }
    }

    $user->save();

    portalAdminAuditLog('user.updated', [
        'target_user_id' => (int) $user->id,
        'target_identifier' => (string) ($user->username ?: $user->email),
        'target_role' => (string) $user->portal_role,
        'before' => $before,
        'after' => [
            'portal_role' => (string) $user->portal_role,
            'portal_enabled' => (bool) $user->portal_enabled,
            'portal_vendor_id' => $user->portal_vendor_id,
            'vendor_verification_status' => Schema::hasColumn('users', 'vendor_verification_status') ? (string) ($user->vendor_verification_status ?? 'pending') : null,
            'vendor_approved_service_categories' => Schema::hasColumn('users', 'vendor_approved_service_categories') ? (string) ($user->vendor_approved_service_categories ?? '[]') : null,
        ],
    ]);

    return back()->with('portal_notice', 'Portal user updated: ' . ($user->username ?: ('#' . $user->id)));
});

Route::get('/portal/admin/vendor-registrations/{registration}/document/{documentType}', function (int $registration, string $documentType) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        abort(404);
    }

    if (!in_array($documentType, ['business_license', 'verification'], true)) {
        abort(404);
    }

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        abort(404);
    }

    $path = $documentType === 'business_license'
        ? (string) ($registrationRow->business_license_document_path ?? '')
        : (string) ($registrationRow->verification_document_path ?? '');

    if ($path === '' || !Storage::disk('local')->exists($path)) {
        abort(404);
    }

    return Storage::disk('local')->download($path);
});

Route::post('/portal/admin/vendor-registrations/{registration}/approve', function (Request $request, int $registration) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor registration table is missing. Run migrations first.',
        ]);
    }

    $validated = $request->validate([
        'portal_vendor_id' => ['required', 'string', 'max:255'],
        'approval_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        return back()->withErrors([
            'registration' => 'Vendor registration request not found.',
        ]);
    }

    if ((string) $registrationRow->status !== 'pending') {
        return back()->withErrors([
            'registration' => 'Only pending registration requests can be approved.',
        ]);
    }

    $email = strtolower(trim((string) $registrationRow->email));
    $vendorId = trim((string) $validated['portal_vendor_id']);
    $approvalNotes = trim((string) ($validated['approval_notes'] ?? ''));

    // ADMIN_CARE can review and request approval, but final approval is ADMIN/ADMIN_SUPER only.
    if (!canApproveVendorRegistrationRequest()) {
        if (!portalActionRequestsEnabled()) {
            return back()->withErrors([
                'registration' => 'Approval request workflow is not available until migrations are applied.',
            ]);
        }

        $existingPending = DB::table('portal_admin_action_requests')
            ->where('status', 'pending')
            ->where('action_type', 'vendor_registration_approve')
            ->where('target_registration_id', $registration)
            ->exists();

        if ($existingPending) {
            return back()->withErrors([
                'registration' => 'An approval request is already pending for this vendor registration.',
            ]);
        }

        $requestId = createPortalActionRequest(
            'vendor_registration_approve',
            null,
            $registration,
            (string) $registrationRow->email,
            $approvalNotes !== '' ? $approvalNotes : 'Submitted by ADMIN_CARE for ADMIN/ADMIN_SUPER approval.',
            [
                'portal_vendor_id' => $vendorId,
                'approval_notes' => $approvalNotes,
            ]
        );

        portalAdminAuditLog('vendor_registration.approval_requested', [
            'target_identifier' => (string) $registrationRow->email,
            'target_role' => 'VENDOR',
            'registration_id' => $registration,
            'action_request_id' => $requestId,
            'portal_vendor_id' => $vendorId,
        ]);

        return back()->with('portal_notice', 'Vendor approval request submitted for ADMIN/ADMIN_SUPER approval.');
    }

    $resetEmailSent = false;
    $resetEmailError = null;
    $approvedUserId = null;
    $approvedUserIdentifier = null;

    try {
        DB::transaction(function () use (
            $registration,
            $email,
            $vendorId,
            $approvalNotes,
            &$resetEmailSent,
            &$resetEmailError,
            &$approvedUserId,
            &$approvedUserIdentifier
        ) {
            $requestRow = DB::table('vendor_registration_requests')
                ->where('id', $registration)
                ->lockForUpdate()
                ->first();

            if (!$requestRow || (string) $requestRow->status !== 'pending') {
                throw new \RuntimeException('This registration request is no longer pending.');
            }

            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('An existing non-vendor account already uses this email.');
            }

            $portalUser = $existingUser;
            if (!$portalUser instanceof User) {
                $portalUser = new User();
                $portalUser->username = generatePortalUsernameFromEmail($email);
                $portalUser->email = $email;
                $portalUser->password = Hash::make(Str::random(24));
            }

            $portalUser->name = (string) $requestRow->contact_name;
            $portalUser->portal_role = 'VENDOR';
            $portalUser->portal_enabled = true;
            $portalUser->portal_vendor_id = $vendorId;
            $portalUser->save();

            $approvedUserId = (int) $portalUser->id;
            $approvedUserIdentifier = (string) ($portalUser->username ?: $portalUser->email);

            try {
                $token = Password::broker('backend_users')->createToken($portalUser);
                $portalUser->sendPasswordResetNotification($token);
                $resetEmailSent = true;
            } catch (\Throwable $mailError) {
                $resetEmailSent = false;
                $resetEmailError = $mailError->getMessage();
                Log::error('Failed to send vendor portal reset email after registration approval.', [
                    'registration_id' => (int) $requestRow->id,
                    'user_id' => (int) $portalUser->id,
                    'email' => $email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            DB::table('vendor_registration_requests')
                ->where('id', $registration)
                ->update([
                    'status' => 'approved',
                    'review_notes' => $approvalNotes !== '' ? $approvalNotes : null,
                    'reviewed_by_user_id' => session('portal_admin_user_id'),
                    'reviewed_at' => now(),
                    'approved_user_id' => $portalUser->id,
                    'updated_at' => now(),
                ]);
        });
    } catch (\Throwable $e) {
        return back()->withErrors([
            'registration' => $e->getMessage(),
        ]);
    }

    portalAdminAuditLog('vendor_registration.approved', [
        'target_user_id' => $approvedUserId,
        'target_identifier' => $approvedUserIdentifier,
        'target_role' => 'VENDOR',
        'registration_id' => $registration,
        'registration_email' => $email,
        'portal_vendor_id' => $vendorId,
        'reset_email_sent' => $resetEmailSent,
    ]);

    if ($resetEmailSent) {
        return back()->with('portal_notice', 'Vendor registration approved and reset email sent.');
    }

    return back()->withErrors([
        'registration' => 'Vendor registration approved, but password setup email failed to send. Please verify mail settings.',
    ]);
});

Route::post('/portal/admin/action-requests/{requestId}/approve', function (int $requestId) {
    if (!portalActionRequestsEnabled()) {
        return back()->withErrors(['request' => 'Action request workflow table is missing. Run migrations first.']);
    }

    $requestRow = DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->first();

    if (!$requestRow || (string) $requestRow->status !== 'pending') {
        return back()->withErrors(['request' => 'Pending action request not found.']);
    }

    if ((string) $requestRow->action_type === 'vendor_delete') {
        if (!canApproveVendorDeleteRequest()) {
            abort(403);
        }

        $targetUserId = (int) ($requestRow->target_user_id ?? 0);
        $targetUser = $targetUserId > 0 ? User::query()->find($targetUserId) : null;
        if ($targetUser instanceof User) {
            if (normalizePortalRoleValue((string) $targetUser->portal_role) !== 'VENDOR') {
                return back()->withErrors(['request' => 'Target user is no longer a vendor account.']);
            }
            $targetUser->delete();
        }

        DB::table('portal_admin_action_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'approved',
                'approved_by_user_id' => session('portal_admin_user_id'),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        portalAdminAuditLog('vendor_delete.approved', [
            // The vendor may have been deleted above; keep identifier/role and avoid stale FK value.
            'target_user_id' => null,
            'target_identifier' => (string) ($requestRow->target_identifier ?? 'unknown-vendor'),
            'target_role' => 'VENDOR',
            'action_request_id' => $requestId,
        ]);

        return back()->with('portal_notice', 'Vendor delete request approved and processed.');
    }

    if ((string) $requestRow->action_type === 'vendor_registration_approve') {
        if (!canApproveVendorRegistrationRequest()) {
            abort(403);
        }

        $registrationId = (int) ($requestRow->target_registration_id ?? 0);
        if ($registrationId <= 0) {
            return back()->withErrors(['request' => 'Vendor registration target is missing.']);
        }

        $registrationRow = DB::table('vendor_registration_requests')
            ->where('id', $registrationId)
            ->first();

        if (!$registrationRow || (string) $registrationRow->status !== 'pending') {
            return back()->withErrors(['request' => 'Vendor registration is no longer pending.']);
        }

        $payload = [];
        if (!empty($requestRow->payload)) {
            $decoded = json_decode((string) $requestRow->payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $vendorId = trim((string) ($payload['portal_vendor_id'] ?? ''));
        if ($vendorId === '') {
            return back()->withErrors(['request' => 'Approval request payload is missing vendor ID.']);
        }
        $approvalNotes = trim((string) ($payload['approval_notes'] ?? ''));

        $email = strtolower(trim((string) $registrationRow->email));
        $resetEmailSent = false;

        DB::transaction(function () use ($registrationId, $email, $vendorId, $approvalNotes, $requestId, &$resetEmailSent): void {
            $requestRegistrationRow = DB::table('vendor_registration_requests')
                ->where('id', $registrationId)
                ->lockForUpdate()
                ->first();

            if (!$requestRegistrationRow || (string) $requestRegistrationRow->status !== 'pending') {
                throw new \RuntimeException('This registration request is no longer pending.');
            }

            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('An existing non-vendor account already uses this email.');
            }

            $portalUser = $existingUser;
            if (!$portalUser instanceof User) {
                $portalUser = new User();
                $portalUser->username = generatePortalUsernameFromEmail($email);
                $portalUser->email = $email;
                $portalUser->password = Hash::make(Str::random(24));
            }

            $portalUser->name = (string) $requestRegistrationRow->contact_name;
            $portalUser->portal_role = 'VENDOR';
            $portalUser->portal_enabled = true;
            $portalUser->portal_vendor_id = $vendorId;
            $portalUser->save();

            $token = Password::broker('backend_users')->createToken($portalUser);
            $portalUser->sendPasswordResetNotification($token);
            $resetEmailSent = true;

            DB::table('vendor_registration_requests')
                ->where('id', $registrationId)
                ->update([
                    'status' => 'approved',
                    'review_notes' => $approvalNotes !== '' ? $approvalNotes : null,
                    'reviewed_by_user_id' => session('portal_admin_user_id'),
                    'reviewed_at' => now(),
                    'approved_user_id' => $portalUser->id,
                    'updated_at' => now(),
                ]);

            DB::table('portal_admin_action_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => 'approved',
                    'approved_by_user_id' => session('portal_admin_user_id'),
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        portalAdminAuditLog('vendor_registration.approval_request_approved', [
            'target_identifier' => (string) $registrationRow->email,
            'target_role' => 'VENDOR',
            'registration_id' => $registrationId,
            'action_request_id' => $requestId,
            'reset_email_sent' => $resetEmailSent,
        ]);

        return back()->with('portal_notice', 'Vendor registration approval request processed successfully.');
    }

    return back()->withErrors(['request' => 'Unsupported action request type.']);
});

Route::post('/portal/admin/action-requests/{requestId}/reject', function (Request $request, int $requestId) {
    if (!portalActionRequestsEnabled()) {
        return back()->withErrors(['request' => 'Action request workflow table is missing. Run migrations first.']);
    }

    $validated = $request->validate([
        'reason' => ['required', 'string', 'max:2000'],
    ]);

    $requestRow = DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->first();

    if (!$requestRow || (string) $requestRow->status !== 'pending') {
        return back()->withErrors(['request' => 'Pending action request not found.']);
    }

    $actionType = (string) $requestRow->action_type;
    if ($actionType === 'vendor_delete' && !canApproveVendorDeleteRequest()) {
        abort(403);
    }
    if ($actionType === 'vendor_registration_approve' && !canApproveVendorRegistrationRequest()) {
        abort(403);
    }

    DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->update([
            'status' => 'rejected',
            'approved_by_user_id' => session('portal_admin_user_id'),
            'approved_at' => now(),
            'rejection_reason' => trim((string) $validated['reason']),
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('action_request.rejected', [
        'target_identifier' => (string) ($requestRow->target_identifier ?? 'unknown-target'),
        'action_type' => $actionType,
        'action_request_id' => $requestId,
    ]);

    return back()->with('portal_notice', 'Action request rejected.');
});

Route::post('/portal/admin/vendor-registrations/{registration}/reject', function (Request $request, int $registration) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor registration table is missing. Run migrations first.',
        ]);
    }

    $validated = $request->validate([
        'review_notes' => ['required', 'string', 'max:2000'],
    ]);

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        return back()->withErrors([
            'registration' => 'Vendor registration request not found.',
        ]);
    }

    if ((string) $registrationRow->status !== 'pending') {
        return back()->withErrors([
            'registration' => 'Only pending registration requests can be rejected.',
        ]);
    }

    DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->update([
            'status' => 'rejected',
            'review_notes' => trim((string) $validated['review_notes']),
            'reviewed_by_user_id' => session('portal_admin_user_id'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('vendor_registration.rejected', [
        'target_identifier' => (string) $registrationRow->email,
        'target_role' => 'VENDOR',
        'registration_id' => $registration,
        'registration_email' => (string) $registrationRow->email,
    ]);

    return back()->with('portal_notice', 'Vendor registration rejected.');
});

// Legacy Laravel business routes are decommissioned in runtime.
Route::post('/portal/admin/listings/{listing}/approve', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_properties') || !Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        return back()->withErrors(['listing' => 'Listing moderation columns are missing. Run migrations first.']);
    }

    $listingRow = DB::table('vendor_properties')->where('id', $listing)->first();
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    if ((string) ($listingRow->listing_moderation_status ?? '') !== 'pending_review') {
        return back()->withErrors(['listing' => 'Only listings in pending_review status can be approved.']);
    }

    $adminNotes = trim((string) ($request->input('admin_notes') ?? ''));
    $adminUserId = (int) session('portal_admin_user_id');

    DB::table('vendor_properties')
        ->where('id', $listing)
        ->update([
            'listing_moderation_status' => 'approved',
            'listing_admin_notes' => $adminNotes ?: null,
            'listing_approved_at' => now(),
            'listing_approved_by_user_id' => $adminUserId,
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('listing.approved', [
        'target_identifier' => (string) ($listingRow->name ?? $listingRow->listing_name ?? ('listing_id:' . $listing)),
        'target_role' => 'VENDOR',
        'listing_id' => $listing,
        'vendor_id' => (int) ($listingRow->vendor_user_id ?? 0),
    ]);

    return back()->with('portal_notice', 'Listing approved and is now open for bookings.');
});

Route::post('/portal/admin/listings/{listing}/reject', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_properties') || !Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        return back()->withErrors(['listing' => 'Listing moderation columns are missing. Run migrations first.']);
    }

    $validated = $request->validate([
        'admin_notes' => ['required', 'string', 'max:2000'],
    ]);

    $listingRow = DB::table('vendor_properties')->where('id', $listing)->first();
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    if (!in_array((string) ($listingRow->listing_moderation_status ?? ''), ['pending_review', 'approved'], true)) {
        return back()->withErrors(['listing' => 'Only pending_review or approved listings can be rejected.']);
    }

    $adminUserId = (int) session('portal_admin_user_id');

    DB::table('vendor_properties')
        ->where('id', $listing)
        ->update([
            'listing_moderation_status' => 'rejected',
            'listing_admin_notes' => trim((string) $validated['admin_notes']),
            'listing_approved_at' => now(),
            'listing_approved_by_user_id' => $adminUserId,
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('listing.rejected', [
        'target_identifier' => (string) ($listingRow->name ?? $listingRow->listing_name ?? ('listing_id:' . $listing)),
        'target_role' => 'VENDOR',
        'listing_id' => $listing,
        'vendor_id' => (int) ($listingRow->vendor_user_id ?? 0),
    ]);

    return back()->with('portal_notice', 'Listing rejected. The vendor will be notified to make corrections.');
});

// Atoll & Island shared data API endpoints
Route::prefix('api/atoll-island')->group(function () {
    Route::get('atolls', [\App\Http\Controllers\AtollIslandApiController::class, 'getAllAtolls']);
    Route::get('atolls/{atoll}/islands', [\App\Http\Controllers\AtollIslandApiController::class, 'getIslandsByAtoll']);
    Route::get('atolls/{atoll}/stats', [\App\Http\Controllers\AtollIslandApiController::class, 'getAtollStats']);
    Route::get('islands/{island}', [\App\Http\Controllers\AtollIslandApiController::class, 'getIslandWithMedia']);
    Route::get('islands', [\App\Http\Controllers\AtollIslandApiController::class, 'getFeaturedIslands']);
});

// Keep these endpoints available only in testing for legacy feature-test coverage.
if (app()->environment('testing')) {
    Route::prefix('api')->group(function () {
        Route::get('workations', [\App\Http\Controllers\WorkationController::class, 'index']);
        Route::get('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'show']);
        Route::post('workations', [\App\Http\Controllers\WorkationController::class, 'store']);
        Route::put('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'update']);
        Route::delete('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'destroy']);

        Route::post('transport/holds', [\App\Http\Controllers\TransportHoldController::class, 'store']);
        Route::post('transport/holds/{hold}/confirm', [\App\Http\Controllers\TransportHoldController::class, 'confirm']);
        Route::post('transport/holds/{hold}/release', [\App\Http\Controllers\TransportHoldController::class, 'release']);
    });
}