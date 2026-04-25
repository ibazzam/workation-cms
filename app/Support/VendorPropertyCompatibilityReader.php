<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VendorPropertyCompatibilityReader
{
    public static function categoryApprovedBaseQuery(string $categoryKey)
    {
        $query = DB::table('vendor_properties')
            ->where('status', 'active')
            ->when(Schema::hasColumn('vendor_properties', 'listing_moderation_status'), static fn ($q) => $q->where('listing_moderation_status', 'approved'));

        if (Schema::hasColumn('vendor_properties', 'listing_category')) {
            $query->whereRaw('LOWER(listing_category) = ?', [$categoryKey]);
        }

        return $query;
    }

    public static function distinctOptionValues(string $categoryKey, string $column, int $limit = 120): Collection
    {
        if (!Schema::hasColumn('vendor_properties', $column)) {
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
