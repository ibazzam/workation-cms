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
    $financeTaxableCategoryOptions = vendorPortalCategoryMap();

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

    // Listing moderation data — read from dedicated category tables (primary source),
    // falling back to vendor_properties if dedicated tables lack moderation columns.
    $pendingModerationListings = \App\Support\VendorPropertyCompatibilityReader::pendingModerationListings(100);
    $listingModerationHistory = \App\Support\VendorPropertyCompatibilityReader::listingModerationHistory(80);

    // Fallback: if dedicated tables have no moderation columns yet, read from vendor_properties.
    if ($pendingModerationListings->isEmpty()
        && Schema::hasTable('vendor_properties')
        && Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
        $pendingModerationListings = DB::table('vendor_properties as vp')
            ->leftJoin('users as vu', 'vu.id', '=', 'vp.vendor_user_id')
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
    }
    if ($listingModerationHistory->isEmpty()
        && Schema::hasTable('vendor_properties')
        && Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
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
        'financeTaxableCategoryOptions' => $financeTaxableCategoryOptions,
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

Route::post('/portal/admin/finance/policy/update', function (Request $request) {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can update reservation finance policy.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return back()->withErrors(['auth' => 'Finance settings table is not ready. Run migrations first.']);
    }

    $categoryKeys = array_keys(vendorPortalCategoryMap());
    $validated = $request->validate([
        'green_tax_room_threshold' => ['required', 'integer', 'min:1', 'max:10000'],
        'taxable_categories' => ['nullable', 'array'],
        'taxable_categories.*' => ['string', Rule::in($categoryKeys)],
        'transfer_default_local_adult_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'transfer_default_local_child_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'transfer_default_foreign_adult_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'transfer_default_foreign_child_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'transfer_default_base_local' => ['required', 'numeric', 'min:0', 'max:1000000'],
        'transfer_default_base_foreign' => ['required', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    $policy = portalFinanceLoadReservationPolicy();
    $policy['green_tax_room_threshold'] = (int) $validated['green_tax_room_threshold'];
    $policy['taxable_categories'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => strtolower(trim((string) $value)), (array) ($validated['taxable_categories'] ?? ['accommodation'])),
        static fn (string $value): bool => $value !== ''
    )));
    $policy['transfer_default_local_adult_rate'] = round((float) $validated['transfer_default_local_adult_rate'], 4);
    $policy['transfer_default_local_child_rate'] = round((float) $validated['transfer_default_local_child_rate'], 4);
    $policy['transfer_default_foreign_adult_rate'] = round((float) $validated['transfer_default_foreign_adult_rate'], 4);
    $policy['transfer_default_foreign_child_rate'] = round((float) $validated['transfer_default_foreign_child_rate'], 4);
    $policy['transfer_default_base_local'] = round((float) $validated['transfer_default_base_local'], 4);
    $policy['transfer_default_base_foreign'] = round((float) $validated['transfer_default_base_foreign'], 4);

    portalFinanceSaveReservationPolicy($policy, $actorUserId);

    portalAdminAuditLog('finance_reservation_policy_updated', [
        'target_role' => 'ADMIN_FINANCE',
        'green_tax_room_threshold' => (int) $validated['green_tax_room_threshold'],
        'taxable_categories' => $policy['taxable_categories'],
        'transfer_default_local_adult_rate' => $policy['transfer_default_local_adult_rate'],
        'transfer_default_local_child_rate' => $policy['transfer_default_local_child_rate'],
        'transfer_default_foreign_adult_rate' => $policy['transfer_default_foreign_adult_rate'],
        'transfer_default_foreign_child_rate' => $policy['transfer_default_foreign_child_rate'],
        'transfer_default_base_local' => $policy['transfer_default_base_local'],
        'transfer_default_base_foreign' => $policy['transfer_default_base_foreign'],
    ]);

    return back()->with('portal_notice', 'Reservation finance policy updated.');
});

Route::post('/portal/admin/finance/policy/apply-maldives-defaults', function () {
    if (!canModeratePortalFinance()) {
        return back()->withErrors(['auth' => 'Only ADMIN_SUPER or ADMIN_FINANCE can apply Maldives finance defaults.']);
    }

    if (!Schema::hasTable('portal_finance_settings')) {
        return back()->withErrors(['auth' => 'Finance settings table is not ready. Run migrations first.']);
    }

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;
    portalFinanceSaveReservationPolicy(ReservationPricingPolicy::defaultPolicy(), $actorUserId);

    portalAdminAuditLog('finance_policy_maldives_defaults_applied', [
        'target_role' => 'ADMIN_FINANCE',
    ]);

    return back()->with('portal_notice', 'Maldives finance defaults applied.');
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
        'applies_to_categories_csv' => ['nullable', 'string', 'max:1000'],
        'active' => ['nullable', Rule::in(['0', '1'])],
        'is_service_charge' => ['nullable', Rule::in(['0', '1'])],
        'exclude_infants' => ['nullable', Rule::in(['0', '1'])],
        'min_room_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'max_room_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
    ]);

    $allowedCategoryKeys = array_keys(vendorPortalCategoryMap());
    $appliesToCategories = [];
    $rawCategories = trim((string) ($validated['applies_to_categories_csv'] ?? ''));
    if ($rawCategories !== '') {
        $appliesToCategories = array_values(array_unique(array_filter(array_map(static function ($value) use ($allowedCategoryKeys): string {
            $normalized = strtolower(trim((string) $value));
            $normalized = str_replace([' ', '-'], '_', $normalized);
            $normalized = preg_replace('/[^a-z0-9_]+/', '', $normalized) ?? '';
            return in_array($normalized, $allowedCategoryKeys, true) ? $normalized : '';
        }, explode(',', $rawCategories)), static fn (string $value): bool => $value !== '')));
    }

    $actorUserId = is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null;

    portalFinanceUpsertTaxComponent([
        'code' => (string) $validated['code'],
        'label' => (string) $validated['label'],
        'calculation_mode' => (string) $validated['calculation_mode'],
        'default_rate' => (float) $validated['default_rate'],
        'applies_to' => (string) $validated['applies_to'],
        'applies_to_categories' => $appliesToCategories,
        'active' => (string) ($validated['active'] ?? '1') === '1',
        'is_service_charge' => (string) ($validated['is_service_charge'] ?? '0') === '1',
        'exclude_infants' => (string) ($validated['exclude_infants'] ?? '0') === '1',
        'min_room_count' => $validated['min_room_count'] ?? null,
        'max_room_count' => $validated['max_room_count'] ?? null,
    ], $actorUserId);

    portalAdminAuditLog('finance_tax_component_upserted', [
        'target_role' => 'ADMIN_FINANCE',
        'tax_code' => (string) $validated['code'],
        'calculation_mode' => (string) $validated['calculation_mode'],
        'default_rate' => round((float) $validated['default_rate'], 4),
        'applies_to' => (string) $validated['applies_to'],
        'applies_to_categories' => $appliesToCategories,
        'exclude_infants' => (string) ($validated['exclude_infants'] ?? '0') === '1',
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
        'option_type' => ['required', Rule::in([
            'transport_mode',
            'accommodation_facility',
            'property_amenity',
            'property_feature',
            'room_amenity',
            'bathroom_amenity',
            'room_bed_type',
            'excursion_type',
            'restaurant_meal_service',
            'vehicle_rental_type',
            'transfer_option',
        ])],
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
        portalForgetHeroSlotCache($slot);
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
        'is_country_capital' => ['nullable', 'boolean'],
        'is_atoll_capital' => ['nullable', 'boolean'],
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
    if (Schema::hasColumn('islands', 'is_country_capital')) {
        $island->is_country_capital = (bool) ($validated['is_country_capital'] ?? false);
    }
    if (Schema::hasColumn('islands', 'is_atoll_capital')) {
        $island->is_atoll_capital = (bool) ($validated['is_atoll_capital'] ?? false);
    }
    $island->save();

    if (Schema::hasColumn('islands', 'is_country_capital') && (bool) ($island->is_country_capital ?? false)) {
        \App\Models\Island::query()
            ->where('id', '!=', (int) $island->id)
            ->where('is_country_capital', true)
            ->update(['is_country_capital' => false]);
    }

    if (Schema::hasColumn('islands', 'is_atoll_capital') && (bool) ($island->is_atoll_capital ?? false)) {
        \App\Models\Island::query()
            ->where('id', '!=', (int) $island->id)
            ->where('atoll_id', (int) $island->atoll_id)
            ->where('is_atoll_capital', true)
            ->update(['is_atoll_capital' => false]);
    }

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
        'is_country_capital' => ['nullable', 'boolean'],
        'is_atoll_capital' => ['nullable', 'boolean'],
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
    if (Schema::hasColumn('islands', 'is_country_capital')) {
        $record->is_country_capital = (bool) ($validated['is_country_capital'] ?? false);
    }
    if (Schema::hasColumn('islands', 'is_atoll_capital')) {
        $record->is_atoll_capital = (bool) ($validated['is_atoll_capital'] ?? false);
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

    if (Schema::hasColumn('islands', 'is_country_capital') && (bool) ($record->is_country_capital ?? false)) {
        \App\Models\Island::query()
            ->where('id', '!=', (int) $record->id)
            ->where('is_country_capital', true)
            ->update(['is_country_capital' => false]);
    }

    if (Schema::hasColumn('islands', 'is_atoll_capital') && (bool) ($record->is_atoll_capital ?? false)) {
        \App\Models\Island::query()
            ->where('id', '!=', (int) $record->id)
            ->where('atoll_id', (int) $record->atoll_id)
            ->where('is_atoll_capital', true)
            ->update(['is_atoll_capital' => false]);
    }

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
