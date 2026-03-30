<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

if (!function_exists('vendorPortalCategoryMap')) {
    function vendorPortalCategoryMap(): array
    {
        return [
            'accommodation' => 'Accommodation',
            'transport' => 'Transports',
            'excursion' => 'Excursions',
            'remote_workspace' => 'Remote Workspaces',
            'resort_day_visit' => 'Resort Day Visits',
            'restaurant' => 'Restaurants',
            'vehicle_rental' => 'Vehicle Rentals',
        ];
    }
}

if (!function_exists('vendorPortalCategoryAliases')) {
    function vendorPortalCategoryAliases(): array
    {
        return [
            'accommodation' => 'accommodation',
            'accommodations' => 'accommodation',
            'transport' => 'transport',
            'transports' => 'transport',
            'excursion' => 'excursion',
            'excursions' => 'excursion',
            'remote_workspace' => 'remote_workspace',
            'remote_workspaces' => 'remote_workspace',
            'remoteworkspace' => 'remote_workspace',
            'remoteworkspaces' => 'remote_workspace',
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
        ];
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

        return Storage::disk('public')->put($relativePath, $binary);
    }
}

if (!function_exists('vendorPortalTransferOptionCatalog')) {
    function vendorPortalTransferOptionCatalog(): array
    {
        return [
            'car',
            'van',
            'ferry',
            'speedboat',
            'seaplane',
            'domestic_flight',
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
    function vendorPortalListingsBackResponse(string $message, int $wizardStep = 1)
    {
        $normalizedStep = max(1, min(4, $wizardStep));

        return back()
            ->with('portal_notice', $message)
            ->with('portal_active_panel', 'listings')
            ->with('listing_wizard_step', $normalizedStep);
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
        $transferRates = [];
        foreach ($transferOptionCatalog as $transferOptionKey) {
            $normalizedRate = vendorPortalNormalizedNumeric($submittedTransferRates[$transferOptionKey] ?? null);
            if ($normalizedRate !== null && $normalizedRate >= 0) {
                $transferRates[$transferOptionKey] = $normalizedRate;
            }
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
            $details['property_amenities'] = $propertyAmenities;
            $details['property_features'] = $propertyFeatures;
            $details['transfer_pricing_basis'] = 'per_pax';
            $details['transfer_options'] = $transferOptions;
            $details['transfer_rates'] = $transferRates;
        }

        if (in_array($listingCategory, ['transport', 'excursion', 'remote_workspace', 'resort_day_visit', 'restaurant', 'vehicle_rental'], true)) {
            $details['capacity_value'] = isset($validated['capacity_value']) ? (int) $validated['capacity_value'] : null;
        }

        if (in_array($listingCategory, ['excursion'], true)) {
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

        if ($listingCategory === 'excursion') {
            $details['excursion_duration_minutes'] = isset($validated['excursion_duration_minutes']) ? (int) $validated['excursion_duration_minutes'] : null;
            $details['excursion_difficulty'] = trim((string) ($validated['excursion_difficulty'] ?? ''));
            $details['excursion_type'] = trim((string) ($validated['excursion_type'] ?? ''));
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
        }

        if (in_array($listingCategory, ['transport', 'excursion', 'resort_day_visit', 'restaurant', 'vehicle_rental'], true)) {
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
            }
        }

        if ($listingCategory === 'excursion') {
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

        if (!Schema::hasTable('vendor_properties') || !Schema::hasColumn('vendor_properties', 'listing_details')) {
            return 0;
        }

        $property = DB::table('vendor_properties')
            ->where('id', $propertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->first(['listing_details']);

        if (!$property || !is_string($property->listing_details) || trim($property->listing_details) === '') {
            return 0;
        }

        $details = json_decode($property->listing_details, true);
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
        if ($resolvedPropertyId <= 0 || !Schema::hasTable('vendor_properties')) {
            return null;
        }

        $property = DB::table('vendor_properties')
            ->where('id', $resolvedPropertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->first(['listing_category', 'listing_details']);

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

        if ($vendorUserId <= 0 || $propertyId === null || $propertyId <= 0 || $requestedOption === '' || !Schema::hasTable('vendor_properties')) {
            return $result;
        }

        $property = DB::table('vendor_properties')
            ->where('id', $propertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->first(['listing_category', 'listing_details']);

        if (!$property) {
            return $result;
        }

        $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
        if (!in_array($listingCategory, ['accommodation', 'remote_workspace'], true)) {
            return $result;
        }

        $detailsRaw = (string) ($property->listing_details ?? '');
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
        $guestCount = max(1, $guests);

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
        ];

        if ($propertyId === null || $propertyId <= 0 || !Schema::hasTable('vendor_properties')) {
            return $breakdown;
        }

        $property = DB::table('vendor_properties')
            ->where('id', $propertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->first(['listing_category']);

        if (!$property) {
            return $breakdown;
        }

        $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
        $breakdown['listing_category'] = $listingCategory;
        if ($listingCategory !== 'accommodation') {
            return $breakdown;
        }

        $breakdown['applied'] = true;
        $serviceChargeRatePercent = 10.0;
        $serviceChargeTotal = round($subtotalAmount * ($serviceChargeRatePercent / 100), 2);
        $breakdown['service_charge_rate_percent'] = $serviceChargeRatePercent;
        $breakdown['service_charge_total'] = $serviceChargeTotal;

        $roomsCount = vendorPortalAccommodationRoomCount($vendorUserId, $propertyId);
        $breakdown['rooms_count'] = $roomsCount;

        if ($isForeigner) {
            $greenTaxRatePerPerson = $roomsCount >= 50 ? 12.0 : 6.0;
            $greenTaxTotal = round($greenTaxRatePerPerson * $guestCount, 2);
            $tgstRatePercent = 17.0;
            $tgstTotal = round($subtotalAmount * ($tgstRatePercent / 100), 2);

            $breakdown['green_tax_rate_per_person'] = $greenTaxRatePerPerson;
            $breakdown['green_tax_total'] = $greenTaxTotal;
            $breakdown['tgst_rate_percent'] = $tgstRatePercent;
            $breakdown['tgst_total'] = $tgstTotal;
        } else {
            $cgstRatePercent = 8.0;
            $cgstTotal = round($subtotalAmount * ($cgstRatePercent / 100), 2);

            $breakdown['cgst_rate_percent'] = $cgstRatePercent;
            $breakdown['cgst_total'] = $cgstTotal;
        }

        $totalTaxAmount = round(
            $breakdown['green_tax_total'] + $breakdown['tgst_total'] + $breakdown['cgst_total'],
            2
        );

        $breakdown['total_tax_amount'] = $totalTaxAmount;
        $breakdown['invoice_total_amount'] = round(
            $subtotalAmount + $breakdown['service_charge_total'] + $totalTaxAmount + $transferChargeAmount,
            2
        );

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

    if ($vendorUserId > 0) {
        if (Schema::hasTable('vendor_properties')) {
            $vendorProperties = DB::table('vendor_properties')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();

            $existingListingCategories = $vendorProperties
                ->map(static fn ($property) => vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')))
                ->filter(static fn ($category) => is_string($category) && $category !== '')
                ->values()
                ->all();
            if ($existingListingCategories !== []) {
                $selectedVendorCategories = array_values(array_unique(array_merge($selectedVendorCategories, $existingListingCategories)));
            }
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
    }

    return view('vendor-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_vendor_user', 'Vendor'),
        'vendorProfile' => [
            'name' => $vendorUser instanceof User ? (string) $vendorUser->name : (string) session('portal_vendor_user', 'Vendor'),
            'email' => $vendorUser instanceof User ? (string) $vendorUser->email : '',
            'phone' => ($vendorUser instanceof User && Schema::hasColumn('users', 'phone')) ? (string) ($vendorUser->phone ?? '') : '',
            'vendor_id' => $vendorUser instanceof User ? (string) ($vendorUser->portal_vendor_id ?? '') : '',
        ],
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
    ]);
});

Route::get('/vendor/overview', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor')->with('portal_active_panel', 'overview');
});

Route::get('/vendor/listings', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor')
        ->with('portal_active_panel', 'listings')
        ->with('listing_wizard_step', 1);
});

Route::get('/vendor/operations', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor#vendorAvailabilitySection')->with('portal_active_panel', 'reservations');
});

Route::get('/vendor/pricing', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor#vendorPricingSection')->with('portal_active_panel', 'reservations');
});

Route::get('/vendor/billing', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    return redirect('/vendor#vendorDailyCollectionSection')->with('portal_active_panel', 'billing');
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

    if (Schema::hasColumn('users', 'portal_service_categories')) {
        $vendorUser->portal_service_categories = json_encode($normalizedCategories);
    }

    if (Schema::hasColumn('users', 'vendor_onboarding_step')) {
        $vendorUser->vendor_onboarding_step = (int) ($validated['onboarding_step'] ?? 1);
    }

    $vendorUser->save();

    return back()->with('portal_notice', 'Vendor categories and onboarding step updated.');
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
        'contact_phone' => ['nullable', 'string', 'max:40'],
    ]);

    $vendorUser->name = trim((string) $validated['display_name']);
    if (Schema::hasColumn('users', 'phone')) {
        $vendorUser->phone = vendorNormalizePhoneNumber((string) ($validated['contact_phone'] ?? ''));
    }
    $vendorUser->save();

    session([
        'portal_vendor_user' => $vendorUser->name,
    ]);

    return back()->with('portal_notice', 'Profile settings updated successfully.');
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
        if (!Schema::hasTable('vendor_properties')) {
            return back()->withErrors(['profile' => 'Properties table is not ready. Run migrations first.']);
        }

        $propertyExists = DB::table('vendor_properties')
            ->where('id', (int) $entityId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();

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
            $storedBannerSizeBytes = (int) (Storage::disk('public')->size($bannerPath) ?? 0);
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

    return vendorPortalListingsBackResponse('Photos uploaded successfully.', 4);
});

Route::post('/portal/vendor/media/{media}/primary', function (int $media) {
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

    return vendorPortalListingsBackResponse('Primary photo updated.', 4);
});

Route::post('/portal/vendor/rooms/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_property_room_categories')) {
        return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
    }

    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'string', 'max:160'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:50'],
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
        'extra_person_price' => ['nullable', 'numeric', 'min:0'],
        'child_price' => ['nullable', 'numeric', 'min:0'],
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

    $propertyRecord = DB::table('vendor_properties')
        ->where('id', $vendorPropertyId)
        ->where('vendor_user_id', $vendorUserId)
        ->first(['id', 'listing_category']);

    if (!$propertyRecord) {
        return back()->withErrors(['profile' => 'Select a valid property owned by your vendor account.'])->withInput();
    }

    $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
    if ($propertyCategory !== 'accommodation') {
        return back()->withErrors(['profile' => 'Room categories can only be added under accommodation listings.'])->withInput();
    }

    $insertPayload = [
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => $vendorPropertyId,
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenityTokens),
        'base_price' => (float) ($validated['base_price'] ?? 0),
        'currency' => 'MVR',
        'created_at' => now(),
        'updated_at' => now(),
    ];

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
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $insertPayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $insertPayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $insertPayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
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
        'extra_person_price' => ['nullable', 'numeric', 'min:0'],
        'child_price' => ['nullable', 'numeric', 'min:0'],
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

    if (Schema::hasTable('vendor_properties') && isset($roomRecord->vendor_property_id)) {
        $propertyRecord = DB::table('vendor_properties')
            ->where('id', (int) $roomRecord->vendor_property_id)
            ->where('vendor_user_id', $vendorUserId)
            ->first(['listing_category']);
        $propertyCategory = vendorPortalCanonicalCategory((string) ($propertyRecord->listing_category ?? ''));
        if ($propertyCategory !== 'accommodation') {
            return back()->withErrors(['profile' => 'Only rooms under accommodation listings can be updated here.'])->withInput();
        }
    }

    $updatePayload = [
        'name' => trim((string) $validated['name']),
        'quantity' => (int) ($validated['quantity'] ?? 1),
        'max_occupancy' => (int) ($validated['max_occupancy'] ?? 1),
        'bed_type' => trim((string) ($validated['bed_type'] ?? '')),
        'amenities' => implode(', ', $roomAmenities),
        'base_price' => (float) ($validated['base_price'] ?? 0),
        'updated_at' => now(),
    ];

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
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
        $updatePayload['bathroom_type'] = $submittedBathroomType === '' ? null : $submittedBathroomType;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
        $updatePayload['bathroom_count'] = isset($validated['bathroom_count']) ? (int) $validated['bathroom_count'] : null;
    }
    if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
        $updatePayload['bathroom_amenities'] = implode(', ', $bathroomAmenityTokens);
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
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
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
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
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
    ]);

    $canonicalListingCategory = vendorPortalCanonicalCategory((string) $validated['listing_category']);
    if ($canonicalListingCategory === null) {
        return back()->withErrors(['profile' => 'Invalid listing category selected.'])->withInput();
    }

    $allowedForUser = vendorPortalSelectedCategories($vendorUser);
    if ($allowedForUser === []) {
        $allowedForUser = ['accommodation'];
        if ($vendorUser instanceof User && Schema::hasColumn('users', 'portal_service_categories')) {
            $vendorUser->portal_service_categories = json_encode($allowedForUser);
            $vendorUser->save();
        }
    }
    if (!in_array($canonicalListingCategory, $allowedForUser, true)) {
        $allowedForUser[] = $canonicalListingCategory;
        $allowedForUser = array_values(array_unique($allowedForUser));
        if ($vendorUser instanceof User && Schema::hasColumn('users', 'portal_service_categories')) {
            $vendorUser->portal_service_categories = json_encode($allowedForUser);
            $vendorUser->save();
        }
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
    if ($canonicalListingCategory === 'excursion' && $submittedExcursionType !== '') {
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

    $payload = [
        'vendor_user_id' => $vendorUserId,
        'name' => trim((string) $validated['name']),
        'property_type' => $resolvedPropertyType,
        'location' => $resolvedLocation,
        'description' => trim((string) ($validated['description'] ?? '')),
        'status' => 'active',
        'base_price' => $resolvedBasePrice,
        'currency' => 'MVR',
        'max_guests' => $normalizedMaxGuests,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_properties', 'listing_category')) {
        $payload['listing_category'] = $canonicalListingCategory;
    }
    if (Schema::hasColumn('vendor_properties', 'listing_details')) {
        $payload['listing_details'] = empty($propertyDetails) ? null : json_encode($propertyDetails);
    }
    if (Schema::hasColumn('vendor_properties', 'amenities')) {
        $payload['amenities'] = implode(', ', $selectedAmenityTokens);
    }

    DB::table('vendor_properties')->insert($payload);

    return vendorPortalListingsBackResponse('Property/service listing added.', 2);
});

Route::post('/portal/vendor/properties/{property}/update', function (Request $request, int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $propertyRecord = DB::table('vendor_properties')
        ->where('id', $property)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

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
        'excursion_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:1440'],
        'excursion_difficulty' => ['nullable', Rule::in(['easy', 'moderate', 'hard'])],
        'excursion_type' => ['nullable', 'string', 'max:80'],
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
        'status' => ['required', Rule::in(['active', 'inactive'])],
    ]);

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
    if ($canonicalListingCategory === 'excursion' && $submittedExcursionType !== '') {
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
        'status' => (string) $validated['status'],
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('vendor_properties', 'amenities')) {
        $updatePayload['amenities'] = implode(', ', $selectedAmenityTokens);
    }

    if ($canonicalListingCategory !== null) {
        if (Schema::hasColumn('vendor_properties', 'property_type')) {
            $updatePayload['property_type'] = vendorPortalPropertyTypeForCategory($canonicalListingCategory);
        }
        if (Schema::hasColumn('vendor_properties', 'listing_category')) {
            $updatePayload['listing_category'] = $canonicalListingCategory;
        }
        if (!empty($existingDetails) && Schema::hasColumn('vendor_properties', 'listing_details')) {
            $existingDetails['listing_category'] = $canonicalListingCategory;
            $updatePayload['listing_details'] = json_encode($existingDetails);
        }
    }

    DB::table('vendor_properties')
        ->where('id', $property)
        ->where('vendor_user_id', $vendorUserId)
        ->update($updatePayload);

    return vendorPortalListingsBackResponse('Property listing updated.', 2);
});

Route::post('/portal/vendor/properties/{property}/delete', function (int $property) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);

    DB::table('vendor_properties')
        ->where('id', $property)
        ->where('vendor_user_id', $vendorUserId)
        ->delete();

    return vendorPortalListingsBackResponse('Property listing removed.', 1);
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
    if ($vendorPropertyId !== null && Schema::hasTable('vendor_properties')) {
        $propertyRecord = DB::table('vendor_properties')
            ->select(['id', 'listing_category'])
            ->where('id', $vendorPropertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();
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
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
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

    $property = DB::table('vendor_properties')
        ->where('id', (int) $validated['vendor_property_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->first();
    if (!$property) {
        return back()->withErrors(['profile' => 'Selected transport listing was not found for this vendor account.'])->withInput();
    }

    $listingCategory = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
    if ($listingCategory !== 'transport') {
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
    DB::table('vendor_properties')
        ->where('id', (int) $validated['vendor_property_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'base_price' => $selectedRate,
            'listing_details' => json_encode($details),
            'updated_at' => now(),
        ]);

    return back()->with('portal_notice', 'Transport tariff updated.');
});

Route::post('/portal/vendor/transfer/rates/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'vendor_property_id' => ['required', 'integer', 'min:1'],
        'transfer_options' => ['required', 'array', 'min:1'],
        'transfer_options.*' => ['required', 'string', 'max:80'],
        'transfer_rates' => ['nullable', 'array'],
        'transfer_rates.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
    ]);

    $property = DB::table('vendor_properties')
        ->where('id', (int) $validated['vendor_property_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->first();
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

    foreach ($submittedTransferOptions as $transferOption) {
        $candidateRate = $submittedTransferRates[$transferOption] ?? null;
        if (!is_numeric($candidateRate) || (float) $candidateRate <= 0) {
            return back()->withErrors(['profile' => 'Provide a transfer rate greater than zero for every selected transfer option.'])->withInput();
        }

        $currentTransferRates[$transferOption] = round((float) $candidateRate, 2);
    }

    $details['listing_category'] = $listingCategory;
    $details['transfer_pricing_basis'] = 'per_pax';
    $details['transfer_options'] = array_values(array_unique($configuredTransferOptions));
    $details['transfer_rates'] = $currentTransferRates;

    DB::table('vendor_properties')
        ->where('id', (int) $validated['vendor_property_id'])
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'listing_details' => json_encode($details),
            'updated_at' => now(),
        ]);

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
        if (!Schema::hasTable('vendor_properties')) {
            return back()->withErrors(['profile' => 'Properties table is not ready. Run migrations first.'])->withInput();
        }

        $propertyExists = DB::table('vendor_properties')
            ->where('id', $vendorPropertyId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();
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
