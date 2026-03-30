<?php

use App\Models\User;
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
        while (\App\Models\User::where('username', $username)->exists()) {
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

Route::get('/', function () {
    $apiBase = workationApiBase();

    return view('welcome', [
        'apiBase' => $apiBase,
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

    return view('customer-portal', [
        'summary' => [
            'upcoming_bookings' => 0,
            'completed_bookings' => 0,
            'receipts_available' => 0,
            'notification_state' => 'ACTIVE',
        ],
        'customerProperties' => $customerProperties,
        'customerRoomsByProperty' => $customerRoomsByProperty,
        'propertyMediaByProperty' => $propertyMediaByProperty,
        'roomMediaByRoom' => $roomMediaByRoom,
    ]);
});

Route::get('/media/vendor/{media}/{variant?}', function (int $media, ?string $variant = 'banner') {
    if (!Schema::hasTable('vendor_listing_media')) {
        abort(404);
    }

    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->first(['file_path', 'mime_type']);

    if (!$mediaRecord) {
        abort(404);
    }

    $originalPath = trim((string) ($mediaRecord->file_path ?? ''));
    if ($originalPath === '') {
        abort(404);
    }

    if (str_starts_with($originalPath, 'http://') || str_starts_with($originalPath, 'https://')) {
        return redirect()->away($originalPath);
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

    $normalizeDiskPath = static function (string $path): string {
        $normalized = trim(str_replace('\\', '/', $path));
        if ($normalized === '') {
            return '';
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
        $originalPath,
        $normalizeDiskPath($candidatePath),
        $normalizeDiskPath($originalPath),
    ])->map(static fn ($path) => trim((string) $path))
      ->filter(static fn ($path) => $path !== '')
      ->unique()
      ->values()
      ->all();

    $resolvedBinary = null;
    $resolvedMimeType = '';

    $publicDisk = Storage::disk('public');
    foreach ($candidatePaths as $path) {
        if (!$publicDisk->exists($path)) {
            continue;
        }

        $resolvedBinary = $publicDisk->get($path);
        $resolvedMimeType = (string) ($publicDisk->mimeType($path) ?: '');
        break;
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
        abort(404);
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

    $query = strtolower(trim((string) $request->query('q', '')));
    $portalUsers = User::query()
        ->whereIn('portal_role', ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'VENDOR'])
        ->orderBy('portal_role')
        ->orderBy('username')
        ->get(['id', 'name', 'username', 'email', 'portal_role', 'portal_enabled', 'portal_vendor_id']);

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

Route::get('/admin', function () {
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
    $portalUsers = User::query()
        ->whereIn('portal_role', ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'VENDOR'])
        ->orderBy('portal_role')
        ->orderBy('username')
        ->get(['id', 'name', 'username', 'email', 'portal_role', 'portal_enabled', 'portal_vendor_id']);

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
        'listingOptionCatalog' => $listingOptionCatalog,
    ]);
});

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

    Route::post('/portal/admin/users/create', function (\Illuminate\Http\Request $request) {
        $canManageUsers = Gate::allows('manage-portal-users');
        $canCreateVendorUsers = canCreateVendorUsers();
        if (!$canManageUsers && !$canCreateVendorUsers) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Insufficient privileges to create portal users.'], 403);
            }

            return back()->withErrors(['auth' => 'Insufficient privileges to create portal users.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'portal_role' => 'required|in:ADMIN,ADMIN_SUPER,ADMIN_CARE,ADMIN_FINANCE,ADMIN_FINACE,VENDOR',
            'portal_enabled' => 'required|boolean',
            'portal_vendor_id' => 'nullable|string|max:255',
        ]);

        $normalizedRole = $validated['portal_role'] === 'ADMIN_FINACE'
            ? 'ADMIN_FINANCE'
            : $validated['portal_role'];

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
            try {
                $token = Password::broker('backend_users')->createToken($user);
                $user->sendPasswordResetNotification($token);
                $resetEmailSent = true;
            } catch (\Throwable $e) {
                $resetEmailError = $e->getMessage();
                Log::error('Failed to send portal user reset email after creation.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
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

    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    if ($portal === 'vendor') {
        return redirect('/portal/vendor/register?mode=email');
    }

    $config = portalConfig($portal);
    if (session()->get($config['session_key'], false)) {
        return redirect(portalRoutePath($portal));
    }

    return view('portal-login', [
        'portal' => $portal,
        'portalName' => $config['name'],
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

Route::get('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);

    return view('portal-forgot-password', [
        'portal' => $portal,
        'portalName' => $config['name'],
    ]);
});

Route::post('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'email' => ['required', 'email'],
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
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            if (!$allowedRoles->contains($resolvedRole) || !$portalUser->portal_enabled) {
                $portalUser = null;
            }
        }
    } else {
        $portalUser = \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }


    if ($portalUser) {
        $broker = ($portalUser instanceof \App\Models\User)
            ? Password::broker('backend_users')
            : Password::broker('customer_users');
        $token = $broker->createToken($portalUser);
        $portalUser->sendPasswordResetNotification($token);
    }

    return back()->with('status', 'If the email is registered for a ' . strtolower($config['name']) . ' account, a reset link has been sent.');
});

Route::get('/portal/{portal}/reset-password/{token}', function (Request $request, string $portal, string $token) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor'], true)) {
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
    if (!in_array($portal, ['admin', 'vendor'], true)) {
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
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            if (!$allowedRoles->contains($resolvedRole)) {
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
        $resetRow = DB::table($tokenTable)
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
            'password' => (string) $validated['password'],
        ];

        // Some production databases may not include remember_token on legacy users schemas.
        if (Schema::hasColumn('users', 'remember_token')) {
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
    if (!in_array($portal, ['admin', 'vendor'], true)) {
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
        $portalLabel = $portal === 'vendor' ? 'Vendor' : 'Admin';
        return back()->withErrors([
            'username' => $portalLabel . ' login temporarily locked due to repeated attempts. Try again in ' . $seconds . ' seconds.',
        ])->withInput($request->only('username'));
    }

    try {
        $config = portalConfig($portal);
        $username = trim((string) $validated['username']);
        $password = (string) $validated['password'];
        $usernameLower = strtolower($username);

        $portalUser = null;

        // Admin/vendor login: users table; customer login: User table
        if (in_array('ADMIN', $config['allowed_roles'], true) || in_array('VENDOR', $config['allowed_roles'], true)) {
            if (Schema::hasColumns('users', ['username', 'portal_enabled', 'portal_role'])) {
                $portalUser = \App\Models\User::query()
                    ->where(function ($query) use ($usernameLower) {
                        $query->whereRaw('LOWER(username) = ?', [$usernameLower])
                            ->orWhereRaw('LOWER(email) = ?', [$usernameLower]);
                    })
                    ->where('portal_enabled', true)
                    ->whereIn('portal_role', $config['allowed_roles'])
                    ->first();
            }
        } else {
            $portalUser = \App\Models\Customer::query()
                ->where(function ($query) use ($usernameLower) {
                    $query->whereRaw('LOWER(email) = ?', [$usernameLower]);
                })
                ->first();
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
                : 'Invalid username or password.';

            return back()->withErrors([
                'username' => $portalMessage,
            ])->withInput($request->only('username'));
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

        // Log in the user using Laravel Auth if found
        if ($portalUser) {
            Auth::login($portalUser);
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

Route::post('/portal/{portal}/logout', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    session()->forget([$config['session_key'], 'portal_' . $portal . '_user', 'portal_' . $portal . '_user_id', 'portal_' . $portal . '_role']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/portal/' . $portal . '/login');
});

Route::post('/portal/admin/users/{user}/manage', function (Request $request, User $user) {
    $canManageUsers = Gate::allows('manage-portal-users');
    $canManageVendorUsers = canManageVendorUsers();
    $currentRole = normalizePortalRoleValue((string) $user->portal_role);
    if (!$canManageUsers && !($canManageVendorUsers && $currentRole === 'VENDOR')) {
        abort(403);
    }

    $validated = $request->validate([
        'portal_role' => ['required', 'in:ADMIN,ADMIN_SUPER,ADMIN_CARE,ADMIN_FINANCE,ADMIN_FINACE,VENDOR'],
        'portal_enabled' => ['required', 'in:1,0'],
        'portal_vendor_id' => ['nullable', 'string', 'max:255'],
    ]);

    $isSelf = (int) session('portal_admin_user_id') === (int) $user->id;
    $nextEnabled = $validated['portal_enabled'] === '1';
    if ($isSelf && !$nextEnabled) {
        return back()->withErrors([
            'portal_enabled' => 'You cannot suspend your own active session.',
        ]);
    }

    $nextRole = (string) $validated['portal_role'];
    if ($nextRole === 'ADMIN_FINACE') {
        $nextRole = 'ADMIN_FINANCE';
    }

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
    ];

    $user->portal_role = $nextRole;
    $user->portal_enabled = $nextEnabled;
    $user->portal_vendor_id = ($nextRole === 'VENDOR' && $vendorId !== '') ? $vendorId : null;
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

