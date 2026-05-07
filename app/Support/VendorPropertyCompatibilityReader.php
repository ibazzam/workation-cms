<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VendorPropertyCompatibilityReader
{
    private static array $tableExistsCache = [];
    private static array $columnExistsCache = [];
    private static array $dedicatedSelectColumnsCache = [];
    private static array $allActiveListingsCache = [];
    private static array $propertyByIdCache = [];

    private static function cachingEnabled(): bool
    {
        return !app()->runningUnitTests();
    }

    public static function categoryTableNameFor(string $categoryKey): string
    {
        return self::categoryTableMap()[$categoryKey] ?? 'vendor_accommodation_listings';
    }

    public static function categoryApprovedBaseQuery(string $categoryKey)
    {
        $tableName = self::categoryTableNameFor($categoryKey);

        if (!self::hasTable($tableName)) {
            // Return a safe no-results query builder so callers can chain without crashing.
            return DB::table($tableName)->whereRaw('1 = 0');
        }

        $query = DB::table($tableName)
            ->where('status', 'active');

        if (self::hasColumn($tableName, 'listing_moderation_status')) {
            $query->where('listing_moderation_status', 'approved');
        }

        return $query;
    }

    public static function distinctOptionValues(string $categoryKey, string $column, int $limit = 120): Collection
    {
        $tableName = self::categoryTableNameFor($categoryKey);

        if (!self::hasTable($tableName) || !self::hasColumn($tableName, $column)) {
            return collect();
        }

        return self::categoryApprovedBaseQuery($categoryKey)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column);
    }

    public static function accommodationSelectColumns(): array
    {
        $columns = ['vendor_property_id', 'name', 'status', 'location', 'description', 'max_guests'];

        if (self::hasColumn('vendor_accommodation_listings', 'details')) {
            $columns[] = 'details';
        }

        if (self::hasColumn('vendor_accommodation_listings', 'listing_details')) {
            $columns[] = 'listing_details';
        }

        if (self::hasColumn('vendor_accommodation_listings', 'currency')) {
            $columns[] = 'currency';
        }

        return $columns;
    }

    public static function loadAccommodationRows(Collection $propertyIds): Collection
    {
        if ($propertyIds->isEmpty() || !self::hasTable('vendor_accommodation_listings')) {
            return collect();
        }

        return DB::table('vendor_accommodation_listings')
            ->whereIn('vendor_property_id', $propertyIds->all())
            ->get(self::accommodationSelectColumns())
            ->keyBy(static fn ($row) => (int) ($row->vendor_property_id ?? 0));
    }

    public static function mergeAccommodationFromDedicated(Collection $legacyProperties, Collection $dedicatedRows, string $parityContext): Collection
    {
        if ($legacyProperties->isEmpty() || $dedicatedRows->isEmpty()) {
            return $legacyProperties;
        }

        return $legacyProperties->map(static function ($property) use ($dedicatedRows, $parityContext) {
            $propertyId = (int) ($property->id ?? 0);
            $dedicated = $dedicatedRows->get($propertyId);
            if (!$dedicated) {
                return $property;
            }

            self::logParityDifferences($property, $dedicated, $parityContext);

            foreach (['name', 'status', 'location', 'description'] as $field) {
                if (isset($dedicated->{$field}) && trim((string) $dedicated->{$field}) !== '') {
                    $property->{$field} = $dedicated->{$field};
                }
            }

            if (property_exists($dedicated, 'currency') && trim((string) ($dedicated->currency ?? '')) !== '') {
                $property->currency = $dedicated->currency;
            }

            if (isset($dedicated->max_guests) && is_numeric($dedicated->max_guests)) {
                $property->max_guests = (int) $dedicated->max_guests;
            }

            $dedicatedDetails = trim((string) ($dedicated->details ?? $dedicated->listing_details ?? ''));
            if ($dedicatedDetails !== '') {
                $property->listing_details = $dedicatedDetails;
            }

            return $property;
        })->values();
    }

    /**
     * Load a single listing owned by a specific vendor.
     * Returns null if not found or ownership doesn't match.
     */
    public static function loadOwnedPropertyById(int $id, int $vendorUserId, ?string $categoryHint = null): ?object
    {
        $row = self::loadPropertyById($id, $categoryHint);
        if ($row === null) {
            return null;
        }

        if ((int) ($row->vendor_user_id ?? 0) !== $vendorUserId) {
            return null;
        }

        return $row;
    }

    /**
     * Check whether a property is owned by the given vendor (existence check only).
     */
    public static function vendorOwnsProperty(int $id, int $vendorUserId): bool
    {
        // Check dedicated tables first.
        foreach (self::categoryTableMap() as $tableName) {
            if (!self::hasTable($tableName)) {
                continue;
            }

            $exists = DB::table($tableName)
                ->where('vendor_property_id', $id)
                ->where('vendor_user_id', $vendorUserId)
                ->exists();

            if ($exists) {
                return true;
            }
        }

        // Fallback to vendor_properties.
        if (self::hasTable('vendor_properties')) {
            return DB::table('vendor_properties')
                ->where('id', $id)
                ->where('vendor_user_id', $vendorUserId)
                ->exists();
        }

        return false;
    }

    /**
     * Return all active+approved listings from dedicated category tables.
     * This replaces the legacy `DB::table('vendor_properties')->where('status','active')` homepage query.
     * Each row is shaped to match the legacy vendor_properties shape expected by the homepage/catalog views.
     */
    public static function allActiveListings(int $limit = 300): Collection
    {
        $normalizedLimit = max(1, $limit);
        if (self::cachingEnabled() && array_key_exists($normalizedLimit, self::$allActiveListingsCache)) {
            return self::$allActiveListingsCache[$normalizedLimit];
        }

        if (!self::cachingEnabled()) {
            $categoryTableMap = self::categoryTableMap();
            $perTableLimit = max(12, (int) ceil($normalizedLimit / max(1, count($categoryTableMap))));
            $all = collect();

            foreach ($categoryTableMap as $categoryKey => $tableName) {
                if (!self::hasTable($tableName)) {
                    continue;
                }

                $selectCols = self::dedicatedTableSelectColumns($tableName);

                $query = DB::table($tableName)
                    ->where('status', 'active');

                if (self::hasColumn($tableName, 'listing_moderation_status')) {
                    $query->where('listing_moderation_status', 'approved');
                }

                $rows = $query->limit($perTableLimit)->get($selectCols);

                $rows = $rows->map(static function ($row) use ($categoryKey) {
                    $row->listing_category = $categoryKey;
                    $row->dedicated_row_id = isset($row->id) ? (int) $row->id : 0;
                    $row->id = (int) ($row->vendor_property_id ?? $row->id ?? 0);
                    if (isset($row->details) && !isset($row->listing_details)) {
                        $row->listing_details = $row->details;
                    }
                    self::normalizeRowBasePrice($row);

                    return $row;
                });

                $all = $all->concat($rows);
            }

            return $all
                ->sortByDesc(static fn ($row) => (string) ($row->updated_at ?? ''))
                ->take($normalizedLimit)
                ->values();
        }

        $cachedRows = Cache::remember(
            'vendor_property_compatibility_reader:all_active_listings:v3:' . $normalizedLimit,
            now()->addMinutes(5),
            static function () use ($normalizedLimit) {
        $categoryTableMap = self::categoryTableMap();
        $perTableLimit = max(12, (int) ceil($normalizedLimit / max(1, count($categoryTableMap))));
        $all = collect();

        foreach ($categoryTableMap as $categoryKey => $tableName) {
            if (!self::hasTable($tableName)) {
                continue;
            }

            $selectCols = self::dedicatedTableSelectColumns($tableName);

            $query = DB::table($tableName)
                ->where('status', 'active');

            if (self::hasColumn($tableName, 'listing_moderation_status')) {
                $query->where('listing_moderation_status', 'approved');
            }

            $rows = $query->limit($perTableLimit)->get($selectCols);

            $rows = $rows->map(static function ($row) use ($categoryKey) {
                // Shape to match the legacy vendor_properties column names
                $row->listing_category = $categoryKey;
                $row->dedicated_row_id = isset($row->id) ? (int) $row->id : 0;
                $row->id = (int) ($row->vendor_property_id ?? $row->id ?? 0);
                if (isset($row->details) && !isset($row->listing_details)) {
                    $row->listing_details = $row->details;
                }
                self::normalizeRowBasePrice($row);

                return $row;
            });

            $all = $all->concat($rows);
        }

        return $all
            ->sortByDesc(static fn ($row) => (string) ($row->updated_at ?? ''))
            ->take($normalizedLimit)
            ->values();
            }
        );

        $result = $cachedRows instanceof Collection ? $cachedRows->values() : collect($cachedRows)->values();
        self::$allActiveListingsCache[$normalizedLimit] = $result;

        return $result;
    }

    /**
     * Load a single listing by its vendor_property_id (the id from vendor_properties).
     * Falls back to vendor_properties if the dedicated table is absent.
     * Returns an object shaped like a vendor_properties row.
     */
    public static function loadPropertyById(int $id, ?string $categoryHint = null): ?object
    {
        $normalizedId = max(0, $id);
        $normalizedCategoryHint = $categoryHint !== null ? trim((string) $categoryHint) : null;
        $memoKey = ($normalizedCategoryHint ?? '*') . ':' . $normalizedId;
        if (self::cachingEnabled() && array_key_exists($memoKey, self::$propertyByIdCache)) {
            return self::$propertyByIdCache[$memoKey];
        }

        if (!self::cachingEnabled()) {
            if ($normalizedCategoryHint !== null) {
                $tableName = self::categoryTableMap()[$normalizedCategoryHint] ?? null;
                if ($tableName !== null && self::hasTable($tableName)) {
                    $row = DB::table($tableName)->where('vendor_property_id', $normalizedId)->first();
                    if ($row) {
                        return self::shapeDedicatedRow($row, $normalizedCategoryHint);
                    }

                    $row = DB::table($tableName)->where('id', $normalizedId)->first();
                    if ($row) {
                        return self::shapeDedicatedRow($row, $normalizedCategoryHint);
                    }
                }
            }

            foreach (self::categoryTableMap() as $categoryKey => $tableName) {
                if (!self::hasTable($tableName)) {
                    continue;
                }
                $row = DB::table($tableName)->where('vendor_property_id', $normalizedId)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $categoryKey);
                }

                $row = DB::table($tableName)->where('id', $normalizedId)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $categoryKey);
                }
            }

            if (!self::hasTable('vendor_properties')) {
                return null;
            }

            return DB::table('vendor_properties')->where('id', $normalizedId)->first();
        }

        $resolved = Cache::remember(
            'vendor_property_compatibility_reader:property_by_id:' . md5($memoKey),
            now()->addMinutes(3),
            static function () use ($normalizedId, $normalizedCategoryHint) {
        // Try to load from the appropriate dedicated table first.
        if ($normalizedCategoryHint !== null) {
            $tableName = self::categoryTableMap()[$normalizedCategoryHint] ?? null;
            if ($tableName !== null && self::hasTable($tableName)) {
                $row = DB::table($tableName)->where('vendor_property_id', $normalizedId)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $normalizedCategoryHint);
                }

                // Safety fallback: support links that still use dedicated-table internal id.
                $row = DB::table($tableName)->where('id', $normalizedId)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $normalizedCategoryHint);
                }
            }
        }

        // Try all dedicated tables if no category hint.
        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if (!self::hasTable($tableName)) {
                continue;
            }
            $row = DB::table($tableName)->where('vendor_property_id', $normalizedId)->first();
            if ($row) {
                return self::shapeDedicatedRow($row, $categoryKey);
            }

            // Safety fallback: support links that still use dedicated-table internal id.
            $row = DB::table($tableName)->where('id', $normalizedId)->first();
            if ($row) {
                return self::shapeDedicatedRow($row, $categoryKey);
            }
        }

        // Final fallback to vendor_properties.
        if (!self::hasTable('vendor_properties')) {
            return null;
        }

        return DB::table('vendor_properties')->where('id', $normalizedId)->first();
            }
        );

        self::$propertyByIdCache[$memoKey] = is_object($resolved) ? $resolved : null;

        return self::$propertyByIdCache[$memoKey];
    }

    /**
     * Load all listings for a given vendor user from dedicated tables.
     * Shaped to match legacy vendor_properties row format.
     */
    public static function loadVendorListings(int $vendorUserId, int $limit = 200, ?string $categoryFilter = null): Collection
    {
        $all = collect();

        $normalizedCategoryFilter = $categoryFilter !== null
            ? strtolower(trim((string) $categoryFilter))
            : null;

        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if ($normalizedCategoryFilter !== null && $normalizedCategoryFilter !== '' && $categoryKey !== $normalizedCategoryFilter) {
                continue;
            }

            if (!self::hasTable($tableName)) {
                continue;
            }

            $selectCols = self::dedicatedTableSelectColumns($tableName);

            $rows = DB::table($tableName)
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get($selectCols)
                ->map(static function ($row) use ($categoryKey) {
                    $row->listing_category = $categoryKey;
                    $row->dedicated_row_id = isset($row->id) ? (int) $row->id : 0;
                    $row->id = (int) ($row->vendor_property_id ?? $row->id ?? 0);
                    if (isset($row->details) && !isset($row->listing_details)) {
                        $row->listing_details = $row->details;
                    }

                    return $row;
                });

            $all = $all->concat($rows);
        }

        return $all->sortByDesc('updated_at')->take($limit)->values();
    }

    /**
     * Query the pending moderation listings from all dedicated tables.
     * Returns a collection shaped like the legacy vendor_properties join result.
     */
    public static function pendingModerationListings(int $limit = 100): Collection
    {
        $all = collect();

        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if (!self::hasTable($tableName)) {
                continue;
            }
            if (!self::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $rows = DB::table($tableName . ' as t')
                ->leftJoin('users as vu', 'vu.id', '=', 't.vendor_user_id')
                ->where('t.listing_moderation_status', 'pending_review')
                ->orderBy('t.listing_submitted_for_review_at')
                ->limit($limit)
                ->get([
                    't.vendor_property_id as id',
                    't.vendor_user_id',
                    't.name as listing_name',
                    't.listing_moderation_status',
                    't.listing_admin_notes',
                    't.listing_submitted_for_review_at',
                    't.created_at',
                    'vu.name as vendor_name',
                    'vu.email as vendor_email',
                    'vu.portal_vendor_id',
                ])
                ->map(static function ($row) use ($categoryKey) {
                    $row->listing_category = $categoryKey;

                    return $row;
                });

            $all = $all->concat($rows);
        }

        return $all->sortBy('listing_submitted_for_review_at')->take($limit)->values();
    }

    /**
     * Query the moderation history (approved/rejected/suspended) from all dedicated tables.
     */
    public static function listingModerationHistory(int $limit = 80): Collection
    {
        $all = collect();

        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if (!self::hasTable($tableName)) {
                continue;
            }
            if (!self::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $rows = DB::table($tableName . ' as t')
                ->leftJoin('users as vu', 'vu.id', '=', 't.vendor_user_id')
                ->leftJoin('users as approver', 'approver.id', '=', 't.listing_approved_by_user_id')
                ->whereIn('t.listing_moderation_status', ['approved', 'rejected', 'suspended'])
                ->orderByDesc('t.listing_approved_at')
                ->limit($limit)
                ->get([
                    't.vendor_property_id as id',
                    't.vendor_user_id',
                    't.name as listing_name',
                    't.listing_moderation_status',
                    't.listing_admin_notes',
                    't.listing_approved_at',
                    't.created_at',
                    'vu.name as vendor_name',
                    'vu.email as vendor_email',
                    'vu.portal_vendor_id',
                    'approver.name as approved_by_name',
                    'approver.portal_role as approved_by_role',
                ])
                ->map(static function ($row) use ($categoryKey) {
                    $row->listing_category = $categoryKey;

                    return $row;
                });

            $all = $all->concat($rows);
        }

        return $all->sortByDesc('listing_approved_at')->take($limit)->values();
    }

    /**
     * Update moderation status across dedicated table + vendor_properties (dual-write during transition).
     */
    public static function updateModerationStatus(
        int $vendorPropertyId,
        string $status,
        ?string $adminNotes,
        ?int $approvedByUserId,
        ?string $categoryHint = null
    ): void {
        $now = now();
        $dedicatedPayload = [
            'listing_moderation_status' => $status,
            'listing_admin_notes' => $adminNotes,
            'listing_approved_at' => $now,
            'listing_approved_by_user_id' => $approvedByUserId,
            'updated_at' => $now,
        ];

        // Write to dedicated table first.
        $tables = $categoryHint !== null
            ? [self::categoryTableMap()[$categoryHint] ?? null]
            : array_values(self::categoryTableMap());

        foreach ($tables as $tableName) {
            if ($tableName === null || !self::hasTable($tableName)) {
                continue;
            }
            if (!self::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $colPayload = array_filter(
                $dedicatedPayload,
                static fn ($key) => self::hasColumn($tableName, $key),
                ARRAY_FILTER_USE_KEY
            );

            $affected = DB::table($tableName)
                ->where('vendor_property_id', $vendorPropertyId)
                ->update($colPayload);

            if ($affected > 0 && $categoryHint === null) {
                break; // Found and updated the right table; stop scanning.
            }
        }

        // Dual-write to vendor_properties during transition.
        if (self::hasTable('vendor_properties') && self::hasColumn('vendor_properties', 'listing_moderation_status')) {
            $vpPayload = ['listing_moderation_status' => $status, 'updated_at' => $now];
            if (self::hasColumn('vendor_properties', 'listing_admin_notes')) {
                $vpPayload['listing_admin_notes'] = $adminNotes;
            }
            if (self::hasColumn('vendor_properties', 'listing_approved_at')) {
                $vpPayload['listing_approved_at'] = $now;
            }
            if (self::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
                $vpPayload['listing_approved_by_user_id'] = $approvedByUserId;
            }

            DB::table('vendor_properties')->where('id', $vendorPropertyId)->update($vpPayload);
        }
    }

    /**
     * Update submit-for-review status on dedicated table + vendor_properties (dual-write).
     */
    public static function submitForReview(int $vendorPropertyId, ?string $categoryHint = null): void
    {
        $now = now();
        $payload = [
            'listing_moderation_status' => 'pending_review',
            'listing_submitted_for_review_at' => $now,
            'listing_admin_notes' => null,
            'listing_approved_at' => null,
            'listing_approved_by_user_id' => null,
            'updated_at' => $now,
        ];

        $tables = $categoryHint !== null
            ? [self::categoryTableMap()[$categoryHint] ?? null]
            : array_values(self::categoryTableMap());

        foreach ($tables as $tableName) {
            if ($tableName === null || !self::hasTable($tableName)) {
                continue;
            }
            if (!self::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $colPayload = array_filter(
                $payload,
                static fn ($key) => self::hasColumn($tableName, $key),
                ARRAY_FILTER_USE_KEY
            );

            $affected = DB::table($tableName)
                ->where('vendor_property_id', $vendorPropertyId)
                ->update($colPayload);

            if ($affected > 0 && $categoryHint === null) {
                break;
            }
        }

        // Dual-write to vendor_properties during transition.
        if (self::hasTable('vendor_properties') && self::hasColumn('vendor_properties', 'listing_moderation_status')) {
            $vpPayload = ['listing_moderation_status' => 'pending_review', 'updated_at' => $now];
            if (self::hasColumn('vendor_properties', 'listing_submitted_for_review_at')) {
                $vpPayload['listing_submitted_for_review_at'] = $now;
            }
            if (self::hasColumn('vendor_properties', 'listing_admin_notes')) {
                $vpPayload['listing_admin_notes'] = null;
            }
            if (self::hasColumn('vendor_properties', 'listing_approved_at')) {
                $vpPayload['listing_approved_at'] = null;
            }
            if (self::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
                $vpPayload['listing_approved_by_user_id'] = null;
            }

            DB::table('vendor_properties')->where('id', $vendorPropertyId)->update($vpPayload);
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private static function normalizeRowBasePrice(object $row): void
    {
        $existingBasePrice = isset($row->base_price) && is_numeric($row->base_price)
            ? (float) $row->base_price
            : 0.0;
        if ($existingBasePrice > 0) {
            return;
        }

        $priceCandidates = [];
        foreach (self::commonPriceColumns() as $column) {
            $rawValue = $row->{$column} ?? null;
            if (!is_numeric($rawValue)) {
                continue;
            }

            $numericValue = (float) $rawValue;
            if ($numericValue > 0) {
                $priceCandidates[] = $numericValue;
            }
        }

        if (!empty($priceCandidates)) {
            $row->base_price = (float) min($priceCandidates);
        }
    }

    private static function commonPriceColumns(): array
    {
        return [
            'base_price',
            'starting_price',
            'from_price',
            'starting_from_price',
            'price_per_night',
            'base_price_per_night',
            'price_per_day',
            'daily_rate',
            'hourly_rate',
            'adult_price',
            'price_per_adult',
            'child_price',
            'price_per_child',
            'per_person_rate',
            'per_pax_rate',
            'per_trip_rate',
            'trip_rate',
            'trip_price',
            'hourly_price',
            'daily_price',
            'adult_rate',
            'child_rate',
            'adult_charge',
            'child_charge',
            'base_charge',
            'booking_fee',
            'service_fee',
            'platform_fee',
            'price',
            'rate',
            'cost',
        ];
    }

    private static function categoryTableMap(): array
    {
        return [
            'accommodation' => 'vendor_accommodation_listings',
            'conference_room' => 'vendor_conference_room_listings',
            'land_transport' => 'vendor_land_transport_listings',
            'excursion' => 'vendor_excursion_listings',
            'remote_workspace' => 'vendor_remote_workspace_listings',
            'resort_day_visit' => 'vendor_resort_day_visit_listings',
            'restaurant' => 'vendor_restaurant_listings',
            'vehicle_rental' => 'vendor_vehicle_rental_listings',
            'water_sports' => 'vendor_water_sports_listings',
            'sea_transport' => 'vendor_sea_transport_listings',
            'liveaboard' => 'vendor_liveaboard_listings',
        ];
    }

    private static function dedicatedTableSelectColumns(string $tableName): array
    {
        if (isset(self::$dedicatedSelectColumnsCache[$tableName])) {
            return self::$dedicatedSelectColumnsCache[$tableName];
        }

        $base = ['id', 'vendor_property_id', 'vendor_user_id', 'name', 'status', 'location', 'description', 'max_guests', 'created_at', 'updated_at'];

        if (self::hasColumn($tableName, 'details')) {
            $base[] = 'details';
        }
        if (self::hasColumn($tableName, 'listing_details')) {
            $base[] = 'listing_details';
        }

        if (self::hasColumn($tableName, 'base_price')) {
            $base[] = 'base_price';
        }
        if (self::hasColumn($tableName, 'currency')) {
            $base[] = 'currency';
        }
        if (self::hasColumn($tableName, 'listing_moderation_status')) {
            $base[] = 'listing_moderation_status';
        }
        if (self::hasColumn($tableName, 'listing_admin_notes')) {
            $base[] = 'listing_admin_notes';
        }
        if (self::hasColumn($tableName, 'listing_submitted_for_review_at')) {
            $base[] = 'listing_submitted_for_review_at';
        }
        if (self::hasColumn($tableName, 'listing_approved_at')) {
            $base[] = 'listing_approved_at';
        }
        if (self::hasColumn($tableName, 'listing_approved_by_user_id')) {
            $base[] = 'listing_approved_by_user_id';
        }

        foreach (['view_count', 'wishlist_count', 'bookings_count', 'total_bookings', 'rating', 'average_rating', 'review_score', 'reviews_count', 'review_count'] as $col) {
            if (self::hasColumn($tableName, $col)) {
                $base[] = $col;
            }
        }

        foreach (self::commonPriceColumns() as $priceColumn) {
            if (self::hasColumn($tableName, $priceColumn)) {
                $base[] = $priceColumn;
            }
        }

        // Common category-specific columns (present on some tables)
        foreach (['island', 'atoll', 'city', 'pickup_location', 'dropoff_location', 'origin_point', 'destination_point'] as $col) {
            if (self::hasColumn($tableName, $col)) {
                $base[] = $col;
            }
        }

        $base = array_values(array_unique($base));

        self::$dedicatedSelectColumnsCache[$tableName] = $base;

        return $base;
    }

    private static function hasTable(string $tableName): bool
    {
        if (app()->runningUnitTests()) {
            return Schema::hasTable($tableName);
        }

        if (!array_key_exists($tableName, self::$tableExistsCache)) {
            self::$tableExistsCache[$tableName] = Schema::hasTable($tableName);
        }

        return self::$tableExistsCache[$tableName];
    }

    private static function hasColumn(string $tableName, string $columnName): bool
    {
        if (app()->runningUnitTests()) {
            return Schema::hasColumn($tableName, $columnName);
        }

        $key = $tableName . '.' . $columnName;

        if (!array_key_exists($key, self::$columnExistsCache)) {
            self::$columnExistsCache[$key] = Schema::hasColumn($tableName, $columnName);
        }

        return self::$columnExistsCache[$key];
    }

    private static function shapeDedicatedRow(object $row, string $categoryKey): object
    {
        $row->listing_category = $categoryKey;
        $row->dedicated_row_id = isset($row->id) ? (int) $row->id : 0;
        $row->id = (int) ($row->vendor_property_id ?? $row->id ?? 0);
        if (isset($row->details) && !isset($row->listing_details)) {
            $row->listing_details = $row->details;
        }

        return $row;
    }

    private static function logParityDifferences(object $legacyProperty, object $dedicatedRow, string $context): void
    {
        if (!(bool) env('WORKATION_VENDOR_PROPERTY_PARITY_LOG', false)) {
            return;
        }

        $mismatches = [];
        foreach (['name', 'status', 'location', 'description', 'currency'] as $field) {
            $legacy = trim((string) ($legacyProperty->{$field} ?? ''));
            $dedicated = trim((string) ($dedicatedRow->{$field} ?? ''));
            if ($dedicated !== '' && $legacy !== '' && $legacy !== $dedicated) {
                $mismatches[$field] = ['legacy' => $legacy, 'dedicated' => $dedicated];
            }
        }

        $legacyGuests = isset($legacyProperty->max_guests) && is_numeric($legacyProperty->max_guests)
            ? (int) $legacyProperty->max_guests
            : null;
        $dedicatedGuests = isset($dedicatedRow->max_guests) && is_numeric($dedicatedRow->max_guests)
            ? (int) $dedicatedRow->max_guests
            : null;

        if ($legacyGuests !== null && $dedicatedGuests !== null && $legacyGuests !== $dedicatedGuests) {
            $mismatches['max_guests'] = ['legacy' => $legacyGuests, 'dedicated' => $dedicatedGuests];
        }

        if ($mismatches === []) {
            return;
        }

        Log::info('vendor_properties parity mismatch detected', [
            'context' => $context,
            'vendor_property_id' => (int) ($legacyProperty->id ?? 0),
            'mismatches' => $mismatches,
        ]);
    }
}