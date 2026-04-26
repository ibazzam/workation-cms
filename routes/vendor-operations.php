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
            'marine_transport' => 'Marine Transport',
            'land_transport' => 'Land Transport',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspaces',
            'resort_day_visit' => 'Resort Day Visits',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
            'water_sports' => 'Water Sports',
            'conference_room' => 'Conference Rooms',
        ];
    }
}

if (!function_exists('vendorPortalCategoryAliases')) {
    function vendorPortalCategoryAliases(): array
    {
        return [
            'accommodation' => 'accommodation',
            'accommodations' => 'accommodation',
            // Legacy transport values remain valid and default to marine transport.
            'transport' => 'marine_transport',
            'transports' => 'marine_transport',
            'marine_transport' => 'marine_transport',
            'marine_transports' => 'marine_transport',
            'marine_transportation' => 'marine_transport',
            'marine-transport' => 'marine_transport',
            'marinetransport' => 'marine_transport',
            'marinetransports' => 'marine_transport',
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
        ];
    }
}

if (!function_exists('vendorPortalCategoryRequiredDocumentChecklist')) {
    function vendorPortalCategoryRequiredDocumentChecklist(): array
    {
        return [
            'marine_transport' => ['Valid marine transport operating license', 'Vessel registration or operator permit'],
            'land_transport' => ['Valid transport operator license', 'Vehicle registration/commercial permit'],
            'water_sports' => ['Activity safety/compliance certification', 'Operator or instructor certification'],
            'excursion' => ['Tour/excursion operator permit', 'Public liability or compliance certificate'],
            'remote_workspace' => ['Business/trade registration for workspace operations'],
            'conference_room' => ['Venue operation approval or business permit'],
            'resort_day_visit' => ['Resort partnership authorization or operating permit'],
            'restaurant' => ['Food service license', 'Health/sanitation compliance certificate'],
            'vehicle_rental' => ['Vehicle rental operator permit', 'Vehicle fleet registration evidence'],
            'accommodation' => ['Tourism or accommodation operating license'],
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
            $details['excursion_duration_minutes'] = isset($validated['excursion_duration_minutes']) ? (int) $validated['excursion_duration_minutes'] : null;
            $details['excursion_difficulty'] = trim((string) ($validated['excursion_difficulty'] ?? ''));
            $details['excursion_type'] = trim((string) ($validated['excursion_type'] ?? ''));
            $details['excursion_min_pax'] = isset($validated['excursion_min_pax']) && $validated['excursion_min_pax'] !== '' ? (int) $validated['excursion_min_pax'] : null;
            $details['excursion_max_pax'] = isset($validated['excursion_max_pax']) && $validated['excursion_max_pax'] !== '' ? (int) $validated['excursion_max_pax'] : null;
            $details['excursion_min_age'] = isset($validated['excursion_min_age']) && $validated['excursion_min_age'] !== '' ? (int) $validated['excursion_min_age'] : null;
            $details['meeting_point'] = trim((string) ($validated['meeting_point'] ?? ''));
            $details['inclusions'] = trim((string) ($validated['inclusions'] ?? ''));
            $details['exclusions'] = trim((string) ($validated['exclusions'] ?? ''));
            $details['safety_waiver_required'] = trim((string) ($validated['safety_waiver_required'] ?? ''));
            $details['equipment_rental_available'] = trim((string) ($validated['equipment_rental_available'] ?? ''));
            $details['equipment_included'] = vendorPortalNormalizedStringList($validated['equipment_included'] ?? []);
            $details['weather_cancellation_policy'] = trim((string) ($validated['weather_cancellation_policy'] ?? ''));
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
            foreach ($transferOptions as $transferOption) {
                $transferRate = $transferRates[$transferOption] ?? null;
                if (!is_numeric($transferRate) || (float) $transferRate <= 0) {
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

Route::get('/vendor', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;

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
    $vendorRoomCategories = collect();
    $vendorMediaAssets = collect();
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

    if ($vendorUserId > 0) {
        // Load vendor listings from dedicated category tables.
        $vendorProperties = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId, 200);

        $existingListingCategories = $vendorProperties
            ->map(static fn ($property) => vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')))
            ->filter(static fn ($category) => is_string($category) && $category !== '')
            ->values()
            ->all();
        if ($existingListingCategories !== []) {
            $selectedVendorCategories = array_values(array_unique(array_merge($selectedVendorCategories, $existingListingCategories)));
        }

        if (Schema::hasTable('vendor_services')) {
            $vendorServices = DB::table('vendor_services')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(250)
                ->get();

            $existingServiceCategories = $vendorServices
                ->map(static fn ($service) => vendorPortalCanonicalCategory((string) ($service->listing_category ?? '')))
                ->filter(static fn ($category) => is_string($category) && $category !== '')
                ->values()
                ->all();
            if ($existingServiceCategories !== []) {
                $selectedVendorCategories = array_values(array_unique(array_merge($selectedVendorCategories, $existingServiceCategories)));
            }
        }

        if (Schema::hasTable('vendor_availability_slots')) {
            $vendorAvailability = DB::table('vendor_availability_slots')
                ->where('vendor_user_id', $vendorUserId)
                ->orderBy('slot_date')
                ->limit(365)
                ->get();
        }

        if (Schema::hasTable('vendor_reservations')) {
            $vendorReservations = DB::table('vendor_reservations')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('start_at')
                ->limit(300)
                ->get();
        }

        if (Schema::hasTable('vendor_pricing_rules')) {
            $vendorPricingRules = DB::table('vendor_pricing_rules')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_billing_details')) {
            $vendorBilling = DB::table('vendor_billing_details')
                ->where('vendor_user_id', $vendorUserId)
                ->first();
        }

        if (Schema::hasTable('vendor_property_room_categories')) {
            $vendorRoomCategories = DB::table('vendor_property_room_categories')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_listing_media')) {
            $vendorMediaAssets = DB::table('vendor_listing_media')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();
        }

        $vendorPropertyIds = $vendorProperties
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

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
            $dateColumn = collect(['created_at', 'reviewed_at', 'submitted_at', 'updated_at'])->first(static fn ($column) => in_array($column, $columns, true));

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

            $ratingColumn = collect(['rating', 'rating_value', 'review_score', 'score'])->first(static fn ($column) => in_array($column, $columns, true));
            $commentColumn = collect(['review_comment', 'comment', 'review_text', 'feedback', 'notes'])->first(static fn ($column) => in_array($column, $columns, true));
            $statusColumn = collect(['status', 'review_status', 'moderation_status'])->first(static fn ($column) => in_array($column, $columns, true));
            $nameColumn = collect(['customer_name', 'guest_name', 'reviewer_name', 'name'])->first(static fn ($column) => in_array($column, $columns, true));
            $emailColumn = collect(['customer_email', 'guest_email', 'reviewer_email', 'email'])->first(static fn ($column) => in_array($column, $columns, true));
            $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response'])->first(static fn ($column) => in_array($column, $columns, true));
            $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));

            $selectColumns = collect([$idColumn, $propertyColumn, $ratingColumn, $commentColumn, $statusColumn, $nameColumn, $emailColumn, $responseColumn, $respondedAtColumn, $dateColumn])
                ->filter(static fn ($column) => is_string($column) && $column !== '')
                ->unique()
                ->values()
                ->all();

            $rows = $query->limit(80)->get($selectColumns);
            if ($rows->isEmpty()) {
                continue;
            }

            $vendorEngagement['reviews_table'] = $reviewTable;
            $vendorEngagement['reviews'] = $rows->map(static function ($row) use ($idColumn, $propertyColumn, $ratingColumn, $commentColumn, $statusColumn, $nameColumn, $emailColumn, $responseColumn, $respondedAtColumn, $dateColumn) {
                return [
                    'id' => (int) (($row->{$idColumn} ?? 0) ?: 0),
                    'vendor_property_id' => $propertyColumn ? (int) (($row->{$propertyColumn} ?? 0) ?: 0) : 0,
                    'rating' => $ratingColumn ? (float) (($row->{$ratingColumn} ?? 0) ?: 0) : 0,
                    'comment' => trim((string) ($commentColumn ? ($row->{$commentColumn} ?? '') : '')),
                    'status' => strtolower(trim((string) ($statusColumn ? ($row->{$statusColumn} ?? 'pending') : 'pending'))),
                    'customer_name' => trim((string) ($nameColumn ? ($row->{$nameColumn} ?? 'Guest') : 'Guest')),
                    'customer_email' => trim((string) ($emailColumn ? ($row->{$emailColumn} ?? '') : '')),
                    'response' => trim((string) ($responseColumn ? ($row->{$responseColumn} ?? '') : '')),
                    'responded_at' => trim((string) ($respondedAtColumn ? ($row->{$respondedAtColumn} ?? '') : '')),
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
        'vendorRoomCategories' => $vendorRoomCategories,
        'vendorRooms' => $vendorRoomCategories,
        'vendorMediaAssets' => $vendorMediaAssets,
        'vendorEngagement' => $vendorEngagement,
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

    return redirect('/vendor?page=overview')->with('portal_active_panel', 'overview');
});

Route::get('/vendor/profile', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $section = strtolower(trim((string) request()->query('section', '')));
    $allowedSections = ['profile', 'categories', 'banking', 'address', 'password', 'all'];

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
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['marine_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    return redirect('/vendor?page=listings')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'create')
        ->with('portal_listing_category', $normalizedCategory);
});

$vendorListingCategoryAliases = [
    'accommodation',
    'marine_transport',
    'land_transport',
    'water_sports',
    'excursion',
    'remote_workspace',
    'conference_room',
    'resort_day_visit',
    'restaurant',
    'vehicle_rental',
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

        return redirect('/vendor?page=listings')
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
        $listingCategoryViewOrder = ['accommodation','marine_transport','land_transport','water_sports','excursion','remote_workspace','conference_room','resort_day_visit','restaurant','vehicle_rental'];
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, ['marine_transport' => 'Marine Transport', 'land_transport' => 'Land Transport', 'conference_room' => 'Conference Rooms']);
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
        $listingCategoryViewOrder = ['accommodation','marine_transport','land_transport','water_sports','excursion','remote_workspace','conference_room','resort_day_visit','restaurant','vehicle_rental'];
        $listingCategoryLabelMap = array_merge($vendorCategoryMap, ['marine_transport' => 'Marine Transport', 'land_transport' => 'Land Transport', 'conference_room' => 'Conference Rooms']);
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
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['marine_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    return redirect('/vendor?page=listings')
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
    $allowedCategories = array_merge(array_keys(vendorPortalCategoryMap()), ['marine_transport', 'land_transport']);
    if (!in_array($normalizedCategory, $allowedCategories, true)) {
        return redirect('/vendor?page=listings')->withErrors([
            'profile' => 'Unsupported listing category route.',
        ]);
    }

    return redirect('/vendor?page=listings')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1)
        ->with('portal_listing_mode', 'manage')
        ->with('portal_listing_category', $normalizedCategory);
});

Route::get('/vendor/reservations', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor?page=reservations')->with('portal_active_panel', 'reservations');
});

Route::get('/vendor/availability', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor?page=reservations')->with('portal_active_panel', 'reservations');
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

    return redirect('/vendor?page=reservations')->with('portal_active_panel', 'reservations');
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

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!vendorPortalCanManageListings($vendorUser)) {
        return redirect('/vendor?page=profile')
            ->with('portal_active_panel', 'profile')
            ->withErrors(['profile' => 'Pricing controls are locked until your vendor account is verified and approved by admin.']);
    }

    return redirect('/vendor?page=pricing')->with('portal_active_panel', 'reservations');
});

Route::get('/vendor/billing', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor?page=billing')->with('portal_active_panel', 'billing');
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

    return redirect('/vendor?page=promotions')->with('portal_active_panel', 'engagement');
});

Route::get('/vendor/reports', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

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
        'Date', 'Subtotal', 'Tax Total', 'Gross', 'Commission (12%)', 'Expected Payout',
        'Payment Status', 'Booking Status',
    ]);

    foreach ($rows as $reservation) {
        $gross = (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0);
        $subtotal = (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0);
        $taxTotal = (float) ($reservation->total_tax_amount ?? 0);
        $paymentStatus = (string) ($reservation->payment_status ?? 'unpaid');
        $bookingStatus = (string) ($reservation->status ?? 'pending');
        $isSettled = $paymentStatus === 'paid' && in_array($bookingStatus, ['confirmed', 'completed'], true);
        $commission = $isSettled ? round($gross * $commissionRate, 2) : 0.0;
        $payout = max(0, round($gross - $commission, 2));
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
            number_format($payout, 2, '.', ''),
            $paymentStatus,
            $bookingStatus,
        ]);
    }

    $csvContent = implode("\r\n", $csvLines);
    $filename = 'vendor-report-' . date('Y-m-d') . '.csv';

    return response($csvContent, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
        'Pragma' => 'no-cache',
    ]);
});

Route::post('/portal/vendor/categories/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'categories' => ['required', 'array', 'min:1'],
        'categories.*' => ['required', 'string', 'max:80'],
        'onboarding_step' => ['nullable', 'integer', 'min:1', 'max:4'],
        'request_action' => ['nullable', Rule::in(['subscribe', 'open', 'release'])],
        'request_note' => ['nullable', 'string', 'max:2000'],
        'supporting_documents' => ['nullable', 'array'],
        'supporting_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
    ]);

    $allowedCategoryKeys = array_keys(vendorPortalCategoryMap());
    $normalizedCategories = [];
    foreach ($validated['categories'] as $inputCategory) {
        $canonicalCategory = vendorPortalCanonicalCategory((string) $inputCategory);
        if ($canonicalCategory === null || !in_array($canonicalCategory, $allowedCategoryKeys, true)) {
            return back()->withErrors([
                'profile' => 'Unsupported vendor category provided. Please select from the listed categories.',
            ])->withInput();
        }

        $normalizedCategories[] = $canonicalCategory;
    }
    $normalizedCategories = array_values(array_unique($normalizedCategories));
    $requestAction = (string) ($validated['request_action'] ?? 'subscribe');
    $requiredDocuments = vendorPortalRequiredDocumentsForCategories($normalizedCategories);

    if (in_array($requestAction, ['subscribe', 'open'], true) && $requiredDocuments !== [] && empty($validated['supporting_documents'])) {
        return back()->withErrors([
            'profile' => 'Supporting documents are required for the selected categories: ' . implode('; ', $requiredDocuments),
        ])->withInput();
    }

    if (Schema::hasColumn('users', 'portal_service_categories')) {
        $vendorUser->portal_service_categories = json_encode($normalizedCategories);
    }

    if (Schema::hasColumn('users', 'vendor_onboarding_step')) {
        $vendorUser->vendor_onboarding_step = (int) ($validated['onboarding_step'] ?? 1);
    }

    $uploadedDocuments = [];
    foreach ((array) ($validated['supporting_documents'] ?? []) as $document) {
        if ($document instanceof \Illuminate\Http\UploadedFile) {
            $storedPath = $document->store('vendor/compliance-documents/' . (int) $vendorUser->id, 'public');
            if (is_string($storedPath) && $storedPath !== '') {
                $uploadedDocuments[] = [
                    'name' => (string) $document->getClientOriginalName(),
                    'path' => $storedPath,
                    'url' => Storage::disk('public')->url($storedPath),
                ];
            }
        }
    }

    if ($uploadedDocuments !== [] && Schema::hasColumn('users', 'vendor_verification_documents')) {
        $vendorUser->vendor_verification_documents = json_encode($uploadedDocuments);
    }

    $vendorUser->save();

    if (function_exists('portalActionRequestsEnabled') && function_exists('createPortalActionRequest') && portalActionRequestsEnabled()) {
        $requestReason = trim((string) ($validated['request_note'] ?? ''));
        createPortalActionRequest(
            'vendor.category_request',
            (int) $vendorUser->id,
            null,
            (string) ($vendorUser->username ?: $vendorUser->email),
            $requestReason !== '' ? $requestReason : 'Vendor category request submitted from portal.',
            [
                'request_action' => $requestAction,
                'categories' => $normalizedCategories,
                'documents' => $uploadedDocuments,
                'required_documents' => $requiredDocuments,
                'requested_via' => 'vendor_portal',
            ]
        );
    }

    return back()->with('portal_notice', 'Category request saved and sent for admin validation review.');
});

Route::post('/portal/vendor/profile/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'display_name' => ['required', 'string', 'max:120'],
        'contact_phone' => ['required', 'string', 'max:40'],
        'company_name' => ['required', 'string', 'max:190'],
        'business_registration_number' => ['required', 'string', 'max:120'],
        'business_license_number' => ['nullable', 'string', 'max:120'],
        'contact_person_name' => ['required', 'string', 'max:190'],
        'contact_person_phone' => ['required', 'string', 'max:60'],
        'contact_person_email' => ['required', 'email:rfc', 'max:190'],
        'contact_person_id_number' => ['required', 'string', 'max:120'],
    ]);

    $vendorUser->name = trim((string) $validated['display_name']);
    if (Schema::hasColumn('users', 'phone')) {
        $vendorUser->phone = vendorNormalizePhoneNumber((string) ($validated['contact_phone'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_company_name')) {
        $vendorUser->vendor_company_name = trim((string) ($validated['company_name'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_business_registration_number')) {
        $vendorUser->vendor_business_registration_number = trim((string) ($validated['business_registration_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_business_license_number')) {
        $vendorUser->vendor_business_license_number = trim((string) ($validated['business_license_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_name')) {
        $vendorUser->vendor_contact_person_name = trim((string) ($validated['contact_person_name'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_phone')) {
        $vendorUser->vendor_contact_person_phone = vendorNormalizePhoneNumber((string) ($validated['contact_person_phone'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_email')) {
        $vendorUser->vendor_contact_person_email = strtolower(trim((string) ($validated['contact_person_email'] ?? '')));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_id_number')) {
        $vendorUser->vendor_contact_person_id_number = trim((string) ($validated['contact_person_id_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_legal_documents_submitted_at')) {
        $vendorUser->vendor_legal_documents_submitted_at = now();
    }
    if (Schema::hasColumn('users', 'vendor_verification_status')) {
        $currentStatus = strtolower(trim((string) ($vendorUser->vendor_verification_status ?? 'pending')));
        if (in_array($currentStatus, ['pending', 'rejected'], true)) {
            $vendorUser->vendor_verification_status = 'under_review';
        }
    }
    $vendorUser->save();

    session([
        'portal_vendor_user' => $vendorUser->name,
    ]);

    return back()->with('portal_notice', 'Profile and compliance details saved. Verification review status updated.');
});

Route::post('/portal/vendor/media/upload', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'entity_type' => ['required', Rule::in(['property', 'service', 'room', 'profile', 'menu', 'vehicle'])],
        'entity_id' => ['nullable', 'integer', 'min:1'],
        'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'photos' => ['nullable', 'array', 'min:1'],
        'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'alt_text' => ['required', 'string', 'max:190'],
        'is_primary' => ['nullable', 'boolean'],
        'primary_upload_index' => ['nullable', 'integer', 'min:0'],
    ]);

    $entityType = (string) $validated['entity_type'];
    $entityId = filled($validated['entity_id'] ?? null) ? (int) $validated['entity_id'] : null;

    if (in_array($entityType, ['property', 'room'], true) && ($entityId === null || $entityId <= 0)) {
        return back()->withErrors(['profile' => 'Choose a valid property or room before uploading photos.'])->withInput();
    }

    if ($entityType === 'property') {
        $propertyExists = \App\Support\VendorPropertyCompatibilityReader::vendorOwnsProperty((int) $entityId, $vendorUserId);
        if (!$propertyExists) {
            return back()->withErrors(['profile' => 'Property not found for this vendor account.'])->withInput();
        }
    }

    if ($entityType === 'room') {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
        }

        $roomExists = DB::table('vendor_property_room_categories')
            ->where('id', (int) $entityId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();

        if (!$roomExists) {
            return back()->withErrors(['profile' => 'Room not found for this vendor account.'])->withInput();
        }
    }

    $uploadedFiles = [];
    if ($request->hasFile('photos')) {
        $candidateFiles = $request->file('photos');
        if (is_array($candidateFiles)) {
            $uploadedFiles = array_values(array_filter($candidateFiles));
        }
    }
    if ($uploadedFiles === [] && $request->hasFile('photo')) {
        $singleFile = $request->file('photo');
        if ($singleFile) {
            $uploadedFiles[] = $singleFile;
        }
    }

    if ($uploadedFiles === []) {
        return back()->withErrors(['profile' => 'Please choose at least one photo to upload.'])->withInput();
    }

    $selectedPrimaryIndex = isset($validated['primary_upload_index'])
        ? (int) $validated['primary_upload_index']
        : 0;
    $selectedPrimaryIndex = max(0, min(count($uploadedFiles) - 1, $selectedPrimaryIndex));

    // Batch upload defines one clear primary image for this entity.
    DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->update(['is_primary' => false, 'updated_at' => now()]);

    $format = vendorPortalPreferredMediaOutputFormat();
    $outputExtension = (string) ($format['extension'] ?? 'jpg');
    $outputMime = (string) ($format['mime'] ?? 'image/jpeg');

    foreach ($uploadedFiles as $fileIndex => $file) {
        $imageSize = @getimagesize($file->getPathname());
        if (!is_array($imageSize) || count($imageSize) < 2) {
            return back()->withErrors(['profile' => 'One of the uploaded files is not a valid image.'])->withInput();
        }

        $widthPx = (int) $imageSize[0];
        $heightPx = (int) $imageSize[1];
        $fileSizeKb = (int) ceil(((int) $file->getSize()) / 1024);

        $sourceImage = vendorPortalCreateImageResourceFromFile(
            (string) $file->getPathname(),
            (string) ($file->getMimeType() ?? '')
        );
        if ($sourceImage === null) {
            return back()->withErrors(['profile' => 'Unable to process one of the uploaded images. Use JPG, PNG, or WebP.'])->withInput();
        }

        $storagePrefix = 'vendor-listings/' . $vendorUserId;
        $entityToken = $entityType . '-' . ($entityId ?? 'shared');
        $baseToken = now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-' . $entityToken . '-' . $fileIndex;
        $bannerPath = $storagePrefix . '/' . $baseToken . '-banner.' . $outputExtension;
        $thumbPath = $storagePrefix . '/' . $baseToken . '-thumb.' . $outputExtension;

        $bannerImage = vendorPortalResizeImageToFill($sourceImage, $widthPx, $heightPx, 1600, 900);
        $thumbImage = vendorPortalResizeImageToFill($sourceImage, $widthPx, $heightPx, 480, 320);

        $bannerWritten = $bannerImage !== null
            ? vendorPortalWriteMediaVariant($bannerImage, $bannerPath, $outputExtension)
            : false;
        $thumbWritten = $thumbImage !== null
            ? vendorPortalWriteMediaVariant($thumbImage, $thumbPath, $outputExtension)
            : false;

        if (is_resource($sourceImage) || $sourceImage instanceof \GdImage) {
            imagedestroy($sourceImage);
        }
        if ((is_resource($bannerImage) || $bannerImage instanceof \GdImage)) {
            imagedestroy($bannerImage);
        }
        if ((is_resource($thumbImage) || $thumbImage instanceof \GdImage)) {
            imagedestroy($thumbImage);
        }

        if (!$bannerWritten || !$thumbWritten) {
            return back()->withErrors(['profile' => 'Failed to generate optimized variants for one of the images.'])->withInput();
        }

        $storedBannerSizeBytes = 0;
        try {
            $storedBannerSizeBytes = (int) (Storage::disk(vendorPortalMediaDiskName())->size($bannerPath) ?? 0);
        } catch (\Throwable $e) {
            $storedBannerSizeBytes = 0;
        }
        $storedBannerSizeKb = (int) ceil($storedBannerSizeBytes / 1024);
        $qualityGrade = $storedBannerSizeKb > 0 && $storedBannerSizeKb <= 900 ? 'A' : 'B';

        $mediaPayload = [
            'vendor_user_id' => $vendorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'file_path' => (string) $bannerPath,
            'mime_type' => $outputMime,
            'alt_text' => trim((string) ($validated['alt_text'] ?? '')),
            'is_primary' => $fileIndex === $selectedPrimaryIndex,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('vendor_listing_media', 'width_px')) {
            $mediaPayload['width_px'] = 1600;
        }
        if (Schema::hasColumn('vendor_listing_media', 'height_px')) {
            $mediaPayload['height_px'] = 900;
        }
        if (Schema::hasColumn('vendor_listing_media', 'file_size_kb')) {
            $mediaPayload['file_size_kb'] = $storedBannerSizeKb > 0 ? $storedBannerSizeKb : $fileSizeKb;
        }
        if (Schema::hasColumn('vendor_listing_media', 'quality_grade')) {
            $mediaPayload['quality_grade'] = $qualityGrade;
        }

        DB::table('vendor_listing_media')->insert($mediaPayload);
    }

    return vendorPortalListingsBackResponse(
        'Photos uploaded successfully.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/bulk-delete', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'media_ids' => ['required', 'array', 'min:1'],
        'media_ids.*' => ['required', 'integer', 'min:1'],
    ]);

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaIds = array_values(array_unique(array_map(static fn ($id) => (int) $id, $validated['media_ids'] ?? [])));
    if ($mediaIds === []) {
        return back()->withErrors(['profile' => 'Select at least one photo to remove.']);
    }

    $mediaRecords = DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->whereIn('id', $mediaIds)
        ->get();

    if ($mediaRecords->isEmpty()) {
        return back()->withErrors(['profile' => 'No selected media items were found for this vendor account.']);
    }

    foreach ($mediaRecords as $mediaRecord) {
        vendorPortalDeleteMediaRecord($mediaRecord, $vendorUserId);
    }

    return vendorPortalListingsBackResponse(
        count($mediaRecords) . ' photo(s) removed.',
        4,
        vendorPortalMediaPanelContextFromRequest($request)
    );
});

Route::post('/portal/vendor/media/{media}/primary', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$mediaRecord) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $entityType = (string) ($mediaRecord->entity_type ?? '');
    $entityId = isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->update([
            'is_primary' => false,
            'updated_at' => now(),
        ]);

    DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'is_primary' => true,
            'updated_at' => now(),
        ]);

    return vendorPortalListingsBackResponse(
        'Primary photo updated.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/{media}/update', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'alt_text' => ['required', 'string', 'max:190'],
    ]);

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $updated = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'alt_text' => trim((string) $validated['alt_text']),
            'updated_at' => now(),
        ]);

    if ($updated <= 0) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    $entityType = $mediaRecord ? (string) ($mediaRecord->entity_type ?? '') : null;
    $entityId = $mediaRecord && isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    return vendorPortalListingsBackResponse(
        'Photo details updated.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/{media}/delete', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$mediaRecord) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $entityType = (string) ($mediaRecord->entity_type ?? '');
    $entityId = isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    vendorPortalDeleteMediaRecord($mediaRecord, $vendorUserId);

    return vendorPortalListingsBackResponse(
        'Photo removed.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/rooms/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
        'room_size_sqm' => ['nullable', 'integer', 'min:5', 'max:2000'],
        'floor_info' => ['nullable', 'string', 'max:80'],
        'has_window' => ['nullable', 'in:0,1'],
        'non_smoking' => ['nullable', 'in:0,1'],
        'extra_person_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'child_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bed_type' => ['nullable', 'string', 'max:80'],
        'room_amenities' => ['nullable', 'array'],
        'room_amenities.*' => ['required', 'string', 'max:80'],
        'bathroom_type' => ['nullable', Rule::in(['ensuite', 'private_external', 'shared'])],
        'bathroom_count' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bathroom_amenities' => ['nullable', 'array'],
        'bathroom_amenities.*' => ['required', 'string', 'max:80'],
        'room_features' => ['nullable', 'array'],
        'room_features.*' => ['required', 'string', 'max:80'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price' => ['nullable', 'numeric', 'min:0'],
        'child_price' => ['nullable', 'numeric', 'min:0'],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'extra_bed_policy' => ['nullable', 'string', 'max:3000'],
    ]);

    $roomAmenities = vendorPortalNormalizedStringList($validated['room_amenities'] ?? []);
    $roomFeatures = vendorPortalNormalizedStringList($validated['room_features'] ?? []);
    $bathroomAmenities = vendorPortalNormalizedStringList($validated['bathroom_amenities'] ?? []);
    $roomAmenityTokens = array_values(array_unique(array_merge($roomAmenities, $roomFeatures)));
    $bathroomAmenityTokens = array_values(array_unique($bathroomAmenities));
    $submittedBedType = trim((string) ($validated['bed_type'] ?? ''));
    $submittedBathroomType = trim((string) ($validated['bathroom_type'] ?? ''));
    $invalidRoomAmenities = vendorPortalDisallowedOptionValues('room_amenity', $roomAmenityTokens);
    if ($invalidRoomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more room amenities are not in the allowed catalog.',
        ])->withInput();
    }
    $invalidBathroomAmenities = vendorPortalDisallowedOptionValues('bathroom_amenity', $bathroomAmenityTokens);
    if ($invalidBathroomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more bathroom amenities are not in the allowed catalog.',
        ])->withInput();
    }
    if ($submittedBedType !== '') {
        $invalidBedTypes = vendorPortalDisallowedOptionValues('room_bed_type', [$submittedBedType]);
        if ($invalidBedTypes !== []) {
            return back()->withErrors([
                'profile' => 'Selected room bed type is not in the allowed catalog.',
            ])->withInput();
        }
    }
    if ($submittedBathroomType === 'shared' && (int) ($validated['bathroom_count'] ?? 0) === 0) {
        return back()->withErrors([
            'profile' => 'Provide bathroom count when bathroom type is shared.',
        ])->withInput();
    }

    $vendorPropertyId = (int) ($validated['vendor_property_id'] ?? 0);

    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($vendorPropertyId, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Select a valid property owned by your vendor account.'])->withInput();
    }

    $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    if ($propertyCategory !== 'accommodation') {
        return back()->withErrors(['profile' => 'Room categories can only be added under accommodation listings.'])->withInput();
    }

    $resolvedRoomOnlyPrice = (float) ($validated['meal_plan_room_only_price'] ?? 0);
    $legacyBasePrice = (float) ($validated['base_price'] ?? 0);
    $resolvedBasePrice = $resolvedRoomOnlyPrice > 0 ? $resolvedRoomOnlyPrice : $legacyBasePrice;

    $insertPayload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenityTokens),
        'base_price' => $resolvedBasePrice,
        'currency' => 'MVR',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_property_room_categories', 'room_size_sqm')) {
        $insertPayload['room_size_sqm'] = isset($validated['room_size_sqm']) ? (int) $validated['room_size_sqm'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'floor_info')) {
        $floorInfo = trim((string) ($validated['floor_info'] ?? ''));
        $insertPayload['floor_info'] = $floorInfo !== '' ? $floorInfo : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'has_window')) {
        $insertPayload['has_window'] = (int) ($validated['has_window'] ?? 1) === 1;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'non_smoking')) {
        $insertPayload['non_smoking'] = (int) ($validated['non_smoking'] ?? 1) === 1;
    }

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
        $insertPayload['extra_person_capacity'] = (int) ($validated['extra_person_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
        $insertPayload['child_capacity'] = (int) ($validated['child_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
        $insertPayload['extra_person_price'] = (float) ($validated['extra_person_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
        $insertPayload['child_price'] = (float) ($validated['child_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price')) {
        $insertPayload['meal_plan_room_only_price'] = $resolvedRoomOnlyPrice > 0 ? $resolvedRoomOnlyPrice : $resolvedBasePrice;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price')) {
        $insertPayload['meal_plan_bb_price'] = (float) ($validated['meal_plan_bb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price')) {
        $insertPayload['meal_plan_hb_price'] = (float) ($validated['meal_plan_hb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price')) {
        $insertPayload['meal_plan_fb_price'] = (float) ($validated['meal_plan_fb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price')) {
        $insertPayload['meal_plan_ai_price'] = (float) ($validated['meal_plan_ai_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $insertPayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $insertPayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $insertPayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_policy')) {
        $childPolicy = trim((string) ($validated['child_policy'] ?? ''));
        $insertPayload['child_policy'] = $childPolicy !== '' ? $childPolicy : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_bed_policy')) {
        $extraBedPolicy = trim((string) ($validated['extra_bed_policy'] ?? ''));
        $insertPayload['extra_bed_policy'] = $extraBedPolicy !== '' ? $extraBedPolicy : null;
    }

    DB::table('vendor_property_room_categories')->insert($insertPayload);

    return vendorPortalListingsBackResponse('Room category added.', 3);
});

Route::post('/portal/vendor/rooms/{room}/update', function (Request $request, int $room) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $roomRecord = DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$roomRecord) {
        return back()->withErrors(['profile' => 'Room category not found for this vendor account.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
        'room_size_sqm' => ['nullable', 'integer', 'min:5', 'max:2000'],
        'floor_info' => ['nullable', 'string', 'max:80'],
        'has_window' => ['nullable', 'in:0,1'],
        'non_smoking' => ['nullable', 'in:0,1'],
        'extra_person_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'child_capacity' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bed_type' => ['nullable', 'string', 'max:80'],
        'room_amenities' => ['nullable', 'array'],
        'room_amenities.*' => ['required', 'string', 'max:80'],
        'bathroom_type' => ['nullable', Rule::in(['ensuite', 'private_external', 'shared'])],
        'bathroom_count' => ['nullable', 'integer', 'min:0', 'max:20'],
        'bathroom_amenities' => ['nullable', 'array'],
        'bathroom_amenities.*' => ['required', 'string', 'max:80'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_room_only_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_bb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_hb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_fb_price' => ['nullable', 'numeric', 'min:0'],
        'meal_plan_ai_price' => ['nullable', 'numeric', 'min:0'],
        'extra_person_price' => ['nullable', 'numeric', 'min:0'],
        'child_price' => ['nullable', 'numeric', 'min:0'],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'extra_bed_policy' => ['nullable', 'string', 'max:3000'],
    ]);

    $roomAmenities = vendorPortalNormalizedStringList($validated['room_amenities'] ?? []);
    $bathroomAmenities = vendorPortalNormalizedStringList($validated['bathroom_amenities'] ?? []);
    $bathroomAmenityTokens = array_values(array_unique($bathroomAmenities));
    $submittedBedType = trim((string) ($validated['bed_type'] ?? ''));
    $submittedBathroomType = trim((string) ($validated['bathroom_type'] ?? ''));
    $invalidRoomAmenities = vendorPortalDisallowedOptionValues('room_amenity', $roomAmenities);
    if ($invalidRoomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more room amenities are not in the allowed catalog.',
        ])->withInput();
    }
    $invalidBathroomAmenities = vendorPortalDisallowedOptionValues('bathroom_amenity', $bathroomAmenityTokens);
    if ($invalidBathroomAmenities !== []) {
        return back()->withErrors([
            'profile' => 'One or more bathroom amenities are not in the allowed catalog.',
        ])->withInput();
    }
    if ($submittedBedType !== '') {
        $invalidBedTypes = vendorPortalDisallowedOptionValues('room_bed_type', [$submittedBedType]);
        if ($invalidBedTypes !== []) {
            return back()->withErrors([
                'profile' => 'Selected room bed type is not in the allowed catalog.',
            ])->withInput();
        }
    }
    if ($submittedBathroomType === 'shared' && (int) ($validated['bathroom_count'] ?? 0) === 0) {
        return back()->withErrors([
            'profile' => 'Provide bathroom count when bathroom type is shared.',
        ])->withInput();
    }

    if (isset($roomRecord->vendor_property_id)) {
        $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $roomRecord->vendor_property_id, $vendorUserId);
        $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
        if ($propertyCategory !== 'accommodation') {
            return back()->withErrors(['profile' => 'Only rooms under accommodation listings can be updated here.'])->withInput();
        }
    }

    $resolvedRoomOnlyPrice = (float) ($validated['meal_plan_room_only_price'] ?? 0);
    $legacyBasePrice = (float) ($validated['base_price'] ?? 0);
    $resolvedBasePrice = $resolvedRoomOnlyPrice > 0 ? $resolvedRoomOnlyPrice : $legacyBasePrice;

    $updatePayload = [
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenities),
        'base_price' => $resolvedBasePrice,
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_property_room_categories', 'room_size_sqm')) {
        $updatePayload['room_size_sqm'] = isset($validated['room_size_sqm']) ? (int) $validated['room_size_sqm'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'floor_info')) {
        $floorInfo = trim((string) ($validated['floor_info'] ?? ''));
        $updatePayload['floor_info'] = $floorInfo !== '' ? $floorInfo : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'has_window')) {
        $updatePayload['has_window'] = (int) ($validated['has_window'] ?? 1) === 1;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'non_smoking')) {
        $updatePayload['non_smoking'] = (int) ($validated['non_smoking'] ?? 1) === 1;
    }

    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
        $updatePayload['extra_person_capacity'] = (int) ($validated['extra_person_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
        $updatePayload['child_capacity'] = (int) ($validated['child_capacity'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
        $updatePayload['extra_person_price'] = (float) ($validated['extra_person_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
        $updatePayload['child_price'] = (float) ($validated['child_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price')) {
        $updatePayload['meal_plan_room_only_price'] = $resolvedRoomOnlyPrice > 0 ? $resolvedRoomOnlyPrice : $resolvedBasePrice;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price')) {
        $updatePayload['meal_plan_bb_price'] = (float) ($validated['meal_plan_bb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price')) {
        $updatePayload['meal_plan_hb_price'] = (float) ($validated['meal_plan_hb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price')) {
        $updatePayload['meal_plan_fb_price'] = (float) ($validated['meal_plan_fb_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price')) {
        $updatePayload['meal_plan_ai_price'] = (float) ($validated['meal_plan_ai_price'] ?? 0);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $updatePayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $updatePayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $updatePayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'child_policy')) {
        $childPolicy = trim((string) ($validated['child_policy'] ?? ''));
        $updatePayload['child_policy'] = $childPolicy !== '' ? $childPolicy : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'extra_bed_policy')) {
        $extraBedPolicy = trim((string) ($validated['extra_bed_policy'] ?? ''));
        $updatePayload['extra_bed_policy'] = $extraBedPolicy !== '' ? $extraBedPolicy : null;
    }

    DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->update($updatePayload);

    return vendorPortalListingsBackResponse('Room category updated.', 3);
});

Route::post('/portal/vendor/rooms/{room}/delete', function (int $room) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    DB::table('vendor_property_room_categories')
        ->where('id', $room)
        ->where('vendor_user_id', $vendorUserId)
        ->delete();

    return vendorPortalListingsBackResponse('Room category removed.', 3);
});

Route::post('/portal/vendor/properties/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || !vendorPortalCanManageListings($vendorUser)) {
        return back()->withErrors([
            'profile' => 'Listings are locked until admin verification is approved. Complete My Account compliance details and wait for approval.',
        ])->withInput();
    }
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', 'string', 'max:80'],
        'property_type' => ['nullable', Rule::in(['property', 'service'])],
        'location' => ['nullable', 'string', 'max:190'],
        'location_country' => ['nullable', 'string', 'max:90'],
        'location_state' => ['nullable', 'string', 'max:120'],
        'location_city' => ['nullable', 'string', 'max:120'],
        'location_ward' => ['nullable', 'string', 'max:120'],
        'address_line' => ['nullable', 'string', 'max:255'],
        'building_house_lot' => ['nullable', 'string', 'max:160'],
        'street' => ['nullable', 'string', 'max:160'],
        'post_code' => ['nullable', 'string', 'max:20'],
        'property_contact_name' => ['nullable', 'string', 'max:120'],
        'property_contact_number' => ['nullable', 'string', 'max:60'],
        'property_contact_email' => ['nullable', 'email:rfc', 'max:190'],
        'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        'map_place_id' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'area_value' => ['nullable', 'numeric', 'min:5', 'max:100000'],
        'area_unit' => ['nullable', Rule::in(['sqm', 'sqft'])],
        'bedroom_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'capacity_value' => ['nullable', 'integer', 'min:1', 'max:20000'],
        'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
        'transport_mode' => ['nullable', 'string', 'max:80'],
        'transport_trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
        'transport_pricing_model' => ['nullable', Rule::in(['per_trip', 'hourly', 'daily'])],
        'vehicle_name' => ['nullable', 'string', 'max:120'],
        'registration_plate' => ['nullable', 'string', 'max:80'],
        'contact_name' => ['nullable', 'string', 'max:120'],
        'contact_number' => ['nullable', 'string', 'max:60'],
        'pickup_location' => ['nullable', 'string', 'max:190'],
        'dropoff_location' => ['nullable', 'string', 'max:190'],
        'transport_departure_state' => ['nullable', 'string', 'max:120'],
        'transport_departure_city' => ['nullable', 'string', 'max:120'],
        'transport_arrival_state' => ['nullable', 'string', 'max:120'],
        'transport_arrival_city' => ['nullable', 'string', 'max:120'],
        'departure_area_port_jetty' => ['nullable', 'string', 'max:190'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'departure_date' => ['nullable', 'date'],
        'departure_time' => ['nullable', 'date_format:H:i'],
        'reporting_time' => ['nullable', 'date_format:H:i'],
        'reporting_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'trip_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'schedule_start_time' => ['nullable', 'date_format:H:i'],
        'schedule_end_time' => ['nullable', 'date_format:H:i'],
        'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        'boarding_instructions' => ['nullable', 'string', 'max:1000'],
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
        'excursion_min_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_max_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_min_age' => ['nullable', 'integer', 'min:0', 'max:99'],
        'meeting_point' => ['nullable', 'string', 'max:255'],
        'inclusions' => ['nullable', 'string', 'max:2000'],
        'exclusions' => ['nullable', 'string', 'max:1000'],
        'safety_waiver_required' => ['nullable', Rule::in(['yes', 'no'])],
        'equipment_rental_available' => ['nullable', Rule::in(['yes', 'no'])],
        'equipment_included' => ['nullable', 'array'],
        'equipment_included.*' => ['required', 'string', 'max:80'],
        'weather_cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'workspace_type' => ['nullable', Rule::in(['shared', 'private', 'cabin'])],
        'internet_speed_mbps' => ['nullable', 'numeric', 'min:1', 'max:10000'],
        'workspace_amenities_free' => ['nullable', 'array'],
        'workspace_amenities_free.*' => ['required', 'string', 'max:80'],
        'workspace_amenities_paid' => ['nullable', 'array'],
        'workspace_amenities_paid.*' => ['required', 'string', 'max:80'],
        'transfer_options' => ['nullable', 'array'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_adult' => ['nullable', 'array'],
        'transfer_rates_local_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_child' => ['nullable', 'array'],
        'transfer_rates_local_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_adult' => ['nullable', 'array'],
        'transfer_rates_foreign_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_child' => ['nullable', 'array'],
        'transfer_rates_foreign_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_local' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_foreign' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'vendor_tax_rates' => ['nullable', 'array'],
        'vendor_tax_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'day_visit_start_time' => ['nullable', 'date_format:H:i'],
        'day_visit_end_time' => ['nullable', 'date_format:H:i'],
        'included_access' => ['nullable', 'string', 'max:2000'],
        'cuisine_type' => ['nullable', 'string', 'max:120'],
        'meal_service' => ['nullable', 'string', 'max:80'],
        'vehicle_type' => ['nullable', 'string', 'max:80'],
        'transmission_type' => ['nullable', Rule::in(['automatic', 'manual'])],
        'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
        'safety_certifications' => ['nullable', 'string', 'max:2000'],
        'accessibility_features' => ['nullable', 'string', 'max:2000'],
        'property_amenities' => ['nullable', 'array'],
        'property_amenities.*' => ['required', 'string', 'max:80'],
        'property_features' => ['nullable', 'array'],
        'property_features.*' => ['required', 'string', 'max:80'],
        'check_in_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'early_check_in_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'late_check_out_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'early_check_in_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'late_check_out_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) $validated['listing_category']);
    if ($canonicalListingCategory === null) {
        return back()->withErrors(['profile' => 'Invalid listing category selected.'])->withInput();
    }

    $approvedCategories = vendorPortalApprovedCategories($vendorUser);
    if (!in_array($canonicalListingCategory, $approvedCategories, true)) {
        return back()->withErrors([
            'profile' => 'This category is not yet approved for your vendor account. Request admin category approval before creating listings.',
        ])->withInput();
    }

    $submittedTransportMode = trim((string) ($validated['transport_mode'] ?? ''));
    if ($canonicalListingCategory === 'transport' && $submittedTransportMode !== '') {
        $invalidTransportModes = vendorPortalDisallowedOptionValues('transport_mode', [$submittedTransportMode]);
        if ($invalidTransportModes !== []) {
            return back()->withErrors(['profile' => 'Selected transport mode is not in the allowed catalog.'])->withInput();
        }
    }

    $propertyAmenities = vendorPortalNormalizedStringList($validated['property_amenities'] ?? []);
    $propertyFeatures = vendorPortalNormalizedStringList($validated['property_features'] ?? []);
    $invalidPropertyAmenities = vendorPortalDisallowedOptionValuesFromTypes(['property_amenity', 'accommodation_facility'], $propertyAmenities);
    if ($invalidPropertyAmenities !== []) {
        return back()->withErrors(['profile' => 'One or more accommodation facilities are not in the allowed catalog.'])->withInput();
    }
    $invalidPropertyFeatures = vendorPortalDisallowedOptionValues('property_feature', $propertyFeatures);
    if ($invalidPropertyFeatures !== []) {
        return back()->withErrors(['profile' => 'One or more property features are not in the allowed catalog.'])->withInput();
    }

    $submittedMealService = trim((string) ($validated['meal_service'] ?? ''));
    if ($canonicalListingCategory === 'restaurant' && $submittedMealService !== '') {
        $invalidMealServices = vendorPortalDisallowedOptionValues('restaurant_meal_service', [$submittedMealService]);
        if ($invalidMealServices !== []) {
            return back()->withErrors(['profile' => 'Selected restaurant meal service is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedExcursionType = trim((string) ($validated['excursion_type'] ?? ''));
    if (in_array($canonicalListingCategory, ['excursion', 'water_sports'], true) && $submittedExcursionType !== '') {
        $invalidExcursionTypes = vendorPortalDisallowedOptionValues('excursion_type', [$submittedExcursionType]);
        if ($invalidExcursionTypes !== []) {
            return back()->withErrors(['profile' => 'Selected excursion type is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedVehicleType = trim((string) ($validated['vehicle_type'] ?? ''));
    if ($canonicalListingCategory === 'vehicle_rental' && $submittedVehicleType !== '') {
        $invalidVehicleTypes = vendorPortalDisallowedOptionValues('vehicle_rental_type', [$submittedVehicleType]);
        if ($invalidVehicleTypes !== []) {
            return back()->withErrors(['profile' => 'Selected vehicle rental type is not in the allowed land/marine catalog.'])->withInput();
        }
    }

    $resolvedPropertyType = vendorPortalPropertyTypeForCategory($canonicalListingCategory);

    $propertyDetails = vendorPortalBuildPropertyDetails($validated, $canonicalListingCategory);
    $propertyDetailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $propertyDetails);
    if (!empty($propertyDetailErrors)) {
        return back()->withErrors(['profile' => implode(' ', $propertyDetailErrors)])->withInput();
    }

    $locationCountry = trim((string) ($validated['location_country'] ?? ''));
    $locationState = trim((string) ($validated['location_state'] ?? ''));
    $locationCity = trim((string) ($validated['location_city'] ?? ''));
    $locationWard = trim((string) ($validated['location_ward'] ?? ''));
    $addressLine = trim((string) ($validated['address_line'] ?? ''));
    $buildingHouseLot = trim((string) ($validated['building_house_lot'] ?? ''));
    $street = trim((string) ($validated['street'] ?? ''));
    $locationParts = array_values(array_filter([$street, $locationWard, $locationCity, $locationState, $locationCountry], static fn (string $item): bool => $item !== ''));
    $locationFromStructuredFields = implode(', ', $locationParts);
    $resolvedLocation = $locationFromStructuredFields !== '' ? $locationFromStructuredFields : trim((string) ($validated['location'] ?? ''));

    if ($buildingHouseLot !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($buildingHouseLot . ', ' . $resolvedLocation) : $buildingHouseLot;
    }

    if ($addressLine !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($addressLine . ' - ' . $resolvedLocation) : $addressLine;
    }

    if ($canonicalListingCategory === 'transport') {
        $pickup = trim((string) ($propertyDetails['pickup_location'] ?? ''));
        $dropoff = trim((string) ($propertyDetails['dropoff_location'] ?? ''));
        $resolvedLocation = ($pickup !== '' && $dropoff !== '')
            ? ($pickup . ' -> ' . $dropoff)
            : ($pickup !== '' ? $pickup : ($dropoff !== '' ? $dropoff : 'Route details pending'));
    }

    $selectedAmenityTokens = array_values(array_unique(array_merge($propertyAmenities, $propertyFeatures)));
    $categoryCapacity = isset($propertyDetails['capacity_value']) && is_numeric($propertyDetails['capacity_value'])
        ? (int) $propertyDetails['capacity_value']
        : null;
    $normalizedMaxGuests = $canonicalListingCategory === 'accommodation'
        ? 0
        : max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? 0)));

    $resolvedBasePrice = $canonicalListingCategory === 'accommodation'
        ? 0
        : (float) ($validated['base_price'] ?? 0);

    if ($canonicalListingCategory === 'transport') {
        $normalizedMaxGuests = max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? 0)));
    }

    DB::transaction(function () use ($canonicalListingCategory, $vendorUserId, $validated, $resolvedLocation, $normalizedMaxGuests, $propertyDetails): void {
        vendorPortalCreateCategoryListingRecord(
            $canonicalListingCategory,
            $vendorUserId,
            trim((string) $validated['name']),
            $resolvedLocation,
            trim((string) ($validated['description'] ?? '')),
            $normalizedMaxGuests,
            $propertyDetails,
            'draft',
            $canonicalListingCategory === 'accommodation' ? 0 : (float) ($validated['base_price'] ?? 0),
            'MVR'
        );
    });

    return vendorPortalListingsBackResponse('Property/service listing added.', 2, [
        'portal_listing_mode' => 'manage',
        'portal_listing_category' => (string) $canonicalListingCategory,
    ]);
});

Route::post('/portal/vendor/properties/{property}/update', function (Request $request, int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($property, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Property not found for this vendor account.']);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'location' => ['nullable', 'string', 'max:190'],
        'location_country' => ['nullable', 'string', 'max:90'],
        'location_state' => ['nullable', 'string', 'max:120'],
        'location_city' => ['nullable', 'string', 'max:120'],
        'location_ward' => ['nullable', 'string', 'max:120'],
        'address_line' => ['nullable', 'string', 'max:255'],
        'building_house_lot' => ['nullable', 'string', 'max:160'],
        'street' => ['nullable', 'string', 'max:160'],
        'post_code' => ['nullable', 'string', 'max:20'],
        'property_contact_name' => ['nullable', 'string', 'max:120'],
        'property_contact_number' => ['nullable', 'string', 'max:60'],
        'property_contact_email' => ['nullable', 'email:rfc', 'max:190'],
        'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        'map_place_id' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:0', 'max:10000'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'area_value' => ['nullable', 'numeric', 'min:5', 'max:100000'],
        'area_unit' => ['nullable', Rule::in(['sqm', 'sqft'])],
        'bedroom_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'capacity_value' => ['nullable', 'integer', 'min:1', 'max:20000'],
        'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
        'transport_mode' => ['nullable', 'string', 'max:80'],
        'transport_trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
        'transport_pricing_model' => ['nullable', Rule::in(['per_trip', 'hourly', 'daily'])],
        'vehicle_name' => ['nullable', 'string', 'max:120'],
        'registration_plate' => ['nullable', 'string', 'max:80'],
        'contact_name' => ['nullable', 'string', 'max:120'],
        'contact_number' => ['nullable', 'string', 'max:60'],
        'pickup_location' => ['nullable', 'string', 'max:190'],
        'dropoff_location' => ['nullable', 'string', 'max:190'],
        'transport_departure_state' => ['nullable', 'string', 'max:120'],
        'transport_departure_city' => ['nullable', 'string', 'max:120'],
        'transport_arrival_state' => ['nullable', 'string', 'max:120'],
        'transport_arrival_city' => ['nullable', 'string', 'max:120'],
        'departure_area_port_jetty' => ['nullable', 'string', 'max:190'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'departure_date' => ['nullable', 'date'],
        'departure_time' => ['nullable', 'date_format:H:i'],
        'reporting_time' => ['nullable', 'date_format:H:i'],
        'reporting_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'trip_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        'schedule_start_time' => ['nullable', 'date_format:H:i'],
        'schedule_end_time' => ['nullable', 'date_format:H:i'],
        'booking_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        'boarding_instructions' => ['nullable', 'string', 'max:1000'],
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
        'excursion_min_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_max_pax' => ['nullable', 'integer', 'min:1', 'max:1000'],
        'excursion_min_age' => ['nullable', 'integer', 'min:0', 'max:99'],
        'meeting_point' => ['nullable', 'string', 'max:255'],
        'inclusions' => ['nullable', 'string', 'max:2000'],
        'exclusions' => ['nullable', 'string', 'max:1000'],
        'safety_waiver_required' => ['nullable', Rule::in(['yes', 'no'])],
        'equipment_rental_available' => ['nullable', Rule::in(['yes', 'no'])],
        'equipment_included' => ['nullable', 'array'],
        'equipment_included.*' => ['required', 'string', 'max:80'],
        'weather_cancellation_policy' => ['nullable', 'string', 'max:2000'],
        'workspace_type' => ['nullable', Rule::in(['shared', 'private', 'cabin'])],
        'internet_speed_mbps' => ['nullable', 'numeric', 'min:1', 'max:10000'],
        'workspace_amenities_free' => ['nullable', 'array'],
        'workspace_amenities_free.*' => ['required', 'string', 'max:80'],
        'workspace_amenities_paid' => ['nullable', 'array'],
        'workspace_amenities_paid.*' => ['required', 'string', 'max:80'],
        'transfer_options' => ['nullable', 'array'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_adult' => ['nullable', 'array'],
        'transfer_rates_local_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_local_child' => ['nullable', 'array'],
        'transfer_rates_local_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_adult' => ['nullable', 'array'],
        'transfer_rates_foreign_adult.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_rates_foreign_child' => ['nullable', 'array'],
        'transfer_rates_foreign_child.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_local' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'transfer_base_foreign' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'vendor_tax_rates' => ['nullable', 'array'],
        'vendor_tax_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'day_visit_start_time' => ['nullable', 'date_format:H:i'],
        'day_visit_end_time' => ['nullable', 'date_format:H:i'],
        'included_access' => ['nullable', 'string', 'max:2000'],
        'cuisine_type' => ['nullable', 'string', 'max:120'],
        'meal_service' => ['nullable', 'string', 'max:80'],
        'vehicle_type' => ['nullable', 'string', 'max:80'],
        'transmission_type' => ['nullable', Rule::in(['automatic', 'manual'])],
        'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
        'property_amenities' => ['nullable', 'array'],
        'property_amenities.*' => ['required', 'string', 'max:80'],
        'property_features' => ['nullable', 'array'],
        'property_features.*' => ['required', 'string', 'max:80'],
        'check_in_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
        'early_check_in_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'late_check_out_allowed' => ['nullable', Rule::in(['yes', 'no', 'subject_to_availability'])],
        'child_policy' => ['nullable', 'string', 'max:3000'],
        'early_check_in_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'late_check_out_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
    ]);
    $resolvedStatus = (string) ($validated['status'] ?? $propertyRecord->status ?? 'active');
    if (!in_array($resolvedStatus, ['active', 'inactive'], true)) {
        $resolvedStatus = 'active';
    }


    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    $existingDetails = [];
    if (isset($propertyRecord->listing_details) && is_string($propertyRecord->listing_details) && trim($propertyRecord->listing_details) !== '') {
        $decodedDetails = json_decode((string) $propertyRecord->listing_details, true);
        if (is_array($decodedDetails)) {
            $existingDetails = $decodedDetails;
        }
    }

    if ($canonicalListingCategory === null && isset($existingDetails['listing_category'])) {
        $canonicalListingCategory = vendorPortalCanonicalCategory((string) $existingDetails['listing_category']);
    }

    $submittedTransportMode = trim((string) ($validated['transport_mode'] ?? ''));
    if ($canonicalListingCategory === 'transport' && $submittedTransportMode !== '') {
        $invalidTransportModes = vendorPortalDisallowedOptionValues('transport_mode', [$submittedTransportMode]);
        if ($invalidTransportModes !== []) {
            return back()->withErrors(['profile' => 'Selected transport mode is not in the allowed catalog.'])->withInput();
        }
    }

    $propertyAmenities = vendorPortalNormalizedStringList($validated['property_amenities'] ?? []);
    $propertyFeatures = vendorPortalNormalizedStringList($validated['property_features'] ?? []);
    $invalidPropertyAmenities = vendorPortalDisallowedOptionValuesFromTypes(['property_amenity', 'accommodation_facility'], $propertyAmenities);
    if ($invalidPropertyAmenities !== []) {
        return back()->withErrors(['profile' => 'One or more accommodation facilities are not in the allowed catalog.'])->withInput();
    }
    $invalidPropertyFeatures = vendorPortalDisallowedOptionValues('property_feature', $propertyFeatures);
    if ($invalidPropertyFeatures !== []) {
        return back()->withErrors(['profile' => 'One or more property features are not in the allowed catalog.'])->withInput();
    }

    $submittedMealService = trim((string) ($validated['meal_service'] ?? ''));
    if ($canonicalListingCategory === 'restaurant' && $submittedMealService !== '') {
        $invalidMealServices = vendorPortalDisallowedOptionValues('restaurant_meal_service', [$submittedMealService]);
        if ($invalidMealServices !== []) {
            return back()->withErrors(['profile' => 'Selected restaurant meal service is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedExcursionType = trim((string) ($validated['excursion_type'] ?? ''));
    if (in_array($canonicalListingCategory, ['excursion', 'water_sports'], true) && $submittedExcursionType !== '') {
        $invalidExcursionTypes = vendorPortalDisallowedOptionValues('excursion_type', [$submittedExcursionType]);
        if ($invalidExcursionTypes !== []) {
            return back()->withErrors(['profile' => 'Selected excursion type is not in the allowed catalog.'])->withInput();
        }
    }

    $submittedVehicleType = trim((string) ($validated['vehicle_type'] ?? ''));
    if ($canonicalListingCategory === 'vehicle_rental' && $submittedVehicleType !== '') {
        $invalidVehicleTypes = vendorPortalDisallowedOptionValues('vehicle_rental_type', [$submittedVehicleType]);
        if ($invalidVehicleTypes !== []) {
            return back()->withErrors(['profile' => 'Selected vehicle rental type is not in the allowed land/marine catalog.'])->withInput();
        }
    }

    if ($canonicalListingCategory !== null) {
        $mergedDetailsInput = array_merge($existingDetails, $validated);
        $mergedDetails = vendorPortalBuildPropertyDetails($mergedDetailsInput, $canonicalListingCategory);
        $detailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $mergedDetails);
        if (!empty($detailErrors)) {
            return back()->withErrors(['profile' => implode(' ', $detailErrors)])->withInput();
        }
        $existingDetails = $mergedDetails;
    }

    $categoryCapacity = isset($existingDetails['capacity_value']) && is_numeric($existingDetails['capacity_value'])
        ? (int) $existingDetails['capacity_value']
        : null;

    $normalizedMaxGuests = $canonicalListingCategory === 'accommodation'
        ? 0
        : max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? ($propertyRecord->max_guests ?? 0))));

    $resolvedBasePrice = $canonicalListingCategory === 'accommodation'
        ? 0
        : (float) ($validated['base_price'] ?? ($propertyRecord->base_price ?? 0));

    $locationCountry = trim((string) ($validated['location_country'] ?? ''));
    $locationState = trim((string) ($validated['location_state'] ?? ''));
    $locationCity = trim((string) ($validated['location_city'] ?? ''));
    $locationWard = trim((string) ($validated['location_ward'] ?? ''));
    $addressLine = trim((string) ($validated['address_line'] ?? ''));
    $buildingHouseLot = trim((string) ($validated['building_house_lot'] ?? ''));
    $street = trim((string) ($validated['street'] ?? ''));
    $locationParts = array_values(array_filter([$street, $locationWard, $locationCity, $locationState, $locationCountry], static fn (string $item): bool => $item !== ''));
    $locationFromStructuredFields = implode(', ', $locationParts);
    $resolvedLocation = $locationFromStructuredFields !== '' ? $locationFromStructuredFields : trim((string) ($validated['location'] ?? ''));

    if ($buildingHouseLot !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($buildingHouseLot . ', ' . $resolvedLocation) : $buildingHouseLot;
    }

    if ($addressLine !== '') {
        $resolvedLocation = $resolvedLocation !== '' ? ($addressLine . ' - ' . $resolvedLocation) : $addressLine;
    }

    if ($canonicalListingCategory === 'transport') {
        $pickup = trim((string) ($existingDetails['pickup_location'] ?? ''));
        $dropoff = trim((string) ($existingDetails['dropoff_location'] ?? ''));
        $resolvedLocation = ($pickup !== '' && $dropoff !== '')
            ? ($pickup . ' -> ' . $dropoff)
            : ($pickup !== '' ? $pickup : ($dropoff !== '' ? $dropoff : 'Route details pending'));
    }

    $selectedAmenityTokens = array_values(array_unique(array_merge($propertyAmenities, $propertyFeatures)));

    if ($canonicalListingCategory === 'transport') {
        $normalizedMaxGuests = max(0, (int) ($categoryCapacity ?? ($validated['max_guests'] ?? ($propertyRecord->max_guests ?? 0))));
    }

    $updatePayload = [
        'name' => trim((string) $validated['name']),
        'location' => $resolvedLocation,
        'description' => trim((string) ($validated['description'] ?? '')),
        'base_price' => $resolvedBasePrice,
        'max_guests' => $normalizedMaxGuests,
        'status' => $resolvedStatus,
        'updated_at' => now(),
    ];

    DB::transaction(function () use ($property, $vendorUserId, $updatePayload, $canonicalListingCategory, $resolvedLocation, $resolvedBasePrice, $normalizedMaxGuests, $existingDetails, $validated, $resolvedStatus): void {
        if ($canonicalListingCategory !== null) {
            vendorPortalSyncCategoryListingRecord(
                $canonicalListingCategory,
                $property,
                $vendorUserId,
                trim((string) ($validated['name'] ?? '')),
                $resolvedStatus,
                $resolvedLocation,
                trim((string) ($validated['description'] ?? '')),
                $resolvedBasePrice,
                'MVR',
                $normalizedMaxGuests,
                $existingDetails
            );

            if ($canonicalListingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
                vendorPortalSyncAccommodationStructuredData($property, $vendorUserId, $existingDetails);
            }
        }
    });

    return vendorPortalListingsBackResponse('Property listing updated.', 2, [
        'portal_listing_mode' => 'manage',
        'portal_listing_category' => (string) ($canonicalListingCategory ?? ''),
    ]);
});

Route::post('/portal/vendor/properties/{property}/delete', function (int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($property, $vendorUserId);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Property not found for this vendor account.']);
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));

    DB::transaction(function () use ($property, $canonicalListingCategory): void {
        if ($canonicalListingCategory !== null) {
            vendorPortalDeleteCategoryListingRecord($canonicalListingCategory, $property);
        }
    });

    return vendorPortalListingsBackResponse('Property listing removed.', 1);
});

Route::post('/portal/vendor/properties/{property}/submit-for-review', function (int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    // Load listing from dedicated table first; fall back to vendor_properties.
    $listing = \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($property);
    if (!$listing || (int) ($listing->vendor_user_id ?? 0) !== $vendorUserId) {
        return back()->withErrors(['profile' => 'Listing not found.']);
    }

    $currentStatus = strtolower(trim((string) ($listing->listing_moderation_status ?? 'draft')));

    if (!in_array($currentStatus, ['draft', 'rejected'], true)) {
        return back()->with('portal_notice', 'This listing cannot be submitted for review in its current state. Status: ' . strtoupper($currentStatus));
    }

    $listingDetails = [];
    $rawDetails = $listing->listing_details ?? ($listing->details ?? null);
    if (is_string($rawDetails) && trim((string) $rawDetails) !== '') {
        $decodedDetails = json_decode((string) $rawDetails, true);
        if (is_array($decodedDetails)) {
            $listingDetails = $decodedDetails;
        }
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($listing->listing_category ?? ''));
    if ($canonicalListingCategory !== null) {
        $detailErrors = vendorPortalValidatePropertyDetails($canonicalListingCategory, $listingDetails);
        if (!empty($detailErrors)) {
            return back()->withErrors([
                'profile' => implode(' ', $detailErrors),
            ]);
        }
    }

    \App\Support\VendorPropertyCompatibilityReader::submitForReview($property, $canonicalListingCategory);

    return vendorPortalListingsBackResponse('Listing submitted for admin review. You will be notified once it has been approved.', 1);
});


Route::post('/portal/vendor/services/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_services')) {
        return back()->withErrors(['profile' => 'Vendor services table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'listing_category' => ['required', 'string', 'max:80'],
        'category' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:3000'],
        'price' => ['required', 'numeric', 'min:0'],
        'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
        'property_id' => ['nullable', 'integer'],
        'measurement_system' => ['nullable', Rule::in(['metric', 'imperial'])],
        'lead_time_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
        'min_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_booking_size' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'quantity_unit' => ['nullable', Rule::in(['seat', 'room', 'desk', 'vehicle', 'ticket', 'table', 'pass'])],
        'compliance_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) $validated['listing_category']);
    if ($canonicalListingCategory === null) {
        return back()->withErrors(['profile' => 'Invalid service listing category selected.'])->withInput();
    }

    $allowedForUser = vendorPortalSelectedCategories($vendorUser);
    if (!in_array($canonicalListingCategory, $allowedForUser, true)) {
        return back()->withErrors(['profile' => 'Select category in onboarding before creating this service.']);
    }

    $serviceDetails = vendorPortalBuildServiceDetails($validated, $canonicalListingCategory);
    $serviceDetailErrors = vendorPortalValidateServiceDetails($serviceDetails);
    if (!empty($serviceDetailErrors)) {
        return back()->withErrors(['profile' => implode(' ', $serviceDetailErrors)])->withInput();
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['property_id'] ?? null) ? (int) $validated['property_id'] : null,
        'name' => trim((string) $validated['name']),
        'category' => trim((string) $validated['category']),
        'description' => trim((string) ($validated['description'] ?? '')),
        'duration_minutes' => (int) ($validated['duration_minutes'] ?? 0),
        'price' => (float) $validated['price'],
        'currency' => 'MVR',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_services', 'listing_category')) {
        $payload['listing_category'] = $canonicalListingCategory;
    }
    if (Schema::hasColumn('vendor_services', 'service_details')) {
        $payload['service_details'] = empty($serviceDetails) ? null : json_encode($serviceDetails);
    }

    DB::table('vendor_services')->insert($payload);

    return back()->with('portal_notice', 'Service added successfully.');
});

Route::post('/portal/vendor/availability/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_availability_slots')) {
        return back()->withErrors(['profile' => 'Vendor availability table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'slot_date' => ['nullable', 'date'],
        'apply_range_from' => ['nullable', 'date'],
        'apply_range_to' => ['nullable', 'date', 'after_or_equal:apply_range_from'],
        'inventory' => ['required', 'integer', 'min:0', 'max:100000'],
        'is_closed' => ['nullable', 'boolean'],
        'listing_category' => ['nullable', 'string', 'max:80'],
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'vendor_service_id' => ['nullable', 'integer', 'min:1'],
        'vendor_room_category_id' => ['nullable', 'integer', 'min:1'],
        'route_name' => ['nullable', 'string', 'max:120'],
        'schedule_profile' => ['nullable', Rule::in(['one_off', 'daily', 'weekly_6', 'weekly_3', 'weekly_custom'])],
        'service_days' => ['nullable', 'array'],
        'service_days.*' => ['integer', 'between:0,6'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $singleDate = filled($validated['slot_date'] ?? null) ? (string) $validated['slot_date'] : '';
    $rangeFrom = filled($validated['apply_range_from'] ?? null) ? (string) $validated['apply_range_from'] : '';
    $rangeTo = filled($validated['apply_range_to'] ?? null) ? (string) $validated['apply_range_to'] : '';

    if ($singleDate === '' && ($rangeFrom === '' || $rangeTo === '')) {
        return back()->withErrors([
            'profile' => 'Provide either a single date or a date range for recurring schedule updates.',
        ])->withInput();
    }

    $scheduleProfile = (string) ($validated['schedule_profile'] ?? 'one_off');
    $submittedServiceDays = collect($validated['service_days'] ?? [])
        ->map(static fn ($day) => (int) $day)
        ->filter(static fn (int $day): bool => $day >= 0 && $day <= 6)
        ->unique()
        ->sort()
        ->values()
        ->all();

    $effectiveServiceDays = $submittedServiceDays;
    if ($scheduleProfile === 'weekly_6') {
        // Default six-day pattern: Monday-Saturday.
        $effectiveServiceDays = [1, 2, 3, 4, 5, 6];
    } elseif ($scheduleProfile === 'weekly_3' && $effectiveServiceDays === []) {
        // Default three-day pattern for marine routes if none selected.
        $effectiveServiceDays = [1, 3, 5];
    }

    if ($scheduleProfile === 'weekly_custom' && $effectiveServiceDays === []) {
        return back()->withErrors([
            'profile' => 'Select at least one service day for weekly custom schedules.',
        ])->withInput();
    }

    $slotDates = [];
    if ($singleDate !== '') {
        $slotDates[] = $singleDate;
    } else {
        $cursor = \Carbon\Carbon::parse($rangeFrom)->startOfDay();
        $last = \Carbon\Carbon::parse($rangeTo)->startOfDay();
        while ($cursor->lessThanOrEqualTo($last)) {
            $slotDates[] = $cursor->toDateString();
            $cursor->addDay();
        }
    }

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) ($validated['listing_category'] ?? ''));
    $normalizedListingCategory = $canonicalListingCategory ?? strtolower(trim((string) ($validated['listing_category'] ?? '')));
    $vendorPropertyId = filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null;
    $vendorServiceId = filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null;
    $vendorRoomCategoryId = filled($validated['vendor_room_category_id'] ?? null) ? (int) $validated['vendor_room_category_id'] : null;
    $routeName = trim((string) ($validated['route_name'] ?? ''));
    $freeNotes = trim((string) ($validated['notes'] ?? ''));

    $propertyCategoryFromTarget = null;
    if ($vendorPropertyId !== null) {
        $propertyRecord = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById($vendorPropertyId, $vendorUserId);
        if (!$propertyRecord) {
            return back()->withErrors(['profile' => 'Selected property is not valid for this vendor account.'])->withInput();
        }
        $propertyCategoryFromTarget = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    }

    $serviceCategoryFromTarget = null;
    if ($vendorServiceId !== null && Schema::hasTable('vendor_services')) {
        $serviceRecord = DB::table('vendor_services')
            ->select(['id', 'listing_category'])
            ->where('id', $vendorServiceId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();
        if (!$serviceRecord) {
            return back()->withErrors(['profile' => 'Selected service is not valid for this vendor account.'])->withInput();
        }
        $serviceCategoryFromTarget = vendorPortalCanonicalCategory((string) ($serviceRecord->listing_category ?? ''));
    }

    if ($vendorRoomCategoryId !== null) {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.'])->withInput();
        }

        $roomRecord = DB::table('vendor_property_room_categories')
            ->select(['id', 'vendor_property_id'])
            ->where('id', $vendorRoomCategoryId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();
        if (!$roomRecord) {
            return back()->withErrors(['profile' => 'Selected room category is not valid for this vendor account.'])->withInput();
        }

        if ($vendorPropertyId === null && isset($roomRecord->vendor_property_id)) {
            $vendorPropertyId = (int) $roomRecord->vendor_property_id;
        }
        if ($normalizedListingCategory === '') {
            $normalizedListingCategory = 'accommodation';
        }
    }

    if ($normalizedListingCategory === '') {
        $normalizedListingCategory = $propertyCategoryFromTarget
            ?? $serviceCategoryFromTarget
            ?? '';
    }

    $appliedCount = 0;
    foreach ($slotDates as $slotDate) {
        $slotWeekday = (int) \Carbon\Carbon::parse($slotDate)->dayOfWeek;
        $isWeeklyProfile = in_array($scheduleProfile, ['weekly_6', 'weekly_3', 'weekly_custom'], true);
        if ($isWeeklyProfile && !in_array($slotWeekday, $effectiveServiceDays, true)) {
            continue;
        }

        $meta = array_filter([
            'listing_category' => $normalizedListingCategory,
            'vendor_property_id' => $vendorPropertyId,
            'vendor_service_id' => $vendorServiceId,
            'vendor_room_category_id' => $vendorRoomCategoryId,
            'route_name' => $routeName,
            'schedule_profile' => $scheduleProfile,
            'service_days' => $effectiveServiceDays,
            'text' => $freeNotes,
        ], static fn ($value) => !($value === null || $value === '' || $value === []));

        $encodedNotes = $freeNotes;
        if ($meta !== []) {
            $encodedNotes = json_encode($meta);
        }

        $matchAttributes = [
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => $vendorPropertyId,
            'slot_date' => $slotDate,
        ];
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $matchAttributes['vendor_service_id'] = $vendorServiceId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id')) {
            $matchAttributes['vendor_room_category_id'] = $vendorRoomCategoryId;
        }

        $updatePayload = [
            'inventory' => (int) $validated['inventory'],
            'is_closed' => (bool) ($validated['is_closed'] ?? false),
            'notes' => $encodedNotes,
            'updated_at' => now(),
            'created_at' => now(),
        ];
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $updatePayload['vendor_service_id'] = $vendorServiceId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id')) {
            $updatePayload['vendor_room_category_id'] = $vendorRoomCategoryId;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'listing_category')) {
            $updatePayload['listing_category'] = $normalizedListingCategory;
        }
        if (Schema::hasColumn('vendor_availability_slots', 'route_name')) {
            $updatePayload['route_name'] = $routeName;
        }

        DB::table('vendor_availability_slots')->updateOrInsert($matchAttributes, $updatePayload);
        $appliedCount++;
    }

    if ($appliedCount === 0) {
        return back()->withErrors([
            'profile' => 'No slots matched the selected schedule pattern. Adjust range or service days and try again.',
        ])->withInput();
    }

    $message = $appliedCount === 1
        ? 'Availability updated for 1 day.'
        : ('Availability updated for ' . $appliedCount . ' days.');

    return back()->with('portal_notice', $message);
});

Route::post('/portal/vendor/reservations/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    return back()->withErrors([
        'profile' => 'Reservations are customer-generated. Vendors can manage booking status and payments from the reservations dashboard.',
    ]);
});

Route::post('/portal/vendor/transport/tariff/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'tariff_mode' => ['required', Rule::in(['per_trip', 'hourly', 'daily', 'private_hire'])],
        'per_trip_rate' => ['nullable', 'numeric', 'min:0'],
        'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        'daily_rate' => ['nullable', 'numeric', 'min:0'],
        'private_hire_rate' => ['nullable', 'numeric', 'min:0'],
    ]);

    $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $validated['vendor_property_id'], $vendorUserId);
    if (!$property) {
        return back()->withErrors(['profile' => 'Selected transport listing was not found for this vendor account.'])->withInput();
    }

    $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
    if (!in_array($listingCategory, ['marine_transport', 'land_transport'], true)) {
        return back()->withErrors(['profile' => 'Tariff options can only be updated for transport listings.'])->withInput();
    }

    $tariffMode = (string) $validated['tariff_mode'];
    $rateByMode = [
        'per_trip' => (float) ($validated['per_trip_rate'] ?? 0),
        'hourly' => (float) ($validated['hourly_rate'] ?? 0),
        'daily' => (float) ($validated['daily_rate'] ?? 0),
        'private_hire' => (float) ($validated['private_hire_rate'] ?? 0),
    ];

    if (($rateByMode[$tariffMode] ?? 0) <= 0) {
        return back()->withErrors(['profile' => 'Provide a tariff amount greater than zero for the selected mode.'])->withInput();
    }

    $details = [];
    if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
        $decoded = json_decode((string) $property->listing_details, true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $details['transport_tariff_mode'] = $tariffMode;
    $details['per_trip_rate'] = $rateByMode['per_trip'];
    $details['hourly_rate'] = $rateByMode['hourly'];
    $details['daily_rate'] = $rateByMode['daily'];
    $details['private_hire_rate'] = $rateByMode['private_hire'];
    if (in_array($tariffMode, ['per_trip', 'hourly', 'daily'], true)) {
        $details['transport_pricing_model'] = $tariffMode;
    } else {
        $details['transport_pricing_model'] = 'per_trip';
    }

    $selectedRate = (float) ($rateByMode[$tariffMode] ?? 0);
    $tableName = vendorPortalCategoryStorageTable($listingCategory);
    if ($tableName !== null && Schema::hasTable($tableName)) {
        $colUpdate = ['details' => json_encode($details), 'updated_at' => now()];
        if (Schema::hasColumn($tableName, 'base_price')) {
            $colUpdate['base_price'] = $selectedRate;
        }
        DB::table($tableName)
            ->where('vendor_property_id', (int) $validated['vendor_property_id'])
            ->where('vendor_user_id', $vendorUserId)
            ->update($colUpdate);
    }

    return back()->with('portal_notice', 'Transport tariff updated.');
});

Route::post('/portal/vendor/transfer/rates/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'transfer_options' => ['required', 'array', 'min:1'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $property = \App\Support\VendorPropertyCompatibilityReader::loadOwnedPropertyById((int) $validated['vendor_property_id'], $vendorUserId);
    if (!$property) {
        return back()->withErrors(['profile' => 'Selected listing was not found for this vendor account.'])->withInput();
    }

    $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
    if (!in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
        return back()->withErrors(['profile' => 'Transfer rates can only be updated for accommodation or remote workspace listings.'])->withInput();
    }

    $details = [];
    if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
        $decoded = json_decode((string) $property->listing_details, true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $transferCatalog = vendorPortalTransferOptionCatalog();
    $configuredTransferOptions = collect(is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : [])
        ->map(static fn ($item): string => strtolower(trim((string) $item)))
        ->filter(static fn (string $item): bool => $item !== '')
        ->values()
        ->all();

    if ($configuredTransferOptions === []) {
        return back()->withErrors(['profile' => 'Set transfer options in listing setup before changing rates from operations.'])->withInput();
    }

    $submittedTransferOptions = collect($validated['transfer_options'] ?? [])
        ->map(static fn ($item): string => strtolower(trim((string) $item)))
        ->filter(static fn (string $item): bool => $item !== '')
        ->unique()
        ->values()
        ->all();

    $invalidTransferOptions = array_values(array_diff($submittedTransferOptions, $transferCatalog));
    if ($invalidTransferOptions !== []) {
        return back()->withErrors(['profile' => 'Transfer options must be selected from the allowed transfer catalog.'])->withInput();
    }

    $disallowedTransferOptions = array_values(array_diff($submittedTransferOptions, $configuredTransferOptions));
    if ($disallowedTransferOptions !== []) {
        return back()->withErrors(['profile' => 'Only transfer options configured in listing setup can be updated from operations.'])->withInput();
    }

    $submittedTransferRates = is_array($validated['transfer_rates'] ?? null)
        ? $validated['transfer_rates']
        : [];

    $currentTransferRates = is_array($details['transfer_rates'] ?? null)
        ? $details['transfer_rates']
        : [];
    $currentTransferRateMatrix = is_array($details['transfer_rate_matrix'] ?? null)
        ? $details['transfer_rate_matrix']
        : [];

    foreach ($submittedTransferOptions as $transferOption) {
        $candidateRate = $submittedTransferRates[$transferOption] ?? null;
        if (!is_numeric($candidateRate) || (float) $candidateRate <= 0) {
            return back()->withErrors(['profile' => 'Provide a transfer rate greater than zero for every selected transfer option.'])->withInput();
        }

        $currentTransferRates[$transferOption] = round((float) $candidateRate, 2);

        $existingMatrix = is_array($currentTransferRateMatrix[$transferOption] ?? null)
            ? $currentTransferRateMatrix[$transferOption]
            : [];
        $currentTransferRateMatrix[$transferOption] = [
            'local_adult_charge' => max(0, (float) ($existingMatrix['local_adult_charge'] ?? 0)),
            'local_child_charge' => max(0, (float) ($existingMatrix['local_child_charge'] ?? 0)),
            // Operations-level transfer update is a single per-pax rate.
            // Keep local matrix values untouched and sync the foreign adult value shown on property pages.
            'foreign_adult_charge' => round((float) $candidateRate, 2),
            'foreign_child_charge' => max(0, (float) ($existingMatrix['foreign_child_charge'] ?? 0)),
            'base_charge_local' => max(0, (float) ($existingMatrix['base_charge_local'] ?? ($details['transfer_base_local'] ?? 0))),
            'base_charge_foreign' => max(0, (float) ($existingMatrix['base_charge_foreign'] ?? ($details['transfer_base_foreign'] ?? 0))),
        ];
    }

    $details['listing_category'] = $listingCategory;
    $details['transfer_pricing_basis'] = 'per_pax';
    $details['transfer_options'] = array_values(array_unique($configuredTransferOptions));
    $details['transfer_rates'] = $currentTransferRates;
    $details['transfer_rate_matrix'] = $currentTransferRateMatrix;

    $tableName = vendorPortalCategoryStorageTable($listingCategory);
    if ($tableName !== null && Schema::hasTable($tableName)) {
        DB::table($tableName)
            ->where('vendor_property_id', (int) $validated['vendor_property_id'])
            ->where('vendor_user_id', $vendorUserId)
            ->update(['details' => json_encode($details), 'updated_at' => now()]);
    }

    if ($listingCategory === 'accommodation' && function_exists('vendorPortalSyncAccommodationStructuredData')) {
        vendorPortalSyncAccommodationStructuredData((int) $validated['vendor_property_id'], $vendorUserId, $details);
    }

    return back()->with('portal_notice', 'Transfer rates updated for availability and bookings.');
});

Route::post('/portal/vendor/reservations/{reservation}/status', function (Request $request, int $reservation) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_reservations')) {
        return back()->withErrors(['profile' => 'Vendor reservations table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
        'payment_status' => ['required', Rule::in(['unpaid', 'partially_paid', 'paid', 'refunded'])],
    ]);

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'status' => (string) $validated['status'],
            'payment_status' => (string) $validated['payment_status'],
            'updated_at' => now(),
        ]);

    return back()->with('portal_notice', 'Reservation status updated.');
});

Route::post('/portal/vendor/inquiries/{inquiry}/status', function (Request $request, int $inquiry) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'table' => ['required', Rule::in(['vendor_customer_inquiries', 'vendor_inquiries', 'customer_inquiries', 'vendor_messages'])],
        'status' => ['nullable', Rule::in(['open', 'pending', 'in_progress', 'replied', 'resolved', 'closed'])],
        'response' => ['nullable', 'string', 'max:3000'],
    ]);

    $table = (string) $validated['table'];
    if (!Schema::hasTable($table)) {
        return back()->withErrors(['profile' => 'Inquiry source table is not available.']);
    }

    $columns = Schema::getColumnListing($table);
    $idColumn = collect(['id', 'inquiry_id', 'message_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($idColumn === null) {
        return back()->withErrors(['profile' => 'Inquiry source table has no supported identifier column.']);
    }

    $query = DB::table($table)->where($idColumn, $inquiry);

    $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($vendorColumn !== null) {
        $query->where($vendorColumn, $vendorUserId);
    } else {
        $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
        if ($propertyColumn === null) {
            return back()->withErrors(['profile' => 'Unable to verify inquiry ownership for this account.']);
        }

        $vendorPropertyIds = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId)
            ->pluck('vendor_property_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($vendorPropertyIds->isEmpty()) {
            return back()->withErrors(['profile' => 'No vendor listings available to validate inquiry access.']);
        }

        $query->whereIn($propertyColumn, $vendorPropertyIds->all());
    }

    $updates = [];
    $statusColumn = collect(['status', 'inquiry_status', 'state'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($statusColumn !== null && filled($validated['status'] ?? null)) {
        $updates[$statusColumn] = (string) $validated['status'];
    }

    $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response', 'resolution_note'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($responseColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$responseColumn] = trim((string) $validated['response']);
    }

    $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($respondedAtColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$respondedAtColumn] = now();
    }

    if (in_array('updated_at', $columns, true)) {
        $updates['updated_at'] = now();
    }

    if ($updates === []) {
        return back()->withErrors(['profile' => 'No compatible inquiry fields found to update.']);
    }

    $updated = $query->update($updates);
    if ($updated < 1) {
        return back()->withErrors(['profile' => 'Inquiry update did not apply. Verify access and try again.']);
    }

    return back()->with('portal_notice', 'Inquiry updated successfully.');
});

Route::post('/portal/vendor/reviews/{review}/respond', function (Request $request, int $review) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'table' => ['required', Rule::in(['vendor_property_reviews', 'vendor_reviews', 'customer_reviews', 'property_reviews'])],
        'status' => ['nullable', Rule::in(['pending', 'approved', 'published', 'hidden', 'rejected', 'responded'])],
        'response' => ['nullable', 'string', 'max:3000'],
    ]);

    $table = (string) $validated['table'];
    if (!Schema::hasTable($table)) {
        return back()->withErrors(['profile' => 'Review source table is not available.']);
    }

    $columns = Schema::getColumnListing($table);
    $idColumn = collect(['id', 'review_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($idColumn === null) {
        return back()->withErrors(['profile' => 'Review source table has no supported identifier column.']);
    }

    $query = DB::table($table)->where($idColumn, $review);

    $vendorColumn = collect(['vendor_user_id', 'vendor_id', 'owner_user_id'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($vendorColumn !== null) {
        $query->where($vendorColumn, $vendorUserId);
    } else {
        $propertyColumn = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])->first(static fn ($column) => in_array($column, $columns, true));
        if ($propertyColumn === null) {
            return back()->withErrors(['profile' => 'Unable to verify review ownership for this account.']);
        }

        $vendorPropertyIds = \App\Support\VendorPropertyCompatibilityReader::loadVendorListings($vendorUserId)
            ->pluck('vendor_property_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($vendorPropertyIds->isEmpty()) {
            return back()->withErrors(['profile' => 'No vendor listings available to validate review access.']);
        }

        $query->whereIn($propertyColumn, $vendorPropertyIds->all());
    }

    $updates = [];
    $statusColumn = collect(['status', 'review_status', 'moderation_status'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($statusColumn !== null && filled($validated['status'] ?? null)) {
        $updates[$statusColumn] = (string) $validated['status'];
    }

    $responseColumn = collect(['vendor_response', 'response_text', 'reply_text', 'response'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($responseColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$responseColumn] = trim((string) $validated['response']);
    }

    $respondedAtColumn = collect(['responded_at', 'replied_at', 'response_at'])->first(static fn ($column) => in_array($column, $columns, true));
    if ($respondedAtColumn !== null && filled($validated['response'] ?? null)) {
        $updates[$respondedAtColumn] = now();
    }

    if (in_array('updated_at', $columns, true)) {
        $updates['updated_at'] = now();
    }

    if ($updates === []) {
        return back()->withErrors(['profile' => 'No compatible review fields found to update.']);
    }

    $updated = $query->update($updates);
    if ($updated < 1) {
        return back()->withErrors(['profile' => 'Review update did not apply. Verify access and try again.']);
    }

    return back()->with('portal_notice', 'Review response saved.');
});

Route::post('/portal/vendor/pricing/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_pricing_rules')) {
        return back()->withErrors(['profile' => 'Vendor pricing rules table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'rule_type' => ['required', Rule::in(['flat', 'percent', 'nightly', 'weekend_markup', 'demand_discount', 'promo_discount'])],
        'value' => ['required', 'numeric', 'min:0'],
        'starts_on' => ['nullable', 'date'],
        'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        'vendor_property_id' => ['nullable', 'integer', 'min:1'],
        'vendor_service_id' => ['nullable', 'integer', 'min:1'],
        'vendor_room_category_id' => ['nullable', 'integer', 'min:1'],
    ]);

    $vendorPropertyId = filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null;
    $vendorServiceId = filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null;
    $vendorRoomCategoryId = filled($validated['vendor_room_category_id'] ?? null) ? (int) $validated['vendor_room_category_id'] : null;

    if ($vendorPropertyId !== null) {
        $propertyExists = \App\Support\VendorPropertyCompatibilityReader::vendorOwnsProperty($vendorPropertyId, $vendorUserId);
        if (!$propertyExists) {
            return back()->withErrors(['profile' => 'Property ID is not valid for this vendor account.'])->withInput();
        }
    }

    if ($vendorServiceId !== null) {
        if (!Schema::hasTable('vendor_services')) {
            return back()->withErrors(['profile' => 'Services table is not ready. Run migrations first.'])->withInput();
        }

        $serviceExists = DB::table('vendor_services')
            ->where('id', $vendorServiceId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();
        if (!$serviceExists) {
            return back()->withErrors(['profile' => 'Service ID is not valid for this vendor account.'])->withInput();
        }
    }

    if ($vendorRoomCategoryId !== null) {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.'])->withInput();
        }

        $roomExists = DB::table('vendor_property_room_categories')
            ->where('id', $vendorRoomCategoryId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();
        if (!$roomExists) {
            return back()->withErrors(['profile' => 'Room category ID is not valid for this vendor account.'])->withInput();
        }
    }

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'vendor_service_id' => $vendorServiceId,
        'name' => trim((string) $validated['name']),
        'rule_type' => (string) $validated['rule_type'],
        'value' => (float) $validated['value'],
        'starts_on' => $validated['starts_on'] ?? null,
        'ends_on' => $validated['ends_on'] ?? null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_pricing_rules', 'vendor_room_category_id')) {
        $payload['vendor_room_category_id'] = $vendorRoomCategoryId;
    }

    DB::table('vendor_pricing_rules')->insert($payload);

    return back()->with('portal_notice', 'Pricing rule saved.');
});

Route::post('/portal/vendor/billing/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_billing_details')) {
        return back()->withErrors(['profile' => 'Vendor billing details table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'business_name' => ['required', 'string', 'max:190'],
        'tax_id' => ['nullable', 'string', 'max:120'],
        'billing_email' => ['required', 'email', 'max:190'],
        'payout_method' => ['required', Rule::in(['bank_transfer', 'mobile_wallet', 'manual'])],
        'beneficiary_name' => ['required', 'string', 'max:190'],
        'payout_reference' => ['nullable', 'string', 'max:190'],
        'bank_name' => ['nullable', 'string', 'max:190'],
        'swift_code' => ['nullable', 'string', 'max:20'],
        'bank_account_number' => ['required', 'string', 'max:60'],
        'bank_account_last4' => ['nullable', 'string', 'max:8'],
        'billing_street_name' => ['required', 'string', 'max:255'],
        'billing_country' => ['required', 'string', 'max:90'],
        'billing_state' => ['required', 'string', 'max:120'],
        'billing_city' => ['required', 'string', 'max:120'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
        'currency' => ['required', Rule::in(['MVR', 'USD'])],
        'invoice_prefix' => ['nullable', 'string', 'max:30'],
    ]);

    $streetName = trim((string) ($validated['billing_street_name'] ?? ''));
    $billingCity = trim((string) ($validated['billing_city'] ?? ''));
    $billingState = trim((string) ($validated['billing_state'] ?? ''));
    $billingCountry = trim((string) ($validated['billing_country'] ?? ''));
    $locationSuffix = implode(', ', array_values(array_filter([$billingCity, $billingState, $billingCountry], static fn (string $value): bool => $value !== '')));
    $composedAddress = trim($streetName . ($locationSuffix !== '' ? ', ' . $locationSuffix : ''));
    $manualAddress = trim((string) ($validated['billing_address'] ?? ''));
    $resolvedBillingAddress = $manualAddress !== '' ? $manualAddress : $composedAddress;
    $bankAccountNumber = trim((string) ($validated['bank_account_number'] ?? ''));

    $payload = [
        'business_name' => trim((string) $validated['business_name']),
        'tax_id' => trim((string) ($validated['tax_id'] ?? '')),
        'billing_email' => strtolower(trim((string) $validated['billing_email'])),
        'payout_method' => (string) $validated['payout_method'],
        'beneficiary_name' => trim((string) ($validated['beneficiary_name'] ?? '')),
        'payout_reference' => trim((string) ($validated['payout_reference'] ?? '')),
        'bank_name' => trim((string) ($validated['bank_name'] ?? '')),
        'bank_account_number' => $bankAccountNumber,
        'bank_account_last4' => trim((string) ($validated['bank_account_last4'] ?? '')),
        'billing_street_name' => $streetName,
        'billing_country' => $billingCountry,
        'billing_state' => $billingState,
        'billing_city' => $billingCity,
        'billing_address' => $resolvedBillingAddress,
        'currency' => strtoupper(trim((string) ($validated['currency'] ?? 'MVR'))),
        'invoice_prefix' => strtoupper(trim((string) ($validated['invoice_prefix'] ?? 'INV'))),
        'updated_at' => now(),
        'created_at' => now(),
    ];

    if ($payload['bank_account_last4'] === '' && $bankAccountNumber !== '') {
        $payload['bank_account_last4'] = substr($bankAccountNumber, -4);
    }

    foreach (['beneficiary_name', 'bank_account_number', 'billing_street_name', 'billing_country', 'billing_state', 'billing_city'] as $column) {
        if (!Schema::hasColumn('vendor_billing_details', $column)) {
            unset($payload[$column]);
        }
    }

    if (!Schema::hasColumn('vendor_billing_details', 'bank_account_last4')) {
        unset($payload['bank_account_last4']);
    }

    if (Schema::hasColumn('vendor_billing_details', 'swift_code')) {
        $payload['swift_code'] = strtoupper(trim((string) ($validated['swift_code'] ?? '')));
    }

    DB::table('vendor_billing_details')->updateOrInsert(
        [
            'vendor_user_id' => $vendorUserId,
        ],
        $payload
    );

    return back()->with('portal_notice', 'Billing details updated.');
});

Route::post('/portal/vendor/address/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_billing_details')) {
        return back()->withErrors(['profile' => 'Vendor billing details table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'billing_street_name' => ['required', 'string', 'max:255'],
        'billing_country' => ['required', 'string', 'max:90'],
        'billing_state' => ['required', 'string', 'max:120'],
        'billing_city' => ['required', 'string', 'max:120'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
    ]);

    $streetName = trim((string) $validated['billing_street_name']);
    $billingCountry = trim((string) $validated['billing_country']);
    $billingState = trim((string) $validated['billing_state']);
    $billingCity = trim((string) $validated['billing_city']);
    $manualAddress = trim((string) ($validated['billing_address'] ?? ''));
    $locationSuffix = implode(', ', array_values(array_filter([$billingCity, $billingState, $billingCountry], static fn (string $value): bool => $value !== '')));
    $resolvedAddress = $manualAddress !== '' ? $manualAddress : trim($streetName . ($locationSuffix !== '' ? ', ' . $locationSuffix : ''));

    $payload = [
        'billing_street_name' => $streetName,
        'billing_country' => $billingCountry,
        'billing_state' => $billingState,
        'billing_city' => $billingCity,
        'billing_address' => $resolvedAddress,
        'updated_at' => now(),
        'created_at' => now(),
    ];

    DB::table('vendor_billing_details')->updateOrInsert(
        ['vendor_user_id' => $vendorUserId],
        $payload
    );

    return back()->with('portal_notice', 'Address details updated.');
});

Route::post('/portal/vendor/password/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'current_password' => ['required', 'string', 'min:8'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if (!Hash::check((string) $validated['current_password'], (string) $vendorUser->password)) {
        return back()->withErrors([
            'profile' => 'Current password is incorrect.',
        ])->withInput();
    }

    $vendorUser->password = (string) $validated['password'];
    $vendorUser->save();

    return back()->with('portal_notice', 'Password updated successfully.');
});
