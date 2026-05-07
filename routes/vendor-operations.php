<?php

use App\Models\User;
use App\Support\ReservationPricingPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

if (!function_exists('vendorPortalCategoryMap')) {
    function vendorPortalCategoryMap(): array
    {
        return [
            'accommodation' => 'Accommodation',
            'sea_transport' => 'Sea Transport & Ferries',
            'land_transport' => 'Land Transport',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspaces',
            'resort_day_visit' => 'Resort Day Visits',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
            'water_sports' => 'Water Sports',
            'conference_room' => 'Conference Rooms',
            'sea_transport' => 'Sea Transport & Ferries',
            'liveaboard' => 'Liveaboard / Safari',
        ];
    }
}

if (!function_exists('vendorPortalCategoryAliases')) {
    function vendorPortalCategoryAliases(): array
    {
        return [
            'accommodation' => 'accommodation',
            'accommodations' => 'accommodation',
            // Legacy transport values all map to sea_transport.
            'transport' => 'sea_transport',
            'transports' => 'sea_transport',
            'marine_transport' => 'sea_transport',
            'marine_transports' => 'sea_transport',
            'marine_transportation' => 'sea_transport',
            'marine-transport' => 'sea_transport',
            'marinetransport' => 'sea_transport',
            'marinetransports' => 'sea_transport',
            'land_transport' => 'land_transport',
            'land_transports' => 'land_transport',
            'land_transportation' => 'land_transport',
            'land-transport' => 'land_transport',
            'landtransport' => 'land_transport',
            'landtransports' => 'land_transport',
            'excursion' => 'excursion',
            'excursions' => 'excursion',
            'remote_workspace' => 'remote_workspace',
            'remote_workspaces' => 'remote_workspace',
            'remoteworkspace' => 'remote_workspace',
            'remoteworkspaces' => 'remote_workspace',
            'conference_room' => 'conference_room',
            'conference_rooms' => 'conference_room',
            'conferenceroom' => 'conference_room',
            'conferencerooms' => 'conference_room',
            'resort_day_visit' => 'resort_day_visit',
            'resort_day_visits' => 'resort_day_visit',
            'resortdayvisit' => 'resort_day_visit',
            'resortdayvisits' => 'resort_day_visit',
            'restaurant' => 'restaurant',
            'restaurants' => 'restaurant',
            'vehicle_rental' => 'vehicle_rental',
            'vehicle_rentals' => 'vehicle_rental',
            'vehiclerental' => 'vehicle_rental',
            'vehiclerentals' => 'vehicle_rental',
            'water_sport' => 'water_sports',
            'water_sports' => 'water_sports',
            'water-sports' => 'water_sports',
            'watersport' => 'water_sports',
            'watersports' => 'water_sports',
            'sea_transport' => 'sea_transport',
            'sea-transport' => 'sea_transport',
            'seatransport' => 'sea_transport',
            'ferry' => 'sea_transport',
            'liveaboard' => 'liveaboard',
            'live_aboard' => 'liveaboard',
            'live-aboard' => 'liveaboard',
            'safari' => 'liveaboard',
        ];
    }
}

if (!function_exists('vendorPortalCategoryRequiredDocumentChecklist')) {
    function vendorPortalCategoryRequiredDocumentChecklist(): array
    {
        return [
            'sea_transport' => ['Valid vessel/ferry operating license', 'Vessel registration or operator permit'],
            'land_transport' => ['Valid transport operator license', 'Vehicle registration/commercial permit'],
            'water_sports' => ['Activity safety/compliance certification', 'Operator or instructor certification'],
            'excursion' => ['Tour/excursion operator permit', 'Public liability or compliance certificate'],
            'remote_workspace' => ['Business/trade registration for workspace operations'],
            'conference_room' => ['Venue operation approval or business permit'],
            'resort_day_visit' => ['Resort partnership authorization or operating permit'],
            'restaurant' => ['Food service license', 'Health/sanitation compliance certificate'],
            'vehicle_rental' => ['Vehicle rental operator permit', 'Vehicle fleet registration evidence'],
            'accommodation' => ['Tourism or accommodation operating license'],
            'sea_transport' => ['Valid vessel/ferry operating license', 'Vessel registration or operator permit'],
            'liveaboard' => ['Liveaboard or safari vessel operating license', 'Vessel registration or operator permit'],
        ];
    }
}

if (!function_exists('vendorPortalRequiredDocumentsForCategories')) {
    function vendorPortalRequiredDocumentsForCategories(array $categories): array
    {
        $checklist = vendorPortalCategoryRequiredDocumentChecklist();
        $required = [];

        foreach ($categories as $categoryKey) {
            $canonical = vendorPortalCanonicalCategory((string) $categoryKey);
            if ($canonical === null) {
                continue;
            }

            foreach ((array) ($checklist[$canonical] ?? []) as $item) {
                $label = trim((string) $item);
                if ($label !== '') {
                    $required[] = $label;
                }
            }
        }

        return array_values(array_unique($required));
    }
}

if (!function_exists('vendorPortalNormalizeCategoryToken')) {
    function vendorPortalNormalizeCategoryToken(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $value) ?? $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;
        return trim($value, '_');
    }
}

if (!function_exists('vendorPortalCanonicalCategory')) {
    function vendorPortalCanonicalCategory(string $value): ?string
    {
        $normalized = vendorPortalNormalizeCategoryToken($value);
        if ($normalized === '') {
            return null;
        }

        $aliases = vendorPortalCategoryAliases();
        return $aliases[$normalized] ?? null;
    }
}

if (!function_exists('vendorPortalSelectedCategories')) {
    function vendorPortalSelectedCategories(?User $vendorUser): array
    {
        if (!$vendorUser instanceof User || !Schema::hasColumn('users', 'portal_service_categories')) {
            return [];
        }

        $raw = $vendorUser->portal_service_categories;
        if (is_array($raw)) {
            $candidate = $raw;
        } else {
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $candidate = is_array($decoded) ? $decoded : [];
        }

        $allowed = array_keys(vendorPortalCategoryMap());
        $normalized = [];
        foreach ($candidate as $item) {
            $canonical = vendorPortalCanonicalCategory((string) $item);
            if ($canonical !== null && in_array($canonical, $allowed, true)) {
                $normalized[] = $canonical;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('vendorPortalVerificationStatus')) {
    function vendorPortalVerificationStatus(?User $vendorUser): string
    {
        if (!$vendorUser instanceof User || !Schema::hasColumn('users', 'vendor_verification_status')) {
            return 'pending';
        }

        $status = strtolower(trim((string) ($vendorUser->vendor_verification_status ?? 'pending')));
        return in_array($status, ['pending', 'under_review', 'approved', 'rejected', 'suspended'], true)
            ? $status
            : 'pending';
    }
}

if (!function_exists('vendorPortalApprovedCategories')) {
    function vendorPortalApprovedCategories(?User $vendorUser): array
    {
        if (!$vendorUser instanceof User || !Schema::hasColumn('users', 'vendor_approved_service_categories')) {
            return [];
        }

        $raw = $vendorUser->vendor_approved_service_categories;
        if (is_array($raw)) {
            $candidate = $raw;
        } else {
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $candidate = is_array($decoded) ? $decoded : [];
        }

        $allowed = array_keys(vendorPortalCategoryMap());
        $normalized = [];
        foreach ($candidate as $item) {
            $canonical = vendorPortalCanonicalCategory((string) $item);
            if ($canonical !== null && in_array($canonical, $allowed, true)) {
                $normalized[] = $canonical;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('vendorPortalCanManageListings')) {
    function vendorPortalCanManageListings(?User $vendorUser): bool
    {
        return vendorPortalVerificationStatus($vendorUser) === 'approved';
    }
}

if (!function_exists('vendorPortalRequiresAccommodation')) {
    function vendorPortalRequiresAccommodation(array $selectedCategories): bool
    {
        return in_array('accommodation', $selectedCategories, true);
    }
}

if (!function_exists('vendorPortalPropertyTypeForCategory')) {
    function vendorPortalPropertyTypeForCategory(string $listingCategory): string
    {
        return $listingCategory === 'accommodation' ? 'property' : 'service';
    }
}

if (!function_exists('vendorPortalCategoryStorageTableMap')) {
    function vendorPortalCategoryStorageTableMap(): array
    {
        return [
            'accommodation' => 'vendor_accommodation_listings',
            'conference_room' => 'vendor_conference_room_listings',
            'sea_transport' => 'vendor_sea_transport_listings',
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
}

if (!function_exists('vendorPortalCategoryStorageTable')) {
    function vendorPortalCategoryStorageTable(string $listingCategory): ?string
    {
        $map = vendorPortalCategoryStorageTableMap();
        return $map[$listingCategory] ?? null;
    }
}

if (!function_exists('vendorPortalSyncCategoryListingRecord')) {
    function vendorPortalSyncCategoryListingRecord(
        string $listingCategory,
        int $vendorPropertyId,
        int $vendorUserId,
        string $name,
        string $status,
        string $location,
        string $description,
        float $basePrice,
        string $currency,
        int $maxGuests,
        array $details,
        ?string $moderationStatus = null
    ): void {
        $tableName = vendorPortalCategoryStorageTable($listingCategory);
        if ($tableName === null) {
            throw new \RuntimeException('No category storage table configured for: ' . $listingCategory);
        }
        if (!Schema::hasTable($tableName)) {
            throw new \RuntimeException('Category storage table is missing: ' . $tableName . '. Run migrations.');
        }

        $payload = [
            'vendor_user_id' => $vendorUserId,
            'name' => $name,
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'max_guests' => $maxGuests,
            'details' => empty($details) ? null : json_encode($details),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn($tableName, 'currency')) {
            $payload['currency'] = $currency;
        }

        if (Schema::hasColumn($tableName, 'base_price')) {
            $payload['base_price'] = $basePrice;
        }

        // Sync moderation status when provided (e.g. during create).
        if ($moderationStatus !== null && Schema::hasColumn($tableName, 'listing_moderation_status')) {
            $payload['listing_moderation_status'] = $moderationStatus;
        }

        DB::table($tableName)->updateOrInsert(
            ['vendor_property_id' => $vendorPropertyId],
            array_merge($payload, ['created_at' => now()])
        );

        if ($listingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
            vendorPortalSyncAccommodationStructuredData($vendorPropertyId, $vendorUserId, $details);
        }
    }
}

if (!function_exists('vendorPortalCreateCategoryListingRecord')) {
    /**
     * Insert a new listing into the dedicated category table and return the
     * canonical vendor_property_id (= the new row's own auto-increment id).
     *
     * The category table's vendor_property_id column is nullable (as of
     * migration 070). We insert the row first to obtain the auto-increment id,
     * then immediately set vendor_property_id = id so reads via
     * VendorPropertyCompatibilityReader::loadPropertyById() work correctly.
     */
    function vendorPortalCreateCategoryListingRecord(
        string $listingCategory,
        int $vendorUserId,
        string $name,
        string $location,
        string $description,
        int $maxGuests,
        array $details,
        string $moderationStatus = 'draft',
        float $basePrice = 0,
        string $currency = 'MVR'
    ): int {
        $tableName = vendorPortalCategoryStorageTable($listingCategory);
        if ($tableName === null) {
            throw new \RuntimeException('No category storage table configured for: ' . $listingCategory);
        }
        if (!Schema::hasTable($tableName)) {
            throw new \RuntimeException('Category storage table is missing: ' . $tableName . '. Run migrations.');
        }

        $payload = [
            'vendor_user_id' => $vendorUserId,
            'name' => $name,
            'status' => 'active',
            'location' => $location,
            'description' => $description,
            'max_guests' => $maxGuests,
            'details' => empty($details) ? null : json_encode($details),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn($tableName, 'base_price')) {
            $payload['base_price'] = max(0, (float) $basePrice);
        }

        if (Schema::hasColumn($tableName, 'currency')) {
            $normalizedCurrency = strtoupper(trim($currency));
            $payload['currency'] = $normalizedCurrency !== '' ? $normalizedCurrency : 'MVR';
        }

        if (Schema::hasColumn($tableName, 'listing_moderation_status')) {
            $payload['listing_moderation_status'] = $moderationStatus;
        }

        $newId = (int) DB::table($tableName)->insertGetId($payload);

        // Self-reference: the category table row's own id becomes the
        // canonical vendor_property_id used across all related tables.
        DB::table($tableName)->where('id', $newId)->update(['vendor_property_id' => $newId]);

        if ($listingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
            vendorPortalSyncAccommodationStructuredData($newId, $vendorUserId, $details);
        }

        return $newId;
    }
}

if (!function_exists('vendorPortalDeleteCategoryListingRecord')) {
    function vendorPortalDeleteCategoryListingRecord(string $listingCategory, int $vendorPropertyId): void
    {
        $tableName = vendorPortalCategoryStorageTable($listingCategory);
        if ($tableName === null || !Schema::hasTable($tableName)) {
            return;
        }

        DB::table($tableName)
            ->where('vendor_property_id', $vendorPropertyId)
            ->delete();

        if ($listingCategory === 'accommodation') {
            if (Schema::hasTable('vendor_accommodation_transfer_rates')) {
                DB::table('vendor_accommodation_transfer_rates')
                    ->where('vendor_property_id', $vendorPropertyId)
                    ->delete();
            }
            if (Schema::hasTable('vendor_accommodation_features')) {
                DB::table('vendor_accommodation_features')
                    ->where('vendor_property_id', $vendorPropertyId)
                    ->delete();
            }
            if (Schema::hasTable('vendor_accommodation_policies')) {
                DB::table('vendor_accommodation_policies')
                    ->where('vendor_property_id', $vendorPropertyId)
                    ->delete();
            }
        }
    }
}

if (!function_exists('vendorPortalSyncAccommodationStructuredData')) {
    function vendorPortalSyncAccommodationStructuredData(int $vendorPropertyId, int $vendorUserId, array $details): void
    {
        if ($vendorPropertyId <= 0 || $vendorUserId <= 0) {
            return;
        }

        $transferOptions = collect(is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : [])
            ->map(static fn ($item): string => strtolower(trim((string) $item)))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
        $transferRates = is_array($details['transfer_rates'] ?? null) ? $details['transfer_rates'] : [];
        $transferRateMatrix = is_array($details['transfer_rate_matrix'] ?? null) ? $details['transfer_rate_matrix'] : [];
        $baseLocal = isset($details['transfer_base_local']) && is_numeric($details['transfer_base_local'])
            ? max(0, (float) $details['transfer_base_local'])
            : 0.0;
        $baseForeign = isset($details['transfer_base_foreign']) && is_numeric($details['transfer_base_foreign'])
            ? max(0, (float) $details['transfer_base_foreign'])
            : 0.0;

        if (Schema::hasTable('vendor_accommodation_transfer_rates')) {
            DB::table('vendor_accommodation_transfer_rates')
                ->where('vendor_property_id', $vendorPropertyId)
                ->delete();

            foreach ($transferOptions as $transferMode) {
                $modeMatrix = is_array($transferRateMatrix[$transferMode] ?? null)
                    ? $transferRateMatrix[$transferMode]
                    : [];

                $localAdult = isset($modeMatrix['local_adult_charge']) && is_numeric($modeMatrix['local_adult_charge'])
                    ? (float) $modeMatrix['local_adult_charge']
                    : 0.0;
                $localChild = isset($modeMatrix['local_child_charge']) && is_numeric($modeMatrix['local_child_charge'])
                    ? (float) $modeMatrix['local_child_charge']
                    : 0.0;
                $foreignAdult = isset($modeMatrix['foreign_adult_charge']) && is_numeric($modeMatrix['foreign_adult_charge'])
                    ? (float) $modeMatrix['foreign_adult_charge']
                    : (isset($transferRates[$transferMode]) && is_numeric($transferRates[$transferMode]) ? (float) $transferRates[$transferMode] : 0.0);
                $foreignChild = isset($modeMatrix['foreign_child_charge']) && is_numeric($modeMatrix['foreign_child_charge'])
                    ? (float) $modeMatrix['foreign_child_charge']
                    : 0.0;

                $rows = [
                    ['resident_type' => 'local', 'passenger_type' => 'adult', 'rate' => $localAdult, 'base_charge' => $baseLocal],
                    ['resident_type' => 'local', 'passenger_type' => 'child', 'rate' => $localChild, 'base_charge' => $baseLocal],
                    ['resident_type' => 'foreigner', 'passenger_type' => 'adult', 'rate' => $foreignAdult, 'base_charge' => $baseForeign],
                    ['resident_type' => 'foreigner', 'passenger_type' => 'child', 'rate' => $foreignChild, 'base_charge' => $baseForeign],
                ];

                foreach ($rows as $item) {
                    DB::table('vendor_accommodation_transfer_rates')->insert([
                        'vendor_property_id' => $vendorPropertyId,
                        'transfer_mode' => $transferMode,
                        'resident_type' => (string) $item['resident_type'],
                        'passenger_type' => (string) $item['passenger_type'],
                        'rate' => round(max(0, (float) $item['rate']), 2),
                        'base_charge' => round(max(0, (float) $item['base_charge']), 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('vendor_accommodation_features')) {
            DB::table('vendor_accommodation_features')
                ->where('vendor_property_id', $vendorPropertyId)
                ->delete();

            $amenities = collect(is_array($details['property_amenities'] ?? null) ? $details['property_amenities'] : [])
                ->map(static fn ($item): string => trim((string) $item))
                ->filter(static fn (string $item): bool => $item !== '')
                ->unique()
                ->values()
                ->all();
            foreach ($amenities as $amenityKey) {
                DB::table('vendor_accommodation_features')->insert([
                    'vendor_property_id' => $vendorPropertyId,
                    'feature_type' => 'amenity',
                    'feature_key' => $amenityKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $facilities = collect(is_array($details['property_features'] ?? null) ? $details['property_features'] : [])
                ->map(static fn ($item): string => trim((string) $item))
                ->filter(static fn (string $item): bool => $item !== '')
                ->unique()
                ->values()
                ->all();
            foreach ($facilities as $facilityKey) {
                DB::table('vendor_accommodation_features')->insert([
                    'vendor_property_id' => $vendorPropertyId,
                    'feature_type' => 'facility',
                    'feature_key' => $facilityKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('vendor_accommodation_policies')) {
            DB::table('vendor_accommodation_policies')->updateOrInsert(
                ['vendor_property_id' => $vendorPropertyId],
                [
                    'check_in_time' => trim((string) ($details['check_in_time'] ?? '')) ?: null,
                    'check_out_time' => trim((string) ($details['check_out_time'] ?? '')) ?: null,
                    'check_in_grace_minutes' => isset($details['check_in_grace_minutes']) && is_numeric($details['check_in_grace_minutes']) ? (int) $details['check_in_grace_minutes'] : null,
                    'early_check_in_allowed' => trim((string) ($details['early_check_in_allowed'] ?? '')) ?: null,
                    'late_check_out_allowed' => trim((string) ($details['late_check_out_allowed'] ?? '')) ?: null,
                    'minimum_nights' => isset($details['minimum_nights']) && is_numeric($details['minimum_nights']) ? (int) $details['minimum_nights'] : null,
                    'house_rules' => trim((string) ($details['house_rules'] ?? '')) ?: null,
                    'child_policy' => trim((string) ($details['child_policy'] ?? '')) ?: null,
                    'cancellation_policy' => trim((string) ($details['cancellation_policy'] ?? '')) ?: null,
                    'early_check_in_fee' => isset($details['early_check_in_fee']) && is_numeric($details['early_check_in_fee']) ? round((float) $details['early_check_in_fee'], 2) : null,
                    'late_check_out_fee' => isset($details['late_check_out_fee']) && is_numeric($details['late_check_out_fee']) ? round((float) $details['late_check_out_fee'], 2) : null,
                    'property_type' => trim((string) ($details['property_type'] ?? '')) ?: null,
                    'star_rating' => isset($details['star_rating']) && is_numeric($details['star_rating']) ? (int) $details['star_rating'] : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

if (!function_exists('vendorPortalNormalizedNumeric')) {
    function vendorPortalNormalizedNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

if (!function_exists('vendorPortalNormalizedStringList')) {
    function vendorPortalNormalizedStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            $token = trim((string) $item);
            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('vendorPortalPreferredMediaOutputFormat')) {
    function vendorPortalPreferredMediaOutputFormat(): array
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

if (!function_exists('vendorPortalCreateImageResourceFromFile')) {
    function vendorPortalCreateImageResourceFromFile(string $filePath, string $mimeType)
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

if (!function_exists('vendorPortalResizeImageToFill')) {
    function vendorPortalResizeImageToFill($sourceImage, int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight)
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

if (!function_exists('vendorPortalMediaDiskName')) {
    function vendorPortalMediaDiskName(): string
    {
        $disk = trim((string) config('filesystems.vendor_media_disk', 'public'));
        return $disk !== '' ? $disk : 'public';
    }
}

if (!function_exists('vendorPortalWriteMediaVariant')) {
    function vendorPortalWriteMediaVariant($image, string $relativePath, string $extension): bool
    {
        if ((!is_resource($image) && !($image instanceof \GdImage)) || $relativePath === '') {
            return false;
        }

        $ext = strtolower(trim($extension));
        ob_start();
        $encoded = false;
        if ($ext === 'webp' && function_exists('imagewebp')) {
            $encoded = (bool) @imagewebp($image, null, 82);
        } else {
            $encoded = (bool) @imagejpeg($image, null, 84);
        }

        $binary = ob_get_clean();
        if (!$encoded || !is_string($binary) || $binary === '') {
            return false;
        }

        return Storage::disk(vendorPortalMediaDiskName())->put($relativePath, $binary);
    }
}

if (!function_exists('vendorPortalTransferOptionCatalog')) {
    function vendorPortalTransferOptionCatalog(): array
    {
        return array_keys(vendorPortalTransferOptionLabelMap());
    }
}

if (!function_exists('vendorPortalTransferOptionLabelMap')) {
    function vendorPortalTransferOptionLabelMap(): array
    {
        $labels = [];
        foreach (vendorPortalListingOptions('transfer_option') as $option) {
            $value = strtolower(trim((string) ($option['value'] ?? '')));
            $label = trim((string) ($option['label'] ?? $value));
            if ($value !== '' && $label !== '') {
                $labels[$value] = $label;
            }
        }

        if ($labels !== []) {
            return $labels;
        }

        return [
            'car' => 'Car',
            'van' => 'Van',
            'ferry' => 'Ferry',
            'speedboat' => 'SpeedBoat',
            'seaplane' => 'SeaPlane',
            'domestic_flight' => 'Domestic Flight',
        ];
    }
}

if (!function_exists('vendorPortalListingOptionDefaults')) {
    function vendorPortalListingOptionDefaults(): array
    {
        return [
            'transport_mode' => [
                ['value' => 'speedboat', 'label' => 'Speedboat', 'group' => 'marine', 'sort_order' => 10],
                ['value' => 'ferry', 'label' => 'Ferry', 'group' => 'marine', 'sort_order' => 20],
                ['value' => 'boat', 'label' => 'Boat', 'group' => 'marine', 'sort_order' => 30],
                ['value' => 'safari', 'label' => 'Safari', 'group' => 'marine', 'sort_order' => 40],
                ['value' => 'dhoni', 'label' => 'Dhoni', 'group' => 'marine', 'sort_order' => 50],
                ['value' => 'launch', 'label' => 'Launch', 'group' => 'marine', 'sort_order' => 60],
                ['value' => 'catamaran', 'label' => 'Catamaran', 'group' => 'marine', 'sort_order' => 70],
                ['value' => 'yacht', 'label' => 'Yacht', 'group' => 'marine', 'sort_order' => 80],
                ['value' => 'other vessel', 'label' => 'Other Vessel', 'group' => 'marine', 'sort_order' => 90],
                ['value' => 'van', 'label' => 'Van', 'group' => 'land', 'sort_order' => 110],
                ['value' => 'car', 'label' => 'Car', 'group' => 'land', 'sort_order' => 120],
                ['value' => 'pickup', 'label' => 'Pickup', 'group' => 'land', 'sort_order' => 130],
                ['value' => 'bus', 'label' => 'Bus', 'group' => 'land', 'sort_order' => 140],
                ['value' => 'suv', 'label' => 'SUV', 'group' => 'land', 'sort_order' => 150],
                ['value' => 'other land vehicle', 'label' => 'Other Land Vehicle', 'group' => 'land', 'sort_order' => 160],
            ],
            'accommodation_facility' => [
                ['value' => 'wifi', 'label' => 'Wi-Fi', 'group' => 'core', 'sort_order' => 10],
                ['value' => 'parking', 'label' => 'Parking', 'group' => 'core', 'sort_order' => 20],
                ['value' => 'pool', 'label' => 'Pool', 'group' => 'core', 'sort_order' => 30],
                ['value' => 'gym', 'label' => 'Gym', 'group' => 'core', 'sort_order' => 40],
                ['value' => 'air_conditioning', 'label' => 'Air Conditioning', 'group' => 'core', 'sort_order' => 50],
                ['value' => 'breakfast', 'label' => 'Breakfast', 'group' => 'food', 'sort_order' => 60],
                ['value' => 'kitchen', 'label' => 'Kitchen', 'group' => 'food', 'sort_order' => 70],
                ['value' => 'workspace_desk', 'label' => 'Workspace Desk', 'group' => 'workspace', 'sort_order' => 80],
            ],
            'property_amenity' => [
                ['value' => 'wifi', 'label' => 'Wi-Fi', 'group' => 'core', 'sort_order' => 10],
                ['value' => 'parking', 'label' => 'Parking', 'group' => 'core', 'sort_order' => 20],
                ['value' => 'pool', 'label' => 'Pool', 'group' => 'core', 'sort_order' => 30],
                ['value' => 'gym', 'label' => 'Gym', 'group' => 'core', 'sort_order' => 40],
                ['value' => 'air_conditioning', 'label' => 'Air Conditioning', 'group' => 'core', 'sort_order' => 50],
                ['value' => 'breakfast', 'label' => 'Breakfast', 'group' => 'food', 'sort_order' => 60],
                ['value' => 'kitchen', 'label' => 'Kitchen', 'group' => 'food', 'sort_order' => 70],
                ['value' => 'workspace_desk', 'label' => 'Workspace Desk', 'group' => 'workspace', 'sort_order' => 80],
            ],
            'property_feature' => [
                ['value' => 'wheelchair_access', 'label' => 'Wheelchair Access', 'group' => 'accessibility', 'sort_order' => 10],
                ['value' => 'elevator', 'label' => 'Elevator', 'group' => 'accessibility', 'sort_order' => 20],
                ['value' => 'family_friendly', 'label' => 'Family Friendly', 'group' => 'guest_type', 'sort_order' => 30],
                ['value' => 'pet_friendly', 'label' => 'Pet Friendly', 'group' => 'guest_type', 'sort_order' => 40],
                ['value' => 'beachfront', 'label' => 'Beachfront', 'group' => 'location', 'sort_order' => 50],
                ['value' => 'sea_view', 'label' => 'Sea View', 'group' => 'location', 'sort_order' => 60],
                ['value' => 'safety_certified', 'label' => 'Safety Certified', 'group' => 'safety', 'sort_order' => 70],
                ['value' => 'kids_play_area', 'label' => 'Kids Play Area', 'group' => 'family', 'sort_order' => 80],
            ],
            'room_amenity' => [
                ['value' => 'air_conditioning', 'label' => 'Air Conditioning', 'group' => 'room', 'sort_order' => 10],
                ['value' => 'ensuite_bathroom', 'label' => 'Ensuite Bathroom', 'group' => 'room', 'sort_order' => 20],
                ['value' => 'smart_tv', 'label' => 'Smart TV', 'group' => 'room', 'sort_order' => 30],
                ['value' => 'mini_bar', 'label' => 'Mini Bar', 'group' => 'room', 'sort_order' => 40],
                ['value' => 'balcony', 'label' => 'Balcony', 'group' => 'room', 'sort_order' => 50],
                ['value' => 'sea_view', 'label' => 'Sea View', 'group' => 'view', 'sort_order' => 60],
                ['value' => 'kettle', 'label' => 'Kettle', 'group' => 'room', 'sort_order' => 70],
                ['value' => 'safe_box', 'label' => 'Safe Box', 'group' => 'room', 'sort_order' => 80],
                ['value' => 'work_desk', 'label' => 'Work Desk', 'group' => 'workspace', 'sort_order' => 90],
                ['value' => 'wifi', 'label' => 'Wi-Fi', 'group' => 'connectivity', 'sort_order' => 100],
            ],
            'bathroom_amenity' => [
                ['value' => 'hot_water', 'label' => 'Hot Water', 'group' => 'core', 'sort_order' => 10],
                ['value' => 'walk_in_shower', 'label' => 'Walk-in Shower', 'group' => 'fixture', 'sort_order' => 20],
                ['value' => 'bathtub', 'label' => 'Bathtub', 'group' => 'fixture', 'sort_order' => 30],
                ['value' => 'bidet', 'label' => 'Bidet', 'group' => 'fixture', 'sort_order' => 40],
                ['value' => 'toiletries', 'label' => 'Toiletries', 'group' => 'service', 'sort_order' => 50],
                ['value' => 'hair_dryer', 'label' => 'Hair Dryer', 'group' => 'service', 'sort_order' => 60],
                ['value' => 'bathrobes', 'label' => 'Bathrobes', 'group' => 'service', 'sort_order' => 70],
                ['value' => 'slippers', 'label' => 'Slippers', 'group' => 'service', 'sort_order' => 80],
            ],
            'room_bed_type' => [
                ['value' => 'king', 'label' => 'King', 'group' => 'bed', 'sort_order' => 10],
                ['value' => 'queen', 'label' => 'Queen', 'group' => 'bed', 'sort_order' => 20],
                ['value' => 'double', 'label' => 'Double', 'group' => 'bed', 'sort_order' => 30],
                ['value' => 'twin', 'label' => 'Twin', 'group' => 'bed', 'sort_order' => 40],
                ['value' => 'bunk', 'label' => 'Bunk', 'group' => 'bed', 'sort_order' => 50],
                ['value' => 'sofa_bed', 'label' => 'Sofa Bed', 'group' => 'bed', 'sort_order' => 60],
                ['value' => 'dorm_bed', 'label' => 'Dorm Bed', 'group' => 'bed', 'sort_order' => 70],
            ],
            'excursion_type' => [
                ['value' => 'snorkeling', 'label' => 'Snorkeling', 'group' => 'water', 'sort_order' => 10],
                ['value' => 'island_hopping', 'label' => 'Island Hopping', 'group' => 'tour', 'sort_order' => 20],
                ['value' => 'dolphin_cruise', 'label' => 'Dolphin Cruise', 'group' => 'water', 'sort_order' => 30],
                ['value' => 'fishing', 'label' => 'Fishing', 'group' => 'water', 'sort_order' => 40],
                ['value' => 'diving', 'label' => 'Diving', 'group' => 'water', 'sort_order' => 50],
                ['value' => 'sandbank_picnic', 'label' => 'Sandbank Picnic', 'group' => 'tour', 'sort_order' => 60],
                ['value' => 'sunset_cruise', 'label' => 'Sunset Cruise', 'group' => 'water', 'sort_order' => 70],
                ['value' => 'cultural_tour', 'label' => 'Cultural Tour', 'group' => 'tour', 'sort_order' => 80],
            ],
            'restaurant_meal_service' => [
                ['value' => 'breakfast', 'label' => 'Breakfast', 'group' => 'meal', 'sort_order' => 10],
                ['value' => 'lunch', 'label' => 'Lunch', 'group' => 'meal', 'sort_order' => 20],
                ['value' => 'dinner', 'label' => 'Dinner', 'group' => 'meal', 'sort_order' => 30],
                ['value' => 'all_day', 'label' => 'All Day', 'group' => 'meal', 'sort_order' => 40],
                ['value' => 'brunch', 'label' => 'Brunch', 'group' => 'meal', 'sort_order' => 50],
                ['value' => 'high_tea', 'label' => 'High Tea', 'group' => 'meal', 'sort_order' => 60],
            ],
            'vehicle_rental_type' => [
                ['value' => 'land_car', 'label' => 'Land - Car', 'group' => 'land', 'sort_order' => 10],
                ['value' => 'land_suv', 'label' => 'Land - SUV', 'group' => 'land', 'sort_order' => 20],
                ['value' => 'land_van', 'label' => 'Land - Van', 'group' => 'land', 'sort_order' => 30],
                ['value' => 'land_bus', 'label' => 'Land - Bus', 'group' => 'land', 'sort_order' => 40],
                ['value' => 'land_motorbike', 'label' => 'Land - Motorbike', 'group' => 'land', 'sort_order' => 50],
                ['value' => 'land_bicycle', 'label' => 'Land - Bicycle', 'group' => 'land', 'sort_order' => 60],
                ['value' => 'marine_speedboat', 'label' => 'Marine - Speedboat', 'group' => 'marine', 'sort_order' => 70],
                ['value' => 'marine_dhoni', 'label' => 'Marine - Dhoni', 'group' => 'marine', 'sort_order' => 80],
                ['value' => 'marine_launch', 'label' => 'Marine - Launch', 'group' => 'marine', 'sort_order' => 90],
                ['value' => 'marine_catamaran', 'label' => 'Marine - Catamaran', 'group' => 'marine', 'sort_order' => 100],
                ['value' => 'marine_yacht', 'label' => 'Marine - Yacht', 'group' => 'marine', 'sort_order' => 110],
                ['value' => 'marine_ferry', 'label' => 'Marine - Ferry', 'group' => 'marine', 'sort_order' => 120],
                ['value' => 'other_land_vehicle', 'label' => 'Other Land Vehicle', 'group' => 'land', 'sort_order' => 130],
                ['value' => 'other_marine_vessel', 'label' => 'Other Marine Vessel', 'group' => 'marine', 'sort_order' => 140],
            ],
            'transfer_option' => [
                ['value' => 'car', 'label' => 'Car', 'group' => 'land', 'sort_order' => 10],
                ['value' => 'van', 'label' => 'Van', 'group' => 'land', 'sort_order' => 20],
                ['value' => 'ferry', 'label' => 'Ferry', 'group' => 'marine', 'sort_order' => 30],
                ['value' => 'speedboat', 'label' => 'SpeedBoat', 'group' => 'marine', 'sort_order' => 40],
                ['value' => 'seaplane', 'label' => 'SeaPlane', 'group' => 'air', 'sort_order' => 50],
                ['value' => 'domestic_flight', 'label' => 'Domestic Flight', 'group' => 'air', 'sort_order' => 60],
            ],
        ];
    }
}

if (!function_exists('vendorPortalListingOptions')) {
    function vendorPortalListingOptions(string $optionType): array
    {
        $defaults = vendorPortalListingOptionDefaults();
        $fallback = $defaults[$optionType] ?? [];

        if (!Schema::hasTable('portal_listing_option_catalog')) {
            return $fallback;
        }

        $rows = DB::table('portal_listing_option_catalog')
            ->where('option_type', $optionType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('option_label')
            ->get(['option_value', 'option_label', 'option_group', 'sort_order']);

        if ($rows->isEmpty()) {
            return $fallback;
        }

        return $rows->map(static function (object $row): array {
            return [
                'value' => trim((string) ($row->option_value ?? '')),
                'label' => trim((string) ($row->option_label ?? '')),
                'group' => trim((string) ($row->option_group ?? '')),
                'sort_order' => (int) ($row->sort_order ?? 100),
            ];
        })->filter(static fn (array $row): bool => $row['value'] !== '' && $row['label'] !== '')
            ->values()
            ->all();
    }
}

if (!function_exists('vendorPortalAllowedOptionValueSet')) {
    function vendorPortalAllowedOptionValueSet(string $optionType): array
    {
        $allowed = [];
        foreach (vendorPortalListingOptions($optionType) as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value !== '') {
                $allowed[$value] = true;
            }
        }

        return $allowed;
    }
}

if (!function_exists('vendorPortalDisallowedOptionValues')) {
    function vendorPortalDisallowedOptionValues(string $optionType, array $submittedValues): array
    {
        $allowed = vendorPortalAllowedOptionValueSet($optionType);
        if ($allowed === []) {
            return [];
        }

        $invalid = [];
        foreach ($submittedValues as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '' && !isset($allowed[$normalized])) {
                $invalid[] = $normalized;
            }
        }

        return array_values(array_unique($invalid));
    }
}

if (!function_exists('vendorPortalDisallowedOptionValuesFromTypes')) {
    function vendorPortalDisallowedOptionValuesFromTypes(array $optionTypes, array $submittedValues): array
    {
        $allowed = [];
        foreach ($optionTypes as $optionType) {
            foreach (vendorPortalAllowedOptionValueSet((string) $optionType) as $value => $isAllowed) {
                if ($isAllowed) {
                    $allowed[$value] = true;
                }
            }
        }

        if ($allowed === []) {
            return [];
        }

        $invalid = [];
        foreach ($submittedValues as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '' && !isset($allowed[$normalized])) {
                $invalid[] = $normalized;
            }
        }

        return array_values(array_unique($invalid));
    }
}

if (!function_exists('vendorPortalListingsBackResponse')) {
    function vendorPortalListingsBackResponse(string $message, int $wizardStep = 1, array $extraFlash = [])
    {
        $normalizedStep = max(1, min(4, $wizardStep));

        $response = redirect('/vendor?page=listings#listings')
            ->with('portal_notice', $message)
            ->with('portal_active_panel', 'listings')
            ->with('listing_wizard_step', $normalizedStep);

        foreach ($extraFlash as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $response->with($key, $value);
        }

        return $response;
    }
}

if (!function_exists('vendorPortalMediaPanelContextFromRequest')) {
    function vendorPortalMediaPanelContextFromRequest(Request $request, ?string $fallbackEntityType = null, ?int $fallbackEntityId = null): array
    {
        $panelEntityType = strtolower(trim((string) $request->input('panel_entity_type', $fallbackEntityType ?? '')));
        $panelEntityId = (int) $request->input('panel_entity_id', $fallbackEntityId ?? 0);

        if (!in_array($panelEntityType, ['property', 'room'], true) || $panelEntityId <= 0) {
            return [];
        }

        return [
            'portal_media_panel_type' => $panelEntityType,
            'portal_media_panel_id' => $panelEntityId,
        ];
    }
}

if (!function_exists('vendorPortalDeleteMediaRecord')) {
    function vendorPortalDeleteMediaRecord(object $mediaRecord, int $vendorUserId): void
    {
        $mediaId = (int) ($mediaRecord->id ?? 0);
        if ($mediaId <= 0) {
            return;
        }

        $entityType = (string) ($mediaRecord->entity_type ?? '');
        $entityId = isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;
        $isPrimary = (bool) ($mediaRecord->is_primary ?? false);

        $originalPath = trim((string) ($mediaRecord->file_path ?? ''));
        $bannerPath = $originalPath;
        $thumbPath = $originalPath;
        if ($originalPath !== '') {
            $bannerPath = preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $originalPath) ?? $originalPath;
            $thumbPath = preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $originalPath) ?? $originalPath;
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

        $pathsToDelete = collect([
            $bannerPath,
            $thumbPath,
            $normalizeDiskPath($bannerPath),
            $normalizeDiskPath($thumbPath),
        ])->map(static fn ($path) => trim((string) $path))
            ->filter(static fn ($path) => $path !== '')
            ->unique()
            ->values()
            ->all();

        foreach ($pathsToDelete as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable $e) {
                // Best effort deletion.
            }

            try {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
                $localPublicPath = 'public/' . ltrim($path, '/');
                if (Storage::disk('local')->exists($localPublicPath)) {
                    Storage::disk('local')->delete($localPublicPath);
                }
            } catch (\Throwable $e) {
                // Best effort deletion.
            }
        }

        DB::table('vendor_listing_media')
            ->where('id', $mediaId)
            ->where('vendor_user_id', $vendorUserId)
            ->delete();

        if ($isPrimary) {
            $replacement = DB::table('vendor_listing_media')
                ->where('vendor_user_id', $vendorUserId)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->orderByDesc('created_at')
                ->first();

            if ($replacement) {
                DB::table('vendor_listing_media')
                    ->where('id', (int) ($replacement->id ?? 0))
                    ->where('vendor_user_id', $vendorUserId)
                    ->update([
                        'is_primary' => true,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}

if (!function_exists('vendorPortalTransportModeProfile')) {
    function vendorPortalTransportModeProfile(string $transportMode): array
    {
        $normalized = strtolower(trim($transportMode));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        $isMarine = preg_match('/\b(speed ?boat|ferry|boat|safari|dhoni|launch|catamaran|yacht)\b/', $normalized) === 1;

        return [
            'is_marine' => $isMarine,
            'pricing_basis' => $isMarine ? 'per_seat' : 'per_trip',
        ];
    }
}

if (!function_exists('vendorPortalDurationMinutesFromTimes')) {
    function vendorPortalDurationMinutesFromTimes(?string $startTime, ?string $endTime): ?int
    {
        $start = trim((string) ($startTime ?? ''));
        $end = trim((string) ($endTime ?? ''));
        if ($start === '' || $end === '') {
            return null;
        }

        if (!preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $start, $startMatch)
            || !preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $end, $endMatch)) {
            return null;
        }

        $startMinutes = ((int) $startMatch[1] * 60) + (int) $startMatch[2];
        $endMinutes = ((int) $endMatch[1] * 60) + (int) $endMatch[2];
        $duration = $endMinutes - $startMinutes;

        // Support overnight activities where end time is after midnight.
        if ($duration <= 0) {
            $duration += 24 * 60;
        }

        return $duration > 0 ? $duration : null;
    }
}

if (!function_exists('vendorPortalBuildPropertyDetails')) {
    function vendorPortalBuildPropertyDetails(array $validated, string $listingCategory): array
    {
        $propertyAmenities = vendorPortalNormalizedStringList($validated['property_amenities'] ?? []);
        $propertyFeatures = vendorPortalNormalizedStringList($validated['property_features'] ?? []);
        $transferOptionCatalog = vendorPortalTransferOptionCatalog();
        $submittedTransferOptions = array_map(
            static fn ($item): string => strtolower(trim((string) $item)),
            vendorPortalNormalizedStringList($validated['transfer_options'] ?? [])
        );
        $transferOptions = array_values(array_intersect($transferOptionCatalog, $submittedTransferOptions));
        $submittedTransferRates = is_array($validated['transfer_rates'] ?? null)
            ? $validated['transfer_rates']
            : [];
        $submittedTransferRatesLocalAdult = is_array($validated['transfer_rates_local_adult'] ?? null)
            ? $validated['transfer_rates_local_adult']
            : [];
        $submittedTransferRatesLocalChild = is_array($validated['transfer_rates_local_child'] ?? null)
            ? $validated['transfer_rates_local_child']
            : [];
        $submittedTransferRatesForeignAdult = is_array($validated['transfer_rates_foreign_adult'] ?? null)
            ? $validated['transfer_rates_foreign_adult']
            : [];
        $submittedTransferRatesForeignChild = is_array($validated['transfer_rates_foreign_child'] ?? null)
            ? $validated['transfer_rates_foreign_child']
            : [];

        $transferRates = [];
        $transferRateMatrix = [];
        foreach ($transferOptionCatalog as $transferOptionKey) {
            $normalizedRate = vendorPortalNormalizedNumeric($submittedTransferRates[$transferOptionKey] ?? null);
            if ($normalizedRate !== null && $normalizedRate >= 0) {
                $transferRates[$transferOptionKey] = $normalizedRate;
            }

            $localAdultRate = vendorPortalNormalizedNumeric($submittedTransferRatesLocalAdult[$transferOptionKey] ?? null);
            $localChildRate = vendorPortalNormalizedNumeric($submittedTransferRatesLocalChild[$transferOptionKey] ?? null);
            $foreignAdultRate = vendorPortalNormalizedNumeric($submittedTransferRatesForeignAdult[$transferOptionKey] ?? null);
            $foreignChildRate = vendorPortalNormalizedNumeric($submittedTransferRatesForeignChild[$transferOptionKey] ?? null);

            if ($localAdultRate !== null || $localChildRate !== null || $foreignAdultRate !== null || $foreignChildRate !== null) {
                $transferRateMatrix[$transferOptionKey] = [
                    'local_adult_charge' => max(0, (float) ($localAdultRate ?? 0)),
                    'local_child_charge' => max(0, (float) ($localChildRate ?? 0)),
                    'foreign_adult_charge' => max(0, (float) ($foreignAdultRate ?? ($normalizedRate ?? 0))),
                    'foreign_child_charge' => max(0, (float) ($foreignChildRate ?? 0)),
                ];
            }
        }

        $vendorTaxOverrides = [];
        $submittedVendorTaxRates = is_array($validated['vendor_tax_rates'] ?? null)
            ? $validated['vendor_tax_rates']
            : [];
        foreach ($submittedVendorTaxRates as $taxCode => $taxRate) {
            $normalizedCode = strtolower(trim((string) $taxCode));
            $normalizedCode = preg_replace('/[^a-z0-9_]+/', '_', $normalizedCode) ?? $normalizedCode;
            $normalizedCode = trim((string) preg_replace('/_+/', '_', $normalizedCode), '_');
            if ($normalizedCode === '' || !is_numeric($taxRate)) {
                continue;
            }

            $vendorTaxOverrides[$normalizedCode] = round(max(0, (float) $taxRate), 4);
        }

        $details = [
            'location_country' => trim((string) ($validated['location_country'] ?? '')),
            'location_state' => trim((string) ($validated['location_state'] ?? '')),
            'location_city' => trim((string) ($validated['location_city'] ?? '')),
            'location_ward' => trim((string) ($validated['location_ward'] ?? '')),
            'address_line' => trim((string) ($validated['address_line'] ?? '')),
            'building_house_lot' => trim((string) ($validated['building_house_lot'] ?? '')),
            'street' => trim((string) ($validated['street'] ?? '')),
            'post_code' => trim((string) ($validated['post_code'] ?? '')),
            'property_contact_name' => trim((string) ($validated['property_contact_name'] ?? '')),
            'property_contact_number' => trim((string) ($validated['property_contact_number'] ?? '')),
            'property_contact_email' => trim((string) ($validated['property_contact_email'] ?? '')),
            'map_latitude' => vendorPortalNormalizedNumeric($validated['map_latitude'] ?? null),
            'map_longitude' => vendorPortalNormalizedNumeric($validated['map_longitude'] ?? null),
            'map_place_id' => trim((string) ($validated['map_place_id'] ?? '')),
            'listing_category' => $listingCategory,
        ];

        if ($listingCategory === 'accommodation') {
            $details['measurement_system'] = 'imperial';
            $details['area_value'] = vendorPortalNormalizedNumeric($validated['area_value'] ?? null);
            $details['area_unit'] = 'sqft';
            $details['bedroom_count'] = isset($validated['bedroom_count']) ? (int) $validated['bedroom_count'] : null;
            $details['check_in_time'] = trim((string) ($validated['check_in_time'] ?? ''));
            $details['check_out_time'] = trim((string) ($validated['check_out_time'] ?? ''));
            $details['check_in_grace_minutes'] = isset($validated['check_in_grace_minutes']) && $validated['check_in_grace_minutes'] !== ''
                ? (int) $validated['check_in_grace_minutes']
                : null;
            $details['early_check_in_allowed'] = trim((string) ($validated['early_check_in_allowed'] ?? ''));
            $details['late_check_out_allowed'] = trim((string) ($validated['late_check_out_allowed'] ?? ''));
            $details['minimum_nights'] = isset($validated['minimum_nights']) && $validated['minimum_nights'] !== ''
                ? (int) $validated['minimum_nights']
                : null;
            $details['house_rules'] = trim((string) ($validated['house_rules'] ?? ''));
            $details['child_policy'] = trim((string) ($validated['child_policy'] ?? ''));
            $details['cancellation_policy'] = trim((string) ($validated['cancellation_policy'] ?? ''));
            $details['property_type'] = trim((string) ($validated['property_type'] ?? ''));
            $details['star_rating'] = isset($validated['star_rating']) && $validated['star_rating'] !== ''
                ? (int) $validated['star_rating']
                : null;
            $details['early_check_in_fee'] = vendorPortalNormalizedNumeric($validated['early_check_in_fee'] ?? null);
            $details['late_check_out_fee'] = vendorPortalNormalizedNumeric($validated['late_check_out_fee'] ?? null);
            $details['property_amenities'] = $propertyAmenities;
            $details['property_features'] = $propertyFeatures;
            $details['transfer_pricing_basis'] = 'per_pax';
            $details['transfer_options'] = $transferOptions;
            $details['transfer_rates'] = $transferRates;
            $details['transfer_rate_matrix'] = $transferRateMatrix;
            $details['transfer_base_local'] = max(0, (float) ($validated['transfer_base_local'] ?? 0));
            $details['transfer_base_foreign'] = max(0, (float) ($validated['transfer_base_foreign'] ?? 0));
            $details['vendor_tax_overrides'] = $vendorTaxOverrides;
        }

        if (in_array($listingCategory, ['transport', 'excursion', 'water_sports', 'remote_workspace', 'conference_room', 'resort_day_visit', 'restaurant', 'vehicle_rental'], true)) {
            $details['capacity_value'] = isset($validated['capacity_value']) ? (int) $validated['capacity_value'] : null;
        }

        if (in_array($listingCategory, ['excursion', 'water_sports'], true)) {
            $details['service_radius_km'] = vendorPortalNormalizedNumeric($validated['service_radius_km'] ?? null);
        }

        if ($listingCategory === 'transport') {
            $details['transport_mode'] = trim((string) ($validated['transport_mode'] ?? ''));
            $details['pickup_location'] = trim((string) ($validated['pickup_location'] ?? ''));
            $details['dropoff_location'] = trim((string) ($validated['dropoff_location'] ?? ''));
            $details['transport_departure_state'] = trim((string) ($validated['transport_departure_state'] ?? ''));
            $details['transport_departure_city'] = trim((string) ($validated['transport_departure_city'] ?? ''));
            $details['transport_arrival_state'] = trim((string) ($validated['transport_arrival_state'] ?? ''));
            $details['transport_arrival_city'] = trim((string) ($validated['transport_arrival_city'] ?? ''));
            $details['departure_area_port_jetty'] = trim((string) ($validated['departure_area_port_jetty'] ?? ''));
            $details['transport_trip_type'] = trim((string) ($validated['transport_trip_type'] ?? ''));
            $details['vehicle_name'] = trim((string) ($validated['vehicle_name'] ?? ''));
            $details['registration_plate'] = trim((string) ($validated['registration_plate'] ?? ''));
            $details['contact_name'] = trim((string) ($validated['contact_name'] ?? ''));
            $details['contact_number'] = trim((string) ($validated['contact_number'] ?? ''));
            $details['transport_pricing_model'] = trim((string) ($validated['transport_pricing_model'] ?? ''));
            $details['hourly_rate'] = vendorPortalNormalizedNumeric($validated['hourly_rate'] ?? null);
            $details['daily_rate'] = vendorPortalNormalizedNumeric($validated['daily_rate'] ?? null);
            $details['departure_date'] = trim((string) ($validated['departure_date'] ?? ''));
            $details['departure_time'] = trim((string) ($validated['departure_time'] ?? ''));
            $details['reporting_time'] = trim((string) ($validated['reporting_time'] ?? ''));
            $details['reporting_lead_minutes'] = isset($validated['reporting_lead_minutes']) && $validated['reporting_lead_minutes'] !== ''
                ? (int) $validated['reporting_lead_minutes']
                : null;
            $details['trip_duration_minutes'] = isset($validated['trip_duration_minutes']) ? (int) $validated['trip_duration_minutes'] : null;
            $details['schedule_start_time'] = trim((string) ($validated['schedule_start_time'] ?? ''));
            $details['schedule_end_time'] = trim((string) ($validated['schedule_end_time'] ?? ''));
            $details['booking_cutoff_minutes'] = isset($validated['booking_cutoff_minutes']) && $validated['booking_cutoff_minutes'] !== ''
                ? (int) $validated['booking_cutoff_minutes']
                : null;
            $details['boarding_instructions'] = trim((string) ($validated['boarding_instructions'] ?? ''));

            $transportModeProfile = vendorPortalTransportModeProfile((string) ($details['transport_mode'] ?? ''));
            $details['transport_pricing_basis'] = (string) ($transportModeProfile['pricing_basis'] ?? 'per_trip');
            if (($details['transport_pricing_basis'] ?? 'per_trip') === 'per_seat') {
                $details['transport_pricing_model'] = 'per_seat';
                $details['hourly_rate'] = null;
                $details['daily_rate'] = null;
            } elseif (!in_array(($details['transport_pricing_model'] ?? ''), ['per_trip', 'hourly', 'daily'], true)) {
                $details['transport_pricing_model'] = 'per_trip';
            }

            if (($details['transport_pricing_model'] ?? 'per_trip') === 'per_trip') {
                $details['hourly_rate'] = null;
                $details['daily_rate'] = null;
            } elseif (($details['transport_pricing_model'] ?? '') === 'hourly') {
                $details['daily_rate'] = null;
            } elseif (($details['transport_pricing_model'] ?? '') === 'daily') {
                $details['hourly_rate'] = null;
            }
        }

        if (in_array($listingCategory, ['excursion', 'water_sports'], true)) {
            $departurePoint = trim((string) ($validated['departure_point'] ?? $validated['meeting_point'] ?? ''));
            $rawWaiverRequired = strtolower(trim((string) ($validated['safety_waiver_required'] ?? '')));
            $waiverRequired = in_array($rawWaiverRequired, ['1', 'yes', 'true', 'on'], true)
                ? 'yes'
                : (in_array($rawWaiverRequired, ['0', 'no', 'false', 'off'], true) ? 'no' : '');
            $rawEquipmentRental = strtolower(trim((string) ($validated['equipment_rental_available'] ?? '')));
            $equipmentRental = in_array($rawEquipmentRental, ['1', 'yes', 'true', 'on'], true)
                ? 'yes'
                : (in_array($rawEquipmentRental, ['0', 'no', 'false', 'off'], true) ? 'no' : '');
            $activityStartTime = trim((string) ($validated['activity_start_time'] ?? ''));
            $activityEndTime = trim((string) ($validated['activity_end_time'] ?? ''));
            $autoDuration = vendorPortalDurationMinutesFromTimes($activityStartTime, $activityEndTime);

            $details['short_description'] = trim((string) ($validated['short_description'] ?? ''));
            $details['activity_start_time'] = $activityStartTime;
            $details['activity_end_time'] = $activityEndTime;
            $details['excursion_duration_minutes'] = $autoDuration ?? (isset($validated['excursion_duration_minutes']) ? (int) $validated['excursion_duration_minutes'] : null);
            $details['excursion_difficulty'] = trim((string) ($validated['excursion_difficulty'] ?? ''));
            $details['excursion_type'] = trim((string) ($validated['excursion_type'] ?? ''));
            $details['excursion_min_pax'] = isset($validated['excursion_min_pax']) && $validated['excursion_min_pax'] !== '' ? (int) $validated['excursion_min_pax'] : null;
            $details['excursion_max_pax'] = isset($validated['excursion_max_pax']) && $validated['excursion_max_pax'] !== '' ? (int) $validated['excursion_max_pax'] : null;
            $details['excursion_min_age'] = isset($validated['excursion_min_age']) && $validated['excursion_min_age'] !== '' ? (int) $validated['excursion_min_age'] : null;
            $details['departure_point'] = $departurePoint;
            $details['departure_time'] = trim((string) ($validated['departure_time'] ?? ''));
            $details['meeting_point'] = $departurePoint !== '' ? $departurePoint : trim((string) ($validated['meeting_point'] ?? ''));
            $details['inclusions'] = trim((string) ($validated['inclusions'] ?? ''));
            $details['exclusions'] = trim((string) ($validated['exclusions'] ?? ''));
            $details['safety_waiver_required'] = $waiverRequired;
            $details['equipment_rental_available'] = $equipmentRental;
            $details['equipment_included'] = vendorPortalNormalizedStringList($validated['equipment_included'] ?? []);
            $details['weather_cancellation_policy'] = trim((string) ($validated['weather_cancellation_policy'] ?? ''));
            $details['cancellation_policy'] = trim((string) ($validated['cancellation_policy'] ?? ''));
            $details['special_instructions'] = trim((string) ($validated['special_instructions'] ?? ''));
            $details['activity_schedule'] = trim((string) ($validated['activity_schedule'] ?? ''));
            // Transfer & slot configuration
            $rawTransferIncluded = strtolower(trim((string) ($validated['transfer_included'] ?? '0')));
            $details['transfer_included'] = in_array($rawTransferIncluded, ['1', 'yes', 'true', 'on'], true);
            $departureTimeMode = trim((string) ($validated['departure_time_mode'] ?? 'fixed'));
            $details['departure_time_mode'] = in_array($departureTimeMode, ['fixed', 'slots'], true) ? $departureTimeMode : 'fixed';
            $departureSlotRaw = trim((string) ($validated['departure_slots'] ?? ''));
            $details['departure_slots'] = $departureSlotRaw !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $departureSlotRaw) ?: []), static fn ($t) => $t !== ''))
                : [];
            $returnTimeMode = trim((string) ($validated['return_time_mode'] ?? 'fixed'));
            $details['return_time_mode'] = in_array($returnTimeMode, ['fixed', 'slots'], true) ? $returnTimeMode : 'fixed';
            $returnSlotRaw = trim((string) ($validated['return_slots'] ?? ''));
            $details['return_slots'] = $returnSlotRaw !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $returnSlotRaw) ?: []), static fn ($t) => $t !== ''))
                : [];
            // Pricing by residency
            $details['adult_price_local'] = isset($validated['adult_price_local']) && $validated['adult_price_local'] !== '' ? max(0, (float) $validated['adult_price_local']) : null;
            $details['adult_price_foreign'] = isset($validated['adult_price_foreign']) && $validated['adult_price_foreign'] !== '' ? max(0, (float) $validated['adult_price_foreign']) : null;
            $details['child_price_local'] = isset($validated['child_price_local']) && $validated['child_price_local'] !== '' ? max(0, (float) $validated['child_price_local']) : null;
            $details['child_price_foreign'] = isset($validated['child_price_foreign']) && $validated['child_price_foreign'] !== '' ? max(0, (float) $validated['child_price_foreign']) : null;
        }

        if ($listingCategory === 'remote_workspace') {
            $details['area_value'] = vendorPortalNormalizedNumeric($validated['area_value'] ?? null);
            $details['area_unit'] = 'sqft';
            $details['workspace_type'] = trim((string) ($validated['workspace_type'] ?? ''));
            $details['internet_speed_mbps'] = vendorPortalNormalizedNumeric($validated['internet_speed_mbps'] ?? null);

            $workspaceAmenityCatalog = [
                'workdesk',
                'wifi',
                'printing',
                'water_bottles',
                'coffee',
                'tea',
                'snacks',
            ];
            $workspaceAmenitiesFree = vendorPortalNormalizedStringList($validated['workspace_amenities_free'] ?? []);
            $workspaceAmenitiesPaid = vendorPortalNormalizedStringList($validated['workspace_amenities_paid'] ?? []);
            $workspaceAmenitiesFree = array_values(array_intersect($workspaceAmenityCatalog, $workspaceAmenitiesFree));
            $workspaceAmenitiesPaid = array_values(array_intersect($workspaceAmenityCatalog, $workspaceAmenitiesPaid));
            $workspaceAmenities = [];
            foreach ($workspaceAmenityCatalog as $amenityKey) {
                $status = 'not_available';
                if (in_array($amenityKey, $workspaceAmenitiesPaid, true)) {
                    $status = 'paid';
                } elseif (in_array($amenityKey, $workspaceAmenitiesFree, true)) {
                    $status = 'free';
                }

                $workspaceAmenities[$amenityKey] = [
                    'status' => $status,
                ];
            }
            $details['workspace_amenities'] = $workspaceAmenities;
            $details['workspace_amenities_free'] = $workspaceAmenitiesFree;
            $details['workspace_amenities_paid'] = $workspaceAmenitiesPaid;
            $details['transfer_pricing_basis'] = 'per_pax';
            $details['transfer_options'] = $transferOptions;
            $details['transfer_rates'] = $transferRates;
            $details['transfer_rate_matrix'] = $transferRateMatrix;
            $details['transfer_base_local'] = max(0, (float) ($validated['transfer_base_local'] ?? 0));
            $details['transfer_base_foreign'] = max(0, (float) ($validated['transfer_base_foreign'] ?? 0));
            $details['vendor_tax_overrides'] = $vendorTaxOverrides;
        }

        if ($listingCategory === 'resort_day_visit') {
            $details['day_visit_start_time'] = trim((string) ($validated['day_visit_start_time'] ?? ''));
            $details['day_visit_end_time'] = trim((string) ($validated['day_visit_end_time'] ?? ''));
            $details['included_access'] = trim((string) ($validated['included_access'] ?? ''));
            $details['activity_schedule'] = trim((string) ($validated['activity_schedule'] ?? ''));

            // Transfer & slot configuration
            $rawTransferIncludedRdv = strtolower(trim((string) ($validated['transfer_included'] ?? '0')));
            $details['transfer_included'] = in_array($rawTransferIncludedRdv, ['1', 'yes', 'true', 'on'], true);
            $details['departure_time'] = trim((string) ($validated['departure_time'] ?? ''));
            $departureTimeModeRdv = trim((string) ($validated['departure_time_mode'] ?? 'fixed'));
            $details['departure_time_mode'] = in_array($departureTimeModeRdv, ['fixed', 'slots'], true) ? $departureTimeModeRdv : 'fixed';
            $departureSlotRawRdv = trim((string) ($validated['departure_slots'] ?? ''));
            $details['departure_slots'] = $departureSlotRawRdv !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $departureSlotRawRdv) ?: []), static fn ($t) => $t !== ''))
                : [];
            $details['return_time'] = trim((string) ($validated['return_time'] ?? ''));
            $returnTimeModeRdv = trim((string) ($validated['return_time_mode'] ?? 'fixed'));
            $details['return_time_mode'] = in_array($returnTimeModeRdv, ['fixed', 'slots'], true) ? $returnTimeModeRdv : 'fixed';
            $returnSlotRawRdv = trim((string) ($validated['return_slots'] ?? ''));
            $details['return_slots'] = $returnSlotRawRdv !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $returnSlotRawRdv) ?: []), static fn ($t) => $t !== ''))
                : [];

            // Pricing by residency
            $details['price_per_adult'] = isset($validated['price_per_adult']) && $validated['price_per_adult'] !== '' ? max(0, (float) $validated['price_per_adult']) : null;
            $details['price_per_child'] = isset($validated['price_per_child']) && $validated['price_per_child'] !== '' ? max(0, (float) $validated['price_per_child']) : null;
            $details['adult_price_local'] = isset($validated['adult_price_local']) && $validated['adult_price_local'] !== '' ? max(0, (float) $validated['adult_price_local']) : null;
            $details['adult_price_foreign'] = isset($validated['adult_price_foreign']) && $validated['adult_price_foreign'] !== '' ? max(0, (float) $validated['adult_price_foreign']) : null;
            $details['child_price_local'] = isset($validated['child_price_local']) && $validated['child_price_local'] !== '' ? max(0, (float) $validated['child_price_local']) : null;
            $details['child_price_foreign'] = isset($validated['child_price_foreign']) && $validated['child_price_foreign'] !== '' ? max(0, (float) $validated['child_price_foreign']) : null;
        }

        if ($listingCategory === 'restaurant') {
            $details['cuisine_type'] = trim((string) ($validated['cuisine_type'] ?? ''));
            $details['meal_service'] = trim((string) ($validated['meal_service'] ?? ''));
        }

        if ($listingCategory === 'vehicle_rental') {
            $details['minimum_age'] = isset($validated['minimum_age']) ? (int) $validated['minimum_age'] : null;
            $details['vehicle_type'] = trim((string) ($validated['vehicle_type'] ?? ''));
            $details['transmission_type'] = trim((string) ($validated['transmission_type'] ?? ''));
            $details['fuel_type'] = trim((string) ($validated['fuel_type'] ?? ''));
        }

        if ($listingCategory === 'sea_transport') {
            $details['vessel_name'] = trim((string) ($validated['vessel_name'] ?? ''));
            $details['registration_no'] = trim((string) ($validated['registration_no'] ?? ''));
            $details['departure_point'] = trim((string) ($validated['departure_point'] ?? ''));
            $details['arrival_point'] = trim((string) ($validated['arrival_point'] ?? ''));
            $details['departure_time'] = trim((string) ($validated['departure_time'] ?? ''));
            $details['return_time'] = trim((string) ($validated['return_time'] ?? ''));
            $details['trip_duration_minutes'] = isset($validated['trip_duration_minutes']) ? (int) $validated['trip_duration_minutes'] : null;
            $details['total_seats'] = isset($validated['total_seats']) ? (int) $validated['total_seats'] : null;
            $details['local_price'] = isset($validated['local_price']) && $validated['local_price'] !== '' ? max(0, (float) $validated['local_price']) : null;
            $details['foreign_price'] = isset($validated['foreign_price']) && $validated['foreign_price'] !== '' ? max(0, (float) $validated['foreign_price']) : null;
            $details['contact_name'] = trim((string) ($validated['contact_name'] ?? ''));
            $details['contact_number'] = trim((string) ($validated['contact_number'] ?? ''));
            $details['boarding_instructions'] = trim((string) ($validated['boarding_instructions'] ?? ''));
            $availabilityRaw = trim((string) ($validated['availability_schedule'] ?? ''));
            $details['availability_schedule'] = $availabilityRaw !== ''
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $availabilityRaw) ?: []), static fn ($t) => $t !== ''))
                : [];
        }

        if ($listingCategory === 'liveaboard') {
            $details['start_point'] = trim((string) ($validated['start_point'] ?? ''));
            $details['end_point'] = trim((string) ($validated['end_point'] ?? ''));
            $details['journey_duration_days'] = isset($validated['journey_duration_days']) ? (int) $validated['journey_duration_days'] : null;
            $details['vessel_name'] = trim((string) ($validated['vessel_name'] ?? ''));
            $details['registration_no'] = trim((string) ($validated['registration_no'] ?? ''));
            $details['cabin_count'] = isset($validated['cabin_count']) ? (int) $validated['cabin_count'] : null;
            $details['contact_name'] = trim((string) ($validated['contact_name'] ?? ''));
            $details['contact_number'] = trim((string) ($validated['contact_number'] ?? ''));
            $details['boarding_instructions'] = trim((string) ($validated['boarding_instructions'] ?? ''));
            
            // Parse stopovers (one per line, format: StopoverName or StopoverName|embark|disembark)
            $stopoverRaw = trim((string) ($validated['stopovers'] ?? ''));
            $stopovers = [];
            if ($stopoverRaw !== '') {
                foreach (preg_split('/[\r\n]+/', $stopoverRaw) ?: [] as $stopoverLine) {
                    $stopoverLine = trim($stopoverLine);
                    if ($stopoverLine === '') continue;
                    $parts = preg_split('/\|/', $stopoverLine);
                    $name = trim($parts[0] ?? '');
                    if ($name === '') continue;
                    $allowEmbark = strtolower(trim($parts[1] ?? 'yes')) === 'yes';
                    $allowDisembark = strtolower(trim($parts[2] ?? 'yes')) === 'yes';
                    $stopovers[] = [
                        'name' => $name,
                        'allow_embark' => $allowEmbark,
                        'allow_disembark' => $allowDisembark,
                    ];
                }
            }
            $details['stopovers'] = $stopovers;
            
            // Parse pricing matrix (format: From→To=Price per line)
            $pricingRaw = trim((string) ($validated['pricing_matrix'] ?? ''));
            $pricingMatrix = [];
            if ($pricingRaw !== '') {
                foreach (preg_split('/[\r\n]+/', $pricingRaw) ?: [] as $pricingLine) {
                    $pricingLine = trim($pricingLine);
                    if ($pricingLine === '') continue;
                    if (!str_contains($pricingLine, '=')) continue;
                    [$routeKey, $price] = explode('=', $pricingLine, 2);
                    $routeKey = trim($routeKey);
                    $price = trim($price);
                    if ($routeKey === '' || !is_numeric($price)) continue;
                    $pricingMatrix[$routeKey] = max(0, (float) $price);
                }
            }
            $details['pricing_matrix'] = $pricingMatrix;
        }

        if ($listingCategory !== 'accommodation') {
            if (array_key_exists('transfer_included', $validated)) {
                $rawTransferIncluded = strtolower(trim((string) ($validated['transfer_included'] ?? '0')));
                $details['transfer_included'] = in_array($rawTransferIncluded, ['1', 'yes', 'true', 'on'], true);
            }

            if (array_key_exists('departure_time_mode', $validated)) {
                $departureTimeMode = trim((string) ($validated['departure_time_mode'] ?? 'fixed'));
                $details['departure_time_mode'] = in_array($departureTimeMode, ['fixed', 'slots'], true) ? $departureTimeMode : 'fixed';
            }

            if (array_key_exists('departure_time', $validated)) {
                $details['departure_time'] = trim((string) ($validated['departure_time'] ?? ''));
            }

            if (array_key_exists('departure_slots', $validated)) {
                $departureSlotRaw = trim((string) ($validated['departure_slots'] ?? ''));
                $details['departure_slots'] = $departureSlotRaw !== ''
                    ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $departureSlotRaw) ?: []), static fn ($t) => $t !== ''))
                    : [];
            }

            if (array_key_exists('return_time_mode', $validated)) {
                $returnTimeMode = trim((string) ($validated['return_time_mode'] ?? 'fixed'));
                $details['return_time_mode'] = in_array($returnTimeMode, ['fixed', 'slots'], true) ? $returnTimeMode : 'fixed';
            }

            if (array_key_exists('return_time', $validated)) {
                $details['return_time'] = trim((string) ($validated['return_time'] ?? ''));
            }

            if (array_key_exists('return_slots', $validated)) {
                $returnSlotRaw = trim((string) ($validated['return_slots'] ?? ''));
                $details['return_slots'] = $returnSlotRaw !== ''
                    ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $returnSlotRaw) ?: []), static fn ($t) => $t !== ''))
                    : [];
            }

            if (array_key_exists('price_local', $validated)) {
                $details['price_local'] = isset($validated['price_local']) && $validated['price_local'] !== ''
                    ? max(0, (float) $validated['price_local'])
                    : null;
            }

            if (array_key_exists('price_usd', $validated)) {
                $details['price_usd'] = isset($validated['price_usd']) && $validated['price_usd'] !== ''
                    ? max(0, (float) $validated['price_usd'])
                    : null;
                $details['price_foreign'] = $details['price_usd'];
            }

            // Normalized pricing structure for all non-accommodation categories.
            $pricingBySegment = [];

            $localAdult = isset($details['adult_price_local']) ? max(0, (float) $details['adult_price_local']) : null;
            $localChild = isset($details['child_price_local']) ? max(0, (float) $details['child_price_local']) : null;
            $foreignAdult = isset($details['adult_price_foreign'])
                ? max(0, (float) $details['adult_price_foreign'])
                : (isset($details['adult_price']) ? max(0, (float) $details['adult_price']) : (isset($details['price_per_adult']) ? max(0, (float) $details['price_per_adult']) : null));
            $foreignChild = isset($details['child_price_foreign'])
                ? max(0, (float) $details['child_price_foreign'])
                : (isset($details['child_price']) ? max(0, (float) $details['child_price']) : (isset($details['price_per_child']) ? max(0, (float) $details['price_per_child']) : null));
            $localFlat = isset($details['price_local']) ? max(0, (float) $details['price_local']) : null;
            $foreignFlat = isset($details['price_foreign']) ? max(0, (float) $details['price_foreign']) : null;

            if ($localAdult !== null || $localChild !== null || $localFlat !== null) {
                $pricingBySegment['local'] = array_filter([
                    'adult' => $localAdult,
                    'child' => $localChild,
                    'flat' => $localFlat,
                ], static fn ($value) => $value !== null);
            }
            if ($foreignAdult !== null || $foreignChild !== null || $foreignFlat !== null) {
                $pricingBySegment['foreign'] = array_filter([
                    'adult' => $foreignAdult,
                    'child' => $foreignChild,
                    'flat' => $foreignFlat,
                ], static fn ($value) => $value !== null);
            }

            if ($pricingBySegment !== []) {
                $details['pricing_by_segment'] = $pricingBySegment;
            }
        }

        return array_filter($details, static fn (mixed $value): bool => !($value === null || $value === ''));
    }
}

if (!function_exists('vendorPortalValidatePropertyDetails')) {
    function vendorPortalValidatePropertyDetails(string $listingCategory, array $details): array
    {
        $errors = [];
        $transferOptionCatalog = vendorPortalTransferOptionCatalog();

        if (in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
            if (!isset($details['area_value']) || $details['area_value'] < 5 || $details['area_value'] > 100000) {
                $errors[] = 'Area measurement must be between 5 and 100000.';
            }
            if (!in_array(($details['area_unit'] ?? ''), ['sqm', 'sqft'], true)) {
                $errors[] = 'Area unit must be sqm or sqft.';
            }
            foreach (['early_check_in_fee', 'late_check_out_fee'] as $feeField) {
                if (isset($details[$feeField]) && $details[$feeField] !== null && (float) $details[$feeField] < 0) {
                    $errors[] = 'Accommodation fee values cannot be negative.';
                    break;
                }
            }
        }

        if ($listingCategory === 'accommodation') {
            if (!empty($details['check_in_time']) && strtotime((string) $details['check_in_time']) === false) {
                $errors[] = 'Check-in time must be a valid time.';
            }
            if (!empty($details['check_out_time']) && strtotime((string) $details['check_out_time']) === false) {
                $errors[] = 'Check-out time must be a valid time.';
            }
            if (isset($details['check_in_grace_minutes']) && ((int) $details['check_in_grace_minutes'] < 0 || (int) $details['check_in_grace_minutes'] > 720)) {
                $errors[] = 'Check-in grace minutes must be between 0 and 720.';
            }
            if (!empty($details['early_check_in_allowed']) && !in_array((string) ($details['early_check_in_allowed'] ?? ''), ['yes', 'no', 'subject_to_availability'], true)) {
                $errors[] = 'Early check-in value is invalid.';
            }
            if (!empty($details['late_check_out_allowed']) && !in_array((string) ($details['late_check_out_allowed'] ?? ''), ['yes', 'no', 'subject_to_availability'], true)) {
                $errors[] = 'Late check-out value is invalid.';
            }
            if (isset($details['minimum_nights']) && ((int) $details['minimum_nights'] < 1 || (int) $details['minimum_nights'] > 365)) {
                $errors[] = 'Minimum nights must be between 1 and 365.';
            }
            if (isset($details['star_rating']) && $details['star_rating'] !== null && ((int) $details['star_rating'] < 1 || (int) $details['star_rating'] > 5)) {
                $errors[] = 'Star rating must be between 1 and 5.';
            }
        }

        if (in_array($listingCategory, ['transport', 'excursion', 'water_sports', 'conference_room', 'resort_day_visit', 'restaurant', 'vehicle_rental'], true)) {
            if (!isset($details['capacity_value']) || $details['capacity_value'] < 1 || $details['capacity_value'] > 20000) {
                $errors[] = 'Capacity must be between 1 and 20000 for this category.';
            }
        }

        if ($listingCategory === 'vehicle_rental') {
            if (!isset($details['minimum_age']) || $details['minimum_age'] < 16 || $details['minimum_age'] > 99) {
                $errors[] = 'Vehicle rental minimum age must be between 16 and 99.';
            }
            if (!empty($details['vehicle_type'])) {
                $invalidVehicleTypes = vendorPortalDisallowedOptionValues('vehicle_rental_type', [(string) $details['vehicle_type']]);
                if ($invalidVehicleTypes !== []) {
                    $errors[] = 'Vehicle rental type must be selected from the allowed land or marine catalog values.';
                }
            }
        }

        if ($listingCategory === 'transport' && empty($details['transport_mode'])) {
            $errors[] = 'Transport mode is required for transport listings.';
        }

        if ($listingCategory === 'transport' && !empty($details['transport_mode'])) {
            $transportModeProfile = vendorPortalTransportModeProfile((string) $details['transport_mode']);
            if (empty($details['pickup_location']) || empty($details['dropoff_location'])) {
                $errors[] = 'Pickup and dropoff locations are required for transport listings.';
            }
            if (empty($details['vehicle_name'])) {
                $errors[] = 'Vehicle name is required for transport listings.';
            }
            if (empty($details['registration_plate'])) {
                $errors[] = 'Registration plate number is required for transport listings.';
            }
            if (empty($details['contact_name'])) {
                $errors[] = 'Contact name is required for transport listings.';
            }
            if (empty($details['contact_number'])) {
                $errors[] = 'Contact number is required for transport listings.';
            }

            if (!empty($transportModeProfile['is_marine'])) {
                if (!in_array(($details['transport_trip_type'] ?? ''), ['one_way', 'round_trip'], true)) {
                    $errors[] = 'Select one-way or round-trip for marine transport listings.';
                }
                if (!isset($details['capacity_value']) || (int) $details['capacity_value'] < 1) {
                    $errors[] = 'Seat capacity is required for marine transport listings.';
                }
                if (empty($details['transport_departure_state']) || empty($details['transport_departure_city'])) {
                    $errors[] = 'Departure state/atoll and city/island are required for marine transport listings.';
                }
                if (empty($details['transport_arrival_state']) || empty($details['transport_arrival_city'])) {
                    $errors[] = 'Arrival state/atoll and city/island are required for marine transport listings.';
                }
                if (empty($details['departure_area_port_jetty'])) {
                    $errors[] = 'Departure area/port/jetty is required for marine transport listings.';
                }
                if (empty($details['departure_time'])) {
                    $errors[] = 'Departure time is required for marine transport listings.';
                }
                $hasReportingClock = !empty($details['reporting_time']);
                $hasReportingLead = isset($details['reporting_lead_minutes']) && $details['reporting_lead_minutes'] !== '';
                if (!$hasReportingClock && !$hasReportingLead) {
                    $errors[] = 'Reporting lead time is required for marine transport listings.';
                }
                if ($hasReportingLead && ((int) $details['reporting_lead_minutes'] < 0 || (int) $details['reporting_lead_minutes'] > 720)) {
                    $errors[] = 'Reporting lead time must be between 0 and 720 minutes for marine transport listings.';
                }
                if (!isset($details['trip_duration_minutes']) || (int) $details['trip_duration_minutes'] < 5 || (int) $details['trip_duration_minutes'] > 1440) {
                    $errors[] = 'Trip duration must be between 5 and 1440 minutes for marine transport listings.';
                }
            } else {
                $pricingModel = (string) ($details['transport_pricing_model'] ?? 'per_trip');
                if (!in_array($pricingModel, ['per_trip', 'hourly', 'daily'], true)) {
                    $errors[] = 'Select per-trip, hourly, or daily pricing for land transport listings.';
                }
                if (empty($details['location_state']) || empty($details['location_city'])) {
                    $errors[] = 'State/atoll and city/island are required for land transport listings.';
                }
                if ($pricingModel === 'hourly' && (!isset($details['hourly_rate']) || (float) $details['hourly_rate'] <= 0)) {
                    $errors[] = 'Hourly rate is required when hourly pricing is selected.';
                }
                if ($pricingModel === 'daily' && (!isset($details['daily_rate']) || (float) $details['daily_rate'] <= 0)) {
                    $errors[] = 'Daily rate is required when daily pricing is selected.';
                }
                if (!empty($details['schedule_start_time']) && !empty($details['schedule_end_time'])) {
                    $scheduleStart = strtotime((string) $details['schedule_start_time']);
                    $scheduleEnd = strtotime((string) $details['schedule_end_time']);
                    if ($scheduleStart !== false && $scheduleEnd !== false && $scheduleStart >= $scheduleEnd) {
                        $errors[] = 'Transport operating schedule end time must be after start time.';
                    }
                }
                if (isset($details['booking_cutoff_minutes']) && ((int) $details['booking_cutoff_minutes'] < 0 || (int) $details['booking_cutoff_minutes'] > 10080)) {
                    $errors[] = 'Transport booking cutoff must be between 0 and 10080 minutes.';
                }
            }
        }

        if (in_array($listingCategory, ['excursion', 'water_sports'], true)) {
            if (!isset($details['excursion_duration_minutes']) || $details['excursion_duration_minutes'] < 30 || $details['excursion_duration_minutes'] > 1440) {
                $errors[] = 'Excursion duration must be between 30 and 1440 minutes.';
            }
            if (!in_array(($details['excursion_difficulty'] ?? ''), ['easy', 'moderate', 'hard'], true)) {
                $errors[] = 'Excursion difficulty must be easy, moderate, or hard.';
            }
            if (!empty($details['excursion_type'])) {
                $invalidExcursionTypes = vendorPortalDisallowedOptionValues('excursion_type', [(string) $details['excursion_type']]);
                if ($invalidExcursionTypes !== []) {
                    $errors[] = 'Excursion type must be selected from the allowed catalog values.';
                }
            }
            if (!empty($details['safety_waiver_required']) && !in_array((string) ($details['safety_waiver_required'] ?? ''), ['yes', 'no'], true)) {
                $errors[] = 'Safety waiver field must be yes or no.';
            }
            if (!empty($details['equipment_rental_available']) && !in_array((string) ($details['equipment_rental_available'] ?? ''), ['yes', 'no'], true)) {
                $errors[] = 'Equipment rental field must be yes or no.';
            }
            if (!empty($details['equipment_included']) && is_array($details['equipment_included'])) {
                $allowedEquipment = ['snorkel_gear', 'life_jacket', 'fins', 'wetsuit', 'helmet', 'gopro_mount'];
                $invalidEquipment = array_values(array_diff(array_map(static fn ($item) => strtolower(trim((string) $item)), $details['equipment_included']), $allowedEquipment));
                if ($invalidEquipment !== []) {
                    $errors[] = 'Equipment included contains unsupported values.';
                }
            }
            if (isset($details['excursion_min_pax'], $details['excursion_max_pax'])
                && $details['excursion_min_pax'] !== null
                && $details['excursion_max_pax'] !== null
                && (int) $details['excursion_min_pax'] > (int) $details['excursion_max_pax']) {
                $errors[] = 'Excursion minimum participants cannot be greater than maximum participants.';
            }
            if (($listingCategory === 'water_sports' || $listingCategory === 'excursion') && trim((string) ($details['weather_cancellation_policy'] ?? '')) === '') {
                $errors[] = 'Weather cancellation policy is required for excursion and water sports listings.';
            }
        }

        if ($listingCategory === 'remote_workspace') {
            if (!in_array(($details['workspace_type'] ?? ''), ['shared', 'private', 'cabin'], true)) {
                $errors[] = 'Workspace type must be shared, private, or cabin.';
            }

            $workspaceAmenityCatalog = [
                'workdesk',
                'wifi',
                'printing',
                'water_bottles',
                'coffee',
                'tea',
                'snacks',
            ];
            $workspaceAmenities = is_array($details['workspace_amenities'] ?? null)
                ? $details['workspace_amenities']
                : [];
            $workspaceAmenitiesFree = is_array($details['workspace_amenities_free'] ?? null)
                ? array_map(static fn ($item) => strtolower(trim((string) $item)), $details['workspace_amenities_free'])
                : [];
            $workspaceAmenitiesPaid = is_array($details['workspace_amenities_paid'] ?? null)
                ? array_map(static fn ($item) => strtolower(trim((string) $item)), $details['workspace_amenities_paid'])
                : [];
            $workspaceAmenityOverlap = array_values(array_intersect($workspaceAmenitiesFree, $workspaceAmenitiesPaid));
            if ($workspaceAmenityOverlap !== []) {
                $errors[] = 'Each workspace amenity can be marked either free or paid, not both.';
            }
            foreach ($workspaceAmenityCatalog as $amenityKey) {
                $amenityConfig = is_array($workspaceAmenities[$amenityKey] ?? null)
                    ? $workspaceAmenities[$amenityKey]
                    : [];
                $status = strtolower(trim((string) ($amenityConfig['status'] ?? 'not_available')));
                if (!in_array($status, ['free', 'paid', 'not_available'], true)) {
                    $errors[] = 'Workspace amenity status must be free, paid, or not available.';
                    continue;
                }
            }
        }

        if (in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
            $transferOptions = is_array($details['transfer_options'] ?? null)
                ? array_map(static fn ($item): string => strtolower(trim((string) $item)), $details['transfer_options'])
                : [];
            $transferOptions = array_values(array_unique(array_filter($transferOptions, static fn (string $item): bool => $item !== '')));
            $invalidTransferOptions = array_values(array_diff($transferOptions, $transferOptionCatalog));
            if ($invalidTransferOptions !== []) {
                $errors[] = 'Transfer options must be selected from the allowed transfer catalog.';
            }

            $transferRates = is_array($details['transfer_rates'] ?? null)
                ? $details['transfer_rates']
                : [];
            $transferRateMatrix = is_array($details['transfer_rate_matrix'] ?? null)
                ? $details['transfer_rate_matrix']
                : [];
            foreach ($transferOptions as $transferOption) {
                $legacyRate = is_numeric($transferRates[$transferOption] ?? null)
                    ? (float) $transferRates[$transferOption]
                    : null;
                $matrixRow = is_array($transferRateMatrix[$transferOption] ?? null)
                    ? $transferRateMatrix[$transferOption]
                    : [];
                $matrixRates = [
                    is_numeric($matrixRow['local_adult_charge'] ?? null) ? (float) $matrixRow['local_adult_charge'] : null,
                    is_numeric($matrixRow['local_child_charge'] ?? null) ? (float) $matrixRow['local_child_charge'] : null,
                    is_numeric($matrixRow['foreign_adult_charge'] ?? null) ? (float) $matrixRow['foreign_adult_charge'] : null,
                    is_numeric($matrixRow['foreign_child_charge'] ?? null) ? (float) $matrixRow['foreign_child_charge'] : null,
                ];

                $hasConfiguredRate = ($legacyRate !== null && $legacyRate > 0)
                    || collect($matrixRates)->contains(static fn ($rate) => $rate !== null && $rate > 0);

                if (!$hasConfiguredRate) {
                    $errors[] = 'Set a transfer charge greater than zero for each selected transfer option.';
                    break;
                }
            }
        }

        if ($listingCategory === 'resort_day_visit') {
            if (empty($details['day_visit_start_time']) || empty($details['day_visit_end_time'])) {
                $errors[] = 'Day visit start and end times are required.';
            } else {
                $start = strtotime((string) $details['day_visit_start_time']);
                $end = strtotime((string) $details['day_visit_end_time']);
                if ($start !== false && $end !== false && $start >= $end) {
                    $errors[] = 'Day visit end time must be after start time.';
                }
            }
        }

        if ($listingCategory === 'restaurant' && empty($details['cuisine_type'])) {
            $errors[] = 'Cuisine type is required for restaurant listings.';
        }

        if ($listingCategory === 'restaurant' && !empty($details['meal_service'])) {
            $invalidMealServices = vendorPortalDisallowedOptionValues('restaurant_meal_service', [(string) $details['meal_service']]);
            if ($invalidMealServices !== []) {
                $errors[] = 'Restaurant meal service must be selected from the allowed catalog values.';
            }
        }

        if ($listingCategory === 'vehicle_rental' && empty($details['vehicle_type'])) {
            $errors[] = 'Vehicle type is required for vehicle rental listings.';
        }

        if ($listingCategory === 'sea_transport') {
            if (empty($details['departure_point'])) {
                $errors[] = 'Departure point is required for sea transport listings.';
            }
            if (empty($details['arrival_point'])) {
                $errors[] = 'Arrival point is required for sea transport listings.';
            }
            if (empty($details['departure_time'])) {
                $errors[] = 'Departure time is required for sea transport listings.';
            }
            if (empty($details['return_time'])) {
                $errors[] = 'Return / arrival time is required for sea transport listings.';
            }
            if (!isset($details['trip_duration_minutes']) || $details['trip_duration_minutes'] < 5 || $details['trip_duration_minutes'] > 1440) {
                $errors[] = 'Trip duration must be between 5 and 1440 minutes.';
            }
            if (!isset($details['total_seats']) || $details['total_seats'] < 1 || $details['total_seats'] > 1000) {
                $errors[] = 'Total seats must be between 1 and 1000.';
            }
            if (!isset($details['local_price']) || $details['local_price'] < 0) {
                $errors[] = 'Local price per seat is required and must be >= 0.';
            }
        }

        if ($listingCategory === 'liveaboard') {
            if (empty($details['start_point'])) {
                $errors[] = 'Start point is required for liveaboard listings.';
            }
            if (empty($details['end_point'])) {
                $errors[] = 'End point is required for liveaboard listings.';
            }
            if (!isset($details['journey_duration_days']) || $details['journey_duration_days'] < 1 || $details['journey_duration_days'] > 90) {
                $errors[] = 'Journey duration must be between 1 and 90 days.';
            }
            if (empty($details['stopovers'])) {
                $errors[] = 'At least one stopover is required for liveaboard listings.';
            }
            if (empty($details['pricing_matrix'])) {
                $errors[] = 'Pricing matrix with at least one route is required for liveaboard listings.';
            }
        }

        return $errors;
    }
}

if (!function_exists('vendorPortalBuildServiceDetails')) {
    function vendorPortalBuildServiceDetails(array $validated, string $listingCategory): array
    {
        $details = [
            'measurement_system' => (string) ($validated['measurement_system'] ?? 'metric'),
            'lead_time_minutes' => isset($validated['lead_time_minutes']) ? (int) $validated['lead_time_minutes'] : null,
            'min_booking_size' => isset($validated['min_booking_size']) ? (int) $validated['min_booking_size'] : null,
            'max_booking_size' => isset($validated['max_booking_size']) ? (int) $validated['max_booking_size'] : null,
            'quantity_unit' => (string) ($validated['quantity_unit'] ?? ''),
            'compliance_notes' => trim((string) ($validated['compliance_notes'] ?? '')),
            'listing_category' => $listingCategory,
        ];

        return array_filter($details, static fn (mixed $value): bool => !($value === null || $value === ''));
    }
}

if (!function_exists('vendorPortalValidateServiceDetails')) {
    function vendorPortalValidateServiceDetails(array $details): array
    {
        $errors = [];

        $minBooking = $details['min_booking_size'] ?? null;
        $maxBooking = $details['max_booking_size'] ?? null;
        if ($minBooking !== null && $maxBooking !== null && $minBooking > $maxBooking) {
            $errors[] = 'Min booking size cannot be greater than max booking size.';
        }

        if (isset($details['lead_time_minutes']) && ($details['lead_time_minutes'] < 0 || $details['lead_time_minutes'] > 43200)) {
            $errors[] = 'Lead time must be between 0 and 43200 minutes.';
        }

        return $errors;
    }
}

if (!function_exists('vendorPortalAccommodationRoomCount')) {
    function vendorPortalAccommodationRoomCount(int $vendorUserId, int $propertyId): int
    {
        if ($vendorUserId <= 0 || $propertyId <= 0) {
            return 0;
        }

        if (Schema::hasTable('vendor_property_room_categories')) {
            $roomCount = (int) DB::table('vendor_property_room_categories')
                ->where('vendor_user_id', $vendorUserId)
                ->where('vendor_property_id', $propertyId)
                ->sum('quantity');
            if ($roomCount > 0) {
                return $roomCount;
            }
        }

        $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($propertyId, $vendorUserId);
        $rawDetails = $property->listing_details ?? ($property->details ?? null);

        if (!$property || !is_string($rawDetails) || trim($rawDetails) === '') {
            return 0;
        }

        $details = json_decode($rawDetails, true);
        if (!is_array($details)) {
            return 0;
        }

        foreach (['room_count', 'rooms_total', 'bedroom_count'] as $candidateKey) {
            if (isset($details[$candidateKey]) && is_numeric($details[$candidateKey])) {
                return max(0, (int) $details[$candidateKey]);
            }
        }

        return 0;
    }
}

if (!function_exists('vendorPortalAccommodationRoomPricing')) {
    function vendorPortalAccommodationRoomPricing(
        int $vendorUserId,
        ?int $propertyId,
        ?int $roomId,
        int $adultGuests,
        int $childGuests,
        string $startAt,
        string $endAt,
        ?string $transferOption = null
    ): ?array {
        if ($vendorUserId <= 0 || $roomId === null || $roomId <= 0 || !Schema::hasTable('vendor_property_room_categories')) {
            return null;
        }

        $roomQuery = DB::table('vendor_property_room_categories')
            ->where('id', $roomId)
            ->where('vendor_user_id', $vendorUserId);

        if ($propertyId !== null && $propertyId > 0) {
            $roomQuery->where('vendor_property_id', $propertyId);
        }

        $room = $roomQuery->first();
        if (!$room) {
            return null;
        }

        $resolvedPropertyId = (int) ($room->vendor_property_id ?? 0);
        if ($resolvedPropertyId <= 0) {
            return null;
        }

        $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($resolvedPropertyId, $vendorUserId);
        if (!$property) {
            return null;
        }

        $propertyCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
        if ($propertyCategory !== 'accommodation') {
            return null;
        }

        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);
        $nights = 1;
        if ($startTs !== false && $endTs !== false && $endTs > $startTs) {
            $nights = max(1, (int) ceil(($endTs - $startTs) / 86400));
        }

        $baseOccupancy = max(1, (int) ($room->max_occupancy ?? 1));
        $extraAdultCapacity = max(0, (int) ($room->extra_person_capacity ?? 0));
        $childCapacity = max(0, (int) ($room->child_capacity ?? 0));

        $chargeableExtraAdults = max(0, $adultGuests - $baseOccupancy);
        if ($extraAdultCapacity > 0) {
            $chargeableExtraAdults = min($chargeableExtraAdults, $extraAdultCapacity);
        }

        $chargeableChildren = max(0, $childGuests);
        if ($childCapacity > 0) {
            $chargeableChildren = min($chargeableChildren, $childCapacity);
        }

        $baseRoomPrice = max(0, (float) ($room->base_price ?? 0));
        $extraAdultPrice = max(0, (float) ($room->extra_person_price ?? 0));
        $childPrice = max(0, (float) ($room->child_price ?? 0));

        $nightlySubtotal = $baseRoomPrice + ($chargeableExtraAdults * $extraAdultPrice) + ($chargeableChildren * $childPrice);
        $subtotal = round($nightlySubtotal * $nights, 2);
        $transferCharge = vendorPortalPropertyTransferCharge(
            $vendorUserId,
            $resolvedPropertyId,
            $transferOption,
            $adultGuests + $childGuests
        );

        $transferOptionApplied = trim((string) ($transferCharge['transfer_option'] ?? ''));
        $transferRatePerPax = max(0, (float) ($transferCharge['transfer_rate_per_pax'] ?? 0));
        $transferGuestCount = max(0, (int) ($transferCharge['transfer_guest_count'] ?? 0));
        $transferChargeTotal = max(0, (float) ($transferCharge['transfer_charge_total'] ?? 0));
        $subtotalWithTransfer = round($subtotal + $transferChargeTotal, 2);

        return [
            'property_id' => $resolvedPropertyId,
            'room_id' => (int) ($room->id ?? 0),
            'nights' => $nights,
            'base_occupancy' => $baseOccupancy,
            'adult_guests' => $adultGuests,
            'child_guests' => $childGuests,
            'chargeable_extra_adults' => $chargeableExtraAdults,
            'chargeable_children' => $chargeableChildren,
            'base_room_price' => $baseRoomPrice,
            'extra_adult_price' => $extraAdultPrice,
            'child_price' => $childPrice,
            'nightly_subtotal' => round($nightlySubtotal, 2),
            'subtotal' => $subtotal,
            'transfer_option' => $transferOptionApplied,
            'transfer_rate_per_pax' => round($transferRatePerPax, 2),
            'transfer_guest_count' => $transferGuestCount,
            'transfer_charge_total' => round($transferChargeTotal, 2),
            'subtotal_with_transfer' => $subtotalWithTransfer,
        ];
    }
}

if (!function_exists('vendorPortalPropertyTransferCharge')) {
    function vendorPortalPropertyTransferCharge(
        int $vendorUserId,
        ?int $propertyId,
        ?string $transferOption,
        int $guestCount
    ): array {
        $requestedOption = strtolower(trim((string) $transferOption));
        $effectiveGuestCount = max(0, $guestCount);

        $result = [
            'transfer_option' => '',
            'transfer_rate_per_pax' => 0.0,
            'transfer_guest_count' => $effectiveGuestCount,
            'transfer_charge_total' => 0.0,
        ];

        if ($vendorUserId <= 0 || $propertyId === null || $propertyId <= 0 || $requestedOption === '') {
            return $result;
        }

        $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($propertyId, $vendorUserId);

        if (!$property) {
            return $result;
        }

        $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
        if (!in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
            return $result;
        }

        $detailsRaw = (string) ($property->listing_details ?? ($property->details ?? ''));
        $details = is_string($detailsRaw) && trim($detailsRaw) !== ''
            ? json_decode($detailsRaw, true)
            : [];
        if (!is_array($details)) {
            return $result;
        }

        $configuredOptions = array_map(
            static fn ($item): string => strtolower(trim((string) $item)),
            is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : []
        );
        $configuredOptions = array_values(array_filter($configuredOptions, static fn (string $item): bool => $item !== ''));
        if (!in_array($requestedOption, $configuredOptions, true)) {
            return $result;
        }

        $rates = is_array($details['transfer_rates'] ?? null) ? $details['transfer_rates'] : [];
        $ratePerPax = isset($rates[$requestedOption]) && is_numeric($rates[$requestedOption])
            ? max(0, (float) $rates[$requestedOption])
            : 0.0;
        if ($ratePerPax <= 0 || $effectiveGuestCount <= 0) {
            return $result;
        }

        $result['transfer_option'] = $requestedOption;
        $result['transfer_rate_per_pax'] = round($ratePerPax, 2);
        $result['transfer_charge_total'] = round($ratePerPax * $effectiveGuestCount, 2);

        return $result;
    }
}

if (!function_exists('vendorPortalReservationInvoiceTaxes')) {
    function vendorPortalReservationInvoiceTaxes(
        int $vendorUserId,
        ?int $propertyId,
        int $guests,
        float $baseAmount,
        bool $isForeigner,
        float $transferChargeTotal = 0
    ): array {
        $subtotalAmount = round(max(0, $baseAmount), 2);
        $transferChargeAmount = round(max(0, $transferChargeTotal), 2);

        $breakdown = [
            'applied' => false,
            'listing_category' => null,
            'guest_is_foreigner' => $isForeigner,
            'rooms_count' => 0,
            'service_charge_rate_percent' => 0.0,
            'service_charge_total' => 0.0,
            'green_tax_rate_per_person' => 0.0,
            'green_tax_total' => 0.0,
            'tgst_rate_percent' => 0.0,
            'tgst_total' => 0.0,
            'cgst_rate_percent' => 0.0,
            'cgst_total' => 0.0,
            'subtotal_amount' => $subtotalAmount,
            'transfer_charge_total' => $transferChargeAmount,
            'total_tax_amount' => 0.0,
            'invoice_total_amount' => round($subtotalAmount + $transferChargeAmount, 2),
            'tax_lines' => [],
        ];

        if ($propertyId === null || $propertyId <= 0) {
            return $breakdown;
        }

        $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($propertyId, $vendorUserId);

        if (!$property) {
            return $breakdown;
        }

        $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
        $breakdown['listing_category'] = $listingCategory;

        $details = [];
        $rawDetails = $property->listing_details ?? ($property->details ?? null);
        if (is_string($rawDetails) && trim($rawDetails) !== '') {
            $decoded = json_decode($rawDetails, true);
            if (is_array($decoded)) {
                $details = $decoded;
            }
        }

        $vendorTaxOverrides = is_array($details['vendor_tax_overrides'] ?? null) ? $details['vendor_tax_overrides'] : [];
        $roomsCount = vendorPortalAccommodationRoomCount($vendorUserId, $propertyId);
        $breakdown['rooms_count'] = $roomsCount;

        $pricing = ReservationPricingPolicy::calculate([
            'listing_category' => (string) ($listingCategory ?? 'accommodation'),
            'subtotal_amount' => $subtotalAmount,
            'discount_percent' => 0,
            'adults' => max(1, (int) $guests),
            'children' => 0,
            'nights' => 1,
            'room_count' => $roomsCount,
            'guest_residency' => $isForeigner ? 'foreign_national' : 'local_resident',
            'transfer_option' => '',
            'transfer_charge_override' => $transferChargeAmount,
            'vendor_tax_overrides' => $vendorTaxOverrides,
        ]);

        $breakdown['applied'] = true;
        $breakdown['service_charge_rate_percent'] = (float) ($pricing['service_charge_rate_percent'] ?? 0);
        $breakdown['service_charge_total'] = (float) ($pricing['service_charge_total'] ?? 0);
        $breakdown['green_tax_rate_per_person'] = (float) ($pricing['green_tax_rate_per_person_per_night'] ?? 0);
        $breakdown['green_tax_total'] = (float) ($pricing['green_tax_total'] ?? 0);
        $breakdown['tgst_rate_percent'] = (float) ($pricing['tgst_rate_percent'] ?? 0);
        $breakdown['tgst_total'] = (float) ($pricing['tgst_total'] ?? 0);
        $breakdown['cgst_rate_percent'] = (float) ($pricing['gst_rate_percent'] ?? 0);
        $breakdown['cgst_total'] = (float) ($pricing['gst_total'] ?? 0);
        $breakdown['total_tax_amount'] = (float) ($pricing['total_tax_amount'] ?? 0);
        $breakdown['invoice_total_amount'] = (float) ($pricing['invoice_total_amount'] ?? 0);
        $breakdown['tax_lines'] = is_array($pricing['tax_lines'] ?? null) ? $pricing['tax_lines'] : [];

        return $breakdown;
    }
}


require __DIR__ . '/vendor/dashboard.php';
require __DIR__ . '/vendor/profile-media.php';
require __DIR__ . '/vendor/listings-management.php';
require __DIR__ . '/vendor/operations-actions.php';