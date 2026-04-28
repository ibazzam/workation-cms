<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partners Portal | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @include('vendor-portal.partials.portal-styles')
</head>
<body>
    @php
        // Defensive fallback: prevents runtime failures if any legacy summary bindings remain.
        $summary = $summary ?? [
            'upcoming_bookings' => 0,
            'completed_bookings' => 0,
            'receipts_available' => 0,
            'notification_state' => 'ACTIVE',
        ];
        $vendorProperties = $vendorProperties ?? collect();
        $vendorServices = $vendorServices ?? collect();
        $vendorAvailability = $vendorAvailability ?? collect();
        $vendorReservations = $vendorReservations ?? collect();
        $vendorPricingRules = $vendorPricingRules ?? collect();
        $vendorBilling = $vendorBilling ?? null;
        $vendorCategoryMap = $vendorCategoryMap ?? [];
        $selectedVendorCategories = $selectedVendorCategories ?? [];
        $vendorOnboardingStep = $vendorOnboardingStep ?? 1;
        $vendorRoomCategories = $vendorRoomCategories ?? collect();
        $vendorRooms = $vendorRooms ?? $vendorRoomCategories;
        $vendorMediaAssets = $vendorMediaAssets ?? collect();
        $vendorCanManageListings = (bool) ($vendorCanManageListings ?? false);
        $approvedVendorCategories = collect($vendorProfile['approved_categories'] ?? [])
            ->map(static function ($categoryKey) {
                return vendorPortalCanonicalCategory((string) $categoryKey);
            })
            ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
            ->unique()
            ->values();
        $vendorTaxComponents = $vendorTaxComponents ?? collect();
        $vendorEngagement = is_array($vendorEngagement ?? null) ? $vendorEngagement : [];
        $vendorDashboardSnapshot = is_array($vendorDashboardSnapshot ?? null) ? $vendorDashboardSnapshot : [];
        $engagementInquiriesTable = (string) ($vendorEngagement['inquiries_table'] ?? '');
        $engagementInquiries = collect($vendorEngagement['inquiries'] ?? []);
        $engagementReviewsTable = (string) ($vendorEngagement['reviews_table'] ?? '');
        $engagementReviews = collect($vendorEngagement['reviews'] ?? []);
        $engagementPromotions = collect($vendorEngagement['promotions'] ?? []);
        $engagementLoyaltyTable = (string) ($vendorEngagement['loyalty_table'] ?? '');
        $engagementLoyaltyPrograms = collect($vendorEngagement['loyalty_programs'] ?? []);
        $engagementLoyalCustomers = collect($vendorEngagement['loyal_customers'] ?? []);
        $categorySet = collect($selectedVendorCategories)->flip();
        $registeredVendorCategories = collect($selectedVendorCategories ?? [])
            ->map(static function ($categoryKey) {
                return vendorPortalCanonicalCategory((string) $categoryKey);
            })
            ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
            ->unique()
            ->values();
        $vendorAllowedCategoryKeys = $approvedVendorCategories->isNotEmpty()
            ? ($registeredVendorCategories->isNotEmpty()
                ? $registeredVendorCategories->intersect($approvedVendorCategories)->values()
                : $approvedVendorCategories->values())
            : $registeredVendorCategories->values();
        $pendingVendorCategories = $approvedVendorCategories->isNotEmpty()
            ? $registeredVendorCategories->diff($approvedVendorCategories)->values()
            : collect();
        $vendorAllowedCategoryLabels = $vendorAllowedCategoryKeys
            ->map(static function ($categoryKey) use ($vendorCategoryMap) {
                return (string) ($vendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', (string) $categoryKey)));
            })
            ->values();
        $vendorPendingCategoryLabels = $pendingVendorCategories
            ->map(static function ($categoryKey) use ($vendorCategoryMap) {
                return (string) ($vendorCategoryMap[$categoryKey] ?? ucwords(str_replace('_', ' ', (string) $categoryKey)));
            })
            ->values();
        $hasCategoryAccess = $vendorAllowedCategoryKeys->isNotEmpty();
        $supportsAccommodation = $categorySet->has('accommodation');
        $hasSelectedCategories = count($selectedVendorCategories) > 0;
        $listingWizardStep = (int) session('listing_wizard_step', 1);
        $listingWizardStep = max(1, min(4, $listingWizardStep));
        $portalPageQuery = strtolower(trim((string) request()->query('page', '')));
        $activePortalPage = in_array($portalPageQuery, ['overview', 'reports', 'profile', 'listings', 'reservations', 'operations', 'availability', 'pricing', 'billing', 'engagement', 'promotions'], true)
            ? $portalPageQuery
            : 'overview';
        $panelFromPageQuery = match ($activePortalPage) {
            'profile' => 'profile',
            'listings' => 'listings',
            'reservations', 'operations', 'availability', 'pricing' => 'reservations',
            'billing' => 'billing',
            'engagement', 'promotions' => 'engagement',
            'reports', 'overview' => 'overview',
            default => '',
        };
        $showProfilePage = $activePortalPage === 'profile';
        $showListingsPage = $activePortalPage === 'listings';
        $showReservationsPage = in_array($activePortalPage, ['reservations', 'operations', 'availability'], true);
        $showPricingPage = $activePortalPage === 'pricing';
        $showBillingPage = $activePortalPage === 'billing';
        $showEngagementPage = in_array($activePortalPage, ['engagement', 'promotions'], true);
        $showOverviewPage = in_array($activePortalPage, ['overview', 'reports'], true);
        $forcedPanelKey = (string) session('portal_active_panel', $panelFromPageQuery);
        $forcedListingMode = strtolower(trim((string) session('portal_listing_mode', '')));
        $forcedListingCategory = strtolower(trim((string) request()->query('category', session('portal_listing_category', ''))));
        $showWorkspaceTabs = in_array($activePortalPage, ['listings', 'reservations', 'operations', 'availability', 'pricing', 'billing'], true);
        $workspacePrimaryPage = match (true) {
            $showListingsPage => 'listings',
            $showPricingPage => 'pricing',
            $showBillingPage => 'billing',
            default => 'reservations',
        };
        $workspaceCategoryTabKeys = collect($listingCategoryViewOrder ?? $vendorAllowedCategoryKeys)
            ->map(static fn ($categoryKey) => vendorPortalCanonicalCategory((string) $categoryKey))
            ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
            ->values();
        $workspaceCategoryQuery = $forcedListingCategory !== '' ? ('?category=' . urlencode($forcedListingCategory)) : '';
        $workspacePrimaryTabs = [
            [
                'key' => 'listings',
                'label' => 'My Listings',
                'active' => $showListingsPage,
                'href' => '/vendor/listings' . $workspaceCategoryQuery,
            ],
            [
                'key' => 'reservations',
                'label' => 'My Bookings / Reservations',
                'active' => $showReservationsPage,
                'href' => '/vendor/reservations' . $workspaceCategoryQuery,
            ],
            [
                'key' => 'billing',
                'label' => 'Billing / Payments',
                'active' => $showBillingPage,
                'href' => '/vendor/billing' . $workspaceCategoryQuery,
            ],
        ];
        $forcedMediaPanelType = strtolower(trim((string) session('portal_media_panel_type', '')));
        $forcedMediaPanelId = (int) session('portal_media_panel_id', 0);
        $propertyMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'property';
        });
        $roomMediaAssets = $vendorMediaAssets->filter(static function ($media): bool {
            return strtolower((string) ($media->entity_type ?? '')) === 'room';
        });
        $listingCategoryViewOrder = collect(['accommodation', 'marine_transport', 'land_transport', 'water_sports', 'excursion', 'remote_workspace', 'conference_room', 'resort_day_visit', 'restaurant', 'vehicle_rental'])
            ->filter(static function (string $categoryKey) use ($vendorAllowedCategoryKeys): bool {
                return $vendorAllowedCategoryKeys->contains($categoryKey);
            })
            ->values()
            ->all();
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, [
            'marine_transport' => 'Marine Transport',
            'land_transport' => 'Land Transport',
            'conference_room' => 'Conference Rooms',
        ]);
        $roomsByPropertyId = $vendorRooms->groupBy(static function ($room) {
            return (int) ($room->vendor_property_id ?? 0);
        });
        $propertyMediaByEntityId = $propertyMediaAssets->groupBy(static function ($media) {
            return (int) ($media->entity_id ?? 0);
        });
        $propertyMediaByPropertyId = $vendorProperties->mapWithKeys(static function ($property) use ($propertyMediaByEntityId) {
            $canonicalId = (int) ($property->id ?? 0);
            $dedicatedId = (int) ($property->dedicated_row_id ?? 0);

            $mediaItems = collect($propertyMediaByEntityId->get($canonicalId, collect()));
            if ($mediaItems->isEmpty() && $dedicatedId > 0) {
                $mediaItems = collect($propertyMediaByEntityId->get($dedicatedId, collect()));
            }

            return [$canonicalId => $mediaItems];
        });
        $roomMediaByRoomId = $roomMediaAssets->groupBy(static function ($media) {
            return (int) ($media->entity_id ?? 0);
        });
        $propertiesByCategory = $vendorProperties->groupBy(static function ($property) {
            $rawCategory = strtolower(trim((string) ($property->listing_category ?? '')));
            if ($rawCategory !== 'transport') {
                return $rawCategory;
            }

            $details = [];
            if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                $decoded = json_decode((string) $property->listing_details, true);
                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $transportMode = strtolower(trim((string) ($details['transport_mode'] ?? '')));
            return preg_match('/(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/', $transportMode)
                ? 'marine_transport'
                : 'land_transport';
        });
        $propertyLookupById = $vendorProperties->keyBy('id');
        $roomLookupById = $vendorRoomCategories->keyBy('id');
        $showCreatePropertyForm = old('property_form_intent') === '1' || $forcedListingMode === 'create';
        $showCreateRoomForm = old('room_form_intent') === '1';
        $commissionRate = 0.12;
        $billingReservationsSource = $vendorReservations;
        if ($forcedListingCategory !== '') {
            $billingReservationsSource = $billingReservationsSource->filter(function ($reservation) use ($forcedListingCategory, $propertyLookupById) {
                $notes = json_decode((string) ($reservation->notes ?? ''), true);
                $notes = is_array($notes) ? $notes : [];

                $reservationCategory = vendorPortalCanonicalCategory((string) ($notes['category_key'] ?? $notes['listing_category'] ?? ''));
                if (is_string($reservationCategory) && $reservationCategory !== '') {
                    return $reservationCategory === $forcedListingCategory;
                }

                $propertyId = (int) ($reservation->vendor_property_id ?? 0);
                $property = $propertyId > 0 ? $propertyLookupById->get($propertyId) : null;
                if ($property) {
                    $propertyCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
                    if (is_string($propertyCategory) && $propertyCategory !== '') {
                        return $propertyCategory === $forcedListingCategory;
                    }
                }

                return false;
            })->values();
        }

        $billingLedgerRows = $billingReservationsSource->take(50)->map(function ($reservation) use ($commissionRate) {
            $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
            $subtotal = (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0);
            $taxTotal = (float) ($reservation->total_tax_amount ?? 0);
            $serviceChargeTotal = (float) ($reservation->service_charge_total ?? 0);
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

            return [
                'invoice_ref' => $invoiceRef,
                'customer_name' => (string) ($reservation->customer_name ?? 'N/A'),
                'customer_email' => (string) ($reservation->customer_email ?? ''),
                'collection_day' => $collectionDay,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'service_charge_total' => $serviceChargeTotal,
                'green_tax_total' => (float) ($reservation->green_tax_total ?? 0),
                'tgst_total' => (float) ($reservation->tgst_total ?? 0),
                'cgst_total' => (float) ($reservation->cgst_total ?? 0),
                'guest_is_foreigner' => (bool) ($reservation->guest_is_foreigner ?? true),
                'gross' => $gross,
                'commission' => $commission,
                'gateway_fee' => $gatewayFee,
                'payout' => $payout,
                'currency' => (string) ($reservation->currency ?? 'MVR'),
                'payment_status' => $paymentStatus,
                'booking_status' => $bookingStatus,
                'is_settled' => $isSettled,
            ];
        });
        $dailyCollection = $billingLedgerRows->groupBy('collection_day')->map(function ($rows) {
            return [
                'gross' => (float) $rows->sum('gross'),
                'commission' => (float) $rows->sum('commission'),
                'gateway_fee' => (float) $rows->sum('gateway_fee'),
                'payout' => (float) $rows->sum('payout'),
                'count' => (int) $rows->count(),
            ];
        })->sortKeysDesc();
        $settledInvoicesCount = (int) $billingLedgerRows->where('is_settled', true)->count();
        $grossCollectionsTotal = (float) ($billingLedgerRows->sum('gross') ?: ($vendorDashboardSnapshot['gross_collections_total'] ?? 0));
        $commissionTotal = (float) $billingLedgerRows->sum('commission');
        $gatewayFeeTotal = (float) $billingLedgerRows->sum('gateway_fee');
        $payoutTotal = (float) $billingLedgerRows->sum('payout');
        $expectedPayoutTotal = (float) $billingLedgerRows->where('is_settled', false)->sum('payout');
        $settledPayoutTotal = (float) $billingLedgerRows->where('is_settled', true)->sum('payout');
        $vendorListingCount = (int) (($vendorDashboardSnapshot['listing_total'] ?? 0) ?: ($vendorProperties->count() + $vendorServices->count()));
        $vendorActiveListingCount = (int) (($vendorDashboardSnapshot['listing_active'] ?? 0) ?: ($vendorProperties->where('status', 'active')->count() + $vendorServices->where('status', 'active')->count()));
        $vendorPendingReservationsCount = (int) (($vendorDashboardSnapshot['pending_reservations'] ?? 0) ?: $vendorReservations->filter(fn ($reservation) => strtolower(trim((string) ($reservation->status ?? ''))) === 'pending')->count());
        $vendorConfirmedReservationsCount = (int) (($vendorDashboardSnapshot['confirmed_reservations'] ?? 0) ?: $vendorReservations->filter(fn ($reservation) => in_array(strtolower(trim((string) ($reservation->status ?? ''))), ['confirmed', 'upcoming'], true))->count());
        $vendorCompletedReservationsCount = (int) (($vendorDashboardSnapshot['completed_reservations'] ?? 0) ?: $vendorReservations->filter(fn ($reservation) => strtolower(trim((string) ($reservation->status ?? ''))) === 'completed')->count());
        $vendorReservationsCount = (int) (($vendorDashboardSnapshot['reservations_count'] ?? 0) ?: $vendorReservations->count());
        $vendorAverageBookingValue = $vendorReservationsCount > 0 ? round($grossCollectionsTotal / max(1, $vendorReservationsCount), 2) : 0.0;
        $vendorUnresolvedCareCount = (int) $engagementInquiries->whereNotIn('status', ['resolved', 'closed', 'replied'])->count();
        $vendorPendingReviewResponses = (int) $engagementReviews->filter(fn ($row) => trim((string) ($row['response'] ?? '')) === '')->count();
        $vendorRefundCases = $billingReservationsSource->filter(function ($reservation) {
            $status = strtolower(trim((string) ($reservation->status ?? '')));
            $paymentStatus = strtolower(trim((string) ($reservation->payment_status ?? '')));
            return in_array($status, ['cancelled', 'canceled', 'refunded'], true) || $paymentStatus === 'refunded';
        });
        $vendorRefundCaseCount = (int) $vendorRefundCases->count();
        $vendorRefundExposureTotal = (float) $vendorRefundCases->sum(fn ($reservation) => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0));
        $hasPricingSetup = (($vendorDashboardSnapshot['has_pricing_rules'] ?? false) === true) || $vendorPricingRules->count() > 0;
        $hasAvailabilitySetup = (($vendorDashboardSnapshot['has_availability'] ?? false) === true) || $vendorAvailability->count() > 0;
        $hasBillingSetup = (($vendorDashboardSnapshot['has_billing'] ?? false) === true) || (bool) $vendorBilling;
        $vendorGoLiveProgress = $vendorListingCount > 0
            ? min(100, (int) round((($vendorActiveListingCount > 0 ? 35 : 0) + ($hasPricingSetup ? 20 : 0) + ($hasAvailabilitySetup ? 20 : 0) + ($hasBillingSetup ? 25 : 0))))
            : 0;
    @endphp
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <div class="hero-top">
                <div class="hero-head">
                    <span class="eyebrow">Vendor Workspace</span>
                    <h1>Partner Operations Center</h1>
                    <p>A clean single workspace for listings, reservations, availability, pricing, collections, payouts, and customer engagement.</p>
                </div>
                <div class="hero-actions">
                    <div class="auth-bar">
                        <span class="auth-user">Signed in as {{ $portalUser }}</span>
                        <form method="POST" action="/portal/vendor/logout">
                            @csrf
                            <button class="logout" type="submit">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="hero-highlights" aria-label="Vendor dashboard highlights">
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Live Listings</p>
                    <p class="hero-highlight-value">{{ $vendorActiveListingCount }} / {{ $vendorListingCount }}</p>
                    <p class="hero-highlight-meta">Active listings ready for reservations</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Reservations in Flow</p>
                    <p class="hero-highlight-value">{{ $vendorPendingReservationsCount + $vendorConfirmedReservationsCount }}</p>
                    <p class="hero-highlight-meta">Pending and confirmed guest reservations</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Gross Earnings</p>
                    <p class="hero-highlight-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    <p class="hero-highlight-meta">Revenue tracked across current vendor bookings</p>
                </article>
                <article class="hero-highlight">
                    <p class="hero-highlight-label">Go-Live Progress</p>
                    <p class="hero-highlight-value">{{ $vendorGoLiveProgress }}%</p>
                    <p class="hero-highlight-meta">Listings, pricing, availability, and billing readiness</p>
                </article>
            </div>
            <div class="hero-links" aria-label="Quick actions">
                <a class="hero-link" href="/vendor/listings">Manage Listings</a>
                <a class="hero-link" href="/vendor/reservations">Moderate Reservations</a>
                <a class="hero-link" href="/vendor/availability">Update Availability</a>
                <a class="hero-link" href="/vendor/pricing">Adjust Pricing</a>
                <a class="hero-link" href="/vendor/billing">Collections &amp; Payouts</a>
            </div>
        </section>

        <section class="card vendor-trust-strip" aria-label="Vendor category verification status">
            <div class="vendor-trust-strip-head">
                <p class="label">Service Access &amp; Verification</p>
                <span class="ops-chip">Admin-governed</span>
            </div>
            <div class="vendor-trust-strip-grid">
                <article class="vendor-trust-metric">
                    <p class="metric-label">Unlocked Categories</p>
                    <p class="metric-value">{{ $vendorAllowedCategoryLabels->count() }}</p>
                    <p class="small">Only these categories are visible in Listings, Operations, Availability, and Pricing.</p>
                </article>
                <article class="vendor-trust-metric">
                    <p class="metric-label">Pending Verification</p>
                    <p class="metric-value">{{ $vendorPendingCategoryLabels->count() }}</p>
                    <p class="small">Pending categories stay hidden until approved by admin.</p>
                </article>
            </div>
            <div class="vendor-trust-chips" aria-label="Category access tags">
                @forelse ($vendorAllowedCategoryLabels as $categoryLabel)
                    <span class="vendor-status-chip is-approved">{{ $categoryLabel }} - Unlocked</span>
                @empty
                    <span class="vendor-status-chip is-pending">No category unlocked yet</span>
                @endforelse
                @foreach ($vendorPendingCategoryLabels as $categoryLabel)
                    <span class="vendor-status-chip is-pending">{{ $categoryLabel }} - Pending admin verification</span>
                @endforeach
            </div>
        </section>

        @if ($showOverviewPage)
        <section class="card" data-panel-group="overview" aria-label="Vendor operating scope" style="margin-top:10px;">
            <p class="label">How To Operate The Portal</p>
            <p class="small" style="margin-top:0;">Follow this sequence for reliable daily operations: listings -> reservations -> availability -> pricing -> billing -> customer care.</p>
            <div class="ops-metrics" style="margin-top:10px;">
                <article class="ops-metric">
                    <p class="metric-label">My Listings</p>
                    <p class="metric-value">Create / Update / Remove</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Reservations</p>
                    <p class="metric-value">Review / Confirm / Complete</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Availability</p>
                    <p class="metric-value">Daily slot calendar</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Pricing</p>
                    <p class="metric-value">Rates / Tariffs / Rules</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Collections &amp; Payouts</p>
                    <p class="metric-value">Invoice -> Collection -> Payout</p>
                </article>
            </div>
        </section>
        @endif

        <div class="portal-shell">
        @include('vendor-portal.partials.sidebar')

        <div class="portal-content">

        @if ($showOverviewPage)
            @include('vendor-portal.partials.overview')
        @endif

        @if (session('portal_notice'))
            <div class="notice" role="status" aria-live="polite">{{ session('portal_notice') }}</div>
        @endif

        @if ($errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first('profile') }}</div>
        @endif

        @if ($errors->any() && !$errors->has('profile'))
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if ($showWorkspaceTabs)
            <section class="vendor-workspace-nav" aria-label="Vendor workspace navigation">
                <div class="workspace-tabs" role="tablist" aria-label="Vendor workspace tabs">
                    @foreach ($workspacePrimaryTabs as $workspaceTab)
                        <a
                            class="workspace-tab {{ $workspaceTab['active'] ? 'is-active' : '' }}"
                            href="{{ $workspaceTab['href'] }}"
                            role="tab"
                            aria-selected="{{ $workspaceTab['active'] ? 'true' : 'false' }}"
                        >{{ $workspaceTab['label'] }}</a>
                    @endforeach
                </div>
                @if ($workspaceCategoryTabKeys->isNotEmpty())
                    <div class="workspace-category-tabs" role="tablist" aria-label="Vendor category filter">
                        <a
                            class="workspace-category-tab {{ $forcedListingCategory === '' ? 'is-active' : '' }}"
                            href="{{ '/vendor?page=' . $workspacePrimaryPage }}"
                            role="tab"
                            aria-selected="{{ $forcedListingCategory === '' ? 'true' : 'false' }}"
                        >All Categories</a>
                        @foreach ($workspaceCategoryTabKeys as $categoryKey)
                            @php
                                $categoryLabel = (string) ($listingCategoryLabelMap[$categoryKey] ?? ucwords(str_replace('_', ' ', $categoryKey)));
                                $categoryHref = match ($workspacePrimaryPage) {
                                    'listings' => '/vendor/listings/' . $categoryKey,
                                    'billing' => '/vendor/billing?category=' . urlencode($categoryKey),
                                    default => '/vendor/reservations?category=' . urlencode($categoryKey),
                                };
                                $categoryIsActive = $forcedListingCategory === $categoryKey;
                            @endphp
                            <a
                                class="workspace-category-tab {{ $categoryIsActive ? 'is-active' : '' }}"
                                href="{{ $categoryHref }}"
                                role="tab"
                                aria-selected="{{ $categoryIsActive ? 'true' : 'false' }}"
                            >{{ $categoryLabel }}</a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if ($showProfilePage)
            @include('vendor-portal.partials.profile')
        @endif

        @if ($showBillingPage || $showProfilePage)
            @include('vendor-portal.partials.billing-settings')
        @endif

        @if ($showListingsPage)
                @include('vendor-portal.partials.listings-console')
        @endif

        @if ($showReservationsPage)
            @include('vendor-portal.partials.category-operations')
        @endif

        @if ($showPricingPage)
            @include('vendor-portal.partials.pricing')
        @endif

        @if ($showBillingPage)
            @include('vendor-portal.partials.billing-collection')
            @include('vendor-portal.partials.payout-status')
        @endif

        <section class="layout" id="vendorAuthApi" data-panel-group="api">
            <article class="card" id="vendorAuthCard">
                <p class="label">Auth</p>
                <input id="tokenInput" class="token-input" type="password" placeholder="Paste vendor JWT bearer token">
                <div>
                    <button id="saveToken" class="btn btn-primary" type="button">Save Token</button>
                    <button id="clearToken" class="btn btn-secondary" type="button">Clear</button>
                </div>
                <div id="tokenState" class="state warn">TOKEN NOT SET</div>
                <div id="tokenMeta" class="token-meta">Token is stored only in this browser tab session.</div>
            </article>

            <article class="card" id="vendorApiCard">
                <p class="label">Vendor API Actions</p>
                <div class="endpoint">
                    <code>GET /api/v1/auth/me</code>
                    <button type="button" data-path="/api/v1/auth/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/bookings (customer reservations)</code>
                    <button type="button" data-path="/api/v1/bookings">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/loyalty/me</code>
                    <button type="button" data-path="/api/v1/loyalty/me">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/vendor/me/settlements/report</code>
                    <button type="button" data-path="/api/v1/payments/vendor/me/settlements/report">Run</button>
                </div>
                <pre id="output">Ready. Save token, then run an endpoint.</pre>
            </article>
        </section>


        @if ($showEngagementPage)
            @include('vendor-portal.partials.engagement')
        @endif
        </div>
        </div>
        @include('partials.global-site-footer')
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            if (window.__vendorPortalPrimaryInitDone === true) {
                return;
            }
            window.__vendorPortalPrimaryInitDone = true;

            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const tokenMeta = document.getElementById("tokenMeta");
            const output = document.getElementById("output");
            const summaryBookings = document.getElementById("summaryBookings");
            const summarySettlements = document.getElementById("summarySettlements");
            const summaryToken = document.getElementById("summaryToken");
            const summaryTokenMeta = document.getElementById("summaryTokenMeta");
            const summaryConnectivity = document.getElementById("summaryConnectivity");
            const summaryLastSync = document.getElementById("summaryLastSync");
            const refreshSummaryBtn = document.getElementById("refreshSummary");
            const payoutSettledTotal = document.getElementById("payoutSettledTotal");
            const payoutPendingTotal = document.getElementById("payoutPendingTotal");
            const payoutNextEstimate = document.getElementById("payoutNextEstimate");
            const payoutRows = document.getElementById("payoutRows");
            const navLinks = Array.from(document.querySelectorAll('.portal-nav a[data-panel-key]'));
            const vendorNavGroupToggles = Array.from(document.querySelectorAll('[data-vendor-nav-toggle]'));
            const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
            const listingStepPanels = Array.from(document.querySelectorAll('[data-listing-step]'));
            const categoryOpsCards = Array.from(document.querySelectorAll('[data-ops-category-section]'));
            const validPanelKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));
            const forcedListingMode = "{{ $forcedListingMode }}";
            const forcedListingCategory = "{{ $forcedListingCategory }}";
            const locationCountry = document.getElementById("location_country");
            const locationState = document.getElementById("location_state");
            const locationCity = document.getElementById("location_city");
            const mapLatitude = document.getElementById("map_latitude");
            const mapLongitude = document.getElementById("map_longitude");
            const mapPlaceId = document.getElementById("map_place_id");
            const billingCountry = document.getElementById("billing_country");
            const billingState = document.getElementById("billing_state");
            const billingCity = document.getElementById("billing_city");
            const closePropertyCreateForm = document.getElementById("closePropertyCreateForm");
            const backToListingsFromCreate = document.getElementById("backToListingsFromCreate");
            const propertyCreateForm = document.getElementById("propertyCreateForm");
            const propertyCreateFormTitle = document.getElementById("propertyCreateFormTitle");
            const propertyCreateFormSubtitle = document.getElementById("propertyCreateFormSubtitle");
            const propertyCreateSubmitButton = document.getElementById("propertyCreateSubmitButton");
            const propertyCategorySelect = document.getElementById("property_listing_category");
            const propertyTypeSelect = document.getElementById("property_type");
            const propertyCategoryScopeNote = document.getElementById("propertyCategoryScopeNote");
            const propertyBasePriceLabel = document.querySelector('label[for="property_base_price"]');
            const propertyMaxGuestsLabel = document.querySelector('label[for="property_max_guests"]');
            const propertyCapacityLabel = document.querySelector('label[for="property_capacity_value"]');
            const transportModeInput = document.getElementById("property_transport_mode");
            const transportPricingHint = document.getElementById("transportPricingHint");
            const transportPricingModelSelect = document.getElementById("property_transport_pricing_model");
            const transportLandOnlyFields = Array.from(document.querySelectorAll("[data-transport-land-only]"));
            const transportMarineOnlyFields = Array.from(document.querySelectorAll("[data-transport-marine-only]"));
            const categoryScopedFields = Array.from(document.querySelectorAll("[data-category-scope]"));
            const categoryViewPanels = Array.from(document.querySelectorAll('[data-category-view]'));
            const openRoomCreateForm = document.getElementById("openRoomCreateForm");
            const closeRoomCreateForm = document.getElementById("closeRoomCreateForm");
            const roomCreateForm = document.getElementById("roomCreateForm");
            const roomPropertySelect = document.getElementById("room_vendor_property_id");
            const roomQuickOpenButtons = Array.from(document.querySelectorAll("[data-open-room-form]"));
            const inlineRoomRows = Array.from(document.querySelectorAll("[data-inline-room-row]"));
            const inlineRoomCloseButtons = Array.from(document.querySelectorAll("[data-close-inline-room-row]"));
            const propertyEditButtons = Array.from(document.querySelectorAll('[data-open-property-edit]'));
            const propertyEditCancelButtons = Array.from(document.querySelectorAll('[data-close-property-edit]'));
            const roomEditButtons = Array.from(document.querySelectorAll('[data-open-room-edit]'));
            const roomEditCancelButtons = Array.from(document.querySelectorAll('[data-close-room-edit]'));
            const propertyMediaToggleButtons = Array.from(document.querySelectorAll('[data-toggle-property-media]'));
            const propertyMediaCloseButtons = Array.from(document.querySelectorAll('[data-close-property-media]'));
            const roomMediaToggleButtons = Array.from(document.querySelectorAll('[data-toggle-room-media]'));
            const roomMediaCloseButtons = Array.from(document.querySelectorAll('[data-close-room-media]'));
            const gallerySelectionForms = Array.from(document.querySelectorAll('[data-gallery-selection-form]'));
            const listingCategoryShortcutButtons = Array.from(document.querySelectorAll('[data-listing-category-shortcut]'));
            const propertyListingRows = Array.from(document.querySelectorAll('[data-property-row]'));
            const guidedTrackProperty = document.getElementById("guidedTrackProperty");
            const guidedWizardSteps = document.getElementById("guidedWizardSteps");
            const guidedWizardStepText = document.getElementById("guidedWizardStepText");
            const guidedWizardProgressFill = document.getElementById("guidedWizardProgressFill");
            const guidedWizardPrev = document.getElementById("guidedWizardPrev");
            const guidedWizardResume = document.getElementById("guidedWizardResume");
            const guidedWizardNext = document.getElementById("guidedWizardNext");
            const serverPanelKey = "{{ in_array($forcedPanelKey, ['overview', 'profile', 'listings', 'billing', 'reservations', 'engagement', 'api'], true) ? $forcedPanelKey : '' }}";
            const forcedMediaPanelType = "{{ in_array($forcedMediaPanelType, ['property', 'room'], true) ? $forcedMediaPanelType : '' }}";
            const forcedMediaPanelId = Number("{{ $forcedMediaPanelId }}") || 0;
            const listingWizardStep = Number("{{ $listingWizardStep }}") || 1;
            let listingWizardStarted = serverPanelKey === "listings";
            let listingWizardPanelStep = 1;
            let guidedWizardTrack = "property";
            let guidedWizardIndex = 0;
            const vendorPropertiesCount = Number("{{ $vendorProperties->count() }}") || 0;
            const vendorRoomsCount = Number("{{ $vendorRooms->count() }}") || 0;
            const vendorBillingReady = "{{ $vendorBilling ? '1' : '0' }}" === "1";
            const GUIDED_WIZARD_STORAGE_KEY = "workation_vendor_guided_wizard";

            function bindGallerySelection(form) {
                const selectAll = form.querySelector('[data-gallery-select-all]');
                const deleteButton = form.querySelector('[data-gallery-bulk-delete-button]');
                const items = Array.from(form.querySelectorAll('[data-gallery-select-item]'));

                if (!deleteButton || items.length === 0) {
                    return;
                }

                const sync = function () {
                    const selectedCount = items.filter(function (item) { return item.checked; }).length;
                    deleteButton.disabled = selectedCount === 0;
                    deleteButton.textContent = 'Delete Selected (' + selectedCount + ')';
                    if (selectAll) {
                        selectAll.checked = selectedCount > 0 && selectedCount === items.length;
                        selectAll.indeterminate = selectedCount > 0 && selectedCount < items.length;
                    }
                };

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        const checked = !!selectAll.checked;
                        items.forEach(function (item) {
                            item.checked = checked;
                        });
                        sync();
                    });
                }

                items.forEach(function (item) {
                    item.addEventListener('change', sync);
                });

                sync();
            }

            gallerySelectionForms.forEach(bindGallerySelection);

            const guidedWizardFlows = {
                property: [
                    {
                        title: "Property setup",
                        hint: "Choose category and set listing basics.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                    },
                    {
                        title: "Review and refine",
                        hint: "Confirm created property and update details.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 2,
                    },
                    {
                        title: "Room inventory",
                        hint: "Add room types and occupancy for each property.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                        openRoomForm: true,
                    },
                    {
                        title: "Photos and media",
                        hint: "Upload property and room photos.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                    },
                    {
                        title: "Publish readiness",
                        hint: "Check pricing, availability, and billing before go-live.",
                        panel: "reservations",
                        targetId: "vendorPricingSection",
                    },
                ],
            };

            const SESSION_KEY = "workation_vendor_token";

            function setState(type, text) {
                tokenState.className = "state " + type;
                tokenState.textContent = text;
            }

            function setMeta(text) {
                if (tokenMeta) {
                    tokenMeta.textContent = text;
                }
            }

            function getToken() {
                return sessionStorage.getItem(SESSION_KEY) || "";
            }

            function decodeBase64Url(value) {
                try {
                    const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
                    const padded = normalized + "=".repeat((4 - (normalized.length % 4)) % 4);
                    return atob(padded);
                } catch (error) {
                    return "";
                }
            }

            function parseJwtPayload(token) {
                const parts = String(token || "").split(".");
                if (parts.length !== 3) {
                    return null;
                }
                const payloadRaw = decodeBase64Url(parts[1]);
                if (!payloadRaw) {
                    return null;
                }
                try {
                    return JSON.parse(payloadRaw);
                } catch (error) {
                    return null;
                }
            }

            function formatDuration(seconds) {
                const total = Math.max(0, Math.floor(seconds));
                const hours = Math.floor(total / 3600);
                const minutes = Math.floor((total % 3600) / 60);
                if (hours > 0) {
                    return hours + "h " + minutes + "m";
                }
                return minutes + "m";
            }

            function formatDateTime(epochSeconds) {
                return new Date(epochSeconds * 1000).toLocaleString();
            }

            function evaluateToken(token) {
                const payload = parseJwtPayload(token);
                if (!payload) {
                    return {
                        isValidFormat: false,
                        isUsable: false,
                        stateType: "err",
                        stateText: "INVALID TOKEN FORMAT",
                        metaText: "Expected a JWT with 3 parts: header.payload.signature"
                    };
                }

                const exp = Number(payload.exp);
                if (!Number.isFinite(exp)) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN SAVED (NO EXP)",
                        metaText: "No expiration claim found. Token expiry cannot be predicted."
                    };
                }

                const now = Math.floor(Date.now() / 1000);
                const secondsLeft = exp - now;
                const expiresAt = formatDateTime(exp);
                if (secondsLeft <= 0) {
                    return {
                        isValidFormat: true,
                        isUsable: false,
                        stateType: "err",
                        stateText: "TOKEN EXPIRED",
                        metaText: "Expired at " + expiresAt + ". Save a fresh token."
                    };
                }

                if (secondsLeft <= 5 * 60) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN EXPIRING SOON",
                        metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                    };
                }

                return {
                    isValidFormat: true,
                    isUsable: true,
                    stateType: "ok",
                    stateText: "TOKEN READY",
                    metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                };
            }

            function applyTokenFeedback(token, fallbackType, fallbackStateText, fallbackMetaText) {
                if (!token) {
                    setState(fallbackType || "warn", fallbackStateText || "TOKEN NOT SET");
                    setMeta(fallbackMetaText || "Token is stored only in this browser tab session.");
                    return;
                }

                const verdict = evaluateToken(token);
                setState(verdict.stateType, verdict.stateText);
                setMeta(verdict.metaText);
            }

            function saveToken() {
                const value = (tokenInput.value || "").trim();
                if (!value) {
                    setState("warn", "TOKEN NOT SET");
                    setMeta("Paste a JWT token to continue.");
                    return;
                }

                const verdict = evaluateToken(value);
                if (!verdict.isValidFormat || !verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    return;
                }

                sessionStorage.setItem(SESSION_KEY, value);
                tokenInput.value = "";
                applyTokenFeedback(value, "ok", "TOKEN SAVED");
                refreshSummary();
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
                setMeta("Token removed from this tab session.");
                setSummaryDefaults();
            }

            function setSummaryDefaults() {
                if (summaryBookings) summaryBookings.textContent = "-";
                if (summarySettlements) summarySettlements.textContent = "-";
                if (summaryToken) summaryToken.textContent = "N/A";
                if (summaryTokenMeta) summaryTokenMeta.textContent = "Save token to evaluate readiness";
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill warn";
                    summaryConnectivity.textContent = "UNKNOWN";
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: not run yet";
                }

                if (payoutSettledTotal) payoutSettledTotal.textContent = "MVR 0.00";
                if (payoutPendingTotal) payoutPendingTotal.textContent = "MVR 0.00";
                if (payoutNextEstimate) payoutNextEstimate.textContent = "N/A";
                if (payoutRows) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">Refresh summary to load payout data.</td></tr>';
                }
            }

            function formatCurrency(value) {
                const amount = Number(value);
                if (!Number.isFinite(amount)) {
                    return "MVR 0.00";
                }
                return "MVR " + amount.toFixed(2);
            }

            function normalizeSettlementRows(payload) {
                if (Array.isArray(payload)) return payload;
                if (payload && Array.isArray(payload.data)) return payload.data;
                if (payload && Array.isArray(payload.items)) return payload.items;
                return [];
            }

            function extractAmount(row) {
                const candidates = [row && row.amount, row && row.net_amount, row && row.total, row && row.value];
                for (const value of candidates) {
                    const parsed = Number(value);
                    if (Number.isFinite(parsed)) {
                        return parsed;
                    }
                }
                return 0;
            }

            function toRowStatus(row) {
                const raw = String((row && (row.status || row.state)) || "").trim();
                return raw === "" ? "UNKNOWN" : raw.toUpperCase();
            }

            function toRowReference(row, index) {
                return String((row && (row.reference || row.settlement_id || row.id || row.code)) || "SETTLEMENT-" + (index + 1));
            }

            function toRowDate(row) {
                const raw = String((row && (row.paid_at || row.created_at || row.date)) || "").trim();
                if (!raw) return "N/A";
                const date = new Date(raw);
                if (Number.isNaN(date.getTime())) return raw;
                return date.toLocaleDateString();
            }

            function renderPayoutCenter(payload) {
                const rows = normalizeSettlementRows(payload);
                let settledTotal = 0;
                let pendingTotal = 0;

                rows.forEach((row) => {
                    const amount = extractAmount(row);
                    const status = toRowStatus(row);
                    if (status.includes("SETTLED") || status.includes("PAID") || status.includes("COMPLETED")) {
                        settledTotal += amount;
                    } else {
                        pendingTotal += amount;
                    }
                });

                if (payoutSettledTotal) payoutSettledTotal.textContent = formatCurrency(settledTotal);
                if (payoutPendingTotal) payoutPendingTotal.textContent = formatCurrency(pendingTotal);

                const nextEstimateDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000);
                if (payoutNextEstimate) {
                    payoutNextEstimate.textContent = rows.length === 0
                        ? "N/A"
                        : nextEstimateDate.toLocaleDateString();
                }

                if (!payoutRows) return;
                if (rows.length === 0) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">No settlements returned for this token yet.</td></tr>';
                    return;
                }

                payoutRows.innerHTML = rows.slice(0, 8).map((row, index) => {
                    const reference = toRowReference(row, index);
                    const status = toRowStatus(row);
                    const amount = formatCurrency(extractAmount(row));
                    const date = toRowDate(row);
                    return '<tr><td>' + reference + '</td><td>' + status + '</td><td>' + amount + '</td><td>' + date + '</td></tr>';
                }).join('');
            }

            let panelStateInitialized = false;

            function initializePanelStateSafe() {
                if (panelStateInitialized) {
                    return;
                }
                panelStateInitialized = true;

                const hashPanelKey = resolvePanelFromHash(window.location.hash || "#overview");
                const initialPanelKey = serverPanelKey && validPanelKeys.has(serverPanelKey) ? serverPanelKey : hashPanelKey;
                listingWizardPanelStep = listingPanelStepFromWizardStep(listingWizardStep);
                showPanelGroup(initialPanelKey);

                if (initialPanelKey === "listings") {
                    if (serverPanelKey === "listings") {
                        activateListingWizardStep(listingWizardStep, true);
                    } else {
                        applyListingWizardVisibility();
                    }
                }
            }

            async function fetchJsonWithAuth(path, token) {
                const res = await fetch(apiBase + path, {
                    method: "GET",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                const bodyText = await res.text();
                let json = null;
                try {
                    json = JSON.parse(bodyText);
                } catch (error) {
                    json = null;
                }

                return { ok: res.ok, status: res.status, json: json, text: bodyText };
            }

            function deriveCount(payload) {
                if (Array.isArray(payload)) {
                    return payload.length;
                }
                if (payload && Array.isArray(payload.data)) {
                    return payload.data.length;
                }
                if (payload && Array.isArray(payload.items)) {
                    return payload.items.length;
                }
                if (payload && Number.isFinite(Number(payload.total))) {
                    return Number(payload.total);
                }
                return null;
            }

            function setConnectivity(type, label, lastSyncText) {
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill " + type;
                    summaryConnectivity.textContent = label;
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: " + lastSyncText;
                }
            }

            function setActiveNavLink(panelKey) {
                const candidates = navLinks.filter((link) => {
                    return (link.dataset.panelKey || "") === panelKey;
                });

                if (candidates.length === 0) {
                    setExactActiveNavLink(null);
                    return;
                }

                const currentUrl = new URL(window.location.href);
                const currentPath = currentUrl.pathname.replace(/\/+$/, "") || "/";
                const currentSearch = currentUrl.search;
                let bestLink = candidates[0];
                let bestScore = -1;

                candidates.forEach((link) => {
                    let score = 0;
                    let linkUrl = null;
                    try {
                        linkUrl = new URL(String(link.getAttribute("href") || ""), window.location.origin);
                    } catch (error) {
                        linkUrl = null;
                    }

                    if (linkUrl) {
                        const linkPath = linkUrl.pathname.replace(/\/+$/, "") || "/";
                        const linkSearch = linkUrl.search;
                        if (linkPath === currentPath) {
                            score += 3;
                        }
                        if (linkSearch === currentSearch) {
                            score += 2;
                        }

                        const linkPage = linkUrl.searchParams.get("page");
                        const currentPage = currentUrl.searchParams.get("page");
                        if (linkPage && currentPage && linkPage === currentPage) {
                            score += 1;
                        }
                    }

                    if (score > bestScore) {
                        bestScore = score;
                        bestLink = link;
                    }
                });

                setExactActiveNavLink(bestLink);
            }

            function setExactActiveNavLink(activeLink) {
                navLinks.forEach((link) => {
                    link.classList.toggle("is-active", !!activeLink && link === activeLink);
                });
            }

            function showPanelGroup(panelKey) {
                const hasMatchingPanel = panelGroups.some((panel) => {
                    return (panel.getAttribute("data-panel-group") || "") === panelKey;
                });
                if (!hasMatchingPanel) {
                    setActiveNavLink(panelKey);
                    return;
                }

                panelGroups.forEach((panel) => {
                    panel.hidden = (panel.getAttribute("data-panel-group") || "") !== panelKey;
                });
                setActiveNavLink(panelKey);

                if (panelKey === "listings") {
                    if (!listingWizardStarted) {
                        listingWizardStarted = true;
                        listingWizardPanelStep = 1;
                    }
                    applyListingWizardVisibility();
                } else {
                    setListingPanelsHidden(true);
                }
            }

            function resolvePanelFromHash(hashValue) {
                const panelKey = String(hashValue || "").replace(/^#/, "").trim().toLowerCase();
                return validPanelKeys.has(panelKey) ? panelKey : "overview";
            }

            function normalizeVendorOpsCategoryKey(categoryKey) {
                const normalized = normalizeCategoryKey(categoryKey || '');
                if (normalized === 'transport') {
                    return 'marine_transport';
                }
                return normalized;
            }

            function applyVendorCategoryOperationsFilter(categoryKey) {
                const normalized = normalizeVendorOpsCategoryKey(categoryKey || 'all');
                if (categoryOpsCards.length === 0) {
                    return;
                }

                categoryOpsCards.forEach((card) => {
                    const sectionKey = String(card.getAttribute('data-ops-category-section') || '');
                    const cardCategory = normalizeVendorOpsCategoryKey(sectionKey.replace('category-operations-', ''));
                    card.hidden = normalized !== 'all' && cardCategory !== normalized;
                });

                if (normalized === 'all') {
                    return;
                }

                const activeCard = categoryOpsCards.find((card) => {
                    const sectionKey = String(card.getAttribute('data-ops-category-section') || '');
                    return normalizeVendorOpsCategoryKey(sectionKey.replace('category-operations-', '')) === normalized;
                });

                if (!activeCard) {
                    return;
                }

                const toggle = activeCard.querySelector('[data-ops-category-toggle]');
                if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
                    toggle.click();
                }
                activeCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function focusListingsWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, Number(step) || 1));
                const stepTargets = {
                    1: "vendorPropertiesSection",
                    2: "vendorPropertiesSection",
                    3: "vendorPropertiesSection",
                    4: "vendorPropertiesSection"
                };
                const targetId = stepTargets[safeStep] || "vendorPropertiesSection";
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;
                targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            function listingPanelStepFromWizardStep(step) {
                return 1;
            }

            function setListingPanelsHidden(hidden) {
                listingStepPanels.forEach((panel) => {
                    panel.hidden = hidden;
                });
            }

            function applyListingWizardVisibility() {
                const activeStepPanel = 1;
                listingStepPanels.forEach((panel) => {
                    const panelStep = Number(panel.getAttribute("data-listing-step") || "0");
                    panel.hidden = panelStep !== activeStepPanel;
                });
            }

            function activateListingWizardStep(wizardStep, shouldScroll) {
                listingWizardStarted = true;
                listingWizardPanelStep = listingPanelStepFromWizardStep(wizardStep);
                applyListingWizardVisibility();
                if (shouldScroll) {
                    focusListingsWizardStep(wizardStep);
                }
            }

            function guidedWizardCurrentFlow() {
                const flow = guidedWizardFlows[guidedWizardTrack];
                return Array.isArray(flow) ? flow : guidedWizardFlows.property;
            }

            function guidedWizardCanMoveToIndex(targetIndex) {
                const safeTargetIndex = Math.max(0, Number(targetIndex) || 0);

                if (guidedWizardTrack === "property") {
                    if (safeTargetIndex >= 1 && vendorPropertiesCount <= 0) {
                        return {
                            ok: false,
                            message: "Create at least one property to continue to review, room setup, and media steps.",
                        };
                    }

                    if (safeTargetIndex >= 3 && vendorRoomsCount <= 0) {
                        return {
                            ok: false,
                            message: "Add at least one room before progressing to media-focused property flow.",
                        };
                    }

                    if (safeTargetIndex >= 4 && !vendorBillingReady) {
                        return {
                            ok: false,
                            message: "Complete billing profile before final publish readiness.",
                        };
                    }
                }

                return { ok: true, message: "" };
            }

            function persistGuidedWizardState() {
                const payload = {
                    track: guidedWizardTrack,
                    index: guidedWizardIndex,
                    savedAt: Date.now(),
                };
                try {
                    sessionStorage.setItem(GUIDED_WIZARD_STORAGE_KEY, JSON.stringify(payload));
                } catch (error) {
                    // Ignore storage errors in private/incognito contexts.
                }
            }

            function restoreGuidedWizardState() {
                try {
                    const raw = sessionStorage.getItem(GUIDED_WIZARD_STORAGE_KEY);
                    if (!raw) {
                        return false;
                    }
                    const parsed = JSON.parse(raw);
                    const track = String(parsed.track || "").toLowerCase();
                    const index = Number(parsed.index);
                    if (!(track in guidedWizardFlows)) {
                        return false;
                    }
                    const flow = guidedWizardFlows[track];
                    if (!Array.isArray(flow) || flow.length === 0) {
                        return false;
                    }
                    guidedWizardTrack = track;
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, Number.isFinite(index) ? index : 0));
                    return true;
                } catch (error) {
                    return false;
                }
            }

            function applyGuidedWizardStep(shouldScroll) {
                const flow = guidedWizardCurrentFlow();
                const safeIndex = Math.max(0, Math.min(flow.length - 1, Number(guidedWizardIndex) || 0));
                guidedWizardIndex = safeIndex;
                const currentStep = flow[safeIndex];
                if (!currentStep) {
                    return;
                }

                showPanelGroup(String(currentStep.panel || "listings"));

                if (typeof currentStep.wizardStep === "number") {
                    activateListingWizardStep(currentStep.wizardStep, Boolean(shouldScroll));
                } else if (shouldScroll && currentStep.targetId) {
                    const target = document.getElementById(currentStep.targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                }

                if (currentStep.openPropertyForm && propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                    if (closePropertyCreateForm) {
                        closePropertyCreateForm.hidden = false;
                    }
                }

                if (currentStep.openRoomForm && roomCreateForm) {
                    roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) {
                        closeRoomCreateForm.hidden = false;
                    }
                }
            }

            function renderGuidedWizard() {
                const flow = guidedWizardCurrentFlow();
                if (!guidedWizardSteps || !guidedWizardStepText || !guidedWizardProgressFill) {
                    return;
                }

                if (guidedTrackProperty) {
                    guidedTrackProperty.classList.toggle("is-active", guidedWizardTrack === "property");
                }

                guidedWizardSteps.innerHTML = "";
                flow.forEach((step, index) => {
                    const item = document.createElement("li");
                    item.className = "guided-step";
                    if (index < guidedWizardIndex) {
                        item.classList.add("is-complete");
                    }
                    if (index === guidedWizardIndex) {
                        item.classList.add("is-active");
                    }
                    item.textContent = "Step " + (index + 1) + ": " + step.title;
                    item.addEventListener("click", function () {
                        guidedWizardIndex = index;
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    });
                    guidedWizardSteps.appendChild(item);
                });

                const progressPercent = flow.length > 1
                    ? Math.round((guidedWizardIndex / (flow.length - 1)) * 100)
                    : 100;
                guidedWizardProgressFill.style.width = String(progressPercent) + "%";

                const activeStep = flow[guidedWizardIndex];
                guidedWizardStepText.textContent = "Step " + (guidedWizardIndex + 1) + " of " + flow.length + " - " + activeStep.hint;

                if (guidedWizardPrev) {
                    guidedWizardPrev.disabled = guidedWizardIndex <= 0;
                }
                if (guidedWizardNext) {
                    const isLastStep = guidedWizardIndex >= flow.length - 1;
                    guidedWizardNext.textContent = isLastStep ? "Go To Final Step" : "Next Step";

                    const targetIndex = Math.min(flow.length - 1, guidedWizardIndex + 1);
                    const gateCheck = guidedWizardCanMoveToIndex(targetIndex);
                    guidedWizardNext.disabled = !gateCheck.ok;
                    if (!gateCheck.ok) {
                        guidedWizardNext.title = gateCheck.message;
                        guidedWizardStepText.textContent = guidedWizardStepText.textContent + " | " + gateCheck.message;
                    } else {
                        guidedWizardNext.title = "";
                    }
                }

                persistGuidedWizardState();
            }

            const FALLBACK_LOCATION_TREE = {
                "Maldives": {
                    "Kaafu Atoll": ["Male", "Hulhumale", "Maafushi"],
                    "Alif Alif Atoll": ["Rasdhoo", "Ukulhas", "Thoddoo"],
                    "Alif Dhaal Atoll": ["Dhigurah", "Dhangethi", "Mahibadhoo"],
                    "Baa Atoll": ["Eydhafushi", "Dharavandhoo", "Maalhos"]
                },
                "Sri Lanka": {
                    "Western Province": ["Colombo", "Negombo", "Kalutara"],
                    "Southern Province": ["Galle", "Matara", "Hambantota"],
                    "Central Province": ["Kandy", "Nuwara Eliya", "Matale"]
                },
                "India": {
                    "Kerala": ["Kochi", "Thiruvananthapuram", "Kozhikode"],
                    "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru"],
                    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai"]
                },
                "Other": {
                    "Other": ["Other"]
                }
            };

            let locationTreeCache = FALLBACK_LOCATION_TREE;
            let locationTreePromise = null;

            function getCurrentLocationTree() {
                return locationTreeCache || FALLBACK_LOCATION_TREE;
            }

            function applyLocationTree(data) {
                if (!data || typeof data !== "object" || Array.isArray(data)) {
                    return getCurrentLocationTree();
                }
                locationTreeCache = data;
                window.__vendorPortalLocationTree = data;
                try {
                    window.sessionStorage.setItem("vendor_portal_location_tree_v1", JSON.stringify(data));
                } catch (error) {
                    // Ignore storage failures and continue with in-memory cache.
                }
                return locationTreeCache;
            }

            function getLocationTree() {
                if (window.__vendorPortalLocationTree && typeof window.__vendorPortalLocationTree === "object") {
                    locationTreeCache = window.__vendorPortalLocationTree;
                    return Promise.resolve(locationTreeCache);
                }

                if (locationTreePromise) {
                    return locationTreePromise;
                }

                locationTreePromise = new Promise(function (resolve) {
                    let restoredFromSession = false;

                    try {
                        const cachedPayload = window.sessionStorage.getItem("vendor_portal_location_tree_v1");
                        if (cachedPayload) {
                            const parsed = JSON.parse(cachedPayload);
                            applyLocationTree(parsed);
                            restoredFromSession = true;
                            resolve(getCurrentLocationTree());
                        }
                    } catch (error) {
                        restoredFromSession = false;
                    }

                    if (restoredFromSession) {
                        return;
                    }

                    // Source Maldives Atoll/Island data from shared API so vendor listings stay in sync
                    // with the same atlas used by blog and customer-facing forms.
                    fetch('/api/atoll-island/atolls', { cache: 'no-store' })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Atoll list request failed with status ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (atolls) {
                            if (!Array.isArray(atolls) || atolls.length === 0) {
                                throw new Error('No atolls returned from API');
                            }

                            const hasEmbeddedIslands = atolls.some(function (atoll) {
                                return Array.isArray(atoll && atoll.islands) && atoll.islands.length > 0;
                            });

                            if (hasEmbeddedIslands) {
                                const maldivesTree = {};
                                atolls.forEach(function (atoll) {
                                    const atollName = String(atoll && atoll.name ? atoll.name : '').trim();
                                    if (atollName === '') {
                                        return;
                                    }
                                    const islandNames = Array.isArray(atoll && atoll.islands)
                                        ? atoll.islands
                                            .map(function (island) {
                                                return String(island && island.name ? island.name : '').trim();
                                            })
                                            .filter(function (name) { return name !== ''; })
                                        : [];
                                    maldivesTree[atollName] = islandNames;
                                });

                                if (Object.keys(maldivesTree).length > 0) {
                                    return maldivesTree;
                                }
                            }

                            const atollRequests = atolls.map(function (atoll) {
                                const atollId = Number(atoll && atoll.id ? atoll.id : 0);
                                const atollName = String(atoll && atoll.name ? atoll.name : '').trim();
                                if (atollId <= 0 || atollName === '') {
                                    return Promise.resolve(null);
                                }

                                return fetch('/api/atoll-island/atolls/' + atollId + '/islands', { cache: 'no-store' })
                                    .then(function (islandsResponse) {
                                        if (!islandsResponse.ok) {
                                            return [];
                                        }
                                        return islandsResponse.json();
                                    })
                                    .then(function (islands) {
                                        const islandNames = Array.isArray(islands)
                                            ? islands
                                                .map(function (island) {
                                                    return String(island && island.name ? island.name : '').trim();
                                                })
                                                .filter(function (name) { return name !== ''; })
                                            : [];

                                        return {
                                            atollName: atollName,
                                            islandNames: islandNames,
                                        };
                                    })
                                    .catch(function () {
                                        return {
                                            atollName: atollName,
                                            islandNames: [],
                                        };
                                    });
                            });

                            return Promise.all(atollRequests).then(function (atollIslandRows) {
                                const maldivesTree = {};
                                (atollIslandRows || []).forEach(function (row) {
                                    if (!row || !row.atollName) {
                                        return;
                                    }
                                    maldivesTree[row.atollName] = Array.isArray(row.islandNames) ? row.islandNames : [];
                                });
                                return maldivesTree;
                            });
                        })
                        .then(function (maldivesTree) {
                            if (Object.keys(maldivesTree).length === 0) {
                                resolve(getCurrentLocationTree());
                                return;
                            }

                            const mergedTree = {
                                ...FALLBACK_LOCATION_TREE,
                                Maldives: maldivesTree,
                            };
                            resolve(applyLocationTree(mergedTree));
                        })
                        .catch(function () {
                            resolve(getCurrentLocationTree());
                        });
                });

                return locationTreePromise;
            }

            function rebuildSelect(selectEl, values, placeholder) {
                if (!selectEl) return;
                selectEl.innerHTML = "";
                const defaultOption = document.createElement("option");
                defaultOption.value = "";
                defaultOption.textContent = placeholder;
                selectEl.appendChild(defaultOption);

                values.forEach((value) => {
                    const option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    selectEl.appendChild(option);
                });
            }

            function ensureSelectHasOption(selectEl, value) {
                if (!selectEl || !value) return;
                const exists = Array.from(selectEl.options).some((option) => option.value === value);
                if (exists) return;
                const option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                selectEl.appendChild(option);
            }

            function refreshLocationSelectors() {
                if (!locationCountry || !locationState || !locationCity) return;
                const selectedCountry = locationCountry.dataset.selectedValue || locationCountry.value || "Maldives";
                ensureSelectHasOption(locationCountry, selectedCountry);
                locationCountry.value = selectedCountry;
                const country = locationCountry.value || "Maldives";
                const locationTree = getCurrentLocationTree();
                const states = Object.keys(locationTree[country] || {});
                rebuildSelect(locationState, states, "Select state/province");
                const selectedState = locationState.dataset.selectedValue || "";
                ensureSelectHasOption(locationState, selectedState);
                if (selectedState && Array.from(locationState.options).some((option) => option.value === selectedState)) {
                    locationState.value = selectedState;
                } else {
                    locationState.value = states[0] || "";
                }
                const cities = (locationTree[country] || {})[locationState.value] || [];
                rebuildSelect(locationCity, cities, "Select city/island");
                const selectedCity = locationCity.dataset.selectedValue || "";
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }

                locationCountry.dataset.selectedValue = "";
                locationState.dataset.selectedValue = "";
                locationCity.dataset.selectedValue = "";
            }

            function refreshCitySelector() {
                if (!locationCountry || !locationState || !locationCity) return;
                const country = locationCountry.value || "Maldives";
                const locationTree = getCurrentLocationTree();
                const cities = (locationTree[country] || {})[locationState.value] || [];
                const selectedCity = locationCity.dataset.selectedValue || "";
                rebuildSelect(locationCity, cities, "Select city/island");
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }
                locationCity.dataset.selectedValue = "";
            }

            const COUNTRY_MAP_CENTER = {
                maldives: [3.2028, 73.2207, 8],
                "sri lanka": [7.8731, 80.7718, 8],
                india: [20.5937, 78.9629, 5],
            };

            let locationMapContext = null;
            const mapGeocodeCache = new Map();
            let mapLookupRequestId = 0;

            function refreshLocationMapViewport() {
                if (!locationMapContext || !locationMapContext.map) {
                    return;
                }
                const map = locationMapContext.map;
                setTimeout(function () {
                    map.invalidateSize();
                    centerMapForLocationSelection(false);
                }, 120);
            }

            window.__vendorPortalRefreshLocationMap = refreshLocationMapViewport;

            function fallbackMapView(countryRaw) {
                const key = String(countryRaw || '').trim().toLowerCase();
                return COUNTRY_MAP_CENTER[key] || [4.1755, 73.5093, 9];
            }

            function locationSelectionPayload() {
                const country = String(locationCountry && locationCountry.value || '').trim();
                const state = String(locationState && locationState.value || '').trim();
                const city = String(locationCity && locationCity.value || '').trim();
                const queryParts = [city, state, country].filter(Boolean);
                const query = queryParts.join(', ');
                const hasCity = city !== '';
                const hasState = state !== '';
                return { country, state, city, query, hasCity, hasState };
            }

            async function geocodeMapSelection(query) {
                const cacheKey = String(query || '').trim().toLowerCase();
                if (cacheKey === '') {
                    return null;
                }
                if (mapGeocodeCache.has(cacheKey)) {
                    return mapGeocodeCache.get(cacheKey);
                }

                const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(query);
                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                        cache: 'force-cache',
                    });
                    if (!response.ok) {
                        return null;
                    }
                    const rows = await response.json();
                    if (!Array.isArray(rows) || rows.length === 0) {
                        return null;
                    }
                    const first = rows[0] || {};
                    const lat = Number(first.lat);
                    const lng = Number(first.lon);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return null;
                    }
                    const point = [lat, lng];
                    mapGeocodeCache.set(cacheKey, point);
                    return point;
                } catch (error) {
                    return null;
                }
            }

            async function centerMapForLocationSelection(forceLookup) {
                if (!locationMapContext || !locationMapContext.map) {
                    return;
                }

                const map = locationMapContext.map;
                const payload = locationSelectionPayload();
                const fallback = fallbackMapView(payload.country);
                const fallbackPoint = [fallback[0], fallback[1]];
                const fallbackZoom = payload.hasCity ? 11 : (payload.hasState ? 9 : fallback[2]);

                // Move immediately so the map always responds even if geocoding is slow.
                map.flyTo(fallbackPoint, fallbackZoom, { animate: true, duration: 0.28 });

                if (payload.query === '') {
                    return;
                }

                const requestId = ++mapLookupRequestId;
                let targetPoint = null;
                if (forceLookup || payload.hasCity || payload.hasState) {
                    targetPoint = await geocodeMapSelection(payload.query);
                }
                if (requestId !== mapLookupRequestId) {
                    return;
                }

                if (!targetPoint) {
                    return;
                }

                const zoom = payload.hasCity ? 13 : (payload.hasState ? 10 : fallback[2]);
                map.flyTo(targetPoint, zoom, { animate: true, duration: 0.45 });
            }

            function normalizeCategoryKey(value) {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
            }

            function categoryScopesFor(category) {
                const normalized = normalizeCategoryKey(category);

                if (normalized === "accommodation") {
                    return ["stay", "accommodation", "policies", "geo"];
                }

                if (normalized === "transport" || normalized === "marine_transport" || normalized === "land_transport") {
                    return ["capacity", "transport", "policies", "geo"];
                }

                if (normalized === "excursion") {
                    return ["capacity", "service", "excursion", "policies", "geo"];
                }

                if (normalized === "water_sports") {
                    return ["capacity", "service", "excursion", "policies", "geo"];
                }

                if (normalized === "remote_workspace") {
                    return ["stay", "capacity", "workspace", "geo"];
                }

                if (normalized === "conference_room") {
                    return ["capacity", "conference", "policies", "geo"];
                }

                if (normalized === "resort_day_visit") {
                    return ["capacity", "day_visit", "policies", "geo"];
                }

                if (normalized === "restaurant") {
                    return ["capacity", "restaurant", "policies", "geo"];
                }

                if (normalized === "vehicle_rental") {
                    return ["vehicle", "capacity", "rental", "policies", "geo"];
                }

                return ["stay", "accommodation", "capacity", "service", "vehicle", "transport", "excursion", "workspace", "day_visit", "restaurant", "rental", "conference", "policies", "geo"];
            }

            function isMarineTransportMode(value) {
                const mode = String(value || "").trim().toLowerCase();
                return /(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/.test(mode);
            }

            function refreshTransportFieldLabels() {
                if (!propertyCategorySelect) {
                    return;
                }

                const normalizedCategory = normalizeCategoryKey(propertyCategorySelect.value);
                const isTransportCategory = normalizedCategory === "transport" || normalizedCategory === "marine_transport" || normalizedCategory === "land_transport";
                const isRemoteWorkspaceCategory = normalizedCategory === "remote_workspace";
                const isMarine = normalizedCategory === "marine_transport"
                    || (normalizedCategory !== "land_transport" && isMarineTransportMode(transportModeInput ? transportModeInput.value : ""));
                const selectedPricingModel = transportPricingModelSelect ? String(transportPricingModelSelect.value || "per_trip") : "per_trip";

                if (propertyBasePriceLabel) {
                    propertyBasePriceLabel.textContent = isTransportCategory
                        ? (isMarine ? "Price Per Seat (MVR)" : "Price Per Trip (MVR)")
                        : (isRemoteWorkspaceCategory ? "Booking Fee Per Guest (MVR)" : "Base Price (MVR)");
                }

                if (propertyCapacityLabel) {
                    propertyCapacityLabel.textContent = isTransportCategory
                        ? (isMarine ? "Seat Capacity" : "Max Passengers Per Trip")
                        : (isRemoteWorkspaceCategory ? "Workspace Capacity (seats/desks)" : "Capacity");
                }

                if (propertyMaxGuestsLabel) {
                    propertyMaxGuestsLabel.textContent = isTransportCategory
                        ? (isMarine ? "Seat Capacity (Legacy)" : "Max Passengers (Legacy)")
                        : (isRemoteWorkspaceCategory ? "Max Bookable Guests" : "Max Guests");
                }

                if (transportPricingHint) {
                    transportPricingHint.textContent = isTransportCategory
                        ? (isMarine
                            ? "Marine transport mode detected: pricing is per seat. Define pickup and dropoff, then select one-way or round-trip."
                            : "Land transport mode detected: choose per-trip, hourly, or daily pricing and set max passengers per trip.")
                        : "Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.";
                }

                transportLandOnlyFields.forEach((field) => {
                    const shouldShow = isTransportCategory && !isMarine;
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });

                transportMarineOnlyFields.forEach((field) => {
                    const shouldShow = isTransportCategory && isMarine;
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });

                const hourlyField = document.getElementById("property_hourly_rate");
                const dailyField = document.getElementById("property_daily_rate");
                if (hourlyField) {
                    const showHourly = isTransportCategory && !isMarine && selectedPricingModel === "hourly";
                    hourlyField.disabled = !showHourly;
                    if (hourlyField.parentElement) {
                        hourlyField.parentElement.hidden = !showHourly;
                        hourlyField.parentElement.style.display = showHourly ? '' : 'none';
                    }
                }
                if (dailyField) {
                    const showDaily = isTransportCategory && !isMarine && selectedPricingModel === "daily";
                    dailyField.disabled = !showDaily;
                    if (dailyField.parentElement) {
                        dailyField.parentElement.hidden = !showDaily;
                        dailyField.parentElement.style.display = showDaily ? '' : 'none';
                    }
                }
            }

            function refreshCategoryViewPanels() {
                if (!propertyCategorySelect || categoryViewPanels.length === 0) {
                    return;
                }
                const activeCategory = normalizeCategoryKey(propertyCategorySelect.value);
                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view'));
                    panel.hidden = panelCategory !== activeCategory;
                });
            }

            function applyCategorySectionFilter(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || 'all');
                if (categoryViewPanels.length === 0) {
                    return;
                }

                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view') || '');
                    panel.hidden = normalizedCategory !== 'all' && panelCategory !== normalizedCategory;
                });
            }

            function categoryMetaFor(category) {
                const normalized = normalizeCategoryKey(category);
                const fallbackLabel = propertyCategorySelect
                    ? (propertyCategorySelect.options[propertyCategorySelect.selectedIndex]?.textContent || 'Listing')
                    : 'Listing';

                const categoryMeta = {
                    accommodation: {
                        title: 'Accommodation Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Accommodation Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'property',
                    },
                    transport: {
                        title: 'Marine or Land Transport Enlisting',
                        subtitle: 'Choose the transport mode and save the listing.',
                        submit: 'Save Transport Listing',
                        note: 'Use marine mode for boats and ferries, or land mode for cars and vans.',
                        propertyType: 'service',
                    },
                    marine_transport: {
                        title: 'Marine Transport Enlisting',
                        subtitle: 'Capture water transfer details and save.',
                        submit: 'Save Marine Transport Listing',
                        note: 'Use marine transport fields for speedboats, ferries, and vessel transfers.',
                        propertyType: 'service',
                    },
                    land_transport: {
                        title: 'Land Transport Enlisting',
                        subtitle: 'Capture vehicle transfer details and save.',
                        submit: 'Save Land Transport Listing',
                        note: 'Use land transport fields for cars, vans, and local ground transfers.',
                        propertyType: 'service',
                    },
                    excursion: {
                        title: 'Excursion Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Excursion Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    remote_workspace: {
                        title: 'Remote Workspace Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Remote Workspace Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    conference_room: {
                        title: 'Conference Room Enlisting',
                        subtitle: 'Capture venue basics, capacity, and save.',
                        submit: 'Save Conference Room Listing',
                        note: 'Use this for meeting rooms, halls, and event spaces.',
                        propertyType: 'service',
                    },
                    resort_day_visit: {
                        title: 'Resort Day Visit Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Resort Day Visit Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    restaurant: {
                        title: 'Restaurant Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Restaurant Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                    vehicle_rental: {
                        title: 'Vehicle Rental Enlisting',
                        subtitle: 'Fill required fields and save.',
                        submit: 'Save Vehicle Rental Listing',
                        note: 'Fill required fields and save.',
                        propertyType: 'service',
                    },
                };

                return categoryMeta[normalized] || {
                    title: fallbackLabel + ' Enlisting',
                    subtitle: 'Fill required fields and save.',
                    submit: 'Save ' + fallbackLabel + ' Listing',
                    note: 'Fill required fields and save.',
                    propertyType: null,
                };
            }

            function applyCategoryFormMeta(category, forceType) {
                const meta = categoryMetaFor(category);
                if (propertyCreateFormTitle) {
                    propertyCreateFormTitle.textContent = meta.title;
                }
                if (propertyCreateFormSubtitle) {
                    propertyCreateFormSubtitle.textContent = meta.subtitle;
                }
                if (propertyCreateSubmitButton) {
                    propertyCreateSubmitButton.textContent = meta.submit;
                }
                if (propertyCategoryScopeNote) {
                    propertyCategoryScopeNote.textContent = meta.note;
                }
                if (forceType && propertyTypeSelect && meta.propertyType) {
                    ensureSelectHasOption(propertyTypeSelect, meta.propertyType);
                    propertyTypeSelect.value = meta.propertyType;
                }
            }

            function ensureAutoCategorySelected(preferredCategory) {
                if (!propertyCategorySelect) {
                    return '';
                }
                const preferred = normalizeCategoryKey(preferredCategory || propertyCategorySelect.getAttribute('data-default-category') || 'accommodation');
                if (preferred !== '') {
                    let matched = Array.from(propertyCategorySelect.options)
                        .find((option) => normalizeCategoryKey(option.value) === preferred);
                    if (!matched && (preferred === 'marine_transport' || preferred === 'land_transport')) {
                        matched = Array.from(propertyCategorySelect.options)
                            .find((option) => normalizeCategoryKey(option.value) === 'transport');
                    }
                    if (matched) {
                        propertyCategorySelect.value = matched.value;
                    }
                }
                if ((!propertyCategorySelect.value || String(propertyCategorySelect.value).trim() === '') && propertyCategorySelect.options.length > 0) {
                    propertyCategorySelect.value = propertyCategorySelect.options[0].value;
                }
                return String(propertyCategorySelect.value || '');
            }

            function refreshPropertyCategoryFields() {
                if (!propertyCategorySelect || categoryScopedFields.length === 0) return;
                const activeCategory = ensureAutoCategorySelected('');
                const activeScopes = categoryScopesFor(activeCategory);
                categoryScopedFields.forEach((field) => {
                    const scopes = String(field.getAttribute("data-category-scope") || "")
                        .split(",")
                        .map((value) => value.trim().toLowerCase())
                        .filter(Boolean);
                    if (scopes.length === 0) {
                        field.hidden = false;
                        field.style.display = '';
                        return;
                    }
                    const shouldShow = scopes.some((scope) => activeScopes.includes(scope));
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? '' : 'none';
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });
                refreshCategoryViewPanels();
                applyCategoryFormMeta(activeCategory, false);
                refreshTransportFieldLabels();
            }

            function applyPropertyCategoryFilter(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || 'all');
                propertyListingRows.forEach((row) => {
                    const rowCategory = normalizeCategoryKey(row.getAttribute('data-listing-category') || '');
                    const shouldShow = normalizedCategory === 'all' || rowCategory === normalizedCategory;
                    row.hidden = !shouldShow;
                });

            }

            function openPropertyFlowWithCategory(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || '');
                window.location.hash = 'listings';
                showPanelGroup('listings');
                activateListingWizardStep(1, true);

                if (propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                }
                if (closePropertyCreateForm) {
                    closePropertyCreateForm.hidden = false;
                }
                if (propertyCategorySelect) {
                    const selectedCategory = ensureAutoCategorySelected(normalizedCategory);
                    if (transportModeInput && (normalizedCategory === 'marine_transport' || normalizedCategory === 'land_transport')) {
                        transportModeInput.value = normalizedCategory === 'marine_transport' ? 'speedboat' : 'car';
                    }
                    propertyCategorySelect.dispatchEvent(new Event('change'));
                    applyCategoryFormMeta(selectedCategory, true);
                }
                if (document.getElementById('property_name')) {
                    document.getElementById('property_name').focus();
                }

                refreshLocationMapViewport();

                applyPropertyCategoryFilter(normalizedCategory || 'all');
                applyCategorySectionFilter(normalizedCategory || 'all');
            }

            function isFieldVisibleForValidation(field) {
                if (!field || field.disabled || field.type === 'hidden') {
                    return false;
                }
                if (field.closest('[hidden]')) {
                    return false;
                }
                return field.offsetParent !== null;
            }

            function applyFieldValidationState(field) {
                if (!field) {
                    return true;
                }

                const visible = isFieldVisibleForValidation(field);
                const shouldValidate = visible && field.required;
                const isInvalid = shouldValidate && !field.checkValidity();

                field.classList.toggle('is-invalid', isInvalid);
                const fieldWrap = field.closest('.ops-field');
                if (fieldWrap) {
                    fieldWrap.classList.toggle('has-invalid', isInvalid);
                }

                return !isInvalid;
            }

            function validatePropertyCreateForm(showNativeMessage) {
                if (!propertyCreateForm) {
                    return true;
                }

                const requiredFields = Array.from(propertyCreateForm.querySelectorAll('input, select, textarea'))
                    .filter((field) => field.required);

                let firstInvalid = null;
                let allValid = true;
                let invalidCount = 0;

                requiredFields.forEach((field) => {
                    const valid = applyFieldValidationState(field);
                    if (!valid && !firstInvalid) {
                        firstInvalid = field;
                    }
                    if (!valid) {
                        allValid = false;
                        invalidCount++;
                    }
                });

                const errorBanner = document.getElementById('propertyCreateFormError');
                if (errorBanner) {
                    if (!allValid) {
                        const noun = invalidCount === 1 ? 'field' : 'fields';
                        errorBanner.textContent = invalidCount + ' required ' + noun + ' must be completed before saving.';
                        errorBanner.hidden = false;
                    } else {
                        errorBanner.hidden = true;
                    }
                }

                if (!allValid && firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (showNativeMessage && typeof firstInvalid.reportValidity === 'function') {
                        firstInvalid.reportValidity();
                    }
                }

                return allValid;
            }

            function refreshBillingLocationSelectors() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const locationTree = getCurrentLocationTree();
                const states = Object.keys(locationTree[country] || {});
                const previousState = billingState.dataset.selectedValue || billingState.value;
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;

                rebuildSelect(billingState, states, "Select state/province");
                ensureSelectHasOption(billingState, previousState);

                if (previousState && Array.from(billingState.options).some((option) => option.value === previousState)) {
                    billingState.value = previousState;
                } else if (states.length > 0) {
                    billingState.value = states[0];
                }

                const cities = (locationTree[country] || {})[billingState.value] || [];
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);

                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }

                billingState.dataset.selectedValue = "";
                billingCity.dataset.selectedValue = "";
            }

            function refreshBillingCitySelector() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const locationTree = getCurrentLocationTree();
                const cities = (locationTree[country] || {})[billingState.value] || [];
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);
                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }
                billingCity.dataset.selectedValue = "";
            }

            function initLocationMap() {
                if (!window.L) return;
                const mapEl = document.getElementById("propertyMap");
                if (!mapEl) return;

                const defaultLat = Number(mapLatitude && mapLatitude.value) || 4.1755;
                const defaultLng = Number(mapLongitude && mapLongitude.value) || 73.5093;
                const map = window.L.map(mapEl, {
                    preferCanvas: true,
                    zoomControl: true,
                    worldCopyJump: true,
                    inertia: true,
                    fadeAnimation: false,
                    markerZoomAnimation: false,
                }).setView([defaultLat, defaultLng], 11);

                const osmLayer = window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    keepBuffer: 4,
                    updateWhenIdle: true,
                    updateWhenZooming: false,
                    attribution: "&copy; OpenStreetMap contributors"
                });

                osmLayer.addTo(map);

                let marker = window.L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
                locationMapContext = { map, marker };

                function updateLocationFromMap(latlng) {
                    const lat = Number(latlng.lat.toFixed(6));
                    const lng = Number(latlng.lng.toFixed(6));

                    if (mapLatitude) mapLatitude.value = String(lat);
                    if (mapLongitude) mapLongitude.value = String(lng);
                    if (mapPlaceId && mapPlaceId.value.trim() === "") {
                        mapPlaceId.value = "PIN-" + lat + "," + lng;
                    }

                    marker.setLatLng([lat, lng]);
                }

                map.on("click", function (event) {
                    updateLocationFromMap(event.latlng);
                });

                marker.on("dragend", function () {
                    updateLocationFromMap(marker.getLatLng());
                });

                setTimeout(function () {
                    map.invalidateSize();
                }, 180);

                centerMapForLocationSelection(false);
            }

            async function refreshSummary() {
                const token = getToken();
                if (!token) {
                    setSummaryDefaults();
                    return;
                }

                if (refreshSummaryBtn) refreshSummaryBtn.disabled = true;

                const verdict = evaluateToken(token);
                if (summaryToken) {
                    summaryToken.textContent = verdict.stateText.replace("TOKEN ", "");
                }
                if (summaryTokenMeta) {
                    summaryTokenMeta.textContent = verdict.metaText;
                }

                try {
                    const [bookingsResult, settlementsResult] = await Promise.all([
                        fetchJsonWithAuth("/api/v1/bookings", token),
                        fetchJsonWithAuth("/api/v1/payments/vendor/me/settlements/report", token),
                    ]);

                    const bookingsCount = deriveCount(bookingsResult.json);
                    const settlementsCount = deriveCount(settlementsResult.json);
                    if (summaryBookings) {
                        summaryBookings.textContent = bookingsCount === null ? "N/A" : String(bookingsCount);
                    }
                    if (summarySettlements) {
                        summarySettlements.textContent = settlementsCount === null ? "N/A" : String(settlementsCount);
                    }

                    renderPayoutCenter(settlementsResult.json);

                    const nowText = new Date().toLocaleString();
                    if (bookingsResult.ok || settlementsResult.ok) {
                        setConnectivity("ok", "ONLINE", nowText);
                    } else if (bookingsResult.status === 401 || bookingsResult.status === 403 || settlementsResult.status === 401 || settlementsResult.status === 403) {
                        setConnectivity("warn", "AUTH ISSUE", nowText);
                    } else {
                        setConnectivity("err", "OFFLINE", nowText);
                    }
                } catch (error) {
                    setConnectivity("err", "OFFLINE", new Date().toLocaleString());
                    if (summaryBookings) summaryBookings.textContent = "N/A";
                    if (summarySettlements) summarySettlements.textContent = "N/A";
                } finally {
                    if (refreshSummaryBtn) refreshSummaryBtn.disabled = false;
                }
            }

            async function run(path, triggerButton) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    setMeta("Save a vendor token before running requests.");
                    output.textContent = "Save a vendor token first.";
                    return;
                }

                const verdict = evaluateToken(token);
                if (!verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    output.textContent = "Token is expired or invalid. Save a fresh vendor token first.";
                    return;
                }

                const button = triggerButton || null;
                if (button) {
                    button.disabled = true;
                    button.classList.add("is-loading");
                    if (!button.dataset.label) {
                        button.dataset.label = button.textContent || "Run";
                    }
                    button.textContent = "Running";
                }

                output.textContent = "Loading " + path + " ...";
                try {
                    const res = await fetch(apiBase + path, {
                        method: "GET",
                        headers: {
                            "Authorization": "Bearer " + token,
                            "Accept": "application/json"
                        },
                        cache: "no-store"
                    });
                    const text = await res.text();
                    let parsed = text;
                    try {
                        parsed = JSON.stringify(JSON.parse(text), null, 2);
                    } catch (error) {
                        // Keep plain text if response is not JSON.
                    }
                    output.textContent = "Status: " + res.status + "\n\n" + parsed;
                    if (res.ok) {
                        applyTokenFeedback(token, "ok", "TOKEN VALID");
                    } else if (res.status === 401 || res.status === 403) {
                        setState("err", "TOKEN INVALID FOR VENDOR");
                        setMeta("The API rejected this token for vendor access.");
                    } else {
                        applyTokenFeedback(token, "warn", "REQUEST COMPLETED WITH WARNINGS");
                    }
                } catch (error) {
                    setState("err", "REQUEST FAILED");
                    setMeta("Request failed before token validation could complete.");
                    output.textContent = "Network/CORS error. Ensure API allows origin https://www.workation.mv\n\n" + String(error);
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove("is-loading");
                        button.textContent = button.dataset.label || "Run";
                    }
                }
            }

            initializePanelStateSafe();

            const saveTokenButton = document.getElementById("saveToken");
            const clearTokenButton = document.getElementById("clearToken");

            if (saveTokenButton) {
                saveTokenButton.addEventListener("click", saveToken);
            }
            if (clearTokenButton) {
                clearTokenButton.addEventListener("click", clearToken);
            }
            if (refreshSummaryBtn) {
                refreshSummaryBtn.addEventListener("click", refreshSummary);
            }
            if (tokenInput) {
                tokenInput.addEventListener("keydown", function (event) {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        saveToken();
                    }
                });
            }
            document.querySelectorAll("button[data-path]").forEach((button) => {
                button.addEventListener("click", function () {
                    run(button.getAttribute("data-path"), button);
                });
            });

            navLinks.forEach((link) => {
                link.addEventListener("click", function (event) {
                    const href = String(link.getAttribute("href") || "").trim();
                    const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                    if (!panelKey) return;

                    const categoryTarget = normalizeVendorOpsCategoryKey(String(link.getAttribute('data-vendor-category-target') || ''));
                    const listingAction = String(link.getAttribute('data-vendor-listing-action') || '').trim().toLowerCase();

                    if (href !== "" && !href.startsWith("#")) {
                        return;
                    }

                    event.preventDefault();

                    window.location.hash = panelKey;
                    showPanelGroup(panelKey);

                    if (panelKey === 'listings') {
                        if (categoryTarget !== '') {
                            openPropertyFlowWithCategory(categoryTarget);
                        } else if (listingAction === 'create') {
                            const defaultCreateCategory = forcedListingCategory || normalizeVendorOpsCategoryKey((categoryViewPanels[0] && categoryViewPanels[0].getAttribute('data-category-view')) || 'accommodation');
                            openPropertyFlowWithCategory(defaultCreateCategory);
                        } else {
                            applyPropertyCategoryFilter('all');
                            applyCategorySectionFilter('all');
                        }
                    }

                    if (panelKey === 'reservations') {
                        applyVendorCategoryOperationsFilter(categoryTarget !== '' ? categoryTarget : 'all');
                    }

                    setExactActiveNavLink(link);
                });
            });

            vendorNavGroupToggles.forEach((toggle) => {
                toggle.addEventListener('click', function () {
                    const groupKey = String(toggle.getAttribute('data-vendor-nav-toggle') || '').trim();
                    if (groupKey === '') {
                        return;
                    }
                    const body = document.querySelector('[data-vendor-nav-group="' + groupKey + '"]');
                    if (!body) {
                        return;
                    }
                    const isOpen = body.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });

            window.addEventListener("hashchange", function () {
                showPanelGroup(resolvePanelFromHash(window.location.hash));
            });

            applyVendorCategoryOperationsFilter('all');

            if (guidedTrackProperty) {
                guidedTrackProperty.addEventListener("click", function () {
                    guidedWizardTrack = "property";
                    guidedWizardIndex = 0;
                    window.location.hash = "listings";
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardPrev) {
                guidedWizardPrev.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex - 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardResume) {
                guidedWizardResume.addEventListener("click", function () {
                    if (restoreGuidedWizardState()) {
                        window.location.hash = "listings";
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    }
                });
            }

            if (guidedWizardNext) {
                guidedWizardNext.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex + 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (propertyCreateForm) {
                propertyCreateForm.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (!field.required) {
                        return;
                    }
                    field.addEventListener('input', function () {
                        applyFieldValidationState(field);
                    });
                    field.addEventListener('change', function () {
                        applyFieldValidationState(field);
                    });
                    field.addEventListener('blur', function () {
                        applyFieldValidationState(field);
                    });
                });

                propertyCreateForm.addEventListener('submit', function (event) {
                    if (!validatePropertyCreateForm(true)) {
                        event.preventDefault();
                    }
                });
            }

            if (propertyCreateSubmitButton && propertyCreateForm) {
                propertyCreateSubmitButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (!validatePropertyCreateForm(true)) {
                        return;
                    }
                    if (typeof propertyCreateForm.requestSubmit === 'function') {
                        propertyCreateForm.requestSubmit();
                    } else {
                        propertyCreateForm.submit();
                    }
                });
            }

            if (closePropertyCreateForm && propertyCreateForm) {
                closePropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    closePropertyCreateForm.hidden = true;
                });
            }

            if (backToListingsFromCreate && propertyCreateForm) {
                backToListingsFromCreate.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    if (closePropertyCreateForm) closePropertyCreateForm.hidden = true;
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(1, true);
                });
            }

            roomQuickOpenButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-property-id") || "").trim();
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(1, false);

                    const targetRow = document.querySelector('[data-inline-room-row="' + propertyId + '"]');
                    if (!targetRow) {
                        return;
                    }

                    inlineRoomRows.forEach((row) => {
                        if (row !== targetRow) {
                            row.hidden = true;
                        }
                    });

                    targetRow.hidden = false;
                    const firstInput = targetRow.querySelector('input[name="name"]');
                    if (firstInput) {
                        firstInput.focus();
                    }
                    targetRow.scrollIntoView({ behavior: "smooth", block: "nearest" });
                });
            });

            inlineRoomCloseButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-close-inline-room-row") || "").trim();
                    if (!propertyId) {
                        return;
                    }
                    const targetRow = document.querySelector('[data-inline-room-row="' + propertyId + '"]');
                    if (targetRow) {
                        targetRow.hidden = true;
                    }
                });
            });

            function applyPropertyEditScope(form, category) {
                if (!form) {
                    return;
                }
                const activeScopes = categoryScopesFor(category);
                form.querySelectorAll('[data-property-edit-scope]').forEach((field) => {
                    const scope = String(field.getAttribute('data-property-edit-scope') || '').trim().toLowerCase();
                    const shouldShow = scope !== '' && activeScopes.includes(scope);
                    field.hidden = !shouldShow;
                    field.style.display = shouldShow ? 'revert' : 'none';
                    if ('disabled' in field) {
                        field.disabled = !shouldShow;
                    }
                    field.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !shouldShow;
                    });
                });
            }

            function initEditLocationSelectors(form) {
                if (!form) {
                    return;
                }

                const countrySelect = form.querySelector('[data-edit-country]');
                const stateSelect = form.querySelector('[data-edit-state]');
                const citySelect = form.querySelector('[data-edit-city]');
                if (!countrySelect || !stateSelect || !citySelect) {
                    return;
                }

                const refreshStatesAndCities = function () {
                    const locationTree = getCurrentLocationTree();
                    const selectedCountry = countrySelect.dataset.selectedValue || countrySelect.value || 'Maldives';

                    ensureSelectHasOption(countrySelect, selectedCountry);
                    countrySelect.value = selectedCountry;

                    const states = Object.keys(locationTree[selectedCountry] || {});
                    rebuildSelect(stateSelect, states, 'Select atoll');

                    const selectedState = stateSelect.dataset.selectedValue || stateSelect.value || '';
                    ensureSelectHasOption(stateSelect, selectedState);
                    if (selectedState && Array.from(stateSelect.options).some((option) => option.value === selectedState)) {
                        stateSelect.value = selectedState;
                    } else {
                        stateSelect.value = states[0] || '';
                    }

                    const cities = (locationTree[selectedCountry] || {})[stateSelect.value] || [];
                    rebuildSelect(citySelect, cities, 'Select island');

                    const selectedCity = citySelect.dataset.selectedValue || citySelect.value || '';
                    ensureSelectHasOption(citySelect, selectedCity);
                    if (selectedCity && Array.from(citySelect.options).some((option) => option.value === selectedCity)) {
                        citySelect.value = selectedCity;
                    } else if (cities.length > 0) {
                        citySelect.value = cities[0];
                    }

                    countrySelect.dataset.selectedValue = '';
                    stateSelect.dataset.selectedValue = '';
                    citySelect.dataset.selectedValue = '';
                };

                const refreshCities = function () {
                    const locationTree = getCurrentLocationTree();
                    const country = countrySelect.value || 'Maldives';
                    const cities = (locationTree[country] || {})[stateSelect.value] || [];
                    const selectedCity = citySelect.dataset.selectedValue || citySelect.value || '';

                    rebuildSelect(citySelect, cities, 'Select island');
                    ensureSelectHasOption(citySelect, selectedCity);
                    if (selectedCity && Array.from(citySelect.options).some((option) => option.value === selectedCity)) {
                        citySelect.value = selectedCity;
                    } else if (cities.length > 0) {
                        citySelect.value = cities[0];
                    }
                    citySelect.dataset.selectedValue = '';
                };

                if (countrySelect.dataset.locationBound !== '1') {
                    countrySelect.addEventListener('change', function () {
                        refreshStatesAndCities();
                    });
                    stateSelect.addEventListener('change', function () {
                        refreshCities();
                    });
                    countrySelect.dataset.locationBound = '1';
                }

                refreshStatesAndCities();
                getLocationTree().then(function () {
                    refreshStatesAndCities();
                });
            }

            function initEditLocationMap(form) {
                if (!window.L || !form) {
                    return;
                }
                const propertyId = String(form.getAttribute('data-property-edit-form') || '').trim();
                if (!propertyId) {
                    return;
                }
                const mapEl = document.getElementById('editPropertyMap_' + propertyId);
                if (!mapEl) {
                    return;
                }

                if (mapEl.dataset.mapReady === '1') {
                    const existingMap = mapEl._workationLeafletMap;
                    if (existingMap && typeof existingMap.invalidateSize === 'function') {
                        setTimeout(function () {
                            existingMap.invalidateSize();
                        }, 180);
                    }
                    return;
                }

                const latInput = form.querySelector('input[name="map_latitude"]');
                const lngInput = form.querySelector('input[name="map_longitude"]');
                const placeInput = form.querySelector('input[name="map_place_id"]');
                const coordsLabel = document.getElementById('editMapCoords_' + propertyId);

                const latValue = Number(latInput && latInput.value ? latInput.value : 4.1755);
                const lngValue = Number(lngInput && lngInput.value ? lngInput.value : 73.5093);
                const defaultLat = Number.isFinite(latValue) ? latValue : 4.1755;
                const defaultLng = Number.isFinite(lngValue) ? lngValue : 73.5093;

                const map = window.L.map(mapEl, {
                    preferCanvas: true,
                    zoomControl: true,
                    worldCopyJump: true,
                    inertia: true,
                    fadeAnimation: false,
                    markerZoomAnimation: false,
                }).setView([defaultLat, defaultLng], 12);

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const marker = window.L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

                const updatePin = function (latlng) {
                    const lat = Number(latlng.lat.toFixed(6));
                    const lng = Number(latlng.lng.toFixed(6));
                    if (latInput) latInput.value = String(lat);
                    if (lngInput) lngInput.value = String(lng);
                    if (placeInput && placeInput.value.trim() === '') {
                        placeInput.value = 'PIN-' + lat + ',' + lng;
                    }
                    marker.setLatLng([lat, lng]);
                    if (coordsLabel) {
                        coordsLabel.textContent = 'Pinned: ' + lat + ', ' + lng;
                    }
                };

                map.on('click', function (event) {
                    updatePin(event.latlng);
                });

                marker.on('dragend', function () {
                    updatePin(marker.getLatLng());
                });

                mapEl._workationLeafletMap = map;
                mapEl.dataset.mapReady = '1';
                setTimeout(function () {
                    map.invalidateSize();
                }, 180);
            }

            function openEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                const category = String(form.getAttribute('data-property-edit-category') || '').trim();
                if (category !== '') {
                    applyPropertyEditScope(form, category);
                }
                form.hidden = false;
                const row = form.closest('tr');
                if (row) {
                    row.classList.add('is-editing');
                    row.hidden = false;
                }
                initEditLocationSelectors(form);
                initEditLocationMap(form);
                const firstInput = form.querySelector('input, select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            }

            function closeEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                form.hidden = true;
                const row = form.closest('tr');
                if (row) {
                    row.classList.remove('is-editing');
                    row.hidden = true;
                }
            }

            propertyEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    const category = String(button.getAttribute('data-property-edit-category') || '').trim();
                    const selector = '[data-property-edit-form="' + editId + '"]';
                    const form = document.querySelector(selector);
                    if (form && category !== '') {
                        applyPropertyEditScope(form, category);
                    }
                    openEditForm(selector);
                });
            });

            propertyEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-property-edit-form="' + editId + '"]');
                });
            });

            roomEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    openEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            roomEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            propertyMediaToggleButtons.forEach((button) => {
                if (button.dataset.mediaToggleBound === '1') {
                    return;
                }
                button.dataset.mediaToggleBound = '1';
                button.addEventListener('click', function () {
                    const propertyId = String(button.getAttribute('data-toggle-property-media') || '').trim();
                    if (!propertyId) {
                        return;
                    }
                    const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                    if (!panel) {
                        return;
                    }
                    panel.hidden = !panel.hidden;
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.toggle('is-media-open', !panel.hidden);
                    }
                    if (!panel.hidden) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });

            propertyMediaCloseButtons.forEach((button) => {
                if (button.dataset.mediaCloseBound === '1') {
                    return;
                }
                button.dataset.mediaCloseBound = '1';
                button.addEventListener('click', function () {
                    const propertyId = String(button.getAttribute('data-close-property-media') || '').trim();
                    if (!propertyId) {
                        return;
                    }
                    const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                    if (panel) {
                        panel.hidden = true;
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.remove('is-media-open');
                        }
                    }
                });
            });

            roomMediaToggleButtons.forEach((button) => {
                if (button.dataset.mediaToggleBound === '1') {
                    return;
                }
                button.dataset.mediaToggleBound = '1';
                button.addEventListener('click', function () {
                    const roomId = String(button.getAttribute('data-toggle-room-media') || '').trim();
                    if (!roomId) {
                        return;
                    }
                    const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                    if (!panel) {
                        return;
                    }
                    panel.hidden = !panel.hidden;
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.toggle('is-media-open', !panel.hidden);
                    }
                    if (!panel.hidden) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });

            roomMediaCloseButtons.forEach((button) => {
                if (button.dataset.mediaCloseBound === '1') {
                    return;
                }
                button.dataset.mediaCloseBound = '1';
                button.addEventListener('click', function () {
                    const roomId = String(button.getAttribute('data-close-room-media') || '').trim();
                    if (!roomId) {
                        return;
                    }
                    const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                    if (panel) {
                        panel.hidden = true;
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.remove('is-media-open');
                        }
                    }
                });
            });

            function initMediaUploadForms() {
                document.querySelectorAll('[data-media-upload-form]').forEach((form, formIndex) => {
                    if (form.dataset.mediaUploaderBound === '1') {
                        return;
                    }
                    form.dataset.mediaUploaderBound = '1';

                    const dropzone = form.querySelector('[data-media-dropzone]');
                    const fileInput = form.querySelector('[data-media-input]');
                    const preview = form.querySelector('[data-media-preview]');
                    const primaryIndexInput = form.querySelector('[data-media-primary-index]');
                    if (!dropzone || !fileInput || !preview || !primaryIndexInput) {
                        return;
                    }

                    const radioName = 'media_primary_picker_' + formIndex;

                    function syncFilesFromList(fileList) {
                        if (typeof DataTransfer === 'undefined') {
                            return;
                        }
                        const transfer = new DataTransfer();
                        fileList.forEach((file) => transfer.items.add(file));
                        fileInput.files = transfer.files;
                    }

                    function removeFileAt(indexToRemove) {
                        const files = Array.from(fileInput.files || []);
                        if (indexToRemove < 0 || indexToRemove >= files.length) {
                            return;
                        }

                        const nextFiles = files.filter((_, index) => index !== indexToRemove);
                        let currentPrimary = parseInt(primaryIndexInput.value || '0', 10) || 0;
                        if (indexToRemove < currentPrimary) {
                            currentPrimary -= 1;
                        } else if (indexToRemove === currentPrimary) {
                            currentPrimary = Math.max(0, currentPrimary - 1);
                        }
                        if (nextFiles.length === 0) {
                            currentPrimary = 0;
                        } else {
                            currentPrimary = Math.min(currentPrimary, nextFiles.length - 1);
                        }

                        primaryIndexInput.value = String(currentPrimary);
                        syncFilesFromList(nextFiles);
                        renderPreview();
                    }

                    function renderPreview() {
                        preview.innerHTML = '';
                        const files = Array.from(fileInput.files || []);
                        if (files.length === 0) {
                            primaryIndexInput.value = '0';
                            return;
                        }

                        const currentPrimary = Math.max(0, Math.min(files.length - 1, parseInt(primaryIndexInput.value || '0', 10) || 0));
                        primaryIndexInput.value = String(currentPrimary);

                        files.forEach((file, index) => {
                            const item = document.createElement('article');
                            item.className = 'media-upload-item';

                            const img = document.createElement('img');
                            img.alt = file.name;
                            img.src = URL.createObjectURL(file);
                            img.onload = function () {
                                URL.revokeObjectURL(img.src);
                            };

                            const meta = document.createElement('div');
                            meta.className = 'media-upload-meta';

                            const name = document.createElement('p');
                            name.className = 'small';
                            name.style.margin = '0';
                            name.textContent = file.name;

                            const label = document.createElement('label');
                            label.className = 'media-primary-select';

                            const radio = document.createElement('input');
                            radio.type = 'radio';
                            radio.name = radioName;
                            radio.value = String(index);
                            radio.checked = index === currentPrimary;
                            radio.addEventListener('change', function () {
                                primaryIndexInput.value = String(index);
                            });

                            const text = document.createElement('span');
                            text.textContent = 'Primary';

                            const removeButton = document.createElement('button');
                            removeButton.type = 'button';
                            removeButton.className = 'media-remove-btn';
                            removeButton.textContent = 'Remove';
                            removeButton.addEventListener('click', function () {
                                removeFileAt(index);
                            });

                            label.appendChild(radio);
                            label.appendChild(text);
                            meta.appendChild(name);
                            meta.appendChild(label);
                            meta.appendChild(removeButton);
                            item.appendChild(img);
                            item.appendChild(meta);
                            preview.appendChild(item);
                        });
                    }

                    fileInput.addEventListener('change', function () {
                        renderPreview();
                    });

                    dropzone.addEventListener('click', function () {
                        fileInput.click();
                    });

                    dropzone.addEventListener('dragover', function (event) {
                        event.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });

                    dropzone.addEventListener('dragleave', function () {
                        dropzone.classList.remove('is-dragover');
                    });

                    dropzone.addEventListener('drop', function (event) {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragover');
                        const droppedFiles = Array.from((event.dataTransfer && event.dataTransfer.files) ? event.dataTransfer.files : []);
                        if (droppedFiles.length === 0) {
                            return;
                        }
                        syncFilesFromList(droppedFiles);
                        renderPreview();
                    });

                    renderPreview();
                });
            }

            initMediaUploadForms();

            listingCategoryShortcutButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const categoryKey = String(button.getAttribute('data-listing-category-shortcut') || '');
                    openPropertyFlowWithCategory(categoryKey);
                });
            });

            document.querySelectorAll('.js-row-update').forEach((button) => {
                button.addEventListener('click', function (event) {
                    const form = button.closest('form');
                    if (!form) {
                        return;
                    }
                    event.preventDefault();
                    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                        return;
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });

            if (locationCountry && locationState && locationCity) {
                locationCountry.dataset.selectedValue = "{{ old('location_country', 'Maldives') }}";
                locationState.dataset.selectedValue = "{{ old('location_state', '') }}";
                locationCity.dataset.selectedValue = "{{ old('location_city', '') }}";
                refreshLocationSelectors();
                getLocationTree().then(function () {
                    refreshLocationSelectors();
                    centerMapForLocationSelection(true);
                });
                locationCountry.addEventListener("change", function () {
                    refreshLocationSelectors();
                    centerMapForLocationSelection(true);
                });
                locationState.addEventListener("change", function () {
                    refreshCitySelector();
                    centerMapForLocationSelection(true);
                });
                locationCity.addEventListener("change", function () {
                    centerMapForLocationSelection(true);
                });
            }

            if (propertyCategorySelect) {
                refreshPropertyCategoryFields();
                propertyCategorySelect.addEventListener("change", refreshPropertyCategoryFields);
            }

            if (transportModeInput) {
                transportModeInput.addEventListener("input", refreshTransportFieldLabels);
                transportModeInput.addEventListener("change", refreshTransportFieldLabels);
            }

            if (transportPricingModelSelect) {
                transportPricingModelSelect.addEventListener("change", refreshTransportFieldLabels);
            }

            if (billingCountry && billingState && billingCity) {
                billingState.dataset.selectedValue = "{{ old('billing_state', optional($vendorBilling)->billing_state ?? '') }}";
                billingCity.dataset.selectedValue = "{{ old('billing_city', optional($vendorBilling)->billing_city ?? '') }}";
                refreshBillingLocationSelectors();
                getLocationTree().then(function () {
                    refreshBillingLocationSelectors();
                });
                billingCountry.addEventListener("change", function () {
                    refreshBillingLocationSelectors();
                });
                billingState.addEventListener("change", function () {
                    refreshBillingCitySelector();
                });
            }
            initLocationMap();

            setInterval(function () {
                const token = getToken();
                if (token) {
                    applyTokenFeedback(token);
                }
            }, 60000);

            if (getToken()) {
                applyTokenFeedback(getToken());
                refreshSummary();
            } else {
                setMeta("Token is stored only in this browser tab session.");
                setSummaryDefaults();
            }

            initializePanelStateSafe();
            restoreGuidedWizardState();
            renderGuidedWizard();
            applyPropertyCategoryFilter('all');
            applyCategorySectionFilter('all');

            if (serverPanelKey === 'listings') {
                if (forcedListingMode === 'create') {
                    openPropertyFlowWithCategory(forcedListingCategory || 'accommodation');
                } else if (forcedListingMode === 'manage') {
                    applyPropertyCategoryFilter(forcedListingCategory || 'all');
                    applyCategorySectionFilter(forcedListingCategory || 'all');
                } else if (forcedListingCategory !== '') {
                    applyPropertyCategoryFilter(forcedListingCategory);
                    applyCategorySectionFilter(forcedListingCategory);
                }
            }

            if (forcedMediaPanelId > 0 && (forcedMediaPanelType === 'property' || forcedMediaPanelType === 'room')) {
                const panelSelector = forcedMediaPanelType === 'property'
                    ? '[data-property-media-panel="' + String(forcedMediaPanelId) + '"]'
                    : '[data-room-media-panel="' + String(forcedMediaPanelId) + '"]';
                const panel = document.querySelector(panelSelector);
                if (panel) {
                    panel.hidden = false;
                    const row = panel.closest('tr');
                    if (row) {
                        row.classList.add('is-media-open');
                    }
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        })();
    </script>
    <script>
        (function () {
            if (window.__vendorPortalPrimaryInitDone === true) {
                return;
            }

            function normalizeCategoryKey(value) {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
            }

            function initOpsCategoryToggles() {
                const toggles = Array.from(document.querySelectorAll('[data-ops-category-toggle]'));
                if (toggles.length === 0) {
                    return;
                }

                const groups = new Map();

                function setExpanded(toggle, content, expanded) {
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    content.hidden = !expanded;
                    const card = toggle.closest('.ops-category-card');
                    if (card) {
                        card.classList.toggle('is-collapsed', !expanded);
                    }
                }

                toggles.forEach((toggle) => {
                    const targetId = String(toggle.getAttribute('data-ops-target') || '').trim();
                    if (targetId === '') {
                        return;
                    }

                    const content = document.getElementById(targetId);
                    if (!content) {
                        return;
                    }

                    const groupKey = String(toggle.getAttribute('data-ops-group') || 'ops').trim() || 'ops';
                    if (!groups.has(groupKey)) {
                        groups.set(groupKey, []);
                    }
                    groups.get(groupKey).push({ toggle, content });

                    toggle.addEventListener('click', function () {
                        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                        setExpanded(toggle, content, !isExpanded);
                    });
                });

                groups.forEach((entries) => {
                    entries.forEach((entry, index) => {
                        setExpanded(entry.toggle, entry.content, index === 0);
                    });
                });
            }

            function initFallbackPanelNavigation() {
                const navLinks = Array.from(document.querySelectorAll('.portal-nav a[data-panel-key]'));
                const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
                if (navLinks.length === 0 || panelGroups.length === 0) {
                    return;
                }

                const validKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));

                function resolvePanelKey(hashValue) {
                    const panelKey = String(hashValue || "").replace(/^#/, "").trim().toLowerCase();
                    return validKeys.has(panelKey) ? panelKey : "overview";
                }

                function showPanel(panelKey) {
                    const hasMatchingPanel = panelGroups.some((panel) => {
                        return (panel.getAttribute('data-panel-group') || '') === panelKey;
                    });
                    if (!hasMatchingPanel) {
                        return;
                    }

                    panelGroups.forEach((panel) => {
                        panel.hidden = (panel.getAttribute('data-panel-group') || '') !== panelKey;
                    });
                    // Nav link highlighting is handled exclusively by the primary script's setActiveNavLink
                }

                navLinks.forEach((link) => {
                    link.addEventListener('click', function (event) {
                        const href = String(link.getAttribute('href') || '').trim();
                        if (href !== '' && !href.startsWith('#')) {
                            return;
                        }
                        event.preventDefault();
                        const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                        if (!panelKey) {
                            return;
                        }
                        window.location.hash = panelKey;
                        showPanel(panelKey);
                    });
                });

                window.addEventListener('hashchange', function () {
                    showPanel(resolvePanelKey(window.location.hash));
                });

                const fallbackInitialKey =
                    (typeof serverPanelKey !== 'undefined' && serverPanelKey && validKeys.has(serverPanelKey))
                        ? serverPanelKey
                        : resolvePanelKey(window.location.hash);
                showPanel(fallbackInitialKey);
            }

            function initFallbackListingActions() {
                const closePropertyCreateForm = document.getElementById('closePropertyCreateForm');
                const backToListingsFromCreate = document.getElementById('backToListingsFromCreate');
                const propertyCreateForm = document.getElementById('propertyCreateForm');
                const propertyCategorySelect = document.getElementById('property_listing_category');
                const propertyCategoryScopeNote = document.getElementById('propertyCategoryScopeNote');
                const propertyCreateFormTitle = document.getElementById('propertyCreateFormTitle');
                const propertyCreateFormSubtitle = document.getElementById('propertyCreateFormSubtitle');
                const propertyCreateSubmitButton = document.getElementById('propertyCreateSubmitButton');
                const propertyTypeSelect = document.getElementById('property_type');
                const propertyBasePriceLabel = document.querySelector('label[for="property_base_price"]');
                const propertyMaxGuestsLabel = document.querySelector('label[for="property_max_guests"]');
                const propertyCapacityLabel = document.querySelector('label[for="property_capacity_value"]');
                const transportModeInput = document.getElementById('property_transport_mode');
                const transportPricingHint = document.getElementById('transportPricingHint');
                const transportPricingModelSelect = document.getElementById('property_transport_pricing_model');
                const transportLandOnlyFields = Array.from(document.querySelectorAll('[data-transport-land-only]'));
                const transportMarineOnlyFields = Array.from(document.querySelectorAll('[data-transport-marine-only]'));
                const categoryScopedFields = Array.from(document.querySelectorAll('[data-category-scope]'));
                const categoryViewPanels = Array.from(document.querySelectorAll('[data-category-view]'));
                const roomCreateForm = document.getElementById('roomCreateForm');
                const closeRoomCreateForm = document.getElementById('closeRoomCreateForm');
                const roomPropertySelect = document.getElementById('room_vendor_property_id');
                const pricingNameInput = document.getElementById('pricing_name');
                const pricingTypeInput = document.getElementById('pricing_type');
                const pricingValueInput = document.getElementById('pricing_value');
                const pricingStartsInput = document.getElementById('pricing_starts');
                const pricingEndsInput = document.getElementById('pricing_ends');
                const pricingPropertyInput = document.getElementById('pricing_property_id');
                const pricingServiceInput = document.getElementById('pricing_service_id');
                const pricingRoomInput = document.getElementById('pricing_room_id');
                const availabilityForms = Array.from(document.querySelectorAll('[data-availability-form]'));
                const transferRateForms = Array.from(document.querySelectorAll('[data-transfer-rate-form]'));
                const availabilityCalendarByForm = new Map();

                function categoryScopesFor(category) {
                    const normalized = normalizeCategoryKey(category);
                    if (normalized === 'accommodation') return ['stay', 'accommodation', 'policies', 'geo'];
                    if (normalized === 'transport' || normalized === 'marine_transport' || normalized === 'land_transport') return ['capacity', 'transport', 'policies', 'geo'];
                    if (normalized === 'excursion') return ['capacity', 'service', 'excursion', 'policies', 'geo'];
                    if (normalized === 'water_sports') return ['capacity', 'service', 'excursion', 'policies', 'geo'];
                    if (normalized === 'remote_workspace') return ['stay', 'capacity', 'workspace', 'geo'];
                    if (normalized === 'conference_room') return ['capacity', 'conference', 'policies', 'geo'];
                    if (normalized === 'resort_day_visit') return ['capacity', 'day_visit', 'policies', 'geo'];
                    if (normalized === 'restaurant') return ['capacity', 'restaurant', 'policies', 'geo'];
                    if (normalized === 'vehicle_rental') return ['vehicle', 'capacity', 'rental', 'policies', 'geo'];
                    return ['stay', 'accommodation', 'capacity', 'service', 'vehicle', 'transport', 'excursion', 'workspace', 'day_visit', 'restaurant', 'rental', 'conference', 'policies', 'geo'];
                }

                function categoryMetaFor(category) {
                    const normalized = normalizeCategoryKey(category);
                    const metaMap = {
                        accommodation: ['Accommodation Enlisting', 'Fill required fields and save.', 'Save Accommodation Listing', 'Fill required fields and save.', 'property'],
                        transport: ['Marine or Land Transport Enlisting', 'Choose the transport mode and save the listing.', 'Save Transport Listing', 'Use marine mode for boats and ferries, or land mode for cars and vans.', 'service'],
                        marine_transport: ['Marine Transport Enlisting', 'Capture water transfer details and save.', 'Save Marine Transport Listing', 'Use marine transport fields for speedboats, ferries, and vessel transfers.', 'service'],
                        land_transport: ['Land Transport Enlisting', 'Capture vehicle transfer details and save.', 'Save Land Transport Listing', 'Use land transport fields for cars, vans, and local ground transfers.', 'service'],
                        water_sports: ['Water Sports Enlisting', 'Fill required fields and save.', 'Save Water Sports Listing', 'Use excursion/service fields for diving, snorkeling, and activity packages.', 'service'],
                        excursion: ['Excursion Enlisting', 'Fill required fields and save.', 'Save Excursion Listing', 'Fill required fields and save.', 'service'],
                        remote_workspace: ['Remote Workspace Enlisting', 'Fill required fields and save.', 'Save Remote Workspace Listing', 'Fill required fields and save.', 'service'],
                        conference_room: ['Conference Room Enlisting', 'Capture venue basics, capacity, and save.', 'Save Conference Room Listing', 'Use this for meeting rooms, halls, and event spaces.', 'service'],
                        resort_day_visit: ['Resort Day Visit Enlisting', 'Fill required fields and save.', 'Save Resort Day Visit Listing', 'Fill required fields and save.', 'service'],
                        restaurant: ['Restaurant Enlisting', 'Fill required fields and save.', 'Save Restaurant Listing', 'Fill required fields and save.', 'service'],
                        vehicle_rental: ['Vehicle Rental Enlisting', 'Fill required fields and save.', 'Save Vehicle Rental Listing', 'Fill required fields and save.', 'service']
                    };
                    return metaMap[normalized] || ['Listing Enlisting', 'Fill required fields and save.', 'Save Listing', 'Fill required fields and save.', 'service'];
                }

                function ensureAutoCategorySelected(preferredCategory) {
                    if (!propertyCategorySelect) return '';
                    const preferred = normalizeCategoryKey(preferredCategory || propertyCategorySelect.getAttribute('data-default-category') || 'accommodation');
                    if (preferred !== '') {
                        let matched = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === preferred);
                        if (!matched && (preferred === 'marine_transport' || preferred === 'land_transport')) {
                            matched = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === 'transport');
                        }
                        if (matched) {
                            propertyCategorySelect.value = matched.value;
                        }
                    }
                    if ((!propertyCategorySelect.value || String(propertyCategorySelect.value).trim() === '') && propertyCategorySelect.options.length > 0) {
                        propertyCategorySelect.value = propertyCategorySelect.options[0].value;
                    }
                    return String(propertyCategorySelect.value || '');
                }

                function isMarineTransportMode(value) {
                    const mode = String(value || '').trim().toLowerCase();
                    return /(^|\s)(speed\s?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)(\s|$)/.test(mode);
                }

                function applyCategorySectionFilter(categoryKey) {
                    const normalized = normalizeCategoryKey(categoryKey || 'all');
                    categoryViewPanels.forEach((panel) => {
                        const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view') || '');
                        panel.hidden = normalized !== 'all' && panelCategory !== normalized;
                    });
                }

                function refreshTransportFieldLabels() {
                    if (!propertyCategorySelect) {
                        return;
                    }

                    const normalizedCategory = normalizeCategoryKey(propertyCategorySelect.value);
                    const isTransportCategory = normalizedCategory === 'transport' || normalizedCategory === 'marine_transport' || normalizedCategory === 'land_transport';
                    const isRemoteWorkspaceCategory = normalizedCategory === 'remote_workspace';
                    const isMarine = normalizedCategory === 'marine_transport'
                        || (normalizedCategory !== 'land_transport' && isMarineTransportMode(transportModeInput ? transportModeInput.value : ''));
                    const selectedPricingModel = transportPricingModelSelect ? String(transportPricingModelSelect.value || 'per_trip') : 'per_trip';

                    if (propertyBasePriceLabel) {
                        propertyBasePriceLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Price Per Seat (MVR)' : 'Price Per Trip (MVR)')
                            : (isRemoteWorkspaceCategory ? 'Booking Fee Per Guest (MVR)' : 'Base Price (MVR)');
                    }
                    if (propertyCapacityLabel) {
                        propertyCapacityLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Seat Capacity' : 'Max Passengers Per Trip')
                            : (isRemoteWorkspaceCategory ? 'Workspace Capacity (seats/desks)' : 'Capacity');
                    }
                    if (propertyMaxGuestsLabel) {
                        propertyMaxGuestsLabel.textContent = isTransportCategory
                            ? (isMarine ? 'Seat Capacity (Legacy)' : 'Max Passengers (Legacy)')
                            : (isRemoteWorkspaceCategory ? 'Max Bookable Guests' : 'Max Guests');
                    }
                    if (transportPricingHint) {
                        transportPricingHint.textContent = isTransportCategory
                            ? (isMarine
                                ? 'Marine transport mode detected: pricing is per seat. Define pickup and dropoff, then select one-way or round-trip.'
                                : 'Land transport mode detected: choose per-trip, hourly, or daily pricing and set max passengers per trip.')
                                : 'Transport pricing mode will auto-adjust from transport mode: speedboat/ferry/boat/safari as per-seat, land transport as per-trip.';
                    }

                    transportLandOnlyFields.forEach((field) => {
                        const shouldShow = isTransportCategory && !isMarine;
                        field.hidden = !shouldShow;
                        field.style.display = shouldShow ? '' : 'none';
                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = !shouldShow;
                        });
                    });

                    transportMarineOnlyFields.forEach((field) => {
                        const shouldShow = isTransportCategory && isMarine;
                        field.hidden = !shouldShow;
                        field.style.display = shouldShow ? '' : 'none';
                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = !shouldShow;
                        });
                    });

                    const hourlyField = document.getElementById('property_hourly_rate');
                    const dailyField = document.getElementById('property_daily_rate');
                    if (hourlyField) {
                        const showHourly = isTransportCategory && !isMarine && selectedPricingModel === 'hourly';
                        hourlyField.disabled = !showHourly;
                        if (hourlyField.parentElement) {
                            hourlyField.parentElement.hidden = !showHourly;
                            hourlyField.parentElement.style.display = showHourly ? '' : 'none';
                        }
                    }
                    if (dailyField) {
                        const showDaily = isTransportCategory && !isMarine && selectedPricingModel === 'daily';
                        dailyField.disabled = !showDaily;
                        if (dailyField.parentElement) {
                            dailyField.parentElement.hidden = !showDaily;
                            dailyField.parentElement.style.display = showDaily ? '' : 'none';
                        }
                    }
                }

                document.querySelectorAll('[data-listing-category-shortcut]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const categoryKey = normalizeCategoryKey(button.getAttribute('data-listing-category-shortcut') || '');
                        if (propertyCategorySelect && categoryKey) {
                            let option = Array.from(propertyCategorySelect.options).find((item) => normalizeCategoryKey(item.value) === categoryKey);
                            if (!option) {
                                option = document.createElement('option');
                                option.value = categoryKey;
                                option.textContent = categoryKey;
                                propertyCategorySelect.appendChild(option);
                            }
                            propertyCategorySelect.value = ensureAutoCategorySelected(option.value);
                            applyCategoryMode(propertyCategorySelect.value);
                        }

                        window.location.hash = 'listings';
                        if (propertyCreateForm) {
                            propertyCreateForm.hidden = false;
                            propertyCreateForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                        if (typeof window.__vendorPortalRefreshLocationMap === 'function') {
                            window.__vendorPortalRefreshLocationMap();
                        }
                    });
                });

                document.querySelectorAll('[data-open-room-form]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-property-id') || '').trim();
                        if (roomCreateForm) {
                            roomCreateForm.hidden = false;
                        }
                        if (closeRoomCreateForm) {
                            closeRoomCreateForm.hidden = false;
                        }
                        if (roomPropertySelect && propertyId) {
                            let option = Array.from(roomPropertySelect.options).find((item) => String(item.value) === propertyId);
                            if (!option) {
                                option = document.createElement('option');
                                option.value = propertyId;
                                option.textContent = '#' + propertyId;
                                roomPropertySelect.appendChild(option);
                            }
                            roomPropertySelect.value = propertyId;
                        }
                        window.location.hash = 'listings';
                        if (roomCreateForm) {
                            roomCreateForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                function applyAvailabilityTargetSelectionFor(form) {
                    if (!form) return;
                    const parentSelect = form.querySelector('[data-availability-parent]');
                    const targetSelect = form.querySelector('[data-availability-target]');
                    if (!targetSelect) return;

                    const selectedParentValue = parentSelect ? String(parentSelect.value || '').trim() : '';
                    Array.from(targetSelect.options).forEach((option, index) => {
                        if (index === 0) {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        const parentValue = String(option.getAttribute('data-parent-value') || '').trim();
                        const matchesParent = selectedParentValue === '' || selectedParentValue === parentValue;
                        option.hidden = !matchesParent;
                        option.disabled = !matchesParent;
                    });

                    if (targetSelect.selectedIndex > 0) {
                        const currentOption = targetSelect.options[targetSelect.selectedIndex] || null;
                        if (!currentOption || currentOption.disabled) {
                            targetSelect.value = '';
                        }
                    }

                    const selectedOption = targetSelect.options[targetSelect.selectedIndex] || null;
                    const propertyId = selectedOption ? String(selectedOption.getAttribute('data-property-id') || '').trim() : '';
                    const serviceId = selectedOption ? String(selectedOption.getAttribute('data-service-id') || '').trim() : '';
                    const roomId = selectedOption ? String(selectedOption.getAttribute('data-room-id') || '').trim() : '';
                    const routeName = selectedOption ? String(selectedOption.getAttribute('data-route-name') || '').trim() : '';
                    const optionParentValue = selectedOption ? String(selectedOption.getAttribute('data-parent-value') || '').trim() : '';

                    const propertyInput = form.querySelector('[data-availability-role="property"]');
                    const serviceInput = form.querySelector('[data-availability-role="service"]');
                    const roomInput = form.querySelector('[data-availability-role="room"]');
                    const routeInput = form.querySelector('[data-availability-role="route"]');
                    const inventoryInput = form.querySelector('[data-availability-inventory]');

                    if (parentSelect && optionParentValue !== '' && String(parentSelect.value || '').trim() !== optionParentValue) {
                        parentSelect.value = optionParentValue;
                    }

                    if (propertyInput) propertyInput.value = propertyId;
                    if (serviceInput) serviceInput.value = serviceId;
                    if (roomInput) roomInput.value = roomId;
                    if (routeInput) routeInput.value = routeName;
                    if (inventoryInput && String(inventoryInput.value || '').trim() === '') {
                        inventoryInput.value = '1';
                    }

                    renderAvailabilityCalendar(form);
                }

                function parseTransferOptions(rawValue) {
                    return String(rawValue || '')
                        .split(',')
                        .map((token) => token.trim().toLowerCase())
                        .filter((token) => token !== '');
                }

                function applyTransferRateSelectionFor(form) {
                    if (!form) {
                        return;
                    }

                    const targetSelect = form.querySelector('[data-transfer-rate-target]');
                    if (!targetSelect) {
                        return;
                    }

                    const selectedOption = targetSelect.options[targetSelect.selectedIndex] || null;
                    const configuredOptions = parseTransferOptions(selectedOption ? selectedOption.getAttribute('data-transfer-options') : '');
                    const configuredOptionSet = new Set(configuredOptions);
                    const hasListingSelected = String(targetSelect.value || '').trim() !== '';

                    const optionChecks = Array.from(form.querySelectorAll('[data-transfer-option-check]'));
                    const rateInputs = Array.from(form.querySelectorAll('[data-transfer-rate-input]'));

                    optionChecks.forEach((check) => {
                        const transferKey = String(check.value || '').trim().toLowerCase();
                        const isConfiguredForListing = hasListingSelected && configuredOptionSet.has(transferKey);
                        check.disabled = !isConfiguredForListing;
                        check.checked = isConfiguredForListing;
                    });

                    rateInputs.forEach((input) => {
                        const transferKey = String(input.getAttribute('data-transfer-rate-input') || '').trim().toLowerCase();
                        const isConfiguredForListing = hasListingSelected && configuredOptionSet.has(transferKey);
                        input.disabled = !isConfiguredForListing;
                        input.value = '';

                        if (!isConfiguredForListing || !selectedOption) {
                            return;
                        }

                        const rateAttr = selectedOption.getAttribute('data-transfer-rate-' + transferKey);
                        const rateValue = Number(rateAttr);
                        if (Number.isFinite(rateValue) && rateValue > 0) {
                            input.value = String(rateValue);
                        }
                    });
                }

                availabilityForms.forEach((form) => {
                    const parentSelect = form.querySelector('[data-availability-parent]');
                    const targetSelect = form.querySelector('[data-availability-target]');
                    const initialStates = parseAvailabilityCalendarStates(form);
                    availabilityCalendarByForm.set(form, {
                        states: initialStates,
                        monthCursor: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                        isSaving: false,
                    });

                    const calendarRoot = form.querySelector('[data-availability-calendar]');
                    if (calendarRoot) {
                        const prevButton = calendarRoot.querySelector('[data-calendar-nav="prev"]');
                        const nextButton = calendarRoot.querySelector('[data-calendar-nav="next"]');
                        const calendarGrid = calendarRoot.querySelector('[data-calendar-grid]');

                        if (prevButton) {
                            prevButton.addEventListener('click', function () {
                                const calendarMeta = availabilityCalendarByForm.get(form);
                                if (!calendarMeta) {
                                    return;
                                }
                                calendarMeta.monthCursor = new Date(calendarMeta.monthCursor.getFullYear(), calendarMeta.monthCursor.getMonth() - 1, 1);
                                renderAvailabilityCalendar(form);
                            });
                        }

                        if (nextButton) {
                            nextButton.addEventListener('click', function () {
                                const calendarMeta = availabilityCalendarByForm.get(form);
                                if (!calendarMeta) {
                                    return;
                                }
                                calendarMeta.monthCursor = new Date(calendarMeta.monthCursor.getFullYear(), calendarMeta.monthCursor.getMonth() + 1, 1);
                                renderAvailabilityCalendar(form);
                            });
                        }

                        if (calendarGrid) {
                            calendarGrid.addEventListener('click', function (event) {
                                const dayButton = event.target instanceof Element
                                    ? event.target.closest('[data-calendar-date]')
                                    : null;
                                if (!dayButton || !(dayButton instanceof HTMLButtonElement) || dayButton.disabled) {
                                    return;
                                }

                                const dateKey = String(dayButton.getAttribute('data-calendar-date') || '').trim();
                                if (dateKey === '') {
                                    return;
                                }

                                const targetKey = selectedAvailabilityTargetKey(form);
                                const dayState = dayStateForTarget(form, dateKey, targetKey);
                                if (dayState.isBooked) {
                                    return;
                                }

                                submitAvailabilityCalendarToggle(form, dateKey, !dayState.isClosed, dayState);
                            });
                        }
                    }

                    if (!targetSelect) {
                        renderAvailabilityCalendar(form);
                        return;
                    }

                    if (parentSelect) {
                        parentSelect.addEventListener('change', function () {
                            applyAvailabilityTargetSelectionFor(form);
                        });
                    }

                    targetSelect.addEventListener('change', function () {
                        applyAvailabilityTargetSelectionFor(form);
                    });

                    form.addEventListener('submit', function (event) {
                        const listingCategoryInput = form.querySelector('input[name="listing_category"]');
                        const listingCategory = String(listingCategoryInput ? listingCategoryInput.value : '').trim().toLowerCase();
                        if (listingCategory !== 'accommodation') {
                            if (targetSelect.setCustomValidity) {
                                targetSelect.setCustomValidity('');
                            }
                            return;
                        }

                        const roomInput = form.querySelector('[data-availability-role="room"]');
                        const selectedRoomId = String(roomInput ? roomInput.value : '').trim();
                        if (selectedRoomId !== '') {
                            if (targetSelect.setCustomValidity) {
                                targetSelect.setCustomValidity('');
                            }
                            return;
                        }

                        event.preventDefault();
                        if (targetSelect.setCustomValidity) {
                            targetSelect.setCustomValidity('Please select a room for accommodation availability.');
                        }
                        if (targetSelect.reportValidity) {
                            targetSelect.reportValidity();
                        }
                        targetSelect.focus();
                    });

                    const closedSelect = form.querySelector('select[name="is_closed"]');
                    if (closedSelect) {
                        closedSelect.dataset.userTouched = 'false';
                        closedSelect.addEventListener('change', function () {
                            closedSelect.dataset.userTouched = 'true';
                        });
                    }

                    applyAvailabilityTargetSelectionFor(form);
                    renderAvailabilityCalendar(form);
                });

                transferRateForms.forEach((form) => {
                    const targetSelect = form.querySelector('[data-transfer-rate-target]');
                    if (!targetSelect) {
                        return;
                    }

                    targetSelect.addEventListener('change', function () {
                        applyTransferRateSelectionFor(form);
                    });

                    applyTransferRateSelectionFor(form);
                });

                const availabilityFormByKey = new Map();
                availabilityForms.forEach((form) => {
                    const formKey = String(form.getAttribute('data-availability-form') || '').trim();
                    if (formKey !== '') {
                        availabilityFormByKey.set(formKey, form);
                    }
                });

                function expandCategoryCardFor(element) {
                    const card = element ? element.closest('.ops-category-card') : null;
                    if (!card) {
                        return;
                    }

                    const toggle = card.querySelector('[data-ops-category-toggle]');
                    if (!toggle) {
                        return;
                    }

                    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        return;
                    }

                    const targetId = String(toggle.getAttribute('data-ops-target') || '').trim();
                    if (targetId !== '') {
                        const content = document.getElementById(targetId);
                        if (content) {
                            content.hidden = false;
                        }
                    }
                    toggle.setAttribute('aria-expanded', 'true');
                    card.classList.remove('is-collapsed');
                }

                function todayIsoDate() {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }

                function isoFromDate(date) {
                    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                        return '';
                    }
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }

                function parseAvailabilityCalendarStates(form) {
                    const jsonNode = form ? form.querySelector('[data-availability-calendar-state]') : null;
                    if (!jsonNode) {
                        return {};
                    }
                    try {
                        const parsed = JSON.parse(String(jsonNode.textContent || '{}'));
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (error) {
                        console.warn('Failed to parse availability calendar payload', error);
                        return {};
                    }
                }

                function selectedAvailabilityTargetKey(form) {
                    const targetSelect = form ? form.querySelector('[data-availability-target]') : null;
                    const selectedValue = targetSelect ? String(targetSelect.value || '').trim() : '';
                    return selectedValue !== '' ? selectedValue : '__generic__';
                }

                function normalizeCalendarDayState(rawState) {
                    const inventory = Number(rawState && rawState.inventory ? rawState.inventory : 0);
                    const reserved = Number(rawState && rawState.reserved ? rawState.reserved : 0);
                    const isClosed = Boolean(rawState && rawState.is_closed);
                    const hasBooking = Boolean(rawState && rawState.has_booking) || reserved > 0;
                    const isFullyReserved = inventory > 0 && reserved >= inventory;
                    return {
                        inventory,
                        reserved,
                        isClosed,
                        hasBooking,
                        isBooked: hasBooking || isFullyReserved,
                    };
                }

                function dayStateForTarget(form, dateKey, targetKey) {
                    const calendarMeta = availabilityCalendarByForm.get(form);
                    if (!calendarMeta || !calendarMeta.states || typeof calendarMeta.states !== 'object') {
                        return normalizeCalendarDayState(null);
                    }

                    const targetStates = calendarMeta.states[targetKey] && typeof calendarMeta.states[targetKey] === 'object'
                        ? calendarMeta.states[targetKey]
                        : {};
                    const genericStates = calendarMeta.states.__generic__ && typeof calendarMeta.states.__generic__ === 'object'
                        ? calendarMeta.states.__generic__
                        : {};
                    const rawState = targetStates[dateKey] || (targetKey !== '__generic__' ? genericStates[dateKey] : null) || null;
                    return normalizeCalendarDayState(rawState);
                }

                function renderAvailabilityCalendar(form) {
                    if (!form) {
                        return;
                    }

                    const calendarMeta = availabilityCalendarByForm.get(form);
                    const calendarGrid = form.querySelector('[data-calendar-grid]');
                    const monthLabel = form.querySelector('[data-calendar-month-label]');
                    const calendarHint = form.querySelector('[data-calendar-hint]');
                    if (!calendarMeta || !calendarGrid || !monthLabel) {
                        return;
                    }

                    const targetSelect = form.querySelector('[data-availability-target]');
                    const selectedTargetKey = selectedAvailabilityTargetKey(form);
                    const isTargetSelected = targetSelect ? String(targetSelect.value || '').trim() !== '' : false;

                    const listingCategoryInput = form.querySelector('input[name="listing_category"]');
                    const listingCategory = String(listingCategoryInput ? listingCategoryInput.value : '').trim().toLowerCase();
                    const requiresSpecificTarget = listingCategory === 'accommodation';

                    if (calendarHint) {
                        calendarHint.textContent = requiresSpecificTarget && !isTargetSelected
                            ? 'Select a room first. Grey dates are already booked and locked.'
                            : 'Click open/blocked dates to toggle. Booked days are disabled.';
                    }

                    const monthCursor = calendarMeta.monthCursor instanceof Date
                        ? new Date(calendarMeta.monthCursor.getFullYear(), calendarMeta.monthCursor.getMonth(), 1)
                        : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
                    calendarMeta.monthCursor = monthCursor;

                    monthLabel.textContent = monthCursor.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

                    const firstDayOfMonth = new Date(monthCursor.getFullYear(), monthCursor.getMonth(), 1);
                    const mondayOffset = (firstDayOfMonth.getDay() + 6) % 7;
                    const firstGridDate = new Date(firstDayOfMonth);
                    firstGridDate.setDate(firstGridDate.getDate() - mondayOffset);

                    const today = todayIsoDate();
                    calendarGrid.innerHTML = '';

                    for (let idx = 0; idx < 42; idx += 1) {
                        const dayDate = new Date(firstGridDate);
                        dayDate.setDate(firstGridDate.getDate() + idx);
                        const dateKey = isoFromDate(dayDate);
                        const inCurrentMonth = dayDate.getMonth() === monthCursor.getMonth();
                        const dayState = dayStateForTarget(form, dateKey, selectedTargetKey);

                        const dayButton = document.createElement('button');
                        dayButton.type = 'button';
                        dayButton.className = 'availability-calendar-day';
                        dayButton.setAttribute('data-calendar-date', dateKey);
                        dayButton.textContent = String(dayDate.getDate());

                        if (!inCurrentMonth) {
                            dayButton.classList.add('is-outside');
                        }
                        if (dateKey === today) {
                            dayButton.classList.add('is-today');
                        }
                        if (dayState.isBooked) {
                            dayButton.classList.add('is-booked');
                            dayButton.disabled = true;
                        } else if (dayState.isClosed) {
                            dayButton.classList.add('is-blocked');
                        } else {
                            dayButton.classList.add('is-open');
                        }

                        if (requiresSpecificTarget && !isTargetSelected) {
                            dayButton.disabled = true;
                            dayButton.classList.add('is-disabled-target');
                        }

                        const titleParts = [dateKey];
                        titleParts.push(dayState.isBooked ? 'Booked' : (dayState.isClosed ? 'Blocked' : 'Open'));
                        titleParts.push('Inventory: ' + dayState.inventory);
                        titleParts.push('Reserved: ' + dayState.reserved);
                        dayButton.title = titleParts.join(' | ');

                        calendarGrid.appendChild(dayButton);
                    }
                }

                async function submitAvailabilityCalendarToggle(form, dateKey, shouldClose, currentState) {
                    if (!form || !dateKey) {
                        return;
                    }

                    const listingCategoryInput = form.querySelector('input[name="listing_category"]');
                    const listingCategory = String(listingCategoryInput ? listingCategoryInput.value : '').trim().toLowerCase();
                    const roomInput = form.querySelector('[data-availability-role="room"]');
                    if (listingCategory === 'accommodation' && String(roomInput ? roomInput.value : '').trim() === '') {
                        window.alert('Select a room first before editing accommodation dates.');
                        return;
                    }

                    const calendarMeta = availabilityCalendarByForm.get(form);
                    if (!calendarMeta || calendarMeta.isSaving) {
                        return;
                    }
                    calendarMeta.isSaving = true;

                    const inventoryInput = form.querySelector('input[name="inventory"]');
                    const parsedInventory = Number(currentState && Number.isFinite(currentState.inventory)
                        ? currentState.inventory
                        : Number(inventoryInput ? inventoryInput.value : 0));
                    const nextInventory = shouldClose
                        ? Math.max(0, Number.isFinite(parsedInventory) ? parsedInventory : 0)
                        : Math.max(1, Number.isFinite(parsedInventory) ? parsedInventory : 1);

                    const dateInput = form.querySelector('input[name="slot_date"]');
                    const rangeFromInput = form.querySelector('input[name="apply_range_from"]');
                    const rangeToInput = form.querySelector('input[name="apply_range_to"]');
                    const scheduleProfileInput = form.querySelector('select[name="schedule_profile"]');
                    const closedSelect = form.querySelector('select[name="is_closed"]');
                    if (inventoryInput) inventoryInput.value = String(nextInventory);
                    if (dateInput) dateInput.value = dateKey;
                    if (rangeFromInput) rangeFromInput.value = dateKey;
                    if (rangeToInput) rangeToInput.value = dateKey;
                    if (scheduleProfileInput) scheduleProfileInput.value = 'one_off';
                    if (closedSelect) closedSelect.value = shouldClose ? '1' : '0';

                    const formData = new FormData(form);
                    formData.set('slot_date', dateKey);
                    formData.set('apply_range_from', dateKey);
                    formData.set('apply_range_to', dateKey);
                    formData.set('schedule_profile', 'one_off');
                    formData.set('inventory', String(nextInventory));
                    formData.set('is_closed', shouldClose ? '1' : '0');

                    const csrfToken = String(formData.get('_token') || '');
                    try {
                        const response = await fetch(String(form.getAttribute('action') || '/portal/vendor/availability/save'), {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error('Update failed with status ' + response.status);
                        }

                        const targetKey = selectedAvailabilityTargetKey(form);
                        if (!calendarMeta.states[targetKey] || typeof calendarMeta.states[targetKey] !== 'object') {
                            calendarMeta.states[targetKey] = {};
                        }
                        const existingRaw = calendarMeta.states[targetKey][dateKey] || {};
                        calendarMeta.states[targetKey][dateKey] = {
                            inventory: nextInventory,
                            reserved: Number(existingRaw.reserved || 0),
                            is_closed: shouldClose,
                            has_booking: Boolean(existingRaw.has_booking),
                        };
                    } catch (error) {
                        console.error('Unable to save availability update', error);
                        window.alert('Could not save this availability change. Please try again.');
                    } finally {
                        calendarMeta.isSaving = false;
                        renderAvailabilityCalendar(form);
                    }
                }

                document.querySelectorAll('[data-availability-pick-target]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const formKey = String(button.getAttribute('data-availability-form-key') || '').trim();
                        const targetValue = String(button.getAttribute('data-target-value') || '').trim();
                        if (formKey === '' || targetValue === '') {
                            return;
                        }

                        const form = availabilityFormByKey.get(formKey);
                        if (!form) {
                            return;
                        }

                        const targetSelect = form.querySelector('[data-availability-target]');
                        if (!targetSelect) {
                            return;
                        }

                        const hasOption = Array.from(targetSelect.options).some((option) => String(option.value) === targetValue);
                        if (!hasOption) {
                            return;
                        }

                        expandCategoryCardFor(button);
                        targetSelect.value = targetValue;
                        applyAvailabilityTargetSelectionFor(form);

                        const dateInput = form.querySelector('input[name="slot_date"]');
                        if (dateInput && String(dateInput.value || '').trim() === '') {
                            dateInput.value = todayIsoDate();
                        }

                        const closedSelect = form.querySelector('select[name="is_closed"]');
                        if (closedSelect && closedSelect.dataset.userTouched !== 'true') {
                            closedSelect.value = '0';
                        }

                        const inventoryInput = form.querySelector('input[name="inventory"]');
                        if (inventoryInput) {
                            inventoryInput.focus({ preventScroll: true });
                            inventoryInput.select();
                        }

                        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    });
                });

                document.querySelectorAll('[data-price-suggestion]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const targetType = String(button.getAttribute('data-target-type') || '').trim().toLowerCase();
                        const targetId = String(button.getAttribute('data-target-id') || '').trim();
                        const ruleType = String(button.getAttribute('data-rule-type') || '').trim();
                        const ruleValue = String(button.getAttribute('data-rule-value') || '').trim();
                        const targetLabel = String(button.getAttribute('data-target-label') || '').trim();

                        if (pricingTypeInput && ruleType !== '') {
                            pricingTypeInput.value = ruleType;
                        }
                        if (pricingValueInput && ruleValue !== '') {
                            pricingValueInput.value = ruleValue;
                        }
                        if (pricingNameInput) {
                            const title = targetLabel !== '' ? targetLabel : (targetType + ' ' + targetId);
                            pricingNameInput.value = (ruleType === 'weekend_markup' ? 'Weekend uplift: ' : 'Promo: ') + title;
                        }

                        if (pricingPropertyInput) pricingPropertyInput.value = '';
                        if (pricingServiceInput) pricingServiceInput.value = '';
                        if (pricingRoomInput) pricingRoomInput.value = '';

                        if (targetType === 'property' && pricingPropertyInput) {
                            pricingPropertyInput.value = targetId;
                        } else if (targetType === 'service' && pricingServiceInput) {
                            pricingServiceInput.value = targetId;
                        } else if (targetType === 'room' && pricingRoomInput) {
                            pricingRoomInput.value = targetId;
                        }

                        const today = new Date();
                        const plusThirty = new Date();
                        plusThirty.setDate(today.getDate() + 30);
                        const nextFriday = new Date(today);
                        const dayOfWeek = nextFriday.getDay();
                        const daysUntilFriday = (5 - dayOfWeek + 7) % 7;
                        nextFriday.setDate(nextFriday.getDate() + (daysUntilFriday === 0 ? 7 : daysUntilFriday));
                        const nextSaturday = new Date(nextFriday);
                        nextSaturday.setDate(nextFriday.getDate() + 1);
                        const isoDay = (date) => {
                            const y = date.getFullYear();
                            const m = String(date.getMonth() + 1).padStart(2, '0');
                            const d = String(date.getDate()).padStart(2, '0');
                            return y + '-' + m + '-' + d;
                        };

                        if (pricingStartsInput && !pricingStartsInput.value && ruleType === 'weekend_markup') {
                            pricingStartsInput.value = isoDay(nextFriday);
                        } else if (pricingStartsInput && !pricingStartsInput.value) {
                            pricingStartsInput.value = isoDay(today);
                        }
                        if (pricingEndsInput && !pricingEndsInput.value && ruleType === 'weekend_markup') {
                            pricingEndsInput.value = isoDay(nextSaturday);
                        }
                        if (pricingEndsInput && !pricingEndsInput.value && (ruleType === 'promo_discount' || ruleType === 'demand_discount')) {
                            pricingEndsInput.value = isoDay(plusThirty);
                        }

                        const pricingSection = document.getElementById('vendorPricingSection');
                        if (pricingSection) {
                            pricingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                document.querySelectorAll('[data-toggle-property-media]').forEach((button) => {
                    if (button.dataset.mediaToggleBound === '1') {
                        return;
                    }
                    button.dataset.mediaToggleBound = '1';
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-toggle-property-media') || '').trim();
                        if (!propertyId) return;
                        const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                        if (!panel) return;
                        panel.hidden = !panel.hidden;
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.toggle('is-media-open', !panel.hidden);
                        }
                    });
                });

                document.querySelectorAll('[data-close-property-media]').forEach((button) => {
                    if (button.dataset.mediaCloseBound === '1') {
                        return;
                    }
                    button.dataset.mediaCloseBound = '1';
                    button.addEventListener('click', function () {
                        const propertyId = String(button.getAttribute('data-close-property-media') || '').trim();
                        if (!propertyId) return;
                        const panel = document.querySelector('[data-property-media-panel="' + propertyId + '"]');
                        if (panel) {
                            panel.hidden = true;
                            const row = panel.closest('tr');
                            if (row) {
                                row.classList.remove('is-media-open');
                            }
                        }
                    });
                });

                document.querySelectorAll('[data-toggle-room-media]').forEach((button) => {
                    if (button.dataset.mediaToggleBound === '1') {
                        return;
                    }
                    button.dataset.mediaToggleBound = '1';
                    button.addEventListener('click', function () {
                        const roomId = String(button.getAttribute('data-toggle-room-media') || '').trim();
                        if (!roomId) return;
                        const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                        if (!panel) return;
                        panel.hidden = !panel.hidden;
                        const row = panel.closest('tr');
                        if (row) {
                            row.classList.toggle('is-media-open', !panel.hidden);
                        }
                    });
                });

                document.querySelectorAll('[data-close-room-media]').forEach((button) => {
                    if (button.dataset.mediaCloseBound === '1') {
                        return;
                    }
                    button.dataset.mediaCloseBound = '1';
                    button.addEventListener('click', function () {
                        const roomId = String(button.getAttribute('data-close-room-media') || '').trim();
                        if (!roomId) return;
                        const panel = document.querySelector('[data-room-media-panel="' + roomId + '"]');
                        if (panel) {
                            panel.hidden = true;
                            const row = panel.closest('tr');
                            if (row) {
                                row.classList.remove('is-media-open');
                            }
                        }
                    });
                });

                document.querySelectorAll('[data-open-property-edit]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                        if (!editId) return;
                        const form = document.querySelector('[data-property-edit-form="' + editId + '"]');
                        if (form) {
                            const category = normalizeCategoryKey(form.getAttribute('data-property-edit-category') || button.getAttribute('data-property-edit-category') || '');
                            const activeScopes = categoryScopesFor(category);
                            form.querySelectorAll('[data-property-edit-scope]').forEach((field) => {
                                const scope = normalizeCategoryKey(field.getAttribute('data-property-edit-scope') || '');
                                const shouldShow = activeScopes.includes(scope);
                                field.hidden = !shouldShow;
                                field.style.display = shouldShow ? '' : 'none';
                                if ('disabled' in field) {
                                    field.disabled = !shouldShow;
                                }
                                field.querySelectorAll('input, select, textarea').forEach((input) => {
                                    input.disabled = !shouldShow;
                                });
                            });
                            form.hidden = false;
                            const row = form.closest('tr');
                            if (row) {
                                row.classList.add('is-editing');
                                row.hidden = false;
                            }
                            initEditLocationSelectors(form);
                            initEditLocationMap(form);
                        }
                    });
                });

                document.querySelectorAll('[data-close-property-edit]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                        if (!editId) return;
                        const form = document.querySelector('[data-property-edit-form="' + editId + '"]');
                        if (form) {
                            form.hidden = true;
                            const row = form.closest('tr');
                            if (row) {
                                row.classList.remove('is-editing');
                                row.hidden = true;
                            }
                        }
                    });
                });

                if (propertyCategorySelect) {
                    propertyCategorySelect.addEventListener('change', function () {
                        applyCategoryMode(propertyCategorySelect.value);
                    });
                    applyCategoryMode(ensureAutoCategorySelected(''));
                }

                if (transportModeInput) {
                    transportModeInput.addEventListener('input', refreshTransportFieldLabels);
                    transportModeInput.addEventListener('change', refreshTransportFieldLabels);
                }

                if (transportPricingModelSelect) {
                    transportPricingModelSelect.addEventListener('change', refreshTransportFieldLabels);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    initFallbackPanelNavigation();
                    initFallbackListingActions();
                    initOpsCategoryToggles();
                });
            } else {
                initFallbackPanelNavigation();
                initFallbackListingActions();
                initOpsCategoryToggles();
            }
        })();
    </script>
</body>
</html>