<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VendorPropertyCompatibilityReader
{
    public static function categoryTableNameFor(string $categoryKey): string
    {
        return self::categoryTableMap()[$categoryKey] ?? 'vendor_accommodation_listings';
    }

    public static function categoryApprovedBaseQuery(string $categoryKey)
    {
        $tableName = self::categoryTableNameFor($categoryKey);

        if (!Schema::hasTable($tableName)) {
            // Return a safe no-results query builder so callers can chain without crashing.
            return DB::table($tableName)->whereRaw('1 = 0');
        }

        $query = DB::table($tableName)
            ->where('status', 'active');

        if (Schema::hasColumn($tableName, 'listing_moderation_status')) {
            $query->where('listing_moderation_status', 'approved');
        }

        return $query;
    }

    public static function distinctOptionValues(string $categoryKey, string $column, int $limit = 120): Collection
    {
        $tableName = self::categoryTableNameFor($categoryKey);

        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column)) {
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
        $columns = ['vendor_property_id', 'name', 'status', 'location', 'description', 'max_guests', 'details'];

        if (Schema::hasColumn('vendor_accommodation_listings', 'currency')) {
            $columns[] = 'currency';
        }

        return $columns;
    }

    public static function loadAccommodationRows(Collection $propertyIds): Collection
    {
        if ($propertyIds->isEmpty() || !Schema::hasTable('vendor_accommodation_listings')) {
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

            if (isset($dedicated->details) && trim((string) $dedicated->details) !== '') {
                $property->listing_details = (string) $dedicated->details;
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
            if (!Schema::hasTable($tableName)) {
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
        if (Schema::hasTable('vendor_properties')) {
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
        $categoryTableMap = self::categoryTableMap();
        $all = collect();

        foreach ($categoryTableMap as $categoryKey => $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $selectCols = self::dedicatedTableSelectColumns($tableName);

            $query = DB::table($tableName)
                ->where('status', 'active');

            if (Schema::hasColumn($tableName, 'listing_moderation_status')) {
                $query->where('listing_moderation_status', 'approved');
            }

            $rows = $query->limit($limit)->get($selectCols);

            $rows = $rows->map(static function ($row) use ($categoryKey) {
                // Shape to match the legacy vendor_properties column names
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

        return $all->take($limit)->values();
    }

    /**
     * Load a single listing by its vendor_property_id (the id from vendor_properties).
     * Falls back to vendor_properties if the dedicated table is absent.
     * Returns an object shaped like a vendor_properties row.
     */
    public static function loadPropertyById(int $id, ?string $categoryHint = null): ?object
    {
        // Try to load from the appropriate dedicated table first.
        if ($categoryHint !== null) {
            $tableName = self::categoryTableMap()[$categoryHint] ?? null;
            if ($tableName !== null && Schema::hasTable($tableName)) {
                $row = DB::table($tableName)->where('vendor_property_id', $id)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $categoryHint);
                }

                // Safety fallback: support links that still use dedicated-table internal id.
                $row = DB::table($tableName)->where('id', $id)->first();
                if ($row) {
                    return self::shapeDedicatedRow($row, $categoryHint);
                }
            }
        }

        // Try all dedicated tables if no category hint.
        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            $row = DB::table($tableName)->where('vendor_property_id', $id)->first();
            if ($row) {
                return self::shapeDedicatedRow($row, $categoryKey);
            }

            // Safety fallback: support links that still use dedicated-table internal id.
            $row = DB::table($tableName)->where('id', $id)->first();
            if ($row) {
                return self::shapeDedicatedRow($row, $categoryKey);
            }
        }

        // Final fallback to vendor_properties.
        if (!Schema::hasTable('vendor_properties')) {
            return null;
        }

        return DB::table('vendor_properties')->where('id', $id)->first();
    }

    /**
     * Load all listings for a given vendor user from dedicated tables.
     * Shaped to match legacy vendor_properties row format.
     */
    public static function loadVendorListings(int $vendorUserId, int $limit = 200): Collection
    {
        $all = collect();

        foreach (self::categoryTableMap() as $categoryKey => $tableName) {
            if (!Schema::hasTable($tableName)) {
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
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            if (!Schema::hasColumn($tableName, 'listing_moderation_status')) {
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
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            if (!Schema::hasColumn($tableName, 'listing_moderation_status')) {
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
            if ($tableName === null || !Schema::hasTable($tableName)) {
                continue;
            }
            if (!Schema::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $colPayload = array_filter(
                $dedicatedPayload,
                static fn ($key) => Schema::hasColumn($tableName, $key),
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
        if (Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
            $vpPayload = ['listing_moderation_status' => $status, 'updated_at' => $now];
            if (Schema::hasColumn('vendor_properties', 'listing_admin_notes')) {
                $vpPayload['listing_admin_notes'] = $adminNotes;
            }
            if (Schema::hasColumn('vendor_properties', 'listing_approved_at')) {
                $vpPayload['listing_approved_at'] = $now;
            }
            if (Schema::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
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
            if ($tableName === null || !Schema::hasTable($tableName)) {
                continue;
            }
            if (!Schema::hasColumn($tableName, 'listing_moderation_status')) {
                continue;
            }

            $colPayload = array_filter(
                $payload,
                static fn ($key) => Schema::hasColumn($tableName, $key),
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
        if (Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
            $vpPayload = ['listing_moderation_status' => 'pending_review', 'updated_at' => $now];
            if (Schema::hasColumn('vendor_properties', 'listing_submitted_for_review_at')) {
                $vpPayload['listing_submitted_for_review_at'] = $now;
            }
            if (Schema::hasColumn('vendor_properties', 'listing_admin_notes')) {
                $vpPayload['listing_admin_notes'] = null;
            }
            if (Schema::hasColumn('vendor_properties', 'listing_approved_at')) {
                $vpPayload['listing_approved_at'] = null;
            }
            if (Schema::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
                $vpPayload['listing_approved_by_user_id'] = null;
            }

            DB::table('vendor_properties')->where('id', $vendorPropertyId)->update($vpPayload);
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private static function categoryTableMap(): array
    {
        return [
            'accommodation' => 'vendor_accommodation_listings',
            'conference_room' => 'vendor_conference_room_listings',
            'marine_transport' => 'vendor_marine_transport_listings',
            'land_transport' => 'vendor_land_transport_listings',
            'excursion' => 'vendor_excursion_listings',
            'remote_workspace' => 'vendor_remote_workspace_listings',
            'resort_day_visit' => 'vendor_resort_day_visit_listings',
            'restaurant' => 'vendor_restaurant_listings',
            'vehicle_rental' => 'vendor_vehicle_rental_listings',
            'water_sports' => 'vendor_water_sports_listings',
        ];
    }

    private static function dedicatedTableSelectColumns(string $tableName): array
    {
        $base = ['id', 'vendor_property_id', 'vendor_user_id', 'name', 'status', 'location', 'description', 'max_guests', 'details', 'created_at', 'updated_at'];

        if (Schema::hasColumn($tableName, 'base_price')) {
            $base[] = 'base_price';
        }
        if (Schema::hasColumn($tableName, 'currency')) {
            $base[] = 'currency';
        }
        if (Schema::hasColumn($tableName, 'listing_moderation_status')) {
            $base[] = 'listing_moderation_status';
        }
        if (Schema::hasColumn($tableName, 'listing_admin_notes')) {
            $base[] = 'listing_admin_notes';
        }
        if (Schema::hasColumn($tableName, 'listing_submitted_for_review_at')) {
            $base[] = 'listing_submitted_for_review_at';
        }
        if (Schema::hasColumn($tableName, 'listing_approved_at')) {
            $base[] = 'listing_approved_at';
        }
        if (Schema::hasColumn($tableName, 'listing_approved_by_user_id')) {
            $base[] = 'listing_approved_by_user_id';
        }

        // Common category-specific columns (present on some tables)
        foreach (['island', 'atoll', 'city', 'pickup_location', 'dropoff_location', 'origin_point', 'destination_point'] as $col) {
            if (Schema::hasColumn($tableName, $col)) {
                $base[] = $col;
            }
        }

        return $base;
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