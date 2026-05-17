<?php

use App\Models\User;
use App\Support\ChannelManagerHealthReport;
use App\Support\ChannelOutboundSyncDispatcher;
use App\Support\ChannelReservationIngestor;
use App\Support\ReservationPricingPolicy;
use App\Support\VendorPortalAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

Route::get('/vendor', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;

    $activePortalPage = strtolower(trim((string) request()->query('page', 'overview')));
    if (!in_array($activePortalPage, ['overview', 'reports', 'profile', 'listings', 'reservations', 'operations', 'availability', 'billing', 'messages', 'engagement', 'promotions', 'distribution', 'setup', 'compliance'], true)) {
        $activePortalPage = 'overview';
    }

    $isOverviewPage = in_array($activePortalPage, ['overview', 'reports'], true);
    $loadListingsHeavyData = $activePortalPage === 'listings';
    $loadRoomInventoryData = in_array($activePortalPage, ['listings', 'reservations', 'operations', 'availability', 'distribution', 'setup'], true);
    $loadEngagementData = in_array($activePortalPage, ['engagement', 'promotions'], true);
    $loadReservationsData = in_array($activePortalPage, ['reservations', 'operations', 'availability', 'billing'], true) || $loadEngagementData;
    $loadAvailabilityData = in_array($activePortalPage, ['availability', 'operations'], true);
    $loadPricingData = $loadEngagementData;
    $loadBillingData = $activePortalPage === 'billing';
    $loadDistributionData = in_array($activePortalPage, ['distribution', 'setup', 'overview', 'operations', 'availability'], true);
    $loadListingsContextData = in_array($activePortalPage, ['listings', 'reservations', 'operations', 'availability', 'engagement', 'promotions', 'distribution', 'setup'], true);
    $vendorPortalCacheTtlSeconds = 900;
    $categoryRouteTokens = array_merge(array_keys(vendorPortalCategoryMap()), ['sea_transport', 'land_transport']);
    $requestedCategoryScope = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    if (!in_array($requestedCategoryScope, $categoryRouteTokens, true)) {
        $requestedCategoryScope = '';
    }

    $vendorCategoryMap = vendorPortalCategoryMap();
    $selectedVendorCategories = vendorPortalSelectedCategories($vendorUser);
    if ($selectedVendorCategories === []) {
        $selectedVendorCategories = ['accommodation'];
    }
    $vendorOnboardingStep = ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_onboarding_step'))
        ? max(1, min(4, (int) ($vendorUser->vendor_onboarding_step ?? 1)))
        : 1;

    $vendorProperties = collect();
    $vendorServices = collect();
    $vendorAvailability = collect();
    $vendorReservations = collect();
    $vendorPricingRules = collect();
    $vendorBilling = null;
    $vendorPayoutAccounts = collect();
    $vendorRoomCategories = collect();
    $vendorRentalItems = collect();
    $vendorMediaAssets = collect();
    $payoutStatusRows = collect();
    $vendorReservationSummaryByProperty = collect();
    $vendorDistribution = [
        'accounts' => collect(),
        'room_mappings' => collect(),
        'recent_events' => collect(),
        'summary' => [
            'connected_channels' => 0,
            'action_required_channels' => 0,
            'mapped_rooms' => 0,
            'failed_events' => 0,
            'pending_events' => 0,
            'last_sync_at' => null,
            'setup_progress' => 0,
            'go_live_ready' => false,
            'readiness_checks' => [],
            'next_step' => 'Connect your first OTA channel to start receiving bookings.',
        ],
    ];
    $vendorAuditTrail = [
        'logs' => collect(),
        'high_severity_logs' => collect(),
        'recent_count' => 0,
        'warn_severity_count' => 0,
        'high_severity_count' => 0,
        'table_ready' => false,
    ];
    $vendorOperationalHealth = [
        'available' => false,
        'status' => 'unavailable',
        'summary' => [
            'accounts_total' => 0,
            'active_accounts' => 0,
            'action_required_accounts' => 0,
            'inbound_failed' => 0,
            'outbound_queued' => 0,
            'outbound_retrying' => 0,
            'dead_letter_events' => 0,
            'stale_accounts' => 0,
        ],
        'issues' => [],
        'stale_threshold_minutes' => 30,
        'top_failed_accounts' => collect(),
    ];
    $vendorEngagement = [
        'inquiries_table' => null,
        'inquiries' => collect(),
        'reviews_table' => null,
        'reviews' => collect(),
        'promotions' => collect(),
        'loyalty_table' => null,
        'loyalty_programs' => collect(),
        'loyal_customers' => collect(),
    ];

    $vendorReservationPolicy = ReservationPricingPolicy::loadPolicy();
    $vendorTaxComponents = collect($vendorReservationPolicy['tax_components'] ?? []);
    $vendorReservationVersion = '0';
    if ($vendorUserId > 0 && Schema::hasTable('vendor_reservations')) {
        $vendorReservationVersion = (string) (DB::table('vendor_reservations')
            ->where('vendor_user_id', $vendorUserId)
            ->max('updated_at') ?? '0');
    }
    $vendorDashboardSnapshot = [
        'listing_total' => 0,
        'listing_active' => 0,
        'pending_reservations' => 0,
        'confirmed_reservations' => 0,
        'completed_reservations' => 0,
        'reservations_count' => 0,
        'gross_collections_total' => 0.0,
        'bookings_today' => 0,
        'revenue_today' => 0.0,
        'has_pricing_rules' => false,
        'has_availability' => false,
        'has_billing' => false,
    ];

    if ($vendorUserId > 0) {
        if ($isOverviewPage || $loadDistributionData || $activePortalPage === 'compliance') {
            $vendorOperationalHealth = ChannelManagerHealthReport::build(30);
        }

        if ($isOverviewPage || $activePortalPage === 'compliance') {
            $vendorAuditTrail['table_ready'] = Schema::hasTable('vendor_portal_audit_logs');
            if ($vendorAuditTrail['table_ready']) {
                $vendorAuditTrail['recent_count'] = (int) DB::table('vendor_portal_audit_logs')
                    ->where('vendor_user_id', $vendorUserId)
                    ->where('created_at', '>=', now()->subDay())
                    ->count();
                $vendorAuditTrail['warn_severity_count'] = (int) DB::table('vendor_portal_audit_logs')
                    ->where('vendor_user_id', $vendorUserId)
                    ->where('created_at', '>=', now()->subDay())
                    ->where('severity', 'warn')
                    ->count();
                $vendorAuditTrail['high_severity_count'] = (int) DB::table('vendor_portal_audit_logs')
                    ->where('vendor_user_id', $vendorUserId)
                    ->where('created_at', '>=', now()->subDay())
                    ->whereIn('severity', ['error', 'critical'])
                    ->count();

                if ($isOverviewPage) {
                    $vendorAuditTrail['high_severity_logs'] = DB::table('vendor_portal_audit_logs')
                        ->where('vendor_user_id', $vendorUserId)
                        ->whereIn('severity', ['error', 'critical'])
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();
                }

                if ($activePortalPage === 'compliance') {
                    $vendorAuditTrail['logs'] = DB::table('vendor_portal_audit_logs')
                        ->where('vendor_user_id', $vendorUserId)
                        ->orderByDesc('created_at')
                        ->limit(20)
                        ->get();
                }
            }
        }

        if ($loadListingsContextData) {
            // Load vendor listings from dedicated category tables only for pages that render listing-level data.
            $vendorProperties = collect(Cache::remember(
                'vendor:portal:listings:v4:' . $vendorUserId . ':' . ($requestedCategoryScope !== '' ? $requestedCategoryScope : 'all'),
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId, $requestedCategoryScope) {
                    return \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId, 200, $requestedCategoryScope !== '' ? $requestedCategoryScope : null)
                        ->values()
                        ->all();
                }
            ));
        }

        $accommodationPropertyIds = $vendorProperties
            ->filter(static fn ($property) => vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')) === 'accommodation')
            ->flatMap(static fn ($property) => [
                (int) ($property->id ?? 0),
                (int) ($property->dedicated_row_id ?? 0),
                (int) ($property->vendor_property_id ?? 0),
            ])
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($loadListingsHeavyData && $accommodationPropertyIds->isNotEmpty()) {
            $transferRowsByPropertyId = collect();
            $featureRowsByPropertyId = collect();
            $policyRowsByPropertyId = collect();

            if (Schema::hasTable('vendor_accommodation_transfer_rates')) {
                $transferRowsByPropertyId = DB::table('vendor_accommodation_transfer_rates')
                    ->whereIn('vendor_property_id', $accommodationPropertyIds->all())
                    ->get(['vendor_property_id', 'transfer_mode', 'resident_type', 'passenger_type', 'rate', 'base_charge'])
                    ->groupBy(static fn ($row) => (int) ($row->vendor_property_id ?? 0));
            }

            if (Schema::hasTable('vendor_accommodation_features')) {
                $featureRowsByPropertyId = DB::table('vendor_accommodation_features')
                    ->whereIn('vendor_property_id', $accommodationPropertyIds->all())
                    ->get(['vendor_property_id', 'feature_type', 'feature_key'])
                    ->groupBy(static fn ($row) => (int) ($row->vendor_property_id ?? 0));
            }

            if (Schema::hasTable('vendor_accommodation_policies')) {
                $policyRowsByPropertyId = DB::table('vendor_accommodation_policies')
                    ->whereIn('vendor_property_id', $accommodationPropertyIds->all())
                    ->get()
                    ->keyBy(static fn ($row) => (int) ($row->vendor_property_id ?? 0));
            }

            $vendorProperties = $vendorProperties->map(static function ($property) use ($transferRowsByPropertyId, $featureRowsByPropertyId, $policyRowsByPropertyId) {
                if (vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')) !== 'accommodation') {
                    return $property;
                }

                $details = [];
                if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
                    $decoded = json_decode((string) $property->listing_details, true);
                    if (is_array($decoded)) {
                        $details = $decoded;
                    }
                }

                $lookupIds = array_values(array_unique(array_filter([
                    (int) ($property->id ?? 0),
                    (int) ($property->dedicated_row_id ?? 0),
                    (int) ($property->vendor_property_id ?? 0),
                ], static fn (int $id): bool => $id > 0)));

                $transferRows = collect();
                $featureRows = collect();
                $policyRow = null;

                foreach ($lookupIds as $lookupId) {
                    if ($transferRows->isEmpty()) {
                        $transferRows = collect($transferRowsByPropertyId->get($lookupId, collect()));
                    }
                    if ($featureRows->isEmpty()) {
                        $featureRows = collect($featureRowsByPropertyId->get($lookupId, collect()));
                    }
                    if ($policyRow === null) {
                        $policyRow = $policyRowsByPropertyId->get($lookupId);
                    }
                }

                if ($transferRows->isNotEmpty()) {
                    $transferOptions = [];
                    $transferRates = [];
                    $transferRateMatrix = [];
                    $transferBaseLocal = 0.0;
                    $transferBaseForeign = 0.0;

                    foreach ($transferRows as $transferRow) {
                        $mode = strtolower(trim((string) ($transferRow->transfer_mode ?? '')));
                        $residentType = strtolower(trim((string) ($transferRow->resident_type ?? '')));
                        $passengerType = strtolower(trim((string) ($transferRow->passenger_type ?? '')));
                        $rate = is_numeric($transferRow->rate ?? null) ? (float) $transferRow->rate : 0.0;
                        $baseCharge = is_numeric($transferRow->base_charge ?? null) ? (float) $transferRow->base_charge : 0.0;

                        if ($mode === '') {
                            continue;
                        }

                        $transferOptions[$mode] = true;
                        if (!isset($transferRateMatrix[$mode])) {
                            $transferRateMatrix[$mode] = [
                                'local_adult_charge' => 0.0,
                                'local_child_charge' => 0.0,
                                'foreign_adult_charge' => 0.0,
                                'foreign_child_charge' => 0.0,
                            ];
                        }

                        if ($residentType === 'local' && $passengerType === 'adult') {
                            $transferRateMatrix[$mode]['local_adult_charge'] = max(0, $rate);
                        } elseif ($residentType === 'local' && $passengerType === 'child') {
                            $transferRateMatrix[$mode]['local_child_charge'] = max(0, $rate);
                        } elseif ($residentType === 'foreigner' && $passengerType === 'adult') {
                            $transferRateMatrix[$mode]['foreign_adult_charge'] = max(0, $rate);
                            $transferRates[$mode] = max(0, $rate);
                        } elseif ($residentType === 'foreigner' && $passengerType === 'child') {
                            $transferRateMatrix[$mode]['foreign_child_charge'] = max(0, $rate);
                        }

                        if ($residentType === 'local') {
                            $transferBaseLocal = max($transferBaseLocal, max(0, $baseCharge));
                        } elseif ($residentType === 'foreigner') {
                            $transferBaseForeign = max($transferBaseForeign, max(0, $baseCharge));
                        }
                    }

                    $details['transfer_options'] = array_values(array_keys($transferOptions));
                    $details['transfer_rates'] = $transferRates;
                    $details['transfer_rate_matrix'] = $transferRateMatrix;
                    $details['transfer_base_local'] = $transferBaseLocal;
                    $details['transfer_base_foreign'] = $transferBaseForeign;
                }

                if ($featureRows->isNotEmpty()) {
                    $amenities = [];
                    $facilities = [];
                    foreach ($featureRows as $featureRow) {
                        $featureType = strtolower(trim((string) ($featureRow->feature_type ?? '')));
                        $featureKey = trim((string) ($featureRow->feature_key ?? ''));
                        if ($featureKey === '') {
                            continue;
                        }

                        if ($featureType === 'amenity') {
                            $amenities[] = $featureKey;
                        } elseif ($featureType === 'facility') {
                            $facilities[] = $featureKey;
                        }
                    }

                    $details['property_amenities'] = array_values(array_unique($amenities));
                    $details['property_features'] = array_values(array_unique($facilities));
                }

                if ($policyRow) {
                    $details['check_in_time'] = trim((string) ($policyRow->check_in_time ?? ($details['check_in_time'] ?? '')));
                    $details['check_out_time'] = trim((string) ($policyRow->check_out_time ?? ($details['check_out_time'] ?? '')));
                    $details['check_in_grace_minutes'] = is_numeric($policyRow->check_in_grace_minutes ?? null) ? (int) $policyRow->check_in_grace_minutes : ($details['check_in_grace_minutes'] ?? null);
                    $details['early_check_in_allowed'] = trim((string) ($policyRow->early_check_in_allowed ?? ($details['early_check_in_allowed'] ?? '')));
                    $details['late_check_out_allowed'] = trim((string) ($policyRow->late_check_out_allowed ?? ($details['late_check_out_allowed'] ?? '')));
                    $details['minimum_nights'] = is_numeric($policyRow->minimum_nights ?? null) ? (int) $policyRow->minimum_nights : ($details['minimum_nights'] ?? null);
                    $details['house_rules'] = trim((string) ($policyRow->house_rules ?? ($details['house_rules'] ?? '')));
                    $details['child_policy'] = trim((string) ($policyRow->child_policy ?? ($details['child_policy'] ?? '')));
                    $details['cancellation_policy'] = trim((string) ($policyRow->cancellation_policy ?? ($details['cancellation_policy'] ?? '')));
                    $details['early_check_in_fee'] = is_numeric($policyRow->early_check_in_fee ?? null) ? (float) $policyRow->early_check_in_fee : ($details['early_check_in_fee'] ?? null);
                    $details['late_check_out_fee'] = is_numeric($policyRow->late_check_out_fee ?? null) ? (float) $policyRow->late_check_out_fee : ($details['late_check_out_fee'] ?? null);
                    $details['property_type'] = trim((string) ($policyRow->property_type ?? ($details['property_type'] ?? '')));
                    $details['star_rating'] = is_numeric($policyRow->star_rating ?? null) ? (int) $policyRow->star_rating : ($details['star_rating'] ?? null);
                }

                $property->listing_details = json_encode($details);

                return $property;
            })->values();
        }

        $existingListingCategories = $vendorProperties
            ->map(static fn ($property) => vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')))
            ->filter(static fn ($category) => is_string($category) && $category !== '')
            ->values()
            ->all();
        if ($existingListingCategories !== []) {
            $selectedVendorCategories = array_values(array_unique(array_merge($selectedVendorCategories, $existingListingCategories)));
        }

        $vendorDashboardSnapshot['listing_total'] = (int) $vendorProperties->count();
        $vendorDashboardSnapshot['listing_active'] = (int) $vendorProperties
            ->filter(static fn ($property) => strtolower(trim((string) ($property->status ?? 'active'))) === 'active')
            ->count();

        if ($loadListingsContextData && Schema::hasTable('vendor_services')) {
            $serviceLimit = $loadListingsHeavyData ? 250 : 80;
            $vendorServices = collect(Cache::remember(
                'vendor:portal:services:v3:' . $vendorUserId . ':' . $serviceLimit . ':' . ($requestedCategoryScope !== '' ? $requestedCategoryScope : 'all'),
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId, $serviceLimit, $requestedCategoryScope) {
                    $query = DB::table('vendor_services')
                        ->where('vendor_user_id', $vendorUserId)
                        ->orderByDesc('updated_at');

                    if ($requestedCategoryScope !== '' && Schema::hasColumn('vendor_services', 'listing_category')) {
                        $query->where('listing_category', $requestedCategoryScope);
                    }

                    return $query->limit($serviceLimit)->get()->all();
                }
            ));

            $existingServiceCategories = $vendorServices
                ->map(static fn ($service) => vendorPortalCanonicalCategory((string) ($service->listing_category ?? '')))
                ->filter(static fn ($category) => is_string($category) && $category !== '')
                ->values()
                ->all();
            if ($existingServiceCategories !== []) {
                $selectedVendorCategories = array_values(array_unique(array_merge($selectedVendorCategories, $existingServiceCategories)));
            }
        }

        // Keep overview metrics accurate even when listing collections are intentionally skipped.
        $vendorListingCountFromDb = 0;
        $vendorActiveListingCountFromDb = 0;
        $vendorPropertiesHasStatusColumn = Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'status');
        $vendorServicesHasStatusColumn = Schema::hasTable('vendor_services') && Schema::hasColumn('vendor_services', 'status');
        if (Schema::hasTable('vendor_properties')) {
            $vendorListingCountFromDb += (int) DB::table('vendor_properties')
                ->where('vendor_user_id', $vendorUserId)
                ->count();
            $vendorPropertiesActiveQuery = DB::table('vendor_properties')
                ->where('vendor_user_id', $vendorUserId);
            if ($vendorPropertiesHasStatusColumn) {
                $vendorPropertiesActiveQuery->whereRaw("LOWER(TRIM(COALESCE(status, 'active'))) = 'active'");
            }
            $vendorActiveListingCountFromDb += (int) $vendorPropertiesActiveQuery->count();
        }
        if (Schema::hasTable('vendor_services')) {
            $vendorListingCountFromDb += (int) DB::table('vendor_services')
                ->where('vendor_user_id', $vendorUserId)
                ->count();
            $vendorServicesActiveQuery = DB::table('vendor_services')
                ->where('vendor_user_id', $vendorUserId);
            if ($vendorServicesHasStatusColumn) {
                $vendorServicesActiveQuery->whereRaw("LOWER(TRIM(COALESCE(status, 'active'))) = 'active'");
            }
            $vendorActiveListingCountFromDb += (int) $vendorServicesActiveQuery->count();
        }
        $vendorDashboardSnapshot['listing_total'] = max((int) ($vendorDashboardSnapshot['listing_total'] ?? 0), $vendorListingCountFromDb);
        $vendorDashboardSnapshot['listing_active'] = max((int) ($vendorDashboardSnapshot['listing_active'] ?? 0), $vendorActiveListingCountFromDb);

        $vendorDashboardSnapshot = Cache::remember(
            'vendor:portal:snapshot:v2:' . $vendorUserId . ':' . $vendorReservationVersion,
            now()->addSeconds($vendorPortalCacheTtlSeconds),
            static function () use ($vendorUserId, $vendorDashboardSnapshot): array {
                $snapshot = $vendorDashboardSnapshot;

                if (Schema::hasTable('vendor_reservations')) {
                    $aggregate = DB::table('vendor_reservations')
                        ->where('vendor_user_id', $vendorUserId)
                        ->selectRaw('COUNT(*) as reservations_count')
                        ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'pending' THEN 1 ELSE 0 END) as pending_reservations")
                        ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN ('confirmed', 'upcoming') THEN 1 ELSE 0 END) as confirmed_reservations")
                        ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'completed' THEN 1 ELSE 0 END) as completed_reservations")
                        ->selectRaw('SUM(COALESCE(invoice_total_amount, total_amount, 0)) as gross_collections_total')
                        ->first();

                    $snapshot['reservations_count'] = (int) ($aggregate->reservations_count ?? 0);
                    $snapshot['pending_reservations'] = (int) ($aggregate->pending_reservations ?? 0);
                    $snapshot['confirmed_reservations'] = (int) ($aggregate->confirmed_reservations ?? 0);
                    $snapshot['completed_reservations'] = (int) ($aggregate->completed_reservations ?? 0);
                    $snapshot['gross_collections_total'] = (float) ($aggregate->gross_collections_total ?? 0);

                    $todayAggregate = DB::table('vendor_reservations')
                        ->where('vendor_user_id', $vendorUserId)
                        ->whereDate('created_at', now()->toDateString())
                        ->selectRaw('COUNT(*) as bookings_today')
                        ->selectRaw('SUM(COALESCE(invoice_total_amount, total_amount, 0)) as revenue_today')
                        ->first();

                    $snapshot['bookings_today'] = (int) ($todayAggregate->bookings_today ?? 0);
                    $snapshot['revenue_today'] = (float) ($todayAggregate->revenue_today ?? 0);
                }

                $snapshot['has_pricing_rules'] = Schema::hasTable('vendor_pricing_rules')
                    ? DB::table('vendor_pricing_rules')->where('vendor_user_id', $vendorUserId)->exists()
                    : false;
                $snapshot['has_availability'] = Schema::hasTable('vendor_availability_slots')
                    ? DB::table('vendor_availability_slots')->where('vendor_user_id', $vendorUserId)->exists()
                    : false;
                $snapshot['has_billing'] = Schema::hasTable('vendor_billing_details')
                    ? DB::table('vendor_billing_details')->where('vendor_user_id', $vendorUserId)->exists()
                    : false;

                return $snapshot;
            }
        );

        if ($loadListingsContextData && $vendorProperties->isNotEmpty() && Schema::hasTable('vendor_reservations')) {
            $vendorReservationSummaryByProperty = collect(Cache::remember(
                'vendor:portal:reservation-summary-by-property:v1:' . $vendorUserId . ':' . $vendorReservationVersion,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId) {
                    return DB::table('vendor_reservations')
                        ->where('vendor_user_id', $vendorUserId)
                        ->whereNotNull('vendor_property_id')
                        ->selectRaw('vendor_property_id')
                        ->selectRaw('COUNT(*) as reservations_count')
                        ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN ('confirmed', 'upcoming') THEN 1 ELSE 0 END) as confirmed_count")
                        ->selectRaw('SUM(COALESCE(invoice_total_amount, total_amount, 0)) as gross_total')
                        ->groupBy('vendor_property_id')
                        ->get()
                        ->keyBy(static fn ($row) => (int) ($row->vendor_property_id ?? 0))
                        ->all();
                }
            ));
        }

        if ($loadAvailabilityData && Schema::hasTable('vendor_availability_slots')) {
            $availabilityLimit = $activePortalPage === 'availability' ? 365 : 60;
            $vendorAvailability = collect(Cache::remember(
                'vendor:portal:availability:v2:' . $vendorUserId . ':' . $availabilityLimit,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId, $availabilityLimit) {
                    return DB::table('vendor_availability_slots')
                        ->where('vendor_user_id', $vendorUserId)
                        ->orderBy('slot_date')
                        ->limit($availabilityLimit)
                        ->get()
                        ->all();
                }
            ));
        }

        if ($loadReservationsData && Schema::hasTable('vendor_reservations')) {
            $reservationLimit = $loadEngagementData
                ? 300
                : (($activePortalPage === 'billing' || $activePortalPage === 'reservations' || $activePortalPage === 'operations') ? 600 : 120);
            $vendorReservations = collect(Cache::remember(
                'vendor:portal:reservations:v2:' . $vendorUserId . ':' . $reservationLimit . ':' . $vendorReservationVersion,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId, $reservationLimit) {
                    $reservationQuery = DB::table('vendor_reservations')
                        ->where(static function ($ownershipQuery) use ($vendorUserId) {
                            $ownershipQuery->where('vendor_user_id', $vendorUserId);

                            if (Schema::hasTable('vendor_accommodation_listings')) {
                                $ownedPropertyIds = DB::table('vendor_accommodation_listings')
                                    ->where('vendor_user_id', $vendorUserId)
                                    ->pluck('vendor_property_id')
                                    ->map(static fn ($id): int => (int) $id)
                                    ->filter(static fn (int $id): bool => $id > 0)
                                    ->values()
                                    ->all();

                                if (!empty($ownedPropertyIds)) {
                                    $ownershipQuery->orWhereIn('vendor_property_id', $ownedPropertyIds);
                                }
                            }
                        })
                        ->orderByDesc('start_at')
                        ->orderByDesc('id')
                        ->limit($reservationLimit);

                    return $reservationQuery
                        ->get()
                        ->all();
                }
            ));

            $latestRefundCaseByReservation = collect();
            $reservationIds = $vendorReservations
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values();

            if ($reservationIds->isNotEmpty() && Schema::hasTable('finance_refund_cases')) {
                $latestRefundCaseByReservation = DB::table('finance_refund_cases')
                    ->whereIn('reservation_id', $reservationIds->all())
                    ->orderByDesc('id')
                    ->get([
                        'id',
                        'reservation_id',
                        'case_ref',
                        'status',
                        'created_at',
                        'review_started_at',
                        'approved_at',
                        'completed_at',
                        'rejected_at',
                        'sla_due_at',
                        'sla_escalated_at',
                    ])
                    ->unique('reservation_id')
                    ->keyBy('reservation_id');
            }

            $vendorReservations = $vendorReservations->map(static function ($reservation) use ($latestRefundCaseByReservation) {
                $reservationId = (int) ($reservation->id ?? 0);
                $refundCase = $latestRefundCaseByReservation->get($reservationId);
                $refundStatus = strtolower(trim((string) ($refundCase->status ?? '')));
                $hasOpenRefundTimeline = in_array($refundStatus, ['requested', 'under_review', 'approved'], true);

                $reservation->refund_case_ref = (string) ($refundCase->case_ref ?? '');
                $reservation->refund_status = (string) ($refundCase->status ?? '');
                $reservation->refund_requested_at = (string) ($refundCase->created_at ?? '');
                $reservation->refund_review_started_at = (string) ($refundCase->review_started_at ?? '');
                $reservation->refund_approved_at = (string) ($refundCase->approved_at ?? '');
                $reservation->refund_completed_at = (string) ($refundCase->completed_at ?? '');
                $reservation->refund_rejected_at = (string) ($refundCase->rejected_at ?? '');
                $reservation->refund_sla_due_at = (string) ($refundCase->sla_due_at ?? '');
                $reservation->refund_sla_escalated_at = (string) ($refundCase->sla_escalated_at ?? '');
                $reservation->has_refund_case = $hasOpenRefundTimeline || (bool) ($reservation->has_refund_case ?? false);

                return $reservation;
            })->filter(static function ($reservation): bool {
                $notes = json_decode((string) ($reservation->notes ?? ''), true);
                if (!is_array($notes)) {
                    return true;
                }

                return trim((string) ($notes['vendor_deleted_at'] ?? '')) === '';
            })->values();

            $propertyNameById = $vendorProperties
                ->keyBy(static fn ($property): int => (int) ($property->id ?? 0))
                ->map(static fn ($property): string => trim((string) ($property->name ?? '')));
            $serviceNameById = $vendorServices
                ->keyBy(static fn ($service): int => (int) ($service->id ?? 0))
                ->map(static fn ($service): string => trim((string) ($service->title ?? $service->name ?? '')));

            // Apply category filter to payout rows when a category tab is active.
            $payoutReservationsSource = $vendorReservations;
            if ($requestedCategoryScope !== '') {
                $propertyCategorySource = $vendorProperties;
                if ($propertyCategorySource->isEmpty()) {
                    $propertyCategorySource = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId, 500, null);
                }

                $propertyCategoryById = $propertyCategorySource
                    ->keyBy(static fn ($p): int => (int) ($p->id ?? 0))
                    ->map(static fn ($p): string => vendorPortalCanonicalCategory((string) ($p->listing_category ?? '')));
                $payoutReservationsSource = $vendorReservations->filter(static function ($reservation) use ($requestedCategoryScope, $propertyCategoryById): bool {
                    $notes = json_decode((string) ($reservation->notes ?? ''), true);
                    $notes = is_array($notes) ? $notes : [];
                    $cat = vendorPortalCanonicalCategory((string) ($notes['category_key'] ?? $notes['listing_category'] ?? ''));
                    if (is_string($cat) && $cat !== '') {
                        return $cat === $requestedCategoryScope;
                    }
                    $propId = (int) ($reservation->vendor_property_id ?? 0);
                    if ($propId > 0) {
                        $propCat = $propertyCategoryById->get($propId, '');
                        return is_string($propCat) && $propCat === $requestedCategoryScope;
                    }
                    return false;
                })->values();
            }

            $payoutStatusRows = $payoutReservationsSource
                ->filter(static function ($reservation): bool {
                    $status = strtolower(trim((string) ($reservation->payment_status ?? '')));
                    $payoutStatus = strtolower(trim((string) ($reservation->payout_status ?? '')));
                    return $status === 'paid' || $payoutStatus !== '';
                })
                ->map(function ($reservation) use ($propertyNameById, $serviceNameById) {
                    $notes = json_decode((string) ($reservation->notes ?? ''), true);
                    $notes = is_array($notes) ? $notes : [];

                    $checkIn = (string) ($reservation->start_at ?? '');
                    $checkOut = (string) ($reservation->end_at ?? '');
                    $propertyId = (int) ($reservation->vendor_property_id ?? 0);
                    $serviceId = (int) ($reservation->vendor_service_id ?? 0);

                    $roomRef = trim((string) ($notes['room_id'] ?? ''));
                    $roomName = trim((string) ($notes['room_name'] ?? ''));
                    $serviceLabel = trim((string) ($notes['service_label'] ?? ''));
                    $propertyName = trim((string) ($propertyNameById->get($propertyId, '')));
                    $serviceName = trim((string) ($serviceNameById->get($serviceId, '')));

                    $bookingLabel = $roomName !== '' ? $roomName : ($serviceLabel !== '' ? $serviceLabel : ($serviceName !== '' ? $serviceName : ($propertyName !== '' ? $propertyName : 'Reservation #' . (int) ($reservation->id ?? 0))));
                    $bookingRef = $roomRef !== '' ? ('Room #' . $roomRef) : ('Booking #' . (int) ($reservation->id ?? 0));

                    $stayNights = null;
                    if ($checkIn !== '' && $checkOut !== '') {
                        try {
                            $stayNights = max(1, \Illuminate\Support\Carbon::parse($checkIn)->diffInDays(\Illuminate\Support\Carbon::parse($checkOut)));
                        } catch (\Throwable $exception) {
                            $stayNights = null;
                        }
                    }

                    return (object) [
                        'id' => (int) ($reservation->id ?? 0),
                        'reservation_code' => 'RSV-' . str_pad((string) ((int) ($reservation->id ?? 0)), 6, '0', STR_PAD_LEFT),
                        'booking_ref' => $bookingRef,
                        'booking_label' => $bookingLabel,
                        'property_name' => $propertyName,
                        'service_or_room' => $roomName !== '' ? $roomName : ($serviceLabel !== '' ? $serviceLabel : ($serviceName !== '' ? $serviceName : '—')),
                        'check_in' => $checkIn !== '' ? substr($checkIn, 0, 10) : '—',
                        'check_out' => $checkOut !== '' ? substr($checkOut, 0, 10) : '—',
                        'stay_nights' => $stayNights,
                        'payout_status' => strtolower(trim((string) ($reservation->payout_status ?? 'queued'))),
                        'payout_currency' => strtoupper(trim((string) ($reservation->payout_currency ?? $reservation->currency ?? 'MVR'))),
                        'payment_currency' => strtoupper(trim((string) ($reservation->payment_currency ?? $reservation->currency ?? 'MVR'))),
                        'payment_status' => strtoupper(trim((string) ($reservation->payment_status ?? 'unpaid'))),
                        'vendor_payout_amount' => (float) ($reservation->vendor_payout_amount ?? 0),
                        'booking_created_at' => (string) ($reservation->created_at ?? null),
                        'payment_collected_at' => (string) ($reservation->payment_collected_at ?? $reservation->payment_verified_at ?? null),
                        'payout_processing_at' => (string) ($reservation->payout_processing_at ?? null),
                        'payout_expected_at' => (string) ($reservation->payout_expected_at ?? null),
                        'payout_paid_at' => (string) ($reservation->payout_paid_at ?? null),
                        'has_open_dispute' => (bool) ($reservation->has_open_dispute ?? in_array(strtolower((string) ($notes['dispute_status'] ?? '')), ['open', 'under_review', 'processing'], true)),
                        'has_refund_case' => (bool) ($reservation->has_refund_case ?? in_array(strtolower((string) ($notes['refund_status'] ?? '')), ['requested', 'under_review', 'approved', 'processing'], true)),
                    ];
                })
                ->sortByDesc(static fn ($row) => strtotime((string) ($row->payment_collected_at ?? '')) ?: 0)
                ->values();
        }

        if ($loadPricingData && Schema::hasTable('vendor_pricing_rules')) {
            $pricingLimit = $activePortalPage === 'pricing' ? 200 : 80;
            $vendorPricingRules = collect(Cache::remember(
                'vendor:portal:pricing-rules:v2:' . $vendorUserId . ':' . $pricingLimit,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId, $pricingLimit) {
                    return DB::table('vendor_pricing_rules')
                        ->where('vendor_user_id', $vendorUserId)
                        ->orderByDesc('updated_at')
                        ->limit($pricingLimit)
                        ->get()
                        ->all();
                }
            ));
        }

        if ($loadBillingData && Schema::hasTable('vendor_billing_details')) {
            $vendorBilling = Cache::remember(
                'vendor:portal:billing:v2:' . $vendorUserId,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId) {
                    return DB::table('vendor_billing_details')
                        ->where('vendor_user_id', $vendorUserId)
                        ->first();
                }
            );
        }

        if ($loadBillingData && Schema::hasTable('vendor_payout_accounts')) {
            $vendorPayoutAccounts = collect(Cache::remember(
                'vendor:portal:payout-accounts:v1:' . $vendorUserId,
                now()->addSeconds($vendorPortalCacheTtlSeconds),
                static function () use ($vendorUserId) {
                    return DB::table('vendor_payout_accounts')
                        ->where('vendor_user_id', $vendorUserId)
                        ->orderByDesc('is_primary')
                        ->orderByDesc('updated_at')
                        ->get()
                        ->all();
                }
            ));
        }

        $vendorPropertyIds = $vendorProperties
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($loadRoomInventoryData && Schema::hasTable('vendor_property_room_categories')) {
            $roomCategoryQuery = DB::table('vendor_property_room_categories')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at');

            if ($vendorPropertyIds->isNotEmpty()) {
                $roomCategoryQuery->whereIn('vendor_property_id', $vendorPropertyIds->all());
            }

            $vendorRoomCategories = $roomCategoryQuery->limit(200)->get();
        }

        if ($loadRoomInventoryData && Schema::hasTable('vendor_water_sports_rental_items')) {
            $rentalItemQuery = DB::table('vendor_water_sports_rental_items')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at');

            if ($vendorPropertyIds->isNotEmpty()) {
                $rentalItemQuery->whereIn('vendor_property_id', $vendorPropertyIds->all());
            }

            $vendorRentalItems = $rentalItemQuery->limit(400)->get();
        }

        if ($loadListingsHeavyData && Schema::hasTable('vendor_listing_media')) {
            $roomIds = $vendorRoomCategories
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values();

            $vendorMediaQuery = DB::table('vendor_listing_media')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('created_at');

            if ($vendorPropertyIds->isNotEmpty() || $roomIds->isNotEmpty()) {
                $vendorMediaQuery->where(function ($query) use ($vendorPropertyIds, $roomIds) {
                    $hasCondition = false;

                    if ($vendorPropertyIds->isNotEmpty()) {
                        $query->where(function ($propertyQuery) use ($vendorPropertyIds) {
                            $propertyQuery->whereIn('entity_type', ['property', 'sea_transport', 'transport'])
                                ->whereIn('entity_id', $vendorPropertyIds->all());
                        });
                        $hasCondition = true;
                    }

                    if ($roomIds->isNotEmpty()) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $query->{$method}(function ($roomQuery) use ($roomIds) {
                            $roomQuery->where('entity_type', 'room')
                                ->whereIn('entity_id', $roomIds->all());
                        });
                    }
                });
            }

            $vendorMediaAssets = $vendorMediaQuery->limit(200)->get();
        }

        if ($loadEngagementData) {
            $reviewTableCandidates = ['vendor_property_reviews', 'vendor_reviews', 'customer_reviews', 'property_reviews'];
            foreach ($reviewTableCandidates as $reviewTable) {
                if (!Schema::hasTable($reviewTable)) {
                    continue;
                }

                $columns = Schema::getColumnListing($reviewTable);
                $idColumn = collect(['id', 'review_id'])->first(static fn ($column) => in_array($column, $columns, true));
                if ($idColumn === null) {
                    continue;
                }

                $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
                $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
                $dateColumn = collect(['created_at', 'reviewed_at', 'updated_at'])->first(static fn ($column) => in_array($column, $columns, true));

                $query = DB::table($reviewTable);
                if ($vendorColumn !== null) {
                    $query->where($vendorColumn, $vendorUserId);
                } elseif ($propertyColumn !== null && $vendorPropertyIds->isNotEmpty()) {
                    $query->whereIn($propertyColumn, $vendorPropertyIds->all());
                } else {
                    continue;
                }

                if ($dateColumn !== null) {
                    $query->orderByDesc($dateColumn);
                }

                $ratingColumn = collect(['rating', 'score', 'review_score'])->first(static fn ($column) => in_array($column, $columns, true));
                $titleColumn = collect(['title', 'subject', 'headline'])->first(static fn ($column) => in_array($column, $columns, true));
                $commentColumn = collect(['comment', 'review_text', 'message', 'body'])->first(static fn ($column) => in_array($column, $columns, true));
                $statusColumn = collect(['status', 'review_status'])->first(static fn ($column) => in_array($column, $columns, true));
                $nameColumn = collect(['customer_name', 'guest_name', 'reviewer_name', 'name'])->first(static fn ($column) => in_array($column, $columns, true));
                $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response'])->first(static fn ($column) => in_array($column, $columns, true));

                $selectColumns = collect([$idColumn, $propertyColumn, $ratingColumn, $titleColumn, $commentColumn, $statusColumn, $nameColumn, $responseColumn, $dateColumn])
                    ->filter(static fn ($column) => is_string($column) && $column !== '')
                    ->unique()
                    ->values()
                    ->all();

                $rows = $query->limit(80)->get($selectColumns);
                if ($rows->isEmpty()) {
                    continue;
                }

                $vendorEngagement['reviews_table'] = $reviewTable;
                $vendorEngagement['reviews'] = $rows->map(static function ($row) use ($idColumn, $propertyColumn, $ratingColumn, $titleColumn, $commentColumn, $statusColumn, $nameColumn, $responseColumn, $dateColumn) {
                    return [
                        'id' => (int) (($row->{$idColumn} ?? 0) ?: 0),
                        'vendor_property_id' => $propertyColumn ? (int) (($row->{$propertyColumn} ?? 0) ?: 0) : 0,
                        'rating' => $ratingColumn ? (float) (($row->{$ratingColumn} ?? 0) ?: 0) : 0,
                        'title' => trim((string) ($titleColumn ? ($row->{$titleColumn} ?? '') : '')),
                        'comment' => trim((string) ($commentColumn ? ($row->{$commentColumn} ?? '') : '')),
                        'status' => strtolower(trim((string) ($statusColumn ? ($row->{$statusColumn} ?? 'pending') : 'pending'))),
                        'customer_name' => trim((string) ($nameColumn ? ($row->{$nameColumn} ?? 'Guest') : 'Guest')),
                        'response' => trim((string) ($responseColumn ? ($row->{$responseColumn} ?? '') : '')),
                        'created_at' => trim((string) ($dateColumn ? ($row->{$dateColumn} ?? '') : '')),
                    ];
                })->values();

                break;
            }

            $inquiryTableCandidates = ['vendor_customer_inquiries', 'vendor_inquiries', 'customer_inquiries', 'vendor_messages'];
            foreach ($inquiryTableCandidates as $inquiryTable) {
                if (!Schema::hasTable($inquiryTable)) {
                    continue;
                }

                $columns = Schema::getColumnListing($inquiryTable);
                $idColumn = collect(['id', 'inquiry_id', 'message_id'])->first(static fn ($column) => in_array($column, $columns, true));
                if ($idColumn === null) {
                    continue;
                }

                $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
                $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
                $dateColumn = collect(['created_at', 'submitted_at', 'sent_at', 'updated_at'])->first(static fn ($column) => in_array($column, $columns, true));

                $query = DB::table($inquiryTable);
                if ($vendorColumn !== null) {
                    $query->where($vendorColumn, $vendorUserId);
                } elseif ($propertyColumn !== null && $vendorPropertyIds->isNotEmpty()) {
                    $query->whereIn($propertyColumn, $vendorPropertyIds->all());
                } else {
                    continue;
                }

                if ($dateColumn !== null) {
                    $query->orderByDesc($dateColumn);
                }

                $subjectColumn = collect(['subject', 'topic', 'title'])->first(static fn ($column) => in_array($column, $columns, true));
                $messageColumn = collect(['message', 'body', 'content', 'inquiry_text'])->first(static fn ($column) => in_array($column, $columns, true));
                $statusColumn = collect(['status', 'inquiry_status', 'state'])->first(static fn ($column) => in_array($column, $columns, true));
                $nameColumn = collect(['customer_name', 'guest_name', 'sender_name', 'name'])->first(static fn ($column) => in_array($column, $columns, true));
                $emailColumn = collect(['customer_email', 'guest_email', 'sender_email', 'email'])->first(static fn ($column) => in_array($column, $columns, true));
                $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response', 'resolution_note'])->first(static fn ($column) => in_array($column, $columns, true));
                $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));

                $selectColumns = collect([$idColumn, $propertyColumn, $subjectColumn, $messageColumn, $statusColumn, $nameColumn, $emailColumn, $responseColumn, $respondedAtColumn, $dateColumn])
                    ->filter(static fn ($column) => is_string($column) && $column !== '')
                    ->unique()
                    ->values()
                    ->all();

                $rows = $query->limit(100)->get($selectColumns);
                if ($rows->isEmpty()) {
                    continue;
                }

                $vendorEngagement['inquiries_table'] = $inquiryTable;
                $vendorEngagement['inquiries'] = $rows->map(static function ($row) use ($idColumn, $propertyColumn, $subjectColumn, $messageColumn, $statusColumn, $nameColumn, $emailColumn, $responseColumn, $respondedAtColumn, $dateColumn) {
                    return [
                        'id' => (int) (($row->{$idColumn} ?? 0) ?: 0),
                        'vendor_property_id' => $propertyColumn ? (int) (($row->{$propertyColumn} ?? 0) ?: 0) : 0,
                        'subject' => trim((string) ($subjectColumn ? ($row->{$subjectColumn} ?? '') : '')),
                        'message' => trim((string) ($messageColumn ? ($row->{$messageColumn} ?? '') : '')),
                        'status' => strtolower(trim((string) ($statusColumn ? ($row->{$statusColumn} ?? 'open') : 'open'))),
                        'customer_name' => trim((string) ($nameColumn ? ($row->{$nameColumn} ?? 'Guest') : 'Guest')),
                        'customer_email' => trim((string) ($emailColumn ? ($row->{$emailColumn} ?? '') : '')),
                        'response' => trim((string) ($responseColumn ? ($row->{$responseColumn} ?? '') : '')),
                        'responded_at' => trim((string) ($respondedAtColumn ? ($row->{$respondedAtColumn} ?? '') : '')),
                        'created_at' => trim((string) ($dateColumn ? ($row->{$dateColumn} ?? '') : '')),
                    ];
                })->values();

                break;
            }

            $vendorEngagement['promotions'] = $vendorPricingRules
                ->filter(static fn ($rule) => in_array(strtolower(trim((string) ($rule->rule_type ?? ''))), ['promo_discount', 'demand_discount', 'weekend_markup'], true))
                ->map(static function ($rule) {
                    return [
                        'id' => (int) ($rule->id ?? 0),
                        'name' => trim((string) ($rule->name ?? 'Promotion Rule')),
                        'rule_type' => strtolower(trim((string) ($rule->rule_type ?? 'promo_discount'))),
                        'value' => (float) ($rule->value ?? 0),
                        'is_active' => (bool) ($rule->is_active ?? true),
                        'starts_on' => (string) ($rule->starts_on ?? ''),
                        'ends_on' => (string) ($rule->ends_on ?? ''),
                    ];
                })
                ->sortByDesc('id')
                ->take(20)
                ->values();

            $loyaltyTableCandidates = ['vendor_loyalty_programs', 'vendor_loyalty_tiers', 'vendor_loyalty_configs'];
            foreach ($loyaltyTableCandidates as $loyaltyTable) {
                if (!Schema::hasTable($loyaltyTable)) {
                    continue;
                }

                $columns = Schema::getColumnListing($loyaltyTable);
                $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
                if ($vendorColumn === null) {
                    continue;
                }

                $nameColumn = collect(['name', 'program_name', 'tier_name', 'title'])->first(static fn ($column) => in_array($column, $columns, true));
                $pointsColumn = collect(['points_per_booking', 'points_rate', 'points_multiplier'])->first(static fn ($column) => in_array($column, $columns, true));
                $statusColumn = collect(['status', 'is_active'])->first(static fn ($column) => in_array($column, $columns, true));
                $dateColumn = collect(['updated_at', 'created_at'])->first(static fn ($column) => in_array($column, $columns, true));

                $query = DB::table($loyaltyTable)->where($vendorColumn, $vendorUserId);
                if ($dateColumn !== null) {
                    $query->orderByDesc($dateColumn);
                }

                $selectColumns = collect([$nameColumn, $pointsColumn, $statusColumn, $dateColumn])
                    ->filter(static fn ($column) => is_string($column) && $column !== '')
                    ->unique()
                    ->values()
                    ->all();

                $rows = $query->limit(20)->get($selectColumns);
                $vendorEngagement['loyalty_table'] = $loyaltyTable;
                $vendorEngagement['loyalty_programs'] = $rows->map(static function ($row) use ($nameColumn, $pointsColumn, $statusColumn, $dateColumn) {
                    return [
                        'name' => trim((string) ($nameColumn ? ($row->{$nameColumn} ?? 'Loyalty Program') : 'Loyalty Program')),
                        'points_rate' => $pointsColumn ? (float) (($row->{$pointsColumn} ?? 0) ?: 0) : 0,
                        'status' => strtolower(trim((string) ($statusColumn ? ($row->{$statusColumn} ?? 'active') : 'active'))),
                        'updated_at' => trim((string) ($dateColumn ? ($row->{$dateColumn} ?? '') : '')),
                    ];
                })->values();
                break;
            }

            $vendorEngagement['loyal_customers'] = $vendorReservations
                ->filter(static fn ($reservation) => trim((string) ($reservation->customer_email ?? '')) !== '')
                ->groupBy(static fn ($reservation) => strtolower(trim((string) ($reservation->customer_email ?? ''))))
                ->map(static function ($rows, $email) {
                    $rows = collect($rows);
                    $latest = $rows->sortByDesc(static fn ($row) => (string) ($row->start_at ?? $row->created_at ?? ''))->first();
                    return [
                        'customer_email' => (string) $email,
                        'customer_name' => trim((string) ($latest->customer_name ?? 'Returning Guest')),
                        'reservations_count' => (int) $rows->count(),
                        'total_spend' => (float) $rows->sum(static fn ($row) => (float) ($row->invoice_total_amount ?? $row->total_amount ?? 0)),
                    ];
                })
                ->sortByDesc('reservations_count')
                ->take(20)
                ->values();
        }

        if ($loadDistributionData) {
            $distributionAccounts = collect();
            $distributionRoomMappings = collect();
            $distributionEvents = collect();

            if (Schema::hasTable('vendor_channel_accounts')) {
                $distributionAccounts = DB::table('vendor_channel_accounts')
                    ->where('vendor_user_id', $vendorUserId)
                    ->orderByDesc('updated_at')
                    ->limit(30)
                    ->get();
            }

            $distributionAccountIds = $distributionAccounts
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values();

            if ($distributionAccountIds->isNotEmpty() && Schema::hasTable('vendor_channel_room_mappings')) {
                $distributionRoomMappings = DB::table('vendor_channel_room_mappings')
                    ->whereIn('vendor_channel_account_id', $distributionAccountIds->all())
                    ->orderByDesc('updated_at')
                    ->limit(120)
                    ->get();
            }

            if ($distributionAccountIds->isNotEmpty() && Schema::hasTable('vendor_channel_events')) {
                $distributionEvents = DB::table('vendor_channel_events')
                    ->whereIn('vendor_channel_account_id', $distributionAccountIds->all())
                    ->orderByDesc('created_at')
                    ->limit(120)
                    ->get();
            }

            $connectedChannels = (int) $distributionAccounts
                ->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['connected', 'active'], true))
                ->count();

            $actionRequiredChannels = (int) $distributionAccounts
                ->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['action_required', 'error', 'token_expired', 'disconnected'], true))
                ->count();

            $mappedRooms = (int) $distributionRoomMappings
                ->filter(static fn ($row): bool => strtolower(trim((string) ($row->mapping_status ?? 'active'))) === 'active')
                ->count();

            $failedEvents = (int) $distributionEvents
                ->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['failed', 'error', 'dead_letter'], true))
                ->count();

            $pendingEvents = (int) $distributionEvents
                ->filter(static fn ($row): bool => in_array(strtolower(trim((string) ($row->status ?? ''))), ['received', 'queued', 'retrying'], true))
                ->count();

            $lastSyncAt = $distributionAccounts
                ->pluck('last_sync_at')
                ->filter(static fn ($value): bool => is_string($value) || $value instanceof \DateTimeInterface)
                ->map(static fn ($value) => (string) $value)
                ->filter(static fn (string $value): bool => trim($value) !== '')
                ->sortDesc()
                ->first();

            $setupProgress = 0;
            if ($connectedChannels > 0) {
                $setupProgress += 34;
            }
            if ($mappedRooms > 0) {
                $setupProgress += 33;
            }
            if ($connectedChannels > 0 && $pendingEvents === 0 && $failedEvents === 0) {
                $setupProgress += 33;
            }
            $setupProgress = max(0, min(100, $setupProgress));

            $nextStep = 'Connect your first OTA channel to start receiving bookings.';
            if ($connectedChannels > 0 && $mappedRooms === 0) {
                $nextStep = 'Map each OTA room to your room types so inventory can sync safely.';
            } elseif ($connectedChannels > 0 && $mappedRooms > 0 && $pendingEvents > 0) {
                $nextStep = 'Sync is running. Wait for event queue to clear, then go live.';
            } elseif ($connectedChannels > 0 && $mappedRooms > 0 && $failedEvents > 0) {
                $nextStep = 'Resolve failed sync events before enabling full auto-sync.';
            } elseif ($connectedChannels > 0 && $mappedRooms > 0 && $pendingEvents === 0 && $failedEvents === 0) {
                $nextStep = 'All set. Keep auto-sync enabled and monitor sync health daily.';
            }

            $readinessChecks = [
                [
                    'label' => 'At least one OTA account connected',
                    'passed' => $connectedChannels > 0,
                    'detail' => $connectedChannels > 0
                        ? $connectedChannels . ' connected account' . ($connectedChannels === 1 ? '' : 's')
                        : 'No active OTA accounts connected yet.',
                ],
                [
                    'label' => 'At least one room mapping active',
                    'passed' => $mappedRooms > 0,
                    'detail' => $mappedRooms > 0
                        ? $mappedRooms . ' active room mapping' . ($mappedRooms === 1 ? '' : 's')
                        : 'No OTA rooms mapped to internal inventory yet.',
                ],
                [
                    'label' => 'No failed sync events blocking launch',
                    'passed' => $failedEvents === 0,
                    'detail' => $failedEvents === 0
                        ? 'No failed or dead-letter events detected.'
                        : $failedEvents . ' failed event' . ($failedEvents === 1 ? '' : 's') . ' still require action.',
                ],
                [
                    'label' => 'No channel accounts in action required state',
                    'passed' => $actionRequiredChannels === 0,
                    'detail' => $actionRequiredChannels === 0
                        ? 'All connected channels are healthy enough for launch review.'
                        : $actionRequiredChannels . ' channel account' . ($actionRequiredChannels === 1 ? '' : 's') . ' need attention.',
                ],
            ];
            $goLiveReady = collect($readinessChecks)->every(static fn (array $check): bool => (bool) ($check['passed'] ?? false));

            $vendorDistribution = [
                'accounts' => $distributionAccounts,
                'room_mappings' => $distributionRoomMappings,
                'recent_events' => $distributionEvents,
                'summary' => [
                    'connected_channels' => $connectedChannels,
                    'action_required_channels' => $actionRequiredChannels,
                    'mapped_rooms' => $mappedRooms,
                    'failed_events' => $failedEvents,
                    'pending_events' => $pendingEvents,
                    'last_sync_at' => $lastSyncAt,
                    'setup_progress' => $setupProgress,
                    'go_live_ready' => $goLiveReady,
                    'readiness_checks' => $readinessChecks,
                    'next_step' => $nextStep,
                ],
            ];
        }

    }

    return view('vendor-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_vendor_user', 'Vendor'),
        'vendorProfile' => [
            'name' => $vendorUser instanceof User ? (string) $vendorUser->name : (string) session('portal_vendor_user', 'Vendor'),
            'email' => $vendorUser instanceof User ? (string) $vendorUser->email : '',
            'phone' => ($vendorUser instanceof User && Schema::hasColumn('users', 'phone')) ? (string) ($vendorUser->phone ?? '') : '',
            'vendor_id' => $vendorUser instanceof User ? (string) ($vendorUser->portal_vendor_id ?? '') : '',
            'company_name' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_company_name')) ? (string) ($vendorUser->vendor_company_name ?? '') : '',
            'business_registration_number' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_business_registration_number')) ? (string) ($vendorUser->vendor_business_registration_number ?? '') : '',
            'business_license_number' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_business_license_number')) ? (string) ($vendorUser->vendor_business_license_number ?? '') : '',
            'contact_person_name' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_contact_person_name')) ? (string) ($vendorUser->vendor_contact_person_name ?? '') : '',
            'contact_person_phone' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_contact_person_phone')) ? (string) ($vendorUser->vendor_contact_person_phone ?? '') : '',
            'contact_person_email' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_contact_person_email')) ? (string) ($vendorUser->vendor_contact_person_email ?? '') : '',
            'contact_person_id_number' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_contact_person_id_number')) ? (string) ($vendorUser->vendor_contact_person_id_number ?? '') : '',
            'verification_status' => vendorPortalVerificationStatus($vendorUser),
            'verification_notes' => ($vendorUser instanceof User && Schema::hasColumn('users', 'vendor_verification_notes')) ? (string) ($vendorUser->vendor_verification_notes ?? '') : '',
            'approved_categories' => vendorPortalApprovedCategories($vendorUser),
        ],
        'vendorCanManageListings' => vendorPortalCanManageListings($vendorUser),
        'vendorCategoryMap' => $vendorCategoryMap,
        'selectedVendorCategories' => $selectedVendorCategories,
        'vendorOnboardingStep' => $vendorOnboardingStep,
        'vendorProperties' => $vendorProperties,
        'vendorServices' => $vendorServices,
        'vendorAvailability' => $vendorAvailability,
        'vendorReservations' => $vendorReservations,
        'vendorPricingRules' => $vendorPricingRules,
        'vendorBilling' => $vendorBilling,
        'vendorPayoutAccounts' => $vendorPayoutAccounts,
        'vendorRoomCategories' => $vendorRoomCategories,
        'vendorRooms' => $vendorRoomCategories,
        'vendorRentalItems' => $vendorRentalItems,
        'vendorMediaAssets' => $vendorMediaAssets,
        'payoutStatusRows' => $payoutStatusRows,
        'vendorReservationSummaryByProperty' => $vendorReservationSummaryByProperty,
        'vendorDistribution' => $vendorDistribution,
        'vendorAuditTrail' => $vendorAuditTrail,
        'vendorOperationalHealth' => $vendorOperationalHealth,
        'vendorEngagement' => $vendorEngagement,
        'vendorDashboardSnapshot' => $vendorDashboardSnapshot,
        'transportModeOptions' => vendorPortalListingOptions('transport_mode'),
        'accommodationFacilityOptions' => vendorPortalListingOptions('accommodation_facility'),
        'roomAmenityOptions' => vendorPortalListingOptions('room_amenity'),
        'bathroomAmenityOptions' => vendorPortalListingOptions('bathroom_amenity'),
        'propertyAmenityOptions' => vendorPortalListingOptions('property_amenity'),
        'propertyFeatureOptions' => vendorPortalListingOptions('property_feature'),
        'roomBedTypeOptions' => vendorPortalListingOptions('room_bed_type'),
        'excursionTypeOptions' => vendorPortalListingOptions('excursion_type'),
        'restaurantMealServiceOptions' => vendorPortalListingOptions('restaurant_meal_service'),
        'vehicleRentalTypeOptions' => vendorPortalListingOptions('vehicle_rental_type'),
        'vendorReservationPolicy' => $vendorReservationPolicy,
        'vendorTaxComponents' => $vendorTaxComponents,
    ]);
});

Route::get('/vendor/overview', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor?page=overview&mode=simple')->with('portal_active_panel', 'overview');
});

Route::get('/vendor/setup', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor?page=setup&mode=simple')->with('portal_active_panel', 'distribution');
});

Route::get('/vendor/profile', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $section = strtolower(trim((string) request()->query('section', '')));
    $allowedSections = ['profile', 'categories', 'banking', 'address', 'password', 'all'];

    VendorPortalAuditLogger::log('vendor_workspace.profile_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:profile',
        'section' => $section !== '' ? $section : 'default',
    ]);

    $target = '/vendor?page=profile';
    if ($section !== '' && in_array($section, $allowedSections, true)) {
        $target .= '&section=' . urlencode($section);
    }

    return redirect($target)->with('portal_active_panel', 'profile');
});

Route::get('/vendor/listings', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Listings are locked until your vendor profile is verified by admin.']);
    }

    VendorPortalAuditLogger::log('vendor_workspace.listings_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:listings',
        'mode' => 'manage',
    ]);

    return redirect('/vendor?page=listings')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1);
});

Route::get('/vendor/listings/create', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Complete compliance verification in My Account and wait for admin approval before creating listings.']);
    }

    VendorPortalAuditLogger::log('vendor_listing.create_started', [
        'severity' => 'info',
        'target_identifier' => 'listing:create',
        'category' => 'unspecified',
    ]);

    return redirect('/vendor?page=listings')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'create');
});

Route::get('/vendor/listings/create/{category}', function (string $category) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Complete compliance verification in My Account and wait for admin approval before creating listings.']);
    }

    $normalizedCategory = vendorPortalNormalizeCategoryToken($category);
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['sea_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    VendorPortalAuditLogger::log('vendor_listing.create_started', [
        'severity' => 'info',
        'target_identifier' => 'listing-category:' . $normalizedCategory,
        'category' => $normalizedCategory,
    ]);

    return redirect('/vendor?page=listings&category=' . urlencode($normalizedCategory))
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'create')
        ->with('portal_listing_category', $normalizedCategory);
});

$vendorListingCategoryAliases = [
    'accommodation',
    'sea_transport',
    'land_transport',
    'water_sports',
    'excursion',
    'remote_workspace',
    'conference_room',
    'resort_day_visit',
    'restaurant',
    'vehicle_rental',
    'liveaboard',
];

foreach ($vendorListingCategoryAliases as $listingCategoryAlias) {
    Route::get('/vendor/listings/' . $listingCategoryAlias, function () use ($listingCategoryAlias) {
        if (!session()->get('portal_vendor_authenticated', false)) {
            return redirect('/portal/vendor/login');
        }

        $vendorUserId = (int) session('portal_vendor_user_id', 0);
        $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
        if (!vendorPortalCanManageListings($vendorUser)) {
            return redirect('/vendor?page=profile')
                ->with('portal_active_panel', 'profile')
                ->withErrors(['profile' => 'Listings are locked until your vendor profile is verified by admin.']);
        }

        VendorPortalAuditLogger::log('vendor_workspace.listings_category_opened', [
            'severity' => 'info',
            'target_identifier' => 'listing-category:' . $listingCategoryAlias,
            'category' => $listingCategoryAlias,
            'mode' => 'manage',
        ]);

        return redirect('/vendor?page=listings&category=' . urlencode($listingCategoryAlias))
            ->with('portal_active_panel', 'listings')
            ->with('listing_wizard_step', 1)
            ->with('portal_listing_mode', 'manage')
            ->with('portal_listing_category', $listingCategoryAlias);
    })->name('vendor.listings.category.' . $listingCategoryAlias);

    Route::get('/vendor/listings/' . $listingCategoryAlias . '/create', function () use ($listingCategoryAlias) {
        if (!session()->get('portal_vendor_authenticated', false)) {
            return redirect('/portal/vendor/login');
        }

        $vendorUserId = (int) session('portal_vendor_user_id', 0);
        $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
        if (!vendorPortalCanManageListings($vendorUser)) {
            return redirect('/vendor?page=profile')
                ->with('portal_active_panel', 'profile')
                ->withErrors(['profile' => 'Complete compliance verification in My Account and wait for admin approval before creating listings.']);
        }

        $approvedCategories = vendorPortalApprovedCategories($vendorUser);
        if (!in_array($listingCategoryAlias, $approvedCategories, true)) {
            return redirect('/vendor/listings/' . $listingCategoryAlias)
                ->withErrors(['profile' => 'This listing category is locked. Contact admin to unlock ' . $listingCategoryAlias . ' for your account.']);
        }

        $vendorCategoryMap = vendorPortalCategoryMap();
        $selectedVendorCategories = vendorPortalSelectedCategories($vendorUser);
        $listingCategoryViewOrder = ['accommodation','sea_transport','land_transport','water_sports','excursion','remote_workspace','conference_room','resort_day_visit','restaurant','vehicle_rental','liveaboard'];
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, ['sea_transport' => 'Sea Transport & Ferries', 'land_transport' => 'Land Transport', 'conference_room' => 'Conference Rooms', 'liveaboard' => 'Liveaboard / Safari']);
        $categoryLabel = $listingCategoryLabelMap[$listingCategoryAlias] ?? ucwords(str_replace('_', ' ', $listingCategoryAlias));

        $vendorProfileRow = null;
        if (Schema::hasTable('vendor_profiles')) {
            $vendorProfileRow = DB::table('vendor_profiles')
                ->where('vendor_user_id', $vendorUserId)
                ->first(['business_name', 'contact_email']);
        }
        $vendorProfile = [
            'name' => (string) ($vendorProfileRow->business_name ?? ($vendorUser->name ?? '')),
            'email' => (string) ($vendorProfileRow->contact_email ?? ($vendorUser->email ?? '')),
            'approved_categories' => $approvedCategories,
        ];

        $transferOptionCatalog = vendorPortalTransferOptionLabelMap();
        $workspaceAmenityCatalog = [
            'workdesk' => 'Workdesk',
            'wifi' => 'WiFi',
            'printing' => 'Printing',
            'water_bottles' => 'Water Bottles',
            'coffee' => 'Coffee',
            'tea' => 'Tea',
            'snacks' => 'Snacks',
        ];

        VendorPortalAuditLogger::log('vendor_listing.create_form_opened', [
            'severity' => 'info',
            'target_identifier' => 'listing-category:' . $listingCategoryAlias,
            'category' => $listingCategoryAlias,
        ]);

        return view('vendor-portal.listing-form-page', [
            'category' => $listingCategoryAlias,
            'categoryLabel' => $categoryLabel,
            'formType' => 'create',
            'pageTitle' => 'New ' . $categoryLabel . ' Listing',
            'pageSubtitle' => 'Complete the form below to create a new ' . strtolower($categoryLabel) . ' listing.',
            'portalUser' => session('portal_vendor_user_email', $vendorUser->email ?? ''),
            'vendorProfile' => $vendorProfile,
            'vendorCategoryMap' => $vendorCategoryMap,
            'selectedVendorCategories' => $selectedVendorCategories,
            'listingCategoryViewOrder' => $listingCategoryViewOrder,
            'listingCategoryLabelMap' => $listingCategoryLabelMap,
            'activePortalPage' => 'listings',
            'forcedListingCategory' => $listingCategoryAlias,
            'transportModeOptions' => vendorPortalListingOptions('transport_mode'),
            'transportModeOptionsCollection' => collect(vendorPortalListingOptions('transport_mode')),
            'propertyAmenityOptions' => vendorPortalListingOptions('property_amenity'),
            'propertyAmenityOptionsCollection' => collect(vendorPortalListingOptions('property_amenity')),
            'propertyFeatureOptions' => vendorPortalListingOptions('property_feature'),
            'propertyFeatureOptionsCollection' => collect(vendorPortalListingOptions('property_feature')),
            'excursionTypeOptions' => vendorPortalListingOptions('excursion_type'),
            'excursionTypeOptionsCollection' => collect(vendorPortalListingOptions('excursion_type')),
            'restaurantMealServiceOptions' => vendorPortalListingOptions('restaurant_meal_service'),
            'restaurantMealServiceOptionsCollection' => collect(vendorPortalListingOptions('restaurant_meal_service')),
            'vehicleRentalTypeOptions' => vendorPortalListingOptions('vehicle_rental_type'),
            'vehicleRentalTypeOptionsCollection' => collect(vendorPortalListingOptions('vehicle_rental_type')),
            'vendorTaxComponents' => collect([]),
            'transferOptionCatalog' => $transferOptionCatalog,
            'workspaceAmenityCatalog' => $workspaceAmenityCatalog,
            'oldTransferOptions' => old('transfer_options', []),
            'oldTransferRatesInput' => [],
            'oldPropertyAmenities' => old('property_amenities', []),
            'oldPropertyFeatures' => old('property_features', []),
            'oldWorkspaceAmenityStatus' => [],
        ]);
    })->name('vendor.listings.category.create.' . $listingCategoryAlias);
}

foreach ($vendorListingCategoryAliases as $listingCategoryAlias) {
    Route::get('/vendor/listings/' . $listingCategoryAlias . '/{propertyId}/edit', function (int $propertyId) use ($listingCategoryAlias) {
        if (!session()->get('portal_vendor_authenticated', false)) {
            return redirect('/portal/vendor/login');
        }

        $vendorUserId = (int) session('portal_vendor_user_id', 0);
        $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
        if (!vendorPortalCanManageListings($vendorUser)) {
            return redirect('/vendor?page=profile')
                ->with('portal_active_panel', 'profile')
                ->withErrors(['profile' => 'Complete compliance verification in My Account and wait for admin approval before editing listings.']);
        }

        $approvedCategories = vendorPortalApprovedCategories($vendorUser);
        if (!in_array($listingCategoryAlias, $approvedCategories, true)) {
            return redirect('/vendor/listings/' . $listingCategoryAlias)
                ->withErrors(['profile' => 'This listing category is locked. Contact admin to unlock ' . $listingCategoryAlias . ' for your account.']);
        }

        $propertyRow = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($propertyId, $vendorUserId, $listingCategoryAlias);
        if (!$propertyRow) {
            return redirect('/vendor/listings/' . $listingCategoryAlias)
                ->withErrors(['profile' => 'Listing not found or access denied.']);
        }

        $propertyDetails = [];
        $rawDetails = $propertyRow->listing_details ?? ($propertyRow->details ?? null);
        if (is_string($rawDetails) && trim($rawDetails) !== '') {
            $decoded = json_decode($rawDetails, true);
            if (is_array($decoded)) {
                $propertyDetails = $decoded;
            }
        }

        $vendorCategoryMap = vendorPortalCategoryMap();
        $selectedVendorCategories = vendorPortalSelectedCategories($vendorUser);
        $listingCategoryViewOrder = ['accommodation','sea_transport','land_transport','water_sports','excursion','remote_workspace','conference_room','resort_day_visit','restaurant','vehicle_rental','liveaboard'];
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, ['sea_transport' => 'Sea Transport & Ferries', 'land_transport' => 'Land Transport', 'conference_room' => 'Conference Rooms', 'liveaboard' => 'Liveaboard / Safari']);
        $categoryLabel = $listingCategoryLabelMap[$listingCategoryAlias] ?? ucwords(str_replace('_', ' ', $listingCategoryAlias));

        $vendorProfileRow = null;
        if (Schema::hasTable('vendor_profiles')) {
            $vendorProfileRow = DB::table('vendor_profiles')
                ->where('vendor_user_id', $vendorUserId)
                ->first(['business_name', 'contact_email']);
        }
        $vendorProfile = [
            'name' => (string) ($vendorProfileRow->business_name ?? ($vendorUser->name ?? '')),
            'email' => (string) ($vendorProfileRow->contact_email ?? ($vendorUser->email ?? '')),
            'approved_categories' => $approvedCategories,
        ];

        $transferOptionCatalog = vendorPortalTransferOptionLabelMap();
        $workspaceAmenityCatalog = [
            'workdesk' => 'Workdesk',
            'wifi' => 'WiFi',
            'printing' => 'Printing',
            'water_bottles' => 'Water Bottles',
            'coffee' => 'Coffee',
            'tea' => 'Tea',
            'snacks' => 'Snacks',
        ];

        VendorPortalAuditLogger::log('vendor_listing.edit_form_opened', [
            'severity' => 'info',
            'target_identifier' => 'listing:' . $propertyId,
            'category' => $listingCategoryAlias,
            'listing_name' => trim((string) ($propertyRow->name ?? 'Listing #' . $propertyId)),
        ]);

        return view('vendor-portal.listing-form-page', [
            'category' => $listingCategoryAlias,
            'categoryLabel' => $categoryLabel,
            'formType' => 'edit',
            'property' => $propertyRow,
            'propertyId' => $propertyId,
            'propertyDetails' => $propertyDetails,
            'pageTitle' => 'Edit: ' . ($propertyRow->name ?? 'Listing #' . $propertyId),
            'pageSubtitle' => 'Update your ' . strtolower($categoryLabel) . ' listing details.',
            'portalUser' => session('portal_vendor_user_email', $vendorUser->email ?? ''),
            'vendorProfile' => $vendorProfile,
            'vendorCategoryMap' => $vendorCategoryMap,
            'selectedVendorCategories' => $selectedVendorCategories,
            'listingCategoryViewOrder' => $listingCategoryViewOrder,
            'listingCategoryLabelMap' => $listingCategoryLabelMap,
            'activePortalPage' => 'listings',
            'forcedListingCategory' => $listingCategoryAlias,
            'transportModeOptions' => vendorPortalListingOptions('transport_mode'),
            'transportModeOptionsCollection' => collect(vendorPortalListingOptions('transport_mode')),
            'propertyAmenityOptions' => vendorPortalListingOptions('property_amenity'),
            'propertyAmenityOptionsCollection' => collect(vendorPortalListingOptions('property_amenity')),
            'propertyFeatureOptions' => vendorPortalListingOptions('property_feature'),
            'propertyFeatureOptionsCollection' => collect(vendorPortalListingOptions('property_feature')),
            'excursionTypeOptions' => vendorPortalListingOptions('excursion_type'),
            'excursionTypeOptionsCollection' => collect(vendorPortalListingOptions('excursion_type')),
            'restaurantMealServiceOptions' => vendorPortalListingOptions('restaurant_meal_service'),
            'restaurantMealServiceOptionsCollection' => collect(vendorPortalListingOptions('restaurant_meal_service')),
            'vehicleRentalTypeOptions' => vendorPortalListingOptions('vehicle_rental_type'),
            'vehicleRentalTypeOptionsCollection' => collect(vendorPortalListingOptions('vehicle_rental_type')),
            'vendorTaxComponents' => collect([]),
            'transferOptionCatalog' => $transferOptionCatalog,
            'workspaceAmenityCatalog' => $workspaceAmenityCatalog,
            'oldTransferOptions' => old('transfer_options', []),
            'oldTransferRatesInput' => [],
            'oldPropertyAmenities' => old('property_amenities', []),
            'oldPropertyFeatures' => old('property_features', []),
            'oldWorkspaceAmenityStatus' => [],
        ]);
    })->name('vendor.listings.category.edit.' . $listingCategoryAlias);
}

Route::get('/vendor/listings/{category}', function (string $category) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Listings are locked until your vendor profile is verified by admin.']);
    }

    $normalizedCategory = vendorPortalNormalizeCategoryToken($category);
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['sea_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    return redirect('/vendor?page=listings&category=' . urlencode($normalizedCategory))
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'manage')
        ->with('portal_listing_category', $normalizedCategory);
});

Route::get('/vendor/listings/manage', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Listings are locked until your vendor profile is verified by admin.']);
    }

    return redirect('/vendor?page=listings')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'manage');
});

Route::get('/vendor/listings/manage/{category}', function (string $category) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Listings are locked until your vendor profile is verified by admin.']);
    }

    $normalizedCategory = vendorPortalNormalizeCategoryToken($category);
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['sea_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    return redirect('/vendor?page=listings&category=' . urlencode($normalizedCategory))
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'manage')
        ->with('portal_listing_category', $normalizedCategory);
});

Route::get('/vendor/reservations', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    $scope = strtolower(trim((string) request()->query('scope', 'all')));
    if (!in_array($scope, ['active', 'pending', 'history', 'all'], true)) {
        $scope = 'all';
    }
    $query = '/vendor?page=reservations';
    $query .= '&scope=' . urlencode($scope);
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'reservations')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/availability', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    $query = '/vendor?page=availability';
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'reservations')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/operations', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Operations are locked until your vendor account is verified and approved by admin.']);
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    $query = '/vendor?page=reservations';
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'reservations')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/engagement', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor#engagement')->with('portal_active_panel', 'engagement');
});

Route::get('/vendor/pricing', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    $query = '/vendor?page=billing';
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'billing')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/billing', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));

    VendorPortalAuditLogger::log('vendor_workspace.billing_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:billing',
        'category' => $category !== '' ? $category : 'all',
    ]);

    $query = '/vendor?page=billing';
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'billing')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/distribution', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    VendorPortalAuditLogger::log('vendor_workspace.distribution_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:distribution',
    ]);

    return redirect('/vendor?page=distribution&mode=advanced')->with('portal_active_panel', 'distribution');
});

Route::get('/vendor/compliance', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    VendorPortalAuditLogger::log('vendor_workspace.compliance_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:compliance',
    ]);

    return redirect('/vendor?page=compliance')->with('portal_active_panel', 'compliance');
});

Route::post('/vendor/distribution/accounts/connect', function (Request $request) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Channel manager tables are not available yet.']);
    }

    $validated = $request->validate([
        'channel_code' => ['required', Rule::in(['booking', 'agoda', 'airbnb'])],
        'account_reference' => ['nullable', 'string', 'max:160'],
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'webhook_secret' => ['required', 'string', 'max:255'],
        'inventory_sync_url' => ['nullable', 'url', 'max:255'],
        'api_base' => ['nullable', 'url', 'max:255'],
        'access_token' => ['nullable', 'string', 'max:5000'],
    ]);

    $channelCode = strtolower(trim((string) ($validated['channel_code'] ?? '')));
    $accountReference = trim((string) ($validated['account_reference'] ?? ''));
    $webhookSecret = trim((string) ($validated['webhook_secret'] ?? ''));
    $inventorySyncUrl = trim((string) ($validated['inventory_sync_url'] ?? ''));
    $apiBase = trim((string) ($validated['api_base'] ?? ''));
    $accessToken = trim((string) ($validated['access_token'] ?? ''));
    $vendorPropertyId = isset($validated['vendor_property_id']) ? (int) $validated['vendor_property_id'] : null;

    if ($inventorySyncUrl === '' && $apiBase === '') {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Provide either Inventory Sync URL or API Base URL.'])
            ->withInput();
    }

    if ($channelCode === 'booking' && $inventorySyncUrl === '') {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Booking.com requires an Inventory Sync URL.'])
            ->withInput();
    }

    if (in_array($channelCode, ['agoda', 'airbnb'], true) && $apiBase === '') {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => strtoupper($channelCode) . ' requires an API Base URL.'])
            ->withInput();
    }

    if ($vendorPropertyId !== null) {
        $ownsProperty = \App\Support\VendorPropertyCompatibilityReader::vendorOwnsProperty($vendorPropertyId, $vendorUserId);
        if (!$ownsProperty) {
            return redirect('/vendor?page=distribution')
                ->with('portal_active_panel', 'distribution')
                ->withErrors(['distribution' => 'Selected listing/property is not valid for this vendor account.'])
                ->withInput();
        }
    }

    $existingQuery = DB::table('vendor_channel_accounts')
        ->where('vendor_user_id', $vendorUserId)
        ->where('channel_code', $channelCode);

    if ($vendorPropertyId === null) {
        $existingQuery->whereNull('vendor_property_id');
    } else {
        $existingQuery->where('vendor_property_id', $vendorPropertyId);
    }

    $existing = $existingQuery->first();

    $connectionMeta = [];
    if ($existing && is_string($existing->connection_meta ?? null) && trim((string) $existing->connection_meta) !== '') {
        $decoded = json_decode((string) $existing->connection_meta, true);
        if (is_array($decoded)) {
            $connectionMeta = $decoded;
        }
    }

    $connectionMeta['webhook_secret'] = $webhookSecret;
    if ($inventorySyncUrl !== '') {
        $connectionMeta['inventory_sync_url'] = $inventorySyncUrl;
    }
    if ($apiBase !== '') {
        $connectionMeta['api_base'] = rtrim($apiBase, '/');
    }
    $connectionMeta['last_connected_at'] = now()->toIso8601String();
    unset($connectionMeta['disconnected_at'], $connectionMeta['disconnect_reason']);

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'channel_code' => $channelCode,
        'account_reference' => $accountReference !== '' ? $accountReference : null,
        'status' => 'connected',
        'connection_meta' => json_encode($connectionMeta, JSON_UNESCAPED_SLASHES),
        'connected_at' => $existing && !empty($existing->connected_at) ? $existing->connected_at : now(),
        'updated_at' => now(),
    ];

    if ($accessToken !== '') {
        $payload['access_token_encrypted'] = Crypt::encryptString($accessToken);
        $connectionMeta['access_token_updated_at'] = now()->toIso8601String();
    } elseif ($existing && !empty($existing->access_token_encrypted)) {
        $payload['access_token_encrypted'] = $existing->access_token_encrypted;
    }

    $payload['connection_meta'] = json_encode($connectionMeta, JSON_UNESCAPED_SLASHES);

    if ($existing) {
        DB::table('vendor_channel_accounts')
            ->where('id', (int) $existing->id)
            ->update($payload);

        VendorPortalAuditLogger::log('vendor_channel_account.updated', [
            'severity' => 'info',
            'target_identifier' => 'channel-account:' . (int) $existing->id,
            'channel_code' => $channelCode,
            'vendor_property_id' => $vendorPropertyId,
        ]);

        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->with('status', strtoupper($channelCode) . ' account updated successfully.');
    }

    $payload['created_at'] = now();
    $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId($payload);

    VendorPortalAuditLogger::log('vendor_channel_account.connected', [
        'severity' => 'info',
        'target_identifier' => 'channel-account:' . $accountId,
        'channel_code' => $channelCode,
        'vendor_property_id' => $vendorPropertyId,
    ]);

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->with('status', strtoupper($channelCode) . ' account connected successfully.');
});

Route::post('/vendor/distribution/accounts/{accountId}/disconnect', function (Request $request, int $accountId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Channel manager tables are not available yet.']);
    }

    $account = DB::table('vendor_channel_accounts')
        ->where('id', $accountId)
        ->where('vendor_user_id', $vendorUserId)
        ->first(['id', 'channel_code', 'connection_meta']);

    if (!$account) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Channel account not found or access denied.']);
    }

    DB::table('vendor_channel_accounts')
        ->where('id', (int) $account->id)
        ->update([
            'status' => 'disconnected',
            'connection_meta' => json_encode(array_merge((array) (json_decode((string) ($account->connection_meta ?? '{}'), true) ?: []), [
                'disconnected_at' => now()->toIso8601String(),
                'disconnect_reason' => 'manual_vendor_disconnect',
            ]), JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

    VendorPortalAuditLogger::log('vendor_channel_account.disconnected', [
        'severity' => 'warn',
        'target_identifier' => 'channel-account:' . (int) $account->id,
        'channel_code' => strtolower(trim((string) ($account->channel_code ?? 'unknown'))),
    ]);

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->with('status', strtoupper(trim((string) ($account->channel_code ?? 'channel'))) . ' account disconnected.');
});

Route::post('/vendor/distribution/room-mappings/save', function (Request $request) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_room_mappings') || !Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Channel mapping tables are not available yet.']);
    }

    $validated = $request->validate([
        'vendor_channel_account_id' => ['required', 'integer', 'min:1'],
        'external_room_id' => ['required', 'string', 'max:160'],
        'external_room_name' => ['nullable', 'string', 'max:190'],
        'external_rate_plan_id' => ['nullable', 'string', 'max:160'],
        'external_rate_plan_name' => ['nullable', 'string', 'max:190'],
        'internal_room_category_id' => ['required', 'integer', 'min:1'],
    ]);

    $account = DB::table('vendor_channel_accounts')
        ->where('id', (int) $validated['vendor_channel_account_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->first(['id', 'channel_code']);

    if (!$account) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Selected OTA account is not available for this vendor.'])
            ->withInput();
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Internal room categories are not available yet.'])
            ->withInput();
    }

    $roomCategory = DB::table('vendor_property_room_categories')
        ->where('id', (int) $validated['internal_room_category_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->first(['id', 'name']);

    if (!$roomCategory) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Selected internal room category is invalid for this vendor.'])
            ->withInput();
    }

    $externalRoomId = trim((string) $validated['external_room_id']);
    $existing = DB::table('vendor_channel_room_mappings')
        ->where('vendor_channel_account_id', (int) $account->id)
        ->where('external_room_id', $externalRoomId)
        ->first(['id']);

    $payload = [
        'vendor_channel_account_id' => (int) $account->id,
        'external_room_id' => $externalRoomId,
        'external_room_name' => trim((string) ($validated['external_room_name'] ?? '')) ?: null,
        'external_rate_plan_id' => trim((string) ($validated['external_rate_plan_id'] ?? '')) ?: null,
        'external_rate_plan_name' => trim((string) ($validated['external_rate_plan_name'] ?? '')) ?: null,
        'internal_room_category_id' => (int) $roomCategory->id,
        'mapping_status' => 'active',
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('vendor_channel_room_mappings')
            ->where('id', (int) $existing->id)
            ->update($payload);

        VendorPortalAuditLogger::log('vendor_channel_room_mapping.updated', [
            'severity' => 'info',
            'target_identifier' => 'channel-room-mapping:' . (int) $existing->id,
            'channel_code' => strtolower(trim((string) ($account->channel_code ?? ''))),
            'external_room_id' => $externalRoomId,
            'internal_room_category_id' => (int) $roomCategory->id,
        ]);

        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->with('status', 'Room mapping updated successfully.');
    }

    $payload['created_at'] = now();
    $mappingId = (int) DB::table('vendor_channel_room_mappings')->insertGetId($payload);

    VendorPortalAuditLogger::log('vendor_channel_room_mapping.created', [
        'severity' => 'info',
        'target_identifier' => 'channel-room-mapping:' . $mappingId,
        'channel_code' => strtolower(trim((string) ($account->channel_code ?? ''))),
        'external_room_id' => $externalRoomId,
        'internal_room_category_id' => (int) $roomCategory->id,
    ]);

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->with('status', 'Room mapping created successfully.');
});

Route::post('/vendor/distribution/room-mappings/{mappingId}/deactivate', function (Request $request, int $mappingId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_room_mappings') || !Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Channel mapping tables are not available yet.']);
    }

    $mapping = DB::table('vendor_channel_room_mappings as m')
        ->join('vendor_channel_accounts as a', 'a.id', '=', 'm.vendor_channel_account_id')
        ->where('m.id', $mappingId)
        ->where('a.vendor_user_id', $vendorUserId)
        ->first([
            'm.id',
            'm.external_room_id',
            'm.internal_room_category_id',
            'a.channel_code',
        ]);

    if (!$mapping) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Room mapping not found or access denied.']);
    }

    DB::table('vendor_channel_room_mappings')
        ->where('id', (int) $mapping->id)
        ->update([
            'mapping_status' => 'inactive',
            'updated_at' => now(),
        ]);

    VendorPortalAuditLogger::log('vendor_channel_room_mapping.deactivated', [
        'severity' => 'warn',
        'target_identifier' => 'channel-room-mapping:' . (int) $mapping->id,
        'channel_code' => strtolower(trim((string) ($mapping->channel_code ?? ''))),
        'external_room_id' => trim((string) ($mapping->external_room_id ?? '')),
        'internal_room_category_id' => (int) ($mapping->internal_room_category_id ?? 0),
    ]);

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->with('status', 'Room mapping deactivated.');
});

Route::post('/vendor/distribution/events/{eventId}/retry', function (Request $request, int $eventId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_events') || !Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->withErrors(['distribution' => 'Channel manager tables are not available yet.']);
    }

    $event = DB::table('vendor_channel_events as e')
        ->join('vendor_channel_accounts as a', 'a.id', '=', 'e.vendor_channel_account_id')
        ->where('e.id', $eventId)
        ->where('a.vendor_user_id', $vendorUserId)
        ->first([
            'e.id',
            'e.vendor_channel_account_id',
            'e.direction',
            'e.event_type',
            'e.status',
            'e.retry_count',
            'e.http_method',
            'e.request_path',
            'e.signature_hash',
            'e.payload',
            'a.channel_code',
        ]);

    if (!$event) {
        return redirect('/vendor?page=distribution')
            ->withErrors(['distribution' => 'Event not found or access denied.']);
    }

    $direction = strtolower(trim((string) ($event->direction ?? 'inbound')));
    $currentStatus = strtolower(trim((string) ($event->status ?? '')));
    if (!in_array($currentStatus, ['failed', 'error', 'dead_letter'], true)) {
        return redirect('/vendor?page=distribution')
            ->withErrors(['distribution' => 'Only failed events can be retried.']);
    }

    if ($direction === 'outbound') {
        DB::table('vendor_channel_events')
            ->where('id', $eventId)
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'updated_at' => now(),
            ]);

        VendorPortalAuditLogger::log('vendor_channel_event.outbound_requeue_requested', [
            'severity' => 'warn',
            'target_identifier' => 'event:' . $eventId,
            'channel_code' => strtolower(trim((string) ($event->channel_code ?? ''))),
            'event_type' => trim((string) ($event->event_type ?? 'event')),
            'retry_count_before' => (int) ($event->retry_count ?? 0),
        ]);

        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->with('status', 'Outbound event re-queued. Dispatcher will retry delivery automatically.');
    }

    $payload = [];
    if (is_string($event->payload ?? null) && trim((string) $event->payload) !== '') {
        $decoded = json_decode((string) $event->payload, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    if ($payload === []) {
        return redirect('/vendor?page=distribution')
            ->withErrors(['distribution' => 'Stored event payload is empty and cannot be retried.']);
    }

    DB::table('vendor_channel_events')
        ->where('id', $eventId)
        ->update([
            'status' => 'retrying',
            'updated_at' => now(),
        ]);

    VendorPortalAuditLogger::log('vendor_channel_event.retry_requested', [
        'severity' => 'warn',
        'target_identifier' => 'event:' . $eventId,
        'channel_code' => strtolower(trim((string) ($event->channel_code ?? ''))),
        'event_type' => trim((string) ($event->event_type ?? 'event')),
    ]);

    $retryIdempotency = implode(':', [
        'retry',
        (int) $event->vendor_channel_account_id,
        (int) $eventId,
        now()->timestamp,
    ]);

    $result = ChannelReservationIngestor::ingest(
        vendorChannelAccountId: (int) $event->vendor_channel_account_id,
        channelCode: strtolower(trim((string) ($event->channel_code ?? ''))),
        payload: $payload,
        idempotencyKey: $retryIdempotency,
        signatureHash: trim((string) ($event->signature_hash ?? 'manual-retry')),
        httpMethod: trim((string) ($event->http_method ?? 'POST')),
        requestPath: '/vendor/distribution/events/' . $eventId . '/retry'
    );

    if (($result['ok'] ?? false) === true) {
        DB::table('vendor_channel_events')
            ->where('id', $eventId)
            ->update([
                'status' => 'recovered',
                'error_message' => null,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        VendorPortalAuditLogger::log('vendor_channel_event.retry_recovered', [
            'severity' => 'info',
            'target_identifier' => 'event:' . $eventId,
            'channel_code' => strtolower(trim((string) ($event->channel_code ?? ''))),
            'event_type' => trim((string) ($event->event_type ?? 'event')),
            'reservation_id' => (int) ($result['reservation_id'] ?? 0),
        ]);

        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->with('status', 'Event retry succeeded and inventory is now synchronized.');
    }

    DB::table('vendor_channel_events')
        ->where('id', $eventId)
        ->update([
            'status' => 'failed',
            'error_message' => (string) ($result['message'] ?? 'Retry failed.'),
            'updated_at' => now(),
        ]);

    VendorPortalAuditLogger::log('vendor_channel_event.retry_failed', [
        'severity' => 'error',
        'target_identifier' => 'event:' . $eventId,
        'channel_code' => strtolower(trim((string) ($event->channel_code ?? ''))),
        'event_type' => trim((string) ($event->event_type ?? 'event')),
        'error_message' => (string) ($result['message'] ?? 'Retry failed.'),
    ]);

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->withErrors(['distribution' => (string) ($result['message'] ?? 'Retry failed.')]);
});

Route::post('/vendor/distribution/events/{eventId}/dispatch-now', function (Request $request, int $eventId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_channel_events') || !Schema::hasTable('vendor_channel_accounts')) {
        return redirect('/vendor?page=distribution')
            ->withErrors(['distribution' => 'Channel manager tables are not available yet.']);
    }

    $rateLimitKey = 'portal_distribution_dispatch_now_last_at';
    $lastDispatchAt = (int) session($rateLimitKey, 0);
    $nowTimestamp = now()->timestamp;
    $cooldownSeconds = 10;
    if ($lastDispatchAt > 0 && ($nowTimestamp - $lastDispatchAt) < $cooldownSeconds) {
        VendorPortalAuditLogger::log('vendor_channel_event.dispatch_now_rate_limited', [
            'severity' => 'warn',
            'target_identifier' => 'event:' . $eventId,
            'cooldown_seconds' => $cooldownSeconds,
        ]);

        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Dispatch now is rate-limited. Please wait a few seconds and try again.']);
    }

    $event = DB::table('vendor_channel_events as e')
        ->join('vendor_channel_accounts as a', 'a.id', '=', 'e.vendor_channel_account_id')
        ->where('e.id', $eventId)
        ->where('a.vendor_user_id', $vendorUserId)
        ->where('e.direction', 'outbound')
        ->first([
            'e.id',
            'e.vendor_channel_account_id',
            'e.event_type',
            'e.status',
            'a.channel_code',
        ]);

    if (!$event) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Outbound event not found or access denied.']);
    }

    $currentStatus = strtolower(trim((string) ($event->status ?? 'queued')));
    if (in_array($currentStatus, ['failed', 'error', 'dead_letter'], true)) {
        DB::table('vendor_channel_events')
            ->where('id', $eventId)
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'updated_at' => now(),
            ]);
    } elseif (!in_array($currentStatus, ['queued', 'retrying'], true)) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Only queued, retrying, or failed outbound events can be dispatched now.']);
    }

    session([$rateLimitKey => $nowTimestamp]);

    VendorPortalAuditLogger::log('vendor_channel_event.dispatch_now_requested', [
        'severity' => 'warn',
        'target_identifier' => 'event:' . $eventId,
        'channel_code' => strtolower(trim((string) ($event->channel_code ?? ''))),
        'event_type' => trim((string) ($event->event_type ?? 'event')),
    ]);

    $summary = ChannelOutboundSyncDispatcher::dispatchQueued(limit: 1, maxRetries: 5, dryRun: false, eventId: $eventId);

    if ((int) ($summary['processed'] ?? 0) > 0) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->with('status', 'Outbound event dispatched successfully.');
    }

    if ((int) ($summary['retrying'] ?? 0) > 0 || (int) ($summary['dead_letter'] ?? 0) > 0 || (int) ($summary['failed'] ?? 0) > 0) {
        return redirect('/vendor?page=distribution')
            ->with('portal_active_panel', 'distribution')
            ->withErrors(['distribution' => 'Dispatch attempt failed. Event remains in retry workflow.']);
    }

    return redirect('/vendor?page=distribution')
        ->with('portal_active_panel', 'distribution')
        ->withErrors(['distribution' => 'No outbound event was dispatched.']);
});

Route::get('/vendor/messages', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $category = vendorPortalCanonicalCategory((string) request()->query('category', session('portal_listing_category', '')));
    $scope = strtolower(trim((string) request()->query('scope', 'all')));
    if (!in_array($scope, ['active', 'pending', 'history', 'all'], true)) {
        $scope = 'all';
    }

    $query = '/vendor?page=messages';
    $query .= '&scope=' . urlencode($scope);
    if ($category !== '') {
        $query .= '&category=' . urlencode($category);
    }

    return redirect($query)
        ->with('portal_active_panel', 'messages')
        ->with('portal_listing_category', $category);
});

Route::get('/vendor/promotions', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Promotions and customer care tools are locked until your vendor account is verified and approved by admin.']);
    }

    VendorPortalAuditLogger::log('vendor_workspace.promotions_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:promotions',
    ]);

    return redirect('/vendor?page=promotions')->with('portal_active_panel', 'engagement');
});

Route::get('/vendor/reports', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    VendorPortalAuditLogger::log('vendor_workspace.reports_opened', [
        'severity' => 'info',
        'target_identifier' => 'workspace:reports',
    ]);

    return redirect('/vendor?page=reports')->with('portal_active_panel', 'overview');
});

Route::get('/vendor/reports/export', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User) {
        return redirect('/portal/vendor/login');
    }

    $commissionRate = 0.12;
    $reservationTables = ['vendor_reservations', 'reservations', 'bookings', 'vendor_bookings'];
    $vendorColumn = null;
    $reservationTable = null;
    foreach ($reservationTables as $table) {
        if (!Schema::hasTable($table)) {
            continue;
        }
        $cols = Schema::getColumnListing($table);
        $colSet = array_flip($cols);
        foreach (['vendor_user_id', 'vendor_id', 'user_id'] as $col) {
            if (isset($colSet[$col])) {
                $vendorColumn = $col;
                $reservationTable = $table;
                break 2;
            }
        }
    }

    $rows = collect();
    if ($reservationTable !== null && $vendorColumn !== null) {
        $cols = Schema::getColumnListing($reservationTable);
        $colSet = array_flip($cols);
        $rows = DB::table($reservationTable)
            ->where($vendorColumn, $vendorUserId)
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    $csvLines = [];
    $csvLines[] = implode(',', [
        'Invoice Ref', 'Customer Name', 'Customer Email',
        'Date', 'Subtotal', 'Tax Total', 'Gross', 'Commission', 'Gateway Fee', 'Expected Payout',
        'Payment Status', 'Booking Status',
    ]);

    foreach ($rows as $reservation) {
        $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
        $subtotal = (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0);
        $taxTotal = (float) ($reservation->total_tax_amount ?? 0);
        $paymentStatus = (string) ($reservation->payment_status ?? 'unpaid');
        $bookingStatus = (string) ($reservation->status ?? 'pending');
        $isSettled = $paymentStatus === 'paid' && in_array($bookingStatus, ['confirmed', 'completed'], true);
        $commission = $isSettled
            ? round((float) ($reservation->commission_amount ?? ($gross * $commissionRate)), 2)
            : 0.0;
        $gatewayFee = $isSettled
            ? round((float) ($reservation->gateway_fee_amount ?? 0), 2)
            : 0.0;
        $payout = max(0, round($gross - $commission - $gatewayFee, 2));
        $invoiceRef = 'INV-' . str_pad((string) ($reservation->id ?? '0'), 6, '0', STR_PAD_LEFT);
        $collectionDate = (string) ($reservation->start_at ?? $reservation->created_at ?? '');
        $collectionDay = strlen($collectionDate) >= 10 ? substr($collectionDate, 0, 10) : 'N/A';
        $customerName = str_replace('"', '""', (string) ($reservation->customer_name ?? ''));
        $customerEmail = str_replace('"', '""', (string) ($reservation->customer_email ?? ''));
        $csvLines[] = implode(',', [
            $invoiceRef,
            '"' . $customerName . '"',
            '"' . $customerEmail . '"',
            $collectionDay,
            number_format($subtotal, 2, '.', ''),
            number_format($taxTotal, 2, '.', ''),
            number_format($gross, 2, '.', ''),
            number_format($commission, 2, '.', ''),
            number_format($gatewayFee, 2, '.', ''),
            number_format($payout, 2, '.', ''),
            $paymentStatus,
            $bookingStatus,
        ]);
    }

    $csvContent = implode("\r\n", $csvLines);
    $filename = 'vendor-report-' . date('Y-m-d') . '.csv';

    VendorPortalAuditLogger::log('vendor_reports.exported', [
        'severity' => 'info',
        'target_identifier' => 'report:' . $filename,
        'rows_exported' => (int) $rows->count(),
    ]);

    return response($csvContent, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
        'Pragma' => 'no-cache',
    ]);
});

if (!function_exists('vendorPortalFindReservationForPrint')) {
    function vendorPortalFindReservationForPrint(int $vendorUserId, int $reservationId): ?object
    {
        if ($vendorUserId <= 0 || $reservationId <= 0) {
            return null;
        }

        $reservationTables = ['vendor_reservations', 'reservations', 'bookings', 'vendor_bookings'];
        foreach ($reservationTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $colSet = array_flip($columns);
            $vendorColumn = null;
            foreach (['vendor_user_id', 'vendor_id', 'user_id'] as $candidate) {
                if (isset($colSet[$candidate])) {
                    $vendorColumn = $candidate;
                    break;
                }
            }

            if (!is_string($vendorColumn) || $vendorColumn === '') {
                continue;
            }

            $row = DB::table($table)
                ->where('id', $reservationId)
                ->where($vendorColumn, $vendorUserId)
                ->first();

            if ($row) {
                $row->__source_table = $table;

                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('vendorPortalPrintVendorLetterhead')) {
    function vendorPortalPrintVendorLetterhead(int $vendorUserId): array
    {
        $fallbackName = 'Vendor';
        if ($vendorUserId > 0) {
            $user = User::query()->find($vendorUserId);
            if ($user instanceof User) {
                $fallbackName = trim((string) ($user->name ?? 'Vendor')) ?: 'Vendor';
            }
        }

        $letterhead = [
            'name' => $fallbackName,
            'email' => '',
            'phone' => '',
            'address' => '',
            'logo_path' => '',
            'logo_url' => '',
        ];

        $resolveStoredLogo = static function (?string $path): string {
            $raw = trim((string) $path);
            if ($raw === '') {
                return '';
            }
            if (preg_match('/^https?:\/\//i', $raw)) {
                return $raw;
            }

            return Storage::disk('public')->url(ltrim($raw, '/'));
        };

        if (Schema::hasTable('vendor_billing_details')) {
            $billing = DB::table('vendor_billing_details')
                ->where('vendor_user_id', $vendorUserId)
                ->first();

            if ($billing) {
                $letterhead['name'] = trim((string) ($billing->business_name ?? $letterhead['name'])) ?: $letterhead['name'];
                $letterhead['email'] = trim((string) ($billing->billing_email ?? ''));
                $letterhead['phone'] = trim((string) ($billing->billing_phone ?? ''));
                $letterhead['address'] = trim((string) ($billing->billing_address ?? ''));

                foreach (['letterhead_logo_path', 'logo_path', 'company_logo_path', 'brand_logo_path'] as $logoColumn) {
                    if (isset($billing->{$logoColumn}) && trim((string) ($billing->{$logoColumn} ?? '')) !== '') {
                        $letterhead['logo_path'] = trim((string) ($billing->{$logoColumn} ?? ''));
                        break;
                    }
                }
            }
        }

        if ($letterhead['logo_path'] === '' && Schema::hasTable('vendor_profiles')) {
            $profile = DB::table('vendor_profiles')
                ->where('vendor_user_id', $vendorUserId)
                ->first();
            if ($profile) {
                foreach (['logo_path', 'company_logo_path', 'brand_logo_path'] as $logoColumn) {
                    if (isset($profile->{$logoColumn}) && trim((string) ($profile->{$logoColumn} ?? '')) !== '') {
                        $letterhead['logo_path'] = trim((string) ($profile->{$logoColumn} ?? ''));
                        break;
                    }
                }
            }
        }

        if ($letterhead['logo_path'] === '' && Schema::hasTable('users')) {
            $user = DB::table('users')->where('id', $vendorUserId)->first();
            if ($user) {
                foreach (['profile_photo_path', 'logo_path', 'avatar_path'] as $logoColumn) {
                    if (isset($user->{$logoColumn}) && trim((string) ($user->{$logoColumn} ?? '')) !== '') {
                        $letterhead['logo_path'] = trim((string) ($user->{$logoColumn} ?? ''));
                        break;
                    }
                }
            }
        }

        $letterhead['logo_url'] = $resolveStoredLogo($letterhead['logo_path']);

        return $letterhead;
    }
}

if (!function_exists('vendorPortalPrintWorkationLetterhead')) {
    function vendorPortalPrintWorkationLetterhead(): array
    {
        return [
            'name' => (string) config('app.name', 'Workation'),
            'email' => '',
            'phone' => '',
            'address' => '',
        ];
    }
}

Route::get('/vendor/print/reservation/{reservationId}', function (int $reservationId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $reservation = vendorPortalFindReservationForPrint($vendorUserId, $reservationId);
    if (!$reservation) {
        return redirect('/vendor?page=reservations')->withErrors(['reservation' => 'Reservation not found or access denied.']);
    }

    $reservationCode = trim((string) ($reservation->reservation_code ?? ''));
    if ($reservationCode === '') {
        $reservationCode = 'RSV-' . str_pad((string) ($reservation->id ?? 0), 6, '0', STR_PAD_LEFT);
    }
    $vendorLetterhead = vendorPortalPrintVendorLetterhead($vendorUserId);

    VendorPortalAuditLogger::log('vendor_print.reservation', [
        'severity' => 'info',
        'target_identifier' => 'reservation:' . (int) ($reservation->id ?? 0),
        'source_table' => (string) ($reservation->__source_table ?? 'unknown'),
    ]);

    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Reservation ' . e($reservationCode) . '</title>'
        . '<style>body{font-family:Segoe UI,Arial,sans-serif;color:#1e2f42;padding:20px}h1{font-size:20px;margin:0 0 12px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.card{border:1px solid #d6e1ea;border-radius:10px;padding:10px}.lbl{font-size:11px;letter-spacing:.06em;color:#567089;text-transform:uppercase}.val{margin-top:4px;font-weight:700}</style>'
        . '</head><body onload="window.print()">'
        . '<div style="border:1px solid #d6e1ea;border-radius:10px;padding:10px;margin-bottom:12px;background:#f8fbff">'
        . (trim((string) ($vendorLetterhead['logo_url'] ?? '')) !== ''
            ? '<div style="margin-bottom:8px;"><img src="' . e((string) ($vendorLetterhead['logo_url'] ?? '')) . '" alt="Vendor logo" style="max-height:52px;max-width:220px;object-fit:contain;"></div>'
            : '')
        . '<div style="font-size:18px;font-weight:800;">' . e((string) ($vendorLetterhead['name'] ?? 'Vendor')) . '</div>'
        . '<div style="font-size:12px;color:#5e7489;">Vendor Letterhead · Powered by ' . e((string) config('app.name', 'Workation')) . '</div>'
        . '</div>'
        . '<h1>Reservation Summary</h1>'
        . '<div class="grid">'
        . '<div class="card"><div class="lbl">Reservation Code</div><div class="val">' . e($reservationCode) . '</div></div>'
        . '<div class="card"><div class="lbl">Booking Status</div><div class="val">' . e(strtoupper((string) ($reservation->status ?? 'pending'))) . '</div></div>'
        . '<div class="card"><div class="lbl">Guest Name</div><div class="val">' . e((string) ($reservation->customer_name ?? 'N/A')) . '</div></div>'
        . '<div class="card"><div class="lbl">Guest Email</div><div class="val">' . e((string) ($reservation->customer_email ?? 'N/A')) . '</div></div>'
        . '<div class="card"><div class="lbl">Check-In</div><div class="val">' . e((string) ($reservation->start_at ?? 'N/A')) . '</div></div>'
        . '<div class="card"><div class="lbl">Check-Out</div><div class="val">' . e((string) ($reservation->end_at ?? 'N/A')) . '</div></div>'
        . '</div>'
        . '</body></html>';

    return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
});

Route::get('/vendor/print/invoice/{reservationId}', function (int $reservationId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $reservation = vendorPortalFindReservationForPrint($vendorUserId, $reservationId);
    if (!$reservation) {
        return redirect('/vendor?page=billing')->withErrors(['billing' => 'Invoice record not found or access denied.']);
    }

    $invoiceRef = 'INV-' . str_pad((string) ($reservation->id ?? 0), 6, '0', STR_PAD_LEFT);
    $currency = (string) ($reservation->currency ?? 'MVR');
    $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
    $subtotal = (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0);
    $taxTotal = (float) ($reservation->total_tax_amount ?? 0);
    $vendorLetterhead = vendorPortalPrintVendorLetterhead($vendorUserId);

    VendorPortalAuditLogger::log('vendor_print.invoice', [
        'severity' => 'info',
        'target_identifier' => 'invoice:' . $invoiceRef,
        'reservation_id' => (int) ($reservation->id ?? 0),
    ]);

    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Invoice ' . e($invoiceRef) . '</title>'
        . '<style>body{font-family:Segoe UI,Arial,sans-serif;color:#1e2f42;padding:20px}h1{font-size:20px;margin:0 0 12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d9e4ee;padding:8px;text-align:left}th{background:#f5faff;font-size:12px;text-transform:uppercase;color:#48637b}</style>'
        . '</head><body onload="window.print()">'
        . '<div style="border:1px solid #d6e1ea;border-radius:10px;padding:10px;margin-bottom:12px;background:#f8fbff">'
        . (trim((string) ($vendorLetterhead['logo_url'] ?? '')) !== ''
            ? '<div style="margin-bottom:8px;"><img src="' . e((string) ($vendorLetterhead['logo_url'] ?? '')) . '" alt="Vendor logo" style="max-height:52px;max-width:220px;object-fit:contain;"></div>'
            : '')
        . '<div style="font-size:18px;font-weight:800;">' . e((string) ($vendorLetterhead['name'] ?? 'Vendor')) . '</div>'
        . '<div style="font-size:12px;color:#5e7489;">Vendor Invoice Template · Powered by ' . e((string) config('app.name', 'Workation')) . '</div>'
        . '</div>'
        . '<h1>Invoice ' . e($invoiceRef) . '</h1>'
        . '<p><strong>Customer:</strong> ' . e((string) ($reservation->customer_name ?? 'N/A')) . ' (' . e((string) ($reservation->customer_email ?? 'N/A')) . ')</p>'
        . '<table><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>'
        . '<tr><td>Subtotal</td><td>' . e($currency) . ' ' . number_format($subtotal, 2) . '</td></tr>'
        . '<tr><td>Tax</td><td>' . e($currency) . ' ' . number_format($taxTotal, 2) . '</td></tr>'
        . '<tr><td><strong>Total</strong></td><td><strong>' . e($currency) . ' ' . number_format($gross, 2) . '</strong></td></tr>'
        . '</tbody></table>'
        . '</body></html>';

    return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
});

Route::get('/vendor/print/bill/{reservationId}', function (int $reservationId) {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $reservation = vendorPortalFindReservationForPrint($vendorUserId, $reservationId);
    if (!$reservation) {
        return redirect('/vendor?page=billing')->withErrors(['billing' => 'Billing record not found or access denied.']);
    }

    $commissionRate = 0.12;
    $currency = (string) ($reservation->currency ?? 'MVR');
    $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
    $paymentStatus = strtolower(trim((string) ($reservation->payment_status ?? 'unpaid')));
    $bookingStatus = strtolower(trim((string) ($reservation->status ?? 'pending')));
    $isSettled = $paymentStatus === 'paid' && in_array($bookingStatus, ['confirmed', 'completed'], true);
    $commission = $isSettled ? round((float) ($reservation->commission_amount ?? ($gross * $commissionRate)), 2) : 0.0;
    $gatewayFee = $isSettled ? round((float) ($reservation->gateway_fee_amount ?? 0), 2) : 0.0;
    $payout = max(0, round($gross - $commission - $gatewayFee, 2));
    $billRef = 'BILL-' . str_pad((string) ($reservation->id ?? 0), 6, '0', STR_PAD_LEFT);
    $workationLetterhead = vendorPortalPrintWorkationLetterhead();

    VendorPortalAuditLogger::log('vendor_print.bill', [
        'severity' => 'info',
        'target_identifier' => 'bill:' . $billRef,
        'reservation_id' => (int) ($reservation->id ?? 0),
    ]);

    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Bill ' . e($billRef) . '</title>'
        . '<style>body{font-family:Segoe UI,Arial,sans-serif;color:#1e2f42;padding:20px}h1{font-size:20px;margin:0 0 12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d9e4ee;padding:8px;text-align:left}th{background:#f5faff;font-size:12px;text-transform:uppercase;color:#48637b}</style>'
        . '</head><body onload="window.print()">'
        . '<div style="border:1px solid #d6e1ea;border-radius:10px;padding:10px;margin-bottom:12px;background:#f8fbff">'
        . '<div style="font-size:18px;font-weight:800;">' . e((string) ($workationLetterhead['name'] ?? 'Workation')) . '</div>'
        . '<div style="font-size:12px;color:#5e7489;">Workation Settlement Template</div>'
        . '</div>'
        . '<h1>Vendor Bill ' . e($billRef) . '</h1>'
        . '<table><thead><tr><th>Line Item</th><th>Amount</th></tr></thead><tbody>'
        . '<tr><td>Gross Collection</td><td>' . e($currency) . ' ' . number_format($gross, 2) . '</td></tr>'
        . '<tr><td>Platform Commission</td><td>' . e($currency) . ' ' . number_format($commission, 2) . '</td></tr>'
        . '<tr><td>Gateway Fee</td><td>' . e($currency) . ' ' . number_format($gatewayFee, 2) . '</td></tr>'
        . '<tr><td><strong>Net Payout</strong></td><td><strong>' . e($currency) . ' ' . number_format($payout, 2) . '</strong></td></tr>'
        . '</tbody></table>'
        . '</body></html>';

    return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
});

Route::get('/vendor/print/report', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    if ($vendorUserId <= 0) {
        return redirect('/portal/vendor/login');
    }

    $reservationTables = ['vendor_reservations', 'reservations', 'bookings', 'vendor_bookings'];
    $rows = collect();
    foreach ($reservationTables as $table) {
        if (!Schema::hasTable($table)) {
            continue;
        }

        $columns = Schema::getColumnListing($table);
        $colSet = array_flip($columns);
        $vendorColumn = null;
        foreach (['vendor_user_id', 'vendor_id', 'user_id'] as $candidate) {
            if (isset($colSet[$candidate])) {
                $vendorColumn = $candidate;
                break;
            }
        }
        if (!is_string($vendorColumn) || $vendorColumn === '') {
            continue;
        }

        $rows = DB::table($table)
            ->where($vendorColumn, $vendorUserId)
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        if ($rows->isNotEmpty()) {
            break;
        }
    }

    $gross = (float) $rows->sum(static fn ($r) => (float) ($r->invoice_total_amount ?? $r->total_amount ?? 0));
    $paidCount = (int) $rows->filter(static fn ($r) => strtolower(trim((string) ($r->payment_status ?? 'unpaid'))) === 'paid')->count();
    $pendingCount = (int) $rows->filter(static fn ($r) => strtolower(trim((string) ($r->status ?? 'pending'))) === 'pending')->count();
    $cancelledCount = (int) $rows->filter(static fn ($r) => in_array(strtolower(trim((string) ($r->status ?? ''))), ['cancelled', 'cancel_requested'], true))->count();
    $workationLetterhead = vendorPortalPrintWorkationLetterhead();

    VendorPortalAuditLogger::log('vendor_print.report', [
        'severity' => 'info',
        'target_identifier' => 'report:print',
        'rows' => (int) $rows->count(),
    ]);

    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Workation Vendor Report</title>'
        . '<style>body{font-family:Segoe UI,Arial,sans-serif;color:#1e2f42;padding:20px}h1{font-size:20px;margin:0 0 12px}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.card{border:1px solid #d6e1ea;border-radius:10px;padding:10px}.lbl{font-size:11px;letter-spacing:.06em;color:#567089;text-transform:uppercase}.val{margin-top:4px;font-weight:700}.small{font-size:12px;color:#5f7489;margin-top:8px}</style>'
        . '</head><body onload="window.print()">'
        . '<div style="border:1px solid #d6e1ea;border-radius:10px;padding:10px;margin-bottom:12px;background:#f8fbff">'
        . '<div style="font-size:18px;font-weight:800;">' . e((string) ($workationLetterhead['name'] ?? 'Workation')) . '</div>'
        . '<div style="font-size:12px;color:#5e7489;">Workation Reporting Template</div>'
        . '</div>'
        . '<h1>Workation Vendor Report</h1>'
        . '<div class="grid">'
        . '<div class="card"><div class="lbl">Total Reservations</div><div class="val">' . (int) $rows->count() . '</div></div>'
        . '<div class="card"><div class="lbl">Paid Reservations</div><div class="val">' . $paidCount . '</div></div>'
        . '<div class="card"><div class="lbl">Pending Reservations</div><div class="val">' . $pendingCount . '</div></div>'
        . '<div class="card"><div class="lbl">Cancelled</div><div class="val">' . $cancelledCount . '</div></div>'
        . '</div>'
        . '<p class="small">Gross collection (approx): MVR ' . number_format($gross, 2) . '</p>'
        . '<p class="small">Generated on: ' . e(now()->format('Y-m-d H:i')) . '</p>'
        . '</body></html>';

    return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
});
