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

Route::get('/', function () {
    $apiBase = workationApiBase();
    $homeHeroBackgroundUrl = portalHeroStoredValueForSlot('home');
    $hasManagedHomeHeroImage = $homeHeroBackgroundUrl !== '';

    if ($hasManagedHomeHeroImage) {
        $homeHeroBackgroundUrl = '/media/portal/hero/home';
    } else {
        $homeHeroBackgroundUrl = portalManagedMediaUrlFromPath($homeHeroBackgroundUrl) ?? $homeHeroBackgroundUrl;
    }

    if ($homeHeroBackgroundUrl === '') {
        $seasonalHeroPath = public_path('images/home-hero-seasonal.jpg');
        if (is_file($seasonalHeroPath)) {
            $homeHeroBackgroundUrl = '/images/home-hero-seasonal.jpg';
        }
    }

    // Keep home sidebar identical to category pages for uniform navigation.
    $homeTopCategoryLinks = collect([
        ['icon' => 'fa-solid fa-hotel', 'title' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas', 'url' => '/catalog/accommodation'],
        ['icon' => 'fa-solid fa-water', 'title' => 'Marine Transport', 'subtitle' => 'Speedboats & water transfers', 'url' => '/catalog/marine-transport'],
        ['icon' => 'fa-solid fa-van-shuttle', 'title' => 'Land Transport', 'subtitle' => 'Cars and ground transfers', 'url' => '/catalog/land-transport'],
        ['icon' => 'fa-solid fa-compass', 'title' => 'Excursion', 'subtitle' => 'Tours and activities', 'url' => '/catalog/excursion'],
        ['icon' => 'fa-solid fa-map-location-dot', 'title' => 'Blog', 'subtitle' => 'Travel stories and island picks', 'url' => '/blog'],
        ['icon' => 'fa-solid fa-laptop', 'title' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces', 'url' => '/catalog/remote_workspace'],
        ['icon' => 'fa-solid fa-object-group', 'title' => 'Conference Rooms', 'subtitle' => 'Meeting & event spaces', 'url' => '/catalog/conference_room'],
        ['icon' => 'fa-solid fa-umbrella-beach', 'title' => 'Resort Day Visit', 'subtitle' => 'Day-use resort offers', 'url' => '/catalog/resort_day_visit'],
        ['icon' => 'fa-solid fa-utensils', 'title' => 'Restaurant', 'subtitle' => 'Dining experiences', 'url' => '/catalog/restaurant'],
        ['icon' => 'fa-solid fa-car', 'title' => 'Vehicle Rental', 'subtitle' => 'Cars and local rentals', 'url' => '/catalog/vehicle_rental'],
    ]);

    $homePromoBanner = [
        'message' => '🎉 Offers & Promotions: Save up to 25% on selected stays and transfer bundles this week.',
        'url' => '/catalog/accommodation?sort=price_low_high',
        'cta' => 'View Promotions',
    ];

    $homeTrendingChips = collect(['Top Islands', 'Top Cities', 'Top Atolls', 'Newly Rising']);

    $homeCuratedDestinationImages = [
        'maafushi' => '/images/home/destinations/maafushi-island.svg',
        'maafushi_island' => '/images/home/destinations/maafushi-island.svg',
        'male' => '/images/home/destinations/male-city.svg',
        'male_city' => '/images/home/destinations/male-city.svg',
        'baa_atoll' => '/images/home/destinations/baa-atoll.svg',
        'ari_atoll' => '/images/home/destinations/ari-atoll.svg',
        'hulhumale' => '/images/home/destinations/hulhumale-seafront.svg',
        'hulhumale_seafront' => '/images/home/destinations/hulhumale-seafront.svg',
        'thulusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo' => '/images/home/destinations/thulusdhoo-island.svg',
        'thulhusdhoo_island' => '/images/home/destinations/thulusdhoo-island.svg',
        'ukulhas' => '/images/home/destinations/ukulhas-island.svg',
        'ukulhas_island' => '/images/home/destinations/ukulhas-island.svg',
        'dhigurah' => '/images/home/destinations/dhigurah-island.svg',
        'dhigurah_island' => '/images/home/destinations/dhigurah-island.svg',
    ];

    $homeDatabaseDestinationImages = [];
    $homeIslandDirectoryDisplayNames = [];
    if (Schema::hasTable('islands')) {
        // Island and atoll photos are always stored on the public disk
        // (Storage::disk('public')), not the portal managed media disk.
        // Resolve them the same way as islands-index.blade.php does.
        $resolveAtlasPhotoUrl = static function (string $photoPath): string {
            if ($photoPath === '') {
                return '';
            }
            // Full URLs (http/https) — return as-is (upgrade to https)
            if (str_starts_with($photoPath, 'https://')) {
                return $photoPath;
            }
            if (str_starts_with($photoPath, 'http://')) {
                return 'https://' . ltrim(substr($photoPath, 7), '/');
            }
            // Internal proxy routes
            if (str_starts_with($photoPath, '/media/')) {
                return $photoPath;
            }
            // Everything else is a relative path on the public disk
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', str_replace('\\', '/', $photoPath)), '/');
            try {
                return Storage::disk('public')->url($normalized);
            } catch (\Throwable $e) {
                return '';
            }
        };

        $islandRows = Cache::remember('home:island-photo-rows:v1', now()->addMinutes(20), static function () {
            return DB::table('islands')
                ->select(['name', 'slug', 'photo_path'])
                ->whereNotNull('photo_path')
                ->where('photo_path', '!=', '')
                ->limit(1500)
                ->get();
        });

        foreach ($islandRows as $row) {
            $imageUrl = $resolveAtlasPhotoUrl((string) ($row->photo_path ?? ''));
            if ($imageUrl === null || $imageUrl === '') {
                continue;
            }

            $displayName = trim((string) ($row->name ?? ''));

            $nameKey = portalNormalizeDestinationMediaKey((string) ($row->name ?? ''));
            $slugKey = portalNormalizeDestinationMediaKey((string) ($row->slug ?? ''));
            $islandNameKey = $nameKey !== '' ? portalNormalizeDestinationMediaKey((string) ($row->name ?? '') . ' island') : '';
            $cityNameKey = $nameKey !== '' ? portalNormalizeDestinationMediaKey((string) ($row->name ?? '') . ' city') : '';

            foreach ([$nameKey, $slugKey, $islandNameKey, $cityNameKey] as $candidateKey) {
                if ($candidateKey === '' || array_key_exists($candidateKey, $homeDatabaseDestinationImages)) {
                    continue;
                }
                $homeDatabaseDestinationImages[$candidateKey] = $imageUrl;
                if ($displayName !== '' && !array_key_exists($candidateKey, $homeIslandDirectoryDisplayNames)) {
                    $homeIslandDirectoryDisplayNames[$candidateKey] = $displayName;
                }
            }

            if (in_array($nameKey, ['male', 'malecity', 'male_city'], true) && $displayName !== '') {
                foreach (['male', 'malecity', 'male_city'] as $maleAlias) {
                    if (!array_key_exists($maleAlias, $homeDatabaseDestinationImages)) {
                        $homeDatabaseDestinationImages[$maleAlias] = $imageUrl;
                    }
                    if (!array_key_exists($maleAlias, $homeIslandDirectoryDisplayNames)) {
                        $homeIslandDirectoryDisplayNames[$maleAlias] = 'Male City';
                    }
                }
            }
        }
    }

    if (Schema::hasTable('atolls')) {
        $atollRows = Cache::remember('home:atoll-photo-rows:v1', now()->addMinutes(20), static function () {
            return DB::table('atolls')
                ->select(['name', 'slug', 'code', 'photo_path'])
                ->whereNotNull('photo_path')
                ->where('photo_path', '!=', '')
                ->limit(300)
                ->get();
        });

        foreach ($atollRows as $row) {
            $imageUrl = $resolveAtlasPhotoUrl((string) ($row->photo_path ?? ''));
            if ($imageUrl === null || $imageUrl === '') {
                continue;
            }

            $nameKey = portalNormalizeDestinationMediaKey((string) ($row->name ?? ''));
            $slugKey = portalNormalizeDestinationMediaKey((string) ($row->slug ?? ''));
            $atollNameKey = $nameKey !== '' ? portalNormalizeDestinationMediaKey((string) ($row->name ?? '') . ' atoll') : '';
            $codeKey = portalNormalizeDestinationMediaKey((string) ($row->code ?? ''));

            foreach ([$nameKey, $slugKey, $atollNameKey, $codeKey] as $candidateKey) {
                if ($candidateKey === '' || array_key_exists($candidateKey, $homeDatabaseDestinationImages)) {
                    continue;
                }
                $homeDatabaseDestinationImages[$candidateKey] = $imageUrl;
            }
        }
    }

    $homeDestinationMediaOverrides = collect();
    if (Schema::hasTable('portal_destination_media_overrides')) {
        $homeDestinationMediaOverrides = Cache::remember('home:destination-media-overrides:v1', now()->addMinutes(10), static function () {
            return DB::table('portal_destination_media_overrides')
                ->orderBy('destination_name')
                ->pluck('image_value', 'destination_key');
        });
    }

    $resolveHomeDestinationKey = static function (array $card): string {
        $candidates = [
            $card['title'] ?? null,
            $card['city'] ?? null,
            $card['location'] ?? null,
            $card['island'] ?? null,
            $card['atoll'] ?? null,
            $card['meta'] ?? null,
        ];

        $url = trim((string) ($card['url'] ?? ''));
        if ($url !== '') {
            $queryString = parse_url($url, PHP_URL_QUERY);
            if (is_string($queryString) && $queryString !== '') {
                parse_str($queryString, $queryParams);
                if (isset($queryParams['q'])) {
                    $candidates[] = $queryParams['q'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = portalNormalizeDestinationMediaKey(is_scalar($candidate) ? (string) $candidate : '');
            if ($normalized === '') {
                continue;
            }

            $normalized = str_replace(['male_city_city', 'city_male_city'], 'male_city', $normalized);

            $aliases = [
                'male' => 'male_city',
                'malecity' => 'male_city',
                'male_city' => 'male_city',
                'male_city_maldives' => 'male_city',
                'male_maldives' => 'male_city',
                'maldives_male_city' => 'male_city',
                'male_city_kaafu' => 'male_city',
                'male_town' => 'male_city',
                'male_capital' => 'male_city',
            ];

            if (array_key_exists($normalized, $aliases)) {
                return $aliases[$normalized];
            }

            if (str_contains($normalized, 'male_city') || $normalized === 'male_city') {
                return 'male_city';
            }

            return $normalized;
        }

        return '';
    };

    $resolveDestinationImageByKey = static function (string $destinationKey, array $firstSource, array $secondSource): ?string {
        $key = strtolower(trim($destinationKey));
        if ($key === '') {
            return null;
        }

        $variants = collect([
            $key,
            preg_replace('/_(island|atoll|city|maldives)$/', '', $key) ?? $key,
            str_replace('_island', '', $key),
            str_replace('_atoll', '', $key),
            str_replace('_city', '', $key),
            str_replace('_maldives', '', $key),
        ])->map(static fn ($value) => strtolower(trim((string) $value)))
            ->filter(static fn ($value) => $value !== '')
            ->unique()
            ->values();

        foreach ($variants as $variantKey) {
            if (array_key_exists($variantKey, $firstSource)) {
                $candidate = trim((string) ($firstSource[$variantKey] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        foreach ($variants as $variantKey) {
            if (array_key_exists($variantKey, $secondSource)) {
                $candidate = trim((string) ($secondSource[$variantKey] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    };

    $resolveHomeCuratedDestinationImage = static function (array $card) use ($homeCuratedDestinationImages, $homeDatabaseDestinationImages, $resolveHomeDestinationKey, $resolveDestinationImageByKey): ?string {
        $destinationKey = $resolveHomeDestinationKey($card);
        return $resolveDestinationImageByKey($destinationKey, $homeDatabaseDestinationImages, $homeCuratedDestinationImages);
    };

    $resolveHomePreferredDestinationArt = static function (array $card) use ($homeCuratedDestinationImages, $homeDatabaseDestinationImages, $resolveHomeDestinationKey, $resolveDestinationImageByKey): ?string {
        $destinationKey = $resolveHomeDestinationKey($card);
        return $resolveDestinationImageByKey($destinationKey, $homeCuratedDestinationImages, $homeDatabaseDestinationImages);
    };

    $resolveHomeDestinationOverrideImage = static function (array $card) use ($homeDestinationMediaOverrides, $resolveHomeDestinationKey): ?string {
        $destinationKey = $resolveHomeDestinationKey($card);
        if ($destinationKey === '') {
            return null;
        }

        $storedValue = trim((string) ($homeDestinationMediaOverrides[$destinationKey] ?? ''));
        if ($storedValue === '') {
            return null;
        }

        return portalManagedMediaUrlFromPath($storedValue) ?? null;
    };

    $applyHomeDestinationImages = static function ($cards) use ($resolveHomeDestinationOverrideImage, $resolveHomeCuratedDestinationImage, $resolveHomeDestinationKey) {
        return collect($cards)->map(function ($card) use ($resolveHomeDestinationOverrideImage, $resolveHomeCuratedDestinationImage, $resolveHomeDestinationKey) {
            if (!is_array($card)) {
                return $card;
            }

            $destinationKey = $resolveHomeDestinationKey($card);
            if ($destinationKey !== '') {
                $card['destination_key'] = $destinationKey;
            }

            $overrideImage = $resolveHomeDestinationOverrideImage($card);
            if ($overrideImage !== null && $overrideImage !== '') {
                $card['image_url'] = $overrideImage;
                $card['fallback_image_url'] = $overrideImage;

                return $card;
            }

            $hasPrimaryImage = trim((string) ($card['image_url'] ?? '')) !== '';
            $hasFallbackImage = trim((string) ($card['fallback_image_url'] ?? '')) !== '';
            if ($hasPrimaryImage || $hasFallbackImage) {
                return $card;
            }

            $curatedImage = $resolveHomeCuratedDestinationImage($card);
            if ($curatedImage !== null && $curatedImage !== '') {
                $card['image_url'] = $curatedImage;
                $card['fallback_image_url'] = $curatedImage;
            }

            return $card;
        })->values();
    };

    $applyHomeDestinationArtPreference = static function ($cards) use ($resolveHomeDestinationOverrideImage, $resolveHomePreferredDestinationArt) {
        return collect($cards)->map(function ($card) use ($resolveHomeDestinationOverrideImage, $resolveHomePreferredDestinationArt) {
            if (!is_array($card)) {
                return $card;
            }

            $overrideImage = $resolveHomeDestinationOverrideImage($card);
            if ($overrideImage !== null && $overrideImage !== '') {
                $card['image_url'] = $overrideImage;
                $card['fallback_image_url'] = $overrideImage;

                return $card;
            }

            $curatedImage = $resolveHomePreferredDestinationArt($card);
            if ($curatedImage !== null && $curatedImage !== '') {
                $card['image_url'] = $curatedImage;
                $card['fallback_image_url'] = $curatedImage;
            }

            return $card;
        })->values();
    };

    $homeBrowseCards = collect([
        ['title' => 'Stay Options', 'subtitle' => 'Hotels, villas, guesthouses', 'url' => '/catalog/accommodation'],
        ['title' => 'Marine Transport', 'subtitle' => 'Speedboat, ferry, water transfer', 'url' => '/catalog/marine-transport'],
        ['title' => 'Land Transport', 'subtitle' => 'Car, van, and island transfers', 'url' => '/catalog/land-transport'],
        ['title' => 'Experiences', 'subtitle' => 'Diving, snorkel, island tours', 'url' => '/catalog/excursion'],
        ['title' => 'Work-Friendly', 'subtitle' => 'Wi-Fi, desks, quiet corners', 'url' => '/catalog/remote_workspace'],
        ['title' => 'Conference Rooms', 'subtitle' => 'Meeting and event-ready spaces', 'url' => '/catalog/conference_room'],
        ['title' => 'Deals Zone', 'subtitle' => 'Promotions and last-minute value', 'url' => '/catalog/accommodation?sort=price_low_high'],
    ]);

    $homeTrendingCards = collect([
        ['title' => 'Maafushi Island', 'subtitle' => 'Most searched for affordable island escapes.', 'url' => '/catalog/accommodation?q=Maafushi', 'image_url' => '/images/home/destinations/maafushi-island.svg', 'fallback_image_url' => '/images/home/destinations/maafushi-island.svg'],
        ['title' => 'Male City', 'subtitle' => 'Convenient urban stays and transfer access.', 'url' => '/catalog/accommodation?q=Male', 'image_url' => '/images/home/destinations/male-city.svg', 'fallback_image_url' => '/images/home/destinations/male-city.svg'],
        ['title' => 'Baa Atoll', 'subtitle' => 'Nature-rich stays and iconic snorkeling spots.', 'url' => '/catalog/accommodation?q=Baa+Atoll', 'image_url' => '/images/home/destinations/baa-atoll.svg', 'fallback_image_url' => '/images/home/destinations/baa-atoll.svg'],
        ['title' => 'Ari Atoll', 'subtitle' => 'Popular for diving and premium island resorts.', 'url' => '/catalog/accommodation?q=Ari+Atoll', 'image_url' => '/images/home/destinations/ari-atoll.svg', 'fallback_image_url' => '/images/home/destinations/ari-atoll.svg'],
    ]);

    $homeWeekendDealCards = collect([
        ['title' => '2-Night Beach Stay', 'subtitle' => 'Weekend promo with breakfast included.', 'url' => '/catalog/accommodation?q=beach&sort=price_low_high'],
        ['title' => 'Stay + Transfer Bundle', 'subtitle' => 'Save when you combine stay and transport.', 'url' => '/catalog/marine-transport?sort=price_low_high'],
        ['title' => 'Family Weekend Pack', 'subtitle' => 'Room upgrade and activity credits included.', 'url' => '/catalog/accommodation?q=family&sort=most_wanted'],
        ['title' => 'Couple Escape Offer', 'subtitle' => 'Curated stay options for a quick retreat.', 'url' => '/catalog/accommodation?q=couple&sort=highest_reviews'],
    ]);

    $homeLovedCards = collect([
        ['title' => 'Hulhumale Seafront', 'subtitle' => 'Consistently high ratings for convenience.', 'url' => '/catalog/accommodation?q=Hulhumale', 'image_url' => '/images/home/destinations/hulhumale-seafront.svg', 'fallback_image_url' => '/images/home/destinations/hulhumale-seafront.svg'],
        ['title' => 'Thulusdhoo Island', 'subtitle' => 'Guest favorite for surf culture and charm.', 'url' => '/catalog/accommodation?q=Thulusdhoo', 'image_url' => '/images/home/destinations/thulusdhoo-island.svg', 'fallback_image_url' => '/images/home/destinations/thulusdhoo-island.svg'],
        ['title' => 'Ukulhas Island', 'subtitle' => 'Loved for clean beaches and relaxed stays.', 'url' => '/catalog/accommodation?q=Ukulhas', 'image_url' => '/images/home/destinations/ukulhas-island.svg', 'fallback_image_url' => '/images/home/destinations/ukulhas-island.svg'],
        ['title' => 'Dhigurah Island', 'subtitle' => 'Strong demand for reef and marine experiences.', 'url' => '/catalog/accommodation?q=Dhigurah', 'image_url' => '/images/home/destinations/dhigurah-island.svg', 'fallback_image_url' => '/images/home/destinations/dhigurah-island.svg'],
    ]);

    $homeTrendingCards = $applyHomeDestinationArtPreference($applyHomeDestinationImages($homeTrendingCards));
    $homeLovedCards = $applyHomeDestinationArtPreference($applyHomeDestinationImages($homeLovedCards));

    $homeDefaultDestinationImages = array_values(array_filter($homeCuratedDestinationImages, static fn ($img) => is_string($img) && trim($img) !== ''));
    $applyHomeImageSafetyFallback = static function ($cards) use ($homeDefaultDestinationImages) {
        return collect($cards)->values()->map(static function ($card, $index) use ($homeDefaultDestinationImages) {
            if (!is_array($card)) {
                return $card;
            }

            $primary = trim((string) ($card['image_url'] ?? ''));
            $fallback = trim((string) ($card['fallback_image_url'] ?? ''));
            if ($primary !== '' || $fallback !== '' || empty($homeDefaultDestinationImages)) {
                return $card;
            }

            $safeImage = (string) ($homeDefaultDestinationImages[$index % count($homeDefaultDestinationImages)] ?? '');
            if ($safeImage !== '') {
                $card['image_url'] = $safeImage;
                $card['fallback_image_url'] = $safeImage;
            }

            return $card;
        });
    };

    $homeTrendingCards = $applyHomeImageSafetyFallback($homeTrendingCards);
    $homeLovedCards = $applyHomeImageSafetyFallback($homeLovedCards);

    $homeListingMediaByProperty = collect();
    $homeTransportDestinationOptions = collect();

    {
        $allProperties = collect(Cache::remember('home:active-listings:v3', now()->addMinutes(15), static function () {
            return VendorPropertyCompatibilityReader::allActiveListings(1200)->values()->all();
        }));

        $propertyIds = $allProperties
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values();
        $propertyLookupIds = $allProperties
            ->flatMap(static fn ($property) => workationPropertyLookupIds($property))
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();
        $accommodationLookupIds = $allProperties
            ->filter(static function ($property): bool {
                return strtolower(trim((string) ($property->listing_category ?? ''))) === 'accommodation';
            })
            ->flatMap(static fn ($property) => workationPropertyLookupIds($property))
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();
        // Hydrate property base_price from the lowest valid room price so home/category
        // cards always show a real "From" value.
        if ($accommodationLookupIds->isNotEmpty()) {
            $combinedRoomPricesByProperty = collect();

            // Accommodation prices must come from room-level RO tables.
            // Reset stale vendor_properties.base_price values first.
            $allProperties = $allProperties->map(static function ($property) {
                $category = strtolower(trim((string) ($property->listing_category ?? '')));
                if ($category === 'accommodation') {
                    $property->base_price = 0;
                }

                return $property;
            })->values();

            $legacyRoomPropertyColumn = null;
            if (Schema::hasTable('vendor_property_room_categories')) {
                if (Schema::hasColumn('vendor_property_room_categories', 'vendor_property_id')) {
                    $legacyRoomPropertyColumn = 'vendor_property_id';
                } elseif (Schema::hasColumn('vendor_property_room_categories', 'property_id')) {
                    $legacyRoomPropertyColumn = 'property_id';
                }
            }

            if ($legacyRoomPropertyColumn !== null) {
                $legacyPriceColumns = [];
                foreach (['meal_plan_room_only_price', 'base_price'] as $candidateColumn) {
                    if (Schema::hasColumn('vendor_property_room_categories', $candidateColumn)) {
                        $legacyPriceColumns[] = $candidateColumn;
                    }
                }

                if (!empty($legacyPriceColumns)) {
                    $legacyRoomRows = DB::table('vendor_property_room_categories')
                        ->whereIn($legacyRoomPropertyColumn, $accommodationLookupIds->all())
                        ->get(array_merge([$legacyRoomPropertyColumn], $legacyPriceColumns));

                    $legacyRoomPrices = $legacyRoomRows
                        ->groupBy(static function ($row) use ($legacyRoomPropertyColumn) {
                            return (int) ($row->{$legacyRoomPropertyColumn} ?? 0);
                        })
                        ->map(static function ($rows) use ($legacyPriceColumns) {
                            return collect($rows)
                                ->flatMap(static function ($row) use ($legacyPriceColumns) {
                                    return collect($legacyPriceColumns)
                                        ->map(static fn ($column) => (float) ($row->{$column} ?? 0));
                                })
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                    $combinedRoomPricesByProperty = $combinedRoomPricesByProperty->union($legacyRoomPrices);
                }
            }

            if (Schema::hasTable('accommodation_rooms') && Schema::hasColumn('accommodation_rooms', 'property_id')) {
                $hasRoomActiveColumn = Schema::hasColumn('accommodation_rooms', 'is_active');
                $roomPriceColumns = ['property_id'];

                foreach (['base_price_per_night', 'base_price'] as $candidateColumn) {
                    if (Schema::hasColumn('accommodation_rooms', $candidateColumn)) {
                        $roomPriceColumns[] = $candidateColumn;
                    }
                }

                if (count($roomPriceColumns) > 1) {
                    $roomRows = DB::table('accommodation_rooms')
                        ->whereIn('property_id', $accommodationLookupIds->all())
                        ->when($hasRoomActiveColumn, static function ($query) {
                            $query->where(static function ($activeQuery) {
                                $activeQuery->where('is_active', 1)
                                    ->orWhereNull('is_active');
                            });
                        })
                        ->get($roomPriceColumns);

                    $canonicalRoomPrices = $roomRows
                        ->groupBy(static fn ($row) => (int) ($row->property_id ?? 0))
                        ->map(static function ($rows) {
                            return collect($rows)
                                ->map(static function ($row) {
                                    $nightly = isset($row->base_price_per_night) ? (float) $row->base_price_per_night : 0;
                                    $legacy = isset($row->base_price) ? (float) $row->base_price : 0;
                                    return $nightly > 0 ? $nightly : $legacy;
                                })
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                    foreach ($canonicalRoomPrices as $propertyLookupId => $candidatePrice) {
                        $normalizedPropertyLookupId = (int) $propertyLookupId;
                        $normalizedCandidatePrice = (float) $candidatePrice;
                        if ($normalizedPropertyLookupId <= 0 || $normalizedCandidatePrice <= 0) {
                            continue;
                        }

                        if ($combinedRoomPricesByProperty->has($normalizedPropertyLookupId)) {
                            $existingPrice = (float) ($combinedRoomPricesByProperty->get($normalizedPropertyLookupId) ?? 0);
                            $combinedRoomPricesByProperty->put(
                                $normalizedPropertyLookupId,
                                $existingPrice > 0 ? min($existingPrice, $normalizedCandidatePrice) : $normalizedCandidatePrice
                            );
                        } else {
                            $combinedRoomPricesByProperty->put($normalizedPropertyLookupId, $normalizedCandidatePrice);
                        }
                    }
                }
            }

            if ($combinedRoomPricesByProperty->isNotEmpty()) {
                $allProperties = $allProperties->map(static function ($property) use ($combinedRoomPricesByProperty) {
                    $category = strtolower(trim((string) ($property->listing_category ?? '')));
                    if ($category !== 'accommodation') {
                        return $property;
                    }

                    $lookupId = collect(workationPropertyLookupIds($property))
                        ->first(static fn (int $candidateId) => $combinedRoomPricesByProperty->has($candidateId));

                    if (is_int($lookupId) && $lookupId > 0) {
                        $property->base_price = (float) ($combinedRoomPricesByProperty->get($lookupId) ?? 0);
                    }

                    return $property;
                });
            }

            // Derive card price from listing details only for non-accommodation categories.
            $allProperties = $allProperties->map(static function ($property) {
                $existingPrice = (float) ($property->base_price ?? 0);
                if ($existingPrice > 0) {
                    return $property;
                }

                $category = strtolower(trim((string) ($property->listing_category ?? '')));
                if ($category === 'accommodation') {
                    return $property;
                }

                $derivedPrice = workationDerivedListingBasePrice($property);
                if ($derivedPrice > 0) {
                    $property->base_price = $derivedPrice;
                }

                return $property;
            })->values();
        }

        if (Schema::hasTable('vendor_listing_media') && $propertyLookupIds->isNotEmpty()) {
            $homeMediaCacheKey = 'home:property-media:v1:' . sha1(implode(',', $propertyLookupIds->all()));
            $mediaRows = Cache::remember($homeMediaCacheKey, now()->addMinutes(10), static function () use ($propertyLookupIds) {
                return DB::table('vendor_listing_media')
                    ->where('entity_type', 'property')
                    ->whereIn('entity_id', $propertyLookupIds->all())
                    ->orderByDesc('is_primary')
                    ->orderByDesc('created_at')
                    ->limit(1200)
                    ->get();
            });

            $mediaByEntityId = $mediaRows->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
            $homeListingMediaByProperty = $allProperties
                ->mapWithKeys(static function ($property) use ($mediaByEntityId) {
                    $canonicalId = (int) ($property->id ?? 0);
                    $dedicatedId = (int) ($property->dedicated_row_id ?? 0);

                    $mediaItems = collect($mediaByEntityId->get($canonicalId, collect()));
                    if ($mediaItems->isEmpty() && $dedicatedId > 0) {
                        $mediaItems = collect($mediaByEntityId->get($dedicatedId, collect()));
                    }

                    return [$canonicalId => $mediaItems];
                })
                ->filter(static fn ($items, $key) => (int) $key > 0);
        }

        $transportRows = $allProperties
            ->filter(static function ($row) {
                $category = strtolower(trim(str_replace('-', '_', (string) ($row->listing_category ?? ''))));
                return in_array($category, ['marine_transport', 'land_transport'], true);
            })
            ->take(2000)
            ->values();

        $transportDestinationMap = [];
        foreach ($transportRows as $row) {
            $candidates = [
                trim((string) (property_exists($row, 'pickup_location') ? $row->pickup_location : '')),
                trim((string) (property_exists($row, 'dropoff_location') ? $row->dropoff_location : '')),
                trim((string) (property_exists($row, 'origin_point') ? $row->origin_point : '')),
                trim((string) (property_exists($row, 'destination_point') ? $row->destination_point : '')),
                trim((string) (property_exists($row, 'island') ? $row->island : '')),
                trim((string) (property_exists($row, 'city') ? $row->city : '')),
                trim((string) (property_exists($row, 'atoll') ? $row->atoll : '')),
            ];

            foreach ($candidates as $candidate) {
                if ($candidate === '') {
                    continue;
                }

                $transportDestinationMap[strtolower($candidate)] = $candidate;
            }
        }

        if (!empty($transportDestinationMap)) {
            natcasesort($transportDestinationMap);
            $homeTransportDestinationOptions = collect(array_values($transportDestinationMap))->values();
        }

        $resolveDirectMediaUrl = static function ($media): ?string {
            $filePath = trim((string) ($media->file_path ?? ''));
            if ($filePath === '') {
                return null;
            }

            if (!str_starts_with($filePath, 'http://') && !str_starts_with($filePath, 'https://')) {
                return null;
            }

            $resolved = trim((string) $filePath);

            if (str_starts_with($resolved, 'http://')) {
                $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
            }

            return $resolved;
        };

        $resolvePropertyImage = static function (int $propertyId) use ($homeListingMediaByProperty, $resolveDirectMediaUrl): ?string {
            if ($propertyId <= 0) {
                return null;
            }

            $mediaItems = collect($homeListingMediaByProperty->get($propertyId, collect()));
            $primaryMedia = $mediaItems->first();
            if (!$primaryMedia) {
                return null;
            }

            $directUrl = $resolveDirectMediaUrl($primaryMedia);
            if ($directUrl !== null && $directUrl !== '') {
                return $directUrl;
            }

            $mediaId = (int) ($primaryMedia->id ?? 0);
            if ($mediaId > 0) {
                return '/media/vendor/' . $mediaId . '/thumb';
            }

            return null;
        };

        $resolvePropertyFallbackImage = static function (int $propertyId) use ($homeListingMediaByProperty, $resolveDirectMediaUrl): ?string {
            if ($propertyId <= 0) {
                return null;
            }

            $mediaItems = collect($homeListingMediaByProperty->get($propertyId, collect()));
            $primaryMedia = $mediaItems->first();
            if (!$primaryMedia) {
                return null;
            }

            $directUrl = $resolveDirectMediaUrl($primaryMedia);
            if ($directUrl !== null && $directUrl !== '') {
                return $directUrl;
            }

            $mediaId = (int) ($primaryMedia->id ?? 0);
            if ($mediaId > 0) {
                return '/media/vendor/' . $mediaId . '/banner';
            }

            return null;
        };

        $propertyLocationLabel = static function ($property): string {
            $island = trim((string) ($property->island ?? ''));
            $city = trim((string) ($property->city ?? ''));
            $atoll = trim((string) ($property->atoll ?? ''));

            if ($island !== '' && $atoll !== '') {
                return $island . ', ' . $atoll;
            }

            if ($island !== '') {
                return $island;
            }

            if ($city !== '') {
                return $city;
            }

            return $atoll;
        };

        $propertyLocationValue = static function ($property): string {
            $island = trim((string) ($property->island ?? ''));
            if ($island !== '') {
                return $island;
            }

            $city = trim((string) ($property->city ?? ''));
            if ($city !== '') {
                return $city;
            }

            return trim((string) ($property->atoll ?? ''));
        };

        if ($allProperties->isNotEmpty()) {
            $normalizeHomeCategoryKey = static function (?string $value): string {
                $normalized = str_replace('-', '_', strtolower(trim((string) $value)));

                return match ($normalized) {
                    'conference_rooms' => 'conference_room',
                    'excursions', 'experience', 'experiences', 'watersports', 'water_sports' => 'excursion',
                    'workspace', 'work_friendly', 'workfriendly' => 'remote_workspace',
                    'marine', 'marine_transfer', 'marine_transfers' => 'marine_transport',
                    'land', 'land_transfer', 'land_transfers' => 'land_transport',
                    default => $normalized,
                };
            };

            $categoryCounts = $allProperties
                ->groupBy(static fn ($property) => $normalizeHomeCategoryKey((string) ($property->listing_category ?? '')))
                ->filter(static fn ($group, $key) => $key !== '')
                ->map(static fn ($group) => $group->count());

            $homeCategoryPriceBucket = static function ($property) use ($normalizeHomeCategoryKey): string {
                $category = $normalizeHomeCategoryKey((string) ($property->listing_category ?? ''));
                if (in_array($category, ['marine_transport', 'land_transport'], true)) {
                    return $category;
                }

                $transportMode = strtolower(trim((string) ($property->transport_mode ?? '')));
                if ($transportMode === '') {
                    $decodedDetails = null;
                    if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                        $decoded = json_decode((string) $property->listing_details, true);
                        $decodedDetails = is_array($decoded) ? $decoded : null;
                    }
                    if (is_array($decodedDetails)) {
                        $transportMode = strtolower(trim((string) ($decodedDetails['transport_mode'] ?? '')));
                    }
                }
                $name = strtolower(trim((string) ($property->name ?? '')));
                $metaText = trim($transportMode . ' ' . $name);

                if ($category === 'transport') {
                    if (preg_match('/speed\\s*boat|ferry|boat|dhoni|yacht|launch|catamaran|seaplane/i', $metaText) === 1) {
                        return 'marine_transport';
                    }

                    if (preg_match('/car|van|taxi|bus|coach|bike|suv|land/i', $metaText) === 1) {
                        return 'land_transport';
                    }

                    return 'marine_transport';
                }

                if ($category === 'water_sports') {
                    return 'excursion';
                }

                return $normalizeHomeCategoryKey($category);
            };

            $categorySamples = $allProperties
                ->filter(static fn ($property) => trim((string) ($property->listing_category ?? '')) !== '')
                ->groupBy(static fn ($property) => $homeCategoryPriceBucket($property))
                ->map(static fn ($group) => $group->first());

            // Reuse the already hydrated active listing set to avoid a second expensive read.
            $homePricingListings = $allProperties;

            $categoryMinPriceRows = $homePricingListings
                ->map(static function ($property) use ($homeCategoryPriceBucket) {
                    $bucket = $homeCategoryPriceBucket($property);
                    $derivedPrice = (float) ($property->base_price ?? 0);
                    if ($derivedPrice <= 0 && $bucket !== 'accommodation') {
                        $derivedPrice = workationDerivedListingBasePrice($property);
                    }

                    return [
                        'bucket' => $bucket,
                        'price' => (float) $derivedPrice,
                        'currency' => strtoupper(trim((string) ($property->currency ?? 'MVR'))),
                    ];
                })
                ->filter(static fn (array $row) => $row['bucket'] !== '' && $row['price'] > 0)
                ->groupBy(static fn (array $row) => $row['bucket'])
                ->map(static function ($rows) {
                    return collect($rows)
                        ->sortBy(static fn (array $row) => (float) ($row['price'] ?? 0))
                        ->first();
                });

            // Accommodation home-card pricing must use the true lowest room-only price,
            // not sampled listing base_price values.
            $accommodationLookupIds = $homePricingListings
                ->filter(static fn ($property) => $homeCategoryPriceBucket($property) === 'accommodation')
                ->flatMap(static fn ($property) => workationPropertyLookupIds($property))
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values();

            if ($accommodationLookupIds->isNotEmpty()) {
                $accommodationPriceCandidates = collect();

                $legacyRoomPropertyColumn = null;
                if (Schema::hasTable('vendor_property_room_categories')) {
                    if (Schema::hasColumn('vendor_property_room_categories', 'vendor_property_id')) {
                        $legacyRoomPropertyColumn = 'vendor_property_id';
                    } elseif (Schema::hasColumn('vendor_property_room_categories', 'property_id')) {
                        $legacyRoomPropertyColumn = 'property_id';
                    }
                }

                if ($legacyRoomPropertyColumn !== null) {
                    $legacyPriceColumns = [];
                    foreach (['meal_plan_room_only_price', 'base_price'] as $candidateColumn) {
                        if (Schema::hasColumn('vendor_property_room_categories', $candidateColumn)) {
                            $legacyPriceColumns[] = $candidateColumn;
                        }
                    }

                    if (!empty($legacyPriceColumns)) {
                        $legacyRows = DB::table('vendor_property_room_categories')
                            ->whereIn($legacyRoomPropertyColumn, $accommodationLookupIds->all())
                            ->get(array_merge([$legacyRoomPropertyColumn], $legacyPriceColumns));

                        $legacyMin = collect($legacyRows)
                            ->flatMap(static function ($row) use ($legacyPriceColumns) {
                                return collect($legacyPriceColumns)
                                    ->map(static fn ($column) => (float) ($row->{$column} ?? 0));
                            })
                            ->filter(static fn (float $value) => $value > 0)
                            ->min();

                        if (is_numeric($legacyMin) && (float) $legacyMin > 0) {
                            $accommodationPriceCandidates->push((float) $legacyMin);
                        }
                    }
                }

                if (Schema::hasTable('accommodation_rooms') && Schema::hasColumn('accommodation_rooms', 'property_id')) {
                    $roomPriceColumns = [];
                    if (Schema::hasColumn('accommodation_rooms', 'base_price_per_night')) {
                        $roomPriceColumns[] = 'base_price_per_night';
                    }
                    if (Schema::hasColumn('accommodation_rooms', 'base_price')) {
                        $roomPriceColumns[] = 'base_price';
                    }

                    if (!empty($roomPriceColumns)) {
                        $roomRows = DB::table('accommodation_rooms')
                            ->whereIn('property_id', $accommodationLookupIds->all())
                            ->when(Schema::hasColumn('accommodation_rooms', 'is_active'), static function ($query) {
                                $query->where(static function ($activeQuery) {
                                    $activeQuery->where('is_active', 1)
                                        ->orWhereNull('is_active');
                                });
                            })
                            ->get(array_merge(['property_id'], $roomPriceColumns));

                        $roomMin = collect($roomRows)
                            ->map(static function ($row) {
                                $nightly = isset($row->base_price_per_night) ? (float) $row->base_price_per_night : 0;
                                $legacy = isset($row->base_price) ? (float) $row->base_price : 0;
                                return $nightly > 0 ? $nightly : $legacy;
                            })
                            ->filter(static fn (float $value) => $value > 0)
                            ->min();

                        if (is_numeric($roomMin) && (float) $roomMin > 0) {
                            $accommodationPriceCandidates->push((float) $roomMin);
                        }
                    }
                }

                $accommodationGlobalMin = $accommodationPriceCandidates
                    ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0)
                    ->min();

                if (is_numeric($accommodationGlobalMin) && (float) $accommodationGlobalMin > 0) {
                    $categoryMinPriceRows->put('accommodation', [
                        'bucket' => 'accommodation',
                        'price' => (float) $accommodationGlobalMin,
                        'currency' => 'MVR',
                    ]);
                }
            }

            $globalMinPriceRow = collect($categoryMinPriceRows->values())
                ->filter(static fn ($row) => is_array($row) && (float) ($row['price'] ?? 0) > 0)
                ->sortBy(static fn (array $row) => (float) ($row['price'] ?? 0))
                ->first();

            $homeTopCategoryLinks = $homeTopCategoryLinks->map(function (array $card) use ($categoryCounts) {
                $key = strtolower(trim((string) ($card['title'] ?? '')));
                $categoryHint = match ($key) {
                    'accommodation' => 'accommodation',
                    'marine transport' => 'marine_transport',
                    'land transport' => 'land_transport',
                    'excursions' => 'excursion',
                    'remote workspace' => 'remote_workspace',
                    'conference rooms' => 'conference_room',
                    'resort day visit' => 'resort_day_visit',
                    'restaurant' => 'restaurant',
                    'vehicle rental' => 'vehicle_rental',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $total = (int) ($categoryCounts[$categoryHint] ?? 0);
                if ($total > 0) {
                    $card['subtitle'] = $total . ' active listings';
                }

                return $card;
            })->values();

            $homeBrowseCards = $homeBrowseCards->map(function (array $card) use ($categoryCounts) {
                $categoryHint = match ($card['title']) {
                    'Stay Options' => 'accommodation',
                    'Marine Transport' => 'marine_transport',
                    'Land Transport' => 'land_transport',
                    'Experiences' => 'excursion',
                    'Work-Friendly' => 'remote_workspace',
                    'Conference Rooms' => 'conference_room',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $total = (int) ($categoryCounts[$categoryHint] ?? 0);
                if ($total > 0) {
                    $card['subtitle'] = $total . ' active listings available';
                }

                return $card;
            });

            $homeBrowseCards = $homeBrowseCards->map(function (array $card) use ($categorySamples, $categoryMinPriceRows, $globalMinPriceRow, $resolvePropertyImage, $resolvePropertyFallbackImage, $propertyLocationLabel) {
                $categoryHint = match ($card['title']) {
                    'Stay Options' => 'accommodation',
                    'Marine Transport' => 'marine_transport',
                    'Land Transport' => 'land_transport',
                    'Experiences' => 'excursion',
                    'Work-Friendly' => 'remote_workspace',
                    'Conference Rooms' => 'conference_room',
                    'Deals Zone' => 'accommodation',
                    default => null,
                };

                if ($categoryHint === null) {
                    return $card;
                }

                $sample = $categorySamples->get($categoryHint);
                if (!$sample) {
                    return $card;
                }

                $sampleId = (int) ($sample->id ?? 0);
                $card['image_url'] = $resolvePropertyImage($sampleId);
                $card['fallback_image_url'] = $resolvePropertyFallbackImage($sampleId);
                $location = $propertyLocationLabel($sample);
                if ($location !== '') {
                    $card['subtitle'] = $location;
                }

                unset($card['price_label']);

                $priceSource = null;
                if ($categoryHint === 'accommodation' && ($card['title'] ?? '') === 'Deals Zone' && is_array($globalMinPriceRow)) {
                    $priceSource = $globalMinPriceRow;
                } elseif (is_array($categoryMinPriceRows->get($categoryHint))) {
                    $priceSource = $categoryMinPriceRows->get($categoryHint);
                }

                if (is_array($priceSource) && (float) ($priceSource['price'] ?? 0) > 0) {
                    $currency = strtoupper(trim((string) ($priceSource['currency'] ?? 'MVR')));
                    $card['price_label'] = $currency . ' ' . number_format((float) ($priceSource['price'] ?? 0), 2);
                }

                return $card;
            })->values();
        }

        $propertyEngagementScore = static function ($property): float {
            $viewCount = (float) ($property->view_count ?? 0);
            $wishlistCount = (float) ($property->wishlist_count ?? 0);
            $bookingsCount = (float) ($property->bookings_count ?? $property->total_bookings ?? 0);
            $reviewCount = (float) ($property->reviews_count ?? $property->review_count ?? 0);
            $rating = (float) ($property->review_score ?? $property->average_rating ?? $property->rating ?? 0);

            return ($viewCount * 1.0)
                + ($wishlistCount * 3.0)
                + ($bookingsCount * 4.0)
                + ($reviewCount * 2.0)
                + ($rating * 5.0);
        };

        $canonicalLocationKey = static function (string $location): string {
            $normalized = portalNormalizeDestinationMediaKey($location);
            if ($normalized === '') {
                return '';
            }

            $normalized = str_replace(['male_city_city', 'city_male_city'], 'male_city', $normalized);
            $aliases = [
                'male' => 'male_city',
                'male_city' => 'male_city',
                'malecity' => 'male_city',
                'male_city_maldives' => 'male_city',
                'male_maldives' => 'male_city',
                'male_city_kaafu' => 'male_city',
                'city_male' => 'male_city',
                'mal_city' => 'male_city',
            ];

            if (array_key_exists($normalized, $aliases)) {
                return $aliases[$normalized];
            }

            if (str_contains($normalized, 'male_city')) {
                return 'male_city';
            }

            return $normalized;
        };

        $locationScores = [];
        foreach ($allProperties as $property) {
            $location = $propertyLocationValue($property);
            if ($location === '') {
                continue;
            }

            $normalizedLocation = $canonicalLocationKey($location);
            if ($normalizedLocation === '') {
                continue;
            }

            $displayLocation = trim((string) ($homeIslandDirectoryDisplayNames[$normalizedLocation] ?? ''));
            if ($displayLocation === '') {
                $displayLocation = $normalizedLocation === 'male_city' ? 'Male City' : $location;
            }
            $engagementScore = $propertyEngagementScore($property);

            $key = strtolower($normalizedLocation);
            if (!array_key_exists($key, $locationScores)) {
                $locationScores[$key] = [
                    'title' => $displayLocation,
                    'count' => 0,
                    'engagement_score' => 0.0,
                    'sample_property' => $property,
                ];
            }
            $locationScores[$key]['count']++;
            $locationScores[$key]['engagement_score'] += $engagementScore;

            if ($engagementScore > (float) ($locationScores[$key]['sample_score'] ?? -1)) {
                $locationScores[$key]['sample_property'] = $property;
                $locationScores[$key]['sample_score'] = $engagementScore;
            }
        }

        if (!empty($locationScores)) {
            uasort($locationScores, static function (array $a, array $b) {
                $scoreA = (float) ($a['engagement_score'] ?? 0);
                $scoreB = (float) ($b['engagement_score'] ?? 0);
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                return ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0));
            });
            $homeTrendingCards = collect(array_slice(array_values($locationScores), 0, 4))
                ->map(function (array $row) use ($resolvePropertyImage, $resolvePropertyFallbackImage) {
                    $sample = $row['sample_property'] ?? null;
                    $sampleId = (int) ($sample->id ?? 0);
                    $sampleCategory = strtolower(trim((string) ($sample->listing_category ?? 'accommodation')));
                    $samplePrice = $sample ? (float) ($sample->base_price ?? 0) : 0;
                    if ($samplePrice <= 0 && $sample) {
                        $samplePrice = max(0, workationDerivedListingBasePrice($sample));
                    }
                    $sampleCurrency = strtoupper(trim((string) ($sample->currency ?? 'MVR')));

                    $payload = [
                        'title' => $row['title'],
                        'subtitle' => $row['count'] . ' listings',
                        'url' => '/catalog/accommodation?q=' . urlencode($row['title']),
                        'image_url' => $resolvePropertyImage($sampleId),
                        'fallback_image_url' => $resolvePropertyFallbackImage($sampleId),
                        'category' => $sampleCategory,
                    ];

                    if ($samplePrice > 0) {
                        $payload['price_label'] = $sampleCurrency . ' ' . number_format($samplePrice, 2);
                    }

                    return $payload;
                })
                ->values();

            $homeTrendingCards = $applyHomeDestinationArtPreference($applyHomeDestinationImages($homeTrendingCards));
            $homeTrendingCards = $applyHomeImageSafetyFallback($homeTrendingCards);
        }

        $priceSorted = $allProperties
            ->filter(static fn ($property) => isset($property->base_price) && is_numeric($property->base_price) && (float) ($property->base_price ?? 0) > 0)
            ->sortBy(static fn ($property) => (float) $property->base_price)
            ->values();

        if ($priceSorted->isNotEmpty()) {
            $accommodationDeals = $priceSorted
                ->filter(static function ($property) {
                    return strtolower(trim((string) ($property->listing_category ?? ''))) === 'accommodation';
                })
                ->values();

            $weekendCandidates = $accommodationDeals;

            $homeWeekendDealCards = $weekendCandidates->take(4)->map(function ($property) use ($resolvePropertyImage, $resolvePropertyFallbackImage) {
                $name = trim((string) ($property->name ?? 'Weekend Offer'));
                $currency = strtoupper(trim((string) ($property->currency ?? 'MVR')));
                $price = number_format((float) ($property->base_price ?? 0), 2);
                $propertyId = (int) ($property->id ?? 0);
                $place = trim((string) ($property->island ?? ''));
                if ($place === '') {
                    $place = trim((string) ($property->atoll ?? ''));
                }

                return [
                    'title' => $name,
                    'subtitle' => $place,
                    'price_label' => $currency . ' ' . $price,
                    'url' => '/property/' . $propertyId,
                    'image_url' => $resolvePropertyImage($propertyId),
                    'fallback_image_url' => $resolvePropertyFallbackImage($propertyId),
                    'meta' => $place,
                ];
            })->values();

            $lowestPrice = (float) ($priceSorted->first()->base_price ?? 0);
            $homePromoBanner = [
                'message' => '🎉 Offers & Promotions: Trending deals now live across stays and services from MVR ' . number_format($lowestPrice, 2) . '.',
                'url' => '/catalog/accommodation?sort=price_low_high',
                'cta' => 'Explore Deals',
            ];
        }

        {
            $lovedRows = $allProperties
                ->sortByDesc(static function ($property) use ($propertyEngagementScore) {
                    $score = $propertyEngagementScore($property);
                    if ($score > 0) {
                        return $score;
                    }

                    return strtotime((string) ($property->updated_at ?? '')) ?: 0;
                })
                ->take(120)
                ->values();

            if ($lovedRows->isNotEmpty()) {
                $lovedDestinationCards = [];
                $seenLovedDestinations = [];

                foreach ($lovedRows as $property) {
                    $location = $propertyLocationValue($property);
                    $destinationKey = portalNormalizeDestinationMediaKey($location);
                    if ($location === '' || $destinationKey === '' || isset($seenLovedDestinations[$destinationKey])) {
                        continue;
                    }

                    $seenLovedDestinations[$destinationKey] = true;
                    $propertyId = (int) ($property->id ?? 0);
                    $propertyPrice = max(0, workationDerivedListingBasePrice($property));
                    $propertyCurrency = strtoupper(trim((string) ($property->currency ?? 'MVR')));

                    $cardPayload = [
                        'title' => $location,
                        'subtitle' => 'Popular Destination',
                        'url' => '/catalog/accommodation?q=' . urlencode($location),
                        'image_url' => $resolvePropertyImage($propertyId),
                        'fallback_image_url' => $resolvePropertyFallbackImage($propertyId),
                        'meta' => trim((string) ($property->atoll ?? '')),
                    ];

                    if ($propertyPrice > 0) {
                        $cardPayload['price_label'] = $propertyCurrency . ' ' . number_format($propertyPrice, 2);
                    }

                    $lovedDestinationCards[] = $cardPayload;

                    if (count($lovedDestinationCards) >= 4) {
                        break;
                    }
                }

                if ($lovedDestinationCards !== []) {
                    $homeLovedCards = collect($lovedDestinationCards)->values();
                }

                $homeLovedCards = $applyHomeDestinationArtPreference($applyHomeDestinationImages($homeLovedCards));
                $homeLovedCards = $applyHomeImageSafetyFallback($homeLovedCards);
            }
        }
    }

    $recentBlogPosts = collect();
    if (Schema::hasTable('blog_posts')) {
        $recentBlogPosts = collect(Cache::remember('home:recent-blog-posts:v1', now()->addMinutes(5), static function () {
            $posts = BlogPost::query()
                ->where('is_published', true)
                ->where(function ($query) {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(array_filter(['id', 'title', 'slug', 'excerpt', \Illuminate\Support\Facades\Schema::hasColumn('blog_posts', 'cover_image_url') ? 'cover_image_url' : null, 'cover_image_path', 'blog_category_slug', 'published_at', 'created_at']));

            if (function_exists('blogHydratePostsWithMeta')) {
                $posts = blogHydratePostsWithMeta($posts);
            }

            return $posts->values()->all();
        }));
    }

    return view('welcome', [
        'apiBase' => $apiBase,
        'homeHeroBackgroundUrl' => $homeHeroBackgroundUrl,
        'homeTopCategoryLinks' => $homeTopCategoryLinks,
        'homePromoBanner' => $homePromoBanner,
        'homeTrendingChips' => $homeTrendingChips,
        'homeBrowseCards' => $homeBrowseCards,
        'homeTrendingCards' => $homeTrendingCards,
        'homeWeekendDealCards' => $homeWeekendDealCards,
        'homeLovedCards' => $homeLovedCards,
        'homeTransportDestinationOptions' => $homeTransportDestinationOptions,
        'recentBlogPosts' => $recentBlogPosts,
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
